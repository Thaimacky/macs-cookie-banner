<?php
/**
 * PixelYourSite compatibility (v1.0.8, ADR-41): run PixelYourSite (Meta
 * Pixel, plus any other pixel it manages) only after "Marketing" consent.
 *
 * Proven failure (production WooCommerce site, 2026-11-08): with PixelYourSite active and no
 * consent given - also after "Nur notwendige" - the visitor's browser loaded
 * connect.facebook.net/en_US/fbevents.js, sent www.facebook.com/tr PageView
 * hits and received _fbp, fr, pbid and eight pys_* cookies. PixelYourSite only
 * honours a fixed list of consent plugins; Mac's Cookie Banner is not one of
 * them, and its own "prior consent" defaults fire every pixel immediately.
 *
 * PixelYourSite does not print fbevents.js as a literal tag - its public.js
 * (handle "pys", properly enqueued) loads the pixel at runtime. That makes the
 * enqueued handle the correct, narrow lever (same pattern as yotu-compat /
 * meta-social-compat, no output buffer, no DOM work):
 *
 * 1. Browser layer (primary): the "pys" script tag (incl. any before/after
 *    inline parts of that same handle) is rewritten through the existing
 *    Consent_Codes::transform_snippet() to type="text/plain" +
 *    data-cookie-category="marketing". Nothing of PixelYourSite runs - no
 *    pixel, no pys_* / pbid cookies - until the visitor allows Marketing; then
 *    the unchanged banner.js::activateBlockedScripts() starts it.
 *    pysOptions (wp_localize_script data) stays as inert data.
 * 2. Raw outputs PixelYourSite echoes itself are switched off while gating is
 *    active: the <head> pbid prefetch (an admin-ajax call that sets the pbid
 *    identifier cookie - public.js re-requests it itself when the prefetch is
 *    missing, see PixelYourSite's own docblock) and the <noscript> pixel
 *    images (without JavaScript no consent can ever be given).
 * 3. Server layer (defence in depth, Conversions API): PixelYourSite asks
 *    Consent()->checkConsent() before server events and filters the answer
 *    through `pys_check_consent_by_gdpr`. In NON-cacheable requests only
 *    (admin-ajax, wc-ajax, REST, POST) this module can turn that answer to
 *    "no" when the visitor's lscc_consent cookie does not allow the pixel's
 *    category. It never turns a "no" into "yes", and it never runs during a
 *    cacheable page render - a page cache must never store a per-visitor
 *    consent state.
 *
 * Opt-in (Safe by Default, ADR-36): recommended ON for fresh installs, never
 * silently enabled on existing installs. Without PixelYourSite the module has
 * no effect at all.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consent gating for PixelYourSite.
 */
final class Macs_Cookie_Banner_Pixelyoursite_Compat {
	/**
	 * Option key toggling this module.
	 */
	const OPTION_KEY = 'pys_consent_gating';

	/**
	 * Consent category PixelYourSite's frontend runs under. Marketing on
	 * purpose: public.js drives the Meta Pixel (and optionally Pinterest,
	 * Bing, ...) - a site that uses PixelYourSite only for GA is still gated,
	 * just one category stricter.
	 */
	const CATEGORY = 'marketing';

	/**
	 * Enqueued script handles of PixelYourSite's frontend runtime.
	 */
	const HANDLES = array( 'pys' );

	/**
	 * PixelYourSite class whose raw outputs are switched off while gating.
	 */
	const EVENTS_MANAGER_CLASS = 'PixelYourSite\\EventsManager';

	/**
	 * Raw echo callbacks of the events manager: hook => method.
	 */
	const RAW_OUTPUTS = array(
		'wp_head'   => 'outputPbidPrefetch',
		'wp_footer' => 'outputNoScriptData',
	);

	/**
	 * Register the gating hooks when the option is enabled.
	 *
	 * @return void
	 */
	public static function init() {
		$options = Macs_Cookie_Banner::get_options();

		if ( empty( $options[ self::OPTION_KEY ] ) ) {
			return;
		}

		// Server layer: registered in every context (admin-ajax.php runs with
		// is_admin() === true); the callback itself decides whether to act.
		add_filter( 'pys_check_consent_by_gdpr', array( __CLASS__, 'filter_server_consent' ), 10, 2 );

		if ( is_admin() ) {
			return;
		}

		add_filter( 'script_loader_tag', array( __CLASS__, 'gate_pys_script' ), 10, 3 );
		add_action( 'template_redirect', array( __CLASS__, 'remove_raw_outputs' ), 99 );
	}

	/**
	 * Browser layer: mark PixelYourSite's runtime script as consent-blocked.
	 *
	 * @param string $tag    The full script HTML of the handle.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL (unused).
	 * @return string
	 */
	public static function gate_pys_script( $tag, $handle, $src ) {
		unset( $src );

		if ( ! in_array( $handle, self::HANDLES, true ) || ! is_string( $tag ) ) {
			return $tag;
		}

		// Already gated (e.g. by another filter): leave untouched.
		if ( false !== stripos( $tag, 'data-cookie-category' ) ) {
			return $tag;
		}

		return Macs_Cookie_Banner_Codes::transform_snippet( $tag, self::CATEGORY );
	}

	/**
	 * Switch off PixelYourSite's raw (non-enqueued) outputs while gating.
	 *
	 * Version-tolerant: finds the callbacks by class and method name instead of
	 * relying on PixelYourSite's internal instance accessors or priorities.
	 *
	 * @return void
	 */
	public static function remove_raw_outputs() {
		global $wp_filter;

		foreach ( self::RAW_OUTPUTS as $hook => $method ) {
			if ( empty( $wp_filter[ $hook ] ) || ! is_object( $wp_filter[ $hook ] ) || empty( $wp_filter[ $hook ]->callbacks ) ) {
				continue;
			}

			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = isset( $callback['function'] ) ? $callback['function'] : null;

					if (
						is_array( $function )
						&& isset( $function[0], $function[1] )
						&& is_object( $function[0] )
						&& is_a( $function[0], self::EVENTS_MANAGER_CLASS )
						&& $method === $function[1]
					) {
						remove_action( $hook, $function, $priority );
					}
				}
			}
		}
	}

	/**
	 * Server layer: tighten PixelYourSite's consent answer in non-cacheable
	 * requests according to the visitor's lscc_consent cookie.
	 *
	 * @param bool   $consented PixelYourSite's own answer.
	 * @param string $pixel     Pixel slug (facebook, ga, gtm, pinterest, ...).
	 * @return bool
	 */
	public static function filter_server_consent( $consented, $pixel ) {
		if ( ! $consented ) {
			return $consented; // Never loosen.
		}

		if ( ! self::is_uncacheable_request() ) {
			return $consented;
		}

		return self::visitor_allows( self::pixel_category( $pixel ) );
	}

	/**
	 * Consent category a PixelYourSite pixel slug belongs to.
	 *
	 * @param string $pixel Pixel slug.
	 * @return string
	 */
	private static function pixel_category( $pixel ) {
		return in_array( (string) $pixel, array( 'ga', 'gtm' ), true ) ? 'statistics' : 'marketing';
	}

	/**
	 * Whether the current request can never be served from a page cache
	 * (admin-ajax, wc-ajax, REST, or a non-GET request).
	 *
	 * @return bool
	 */
	private static function is_uncacheable_request() {
		if ( wp_doing_ajax() ) {
			return true;
		}

		if ( is_admin() ) {
			return false; // Regular wp-admin screens: leave PixelYourSite alone.
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only routing flags, no state change.
		if ( isset( $_GET['wc-ajax'] ) || isset( $_GET['rest_route'] ) ) {
			return true;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only substring-compared.

		if ( function_exists( 'rest_get_url_prefix' ) && false !== strpos( $uri, '/' . rest_get_url_prefix() . '/' ) ) {
			return true;
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';

		return 'GET' !== $method && 'HEAD' !== $method;
	}

	/**
	 * Whether the visitor's lscc_consent cookie allows a category.
	 *
	 * Mirrors banner.js::isValidConsent(): same cookie, same schema version,
	 * same expiry rules. Read-only - nothing is written, nothing is trusted
	 * beyond strict boolean checks.
	 *
	 * @param string $category Consent category.
	 * @return bool
	 */
	private static function visitor_allows( $category ) {
		$name = Macs_Cookie_Banner::COOKIE_NAME;

		if ( empty( $_COOKIE[ $name ] ) || ! is_string( $_COOKIE[ $name ] ) ) {
			return false;
		}

		$raw  = wp_unslash( $_COOKIE[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded as JSON and only strictly compared below.
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) && false !== strpos( $raw, '%' ) ) {
			$data = json_decode( rawurldecode( $raw ), true );
		}

		if ( ! is_array( $data ) || ! isset( $data['version'], $data['categories'] ) || ! is_array( $data['categories'] ) ) {
			return false;
		}

		if ( (string) $data['version'] !== (string) MCB_CONSENT_VERSION ) {
			return false;
		}

		$now = time();

		if ( ! empty( $data['expiresAt'] ) ) {
			$expires = strtotime( (string) $data['expiresAt'] );

			if ( false !== $expires && $expires < $now ) {
				return false;
			}
		}

		if ( ! empty( $data['createdAt'] ) ) {
			$created  = strtotime( (string) $data['createdAt'] );
			$options  = Macs_Cookie_Banner::get_options();
			$lifetime = isset( $options['consent_lifetime_days'] ) ? (int) $options['consent_lifetime_days'] : 180;

			if ( false !== $created && $lifetime > 0 && $created + $lifetime * DAY_IN_SECONDS < $now ) {
				return false;
			}
		}

		return isset( $data['categories'][ $category ] ) && true === $data['categories'][ $category ];
	}
}
