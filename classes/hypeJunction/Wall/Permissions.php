<?php

namespace hypeJunction\Wall;

use ElggUser;

/** @access private */
class Permissions {

	/**
	 * @param \Elgg\Event $event Plugin event object
	 * @return ?bool
	 */
	public static function containerPermissionsCheck(\Elgg\Event $event): ?bool {
		$return = $event->getValue();
		$params = $event->getParams();
		$container = elgg_extract('container', $params);
		$user = elgg_extract('user', $params);
		$subtype = elgg_extract('subtype', $params);

		if ($subtype !== Post::SUBTYPE) {
			return $return;
		}

		if (!$container instanceof ElggUser) {
			return $return;
		}

		if (!$user instanceof ElggUser) {
			return $return;
		}

		if ((bool) elgg_get_relationships(['guid' => $container->guid, 'relationship' => 'friend', 'guid_two' => $user->guid, 'count' => true])) {
			return true;
		} else {
			$third_party_wall_global = elgg_get_plugin_setting('third_party_wall', 'hypewall');
			$third_party_wall_user = elgg_get_plugin_user_setting('third_party_wall', $container->guid, 'hypewall');

			if ($third_party_wall_global && $third_party_wall_user) {
				return true;
			}
		}

		return $return;
	}
}
