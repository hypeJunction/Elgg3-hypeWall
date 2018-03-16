<?php

$group = elgg_get_page_owner_entity();

if ($group->wall_enable !== "yes") {
	return;
}

elgg_push_context('wall');
elgg_push_context('widgets');

$content = elgg_view('lists/wall', [
	'entity' => $group,
	'options' => [
		'limit' => elgg_extract('limit', $vars, elgg_get_config('default_limit') ? : 10),
		'list_class' => 'wall-post-list wall-widget-list',
		'pagination' => false,
	],
		]);

$all_link = elgg_view('output/url', [
	'href' => elgg_generate_url('collection:object:hjwall:group', [
		'guid' => $group->guid,
	]),
	'text' => elgg_echo('link:view:all'),
		]);

$new_link = elgg_view('output/url', [
	'href' => elgg_generate_url('collection:object:hjwall:group', [
		'guid' => $group->guid,
	]),
	'text' => elgg_echo('wall:groups:post'),
		]);

echo elgg_view('groups/profile/module', [
	'title' => elgg_echo('wall:groups'),
	'content' => $content,
	'all_link' => $all_link,
	'add_link' => $new_link,
]);

elgg_pop_context();
elgg_pop_context();
