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

if (get_field('show_presentation', $context_id)) :
    $presentation_title = get_field('presentation_title', $context_id);
    $presentation_subtitle = get_field('presentation_subtitle', $context_id);
    $presentation_btn_text = get_field('presentation_btn_text', $context_id) ?: 'Скачать презентацию';
    $presentation_image = get_field('presentation_image', $context_id);
    $presentation_file_url = function_exists('dornott_get_presentation_file_url')
        ? dornott_get_presentation_file_url()
        : '';
?>
    <section id="presentation" class="gift">
        <div class="container">
            <div class="gift__body">
                <div class="gift__content">
                    <?php if ($presentation_title) : ?>
                        <h2 class="gift__title title"><?php echo esc_html($presentation_title); ?></h2>
                    <?php endif; ?>
                    <?php if ($presentation_subtitle) : ?>
                        <p class="gift__subtitle"><?php echo $presentation_subtitle; ?></p>
                    <?php endif; ?>

                    <?php if ($presentation_file_url) : ?>
                        <a href="<?php echo esc_url($presentation_file_url); ?>"
                            class="btn btn-primary icon-chevron-right gift__btn"
                            target="_blank"
                            rel="noopener noreferrer">
                            <?php echo esc_html($presentation_btn_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($presentation_image) : ?>
                    <div class="gift__image">
                        <img src="<?php echo esc_url($presentation_image['url']); ?>"
                            alt="<?php echo esc_attr($presentation_image['alt']); ?>">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
