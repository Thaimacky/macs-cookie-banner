// tools/consent-runtime-test.js - Consent-Runtime-Test (Entwicklerwerkzeug, kein Plugin-Code).
//
// Simuliert einen echten Besucher mit Headless-Chrome und protokolliert pro Schritt alle
// Tracking-Requests, Tracking-Cookies und JS-Globals. Aendert nichts an der Website
// (reine Besucher-Aktionen: Seitenaufruf, Maus/Scroll, Klick auf Banner-Buttons).
// Testplan und Erwartungswerte: TESTPLAN_CONSENT_RUNTIME.md.
//
// Voraussetzungen (einmalig, AUSSERHALB des Repositorys - kein npm im Plugin):
//   mkdir %TEMP%\mcb-pw && cd %TEMP%\mcb-pw && npm init -y && npm install playwright-core
//   Google Chrome installiert (Pfad per CHROME_PATH ueberschreibbar).
// Aufruf:
//   set NODE_PATH=%TEMP%\mcb-pw\node_modules
//   node tools\consent-runtime-test.js https://example.ch/ [weitere URLs]
// Optionen (Umgebungsvariablen):
//   WAIT_MS=8000         Wartezeit pro Schritt
//   ABORT_TRACKING=1     Tracking-Requests protokollieren und dann abbrechen (fuer lokale Fixtures)
//   CHROME_PATH=...      Pfad zu chrome.exe
// Wichtig: Schritt 1b simuliert Maus/Scroll OHNE Banner-Klick. Seiten mit WP Rocket
// "Delay JS" laden verzoegerte Skripte erst bei Interaktion - ohne 1b bleibt ein
// Bypass unsichtbar.
// Aufruf: node consent-runtime-test.js <url> [<url> ...]
// Ergebnis: JSON unter %TEMP%/mcb-consent-results/<host>-<ts>.json (RESULTS_DIR ueberschreibt) + Konsolenzusammenfassung.
'use strict';
const { chromium } = require('playwright-core');
const fs = require('fs');
const path = require('path');

const CHROME = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const WAIT = Number(process.env.WAIT_MS || 8000);

const TRACK_HOST = /(googletagmanager\.com|google-analytics\.com|analytics\.google\.com|googleadservices\.com|doubleclick\.net|facebook\.net|facebook\.com|chimpstatic\.com|list-manage\.com|hotjar\.com|clarity\.ms|bing\.com\/action|tiktok\.com|linkedin\.com\/px|snap\.licdn)/i;
const TRACK_AJAX = /admin-ajax\.php.*action=pys_|admin-ajax\.php\?action=pys/i;
const TRACK_COOKIE = /^(_ga|_gid|_gat|_gcl|_fbp|_fbc|fr$|pys|last_pys|pbid|_hj|_clck|_clsk|mailchimp|_uet|_ttp|li_)/i;

const BANNERS = {
	macs: {
		detect: '[data-lscc-root]',
		acceptAll: '[data-lscc-accept-all]',
		necessary: '[data-lscc-necessary]',
		async openSettings(page) {
			// Reopen-Button bzw. Shortcut-Trigger -> Banner mit Einstellungen.
			const trig = await page.$('[data-lscc-reopen]:not([hidden]) , [data-lscc-open-consent-settings]');
			if (trig) { await trig.click({ force: true }); return true; }
			return false;
		},
	},
	nw: {
		detect: '#nw-cookie-root',
		acceptAll: '#nw-accept-all',
		necessary: '#nw-accept-essential',
		async openSettings(page) {
			await page.evaluate(() => { window.location.hash = 'cookie-settings'; });
			return true;
		},
	},
};

function siteUrl(s) { return /^https?:/.test(s) ? s : 'https://' + s + '/'; }

async function interact(page) {
	// Echte Nutzerinteraktion ohne Klick auf den Banner: Maus + Scroll + Touch-aehnlich.
	try {
		for (let i = 0; i < 6; i++) { await page.mouse.move(200 + i * 40, 300 + i * 15, { steps: 3 }); }
		await page.mouse.wheel(0, 600); await page.waitForTimeout(400); await page.mouse.wheel(0, -600);
		await page.keyboard.press('Shift');
	} catch (e) { /* ignore */ }
}

async function snapshot(page, ctx, log, from) {
	const cookies = (await ctx.cookies()).map((c) => ({ name: c.name, domain: c.domain }));
	const globals = await page.evaluate(() => ({
		fbq: typeof window.fbq,
		gtag: typeof window.gtag,
		dataLayer: Array.isArray(window.dataLayer) ? window.dataLayer.length : null,
		google_tag_manager: typeof window.google_tag_manager,
		mcPixel: typeof window.mcPixel,
		pysOptions: typeof window.pysOptions,
		lscc: (function () { try { return localStorage.getItem('lscc_consent'); } catch (e) { return 'n/a'; } })(),
		nw: (function () { try { return localStorage.getItem('neue_webseite_cookie_consent_v1'); } catch (e) { return 'n/a'; } })(),
		gatedScripts: document.querySelectorAll('script[type="text/plain"][data-cookie-category]').length,
		rocketDelayed: document.querySelectorAll('script[type="text/rocketlazyloadscript"]').length,
		bannerVisible: (function () {
			var r = document.querySelector('[data-lscc-root]') || document.getElementById('nw-cookie-banner');
			if (!r) return null; var s = getComputedStyle(r); return !r.hidden && s.display !== 'none' && s.visibility !== 'hidden';
		})(),
	})).catch((e) => ({ error: String(e) }));
	const reqs = log.slice(from);
	return {
		trackingRequests: reqs.filter((r) => r.tracking).map((r) => r.method + ' ' + r.url.slice(0, 180)),
		trackingCookies: cookies.filter((c) => TRACK_COOKIE.test(c.name)).map((c) => c.name + ' @' + c.domain),
		allCookies: cookies.map((c) => c.name),
		globals,
	};
}

async function newCtx(browser) {
	const ctx = await browser.newContext({
		userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36',
		locale: 'de-CH',
		viewport: { width: 1366, height: 860 },
	});
	if (process.env.ABORT_TRACKING === '1') {
		// Lokale Fixtures: Tracking-Requests werden protokolliert (request-Event) und dann abgebrochen,
		// damit keine Testdaten an Google/Meta gehen.
		await ctx.route(TRACK_HOST, (route) => route.abort());
	}
	const page = await ctx.newPage();
	const log = [];
	page.on('request', (r) => {
		const url = r.url();
		const post = r.postData() || '';
		log.push({ url, method: r.method(), tracking: TRACK_HOST.test(url) || TRACK_AJAX.test(url) || (/admin-ajax\.php/.test(url) && /action=pys_/.test(post)) });
	});
	return { ctx, page, log };
}

async function detectBanner(page) {
	if (await page.$(BANNERS.macs.detect)) return 'macs';
	if (await page.$(BANNERS.nw.detect)) return 'nw';
	return null;
}

async function clickBanner(page, selector) {
	await interact(page);
	await page.waitForSelector(selector, { state: 'visible', timeout: 20000 });
	await page.click(selector);
}

async function runSite(browser, site) {
	const url = siteUrl(site);
	const out = { site, url, startedAt: new Date().toISOString(), steps: {} };
	let mark;

	// ---------- Schritt 1: Erstbesuch ohne Zustimmung ----------
	let { ctx, page, log } = await newCtx(browser);
	await page.goto(url, { waitUntil: 'load', timeout: 60000 });
	const kind = await detectBanner(page);
	out.banner = kind;
	const B = BANNERS[kind] || BANNERS.macs;
	await page.waitForTimeout(WAIT);
	out.steps['1a_erstbesuch_ohne_interaktion'] = await snapshot(page, ctx, log, 0);
	mark = log.length;
	await interact(page);
	await page.waitForTimeout(WAIT);
	out.steps['1b_erstbesuch_nach_interaktion'] = await snapshot(page, ctx, log, 0);

	// ---------- Schritt 2: Nur notwendige ----------
	mark = log.length;
	try {
		await clickBanner(page, B.necessary);
		await page.waitForTimeout(WAIT);
		out.steps['2a_nur_notwendige_klick'] = await snapshot(page, ctx, log, 0);
		mark = log.length;
		await page.reload({ waitUntil: 'load' });
		await interact(page);
		await page.waitForTimeout(WAIT);
		out.steps['2b_nur_notwendige_reload'] = await snapshot(page, ctx, log, mark);
	} catch (e) { out.steps['2_error'] = String(e).slice(0, 300); }
	await ctx.close();

	// ---------- Schritt 3: Alle akzeptieren ----------
	({ ctx, page, log } = await newCtx(browser));
	await page.goto(url, { waitUntil: 'load', timeout: 60000 });
	try {
		await clickBanner(page, B.acceptAll);
		mark = 0;
		await page.waitForTimeout(WAIT);
		out.steps['3a_alle_akzeptieren_klick'] = await snapshot(page, ctx, log, 0);
		mark = log.length;
		await page.reload({ waitUntil: 'load' });
		await interact(page);
		await page.waitForTimeout(WAIT);
		out.steps['3b_alle_akzeptieren_reload'] = await snapshot(page, ctx, log, mark);
	} catch (e) { out.steps['3_error'] = String(e).slice(0, 300); }

	// ---------- Schritt 4: Widerruf ----------
	try {
		await interact(page);
		await page.waitForTimeout(1500);
		const opened = await B.openSettings(page);
		out.revokeTriggerFound = opened;
		await page.waitForSelector(B.necessary, { state: 'visible', timeout: 15000 });
		mark = log.length;
		await page.click(B.necessary);
		await page.waitForTimeout(3000);
		out.steps['4a_widerruf_selbe_seite'] = await snapshot(page, ctx, log, mark);
		mark = log.length;
		await page.reload({ waitUntil: 'load' });
		await interact(page);
		await page.waitForTimeout(WAIT);
		out.steps['4b_widerruf_reload'] = await snapshot(page, ctx, log, mark);
	} catch (e) { out.steps['4_error'] = String(e).slice(0, 300); }
	await ctx.close();
	return out;
}

(async () => {
	const sites = process.argv.slice(2);
	if (!sites.length) { console.error('usage: node consent-test.js <site>...'); process.exit(2); }
	const browser = await chromium.launch({ executablePath: CHROME, headless: true, args: ['--disable-blink-features=AutomationControlled'] });
	const resultsDir = process.env.RESULTS_DIR || path.join(require('os').tmpdir(), 'mcb-consent-results');
	fs.mkdirSync(resultsDir, { recursive: true });
	for (const s of sites) {
		let res;
		try { res = await runSite(browser, s); } catch (e) { res = { site: s, fatal: String(e) }; }
		const host = (res.url || s).replace(/^https?:\/\//, '').replace(/[^a-z0-9.-]/gi, '_');
		const file = path.join(resultsDir, host + '-' + Date.now() + '.json');
		fs.writeFileSync(file, JSON.stringify(res, null, 2));
		console.log('\n######## ' + s + '  (Banner: ' + res.banner + ')  -> ' + file);
		if (res.fatal) { console.log('FATAL', res.fatal); continue; }
		for (const [k, v] of Object.entries(res.steps)) {
			if (typeof v === 'string') { console.log('  ' + k + ': ' + v); continue; }
			const uniq = Array.from(new Set(v.trackingRequests.map((u) => u.replace(/\?.*$/, ''))));
			console.log('  ' + k + ': req=' + v.trackingRequests.length + ' cookies=[' + v.trackingCookies.join(', ') + '] fbq=' + v.globals.fbq + ' gtag=' + v.globals.gtag + ' gated=' + v.globals.gatedScripts + ' rocketDelayed=' + v.globals.rocketDelayed + ' banner=' + v.globals.bannerVisible);
			uniq.slice(0, 12).forEach((u) => console.log('      ' + u));
		}
	}
	await browser.close();
})();
