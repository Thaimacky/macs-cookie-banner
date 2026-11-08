<?php
/**
 * WP Rocket compatibility (v1.0.8, ADR-41): never let "Delay JavaScript
 * execution" re-arm a consent-gated script.
 *
 * Proven failure (production Avada site, 2026-11-08): WP Rocket's Delay JS
 * (inc/Engine/Optimization/DelayJS/HTML.php::replace_scripts()) decides
 * whether a <script> is JavaScript by reading the FIRST "type=" substring of
 * its attributes - without a word boundary. On a gated tag whose
 * data-cookie-type="text/javascript" preceded type="text/plain" it took the
 * data attribute for the real type, rewrote the tag to
 * type="text/rocketlazyloadscript" + data-rocket-src and executed it on the
 * first mouse move/scroll - i.e. Google Analytics loaded without consent and
 * even after "Nur notwendige".
 *
 * Two independent safeguards close this:
 * 1. google-tracking-shield.php no longer emits data-cookie-type ahead of
 *    type="text/plain" (attribute-order fix at the source).
 * 2. This module adds "data-cookie-category" to WP Rocket's own, documented
 *    exclusion filter `rocket_delay_js_exclusions`. WP Rocket matches every
 *    exclusion pattern against the complete <script> tag, so EVERY gated
 *    script of this plugin (Consent-Code-Manager, Google Tracking Shield,
 *    Meta Social, YOTU, Avada Maps, PixelYourSite) is left untouched by Delay
 *    JS, regardless of attribute order. Activation after consent keeps running
 *    exclusively through the unchanged banner.js mechanic.
 *
 * Scope: this does not rewrite, block or delay anything itself. It only tells
 * WP Rocket to leave already-gated scripts alone. Without WP Rocket the filter
 * is never applied (zero cost). Always active - it is a bugfix that keeps the
 * existing gating effective, not a new protection feature, hence no option.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps WP Rocket Delay JS away from consent-gated scripts.
 */
final class Macs_Cookie_Banner_Wp_Rocket_Compat {
	/**
	 * Exclusion pattern. WP Rocket wraps each pattern as "#{$pattern}#i" and
	 * tests it against the full script tag; a plain attribute name is safe in
	 * that context (no regex metacharacters).
	 */
	const GATED_PATTERN = 'data-cookie-category';

	/**
	 * Register the exclusion filter on the frontend.
	 *
	 * @return void
	 */
	public static function init() {
		if ( is_admin() ) {
			return;
		}

		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'exclude_gated_scripts' ) );
	}

	/**
	 * Add the gated-script pattern to WP Rocket's Delay JS exclusions.
	 *
	 * @param mixed $exclusions Existing exclusion patterns.
	 * @return array
	 */
	public static function exclude_gated_scripts( $exclusions ) {
		$exclusions = is_array( $exclusions ) ? $exclusions : array();

		if ( ! in_array( self::GATED_PATTERN, $exclusions, true ) ) {
			$exclusions[] = self::GATED_PATTERN;
		}

		return $exclusions;
	}
}
