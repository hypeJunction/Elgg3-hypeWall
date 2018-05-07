<?php

namespace hypeJunction\Wall;

use ElggBatch;
use ElggObject;
use hypeJunction\BatchResult;
use hypeJunction\Data\Property;
use hypeJunction\Data\PropertyInterface;

class Post extends ElggObject {

	const TYPE = 'object';
	const SUBTYPE = 'hjwall';

	/**
	 * {@inheritdoc}
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();
		$this->attributes['subtype'] = self::SUBTYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDisplayName() {
		$owner = $this->getOwnerEntity();
		$container = $this->getContainerEntity();
		if ($owner->guid == $container->guid) {
			return elgg_echo('wall:post:status_update', [elgg_echo('wall:byline', [$owner->getDisplayName()])]);
		} else if ($owner) {
			return elgg_echo('wall:post:wall_to_wall', [elgg_echo('wall:byline', [$owner->getDisplayName()])]);
		}
		return parent::getDisplayName();
	}

	/**
	 * Formats wall message
	 *
	 * @param bool $include_address Include URL from the post
	 * @return string
	 */
	public function formatMessage($include_address = false) {
		$output = elgg_view('object/hjwall/elements/message', [
			'entity' => $this,
			'include_address' => $include_address,
		]);

		return elgg_trigger_plugin_hook('message:format', 'wall', ['entity' => $this], $output);
	}

	/**
	 * Prepare wall post attachments
	 * @return string|false
	 */
	public function formatAttachments() {

		$attachments = [];

		if ($this->address) {
			$attachments[] = elgg_view('output/wall/url', [
				'value' => $this->address,
			]);
		}

		$attachments[] = $this->html;

		$attachments[] = elgg_view('output/wall/attachments', [
			'entity' => $this,
		]);

		$attachments = array_filter($attachments);

		$output = (count($attachments)) ? implode('', $attachments) : false;
		return elgg_trigger_plugin_hook('attachments:format', 'wall', ['entity' => $this], $output);
	}

	/**
	 * Prepare wall river summary
	 * @return string
	 */
	public function formatSummary() {

		$subject = $this->getOwnerEntity();
		$wall_owner = $this->getContainerEntity();

		if ($wall_owner->guid == $subject->guid || $wall_owner->guid == elgg_get_page_owner_guid()) {
			$owned = true;
		}

		if (elgg_instanceof($wall_owner, 'group')) {
			$group_wall = true;
		}

		$summary[] = elgg_view('output/url', [
			'text' => $subject->name,
			'href' => $subject->getURL(),
			'class' => 'elgg-river-subject',
		]);

		if ($this->address) {
			$summary[] = elgg_echo('wall:new:address');
		} else {
			$files = elgg_get_entities([
				'relationship' => 'attached',
				'relationship_guid' => $this->guid,
				'count' => true,
			]);
			if ($files) {
				$images = elgg_get_entities([
					'types' => 'object',
					'subtypes' => 'file',
					'metadata_name_value_pairs' => [
						'name' => 'simpletype', 'value' => 'image',
					],
					'relationship' => 'attached',
					'relationship_guid' => $this->guid,
					'count' => true,
				]);
				if ($files == $images) {
					$summary[] = elgg_echo('wall:new:images', [$images]);
				} else if (!$images) {
					$summary[] = elgg_echo('wall:new:items', [$files]);
				} else {
					$summary[] = elgg_echo('wall:new:attachments', [$images, $files - $images]);
				}
			} else if (!$owned && !$group_wall) {
				$summary[] = elgg_echo('wall:new:status');
			}
		}

		if (!$owned && !$group_wall) {
			$wall_owner_link = elgg_view('output/url', [
				'text' => $wall_owner->name,
				'href' => $wall_owner->getURL(),
				'class' => 'elgg-river-object',
			]);
			$summary[] = elgg_echo('wall:owner:suffix', [$wall_owner_link]);
		}

		$output = implode(' ', $summary);
		return elgg_trigger_plugin_hook('summary:format', 'wall', ['entity' => $this], $output);
	}

	/**
	 * Get attachments
	 *
	 * @param string $format links|icons or null for an array of entities
	 * @param size   $size   Icon size
	 * @return mixed
	 */
	public function getAttachments($format = null, $size = 'small') {

		$attachment_tags = [];

		$attachments = new ElggBatch('elgg_get_entities', [
			'relationship' => 'attached',
			'relationship_guid' => $this->guid,
			'limit' => false
		]);

		foreach ($attachments as $attachment) {
			if ($format == 'links') {
				$attachment_tags[] = elgg_view('output/url', [
					'text' => (isset($attachment->name)) ? $attachment->name : $attachment->title,
					'href' => $attachment->getURL(),
					'is_trusted' => true
				]);
			} else if ($format == 'icons') {
				$attachment_tags[] = elgg_view_entity_icon($attachment, $size, [
					'class' => 'wall-post-tag-icon',
					'use_hover' => false
				]);
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
	public function getTaggedFriends($format = null, $size = 'small') {

		$tagged_friends = [];

		$tags = new ElggBatch('elgg_get_entities', [
			'types' => 'user',
			'relationship' => 'tagged_in',
			'relationship_guid' => $this->guid,
			'inverse_relationship' => true,
			'limit' => false
		]);

		foreach ($tags as $tag) {
			if ($format == 'links') {
				$tagged_friends[] = elgg_view('output/url', [
					'text' => (isset($tag->name)) ? $tag->name : $tag->title,
					'href' => $tag->getURL(),
					'is_trusted' => true
				]);
			} else if ($format == 'icons') {
				$tagged_friends[] = elgg_view_entity_icon($tag, $size, [
					'class' => 'wall-post-tag-icon',
					'use_hover' => false
				]);
			} else {
				$tagged_friends[] = $tag;
			}
		}

		return $tagged_friends;
	}

	public static function getTaggedUsersProp(PropertyInterface $prop, Post $post) {
		return new BatchResult('elgg_get_entities', [
			'types' => 'user',
			'relationship' => 'tagged_in',
			'relationship_guid' => (int) $post->guid,
			'inverse_relationship' => true,
			'limit' => \hypeJunction\Graph\Graph::LIMIT_MAX,
		]);
	}

	public static function getAttachmentsProp(PropertyInterface $prop, Post $post) {
		return new BatchResult('elgg_get_entities', [
			'relationship' => 'attached',
			'relationship_guid' => (int) $post->guid,
			'limit' => \hypeJunction\Graph\Graph::LIMIT_MAX,
		]);
	}

	public static function getGraphAlias($hook, $type, $return, $params) {
		$return['object'][Post::SUBTYPE] = ':wall';
		return $return;
	}

	public static function getPostProperties($hook, $type, $return, $params) {

		$fields[] = 'location';
		$fields[] = 'address';
		$fields[] = 'tagged_users';
		$fields[] = 'attachments';

		$return[] = new Property('location', [
			'getter' => '\hypeJunction\Data\Values::getLocation',
			'setter' => '\hypeJunction\Data\Values::setLocation',
			'type' => 'string',
			'input' => 'location',
			'output' => 'location',
			'validation' => [
				'rules' => [
					'type' => 'location',
				]
			]
		]);

		$return[] = new Property('address', [
			'getter' => '\hypeJunction\Data\Values::getVerbatim',
			'setter' => '\hypeJunction\Data\Values::setVerbatim',
			'type' => 'url',
			'input' => 'url',
			'output' => 'url',
			'validation' => [
				'rules' => [
					'type' => 'url',
				]
			],
		]);

		$return[] = new Property('embed', [
			'attribute' => 'address',
			'getter' => '\hypeJunction\Data\Values::getUrlMetadata',
			'read_only' => true,
		]);

		$return[] = new Property('tagged_users', [
			'getter' => '\hypeJunction\Wall\Post::getTaggedUsersProp',
			'read_only' => true,
		]);

		$return[] = new Property('attachments', [
			'getter' => '\hypeJunction\Wall\Post::getAttachmentsProp',
			'read_only' => true,
		]);

		return $return;
	}

}
