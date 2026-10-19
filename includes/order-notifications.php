<?php

function dornott_render_order_email_html($subject, $fields)
{
	$username = (string) ($fields['username'] ?? '');
	$phone = (string) ($fields['phone'] ?? '');
	$email = (string) ($fields['email'] ?? '');
	$city = (string) ($fields['city'] ?? '');
	$address = (string) ($fields['address'] ?? '');
	$delivery = (string) ($fields['delivery'] ?? '');
	$message_text = (string) ($fields['message_text'] ?? '');
	$cart_items = $fields['cart_items'] ?? null;
	$delivery_price = intval($fields['delivery_price'] ?? 0);

	ob_start();
?>
	<div style="font-family: Arial, sans-serif; color: #333;">
		<h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px;"><?php echo esc_html($subject); ?></h2>

		<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
			<tr>
				<td style="padding: 5px 0; width: 150px;"><strong>Имя:</strong></td>
				<td><?php echo esc_html($username); ?></td>
			</tr>
			<tr>
				<td style="padding: 5px 0;"><strong>Телефон:</strong></td>
				<td><a href="tel:<?php echo esc_html($phone); ?>"><?php echo esc_html($phone); ?></a></td>
			</tr>
			<tr>
				<td style="padding: 5px 0;"><strong>Email:</strong></td>
				<td><a href="mailto:<?php echo esc_html($email); ?>"><?php echo esc_html($email); ?></a></td>
			</tr>
			<?php if ($delivery): ?>
				<tr>
					<td style="padding: 5px 0;"><strong>Доставка:</strong></td>
					<td><?php echo ($delivery === 'cdek') ? 'СДЭК / ТК' : 'Самовывоз'; ?></td>
				</tr>
			<?php endif; ?>
			<?php if ($city): ?>
				<tr>
					<td style="padding: 5px 0;"><strong>Город:</strong></td>
					<td><?php echo esc_html($city); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ($address): ?>
				<tr>
					<td style="padding: 5px 0;"><strong>Адрес:</strong></td>
					<td><?php echo esc_html($address); ?></td>
				</tr>
			<?php endif; ?>
		</table>

		<?php if (is_array($cart_items) && !empty($cart_items)): ?>
			<h3 style="background: #f9f9f9; padding: 10px;">Состав заказа</h3>
			<table style="width: 100%; border-collapse: collapse; border: 1px solid #eee;">
				<thead>
					<tr style="background: #eee;">
						<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Товар</th>
						<th style="padding: 10px; text-align: center; border: 1px solid #ddd; width: 80px;">Кол-во</th>
						<th style="padding: 10px; text-align: right; border: 1px solid #ddd; width: 100px;">Цена</th>
						<th style="padding: 10px; text-align: right; border: 1px solid #ddd; width: 100px;">Сумма</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$total_sum = 0;
					foreach ($cart_items as $item):
						$item_sum = intval($item['price'] ?? 0) * intval($item['quantity'] ?? 0);
						$total_sum += $item_sum;
					?>
						<tr>
							<td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($item['name'] ?? ''); ?></td>
							<td style="padding: 10px; text-align: center; border: 1px solid #ddd;"><?php echo intval($item['quantity'] ?? 0); ?></td>
							<td style="padding: 10px; text-align: right; border: 1px solid #ddd;"><?php echo number_format(intval($item['price'] ?? 0), 0, '.', ' '); ?> ₽</td>
							<td style="padding: 10px; text-align: right; border: 1px solid #ddd;"><?php echo number_format($item_sum, 0, '.', ' '); ?> ₽</td>
						</tr>
					<?php endforeach; ?>

					<?php if ($delivery_price > 0):
						$total_sum += $delivery_price;
					?>
						<tr>
							<td colspan="3" style="padding: 10px; border: 1px solid #ddd; font-style: italic;">Доставка</td>
							<td style="padding: 10px; text-align: right; border: 1px solid #ddd;"><?php echo number_format($delivery_price, 0, '.', ' '); ?> ₽</td>
						</tr>
					<?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="3" style="padding: 10px; text-align: right; font-weight: bold;">Итого к оплате:</td>
						<td style="padding: 10px; text-align: right; font-weight: bold;"><?php echo number_format($total_sum, 0, '.', ' '); ?> ₽</td>
					</tr>
				</tfoot>
			</table>
		<?php endif; ?>

		<?php if ($message_text): ?>
			<div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
				<strong>Комментарий к заказу:</strong><br>
				<?php echo $message_text; ?>
			</div>
		<?php endif; ?>

		<p style="color: #999; font-size: 12px; margin-top: 30px;">Отправлено с сайта dornott.ru</p>
	</div>
<?php
	return ob_get_clean();
}

function dornott_send_paid_order_emails($pending, $order_id)
{
	if (!is_array($pending)) {
		return ['ok' => false, 'message' => 'Заказ не найден'];
	}

	if (!empty($pending['emails_sent'])) {
		return ['ok' => true, 'message' => 'already_sent'];
	}

	$order_id = sanitize_text_field((string) $order_id);
	$email = sanitize_email($pending['email'] ?? '');
	$subject = 'Новый оплаченный заказ №' . $order_id;
	$headers = ['Content-Type: text/html; charset=UTF-8'];

	$message = dornott_render_order_email_html($subject, [
		'username'       => sanitize_text_field($pending['username'] ?? ''),
		'phone'          => sanitize_text_field($pending['phone'] ?? ''),
		'email'          => $email,
		'city'           => sanitize_text_field($pending['city'] ?? ''),
		'address'        => sanitize_text_field($pending['address'] ?? ''),
		'delivery'       => sanitize_text_field($pending['delivery'] ?? ''),
		'message_text'   => nl2br(sanitize_textarea_field($pending['message'] ?? '')),
		'cart_items'     => is_array($pending['items'] ?? null) ? $pending['items'] : [],
		'delivery_price' => intval($pending['totals']['deliveryPrice'] ?? 0),
	]);

	$to = get_form_recipients();
	$recipients = array_map('trim', explode(',', (string) $to));
	if (is_email($email)) {
		$recipients[] = $email;
	}
	$recipients = array_values(array_unique(array_map('strtolower', array_filter($recipients, 'is_email'))));
	if (empty($recipients)) {
		return ['ok' => false, 'message' => 'Не настроены получатели писем'];
	}

	// Keep successful recipients so a bank retry only resends failed messages.
	$sent = $pending['email_recipients_sent'] ?? [];
	foreach ($recipients as $recipient) {
		if (!in_array($recipient, $sent, true) && wp_mail($recipient, $subject, $message, $headers)) {
			$sent[] = $recipient;
		}
	}
	$pending['email_recipients_sent'] = $sent;
	$pending['emails_sent'] = count(array_diff($recipients, $sent)) === 0;
	set_transient(
		dornott_pending_order_transient_key($order_id),
		$pending,
		defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400
	);
	return ['ok' => $pending['emails_sent'], 'message' => $pending['emails_sent'] ? 'sent' : 'Ошибка при отправке письма'];
}

function handle_tbank_notification()
{
	$payload = dornott_tbank_parse_notification();
	$secret_key = $_ENV['TBANK_SECRET_KEY'] ?? '';

	if (!dornott_tbank_notification_token_valid($payload, $secret_key)) {
		status_header(403);
		echo 'FORBIDDEN';
		wp_die('', '', ['response' => 403]);
		return;
	}

	$status = (string) ($payload['Status'] ?? '');
	$order_id = sanitize_text_field((string) ($payload['OrderId'] ?? ''));

	if ($order_id !== '' && dornott_is_paid_status($status)) {
		$pending = get_transient(dornott_pending_order_transient_key($order_id));
		$payment_id = (string) ($payload['PaymentId'] ?? '');

		if (!is_array($pending)) {
			status_header(503);
			echo 'ORDER_NOT_FOUND';
			wp_die('', '', ['response' => 503]);
			return;
		}
		if ((string) ($pending['payment_id'] ?? '') !== $payment_id || $payment_id === '') {
			status_header(403);
			echo 'PAYMENT_MISMATCH';
			wp_die('', '', ['response' => 403]);
			return;
		}
		$result = dornott_send_paid_order_emails($pending, $order_id);
		if (empty($result['ok'])) {
			status_header(503);
			echo 'MAIL_RETRY';
			wp_die('', '', ['response' => 503]);
			return;
		}
	}

	header('Content-Type: text/plain; charset=UTF-8');
	echo 'OK';
	// The default AJAX wp_die handler appends "0" to an empty response.
	wp_die('', '', ['response' => 200]);
}
