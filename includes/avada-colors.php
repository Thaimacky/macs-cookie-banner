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
	 * Return the CSS custom-property names the Avada brand color references, in
	 * priority order (e.g. array( '--awb-color5' )).
	 *
	 * When server-side palette resolution fails, the admin import lets the
	 * browser resolve these variables via getComputedStyle() and submits the
	 * resulting hex back. No assumption about the color number/count: whatever
	 * `var(--awb-colorX)` the customer's primary color points to is returned.
	 * Empty when the brand color is already a direct hex or no reference exists.
	 *
	 * @return string[] List of CSS variable names (deduplicated, priority order).
	 */
	public static function get_brand_css_vars() {
		$vars = array();

		foreach ( self::BRAND_KEYS as $key ) {
			$raw = self::read_raw( $key );
			if ( is_string( $raw ) && preg_match( '/(--[a-z0-9_-]+)/i', $raw, $m ) ) {
				if ( ! in_array( $m[1], $vars, true ) ) {
					$vars[] = $m[1];
				}
			}
		}

		return $vars;
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
	 * Accepts a plain hex (sanitized) and ANY Avada global-color reference of the
	 * form `var(--awb-colorN)`, `var(--awb-custom_color_N)` or a bare `--awb-...`
	 * token — with no assumption about how many global colors exist or which
	 * number the brand color points to. The reference is matched dynamically
	 * against the live palette built in get_palette().
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

		// Any CSS custom-property reference, e.g. var(--awb-color5),
		// var(--awb-color27) or var(--awb-custom_color_3).
		if ( preg_match( '/--([a-z0-9_-]+)/i', $value, $matches ) ) {
			$palette = self::get_palette();
			$token   = self::normalize_token( $matches[1] );
			if ( '' !== $token && isset( $palette[ $token ] ) ) {
				return $palette[ $token ];
			}
		}

		return '';
	}

	/**
	 * Build the Avada global color palette as a normalized token => hex map.
	 *
	 * Structure-agnostic: each palette entry may be an associative array
	 * (`color` plus an `id`/`slug`/key) or a plain color string, and entries may
	 * be keyed by identifier or by position. The CSS variable `--awb-colorN` that
	 * Avada emits maps to the N-th palette entry, so position is the canonical
	 * source ("colorN"); any explicit identifier is honored as well and wins over
	 * position. No fixed count or numbering is assumed.
	 *
	 * @return array Map of normalized color token (e.g. "color5", "customcolor3") => hex.
	 */
	private static function get_palette() {
		$palette = self::read_palette_raw();
		if ( ! is_array( $palette ) ) {
			return array();
		}

		$by_id       = array();
		$by_position = array();
		$position    = 0;

		foreach ( $palette as $key => $entry ) {
			$position++;

			if ( is_array( $entry ) ) {
				$raw_color = isset( $entry['color'] ) ? $entry['color'] : '';
				$ids       = array();
				foreach ( array( 'id', 'slug', 'key', 'name' ) as $id_key ) {
					if ( isset( $entry[ $id_key ] ) && is_string( $entry[ $id_key ] ) && '' !== $entry[ $id_key ] ) {
						$ids[] = $entry[ $id_key ];
					}
				}
			} else {
				$raw_color = is_string( $entry ) ? $entry : '';
				$ids       = array();
			}

			if ( is_string( $key ) && '' !== $key ) {
				$ids[] = $key;
			}

			$hex = self::color_value_to_hex( $raw_color );
			if ( '' === $hex ) {
				continue;
			}

			foreach ( $ids as $id ) {
				$token = self::normalize_token( $id );
				if ( '' !== $token && ! isset( $by_id[ $token ] ) ) {
					$by_id[ $token ] = $hex;
				}
			}

			$by_position[ 'color' . $position ] = $hex;
		}

		// Explicit identifiers win; positional mapping fills the gaps so a palette
		// that stores colors without ids still resolves.
		return $by_id + $by_position;
	}

	/**
	 * Read the raw Avada global color palette from the available sources.
	 *
	 * @return array Raw palette array, or empty array when unavailable.
	 */
	private static function read_palette_raw() {
		if ( function_exists( 'fusion_get_option' ) ) {
			$palette = fusion_get_option( 'color_palette' );
			if ( is_array( $palette ) ) {
				return $palette;
			}
		}

		if ( function_exists( 'Avada' ) ) {
			$avada = Avada();
			if ( is_object( $avada ) && isset( $avada->settings ) && is_object( $avada->settings ) && method_exists( $avada->settings, 'get' ) ) {
				$palette = $avada->settings->get( 'color_palette' );
				if ( is_array( $palette ) ) {
					return $palette;
				}
			}
		}

		$options = get_option( 'fusion_options' );
		if ( is_array( $options ) && isset( $options['color_palette'] ) && is_array( $options['color_palette'] ) ) {
			return $options['color_palette'];
		}

		return array();
	}

	/**
	 * Normalize a color identifier or reference token for comparison.
	 *
	 * Lowercases, strips every non-alphanumeric character and a leading "awb"
	 * prefix so that `--awb-color5`, `awb-color5`, `color_5` and a positional
	 * `color5` all collapse to the same token ("color5"); custom colors collapse
	 * to e.g. "customcolor3".
	 *
	 * @param string $token Raw identifier or reference token.
	 * @return string Normalized token, or '' when empty.
	 */
	private static function normalize_token( $token ) {
		$token = strtolower( (string) $token );
		$token = preg_replace( '/[^a-z0-9]/', '', $token );
		$token = preg_replace( '/^awb/', '', (string) $token );

		return (string) $token;
	}

	/**
	 * Convert a stored palette color value to a hex color.
	 *
	 * Accepts a hex value directly and converts an rgb()/rgba() value to hex
	 * (alpha dropped), so global colors defined with transparency still resolve.
	 *
	 * @param string $value Raw color value.
	 * @return string Hex color, or '' when it cannot be parsed.
	 */
	private static function color_value_to_hex( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value ) {
			return '';
		}

		$hex = sanitize_hex_color( $value );
		if ( $hex ) {
			return $hex;
		}

		if ( preg_match( '/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i', $value, $m ) ) {
			return sprintf(
				'#%02x%02x%02x',
				min( 255, (int) $m[1] ),
				min( 255, (int) $m[2] ),
				min( 255, (int) $m[3] )
			);
		}

		return '';
	}

	/**
	 * Reset the Avada/Fusion caches via the theme's own API.
	 *
	 * After the brand color is written, Avada/Fusion keeps serving the inline CSS
	 * it generated earlier (the cached `--lscc-primary` custom property) until its
	 * own cache is rebuilt — which is why the banner kept showing the previous
	 * color until a manual Avada/browser cache flush (root cause, see ADR-29).
	 * This calls Avada's existing cache-reset API only; no custom cache handling
	 * is introduced. The known entry points are tried in order and the first one
	 * that exists wins.
	 *
	 * @return bool True when an Avada/Fusion cache-reset entry point was invoked.
	 */
	public static function reset_caches() {
		// 1) Canonical Fusion helper: resets the dynamic CSS and Fusion caches.
		if ( function_exists( 'fusion_reset_all_caches' ) ) {
			fusion_reset_all_caches();
			return true;
		}

		// 2) Fusion_Cache class API (same effect on alternate/older Avada builds).
		if ( class_exists( 'Fusion_Cache' ) && method_exists( 'Fusion_Cache', 'reset_all_caches' ) ) {
			$cache = new Fusion_Cache();
			$cache->reset_all_caches();
			return true;
		}

		return false;
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
