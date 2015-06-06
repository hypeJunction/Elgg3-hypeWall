<?php

namespace hypeJunction\Wall;

class Post extends \ElggObject {

	const CLASSNAME = __CLASS__;
	const TYPE = 'object';
	const SUBTYPE = 'hjwall';

	/**
	 * Initialize object attributes
	 * @return void
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();
		$this->attributes['subtype'] = self::SUBTYPE;
	}

	/**
	 * Formats wall message
	 *
	 * @param bool $include_address Include URL from the post
	 * @return string
	 */
	public function formatMessage($include_address = false) {
		$output = elgg_view('object/hjwall/elements/message', array(
			'entity' => $this,
			'include_address' => $include_address,
		));

		return elgg_trigger_plugin_hook('message:format', 'wall', array('entity' => $this), $output);
	}

	/**
	 * Prepare wall post attachments
	 * @return string|false
	 */
	function formatAttachments() {

		$attachments = array();

		if ($this->address) {
			$attachments[] = elgg_view('output/wall/url', array(
				'value' => $this->address,
			));
		}

		$attachments[] = $this->html;

		$attachments[] = elgg_view('output/wall/attachments', array(
			'entity' => $this,
		));

		return (count($attachments)) ? implode('', $attachments) : false;
	}

	/**
	 * Prepare wall river summary
	 * @return string
	 */
	function formatSummary() {

		$subject = $this->getOwnerEntity();
		$wall_owner = $this->getContainerEntity();

		if ($wall_owner->guid == $subject->guid || $wall_owner->guid == elgg_get_page_owner_guid()) {
			$owned = true;
		}

		if (elgg_instanceof($wall_owner, 'group')) {
			$group_wall = true;
		}

		$summary[] = elgg_view('output/url', array(
			'text' => $subject->name,
			'href' => $subject->getURL(),
			'class' => 'elgg-river-subject',
		));

		if ($this->address) {
			$summary[] = elgg_echo('wall:new:address');
		} else {
			$files = elgg_get_entities_from_relationship(array(
				'relationship' => 'attached',
				'relationship_guid' => $this->guid,
				'inverse_relationship' => true,
				'count' => true,
			));
			if ($files) {
				$images = elgg_get_entities_from_relationship(array(
					'types' => 'object',
					'subtypes' => 'file',
					'metadata_name_value_pairs' => array(
						'name' => 'simpletype', 'value' => 'image',
					),
					'relationship' => 'attached',
					'relationship_guid' => $this->guid,
					'inverse_relationship' => true,
					'count' => true,
				));
				if ($files == $images) {
					$summary[] = elgg_echo('wall:new:images', array($images));
				} else if (!$images) {
					$summary[] = elgg_echo('wall:new:items', array($files));
				} else {
					$summary[] = elgg_echo('wall:new:attachments', array($images, $files - $images));
				}
			} else if (!$owned && !$group_wall) {
				$summary[] = elgg_echo('wall:new:status');
			}
		}

		if (!$owned && !$group_wall) {
			$wall_owner_link = elgg_view('output/url', array(
				'text' => $wall_owner->name,
				'href' => $wall_owner->getURL(),
				'class' => 'elgg-river-object',
			));
			$summary[] = elgg_echo('wall:owner:suffix', array($wall_owner_link));
		}

		return implode(' ', $summary);
	}

	/**
	 * Get attachments
	 *
	 * @param string $format links|icons or null for an array of entities
	 * @param size   $size   Icon size
	 * @return mixed
	 */
	function getAttachments($format = null, $size = 'small') {

		$attachment_tags = array();

		$attachments = new \ElggBatch('elgg_get_entities_from_relationship', array(
			'relationship' => 'attached',
			'relationship_guid' => $this->guid,
			'inverse_relationship' => true,
			'limit' => false
		));

		foreach ($attachments as $attachment) {
			if ($format == 'links') {
				$attachment_tags[] = elgg_view('output/url', array(
					'text' => (isset($attachment->name)) ? $attachment->name : $attachment->title,
					'href' => $attachment->getURL(),
					'is_trusted' => true
				));
			} else if ($format == 'icons') {
				$attachment_tags[] = elgg_view_entity_icon($attachment, $size, array(
					'class' => 'wall-post-tag-icon',
					'use_hover' => false
				));
			} else {
				$attachment_tags[] = $attachment;
			}
		}

		return $attachment_tags;
	}

	/**
	 * Returns tagged friends
	 *
	 * @param string $format links|icons or null for an array of entities
	 * @param size   $size   Icon size
	 * @return mixed
	 */
	function getTaggedFriends($format = null, $size = 'small') {

		$tagged_friends = array();

		$tags = new \ElggBatch('elgg_get_entities_from_relationship', array(
			'types' => 'user',
			'relationship' => 'tagged_in',
			'relationship_guid' => $this->guid,
			'inverse_relationship' => true,
			'limit' => false
		));

		foreach ($tags as $tag) {
			if ($format == 'links') {
				$tagged_friends[] = elgg_view('output/url', array(
					'text' => (isset($tag->name)) ? $tag->name : $tag->title,
					'href' => $tag->getURL(),
					'is_trusted' => true
				));
			} else if ($format == 'icons') {
				$tagged_friends[] = elgg_view_entity_icon($tag, $size, array(
					'class' => 'wall-post-tag-icon',
					'use_hover' => false
				));
			} else {
				$tagged_friends[] = $tag;
			}
		}

		return $tagged_friends;
	}

}
