<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- favicon -->
    <link rel="icon" type="image/png" href="<?php echo get_template_directory_uri(); ?>/assets/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="<?php echo get_template_directory_uri(); ?>/assets/favicon.svg">
    <link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri(); ?>/assets/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-title" content="Dornott">
    <link rel="manifest" href="<?php echo get_template_directory_uri(); ?>/assets/site.webmanifest">
    <!-- favicon -->

    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m, e, t, r, i, k, a) {
            m[i] = m[i] || function() {
                (m[i].a = m[i].a || []).push(arguments)
            };
            m[i].l = 1 * new Date();
            for (var j = 0; j < document.scripts.length; j++) {
                if (document.scripts[j].src === r) {
                    return;
                }
            }
            k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
        })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=105964434', 'ym');
        ym(105964434, 'init', {
            ssr: true,
            webvisor: true,
            clickmap: true,
            ecommerce: "dataLayer",
            accurateTrackBounce: true,
            trackLinks: true
        });
    </script>
    <noscript>
        <div><img src="https://mc.yandex.ru/watch/105964434" style="position:absolute; left:-9999px;" alt="" /></div>
    </noscript>
    <!-- /Yandex.Metrika counter -->
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php require_once(TEMPLATE_PATH . '_preloader.php'); ?>
    <div class="wrapper">
        <header class="header">
            <div class="header__body">
                <div class="container container--fluid">
                    <?php require_once(TEMPLATE_PATH . '_header-main.php'); ?>
                </div>
            </div>
        </header>
        <main class="main">