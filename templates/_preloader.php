<div class="preloader">
    <noscript>
        <style>
            .preloader {
                display: none !important;
            }

            body.preloading {
                overflow: visible !important;
            }
        </style>
    </noscript>
    <div class="preloader__inner">
        <div class="preloader__logo">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/logo-white.svg" alt="Лого">
        </div>
        <span class="preloader__percentage">
            <span id="percentage">0</span>%
        </span>
        <span class="preloader__text">Пожалуйста, подождите, идёт&nbsp;загрузка...</span>
    </div>
</div>