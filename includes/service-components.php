<?php
/**
 * Controlled external media service components.
 *
 * @package LightSwissCookieConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service component shortcodes.
 */
final class Light_Swiss_Cookie_Consent_Service_Components {
	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'lscc_youtube', array( __CLASS__, 'render_youtube' ) );
		add_shortcode( 'lscc_vimeo', array( __CLASS__, 'render_vimeo' ) );
		add_shortcode( 'lscc_google_map', array( __CLASS__, 'render_google_map' ) );
	}

	/**
	 * Render a controlled YouTube component.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_youtube( $atts ) {
		$atts     = shortcode_atts( array( 'id' => '' ), $atts, 'lscc_youtube' );
		$video_id = self::sanitize_media_id( $atts['id'] );

		if ( '' === $video_id ) {
			return '';
		}

		return self::render_component(
			'youtube',
			'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video_id ),
			__( 'YouTube-Video', 'light-swiss-cookie-consent' ),
			__( 'Dieses YouTube-Video wird erst nach Zustimmung zu externen Medien geladen.', 'light-swiss-cookie-consent' )
		);
	}

	/**
	 * Render a controlled Vimeo component.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_vimeo( $atts ) {
		$atts     = shortcode_atts( array( 'id' => '' ), $atts, 'lscc_vimeo' );
		$video_id = self::sanitize_media_id( $atts['id'] );

		if ( '' === $video_id ) {
			return '';
		}

		return self::render_component(
			'vimeo',
			'https://player.vimeo.com/video/' . rawurlencode( $video_id ),
			__( 'Vimeo-Video', 'light-swiss-cookie-consent' ),
			__( 'Dieses Vimeo-Video wird erst nach Zustimmung zu externen Medien geladen.', 'light-swiss-cookie-consent' )
		);
	}

	/**
	 * Render a controlled Google Maps component.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_google_map( $atts ) {
		$atts = shortcode_atts( array( 'url' => '' ), $atts, 'lscc_google_map' );
		$url  = self::sanitize_google_maps_url( $atts['url'] );

		if ( '' === $url ) {
			return '';
		}

		return self::render_component(
			'google-map',
			$url,
			__( 'Google Maps', 'light-swiss-cookie-consent' ),
			__( 'Diese Google-Maps-Karte wird erst nach Zustimmung zu externen Medien geladen.', 'light-swiss-cookie-consent' )
		);
	}

	/**
	 * Sanitize a media ID.
	 *
	 * @param string $media_id Raw media ID.
	 * @return string
	 */
	private static function sanitize_media_id( $media_id ) {
		$media_id = sanitize_text_field( (string) $media_id );

		return preg_replace( '/[^A-Za-z0-9_-]/', '', $media_id );
	}

	/**
	 * Sanitize and limit Google Maps iframe URLs.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	private static function sanitize_google_maps_url( $url ) {
		$url  = esc_url_raw( trim( (string) $url ) );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( ! $host || ! $path ) {
			return '';
		}

		$host = strtolower( $host );
		$path = strtolower( $path );

		if ( ! preg_match( '/(^|\.)google\.[a-z.]+$/', $host ) && 'maps.google.com' !== $host ) {
			return '';
		}

		if ( false === strpos( $path, '/maps' ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Render a passive placeholder component.
	 *
	 * @param string $service Service key.
	 * @param string $src     Iframe source.
	 * @param string $title   Iframe title.
	 * @param string $notice  Placeholder notice.
	 * @return string
	 */
	private static function render_component( $service, $src, $title, $notice ) {
		$options      = Light_Swiss_Cookie_Consent::get_options();
		$style        = Light_Swiss_Cookie_Consent::get_css_variables( $options );
		$button_label = __( 'Externe Medien akzeptieren', 'light-swiss-cookie-consent' );
		$notice_id    = wp_unique_id( 'lscc-media-notice-' );

		return sprintf(
			'<div class="lscc-media lscc-media--%1$s" style="%2$s" data-lscc-media data-lscc-category="external_media" data-lscc-src="%3$s" data-lscc-title="%4$s" data-lscc-service="%1$s"><div class="lscc-media__placeholder"><p id="%5$s" class="lscc-media__notice">%6$s</p><button type="button" class="lscc-media__button" data-lscc-accept-media aria-describedby="%5$s" aria-label="%7$s">%8$s</button></div></div>',
			esc_attr( $service ),
			esc_attr( $style ),
			esc_url( $src ),
			esc_attr( $title ),
			esc_attr( $notice_id ),
			esc_html( $notice ),
			esc_attr( $button_label ),
			esc_html( $button_label )
		);
	}
}
