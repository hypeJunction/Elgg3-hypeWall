<?php

namespace hypeJunction\Wall;

use Elgg\Event;
use ElggMenuItem;
use ElggRiverItem;

/** @access private */
class Menus {

	/**
	 * @param Event $event Plugin event object
	 * @return ?array
	 */
	public static function entityMenuSetup(Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();
		$entity = elgg_extract('entity', $params);

		if (!$entity instanceof Post) {
			return $return;
		}

		$logged_in = elgg_get_logged_in_user_entity();
		if (elgg_get_relationships(['guid' => $logged_in->guid, 'relationship' => 'tagged_in', 'guid_two' => $entity->guid, 'count' => true])) {
			$return[] = ElggMenuItem::factory([
				'name' => 'remove_tag',
				'text' => elgg_echo('wall:remove_tag'),
				'title' => elgg_echo('wall:remove_tag'),
				'priority' => 800,
				'href' => "action/wall/remove_tag?guid=$entity->guid",
				'is_action' => true,
			]);
		}

		if ($entity->canEdit()) {
			$return[] = ElggMenuItem::factory([
				'name' => 'edit',
				'text' => elgg_echo('edit'),
				'title' => elgg_echo('wall:edit'),
				'priority' => 800,
				'href' => "wall/edit/$entity->guid",
			]);
		}

		if ($entity->canDelete()) {
			$return[] = ElggMenuItem::factory([
				'name' => 'delete',
				'text' => elgg_view_icon('delete'),
				'title' => elgg_echo('wall:delete'),
				'priority' => 900,
				'href' => "action/entity/delete?guid=$entity->guid",
				'is_action' => true,
				'confirm' => true,
			]);
		}

		return $return;
	}

	/**
	 * @param Event $event Plugin event object
	 * @return ?array
	 */
	public static function riverMenuSetup(Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();
		$item = elgg_extract('item', $params);

		if (!($item instanceof ElggRiverItem)) {
			return $return;
		}

		$object = $item->getObjectEntity();

		if (!$object instanceof Post) {
			return null;
		}

		$logged_in = elgg_get_logged_in_user_entity();
		if (elgg_get_relationships(['guid' => $logged_in->guid, 'relationship' => 'tagged_in', 'guid_two' => $object->guid, 'count' => true])) {
			$return[] = ElggMenuItem::factory([
				'name' => 'remove_tag',
				'text' => elgg_echo('wall:remove_tag'),
				'title' => elgg_echo('wall:remove_tag'),
				'priority' => 800,
				'href' => "action/wall/remove_tag?guid=$object->guid",
				'is_action' => true,
			]);
		}

		if ($object->canEdit()) {
			$return[] = ElggMenuItem::factory([
				'name' => 'edit',
				'text' => elgg_echo('edit'),
				'title' => elgg_echo('wall:edit'),
				'priority' => 800,
				'href' => "wall/edit/$object->guid",
			]);
		}

		if ($object->canDelete()) {
			$return[] = ElggMenuItem::factory([
				'name' => 'delete',
				'text' => elgg_view_icon('delete'),
				'title' => elgg_echo('wall:delete'),
				'priority' => 900,
				'href' => "action/entity/delete?guid=$object->guid",
				'is_action' => true,
				'confirm' => true,
			]);
		}

		return $return;
	}

	/**
	 * @param Event $event Plugin event object
	 * @return ?array
	 */
	public static function ownerBlockMenuSetup(Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();
		$entity = elgg_extract('entity', $params);

		if ($entity instanceof \ElggUser) {
			$return[] = ElggMenuItem::factory([
				'name' => 'wall',
				'text' => elgg_echo('wall'),
				'href' => "wall/owner/{$entity->username}",
			]);
		} else if ($entity instanceof \ElggGroup && $entity->wall_enable == 'yes') {
			$return[] = ElggMenuItem::factory([
				'name' => 'wall',
				'text' => elgg_echo('wall:groups'),
				'href' => "wall/group/{$entity->guid}",
			]);
		}

		return $return;
	}

	/**
	 * @param Event $event Plugin event object
	 * @return ?array
	 */
	public static function userHoverMenuSetup(Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();
		$entity = elgg_extract('entity', $params);

		if ($entity instanceof \ElggUser) {
			$return[] = ElggMenuItem::factory([
				'name' => 'wall',
				'text' => ($entity->canWriteToContainer(0, 'object', Post::SUBTYPE)) ? elgg_echo('wall:write') : elgg_echo('wall:view'),
				'href' => "wall/owner/{$entity->username}",
				'section' => 'action',
				'icon' => 'comments-o',
			]);
		}

		return $return;
	}

	/**
	 * @param Event $event Plugin event object
	 * @return ?array
	 */
	public static function setupCardMenu(Event $event) {
		$return = $event->getValue();
		$params = $event->getParams();

		$user = elgg_get_logged_in_user_entity();
		if (!$user) {
			return null;
		}

		$href = elgg_extract('href', $params);
		if (!$href) {
			return null;
		}

		$return[] = ElggMenuItem::factory([
			'name' => 'repost',
			'href' => elgg_http_add_url_query_elements("wall/owner/$user->username", [
				'address' => $href,
			]),
			'text' => elgg_view_icon('retweet'),
			'title' => elgg_echo('wall:repost'),
		]);

		return $return;
	}

	/**
	 * @param Event $event Plugin event object
	 * @return ?array
	 */
	public static function setupQuickLinks(Event $event) {
		$items = $event->getValue();

		$entity = $event->getEntityParam();
		if (!$entity || !$entity->canWriteToContainer(0, 'object', Post::SUBTYPE)) {
			return null;
		}

		$types = get_registered_entity_types('object');

		if (empty($types)) {
			return null;
		}

		foreach ($types as $type) {
			try {
				$url = elgg_generate_url("add:object:$type", [
					'guid' => $entity->guid,
				]);
			} catch (\Exception $ex) {
				$url = null;
			}

			if (!$url) {
				continue;
			}

			$items[] = ElggMenuItem::factory([
				'name' => $type,
				'href' => $url,
				'text' => elgg_echo("add:object:$type"),
			]);
		}

		return $items;
	}
}
