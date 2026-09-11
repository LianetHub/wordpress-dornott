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

if (get_field('show_reviews', $context_id)):
    $reviews_title = get_field('reviews_title', $context_id);
    $reviews_subtitle = get_field('reviews_subtitle', $context_id);
    $reviews_text_items = get_field('reviews_text', $context_id);
    $reviews_screenshots = get_field('reviews_screenshots', $context_id);
    $reviews_default_type = get_field('reviews_deafult_type', $context_id);

?>
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
    <section id="reviews" class="reviews">
        <div class="container">
            <div class="reviews__header">
                <div class="reviews__header-main">
                    <div class="reviews__info">
                        <?php if ($reviews_title): ?>
                            <h2 class="reviews__title title"><?php echo esc_html($reviews_title); ?></h2>
                        <?php endif; ?>
                        <?php if ($reviews_subtitle): ?>
                            <p class="reviews__subtitle"><?php echo esc_html($reviews_subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="reviews__controls reviews__controls--text" style="<?php echo esc_attr($text_style); ?>">
                        <div class="reviews__pagination swiper-pagination"></div>
                        <div class="reviews__controls-btns">
                            <button type="button" class="reviews__prev swiper-button-prev"></button>
                            <button type="button" class="reviews__next swiper-button-next"></button>
                        </div>
                    </div>
                    <div class="reviews__controls reviews__controls--screenshots" style="<?php echo esc_attr($screenshots_style); ?>">
                        <div class="reviews__pagination swiper-pagination"></div>
                        <div class="reviews__controls-btns">
                            <button type="button" class="reviews__prev swiper-button-prev"></button>
                            <button type="button" class="reviews__next swiper-button-next"></button>
                        </div>
                    </div>
                </div>
                <?php if ($reviews_text_items || $reviews_screenshots): ?>
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
            </div>


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
                                                    $raw_date = $review['date'];

                                                    $date_string_for_strtotime = str_replace('/', '-', $raw_date);

                                                    $timestamp = strtotime($date_string_for_strtotime);

                                                    if ($timestamp) {
                                                        $date_format = 'j F Y г.';
                                                        $formatted_date = date_i18n($date_format, $timestamp);

                                                        $iso_date = date('Y-m-d', $timestamp);

                                                    ?>
                                                        <time datetime="<?php echo esc_attr($iso_date); ?>" class="review-card__time"><?php echo esc_html($formatted_date); ?></time>
                                                    <?php
                                                    } else {
                                                    ?>
                                                        <div class="review-card__time"><?php echo esc_html($raw_date); ?></div>
                                                    <?php
                                                    }
                                                    ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($review['text']): ?>
                                                <blockquote class="review-card__quote"><?php echo $review['text']; ?></blockquote>
                                            <?php endif; ?>
                                            <div class="review-card__footer">
                                                <?php
                                                $initials = '';
                                                if (!$person_thumb && $person_name) {
                                                    $initials = mb_substr(trim($person_name), 0, 1);
                                                }
                                                ?>

                                                <?php if ($person_thumb || $initials): ?>
                                                    <div class="review-card__thumb">
                                                        <?php if ($person_thumb): ?>
                                                            <img src="<?php echo esc_url($person_thumb['sizes']['thumbnail']); ?>" alt="<?php echo esc_attr($person_thumb['alt']); ?>" class="cover-image">
                                                        <?php else: ?>
                                                            <span class="review-card__initials"><?php echo esc_html($initials); ?></span>
                                                        <?php endif; ?>
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