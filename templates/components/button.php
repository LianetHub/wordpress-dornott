<?php

/**
 * Компонент кнопки
 * Шаблон для вывода универсальной кнопки (ссылки или попапа) 
 * с гибкой настройкой стилей, типов и иконок через аргументы.
 *
 * @param array $args {
 * Аргументы, передаваемые в компонент через get_template_part.
 *
 * @type array  $data  Обязательный. Массив данных кнопки, полученный из ACF Group Field.
 * @type string $class Необязательный. Дополнительные классы для позиционирования (например, 'hero__offer-btn').
 * @type string $type  Необязательный. Тип кнопки для стилизации (по умолчанию 'primary'). Генерирует класс btn-{type}.
 * @type string $icon  Необязательный. Имя иконки (например, 'chevron-right'). Генерирует класс icon-{icon}.
 * }
 */
?>

<?php
$data = $args['data'] ?? [];
$custom_class = $args['class'] ?? '';
$type = $args['type'] ?? 'primary';
$icon = $args['icon'] ?? false;

if (empty($data) || empty($data['btn_txt'])) {
    return;
}

$classes = ['btn'];
$classes[] = 'btn-' . $type;

if ($icon) {
    $classes[] = 'icon-' . $icon;
}

if ($custom_class) {
    $classes[] = $custom_class;
}

$btn_txt = $data['btn_txt'];
$is_link = $data['is_link'];
?>

<?php if ($is_link) : ?>
    <?php
    $btn_link = $data['btn_link'];
    $btn_target = !empty($data['btn_target']) ? '_blank' : '_self';

    if ($btn_target === '_self') {
        $classes[] = 'anchor';
    }

    $class_string = implode(' ', $classes);
    ?>
    <a href="<?php echo esc_url($btn_link); ?>"
        class="<?php echo esc_attr($class_string); ?>"
        target="<?php echo esc_attr($btn_target); ?>">
        <?php echo esc_html($btn_txt); ?>
    </a>

<?php else : ?>
    <?php
    $btn_popup = $data['btn_popup'];
    $class_string = implode(' ', $classes);

    $policy_ids = ['#privacy-policy', '#data-protection', '#payment-and-delivery'];
    $data_src = in_array($btn_popup, $policy_ids) ? ' data-src="#policies"' : '';
    ?>
    <a href="<?php echo esc_attr($btn_popup); ?>"
        <?php echo $data_src; ?>
        data-fancybox
        class="<?php echo esc_attr($class_string); ?>">
        <?php echo esc_html($btn_txt); ?>
    </a>
<?php endif; ?>