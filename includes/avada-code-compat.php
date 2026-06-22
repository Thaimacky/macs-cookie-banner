<?php
/**
 * Avada (Fusion Builder) Code Block compatibility: consent-gate a RAW Google
 * Maps embed iframe pasted into an Avada Code Block ([fusion_code]).
 *
 * Problem this closes (proven): a raw <iframe src="https://www.google.com/maps/
 * embed?..."> inside an Avada Code Block is server-rendered independent of
 * consent and carries none of the LSCC markers (no data-lscc-media, no
 * data-cookie-category). banner.js therefore never gates it — it loads on every
 * page view, including "necessary only" and after a revocation.
 *
 * Scope is deliberately NARROW (opt-in, default OFF):
 * - ONLY the `fusion_code` shortcode (Avada Code Block).
 * - ONLY when its content is exactly ONE Google Maps embed iframe.
 * - Anything else (other iframes, YouTube/Vimeo, scripts, mixed markup) is left
 *   untouched and Avada renders normally — no content is ever destroyed.
 *
 * This is render-pipeline interception of Avada's own shortcode (like
 * avada-maps-compat.php for fusion_map), NOT a global iframe blockade, NOT a
 * MutationObserver, NOT a the_content rewrite. Fully reversible (switch off).
 *
 * The replacement reuses the EXISTING placeholder component
 * (Service_Components::render_google_map) and the existing external_media gate
 * in banner.js — no new front-end logic, no new placeholder architecture.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render-layer consent gating for raw Google Maps iframes in Avada Code Blocks.
 */
final class Macs_Cookie_Banner_Avada_Code_Compat {
	/**
	 * Option key toggling this module (default OFF, opt-in).
	 */
	const OPTION_KEY = 'avada_code_maps_block';

	/**
	 * Register the gating filter on the front end when enabled.
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

		add_filter( 'pre_do_shortcode_tag', array( __CLASS__, 'intercept' ), 10, 4 );
	}

	/**
	 * Short-circuit a fusion_code block that contains exactly one Google Maps
	 * embed iframe with the consent-gated LSCC placeholder.
	 *
	 * @param false|string $output Short-circuit return value (false = render normally).
	 * @param string       $tag    Shortcode tag.
	 * @param array|string $attr   Shortcode attributes (unused).
	 * @param array        $m      Regex match array ($m[5] = enclosed content).
	 * @return false|string
	 */
	public static function intercept( $output, $tag, $attr, $m ) {
		unset( $attr );

		if ( 'fusion_code' !== $tag ) {
			return $output;
		}

		$raw = isset( $m[5] ) ? (string) $m[5] : '';

		if ( '' === trim( $raw ) ) {
			return $output;
		}

		$decoded = self::decode_fusion_code( $raw );

		if ( '' === $decoded ) {
			return $output;
		}

		$map = self::extract_maps_embed( $decoded );

		// Not a clean single Maps embed: let Avada render unchanged (no destruction).
		if ( null === $map ) {
			return $output;
		}

		// Reuse the existing placeholder component; it re-validates the URL via its
		// own host/path allowlist and returns '' on anything unexpected. The original
		// width/height/title are carried over so the visible geometry is preserved.
		$markup = Macs_Cookie_Banner_Service_Components::render_google_map(
			array(
				'url'    => $map['src'],
				'width'  => $map['width'],
				'height' => $map['height'],
				'title'  => $map['title'],
			)
		);

		return '' !== $markup ? $markup : $output;
	}

	/**
	 * Decode the Avada Code Block payload to HTML.
	 *
	 * Avada stores Code Block content base64-encoded; some edge cases store raw
	 * HTML. Returns the HTML only when it plausibly contains an iframe, else ''.
	 *
	 * @param string $content Raw shortcode content ($m[5]).
	 * @return string Decoded HTML containing an iframe, or '' when not applicable.
	 */
	public static function decode_fusion_code( $content ) {
		$content = trim( (string) $content );

		if ( '' === $content ) {
			return '';
		}

		// Already raw HTML with an iframe (non-base64 edge case).
		if ( false !== stripos( $content, '<iframe' ) ) {
			return $content;
		}

		// Base64 candidate: strict charset, decodes cleanly, yields an iframe.
		// Strip whitespace first so newlines in the payload do not trip strict mode.
		$candidate = preg_replace( '/\s+/', '', $content );

		if ( is_string( $candidate ) && '' !== $candidate && preg_match( '#^[A-Za-z0-9+/=]+$#', $candidate ) ) {
			$maybe = base64_decode( $candidate, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding Avada Code Block payload, not obfuscation.

			if ( false !== $maybe && '' !== $maybe && false !== stripos( $maybe, '<iframe' ) ) {
				return $maybe;
			}
		}

		return '';
	}

	/**
	 * Return the Maps embed details ONLY when the decoded HTML is exactly one
	 * Google Maps embed iframe and nothing else of substance. Conservative by
	 * design: any extra iframe, a <script>, or leftover visible content yields null.
	 *
	 * @param string $html Decoded Code Block HTML.
	 * @return array|null { src, width, height, title } or null when not a clean single Maps embed.
	 */
	public static function extract_maps_embed( $html ) {
		$html = (string) $html;

		// Never touch blocks that also ship script (avoid breaking custom JS embeds).
		if ( false !== stripos( $html, '<script' ) ) {
			return null;
		}

		// Exactly one iframe opening tag.
		if ( ! preg_match_all( '#<iframe\b[^>]*>#i', $html, $tags ) || 1 !== count( $tags[0] ) ) {
			return null;
		}

		$tag = $tags[0][0];
		$src = self::tag_attr( $tag, 'src' );

		if ( '' === $src ) {
			return null;
		}

		$lc = strtolower( $src );

		// Must be a Google Maps EMBED iframe specifically.
		if ( false === strpos( $lc, '/maps/embed' ) || false === strpos( $lc, 'google.' ) ) {
			return null;
		}

		// Remove the whole iframe element; if anything of substance remains
		// (text or other embeds), do NOT replace — Avada renders the original.
		$remainder = preg_replace( '#<iframe\b[^>]*>.*?</iframe>#is', '', $html );
		$remainder = preg_replace( '#<iframe\b[^>]*/?>#i', '', (string) $remainder );
		$remainder = trim( wp_strip_all_tags( (string) $remainder ) );

		if ( '' !== $remainder ) {
			return null;
		}

		return array(
			'src'    => $src,
			'width'  => self::tag_attr( $tag, 'width' ),
			'height' => self::tag_attr( $tag, 'height' ),
			'title'  => self::tag_attr( $tag, 'title' ),
		);
	}

	/**
	 * Read a single attribute value from an iframe opening tag.
	 *
	 * @param string $tag  The <iframe ...> opening tag.
	 * @param string $name Attribute name.
	 * @return string Attribute value or ''.
	 */
	private static function tag_attr( $tag, $name ) {
		if ( preg_match( '/\b' . preg_quote( $name, '/' ) . '\s*=\s*("|\')(.*?)\1/i', (string) $tag, $m ) ) {
			return trim( $m[2] );
		}

		return '';
	}

	/**
	 * Whether a post's content holds an Avada Code Block with a raw Google Maps
	 * embed iframe. Used by the Privacy Check content scan to surface the case.
	 *
	 * @param string $content Raw post_content.
	 * @return bool
	 */
	public static function content_has_code_block_map( $content ) {
		$content = (string) $content;

		if ( false === stripos( $content, '[fusion_code' ) ) {
			return false;
		}

		if ( ! preg_match_all( '#\[fusion_code\b[^\]]*\](.*?)\[/fusion_code\]#is', $content, $blocks, PREG_SET_ORDER ) ) {
			return false;
		}

		foreach ( $blocks as $block ) {
			$decoded = self::decode_fusion_code( $block[1] );

			if ( '' !== $decoded && null !== self::extract_maps_embed( $decoded ) ) {
				return true;
			}
		}

		return false;
	}
}
