<?php

use hypeJunction\Wall\Post;

/**
 * Outputs formatted wall message
 *
 * @uses $vars['entity']          Wall post
 * @uses $vars['include_address'] Include attached URL address
 */

$entity = elgg_extract('entity', $vars);

if (!$entity instanceof Post) {
	return true;
}

$status = elgg_view('output/longtext', [
	'value' => $entity->description,
	'class' => 'wall-status',
]);

if (elgg_view_exists('output/linkify')) {
	$status = elgg_view('output/linkify', [
		'value' => $status,
	]);
}

$message = [$status];

$address = $entity->address;
if ($address) {
	$include_address = elgg_extract('include_address', $vars, (strpos($status, $address) === false)) || !$status;
	if ($include_address) {
		$message[] = elgg_view('output/url', [
			'href' => $address,
			'class' => 'wall-attached-url',
		]);
	}
}

$tagged_friends = $entity->getTaggedFriends('links');
if ($tagged_friends) {
	$message[] = elgg_format_element('span', [
		'class' => 'wall-tagged-friends',
			], elgg_echo('wall:with', [implode(', ', $tagged_friends)]));
}

$location = $entity->getLocation();
if ($location) {
	$location = elgg_view('output/wall/location', [
		'value' => $location
	]);
	$message[] = elgg_format_element('span', [
		'class' => 'wall-tagged-location'
			], elgg_echo('wall:at', [$location]));
}

echo implode(' ', $message);
