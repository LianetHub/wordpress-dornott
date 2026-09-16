<?php
$option_page = 'option';
$logo = get_field('logo', $option_page);

?>

<div class="header__content">
    <?php if ($logo): ?>
        <a href="<?php echo esc_url(home_url('/#')); ?>" class="header__logo">
            <?php echo function_exists('dornott_acf_image')
				? dornott_acf_image($logo, 'full', ['loading' => 'eager', 'fetchpriority' => 'low', 'alt' => $logo['alt'] ?: 'Логотип «DORNOTT»'])
				: ''; ?>
        </a>
    <?php endif; ?>
    <div class="header__wrapper">
        <nav aria-label="Меню" class="header__menu menu">
            <div class="menu__logo">
                <?php if ($logo): ?>
                    <?php echo function_exists('dornott_acf_image')
						? dornott_acf_image($logo, 'full', ['loading' => 'lazy', 'alt' => $logo['alt'] ?: 'Логотип «DORNOTT»'])
						: ''; ?>
                <?php endif; ?>
            </div>
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
            <a href="#cart" data-fancybox aria-label="Корзина" data-cart-toggler class="header__action icon-cart"></a>
            <button type="button" aria-label="Открыть меню" class="header__menu-toggler icon-menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</div>