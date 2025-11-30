<?php
$phone = get_field('phone', 'option');
$email = get_field('email', 'option');
?>

<div class="header__top">
    <?php if (has_custom_logo()) { ?>
        <?php the_custom_logo(); ?>
    <?php } ?>

    <?php if ($phone || $email): ?>
        <div class="header__contacts">
            <?php if ($phone): ?>
                <a href="tel:<?php echo preg_replace('/\D+/', '', $phone); ?>" class="header__phone">
                    <?php echo esc_html($phone); ?>
                </a>
            <?php endif; ?>

            <?php if ($email): ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" class="header__email">
                    <?php echo esc_html($email); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <button type="button" aria-label="Открыть меню" class="header__menu-toggler icon-menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
</div>
<nav aria-label="Меню" class="header__menu menu">
    <?php
    wp_nav_menu([
        'theme_location' => 'main_menu',
        'container'      => false,
        'menu_class'     => 'menu__list',
    ]);
    ?>
</nav>
