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
 * @see     https://woocommerce.com/document/template-structure/
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

$image_id = $product->get_image_id();
$gallery_ids = $product->get_gallery_image_ids();
$image_size = 'woocommerce_single';

$slides_html = '';

$image_args = [
	'class'   => 'product-card__image cover-image',
	'loading' => 'lazy',
];

if ($image_id) {
	$image_html = wp_get_attachment_image($image_id, $image_size, false, $image_args);
	$slides_html .= '<div class="product-card__slide swiper-slide">' . $image_html . '<span class="swiper-lazy-preloader"></span></div>';
} else {
	$slides_html .= '<div class="product-card__slide swiper-slide">' . wc_placeholder_img($image_size, $image_args) . '<span class="swiper-lazy-preloader"></span></div>';
}

if (!empty($gallery_ids)) {
	foreach ($gallery_ids as $gallery_image_id) {
		$image_html = wp_get_attachment_image($gallery_image_id, $image_size, false, $image_args);
		$slides_html .= '<div class="product-card__slide swiper-slide">' . $image_html . '<span class="swiper-lazy-preloader"></span></div>';
	}
}

$labels = get_field('product_labels', $product_id);

$is_variable = $product->is_type('variable');
$default_price_html = $product->get_price_html();

$variations_data = [];
$first_variation_id = 0;
$first_variation_price_html = '';
$first_variation_regular_price_html = '';

if ($is_variable) {
	$variations = $product->get_available_variations();

	foreach ($variations as $variation_data) {
		$variation_obj = wc_get_product($variation_data['variation_id']);
		if (!$variation_obj) continue;

		$variation_id = $variation_data['variation_id'];
		$variation_price = $variation_obj->get_price();
		$variation_regular_price = $variation_obj->get_regular_price();
		$variation_price_html = wc_price($variation_price);
		$variation_regular_price_html = ($variation_regular_price > $variation_price) ? wc_price($variation_regular_price) : '';

		$attribute_name = key($variation_data['attributes']);
		$attribute_value = current($variation_data['attributes']);

		if (!$first_variation_id) {
			$first_variation_id = $variation_id;
			$first_variation_price_html = $variation_price_html;
			$first_variation_regular_price_html = $variation_regular_price_html;
		}

		$variations_data[$variation_id] = [
			'id' => $variation_id,
			'price_html' => $variation_price_html,
			'regular_price_html' => $variation_regular_price_html,
			'attribute_value' => $attribute_value,
			'is_in_stock' => $variation_obj->is_in_stock(),
			$attribute_name => $attribute_value,
			'attribute_label' => $attribute_value,
		];
	}
}

if ($is_variable) {
	// Вариативный товар
	$initial_price_html = $first_variation_price_html;
	$initial_regular_price_html = $first_variation_regular_price_html;
	$initial_product_id = $first_variation_id;
} else {
	// Простой товар
	$initial_price_html = wc_price($product->get_price());
	$initial_regular_price_html = ($is_on_sale && !empty($regular_price) && $regular_price > $product->get_price()) ? wc_price($regular_price) : '';
	$initial_product_id = $product_id;
}

?>
<li <?php wc_product_class('product-card', $product); ?> data-product-id="<?php echo esc_attr($product_id); ?>">
	<div class="product-card__header">
		<div class="product-card__slider swiper">
			<div class="swiper-wrapper">
				<?php echo $slides_html; ?>
			</div>
		</div>
		<div class="product-card__labels">
			<?php if ($is_on_sale && $sale_percentage > 0) : ?>
				<span class="label label--red">
					-<?php echo absint($sale_percentage); ?>%
				</span>
			<?php endif; ?>

			<?php if ($labels) : ?>
				<?php foreach ($labels as $label_item) : ?>
					<?php
					$label_text = $label_item['label'] ?? '';
					$label_color = $label_item['color'] ?? '';
					$label_icon = $label_item['icon'] ?? null;
					$label_icon_id = 0;

					if ($label_icon && is_array($label_icon) && isset($label_icon['ID'])) {
						$label_icon_id = $label_icon['ID'];
					}

					$label_class = '';
					if ($label_color) {
						$label_class = 'label--' . sanitize_title($label_color);
					}
					?>
					<?php if ($label_text) : ?>
						<span class="label <?php echo esc_attr($label_class); ?>">
							<?php if ($label_icon_id) : ?>
								<span class="label__icon">
									<?php echo wp_get_attachment_image($label_icon_id, 'thumbnail', false, ['class' => 'label__icon']); ?>
								</span>
							<?php endif; ?>
							<?php echo esc_html($label_text); ?>
						</span>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="product-card__actions">
		<?php if (!empty($sku)) : ?>
			<div class="product-card__sku">
				<?php echo esc_html($sku); ?>
			</div>
		<?php endif; ?>
		<div class="product-card__pagination swiper-pagination"></div>
		<button type="button"
			class="favorite-btn"
			aria-label="Добавить в избранное"></button>
	</div>
	<div class="product-card__details">
		<h3 class="product-card__title">
			<?php echo esc_html($product->get_name()); ?>
		</h3>

		<?php if ($is_variable) : ?>
			<div class="product-card__variations" data-variations-data="<?php echo esc_attr(json_encode($variations_data)); ?>">
				<?php foreach ($variations_data as $variation_id => $data) : ?>
					<label class="product-card__variations-label <?php echo !$data['is_in_stock'] ? 'out-of-stock' : ''; ?>">
						<input type="radio"
							name="variation-<?php echo esc_attr($product_id); ?>"
							value="<?php echo esc_attr($variation_id); ?>"
							data-price-html="<?php echo esc_attr($data['price_html']); ?>"
							data-regular-price-html="<?php echo esc_attr($data['regular_price_html']); ?>"
							<?php checked($variation_id, $first_variation_id); ?>
							<?php disabled(!$data['is_in_stock']); ?>
							class="product-card__variations-input hidden"
							hidden>
						<span class="product-card__variations-btn">
							<?php echo esc_html($data['attribute_label']); ?>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if (!empty($description)) : ?>
			<div class="product-card__description">
				<?php echo wp_kses_post($description); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="product-card__footer">
		<div class="product-card__price price-block">
			<div class="price-block__header">
				<div class="price-block__old"
					data-price-role="regular-price">
					<?php echo $initial_regular_price_html; ?>
				</div>
				<?php if ($is_on_sale && $sale_percentage > 0) : ?>
					<div class="price-block__sale">
						-<?php echo $sale_percentage ?>%
					</div>
				<?php endif; ?>
			</div>
			<div class="price-block__current"
				data-price-role="current-price">
				<?php echo $initial_price_html; ?>
			</div>
		</div>

		<button
			class="add-to-cart-button btn btn-primary ajax_add_to_cart"
			data-product-id="<?php echo esc_attr($product_id); ?>"
			<?php if ($is_variable) : ?>
			data-variation-id="<?php echo esc_attr($initial_product_id); ?>"
			<?php endif; ?>
			data-quantity="1"
			aria-label="Добавить <?php echo esc_attr($product->get_name()); ?> в корзину">
		</button>
	</div>
</li>