import path from 'path';
import { fileURLToPath } from 'url';

import { env } from '../config/env.js';

const projectRoot = path.resolve(
	path.dirname(fileURLToPath(import.meta.url)),
	'../..'
);

export const server = (done) => {
	const proxyTarget = env.WP_PROXY_URL;
	const proxyUrl = new URL(proxyTarget);
	const proxyHost = proxyUrl.host;
	const proxyHostPattern = proxyHost.replace(/\./g, '\\.');
	const localHost = 'localhost:3000';

	app.plugins.browsersync.init(
		{
			proxy: {
				target: proxyTarget,
				proxyReq: [
					(proxyReq) => {
						proxyReq.setHeader('host', proxyHost);
						// Upstream gzip ломает rewriteRules / snippet / serveStatic overlay.
						proxyReq.setHeader('accept-encoding', 'identity');
					},
				],
			},
			// Локальные assets поверх прокси — без ожидания FTP и полного reload.
			serveStatic: [
				{
					route: env.THEME_ASSETS_ROUTE,
					dir: path.join(projectRoot, 'assets'),
				},
			],
			rewriteRules: [
				// WP Fastest Cache: устаревший минифицированный CSS перебивает тему.
				{
					match: /<link[^>]+wp-content\/cache\/wpfc-minified[^>]*>/gi,
					replace: '',
				},
				{
					match: new RegExp(`(?:https?:)?//${proxyHostPattern}`, 'g'),
					replace: `//${localHost}`,
				},
			],
			port: 3000,
			open: 'local',
			notify: true,
			ghostMode: false,
			reloadOnRestart: true,
			reloadDelay: 150,
			reloadDebounce: 400,
		},
		done
	);
};
