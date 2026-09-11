<?php

add_filter('pre_http_request', function ($pre, $args, $url) {
	$block = array(
		'connect.advancedcustomfields.com',
		'api.wordpress.org/plugins/update-check',
		'api.wordpress.org/core/version-check',
		'api.wordpress.org/themes/update-check',
	);

	foreach ($block as $needle) {
		if (strpos((string) $url, $needle) !== false) {
			return new WP_Error('http_request_blocked', 'Update check disabled');
		}
	}

	return $pre;
}, 10, 3);

add_filter('acf/settings/show_updates', '__return_false');
