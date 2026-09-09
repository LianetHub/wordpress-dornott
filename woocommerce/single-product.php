<?php

/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) {
	the_post();
	wc_get_template_part('content', 'single-product');
}

get_footer();
