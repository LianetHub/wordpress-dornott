<?php
if (class_exists('WPSEO_Breadcrumbs')) :
    $breadcrumbs = WPSEO_Breadcrumbs::get_instance()->get_links();
    if ($breadcrumbs) : ?>
        <nav aria-label="Хлебные крошки" class="breadcrumbs">
            <ul class="breadcrumbs__list">
                <?php foreach ($breadcrumbs as $i => $breadcrumb) : ?>
                    <li class="breadcrumbs__item">
                        <?php if ($i < count($breadcrumbs) - 1) : ?>
                            <a href="<?php echo esc_url($breadcrumb['url']); ?>"
                                class="breadcrumbs__link <?php echo ($i === 0) ? 'icon-home' : ''; ?>"
                                <?php echo ($i === 0) ? 'aria-label="Главная"' : ''; ?>>
                                <?php echo ($i === 0) ? '' : esc_html($breadcrumb['text']); ?>
                            </a>
                        <?php else : ?>
                            <span class="breadcrumbs__current" aria-current="page">
                                <?php echo esc_html($breadcrumb['text']); ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
<?php endif;
endif; ?>