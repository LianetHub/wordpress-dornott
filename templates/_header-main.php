<?php
$option_page = 'option';

$logo = get_field('logo', $option_page);

?>

<div class="header__top">
    <?php if ($logo): ?>
        <a href="#" class="header__logo">
            <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($logo['alt']) ?: 'Логотип «DORNOTT»'; ?>">
        </a>
    <?php endif; ?>
    <nav aria-label="Меню" class="header__menu menu">
        <?php
        wp_nav_menu(array(
            'theme_location' => 'general_menu',
            'container'      => false,
            'menu_class'     => 'menu__list',
            'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            'walker'         => new Dornott_Menu_Walker()
        ));
        ?>
    </nav>
    <div class="header__actions">
        <a href="#callback" data-fancybox aria-label="Обратная связь" class="header__action icon-phone-incoming"></a>
        <a href="#cart" data-fancybox aria-label="Корзина" class="header__action icon-cart"></a>
        <button type="button" aria-label="Открыть меню" class="header__menu-toggler icon-menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</div>