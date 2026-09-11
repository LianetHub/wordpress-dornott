<?php

if (!defined('DORNOTT_CART_PAYMENT_LOADED')) {
	define('DORNOTT_CART_PAYMENT_LOADED', true);
}

function dornott_cart_ids_equal($a, $b)
{
	return (string) $a === (string) $b;
}

function dornott_clamp_qty($qty)
{
	$qty = intval($qty);
	if ($qty < 1) {
		return 1;
	}
	if ($qty > 999) {
		return 999;
	}
	return $qty;
}

function dornott_normalize_phone($raw_phone)
{
	$clean_phone = '+' . preg_replace('/[^0-9]/', '', (string) $raw_phone);
	if (strpos($clean_phone, '+8') === 0) {
		$clean_phone = '+7' . substr($clean_phone, 2);
	}
	return $clean_phone;
}

function dornott_order_info_value($order_info, $name)
{
	if (!is_array($order_info)) {
		return '';
	}

	foreach ($order_info as $info) {
		if (!is_array($info)) {
			continue;
		}
		if (($info['name'] ?? '') === $name) {
			return (string) ($info['value'] ?? '');
		}
	}

	return '';
}

function dornott_recalculate_totals($items, $delivery_price = 0)
{
	$subtotal = 0;
	$discount = 0;
	$total_qty = 0;
	$delivery_price = intval($delivery_price);

	if (is_array($items)) {
		foreach ($items as $item) {
			$qty = dornott_clamp_qty($item['quantity'] ?? 1);
			$regular = intval($item['regular_price'] ?? 0);
			$price = intval($item['price'] ?? 0);
			if ($regular <= 0) {
				$regular = $price;
			}
			$subtotal += $regular * $qty;
			$discount += ($regular - $price) * $qty;
			$total_qty += $qty;
		}
	}

	return [
		'subtotal'       => $subtotal,
		'discount'       => $discount,
		'totalQty'       => $total_qty,
		'deliveryPrice'  => $delivery_price,
		'finalPrice'     => $subtotal - $discount + $delivery_price,
	];
}

function dornott_lookup_delivery_price($slug, $methods)
{
	$slug = (string) $slug;
	if ($slug === '' || !is_array($methods)) {
		return null;
	}

	foreach ($methods as $method) {
		if (!is_array($method)) {
			continue;
		}
		if (($method['slug'] ?? '') === $slug) {
			return intval($method['price'] ?? 0);
		}
	}

	return null;
}

function dornott_tbank_sign($params, $secret_key)
{
	$token_params = [];

	foreach ((array) $params as $key => $value) {
		if ($key === 'Token' || $key === 'Receipt' || $key === 'DATA' || is_array($value)) {
			continue;
		}
		$token_params[$key] = (string) $value;
	}

	$token_params['Password'] = (string) $secret_key;
	ksort($token_params);

	$token_str = '';
	foreach ($token_params as $val) {
		$token_str .= (string) $val;
	}

	return hash('sha256', $token_str);
}

function dornott_is_paid_status($status)
{
	return in_array(strtoupper((string) $status), ['CONFIRMED', 'AUTHORIZED'], true);
}

function dornott_pending_order_transient_key($order_id)
{
	return 'dornott_order_' . preg_replace('/[^0-9A-Za-z_-]/', '', (string) $order_id);
}

function dornott_build_receipt_items($items, $delivery_price = 0)
{
	$receipt = [];

	if (is_array($items)) {
		foreach ($items as $item) {
			$qty = dornott_clamp_qty($item['quantity'] ?? 1);
			$price = intval($item['price'] ?? 0);
			$name = (string) ($item['name'] ?? 'Товар');
			if (function_exists('mb_strimwidth')) {
				$name = mb_strimwidth($name, 0, 128);
			} else {
				$name = substr($name, 0, 128);
			}

			$receipt[] = [
				'Name'     => $name,
				'Price'    => $price * 100,
				'Quantity' => $qty,
				'Amount'   => $price * $qty * 100,
				'Tax'      => 'none',
			];
		}
	}

	$delivery_price = intval($delivery_price);
	if ($delivery_price > 0) {
		$receipt[] = [
			'Name'     => 'Доставка',
			'Price'    => $delivery_price * 100,
			'Quantity' => 1,
			'Amount'   => $delivery_price * 100,
			'Tax'      => 'none',
		];
	}

	return $receipt;
}

function dornott_resolve_cart_item_from_product($client_item, $product)
{
	if (!$product) {
		return null;
	}

	$purchasable = true;
	if (is_object($product) && method_exists($product, 'is_purchasable')) {
		$purchasable = (bool) $product->is_purchasable();
	}
	if (!$purchasable) {
		return null;
	}

	$qty = dornott_clamp_qty($client_item['quantity'] ?? 1);
	$price = 0;
	$regular = 0;
	$name = '';
	$sku = '';
	$id = 0;

	if (is_object($product)) {
		$id = method_exists($product, 'get_id') ? intval($product->get_id()) : intval($client_item['id'] ?? 0);
		$price = method_exists($product, 'get_price') ? intval($product->get_price()) : 0;
		$regular = method_exists($product, 'get_regular_price') ? intval($product->get_regular_price()) : 0;
		$name = method_exists($product, 'get_name') ? (string) $product->get_name() : '';
		$sku = method_exists($product, 'get_sku') ? (string) $product->get_sku() : '';
	} elseif (is_array($product)) {
		$id = intval($product['id'] ?? ($client_item['id'] ?? 0));
		$price = intval($product['price'] ?? 0);
		$regular = intval($product['regular_price'] ?? 0);
		$name = (string) ($product['name'] ?? '');
		$sku = (string) ($product['sku'] ?? '');
		if (array_key_exists('purchasable', $product) && !$product['purchasable']) {
			return null;
		}
	}

	if ($regular <= 0) {
		$regular = $price;
	}

	return [
		'id'            => $id,
		'name'          => $name,
		'sku'           => $sku,
		'price'         => $price,
		'regular_price' => $regular,
		'quantity'      => $qty,
	];
}

function dornott_get_delivery_methods()
{
	$methods = [];

	if (!function_exists('have_rows') || !have_rows('delivery', 'option')) {
		return $methods;
	}

	while (have_rows('delivery', 'option')) {
		the_row();
		$name = (string) get_sub_field('company_name');
		$methods[] = [
			'slug'  => function_exists('sanitize_title') ? sanitize_title($name) : strtolower($name),
			'price' => intval(get_sub_field('company_delivery_price')),
			'name'  => $name,
		];
	}

	return $methods;
}

function dornott_resolve_cart_items($client_items)
{
	if (empty($client_items) || !is_array($client_items)) {
		return ['ok' => false, 'message' => 'Корзина пуста', 'items' => []];
	}

	$resolved = [];

	foreach ($client_items as $item) {
		$id = intval($item['id'] ?? 0);
		if ($id <= 0) {
			return ['ok' => false, 'message' => 'Некорректный товар', 'items' => []];
		}

		if (!function_exists('wc_get_product')) {
			return ['ok' => false, 'message' => 'Каталог недоступен', 'items' => []];
		}

		$product = wc_get_product($id);
		$resolved_item = dornott_resolve_cart_item_from_product($item, $product);
		if (!$resolved_item) {
			return ['ok' => false, 'message' => 'Товар недоступен', 'items' => []];
		}

		$resolved[] = $resolved_item;
	}

	return ['ok' => true, 'message' => '', 'items' => $resolved];
}

function dornott_verify_ajax_nonce()
{
	if (!function_exists('wp_verify_nonce')) {
		return false;
	}

	$nonce = '';
	if (!empty($_POST['_ajax_nonce'])) {
		$nonce = $_POST['_ajax_nonce'];
	} elseif (!empty($_POST['_wpnonce'])) {
		$nonce = $_POST['_wpnonce'];
	} elseif (!empty($_SERVER['HTTP_X_WP_NONCE'])) {
		$nonce = $_SERVER['HTTP_X_WP_NONCE'];
	}

	return (bool) wp_verify_nonce($nonce, 'dornott_cart');
}

function dornott_tbank_request($endpoint, $params, $secret_key)
{
	$params['Token'] = dornott_tbank_sign($params, $secret_key);

	if (!function_exists('wp_remote_post')) {
		return ['ok' => false, 'body' => null, 'message' => 'HTTP клиент недоступен'];
	}

	$response = wp_remote_post('https://securepay.tinkoff.ru/v2/' . ltrim($endpoint, '/'), [
		'headers' => [
			'Content-Type' => 'application/json',
			'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
		],
		'body'    => wp_json_encode($params),
		'timeout' => 30,
	]);

	if (is_wp_error($response)) {
		return ['ok' => false, 'body' => null, 'message' => $response->get_error_message()];
	}

	$body = json_decode(wp_remote_retrieve_body($response), true);

	return [
		'ok'      => !empty($body['Success']),
		'body'    => is_array($body) ? $body : null,
		'message' => $body['Details'] ?? ($body['Message'] ?? ''),
	];
}

function dornott_tbank_get_state($payment_id)
{
	$terminal_key = $_ENV['TBANK_TERMINAL_KEY'] ?? '';
	$secret_key = $_ENV['TBANK_SECRET_KEY'] ?? '';

	if ($payment_id === '' || $terminal_key === '' || $secret_key === '') {
		return '';
	}

	$result = dornott_tbank_request('GetState', [
		'TerminalKey' => $terminal_key,
		'PaymentId'   => $payment_id,
	], $secret_key);

	if (!$result['ok'] || empty($result['body']['Status'])) {
		return '';
	}

	return (string) $result['body']['Status'];
}

function dornott_verify_tbank_paid($pending)
{
	if (!is_array($pending) || empty($pending['payment_id'])) {
		return false;
	}

	return dornott_is_paid_status(dornott_tbank_get_state($pending['payment_id']));
}
