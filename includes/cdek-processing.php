<?php

/** MySQL advisory locks serialize webhook, browser callback and worker without a new registry. */
function dornott_order_lock($order_id, $release = false)
{
    global $wpdb;
    $name = 'dornott_' . sha1((string) $order_id);
    $sql = $release ? 'SELECT RELEASE_LOCK(%s)' : 'SELECT GET_LOCK(%s, 0)';
    return (int) $wpdb->get_var($wpdb->prepare($sql, $name)) === 1;
}

function dornott_store_pending_order($order_id, $pending)
{
    $ttl = max(1, (int) ($pending['created_at'] ?? time()) + 86400 - time());
	$key = dornott_pending_order_transient_key($order_id);
	return set_transient($key, $pending, $ttl) || get_transient($key) === $pending;
}

/** An earlier unlocked payment check may have primed this request's option cache. */
function dornott_pending_order_fresh($order_id)
{
    $key = dornott_pending_order_transient_key($order_id);
    if (wp_using_ext_object_cache()) return wp_cache_get($key, 'transient', true);
    wp_cache_delete('_transient_' . $key, 'options');
    wp_cache_delete('_transient_timeout_' . $key, 'options');
    wp_cache_delete('notoptions', 'options');
    return get_transient($key);
}

function dornott_cdek_schedule($order_id, $delay = 30)
{
    $args = [(string) $order_id];
    if (wp_next_scheduled('dornott_cdek_process', $args)) return true;
    $result = wp_schedule_single_event(time() + $delay, 'dornott_cdek_process', $args);
    return $result !== false && !is_wp_error($result);
}

/** Called only after server-side payment verification, using a fresh locked snapshot. */
function dornott_complete_paid_order($order_id)
{
    if (!dornott_order_lock($order_id)) return ['ok' => false, 'message' => 'Заказ обрабатывается. Повторите позже.'];
    try {
        $pending = dornott_pending_order_fresh($order_id);
        if (!is_array($pending)) return ['ok' => false, 'message' => 'Заказ не найден'];
        $pending['paid_confirmed'] = true;
        if (!dornott_store_pending_order($order_id, $pending)) return ['ok' => false, 'message' => 'Не удалось сохранить состояние оплаты'];
        $mail = dornott_send_paid_order_emails($pending, $order_id);
        // The mail helper persists recipient flags. Never overwrite them with the stale snapshot.
        $pending = dornott_pending_order_fresh($order_id);
        if (($pending['delivery'] ?? '') === 'cdek' && !empty($pending['cdek'])) {
            if (empty($pending['cdek_state'])) {
                $pending['cdek_state'] = ['status' => 'queued', 'attempts' => 0];
                if (!dornott_store_pending_order($order_id, $pending)) return ['ok' => false, 'message' => 'Не удалось сохранить состояние доставки'];
            }
            if (!in_array($pending['cdek_state']['status'], ['created', 'failed'], true) && !dornott_cdek_schedule($order_id, 1)) {
                return ['ok' => false, 'message' => 'Не удалось запланировать обработку СДЭК'];
            }
        }
        return $mail;
    } finally {
        dornott_order_lock($order_id, true);
    }
}

function dornott_cdek_result_error($body)
{
    $errors = $body['errors'] ?? [];
    foreach (($body['requests'] ?? []) as $request) {
        if (!empty($request['errors'])) $errors = array_merge($errors, $request['errors']);
        if (($request['state'] ?? '') === 'INVALID' && !$errors) $errors[] = ['code' => 'INVALID'];
    }
    if (!$errors) return '';
    // Return codes only: API messages can contain credentials or unescaped user data.
    $code = (string) ($errors[0]['code'] ?? 'CDEK_VALIDATION_ERROR');
    return preg_replace('/[^A-Za-z0-9_-]/', '', substr($code, 0, 100));
}

function dornott_cdek_notify_owner($order_id, &$pending)
{
    $state = &$pending['cdek_state'];
    if (!empty($state['notified'])) return;
    $success = $state['status'] === 'created';
    $subject = 'СДЭК: ' . ($success ? 'проверьте накладную' : 'требуется проверка заказа') . ' №' . $order_id;
    $body = $success
        ? 'Накладная СДЭК: ' . ($state['number'] ?? '') . "\nПроверьте получателя, тариф, вес и габариты в https://lk.cdek.ru/ перед сдачей посылки. Упаковка может быть предварительной. Вызов курьера не выполнялся."
        : "Не удалось подтвердить создание накладной СДЭК. Проверьте заказ в https://lk.cdek.ru/ по номеру ИМ " . $order_id
            . ".\nНе создавайте повторную накладную до сверки: первоначальный запрос мог быть принят.\nКод: " . ($state['error'] ?? 'UNKNOWN');
    $body .= "\nПВЗ получателя: " . ($pending['cdek']['pvz_code'] ?? '')
        . "\nДоставка для покупателя бесплатна. Данные покупателя и состав заказа — в письме об оплате.";
    $recipients = array_values(array_unique(array_filter(array_map('trim', explode(',', get_form_recipients())), 'is_email')));
    $sent = $state['notice_sent'] ?? [];
    foreach ($recipients as $recipient) {
        if (!in_array($recipient, $sent, true) && wp_mail($recipient, $subject, $body, ['Content-Type: text/plain; charset=UTF-8'])) $sent[] = $recipient;
    }
    $state['notice_sent'] = $sent;
    $state['notified'] = $recipients && !array_diff($recipients, $sent);
    $state['notice_attempts'] = (int) ($state['notice_attempts'] ?? 0) + 1;
    dornott_store_pending_order($order_id, $pending);
    if (!$state['notified'] && $state['notice_attempts'] < 5) dornott_cdek_schedule($order_id, 60);
}

/** Background only: never hold the bank's notification open while calling CDEK. */
function dornott_cdek_process($order_id)
{
    if (!dornott_order_lock($order_id)) { dornott_cdek_schedule($order_id); return; }
    try {
        $pending = dornott_pending_order_fresh($order_id);
        if (!is_array($pending) || empty($pending['paid_confirmed']) || empty($pending['cdek']) || empty($pending['cdek_state'])) return;
        $state = &$pending['cdek_state'];
        if (in_array($state['status'], ['created', 'failed'], true)) {
            dornott_cdek_notify_owner($order_id, $pending);
            return;
        }
        $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
        $config = dornott_cdek_config();
        $client = new Dornott_Cdek_Client($config);
        $creating = $state['status'] === 'queued';
        if ($creating) {
            // Persist intent and schedule recovery BEFORE POST; a killed PHP process must not POST again.
            if (!dornott_cdek_schedule($order_id, 60)) return;
            $state['status'] = 'creating';
            if (!dornott_store_pending_order($order_id, $pending)) return;
            $result = $client->request('POST', 'orders', dornott_cdek_order_payload($pending, $order_id, $config));
        } else {
            $result = !empty($state['uuid'])
                ? $client->request('GET', 'orders/' . rawurlencode($state['uuid']))
                : $client->request('GET', 'orders', ['im_number' => (string) $order_id]);
        }
        $error = dornott_cdek_result_error($result['data']);
        if ($result['ok'] && !$error) {
            $entity = $result['data']['entity'] ?? [];
            if (!empty($entity['uuid'])) $state['uuid'] = (string) $entity['uuid'];
            if (!empty($entity['cdek_number'])) {
                $state['number'] = (string) $entity['cdek_number'];
                $state['status'] = 'created';
            } else {
                $state['status'] = !empty($state['uuid']) ? 'submitted' : 'unknown';
            }
        } elseif ($error || ($creating && $result['status'] >= 400 && $result['status'] < 500 && !in_array($result['status'], [401, 429], true))) {
            $state['status'] = 'failed';
            $state['error'] = $error ?: 'HTTP_' . $result['status'];
        } else {
            // Only known pre-creation failures (auth/429) allow another POST.
            $state['status'] = $creating && !$result['uncertain'] ? 'queued' : (!empty($state['uuid']) ? 'submitted' : 'unknown');
        }
        if ($state['attempts'] >= 5 && !in_array($state['status'], ['created', 'failed'], true)) {
            $state['status'] = 'failed';
            $state['error'] = 'REQUIRES_MANUAL_REVIEW';
        }
        dornott_store_pending_order($order_id, $pending);
        if (in_array($state['status'], ['created', 'failed'], true)) dornott_cdek_notify_owner($order_id, $pending);
        else dornott_cdek_schedule($order_id, 60);
    } finally {
        dornott_order_lock($order_id, true);
    }
}
