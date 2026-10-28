<?php

function dornott_cdek_config()
{
    $settings = apply_filters('dornott_cdek_settings', require __DIR__ . '/cdek-settings.php');
    return array_merge($settings, [
        'client_id' => trim($_ENV['CDEK_CLIENT_ID'] ?? ''),
        'client_secret' => trim($_ENV['CDEK_CLIENT_SECRET'] ?? ''),
        'maps_key' => trim($_ENV['YANDEX_MAPS_API_KEY'] ?? ''),
    ]);
}

function dornott_cdek_ready($config = null)
{
    $config = $config ?: dornott_cdek_config();
    return $config['client_id'] !== '' && $config['client_secret'] !== '' && $config['maps_key'] !== ''
        && $config['sender_name'] !== '' && preg_match('/^\+7\d{10}$/', $config['sender_phone'])
        && $config['tariff_code'] > 0;
}

/** Shipping facts are resolved from WooCommerce, never from localStorage. */
function dornott_cdek_product_shipping($product)
{
    $shipping = [];
    if (!is_object($product)) return $shipping;
    foreach (['weight', 'length', 'width', 'height'] as $field) {
        $method = 'get_' . $field;
        $raw = method_exists($product, $method) ? (float) $product->$method() : 0;
        if ($raw <= 0) continue;
        $value = $field === 'weight' ? wc_get_weight($raw, 'g') : wc_get_dimension($raw, 'cm');
        if ($value > 0) $shipping[$field] = (int) ceil($value);
    }
    return $shipping;
}

/** Catalog first; fill absent measurements from the explicitly configured PHP template. */
function dornott_cdek_packages($items, $config = null)
{
    $config = $config ?: dornott_cdek_config();
    $packages = [];
    foreach ($items as $item) {
        $size = [];
        $used_default = false;
        foreach (['weight', 'length', 'width', 'height'] as $field) {
            $value = $item['shipping'][$field] ?? null;
            if ($value === null || $value === '' || (is_numeric($value) && (float) $value === 0.0)) {
                $value = $config['default_package'][$field] ?? null;
                $used_default = true;
            }
            if (!is_numeric($value) || !is_finite((float) $value) || (float) $value <= 0) return [];
            $size[$field] = (int) ceil((float) $value);
            if ($size[$field] <= 0) return [];
        }
        for ($i = 0; $i < dornott_clamp_qty($item['quantity'] ?? 1); $i++) {
            $name = (string) ($item['name'] ?? 'Товар');
            $name = function_exists('mb_substr') ? mb_substr($name, 0, 150) : substr($name, 0, 150);
            $packages[] = array_merge($size, ['number' => (string) (count($packages) + 1), 'items' => [[
                'name' => $name, 'ware_key' => (string) (($item['sku'] ?? '') ?: 'product-' . $item['id']),
                'payment' => ['value' => 0], 'cost' => (float) $item['price'],
                'weight' => $size['weight'], 'amount' => 1,
            ]]]);
            if ($used_default) $packages[count($packages) - 1]['comment'] = 'Использованы предварительные параметры упаковки. Проверить в ЛК перед сдачей.';
            // Bound an invalid or excessive checkout before allocating huge requests.
            if (count($packages) > 100) return [];
        }
    }
    return $packages;
}

function dornott_cdek_office($client, $code, $origin = false)
{
    $result = $client->request('GET', 'deliverypoints', ['code' => $code, 'country_code' => 'RU', 'type' => 'PVZ']);
    if (!$result['ok']) return null;
    foreach ($result['data'] as $office) {
        if (!is_array($office) || ($office['code'] ?? '') !== $code || ($office['type'] ?? '') !== 'PVZ'
            || ($office['location']['country_code'] ?? '') !== 'RU' || empty($office['location']['city_code'])
            || empty($office[$origin ? 'is_reception' : 'is_handout'])) continue;
        return $office;
    }
    return null;
}

function dornott_cdek_prepare_checkout($info, $items, $client = null, $config = null)
{
    $config = $config ?: dornott_cdek_config();
    $failure = ['ok' => false, 'message' => 'Не удалось подтвердить доставку в выбранный ПВЗ. Выберите другой пункт или попробуйте позже.'];
    $code = dornott_order_info_value($info, 'cdek_pvz_code');
    if (!preg_match('/^[A-Z0-9_-]{2,30}$/D', $code)) return $failure;
    $packages = dornott_cdek_packages($items, $config);
    if (!$packages) return ['ok' => false, 'message' => 'Уточните упаковку заказа у менеджера.'];
    $client = $client ?: new Dornott_Cdek_Client($config);
    $origin = dornott_cdek_office($client, $config['shipment_point'], true);
    if (!$origin) return $failure;
    $office = dornott_cdek_office($client, $code);
    if (!$office) return $failure;
    foreach ($packages as $package) {
        $kg = $package['weight'] / 1000;
        if ((!empty($office['weight_max']) && $kg > $office['weight_max'])
            || (!empty($office['weight_min']) && $kg < $office['weight_min'])) return $failure;
    }
    $result = $client->request('POST', 'calculator/tariff', [
        'type' => 1, 'tariff_code' => $config['tariff_code'],
        'from_location' => ['code' => (int) $origin['location']['city_code']],
        'to_location' => ['code' => (int) $office['location']['city_code']],
        'packages' => array_map(function ($parcel) { return array_intersect_key($parcel, array_flip(['weight', 'length', 'width', 'height'])); }, $packages),
    ]);
    if (!$result['ok'] || !empty($result['data']['errors']) || !isset($result['data']['delivery_sum'])) return $failure;
    return ['ok' => true, 'delivery_price' => 0, 'city' => (string) $office['location']['city'],
        'address' => (string) $office['location']['address'], 'cdek' => [
            'pvz_code' => $code, 'pvz_name' => (string) ($office['name'] ?? $code),
            'tariff_code' => $config['tariff_code'], 'shipment_point' => $config['shipment_point'],
            'packages' => $packages,
        ]];
}

function dornott_cdek_order_payload($order, $order_id, $config)
{
    return [
        'type' => 1, 'number' => (string) $order_id, 'tariff_code' => (int) $order['cdek']['tariff_code'],
        'shipment_point' => $order['cdek']['shipment_point'], 'delivery_point' => $order['cdek']['pvz_code'],
        'delivery_recipient_cost' => ['value' => 0],
        'sender' => ['name' => $config['sender_name'], 'phones' => [['number' => $config['sender_phone']]]],
        'recipient' => ['name' => $order['username'], 'phones' => [['number' => dornott_normalize_phone($order['phone'])]], 'email' => $order['email']],
        'comment' => trim('Заказ оплачен. Доставка за счёт отправителя. ' . ($config['package_comment'] ?? 'Проверить упаковку перед сдачей в СДЭК.')),
        'packages' => $order['cdek']['packages'],
    ];
}

/** Restricted public proxy for the widget: pickup information only. */
function dornott_cdek_widget_response()
{
    if (!dornott_cdek_ready()) wp_send_json(['message' => 'Карта ПВЗ временно недоступна.'], 503);
    if (($_GET['action'] ?? '') !== 'offices') wp_send_json(['message' => 'Недопустимая операция.'], 400);
    $params = ['country_code' => 'RU', 'type' => 'PVZ', 'is_handout' => 'true'];
    foreach (['city_code', 'postal_code'] as $field) {
        if (isset($_GET[$field]) && is_scalar($_GET[$field]) && preg_match('/^\d{1,12}$/D', (string) $_GET[$field])) $params[$field] = (string) $_GET[$field];
    }
    foreach (['page' => [0, 1000], 'size' => [1, 500]] as $field => $bounds) {
        if (isset($_GET[$field]) && is_scalar($_GET[$field]) && ctype_digit((string) $_GET[$field])) {
            $params[$field] = max($bounds[0], min($bounds[1], (int) $_GET[$field]));
        }
    }
    $client = new Dornott_Cdek_Client(dornott_cdek_config());
    $result = $client->request('GET', 'deliverypoints', $params);
    if ($result['ok'] && ctype_digit($result['total'] ?? '')) header('X-Total-Elements: ' . $result['total']);
    nocache_headers();
    wp_send_json($result['ok'] ? $result['data'] : ['message' => 'Карта ПВЗ временно недоступна.'], $result['ok'] ? 200 : 503);
}
