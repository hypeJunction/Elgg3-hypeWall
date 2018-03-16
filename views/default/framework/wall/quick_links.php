<?php

$entity = elgg_extract('entity', $vars);
if (!$entity) {
	$entity = elgg_get_page_owner_entity();
}
if (!$entity) {
	return;
}

$menu = elgg_view_menu('wall:quick_links', [
	'entity' => $entity,
	'class' => 'elgg-menu-hover hidden',
	'id' => elgg_extract('id', $vars),
]);

echo $menu;


