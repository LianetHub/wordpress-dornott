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

function dornott_section_field_names($set = 'all')
{
	$catalog = array(
		'show_about',
		'about_title',
		'about_description',
		'about_image',
		'about_benefits',
	);

	$gift = array(
		'show_gift',
		'gift_title',
		'gift_subtitle',
		'gift_button',
		'gift_image',
	);

	$presentation = array(
		'show_presentation',
		'presentation_title',
		'presentation_subtitle',
		'presentation_btn_text',
		'presentation_image',
	);

	$shared = array(
		'show_special_offer',
		'special_offer_title',
		'special_offer_subtitle',
		'special_offer_button',
		'special_offer_sale_value',
		'special_offer_image',
		'show_order_steps',
		'order_steps_title',
		'order_steps_items',
		'order_terms',
		'show_reviews',
		'reviews_title',
		'reviews_subtitle',
		'reviews_text',
		'reviews_screenshots',
		'reviews_deafult_type',
		'show_contacts',
		'contacts_title',
		'contacts_form',
	);

	$shared = array_merge($gift, $presentation, $shared);

	if ($set === 'catalog') {
		return array_merge($catalog, $shared);
	}

	if ($set === 'product') {
		return $shared;
	}

	if ($set === 'presentation') {
		return $presentation;
	}

	if ($set === 'gift') {
		return $gift;
	}

	return array_merge($catalog, $shared);
}

function dornott_copy_section_fields($from_id, $to_id, $field_names)
{
	if (!function_exists('get_field') || !function_exists('update_field')) {
		return;
	}

	$from_id = (int) $from_id;
	$to_id = (int) $to_id;

	if ($from_id <= 0 || $to_id <= 0 || $from_id === $to_id) {
		return;
	}

	foreach ($field_names as $name) {
		$value = get_field($name, $from_id, false);
		if ($value === null) {
			continue;
		}
		update_field($name, $value, $to_id);
	}
}

add_action('init', function () {
	if (get_option('dornott_independent_sections_seeded') === '1') {
		return;
	}

	if (!function_exists('get_field') || !function_exists('update_field')) {
		return;
	}

	$front_id = dornott_front_page_id();
	if ($front_id <= 0) {
		return;
	}

	if (function_exists('wc_get_page_id')) {
		dornott_copy_section_fields($front_id, (int) wc_get_page_id('shop'), dornott_section_field_names('catalog'));
	}

	if (function_exists('wc_get_products')) {
		$products = wc_get_products(array(
			'status' => 'publish',
			'limit'  => -1,
			'return' => 'ids',
		));

		foreach ($products as $product_id) {
			dornott_copy_section_fields($front_id, (int) $product_id, dornott_section_field_names('product'));
		}
	}

	update_option('dornott_independent_sections_seeded', '1', false);
}, 40);

function dornott_enable_section_toggles_if_empty($target_id, $field_names)
{
	if (!function_exists('get_field') || !function_exists('update_field')) {
		return;
	}

	$target_id = is_int($target_id) || ctype_digit((string) $target_id)
		? (int) $target_id
		: $target_id;

	foreach ($field_names as $name) {
		$value = get_field($name, $target_id, false);
		if ($value === null || $value === false || $value === '' || $value === '0' || $value === 0) {
			update_field($name, 1, $target_id);
		}
	}
}

function dornott_migrate_presentation_file_to_options($front_id)
{
	$existing = get_option('options_presentation_file');
	if ($existing) {
		return;
	}

	$from_front = null;
	if (function_exists('get_field')) {
		$existing_field = get_field('presentation_file', 'option', false);
		if ($existing_field) {
			return;
		}
		$from_front = get_field('presentation_file', $front_id, false);
	}

	if (!$from_front) {
		$from_front = get_post_meta($front_id, 'presentation_file', true);
	}

	if (!$from_front) {
		return;
	}

	if (function_exists('update_field')) {
		update_field('field_69d7c1a082806', $from_front, 'option');
	}

	if (!get_option('options_presentation_file')) {
		update_option('options_presentation_file', $from_front, false);
		update_option('_options_presentation_file', 'field_69d7c1a082806', false);
	}
}

add_action('init', function () {
	if (get_option('dornott_presentation_sections_seeded') === '1') {
		return;
	}

	if (!function_exists('get_field') || !function_exists('update_field')) {
		return;
	}

	$front_id = dornott_front_page_id();
	if ($front_id <= 0) {
		return;
	}

	dornott_migrate_presentation_file_to_options($front_id);

	$presentation_fields = dornott_section_field_names('presentation');
	$gift_fields = dornott_section_field_names('gift');
	$toggle_fields = array('show_presentation', 'show_gift', 'show_order_steps');

	$shop_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
	if ($shop_id > 0) {
		dornott_copy_section_fields($front_id, $shop_id, $presentation_fields);
		dornott_enable_section_toggles_if_empty($shop_id, $toggle_fields);
	}

	dornott_enable_section_toggles_if_empty($front_id, $toggle_fields);

	if (function_exists('wc_get_products')) {
		$products = wc_get_products(array(
			'status' => 'publish',
			'limit'  => -1,
			'return' => 'ids',
		));

		foreach ($products as $product_id) {
			$product_id = (int) $product_id;
			dornott_copy_section_fields($front_id, $product_id, $presentation_fields);
			dornott_copy_section_fields($front_id, $product_id, $gift_fields);
			dornott_enable_section_toggles_if_empty($product_id, $toggle_fields);
		}
	}

	update_option('dornott_presentation_sections_seeded', '1', false);
}, 41);
