<?php

/**
 * Бэкап и применение SEO-контента товаров. Вызывается одноразовыми скриптами.
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/product-seo-seed-data.php';

function dornott_product_seo_field_keys()
{
	return [
		'labels' => 'field_6940714f124ce',
		'unit'   => 'field_6a9fe001unit01',
		'specs'  => 'field_6a9fe002specs01',
	];
}

function dornott_product_seo_yoast_keys()
{
	return [
		'_yoast_wpseo_title',
		'_yoast_wpseo_metadesc',
		'_yoast_wpseo_focuskw',
		'_yoast_wpseo_opengraph-title',
		'_yoast_wpseo_opengraph-description',
		'_yoast_wpseo_twitter-title',
		'_yoast_wpseo_twitter-description',
	];
}

function dornott_product_seo_collect_post($post_id)
{
	$yoast = [];
	foreach (dornott_product_seo_yoast_keys() as $meta_key) {
		$yoast[$meta_key] = get_post_meta($post_id, $meta_key, true);
	}

	$image_id = 0;
	$image_alt = '';
	if (function_exists('wc_get_product')) {
		$product = wc_get_product($post_id);
		if ($product) {
			$image_id = (int) $product->get_image_id();
		}
	}
	if (!$image_id) {
		$image_id = (int) get_post_thumbnail_id($post_id);
	}
	if ($image_id) {
		$image_alt = (string) get_post_meta($image_id, '_wp_attachment_image_alt', true);
	}

	return [
		'ID'           => (int) $post_id,
		'post_name'    => get_post_field('post_name', $post_id),
		'post_title'   => get_post_field('post_title', $post_id),
		'post_content' => get_post_field('post_content', $post_id),
		'post_excerpt' => get_post_field('post_excerpt', $post_id),
		'sku'          => function_exists('wc_get_product') && ($p = wc_get_product($post_id)) ? $p->get_sku() : get_post_meta($post_id, '_sku', true),
		'acf'          => [
			'product_labels' => function_exists('get_field') ? get_field('product_labels', $post_id) : null,
			'product_unit'   => function_exists('get_field') ? get_field('product_unit', $post_id) : get_post_meta($post_id, 'product_unit', true),
			'product_specs'  => function_exists('get_field') ? get_field('product_specs', $post_id) : null,
		],
		'yoast'        => $yoast,
		'image_id'     => $image_id,
		'image_alt'    => $image_alt,
	];
}

function dornott_product_seo_backup()
{
	$slugs = array_keys(dornott_product_seo_seed_data());
	$products = [];

	foreach ($slugs as $slug) {
		$post = get_page_by_path($slug, OBJECT, 'product');
		if (!$post) {
			$products[$slug] = null;
			continue;
		}
		$products[$slug] = dornott_product_seo_collect_post($post->ID);
	}

	$shop = null;
	if (function_exists('wc_get_page_id')) {
		$shop_id = (int) wc_get_page_id('shop');
		if ($shop_id > 0) {
			$shop = dornott_product_seo_collect_post($shop_id);
		}
	}

	$payload = [
		'created_at' => gmdate('c'),
		'siteurl'    => get_option('siteurl'),
		'products'   => $products,
		'shop'       => $shop,
	];

	$uploads = wp_upload_dir();
	$dir = trailingslashit($uploads['basedir']) . 'backups';
	if (!is_dir($dir) && !wp_mkdir_p($dir)) {
		return new WP_Error('backup_dir', 'Не удалось создать каталог backups.');
	}

	$filename = 'products-seo-' . gmdate('Ymd-His') . '.json';
	$path = $dir . '/' . $filename;
	$written = file_put_contents(
		$path,
		wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	);

	if ($written === false) {
		return new WP_Error('backup_write', 'Не удалось записать JSON бэкапа.');
	}

	return [
		'file' => $path,
		'url'  => trailingslashit($uploads['baseurl']) . 'backups/' . $filename,
		'data' => $payload,
	];
}

function dornott_product_seo_apply_yoast($post_id, array $yoast)
{
	$title = $yoast['title'] ?? '';
	$desc  = $yoast['metadesc'] ?? '';
	$kw    = $yoast['focuskw'] ?? '';

	$map = [
		'title'                  => $title,
		'metadesc'               => $desc,
		'focuskw'                => $kw,
		'opengraph-title'        => $title,
		'opengraph-description'  => $desc,
		'twitter-title'          => $title,
		'twitter-description'    => $desc,
	];

	if (class_exists('WPSEO_Meta')) {
		foreach ($map as $key => $value) {
			WPSEO_Meta::set_value($key, $value, $post_id);
		}
	} else {
		foreach ($map as $key => $value) {
			update_post_meta($post_id, '_yoast_wpseo_' . $key, $value);
		}
	}

	dornott_product_seo_rebuild_yoast_indexable($post_id);
}

function dornott_product_seo_rebuild_yoast_indexable($post_id)
{
	if (!function_exists('YoastSEO')) {
		return;
	}

	try {
		$container = YoastSEO()->classes;
		$builder = $container->get(\Yoast\WP\SEO\Builders\Indexable_Builder::class);
		$repo = $container->get(\Yoast\WP\SEO\Repositories\Indexable_Repository::class);
		$indexable = $repo->find_by_id_and_type($post_id, 'post', false);
		$builder->build_for_id_and_type($post_id, 'post', $indexable);
	} catch (Throwable $e) {
		// Мета уже записана; индекс Yoast обновится при следующем сохранении в админке.
	}
}

function dornott_product_seo_apply()
{
	$keys = dornott_product_seo_field_keys();
	$seed = dornott_product_seo_seed_data();
	$result = [
		'products' => [],
		'shop'     => null,
	];

	foreach ($seed as $slug => $data) {
		$post = get_page_by_path($slug, OBJECT, 'product');
		if (!$post) {
			$result['products'][$slug] = [
				'ok'    => false,
				'error' => 'Товар не найден',
			];
			continue;
		}

		$post_id = (int) $post->ID;

		wp_update_post(wp_slash([
			'ID'           => $post_id,
			'post_title'   => $data['title'],
			'post_content' => $data['description'],
		]));

		if (function_exists('update_field')) {
			update_field($keys['unit'], $data['unit'], $post_id);
			update_field($keys['specs'], $data['specs'], $post_id);
			update_field($keys['labels'], $data['labels'], $post_id);
		} else {
			update_post_meta($post_id, 'product_unit', $data['unit']);
		}

		if (!empty($data['yoast'])) {
			dornott_product_seo_apply_yoast($post_id, $data['yoast']);
		}

		$image_id = 0;
		if (function_exists('wc_get_product')) {
			$product = wc_get_product($post_id);
			if ($product) {
				$image_id = (int) $product->get_image_id();
			}
		}
		if (!$image_id) {
			$image_id = (int) get_post_thumbnail_id($post_id);
		}
		if ($image_id && !empty($data['image_alt'])) {
			update_post_meta($image_id, '_wp_attachment_image_alt', $data['image_alt']);
		}

		$sizes_result = dornott_product_ensure_sizes($post_id, $data['sizes'] ?? []);

		clean_post_cache($post_id);

		$result['products'][$slug] = [
			'ok'      => true,
			'ID'      => $post_id,
			'title'   => $data['title'],
			'image_id'=> $image_id,
			'sizes'   => $sizes_result,
		];
	}

	if (function_exists('wc_get_page_id')) {
		$shop_id = (int) wc_get_page_id('shop');
		$catalog = dornott_catalog_seo_seed_data();
		if ($shop_id > 0 && !empty($catalog['yoast'])) {
			dornott_product_seo_apply_yoast($shop_id, $catalog['yoast']);
			clean_post_cache($shop_id);
			$result['shop'] = [
				'ok' => true,
				'ID' => $shop_id,
			];
		}
	}

	if (function_exists('wc_delete_product_transients')) {
		wc_delete_product_transients();
	}

	return $result;
}

/**
 * @param int $product_id
 * @param string[] $sizes
 * @return array<string, mixed>
 */
function dornott_product_ensure_sizes($product_id, array $sizes)
{
	if (!function_exists('wc_get_product') || empty($sizes)) {
		return [
			'ok'     => empty($sizes),
			'skipped'=> true,
			'sizes'  => $sizes,
		];
	}

	$product = wc_get_product($product_id);
	if (!$product) {
		return [
			'ok'    => false,
			'error' => 'Товар не найден',
		];
	}

	$regular_price = $product->get_regular_price();
	$sale_price = $product->get_sale_price();

	if ($product->is_type('variable')) {
		$min_regular = $product->get_variation_regular_price('min');
		if ($min_regular !== '' && $min_regular !== null) {
			$regular_price = $min_regular;
		}
		$min_sale = $product->get_variation_sale_price('min');
		if ($min_sale !== '' && $min_sale !== null && (float) $min_sale < (float) $regular_price) {
			$sale_price = $min_sale;
		}
	}

	$attr_name = 'Размер';
	$attr_slug = sanitize_title($attr_name);

	if (!$product->is_type('variable')) {
		wp_set_object_terms($product_id, 'variable', 'product_type');
		$product = new WC_Product_Variable($product_id);
	}

	$attribute = new WC_Product_Attribute();
	$attribute->set_id(0);
	$attribute->set_name($attr_name);
	$attribute->set_options($sizes);
	$attribute->set_position(0);
	$attribute->set_visible(true);
	$attribute->set_variation(true);
	$product->set_attributes([$attribute]);
	$product->save();

	$existing_by_size = [];
	foreach ($product->get_children() as $variation_id) {
		$variation = wc_get_product($variation_id);
		if (!$variation) {
			continue;
		}
		$attrs = $variation->get_attributes();
		$value = $attrs[$attr_slug] ?? (string) current($attrs);
		$existing_by_size[(string) $value] = $variation;
	}

	$created = [];
	foreach ($sizes as $size) {
		$variation = $existing_by_size[$size] ?? new WC_Product_Variation();
		if (!$variation->get_id()) {
			$variation->set_parent_id($product_id);
		}

		$variation->set_attributes([$attr_slug => $size]);
		if ($regular_price !== '' && $regular_price !== null) {
			$variation->set_regular_price($regular_price);
		}
		if ($sale_price !== '' && $sale_price !== null) {
			$variation->set_sale_price($sale_price);
		} else {
			$variation->set_sale_price('');
		}
		$variation->set_status('publish');
		$variation->set_manage_stock(false);
		$variation->set_stock_status('instock');
		$variation->save();
		$created[] = [
			'id'   => $variation->get_id(),
			'size' => $size,
		];
	}

	WC_Product_Variable::sync($product_id);
	wc_delete_product_transients($product_id);

	return [
		'ok'      => true,
		'attr'    => $attr_slug,
		'created' => $created,
	];
}

function dornott_product_seo_apply_sizes()
{
	$seed = dornott_product_seo_seed_data();
	$result = [];

	foreach ($seed as $slug => $data) {
		$post = get_page_by_path($slug, OBJECT, 'product');
		if (!$post) {
			$result[$slug] = [
				'ok'    => false,
				'error' => 'Товар не найден',
			];
			continue;
		}

		$result[$slug] = dornott_product_ensure_sizes((int) $post->ID, $data['sizes'] ?? []);
	}

	return $result;
}
