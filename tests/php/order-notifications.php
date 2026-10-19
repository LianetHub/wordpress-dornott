<?php
ob_start();
// Isolated bank webhook and mail transport: no network, payments or real mail.
require_once dirname(__DIR__, 2) . '/includes/cart-payment.php';
require_once dirname(__DIR__, 2) . '/includes/order-notifications.php';

$orders = [];
$mail_calls = [];
$fail_addresses = [];
$status = 200;
$passed = 0;
$failed = 0;
function sanitize_text_field($value) { return (string) $value; }
function sanitize_textarea_field($value) { return (string) $value; }
function sanitize_email($value) { return trim((string) $value); }
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function wp_kses_post($value) { return $value; }
function get_form_recipients() { return 'owner@example.com'; }
function get_transient($key) { return $GLOBALS['orders'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['orders'][$key] = $value; return true; }
function status_header($code) { $GLOBALS['status'] = $code; }
function wp_die($message = '', $title = '', $args = []) {
    // Model WordPress AJAX's default trailing "0" for an empty message.
    $GLOBALS['status'] = $args['response'] ?? 200;
    echo isset($args['response']) ? $message : ($message === '' ? '0' : $message);
}
function wp_mail($to, $subject, $body, $headers) {
    $GLOBALS['mail_calls'][] = compact('to', 'subject', 'body');
    return !in_array($to, $GLOBALS['fail_addresses'], true);
}
function check($condition, $message) {
    $GLOBALS[$condition ? 'passed' : 'failed']++;
    echo ($condition ? 'ok  - ' : 'FAIL - ') . $message . "\n";
}
function webhook($payload) {
    $_POST = $payload;
    $GLOBALS['status'] = 200;
    ob_start();
    handle_tbank_notification();
    return ['body' => ob_get_clean(), 'status' => $GLOBALS['status']];
}

$_ENV['TBANK_SECRET_KEY'] = 'secret';
$pending = [
    'payment_id' => '99', 'email' => 'buyer@example.com', 'username' => 'Покупатель',
    'items' => [['name' => 'Товар', 'quantity' => 1, 'price' => 50, 'regular_price' => 50]],
    'totals' => ['deliveryPrice' => 0],
];
$key = dornott_pending_order_transient_key('42');
$orders[$key] = $pending;
$payload = [
    'TerminalKey' => 'demo', 'OrderId' => '42', 'Success' => true,
    'Status' => 'CONFIRMED', 'PaymentId' => '99', 'Amount' => 5000,
];
$payload['Token'] = hash('sha256', '5000' . '42' . 'secret' . '99' . 'CONFIRMED' . 'true' . 'demo');

// Run before printing, like a real HTTP request (headers must still be writable).
$response = webhook($payload);
$repeat = webhook($payload);
check($response === ['body' => 'OK', 'status' => 200], 'signed bank JSON returns exactly OK without trailing zero');
check(count($mail_calls) === 2, 'bank webhook sends owner and buyer once despite repeated notification');
check($mail_calls[0]['to'] === 'owner@example.com' && $mail_calls[1]['to'] === 'buyer@example.com', 'correct recipients');
check(strpos($mail_calls[0]['body'], 'Товар') !== false, 'custom email includes ordered product');
check($mail_calls[0]['body'] === $mail_calls[1]['body'], 'owner and buyer receive same order details');

$orders[$key] = $pending;
$mail_calls = [];
$fail_addresses = ['owner@example.com'];
$response = webhook($payload);
check($response['status'] === 503, 'owner mail failure requests bank retry');
check(count($mail_calls) === 2, 'buyer mail attempted even if owner mail fails');
$fail_addresses = [];
$response = webhook($payload);
check(count($mail_calls) === 3 && $mail_calls[2]['to'] === 'owner@example.com', 'retry sends only previously failed owner message');
check($response === ['body' => 'OK', 'status' => 200], 'successful retry acknowledged');

$orders[$key] = $pending;
$mail_calls = [];
$fail_addresses = ['buyer@example.com'];
check(webhook($payload)['status'] === 503, 'buyer mail failure also requests retry');
$fail_addresses = [];
webhook($payload);
check(count($mail_calls) === 3 && $mail_calls[2]['to'] === 'buyer@example.com', 'buyer failure retried without repeating owner email');

$orders[$key] = array_merge($pending, ['email' => 'OWNER@example.com']);
$mail_calls = [];
dornott_send_paid_order_emails($orders[$key], '42');
check(count($mail_calls) === 1, 'same owner and buyer address deduplicated ignoring case');

$mail_calls = [];
$bad = $payload;
$bad['Token'] = 'invalid';
check(webhook($bad)['status'] === 403 && count($mail_calls) === 0, 'invalid signature cannot send mail');
$orders[$key] = array_merge($pending, ['payment_id' => 'other']);
check(webhook($payload)['status'] === 403 && count($mail_calls) === 0, 'wrong payment cannot send mail');
unset($orders[$key]);
check(webhook($payload)['status'] === 503, 'missing order is not silently acknowledged');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
