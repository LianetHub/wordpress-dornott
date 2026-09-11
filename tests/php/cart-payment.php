<?php

require_once dirname(__DIR__, 2) . '/includes/cart-payment.php';

$failed = 0;
$passed = 0;

function test_assert($condition, $message)
{
	global $failed, $passed;
	if ($condition) {
		$passed++;
		echo "ok  - {$message}\n";
		return;
	}

	$failed++;
	echo "FAIL - {$message}\n";
}

test_assert(dornott_cart_ids_equal(5, '5'), 'numeric and string ids are equal');
test_assert(!dornott_cart_ids_equal(5, 6), 'different ids are not equal');

test_assert(dornott_clamp_qty(0) === 1, 'qty 0 clamps to 1');
test_assert(dornott_clamp_qty(1500) === 999, 'qty 1500 clamps to 999');
test_assert(dornott_clamp_qty('12') === 12, 'qty string is parsed');

test_assert(dornott_normalize_phone('+7 (900) 111-22-33') === '+79001112233', 'phone keeps +7');
test_assert(dornott_normalize_phone('8 900 111-22-33') === '+79001112233', 'phone 8xxx becomes +7');

$order_info = [
	['name' => 'email', 'value' => 'a@b.c'],
	['name' => 'delivery', 'value' => 'cdek'],
];
test_assert(dornott_order_info_value($order_info, 'delivery') === 'cdek', 'order_info value by name');
test_assert(dornott_order_info_value($order_info, 'missing') === '', 'missing order_info value is empty');

$totals = dornott_recalculate_totals(
	[
		['price' => 1000, 'regular_price' => 1500, 'quantity' => 2],
		['price' => 500, 'regular_price' => 500, 'quantity' => 1],
	],
	350
);
test_assert($totals['subtotal'] === 3500, 'subtotal uses regular prices');
test_assert($totals['discount'] === 1000, 'discount is regular minus sale');
test_assert($totals['totalQty'] === 3, 'total qty sums items');
test_assert($totals['deliveryPrice'] === 350, 'delivery is included in totals');
test_assert($totals['finalPrice'] === 2850, 'final price ignores client totals');

$methods = [
	['slug' => 'cdek', 'price' => 500],
	['slug' => 'pickup', 'price' => 0],
];
test_assert(dornott_lookup_delivery_price('cdek', $methods) === 500, 'delivery price is taken from options');
test_assert(dornott_lookup_delivery_price('unknown', $methods) === null, 'unknown delivery slug is rejected');
test_assert(dornott_lookup_delivery_price('', $methods) === null, 'empty delivery slug is rejected');

$resolved = dornott_resolve_cart_item_from_product(
	['id' => 10, 'quantity' => 4, 'price' => 1],
	[
		'id' => 10,
		'name' => 'Серверный товар',
		'sku' => 'SRV',
		'price' => 1200,
		'regular_price' => 1500,
	]
);
test_assert($resolved['price'] === 1200, 'item price is taken from catalog, not client');
test_assert($resolved['quantity'] === 4, 'client qty is kept after clamp');
test_assert($resolved['name'] === 'Серверный товар', 'item name is taken from catalog');

$blocked = dornott_resolve_cart_item_from_product(
	['id' => 11, 'quantity' => 1],
	['id' => 11, 'price' => 100, 'purchasable' => false]
);
test_assert($blocked === null, 'non-purchasable product is rejected');

$receipt = dornott_build_receipt_items(
	[['name' => 'Товар', 'price' => 1000, 'quantity' => 2]],
	500
);
test_assert(count($receipt) === 2, 'receipt includes delivery line');
test_assert($receipt[0]['Amount'] === 200000, 'receipt item amount is kopecks');
test_assert($receipt[1]['Name'] === 'Доставка', 'delivery receipt name');
test_assert($receipt[1]['Amount'] === 50000, 'delivery amount is kopecks');

$signed = dornott_tbank_sign(
	[
		'TerminalKey' => 'demo',
		'Amount' => '10000',
		'OrderId' => '1',
		'Description' => 'test',
		'Receipt' => ['ignored' => true],
	],
	'secret'
);
// ksort: Amount, Description, OrderId, Password, TerminalKey
$expected = hash('sha256', '10000' . 'test' . '1' . 'secret' . 'demo');
test_assert($signed === $expected, 'tbank token concatenates sorted scalar params plus password');

test_assert(dornott_is_paid_status('CONFIRMED') === true, 'CONFIRMED is paid');
test_assert(dornott_is_paid_status('AUTHORIZED') === true, 'AUTHORIZED is paid');
test_assert(dornott_is_paid_status('REJECTED') === false, 'REJECTED is not paid');
test_assert(dornott_pending_order_transient_key('12abc!') === 'dornott_order_12abc', 'transient key is sanitized');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
