<?php

function dornott_create_tbank_payment($payload)
{
	if (!is_array($payload) || !$payload) {
		return ['ok' => false, 'data' => ['message' => 'Пустой запрос']];
	}

	$order_info = $payload['order_info'] ?? [];
	if (!dornott_verify_smartcaptcha(dornott_order_info_value($order_info, 'smart-token'))) {
		return ['ok' => false, 'data' => ['message' => 'Подтвердите, что вы не робот']];
	}

	$resolved = dornott_resolve_cart_items($payload['items'] ?? []);
	if (!$resolved['ok']) {
		return ['ok' => false, 'data' => ['message' => $resolved['message']]];
	}

	$delivery_slug = sanitize_title(dornott_order_info_value($order_info, 'delivery'));
	$delivery_price = dornott_lookup_delivery_price($delivery_slug, dornott_get_delivery_methods());
	if ($delivery_price === null) {
		return ['ok' => false, 'data' => ['message' => 'Выберите способ доставки']];
	}
	$shipping = null;
	if ($delivery_slug === 'cdek') {
		if (!dornott_cdek_ready()) return ['ok' => false, 'data' => ['message' => 'Доставка СДЭК пока не настроена. Свяжитесь с нами.']];
		$name = trim(dornott_order_info_value($order_info, 'username'));
		$phone = dornott_normalize_phone(dornott_order_info_value($order_info, 'phone'));
		$email_input = dornott_order_info_value($order_info, 'email');
		if ($name === '' || !preg_match('/^\+7\d{10}$/D', $phone) || !is_email($email_input)) {
			return ['ok' => false, 'data' => ['message' => 'Укажите имя, российский телефон и email получателя.']];
		}
		$shipping = dornott_cdek_prepare_checkout($order_info, $resolved['items']);
		if (!$shipping['ok']) return ['ok' => false, 'data' => ['message' => $shipping['message']]];
		$delivery_price = 0;
	}

	$totals = dornott_recalculate_totals($resolved['items'], $delivery_price);
	if ($totals['finalPrice'] <= 0) {
		return ['ok' => false, 'data' => ['message' => 'Некорректная сумма заказа']];
	}

	$terminal_key = $_ENV['TBANK_TERMINAL_KEY'] ?? '';
	$secret_key = $_ENV['TBANK_SECRET_KEY'] ?? '';
	if ($terminal_key === '' || $secret_key === '') {
		return ['ok' => false, 'data' => ['message' => 'Платежный сервис недоступен']];
	}

	$email = sanitize_email(dornott_order_info_value($order_info, 'email'));
	$raw_phone = dornott_order_info_value($order_info, 'phone');
	$clean_phone = dornott_normalize_phone($raw_phone);
	$order_id = bin2hex(random_bytes(12));
	$amount = intval($totals['finalPrice']) * 100;

	$params = [
		'TerminalKey'      => $terminal_key,
		'Amount'           => $amount,
		'OrderId'          => $order_id,
		'Description'      => 'Оплата заказа №' . $order_id,
		'NotificationURL'  => admin_url('admin-ajax.php?action=dornott_tbank_notification'),
		'PayType'          => 'O',
		'DATA'             => [
			'Email' => $email,
			'Phone' => $clean_phone,
		],
		'Receipt'          => [
			'Email'    => $email,
			'Phone'    => $clean_phone,
			'Taxation' => 'osn',
			'Items'    => dornott_build_receipt_items($resolved['items'], $delivery_price),
		],
	];

	$result = dornott_tbank_request('Init', $params, $secret_key);
	if (!$result['ok'] || empty($result['body']['PaymentURL'])) {
		$error = $result['body']['Message'] ?? 'Ошибка API';
		$details = $result['body']['Details'] ?? ($result['message'] ?? '');
		return ['ok' => false, 'data' => ['message' => $result['body'] ? "Банк: $error. $details" : ($result['message'] ?: 'Ошибка API')]];
	}

	$payment_id = $result['body']['PaymentId'] ?? '';
	if ($payment_id === '') {
		return ['ok' => false, 'data' => ['message' => 'Банк не вернул идентификатор платежа']];
	}

	if (!dornott_store_pending_order($order_id, [
		'order_id'    => $order_id,
		'payment_id'  => $payment_id,
		'items'       => $resolved['items'],
		'totals'      => $totals,
		'username'    => sanitize_text_field(dornott_order_info_value($order_info, 'username')),
		'phone'       => sanitize_text_field($raw_phone),
		'email'       => $email,
		'city'        => $shipping ? $shipping['city'] : '',
		'address'     => $shipping ? $shipping['address'] : '',
		'delivery'    => $delivery_slug,
		'cdek'        => $shipping ? $shipping['cdek'] : null,
		'amount'      => $amount,
		'terminal_key' => $terminal_key,
		'created_at'  => time(),
		'message'     => sanitize_textarea_field(dornott_order_info_value($order_info, 'message')),
	])) {
		return ['ok' => false, 'data' => ['message' => 'Не удалось сохранить заказ. Попробуйте позже.']];
	}

	return ['ok' => true, 'data' => [
		'paymentUrl' => $result['body']['PaymentURL'],
		'orderId'    => $order_id,
	]];
}
