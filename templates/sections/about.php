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

if (get_field('show_about', $context_id)) :
    $about_image = get_field('about_image', $context_id);
    $about_title = get_field('about_title', $context_id);
    $about_description = get_field('about_description', $context_id);
    $about_benefits = have_rows('about_benefits', $context_id);
?>
    <section id="about" class="about">
        <div class="container">
            <div class="about__header">

                <?php if ($about_image) : ?>
                    <div class="about__image">
                        <img src="<?php echo esc_url($about_image['url']); ?>" alt="<?php echo esc_attr($about_image['alt']); ?>" class="cover-image">
                    </div>
                <?php endif; ?>

                <div class="about__info">
                    <?php if ($about_title) : ?>
                        <h2 class="about__title title"><?php echo esc_html($about_title); ?></h2>
                    <?php endif; ?>

                    <?php if ($about_description) : ?>
                        <div class="about__description"><?php echo $about_description; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($about_benefits) : ?>
                <ul class="about__benefits">
                    <?php while (have_rows('about_benefits', $context_id)) : the_row(); ?>
                        <?php
                        $icon = get_sub_field('icon');
                        $title = get_sub_field('title');
                        $description = get_sub_field('description');
                        ?>
                        <li class="about__benefit">

                            <?php if ($icon) : ?>
                                <div class="about__benefit-icon">
                                    <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>">
                                </div>
                            <?php endif; ?>

                            <?php if ($title) : ?>
                                <div class="about__benefit-title"><?php echo esc_html($title); ?></div>
                            <?php endif; ?>

                            <?php if ($description) : ?>
                                <div class="about__benefit-description"><?php echo $description; ?></div>
                            <?php endif; ?>

                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>