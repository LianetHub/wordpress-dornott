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
