<?php

if (elgg_is_active_plugin('hypeActivity')) {
	return;
}

echo elgg_view('framework/wall/group_module', $vars);