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

if (get_field('show_gift', $context_id)):
    $gift_title = get_field('gift_title', $context_id);
    $gift_subtitle = get_field('gift_subtitle', $context_id);
    $gift_image = get_field('gift_image', $context_id);
    $gift_button_full = get_field('gift_button', $context_id);
    $gift_button_data = $gift_button_full['btn'] ?? null;
?>

    <section id="gift" class="gift">
        <div class="container">
            <div class="gift__body">
                <?php if ($gift_image): ?>
                    <div class="gift__image">
                        <img src="<?php echo esc_url($gift_image['url']); ?>" alt="<?php echo esc_attr($gift_image['alt']); ?>">
                    </div>
                <?php endif; ?>
                <div class="gift__content">
                    <?php if ($gift_title): ?>
                        <h2 class="gift__title title"><?php echo esc_html($gift_title); ?></h2>
                    <?php endif; ?>
                    <?php if ($gift_subtitle): ?>
                        <p class="gift__subtitle"><?php echo esc_html($gift_subtitle); ?></p>
                    <?php endif; ?>

                    <?php
                    if ($gift_button_data) {
                        get_template_part('templates/components/button', null, [
                            'data'  => $gift_button_data,
                            'class' => 'gift__btn',
                            'type'  => 'primary',
                            'icon'  => 'chevron-right'
                        ]);
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>