<?php

namespace hypeJunction\Wall;

$url = elgg_extract('value', $vars);
$output = elgg_view('output/url', [
	'href' => $url,
	'text' => $url,
	'title' => 'oembed',
	'target' => '_blank'
]);

$vars['src'] = $url;
echo elgg_trigger_event_results('format:src', 'embed', $vars, $output);
