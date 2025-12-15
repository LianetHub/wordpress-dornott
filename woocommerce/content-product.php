<?php

/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */


defined('ABSPATH') || exit;

global $product;

if (!is_a($product, WC_Product::class) || !$product->is_visible()) {
	return;
}

$product_id = $product->get_id();
$sku = $product->get_sku();
$permalink = $product->get_permalink();
$is_on_sale = $product->is_on_sale();
$regular_price = $product->get_regular_price();
$sale_price = $product->get_sale_price();
$description = $product->get_description();

$sale_percentage = 0;
$discount_amount = 0;
if ($is_on_sale && $regular_price > 0 && $sale_price !== '') {
	$discount_amount = $regular_price - $sale_price;
	$sale_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
}

$label_action = 'Акция';
$label_cashback = 'Кэшбэк 10%';

$image_id = $product->get_image_id();
$gallery_ids = $product->get_gallery_image_ids();
$image_size = 'woocommerce_single';

$slides_html = '';

if ($image_id) {
	$image_html = wp_get_attachment_image($image_id, $image_size, false, ['class' => 'product__image_main cover-image']);
	$slides_html .= '<div class="product__slide swiper-slide">' . $image_html . '</div>';
} else {
	$slides_html .= '<div class="product__slide swiper-slide">' . wc_placeholder_img($image_size) . '</div>';
}

if (!empty($gallery_ids)) {
	foreach ($gallery_ids as $gallery_image_id) {
		$image_html = wp_get_attachment_image($gallery_image_id, $image_size);
		$slides_html .= '<div class="product__slide swiper-slide">' . $image_html . '</div>';
	}
}

?>
<li <?php wc_product_class('custom-product-card', $product); ?>>
	<div class="product__header">
		<div class="product__slider swiper">
			<div class="swiper-wrapper">
				<?php echo $slides_html; ?>
			</div>
		</div>
		<div class="product__labels">
			<?php if (!empty($label_action)) : ?>
				<span class="label"><?php echo esc_html($label_action); ?></span>
			<?php endif; ?>



			<?php if (!empty($label_cashback)) : ?>
				<span class="label label--yellow">
					<?php echo esc_html($label_cashback); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<div class="product__actions">
		<?php if (!empty($sku)) : ?>
			<div class="product__sku">
				<?php echo esc_html($sku); ?>
			</div>
		<?php endif; ?>
		<div class="product__pagination swiper-pagination"></div>
		<button type="button"
			class="favorite-btn"
			aria-label="Добавить в избранное"></button>
	</div>
	<div class="product__details">
		<h3 class="product__title">
			<?php echo esc_html($product->get_name()); ?>
		</h3>
		<?php if (!empty($description)) : ?>
			<div class="product__description">
				<?php echo wp_kses_post($description); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="product__footer">
		<div class="product__price">
			<div class="product__price-header">
				<?php if ($is_on_sale && !empty($regular_price)) : ?>
					<div class="product__price-old"><?php echo wc_price($regular_price); ?></div>
				<?php endif; ?>
				<?php if ($is_on_sale && $sale_percentage > 0) : ?>
					<span class="product__price-sale">-<?php echo absint($sale_percentage); ?>%</span>
				<?php endif; ?>
			</div>
			<div class="product__price-current"><?php echo wc_price($product->get_price()); ?></div>
		</div>

		<button
			class="add-to-cart-button btn btn-primary ajax_add_to_cart"
			data-product-id="<?php echo esc_attr($product_id); ?>"
			data-quantity="1"
			aria-label="Добавить <?php echo esc_attr($product->get_name()); ?> в корзину">
		</button>
	</div>
</li>