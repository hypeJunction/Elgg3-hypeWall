<?php

use hypeJunction\Wall\Post;

$guid = elgg_extract('guid', $vars);

elgg_entity_gatekeeper($guid, 'object', Post::SUBTYPE);

$entity = get_entity($guid);

elgg_push_entity_breadcrumbs($entity, false);

$content = elgg_view('lists/wall', [
	'post_guids' => [$guid],
	'entity' => $entity->getContainerEntity(),
]);

$layout = elgg_view_layout('default', [
	'title' => $entity->getDisplayName(),
	'content' => $content,
	'filter_id' => 'wall/view',
	'class' => 'elgg-river-layout',
]);

echo elgg_view_page(elgg_get_excerpt($entity->description), $layout);

