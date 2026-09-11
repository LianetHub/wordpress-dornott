import { afterEach } from "vitest";
import $ from "jquery";

globalThis.$ = $;
globalThis.jQuery = $;

afterEach(() => {
	$(document).off(".dornottCart");
	$("form").off(".dornottCart");
	localStorage.clear();
	document.body.innerHTML = "";
	delete window.formController;
	delete window.dornott_ajax;
});
