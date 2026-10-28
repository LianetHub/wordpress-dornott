<?php
ob_start();
// Real client/formatter with a deterministic HTTP boundary; no real CDEK requests.
require_once dirname(__DIR__, 2) . '/includes/cart-payment.php';
foreach (['cdek-client.php', 'cdek-delivery.php'] as $file) {
    if (file_exists(dirname(__DIR__, 2) . '/includes/' . $file)) require_once dirname(__DIR__, 2) . '/includes/' . $file;
}
$passed = 0; $failed = 0;
function check($ok, $label) { $GLOBALS[$ok ? 'passed' : 'failed']++; echo ($ok ? 'ok  - ' : 'FAIL - ') . $label . "\n"; }
function is_wp_error($value) { return $value instanceof Exception; }
function wp_json_encode($value) { return json_encode($value); }
function wp_remote_retrieve_response_code($value) { return $value['response']['code'] ?? 0; }
function wp_remote_retrieve_body($value) { return $value['body'] ?? ''; }
function wp_remote_retrieve_header($value, $key) { return $value['headers'][$key] ?? ''; }
function wp_send_json($data, $status = 200) { throw new JsonResult($data, $status); }
class JsonResult extends Exception { public $data; public function __construct($data, $status) { parent::__construct('', $status); $this->data = $data; } }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function nocache_headers() {}
function apply_filters($hook, $settings) { return array_merge($settings, ['sender_name' => 'Sender', 'sender_phone' => '+79001112233']); }
function wc_get_weight($value, $unit) { return $value * 1000; }
function wc_get_dimension($value, $unit) { return $value * 100; }
class MeasuredCdekProduct {
    public function get_weight() { return '0.4'; }
    public function get_length() { return '0.4'; }
    public function get_width() { return '0.3'; }
    public function get_height() { return '0.25'; }
}
function http_response($data, $code = 200) { return ['response' => ['code' => $code], 'body' => json_encode($data)]; }
check(class_exists('Dornott_Cdek_Client') && function_exists('dornott_cdek_prepare_checkout'), 'CDEK implementation is available');
if ($failed) exit(1);

$config = ['base_url' => 'https://api.edu.cdek.ru/v2', 'client_id' => 'test-id', 'client_secret' => 'test-secret', 'shipment_point' => 'MTSHCH91', 'sender_name' => 'Отправитель', 'sender_phone' => '+79001112233', 'tariff_code' => 136];
$queue = []; $calls = [];
$transport = function ($url, $args) use (&$queue, &$calls) { $calls[] = compact('url', 'args'); if (!$queue) throw new Exception('Unexpected HTTP call'); return array_shift($queue); };
$queue = [http_response(['access_token' => 'token']), http_response([]), http_response([])];
$client = new Dornott_Cdek_Client($config, $transport);
check($client->request('GET', 'deliverypoints', ['country_code' => 'RU'])['ok'], 'authenticated API call succeeds');
$client->request('GET', 'deliverypoints', ['code' => 'MSK1']);
check(count($calls) === 3, 'one token is reused across calls');
check(strpos($calls[1]['url'], 'country_code=RU') !== false, 'GET query is encoded');
check($calls[1]['args']['headers']['Authorization'] === 'Bearer token', 'token reaches only the server HTTP client');
check($calls[0]['args']['timeout'] <= 10 && $calls[1]['args']['redirection'] === 0, 'requests have bounded timeouts and no redirects');

$queue = [http_response(['access_token' => 'old']), http_response([], 401), http_response(['access_token' => 'new']), http_response([])]; $calls = [];
$client = new Dornott_Cdek_Client($config, $transport);
check($client->request('GET', 'deliverypoints')['ok'] && count($calls) === 4, 'expired token refreshed once after 401');
$queue = [http_response(['access_token' => 'old']), http_response([], 401), http_response(['access_token' => 'new']), http_response([], 401)];
check(!(new Dornott_Cdek_Client($config, $transport))->request('GET', 'deliverypoints')['ok'], 'repeated 401 stops retrying');
$queue = [http_response([], 403)];
check(!(new Dornott_Cdek_Client($config, $transport))->request('GET', 'deliverypoints')['ok'], 'failed authorization is reported');
$queue = [http_response(['access_token' => 'token']), new Exception('timeout')];
check((new Dornott_Cdek_Client($config, $transport))->request('POST', 'orders', [])['uncertain'], 'lost creation response is uncertain, not safe to recreate');
$queue = [http_response(['access_token' => 'token']), ['response' => ['code' => 200], 'body' => '<html>error</html>']];
check(!(new Dornott_Cdek_Client($config, $transport))->request('GET', 'deliverypoints')['ok'], 'invalid JSON is an API failure');
$calls = []; $bad_config = array_merge($config, ['base_url' => 'https://attacker.test/v2']);
check(!(new Dornott_Cdek_Client($bad_config, $transport))->request('GET', 'deliverypoints')['ok'] && !$calls, 'credentials cannot be sent to an arbitrary API host');

$items = [['id' => 12, 'sku' => 'SKU12', 'name' => 'Товар', 'price' => 1250, 'regular_price' => 1500, 'quantity' => 2,
    'shipping' => ['weight' => 400, 'length' => 40, 'width' => 30, 'height' => 25]]];
$packages = dornott_cdek_packages($items);
check(count($packages) === 2 && $packages[0]['weight'] === 400, 'each unit uses its catalog weight in a separate parcel');
check($packages[0]['items'][0]['payment']['value'] === 0 && $packages[0]['items'][0]['cost'] === 1250.0, 'prepaid items have zero COD while retaining declared value');
check($packages[0]['items'][0]['amount'] === 1 && $packages[1]['number'] === '2', 'each parcel has one unit and a unique number');
$measured = $items; $measured[0]['quantity'] = 1; $measured[0]['shipping'] = ['weight' => 700, 'length' => 15, 'width' => 10, 'height' => 5];
check(dornott_cdek_packages($measured)[0]['weight'] === 700, 'catalog shipping data determines parcel weight');
foreach (['weight', 'length', 'width', 'height'] as $field) {
    $incomplete = $items; unset($incomplete[0]['shipping'][$field]);
    $fallback = dornott_cdek_packages($incomplete);
    check(count($fallback) === 2 && $fallback[0][$field] === $items[0]['shipping'][$field] && !empty($fallback[0]['comment']), "missing $field gets the PHP template and a review comment");
    $incomplete[0]['shipping'][$field] = 0;
    check(count(dornott_cdek_packages($incomplete)) === 2, "zero $field gets the PHP template");
}
check(!isset($packages[0]['comment']), 'complete catalog measurements are preserved without marking a default');
$partial = $items; $partial[0]['shipping'] = ['weight' => 700];
$fallback = dornott_cdek_packages($partial);
check($fallback[0]['weight'] === 700 && $fallback[0]['length'] === 40 && $fallback[0]['width'] === 30 && $fallback[0]['height'] === 25, 'defaults fill only missing fields and preserve measured catalog weight');
check(dornott_cdek_product_shipping(new MeasuredCdekProduct()) === ['weight' => 400, 'length' => 40, 'width' => 30, 'height' => 25], 'WooCommerce conversion helpers resolve catalog kilograms/metres into grams/centimetres');
$_ENV['CDEK_SHIPMENT_POINT'] = 'ENV-POINT'; $_ENV['CDEK_TARIFF_CODE'] = '999'; $_ENV['CDEK_API_BASE_URL'] = 'https://api.edu.cdek.ru/v2';
$settings = dornott_cdek_config();
check($settings['shipment_point'] === 'MTSHCH91' && $settings['tariff_code'] === 136 && $settings['base_url'] === 'https://api.cdek.ru/v2', 'shipment settings come from PHP rather than legacy env values');
$_ENV['CDEK_DEFAULT_PACKAGE_WEIGHT_G'] = '9999';
$blank = $items; $blank[0]['shipping'] = [];
check(dornott_cdek_packages($blank)[0]['weight'] === 400, 'fallback packaging is configured in PHP, not legacy ENV');

$origin = ['code' => 'MTSHCH91', 'type' => 'PVZ', 'is_reception' => true, 'location' => ['country_code' => 'RU', 'city_code' => 123, 'city' => 'Мытищи', 'address' => 'Отправление']];
$office = ['code' => 'MSK1', 'type' => 'PVZ', 'is_handout' => true, 'location' => ['country_code' => 'RU', 'city_code' => 44, 'city' => 'Москва', 'address' => 'Проверенный адрес']];
$info = [['name' => 'cdek_pvz_code', 'value' => 'MSK1'], ['name' => 'city', 'value' => 'Поддельный город'], ['name' => 'address', 'value' => 'Поддельный адрес']];
$queue = [http_response(['access_token' => 'token']), http_response([$origin]), http_response([$office]), http_response(['delivery_sum' => 234.5])]; $calls = [];
$checkout = dornott_cdek_prepare_checkout($info, $items, new Dornott_Cdek_Client($config, $transport), $config);
check($checkout['ok'] && $checkout['delivery_price'] === 0, 'available CDEK tariff stays free for the buyer');
check($checkout['address'] === 'Проверенный адрес' && $checkout['city'] === 'Москва', 'destination comes from CDEK, not client text');
$calc = json_decode($calls[3]['args']['body'], true);
check($calc['from_location']['code'] === 123 && $calc['to_location']['code'] === 44 && $calc['tariff_code'] === 136, 'tariff checked on the server between real origin and destination');
check(!dornott_cdek_prepare_checkout([], $items, new Dornott_Cdek_Client($config, $transport), $config)['ok'], 'missing PVZ rejected before payment');
foreach (['foreign', 'postamat', 'wrong_code', 'closed'] as $kind) {
    $invalid = $office;
    if ($kind === 'foreign') $invalid['location']['country_code'] = 'KZ';
    if ($kind === 'postamat') $invalid['type'] = 'POSTAMAT';
    if ($kind === 'wrong_code') $invalid['code'] = 'OTHER';
    if ($kind === 'closed') $invalid['is_handout'] = false;
    $queue = [http_response(['access_token' => 'token']), http_response([$origin]), http_response([$invalid])];
    check(!dornott_cdek_prepare_checkout($info, $items, new Dornott_Cdek_Client($config, $transport), $config)['ok'], "reject $kind PVZ");
}
$queue = [http_response(['access_token' => 'token']), http_response([$origin]), http_response([$office]), http_response(['errors' => [['code' => 'ERR_INVALID_TARIFF']]], 400)];
check(!dornott_cdek_prepare_checkout($info, $items, new Dornott_Cdek_Client($config, $transport), $config)['ok'], 'unavailable route cannot be paid as CDEK');
$order = ['username' => 'Иван Иванов', 'phone' => '+79001112244', 'email' => 'buyer@example.com', 'items' => $items, 'cdek' => $checkout['cdek']];
$payload = dornott_cdek_order_payload($order, 'ORDER-42', $config);
check($payload['number'] === 'ORDER-42' && $payload['shipment_point'] === 'MTSHCH91' && $payload['delivery_point'] === 'MSK1', 'stable website order number and selected PVZ reach CDEK');
check($payload['delivery_recipient_cost']['value'] === 0 && $payload['recipient']['name'] === 'Иван Иванов', 'recipient owes no delivery charge');
check(!isset($payload['intake_date']) && !isset($payload['to_location']), 'PVZ shipment does not request a courier or substitute a street address');
$payload_with_note = dornott_cdek_order_payload($order, 'ORDER-42', dornott_cdek_config());
check($payload_with_note['comment'] === 'Заказ оплачен. Доставка за счёт отправителя.', 'waybill without a customer comment contains only payment and delivery information');
$order_with_note = $order;
$order_with_note['message'] = "  Позвоните перед выдачей.\nПолучатель будет после 18:00.  ";
$payload_with_note = dornott_cdek_order_payload($order_with_note, 'ORDER-42', $config);
check($payload_with_note['comment'] === "Заказ оплачен. Доставка за счёт отправителя. Комментарий клиента: Позвоните перед выдачей.\nПолучатель будет после 18:00.", 'customer checkout comment reaches the waybill with a clear label');
$order_with_note['message'] = " \n\t ";
check(dornott_cdek_order_payload($order_with_note, 'ORDER-42', $config)['comment'] === $payload['comment'], 'blank customer comment does not add an empty label');
check(!array_key_exists('package_comment', require dirname(__DIR__, 2) . '/includes/cdek-settings.php'), 'settings no longer contain the mock packaging comment');

// The real published widget requests page=1,size=1 to discover total count,
// then fetches 500-point pages. Its proxy must preserve that contract.
$_ENV = array_merge($_ENV, ['CDEK_CLIENT_ID' => 'test-id', 'CDEK_CLIENT_SECRET' => 'test-secret', 'CDEK_SENDER_NAME' => 'Sender', 'CDEK_SENDER_PHONE' => '+79001112233', 'YANDEX_MAPS_API_KEY' => 'public']);
function wp_remote_request($url, $args) { $GLOBALS['calls'][] = compact('url', 'args'); return array_shift($GLOBALS['queue']); }
$_GET = ['action' => 'offices', 'page' => '1', 'size' => '1', 'country_code' => 'KZ'];
$queue = [http_response(['access_token' => 'token']), array_merge(http_response([$office]), ['headers' => ['x-total-elements' => '1200']])]; $calls = [];
try { dornott_cdek_widget_response(); } catch (JsonResult $json) {
    check($json->getCode() === 200 && $json->data[0]['code'] === 'MSK1', 'widget receives raw PVZ records rather than a WordPress success envelope');
}
$query = []; parse_str(parse_url($calls[1]['url'], PHP_URL_QUERY), $query);
check(($query['page'] ?? null) === '1' && ($query['size'] ?? null) === '1' && $query['country_code'] === 'RU', 'widget pagination forwarded while destination country cannot be overridden');
$queue = [http_response(['access_token' => 'token']), array_merge(http_response([]), ['headers' => ['x-total-elements' => '1200']])];
check((new Dornott_Cdek_Client($config, $transport))->request('GET', 'deliverypoints')['total'] === '1200', 'pickup total-count header survives the API client');
$_GET = ['action' => 'calculate']; $calls = [];
try { dornott_cdek_widget_response(); } catch (JsonResult $json) { check($json->getCode() === 400 && !$calls, 'public proxy cannot calculate arbitrary shipments or create orders'); }
echo "\n$passed passed, $failed failed\n"; exit($failed ? 1 : 0);
