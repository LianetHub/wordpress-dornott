<?php

//add option page
if (function_exists('acf_add_options_page')) {

	acf_add_options_page(array(
		'page_title' 	=> 'Настройки темы',
		'menu_title'	=> 'Настройки темы',
		'menu_slug' 	=> 'theme-general-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));
}


function my_acf_admin_head()
{
?>
	<style type="text/css">
		h2.hndle.ui-sortable-handle {
			background: #cfa144;
			color: #fff !important;
			-webkit-transition: all 0.25s;
			-o-transition: all 0.25s;
			transition: all 0.25s;
		}

		.acf-field.acf-accordion .acf-label.acf-accordion-title {
			background: #EBE9F5;
			transition: all 0.25s;
		}

		.acf-accordion .acf-accordion-title label {
			text-transform: uppercase;
			color: #000;
		}

		.acf-field p.description {
			color: #ffa500;
		}

		.acf-field-group {
			border: 1px solid #282D41 !important;
		}
	</style>
<?php
}

add_action('acf/input/admin_head', 'my_acf_admin_head');

if (!function_exists('dornott_front_page_id')) {
	function dornott_front_page_id()
	{
		return (int) get_option('page_on_front');
	}
}

if (!function_exists('dornott_sections_context_id')) {
	function dornott_sections_context_id()
	{
		if (function_exists('is_shop') && is_shop() && function_exists('wc_get_page_id')) {
			return (int) wc_get_page_id('shop');
		}

		if (function_exists('is_product') && is_product()) {
			return (int) get_queried_object_id();
		}

		if (is_front_page()) {
			return dornott_front_page_id();
		}

		$id = (int) get_queried_object_id();
		return $id ?: dornott_front_page_id();
	}
}

function dornott_normalize_url_path($path)
{
	$path = untrailingslashit((string) $path);
	return ($path === '') ? '/' : $path;
}

function dornott_url_path_is_home($path)
{
	$home_path = dornott_normalize_url_path((string) wp_parse_url(home_url('/'), PHP_URL_PATH));
	return dornott_normalize_url_path($path) === $home_path;
}

function dornott_current_page_has_section_anchor($anchor)
{
	$anchor = sanitize_title($anchor);
	if ($anchor === '') {
		return false;
	}

	$is_front = is_front_page();
	$is_shop = function_exists('is_shop') && is_shop();
	$is_product = function_exists('is_product') && is_product();
	$is_product_taxonomy = function_exists('is_product_taxonomy') && is_product_taxonomy();
	$has_inner_hero = $is_shop || $is_product || $is_product_taxonomy;
	$has_shared_stack = $is_front || $is_shop || $is_product || $is_product_taxonomy;

	$context_id = dornott_sections_context_id();
	$field_on = static function ($name) use ($context_id) {
		return $context_id > 0 && function_exists('get_field') && (bool) get_field($name, $context_id);
	};

	switch ($anchor) {
		case 'hero':
			return $is_front || $has_inner_hero;
		case 'about':
			return ($is_front || $is_shop) && $field_on('show_about');
		case 'catalog':
			return $is_front;
		case 'cert':
			return $is_front && $field_on('show_cert');
		case 'steps':
			return $has_shared_stack && $field_on('show_order_steps');
		case 'reviews':
			return $has_shared_stack && $field_on('show_reviews');
		case 'gift':
			return $has_shared_stack && $field_on('show_gift');
		case 'presentation':
			return $has_shared_stack && $field_on('show_presentation');
		case 'contacts':
			return $has_shared_stack && $field_on('show_contacts');
		default:
			return false;
	}
}

function dornott_resolve_section_anchor_url($url)
{
	$url = trim((string) $url);
	if ($url === '' || $url === '#') {
		return $url;
	}

	$parts = wp_parse_url($url);
	if (!is_array($parts) || empty($parts['fragment'])) {
		return $url;
	}

	$scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
	if (in_array($scheme, array('mailto', 'tel', 'javascript'), true)) {
		return $url;
	}

	$host = $parts['host'] ?? '';
	if ($host !== '') {
		$home_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
		if ($home_host !== '' && strcasecmp($host, $home_host) !== 0) {
			return $url;
		}
	}

	$fragment = $parts['fragment'];
	$path = $parts['path'] ?? '';
	$is_pure_hash = isset($url[0]) && $url[0] === '#';
	$points_to_home = $is_pure_hash || dornott_url_path_is_home($path);

	if (!$points_to_home) {
		return $url;
	}

	$home_hash_url = home_url('/#' . $fragment);

	// Inner pages also have #hero (page title). Menu "Главная" must keep going to the front page.
	if ($fragment === 'hero' && !$is_pure_hash) {
		return is_front_page() ? '#hero' : $home_hash_url;
	}

	if (dornott_current_page_has_section_anchor($fragment)) {
		return '#' . $fragment;
	}

	return $home_hash_url;
}

add_filter('acf/location/rule_types', function ($choices) {
	$choices['WooCommerce']['woo_page_shop'] = 'Страница каталога';
	return $choices;
});

add_filter('acf/location/rule_values/woo_page_shop', function ($choices) {
	return array('yes' => 'Да');
});

add_filter('acf/location/rule_match/woo_page_shop', function ($match, $rule, $screen) {
	$shop_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
	$post_id = isset($screen['post_id']) ? (int) $screen['post_id'] : 0;
	$is_shop = $shop_id > 0 && $post_id === $shop_id;

	return ($rule['operator'] === '!=') ? !$is_shop : $is_shop;
}, 10, 3);

function dornott_get_presentation_file_url()
{
	$file = function_exists('get_field') ? get_field('presentation_file', 'option') : null;

	if (is_array($file) && !empty($file['url'])) {
		return (string) $file['url'];
	}

	if (is_string($file) && $file !== '') {
		return $file;
	}

	$file_id = get_option('options_presentation_file');
	if (!$file_id && function_exists('dornott_front_page_id')) {
		$front_id = dornott_front_page_id();
		if ($front_id > 0) {
			$file_id = get_post_meta($front_id, 'presentation_file', true);
		}
	}

	if (!$file_id) {
		return '';
	}

	$url = wp_get_attachment_url((int) $file_id);

	return $url ? $url : '';
}
