<?php

$request = elgg_extract('request', $vars);
/* @var $request \Elgg\Request */

$post_guids = (array) $request->getParam('post_guids', []);

$user = $request->getUserParam('username');
if (!$user) {
	$user = $request->elgg()->session->getLoggedInUser();
}

elgg_entity_gatekeeper($user->guid);

elgg_push_collection_breadcrumbs('object', 'hjwall', $user);

$content = elgg_view('lists/wall', [
	'entity' => $user,
	'post_guids' => $post_guids,
		]);

if ($request->isXhr()) {
	echo $content;
} else {
	$layout = elgg_view_layout('default', [
		'content' => $content,
		'filter_id' => 'wall',
		'class' => 'elgg-river-layout',
	]);

	echo elgg_view_page(elgg_echo('wall'), $layout);
}
