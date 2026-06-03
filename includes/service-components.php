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
		$atts     = shortcode_atts(
			array(
				'id'           => '',
				'title'        => '',
				'thumbnail_id' => '',
			),
			$atts,
			'lscc_youtube'
		);
		$video_id = self::extract_youtube_id( $atts['id'] );

		if ( '' === $video_id ) {
			return '';
		}

		$title = '' !== trim( (string) $atts['title'] )
			? sanitize_text_field( $atts['title'] )
			: __( 'YouTube-Video', 'light-swiss-cookie-consent' );

		return self::render_component(
			'youtube',
			'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video_id ),
			$title,
			__( 'Dieses YouTube-Video wird erst nach Zustimmung zu externen Medien geladen.', 'light-swiss-cookie-consent' ),
			self::resolve_youtube_thumbnail_html( $atts['thumbnail_id'], $video_id )
		);
	}

	/**
	 * Extract a YouTube video id from a raw id or a YouTube URL.
	 *
	 * Accepts raw ids ("dQw4w9WgXcQ") as well as common URL forms
	 * (youtu.be/ID, watch?v=ID, /embed/ID, /v/ID). Returns '' when nothing
	 * usable can be parsed. A bare id is passed through sanitize_media_id() so
	 * existing [lscc_youtube id="VIDEO_ID"] usages stay byte-compatible.
	 *
	 * @param mixed $raw Raw value from the shortcode `id` attribute.
	 * @return string
	 */
	public static function extract_youtube_id( $raw ) {
		$raw = trim( (string) $raw );

		if ( '' === $raw ) {
			return '';
		}

		$looks_like_url = ( false !== strpos( $raw, '://' ) ) || ( false !== stripos( $raw, 'youtu' ) && false !== strpos( $raw, '/' ) );

		if ( $looks_like_url ) {
			if ( preg_match( '#youtu\.be/([A-Za-z0-9_-]{6,})#i', $raw, $match ) ) {
				return $match[1];
			}
			if ( preg_match( '#[?&]v=([A-Za-z0-9_-]{6,})#i', $raw, $match ) ) {
				return $match[1];
			}
			if ( preg_match( '#/(?:embed|v)/([A-Za-z0-9_-]{6,})#i', $raw, $match ) ) {
				return $match[1];
			}

			return '';
		}

		return self::sanitize_media_id( $raw );
	}

	/**
	 * Resolve the thumbnail markup for a YouTube component.
	 *
	 * Priority: a local media-library attachment (always privacy-safe) wins.
	 * Otherwise, only if the admin opted in via `youtube_remote_thumbnails`
	 * (default off), a remote i.ytimg.com preview image is used. NOTE: the
	 * remote variant loads an image from Google BEFORE consent — a deliberate
	 * operator trade-off. No iframe and no youtube.com cookies are involved
	 * before consent in either case.
	 *
	 * @param mixed  $thumbnail_id Raw attachment id from the shortcode.
	 * @param string $video_id     Sanitized YouTube video id.
	 * @return string Escaped <img> markup or '' for the plain placeholder.
	 */
	private static function resolve_youtube_thumbnail_html( $thumbnail_id, $video_id ) {
		$local = self::get_local_thumbnail_html( $thumbnail_id );

		if ( '' !== $local ) {
			return $local;
		}

		$options = Light_Swiss_Cookie_Consent::get_options();

		if ( ! empty( $options['youtube_remote_thumbnails'] ) && '' !== $video_id ) {
			return sprintf(
				'<img class="lscc-media__thumb" src="%s" loading="lazy" alt="" referrerpolicy="no-referrer" />',
				esc_url( 'https://i.ytimg.com/vi/' . rawurlencode( $video_id ) . '/hqdefault.jpg' )
			);
		}

		return '';
	}

	/**
	 * Resolve a local media-library attachment to safe <img> markup.
	 *
	 * Only a numeric WordPress attachment ID is accepted. There is no external
	 * image source, no auto-fetch from the video ID and no request to YouTube
	 * or Google. When no valid local image exists, an empty string is returned
	 * and the component silently falls back to the plain placeholder.
	 *
	 * @param mixed $thumbnail_id Raw attachment ID from the shortcode.
	 * @return string Escaped <img> markup, or '' when no valid local image exists.
	 */
	private static function get_local_thumbnail_html( $thumbnail_id ) {
		$attachment_id = absint( $thumbnail_id );

		if ( $attachment_id < 1 ) {
			return '';
		}

		if ( 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
			return '';
		}

		return wp_get_attachment_image(
			$attachment_id,
			'large',
			false,
			array(
				'class'   => 'lscc-media__thumb',
				'loading' => 'lazy',
			)
		);
	}

	/**
	 * Render a controlled Vimeo component.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_vimeo( $atts ) {
		$atts     = shortcode_atts(
			array(
				'id'           => '',
				'thumbnail_id' => '',
			),
			$atts,
			'lscc_vimeo'
		);
		$video_id = self::sanitize_media_id( $atts['id'] );

		if ( '' === $video_id ) {
			return '';
		}

		return self::render_component(
			'vimeo',
			'https://player.vimeo.com/video/' . rawurlencode( $video_id ),
			__( 'Vimeo-Video', 'light-swiss-cookie-consent' ),
			__( 'Dieses Vimeo-Video wird erst nach Zustimmung zu externen Medien geladen.', 'light-swiss-cookie-consent' ),
			self::get_local_thumbnail_html( $atts['thumbnail_id'] )
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
	 * @param string $service        Service key.
	 * @param string $src            Iframe source.
	 * @param string $title          Iframe title.
	 * @param string $notice         Placeholder notice.
	 * @param string $thumbnail_html Optional pre-escaped local <img> markup. When non-empty,
	 *                               a thumbnail layer and a centered play button are rendered.
	 * @return string
	 */
	private static function render_component( $service, $src, $title, $notice, $thumbnail_html = '' ) {
		$options      = Light_Swiss_Cookie_Consent::get_options();
		$style        = Light_Swiss_Cookie_Consent::get_css_variables( $options );
		$button_label = __( 'Externe Medien akzeptieren', 'light-swiss-cookie-consent' );
		$notice_id    = wp_unique_id( 'lscc-media-notice-' );
		$has_thumb    = '' !== $thumbnail_html;
		$show_play    = $has_thumb || 'youtube' === $service || 'vimeo' === $service;

		$play_markup = '';

		if ( $show_play ) {
			// data-lscc-autoplay marks this trigger so banner.js starts the video
			// right after consent when the visitor used the play button.
			$play_markup = sprintf(
				'<button type="button" class="lscc-media__play" data-lscc-accept-media data-lscc-autoplay aria-describedby="%1$s" aria-label="%2$s"></button>',
				esc_attr( $notice_id ),
				esc_attr( $button_label )
			);
		}

		return sprintf(
			'<div class="lscc-media lscc-media--%1$s%2$s" style="%3$s" data-lscc-media data-lscc-category="external_media" data-lscc-src="%4$s" data-lscc-title="%5$s" data-lscc-service="%1$s">%6$s<div class="lscc-media__placeholder">%7$s<p id="%8$s" class="lscc-media__notice">%9$s</p><button type="button" class="lscc-media__button" data-lscc-accept-media aria-describedby="%8$s" aria-label="%10$s">%11$s</button></div></div>',
			esc_attr( $service ),
			$has_thumb ? ' lscc-media--has-thumb' : '',
			esc_attr( $style ),
			esc_url( $src ),
			esc_attr( $title ),
			$has_thumb ? $thumbnail_html : '',
			$play_markup,
			esc_attr( $notice_id ),
			esc_html( $notice ),
			esc_attr( $button_label ),
			esc_html( $button_label )
		);
	}
}
