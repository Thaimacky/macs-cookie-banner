<?php
/**
 * GitHub-based auto-update integration.
 *
 * Wires the bundled Plugin Update Checker (PUC) library to the plugin's
 * GitHub repository so the site can install updates from tagged GitHub
 * releases. Updates are pulled from the ZIP asset attached to each release
 * (enableReleaseAssets), which keeps the installed package free of the
 * repository's build/dev cruft.
 *
 * @package LightSwissCookieConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstraps the plugin update checker.
 */
final class Light_Swiss_Cookie_Consent_Updater {

	/**
	 * GitHub repository the releases are published to.
	 */
	const REPOSITORY_URL = 'https://github.com/Thaimacky/light-swiss-cookie-consent/';

	/**
	 * Plugin slug used by the update checker (must match the plugin folder).
	 */
	const SLUG = 'light-swiss-cookie-consent';

	/**
	 * The update checker instance.
	 *
	 * @var \YahnisElsts\PluginUpdateChecker\v5p6\Plugin\UpdateChecker|null
	 */
	private static $checker = null;

	/**
	 * Build and register the update checker.
	 *
	 * @return void
	 */
	public static function init() {
		$entry = LSCC_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';

		if ( ! is_readable( $entry ) ) {
			return;
		}

		require_once $entry;

		if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		self::$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			self::REPOSITORY_URL,
			LSCC_PLUGIN_FILE,
			self::SLUG
		);

		// Pull the update package from the ZIP asset attached to the GitHub
		// release rather than GitHub's auto-generated source archive.
		$api = self::$checker->getVcsApi();
		if ( $api && method_exists( $api, 'enableReleaseAssets' ) ) {
			$api->enableReleaseAssets();
		}
	}
}
