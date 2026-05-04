<?php

namespace hypeJunction\Wall;

$entity = elgg_extract('entity', $vars);

if (!$entity instanceof \ElggEntity) {
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
echo elgg_trigger_event_results('format:src', 'embed', $vars, $output);
