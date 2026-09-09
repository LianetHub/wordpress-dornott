<?php

/**
 * Displayed when no products are found matching the current query
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/no-products-found.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

defined('ABSPATH') || exit;
?>
<p class="catalog__empty"><?php esc_html_e('В настоящее время товаров нет.', 'woocommerce'); ?></p>