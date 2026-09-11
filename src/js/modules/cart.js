import $ from "jquery";

export const CART_STORAGE_KEY = "dornott_cart";

export function idsEqual(a, b) {
	return String(a) === String(b);
}

export function escapeHtml(value) {
	return String(value ?? "")
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#39;");
}

export function sanitizeImageSrc(src) {
	const value = String(src ?? "").trim();
	if (!value || /^\s*javascript:/i.test(value) || /^\s*data:/i.test(value)) {
		return "";
	}
	return escapeHtml(value);
}

export function clampQty(val) {
	const n = parseInt(String(val).replace(/\D/g, ""), 10);
	if (Number.isNaN(n)) return 1;
	return Math.min(999, Math.max(1, n));
}

export function parseCartData(raw) {
	if (!raw) return [];
	try {
		const data = JSON.parse(raw);
		return Array.isArray(data) ? data : [];
	} catch (e) {
		return [];
	}
}

export function calculateCartTotals(data, deliveryPrice = 0) {
	let subtotal = 0;
	let discount = 0;
	let totalQty = 0;

	(data || []).forEach((item) => {
		const qty = clampQty(item.quantity);
		const regular = Number(item.regular_price) || 0;
		const price = Number(item.price) || 0;
		subtotal += regular * qty;
		discount += (regular - price) * qty;
		totalQty += qty;
	});

	const delivery = Number(deliveryPrice) || 0;

	return {
		subtotal,
		discount,
		totalQty,
		deliveryPrice: delivery,
		finalPrice: subtotal - discount + delivery,
	};
}

export class DornottCart {
	constructor() {
		this.$cartModal = $("#cart");
		this.storageKey = CART_STORAGE_KEY;
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

	unbindEvents() {
		$(document).off(".dornottCart");
		this.$form.off(".dornottCart");
	}

	getData() {
		return parseCartData(localStorage.getItem(this.storageKey));
	}

	saveData(data) {
		localStorage.setItem(this.storageKey, JSON.stringify(data));
		this.updateInterface();
	}

	showTooltip($target, text, type = "success") {
		if (!$target || !$target.length) return;

		$(".tooltip").remove();

		const $tooltip = $(`<div class="tooltip ${type}"></div>`).text(text);
		$("body").append($tooltip);

		const offset = $target.offset() || { top: 0, left: 0 };
		const tooltipWidth = $tooltip.outerWidth() || 0;
		const tooltipHeight = $tooltip.outerHeight() || 0;
		const elementWidth = $target.outerWidth() || 0;
		const elementHeight = $target.outerHeight() || 0;

		let top = offset.top - tooltipHeight - 10;
		let left = offset.left + elementWidth - tooltipWidth;

		if (top < ($(window).scrollTop() || 0)) {
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
		this.unbindEvents();

		$(document).on("click.dornottCart", ".toggle-to-cart-button", (e) => this.handleAddToCart(e));
		$(document).on("click.dornottCart", ".quantity-block__up", (e) => this.changeQty(e, 1));
		$(document).on("click.dornottCart", ".quantity-block__down", (e) => this.changeQty(e, -1));
		$(document).on("change.dornottCart", ".quantity-block__input", (e) => this.handleQtyInput(e));
		$(document).on("click.dornottCart", ".cart__item-remove", (e) => {
			const $btn = $(e.target);
			const id = $btn.closest(".cart__item").data("id");
			this.removeItem(id);
			this.showTooltip($btn, "Товар удален", "");
		});

		$(document).on("change.dornottCart", 'input[name="delivery"]', () => {
			this.updateInterface();
			this.toggleAddressStep();
		});
		$(document).on("change.dornottCart", 'input[name="select_all"]', (e) => this.toggleAllCheckboxes(e));
		$(document).on("change.dornottCart", ".cart__item-checkbox .checkbox__input", () => this.updateSelectAllState());
		$(document).on("click.dornottCart", ".cart__clear", (e) => {
			if (this.removeSelected()) {
				this.showTooltip($(e.currentTarget), "Выбранные товары удалены", "");
			}
		});
		$(document).on("change.dornottCart", ".product-card__variations-input", (e) => this.handleVariationChange(e));

		this.$form.on("submit.dornottCart", (e) => this.handleSubmit(e));
		this.$form.on("input.dornottCart change.dornottCart", "[data-required]", () => this.validateForm());
	}

	readProductFromCard($card, $btn) {
		const price = parseInt($card.find('[data-price-role="current-price"]').text().replace(/\D/g, ""), 10) || 0;
		const regPrice = parseInt($card.find('[data-price-role="regular-price"]').text().replace(/\D/g, ""), 10) || price;
		const saleText = $card.find(".price-block__sale").text().trim();
		const $qtyInput = $card.find(".quantity-block__input");
		const quantity = $qtyInput.length ? clampQty($qtyInput.val()) : 1;

		return {
			id: $btn.data("variation-id") || $btn.data("product-id"),
			name: ($card.find(".product-card__title").text() || $card.find(".product__title").text()).trim(),
			sku: $card.find(".product-card__sku, .product__sku").text().trim() || String($card.data("sku") || ""),
			price: price,
			regular_price: regPrice,
			sale_label: saleText,
			image: $card.find(".product-card__image, .product__image").first().attr("src") || "",
			quantity: quantity,
		};
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
			this.addItem(this.readProductFromCard($card, $btn));
			$btn.addClass("active");
			this.showTooltip($btn, "Товар добавлен в корзину", "success");
		}
	}

	addItem(product) {
		const data = this.getData();
		const index = data.findIndex((item) => idsEqual(item.id, product.id));
		if (index === -1) data.push(product);
		this.saveData(data);
	}

	removeItem(id) {
		let data = this.getData();
		data = data.filter((item) => !idsEqual(item.id, id));
		this.saveData(data);
		$(`.toggle-to-cart-button[data-product-id="${id}"], .toggle-to-cart-button[data-variation-id="${id}"]`).removeClass("active");
	}

	syncProductQuantity($input, val) {
		const $product = $input.closest(".product");
		if (!$product.length) return;

		const $btn = $product.find(".toggle-to-cart-button");
		$btn.attr("data-quantity", val);

		const productId = $btn.data("variation-id") || $btn.data("product-id");
		if (this.getData().some((item) => idsEqual(item.id, productId))) {
			this.updateQuantity(productId, val);
		}
	}

	changeQty(e, delta) {
		const $input = $(e.currentTarget).siblings(".quantity-block__input");
		const $cartItem = $(e.currentTarget).closest(".cart__item");
		let val = clampQty((parseInt($input.val(), 10) || 1) + delta);
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
		const val = clampQty($input.val());
		$input.val(val);

		if ($cartItem.length) {
			this.updateQuantity($cartItem.data("id"), val);
			return;
		}

		this.syncProductQuantity($input, val);
	}

	updateQuantity(id, qty) {
		const data = this.getData();
		const index = data.findIndex((item) => idsEqual(item.id, id));
		if (index > -1) {
			data[index].quantity = clampQty(qty);
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

	getRenderedItemIds() {
		const ids = [];
		this.$container.find(".cart__item").each((_, el) => {
			ids.push(String($(el).data("id")));
		});
		return ids;
	}

	getCheckedItemIds() {
		const ids = [];
		this.$container.find(".cart__item-checkbox .checkbox__input:checked").each((_, el) => {
			ids.push(String($(el).closest(".cart__item").data("id")));
		});
		return ids;
	}

	removeSelected() {
		const ids = this.getCheckedItemIds();
		if (!ids.length) return false;

		const data = this.getData().filter((item) => !ids.some((id) => idsEqual(item.id, id)));
		this.saveData(data);

		ids.forEach((id) => {
			$(`.toggle-to-cart-button[data-product-id="${id}"], .toggle-to-cart-button[data-variation-id="${id}"]`).removeClass("active");
		});

		return true;
	}

	handleVariationChange(e) {
		const $input = $(e.currentTarget);
		const $card = $input.closest(".product-card, .product");
		const $btn = $card.find(".toggle-to-cart-button");

		$card.find('[data-price-role="current-price"]').html($input.data("price-html"));
		$card.find('[data-price-role="regular-price"]').html($input.data("regular-price-html"));

		const newId = $input.val();
		$btn.attr("data-variation-id", newId);
		$btn.data("variation-id", newId);

		const data = this.getData();
		const isActive = data.some((item) => idsEqual(item.id, newId));
		$btn.toggleClass("active", isActive);
		this.syncProductCartButtonLabel($btn, isActive);
	}

	calculateTotals() {
		const deliveryPrice = parseInt($('input[name="delivery"]:checked').data("price"), 10) || 0;
		return calculateCartTotals(this.getData(), deliveryPrice);
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

		$btn.find(".product__cart-btn-text--add").toggle(!isActive);
		$btn.find(".product__cart-btn-text--added").toggle(isActive);

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
			const isActive = data.some((item) => idsEqual(item.id, id));
			$btn.toggleClass("active", isActive);
			this.syncProductCartButtonLabel($btn, isActive);
		});
	}

	renderItems(data) {
		const previousIds = this.getRenderedItemIds();
		const checkedIds = this.getCheckedItemIds();

		this.$container.empty();
		data.forEach((item) => {
			const itemId = String(item.id);
			const isNew = !previousIds.includes(itemId);
			const isChecked = isNew || checkedIds.includes(itemId);
			const checkedAttr = isChecked ? " checked" : "";
			const saleLabel = escapeHtml(item.sale_label || "");
			const oldPrice = item.regular_price > item.price ? `${Number(item.regular_price).toLocaleString()} ₽` : "";

			this.$container.append(`
                <div class="cart__item" data-id="${escapeHtml(item.id)}">
                    <div class="cart__item-block cart__item-block--details">
                        <label class="cart__item-checkbox checkbox">
                            <input type="checkbox" class="checkbox__input hidden" hidden${checkedAttr}>
                            <span class="checkbox__text"></span>
                        </label>
                        <div class="cart__item-thumb"><img src="${sanitizeImageSrc(item.image)}" class="cover-image"></div>
                        <div class="cart__item-info">
                            <div class="cart__item-sku">${escapeHtml(item.sku)}</div>
                            <div class="cart__item-name">${escapeHtml(item.name)}</div>
                        </div>
                    </div>
                    <div class="cart__item-block cart__item-block--quantity">
                        <div class="quantity-block">
                            <button type="button" class="quantity-block__down icon-minus"></button>
                            <input type="number" class="quantity-block__input" value="${clampQty(item.quantity)}">
                            <button type="button" class="quantity-block__up icon-plus"></button>
                        </div>
                    </div>
                    <div class="cart__item-block cart__item-block--price">
                        <div class="cart__item-price price-block">
                            <div class="price-block__header">
                                <div class="price-block__old">${oldPrice}</div>
                                <div class="price-block__sale">${saleLabel}</div>
                            </div>
                            <div class="price-block__current">${Number(item.price).toLocaleString()} ₽</div>
                        </div>
                        <button type="button" class="cart__item-remove icon-cross"></button>
                    </div>
                </div>
            `);
		});
		this.updateSelectAllState();
	}

	getAjaxNonce() {
		return window.dornott_ajax?.nonce || this.$form.find('input[name="_ajax_nonce"]').val() || "";
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
				const ajaxUrl = window.dornott_ajax?.ajax_url || "/wp-admin/admin-ajax.php";
				const response = await fetch(`${ajaxUrl}?action=init_tbank_payment`, {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
						"X-WP-Nonce": this.getAjaxNonce(),
					},
					body: JSON.stringify(payload),
				});

				const result = await response.json();

				if (result.success && result.data.paymentUrl) {
					this.openPaymentIframe(result.data.paymentUrl, result.data.orderId);
				} else {
					console.error("Ошибка инициализации платежа", result.data?.message);
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
				REJECTED: "Платеж отклонен банком. Проверьте данные карты, баланс или попробуйте другую карту.",
				CANCELED: "Оплата была отменена. Если это произошло случайно, вы можете попробовать еще раз.",
				EXPIRED: "Время на оплату истекло. Пожалуйста, создайте новый заказ.",
				PROCESSING_ERROR: "Произошла техническая ошибка при обработке данных. Пожалуйста, попробуйте позже.",
				default: "Произошла непредвиденная ошибка при оплате. Попробуйте еще раз или свяжитесь с поддержкой.",
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
							this.updateInterface();

							await this.resetInterface();

							const instance = typeof Fancybox !== "undefined" ? Fancybox.getInstance() : null;
							if (instance) {
								instance.destroy();
							}

							const $successPopup = $("#success-order");
							if (OrderId) {
								$successPopup.find(".order-number").text(`№ ${OrderId}`);
							}

							if (typeof Fancybox !== "undefined") {
								Fancybox.show([
									{
										src: "#success-order",
										type: "inline",
										autoFocus: false,
									},
								]);
							}
						} else if (["REJECTED", "CANCELED", "EXPIRED", "PROCESSING_ERROR"].includes(status)) {
							const message = statusMessages[status] || statusMessages["default"];

							await this.resetInterface();

							const instance = typeof Fancybox !== "undefined" ? Fancybox.getInstance() : null;
							if (instance) {
								instance.destroy();
							}

							const $errorPopup = $("#error-order");
							$errorPopup.find(".popup__subtitle").text(message);

							setTimeout(() => {
								if (typeof Fancybox !== "undefined") {
									Fancybox.show([
										{
											src: "#error-order",
											type: "inline",
											autoFocus: false,
											trapFocus: false,
											placeFocusBack: false,
										},
									]);
								}
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
