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

if (get_field('show_contacts', $context_id)):
    $contacts_title = get_field('contacts_title', $context_id);
    $contacts_form_group = get_field('contacts_form', $context_id);

    $form_caption = $contacts_form_group['title'];
    $form_text = $contacts_form_group['subtitle'];

    $address = get_field('address', 'option') ?? '';
    $hours = get_field('worktime', 'option') ?? '';
    $phone = get_field('tel', 'option') ?? '';
    $email = get_field('email', 'option') ?? '';
    $map_coords = get_field('coords', 'option') ?? '';
    $telegram = get_field('telegram', 'option') ?? '';
    $whatsapp = get_field('whatsapp', 'option') ?? '';
    $max = get_field('max', 'option') ?? '';
    $privacy_txt = get_field('privacy_txt', 'option') ?? '';
    $placemarker_logo_url = get_field('placemarker_logo', 'option') ?? '';
?>
    <section id="contacts" class="contacts">
        <div class="container">
            <?php if ($contacts_title): ?>
                <h2 class="contacts__title title"><?php echo esc_html($contacts_title); ?></h2>
            <?php endif; ?>

            <div class="contacts__body">
                <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="POST" class="contacts__form form">
                    <input type="hidden" name="action" value="send_contact_form">
                    <div class="contacts__form-header">
                        <?php if ($form_caption): ?>
                            <div class="contacts__form-caption title-sm"><?php echo esc_html($form_caption); ?></div>
                        <?php endif; ?>
                        <?php if ($form_text): ?>
                            <div class="contacts__form-text"><?php echo esc_html($form_text); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="contacts__form-fields">
                        <label class="contacts__form-field form__field">
                            <input type="text" name="username" data-required class="form__control" placeholder="Ваше имя">
                        </label>
                        <label class="contacts__form-field form__field">
                            <input type="tel" name="phone" data-required class="form__control" placeholder="Телефон">
                        </label>
                        <label class="contacts__form-field form__field">
                            <textarea name="message" class="form__control" placeholder="Ваш вопрос или предложение..."></textarea>
                        </label>
                        <div class="form__file">
                            <label class="form__file-field">
                                <input type="file" name="file" class="form__file-input" hidden>
                                <span class="form__file-btn icon-clip">Прикрепить (до 10 мб.)</span>
                            </label>
                        </div>
                    </div>
                    <?php dornott_smartcaptcha_html(); ?>
                    <div class="contacts__form-footer">
                        <button type="submit" class="form__btn btn btn-primary btn-sm">Отправить</button>
                        <div class="form__policy">
                            <?php echo wp_kses_post($privacy_txt) ?>
                        </div>
                    </div>
                </form>

                <div class="contacts__map">
                    <?php if ($map_coords): ?>
                        <div class="contacts__map-header">
                            <div id="map"
                                class="contacts__map-block"
                                data-coords="<?php echo esc_attr($map_coords); ?>"
                                data-placemark-logo="<?php echo esc_attr($placemarker_logo_url); ?>"></div>
                        </div>
                    <?php endif; ?>
                    <ul class="contacts__list">
                        <?php if ($address): ?>
                            <li class="contacts__list-item">
                                <div class="contacts__caption">Адрес</div>
                                <address class="contacts__text"><?php echo esc_html($address); ?></address>
                            </li>
                        <?php endif; ?>

                        <?php if ($hours): ?>
                            <li class="contacts__list-item">
                                <div class="contacts__caption">Режим работы</div>
                                <div class="contacts__text"><?php echo esc_html($hours); ?></div>
                            </li>
                        <?php endif; ?>

                        <?php if ($phone || $telegram || $whatsapp || $max): ?>
                            <li class="contacts__list-item">
                                <div class="contacts__caption">Телефон</div>
                                <?php if ($phone): ?>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^\d\+]/', '', $phone)); ?>" class="contacts__link"><?php echo esc_html($phone); ?></a>
                                <?php endif; ?>

                                <?php if ($telegram || $whatsapp || $max): ?>
                                    <div class="contacts__socials socials">
                                        <?php if ($telegram): ?>
                                            <a href="<?php echo esc_url($telegram); ?>" aria-label="Следите за нами в Telegram" class="socials__link icon-telegram"></a>
                                        <?php endif; ?>
                                        <?php if ($whatsapp): ?>
                                            <a href="<?php echo esc_url($whatsapp); ?>" aria-label="Следите за нами в WhatsApp" class="socials__link icon-whatsapp"></a>
                                        <?php endif; ?>
                                        <?php if ($max): ?>
                                            <a href="<?php echo esc_url($max); ?>" aria-label="Следите за нами в Max" class="socials__link icon-max"></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>

                        <?php if ($email): ?>
                            <li class="contacts__list-item">
                                <div class="contacts__caption">Email</div>
                                <a href="mailto:<?php echo esc_attr($email); ?>" class="contacts__link"><?php echo esc_html($email); ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>