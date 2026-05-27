(function () {
	'use strict';

	var settings = window.lsccSettings || {};
	var storageKey = settings.storageKey || 'lscc_consent';
	var cookieName = settings.cookieName || 'lscc_consent';
	var consentVersion = settings.consentVersion || '1';
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
		return Boolean(
			consent &&
			typeof consent === 'object' &&
			String(consent.version) === String(consentVersion) &&
			consent.categories &&
			typeof consent.categories === 'object'
		);
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
		return {
			version: consentVersion,
			createdAt: new Date().toISOString(),
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
		var maxAge = 60 * 60 * 24 * 180;
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

	function activateBlockedScripts() {
		var blockedScripts = document.querySelectorAll('script[type="text/plain"][data-cookie-category]');
		var activatedCount = 0;

		Array.prototype.forEach.call(blockedScripts, function (script) {
			var category = script.getAttribute('data-cookie-category');
			var activeScript = null;

			if (!category || !consentAllows(category)) {
				return;
			}

			activeScript = document.createElement('script');

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

			activeScript.text = script.text || script.textContent || '';
			script.parentNode.replaceChild(activeScript, script);
			activatedCount += 1;
		});

		debugLog('LSCC activated scripts', activatedCount);
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

	function setBannerVisible(root, reopenButton, visible, showSettings) {
		var settingsPanel = root.querySelector('[data-lscc-settings]');
		var mainActions = root.querySelector('[data-lscc-main-actions]');

		root.hidden = !visible;
		root.setAttribute('aria-hidden', visible ? 'false' : 'true');
		reopenButton.hidden = visible || !hasStoredConsent();
		syncSettingsTriggers(visible);

		if (settingsPanel) {
			settingsPanel.hidden = !showSettings;
			settingsPanel.setAttribute('aria-hidden', showSettings ? 'false' : 'true');
		}

		if (mainActions) {
			mainActions.hidden = Boolean(showSettings);
		}

		if (visible) {
			updateInputs(root, getStoredConsent());
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
		activateBlockedScripts();
		setBannerVisible(root, reopenButton, false, false);
	}

	function initBanner() {
		var root = document.querySelector('[data-lscc-root]');
		var reopenButton = document.querySelector('[data-lscc-reopen]');

		if (!root || !reopenButton) {
			activateBlockedScripts();
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

		root.querySelector('[data-lscc-settings-accept-all]').addEventListener('click', function () {
			saveAndClose(root, reopenButton, createConsent(true));
		});

		root.querySelector('[data-lscc-settings-necessary]').addEventListener('click', function () {
			saveAndClose(root, reopenButton, createConsent(false));
		});

		root.querySelector('[data-lscc-settings]').addEventListener('submit', function (event) {
			event.preventDefault();
			saveAndClose(root, reopenButton, collectConsent(root));
		});

		bindSettingsTriggers(root, reopenButton);

		if (hasStoredConsent()) {
			activateBlockedScripts();
			setBannerVisible(root, reopenButton, false, false);
			return;
		}

		setBannerVisible(root, reopenButton, true, false);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initBanner);
	} else {
		initBanner();
	}
}());
