<?php get_header(); ?>

<section class="hero" id="hero">
    <div class="hero__main">
        <div class="container">
            <div class="hero__offer">
                <div class="hero__offer-slider swiper">
                    <div class="swiper-wrapper">
                        <?php if (have_rows('hero_slides')) : ?>
                            <?php while (have_rows('hero_slides')) : the_row(); ?>

                                <?php
                                $button_group = get_sub_field('btn');
                                if (!$button_group) {
                                    $button_group = get_sub_field('hero_button_btn');
                                }
                                ?>

                                <div class="hero__offer-slide swiper-slide">
                                    <?php if (get_sub_field('title')) : ?>
                                        <h1 class="hero__offer-title title-lg">
                                            <?php the_sub_field('title'); ?>
                                        </h1>
                                    <?php endif; ?>

                                    <?php if (get_sub_field('description')) : ?>
                                        <p class="hero__offer-description">
                                            <?php the_sub_field('description'); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php
                                    if ($button_group) {
                                        get_template_part('templates/components/button', null, [
                                            'data'  => $button_group,
                                            'class' => 'hero__offer-btn',
                                            'type'  => 'primary',
                                            'icon'  => 'chevron-right'
                                        ]);
                                    }
                                    ?>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (get_field('hero_footer_text')) : ?>
                    <div class="hero__offer-footer">
                        <?php echo esc_html(get_field('hero_footer_text')); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="hero__images swiper">
        <div class="swiper-wrapper">
            <?php if (have_rows('hero_slides')) : ?>
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
            <button type="button" class="hero__prev swiper-button-prev"></button>
            <button type="button" class="hero__next swiper-button-next"></button>
        </div>
    </div>
</section>

<?php get_footer(); ?>