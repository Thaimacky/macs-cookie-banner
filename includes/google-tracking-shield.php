<?php
/**
 * Google Tracking Shield: source-independent consent gating for Google
 * Analytics 4 / Universal Analytics / Google Tag Manager / Google Ads script
 * tags in the final rendered frontend HTML (v1.0.7, ADR-40).
 *
 * Problem this closes (proven, real-world case): a raw
 * <script async src="https://www.googletagmanager.com/gtag/js?id=G-...">
 * plus its gtag('config', ...) inline bootstrap can appear in the delivered
 * HTML regardless of which plugin/theme/builder/header field produced it
 * (Google Site Kit, a GTM plugin, an Avada theme "scripts in head" field,
 * Rank Math, raw custom code, ...). None of these paths are enqueued via
 * wp_enqueue_script(), so the existing script_loader_tag-based modules
 * (yotu-compat, avada-maps-compat, meta-social-compat) structurally cannot
 * see them — they only ever see properly registered/enqueued assets.
 *
 * This module closes exactly that gap for Google Analytics/GTM/Ads, and
 * ONLY that gap. It is deliberately NOT the full "Universal Tracking
 * Protection" architecture discussed separately — no generic vendor list,
 * no browser-side network guard, no CSP. Narrow, proven, reversible.
 *
 * Mechanism (two layers, no new consent engine):
 *
 * Layer A (primary) - external <script src="..."> tags. Uses WordPress
 * Core's own WP_HTML_Tag_Processor (available since WP 6.2) purely at the
 * ATTRIBUTE level: walk every <script> opening tag, read its `src`, and if
 * it matches a KNOWN Google Analytics/GTM/Ads loader URL (via the existing,
 * already-shipped Consent_Codes::match_vendor() classifier - the same
 * engine the Consent-Code-Manager and Privacy Check already trust), rewrite
 * `type` to "text/plain" + the LSCC gating attributes. No raw-text/DOM
 * parsing involved - this is the well-established, safe use of the API and
 * alone already closes the reported real-world case.
 *
 * Layer B (secondary, narrow) - pure INLINE <script>...</script> blocks
 * (no `src`) that are the accompanying gtag('config', ...) / gtm.start
 * bootstrap. WP_HTML_Tag_Processor does not expose inline script text for
 * safe rewriting, so this layer uses a tag-boundary-anchored
 * preg_replace_callback (</script> cannot legally appear inside a script
 * body - the same boundary rule already relied on elsewhere in this plugin,
 * e.g. Consent_Codes::transform_snippet() and Avada_Code_Compat's iframe
 * extraction). A block is only gated when its ENTIRE trimmed content
 * classifies as ga4/gtm/google_ads via the same match_vendor() engine -
 * never on a loose keyword like "google"/"dataLayer"/"analytics"/"collect".
 * This is NOT the "fragile global regex" the product owner explicitly ruled
 * out (a page-wide, tag-unaware string replace); it is the same narrow,
 * tag-scoped transform class already used and accepted in this codebase.
 *
 * Both layers reuse Consent_Codes::transform_snippet() / the same
 * type="text/plain" + data-cookie-category rewrite shape used everywhere
 * else in the plugin, so reactivation after consent runs entirely through
 * the existing, unmodified banner.js::activateBlockedScripts() sequential
 * activation - no new frontend code, no second consent engine.
 *
 * Scope guard: the output buffer only ever wraps genuine HTML frontend
 * responses. wp-admin, REST, AJAX, cron, XML-RPC, feeds, robots.txt and
 * trackbacks never start a buffer at all; as a second, independent safety
 * net the captured buffer is only ever rewritten when it plausibly starts
 * as an HTML document (doctype/<html>). Everything else (JSON, XML
 * sitemaps, downloads, ...) passes through completely untouched.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Source-independent output-buffer gating for Google Analytics/GTM/Ads.
 */
final class Macs_Cookie_Banner_Google_Tracking_Shield {
	/**
	 * Option key toggling this module (ADR-36 EXCEPTION: also force-enabled
	 * once for existing installs on upgrade to 1.0.7, see ADR-40 / the
	 * migration in macs-cookie-banner.php). Default OFF at the structural
	 * baseline; the recommended/migrated value is true.
	 */
	const OPTION_KEY = 'google_tracking_shield';

	/**
	 * Vendor keys this module acts on. Deliberately narrow: only vendors the
	 * existing, already-shipped Consent_Codes::match_vendor() classifies
	 * unambiguously as Google Analytics/GTM/Ads. Anything else ('custom',
	 * any other vendor, or no match at all) is left completely untouched.
	 */
	const GATED_VENDORS = array( 'ga4', 'gtm', 'google_ads' );

	/**
	 * Script `type` values considered executable JavaScript. A <script> tag
	 * carrying any OTHER explicit type (e.g. application/ld+json structured
	 * data, common from SEO plugins like Rank Math) is never touched by
	 * either layer - hard false-positive guard.
	 */
	const JS_TYPES = array( 'text/javascript', 'application/javascript', 'module' );

	/**
	 * Register the output buffer on the frontend when enabled.
	 *
	 * @return void
	 */
	public static function init() {
		if ( is_admin() ) {
			return;
		}

		// Emergency kill switch independent of the admin UI (defense in
		// depth; the settings checkbox alone is already always reachable
		// since wp-admin is never buffered, see should_skip_request()).
		if ( defined( 'MCB_DISABLE_GOOGLE_SHIELD' ) && MCB_DISABLE_GOOGLE_SHIELD ) {
			return;
		}

		$options = Macs_Cookie_Banner::get_options();

		if ( empty( $options[ self::OPTION_KEY ] ) ) {
			return;
		}

		add_action( 'template_redirect', array( __CLASS__, 'maybe_start_buffer' ) );
	}

	/**
	 * Start the output buffer, unless this request is clearly not a normal
	 * HTML frontend page (admin/REST/AJAX/cron/feed/robots/trackback/JSON).
	 *
	 * template_redirect runs late enough that all of the relevant WP query
	 * conditionals (is_feed(), is_robots(), ...) are reliable, and late
	 * enough that a typical full-page-cache plugin's own (earlier) output
	 * buffer wraps AROUND this one - so the cache stores the already-gated
	 * HTML, not the reverse.
	 *
	 * @return void
	 */
	public static function maybe_start_buffer() {
		if ( self::should_skip_request() ) {
			return;
		}

		ob_start( array( __CLASS__, 'process_buffer' ) );
	}

	/**
	 * Whether the current request should never be buffered.
	 *
	 * @return bool
	 */
	private static function should_skip_request() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return true;
		}

		if (
			( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
			|| ( defined( 'DOING_CRON' ) && DOING_CRON )
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		) {
			return true;
		}

		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return true;
		}

		if ( is_feed() || is_robots() || is_trackback() ) {
			return true;
		}

		return false;
	}

	/**
	 * Output buffer callback: gate known Google Analytics/GTM/Ads script
	 * tags in the final HTML. Returns the input UNCHANGED whenever the
	 * buffer does not plausibly look like an HTML document, or on any
	 * unexpected condition - conservative by design, never destructive.
	 *
	 * @param string $html Captured output buffer.
	 * @return string
	 */
	public static function process_buffer( $html ) {
		if ( ! is_string( $html ) || ! self::looks_like_html( $html ) ) {
			return $html;
		}

		if ( ! class_exists( 'Macs_Cookie_Banner_Codes' ) ) {
			return $html;
		}

		$html = self::gate_external_scripts( $html );
		$html = self::gate_inline_bootstraps( $html );

		return $html;
	}

	/**
	 * Cheap, conservative HTML sniff on the first bytes of the buffer.
	 * Guards against ever touching XML sitemaps, JSON, downloads or other
	 * non-HTML output that reached template_redirect without matching one
	 * of the explicit skip conditions above.
	 *
	 * @param string $html Captured buffer.
	 * @return bool
	 */
	private static function looks_like_html( $html ) {
		$head = ltrim( substr( $html, 0, 1024 ) );

		if ( '' === $head ) {
			return false;
		}

		return 0 === stripos( $head, '<!doctype' ) || false !== stripos( $head, '<html' );
	}

	/**
	 * Layer A: gate every external <script src="..."> tag whose src matches
	 * a known Google Analytics/GTM/Ads loader, via WP_HTML_Tag_Processor.
	 * Attribute-only operation - no raw text is read or written.
	 *
	 * @param string $html HTML to process.
	 * @return string
	 */
	private static function gate_external_scripts( $html ) {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			// WP < 6.2: this layer safely no-ops rather than guessing at an
			// unavailable API. Layer B (plain regex) still runs.
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( array( 'tag_name' => 'script' ) ) ) {
			$src = $processor->get_attribute( 'src' );

			if ( ! is_string( $src ) || '' === $src ) {
				continue; // Inline scripts are Layer B's job.
			}

			$type = $processor->get_attribute( 'type' );

			if ( is_string( $type ) && '' !== $type && ! in_array( strtolower( $type ), self::JS_TYPES, true ) ) {
				continue; // Non-JS script type (e.g. JSON-LD) - never touch.
			}

			$vendor = Macs_Cookie_Banner_Codes::match_vendor( $src );

			if ( ! in_array( $vendor, self::GATED_VENDORS, true ) ) {
				continue;
			}

			$category = Macs_Cookie_Banner_Codes::vendor_default_category( $vendor );

			$processor->set_attribute( 'data-cookie-type', ( is_string( $type ) && '' !== $type ) ? $type : 'text/javascript' );
			$processor->set_attribute( 'data-cookie-category', $category );
			$processor->set_attribute( 'type', 'text/plain' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Layer B: gate pure inline <script>...</script> blocks (no `src`) whose
	 * ENTIRE trimmed content classifies unambiguously as ga4/gtm/google_ads.
	 * Tag-boundary-anchored regex ('</script>' cannot legally occur inside a
	 * script body) - not a page-wide content sweep. Never matches on a bare
	 * keyword: classification goes through the same match_vendor() engine
	 * used everywhere else in the plugin.
	 *
	 * @param string $html HTML to process.
	 * @return string
	 */
	private static function gate_inline_bootstraps( $html ) {
		$result = preg_replace_callback(
			'#<script\b([^>]*)>(.*?)</script\s*>#is',
			array( __CLASS__, 'maybe_gate_inline_block' ),
			$html
		);

		return is_string( $result ) ? $result : $html;
	}

	/**
	 * preg_replace_callback handler for a single <script>...</script> match.
	 *
	 * @param array $m Regex match ($m[1] = opening tag attributes, $m[2] = body).
	 * @return string
	 */
	private static function maybe_gate_inline_block( $m ) {
		$attrs = $m[1];
		$body  = $m[2];

		if ( preg_match( '/\bsrc\s*=/i', $attrs ) ) {
			return $m[0]; // External - Layer A's job.
		}

		if ( preg_match( '/\btype\s*=\s*("|\')(.*?)\1/i', $attrs, $tm ) ) {
			$type = strtolower( trim( $tm[2] ) );

			if ( '' !== $type && ! in_array( $type, self::JS_TYPES, true ) ) {
				return $m[0]; // Non-JS script type (e.g. JSON-LD) - never touch.
			}
		}

		if ( '' === trim( $body ) ) {
			return $m[0];
		}

		$vendor = Macs_Cookie_Banner_Codes::match_vendor( $body );

		if ( ! in_array( $vendor, self::GATED_VENDORS, true ) ) {
			return $m[0];
		}

		$category = Macs_Cookie_Banner_Codes::vendor_default_category( $vendor );

		// Reuse the existing, already-shipped rewrite - identical output
		// shape to every Consent-Code-Manager snippet, so the unmodified
		// banner.js reactivation mechanic applies unchanged.
		return Macs_Cookie_Banner_Codes::transform_snippet( $m[0], $category );
	}
}
