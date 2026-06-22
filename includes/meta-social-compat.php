<?php
/**
 * Meta social embed compatibility (v1.0.4): consent-gate the Facebook and
 * Instagram embed SDKs before consent, via the existing external_media mechanic.
 *
 * Facebook XFBML widgets (fb-page / fb-post / fb-video) render client-side once
 * connect.facebook.net/<locale>/sdk.js runs; Instagram blockquotes (instagram-
 * media) render once instagram.com/embed.js / platform.instagram.com/.../embeds.js
 * runs. Both contact Facebook/Instagram on page load — before consent.
 *
 * This module (opt-in) rewrites those SDK <script> tags (SRC-based, handle-
 * agnostic — same approach as avada-maps-compat::block_maps_api) to
 * type="text/plain" data-cookie-category="external_media". After external_media
 * consent the existing banner.js::activateBlockedScripts() re-activates them, so
 * FB.init / window.instgrm run and the widgets render — no new front-end logic.
 *
 * STRICT separation from the Meta Pixel: fbevents.js / fbq() are NEVER touched
 * here (the Pixel stays a marketing-category concern handled via the CCM).
 *
 * Scope limit: this gates SDK scripts that go through wp_enqueue_script (so
 * script_loader_tag fires). A hardcoded raw <script src="...sdk.js"> in a theme
 * or a raw plugins/*.php <iframe> is detected by the Privacy Check but is best
 * routed through the Consent-Code-Manager or [lscc_facebook]/[lscc_instagram].
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consent gating for the Facebook/Instagram embed SDKs.
 */
final class Macs_Cookie_Banner_Meta_Social_Compat {
	/**
	 * Option key toggling this module (Safe-by-Default: recommended ON for fresh
	 * installs, never silently enabled on existing installs — see ADR-36/ADR-37).
	 */
	const OPTION_KEY = 'meta_social_block';

	/**
	 * Register the SRC-based gating filter on the front end when enabled.
	 *
	 * @return void
	 */
	public static function init() {
		if ( is_admin() ) {
			return;
		}

		$options = Macs_Cookie_Banner::get_options();

		if ( empty( $options[ self::OPTION_KEY ] ) ) {
			return;
		}

		add_filter( 'script_loader_tag', array( __CLASS__, 'block_social_sdk' ), 10, 3 );
	}

	/**
	 * Mark the Facebook/Instagram embed SDK script as consent-blocked (src-based).
	 *
	 * @param string $tag    The full <script> HTML.
	 * @param string $handle Script handle (unused; src-based).
	 * @param string $src    Script source URL.
	 * @return string
	 */
	public static function block_social_sdk( $tag, $handle, $src ) {
		unset( $handle );

		$lc = strtolower( (string) $src );

		// Never touch the Meta Pixel — strict Pixel/Social separation.
		if ( false !== strpos( $lc, 'fbevents.js' ) ) {
			return $tag;
		}

		if ( ! self::is_social_sdk( $lc ) ) {
			return $tag;
		}

		// Drop any existing type, then inject the LSCC blocking attributes.
		$tag = preg_replace( '/\stype=("|\')[^"\']*\1/i', '', $tag, 1 );

		return preg_replace(
			'/<script\b/i',
			'<script type="text/plain" data-cookie-category="external_media" data-cookie-type="text/javascript"',
			$tag,
			1
		);
	}

	/**
	 * Whether a (lowercased) script src is a Facebook/Instagram embed SDK.
	 *
	 * @param string $lc Lowercased script src.
	 * @return bool
	 */
	private static function is_social_sdk( $lc ) {
		// Facebook SDK: connect.facebook.net/<locale>/sdk.js (locale-agnostic).
		if ( false !== strpos( $lc, 'connect.facebook.net' ) && false !== strpos( $lc, 'sdk.js' ) ) {
			return true;
		}

		// Instagram embed script: instagram.com/embed.js.
		if ( false !== strpos( $lc, 'instagram.com/embed.js' ) ) {
			return true;
		}

		// Instagram platform embeds.js: platform.instagram.com/<locale>/embeds.js.
		if ( false !== strpos( $lc, 'platform.instagram.com' ) && false !== strpos( $lc, 'embed' ) ) {
			return true;
		}

		return false;
	}
}
