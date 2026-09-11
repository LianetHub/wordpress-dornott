<?php

/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

global $product;

if (!is_a($product, WC_Product::class) || !$product->is_visible()) {
	return;
}

$product_id = $product->get_id();
$sku = $product->get_sku();
$is_on_sale = $product->is_on_sale();
$regular_price = $product->get_regular_price();
$sale_price = $product->get_sale_price();
$description = $product->get_description();

$sale_percentage = 0;
if ($is_on_sale && $regular_price > 0 && $sale_price !== '') {
	$sale_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
}

$image_id = $product->get_image_id();
$gallery_ids = $product->get_gallery_image_ids();
$image_size = 'woocommerce_single';
$thumb_size = 'woocommerce_gallery_thumbnail';

$gallery_items = [];
$all_image_ids = [];

if ($image_id) {
	$all_image_ids[] = $image_id;
}

if (!empty($gallery_ids)) {
	$all_image_ids = array_merge($all_image_ids, $gallery_ids);
}

foreach ($all_image_ids as $attachment_id) {
	$full_url = wp_get_attachment_image_url($attachment_id, 'full');
	$single_url = wp_get_attachment_image_url($attachment_id, $image_size) ?: $full_url;
	$thumb_url = wp_get_attachment_image_url($attachment_id, $thumb_size) ?: $single_url;
	$alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

	if (!$full_url) {
		continue;
	}

	$gallery_items[] = [
		'full'   => $full_url,
		'single' => $single_url,
		'thumb'  => $thumb_url,
		'alt'    => $alt ?: $product->get_name(),
	];
}

if (empty($gallery_items)) {
	$placeholder = wc_placeholder_img_src($image_size);
	$gallery_items[] = [
		'full'   => $placeholder,
		'single' => $placeholder,
		'thumb'  => $placeholder,
		'alt'    => $product->get_name(),
	];
}

$main_item = $gallery_items[0];
$has_thumbs = count($gallery_items) > 1;

$labels = get_field('product_labels', $product_id);
$product_unit = get_field('product_unit', $product_id);
$product_specs = get_field('product_specs', $product_id) ?: [];

$specs_rows = [];
if (!empty($sku)) {
	$specs_rows[] = [
		'name'  => 'Артикул',
		'value' => $sku,
	];
}

foreach ($product_specs as $spec_row) {
	$spec_name = trim((string) ($spec_row['spec_name'] ?? ''));
	$spec_value = trim((string) ($spec_row['spec_value'] ?? ''));

	if ($spec_name === '' || $spec_value === '') {
		continue;
	}

	$specs_rows[] = [
		'name'  => $spec_name,
		'value' => $spec_value,
	];
}

$is_variable = $product->is_type('variable');
$variations_data = [];
$first_variation_id = 0;
$first_variation_price_html = '';
$first_variation_regular_price_html = '';

if ($is_variable) {
	$variations = $product->get_available_variations();

	foreach ($variations as $variation_data) {
		$variation_obj = wc_get_product($variation_data['variation_id']);
		if (!$variation_obj) {
			continue;
		}

		$variation_id = $variation_data['variation_id'];
		$variation_price = $variation_obj->get_price();
		$variation_regular_price = $variation_obj->get_regular_price();
		$variation_price_html = wc_price($variation_price);
		$variation_regular_price_html = ($variation_regular_price > $variation_price) ? wc_price($variation_regular_price) : '';

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
			'is_in_stock' => $variation_obj->is_in_stock(),
			'attribute_label' => $attribute_value,
		];
	}
}

if ($is_variable) {
	$initial_price_html = $first_variation_price_html;
	$initial_regular_price_html = $first_variation_regular_price_html;
	$initial_product_id = $first_variation_id;
} else {
	$initial_price_html = wc_price($product->get_price());
	$initial_regular_price_html = ($is_on_sale && !empty($regular_price) && $regular_price > $product->get_price()) ? wc_price($regular_price) : '';
	$initial_product_id = $product_id;
}

$other_products = wc_get_products([
	'status'  => 'publish',
	'limit'   => 4,
	'exclude' => [$product_id],
	'orderby' => 'menu_order',
	'order'   => 'ASC',
]);
?>

<section class="product" data-product-id="<?php echo esc_attr($product_id); ?>" data-sku="<?php echo esc_attr($sku); ?>">
	<?php include TEMPLATE_PATH . '_hero.php'; ?>
	<div class="container">
		<div class="product__body">
			<div class="product__gallery" data-gallery="<?php echo esc_attr(wp_json_encode($gallery_items)); ?>">
				<div class="product__stage">
					<div class="product__main">
						<a href="<?php echo esc_url($main_item['full']); ?>" class="product__main-link">
							<img
								src="<?php echo esc_url($main_item['single']); ?>"
								alt="<?php echo esc_attr($main_item['alt']); ?>"
								class="product__image product-card__image cover-image">
						</a>
						<div class="product__zoom" aria-hidden="true"></div>
					</div>
					<div class="product__labels">
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

				<?php if ($has_thumbs) : ?>
					<div class="product__thumbs swiper">
						<div class="swiper-wrapper">
							<?php foreach ($gallery_items as $index => $item) : ?>
								<button
									type="button"
									class="product__thumb swiper-slide<?php echo $index === 0 ? ' is-active' : ''; ?>"
									data-index="<?php echo esc_attr((string) $index); ?>"
									aria-label="<?php echo esc_attr(sprintf('Показать фото %d', $index + 1)); ?>">
									<img
										src="<?php echo esc_url($item['thumb']); ?>"
										alt="<?php echo esc_attr($item['alt']); ?>">
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="product__info">
				<div class="product__actions">
					<button type="button"
						class="favorite-btn"
						aria-label="Добавить в избранное"></button>
				</div>

				<p class="product__title product-card__title title">
					<?php echo esc_html($product->get_name()); ?>
				</p>

				<?php if ($is_variable) : ?>
					<div class="product__variations product-card__variations" data-variations-data="<?php echo esc_attr(wp_json_encode($variations_data)); ?>">
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
					<div class="product__description">
						<?php echo wp_kses_post($description); ?>
					</div>
				<?php endif; ?>

				<div class="product__buy">
					<div class="product__buy-row">
						<div class="product__price price-block">
							<div class="price-block__header">
								<div class="price-block__old"
									data-price-role="regular-price">
									<?php echo $initial_regular_price_html; ?>
								</div>
								<?php if ($is_on_sale && $sale_percentage > 0) : ?>
									<div class="price-block__sale">
										-<?php echo absint($sale_percentage); ?>%
									</div>
								<?php endif; ?>
							</div>
							<div class="product__price-current">
								<div class="price-block__current"
									data-price-role="current-price">
									<?php echo $initial_price_html; ?>
								</div>
								<?php if (!empty($product_unit)) : ?>
									<span class="product__unit"><?php echo esc_html($product_unit); ?></span>
								<?php endif; ?>
							</div>
						</div>

						<div class="quantity-block">
							<button type="button" class="quantity-block__down icon-minus" aria-label="Уменьшить количество"></button>
							<input type="number" class="quantity-block__input" value="1" min="1" max="999" aria-label="Количество">
							<button type="button" class="quantity-block__up icon-plus" aria-label="Увеличить количество"></button>
						</div>
					</div>

					<div class="product__btns">
						<a href="#order" data-fancybox class="btn btn-primary product__order-btn">Быстрый заказ</a>
						<button
							type="button"
							class="toggle-to-cart-button btn btn-secondary product__cart-btn"
							data-product-id="<?php echo esc_attr($product_id); ?>"
							<?php if ($is_variable) : ?>
							data-variation-id="<?php echo esc_attr($initial_product_id); ?>"
							<?php endif; ?>
							data-quantity="1"
							data-aria-add="Добавить <?php echo esc_attr($product->get_name()); ?> в корзину"
							data-aria-added="Удалить <?php echo esc_attr($product->get_name()); ?> из корзины"
							aria-label="Добавить <?php echo esc_attr($product->get_name()); ?> в корзину">
							<span class="product__cart-btn-text product__cart-btn-text--add">Добавить в корзину</span>
							<span class="product__cart-btn-text product__cart-btn-text--added">В корзине</span>
						</button>
					</div>
				</div>
			</div>
		</div>

		<?php if (!empty($specs_rows)) : ?>
			<div class="product__specs">
				<h2 class="product__specs-title">Характеристики</h2>
				<ul class="product__specs-list">
					<?php foreach ($specs_rows as $spec) : ?>
						<li class="product__specs-row">
							<span class="product__specs-name"><?php echo esc_html($spec['name']); ?></span>
							<span class="product__specs-dots" aria-hidden="true"></span>
							<span class="product__specs-value"><?php echo esc_html($spec['value']); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if (!empty($other_products)) : ?>
	<section class="catalog catalog--related">
		<div class="container">
			<h2 class="catalog__title title">Другие товары</h2>
			<ul class="catalog__grid">
				<?php
				foreach ($other_products as $other_product) {
					$post_object = get_post($other_product->get_id());
					setup_postdata($GLOBALS['post'] = $post_object);
					wc_get_template_part('content', 'product');
				}
				wp_reset_postdata();
				?>
			</ul>
		</div>
	</section>
<?php endif; ?>