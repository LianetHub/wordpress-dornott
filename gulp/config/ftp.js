import { Client } from 'basic-ftp';
import { env } from './env.js';

export const assetsDeployGlobs = ['assets/**/*.*'];

// Полный деплой (deploy:ftp). PHP и шаблоны в dev — через .vscode/sftp.json.
export const deployGlobs = [
	...assetsDeployGlobs,
	'includes/**/*.*',
	'templates/**/*.*',
	'woocommerce/**/*.*',
	'acf-json/**/*.*',
	'*.php',
	'style.css',
	'screenshot.png',
];

export function isFtpConfigured() {
	return Boolean(env.FTP_HOST && env.FTP_USER && env.FTP_PASSWORD);
}

export async function createFtpConnection() {
	if (!isFtpConfigured()) {
		return null;
	}

	const client = new Client(30_000);
	await client.access({
		host: env.FTP_HOST,
		port: env.FTP_PORT,
		user: env.FTP_USER,
		password: env.FTP_PASSWORD,
		secure: false,
	});
	return client;
}

export { env as ftpEnv };
