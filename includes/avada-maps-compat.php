<?php
/**
 * Avada (Fusion Builder) Google Maps compatibility: consent-gate fusion_map.
 *
 * Avada renders its map element client-side via the Google Maps JavaScript API
 * (maps.googleapis.com/maps/api/js), which contacts Google on page load — before
 * consent. This module (opt-in, default OFF) routes Avada maps onto the existing
 * LSCC architecture, variant 3A-i (ADR-25):
 *
 * - `pre_do_shortcode_tag` for `fusion_map` → replaces the map with the existing
 *   LSCC placeholder built from a keyless Google Maps EMBED URL
 *   (Service_Components::render_google_map). The embed iframe is created by the
 *   existing banner.js mechanic only after `external_media` consent.
 * - `script_loader_tag` (SRC-based, handle-agnostic) → any
 *   `maps.googleapis.com/maps/api/js` script is marked type="text/plain" so the
 *   API does not load before consent.
 *
 * No DOM hijacking, no MutationObserver, no Avada re-init, no JS lifecycle
 * management. After consent the LSCC placeholder loads the embed map (location
 * pin) — not Avada's fully styled JS-API map (documented trade-off).
 *
 * IMPORTANT: use only ONE consent layer. If Avada's own Privacy feature already
 * gates Google Maps, do NOT enable this module (see the admin description).
 *
 * @package LightSwissCookieConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render-layer consent gating for Avada Google Maps.
 */
final class Light_Swiss_Cookie_Consent_Avada_Maps_Compat {
	/**
	 * Source fragment identifying the Google Maps JS API.
	 */
	const MAPS_API_NEEDLE = 'maps.googleapis.com/maps/api/js';

	/**
	 * Register the gating filters on the front end when enabled.
	 *
	 * @return void
	 */
	public static function init() {
		if ( is_admin() ) {
			return;
		}

		$options = Light_Swiss_Cookie_Consent::get_options();

		if ( empty( $options['avada_maps_block'] ) ) {
			return;
		}

		add_filter( 'pre_do_shortcode_tag', array( __CLASS__, 'intercept' ), 10, 4 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'block_maps_api' ), 10, 3 );
	}

	/**
	 * Short-circuit fusion_map with a consent-gated embed placeholder.
	 *
	 * @param false|string $output Short-circuit return value (false = render normally).
	 * @param string       $tag    Shortcode tag.
	 * @param array|string $attr   Shortcode attributes.
	 * @param array        $m      Regex match array ($m[5] = enclosed content).
	 * @return false|string
	 */
	public static function intercept( $output, $tag, $attr, $m ) {
		if ( 'fusion_map' !== $tag ) {
			return $output;
		}

		$atts    = is_array( $attr ) ? $attr : array();
		$content = isset( $m[5] ) ? (string) $m[5] : '';
		$address = self::extract_address( $atts, $content );

		// No usable address: let Avada render (the JS API stays gated via src).
		if ( '' === $address ) {
			return $output;
		}

		$embed = Light_Swiss_Cookie_Consent_Service_Components::build_maps_embed_url( $address );

		if ( '' === $embed ) {
			return $output;
		}

		$markup = Light_Swiss_Cookie_Consent_Service_Components::render_google_map( array( 'url' => $embed ) );

		return '' !== $markup ? $markup : $output;
	}

	/**
	 * Extract the primary address from the fusion_map attributes or markers.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content (may hold [fusion_map_marker ...]).
	 * @return string
	 */
	private static function extract_address( $atts, $content ) {
		if ( isset( $atts['address'] ) && '' !== trim( (string) $atts['address'] ) ) {
			// Multiple addresses may be separated by new lines or pipes; take the first.
			$parts = preg_split( '/[\r\n|]+/', (string) $atts['address'] );
			if ( ! empty( $parts[0] ) && '' !== trim( $parts[0] ) ) {
				return trim( $parts[0] );
			}
		}

		if ( '' !== $content && preg_match( '/address\s*=\s*("|\')(.*?)\1/i', $content, $match ) ) {
			return trim( $match[2] );
		}

		return '';
	}

	/**
	 * Mark the Google Maps JS API script as consent-blocked (src-based).
	 *
	 * Handle-agnostic: matches the source URL, so it is robust across Avada
	 * versions and other plugins that load the same API.
	 *
	 * @param string $tag    The full <script> HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string
	 */
	public static function block_maps_api( $tag, $handle, $src ) {
		unset( $handle );

		if ( false === strpos( (string) $src, self::MAPS_API_NEEDLE ) ) {
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
}
