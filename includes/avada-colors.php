<?php
/**
 * Avada brand color import (admin-only, read-only).
 *
 * Reads a single brand color from the active Avada theme options and maps it
 * onto the banner accent colors. No hooks, no frontend code, no auto-apply:
 * the import runs only on an explicit operator click (see ADR-27). Assumes a
 * current Avada version; no legacy/6.x compatibility paths.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only Avada brand color reader/mapper.
 */
final class Macs_Cookie_Banner_Avada_Colors {

	/**
	 * Avada theme option keys queried for the brand color, in priority order.
	 * First valid hit wins: main color first, gradient only as last resort.
	 *
	 * @var array
	 */
	const BRAND_KEYS = array( 'primary_color', 'accent_color', 'link_color', 'button_gradient_top_color' );

	/**
	 * Whether the active theme is Avada.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return function_exists( 'Avada' ) || class_exists( 'Avada' ) || defined( 'AVADA_VERSION' );
	}

	/**
	 * Resolve the brand color: the first valid hex in the priority chain.
	 *
	 * @return string Hex color (e.g. "#1e73be") or '' when nothing resolves.
	 */
	public static function get_brand_color() {
		foreach ( self::BRAND_KEYS as $key ) {
			$hex = self::resolve_color( self::read_raw( $key ) );
			if ( '' !== $hex ) {
				return $hex;
			}
		}

		return '';
	}

	/**
	 * Read a single Avada theme option value defensively.
	 *
	 * @param string $key Avada option key.
	 * @return string Raw value, or '' on any failure.
	 */
	public static function read_raw( $key ) {
		if ( function_exists( 'fusion_get_option' ) ) {
			$value = fusion_get_option( $key );
			if ( is_string( $value ) && '' !== $value ) {
				return $value;
			}
		}

		if ( function_exists( 'Avada' ) ) {
			$avada = Avada();
			if ( is_object( $avada ) && isset( $avada->settings ) && is_object( $avada->settings ) && method_exists( $avada->settings, 'get' ) ) {
				$value = $avada->settings->get( $key );
				if ( is_string( $value ) && '' !== $value ) {
					return $value;
				}
			}
		}

		$options = get_option( 'fusion_options' );
		if ( is_array( $options ) && isset( $options[ $key ] ) && is_string( $options[ $key ] ) ) {
			return $options[ $key ];
		}

		return '';
	}

	/**
	 * Resolve a raw Avada color value to a hex color.
	 *
	 * Accepts a plain hex (sanitized) and a single Avada global color reference
	 * `var(--awb-colorN)` / `--awb-colorN`, resolved against the color palette.
	 *
	 * @param string $value Raw value.
	 * @return string Hex color, or '' when it cannot be resolved.
	 */
	public static function resolve_color( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value ) {
			return '';
		}

		$hex = sanitize_hex_color( $value );
		if ( $hex ) {
			return $hex;
		}

		if ( preg_match( '/--awb-color\d+/', $value, $matches ) ) {
			$palette = self::get_palette();
			$slug    = ltrim( $matches[0], '-' );
			if ( isset( $palette[ $slug ] ) ) {
				$resolved = sanitize_hex_color( $palette[ $slug ] );
				return $resolved ? $resolved : '';
			}
		}

		return '';
	}

	/**
	 * Read the Avada global color palette as slug => raw color.
	 *
	 * @return array
	 */
	private static function get_palette() {
		$map     = array();
		$palette = function_exists( 'fusion_get_option' ) ? fusion_get_option( 'color_palette' ) : '';

		if ( ! is_array( $palette ) ) {
			$options = get_option( 'fusion_options' );
			if ( is_array( $options ) && isset( $options['color_palette'] ) && is_array( $options['color_palette'] ) ) {
				$palette = $options['color_palette'];
			}
		}

		if ( is_array( $palette ) ) {
			foreach ( $palette as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$slug = isset( $entry['id'] ) ? $entry['id'] : ( isset( $entry['slug'] ) ? $entry['slug'] : '' );
				$col  = isset( $entry['color'] ) ? $entry['color'] : '';
				if ( '' !== $slug && '' !== $col ) {
					$map[ $slug ] = $col;
				}
			}
		}

		return $map;
	}

	/**
	 * Map a brand color onto the banner accent color keys.
	 *
	 * Sets the primary button and the border to the brand color and computes a
	 * readable button text color. Secondary button, background, text and overlay
	 * are deliberately left untouched.
	 *
	 * @param string $brand Hex brand color.
	 * @return array Map of banner option key => hex (empty when no brand color).
	 */
	public static function map_to_banner( $brand ) {
		$brand = is_string( $brand ) ? sanitize_hex_color( $brand ) : '';
		if ( ! $brand ) {
			return array();
		}

		return array(
			'primary_button_color' => $brand,
			'border_color'         => $brand,
			'primary_text_color'   => self::contrast_color( $brand ),
		);
	}

	/**
	 * Pick a readable text color (white or the banner's dark default) for a
	 * given background hex, using WCAG contrast ratios.
	 *
	 * @param string $hex Background hex color.
	 * @return string Hex text color (#ffffff or #111827).
	 */
	public static function contrast_color( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$r = hexdec( str_repeat( $hex[0], 2 ) );
			$g = hexdec( str_repeat( $hex[1], 2 ) );
			$b = hexdec( str_repeat( $hex[2], 2 ) );
		} elseif ( 6 === strlen( $hex ) ) {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		} else {
			return '#ffffff';
		}

		$bg   = self::relative_luminance( $r, $g, $b );
		$dark = self::relative_luminance( 0x11, 0x18, 0x27 ); // #111827, banner default text.

		$contrast_white = ( 1.0 + 0.05 ) / ( $bg + 0.05 );
		$contrast_dark  = ( $bg + 0.05 ) / ( $dark + 0.05 );

		return ( $contrast_white >= $contrast_dark ) ? '#ffffff' : '#111827';
	}

	/**
	 * WCAG relative luminance for an 8-bit sRGB color.
	 *
	 * @param int $r Red channel (0-255).
	 * @param int $g Green channel (0-255).
	 * @param int $b Blue channel (0-255).
	 * @return float
	 */
	private static function relative_luminance( $r, $g, $b ) {
		return 0.2126 * self::linearize( $r ) + 0.7152 * self::linearize( $g ) + 0.0722 * self::linearize( $b );
	}

	/**
	 * Linearize a single 8-bit sRGB channel.
	 *
	 * @param int $c Channel value (0-255).
	 * @return float
	 */
	private static function linearize( $c ) {
		$c = $c / 255;

		return ( $c <= 0.03928 ) ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}
}
