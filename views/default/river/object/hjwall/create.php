<?php

use hypeJunction\Wall\Post;

$item = elgg_extract('item', $vars);
if (!$item instanceof ElggRiverItem) {
	return;
}

$object = $item->getObjectEntity();
if (!$object instanceof Post) {
	return;
}

$item_vars = [
	'summary' => $object->formatSummary(),
	'message' => $object->formatMessage(),
	'attachments' => $object->formatAttachments(),
];

$vars = array_merge($vars, $item_vars);

echo elgg_view('river/elements/layout', $vars);

echo elgg_view('notifier/view_listener', [
	'entity' => $object,
]);
