<?php
/**
 * YOTU (Yotuwp – Easy YouTube Embed) compatibility: consent-gate the gallery.
 *
 * The Yotuwp plugin renders a YouTube gallery whose front-end script
 * (handle `yotu-script`, plus its inline `-extra` localize and `-after` data)
 * dynamically injects `https://www.youtube.com/iframe_api` and builds players
 * on click — entirely outside the Light Swiss Cookie Consent layer. In
 * addition, its thumbnails are lazy-loaded from `i.ytimg.com` (the swap is done
 * by the theme's lazy-load, not by Yotu), which contacts Google before consent.
 *
 * This module, when enabled (opt-in, default OFF), gates the gallery via the
 * existing LSCC mechanisms — no DOM hijacking, no MutationObserver, no scanner:
 *
 * - Phase 1: the three Yotu script parts are tagged `type="text/plain"` +
 *   `data-cookie-category="external_media"` via the official `script_loader_tag`
 *   and `wp_inline_script_attributes` filters. The existing banner.js
 *   `activateBlockedScripts()` re-runs them (in order) only after consent.
 * - Phase 2: in the Yotu shortcode output, the thumbnail `data-orig-src`
 *   (i.ytimg.com) is renamed to `data-lscc-orig-src` so the theme's lazy-load
 *   finds nothing to fetch before consent; banner.js restores it after consent.
 *   A small consent notice is prepended above the gallery.
 *
 * Before external_media consent: no youtube.com, no youtube-nocookie.com, no
 * iframe_api, no www-widgetapi, no i.ytimg.com. After consent: Yotu works
 * normally. Fully reversible (disable the option → all filters drop).
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render-layer consent gating for the Yotuwp YouTube gallery.
 */
final class Macs_Cookie_Banner_Yotu_Compat {
	/**
	 * Registered front-end script handle of the Yotuwp plugin.
	 */
	const SCRIPT_HANDLE = 'yotu-script';

	/**
	 * Register the gating filters on the front end when enabled.
	 *
	 * @return void
	 */
	public static function init() {
		// Never touch the admin / block-editor rendering.
		if ( is_admin() ) {
			return;
		}

		$options = Macs_Cookie_Banner::get_options();

		if ( empty( $options['yotu_consent_gating'] ) ) {
			return;
		}

		add_filter( 'script_loader_tag', array( __CLASS__, 'block_script_tag' ), 10, 3 );
		add_filter( 'wp_inline_script_attributes', array( __CLASS__, 'block_inline_attributes' ), 10, 1 );
		add_filter( 'do_shortcode_tag', array( __CLASS__, 'gate_shortcode_output' ), 10, 4 );
	}

	/**
	 * Block the main Yotu front-end script (the `src` file).
	 *
	 * @param string $tag    The full `<script>` HTML.
	 * @param string $handle The script handle.
	 * @param string $src    The script source URL.
	 * @return string
	 */
	public static function block_script_tag( $tag, $handle, $src ) {
		unset( $src );

		if ( self::SCRIPT_HANDLE !== $handle ) {
			return $tag;
		}

		return self::convert_tag_to_blocked( (string) $tag );
	}

	/**
	 * Block the Yotu inline scripts (localized `-extra` data and `-after` init).
	 *
	 * Requires WordPress 5.7+ (filter availability). On older cores the main
	 * script is still blocked via block_script_tag(), so no third-party request
	 * happens before consent; only the harmless inline parts would run.
	 *
	 * @param array $attributes Inline script tag attributes.
	 * @return array
	 */
	public static function block_inline_attributes( $attributes ) {
		if ( ! is_array( $attributes ) || empty( $attributes['id'] ) ) {
			return $attributes;
		}

		if ( 0 !== strpos( (string) $attributes['id'], self::SCRIPT_HANDLE . '-js' ) ) {
			return $attributes;
		}

		$attributes['type']                 = 'text/plain';
		$attributes['data-cookie-category']  = 'external_media';
		$attributes['data-cookie-type']      = 'text/javascript';

		return $attributes;
	}

	/**
	 * Inject the LSCC blocking attributes into a `<script>` opening tag.
	 *
	 * @param string $tag The full `<script>` HTML.
	 * @return string
	 */
	private static function convert_tag_to_blocked( $tag ) {
		// Drop any existing type attribute so the browser does not execute it.
		$tag = preg_replace( '/\stype=("|\')[^"\']*\1/i', '', $tag, 1 );

		return preg_replace(
			'/<script\b/i',
			'<script type="text/plain" data-cookie-category="external_media" data-cookie-type="text/javascript"',
			$tag,
			1
		);
	}

	/**
	 * Neutralize Yotu thumbnails and prepend a consent notice in the gallery.
	 *
	 * Targets only shortcode output that actually contains the Yotu gallery
	 * markup (`yotu-video-thumb`); all other shortcodes pass through untouched.
	 *
	 * @param string $output Shortcode output HTML.
	 * @param string $tag    Shortcode tag.
	 * @param array  $attr   Shortcode attributes.
	 * @param array  $m      Regex match array.
	 * @return string
	 */
	public static function gate_shortcode_output( $output, $tag, $attr, $m ) {
		unset( $tag, $attr, $m );

		if ( ! is_string( $output ) || false === strpos( $output, 'yotu-video-thumb' ) ) {
			return $output;
		}

		// Rename the lazy-load source so the theme's lazy-load cannot fetch the
		// i.ytimg.com thumbnail before consent. banner.js restores it after.
		$output = str_replace( ' data-orig-src=', ' data-lscc-orig-src=', $output );

		return self::render_consent_notice() . $output;
	}

	/**
	 * Build the small consent notice shown above a gated Yotu gallery.
	 *
	 * Reuses the existing `data-lscc-accept-media` hook (bound by banner.js) so
	 * a click grants external_media consent, activates the Yotu scripts and
	 * restores the thumbnails. `data-lscc-gated-notice` is hidden after consent.
	 *
	 * @return string
	 */
	private static function render_consent_notice() {
		$text   = __( 'Diese YouTube-Galerie wird erst nach Zustimmung zu externen Medien geladen.', 'macs-cookie-banner' );
		$button = __( 'Externe Medien akzeptieren', 'macs-cookie-banner' );

		return sprintf(
			'<div class="lscc-yotu-consent" data-lscc-gated-notice><p class="lscc-yotu-consent__text">%1$s</p><button type="button" class="lscc-yotu-consent__button" data-lscc-accept-media>%2$s</button></div>',
			esc_html( $text ),
			esc_html( $button )
		);
	}
}
