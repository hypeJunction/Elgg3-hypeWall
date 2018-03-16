<?php

$request = elgg_extract('request', $vars);
/* @var $request \Elgg\Request */

$post_guids = (array) $request->getParam('post_guids', []);

$guid = $request->getParam('guid');

elgg_entity_gatekeeper($guid);

$group = $request->getEntityParam();

elgg_push_collection_breadcrumbs('object', 'hjwall', $group);

$content = elgg_view('lists/wall', [
	'entity' => $group,
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
