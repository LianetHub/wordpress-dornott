<?php
$current_id = get_the_ID();

if (function_exists('is_shop') && is_shop() && function_exists('wc_get_page_id')) {
	$shop_page_id = wc_get_page_id('shop');
	if ($shop_page_id > 0) {
		$current_id = $shop_page_id;
	}
}

$hide_image_context = (function_exists('is_shop') && is_shop())
	|| (function_exists('is_product') && is_product())
	|| (function_exists('is_product_taxonomy') && is_product_taxonomy())
	|| is_archive()
	|| is_tax();

$has_thumbnail = $current_id && has_post_thumbnail($current_id) && !$hide_image_context;
$hero_class = $has_thumbnail ? ' hero--has-poster' : '';
?>

<section id="hero" class="hero hero--inner<?php echo $hero_class; ?>">
	<div class="container">
		<div class="hero__content">
			<?php include TEMPLATE_PATH . 'components/breadcrumbs.php'; ?>

			<h1 class="hero__title title">
				<?php
				if (function_exists('is_product') && is_product()) {
					the_title();
				} elseif (function_exists('is_shop') && is_shop()) {
					woocommerce_page_title();
				} elseif (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
					single_term_title();
				} elseif (is_singular()) {
					the_title();
				} elseif (is_post_type_archive()) {
					post_type_archive_title();
				} elseif (is_tax() || is_category() || is_tag()) {
					single_term_title();
				} else {
					echo get_the_archive_title();
				}
				?>
			</h1>

			<?php if ($has_thumbnail) : ?>
				<div class="hero__image">
					<img
						src="<?php echo esc_url(get_the_post_thumbnail_url($current_id, 'full')); ?>"
						alt="<?php echo esc_attr(get_the_title($current_id)); ?>"
						class="cover-image">
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>