import { afterEach } from "vitest";
import $ from "jquery";
import { JSDOM } from "jsdom";
import { readFileSync } from "node:fs";

// Run the existing in-page controller itself, without bootstrapping sliders/captcha/network.
export function createFormController() {
	const source = readFileSync("src/js/app.js", "utf8");
	const start = source.indexOf("class FormController {");
	const end = source.indexOf("window.formController = new FormController();", start);
	if (start < 0 || end < 0) throw Error("Existing FormController definition not found");
	const Controller = new Function("$", "window", "document", `return (${source.slice(start, end).trim()});`)($, window, document);
	class UnboundController extends Controller { init() {} }
	// jsdom has no layout; these form controls represent the opened checkout in the browser.
	document.querySelectorAll("[data-required]").forEach(el => { el.getClientRects = () => [{}]; });
	return new UnboundController();
}

globalThis.$ = $;
globalThis.jQuery = $;

// Node 26 exposes its own storage; browser code must use jsdom's origin storage.
const browserStorage = new JSDOM("", { url: "https://dornott.test/" }).window.localStorage;
Object.defineProperty(globalThis, "localStorage", { configurable: true, value: browserStorage });
Object.defineProperty(window, "localStorage", { configurable: true, value: browserStorage });
Object.defineProperty(globalThis, "Storage", { configurable: true, value: browserStorage.constructor });

afterEach(() => {
	$(document).off(".dornottCart");
	$("form").off(".dornottCart");
	localStorage.clear();
	document.body.innerHTML = "";
	delete window.formController;
	delete window.dornott_ajax;
});
