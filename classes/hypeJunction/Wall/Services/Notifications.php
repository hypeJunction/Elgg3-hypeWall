<?php

namespace hypeJunction\Wall\Services;

class Notifications {

	const CLASSNAME = __CLASS__;
	
	/**
	 * Prepare a notification for when the wall post or wire is created
	 *
	 * @param string                          $hook         Equals 'prepare'
	 * @param string                          $type         Equals ''notification:publish:object:thewire' or 'notification:publish:object:hjwall'
	 * @param Elgg_Notifications_Notification $notification Notification object
	 * @param array                           $params       Additional params
	 * @return Elgg_Notifications_Notification
	 */
	function formatMessage($hook, $type, $notification, $params) {

		$event = elgg_extract('event', $params);
		$entity = $event->getObject();
		$recipient = elgg_extract('recipient', $params);
		$language = elgg_extract('language', $params);
		$method = elgg_extract('method', $params);

		if (!$entity instanceof Post || $entity->origin != 'wall') {
			return $notification;
		}

		$poster = $entity->getOwnerEntity();
		$wall_owner = $entity->getContainerEntity();

		$target = elgg_echo("wall:target:{$entity->getSubtype()}");

		if ($poster->guid == $wall_owner->guid) {
			$ownership = elgg_echo('wall:ownership:own', array($target), $language);
		} else if ($wall_owner->guid == $recipient->guid) {
			$ownership = elgg_echo('wall:ownership:your', array($target), $language);
		} else {
			$ownership = elgg_echo('wall:ownership:owner', array($wall_owner->name, $target), $language);
		}

		$notification->subject = elgg_echo('wall:new:notification:subject', array($poster->name, $ownership), $language);
		$notification->summary = elgg_view('output/url', array(
			'text' => elgg_echo('wall:new:notification:summary', array($ownership), $language),
			'href' => $entity->getURL(),
		));
		$notification->body = elgg_echo('wall:new:notification:message', array(
			$poster->name,
			$ownership,
			$entity->formatMessage(true),
			$entity->getURL()
				), $language);
	}

	/**
	 * Listen to the 'publish','object' event and send out notifications
	 * to interested users, as well as anyone tagged
	 *
	 * @param string     $event       Equals 'publish'
	 * @param string     $entity_type Equals 'object'
	 * @param ElggEntity $entity      Published entity
	 * @return boolean
	 */
	function sendCustomNotifications($event, $entity_type, $entity) {

		if (!$entity instanceof Post || $entity->origi !== 'wall') {
			return true;
		}

		$poster = $entity->getOwnerEntity();
		$container = $entity->getContainerEntity();
		$message = $entity->formatMessage(true);

		$sent = array(elgg_get_logged_in_user_guid(), $poster->guid, $container->guid);

		// Notify wall owner
		if ($poster->guid !== $container->guid && $container instanceof \ElggUser) {
			$to_guid = $container->guid;
			$from_guid = $poster->guid;

			$target = elgg_echo("wall:target:{$entity->getSubtype()}");
			$ownership = elgg_echo('wall:ownership:your', array($target));

			$subject = elgg_echo('wall:new:notification:subject', array($poster->name, $ownership));
			$summary = elgg_view('output/url', array(
				'text' => $subject,
				'href' => $entity->getURL(),
			));
			$body = elgg_echo('wall:new:notification:message', array(
				$poster->name,
				$ownership,
				$message,
				$entity->getURL()
			));

			notify_user($to_guid, $from_guid, $subject, $body, array(
				'summary' => $summary,
				'object' => $entity,
				'action' => 'received',
			));
		}

		// Notify tagged users
		$tagged_friends = $entity->getTaggedFriends();
		foreach ($tagged_friends as $tagged_friend) {
			// user tagged herself or the wall owner
			if ($tagged_friend->guid == $poster->guid || $tagged_friend->guid == $container->guid || in_array($tagged_friend->guid, $sent)) {
				continue;
			}

			$sent[] = $tagged_friend->guid;

			$to_guid = $tagged_friend->guid;
			$from_guid = $poster->guid;
			$subject = elgg_echo('wall:tagged:notification:subject', array($poster->name));
			$summary = elgg_view('output/url', array(
				'text' => $subject,
				'href' => $entity->getURL(),
			));
			$body = elgg_echo('wall:tagged:notification:message', array(
				$poster->name,
				$message,
				$entity->getURL()
			));

			notify_user($to_guid, $from_guid, $subject, $body, array(
				'summary' => $summary,
				'object' => $entity,
				'action' => 'tagged',
			));
		}

		return true;
	}

	/**
	 * Pre 1.9 notifications
	 *
	 * We want notifications to be more meaningful and include additional information,
	 * such as tags, attached entities etc. We will therefore ignore the default
	 * notification logic and build our own
	 * 
	 * @see \hypeJunction\Wall\Notifications::send
	 *
	 * @param string  $hook   Equals 'object:notifications'
	 * @param string  $type   Equals 'object'
	 * @param boolean $return Flag
	 * @param array   $params Additional params
	 * @return boolean Updated flag
	 */
	function disableDefaultHandlerLegacy($hook, $type, $return, $params) {

		$event = elgg_extract('event', $params);
		$object_type = elgg_extract('object_type', $params);
		$object = elgg_extract('object', $params);

		// We don't want the default notification handler to send out notifications when a wall post is made
		if ($object->origin == 'wall') {
			return true;
		}

		return $return;
	}

	/**
	 * Pre 1.9 notificatins
	 *
	 * Listen to the 'publish','object' event and send out notifications
	 * to interested users, as well as anyone tagged
	 *
	 * @param string      $event       Equals 'publish'
	 * @param string      $entity_type Equals 'object'
	 * @param \ElggEntity $entity      Published entity
	 * @return boolean
	 */
	function sendLegacy($event, $entity_type, $entity) {

		if (!$entity instanceof Post || $entity->origin != 'wall') {
			return true;
		}

		$poster = $entity->getOwnerEntity();
		$container = $entity->getContainerEntity();
		$message = $entity->formatMessage(true);

		$sent = array(elgg_get_logged_in_user_guid(), $poster->guid, $container->guid);

		// Notify wall owner
		if ($poster->guid !== $container->guid && $container instanceof \ElggUser) {
			$to = $container->guid;
			$from = $poster->guid;

			$target = elgg_echo("wall:target:{$entity->getSubtype()}");
			$ownership = elgg_echo('wall:ownership:your', array($target));

			$subject = elgg_echo('wall:new:notification:subject', array($poster->name, $ownership));
			$body = elgg_echo('wall:new:notification:message', array(
				$poster->name,
				$ownership,
				$message,
				$entity->getURL()
			));

			notify_user($to, $from, $subject, $body);
		}

		// Notify tagged users
		$tagged_friends = $entity->getTaggedFriends();
		foreach ($tagged_friends as $tagged_friend) {
			// user tagged herself or the wall owner
			if ($tagged_friend->guid == $poster->guid || $tagged_friend->guid == $container->guid || in_array($tagged_friend->guid, $sent)) {
				continue;
			}

			$sent[] = $tagged_friend->guid;

			$to = $tagged_friend->guid;
			$from = $poster->guid;
			$subject = elgg_echo('wall:tagged:notification:subject', array($poster->name));
			$body = elgg_echo('wall:tagged:notification:message', array(
				$poster->name,
				$message,
				$entity->getURL()
			));

			notify_user($to, $from, $subject, $body);
		}

		elgg_push_context('widgets');
		$default_msg_body = elgg_view_entity($entity, array('full_view' => false));
		elgg_pop_context();

		global $NOTIFICATION_HANDLERS;

		// Get users interested in content from this person and notify them
		// (Person defined by container_guid so we can also subscribe to groups if we want)
		foreach ($NOTIFICATION_HANDLERS as $method => $foo) {
			$interested_users = \ElggBatch('elgg_get_entities_from_relationship', array(
				'site_guids' => ELGG_ENTITIES_ANY_VALUE,
				'relationship' => 'notify' . $method,
				'relationship_guid' => $entity->container_guid,
				'inverse_relationship' => true,
				'type' => 'user',
				'limit' => false
			));

			foreach ($interested_users as $user) {
				if ($user instanceof \ElggUser && !$user->isBanned() && !in_array($user->guid, $sent)) {
					if (has_access_to_entity($entity, $user) && $entity->access_id != ACCESS_PRIVATE) {
						$body = elgg_trigger_plugin_hook('notify:entity:message', 'object', array(
							'entity' => $entity,
							'to_entity' => $user,
							'method' => $method), $default_msg_body);

						if ($body !== false) {
							notify_user($user->guid, $entity->container_guid, $subject, $body, null, array($method));
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Pre 1.9 notifications
	 *
	 * Formats notification message
	 *
	 * @param string $hook    "notify:entity:message"
	 * @param string $type    "object"
	 * @param string $message Notification message
	 * @param array  $params  Hook params
	 * @return string
	 */
	function formatMessageLegacy($hook, $type, $message, $params) {

		$entity = elgg_extract('entity', $params);
		$to_entity = elgg_extract('to_entity', $params);

		if (!$entity instanceof Post || $entity->origin !== 'wall') {
			return $message;
		}

		$poster = $entity->getOwnerEntity();
		$wall_owner = $entity->getContainerEntity();

		$target = elgg_echo("wall:target:{$entity->getSubtype()}");

		if ($poster->guid == $wall_owner->guid) {
			$ownership = elgg_echo('wall:ownership:own', array($target));
		} else if ($wall_owner->guid == $to_entity->guid) {
			$ownership = elgg_echo('wall:ownership:your', array($target));
		} else {
			$ownership = elgg_echo('wall:ownership:owner', array($wall_owner->name, $target));
		}

		return elgg_echo('wall:new:notification:message', array(
			$poster->name,
			$ownership,
			format_wall_message($entity, true),
			$entity->getURL()
		));
	}

}
