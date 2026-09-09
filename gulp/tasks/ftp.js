import path from 'path';
import gulp from 'gulp';
import { glob } from 'glob';
import {
	assetsDeployGlobs,
	createFtpConnection,
	deployGlobs,
	ftpEnv,
	isFtpConfigured,
} from '../config/ftp.js';

const DEBOUNCE_MS = 400;
let pendingPaths = new Set();
let debounceTimer = null;
let deployQueue = Promise.resolve();

function log(message) {
	console.log(`[FTP] ${message}`);
}

function toPosix(filePath) {
	return filePath.replace(/\\/g, '/');
}

function remoteFilePath(localPath) {
	return toPosix(path.posix.join(ftpEnv.FTP_REMOTE_PATH, toPosix(localPath)));
}

async function uploadFiles(localPaths, label) {
	const client = await createFtpConnection();
	if (!client) {
		log('Credentials not configured, skipping deploy');
		return;
	}

	const uniquePaths = [...new Set(localPaths.map(toPosix))].filter(Boolean);
	if (!uniquePaths.length) {
		client.close();
		return;
	}

	if (label) {
		log(label);
	}

	log(`Uploading ${uniquePaths.length} file(s)...`);

	try {
		for (const localPath of uniquePaths) {
			const remotePath = remoteFilePath(localPath);
			const remoteDir = path.posix.dirname(remotePath);
			await client.ensureDir(remoteDir);
			await client.uploadFrom(localPath, path.posix.basename(remotePath));
			log(`↑ ${localPath}`);
		}
	} finally {
		client.close();
	}
}

async function resolveGlobs(globs) {
	const patterns = Array.isArray(globs) ? globs : [globs];
	const matches = await Promise.all(
		patterns.map((pattern) =>
			glob(pattern, {
				nodir: true,
				dot: false,
				windowsPathsNoEscape: true,
			})
		)
	);
	return [...new Set(matches.flat().map(toPosix))];
}

const MAX_DEPLOY_ATTEMPTS = 3;

async function deployToFtp(globs, label, attempt = 1) {
	if (!isFtpConfigured()) {
		return;
	}

	try {
		const files = await resolveGlobs(globs);
		await uploadFiles(files, attempt === 1 ? label : undefined);
	} catch (error) {
		if (attempt >= MAX_DEPLOY_ATTEMPTS) {
			throw error;
		}

		log(`Retry ${attempt}/${MAX_DEPLOY_ATTEMPTS - 1}: ${error.message}`);
		await new Promise((resolve) => setTimeout(resolve, 1500 * attempt));
		await deployToFtp(globs, label, attempt + 1);
	}
}

function reloadBrowserIfActive() {
	if (app.plugins.browsersync.active) {
		app.plugins.browsersync.reload();
	}
}

async function uploadPaths(paths) {
	await uploadFiles(paths);
	reloadBrowserIfActive();
}

function flushPendingUploads() {
	const paths = [...pendingPaths];
	pendingPaths.clear();
	debounceTimer = null;

	if (!paths.length) {
		return Promise.resolve();
	}

	deployQueue = deployQueue.then(() => uploadPaths(paths));
	return deployQueue;
}

export function queueFtpDeploy(paths) {
	if (!app.isDev || !isFtpConfigured()) {
		return Promise.resolve();
	}

	const normalized = (Array.isArray(paths) ? paths : [paths]).map(toPosix);

	normalized.forEach((filePath) => pendingPaths.add(filePath));

	if (debounceTimer) {
		clearTimeout(debounceTimer);
	}

	return new Promise((resolve) => {
		debounceTimer = setTimeout(() => {
			flushPendingUploads().then(resolve);
		}, DEBOUNCE_MS);
	});
}

export function ftpDeployAll() {
	if (!isFtpConfigured()) {
		log('Credentials not configured, skipping full deploy');
		return Promise.resolve();
	}

	log(`Full deploy to ${ftpEnv.FTP_REMOTE_PATH}`);

	return deployToFtp(deployGlobs).then(() => {
		log('Full deploy complete');
	});
}

export function ftpDeployAssets() {
	if (!isFtpConfigured()) {
		log('Credentials not configured, skipping assets deploy');
		return Promise.resolve();
	}

	log(`Assets deploy to ${ftpEnv.FTP_REMOTE_PATH}`);

	return deployToFtp(assetsDeployGlobs).then(() => {
		log('Assets deploy complete');
	});
}

export function ftpDeployChanged(filePath) {
	return queueFtpDeploy([filePath]);
}

export function ftpDeployGlobs(globs, { reload = false } = {}) {
	if (!app.isDev || !isFtpConfigured()) {
		return (done) => done();
	}

	return () =>
		deployToFtp(globs).then(() => {
			if (reload) {
				reloadBrowserIfActive();
			}
		});
}

export function withFtpDeploy(task, globs, options = {}) {
	return gulp.series(task, ftpDeployGlobs(globs, options));
}
