<?php

namespace hypeJunction\Wall;

$entity = elgg_extract('entity', $vars);

if (!elgg_instanceof($entity)) {
	return;
}

$url = $entity->getURL();
$output = elgg_view('output/url', [
	'href' => $url,
	'text' => $url,
	'title' => 'oembed',
	'target' => '_blank'
]);

$vars['src'] = $url;
echo elgg_trigger_plugin_hook('format:src', 'embed', $vars, $output);
