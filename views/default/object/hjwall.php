<?php

use hypeJunction\Wall\Post;

$entity = elgg_extract('entity', $vars);
if (!$entity instanceof Post) {
	return;
}

$params = $vars;

$poster = $entity->getOwnerEntity();

$message = $entity->formatMessage();
if ($message) {
	$params['content'] = elgg_format_element('div', [
		'class' => 'wall-message',
			], $message);
}

$attachments = $entity->formatAttachments();
if ($attachments) {
	$params['attachments'] = elgg_format_element('div', [
		'class' => 'wall-attachments',
			], $attachments);
}

if (elgg_extract('full_view', $vars, false)) {
	echo elgg_view('object/elements/full', $params);
} else {
	echo elgg_view('object/elements/summary', $params);
}
