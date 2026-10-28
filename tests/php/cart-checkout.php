<?php
foreach (['cart-payment.php', 'cdek-client.php', 'cdek-delivery.php', 'cdek-processing.php', 'cart-checkout.php'] as $file) {
    if (file_exists(dirname(__DIR__, 2) . '/includes/' . $file)) require_once dirname(__DIR__, 2) . '/includes/' . $file;
}
$passed = 0; $failed = 0; $http = []; $bank_calls = []; $orders = []; $captcha_ok = true; $persist_ok = true;
$missing_measurement = false;
function check($ok, $label) { $GLOBALS[$ok ? 'passed' : 'failed']++; echo ($ok ? 'ok  - ' : 'FAIL - ') . $label . "\n"; }
function dornott_verify_smartcaptcha($token) { return $GLOBALS['captcha_ok']; }
function sanitize_title($text) { return strtolower($text); }
function sanitize_text_field($text) { return strip_tags($text); }
function sanitize_textarea_field($text) { return strip_tags($text); }
function sanitize_email($text) { return trim($text); }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
function apply_filters($hook, $settings) { return array_merge($settings, ['sender_name' => 'Sender', 'sender_phone' => '+79001112233']); }
function wc_get_weight($value, $unit) { return $value * 1000; }
function wc_get_dimension($value, $unit) { return $value; }
function admin_url($path) { return 'https://dornott.test/wp-admin/' . $path; }
function wp_json_encode($value) { return json_encode($value); }
function is_wp_error($value) { return $value instanceof Exception; }
function wp_remote_retrieve_response_code($value) { return $value['response']['code'] ?? 0; }
function wp_remote_retrieve_body($value) { return $value['body'] ?? ''; }
function wp_remote_retrieve_header($value, $key) { return ''; }
function set_transient($key, $value, $ttl) { if (!$GLOBALS['persist_ok']) return false; $GLOBALS['orders'][$key] = $value; return true; }
function get_transient($key) { return $GLOBALS['orders'][$key] ?? false; }
function response($data, $code = 200) { return ['response' => ['code' => $code], 'body' => json_encode($data)]; }
function wp_remote_request($url, $args) { if (!$GLOBALS['http']) throw new Exception('Unexpected API request'); return array_shift($GLOBALS['http']); }
function wp_remote_post($url, $args) { $GLOBALS['bank_calls'][] = json_decode($args['body'], true); return response(['Success' => true, 'PaymentId' => 'payment-99', 'PaymentURL' => 'https://bank.test/payment']); }
class CheckoutProduct {
    public function is_purchasable() { return true; }
    public function get_id() { return 1; }
    public function get_price() { return 1250; }
    public function get_regular_price() { return 1500; }
    public function get_name() { return 'Каталог'; }
    public function get_sku() { return 'SKU'; }
    public function get_weight() { return $GLOBALS['missing_measurement'] ? '' : '0.4'; }
    public function get_length() { return '40'; }
    public function get_width() { return '30'; }
    public function get_height() { return '25'; }
}
function wc_get_product($id) { return $id === 1 ? new CheckoutProduct() : false; }
function api_queue() {
    $GLOBALS['http'] = [response(['access_token' => 'token']),
        response([['code' => 'MTSHCH91', 'type' => 'PVZ', 'is_reception' => true, 'location' => ['country_code' => 'RU', 'city_code' => 123]]]),
        response([['code' => 'MSK1', 'type' => 'PVZ', 'is_handout' => true, 'location' => ['country_code' => 'RU', 'city_code' => 44, 'city' => 'Москва', 'address' => 'Реальный ПВЗ']]]),
        response(['delivery_sum' => 999]),
    ];
}
$_ENV = array_merge($_ENV, ['CDEK_CLIENT_ID' => 'id', 'CDEK_CLIENT_SECRET' => 'secret', 'CDEK_SENDER_NAME' => 'Sender', 'CDEK_SENDER_PHONE' => '+79001112233', 'YANDEX_MAPS_API_KEY' => 'maps-public', 'TBANK_TERMINAL_KEY' => 'terminal', 'TBANK_SECRET_KEY' => 'bank-secret']);
$payload = ['items' => [['id' => 1, 'quantity' => 2, 'price' => 1, 'shipping' => ['weight' => 1]]], 'totals' => ['finalPrice' => 1], 'order_info' => [
    ['name' => 'delivery', 'value' => 'cdek'], ['name' => 'delivery_price', 'value' => '500'], ['name' => 'cdek_pvz_code', 'value' => 'MSK1'],
    ['name' => 'last_name', 'value' => 'Иванов'], ['name' => 'first_name', 'value' => 'Иван'], ['name' => 'middle_name', 'value' => 'Иванович'],
    ['name' => 'phone', 'value' => '8 (900) 111-22-33'], ['name' => 'email', 'value' => 'buyer@example.com'], ['name' => 'address', 'value' => 'Fake'],
]];
check(function_exists('dornott_create_tbank_payment'), 'checkout business flow is available');
check(function_exists('dornott_checkout_recipient_name'), 'recipient full name is assembled and validated on the server');
if ($failed) exit(1);
foreach ([
    ['Иванов', 'Иван', 'Иванович', 'Иванов Иван Иванович'],
    ['Салтыков-Щедрин', 'Михаил', '', 'Салтыков-Щедрин Михаил'],
    ['О’Нил', '李', '', 'О’Нил 李'],
    ['  Иванов  ', '  Иван   Пётр  ', '', 'Иванов Иван Пётр'],
    ['Иванов (тест)', 'И.', '', 'Иванов (тест) И.'],
    ['', 'Иван', '', ''], ['Иванов', 'Иван123', '', ''], ['Иванов', 'Иван', 'Иванович2', ''],
] as $case) {
    $info = [['name' => 'last_name', 'value' => $case[0]], ['name' => 'first_name', 'value' => $case[1]], ['name' => 'middle_name', 'value' => $case[2]]];
    check(dornott_checkout_recipient_name($info) === $case[3], 'recipient name normalization and validation: ' . json_encode($case));
}
api_queue(); $result = dornott_create_tbank_payment($payload);
check($result['ok'], 'valid CDEK checkout produces a payment URL');
$bank = $bank_calls[0]; $order_id = $result['data']['orderId']; $pending = $orders[dornott_pending_order_transient_key($order_id)];
check($pending['username'] === 'Иванов Иван Иванович'
    && dornott_cdek_order_payload($pending, $order_id, dornott_cdek_config())['recipient']['name'] === 'Иванов Иван Иванович',
    'three name fields reach the saved order and CDEK without the legacy username field');
check($bank['Amount'] === 250000 && count($bank['Receipt']['Items']) === 1 && $bank['Receipt']['Items'][0]['Amount'] === 250000, 'bank amount and receipt contain only catalog-priced goods, no delivery');
check($bank['PayType'] === 'O' && $pending['amount'] === 250000, 'one-stage payment and expected captured amount are persisted');
check($pending['address'] === 'Реальный ПВЗ' && count($pending['cdek']['packages']) === 2 && $pending['cdek']['packages'][0]['weight'] === 400, 'authoritative PVZ and server packaging saved before exposing payment URL');
api_queue(); $second = dornott_create_tbank_payment($payload);
check($second['data']['orderId'] !== $order_id, 'same-second checkouts have distinct identifiers');
foreach ([
    ['+7 (960) 344-57-11', 'johnbalotello@gmail.com', '+79603445711'],
    ['+1 (202) 555-0123', 'buyer+test@example.museum', '+12025550123'],
    ['+33 1 23 45 67 89', 'buyer@example.fr', '+33123456789'],
    ['+81 3 1234 5678', 'buyer@example.jp', '+81312345678'],
] as $case) {
    $contact_payload = $payload;
    foreach ($contact_payload['order_info'] as &$entry) {
        if ($entry['name'] === 'phone') $entry['value'] = $case[0];
        if ($entry['name'] === 'email') $entry['value'] = $case[1];
    }
    unset($entry);
    api_queue(); $contact_result = dornott_create_tbank_payment($contact_payload);
    $contact_order = $contact_result['ok'] ? $orders[dornott_pending_order_transient_key($contact_result['data']['orderId'])] : [];
    $contact_bank = end($bank_calls);
    $contact_cdek = $contact_order ? dornott_cdek_order_payload($contact_order, $contact_result['data']['orderId'], dornott_cdek_config()) : [];
    check($contact_result['ok'] && $contact_bank['DATA']['Phone'] === $case[2] && $contact_cdek['recipient']['phones'][0]['number'] === $case[2] && $contact_order['email'] === $case[1], 'filled recipient contacts reach payment and CDEK: ' . $case[0]);
}
$bank_calls = []; $captcha_ok = false;
check(!dornott_create_tbank_payment($payload)['ok'] && !$bank_calls, 'invalid captcha never reaches bank');
$captcha_ok = true;
foreach (['last_name' => '', 'first_name' => 'Иван123', 'middle_name' => 'Иванович2', 'phone' => '+ () -', 'email' => 'broken-address'] as $field => $value) {
    $bad = $payload;
    foreach ($bad['order_info'] as &$entry) { if ($entry['name'] === $field) $entry['value'] = $value; }
    unset($entry);
    $http = [];
    check(!dornott_create_tbank_payment($bad)['ok'] && !$bank_calls, 'invalid recipient field blocks bank Init and delivery API: ' . $field);
}
$missing_measurement = true;
api_queue();
$fallback_checkout = dornott_create_tbank_payment($payload);
$fallback_order = $fallback_checkout['ok'] ? $orders[dornott_pending_order_transient_key($fallback_checkout['data']['orderId'])] : [];
check($fallback_checkout['ok'] && $fallback_order['cdek']['packages'][0]['weight'] === 400 && !empty($fallback_order['cdek']['packages'][0]['comment']), 'missing catalog weight uses the server template, not forged browser shipping facts');
$missing_measurement = false;
$bank_calls = [];
$bad = $payload; $bad['order_info'][0]['value'] = 'pickup';
check(!dornott_create_tbank_payment($bad)['ok'] && !$bank_calls, 'hidden store pickup cannot bypass required CDEK delivery');
$bad = $payload; $bad['order_info'][2]['value'] = '';
check(!dornott_create_tbank_payment($bad)['ok'] && !$bank_calls, 'missing PVZ cannot initialize a payable order');
$http = [response([], 403)];
check(!dornott_create_tbank_payment($payload)['ok'] && !$bank_calls, 'unavailable CDEK route fails before bank init');
api_queue(); $persist_ok = false;
$failure = dornott_create_tbank_payment($payload);
check(!$failure['ok'] && !isset($failure['data']['paymentUrl']), 'failed snapshot persistence never exposes the bank payment URL');
echo "\n$passed passed, $failed failed\n"; exit($failed ? 1 : 0);
