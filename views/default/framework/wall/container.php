<?php

$user = elgg_get_logged_in_user_entity();
if (!$user) {
	return;
}

$entity = elgg_extract('entity', $vars);

if ($entity instanceof \hypeJunction\Post\Post) {
	$user = $entity->getOwnerEntity();
} else {
	$container = elgg_extract('container', $vars, $entity);
	unset($entity);

	if (!$container) {
		$container = elgg_get_page_owner_entity();
	}
	if (!$container) {
		$container = $user;
	}
	if (!$container->canWriteToContainer(0, 'object', \hypeJunction\Wall\Post::SUBTYPE)) {
		return;
	}
}

$tabs = [
	'status' => [
		'text' => elgg_echo('wall:status'),
		'icon' => 'pencil',
		'selected' => get_input('wall_tab', 'status') === 'status',
		'content' => elgg_view_form('wall/status', [
			'id' => 'wall-form-status',
			'class' => [
				'wall-form',
				$entity ? 'wall-form-edit' : '',
				elgg_is_active_plugin('hypeLists') ? 'wall-has-lists-api' : '',
			],
		], $vars),
	],
];

$quick_links = elgg_view('framework/wall/quick_links', [
	'entity' => $container,
	'id' => 'wall-quick-links',
]);

if ($quick_links) {
	$tabs['quick_links'] = [
		'text' => elgg_echo('wall:quick_links') . elgg_view_icon('angle-down'),
		'icon' => 'plus-square',
		'selected' => false,
		'href' => '#wall-quick-links',
		'rel' => 'popup',
		'data-position' => json_encode([
			'my' => 'left top',
			'at' => 'left bottom',
			'collision' => 'fit fit',
		]),
		'class' => 'wall-quick-links-toggle',
	];
}

$tabs = elgg_trigger_plugin_hook('tabs', 'wall', $vars, [
	'tabs' => $tabs,
	'class' => 'wall-forms',
]);

echo elgg_view('page/components/tabs', $tabs);
echo $quick_links;

?>
<script>
	require(['framework/wall/container'], function (lib) {
		lib.init();
	});
</script>