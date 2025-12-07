<?php

require_once('includes/admin-custom.php');
require_once('includes/acf-custom.php');
require_once('includes/woocommerce-custom.php');

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
function register_custom_menus()
{
	register_nav_menus(array(
		'general_menu' => ' Меню'
	));
}
add_action('after_setup_theme', 'register_custom_menus');

class Dornott_Menu_Walker extends Walker_Nav_Menu
{

	public function start_lvl(&$output, $depth = 0, $args = null)
	{
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class=\"submenu__list\">\n";
	}

	public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
	{
		$indent = ($depth) ? str_repeat("\t", $depth) : '';

		$classes = empty($item->classes) ? array() : (array) $item->classes;
		$classes[] = 'menu__item';

		$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
		$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

		$output .= $indent . '<li' . $class_names . '>';

		$atts = array();
		$atts['title']  = ! empty($item->attr_title) ? $item->attr_title : '';
		$atts['target'] = ! empty($item->target)     ? $item->target     : '';
		$atts['rel']    = ! empty($item->xfn)        ? $item->xfn        : '';
		$atts['href']   = ! empty($item->url)        ? $item->url        : '';
		$atts['class']  = 'menu__link';

		$atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

		$attributes = '';
		foreach ($atts as $attr => $value) {
			if (! empty($value)) {
				$value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$title = apply_filters('the_title', $item->title, $item->ID);
		$title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

		$item_output = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . $title . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
	}
}

function append_contacts_to_nav($items, $args)
{
	if ($args->theme_location === 'general_menu') {

		ob_start();
		get_template_part('templates/components/menu', 'contacts-block');
		$contacts_item = ob_get_clean();

		$items .= $contacts_item;
	}
	return $items;
}
add_filter('wp_nav_menu_items', 'append_contacts_to_nav', 10, 2);


// убираем с фронта ненужную инфу в хедере
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('wp_head', 'rel_canonical');

remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'start_post_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
remove_action('wp_head', 'parent_post_rel_link', 10, 0);

// Выключаем xmlrpc, ибо дыра
add_filter('xmlrpc_enabled', '__return_false');


// Убираем из панели админки лого вп и обновления
function remove_admin_bar_links()
{
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu('wp-logo');
	$wp_admin_bar->remove_menu('updates');
}
add_action('wp_before_admin_bar_render', 'remove_admin_bar_links');

//фикс ошибок микроразметки
add_filter('disable_wpseo_json_ld_search', '__return_true');


function add_preloading_body_class($classes)
{
	$classes[] = 'preloading';
	return $classes;
}
add_filter('body_class', 'add_preloading_body_class');

// СF7 Settings

add_filter('wpcf7_autop_or_not', '__return_false');

function currentYear()
{
	return date('Y');
}
