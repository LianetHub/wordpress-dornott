<?php

add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
});

add_action('after_setup_theme', 'custom_wc_disable_features', 99);

function custom_wc_disable_features()
{
    remove_all_actions('woocommerce_before_checkout_form');
    remove_all_actions('woocommerce_checkout_order_review');
    remove_all_actions('woocommerce_checkout_after_order_review');

    if (defined('WC_TEMPLATE_PATH')) {
        remove_action('init', 'woocommerce_prevent_admin_access');
        remove_action('init', 'woocommerce_add_to_cart_action');
        remove_action('init', 'woocommerce_setup_session');
    }

    add_filter('woocommerce_payment_gateways', 'disable_all_payment_gateways', 999);
}

function disable_all_payment_gateways($gateways)
{
    return array();
}

add_action('init', 'disable_wc_pages', 99);
function disable_wc_pages()
{
    $pages_to_disable = array('cart', 'checkout', 'myaccount');

    foreach ($pages_to_disable as $slug) {
        $page_id = wc_get_page_id($slug);
        if ($page_id > 0) {
            wp_update_post(array('ID' => $page_id, 'post_status' => 'trash'));
        }
    }
}

add_action('init', function () {
    if (class_exists('WooCommerce')) {
        update_option('woocommerce_coming_soon', 'no');
        update_option('woocommerce_store_pages_only', 'no');
    }
});

add_action('admin_menu', 'hide_wc_admin_menus', 999);
function hide_wc_admin_menus()
{
    remove_menu_page('wc-admin&path=/analytics/overview');
    remove_menu_page('woocommerce-marketing');
    remove_menu_page('wc-admin&path=/payments/overview');
    remove_menu_page('admin.php?page=wc-settings&tab=checkout');
    remove_menu_page('admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM');

    remove_submenu_page('edit.php?post_type=product', 'product_attributes');
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_cat&post_type=product');
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_tag&post_type=product');
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_brand&post_type=product');
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=pwb-brand&post_type=product');
    remove_submenu_page('edit.php?post_type=product', 'product-reviews');
}

add_action('wp_enqueue_scripts', 'dequeue_unnecessary_wc_scripts', 99);
function dequeue_unnecessary_wc_scripts()
{
    wp_dequeue_style('woocommerce-general');
    wp_dequeue_style('woocommerce-layout');
    wp_dequeue_style('woocommerce-smallscreen');
    wp_dequeue_style('woocommerce_frontend_styles');
    wp_dequeue_style('woocommerce_chosen_styles');
    wp_dequeue_script('wc-add-to-cart');
    wp_dequeue_script('woocommerce');
    wp_dequeue_script('wc-cart-fragments');
    wp_dequeue_script('wc-checkout');
    wp_dequeue_script('wc-add-to-cart-variation');
}

add_action('woocommerce_product_query', 'dornott_catalog_product_query');
function dornott_catalog_product_query($q)
{
    if (is_admin()) {
        return;
    }

    $q->set('posts_per_page', -1);
    $q->set('orderby', 'menu_order');
    $q->set('order', 'ASC');
}

define('DORNOTT_CATALOG_SLUG', 'katalog');
define('DORNOTT_CATALOG_URL_VERSION', '2');

add_action('init', 'dornott_setup_catalog_permalinks', 0);
function dornott_setup_catalog_permalinks()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    $shop_id = wc_get_page_id('shop');
    if ($shop_id > 0) {
        $shop = get_post($shop_id);
        if ($shop && $shop->post_name !== DORNOTT_CATALOG_SLUG) {
            wp_update_post(array(
                'ID'        => $shop_id,
                'post_name' => DORNOTT_CATALOG_SLUG,
            ));
        }
    }

    $permalinks = (array) get_option('woocommerce_permalinks', array());
    $desired_base = '/' . DORNOTT_CATALOG_SLUG;
    $needs_update = ($permalinks['product_base'] ?? '') !== $desired_base
        || empty($permalinks['use_verbose_page_rules']);

    if ($needs_update) {
        $permalinks['product_base'] = $desired_base;
        $permalinks['use_verbose_page_rules'] = true;
        update_option('woocommerce_permalinks', $permalinks);
        delete_option('dornott_catalog_url_version');
    }
}

add_filter('woocommerce_register_post_type_product', 'dornott_product_post_type_args');
function dornott_product_post_type_args($args)
{
    if (!is_array($args['rewrite'] ?? null)) {
        $args['rewrite'] = array();
    }

    $args['rewrite']['slug'] = DORNOTT_CATALOG_SLUG;
    $args['rewrite']['with_front'] = false;

    return $args;
}

add_action('init', 'dornott_catalog_rewrite_rules', 6);
function dornott_catalog_rewrite_rules()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    $slug = DORNOTT_CATALOG_SLUG;

    add_rewrite_rule('^' . $slug . '/([^/]+)/?$', 'index.php?product=$matches[1]', 'top');
    add_rewrite_rule('^' . $slug . '/page/([0-9]+)/?$', 'index.php?post_type=product&paged=$matches[1]', 'top');
    add_rewrite_rule('^product/([^/]+)/?$', 'index.php?product=$matches[1]', 'top');
}

add_action('init', 'dornott_flush_product_rewrites', 999);
function dornott_flush_product_rewrites()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    if (get_option('dornott_catalog_url_version') === DORNOTT_CATALOG_URL_VERSION) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('dornott_catalog_url_version', DORNOTT_CATALOG_URL_VERSION);
}

add_action('template_redirect', 'dornott_redirect_legacy_product_urls', 1);
function dornott_redirect_legacy_product_urls()
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    $canonical_path = wp_parse_url(get_permalink(), PHP_URL_PATH);
    $current_path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    if (!$canonical_path || !$current_path) {
        return;
    }

    $canonical_path = rawurldecode(untrailingslashit($canonical_path));
    $current_path = rawurldecode(untrailingslashit($current_path));

    if ($current_path !== $canonical_path) {
        wp_safe_redirect(get_permalink(), 301);
        exit;
    }
}

add_filter('wpseo_breadcrumb_links', 'dornott_product_breadcrumb_links');
function dornott_product_breadcrumb_links($links)
{
    if (!function_exists('is_product') || !is_product()) {
        return $links;
    }

    $shop_id = wc_get_page_id('shop');
    if ($shop_id <= 0) {
        return $links;
    }

    $shop_url = untrailingslashit(get_permalink($shop_id));
    foreach ($links as $link) {
        if (!empty($link['url']) && untrailingslashit($link['url']) === $shop_url) {
            return $links;
        }
    }

    array_splice($links, 1, 0, array(array(
        'url'  => get_permalink($shop_id),
        'text' => get_the_title($shop_id),
    )));

    return $links;
}

add_action('init', 'custom_remove_product_taxonomies', 100);
function custom_remove_product_taxonomies()
{
    unregister_taxonomy_for_object_type('product_cat', 'product');
    unregister_taxonomy_for_object_type('product_tag', 'product');
    unregister_taxonomy_for_object_type('product_brand', 'product');
    unregister_taxonomy_for_object_type('pwb-brand', 'product');
}

add_action('init', 'custom_remove_product_features', 999);
function custom_remove_product_features()
{
    remove_post_type_support('product', 'comments');
    remove_post_type_support('product', 'reviews');
    remove_post_type_support('product', 'excerpt');
}

add_filter('wpseo_sitemap_exclude_post_type', 'dornott_exclude_junk_post_types_from_sitemap', 10, 2);
function dornott_exclude_junk_post_types_from_sitemap($exclude, $post_type)
{
    if ($post_type === 'post') {
        return true;
    }
    return $exclude;
}

add_filter('wpseo_sitemap_exclude_taxonomy', 'custom_exclude_product_taxonomy_from_sitemap', 10, 2);
function custom_exclude_product_taxonomy_from_sitemap($exclude, $taxonomy)
{
    $taxonomies = array('category', 'post_tag', 'product_cat', 'product_tag', 'product_brand', 'pwb-brand');
    if (in_array($taxonomy, $taxonomies, true)) {
        return true;
    }
    return $exclude;
}

add_filter('wpseo_sitemap_exclude_author', 'dornott_exclude_authors_from_sitemap');
function dornott_exclude_authors_from_sitemap($users)
{
    return array();
}

add_filter('wp_sitemaps_post_types', 'dornott_exclude_core_sitemap_post_types');
function dornott_exclude_core_sitemap_post_types($post_types)
{
    unset($post_types['post']);
    return $post_types;
}

add_filter('wp_sitemaps_taxonomies', 'dornott_exclude_core_sitemap_taxonomies');
function dornott_exclude_core_sitemap_taxonomies($taxonomies)
{
    unset($taxonomies['category'], $taxonomies['post_tag']);
    return $taxonomies;
}

add_filter('wp_sitemaps_add_provider', 'dornott_exclude_core_sitemap_users', 10, 2);
function dornott_exclude_core_sitemap_users($provider, $name)
{
    if ($name === 'users') {
        return false;
    }
    return $provider;
}

add_filter('woocommerce_get_query_vars', 'custom_remove_wc_query_vars', 99);
function custom_remove_wc_query_vars($vars)
{
    unset($vars['product_cat']);
    unset($vars['product_tag']);
    return $vars;
}

add_filter('woocommerce_product_tabs', 'custom_remove_product_tabs', 98);
function custom_remove_product_tabs($tabs)
{
    unset($tabs['reviews']);
    return $tabs;
}
