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

function dornott_section_field_names($set = 'all')
{
	$catalog = array(
		'show_about',
		'about_title',
		'about_description',
		'about_image',
		'about_benefits',
		'show_gift',
		'gift_title',
		'gift_subtitle',
		'gift_button',
		'gift_image',
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

	if ($set === 'catalog') {
		return array_merge($catalog, $shared);
	}

	if ($set === 'product') {
		return $shared;
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
