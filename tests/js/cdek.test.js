import { describe, it, expect, afterEach, vi } from "vitest";
import $ from "jquery";
import { readFileSync } from "node:fs";
import { createFormController } from "./setup.js";
import { DornottCart } from "../../src/js/modules/cart.js";

const template = readFileSync("templates/_cart.php", "utf8");
let modal;
const tick = () => new Promise(resolve => setTimeout(resolve, 0));

function mount(loader, useDefaultDialog = false) {
	document.body.innerHTML = template.replace(/<\?php[\s\S]*?\?>/g, "");
	window.formController = createFormController();
	$("[name=username]").val("Иван Иванов"); $("[name=phone]").val("+79991112233"); $("[name=email]").val("buyer@example.com");
	modal = null;
	// Only the external window manager is substituted; the actual template and controller run.
	const showDialog = options => {
		const node = document.getElementById("cdek-pickup-dialog");
		if (!node) throw new Error("Map dialog missing");
		const placeholder = document.createComment("dialog home");
		node.before(placeholder);
		const overlay = document.createElement("div");
		document.body.append(overlay); overlay.append(node);
		modal = { options, closed: false, close() {
			options.on.close(); this.closed = true;
			placeholder.replaceWith(node); overlay.remove(); options.on.destroy();
		} };
		queueMicrotask(() => options.on.ready());
		return modal;
	};
	if (useDefaultDialog) window.Fancybox = { show: (slides, options) => { showDialog(options); }, getInstance: () => modal };
	window.dornott_ajax = { cdek: { mapsKey: "public-key", servicePath: "/cdek-widget.php" } };
	const cart = new DornottCart();
	const pickup = cart.cdekPickup;
	if (loader) pickup.loader = loader;
	if (!useDefaultDialog) pickup.showDialog = showDialog;
	return pickup;
}
afterEach(() => { vi.useRealTimers(); delete window.CDEKWidget; delete window.Fancybox; });
const office = { code: "MSK1", city: "Москва", address: "Улица, 1", country_code: "RU", type: "PVZ" };

describe("CDEK pickup", () => {
	it("opens immediately during SDK loading and remains busy until the widget signals readiness", async () => {
		let resolveLoader, options;
		const pickup = mount(() => new Promise(resolve => { resolveLoader = resolve; }));
		const opening = pickup.open(); await tick();
		expect(modal.closed).toBe(false);
		expect($("[data-cdek-open]").prop("disabled")).toBe(false);
		expect($("#cdek-pvz-map").attr("aria-busy")).toBe("true");
		resolveLoader(class { constructor(config) { options = config; } });
		await opening;
		expect($("#cdek-pvz-map").attr("aria-busy")).toBe("true");
		options.onReady(); await tick();
		expect($("#cdek-pvz-map").attr("aria-busy")).toBe("false");
	});
	it("can open a preloaded but initializing widget without resetting its unfinished state", async () => {
		let options, ready = false;
		const pickup = mount(async () => class {
			constructor(config) { options = config; }
			clearSelection() { if (!ready) throw Error("SDK not ready"); }
		});
		await pickup.preload(); await pickup.open();
		expect(modal.closed).toBe(false);
		expect($("#cdek-pvz-map").attr("aria-busy")).toBe("true");
		modal.close(); await pickup.open();
		ready = true; options.onReady(); await tick();
		expect(modal.closed).toBe(false);
		expect($("#cdek-pvz-map").attr("aria-busy")).toBe("false");
	});
	it("reenables the chooser if the window manager never signals readiness and allows a retry", async () => {
		vi.useFakeTimers();
		const pickup = mount(async () => class {});
		const normalDialog = pickup.showDialog;
		pickup.showDialog = () => ({ close() {} });
		const opening = pickup.open();
		await vi.advanceTimersByTimeAsync(10000);
		expect($("[data-cdek-open]").prop("disabled")).toBe(false);
		expect($("#cart-validation-warning .cart__warning-text").text()).toContain("Не удалось");
		await opening;
		pickup.showDialog = normalDialog;
		const retry = pickup.open(); await vi.advanceTimersByTimeAsync(0); await retry;
		expect(modal.closed).toBe(false);
	});
	it("preinitializes a hidden map without opening a modal or committing an office", async () => {
		let mounts = 0;
		const pickup = mount(async () => class {
			constructor(config) { mounts++; config.onChoose("office", null, office); }
		});
		await pickup.preload(); await pickup.preload();
		expect(mounts).toBe(1);
		expect(modal).toBeNull();
		expect($("#cdek-pickup-dialog").hasClass("cdek-dialog--preloading")).toBe(true);
		expect($("#cdek-pickup-dialog").attr("aria-hidden")).toBe("true");
		expect($("[name=cdek_pvz_code]").val()).toBe("");
		expect($("[data-cdek-open]").prop("disabled")).toBe(false);
	});
	it("shares pending preinitialization with an immediate map opening", async () => {
		let resolveLoader, loads = 0, mounts = 0;
		const pickup = mount(() => { loads++; return new Promise(resolve => { resolveLoader = resolve; }); });
		const preloading = pickup.preload();
		const opening = pickup.open(); await tick();
		resolveLoader(class { constructor() { mounts++; } });
		await Promise.all([preloading, opening]);
		expect(loads).toBe(1); expect(mounts).toBe(1);
		expect(modal.closed).toBe(false);
		expect($("#cdek-pickup-dialog").hasClass("cdek-dialog--preloading")).toBe(false);
		expect($("#cdek-pickup-dialog").attr("aria-hidden")).toBeUndefined();
	});
	it("silently recovers from a failed preload on explicit opening", async () => {
		let loads = 0;
		const pickup = mount(async () => { if (++loads === 1) throw Error("offline"); return class {}; });
		await pickup.preload();
		expect($("#cart-validation-warning").hasClass("hidden")).toBe(true);
		await pickup.open();
		expect(modal.closed).toBe(false);
		expect(loads).toBe(2);
	});
	it("restores measurable background layout if the map is closed before preloading finishes", async () => {
		let resolveLoader;
		const pickup = mount(() => new Promise(resolve => { resolveLoader = resolve; }));
		const preloading = pickup.preload();
		const opening = pickup.open(); await tick(); modal.close();
		let backgroundAtMount = false;
		resolveLoader(class { constructor() { backgroundAtMount = $("#cdek-pickup-dialog").hasClass("cdek-dialog--preloading"); } });
		await Promise.all([preloading, opening]);
		expect(backgroundAtMount).toBe(true);
		expect($("#cdek-pickup-dialog").attr("aria-hidden")).toBe("true");
	});
	it("closes the current Fancybox after confirmation when show returns no instance", async () => {
		let options;
		const pickup = mount(async () => class { constructor(config) { options = config; } }, true);
		await pickup.open();
		options.onChoose("office", null, office);
		expect(modal.closed).toBe(true);
		expect($("[name=cdek_pvz_code]").val()).toBe("MSK1");
	});
	it("prevents checkout without PVZ and allows it after an office is selected", () => {
		const pickup = mount();
		expect(window.formController.validateForm($("#cart-form"), { showDeliveryError: true })).toBe(false);
		expect($("#cart-validation-warning .cart__warning-text").text()).toContain("Выберите");
		pickup.choose("office", null, office);
		expect(window.formController.validateForm($("#cart-form"), { showDeliveryError: true })).toBe(true);
		expect($("[name=cdek_pvz_code]").val()).toBe("MSK1");
		expect($("[name=address]").val()).toBe("Улица, 1");
	});
	it("changes selected office and displays addresses as text", () => {
		const pickup = mount();
		pickup.choose("office", null, office);
		pickup.choose("office", null, { ...office, code: "MSK2", address: "<img src=x onerror=alert(1)>" });
		expect($("[name=cdek_pvz_code]").val()).toBe("MSK2");
		expect($("[data-cdek-address] img")).toHaveLength(0);
		expect($("[data-cdek-selection]").text()).toContain("<img");
	});
	it("keeps the selected address while reopening the map and replaces it after a new choice", async () => {
		const pickup = mount(async () => class {});
		expect($("[data-cdek-selection]").prop("hidden")).toBe(true);
		await pickup.open();
		pickup.choose("office", null, { ...office, work_time: "Пн–Вс: 10:00–21:00" });
		expect($("[data-cdek-selection]").prop("hidden")).toBe(false);
		expect($("[data-cdek-select]").prop("hidden")).toBe(true);
		expect($("[data-cdek-address]").text()).toBe("Москва, Улица, 1");
		expect($("[data-cdek-hours]").text()).toBe("Пн–Вс: 10:00–21:00");
		expect($("[data-cdek-edit]").attr("aria-expanded")).toBe("false");
		$("[data-cdek-edit]").trigger("click");
		await tick();
		expect(modal.closed).toBe(false);
		expect($("[data-cdek-edit]").attr("aria-expanded")).toBe("true");
		expect($("[name=cdek_pvz_code]").val()).toBe("MSK1");
		pickup.choose("office", null, { ...office, code: "MSK2", address: "Другая улица, 2" });
		expect(modal.closed).toBe(true);
		expect($("[data-cdek-address]").text()).toBe("Москва, Другая улица, 2");
		expect($("[data-cdek-hours]").prop("hidden")).toBe(true);
		expect($("[name=cdek_pvz_code]").val()).toBe("MSK2");
	});
	it.each([
		["door", office], ["office", { ...office, type: "POSTAMAT" }],
		["office", { ...office, country_code: "KZ" }], ["office", null],
	])("rejects unsupported selection %s", (type, target) => {
		const pickup = mount(); pickup.choose(type, null, target);
		expect(window.formController.validateForm($("#cart-form"), { showDeliveryError: true })).toBe(false);
		expect($("[name=cdek_pvz_code]").val()).toBe("");
	});
	it("keeps the cart and confirmed office unchanged when the map is dismissed", async () => {
		let options;
		const pickup = mount(async () => class { constructor(config) { options = config; } });
		pickup.choose("office", null, office);
		$("[name=username]").val("Покупатель");
		$("[name=message]").val("Комментарий");
		await pickup.open();
		expect(modal.options.closeExisting).toBe(false);
		expect($("#cart-form").find("#cdek-pvz-map")).toHaveLength(0);
		modal.close();
		// A late callback from a dismissed window cannot silently replace the address.
		options.onChoose("office", null, { ...office, code: "MSK2" });
		expect($("[name=cdek_pvz_code]").val()).toBe("MSK1");
		expect($("[name=username]").val()).toBe("Покупатель");
		expect($("[name=message]").val()).toBe("Комментарий");
		expect($("[data-cdek-edit]").prop("disabled")).toBe(false);
	});
	it("does not mount a widget when its script finishes after cancellation", async () => {
		let resolveLoader;
		let mounts = 0;
		const pickup = mount(() => new Promise(resolve => { resolveLoader = resolve; }));
		const opening = pickup.open(); await tick(); modal.close();
		resolveLoader(class { constructor() { mounts++; } });
		await opening;
		expect(mounts).toBe(0);
		expect($("[name=cdek_pvz_code]").val()).toBe("");
		expect($("[data-cdek-open]").prop("disabled")).toBe(false);
	});
	it("closes using the in-header button and returns focus to the initial chooser", async () => {
		const pickup = mount(async () => class {});
		await pickup.open();
		$("[data-cdek-close]").trigger("click"); await tick();
		expect(modal.closed).toBe(true);
		expect(document.activeElement).toBe($("[data-cdek-select] [data-cdek-open]")[0]);
		expect(window.formController.validateForm($("#cart-form"), { showDeliveryError: true })).toBe(false);
	});
	it("reuses the widget and restores the committed office when reopened", async () => {
		const selected = [];
		let mounts = 0;
		const pickup = mount(async () => class {
			constructor(config) { mounts++; queueMicrotask(() => config.onReady()); }
			clearSelection() { selected.push(null); }
			selectOffice(code) { selected.push(code); }
		});
		await pickup.open(); pickup.choose("office", null, office);
		await pickup.open(); modal.close();
		await pickup.open();
		expect(mounts).toBe(1);
		expect(selected).toEqual([null, "MSK1", null, "MSK1"]);
	});
	it("preserves a committed office after an invalid replacement callback", () => {
		const pickup = mount(); pickup.choose("office", null, office);
		pickup.choose("office", null, { ...office, country_code: "KZ" });
		expect($("[name=cdek_pvz_code]").val()).toBe("MSK1");
		expect($("[data-cdek-address]").text()).toBe("Москва, Улица, 1");
	});
	it("shows a recoverable error when widget loading fails", async () => {
		const pickup = mount(async () => { throw new Error("offline"); });
		await pickup.open();
		expect($("#cart-validation-warning .cart__warning-text").text()).toContain("Не удалось");
		expect($("[data-cdek-open]").prop("disabled")).toBe(false);
		expect(window.formController.validateForm($("#cart-form"), { showDeliveryError: true })).toBe(false);
	});
	it("opens an office-only widget without a priced tariff", async () => {
		let options;
		window.CDEKWidget = class { constructor(config) { options = config; } };
		const pickup = mount(async () => window.CDEKWidget);
		await pickup.open();
		expect(modal.closed).toBe(false);
		expect(options.hideDeliveryOptions.door).toBe(true);
		expect(options.forceFilters.type).toBe("PVZ");
		expect(options.from).toBeUndefined();
		expect(options.goods).toBeUndefined();
		options.onChoose("office", null, office);
		expect(modal.closed).toBe(true);
		expect(window.formController.validateForm($("#cart-form"), { showDeliveryError: true })).toBe(true);
	});
	it("labels only the widget confirmation button as Deliver here, including rerenders", async () => {
		const pickup = mount(async () => class {}); await pickup.open();
		$("#cdek-pvz-map").html('<button>Выбрать</button><button>Фильтры</button>');
		await tick();
		expect($("#cdek-pvz-map button").first().text()).toBe("Доставить сюда");
		expect($("#cdek-pvz-map button").last().text()).toBe("Фильтры");
		$("#cdek-pvz-map button").first().text("Выбран"); await tick();
		expect($("#cdek-pvz-map button").first().text()).toBe("Доставить сюда");
	});
});
