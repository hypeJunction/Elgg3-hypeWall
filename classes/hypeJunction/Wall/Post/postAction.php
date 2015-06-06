<?php

namespace hypeJunction\Wall\Post;

use ElggObject;
use hypeJunction\Controllers\Action;
use hypeJunction\Wall\Post;

/**
 * @property \ElggUser $poster           User making the post
 * @property Post      $post             Post created/being updated
 * @property int       $guid             GUID of an existing post
 * @property string    $status           Status message
 * @property string    $subtype          Subtype of the post object
 * @property int       $access_id        Post access
 * @property int       $container_guid   GUID of the container entity
 * @property string    $location         Location tag
 * @property int[]     $friend_guids     Friends tagged in a post
 * @property int[]     $attachment_guids Entities to attach to the post
 * @property int[]     $upload_guids     Files to attach to the post
 * @property string    $address          URL to attach to the post
 *
 */
class postAction extends Action {

	const CLASSNAME = __CLASS__;

	/**
	 * {@inheritdoc}
	 */
	public function setup() {
		parent::setup();

		$this->post = get_entity($this->guid);
		$this->poster = elgg_get_logged_in_user_entity();
		$this->status = ($this->status) ? strip_tags($this->status) : '';

		// GUIDs of friends that were tagged in the post
		if (!is_array($this->friend_guids)) {
			$this->friend_guids = string_to_tag_array((string) $this->friend_guids);
		}

		if (!is_array($this->attachment_guids)) {
			$this->attachment_guids = string_to_tag_array((string) $this->attachment_guids);
		}

		if (!is_array($this->upload_guids)) {
			$this->upload_guids = array();
		}

		$this->subtype = Post::SUBTYPE;

		$this->container = $this->poster;
		if ($this->container_guid) {
			$container = get_entity($this->container_guid);
			if ($container) {
				$this->container = $container;
			}
			if ($this->container->guid != $this->poster->guid) {
				$this->subtype = hypeWall()->config->getPostSubtype();
			}
		}

		// For underlying views to know who the container is
		elgg_set_page_owner_guid($this->container->guid);

		if ($this->poster->guid == $this->container_guid) {
			$this->title = elgg_echo('wall:post:status_update', array(elgg_echo('wall:byline', array($this->poster->name))));
		} else {
			$this->title = elgg_echo('wall:post:wall_to_wall', array(elgg_echo('wall:byline', array($this->poster->name))));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate() {
		if (!$this->container || !$this->container->canWriteToContainer($this->poster->guid, 'object', $this->subtype)) {
			throw new \hypeJunction\Exceptions\PermissionsException(elgg_echo('wall:error:container_permissions'));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function execute() {

		if (!$this->post) {
			if ($this->subtype == 'thewire' && is_callable('thewire_save_post')) {
				$guid = thewire_save_post($this->status, $this->poster->guid, $this->access_id, 0, 'wall');
				$this->post = get_entity($guid);
			} else {
				$this->post = new Post();
				$this->post->subtype = $this->subtype;
				$this->post->owner_guid = $this->poster->guid;
				$this->post->container_guid = $this->container->guid;
				$guid = $this->post->save();
			}
		}

	$this->post->title = $this->title;
	$this->post->description = $this->status;

		if (!$guid) {
			$this->result->addError(elgg_echo('wall:create:error'));
			return;
		}

		if (\hypeJunction\Integration::isElggVersionBelow('1.9.0')) {
			$river_id = add_to_river('river/object/hjwall/create', 'create', $this->poster->guid, $this->post->guid);
		} else {
			// Create a river entry for this wall post
			$river_id = elgg_create_river_item(array(
				'view' => 'river/object/hjwall/create',
				'action_type' => 'create',
				'subject_guid' => $this->post->getOwnerGUID(),
				'object_guid' => $this->post->getGUID(),
				'target_guid' => $this->post->getContainerGUID(),
			));
		}

		$this->post->origin = 'wall';

		$qualifiers = elgg_trigger_plugin_hook('extract:qualifiers', 'wall', array('source' => $this->post->description), array());

		if (count($qualifiers['hashtags'])) {
			$this->post->tags = $qualifiers['hashtags'];
		}

		if (count($qualifiers['usernames'])) {
			foreach ($qualifiers['usernames'] as $username) {
				$user = get_user_by_username($username);
				if (elgg_instanceof($user) && !in_array($user->guid, $this->friend_guids)) {
					$this->friend_guids[] = $user->guid;
				}
			}
		}

		// Add 'tagged_in' relationships
		// If the access level for the post is not set to private, also create a river item
		// with the access level specified in their settings by the tagged user
		if (!empty($this->friend_guids)) {
			foreach ($this->friend_guids as $friend_guid) {
				if (add_entity_relationship($friend_guid, 'tagged_in', $this->post->guid)) {
					if (!in_array($this->access_id, array(ACCESS_PRIVATE, ACCESS_LOGGED_IN, ACCESS_PUBLIC))) {
						$river_access_id = elgg_get_plugin_user_setting('river_access_id', $friend_guid, PLUGIN_ID);
						if (!is_null($river_access_id) && $river_access_id !== ACCESS_PRIVATE) {
							$river_id = elgg_create_river_item(array(
								'view' => 'river/relationship/tagged/create',
								'action_type' => 'tagged',
								'subject_guid' => $friend_guid,
								'object_guid' => $this->post->getGUID(),
								'target_guid' => $this->post->getContainerGUID(),
								'access_id' => $river_access_id,
							));
						}
					}
				}
			}
		}


		// Wall post access id is set to private, which means it should be visible only to the poster and tagged users
		// Creating a new ACL for that
		if ($this->access_id == ACCESS_PRIVATE && count($this->friend_guids)) {

			$members = $this->friend_guids;
			$members[] = $this->poster->guid;
			$members[] = $this->container->guid;

			$acl_id = \hypeJunction\Wall\AccessCollection::create($members);
			$this->post->access_id = $acl_id;
			$this->post->save();
		}


		if (!empty($this->attachment_guids)) {
			foreach ($this->attachment_guids as $attachment_guid) {
				add_entity_relationship($attachment_guid, 'attached', $this->post->guid);
			}
		}

		// files being uploaded via $_FILES
		$uploads = hypeApps()->uploader->handle('upload_guids');
		if ($uploads) {
			foreach ($uploads as $upload) {
				if ($upload->guid) {
					$this->upload_guids[] = $upload->guid;
				}
			}
		}

		if (!empty($this->upload_guids)) {
			foreach ($this->upload_guids as $upload_guid) {
				$upload = get_entity($upload_guid);
				$upload->description = $this->post->description;
				$upload->origin = 'wall';
				$upload->access_id = $this->post->access_id;
				$upload->container_guid = ($this->container->canWriteToContainer($this->poster->guid, 'object', 'file')) ? $this->container->guid : ELGG_ENTITIES_ANY_VALUE;
				$upload->save();
				add_entity_relationship($upload_guid, 'attached', $this->post->guid);
			}
		}

		$this->post->setLocation($this->location);
		$this->post->address = $this->address;

		if ($this->post->address && get_input('make_bookmark', false)) {

			$document = elgg_trigger_plugin_hook('extract:meta', 'wall', array('src' => $this->post->address));

			$bookmark = new ElggObject;
			$bookmark->subtype = "bookmarks";
			$bookmark->container_guid = ($this->container->canWriteToContainer($this->poster->guid, 'object', 'bookmarks')) ?
					$this->container->guid : ELGG_ENTITIES_ANY_VALUE;
			$bookmark->address = $this->post->address;
			$bookmark->access_id = $this->post->access_id;
			$bookmark->origin = 'wall';

			if (!$document) {
				$bookmark->title = $this->post->title;
				$bookmark->description = $this->post->description;
				$bookmark->tags = $this->post->tags;
			} else {
				$bookmark->title = filter_tags($document->meta->title);
				$bookmark->description = filter_tags($document->meta->description);
				$bookmark->tags = string_to_tag_array(filter_tags($document->meta->keywords));
			}

			$bookmark->save();
		}

		if ($this->post->save()) {
			$message = $this->post->formatMessage();
			$params = array(
				'entity' => $this->post,
				'user' => $this->poster,
				'message' => $message,
				'url' => $this->post->getURL(),
				'origin' => 'wall',
			);
			elgg_trigger_plugin_hook('status', 'user', $params);

			// Trigger a publish event, so that we can send out notifications
			elgg_trigger_event('publish', 'object', $this->post);

			if (get_input('widget')) {
				elgg_push_context('widgets');
			}

			if (elgg_is_xhr()) {
				$this->result->output .= elgg_list_river(array('object_guids' => $this->post->guid));
			}

			$this->result->addMessage(elgg_echo('wall:create:success'));
			$this->result->setForwardURL($this->post->getURL());
		} else {
			$this->result->addError(elgg_echo('wall:create:error'));
		}
	}

}
