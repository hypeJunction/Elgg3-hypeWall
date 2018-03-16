<?php

use hypeJunction\Wall\Post;

$guid = elgg_extract('guid', $vars);

elgg_entity_gatekeeper($guid, 'object', Post::SUBTYPE);

$entity = get_entity($guid);
if (!$entity->canEdit()) {
	throw new \Elgg\EntityPermissionsException();
}

elgg_push_entity_breadcrumbs($entity);

$title = elgg_echo('wall:edit');

$form = elgg_view('framework/wall/container', [
	'entity' => $entity,
]);

$content =  elgg_format_element('div', [
	'class' => [
		'wall-component',
		'wall-to-wall',
	],
], $form);

$layout = elgg_view_layout('default', [
	'title' => $title,
	'content' => $content,
	'filter_id' => 'wall/edit',
	'class' => 'elgg-river-layout',
]);

echo elgg_view_page($title, $layout);

