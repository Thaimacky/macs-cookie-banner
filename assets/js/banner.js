(function () {
	'use strict';

	var settings = window.lsccSettings || {};
	var storageKey = settings.storageKey || 'lscc_consent';
	var cookieName = settings.cookieName || 'lscc_consent';
	var consentVersion = settings.consentVersion || 2;
	var lifetimeDays = (typeof settings.lifetimeDays === 'number' && settings.lifetimeDays > 0) ? settings.lifetimeDays : 180;
	var debugEnabled = Boolean(settings.debug);
	var allowedCategories = ['necessary', 'statistics', 'marketing', 'external_media'];
	var categories = Array.isArray(settings.categories) ? settings.categories.filter(isAllowedCategory) : allowedCategories.slice();

	if (categories.indexOf('necessary') === -1) {
		categories.unshift('necessary');
	}

	function isAllowedCategory(category) {
		return allowedCategories.indexOf(category) !== -1;
	}

	function hasOwn(object, key) {
		return Object.prototype.hasOwnProperty.call(object, key);
	}

	function debugLog() {
		if (!debugEnabled || !window.console || typeof window.console.log !== 'function') {
			return;
		}

		window.console.log.apply(window.console, arguments);
	}

	function isValidConsent(consent) {
		if (!consent || typeof consent !== 'object') {
			return false;
		}
		if (String(consent.version) !== String(consentVersion)) {
			return false;
		}
		if (!consent.categories || typeof consent.categories !== 'object') {
			return false;
		}

		var now = Date.now();
		var ttlMs = lifetimeDays * 86400000;

		// Explicit expiresAt always wins when present.
		if (consent.expiresAt) {
			var exp = Date.parse(consent.expiresAt);
			if (!isNaN(exp) && exp < now) {
				return false;
			}
		}

		// Admin can shorten the lifetime later; createdAt + lifetimeDays catches that.
		if (consent.createdAt) {
			var created = Date.parse(consent.createdAt);
			if (!isNaN(created) && created + ttlMs < now) {
				return false;
			}
		}

		return true;
	}

	function parseStoredConsent(value) {
		var parsed = null;

		if (!value) {
			return null;
		}

		try {
			parsed = JSON.parse(value);
		} catch (error) {
			return null;
		}

		return isValidConsent(parsed) ? parsed : null;
	}

	function getDefaultConsent() {
		var now = new Date();
		var expires = new Date(now.getTime() + lifetimeDays * 86400000);
		return {
			version: consentVersion,
			createdAt: now.toISOString(),
			expiresAt: expires.toISOString(),
			categories: {
				necessary: true,
				statistics: false,
				marketing: false,
				external_media: false
			}
		};
	}

	function normalizeConsent(consent) {
		var normalized = getDefaultConsent();

		if (!consent || typeof consent !== 'object') {
			return normalized;
		}

		if (consent.version) {
			normalized.version = String(consent.version);
		}

		if (consent.createdAt) {
			normalized.createdAt = String(consent.createdAt);
		}

		if (consent.expiresAt) {
			normalized.expiresAt = String(consent.expiresAt);
		}

		if (consent.categories && typeof consent.categories === 'object') {
			categories.forEach(function (category) {
				normalized.categories[category] = category === 'necessary' ? true : Boolean(consent.categories[category]);
			});
		}

		return normalized;
	}

	function readCookie() {
		var name = cookieName + '=';
		var cookieList = document.cookie ? document.cookie.split(';') : [];
		var value = '';

		cookieList.some(function (cookie) {
			var current = cookie.trim();

			if (current.indexOf(name) === 0) {
				value = current.substring(name.length);
				return true;
			}

			return false;
		});

		if (!value) {
			return null;
		}

		try {
			return parseStoredConsent(decodeURIComponent(value));
		} catch (error) {
			return null;
		}
	}

	function getStoredConsent() {
		var localValue = null;

		try {
			localValue = window.localStorage.getItem(storageKey);
		} catch (error) {
			localValue = null;
		}

		if (localValue) {
			localValue = parseStoredConsent(localValue);

			if (localValue) {
				return normalizeConsent(localValue);
			}
		}

		return normalizeConsent(readCookie());
	}

	function hasStoredConsent() {
		var localValue = null;
		var cookieValue = readCookie();

		try {
			localValue = window.localStorage.getItem(storageKey);
		} catch (error) {
			localValue = null;
		}

		return Boolean(parseStoredConsent(localValue) || cookieValue);
	}

	function writeConsent(consent) {
		var normalized = normalizeConsent(consent);
		var encoded = encodeURIComponent(JSON.stringify(normalized));
		var maxAge = lifetimeDays * 86400;
		var secureFlag = window.location.protocol === 'https:' ? '; Secure' : '';

		try {
			window.localStorage.setItem(storageKey, JSON.stringify(normalized));
		} catch (error) {
			/* localStorage can be unavailable in hardened browser modes. */
		}

		document.cookie = cookieName + '=' + encoded + '; Max-Age=' + maxAge + '; Path=/; SameSite=Lax' + secureFlag;
		if (typeof window.CustomEvent === 'function') {
			window.dispatchEvent(new CustomEvent('lscc:consentChanged', { detail: normalized }));
		}

		debugLog('LSCC consent saved', normalized);

		return normalized;
	}

	function consentAllows(category) {
		var consent = getStoredConsent();

		if (!isAllowedCategory(category)) {
			return false;
		}

		if (category === 'necessary') {
			return true;
		}

		return hasOwn(consent.categories, category) && Boolean(consent.categories[category]);
	}

	function normalizeScriptType(type) {
		var allowedTypes = ['text/javascript', 'application/javascript', 'module'];
		var normalized = type ? String(type).toLowerCase() : '';

		return allowedTypes.indexOf(normalized) !== -1 ? normalized : 'text/javascript';
	}

	function shouldCopyScriptAttribute(attributeName) {
		var name = String(attributeName).toLowerCase();

		return name !== 'type' && name !== 'data-cookie-category' && name !== 'data-cookie-type' && name.indexOf('on') !== 0;
	}

	function buildActiveScript(script) {
		var activeScript = document.createElement('script');

		Array.prototype.forEach.call(script.attributes, function (attribute) {
			if (attribute.name === 'data-cookie-type') {
				activeScript.setAttribute('type', normalizeScriptType(attribute.value));
				return;
			}

			if (!shouldCopyScriptAttribute(attribute.name)) {
				return;
			}

			activeScript.setAttribute(attribute.name, attribute.value);
		});

		if (!activeScript.getAttribute('type')) {
			activeScript.setAttribute('type', normalizeScriptType(''));
		}

		return activeScript;
	}

	function activateBlockedScripts() {
		// Some gated integrations (e.g. an external library plus an inline init
		// that depends on it) require execution order. Activate sequentially and
		// wait for each external script to load before running the next node, so
		// a following inline script never runs before its dependency is ready.
		restoreExternalMediaThumbnails();

		var blockedScripts = Array.prototype.slice.call(
			document.querySelectorAll('script[type="text/plain"][data-cookie-category]')
		);
		var activatedCount = 0;

		function activateNext(index) {
			if (index >= blockedScripts.length) {
				debugLog('LSCC activated scripts', activatedCount);
				return;
			}

			var script = blockedScripts[index];
			var category = script.getAttribute('data-cookie-category');

			if (!category || !consentAllows(category)) {
				activateNext(index + 1);
				return;
			}

			var activeScript = buildActiveScript(script);
			var hasSrc = Boolean(activeScript.src || activeScript.getAttribute('src'));
			activatedCount += 1;

			if (hasSrc) {
				// async=false keeps external scripts in insertion order; the
				// load/error callback gates the next (possibly inline) script.
				activeScript.async = false;
				activeScript.onload = activeScript.onerror = function () {
					activateNext(index + 1);
				};
				script.parentNode.replaceChild(activeScript, script);
			} else {
				activeScript.text = script.text || script.textContent || '';
				script.parentNode.replaceChild(activeScript, script);
				activateNext(index + 1);
			}
		}

		activateNext(0);
	}

	function restoreExternalMediaThumbnails() {
		// Server-side gating (e.g. the Yotu module) parks lazy thumbnail URLs in
		// data-lscc-orig-src so the theme lazy-load cannot fetch them before
		// consent. Restore them once external media is allowed and hide any
		// gated-gallery consent notice.
		if (!consentAllows('external_media')) {
			return;
		}

		var images = document.querySelectorAll('img[data-lscc-orig-src]');

		Array.prototype.forEach.call(images, function (img) {
			var url = img.getAttribute('data-lscc-orig-src');

			if (url) {
				img.setAttribute('src', url);
				img.setAttribute('data-orig-src', url);
			}

			img.removeAttribute('data-lscc-orig-src');

			if (img.classList) {
				img.classList.remove('lazyload');
				img.classList.remove('lscc-gated-thumb');
			}
		});

		var notices = document.querySelectorAll('[data-lscc-gated-notice]');

		Array.prototype.forEach.call(notices, function (notice) {
			notice.hidden = true;
		});
	}

	function createMediaIframe(component) {
		var iframe = document.createElement('iframe');
		var src = component.getAttribute('data-lscc-src');
		var title = component.getAttribute('data-lscc-title') || '';
		var service = component.getAttribute('data-lscc-service') || '';

		if (!src) {
			return null;
		}

		// Start playback right after consent when the visitor used the play button.
		if (component.getAttribute('data-lscc-autoplay-now') === '1' && (service === 'youtube' || service === 'vimeo')) {
			src += (src.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1';
		}

		iframe.setAttribute('class', 'lscc-media__iframe');
		iframe.setAttribute('src', src);
		iframe.setAttribute('title', title);
		iframe.setAttribute('loading', 'lazy');
		iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
		iframe.setAttribute('allowfullscreen', '');

		if (service === 'youtube' || service === 'vimeo') {
			iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
		}

		return iframe;
	}

	function setMediaComponentLoaded(component, loaded) {
		var placeholder = component.querySelector('.lscc-media__placeholder');
		var iframe = component.querySelector('.lscc-media__iframe');

		if (loaded && !iframe) {
			iframe = createMediaIframe(component);

			if (!iframe) {
				return;
			}

			component.appendChild(iframe);
		}

		if (!loaded && iframe) {
			iframe.parentNode.removeChild(iframe);
		}

		if (placeholder) {
			placeholder.hidden = Boolean(loaded);
		}

		component.classList.toggle('is-loaded', Boolean(loaded));
		component.setAttribute('data-lscc-loaded', loaded ? '1' : '0');
	}

	function syncMediaComponents() {
		var components = document.querySelectorAll('[data-lscc-media][data-lscc-category="external_media"]');
		var allowed = consentAllows('external_media');
		var loadedCount = 0;

		Array.prototype.forEach.call(components, function (component) {
			setMediaComponentLoaded(component, allowed);

			if (allowed) {
				loadedCount += 1;
			}
		});

		debugLog('LSCC media components synced', loadedCount);
	}

	function acceptExternalMedia(root, reopenButton) {
		var consent = getStoredConsent();

		consent.categories.external_media = true;
		writeConsent(consent);
		activateBlockedScripts();
		syncMediaComponents();

		if (root && reopenButton) {
			setBannerVisible(root, reopenButton, false, false);
		}
	}

	function bindMediaComponents(root, reopenButton) {
		var buttons = document.querySelectorAll('[data-lscc-accept-media]');

		Array.prototype.forEach.call(buttons, function (button) {
			if (button.getAttribute('data-lscc-media-bound') === '1') {
				return;
			}

			button.setAttribute('data-lscc-media-bound', '1');
			button.addEventListener('click', function (event) {
				event.preventDefault();

				if (button.hasAttribute('data-lscc-autoplay') && typeof button.closest === 'function') {
					var playComponent = button.closest('[data-lscc-media]');

					if (playComponent) {
						playComponent.setAttribute('data-lscc-autoplay-now', '1');
					}
				}

				acceptExternalMedia(root, reopenButton);
			});
		});
	}

	function createConsent(allowAll) {
		var consent = getDefaultConsent();

		categories.forEach(function (category) {
			consent.categories[category] = category === 'necessary' ? true : Boolean(allowAll);
		});

		return consent;
	}

	function collectConsent(root) {
		var consent = getDefaultConsent();
		var inputs = root.querySelectorAll('[data-lscc-category]');

		Array.prototype.forEach.call(inputs, function (input) {
			var category = input.getAttribute('data-lscc-category');

			if (!category) {
				return;
			}

			consent.categories[category] = category === 'necessary' ? true : Boolean(input.checked);
		});

		return consent;
	}

	function updateInputs(root, consent) {
		var normalized = normalizeConsent(consent);
		var inputs = root.querySelectorAll('[data-lscc-category]');

		Array.prototype.forEach.call(inputs, function (input) {
			var category = input.getAttribute('data-lscc-category');

			if (!category || !hasOwn(normalized.categories, category)) {
				return;
			}

			input.checked = Boolean(normalized.categories[category]);
		});
	}

	function setQuickButtonState(button, state) {
		// state: 'active' | 'inactive' | 'neutral'. Display only.
		if (!button) {
			return;
		}

		button.classList.toggle('is-active', state === 'active');
		button.classList.toggle('is-inactive', state === 'inactive');
		button.setAttribute('aria-pressed', state === 'active' ? 'true' : 'false');
	}

	function updateQuickButtons(root) {
		// Reflect the stored consent on the quick-choice buttons so the active
		// preset is visible on open. Pure presentation: reads getStoredConsent(),
		// never writes. Before any choice (no stored consent) both stay neutral so
		// "Accept all" and "Necessary only" remain equally prominent.
		var acceptAll = root.querySelector('[data-lscc-accept-all]');
		var necessary = root.querySelector('[data-lscc-necessary]');

		if (!hasStoredConsent()) {
			setQuickButtonState(acceptAll, 'neutral');
			setQuickButtonState(necessary, 'neutral');
			return;
		}

		var consent = getStoredConsent();
		var optional = categories.filter(function (category) {
			return category !== 'necessary';
		});
		var allOn = optional.length > 0 && optional.every(function (category) {
			return Boolean(consent.categories[category]);
		});
		var allOff = optional.every(function (category) {
			return !consent.categories[category];
		});

		// allOn -> Accept all active; allOff -> Necessary only active; mixed
		// (custom) -> neither active, both de-emphasised.
		setQuickButtonState(acceptAll, allOn ? 'active' : 'inactive');
		setQuickButtonState(necessary, allOff ? 'active' : 'inactive');
	}

	function setBannerVisible(root, reopenButton, visible, showSettings) {
		var settingsPanel = root.querySelector('[data-lscc-settings]');
		var mainActions = root.querySelector('[data-lscc-main-actions]');

		root.hidden = !visible;
		root.setAttribute('aria-hidden', visible ? 'false' : 'true');
		reopenButton.hidden = visible || !hasStoredConsent();
		syncSettingsTriggers(visible);

		var overlay = document.querySelector('[data-lscc-overlay]');
		if (overlay) {
			overlay.hidden = !visible;
			overlay.setAttribute('aria-hidden', visible ? 'false' : 'true');
		}

		if (settingsPanel) {
			settingsPanel.hidden = !showSettings;
			settingsPanel.setAttribute('aria-hidden', showSettings ? 'false' : 'true');
		}

		if (mainActions) {
			mainActions.hidden = false;
		}

		var openSettingsBtn = root.querySelector('[data-lscc-open-settings]');
		if (openSettingsBtn) {
			openSettingsBtn.hidden = Boolean(showSettings);
		}

		if (visible) {
			updateInputs(root, getStoredConsent());
			updateQuickButtons(root);
		}

		if (visible && showSettings) {
			focusSettings(root);
		}
	}

	function syncSettingsTriggers(expanded) {
		var triggers = document.querySelectorAll('[data-lscc-open-consent-settings]');

		Array.prototype.forEach.call(triggers, function (trigger) {
			trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
		});
	}

	function focusSettings(root) {
		var target = root.querySelector('[data-lscc-settings] input:not([disabled]), [data-lscc-settings] button');

		if (!target || typeof target.focus !== 'function') {
			return;
		}

		window.setTimeout(function () {
			target.focus();
		}, 0);
	}

	function bindSettingsTriggers(root, reopenButton) {
		var triggers = document.querySelectorAll('[data-lscc-open-consent-settings]');

		Array.prototype.forEach.call(triggers, function (trigger) {
			if (trigger.getAttribute('data-lscc-trigger-bound') === '1') {
				return;
			}

			trigger.setAttribute('data-lscc-trigger-bound', '1');
			trigger.setAttribute('aria-expanded', 'false');
			trigger.addEventListener('click', function (event) {
				event.preventDefault();
				setBannerVisible(root, reopenButton, true, true);
			});
		});
	}

	function saveAndClose(root, reopenButton, consent) {
		writeConsent(consent);
		// Keep the visible checkboxes in lockstep with the saved consent, so the
		// quick actions ("Alle akzeptieren" / "Nur notwendige") and the settings
		// panel never drift apart.
		updateInputs(root, consent);
		updateQuickButtons(root);
		activateBlockedScripts();
		syncMediaComponents();
		setBannerVisible(root, reopenButton, false, false);
	}

	function initBanner() {
		var root = document.querySelector('[data-lscc-root]');
		var reopenButton = document.querySelector('[data-lscc-reopen]');

		if (!root || !reopenButton) {
			bindMediaComponents(null, null);
			activateBlockedScripts();
			syncMediaComponents();
			return;
		}

		root.querySelector('[data-lscc-accept-all]').addEventListener('click', function () {
			saveAndClose(root, reopenButton, createConsent(true));
		});

		root.querySelector('[data-lscc-necessary]').addEventListener('click', function () {
			saveAndClose(root, reopenButton, createConsent(false));
		});

		root.querySelector('[data-lscc-open-settings]').addEventListener('click', function () {
			setBannerVisible(root, reopenButton, true, true);
		});

		root.querySelector('[data-lscc-settings]').addEventListener('submit', function (event) {
			event.preventDefault();
			saveAndClose(root, reopenButton, collectConsent(root));
		});

		bindSettingsTriggers(root, reopenButton);
		bindMediaComponents(root, reopenButton);

		// Stored consent is the single source of truth for the checkboxes on every
		// load — independent of any browser form-state restoration after a reload.
		updateInputs(root, getStoredConsent());
		updateQuickButtons(root);

		if (hasStoredConsent()) {
			activateBlockedScripts();
			syncMediaComponents();
			setBannerVisible(root, reopenButton, false, false);
			return;
		}

		syncMediaComponents();
		setBannerVisible(root, reopenButton, true, false);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initBanner);
	} else {
		initBanner();
	}
}());
