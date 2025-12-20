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
    remove_menu_page('woocommerce');
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

add_filter('woocommerce_register_post_type_product', 'custom_disable_product_pages');
function custom_disable_product_pages($args)
{
    $args['public']              = false;
    $args['publicly_queryable']  = false;
    $args['exclude_from_search'] = true;
    $args['has_archive']         = false;
    $args['rewrite']             = false;
    $args['query_var']           = false;
    $args['supports']            = array('title', 'editor', 'thumbnail');

    return $args;
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

add_filter('wpseo_sitemap_exclude_post_type', 'custom_exclude_product_from_sitemap', 10, 2);
function custom_exclude_product_from_sitemap($exclude, $post_type)
{
    if ($post_type === 'product') {
        return true;
    }
    return $exclude;
}

add_filter('wpseo_sitemap_exclude_taxonomy', 'custom_exclude_product_taxonomy_from_sitemap', 10, 2);
function custom_exclude_product_taxonomy_from_sitemap($exclude, $taxonomy)
{
    $taxonomies = array('product_cat', 'product_tag', 'product_brand', 'pwb-brand');
    if (in_array($taxonomy, $taxonomies)) {
        return true;
    }
    return $exclude;
}

add_filter('woocommerce_get_query_vars', 'custom_remove_wc_query_vars', 99);
function custom_remove_wc_query_vars($vars)
{
    unset($vars['product']);
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
