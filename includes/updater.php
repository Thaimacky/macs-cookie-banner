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
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstraps the plugin update checker.
 */
final class Macs_Cookie_Banner_Updater {

	/**
	 * GitHub repository the releases are published to.
	 */
	const REPOSITORY_URL = 'https://github.com/Thaimacky/macs-cookie-banner/';

	/**
	 * Plugin slug used by the update checker (must match the plugin folder).
	 *
	 * Deliberately kept as the historical slug during the 0.3.4 rebrand
	 * (display name -> "Mac's Cookie Banner"). The folder, text domain and all
	 * stored DB keys keep their original identity so existing installations
	 * update in place without losing settings or consents. Only REPOSITORY_URL
	 * changes: 0.3.4 acts as a bridge release that points existing sites at the
	 * new GitHub repository for all future updates.
	 */
	const SLUG = 'macs-cookie-banner';

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
		$entry = MCB_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';

		if ( ! is_readable( $entry ) ) {
			return;
		}

		require_once $entry;

		if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		self::$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			self::REPOSITORY_URL,
			MCB_PLUGIN_FILE,
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
