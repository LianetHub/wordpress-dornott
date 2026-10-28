<?php
ob_start();
require_once dirname(__DIR__, 2) . '/includes/cart-payment.php';
require_once dirname(__DIR__, 2) . '/includes/cdek-client.php';
require_once dirname(__DIR__, 2) . '/includes/cdek-delivery.php';
require_once dirname(__DIR__, 2) . '/includes/order-notifications.php';
if (file_exists(dirname(__DIR__, 2) . '/includes/cdek-processing.php')) require_once dirname(__DIR__, 2) . '/includes/cdek-processing.php';
$orders = []; $mail_calls = []; $events = []; $http = []; $calls = []; $locked = false; $status = 200; $passed = 0; $failed = 0;
$cache = []; $cache_mode = false; $on_lock = null; $schedule_ok = true; $persist_ok = true; $fail_addresses = [];
function check($ok, $label) { $GLOBALS[$ok ? 'passed' : 'failed']++; echo ($ok ? 'ok  - ' : 'FAIL - ') . $label . "\n"; }
function get_transient($key) {
    if ($GLOBALS['cache_mode']) {
        if (!array_key_exists($key, $GLOBALS['cache'])) $GLOBALS['cache'][$key] = $GLOBALS['orders'][$key] ?? false;
        return $GLOBALS['cache'][$key];
    }
    return $GLOBALS['orders'][$key] ?? false;
}
function set_transient($key, $value, $ttl) { if (!$GLOBALS['persist_ok']) return false; $GLOBALS['orders'][$key] = $value; if ($GLOBALS['cache_mode']) $GLOBALS['cache'][$key] = $value; return true; }
function wp_using_ext_object_cache() { return false; }
function wp_cache_delete($key, $group) { if ($group === 'options' && strpos($key, '_transient_') === 0) unset($GLOBALS['cache'][substr($key, 11)]); }
function wp_next_scheduled($hook, $args) { return $GLOBALS['events'][$hook . $args[0]] ?? false; }
function wp_schedule_single_event($time, $hook, $args) { if (!$GLOBALS['schedule_ok']) return false; $GLOBALS['events'][$hook . $args[0]] = $time; return true; }
function is_wp_error($value) { return $value instanceof Exception; }
function wp_remote_request($url, $args) { $GLOBALS['calls'][] = compact('url', 'args'); if (!$GLOBALS['http']) throw new Exception('Unexpected HTTP call'); return array_shift($GLOBALS['http']); }
function wp_remote_retrieve_response_code($value) { return $value['response']['code'] ?? 0; }
function wp_remote_retrieve_body($value) { return $value['body'] ?? ''; }
function wp_remote_retrieve_header($value, $key) { return $value['headers'][$key] ?? ''; }
function wp_json_encode($value) { return json_encode($value); }
function response($data, $code = 200) { return ['response' => ['code' => $code], 'body' => json_encode($data)]; }
function sanitize_text_field($value) { return (string) $value; }
function sanitize_textarea_field($value) { return (string) $value; }
function sanitize_email($value) { return trim((string) $value); }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
function apply_filters($hook, $settings) { return array_merge($settings, ['sender_name' => 'Sender', 'sender_phone' => '+79001112233']); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function wp_kses_post($value) { return $value; }
function get_form_recipients() { return 'owner@example.com'; }
function wp_mail($to, $subject, $body, $headers = []) { $GLOBALS['mail_calls'][] = compact('to', 'subject', 'body'); return !in_array($to, $GLOBALS['fail_addresses'], true); }
function status_header($code) { $GLOBALS['status'] = $code; }
function wp_die($message = '', $title = '', $args = []) { $GLOBALS['status'] = $args['response'] ?? 200; }
class TestDatabase {
    public function prepare($query, $value) { return $query . $value; }
    public function get_var($query) {
        if (strpos($query, 'GET_LOCK') === false) return '1';
        if ($GLOBALS['on_lock']) { $callback = $GLOBALS['on_lock']; $GLOBALS['on_lock'] = null; $callback(); }
        return $GLOBALS['locked'] ? '0' : '1';
    }
}
$wpdb = new TestDatabase();
function webhook($payload) { $_POST = $payload; $GLOBALS['status'] = 200; ob_start(); handle_tbank_notification(); return ['body' => ob_get_clean(), 'status' => $GLOBALS['status']]; }
function signed($changes = []) { $p = array_merge(['TerminalKey' => 'demo', 'OrderId' => '42', 'PaymentId' => '99', 'Amount' => 5000, 'Status' => 'CONFIRMED', 'Success' => true], $changes); $p['Token'] = dornott_tbank_sign($p, 'secret'); return $p; }
$_ENV = array_merge($_ENV, ['TBANK_SECRET_KEY' => 'secret', 'TBANK_TERMINAL_KEY' => 'demo', 'CDEK_CLIENT_ID' => 'id', 'CDEK_CLIENT_SECRET' => 'secret', 'CDEK_SENDER_NAME' => 'Sender', 'CDEK_SENDER_PHONE' => '+79001112233']);
$pending = ['order_id' => '42', 'payment_id' => '99', 'amount' => 5000, 'terminal_key' => 'demo', 'email' => 'buyer@example.com', 'username' => 'Покупатель', 'phone' => '+79001112233', 'city' => 'Москва', 'address' => 'Улица 1', 'delivery' => 'cdek', 'created_at' => time(),
    'items' => [['id' => 1, 'name' => 'Товар', 'price' => 50, 'quantity' => 1]], 'totals' => ['deliveryPrice' => 0, 'finalPrice' => 50],
    'cdek' => ['pvz_code' => 'MSK1', 'tariff_code' => 136, 'shipment_point' => 'MTSHCH91', 'packages' => [['number' => '1', 'weight' => 400, 'length' => 40, 'width' => 30, 'height' => 25, 'items' => [['name' => 'Товар', 'ware_key' => '1', 'payment' => ['value' => 0], 'cost' => 50, 'weight' => 400, 'amount' => 1]]]]]];
$key = dornott_pending_order_transient_key('42');
check(function_exists('dornott_cdek_process'), 'paid-order worker is available');
if ($failed) exit(1);
foreach ([['Status' => 'AUTHORIZED'], ['Amount' => 1], ['TerminalKey' => 'other'], ['Success' => false], ['PaymentId' => 'other']] as $changes) {
    $orders[$key] = $pending; $mail_calls = []; $events = [];
    webhook(signed($changes));
    check(!$mail_calls && !$events, 'unconfirmed or mismatched bank notification cannot send mail or create shipment: ' . json_encode($changes));
}
$orders[$key] = $pending; $mail_calls = []; $events = []; $calls = [];
check(webhook(signed()) === ['body' => 'OK', 'status' => 200], 'payment is acknowledged before any CDEK HTTP request');
webhook(signed());
check(count($mail_calls) === 2 && !$calls && count($events) === 1, 'repeated bank notifications send mail and enqueue shipment once');
check(strpos($mail_calls[0]['body'], 'MSK1') !== false, 'order email contains the selected PVZ code');
$http = [response(['access_token' => 'token']), response(['entity' => ['uuid' => 'uuid-42'], 'requests' => [['type' => 'CREATE', 'state' => 'ACCEPTED']]], 202)];
$events = []; dornott_cdek_process('42');
check($orders[$key]['cdek_state']['status'] === 'submitted', 'UUID alone means submitted, not a successfully created waybill');
check(count($mail_calls) === 2, 'success notice waits for actual CDEK processing');
$http = [response(['access_token' => 'token']), response(['entity' => ['uuid' => 'uuid-42', 'cdek_number' => '1234567890'], 'requests' => [['type' => 'CREATE', 'state' => 'SUCCESSFUL']]])];
dornott_cdek_process('42');
check($orders[$key]['cdek_state']['status'] === 'created', 'successful CDEK processing marks the shipment created');
check(count($mail_calls) === 3 && $mail_calls[2]['to'] === 'owner@example.com' && strpos($mail_calls[2]['body'], '1234567890') !== false, 'waybill result goes only to owner with a review reminder');
$before = count($calls); dornott_cdek_process('42');
check(count($calls) === $before && count($mail_calls) === 3, 'repeated worker does not recreate shipment or resend notice');

// Lost POST response: later attempts may only look up the stable website number.
$orders[$key] = $pending; $mail_calls = []; $calls = []; webhook(signed());
$http = [response(['access_token' => 'token']), new Exception('lost reply')];
dornott_cdek_process('42');
check($orders[$key]['cdek_state']['status'] === 'unknown', 'lost creation response is persisted as unknown');
$http = [response(['access_token' => 'token']), response(['message' => 'not found'], 404)];
dornott_cdek_process('42');
$posts = array_filter($calls, function ($call) { return substr($call['url'], -7) === '/orders' && $call['args']['method'] === 'POST'; });
check(count($posts) === 1 && strpos(end($calls)['url'], 'im_number=42') !== false, 'unknown result is reconciled without a second POST');
$http = [response(['access_token' => 'token']), response(['entity' => ['uuid' => 'found', 'cdek_number' => '555'], 'requests' => [['type' => 'CREATE', 'state' => 'SUCCESSFUL']]])];
dornott_cdek_process('42');
check($orders[$key]['cdek_state']['status'] === 'created', 'reconciliation adopts the original waybill');

$orders[$key] = $pending; $mail_calls = []; $calls = []; webhook(signed());
$http = [response(['access_token' => 'token']), response(['entity' => ['uuid' => 'bad'], 'requests' => [['type' => 'CREATE', 'state' => 'INVALID', 'errors' => [['code' => 'ERR_PACKAGE', 'message' => 'Invalid package']]]]], 202)];
dornott_cdek_process('42');
check($orders[$key]['cdek_state']['status'] === 'failed', 'asynchronous validation error is not mistaken for success');
check(count($mail_calls) === 3 && strpos($mail_calls[2]['subject'], 'СДЭК') !== false, 'CDEK failure is reported after ordinary paid-order emails');
$orders[$key] = $pending; $mail_calls = []; $events = []; $locked = true;
check(webhook(signed())['status'] === 503 && !$mail_calls && !$events, 'concurrent processing cannot overwrite service flags');
$locked = false;

// Another request completes between this request's initial read and GET_LOCK.
$orders[$key] = $pending; $mail_calls = []; $events = []; $cache = []; $cache_mode = true;
$on_lock = function () use ($key, $pending) { $GLOBALS['orders'][$key] = array_merge($pending, [
    'paid_confirmed' => true, 'emails_sent' => true,
    'cdek_state' => ['status' => 'created', 'number' => 'existing', 'notified' => true],
]); };
webhook(signed());
check(!$mail_calls && !$events && $orders[$key]['cdek_state']['status'] === 'created', 'locked fresh read bypasses WordPress request cache after concurrent completion');
$cache_mode = false;

$orders[$key] = array_merge($pending, ['paid_confirmed' => true, 'cdek_state' => ['status' => 'queued', 'attempts' => 0]]);
$events = []; $calls = []; $schedule_ok = false;
dornott_cdek_process('42');
check(!$calls && $orders[$key]['cdek_state']['status'] === 'queued', 'failed recovery scheduling leaves unsent shipment queued');
$schedule_ok = true;
$http = [response(['access_token' => 'token']), response(['entity' => ['uuid' => 'recovered'], 'requests' => [['state' => 'ACCEPTED']]], 202)];
dornott_cdek_process('42');
check($orders[$key]['cdek_state']['status'] === 'submitted', 'shipment can be created after scheduler recovers');

$orders[$key] = array_merge($pending, ['paid_confirmed' => true, 'cdek_state' => ['status' => 'queued', 'attempts' => 0]]);
$calls = []; $persist_ok = false;
dornott_cdek_process('42');
check(!$calls, 'state persistence failure cannot issue an untracked POST');
$persist_ok = true;

$orders[$key] = $pending; $events = []; $calls = []; $mail_calls = []; $fail_addresses = ['owner@example.com'];
check(webhook(signed())['status'] === 503 && count($events) === 1, 'ordinary email failure does not block scheduling delivery');
$fail_addresses = [];
$http = [response(['access_token' => 'token']), response(['entity' => ['uuid' => 'mail-independent', 'cdek_number' => '777']], 202)];
dornott_cdek_process('42');
check($orders[$key]['cdek_state']['status'] === 'created', 'delivery succeeds independently of an earlier email failure');
webhook(signed());
check($orders[$key]['emails_sent'] && $orders[$key]['cdek_state']['status'] === 'created', 'mail retry preserves completed CDEK state');
unset($orders[$key]); $calls = [];
dornott_cdek_process('42');
check(!$calls, 'expired temporary order cannot trigger a shipment');
// DEMO acquiring intentionally uses the ordinary CDEK creation path; no external HTTP here.
$_ENV['TBANK_TERMINAL_KEY'] = '123456DEMO';
$orders[$key] = array_merge($pending, ['terminal_key' => '123456DEMO']);
$events = []; $calls = []; $mail_calls = [];
$cache_mode = false; $locked = false; $persist_ok = true; $schedule_ok = true; $fail_addresses = [];
check(webhook(signed(['TerminalKey' => '123456DEMO'])) === ['body' => 'OK', 'status' => 200]
    && count($events) === 1, 'signed DEMO CONFIRMED payment schedules the ordinary CDEK worker');
$http = [response(['access_token' => 'token']), response(['entity' => ['uuid' => 'demo-payment-waybill', 'cdek_number' => '888']], 202)];
dornott_cdek_process('42');
$creation_calls = array_filter($calls, function ($call) {
    return $call['args']['method'] === 'POST' && substr($call['url'], -7) === '/orders';
});
check($orders[$key]['cdek_state']['status'] === 'created' && count($creation_calls) === 1,
    'DEMO payment reaches normal CDEK creation without a real card charge');
echo "\n$passed passed, $failed failed\n"; exit($failed ? 1 : 0);
