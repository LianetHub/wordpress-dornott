</main>
<?php
$option_page = 'option';

$white_logo = get_field('white_logo', $option_page);
$copy_text = get_field('copy', $option_page);
$requisites = get_field('requisites', $option_page);

// Контакты
$phone = get_field('tel', $option_page);
$email = get_field('email', $option_page);

// Соцсети
$social_telegram = get_field('telegram', $option_page);
$social_whatsapp = get_field('whatsapp', $option_page);
$social_youtube = get_field('youtube', $option_page);
$social_rutube = get_field('rutube', $option_page);
$social_vk = get_field('vk', $option_page);

// Политики
$privacy_policy =  get_field('privacy_policy', $option_page);
$data_protection =  get_field('data_protection_policy', $option_page);
$payment_delivery =  get_field('payment_and_delivery_policy', $option_page);

// Кнопка
$footer_button_full = get_field('footer_btn', $option_page);
$footer_button_data = $footer_button_full['btn'] ?? null;
?>

<footer class="footer">
    <div class="footer__top">
        <div class="container">
            <div class="footer__side">
                <?php if ($white_logo): ?>
                    <a href="#hero" class="footer__logo">
                        <img src="<?php echo esc_url($white_logo['url']); ?>" alt="<?php echo esc_attr($white_logo['alt']) ?: 'Логотип «DORNOTT»'; ?>">
                    </a>
                <?php endif; ?>

                <?php if ($requisites): ?>
                    <div class="footer__reqs">
                        <?php echo $requisites; ?>
                    </div>
                <?php endif; ?>
            </div>

            <nav aria-label="Меню" class="menu">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'general_menu',
                    'container'      => false,
                    'menu_class'     => 'menu__list',
                    'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    'walker'         => new Dornott_Menu_Walker()
                ));
                ?>
                <?php if ($privacy_policy || $data_protection || $payment_delivery): ?>
                    <ul class="footer__policies">
                        <?php if ($privacy_policy): ?>
                            <li class="footer__policy">
                                <a href="#privacy-policy" data-fancybox class="footer__policy-link">Политика конфиденциальности</a>
                            </li>
                        <?php endif; ?>


                        <?php if ($data_protection): ?>
                            <li class="footer__policy">
                                <a href="#data-protection" data-fancybox class="footer__policy-link">Защита данных</a>
                            </li>
                        <?php endif; ?>


                        <?php if ($payment_delivery): ?>
                            <li class="footer__policy">
                                <a href="#payment-and-delivery" data-fancybox class="footer__policy-link">Оплата и доставка</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </nav>

            <?php

            if ($footer_button_data) {
                get_template_part('templates/components/button', null, [
                    'data'  => $footer_button_data,
                    'class' => 'footer__callback',
                    'type'  => 'primary',
                    'icon'  => 'phone-incoming'
                ]);
            }
            ?>
        </div>
    </div>

    <div class="footer__bottom">
        <div class="container">
            <?php if ($copy_text): ?>
                <div class="footer__copy">
                    © 2020-<?php echo currentYear(); ?> <?php echo $copy_text; ?>
                </div>
            <?php endif; ?>

            <div class="footer__socials socials">
                <?php if ($social_telegram): ?>
                    <a href="<?php echo esc_url($social_telegram); ?>" aria-label="Следите за нами в Telegram" class="socials__link icon-telegram"></a>
                <?php endif; ?>
                <?php if ($social_whatsapp): ?>
                    <a href="<?php echo esc_url($social_whatsapp); ?>" aria-label="Следите за нами в WhatsApp" class="socials__link icon-whatsapp"></a>
                <?php endif; ?>
                <?php if ($social_youtube): ?>
                    <a href="<?php echo esc_url($social_youtube); ?>" aria-label="Следите за нами в Youtube" class="socials__link icon-youtube"></a>
                <?php endif; ?>
                <?php if ($social_rutube): ?>
                    <a href="<?php echo esc_url($social_rutube); ?>" aria-label="Следите за нами в Rutube" class="socials__link icon-rutube"></a>
                <?php endif; ?>
                <?php if ($social_vk): ?>
                    <a href="<?php echo esc_url($social_vk); ?>" aria-label="Следите за нами в VK" class="socials__link icon-vk"></a>
                <?php endif; ?>
            </div>

            <div class="footer__prod">
                <a href="" class="footer__prod-link">
                    <img src="@img/gektor.svg" alt="Студия-разработчик">
                </a>
            </div>
        </div>
    </div>
</footer>
</div>
<?php require_once(TEMPLATE_PATH . '_modals.php'); ?>
<?php wp_footer(); ?>
</body>

</html>