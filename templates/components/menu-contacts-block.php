<?php
$option_page = 'option';

$phone = get_field('tel', $option_page);
$email = get_field('email', $option_page);
$social_telegram = get_field('telegram', $option_page);
$social_whatsapp = get_field('whatsapp', $option_page);

$has_main_contacts = $phone || $email;
$has_messengers = $social_telegram || $social_whatsapp;
$has_contacts = $has_main_contacts || $has_messengers;

$privacy_policy = get_field('privacy_policy', $option_page);
$data_protection = get_field('data_protection_policy', $option_page);
$payment_delivery = get_field('payment_and_delivery_policy', $option_page);

$has_policy_links = $privacy_policy || $data_protection || $payment_delivery;
?>

<li class="menu__item menu__item--parent">
    <button type="button" class="menu__btn icon-chevron-down">Контакты</button>
    <div class="submenu">
        <ul class="submenu__list">

            <?php if ($has_contacts): ?>
                <li class="submenu__contacts">

                    <?php if ($has_main_contacts): ?>
                        <div class="submenu__contacts-block">
                            <?php if ($phone): ?>
                                <a href="tel:<?php echo esc_attr(preg_replace('/[^\d\+]/', '', $phone)); ?>" class="submenu__contacts-phone"><?php echo esc_html($phone); ?></a>
                            <?php endif; ?>
                            <?php if ($email): ?>
                                <a href="mailto:<?php echo esc_attr($email); ?>" class="submenu__contacts-mail"><?php echo esc_html($email); ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($has_messengers): ?>
                        <div class="submenu__contacts-block">
                            <div class="submenu__contacts-caption">Мессенджеры:</div>
                            <div class="submenu__contacts-socials socials">
                                <?php if ($social_telegram): ?>
                                    <a href="<?php echo esc_url($social_telegram); ?>" aria-label="Следите за нами в Telegram" class="socials__link icon-telegram"></a>
                                <?php endif; ?>
                                <?php if ($social_whatsapp): ?>
                                    <a href="<?php echo esc_url($social_whatsapp); ?>" aria-label="Следите за нами в WhatsApp" class="socials__link icon-whatsapp"></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </li>
            <?php endif; ?>

            <?php if ($has_policy_links): ?>

                <?php if ($privacy_policy): ?>
                    <li class="submenu__item">
                        <a href="#privacy-policy" data-src="#policies" data-fancybox class="submenu__link icon-chevron-right">Политика конфиденциальности</a>
                    </li>
                <?php endif; ?>

                <?php if ($data_protection): ?>
                    <li class="submenu__item">
                        <a href="#data-protection" data-src="#policies" data-fancybox class="submenu__link icon-chevron-right">Защита данных</a>
                    </li>
                <?php endif; ?>

                <?php if ($payment_delivery): ?>
                    <li class="submenu__item">
                        <a href="#payment-and-delivery" data-src="#policies" data-fancybox class="submenu__link icon-chevron-right">Оплата и доставка</a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

        </ul>
    </div>
</li>