<?php

require_once('includes/admin-custom.php');
require_once('includes/acf-custom.php');

// =========================================================================
// 1. CONSTANTS
// =========================================================================

define('TEMPLATE_PATH', dirname(__FILE__) . '/templates/');

// =========================================================================
// 2. ENQUEUE STYLES AND SCRIPTS
// =========================================================================

add_theme_support('title-tag');

// Enqueue theme styles (CSS)
function theme_enqueue_styles()
{
	wp_enqueue_style('swiper', get_template_directory_uri() . '/assets/css/libs/swiper-bundle.min.css');
	wp_enqueue_style('fancybox', get_template_directory_uri() . '/assets/css/libs/fancybox.css');
	wp_enqueue_style('reset', get_template_directory_uri() . '/assets/css/reset.min.css');
	wp_enqueue_style('main-style', get_template_directory_uri() . '/assets/css/style.min.css');
}
add_action('wp_enqueue_scripts', 'theme_enqueue_styles');


// Enqueue theme scripts (JS)
function theme_enqueue_scripts()
{
	wp_deregister_script('jquery');
	wp_enqueue_script('jquery', get_template_directory_uri() . '/assets/js/libs/jquery-3.7.1.min.js', array(), null, true);
	wp_enqueue_script('swiper-js', get_template_directory_uri() . '/assets/js/libs/swiper-bundle.min.js', array(), null, true);
	wp_enqueue_script('fancybox-js', get_template_directory_uri() . '/assets/js/libs/fancybox.umd.js', array(), null, true);
	wp_enqueue_script('app-js', get_template_directory_uri() . '/assets/js/app.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'theme_enqueue_scripts');


// =========================================================================
// 3. THEME SUPPORT AND UTILITIES
// =========================================================================

// Allow SVG file uploads
function allow_svg_uploads($mimes)
{
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'allow_svg_uploads');


// Register navigation menus
// function register_custom_menus()
// {
// 	register_nav_menus(array(
// 		'main_menu' => 'Главное меню'
// 	));
// }
// add_action('after_setup_theme', 'register_custom_menus');



// Enable Custom Logo feature
// function  custom_logo_setup()
// {
// 	add_theme_support('custom-logo', array(
// 		'height'      => 50,
// 		'width'       => 362,
// 		'flex-height' => true,
// 		'flex-width'  => true,
// 		'header-text' => array('site-title', 'site-description'),
// 	));
// }
// add_action('after_setup_theme', 'custom_logo_setup');


// ACF Page Settins
// if (function_exists('acf_add_options_page')) {
// 	acf_add_options_page(array(
// 		'page_title'    => 'Общие поля для всего сайта',
// 		'menu_title'    => 'Настройки темы',
// 		'menu_slug'     => 'site-global-settings',
// 		'capability'    => 'edit_posts',
// 		'redirect'      => false
// 	));
// }

// add_action('init', 'register_services_post_type');







// Declare WooCommerce theme support

// add_action('after_setup_theme', function () {
// 	add_theme_support('woocommerce');
// });


// add_filter('woocommerce_register_post_type_shop_order', fn($args) => array_merge($args, ['public' => false, 'show_ui' => false]));
// add_filter('woocommerce_register_post_type_shop_coupon', fn($args) => array_merge($args, ['public' => false, 'show_ui' => false]));
// add_filter('woocommerce_register_post_type_shop_order_refund', fn($args) => array_merge($args, ['public' => false, 'show_ui' => false]));


// add_filter('woocommerce_get_settings_pages', function ($settings) {
// 	if (is_array($settings)) {
// 		foreach ($settings as $key => $page) {
// 			if (isset($page->id) && in_array($page->id, ['checkout', 'account'], true)) {
// 				unset($settings[$key]);
// 			}
// 		}
// 	}
// 	return $settings;
// });

// add_action('wp_enqueue_scripts', function () {
// 	if (!(is_shop() || is_product_category() || is_product())) {
// 		wp_dequeue_style('woocommerce-general');
// 		wp_dequeue_style('woocommerce-layout');
// 		wp_dequeue_style('woocommerce-smallscreen');
// 		wp_dequeue_script('wc-cart-fragments');
// 		wp_dequeue_script('woocommerce');
// 		wp_dequeue_script('wc-add-to-cart');
// 	}
// }, 99);


// add_action('admin_menu', function () {
// 	remove_menu_page('edit.php?post_type=shop_order');
// 	remove_menu_page('edit.php?post_type=shop_coupon');
// });




// СF7 Settings

add_filter('wpcf7_autop_or_not', '__return_false');
