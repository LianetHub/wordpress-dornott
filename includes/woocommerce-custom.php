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

add_action('admin_menu', 'hide_wc_admin_menus', 99);
function hide_wc_admin_menus()
{
    // remove_menu_page('woocommerce');
    remove_menu_page('wc-admin&path=/analytics/overview');
    remove_menu_page('admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM');
    remove_menu_page('woocommerce-marketing');
}

add_action('wp_enqueue_scripts', 'dequeue_unnecessary_wc_scripts', 99);
function dequeue_unnecessary_wc_scripts()
{
    if (!is_woocommerce() && !is_cart() && !is_checkout()) {
        wp_dequeue_style('woocommerce-general');
        wp_dequeue_style('woocommerce-layout');
        wp_dequeue_style('woocommerce-smallscreen');
        wp_dequeue_style('woocommerce_frontend_styles');
        wp_dequeue_style('woocommerce_chosen_styles');
        wp_dequeue_script('wc-add-to-cart');
        wp_dequeue_script('woocommerce');
        wp_dequeue_script('wc-cart-fragments');
    }
}

add_action('wp_ajax_custom_add_to_cart', 'custom_add_to_cart');
add_action('wp_ajax_nopriv_custom_add_to_cart', 'custom_add_to_cart');

function custom_add_to_cart()
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($product_id > 0 && WC()->cart->add_to_cart($product_id, $quantity)) {
        wp_send_json_success(array('message' => 'Товар добавлен', 'cart' => custom_get_cart_details()));
    } else {
        wp_send_json_error(array('message' => 'Ошибка добавления товара'));
    }
    wp_die();
}

add_action('wp_ajax_custom_remove_from_cart', 'custom_remove_from_cart');
add_action('wp_ajax_nopriv_custom_remove_from_cart', 'custom_remove_from_cart');

function custom_remove_from_cart()
{
    $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';

    if (WC()->cart->remove_cart_item($cart_item_key)) {
        wp_send_json_success(array('message' => 'Товар удален', 'cart' => custom_get_cart_details()));
    } else {
        wp_send_json_error(array('message' => 'Ошибка удаления товара'));
    }
    wp_die();
}

add_action('wp_ajax_custom_get_cart', 'custom_get_cart_details_ajax');
add_action('wp_ajax_nopriv_custom_get_cart', 'custom_get_cart_details_ajax');

function custom_get_cart_details_ajax()
{
    wp_send_json_success(array('cart' => custom_get_cart_details()));
    wp_die();
}

add_action('wp_ajax_custom_prepare_order', 'custom_prepare_order');
add_action('wp_ajax_nopriv_custom_prepare_order', 'custom_prepare_order');

function custom_prepare_order()
{
    $cart = WC()->cart;
    if (!$cart->is_empty()) {
        $order_details = array();
        $items_for_tbank = array();

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $price_in_kopecks = round($cart_item['line_total'] * 100);

            $items_for_tbank[] = array(
                'name' => $product->get_name(),
                'price' => $price_in_kopecks,
                'quantity' => $cart_item['quantity'],
                'amount' => $price_in_kopecks * $cart_item['quantity'],
                'tax' => 'none'
            );
        }

        $order_details['total'] = $cart->get_total('edit');
        $order_details['total_kopecks'] = round($cart->get_total() * 100);
        $order_details['items_for_tbank'] = $items_for_tbank;

        $response_data = array(
            'message' => 'Данные заказа готовы',
            'order_data' => $order_details
        );

        // Здесь должна быть логика вызова Init API Т-Банка
        // $payment_url = tbank_api_init($order_details);
        // $response_data['payment_url'] = $payment_url;

        wp_send_json_success($response_data);
    } else {
        wp_send_json_error(array('message' => 'Корзина пуста. Невозможно создать заказ.'));
    }
    wp_die();
}

function custom_get_cart_details()
{
    $cart = WC()->cart;
    $items_list = array();

    if ($cart) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $items_list[] = array(
                'key' => $cart_item_key,
                'product_id' => $cart_item['product_id'],
                'name' => $product->get_name(),
                'price_formatted' => $cart_item['line_total'],
                'quantity' => $cart_item['quantity'],
                'thumbnail_url' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
            );
        }
    }

    return array(
        'count' => $cart ? $cart->get_cart_contents_count() : 0,
        'total' => $cart ? $cart->get_cart_total() : 0,
        'items' => $items_list
    );
}

add_action('wp_enqueue_scripts', 'custom_enqueue_cart_scripts');

function custom_enqueue_cart_scripts()
{
    wp_enqueue_script('custom-cart-ajax', get_template_directory_uri() . '/js/custom-cart-ajax.js', array('jquery'), '1.0', true);
    wp_localize_script('custom-cart-ajax', 'custom_cart_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
