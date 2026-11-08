<?php
/**
 * Page-cache purge after plugin updates and settings changes (v1.0.9, ADR-43).
 *
 * Proven problem (2026-11-08): after updating to 1.0.8, a site with WP Rocket
 * kept serving HTML that was cached under 1.0.7 - including the gated Google
 * tag that WP Rocket Delay JS had re-armed. The fix only reached visitors
 * after a manual rocket_clean_domain() - and even then two sites kept serving
 * a stale minified banner.js from wp-content/cache/min/, because
 * rocket_clean_domain() does not touch minified files. Likewise, switching a protection on
 * (e.g. PixelYourSite gating) only changes freshly generated pages.
 *
 * Every change that alters the delivered HTML therefore purges the page cache
 * once:
 * - first request after a plugin update or (re)activation (version stamp
 *   MCB_CACHE_PURGED_VERSION_OPTION, works for auto-updates, ZIP uploads and
 *   file replacements alike - the new code runs the purge itself);
 * - every real change of the plugin settings (update_option_lscc_options /
 *   add_option_lscc_options - settings save, "restore recommended", one-time
 *   migrations, Avada colour sync).
 *
 * Purge is deferred to wp_loaded so cache plugins are loaded; it runs at most
 * once per request. Only public, documented purge entry points are used and
 * each one only when it exists - no cache plugin is a dependency. Edge caches
 * outside WordPress (e.g. Cloudflare APO / "Cache Everything") cannot be
 * purged from here; see RELEASE_GUIDE.md.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Purges known page caches when the delivered HTML changes.
 */
final class Macs_Cookie_Banner_Cache_Purge {
	/**
	 * Option storing the plugin version whose cache purge already ran.
	 */
	const STAMP_OPTION = 'mcb_cache_purged_version';

	/**
	 * Whether a purge is pending/already done in this request.
	 *
	 * @var bool
	 */
	private static $requested = false;

	/**
	 * Names of the cache layers purged in this request (for tests/diagnostics).
	 *
	 * @var string[]
	 */
	public static $purged = array();

	/**
	 * Register hooks and check the version stamp.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'update_option_' . Macs_Cookie_Banner::OPTION_NAME, array( __CLASS__, 'on_settings_changed' ) );
		add_action( 'add_option_' . Macs_Cookie_Banner::OPTION_NAME, array( __CLASS__, 'on_settings_changed' ) );

		if ( (string) get_option( self::STAMP_OPTION, '' ) !== (string) MCB_VERSION ) {
			update_option( self::STAMP_OPTION, MCB_VERSION );
			self::request();
		}
	}

	/**
	 * Settings changed: the delivered HTML may change, purge once.
	 *
	 * @return void
	 */
	public static function on_settings_changed() {
		self::request();
	}

	/**
	 * Plugin (re)activation: force a purge on this or the next request.
	 *
	 * @return void
	 */
	public static function on_activate() {
		delete_option( self::STAMP_OPTION );
		self::request();
	}

	/**
	 * Schedule the purge for wp_loaded (or run it now if that already fired).
	 *
	 * @return void
	 */
	public static function request() {
		if ( self::$requested ) {
			return;
		}

		self::$requested = true;

		if ( did_action( 'wp_loaded' ) ) {
			self::purge_all();
			return;
		}

		add_action( 'wp_loaded', array( __CLASS__, 'purge_all' ) );
	}

	/**
	 * Purge every known, installed page cache. Each call is guarded; a missing
	 * cache plugin is simply skipped.
	 *
	 * @return void
	 */
	public static function purge_all() {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
			self::$purged[] = 'wp-rocket';
		}

		// Proven (2026-11-09): rocket_clean_domain() only clears the HTML cache.
		// WP Rocket's minified copies in wp-content/cache/min/ survive it and keep
		// serving an OLD banner.js after a plugin update (no withdrawal handling,
		// no #cookie-settings). Clear them as well.
		if ( function_exists( 'rocket_clean_minify' ) ) {
			rocket_clean_minify();
			self::$purged[] = 'wp-rocket-minify';
		}

		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
			self::$purged[] = 'litespeed';
		}

		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
			self::$purged[] = 'w3-total-cache';
		}

		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
			self::$purged[] = 'wp-super-cache';
		}

		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
			self::$purged[] = 'sg-optimizer';
		}

		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			autoptimizeCache::clearall();
			self::$purged[] = 'autoptimize';
		}

		/**
		 * Lets site owners hook additional cache layers.
		 *
		 * @param string[] $purged Cache layers purged by the plugin.
		 */
		do_action( 'mcb_purge_page_cache', self::$purged );
	}
}
