"use strict";

// preloader
function releasePreloader() {
	$("body").removeClass("preloading").addClass("is-loaded");
}

if ($("body").hasClass("preloading") && $(".preloader").length > 0) {
	let counting = setInterval(function () {
		let loader = $("#percentage");
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
			setTimeout(releasePreloader, 300);
		}
	}, 20);
} else {
	releasePreloader();
}

$(function () {
	// Smooth Scroll Anchors
	$('a[href^="#"]')
		.not("[data-fancybox]")
		.on("click", function (event) {
			const href = this.getAttribute("href");

			if (href === "#") return;

			const target = $(href);
			const header = $(".header");

			if (target.length) {
				event.preventDefault();

				const headerHeight = header.outerHeight() || 0;
				const targetPosition = target.offset().top - headerHeight;

				$("html, body").stop().animate(
					{
						scrollTop: targetPosition,
					},
					800,
				);
			}
		});

	//  init Fancybox
	if (typeof Fancybox !== "undefined" && Fancybox !== null) {
		Fancybox.bind("[data-fancybox]", {
			dragToClose: false,
			on: {
				ready: (fancyboxRef) => {
					const slide = fancyboxRef.getSlide();
					if (slide.src === "#policies") {
						const $container = $(slide.el);
						const trigger = slide.triggerEl;

						if (trigger) {
							const targetSlug = trigger.getAttribute("href").replace("#", "");
							const $targetRadio = $container.find(`input[name="policy-type"][value="${targetSlug}"]`);
							if ($targetRadio.length) {
								$targetRadio.prop("checked", true).trigger("change");
							} else {
								$container.find('input[name="policy-type"]:checked').trigger("change");
							}
							setTimeout(() => {
								if ($targetRadio.length) {
									$targetRadio.prop("checked", true).trigger("change");
								} else {
									$container.find('input[name="policy-type"]:checked').trigger("change");
								}
							}, 300);
						}
					}
				},
				close: (fancyboxRef, event) => {
					const targetElement = event.target;

					if (targetElement && targetElement.hasAttribute("data-goto-catalog")) {
						const target = $("#catalog");
						const header = $(".header");

						if (target.length) {
							const headerHeight = header.outerHeight() || 0;
							const targetPosition = target.offset().top - headerHeight;

							$("html, body").stop().animate(
								{
									scrollTop: targetPosition,
								},
								800,
							);
						}
					}
				},
				done: () => {
					setTimeout(() => {
						window.formController?.initCaptchas();
					}, 100);
				},
				destroy: (fancyboxRef) => {
					if (fancyboxRef.getSlide().src === "#cart") {
						window?.dornottCart.resetInterface();
					}
				},
			},
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
			$("body").removeClass("_pc").addClass("_touch");
		} else {
			$("body").removeClass("_touch").addClass("_pc");
		}
	}

	getNavigator();
	setupStepsHandlers();

	$(window).on("resize", () => {
		clearTimeout(window.resizeTimer);
		window.resizeTimer = setTimeout(() => {
			getNavigator();
			setupStepsHandlers();
		}, 100);
	});

	// steps animation

	function setupStepsHandlers() {
		const $stepsItems = $(".steps__item");

		$stepsItems.off("click mouseenter mouseleave");

		if ($("body").hasClass("_pc")) {
			$stepsItems.on("mouseenter", function () {
				$(this).addClass("active").siblings().removeClass("active");
			});
		} else {
			$stepsItems.on("click", function (e) {
				$(this).addClass("active").siblings().removeClass("active");
			});
		}
	}

	// event handlers
	$(document).on("click", (e) => {
		const $target = $(e.target);

		// Handle submenu logic
		if ($target.closest(".menu__arrow").length) {
			const $parentItem = $target.closest(".menu__arrow").parent();

			if ($parentItem.hasClass("active")) {
				$parentItem.removeClass("active");
			} else {
				$(".menu__item.active").removeClass("active");
				$parentItem.addClass("active");
			}
		}

		// Close all submenus when clicking outside the menu
		if (!$target.closest(".menu__arrow").length && !$target.closest(".menu").length) {
			$(".menu__item.active").removeClass("active");
		}

		// Close all submenus when clicking outside the menu
		if (!$target.closest(".menu").length && !$target.closest(".icon-menu").length) {
			$(".menu__item.active").removeClass("active");
		}

		// Open/close the mobile menu
		if ($target.closest(".icon-menu").length) {
			$(".icon-menu").toggleClass("active");
			$(".menu").toggleClass("menu--open");
			$("body").toggleClass("menu-lock");
		}

		// Correctly close the mobile menu on outside click
		if ($(".menu").hasClass("menu--open") && !$target.closest(".menu").length && !$target.closest(".icon-menu").length) {
			$(".icon-menu").removeClass("active");
			$(".menu").removeClass("menu--open");
			$("body").removeClass("menu-lock");
		}

		if ($(".menu").hasClass("menu--open") && $target.closest(".menu__link").length) {
			$(".icon-menu").removeClass("active");
			$(".menu").removeClass("menu--open");
			$("body").removeClass("menu-lock");
		}

		// toggle active state favorite
		if ($target.closest(".favorite-btn").length) {
			$target.closest(".favorite-btn").toggleClass("active");
		}
	});

	// phone input mask

	var phoneInputs = document.querySelectorAll('input[type="tel"]');

	var getInputNumbersValue = function (input) {
		// Return stripped input value — just numbers
		return input.value.replace(/\D/g, "");
	};

	var onPhonePaste = function (e) {
		var input = e.target,
			inputNumbersValue = getInputNumbersValue(input);
		var pasted = e.clipboardData || window.clipboardData;
		if (pasted) {
			var pastedText = pasted.getData("Text");
			if (/\D/g.test(pastedText)) {
				// Attempt to paste non-numeric symbol — remove all non-numeric symbols,
				// formatting will be in onPhoneInput handler
				input.value = inputNumbersValue;
				return;
			}
		}
	};

	var onPhoneInput = function (e) {
		var input = e.target,
			inputNumbersValue = getInputNumbersValue(input),
			selectionStart = input.selectionStart,
			formattedInputValue = "";

		if (!inputNumbersValue) {
			return (input.value = "");
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
			var firstSymbols = inputNumbersValue[0] == "8" ? "8" : "+7";
			formattedInputValue = input.value = firstSymbols + " ";
			if (inputNumbersValue.length > 1) {
				formattedInputValue += "(" + inputNumbersValue.substring(1, 4);
			}
			if (inputNumbersValue.length >= 5) {
				formattedInputValue += ") " + inputNumbersValue.substring(4, 7);
			}
			if (inputNumbersValue.length >= 8) {
				formattedInputValue += "-" + inputNumbersValue.substring(7, 9);
			}
			if (inputNumbersValue.length >= 10) {
				formattedInputValue += "-" + inputNumbersValue.substring(9, 11);
			}
		} else {
			formattedInputValue = "+" + inputNumbersValue.substring(0, 16);
		}
		input.value = formattedInputValue;
	};

	var onPhoneKeyDown = function (e) {
		// Clear input after remove last symbol
		var inputValue = e.target.value.replace(/\D/g, "");
		if (e.keyCode == 8 && inputValue.length == 1) {
			e.target.value = "";
		}
	};

	for (var phoneInput of phoneInputs) {
		phoneInput.addEventListener("keydown", onPhoneKeyDown);
		phoneInput.addEventListener("input", onPhoneInput, false);
		phoneInput.addEventListener("paste", onPhonePaste, false);
	}

	// sliders
	const swiperPaginationConfig = {
		type: "fraction",
		formatFractionCurrent: (number) => {
			return number < 10 ? "0" + number : number;
		},
		formatFractionTotal: (number) => {
			return number < 10 ? "0" + number : number;
		},
		renderFraction: (currentClass, totalClass) => {
			return '<span class="' + currentClass + '"></span>' + "/" + '<span class="' + totalClass + '"></span>';
		},
	};

	if ($(".hero").length) {
		const heroImages = $(".hero__images");
		const heroOffer = $(".hero__offer-slider");

		if (heroImages.length && heroOffer.length) {
			const heroImagesSlider = new Swiper(heroImages.get(0), {
				loop: true,
				effect: "fade",
				fadeEffect: {
					crossFade: true,
				},
				speed: 600,
				allowTouchMove: false,

				navigation: {
					nextEl: ".hero__next",
					prevEl: ".hero__prev",
				},

				pagination: {
					el: ".hero__pagination",
					...swiperPaginationConfig,
				},
			});

			const heroOfferSlider = new Swiper(heroOffer.get(0), {
				loop: true,
				autoHeight: true,
				speed: 600,
				effect: "fade",
				fadeEffect: {
					crossFade: true,
				},
				controller: {
					control: heroImagesSlider,
				},
				breakpoints: {
					991.98: {
						autoHeight: false,
					},
				},
			});

			heroImagesSlider.controller.control = heroOfferSlider;
		}
	}

	const hasCursor = window.matchMedia("(hover: hover)").matches;

	const initProductGallery = ($root, sliderSelector, paginationSelector) => {
		const $slider = $root.find(sliderSelector);
		if (!$slider.length) return;

		const pagination = $root.find(paginationSelector)[0];

		const swiper = new Swiper($slider[0], {
			slidesPerView: 1,
			speed: 300,
			lazy: true,
			loop: true,
			watchOverflow: true,
			pagination: {
				el: pagination,
				clickable: true,
			},
			navigation: {
				nextEl: $slider.find(".product-card__next")[0],
				prevEl: $slider.find(".product-card__prev")[0],
			},
		});

		const realSlidesCount = $slider.find(".swiper-slide:not(.swiper-slide-duplicate)").length;

		if (realSlidesCount > 1) {
			const $areasWrapper = $('<div class="product-card__hover-areas"></div>');
			$areasWrapper.css({
				position: "absolute",
				top: 0,
				left: 0,
				right: 0,
				bottom: 0,
				display: "flex",
				zIndex: 10,
			});

			if (!hasCursor) {
				$areasWrapper[0].style.setProperty("pointer-events", "none", "important");
			}

			const permalink = $root.is(".product-card") ? $root.find(".product-card__title a").attr("href") : null;

			for (let i = 0; i < realSlidesCount; i++) {
				const $area = $('<div class="product-card__hover-area"></div>');
				$area.css({
					flex: "1 1 0",
				});

				$area.on("mouseenter", () => {
					swiper.slideToLoop(i, 0);
				});

				if (permalink) {
					$area.css("cursor", "pointer");
					$area.on("click", () => {
						window.location.href = permalink;
					});
				}

				$areasWrapper.append($area);
			}

			$slider.css("position", "relative").append($areasWrapper);

			if (hasCursor) {
				$slider.on("mouseleave", () => {
					swiper.slideToLoop(0, 0);
				});
			}
		}
	};

	$(".product-card").each(function () {
		initProductGallery($(this), ".product-card__slider", ".product-card__pagination");
	});

	const initProductPageGallery = ($product) => {
		const $gallery = $product.find(".product__gallery");
		if (!$gallery.length) return;

		const $main = $gallery.find(".product__main");
		const $link = $main.find(".product__main-link");
		const $img = $main.find(".product__image");
		const $zoom = $main.find(".product__zoom");
		const $thumbs = $gallery.find(".product__thumbs");
		let gallery = $gallery.data("gallery") || [];

		if (typeof gallery === "string") {
			try {
				gallery = JSON.parse(gallery);
			} catch (e) {
				gallery = [];
			}
		}

		if (!Array.isArray(gallery)) {
			gallery = [];
		}

		let currentIndex = 0;

		const setActive = (index) => {
			const item = gallery[index];
			if (!item) return;

			currentIndex = index;
			$link.attr("href", item.full);
			$img.attr({
				src: item.single,
				alt: item.alt || "",
			});
			$zoom.css("background-image", `url("${item.full}")`);
			$thumbs.find(".product__thumb").removeClass("is-active").eq(index).addClass("is-active");
		};

		$link.on("click", (e) => {
			e.preventDefault();
			if (typeof Fancybox === "undefined" || !gallery.length) return;

			Fancybox.show(
				gallery.map((item) => ({
					src: item.full,
					type: "image",
				})),
				{
					startIndex: currentIndex,
					dragToClose: false,
				},
			);
		});

		if (hasCursor) {
			$main.on("mouseenter", () => {
				const item = gallery[currentIndex];
				if (!item) return;

				$zoom
					.css({
						backgroundImage: `url("${item.full}")`,
						backgroundSize: "250%",
					})
					.addClass("is-visible");
			});

			$main.on("mousemove", (e) => {
				const rect = $main[0].getBoundingClientRect();
				if (!rect.width || !rect.height) return;

				const x = ((e.clientX - rect.left) / rect.width) * 100;
				const y = ((e.clientY - rect.top) / rect.height) * 100;
				$zoom.css("background-position", `${x}% ${y}%`);
			});

			$main.on("mouseleave", () => {
				$zoom.removeClass("is-visible");
			});
		}

		if ($thumbs.length) {
			new Swiper($thumbs[0], {
				slidesPerView: "auto",
				spaceBetween: 8,
				watchOverflow: true,
				watchSlidesProgress: true,
			});

			$thumbs.on("click", ".product__thumb", function () {
				setActive(Number($(this).data("index")));
			});
		}
	};

	$(".product").each(function () {
		initProductPageGallery($(this));
	});

	if ($(".reviews").length) {
		let reviewsTextSwiper;
		const initTextSwiper = () => {
			if ($(".reviews__text .reviews__slider").length && !reviewsTextSwiper) {
				reviewsTextSwiper = new Swiper(".reviews__text .reviews__slider", {
					slidesPerView: "auto",
					spaceBetween: 12,
					watchOverflow: true,
					navigation: {
						nextEl: ".reviews__controls--text .reviews__next",
						prevEl: ".reviews__controls--text .reviews__prev",
					},
					pagination: {
						el: ".reviews__controls--text .reviews__pagination",
						...swiperPaginationConfig,
					},
					breakpoints: {
						991.98: {
							slidesPerView: 3,
							spaceBetween: 18,
						},
						1399.98: {
							slidesPerView: 4,
							spaceBetween: 24,
						},
					},
				});
			}
		};

		let reviewsScreenshotsSwiper;
		const initScreenshotsSwiper = () => {
			if ($(".reviews__screenshots .reviews__slider").length && !reviewsScreenshotsSwiper) {
				reviewsScreenshotsSwiper = new Swiper(".reviews__screenshots .reviews__slider", {
					slidesPerView: "auto",
					spaceBetween: 12,
					watchOverflow: true,
					navigation: {
						nextEl: ".reviews__controls--screenshots .reviews__next",
						prevEl: ".reviews__controls--screenshots .reviews__prev",
					},
					pagination: {
						el: ".reviews__controls--screenshots .reviews__pagination",
						...swiperPaginationConfig,
					},
					breakpoints: {
						991.98: {
							slidesPerView: 4,
							spaceBetween: 18,
						},
						1399.98: {
							slidesPerView: 5,
							spaceBetween: 24,
						},
					},
				});
			}
		};

		const $reviewsSection = $("#reviews");
		const $switcherInputs = $reviewsSection.find('.reviews__switcher input[name="reviews-type"]');
		const $reviewsTextContainer = $reviewsSection.find(".reviews__text");
		const $reviewsScreenshotsContainer = $reviewsSection.find(".reviews__screenshots");
		const $controlsText = $reviewsSection.find(".reviews__controls--text");
		const $controlsScreenshots = $reviewsSection.find(".reviews__controls--screenshots");

		if ($switcherInputs.filter(":checked").val() === "text") {
			initTextSwiper();
		} else {
			initScreenshotsSwiper();
		}

		$switcherInputs.on("change", function () {
			const type = $(this).val();

			if (type === "text") {
				$reviewsTextContainer.show();
				$reviewsScreenshotsContainer.hide();
				$controlsText.show();
				$controlsScreenshots.hide();
				initTextSwiper();
			} else if (type === "screenshots") {
				$reviewsTextContainer.hide();
				$reviewsScreenshotsContainer.show();
				$controlsText.hide();
				$controlsScreenshots.show();
				initScreenshotsSwiper();
			}

			if (reviewsTextSwiper) {
				reviewsTextSwiper.update();
			}
			if (reviewsScreenshotsSwiper) {
				reviewsScreenshotsSwiper.update();
			}
		});
	}

	if ($(".nav-slider").length) {
		let policiesSlider = new Swiper(".nav-slider", {
			slidesPerView: "auto",
			spaceBetween: 4,
			slideToClickedSlide: true,
			initialSlide: $(".nav-slider__item .nav-slider__link.active").parent().index(),
		});

		$(document).on("change", '#policies input[name="policy-type"]', function () {
			const $this = $(this);
			const $popup = $this.closest("#policies");
			const $items = $popup.find(".switcher__item");
			const currentIndex = $items.index($this.closest(".switcher__item"));
			const $textBlocks = $popup.find(".popup__text");

			$textBlocks.hide().eq(currentIndex).show();

			if (policiesSlider) {
				policiesSlider.slideTo(currentIndex);
			}
		});
	}
	// product variation change price

	$(document).on("change", ".product-card__variations-input", function () {
		var $card = $(this).closest(".product-card, .product");
		var selectedVariationId = $(this).val();
		var newPriceHtml = $(this).data("price-html");
		var newRegularPriceHtml = $(this).data("regular-price-html");
		var isInStock = !$(this).is(":disabled");

		var $currentPriceElement = $card.find('[data-price-role="current-price"]');
		var $regularPriceElement = $card.find('[data-price-role="regular-price"]');
		var $addToCartButton = $card.find(".toggle-to-cart-button, .ajax_add_to_cart");

		$currentPriceElement.html(newPriceHtml);
		$regularPriceElement.html(newRegularPriceHtml);

		$addToCartButton.data("variation-id", selectedVariationId);

		if (isInStock) {
			$addToCartButton.removeAttr("disabled");
		} else {
			$addToCartButton.attr("disabled", "disabled");
		}
	});

	$(".product-card, .product").each(function () {
		var $card = $(this);
		var $firstRadio = $card.find(".product-card__variations-input:checked");

		if ($firstRadio.length) {
			$firstRadio.trigger("change");
		}
	});

	// header observer
	const headerElement = $(".header");

	const callback = function (entries, observer) {
		if (entries[0].isIntersecting) {
			headerElement.removeClass("scroll");
		} else {
			headerElement.addClass("scroll");
		}
	};

	const headerObserver = new IntersectionObserver(callback);
	headerObserver.observe(headerElement[0]);

	// switcher animation

	$(".switcher").each(function () {
		var $switcher = $(this);
		var $slider = $('<div class="switcher__slider"></div>');
		$switcher.prepend($slider);

		function updateSliderPosition($checkedInput) {
			if (!$switcher.is(":visible")) {
				return;
			}

			var $button = $checkedInput.next(".switcher__btn");
			if (!$button.length) return;

			var width = $button.outerWidth();
			var offsetLeft = $button.offset().left;
			var parentOffsetLeft = $switcher.offset().left;
			var parentPaddingLeft = parseFloat($switcher.css("padding-left"));
			var offset = offsetLeft - parentOffsetLeft - parentPaddingLeft;

			$switcher.css("--active-width", width + "px");
			$switcher.css("--active-offset", offset + "px");
		}

		var observer = new ResizeObserver(function (entries) {
			for (var entry of entries) {
				if (entry.contentRect.width > 0 || entry.contentRect.height > 0) {
					var $initialChecked = $switcher.find(".switcher__input:checked");
					if ($initialChecked.length) {
						updateSliderPosition($initialChecked);
					}
				}
			}
		});

		observer.observe($switcher[0]);

		$switcher.on("change", ".switcher__input", function () {
			updateSliderPosition($(this));
		});

		var resizeTimeout;
		$(window).on("resize", function () {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(function () {
				var $currentChecked = $switcher.find(".switcher__input:checked");
				if ($currentChecked.length) {
					updateSliderPosition($currentChecked);
				}
			}, 150);
		});
	});

	// Contacts Block Map
	function createMap() {
		const $map = $("#map");

		var coordsAddress = $map.data("coords").split("; ");
		var placemarkURL = $map.data("placemark-logo");

		for (var i = 0; i < coordsAddress.length; i++) {
			coordsAddress[i] = coordsAddress[i].split(", ");
			for (var j = 0; j < coordsAddress[i].length; j++) {
				coordsAddress[i][j] = parseFloat(coordsAddress[i][j]);
			}
		}

		var mapCenter = coordsAddress[0].map(function (element) {
			return element;
		});

		ymaps.ready(function () {
			var myMap = new ymaps.Map("map", {
				center: mapCenter,
				zoom: 16,
				controls: [],
			});

			var addressOne = new ymaps.Placemark(
				coordsAddress[0],
				{},
				{
					iconLayout: "default#image",
					iconImageHref: placemarkURL,
					iconImageSize: [69, 78],
					iconImageOffset: [-35, -78],
				},
			);

			var zoomControl = new ymaps.control.ZoomControl({
				options: {
					position: {
						right: 10,
						top: 200,
					},
				},
			});

			myMap.behaviors.disable("scrollZoom");

			myMap.controls.add(zoomControl);

			myMap.geoObjects.add(addressOne);
		});
	}

	var isMapShown = false;
	$(window).scroll(function () {
		let container = $("#map");
		if (!isMapShown && container.length > 0) {
			let script = document.createElement("script");
			script.async = true;
			script.defer = true;
			script.src = "https://api-maps.yandex.ru/2.1/?lang=ru_RU";
			let container_pos = container.offset().top;
			let winHeight = $(window).height();
			let scrollToElem = container_pos - winHeight - 200;
			if ($(this).scrollTop() > scrollToElem) {
				document.head.append(script);
				script.onload = function () {
					createMap();
				};
				isMapShown = true;
			}
		}
	});

	class FormController {
		constructor() {
			this.selectors = {
				field: ".form__field",
				errorClass: "_error",
				loadingClass: "_loading",
				requiredAttr: "[data-required]",
				fileInput: ".form__file-input",
				fileContainer: ".form__file",
				fileRemove: ".form__file-remove",
				submitBtn: '[type="submit"]',
			};
			this.init();
		}

		init() {
			const self = this;

			$("form").each(function () {
				self.bindSubmit($(this));
			});

			$(document).on("input change", this.selectors.requiredAttr, (e) => {
				const $field = $(e.target);
				if ($field.hasClass(this.selectors.errorClass) || $field.closest(this.selectors.field).hasClass(this.selectors.errorClass)) {
					this.toggleErrorState($field, true);
				}
			});

			$(document).on("keydown", 'input[type="tel"]', (e) => this.onPhoneKeyDown(e));
			$(document).on("input", 'input[type="tel"]', (e) => this.onPhoneInput(e));
			$(document).on("paste", 'input[type="tel"]', (e) => this.onPhonePaste(e));
			$(document).on("focus", 'input[type="tel"]', (e) => {
				if (!e.target.value) {
					e.target.value = "+7 ";
				}
			});

			$(document)
				.off("change", this.selectors.fileInput)
				.on("change", this.selectors.fileInput, (e) => this.handleFileChange(e));
			$(document)
				.off("click", this.selectors.fileRemove)
				.on("click", this.selectors.fileRemove, (e) => this.handleFileRemove(e));

			$(document).on("input change", 'input[name="smart-token"]', (e) => {
				const $form = $(e.target).closest("form");
				if ($form.length) {
					this.toggleCaptchaError($form, false);
				}
			});

			this.waitForCaptchaApi();
			document.addEventListener("dornott-smartcaptcha-ready", () => this.initCaptchas());
		}

		initCaptchas($scope) {
			const $root = $scope ? ($scope instanceof jQuery ? $scope : $($scope)) : $(document);
			const clientKey = window.dornott_ajax?.captcha_client_key;
			const $containers = $root.find("form [data-smart-captcha]");

			if (!clientKey || !window.smartCaptcha) {
				return false;
			}

			let rendered = 0;

			$containers.each((_, el) => {
				if (el.dataset.widgetId !== undefined) {
					return;
				}

				try {
					const widgetId = window.smartCaptcha.render(el, {
						sitekey: clientKey,
						hl: "ru",
						theme: "light",
					});

					el.dataset.widgetId = String(widgetId);
					rendered++;
				} catch (error) {
					console.error("[SmartCaptcha] ошибка render", error);
				}
			});

			return rendered > 0;
		}

		waitForCaptchaApi() {
			if (this.initCaptchas()) {
				return;
			}

			const timer = setInterval(() => {
				if (this.initCaptchas()) {
					clearInterval(timer);
				}
			}, 200);

			setTimeout(() => {
				clearInterval(timer);
				if (!$("form [data-smart-captcha][data-widget-id]").length && $("form [data-smart-captcha]").length) {
					console.error("[SmartCaptcha] не удалось инициализировать виджеты за 15 сек");
				}
			}, 15000);
		}

		resetCaptcha($form) {
			if (!window.smartCaptcha) {
				return;
			}

			const container = $form.find("[data-smart-captcha]")[0];

			if (container?.dataset.widgetId !== undefined) {
				window.smartCaptcha.reset(Number(container.dataset.widgetId));
			}
		}

		toggleCaptchaError($form, hasError) {
			$form.find("[data-smart-captcha]").toggleClass(this.selectors.errorClass, hasError);
		}

		isCaptchaSolved($form) {
			if (!$form.find("[data-smart-captcha]").length) {
				return true;
			}

			const formData = new FormData($form[0]);
			if (formData.get("smart-token")) {
				this.toggleCaptchaError($form, false);
				return true;
			}

			this.toggleCaptchaError($form, true);
			return false;
		}

		bindSubmit($form) {
			$form.on("submit", async (e) => {
				if ($form.attr("id") === "cart-form") {
					return;
				}
				e.preventDefault();

				if (this.validateForm($form)) {
					await this.sendForm($form);
				}
			});
		}

		async sendForm($form, isSilent = false) {
			console.log("submit from form controller");
			const url = $form.attr("action");
			const method = $form.attr("method") || "POST";
			const formData = new FormData($form[0]);
			const $submitBtn = $form.find(this.selectors.submitBtn);

			if ($form.find("[data-smart-captcha]").length && !formData.get("smart-token")) {
				this.toggleCaptchaError($form, true);
				return;
			}

			this.toggleCaptchaError($form, false);
			$submitBtn.addClass(this.selectors.loadingClass);

			try {
				const response = await fetch(url, {
					method: method,
					body: formData,
				});

				if (response.ok) {
					const result = await response.json();

					if (result.success) {
						$form[0].reset();
						$form.find(".form__file-preview").remove();
						$form.find(".uploaded").removeClass("uploaded");
						this.resetCaptcha($form);

						if (window.dornottCart && $form.attr("id") === "cart-form") {
							localStorage.removeItem(window.dornottCart.storageKey);
							window.dornottCart.updateInterface();
						}

						if (!isSilent) {
							const instance = Fancybox.getInstance();
							if (instance) {
								instance.destroy();
							}

							Fancybox.show([
								{
									src: "#success-submitting",
									type: "inline",
								},
							]);

							if (typeof ym === "function") {
								ym(105964434, "reachGoal", "sendmail");
							}
						}
					} else {
						console.error("Ошибка логики сервера:", result.data.message);
						this.resetCaptcha($form);
						this.showErrorPopup();
					}
				} else {
					console.error("Ошибка сервера (HTTP статус)");
					this.showErrorPopup();
				}
			} catch (error) {
				console.error("Ошибка сети", error);
				this.showErrorPopup();
			} finally {
				$submitBtn.removeClass(this.selectors.loadingClass);
			}
		}

		showErrorPopup() {
			const instance = Fancybox.getInstance();
			if (instance) {
				instance.destroy();
			}
			Fancybox.show([
				{
					src: "#error-submitting",
					type: "inline",
				},
			]);
		}

		validateField($field) {
			if (!$field.is(":visible")) {
				this.toggleErrorState($field, true);
				return true;
			}

			const type = $field.attr("type");
			const name = $field.attr("name");
			const val = $field.val() ? $field.val().trim() : "";
			let isValid = true;

			if (type === "checkbox") {
				isValid = $field.is(":checked");
			} else if (name === "username") {
				isValid = /^[^\d]+$/.test(val) && val.length > 1;
			} else if (type === "tel") {
				const numbers = val.replace(/\D/g, "");
				isValid = numbers.length >= 11;
			} else if (type === "email") {
				isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
			} else if (name === "city") {
				isValid = val.length >= 2;
			} else if (name === "address") {
				isValid = val.length >= 5;
			} else {
				isValid = val !== "";
			}

			this.toggleErrorState($field, isValid);
			return isValid;
		}

		toggleErrorState($field, isValid) {
			$field.closest(this.selectors.field).toggleClass(this.selectors.errorClass, !isValid);
			$field.toggleClass(this.selectors.errorClass, !isValid);
		}

		validateForm($form) {
			let isAllValid = true;
			const self = this;

			$form.find(this.selectors.requiredAttr).each(function () {
				if (!self.validateField($(this))) {
					isAllValid = false;
				}
			});

			if (window.formController && !window.formController.isCaptchaSolved($form)) {
				isAllValid = false;
			}

			return isAllValid;
		}

		handleFileChange(e) {
			const $input = $(e.currentTarget);
			const $container = $input.closest(this.selectors.fileContainer);
			const file = e.target.files[0];

			$container.find(".form__file-preview").remove();
			$container.removeClass("uploaded");

			if (file) {
				const isImage = file.type.match("image.*");
				const reader = new FileReader();

				reader.onload = (event) => {
					let previewContent = "";
					if (isImage) {
						previewContent = `
                        <span class="form__file-image">
                            <img src="${event.target.result}" alt="Превью" class="cover-image">
                        </span>
                    `;
					}
					const previewHtml = `
                    <div class="form__file-preview">
                        ${previewContent}
                        <span class="form__file-name">${file.name}</span>
                        <button type="button" aria-label="Удалить" class="form__file-remove icon-cross"></button>
                    </div>
                `;
					$container.append(previewHtml);
					$container.addClass("uploaded");
				};
				reader.readAsDataURL(file);
			}
		}

		handleFileRemove(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $container = $btn.closest(this.selectors.fileContainer);
			const $preview = $btn.closest(".form__file-preview");

			$container.find(this.selectors.fileInput).val("");
			$container.removeClass("uploaded");
			$preview.remove();
		}

		onPhoneInput(e) {
			const input = e.target;
			let inputNumbersValue = input.value.replace(/\D/g, "");
			const selectionStart = input.selectionStart;
			let formattedInputValue = "";

			if (!inputNumbersValue) {
				return (input.value = "");
			}

			if (input.value.length != selectionStart) {
				if (e.originalEvent.data && /\D/g.test(e.originalEvent.data)) {
					input.value = inputNumbersValue;
				}
				return;
			}

			if (inputNumbersValue.length > 11) {
				inputNumbersValue = inputNumbersValue.substring(0, 11);
			}

			formattedInputValue = "+7 (";

			if (inputNumbersValue.length >= 2) {
				formattedInputValue += inputNumbersValue.substring(1, 4);
			}
			if (inputNumbersValue.length >= 5) {
				formattedInputValue += ") " + inputNumbersValue.substring(4, 7);
			}
			if (inputNumbersValue.length >= 8) {
				formattedInputValue += "-" + inputNumbersValue.substring(7, 9);
			}
			if (inputNumbersValue.length >= 10) {
				formattedInputValue += "-" + inputNumbersValue.substring(9, 11);
			}

			input.value = formattedInputValue;
		}

		onPhoneKeyDown(e) {
			const inputValue = e.target.value.replace(/\D/g, "");
			if (e.keyCode == 8 && inputValue.length == 1) {
				e.target.value = "";
			}
		}

		onPhonePaste(e) {
			const input = e.target;
			const inputNumbersValue = input.value.replace(/\D/g, "");
			const pasted = e.originalEvent.clipboardData || window.clipboardData;
			if (pasted) {
				const pastedText = pasted.getData("Text");
				if (/\D/g.test(pastedText)) {
					input.value = inputNumbersValue;
				}
			}
		}
	}

	window.formController = new FormController();

	class DornottCart {
		constructor() {
			this.$cartModal = $("#cart");
			this.storageKey = "dornott_cart";
			this.$form = $("#cart-form");
			this.$cartBody = $(".cart__body");
			this.$container = $("#cart-items-container");
			this.$cartToggler = $("[data-cart-toggler]");
			this.$addressStep = this.$form.find(".order__step").eq(2);
			this.$checkoutBtn = $("#checkout-button");
			this.$paymentContainer = $("#payment-container");
			this.$cartTitle = $(".cart__title");

			this.init();
		}

		init() {
			this.bindEvents();
			this.updateInterface();
			this.toggleAddressStep();
		}

		getData() {
			return JSON.parse(localStorage.getItem(this.storageKey)) || [];
		}

		saveData(data) {
			localStorage.setItem(this.storageKey, JSON.stringify(data));
			this.updateInterface();
		}

		showTooltip($target, text, type = "success") {
			$(".tooltip").remove();

			const $tooltip = $(`<div class="tooltip ${type}"></div>`).text(text);
			$("body").append($tooltip);

			const offset = $target.offset();
			const tooltipWidth = $tooltip.outerWidth();
			const tooltipHeight = $tooltip.outerHeight();
			const elementWidth = $target.outerWidth();
			const elementHeight = $target.outerHeight();

			let top = offset.top - tooltipHeight - 10;
			let left = offset.left + elementWidth - tooltipWidth;

			if (top < $(window).scrollTop()) {
				top = offset.top + elementHeight + 10;
				$tooltip.addClass("open-bottom");
			} else {
				$tooltip.addClass("open-top");
			}

			if (left < 5) {
				left = 5;
			}

			$tooltip
				.css({
					top: top,
					left: left,
					position: "absolute",
					opacity: 0,
					display: "block",
				})
				.animate({ opacity: 1 }, 200);

			setTimeout(() => {
				$tooltip.fadeOut(300, function () {
					$(this).remove();
				});
			}, 2000);
		}

		bindEvents() {
			$(document).on("click", ".toggle-to-cart-button", (e) => this.handleAddToCart(e));
			$(document).on("click", ".quantity-block__up", (e) => this.changeQty(e, 1));
			$(document).on("click", ".quantity-block__down", (e) => this.changeQty(e, -1));
			$(document).on("change", ".quantity-block__input", (e) => this.handleQtyInput(e));
			$(document).on("click", ".cart__item-remove", (e) => {
				const $btn = $(e.target);
				const id = $btn.closest(".cart__item").data("id");
				this.removeItem(id);
				this.showTooltip($btn, "Товар удален", "");
			});

			$(document).on("change", 'input[name="delivery"]', () => {
				this.updateInterface();
				this.toggleAddressStep();
			});
			$(document).on("change", 'input[name="select_all"]', (e) => this.toggleAllCheckboxes(e));
			$(document).on("change", ".cart__item-checkbox .checkbox__input", () => this.updateSelectAllState());
			$(document).on("click", ".cart__clear", (e) => {
				this.removeSelected();
				this.showTooltip($(e.currentTarget), "Выбранные товары удалены", "");
			});
			$(document).on("change", ".product-card__variations-input", (e) => this.handleVariationChange(e));

			this.$form.on("submit", (e) => this.handleSubmit(e));
			this.$form.on("input change", "[data-required]", () => this.validateForm());
		}

		handleAddToCart(e) {
			const $btn = $(e.currentTarget);
			const $card = $btn.closest(".product-card, .product");
			const productId = $btn.data("variation-id") || $btn.data("product-id");

			if ($btn.hasClass("active")) {
				this.removeItem(productId);
				$btn.removeClass("active");
				this.showTooltip($btn, "Удалено из корзины", "");
			} else {
				const price = parseInt($card.find('[data-price-role="current-price"]').text().replace(/\D/g, ""));
				const regPrice = parseInt($card.find('[data-price-role="regular-price"]').text().replace(/\D/g, "")) || price;
				const saleText = $card.find(".price-block__sale").text().trim();

				const $qtyInput = $card.find(".quantity-block__input");
				const quantity = $qtyInput.length ? Math.min(999, Math.max(1, parseInt($qtyInput.val(), 10) || 1)) : 1;

				const product = {
					id: productId,
					name: $card.find(".product-card__title").text().trim(),
					sku: $card.find(".product-card__sku").text().trim() || String($card.data("sku") || ""),
					price: price,
					regular_price: regPrice,
					sale_label: saleText,
					image: $card.find(".product-card__image").first().attr("src"),
					quantity: quantity,
				};

				this.addItem(product);
				$btn.addClass("active");
				this.showTooltip($btn, "Товар добавлен в корзину", "success");
			}
		}

		addItem(product) {
			let data = this.getData();
			const index = data.findIndex((item) => item.id === product.id);
			if (index === -1) data.push(product);
			this.saveData(data);
		}

		removeItem(id) {
			let data = this.getData();
			data = data.filter((item) => item.id !== id);
			this.saveData(data);
			$(`.toggle-to-cart-button[data-product-id="${id}"], .toggle-to-cart-button[data-variation-id="${id}"]`).removeClass("active");
		}

		syncProductQuantity($input, val) {
			const $product = $input.closest(".product");
			if ($product.length) {
				$product.find(".toggle-to-cart-button").attr("data-quantity", val);
			}
		}

		changeQty(e, delta) {
			const $input = $(e.currentTarget).siblings(".quantity-block__input");
			const $cartItem = $(e.currentTarget).closest(".cart__item");
			let val = parseInt($input.val()) || 1;
			val = Math.min(999, Math.max(1, val + delta));
			$input.val(val);

			if ($cartItem.length) {
				this.updateQuantity($cartItem.data("id"), val);
				return;
			}

			this.syncProductQuantity($input, val);
		}

		handleQtyInput(e) {
			const $input = $(e.currentTarget);
			const $cartItem = $input.closest(".cart__item");
			let val = parseInt($input.val().replace(/\D/g, "")) || 1;
			val = Math.min(999, Math.max(1, val));
			$input.val(val);

			if ($cartItem.length) {
				this.updateQuantity($cartItem.data("id"), val);
				return;
			}

			this.syncProductQuantity($input, val);
		}

		updateQuantity(id, qty) {
			let data = this.getData();
			const index = data.findIndex((item) => item.id === id);
			if (index > -1) {
				data[index].quantity = qty;
				this.saveData(data);
			}
		}

		validateForm() {
			let isAllValid = true;
			this.$form.find("[data-required]").each((_, el) => {
				if (window.formController && !window.formController.validateField($(el))) {
					isAllValid = false;
				}
			});

			$("#cart-validation-warning").toggleClass("hidden", isAllValid);

			if (window.formController && !window.formController.isCaptchaSolved(this.$form)) {
				isAllValid = false;
			}

			return isAllValid;
		}

		toggleAllCheckboxes(e) {
			const isChecked = $(e.currentTarget).is(":checked");
			$(".cart__item-checkbox .checkbox__input").prop("checked", isChecked);
		}

		updateSelectAllState() {
			const totalItems = $(".cart__item-checkbox .checkbox__input").length;
			const checkedItems = $(".cart__item-checkbox .checkbox__input:checked").length;
			$('input[name="select_all"]').prop("checked", totalItems === checkedItems && totalItems > 0);
		}

		removeSelected() {
			$(".cart__item-checkbox .checkbox__input:checked").each((_, el) => {
				this.removeItem($(el).closest(".cart__item").data("id"));
			});
		}

		handleVariationChange(e) {
			const $input = $(e.currentTarget);
			const $card = $input.closest(".product-card, .product");
			const $btn = $card.find(".toggle-to-cart-button");

			$card.find('[data-price-role="current-price"]').html($input.data("price-html"));
			$card.find('[data-price-role="regular-price"]').html($input.data("regular-price-html"));

			const newId = $input.val();
			$btn.data("variation-id", newId);

			const data = this.getData();
			const isActive = data.some((item) => item.id == newId);
			$btn.toggleClass("active", isActive);
			this.syncProductCartButtonLabel($btn, isActive);
		}

		calculateTotals() {
			const data = this.getData();
			const deliveryPrice = parseInt($('input[name="delivery"]:checked').data("price")) || 0;
			let subtotal = 0,
				discount = 0,
				totalQty = 0;

			data.forEach((item) => {
				subtotal += item.regular_price * item.quantity;
				discount += (item.regular_price - item.price) * item.quantity;
				totalQty += item.quantity;
			});

			return { subtotal, discount, totalQty, deliveryPrice, finalPrice: subtotal - discount + deliveryPrice };
		}

		updateInterface() {
			const data = this.getData();
			const totals = this.calculateTotals();

			if (data.length > 0) {
				this.$cartBody.show();
				$("#cart-empty-state").hide();
				if (!this.$cartToggler.find(".cart-quantity").length) {
					this.$cartToggler.append('<span class="cart-quantity"></span>');
				}
				this.$cartToggler.find(".cart-quantity").text(totals.totalQty);
			} else {
				this.$cartBody.hide();
				$("#cart-empty-state").show();
				this.$cartToggler.find(".cart-quantity").remove();
			}

			$("#total-qty").text(`${totals.totalQty} шт.`);
			$("#subtotal-price").text(`${totals.subtotal.toLocaleString()} ₽`);
			$("#total-discount").text(`${totals.discount.toLocaleString()} ₽`);
			$("#delivery-price").text(`${totals.deliveryPrice.toLocaleString()} ₽`);
			$("#final-price").text(`${totals.finalPrice.toLocaleString()} ₽`);

			this.$form.find('input[name="delivery_price"]').val(totals.deliveryPrice);

			this.renderItems(data);
			this.syncButtons(data);
		}

		toggleAddressStep() {
			const deliveryMethod = $('input[name="delivery"]:checked').val();
			if (this.$addressStep.length) {
				if (deliveryMethod === "pickup") {
					this.$addressStep.hide();
					if (window.formController) {
						this.$addressStep.find("." + window.formController.selectors.errorClass).removeClass(window.formController.selectors.errorClass);
					}
				} else {
					this.$addressStep.show();
				}
			}
		}

		syncProductCartButtonLabel($btn, isActive) {
			if (!$btn.hasClass("product__cart-btn")) return;

			const addLabel = $btn.attr("data-aria-add");
			const addedLabel = $btn.attr("data-aria-added");

			if (addLabel && addedLabel) {
				$btn.attr("aria-label", isActive ? addedLabel : addLabel);
			}
		}

		syncButtons(data) {
			$(".toggle-to-cart-button").each((_, el) => {
				const $btn = $(el);
				const id = $btn.data("variation-id") || $btn.data("product-id");
				const isActive = data.some((item) => item.id == id);
				$btn.toggleClass("active", isActive);
				this.syncProductCartButtonLabel($btn, isActive);
			});
		}

		renderItems(data) {
			this.$container.empty();
			data.forEach((item) => {
				this.$container.append(`
                <div class="cart__item" data-id="${item.id}">
                    <div class="cart__item-block cart__item-block--details">
                        <label class="cart__item-checkbox checkbox">
                            <input type="checkbox" class="checkbox__input hidden" hidden checked>
                            <span class="checkbox__text"></span>
                        </label>
                        <div class="cart__item-thumb"><img src="${item.image}"></div>
                        <div class="cart__item-info">
                            <div class="cart__item-sku">${item.sku}</div>
                            <div class="cart__item-name">${item.name}</div>
                        </div>
                    </div>
                    <div class="cart__item-block cart__item-block--quantity">
                        <div class="quantity-block">
                            <button type="button" class="quantity-block__down icon-minus"></button>
                            <input type="number" class="quantity-block__input" value="${item.quantity}">
                            <button type="button" class="quantity-block__up icon-plus"></button>
                        </div>
                    </div>
                    <div class="cart__item-block cart__item-block--price">
                        <div class="cart__item-price price-block">
                            <div class="price-block__header">
                                <div class="price-block__old">${item.regular_price > item.price ? item.regular_price.toLocaleString() + " ₽" : ""}</div>
                                <div class="price-block__sale">${item.sale_label || ""}</div>
                            </div>
                            <div class="price-block__current">${item.price.toLocaleString()} ₽</div>
                        </div>
                        <button type="button" class="cart__item-remove icon-cross"></button>
                    </div>
                </div>
            `);
			});
			this.updateSelectAllState();
		}

		async handleSubmit(e) {
			e.preventDefault();
			if (this.validateForm()) {
				this.$checkoutBtn.addClass("_loading");

				const formData = this.$form.serializeArray().filter((item) => item.name !== "select_all");
				const payload = {
					order_info: formData,
					items: this.getData(),
					totals: this.calculateTotals(),
				};

				try {
					const response = await fetch("/wp-admin/admin-ajax.php?action=init_tbank_payment", {
						method: "POST",
						headers: {
							"Content-Type": "application/json",
						},
						body: JSON.stringify(payload),
					});

					const result = await response.json();

					if (result.success && result.data.paymentUrl) {
						this.openPaymentIframe(result.data.paymentUrl, result.data.orderId);
					} else {
						console.error("Ошибка инициализации платежа", result.data.message);
						if (window.formController) {
							window.formController.resetCaptcha(this.$form);
						}
						this.$checkoutBtn.removeClass("_loading");
					}
				} catch (error) {
					console.error("Произошла сетевая ошибка:", error);
					this.$checkoutBtn.removeClass("_loading");
				}
			}
		}

		async openPaymentIframe(paymentUrl, OrderId) {
			if (!window.tbankSDK) {
				console.error("SDK не инициализирован");
				return;
			}

			this.$cartModal.addClass("open-payment");
			this.$paymentContainer.show();
			this.$form.hide();
			this.$cartTitle.text("Оплата заказа");

			if (OrderId) {
				this.$form.find('input[name="order_id"]').val(OrderId);
			}

			try {
				const MAIN_INTEGRATION_NAME = "dornott-checkout-frame";

				const statusMessages = {
					"REJECTED": "Платеж отклонен банком. Проверьте данные карты, баланс или попробуйте другую карту.",
					"CANCELED": "Оплата была отменена. Если это произошло случайно, вы можете попробовать еще раз.",
					"EXPIRED": "Время на оплату истекло. Пожалуйста, создайте новый заказ.",
					"PROCESSING_ERROR": "Произошла техническая ошибка при обработке данных. Пожалуйста, попробуйте позже.",
					"default": "Произошла непредвиденная ошибка при оплате. Попробуйте еще раз или свяжитесь с поддержкой.",
				};

				const iframeConfig = {
					status: {
						changedCallback: async (status) => {
							console.log("T-Bank Payment Status:", status);

							if (status === "SUCCESS") {
								if (typeof ym === "function") {
									ym(105964434, "reachGoal", "payment_ok");
								}

								let cartDataForEmail = [];
								try {
									const storageRaw = localStorage.getItem(this.storageKey);
									if (storageRaw) {
										const storageData = JSON.parse(storageRaw);
										if (Array.isArray(storageData)) {
											cartDataForEmail = storageData;
										} else if (storageData && storageData.items) {
											cartDataForEmail = storageData.items;
										}
									}
								} catch (e) {
									console.error("Error reading cart for email", e);
								}

								this.$form.find('input[name="cart_items"]').val(JSON.stringify(cartDataForEmail));

								if (window.formController) {
									await window.formController.sendForm(this.$form, true);
								}

								localStorage.removeItem(this.storageKey);

								await this.resetInterface();

								const instance = Fancybox.getInstance();
								if (instance) {
									instance.destroy();
								}

								const $successPopup = $("#success-order");
								if (OrderId) {
									$successPopup.find(".order-number").text(`№ ${OrderId}`);
								}

								Fancybox.show([
									{
										src: "#success-order",
										type: "inline",
										autoFocus: false,
									},
								]);
							} else if (["REJECTED", "CANCELED", "EXPIRED", "PROCESSING_ERROR"].includes(status)) {
								const message = statusMessages[status] || statusMessages["default"];

								await this.resetInterface();

								const instance = Fancybox.getInstance();
								if (instance) {
									instance.destroy();
								}

								const $errorPopup = $("#error-order");
								$errorPopup.find(".popup__subtitle").text(message);

								setTimeout(() => {
									Fancybox.show([
										{
											src: "#error-order",
											type: "inline",
											autoFocus: false,
											trapFocus: false,
											placeFocusBack: false,
										},
									]);
								}, 100);
							}
						},
					},
				};

				this.currentPaymentWidget = await window.tbankSDK.iframe.create(MAIN_INTEGRATION_NAME, iframeConfig);
				const container = this.$paymentContainer[0];
				await this.currentPaymentWidget.mount(container, paymentUrl);
			} catch (error) {
				console.error("Iframe mount error:", error);
				this.resetInterface();
			}
		}

		async resetInterface() {
			if (this.currentPaymentWidget) {
				try {
					await this.currentPaymentWidget.unmount();
					this.currentPaymentWidget = null;
				} catch (e) {
					console.warn("Ошибка при unmount:", e);
				}
			}

			this.$paymentContainer.hide().html("");
			this.$form.show();
			this.$cartTitle.text("Корзина");
			this.$checkoutBtn.removeClass("_loading");
			this.$cartModal.removeClass("open-payment");
		}
	}

	window.dornottCart = new DornottCart();
});
