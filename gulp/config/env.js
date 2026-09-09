import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(__dirname, '../..');
const envPath = path.join(projectRoot, '.env');

function loadEnvFile() {
	const vars = {};
	if (!fs.existsSync(envPath)) {
		return vars;
	}
	const content = fs.readFileSync(envPath, 'utf8');
	content.split('\n').forEach((line) => {
		const trimmed = line.trim();
		if (!trimmed || trimmed.startsWith('#')) {
			return;
		}
		const eq = trimmed.indexOf('=');
		if (eq === -1) {
			return;
		}
		const key = trimmed.slice(0, eq).trim();
		let value = trimmed.slice(eq + 1).trim();
		if (
			(value.startsWith('"') && value.endsWith('"')) ||
			(value.startsWith("'") && value.endsWith("'"))
		) {
			value = value.slice(1, -1);
		}
		vars[key] = value;
	});
	return vars;
}

function pick(key, fallback = '') {
	return process.env[key] || fileEnv[key] || fallback;
}

const fileEnv = loadEnvFile();

function getThemeAssetsRoute(remotePath) {
	const match = remotePath.match(/\/wp-content\/themes\/[^/]+/);
	return match ? `${match[0]}/assets` : '/assets';
}

const ftpRemotePath = pick(
	'FTP_REMOTE_PATH',
	'/dornott.ru/public_html/wp-content/themes/dornott-theme'
);

export const env = {
	WP_PROXY_URL: pick('WP_PROXY_URL', 'https://dornott.ru'),
	FTP_HOST: pick('FTP_HOST'),
	FTP_USER: pick('FTP_USER'),
	FTP_PASSWORD: pick('FTP_PASSWORD'),
	FTP_PORT: Number(pick('FTP_PORT', '21')),
	FTP_REMOTE_PATH: ftpRemotePath,
	THEME_ASSETS_ROUTE: getThemeAssetsRoute(ftpRemotePath),
};
