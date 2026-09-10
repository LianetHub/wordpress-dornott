<?php

/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

get_header();
?>

<section class="catalog catalog--page">
	<?php include TEMPLATE_PATH . '_hero.php'; ?>
	<div class="container">
		<?php if (woocommerce_product_loop()) : ?>
			<?php
			woocommerce_product_loop_start();

			if (wc_get_loop_prop('total')) {
				while (have_posts()) {
					the_post();
					do_action('woocommerce_shop_loop');
					wc_get_template_part('content', 'product');
				}
			}

			woocommerce_product_loop_end();
			?>
		<?php else : ?>
			<?php do_action('woocommerce_no_products_found'); ?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
