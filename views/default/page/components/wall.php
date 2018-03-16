<?php

/**
 * Extension for page layout
 */
if (!elgg_is_logged_in()) {
	return;
}

$filter_id = elgg_extract('filter_id', $vars);

if (!elgg_in_context('activity') && $filter_id !== 'wall') {
	return;
}

$form = elgg_view('framework/wall/container', $vars);

if ($form) {
	echo elgg_format_element('div', [
		'class' => [
			'wall-component',
			elgg_in_context('activity') ? 'wall-river' : 'wall-to-wall',
		],
	], $form);
}
