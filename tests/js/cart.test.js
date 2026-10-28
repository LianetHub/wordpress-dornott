import { describe, it, expect, beforeEach, vi } from "vitest";
import $ from "jquery";
import { readFileSync } from "node:fs";
import { createFormController } from "./setup.js";
import {
	DornottCart,
	CART_STORAGE_KEY,
	idsEqual,
	escapeHtml,
	sanitizeImageSrc,
	clampQty,
	parseCartData,
	calculateCartTotals,
} from "../../src/js/modules/cart.js";

function cartFixture() {
	return `
		<button type="button" data-cart-toggler class="cart-toggler"></button>
		<div id="cart" class="popup cart">
			<h2 class="cart__title">Корзина</h2>
			<div id="cart-empty-state" class="cart__empty">Ваша корзина пока пуста</div>
			<div class="cart__body" style="display: none;">
				<form id="cart-form" class="cart__form">
					<input type="hidden" name="cart_items" value="">
					<input type="hidden" name="order_id" value="">
					<input type="hidden" name="delivery_price" value="">
					<input type="hidden" name="_ajax_nonce" value="test-nonce">
					<div class="cart__products-header">
						<label class="cart__select-all checkbox">
							<input type="checkbox" name="select_all" class="checkbox__input">
							<span class="checkbox__text">Выбрать все</span>
						</label>
						<button type="button" class="cart__clear">Удалить</button>
					</div>
					<div id="cart-items-container" class="cart__table-items"></div>
					<div class="order">
						<div class="order__step">Получатель</div>
						<div class="order__step">
							<label>
								<input type="radio" name="delivery" value="cdek" data-price="500">
								СДЭК
							</label>
							<label>
								<input type="radio" name="delivery" value="pickup" data-price="0">
								Самовывоз
							</label>
						</div>
						<div class="order__step">Адрес</div>
					</div>
					<div id="total-qty">0 шт.</div>
					<div id="subtotal-price">0 ₽</div>
					<div id="total-discount">0 ₽</div>
					<div id="delivery-price">0 ₽</div>
					<div id="final-price">0 ₽</div>
					<div id="cart-validation-warning" class="hidden"><div class="cart__warning-text"></div></div>
					<button type="submit" id="checkout-button">оплатить заказ</button>
				</form>
				<div id="payment-container"></div>
			</div>
		</div>
		<div class="product-card" data-sku="CARD-SKU">
			<img class="product-card__image" src="/img/card.jpg" alt="">
			<div class="product-card__sku">CARD-SKU</div>
			<h3 class="product-card__title">Карточка товара</h3>
			<div data-price-role="regular-price">2 000 ₽</div>
			<div data-price-role="current-price">1 500 ₽</div>
			<div class="price-block__sale">-25%</div>
			<button type="button" class="toggle-to-cart-button" data-product-id="10"></button>
		</div>
		<div class="product" data-sku="PAGE-SKU">
			<img class="product__image product-card__image" src="/img/page.jpg" alt="">
			<p class="product__title">Страница товара</p>
			<div data-price-role="regular-price">3 000 ₽</div>
			<div data-price-role="current-price">3 000 ₽</div>
			<div class="quantity-block">
				<button type="button" class="quantity-block__down"></button>
				<input type="number" class="quantity-block__input" value="2">
				<button type="button" class="quantity-block__up"></button>
			</div>
			<button type="button" class="toggle-to-cart-button product__cart-btn" data-product-id="20"
				data-aria-add="Добавить" data-aria-added="Удалить">
				<span class="product__cart-btn-text product__cart-btn-text--add">Добавить в корзину</span>
				<span class="product__cart-btn-text product__cart-btn-text--added">В корзине</span>
			</button>
		</div>
	`;
}

function sampleItem(overrides = {}) {
	return {
		id: 1,
		name: "Товар 1",
		sku: "SKU-1",
		price: 1000,
		regular_price: 1500,
		sale_label: "-33%",
		image: "/img/1.jpg",
		quantity: 1,
		...overrides,
	};
}

function mountCart() {
	document.body.innerHTML = cartFixture();
	window.formController ||= createFormController();
	return new DornottCart();
}

function checkedIds() {
	return $(".cart__item-checkbox .checkbox__input:checked")
		.toArray()
		.map((el) => String($(el).closest(".cart__item").data("id")));
}

describe("cart helpers", () => {
	it("compares numeric and string ids", () => {
		expect(idsEqual(5, "5")).toBe(true);
		expect(idsEqual("5", 5)).toBe(true);
		expect(idsEqual(5, 6)).toBe(false);
	});

	it("escapes html in product fields", () => {
		expect(escapeHtml('<img src=x onerror=alert(1)>')).toBe("&lt;img src=x onerror=alert(1)&gt;");
	});

	it("rejects javascript image urls", () => {
		expect(sanitizeImageSrc("javascript:alert(1)")).toBe("");
		expect(sanitizeImageSrc("/img/ok.jpg")).toBe("/img/ok.jpg");
	});

	it("clamps quantity between 1 and 999", () => {
		expect(clampQty(0)).toBe(1);
		expect(clampQty("abc")).toBe(1);
		expect(clampQty(1500)).toBe(999);
	});

	it("returns empty cart for broken localStorage json", () => {
		expect(parseCartData("{not-json")).toEqual([]);
		expect(parseCartData(JSON.stringify({ items: [] }))).toEqual([]);
	});

	it("calculates totals with discount and delivery", () => {
		const totals = calculateCartTotals(
			[
				{ price: 1000, regular_price: 1500, quantity: 2 },
				{ price: 500, regular_price: 500, quantity: 1 },
			],
			350,
		);
		expect(totals).toEqual({
			subtotal: 3500,
			discount: 1000,
			totalQty: 3,
			deliveryPrice: 350,
			finalPrice: 2850,
		});
	});
});

describe("DornottCart", () => {
	it("uses the existing common controller for recipient and delivery checks before payment", async () => {
		document.body.innerHTML = readFileSync("templates/_cart.php", "utf8").replace(/<\?php[\s\S]*?\?>/g, "");
		window.formController = createFormController();
		window.formController.bindSubmit($("#cart-form"));
		const commonValidation = vi.spyOn(window.formController, "validateForm");
		const cart = new DornottCart();
		const bank = vi.fn(); globalThis.fetch = bank;
		await cart.$form.triggerHandler("submit");
		expect(commonValidation).toHaveBeenCalledTimes(1);
		expect(bank).not.toHaveBeenCalled();
		expect($("#cart-validation-warning .cart__warning-text").text()).toContain("получателя");
		expect($("#cart-validation-warning .cart__warning-text").text()).toContain("Выберите пункт выдачи");
		$("[name=username]").val("Иван Иванов"); $("[name=phone]").val("+79991112233"); $("[name=email]").val("buyer@example.com");
		expect(cart.validateForm(true)).toBe(false);
		expect($("[data-cdek-pickup]").hasClass("_error")).toBe(true);
		cart.cdekPickup.choose("office", null, { code: "MSK1", type: "PVZ", country_code: "RU", city: "Москва", address: "Улица, 1" });
		expect(cart.validateForm(true)).toBe(true);
		expect($("#cart-validation-warning").hasClass("hidden")).toBe(true);
	});
	it("keeps other forms on common field and captcha validation without requiring delivery", () => {
		document.body.innerHTML = '<form id="contact-form"><input name="username" data-required value="Иван"></form>';
		const controller = createFormController();
		const $form = $("#contact-form");
		expect(controller.validateForm($form)).toBe(true);
		$form.append('<div data-smart-captcha></div><input type="hidden" name="smart-token" value="">');
		expect(controller.validateForm($form)).toBe(false);
		$form.find('[name="smart-token"]').val("solved");
		expect(controller.validateForm($form)).toBe(true);
	});
	it("marks delivery only after a payment attempt and clears it once an office is chosen", async () => {
		document.body.innerHTML = readFileSync("templates/_cart.php", "utf8").replace(/<\?php[\s\S]*?\?>/g, "");
		window.formController = createFormController();
		window.formController.validateField = () => true;
		const cart = new DornottCart();
		cart.saveData([sampleItem({ id: 1 })]);
		const $delivery = $("[data-cdek-pickup]");
		$("[name=username]").val("Покупатель").trigger("input");
		expect($delivery.hasClass("_error")).toBe(false);
		const bank = vi.fn(); globalThis.fetch = bank;
		$("[data-cart-checkout]").trigger("click");
		expect($delivery.hasClass("_error")).toBe(true);
		expect($delivery.attr("aria-invalid")).toBe("true");
		expect($("#cart-validation-warning .cart__warning-text").text()).toContain("Выберите пункт выдачи");
		expect(bank).not.toHaveBeenCalled();
		cart.cdekPickup.choose("office", null, { code: "MSK1", type: "PVZ", country_code: "RU", city: "Москва", address: "Улица, 1" });
		expect($delivery.hasClass("_error")).toBe(false);
		window.formController.validateField = () => false;
		await cart.handleSubmit($.Event("submit"));
		expect($delivery.hasClass("_error")).toBe(false);
		expect(bank).not.toHaveBeenCalled();
		expect($("#cart-validation-warning .cart__warning-text").text()).toContain("получателя");
	});
	it("disables payment and never requests bank Init until a pickup office is selected", async () => {
		document.body.innerHTML = readFileSync("templates/_cart.php", "utf8").replace(/<\?php[\s\S]*?\?>/g, "");
		window.formController = createFormController();
		window.formController.validateField = () => true;
		const cart = new DornottCart();
		cart.saveData([sampleItem({ id: 1 })]);
		const bank = vi.fn(); globalThis.fetch = bank;
		expect($("#checkout-button").prop("disabled")).toBe(true);
		await cart.handleSubmit($.Event("submit"));
		expect(bank).not.toHaveBeenCalled();
		cart.cdekPickup.choose("office", null, { code: "MSK1", type: "PVZ", country_code: "RU", city: "Москва", address: "Улица, 1" });
		expect($("#checkout-button").prop("disabled")).toBe(false);
		expect($("#cart-validation-warning").hasClass("hidden")).toBe(true);
	});
	it("preloads the delivery map on cart opening only when products are present", async () => {
		document.body.innerHTML = readFileSync("templates/_cart.php", "utf8").replace(/<\?php[\s\S]*?\?>/g, "");
		window.dornott_ajax = { cdek: { mapsKey: "test-key", servicePath: "/unused" } };
		window.formController = createFormController();
		const cart = new DornottCart();
		let loads = 0, mounts = 0;
		cart.cdekPickup.loader = async () => { loads++; return class { constructor() { mounts++; } }; };
		await cart.preloadDelivery();
		expect(loads).toBe(0);
		localStorage.setItem(CART_STORAGE_KEY, JSON.stringify([{ id: 1, quantity: 1 }]));
		await cart.preloadDelivery(); await cart.preloadDelivery();
		expect(loads).toBe(1); expect(mounts).toBe(1);
		localStorage.removeItem(CART_STORAGE_KEY);
		await cart.preloadDelivery();
		expect(loads).toBe(1);
	});
	beforeEach(() => {
		localStorage.clear();
	});

	it("adds and removes catalog items", () => {
		const cart = mountCart();
		$(".product-card .toggle-to-cart-button").trigger("click");

		expect(cart.getData()).toHaveLength(1);
		expect(cart.getData()[0]).toMatchObject({
			id: 10,
			name: "Карточка товара",
			sku: "CARD-SKU",
			price: 1500,
			regular_price: 2000,
			quantity: 1,
		});
		expect($(".cart-quantity").text()).toBe("1");

		$(".product-card .toggle-to-cart-button").trigger("click");
		expect(cart.getData()).toHaveLength(0);
		expect($(".cart-quantity").length).toBe(0);
		expect($("#cart-empty-state").css("display")).not.toBe("none");
	});

	it("reads name and sku from the product page", () => {
		const cart = mountCart();
		$(".product .toggle-to-cart-button").trigger("click");

		expect(cart.getData()[0]).toMatchObject({
			id: 20,
			name: "Страница товара",
			sku: "PAGE-SKU",
			image: "/img/page.jpg",
			quantity: 2,
		});
	});

	it("keeps string and numeric ids as the same line item", () => {
		const cart = mountCart();
		cart.addItem(sampleItem({ id: "5" }));
		cart.removeItem(5);
		expect(cart.getData()).toHaveLength(0);
	});

	it("does not throw on corrupted storage", () => {
		localStorage.setItem(CART_STORAGE_KEY, "{broken");
		const cart = mountCart();
		expect(cart.getData()).toEqual([]);
	});

	it("toggles select-all and syncs the master checkbox", () => {
		const cart = mountCart();
		cart.saveData([sampleItem({ id: 1 }), sampleItem({ id: 2, name: "Товар 2" })]);

		expect($('input[name="select_all"]').prop("checked")).toBe(true);
		expect(checkedIds()).toEqual(["1", "2"]);

		$('input[name="select_all"]').prop("checked", false).trigger("change");
		expect(checkedIds()).toEqual([]);

		$(".cart__item").first().find(".checkbox__input").prop("checked", true).trigger("change");
		expect($('input[name="select_all"]').prop("checked")).toBe(false);

		$(".cart__item").last().find(".checkbox__input").prop("checked", true).trigger("change");
		expect($('input[name="select_all"]').prop("checked")).toBe(true);
	});

	it("preserves checkbox selection after quantity and delivery changes", () => {
		const cart = mountCart();
		cart.saveData([sampleItem({ id: 1 }), sampleItem({ id: 2, name: "Товар 2", quantity: 1 })]);

		$(".cart__item[data-id='1'] .checkbox__input").prop("checked", false).trigger("change");
		expect($('input[name="select_all"]').prop("checked")).toBe(false);

		$(".cart__item[data-id='2'] .quantity-block__up").trigger("click");
		expect(cart.getData().find((item) => idsEqual(item.id, 2)).quantity).toBe(2);
		expect(checkedIds()).toEqual(["2"]);
		expect($(".cart__item[data-id='1'] .checkbox__input").prop("checked")).toBe(false);

		$('input[name="delivery"][value="cdek"]').prop("checked", true).trigger("change");
		expect(checkedIds()).toEqual(["2"]);
		expect($("#delivery-price").text()).toBe("Бесплатно");
	});

	it("deletes only checked items in one storage write", () => {
		const cart = mountCart();
		cart.saveData([
			sampleItem({ id: 1 }),
			sampleItem({ id: 2, name: "Товар 2" }),
			sampleItem({ id: 3, name: "Товар 3" }),
		]);

		$(".cart__item[data-id='2'] .checkbox__input").prop("checked", false).trigger("change");

		const spy = vi.spyOn(Storage.prototype, "setItem");
		$(".cart__clear").trigger("click");
		const cartWrites = spy.mock.calls.filter(([key]) => key === CART_STORAGE_KEY);
		spy.mockRestore();

		expect(cart.getData().map((item) => item.id)).toEqual([2]);
		expect(cartWrites).toHaveLength(1);
		expect($(".tooltip").text()).toBe("Выбранные товары удалены");
	});

	it("does not show a tooltip when nothing is selected for deletion", () => {
		const cart = mountCart();
		cart.saveData([sampleItem({ id: 1 }), sampleItem({ id: 2, name: "Товар 2" })]);
		$('input[name="select_all"]').prop("checked", false).trigger("change");

		$(".cart__clear").trigger("click");

		expect(cart.getData()).toHaveLength(2);
		expect($(".tooltip").length).toBe(0);
	});

	it("escapes html when rendering cart items", () => {
		const cart = mountCart();
		cart.addItem(
			sampleItem({
				id: 99,
				name: '<img src=x onerror=alert(1)>',
				sku: "<script>alert(1)</script>",
				image: "javascript:alert(1)",
			}),
		);

		expect($(".cart__item-name").html()).toContain("&lt;img");
		expect($(".cart__item-sku").html()).toContain("&lt;script&gt;");
		expect($(".cart__item-thumb img").attr("src")).toBe("");
	});

	it("syncs product-page quantity into storage when the item is already in the cart", () => {
		const cart = mountCart();
		$(".product .toggle-to-cart-button").trigger("click");
		expect(cart.getData()[0].quantity).toBe(2);

		$(".product .quantity-block__up").trigger("click");
		expect(cart.getData()[0].quantity).toBe(3);
		expect($(".product .toggle-to-cart-button").attr("data-quantity")).toBe("3");
	});

	it("clears the badge and list after storage is wiped", () => {
		const cart = mountCart();
		cart.saveData([sampleItem({ id: 1, quantity: 2 })]);
		expect($(".cart-quantity").text()).toBe("2");

		localStorage.removeItem(CART_STORAGE_KEY);
		cart.updateInterface();

		expect($(".cart__item").length).toBe(0);
		expect($(".cart-quantity").length).toBe(0);
		expect($("#cart-empty-state").css("display")).not.toBe("none");
	});

	it("includes nonce header when submitting payment", async () => {
		window.dornott_ajax = { nonce: "abc123", ajax_url: "/wp-admin/admin-ajax.php" };
		window.formController = Object.assign(createFormController(), {
			selectors: { errorClass: "_error" },
			validateField: () => true,
			isCaptchaSolved: () => true,
			resetCaptcha: () => {},
		});

		const cart = mountCart();
		cart.saveData([sampleItem({ id: 1 })]);
		$('input[name="delivery"][value="cdek"]').prop("checked", true);
		cart.$form.append('<input type="hidden" name="cdek_pvz_code" value="MSK1">');

		const fetchMock = vi.fn().mockResolvedValue({
			json: async () => ({ success: false, data: { message: "stop" } }),
		});
		globalThis.fetch = fetchMock;

		await cart.handleSubmit($.Event("submit"));

		expect(fetchMock).toHaveBeenCalled();
		expect(fetchMock.mock.calls[0][1].headers["X-WP-Nonce"]).toBe("abc123");
		const payload = JSON.parse(fetchMock.mock.calls[0][1].body);
		expect(payload.items).toHaveLength(1);
		expect(payload.totals.deliveryPrice).toBe(0);
		expect($("#cart-validation-warning").hasClass("hidden")).toBe(false);
		expect($("#cart-validation-warning .cart__warning-text").text()).toBe("stop");
	});
});
