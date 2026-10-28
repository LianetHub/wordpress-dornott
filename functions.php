<?php

require_once('includes/admin-custom.php');
require_once('includes/acf-custom.php');
require_once('includes/woocommerce-custom.php');
require_once('includes/cart-payment.php');
require_once('includes/cdek-client.php');
require_once('includes/cdek-delivery.php');
require_once('includes/cdek-processing.php');
require_once('includes/cart-checkout.php');
require_once('includes/order-notifications.php');
require_once('includes/disable-update-http.php');

if (!function_exists('dornott_front_page_id')) {
	function dornott_front_page_id()
	{
		return (int) get_option('page_on_front');
	}
}

if (!function_exists('dornott_sections_context_id')) {
	function dornott_sections_context_id()
	{
		if (function_exists('is_shop') && is_shop() && function_exists('wc_get_page_id')) {
			return (int) wc_get_page_id('shop');
		}

		if (function_exists('is_product') && is_product()) {
			return (int) get_queried_object_id();
		}

		if (is_front_page()) {
			return dornott_front_page_id();
		}

		$id = (int) get_queried_object_id();
		return $id ?: dornott_front_page_id();
	}
}

// =========================================================================
// 1. CONSTANTS
// =========================================================================

define('TEMPLATE_PATH', dirname(__FILE__) . '/templates/');
define('DORNOTT_SMARTCAPTCHA_SITEKEY', 'ysc1_kzIyE344s2faPC2YmCuzMwsjuBxBP8bbf1KqkovV20820e34');

// =========================================================================
// 2. ENQUEUE STYLES AND SCRIPTS
// =========================================================================

add_theme_support('title-tag');
add_action('after_setup_theme', function () {
	add_post_type_support('page', 'excerpt');
});

// Enqueue theme styles (CSS)
function theme_enqueue_styles()
{
	wp_enqueue_style('swiper', get_template_directory_uri() . '/assets/css/libs/swiper-bundle.min.css');
	wp_enqueue_style('fancybox', get_template_directory_uri() . '/assets/css/libs/fancybox.css');
	wp_enqueue_style('main-style', get_template_directory_uri() . '/assets/css/style.min.css', array(), filemtime(get_template_directory() . '/assets/css/style.min.css'));
}
add_action('wp_enqueue_scripts', 'theme_enqueue_styles');

function dornott_defer_library_styles($html, $handle)
{
	$deferred = array('swiper', 'fancybox');
	if (!in_array($handle, $deferred, true)) {
		return $html;
	}

	$noscript = $html;

	if (preg_match('/\smedia=/', $html)) {
		$html = preg_replace("/\smedia=(['\"])all\\1/", ' media="print" onload="this.media=\'all\'"', $html, 1);
	} else {
		$html = preg_replace("/\srel=(['\"])stylesheet\\1/", ' rel="stylesheet" media="print" onload="this.media=\'all\'"', $html, 1);
	}

	return $html . '<noscript>' . $noscript . '</noscript>';
}
add_filter('style_loader_tag', 'dornott_defer_library_styles', 10, 2);


// Enqueue theme scripts (JS)
function theme_enqueue_scripts()
{
	$defer = array(
		'in_footer' => true,
		'strategy'  => 'defer',
	);

	wp_deregister_script('jquery');
	wp_enqueue_script('jquery', get_template_directory_uri() . '/assets/js/libs/jquery-3.7.1.min.js', array(), null, $defer);
	wp_enqueue_script('swiper-js', get_template_directory_uri() . '/assets/js/libs/swiper-bundle.min.js', array(), null, $defer);
	wp_enqueue_script('fancybox-js', get_template_directory_uri() . '/assets/js/libs/fancybox.umd.js', array(), null, $defer);

	wp_enqueue_script(
		'app-js',
		get_template_directory_uri() . '/assets/js/app.min.js',
		array('jquery', 'swiper-js', 'fancybox-js'),
		filemtime(get_template_directory() . '/assets/js/app.min.js'),
		$defer
	);

	wp_localize_script('app-js', 'dornott_ajax', array(
		'captcha_client_key' => $_ENV['SMARTCAPTCHA_CLIENT_KEY'] ?? DORNOTT_SMARTCAPTCHA_SITEKEY,
		'nonce'              => wp_create_nonce('dornott_cart'),
		'ajax_url'           => admin_url('admin-ajax.php'),
		'home_url'           => home_url('/'),
		'cdek'               => [
			'mapsKey' => $_ENV['YANDEX_MAPS_API_KEY'] ?? '',
			'servicePath' => get_template_directory_uri() . '/cdek-widget.php',
		],
	));

	if (is_front_page()) {
		wp_enqueue_script('digift-widget', 'https://dornott.digift.ru/script', array(), null, $defer);
	}
}
add_action('wp_enqueue_scripts', 'theme_enqueue_scripts');

function dornott_acf_image($image, $size = 'large', $args = [])
{
	if (empty($image)) {
		return '';
	}

	$id = 0;
	if (is_numeric($image)) {
		$id = (int) $image;
	} elseif (is_array($image)) {
		$id = (int) ($image['ID'] ?? $image['id'] ?? 0);
	}

	if ($id <= 0) {
		return '';
	}

	$defaults = array(
		'loading'  => 'lazy',
		'decoding' => 'async',
		'sizes'    => '(max-width: 991px) 100vw, 50vw',
	);

	add_filter('wp_img_tag_add_auto_sizes', '__return_false');
	$html = wp_get_attachment_image($id, $size, false, array_merge($defaults, $args));
	remove_filter('wp_img_tag_add_auto_sizes', '__return_false');

	return $html;
}

function dornott_catalog_image_sizes()
{
	return '(max-width: 575px) 92vw, (max-width: 767px) 46vw, (max-width: 991px) 31vw, 23vw';
}

function dornott_product_image_sizes()
{
	return '(max-width: 991px) 92vw, 560px';
}

function dornott_preload_front_assets()
{
	$theme_uri = get_template_directory_uri();
	$theme_dir = get_template_directory();
	$css_path = $theme_dir . '/assets/css/style.min.css';
	$css_ver = file_exists($css_path) ? filemtime($css_path) : null;

	printf(
		'<link rel="preload" href="%s" as="style">' . "\n",
		esc_url($theme_uri . '/assets/css/style.min.css' . ($css_ver ? '?ver=' . $css_ver : ''))
	);

	$fonts = array(
		'PTSans-Regular.woff2',
		'PTSans-Bold.woff2',
		'Comfortaa-VariableFont_wght.woff2',
		'icons.woff2',
	);

	foreach ($fonts as $font_file) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url($theme_uri . '/assets/fonts/' . $font_file)
		);
	}

	$lcp = dornott_get_lcp_image();
	if (!empty($lcp['src'])) {
		$attrs = sprintf('href="%s" as="image"', esc_url($lcp['src']));
		if (!empty($lcp['srcset'])) {
			$attrs .= ' imagesrcset="' . esc_attr($lcp['srcset']) . '"';
		}
		if (!empty($lcp['sizes'])) {
			$attrs .= ' imagesizes="' . esc_attr($lcp['sizes']) . '"';
		}
		echo '<link rel="preload" fetchpriority="high" ' . $attrs . '>' . "\n";
	}
}
add_action('wp_head', 'dornott_preload_front_assets', 1);

function dornott_get_lcp_image()
{
	$attachment_id = 0;
	$size = 'woocommerce_single';
	$sizes = '';

	if (function_exists('is_product') && is_product()) {
		$product = wc_get_product(get_queried_object_id());
		if ($product) {
			$attachment_id = (int) $product->get_image_id();
			$sizes = dornott_product_image_sizes();
		}
	} elseif (function_exists('is_shop') && is_shop()) {
		global $wp_query;
		$first_id = 0;
		if (!empty($wp_query->posts[0]->ID)) {
			$first_id = (int) $wp_query->posts[0]->ID;
		}
		if ($first_id && function_exists('wc_get_product')) {
			$product = wc_get_product($first_id);
			if ($product) {
				$attachment_id = (int) $product->get_image_id();
				$sizes = dornott_catalog_image_sizes();
			}
		}
	}

	if ($attachment_id <= 0) {
		return array();
	}

	$src = wp_get_attachment_image_url($attachment_id, $size);
	if (!$src) {
		$src = wp_get_attachment_image_url($attachment_id, 'woocommerce_single')
			?: wp_get_attachment_image_url($attachment_id, 'medium_large');
	}

	return array(
		'src'    => $src ?: '',
		'srcset' => wp_get_attachment_image_srcset($attachment_id, $size) ?: '',
		'sizes'  => $sizes,
	);
}

add_filter('image_editor_output_format', function ($formats) {
	$formats['image/jpeg'] = 'image/webp';
	$formats['image/png'] = 'image/webp';
	return $formats;
});

function dornott_get_policies()
{
	$option_page = 'option';
	$payment = function_exists('get_field') ? (string) get_field('payment_and_delivery_policy', $option_page) : '';
	$data = function_exists('get_field') ? (string) get_field('data_protection_policy', $option_page) : '';
	$privacy = function_exists('get_field') ? (string) get_field('privacy_policy', $option_page) : '';

	wp_send_json_success(array(
		'payment-and-delivery' => wp_kses_post($payment),
		'data-protection'      => wp_kses_post($data),
		'privacy-policy'       => wp_kses_post($privacy),
	));
}
add_action('wp_ajax_dornott_get_policies', 'dornott_get_policies');
add_action('wp_ajax_nopriv_dornott_get_policies', 'dornott_get_policies');

function dornott_smartcaptcha_html($theme = 'light')
{
	printf(
		'<div class="form__captcha" data-smart-captcha data-theme="%s" style="height: 100px"></div>',
		esc_attr($theme)
	);
}

function dornott_verify_smartcaptcha($token)
{
	$secret = $_ENV['SMARTCAPTCHA_SERVER_KEY'] ?? ($_ENV['YANDEX_SMARTCAPTCHA_SECRET'] ?? '');
	$token = is_string($token) ? trim($token) : '';

	if ($secret === '' || $token === '') {
		return false;
	}

	$body_args = array(
		'secret' => $secret,
		'token'  => $token,
	);

	$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
	if (is_string($ip) && strpos($ip, ',') !== false) {
		$ip = trim(explode(',', $ip)[0]);
	}
	if (filter_var($ip, FILTER_VALIDATE_IP)) {
		$body_args['ip'] = $ip;
	}

	$response = wp_remote_post(
		'https://smartcaptcha.cloud.yandex.ru/validate',
		array(
			'timeout' => 5,
			'body'    => $body_args,
		)
	);

	if (is_wp_error($response)) {
		return false;
	}

	$body = json_decode(wp_remote_retrieve_body($response), true);

	return isset($body['status']) && $body['status'] === 'ok';
}

// =========================================================================
// 3. THEME SUPPORT AND UTILITIES
// =========================================================================


function load_env_configs($path)
{
	if (!file_exists($path)) return;

	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		if (strpos(trim($line), '#') === 0) continue;
		list($name, $value) = explode('=', $line, 2);
		$_ENV[trim($name)] = trim($value);
	}
}

load_env_configs(ABSPATH . '.env');

// Allow SVG file uploads
function allow_svg_uploads($mimes)
{
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'allow_svg_uploads');


// Register navigation menus
function register_custom_menus()
{
	register_nav_menus(array(
		'general_menu' => ' Меню'
	));
}
add_action('after_setup_theme', 'register_custom_menus');

class Dornott_Menu_Walker extends Walker_Nav_Menu
{

	public function start_lvl(&$output, $depth = 0, $args = null)
	{
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class=\"submenu__list\">\n";
	}

	public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
	{
		$indent = ($depth) ? str_repeat("\t", $depth) : '';

		$classes = empty($item->classes) ? array() : (array) $item->classes;
		$classes[] = 'menu__item';

		$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
		$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

		$output .= $indent . '<li' . $class_names . '>';

		$atts = array();
		$atts['title']  = ! empty($item->attr_title) ? $item->attr_title : '';
		$atts['target'] = ! empty($item->target)     ? $item->target     : '';
		$atts['rel']    = ! empty($item->xfn)        ? $item->xfn        : '';
		$atts['href']   = ! empty($item->url)        ? $item->url        : '';
		$atts['class']  = 'menu__link';

		$atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

		$attributes = '';
		foreach ($atts as $attr => $value) {
			if (! empty($value)) {
				$value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$title = apply_filters('the_title', $item->title, $item->ID);
		$title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

		$item_output = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . $title . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
	}
}

add_filter('nav_menu_link_attributes', function ($atts, $item) {
	if (!empty($atts['href']) && function_exists('dornott_resolve_section_anchor_url')) {
		$atts['href'] = dornott_resolve_section_anchor_url($atts['href']);
	}
	return $atts;
}, 10, 2);

function append_presentation_to_nav($items, $args)
{
	if ($args->theme_location !== 'general_menu') {
		return $items;
	}

	$url = function_exists('dornott_get_presentation_file_url') ? dornott_get_presentation_file_url() : '';
	if ($url === '') {
		return $items;
	}

	$items .= sprintf(
		'<li class="menu__item menu__item--presentation"><a href="%s" class="menu__link" target="_blank" rel="noopener noreferrer">%s</a></li>',
		esc_url($url),
		esc_html('Скачать презентацию')
	);

	return $items;
}
add_filter('wp_nav_menu_items', 'append_presentation_to_nav', 9, 2);

function append_contacts_to_nav($items, $args)
{
	if ($args->theme_location === 'general_menu') {

		ob_start();
		get_template_part('templates/components/menu', 'contacts-block');
		$contacts_item = ob_get_clean();

		$items .= $contacts_item;
	}
	return $items;
}
add_filter('wp_nav_menu_items', 'append_contacts_to_nav', 10, 2);


// убираем с фронта ненужную инфу в хедере
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('wp_head', 'rel_canonical');

remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'start_post_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
remove_action('wp_head', 'parent_post_rel_link', 10, 0);

// Выключаем xmlrpc, ибо дыра
add_filter('xmlrpc_enabled', '__return_false');


// Убираем из панели админки лого вп и обновления
function remove_admin_bar_links()
{
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu('wp-logo');
	$wp_admin_bar->remove_menu('updates');
}
add_action('wp_before_admin_bar_render', 'remove_admin_bar_links');

//фикс ошибок микроразметки
add_filter('disable_wpseo_json_ld_search', '__return_true');
add_filter('wpseo_breadcrumb_separator', '__return_empty_string');


function dornott_is_preloader_enabled()
{
	if (function_exists('get_field')) {
		$value = get_field('show_preloader', 'option');
		return $value === true || $value === 1 || $value === '1';
	}

	$stored = get_option('options_show_preloader');
	return $stored === '1' || $stored === 1 || $stored === true;
}

function add_preloading_body_class($classes)
{
	if (dornott_is_preloader_enabled() && is_front_page()) {
		$classes[] = 'preloading';
	} else {
		$classes[] = 'no-preloader';
		$classes[] = 'is-loaded';
	}
	return $classes;
}
add_filter('body_class', 'add_preloading_body_class');



function currentYear()
{
	return date('Y');
}

// FORM SUBMIT CONFIG

add_action('phpmailer_init', 'configure_smtp_mailer');

function configure_smtp_mailer($phpmailer)
{
	if (!isset($_ENV['SMTP_HOST']) || !isset($_ENV['SMTP_USERNAME']) || !isset($_ENV['SMTP_PASSWORD'])) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host = $_ENV['SMTP_HOST'];
	$phpmailer->SMTPAuth = true;
	$phpmailer->Port = 465;
	$phpmailer->Username = $_ENV['SMTP_USERNAME'];
	$phpmailer->Password = $_ENV['SMTP_PASSWORD'];
	$phpmailer->SMTPSecure = 'ssl';
	$phpmailer->SMTPOptions = [
		'ssl' => [
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true
		]
	];
}

add_filter('wp_mail_from', 'custom_mail_from_email');
function custom_mail_from_email($original_email)
{
	return $_ENV['SMTP_USERNAME'] ?? 'no-reply@dornott.ru';
}

add_filter('wp_mail_from_name', 'custom_mail_from_name');
function custom_mail_from_name($original_name)
{
	return 'Dornott - Уведомления';
}

function get_form_recipients()
{
	$recipients = [];
	if (function_exists('get_field')) {
		$emails_repeater = get_field('send_email', 'option');
		if (is_array($emails_repeater)) {
			foreach ($emails_repeater as $row) {
				if (!empty($row['email'])) {
					$recipients[] = sanitize_email($row['email']);
				}
			}
		}
	}
	return empty($recipients) ? get_option('admin_email') : implode(', ', $recipients);
}

add_action('wp_ajax_send_contact_form', 'handle_universal_form');
add_action('wp_ajax_nopriv_send_contact_form', 'handle_universal_form');

add_action('wp_ajax_send_callback_form', 'handle_universal_form');
add_action('wp_ajax_nopriv_send_callback_form', 'handle_universal_form');

add_action('wp_ajax_send_order_form', 'handle_universal_form');
add_action('wp_ajax_nopriv_send_order_form', 'handle_universal_form');

add_action('wp_ajax_dornott_tbank_notification', 'handle_tbank_notification');
add_action('wp_ajax_nopriv_dornott_tbank_notification', 'handle_tbank_notification');
add_action('dornott_cdek_process', 'dornott_cdek_process');

function handle_universal_form()
{
	if (!dornott_verify_ajax_nonce()) {
		wp_send_json_error(['message' => 'Сессия устарела. Обновите страницу.']);
	}

	$data = $_POST;
	$action = $_POST['action'] ?? '';

	if ($action === 'send_order_form' && !empty($data['order_id'])) {
		$order_id = sanitize_text_field($data['order_id']);
		$pending = get_transient(dornott_pending_order_transient_key($order_id));

		if (!$pending || !dornott_verify_tbank_paid($pending)) {
			wp_send_json_error(['message' => 'Оплата не подтверждена']);
		}

		$result = dornott_complete_paid_order($order_id);
		if (!empty($result['ok'])) {
			wp_send_json_success(['message' => 'Сообщение успешно отправлено']);
		}

		wp_send_json_error(['message' => $result['message'] ?? 'Ошибка при отправке письма']);
	}

	if (!dornott_verify_smartcaptcha($_POST['smart-token'] ?? '')) {
		wp_send_json_error(['message' => 'Подтвердите, что вы не робот']);
	}

	$subjects = [
		'send_contact_form'  => 'Новое сообщение из контактов',
		'send_callback_form' => 'Заказ обратного звонка',
		'send_order_form'    => 'Новый оплаченный заказ',
	];

	$subject = $subjects[$action] ?? 'Новая заявка с сайта';
	$headers = ['Content-Type: text/html; charset=UTF-8'];

	$username = sanitize_text_field($data['username'] ?? '');
	$phone    = sanitize_text_field($data['phone'] ?? '');
	$email    = sanitize_email($data['email'] ?? '');
	$city     = sanitize_text_field($data['city'] ?? '');
	$address  = sanitize_text_field($data['address'] ?? '');
	$delivery = sanitize_text_field($data['delivery'] ?? '');
	$delivery_price = intval($data['delivery_price'] ?? 0);
	$message_text = nl2br(sanitize_textarea_field($data['message'] ?? ''));

	$cart_items = null;
	if (!empty($data['cart_items'])) {
		$decoded = json_decode(stripslashes($data['cart_items']), true);
		if (is_array($decoded)) {
			$cart_items = $decoded;
		}
	}

	$message = dornott_render_order_email_html($subject, [
		'username'       => $username,
		'phone'          => $phone,
		'email'          => $email,
		'city'           => $city,
		'address'        => $address,
		'delivery'       => $delivery,
		'message_text'   => $message_text,
		'cart_items'     => $cart_items,
		'delivery_price' => $delivery_price,
	]);

	$attachments = [];
	if (!empty($_FILES['file']['name'])) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		$uploaded_file = $_FILES['file'];
		$upload_overrides = ['test_form' => false];
		$movefile = wp_handle_upload($uploaded_file, $upload_overrides);

		if ($movefile && !isset($movefile['error'])) {
			$attachments[] = $movefile['file'];
		}
	}

	$to = get_form_recipients();
	$mail_sent = wp_mail($to, $subject, $message, $headers, $attachments);

	if (!empty($attachments)) {
		foreach ($attachments as $file) {
			@unlink($file);
		}
	}

	if ($mail_sent) {
		wp_send_json_success(['message' => 'Сообщение успешно отправлено']);
	} else {
		wp_send_json_error(['message' => 'Ошибка при отправке письма']);
	}
}

// Payment Service

add_action('wp_ajax_init_tbank_payment', 'handle_tbank_init');
add_action('wp_ajax_nopriv_init_tbank_payment', 'handle_tbank_init');

function handle_tbank_init()
{
	if (!dornott_verify_ajax_nonce()) {
		wp_send_json_error(['message' => 'Сессия устарела. Обновите страницу.']);
	}
	$payload = json_decode(file_get_contents('php://input'), true);
	$result = dornott_create_tbank_payment($payload);
	if (!$result['ok']) wp_send_json_error($result['data']);
	wp_send_json_success($result['data']);
}
