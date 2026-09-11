<?php
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
            return (int) get_option('page_on_front');
        }
        $id = (int) get_queried_object_id();
        return $id ?: (int) get_option('page_on_front');
    }
}

$context_id = dornott_sections_context_id();

if (get_field('show_order_steps', $context_id)):
    $steps_title = get_field('order_steps_title', $context_id);
    $steps_items = get_field('order_steps_items', $context_id);
    $order_terms_group = get_field('order_terms', $context_id);
    $terms_title = $order_terms_group['order_terms_title'];
    $terms_button = $order_terms_group['order_terms_button_btn'] ?? null;
?>
    <section id="steps" class="steps">
        <div class="container">
            <div class="steps__header">
                <?php if ($steps_title): ?>
                    <h2 class="steps__title title"><?php echo esc_html($steps_title); ?></h2>
                <?php endif; ?>
                <p class="steps__subtitle">
                    Простое оформление в
                    <?php
                    $num_steps = is_array($steps_items) ? count($steps_items) : 0;
                    ?>
                    <span><?php echo $num_steps; ?> шагов</span>
                </p>
            </div>
            <div class="steps__body">
                <?php if ($steps_items): ?>
                    <ul class="steps__list">
                        <?php
                        $i = 1;
                        foreach ($steps_items as $item):
                            $item_image = $item['image'] ?? null;
                            $item_title = $item['title'] ?? '';
                            $item_description = $item['description'] ?? '';
                        ?>

                            <li class="steps__item <?php if ($i == 1): ?> active <?php endif; ?>">
                                <div class="steps__item-content">
                                    <?php if ($item_image): ?>
                                        <div class="steps__item-image">
                                            <img src="<?php echo esc_url($item_image['url']); ?>" alt="<?php echo esc_attr($item_image['alt']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($item_title): ?>
                                        <div class="steps__item-title"><?php echo esc_html($item_title); ?></div>
                                    <?php endif; ?>
                                    <?php if ($item_description): ?>
                                        <p class="steps__item-subtitle"><?php echo esc_html($item_description); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="steps__item-order">
                                    <div class="steps__item-hint">Шаг</div>
                                    <div class="steps__item-number"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></div>
                                </div>
                            </li>
                        <?php
                            $i++;
                        endforeach;
                        ?>
                    </ul>
                <?php endif; ?>
                <?php if ($terms_title || $terms_button): ?>
                    <div class="steps__terms">
                        <?php if ($terms_title): ?>
                            <div class="steps__terms-title"><?php echo esc_html($terms_title); ?></div>
                        <?php endif; ?>
                        <?php
                        if ($terms_button) {
                            get_template_part('templates/components/button', null, [
                                'data'  => $terms_button,
                                'class' => 'steps__terms-btn',
                                'type'  => 'primary',
                                'icon'  => 'chevron-right'
                            ]);
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>