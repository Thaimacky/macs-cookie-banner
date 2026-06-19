<?php
/**
 * Avada (Fusion Builder) compatibility: consent-gate fusion_youtube.
 *
 * Intercepts Avada's `fusion_youtube` shortcode at the render layer (before the
 * iframe is produced) and replaces it with the existing Light Swiss Cookie
 * Consent placeholder markup. The YouTube iframe is only built client-side by
 * the existing banner.js once the visitor consents to the `external_media`
 * category. There is no DOM hijacking, no MutationObserver, no front-end
 * scanner and no external request before consent.
 *
 * Scope (v0.1.9): YouTube only. Vimeo, Maps, background videos, fusion_code and
 * raw iframes are intentionally not handled here.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render-layer interception for Avada video elements.
 */
final class Macs_Cookie_Banner_Avada_Compat {
	/**
	 * Register the interception filter on the front end when enabled.
	 *
	 * @return void
	 */
	public static function init() {
		// Never touch the admin / builder backend rendering.
		if ( is_admin() ) {
			return;
		}

		$options = Macs_Cookie_Banner::get_options();

		if ( empty( $options['avada_youtube_block'] ) ) {
			return;
		}

		add_filter( 'pre_do_shortcode_tag', array( __CLASS__, 'intercept' ), 10, 4 );
	}

	/**
	 * Short-circuit the fusion_youtube shortcode with a consent placeholder.
	 *
	 * @param false|string $output Short-circuit return value (false = render normally).
	 * @param string       $tag    Shortcode tag being processed.
	 * @param array|string $attr   Shortcode attributes.
	 * @param array        $m      Regular expression match array.
	 * @return false|string
	 */
	public static function intercept( $output, $tag, $attr, $m ) {
		unset( $m );

		if ( 'fusion_youtube' !== $tag ) {
			return $output;
		}

		$atts     = is_array( $attr ) ? $attr : array();
		$raw_id   = isset( $atts['id'] ) ? $atts['id'] : '';
		$video_id = Macs_Cookie_Banner_Service_Components::extract_youtube_id( $raw_id );

		// If we cannot safely determine the video id, do not break the page:
		// let Avada render its original output.
		if ( '' === $video_id ) {
			return $output;
		}

		$markup = Macs_Cookie_Banner_Service_Components::render_youtube( array( 'id' => $video_id ) );

		if ( '' === $markup ) {
			return $output;
		}

		return $markup;
	}
}
