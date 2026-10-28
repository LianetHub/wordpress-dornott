import $ from "jquery";

let widgetScript;
function loadWidget() {
	if (window.CDEKWidget) return Promise.resolve(window.CDEKWidget);
	if (widgetScript) return widgetScript;
	widgetScript = new Promise((resolve, reject) => {
		const script = document.createElement("script");
		script.src = "https://cdn.jsdelivr.net/npm/@cdek-it/widget@3.13.1/dist/cdek-widget.umd.js";
		const timer = setTimeout(() => fail(), 20000);
		const fail = () => { clearTimeout(timer); script.remove(); widgetScript = null; reject(new Error("Widget unavailable")); };
		script.onload = () => { clearTimeout(timer); window.CDEKWidget ? resolve(window.CDEKWidget) : fail(); };
		script.onerror = fail;
		document.head.appendChild(script);
	});
	return widgetScript;
}

export class CdekPickup {
	constructor($form, config = {}, loader = loadWidget, showDialog = (options, trigger) => {
		window.Fancybox.show([{ src: "#cdek-pickup-dialog", type: "inline", triggerEl: trigger }], options);
		const instance = window.Fancybox.getInstance();
		if (instance?.getSlide && instance.getSlide()?.src !== "#cdek-pickup-dialog") throw new Error("Map dialog did not open");
		return instance;
	}) {
		this.$form = $form;
		this.config = config;
		this.loader = loader;
		this.showDialog = showDialog;
		this.$root = $form.find("[data-cdek-pickup]");
		this.$dialog = $("#cdek-pickup-dialog");
		this.$root.on("click.dornottCdek", "[data-cdek-open]", event => this.open(event.currentTarget));
		this.$dialog.on("click.dornottCdek", "[data-cdek-close]", () => this.modal?.close());
	}

	choose(type, tariff, target) {
		if (type !== "office" || !target || target.type !== "PVZ" || target.country_code !== "RU" || !/^[A-Z0-9_-]{2,30}$/.test(target.code)) {
			this.$form.trigger("checkout:error", ["Выберите пункт выдачи СДЭК в России."]);
			return;
		}
		this.$form.find('[name="cdek_pvz_code"]').val(target.code);
		this.$form.find('[name="city"]').val(target.city);
		this.$form.find('[name="address"]').val(target.address);
		this.selected = { code: target.code };
		this.$root.find("[data-cdek-address]").text([target.city, target.address].filter(Boolean).join(", "));
		this.$root.find("[data-cdek-hours]").text(target.work_time || "").prop("hidden", !target.work_time);
		this.$root.find("[data-cdek-title]").text("Пункт выдачи СДЭК");
		this.$root.find("[data-cdek-subtitle]").text("Пункт выдачи выбран");
		this.$root.find("[data-cdek-selection]").prop("hidden", false);
		this.$root.find("[data-cdek-select]").prop("hidden", true);
		this.$root.find("[data-cdek-open]").attr("aria-expanded", "false");
		this.$form.trigger("cdek:change");
		this.modal?.close();
	}

	releasePreloadLayout() {
		this.$dialog.removeClass("cdek-dialog--preloading").removeAttr("aria-hidden").prop("inert", false);
	}

	preparePreloadLayout() {
		this.$dialog.addClass("cdek-dialog--preloading").attr("aria-hidden", "true").prop("inert", true);
	}

	setMapBusy(loading) {
		this.$dialog.find("#cdek-pvz-map").attr("aria-busy", String(loading));
	}

	revealReadyWidget() {
		const session = this.session;
		if (!session?.active || !this.widgetReady || !this.widget) return;
		if (session.restoreSelection && !session.selectionRestored) {
			session.selectionRestored = true;
			this.widget.clearSelection?.();
			if (this.selected) this.widget.selectOffice?.(this.selected.code);
		}
		this.setMapBusy(false);
		window.dispatchEvent(new Event("resize"));
	}

	preload() {
		if (!this.config.mapsKey || !this.config.servicePath || !this.$dialog.length) return Promise.resolve(false);
		if (this.widget) return Promise.resolve(true);
		this.preloadRequested = true;
		if (!this.session) {
			// A measurable off-screen container lets Yandex initialize before the modal opens.
			this.preparePreloadLayout();
		}
		return this.ensureWidget().then(() => true, () => {
			if (!this.session) this.releasePreloadLayout();
			return false; // Background failure is retried on the next explicit opening.
		});
	}

	ensureWidget() {
		if (this.widget) return Promise.resolve(this.widget);
		if (this.widgetPromise) return this.widgetPromise;
		this.widgetPromise = (async () => {
			const Widget = await this.loader();
			if (!this.preloadRequested && !this.session?.active) return null;
			if (!this.session?.active) this.preparePreloadLayout();
			this.widget = new Widget({
				root: "cdek-pvz-map", apiKey: this.config.mapsKey, servicePath: this.config.servicePath,
				defaultLocation: [37.6176, 55.7558], lang: "rus", popup: false, canChoose: true,
				hideDeliveryOptions: { door: true, office: false },
				forceFilters: { type: "PVZ" }, hideFilters: { type: true },
				selected: { office: this.selected?.code || null },
				onReady: () => {
					this.widgetReady = true;
					// Some loaders call onReady during construction, before assignment completes.
					queueMicrotask(() => this.revealReadyWidget());
				},
				onChoose: (type, tariff, target) => {
					if (this.session?.active) this.choose(type, tariff, target);
				},
			});
			return this.widget;
		})().finally(() => { this.widgetPromise = null; });
		return this.widgetPromise;
	}

	// The pinned widget has no public label override. Keep its native confirmation
	// action and translate only that button, without relying on private Vue state.
	observeConfirmation() {
		const map = this.$dialog.find("#cdek-pvz-map")[0];
		const translate = () => {
			map.querySelectorAll("button").forEach(button => {
				if (["Выбрать", "Выбран"].includes(button.textContent.trim())) {
					button.textContent = "Доставить сюда";
					button.setAttribute("data-cdek-confirm", "");
				}
			});
		};
		this.labelObserver?.disconnect();
		this.labelObserver = new MutationObserver(translate);
		this.labelObserver.observe(map, { childList: true, subtree: true, characterData: true });
		translate();
	}

	async open(trigger) {
		if (this.session) return;
		const session = { active: true };
		this.session = session;
		const $button = this.$root.find("[data-cdek-open]");
		let readyTimer;
		try {
			if (!this.config.mapsKey || !this.config.servicePath) throw new Error("Widget not configured");
			if (!this.$dialog.length) throw new Error("Map dialog missing");
			this.releasePreloadLayout();
			this.setMapBusy(!this.widgetReady);
			$button.attr("aria-expanded", "true");
			let ready;
			const visible = new Promise((resolve, reject) => {
				ready = value => { clearTimeout(readyTimer); resolve(value); };
				readyTimer = setTimeout(() => reject(new Error("Map dialog readiness timed out")), 8000);
			});
			this.modal = this.showDialog({
				closeExisting: false, dragToClose: false, placeFocusBack: false, closeButton: false,
				mainClass: "cdek-dialog-shell", theme: "light",
				on: {
					ready: () => ready(true),
					close: () => { session.active = false; ready(false); },
					destroy: () => {
						session.active = false; ready(false);
						if (this.session !== session) return;
						this.session = null; this.modal = null;
						this.labelObserver?.disconnect();
						$button.prop("disabled", false).attr("aria-expanded", "false");
						queueMicrotask(() => this.$root.find(this.selected ? "[data-cdek-edit]" : "[data-cdek-select] [data-cdek-open]").trigger("focus"));
					},
				},
			}, trigger);
			if (!this.modal) { ready(false); throw new Error("Map dialog unavailable"); }
			if (!await visible || !session.active) return;
			session.restoreSelection = Boolean(this.widget);
			await this.ensureWidget();
			if (!session.active) return;
			this.revealReadyWidget();
			this.observeConfirmation();
			window.dispatchEvent(new Event("resize"));
		} catch (error) {
			if (this.session === session) {
				this.$form.trigger("checkout:error", ["Не удалось открыть карту ПВЗ. Попробуйте ещё раз или свяжитесь с нами."]);
				session.active = false;
				const failedModal = this.modal;
				this.session = null; this.modal = null;
				this.labelObserver?.disconnect();
				// A broken readiness callback must not leave either the button or overlay stuck.
				if (failedModal?.destroy) failedModal.destroy();
				else failedModal?.close();
			}
		} finally {
			clearTimeout(readyTimer);
			if (!this.session) $button.prop("disabled", false).attr("aria-expanded", "false");
		}
	}
}
