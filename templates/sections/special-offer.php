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

if (get_field('show_special_offer', $context_id)) :
    $title = get_field('special_offer_title', $context_id);
    $subtitle = get_field('special_offer_subtitle', $context_id);
    $special_offer_btn = get_field('special_offer_button', $context_id);
    $sale_value = get_field('special_offer_sale_value', $context_id);
    $image = get_field('special_offer_image', $context_id);
?>
    <section class="special-offer">
        <div class="container">
            <div class="special-offer__body">
                <div class="special-offer__main">
                    <?php if ($title) : ?>
                        <h2 class="special-offer__title title">
                            <?php echo esc_html($title); ?>
                        </h2>
                    <?php endif; ?>

                    <?php if ($subtitle) : ?>
                        <p class="special-offer__subtitle">
                            <?php echo $subtitle; ?>
                        </p>
                    <?php endif; ?>

                    <?php
                    if ($special_offer_btn) {
                        get_template_part('templates/components/button', null, [
                            'data'  => $special_offer_btn,
                            'class' => 'special-offer__btn',
                            'type'  => 'primary',
                            'icon'  => 'chevron-right'
                        ]);
                    }
                    ?>
                    <?php
                    $special_offer_button_group = $special_offer_btn['btn'];
                    if ($special_offer_button_group) {
                        get_template_part('templates/components/button', null, [
                            'data'  => $special_offer_button_group,
                            'class' => 'special-offer__btn',
                            'type'  => 'primary',
                            'icon'  => 'chevron-right'
                        ]);
                    }
                    ?>
                </div>

                <div class="special-offer__image-wrapper">
                    <?php if ($sale_value) : ?>
                        <div class="special-offer__label">
                            <span>
                                <?php echo esc_html($sale_value); ?>%
                            </span>
                            скидка
                        </div>
                    <?php endif; ?>

                    <?php if ($image) : ?>
                        <div class="special-offer__image">
                            <img src="<?php echo esc_url($image['url']); ?>"
                                alt="<?php echo esc_attr($image['alt']); ?>"
                                class="cover-image">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>