"use strict";



// preloader
if ($('.preloader').length > 0) {
    let counting = setInterval(function () {
        let loader = $('#percentage');
        let currval = parseInt(loader.text());

        if (currval < 90) {
            loader.text(++currval);
        } else if (currval < 95 && document.readyState === "interactive") {
            loader.text(95);
        } else if (currval < 99 && document.readyState === "complete") {
            loader.text(99);
        }

        if (currval >= 99 && document.readyState === "complete") {
            clearInterval(counting);
            loader.text(100);
            setTimeout(function () {
                $('body').removeClass('preloading').addClass('is-loaded');
            }, 300);
        }
    }, 20);
}

$(function () {


    //  init Fancybox
    if (typeof Fancybox !== "undefined" && Fancybox !== null) {
        Fancybox.bind("[data-fancybox]", {
            dragToClose: false
        });
    }

    // detect user OS
    const isMobile = {
        Android: () => /Android/i.test(navigator.userAgent),
        BlackBerry: () => /BlackBerry/i.test(navigator.userAgent),
        iOS: () => /iPhone|iPad|iPod/i.test(navigator.userAgent),
        Opera: () => /Opera Mini/i.test(navigator.userAgent),
        Windows: () => /IEMobile/i.test(navigator.userAgent),
        any: function () {
            return this.Android() || this.BlackBerry() || this.iOS() || this.Opera() || this.Windows();
        },
    };

    function getNavigator() {
        if (isMobile.any() || $(window).width() < 992) {
            $('body').removeClass('_pc').addClass('_touch');
        } else {
            $('body').removeClass('_touch').addClass('_pc');
        }
    }

    getNavigator();

    setupStepsHandlers();

    $(window).on('resize', () => {
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(() => {
            getNavigator();
            setupStepsHandlers();
        }, 100);
    });

    // steps animation

    function setupStepsHandlers() {
        const $stepsItems = $('.steps__item');

        $stepsItems.off('click mouseenter mouseleave');

        if ($('body').hasClass('_pc')) {
            $stepsItems.on('mouseenter', function () {
                $(this).addClass('active').siblings().removeClass('active');
            });

        } else {
            $stepsItems.on('click', function (e) {
                $(this).addClass('active').siblings().removeClass('active');
            });
        }
    }


    // event handlers
    $(document).on('click', (e) => {
        const $target = $(e.target);


        // menu 
        if ($target.closest('.header__menu-toggler').length) {
            $('.header__menu-toggler').toggleClass('active');
            $('.header').toggleClass('open-mobile-menu');
            $('body').toggleClass('lock-mobile-menu');
        }

        if (!$target.closest('.header').length && $('.header').hasClass('open-mobile-menu')) {
            $('.header__menu-toggler').removeClass('active');
            $('.header').removeClass('open-mobile-menu');
            $('body').removeClass('lock-mobile-menu');
        }

        // toggle active state favorite
        if ($target.closest('.favorite-btn').length) {
            $target.closest('.favorite-btn').toggleClass('active')
        }

        // add to cart
        if ($target.closest('.add-to-cart-button').length) {
            $target.closest('.add-to-cart-button').toggleClass('active');
        }


    });

    // phone input mask

    var phoneInputs = document.querySelectorAll('input[type="tel"]');

    var getInputNumbersValue = function (input) {
        // Return stripped input value — just numbers
        return input.value.replace(/\D/g, '');
    }

    var onPhonePaste = function (e) {
        var input = e.target,
            inputNumbersValue = getInputNumbersValue(input);
        var pasted = e.clipboardData || window.clipboardData;
        if (pasted) {
            var pastedText = pasted.getData('Text');
            if (/\D/g.test(pastedText)) {
                // Attempt to paste non-numeric symbol — remove all non-numeric symbols,
                // formatting will be in onPhoneInput handler
                input.value = inputNumbersValue;
                return;
            }
        }
    }

    var onPhoneInput = function (e) {
        var input = e.target,
            inputNumbersValue = getInputNumbersValue(input),
            selectionStart = input.selectionStart,
            formattedInputValue = "";

        if (!inputNumbersValue) {
            return input.value = "";
        }

        if (input.value.length != selectionStart) {
            // Editing in the middle of input, not last symbol
            if (e.data && /\D/g.test(e.data)) {
                // Attempt to input non-numeric symbol
                input.value = inputNumbersValue;
            }
            return;
        }

        if (["7", "8", "9"].indexOf(inputNumbersValue[0]) > -1) {
            if (inputNumbersValue[0] == "9") inputNumbersValue = "7" + inputNumbersValue;
            var firstSymbols = (inputNumbersValue[0] == "8") ? "8" : "+7";
            formattedInputValue = input.value = firstSymbols + " ";
            if (inputNumbersValue.length > 1) {
                formattedInputValue += '(' + inputNumbersValue.substring(1, 4);
            }
            if (inputNumbersValue.length >= 5) {
                formattedInputValue += ') ' + inputNumbersValue.substring(4, 7);
            }
            if (inputNumbersValue.length >= 8) {
                formattedInputValue += '-' + inputNumbersValue.substring(7, 9);
            }
            if (inputNumbersValue.length >= 10) {
                formattedInputValue += '-' + inputNumbersValue.substring(9, 11);
            }
        } else {
            formattedInputValue = '+' + inputNumbersValue.substring(0, 16);
        }
        input.value = formattedInputValue;
    }

    var onPhoneKeyDown = function (e) {
        // Clear input after remove last symbol
        var inputValue = e.target.value.replace(/\D/g, '');
        if (e.keyCode == 8 && inputValue.length == 1) {
            e.target.value = "";
        }
    }

    for (var phoneInput of phoneInputs) {
        phoneInput.addEventListener('keydown', onPhoneKeyDown);
        phoneInput.addEventListener('input', onPhoneInput, false);
        phoneInput.addEventListener('paste', onPhonePaste, false);
    }


    // sliders
    if ($('.hero').length) {


        const heroImages = $('.hero__images');
        const heroOffer = $('.hero__offer-slider');

        if (heroImages.length && heroOffer.length) {

            const heroImagesSlider = new Swiper(heroImages.get(0), {
                loop: true,
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                },
                speed: 600,
                allowTouchMove: false,

                navigation: {
                    nextEl: '.hero__next',
                    prevEl: '.hero__prev',
                },

                pagination: {
                    el: '.hero__pagination',
                    type: 'fraction',
                    formatFractionCurrent: (number) => {
                        return number < 10 ? '0' + number : number;
                    },
                    formatFractionTotal: (number) => {
                        return number < 10 ? '0' + number : number;
                    },
                    renderFraction: (currentClass, totalClass) => {
                        return '<span class="' + currentClass + '"></span>' +
                            '/' +
                            '<span class="' + totalClass + '"></span>';
                    }
                },
            });

            const heroOfferSlider = new Swiper(heroOffer.get(0), {
                loop: true,
                speed: 600,
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                },
                controller: {
                    control: heroImagesSlider
                },
            });


            heroImagesSlider.controller.control = heroOfferSlider;
        }
    }

    if ($('.product-card').length) {
        $('.product-card').each(function (index, element) {
            const $slider = $(element).find('.product-card__slider');
            if (!$slider.length) return;

            const pagination = $(element).find('.product-card__pagination')[0];

            const swiper = new Swiper($slider[0], {
                slidesPerView: 1,
                speed: 0,
                lazy: true,
                watchOverflow: true,
                pagination: {
                    el: pagination,
                    clickable: true
                }
            });

            const slidesCount = swiper.slides.length;

            if (slidesCount > 1) {
                const $areasWrapper = $('<div class="product-card__hover-areas"></div>');
                $areasWrapper.css({
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    display: 'flex',
                    zIndex: 10
                });

                for (let i = 0; i < slidesCount; i++) {
                    const $area = $('<div class="product-card__hover-area"></div>');
                    $area.css({
                        flex: '1 1 0',
                    });

                    $area.on('mouseenter', () => {
                        swiper.slideTo(i);
                    });

                    $areasWrapper.append($area);
                }

                $slider.css('position', 'relative').append($areasWrapper);
            }
        });
    }

    // product variation change price

    $(document).on('change', '.product-card .product-card__variations-input', function () {
        var $card = $(this).closest('.product-card');
        var selectedVariationId = $(this).val();
        var newPriceHtml = $(this).data('price-html');
        var newRegularPriceHtml = $(this).data('regular-price-html');
        var isInStock = !$(this).is(':disabled');

        var $currentPriceElement = $card.find('[data-price-role="current-price"]');
        var $regularPriceElement = $card.find('[data-price-role="regular-price"]');
        var $addToCartButton = $card.find('.ajax_add_to_cart');

        $currentPriceElement.html(newPriceHtml);
        $regularPriceElement.html(newRegularPriceHtml);

        $addToCartButton.data('variation-id', selectedVariationId);

        if (isInStock) {
            $addToCartButton.removeAttr('disabled');
        } else {
            $addToCartButton.attr('disabled', 'disabled');
        }
    });

    $('.product-card').each(function () {
        var $card = $(this);
        var $firstRadio = $card.find('.product-card__variations-input:checked');

        if ($firstRadio.length) {
            $firstRadio.trigger('change');
        }
    });



    // header observer
    const headerElement = $('.header');

    const callback = function (entries, observer) {
        if (entries[0].isIntersecting) {
            headerElement.removeClass('scroll');
        } else {
            headerElement.addClass('scroll');
        }
    };

    const headerObserver = new IntersectionObserver(callback);
    headerObserver.observe(headerElement[0]);


    // WPCF7 Redirect
    // document.addEventListener('wpcf7mailsent', function (event) {
    //     window.location.href = '/vy-zakazali-zvonok/';
    // }, false);



})


