<?php

namespace hypeJunction\Wall;

use ElggUser;

/**
 * @access private
 */
class Permissions {

	/**
	 * Allow users to post on each other's walls
	 * Container here is the wall, and can be a user or group
	 *
	 * @param string  $hook   Equals 'container_permissions_check'
	 * @param string  $type   Equals 'object'
	 * @param boolean $return Current permission
	 * @param array   $params Additional params
	 * @return boolean Filtered permission
	 */
	public static function containerPermissionsCheck(\Elgg\Hook $hook) {
		$type = $hook->getType();

		$return = $hook->getValue();

		if ($hook instanceof \Elgg\Hook) {
			$type = $hook->getType();
			$return = $hook->getValue();
			$hook->getParams() = $hook->getParams();
		}
		$container = $hook->getParam('container');
		$user = $hook->getParam('user');
		$subtype = $hook->getParam('subtype');

		if ($subtype !== Post::SUBTYPE) {
			return $return;
		}

		if (!$container instanceof ElggUser) {
			return $return;
		}

		if (!$user instanceof ElggUser) {
			return $return;
		}

		if ($container->isFriend($user)) {
			return true;
		} else {
			$third_party_wall_global = \elgg_get_plugin_setting('third_party_wall', 'hypewall');
			$third_party_wall_user = \elgg_get_plugin_user_setting('third_party_wall', $container->guid, 'hypewall');

			if ($third_party_wall_global && $third_party_wall_user) {
				return true;
			}
		}

		return $return;
	}

}
