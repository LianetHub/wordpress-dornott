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
                                $button_group_data = $button_group_full['btn'] ?? $button_group_full; // Логика извлечения данных кнопки

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
            <button type="button" class="hero__prev swiper-button-prev"></button>
            <button type="button" class="hero__next swiper-button-next"></button>
        </div>
    </div>
</section>

<?php if (get_field('show_about')) :
    $about_image = get_field('about_image');
    $about_title = get_field('about_title');
    $about_description = get_field('about_description');
    $about_benefits = have_rows('about_benefits');
?>
    <section id="about" class="about">
        <div class="container">
            <div class="about__header">

                <?php if ($about_image) : ?>
                    <div class="about__image">
                        <img src="<?php echo esc_url($about_image['url']); ?>" alt="<?php echo esc_attr($about_image['alt']); ?>">
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
                    <?php while (have_rows('about_benefits')) : the_row(); ?>
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
            <ul class="catalog__grid products columns-3">
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


<?php if (get_field('show_special_offer')) :
    $title = get_field('special_offer_title');
    $subtitle = get_field('special_offer_subtitle');
    $special_offer_btn = get_field('special_offer_button');
    $sale_value = get_field('special_offer_sale_value');
    $image = get_field('special_offer_image');
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
                            'class' => 'cert__btn',
                            'type'  => 'primary',
                            'icon'  => 'chevron-right'
                        ]);
                    }
                    ?>
                </div>

                <div class="special-offer__image-wrapper">
                    <?php if ($sale_value) : ?>
                        <div class="special-offer__label">
                            <?php echo esc_html($sale_value); ?>% скидка
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

<?php if (get_field('show_cert')):
    $cert_title = get_field('cert_title');
    $cert_subtitle = get_field('cert_subtitle');
    $cert_button = get_field('cert_button');
    $cert_widget = get_field('cert_widget');
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

                    <?php
                    $cert_button_group = $cert_button['btn'];
                    if ($cert_button_group) {
                        get_template_part('templates/components/button', null, [
                            'data'  => $cert_button_group,
                            'class' => 'cert__btn',
                            'type'  => 'primary',
                            'icon'  => 'chevron-right'
                        ]);
                    }
                    ?>
                </div>
                <?php if ($cert_widget): ?>
                    <div class="cert__widget">
                        <?php echo $cert_widget; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>


<?php if (get_field('show_order_steps')):
    $steps_title = get_field('order_steps_title');
    $steps_items = get_field('order_steps_items');
    $order_terms_group = get_field('order_terms');
    $terms_title = $order_terms_group['order_terms_title'] ?? '';
    $terms_button = $order_terms_group['order_terms_button_btn'] ?? null;
?>
    <section id="steps" class="steps">
        <div class="container">
            <div class="steps__header">
                <?php if ($steps_title): ?>
                    <h2 class="steps__title title"><?php echo esc_html($steps_title); ?></h2>
                <?php endif; ?>
                <p class="steps__subtitle">
                    <?php
                    $num_steps = is_array($steps_items) ? count($steps_items) : 0;
                    echo "Простое оформление в {$num_steps} шагов";
                    ?>
                </p>
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
                                <li class="steps__item">
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
                                    <div class="steps__item-hint">Шаг</div>
                                    <div class="steps__item-number"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></div>
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
        </div>
    </section>
<?php endif; ?>

<?php if (get_field('show_reviews')):
    $reviews_title = get_field('reviews_title');
    $reviews_subtitle = get_field('reviews_subtitle');
    $reviews_text_items = get_field('reviews_text');
    $reviews_screenshots = get_field('reviews_screenshots');
    $reviews_default_type = get_field('reviews_deafult_type');
?>
    <section id="reviews" class="reviews">
        <div class="container">
            <div class="reviews__header">
                <div class="reviews__info">
                    <?php if ($reviews_title): ?>
                        <h2 class="reviews__title title"><?php echo esc_html($reviews_title); ?></h2>
                    <?php endif; ?>
                    <?php if ($reviews_subtitle): ?>
                        <p class="reviews__subtitle"><?php echo esc_html($reviews_subtitle); ?></p>
                    <?php endif; ?>
                </div>
                <div class="reviews__controls">
                    <div class="reviews__pagination swiper-pagination"></div>
                    <button type="button" class="reviews__prev swiper-button-prev"></button>
                    <button type="button" class="reviews__next swiper-button-next"></button>
                </div>
            </div>

            <?php if ($reviews_text_items || $reviews_screenshots): ?>
                <?php

                $text_active = $reviews_default_type;

                if (!$reviews_screenshots) {
                    $text_active = true;
                } elseif (!$reviews_text_items) {
                    $text_active = false;
                }


                $check_text = $text_active ? 'checked' : '';
                $check_screenshots = $text_active ? '' : 'checked';

                $text_style = $text_active ? '' : 'display: none;';
                $screenshots_style = $text_active ? 'display: none;' : '';
                ?>

                <div class="reviews__switcher switcher">
                    <?php if ($reviews_text_items): ?>
                        <label class="switcher__item">
                            <input type="radio"
                                name="reviews-type"
                                value="text"
                                <?php echo $check_text; ?>
                                class="switcher__input hidden" hidden>
                            <span class="switcher__btn">Текстовые</span>
                        </label>
                    <?php endif; ?>

                    <?php if ($reviews_screenshots): ?>
                        <label class="switcher__item">
                            <input type="radio"
                                name="reviews-type"
                                value="screenshots"
                                <?php echo $check_screenshots; ?>
                                class="switcher__input hidden" hidden>
                            <span class="switcher__btn">Из соц. сетей</span>
                        </label>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="reviews__content">
                <?php if ($reviews_text_items): ?>
                    <div class="reviews__text" style="<?php echo esc_attr($text_style); ?>">
                        <div class="reviews__slider swiper">
                            <ul class="swiper-wrapper">
                                <?php foreach ($reviews_text_items as $review):
                                    $author_group = $review['author'] ?? [];
                                    $is_company = $author_group['is_company'] ?? false;
                                    $company_group = $author_group['company'] ?? [];

                                    $person_name = $author_group['name'] ?? '';
                                    $person_thumb = $author_group['thumb'] ?? null;
                                    $person_info_html = '';

                                    if ($is_company) {
                                        $company_name = $company_group['company_name'] ?? '';
                                        $company_position = $company_group['company_position'] ?? '';
                                        $company_url = $company_group['company_url'] ?? '';

                                        if ($company_position) {
                                            $person_info_html .= esc_html($company_position);
                                        }
                                        if ($company_name) {
                                            $person_info_html .= ($person_info_html ? ' в ' : '') . '<a href="' . esc_url($company_url) . '" target="_blank">@' . esc_html($company_name) . '</a>';
                                        }
                                    } else {
                                        $person_info_html = esc_html($author_group['client_info'] ?? '');
                                    }
                                ?>
                                    <li class="reviews__slide swiper-slide">
                                        <div class="review-card">
                                            <div class="review-card__header">
                                                <?php if ($review['city']): ?>
                                                    <div class="review-card__place"><?php echo esc_html($review['city']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($review['date']): ?>
                                                    <?php
                                                    $date_format = 'd.m.Y';
                                                    $formatted_date = date_i18n($date_format, strtotime($review['date']));
                                                    ?>
                                                    <time datetime="<?php echo esc_attr($review['date']); ?>" class="review-card__time"><?php echo esc_html($formatted_date); ?></time>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($review['text']): ?>
                                                <blockquote class="review-card__qoute"><?php echo $review['text']; ?></blockquote>
                                            <?php endif; ?>
                                            <div class="review-card__footer">
                                                <?php if ($person_thumb): ?>
                                                    <div class="review-card__thumb">
                                                        <img src="<?php echo esc_url($person_thumb['sizes']['thumbnail']); ?>" alt="<?php echo esc_attr($person_thumb['alt']); ?>">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="review-card__person">
                                                    <?php if ($person_name): ?>
                                                        <div class="review-card__person-name"><?php echo esc_html($person_name); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($person_info_html): ?>
                                                        <div class="review-card__person-info">
                                                            <?php echo $person_info_html; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($reviews_screenshots): ?>
                    <div class="reviews__screenshots" style="<?php echo esc_attr($screenshots_style); ?>">
                        <div class="reviews__slider swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($reviews_screenshots as $image): ?>
                                    <a href="<?php echo esc_url($image['url']); ?>" data-fancybox="screenshots-reviews" class="reviews__slide swiper-slide">
                                        <img src="<?php echo esc_url($image['sizes']['large']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (get_field('show_gift')):
    $gift_title = get_field('gift_title');
    $gift_subtitle = get_field('gift_subtitle');
    $gift_image = get_field('gift_image');
    $gift_button_full = get_field('gift_button');
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

<?php if (get_field('show_contacts')):
    $contacts_title = get_field('contacts_title');
    $contacts_form_group = get_field('contacts_form');

    $form_caption = $contacts_form_group['title'];
    $form_text = $contacts_form_group['subtitle'];

    $global_address = get_field('address', 'option') ?? '';
    $global_hours = get_field('working_hours', 'option') ?? '';
    $global_phone = get_field('phone', 'option') ?? '';
    $global_email = get_field('email', 'option') ?? '';
    $global_map_coords = get_field('coords', 'option') ?? '';
    $global_telegram = get_field('telegram_url', 'option') ?? '';
    $global_whatsapp = get_field('whatsapp_url', 'option') ?? '';
?>
    <section id="contacts" class="contacts">
        <div class="container">
            <?php if ($contacts_title): ?>
                <h2 class="contacts__title title"><?php echo esc_html($contacts_title); ?></h2>
            <?php endif; ?>

            <div class="contacts__body">
                <form action="#" class="contacts__form form">
                    <div class="contacts__form-header">
                        <?php if ($form_caption): ?>
                            <div class="contacts__form-caption"><?php echo esc_html($form_caption); ?></div>
                        <?php endif; ?>
                        <?php if ($form_text): ?>
                            <div class="contacts__form-text"><?php echo esc_html($form_text); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="contacts__form-fields">
                        <label class="contacts__form-field contacts__form-field--large form__field">
                            <input type="text" name="name" data-required class="form__control" placeholder="">
                            <span class="form__field-placeholder">Ваше имя</span>
                        </label>
                        <label class="contacts__form-field form__field">
                            <input type="tel" name="phone" data-required class="form__control" placeholder="">
                            <span class="form__field-placeholder">Телефон</span>
                        </label>
                        <label class="contacts__form-field form__field">
                            <input type="email" name="email" data-required class="form__control" placeholder="">
                            <span class="form__field-placeholder">Email</span>
                        </label>
                        <label class="contacts__form-field contacts__form-field--large form__field">
                            <textarea name="message" class="form__control" placeholder=""></textarea>
                            <span class="form__field-placeholder">Ваш вопрос или предложение...</span>
                        </label>
                        <label class="form__file contacts__form-field--large">
                            <input type="file" name="file" class="form__file-input" hidden>
                            <span class="form__file-btn icon-clip">Прикрепить (до 10 мб.)</span>
                        </label>
                    </div>
                    <label class="form__checkbox">
                        <input type="checkbox" name="agree" data-required class="form__checkbox-input" hidden>
                        <span class="form__checkbox-text">
                            Нажимая кнопку, вы соглашаетесь с
                            <a href="#privacy-policy" data-fancybox>
                                политикой конфиденциальности сайта
                            </a>
                        </span>
                    </label>
                    <button type="submit" class="form__btn btn btn-primary">Отправить</button>
                </form>

                <div class="contacts__map">
                    <?php if ($global_map_coords): ?>
                        <div class="contacts__map-header">
                            <div id="map" class="contacts__map-block" data-coords="<?php echo esc_attr($global_map_coords); ?>"></div>
                        </div>
                    <?php endif; ?>
                    <ul class="contacts__list">
                        <?php if ($global_address): ?>
                            <li class="contacts__list-item">
                                <div class="contacts__caption">Адрес</div>
                                <address class="contacts__text"><?php echo esc_html($global_address); ?></address>
                            </li>
                        <?php endif; ?>

                        <?php if ($global_hours): ?>
                            <li class="contacts__list-item">
                                <div class="contacts__caption">Режим работы</div>
                                <div class="contacts__text"><?php echo esc_html($global_hours); ?></div>
                            </li>
                        <?php endif; ?>

                        <?php if ($global_phone || $global_telegram || $global_whatsapp): ?>
                            <li class="contacts__list-item">
                                <div class="contacts__caption">Телефон</div>
                                <?php if ($global_phone): ?>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^\d\+]/', '', $global_phone)); ?>" class="contacts__link"><?php echo esc_html($global_phone); ?></a>
                                <?php endif; ?>

                                <?php if ($global_telegram || $global_whatsapp): ?>
                                    <div class="contacts__socials socials">
                                        <?php if ($global_telegram): ?>
                                            <a href="<?php echo esc_url($global_telegram); ?>" aria-label="Следите за нами в Telegram" class="socials__link icon-telegram"></a>
                                        <?php endif; ?>
                                        <?php if ($global_whatsapp): ?>
                                            <a href="<?php echo esc_url($global_whatsapp); ?>" aria-label="Следите за нами в WhatsApp" class="socials__link icon-whatsapp"></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>

                        <?php if ($global_email): ?>
                            <li class="contacts__list-item">
                                <div class="contacts__caption">Email</div>
                                <a href="mailto:<?php echo esc_attr($global_email); ?>" class="contacts__link"><?php echo esc_html($global_email); ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>


<?php get_footer(); ?>