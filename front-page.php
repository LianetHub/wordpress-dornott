<?php get_header(); ?>

<section class="hero" id="hero">
    <?php
    $hero_slides = have_rows('hero_slides');
    $hero_footer_block = get_field('hero_footer_block');
    $footer_image = $hero_footer_block['image'] ?? null;
    $footer_text = $hero_footer_block['text'] ?? '';
    ?>
    <div class="hero__main">
        <div class="container">
            <div class="hero__offer">
                <div class="hero__offer-slider swiper">
                    <div class="swiper-wrapper">
                        <?php if ($hero_slides) : ?>
                            <?php $slide_index = 0; ?>
                            <?php while (have_rows('hero_slides')) : the_row(); ?>

                                <?php
                                $button_group_full = get_sub_field('btn');
                                if (!$button_group_full) {
                                    $button_group_full = get_sub_field('hero_button_btn');
                                }
                                $button_group_data = $button_group_full['btn'] ?? $button_group_full;

                                $tag = ($slide_index === 0) ? 'h1' : 'div';
                                ?>

                                <div class="hero__offer-slide swiper-slide">
                                    <<?php echo $tag; ?> class="hero__offer-title title-lg">
                                        <?php the_sub_field('title'); ?>
                                    </<?php echo $tag; ?>>

                                    <?php if (get_sub_field('description')) : ?>
                                        <p class="hero__offer-description">
                                            <?php the_sub_field('description'); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php
                                    if ($button_group_data) {
                                        get_template_part('templates/components/button', null, [
                                            'data'  => $button_group_data,
                                            'class' => 'hero__offer-btn',
                                            'type'  => 'primary',
                                            'icon'  => 'chevron-right'
                                        ]);
                                    }
                                    ?>
                                </div>
                                <?php $slide_index++; ?>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($hero_footer_block) : ?>
                    <div class="hero__tagline">
                        <?php if ($footer_image) : ?>
                            <div class="hero__tagline-image">
                                <img src="<?php echo esc_url($footer_image['url']); ?>"
                                    alt="<?php echo esc_attr($footer_image['alt']); ?>"
                                    class="cover-image">
                            </div>
                        <?php endif; ?>

                        <?php if ($footer_text) : ?>
                            <div class="hero__tagline-text">
                                <?php echo esc_html($footer_text); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="hero__images swiper">
        <div class="swiper-wrapper">
            <?php if ($hero_slides) : ?>
                <?php while (have_rows('hero_slides')) : the_row(); ?>
                    <?php $image = get_sub_field('image'); ?>
                    <?php if ($image) : ?>
                        <div class="hero__image swiper-slide">
                            <img src="<?php echo esc_url($image['url']); ?>"
                                alt="<?php echo esc_attr($image['alt']); ?>"
                                class="cover-image">
                        </div>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        <div class="hero__controls">
            <div class="hero__pagination swiper-pagination"></div>
            <div class="hero__controls-btns">
                <button type="button" class="hero__prev swiper-button-prev"></button>
                <button type="button" class="hero__next swiper-button-next"></button>
            </div>
        </div>
    </div>
</section>

<?php get_template_part('templates/sections/about'); ?>

<section id="catalog" class="catalog">
    <div class="container">
        <h2 class="catalog__title title">Наша продукция</h2>
        <p class="catalog__subtitle">Мы дарим теплоту и заботу каждому</p>

        <?php
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'status'         => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );

        $products_query = new WP_Query($args);

        if ($products_query->have_posts()) :
        ?>
            <ul class="catalog__grid">
                <?php
                while ($products_query->have_posts()) :
                    $products_query->the_post();

                    wc_get_template_part('content', 'product');
                endwhile;
                ?>
            </ul>

        <?php else : ?>
            <p>В настоящее время товаров нет.</p>
        <?php endif;

        wp_reset_postdata();
        ?>
    </div>
</section>


<?php get_template_part('templates/sections/special-offer'); ?>

<?php if (get_field('show_presentation')) :
    $presentation_title = get_field('presentation_title');
    $presentation_subtitle = get_field('presentation_subtitle');
    $presentation_btn_text = get_field('presentation_btn_text') ?: 'Скачать презентацию';
    $presentation_file = get_field('presentation_file');
    $presentation_image = get_field('presentation_image');
    $presentation_file_url = is_array($presentation_file) ? ($presentation_file['url'] ?? '') : $presentation_file;
?>
    <section id="presentation" class="special-offer special-offer--presentation">
        <div class="container">
            <div class="special-offer__body">
                <div class="special-offer__image-wrapper">
                    <?php if ($presentation_image) : ?>
                        <div class="special-offer__image">
                            <img src="<?php echo esc_url($presentation_image['url']); ?>"
                                alt="<?php echo esc_attr($presentation_image['alt']); ?>"
                                class="cover-image">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="special-offer__main">
                    <?php if ($presentation_title) : ?>
                        <h2 class="special-offer__title title">
                            <?php echo esc_html($presentation_title); ?>
                        </h2>
                    <?php endif; ?>

                    <?php if ($presentation_subtitle) : ?>
                        <p class="special-offer__subtitle">
                            <?php echo $presentation_subtitle; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($presentation_file_url) : ?>
                        <a href="<?php echo esc_url($presentation_file_url); ?>"
                            class="btn btn-primary icon-chevron-right special-offer__btn"
                            target="_blank"
                            rel="noopener noreferrer">
                            <?php echo esc_html($presentation_btn_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (get_field('show_cert')):
    $cert_title = get_field('cert_title');
    $cert_subtitle = get_field('cert_subtitle');
    $cert_btn_text = get_field('cert_btn_text') ?? "Купить сертификат";
    $cert_image = get_field('cert_image');
?>
    <section id="cert" class="cert">
        <div class="container">
            <div class="cert__body">
                <div class="cert__offer">
                    <?php if ($cert_title): ?>
                        <h2 class="cert__title title"><?php echo $cert_title; ?></h2>
                    <?php endif; ?>
                    <?php if ($cert_subtitle): ?>
                        <p class="cert__subtitle">
                            <?php echo $cert_subtitle; ?>
                        </p>
                    <?php endif; ?>
                    <button type="button" class="js_iframe_widget cert__btn btn btn-primary icon-chevron-right"><?= $cert_btn_text ?></button>
                    <script type="text/javascript">
                        document.addEventListener("DOMContentLoaded", function() {
                            IframeWidgetFunctional.init('.js_iframe_widget');
                        });
                    </script>
                </div>
                <?php if ($cert_image): ?>
                    <div class="cert__image">
                        <img src="<?php echo esc_url($cert_image['url']); ?>"
                            alt="<?php echo esc_attr($cert_image['alt']); ?>"
                            class="cover-image">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php get_template_part('templates/sections/steps'); ?>
<?php get_template_part('templates/sections/reviews'); ?>
<?php get_template_part('templates/sections/gift'); ?>
<?php get_template_part('templates/sections/contacts'); ?>

<?php get_footer(); ?>