<?php

require_once('includes/admin-custom.php');
require_once('includes/acf-custom.php');
require_once('includes/woocommerce-custom.php');

// =========================================================================
// 1. CONSTANTS
// =========================================================================

define('TEMPLATE_PATH', dirname(__FILE__) . '/templates/');

// =========================================================================
// 2. ENQUEUE STYLES AND SCRIPTS
// =========================================================================

add_theme_support('title-tag');

// Enqueue theme styles (CSS)
function theme_enqueue_styles()
{
	wp_enqueue_style('swiper', get_template_directory_uri() . '/assets/css/libs/swiper-bundle.min.css');
	wp_enqueue_style('fancybox', get_template_directory_uri() . '/assets/css/libs/fancybox.css');
	wp_enqueue_style('reset', get_template_directory_uri() . '/assets/css/reset.min.css');
	wp_enqueue_style('main-style', get_template_directory_uri() . '/assets/css/style.min.css');
}
add_action('wp_enqueue_scripts', 'theme_enqueue_styles');


// Enqueue theme scripts (JS)
function theme_enqueue_scripts()
{
	wp_deregister_script('jquery');
	wp_enqueue_script('jquery', get_template_directory_uri() . '/assets/js/libs/jquery-3.7.1.min.js', array(), null, true);
	wp_enqueue_script('swiper-js', get_template_directory_uri() . '/assets/js/libs/swiper-bundle.min.js', array(), null, true);
	wp_enqueue_script('fancybox-js', get_template_directory_uri() . '/assets/js/libs/fancybox.umd.js', array(), null, true);
	wp_enqueue_script('app-js', get_template_directory_uri() . '/assets/js/app.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'theme_enqueue_scripts');


// =========================================================================
// 3. THEME SUPPORT AND UTILITIES
// =========================================================================

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


function add_preloading_body_class($classes)
{
	$classes[] = 'preloading';
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
	if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = SMTP_HOST;
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Port       = 465;
	$phpmailer->Username   = SMTP_USERNAME;
	$phpmailer->Password   = SMTP_PASSWORD;
	$phpmailer->SMTPSecure = 'ssl';
	$phpmailer->From       = SMTP_USERNAME;
	$phpmailer->FromName   = get_bloginfo('name');
}

add_filter('wp_mail_from', 'custom_mail_from_email');
add_filter('wp_mail_from_name', 'custom_mail_from_name');

function custom_mail_from_email($original_email)
{
	return 'no-reply@dornott.ru';
}

function custom_mail_from_name($original_name)
{
	return 'Dornott - Уведомления';
}

add_action('wp_ajax_send_contact_form', 'handle_universal_form');
add_action('wp_ajax_nopriv_send_contact_form', 'handle_universal_form');

add_action('wp_ajax_send_callback_form', 'handle_universal_form');
add_action('wp_ajax_nopriv_send_callback_form', 'handle_universal_form');

add_action('wp_ajax_send_order_form', 'handle_universal_form');
add_action('wp_ajax_nopriv_send_order_form', 'handle_universal_form');

function handle_universal_form()
{
	$data = $_POST;
	$action = $_POST['action'] ?? '';

	$subjects = [
		'send_contact_form'  => 'Новое сообщение из контактов',
		'send_callback_form' => 'Заказ обратного звонка',
		'send_order_form'    => 'Заявка на корпоративный заказ',
	];

	$subject = $subjects[$action] ?? 'Новая заявка с сайта';
	$headers = ['Content-Type: text/html; charset=UTF-8'];

	$username = sanitize_text_field($data['username'] ?? '');
	$phone    = sanitize_text_field($data['phone'] ?? '');
	$message_text = nl2br(sanitize_textarea_field($data['message'] ?? ''));

	ob_start();
?>
	<h3><?php echo esc_html($subject); ?></h3>
	<p><strong>Имя:</strong> <?php echo esc_html($username); ?></p>
	<p><strong>Телефон:</strong> <?php echo esc_html($phone); ?></p>
	<?php if ($message_text): ?>
		<p><strong>Сообщение/Вопрос:</strong><br><?php echo $message_text; ?></p>
	<?php endif; ?>
	<hr>
	<p><small>Отправлено с сайта dornott.ru</small></p>
	<?php
	$message = ob_get_clean();

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

	$to = get_option('admin_email');
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

add_action('wp_ajax_process_checkout', 'handle_checkout_form');
add_action('wp_ajax_nopriv_process_checkout', 'handle_checkout_form');

function handle_checkout_form()
{
	$data = $_POST;

	$to = get_option('admin_email');
	$subject = 'Новый заказ через корзину (Ожидает оплаты)';
	$headers = ['Content-Type: text/html; charset=UTF-8'];

	ob_start();
	?>
	<h3>Детали заказа из корзины</h3>
	<p><strong>ФИО:</strong> <?php echo esc_html($data['username']); ?></p>
	<p><strong>Телефон:</strong> <?php echo esc_html($data['phone']); ?></p>
	<p><strong>Email:</strong> <?php echo esc_html($data['email']); ?></p>
	<p><strong>Город:</strong> <?php echo esc_html($data['city']); ?></p>
	<p><strong>Адрес:</strong> <?php echo esc_html($data['address']); ?></p>
	<p><strong>Доставка:</strong> <?php echo esc_html($data['delivery'] === 'cdek' ? 'СДЭК' : 'Самовывоз'); ?></p>
	<p><strong>Комментарий:</strong> <?php echo nl2br(esc_html($data['message'])); ?></p>
	<hr>
	<h4>Данные для оплаты будут сформированы здесь</h4>
<?php
	$message = ob_get_clean();

	wp_mail($to, $subject, $message, $headers);

	wp_send_json_success([
		'message' => 'Заказ принят, перенаправляем на оплату...',
		'redirect_url' => '#'
	]);
}
