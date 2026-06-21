<?php
/**
 * Plugin Name: Mac's Cookie Banner
 * Plugin URI:  https://github.com/Thaimacky/macs-cookie-banner
 * Description: Lightweight cookie consent banner with script blocking for WordPress.
 * Version:     0.5.10
 * Author:      Mac's Cookie Banner
 * Text Domain: macs-cookie-banner
 * Domain Path: /languages
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MCB_VERSION', '0.5.10' );

/**
 * Consent schema version. Bump this whenever the stored consent shape
 * changes in a backwards-incompatible way. Existing client-side consents
 * with a different version are treated as invalid and the banner re-appears.
 */
if ( ! defined( 'MCB_CONSENT_VERSION' ) ) {
	define( 'MCB_CONSENT_VERSION', 2 );
}

define( 'MCB_PLUGIN_FILE', __FILE__ );
define( 'MCB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'MCB_DEBUG' ) ) {
	define( 'MCB_DEBUG', false );
}

/**
 * Main plugin class.
 */
final class Macs_Cookie_Banner {
	/**
	 * Option name used for all plugin settings.
	 */
	const OPTION_NAME = 'lscc_options';

	/**
	 * Consent cookie name.
	 */
	const COOKIE_NAME = 'lscc_consent';

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'register_wpml_strings' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_refresh_imprint_detection' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_banner' ), 10 );
		add_shortcode( 'simple_cookie_settings', array( __CLASS__, 'render_settings_shortcode' ) );

		require_once MCB_PLUGIN_DIR . 'includes/service-components.php';
		Macs_Cookie_Banner_Service_Components::init();

		require_once MCB_PLUGIN_DIR . 'includes/avada-compat.php';
		Macs_Cookie_Banner_Avada_Compat::init();

		require_once MCB_PLUGIN_DIR . 'includes/yotu-compat.php';
		Macs_Cookie_Banner_Yotu_Compat::init();

		require_once MCB_PLUGIN_DIR . 'includes/avada-maps-compat.php';
		Macs_Cookie_Banner_Avada_Maps_Compat::init();

		require_once MCB_PLUGIN_DIR . 'includes/consent-codes.php';
		Macs_Cookie_Banner_Codes::init();

		require_once MCB_PLUGIN_DIR . 'includes/updater.php';
		Macs_Cookie_Banner_Updater::init();

		if ( is_admin() ) {
			require_once MCB_PLUGIN_DIR . 'includes/avada-colors.php';
			require_once MCB_PLUGIN_DIR . 'includes/admin-page.php';
			Macs_Cookie_Banner_Admin::init();
		}
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain(
			'macs-cookie-banner',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Return plugin defaults.
	 *
	 * @return array
	 */
	public static function get_default_options() {
		return array(
			'banner_title'           => self::get_neutral_text( 'banner_title' ),
			'banner_text'            => self::get_neutral_text( 'banner_text' ),
			'accept_all_text'        => self::get_neutral_text( 'accept_all_text' ),
			'necessary_only_text'    => self::get_neutral_text( 'necessary_only_text' ),
			'settings_text'          => self::get_neutral_text( 'settings_text' ),
			'save_settings_text'     => self::get_neutral_text( 'save_settings_text' ),
			'reopen_text'            => self::get_neutral_text( 'reopen_text' ),
			'background_color'       => '#111827',
			'text_color'             => '#f9fafb',
			'primary_button_color'   => '#e11d48',
			'primary_text_color'     => '#ffffff',
			'secondary_button_color' => '#1f2937',
			'border_color'           => '#374151',
			'overlay_color'          => '#000000',
			'overlay_enabled'        => true,
			'overlay_opacity'        => 0.45,
			'blur_enabled'           => true,
			'blur_strength'          => 4,
			'reopen_position'        => 'bottom-right',
			'reopen_offset_x'        => 24,
			'reopen_offset_y'        => 24,
			'show_legal_links'       => true,
			'privacy_url_override'   => '',
			'imprint_url_override'   => '',
			'consent_lifetime_days'      => 180,
			'avada_youtube_block'        => true,
			'youtube_remote_thumbnails'  => false,
			'yotu_consent_gating'        => false,
			'avada_maps_block'           => false,
			'design_preset'              => 'classic',
		);
	}

	/**
	 * Resolve a locale-aware neutral default text for a single UI string.
	 *
	 * Picks the default that matches the currently active locale. Under WPML or
	 * Polylang the active locale follows the current front-end language, so the
	 * banner defaults automatically follow the visitor's language without any
	 * `.mo` lookup. Falls back to language-prefix matching (e.g. `de_AT` -> `de`)
	 * and finally to English when no specific language entry exists.
	 *
	 * @param string      $key    Text key (see get_default_text_table()).
	 * @param string|null $locale Optional explicit locale; defaults to the active one.
	 * @return string
	 */
	public static function get_neutral_text( $key, $locale = null ) {
		if ( null === $locale ) {
			$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		}

		$table = self::get_default_text_table();
		$lang  = self::extract_language_prefix( (string) $locale );

		if ( '' !== $lang && isset( $table[ $lang ][ $key ] ) ) {
			return $table[ $lang ][ $key ];
		}

		if ( isset( $table['en'][ $key ] ) ) {
			return $table['en'][ $key ];
		}

		return '';
	}

	/**
	 * Backwards-compatible wrapper resolving the neutral banner intro text.
	 *
	 * @return string
	 */
	public static function get_neutral_banner_text() {
		return self::get_neutral_text( 'banner_text' );
	}

	/**
	 * Extract the lowercase language prefix (e.g. "de") from any locale code.
	 *
	 * Accepts variants like `de_CH`, `de-CH`, `de`, `DE`, `de_AT`, `pt_BR`.
	 *
	 * @param string $locale Locale code.
	 * @return string
	 */
	public static function extract_language_prefix( $locale ) {
		$locale = strtolower( (string) $locale );
		$locale = preg_replace( '/[^a-z_]/', '_', $locale );
		$parts  = explode( '_', $locale );
		$prefix = isset( $parts[0] ) ? $parts[0] : '';

		if ( strlen( $prefix ) < 2 || strlen( $prefix ) > 3 ) {
			return '';
		}

		return $prefix;
	}

	/**
	 * Neutral default UI texts per language prefix.
	 *
	 * Holds the defaults for every editable banner string so that an unsaved
	 * install automatically renders in the active language (any current or
	 * future WPML/Polylang language that maps to one of these prefixes). Unknown
	 * languages fall back to English. Admin overrides and WPML/Polylang String
	 * Translation still take precedence over these defaults at render time.
	 *
	 * The texts are deliberately phrased without `du`/`Sie` so that they fit
	 * Swiss, German and international audiences alike. Swiss spelling: no `ß`.
	 *
	 * @return array Map of language prefix => map of text key => string.
	 */
	public static function get_default_text_table() {
		return array(
			'de' => array(
				'banner_title'        => 'Cookie-Einstellungen',
				'banner_text'         => 'Wir verwenden notwendige Cookies für den Betrieb dieser Website. Statistik, Marketing und externe Medien werden erst nach Zustimmung geladen.',
				'accept_all_text'     => 'Alle akzeptieren',
				'necessary_only_text' => 'Nur notwendige',
				'settings_text'       => 'Einstellungen',
				'save_settings_text'  => 'Auswahl speichern',
				'reopen_text'         => 'Cookie-Einstellungen',
			),
			'en' => array(
				'banner_title'        => 'Cookie settings',
				'banner_text'         => 'We use necessary cookies to operate this website. Statistics, marketing and external media are loaded only after consent.',
				'accept_all_text'     => 'Accept all',
				'necessary_only_text' => 'Necessary only',
				'settings_text'       => 'Settings',
				'save_settings_text'  => 'Save selection',
				'reopen_text'         => 'Cookie settings',
			),
			'fr' => array(
				'banner_title'        => 'Paramètres des cookies',
				'banner_text'         => 'Nous utilisons des cookies nécessaires au fonctionnement de ce site. Les statistiques, le marketing et les médias externes sont chargés uniquement après consentement.',
				'accept_all_text'     => 'Tout accepter',
				'necessary_only_text' => 'Nécessaires uniquement',
				'settings_text'       => 'Paramètres',
				'save_settings_text'  => 'Enregistrer la sélection',
				'reopen_text'         => 'Paramètres des cookies',
			),
			'it' => array(
				'banner_title'        => 'Impostazioni dei cookie',
				'banner_text'         => 'Utilizziamo cookie necessari per il funzionamento di questo sito. Statistiche, marketing e contenuti esterni vengono caricati solo dopo il consenso.',
				'accept_all_text'     => 'Accetta tutto',
				'necessary_only_text' => 'Solo necessari',
				'settings_text'       => 'Impostazioni',
				'save_settings_text'  => 'Salva selezione',
				'reopen_text'         => 'Impostazioni dei cookie',
			),
			'tr' => array(
				'banner_title'        => 'Çerez ayarları',
				'banner_text'         => 'Bu web sitesinin çalışması için gerekli çerezleri kullanıyoruz. İstatistik, pazarlama ve harici medya yalnızca onaydan sonra yüklenir.',
				'accept_all_text'     => 'Tümünü kabul et',
				'necessary_only_text' => 'Yalnızca gerekli',
				'settings_text'       => 'Ayarlar',
				'save_settings_text'  => 'Seçimi kaydet',
				'reopen_text'         => 'Çerez ayarları',
			),
			'hu' => array(
				'banner_title'        => 'Süti beállítások',
				'banner_text'         => 'A weboldal működéséhez szükséges sütiket használunk. A statisztika, marketing és külső média csak hozzájárulás után töltődik be.',
				'accept_all_text'     => 'Összes elfogadása',
				'necessary_only_text' => 'Csak a szükségesek',
				'settings_text'       => 'Beállítások',
				'save_settings_text'  => 'Kiválasztás mentése',
				'reopen_text'         => 'Süti beállítások',
			),
		);
	}

	/**
	 * Return option keys that contain plain text.
	 *
	 * @return array
	 */
	public static function get_text_option_keys() {
		return array(
			'banner_title',
			'banner_text',
			'accept_all_text',
			'necessary_only_text',
			'settings_text',
			'save_settings_text',
			'reopen_text',
		);
	}

	/**
	 * Return option keys that contain hex colors.
	 *
	 * @return array
	 */
	public static function get_color_option_keys() {
		return array(
			'background_color',
			'text_color',
			'primary_button_color',
			'primary_text_color',
			'secondary_button_color',
			'border_color',
			'overlay_color',
		);
	}

	/**
	 * Option keys that are stored as booleans (admin checkboxes).
	 *
	 * @return array
	 */
	public static function get_bool_option_keys() {
		return array(
			'overlay_enabled',
			'blur_enabled',
			'show_legal_links',
			'avada_youtube_block',
			'youtube_remote_thumbnails',
			'yotu_consent_gating',
			'avada_maps_block',
		);
	}

	/**
	 * Option keys that are stored as clamped integers.
	 *
	 * @return array Map of key => array{min:int, max:int}.
	 */
	public static function get_int_option_keys() {
		return array(
			'blur_strength'         => array(
				'min' => 0,
				'max' => 20,
			),
			'reopen_offset_x'       => array(
				'min' => 0,
				'max' => 200,
			),
			'reopen_offset_y'       => array(
				'min' => 0,
				'max' => 200,
			),
			'consent_lifetime_days' => array(
				'min' => 1,
				'max' => 365,
			),
		);
	}

	/**
	 * Option keys that are stored as clamped floats.
	 *
	 * @return array Map of key => array{min:float, max:float}.
	 */
	public static function get_float_option_keys() {
		return array(
			'overlay_opacity' => array(
				'min' => 0.0,
				'max' => 1.0,
			),
		);
	}

	/**
	 * Option keys that are stored as URLs (may be empty).
	 *
	 * @return array
	 */
	public static function get_url_option_keys() {
		return array(
			'privacy_url_override',
			'imprint_url_override',
		);
	}

	/**
	 * Option keys that accept one value from a fixed list.
	 *
	 * @return array Map of key => array of allowed string values.
	 */
	public static function get_enum_option_keys() {
		return array(
			'reopen_position' => array(
				'bottom-right',
				'bottom-left',
				'top-right',
				'top-left',
				'hidden',
			),
			'design_preset'   => array(
				'classic',
				'modern',
				'premium',
			),
		);
	}

	/**
	 * Sanitize plugin options from any source.
	 *
	 * @param array $options Raw options.
	 * @return array
	 */
	public static function sanitize_options( $options ) {
		$defaults  = self::get_default_options();
		$sanitized = array();

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		foreach ( self::get_text_option_keys() as $key ) {
			$value = isset( $options[ $key ] ) && is_scalar( $options[ $key ] )
				? (string) $options[ $key ]
				: $defaults[ $key ];

			$sanitized[ $key ] = sanitize_text_field( $value );
		}

		foreach ( self::get_color_option_keys() as $key ) {
			$value = isset( $options[ $key ] ) && is_scalar( $options[ $key ] )
				? (string) $options[ $key ]
				: '';
			$color = sanitize_hex_color( $value );

			$sanitized[ $key ] = $color ? $color : $defaults[ $key ];
		}

		foreach ( self::get_bool_option_keys() as $key ) {
			if ( array_key_exists( $key, $options ) ) {
				$sanitized[ $key ] = ! empty( $options[ $key ] );
			} else {
				// Key not present at all (e.g. migration from an older saved
				// option set that did not yet contain this checkbox). Use the
				// documented default rather than silently flipping to false.
				$sanitized[ $key ] = (bool) $defaults[ $key ];
			}
		}

		foreach ( self::get_int_option_keys() as $key => $range ) {
			$raw = isset( $options[ $key ] ) && is_scalar( $options[ $key ] )
				? (int) $options[ $key ]
				: (int) $defaults[ $key ];

			$sanitized[ $key ] = max( $range['min'], min( $range['max'], $raw ) );
		}

		foreach ( self::get_float_option_keys() as $key => $range ) {
			$raw = isset( $options[ $key ] ) && is_scalar( $options[ $key ] )
				? (float) $options[ $key ]
				: (float) $defaults[ $key ];

			$sanitized[ $key ] = max( $range['min'], min( $range['max'], $raw ) );
		}

		foreach ( self::get_url_option_keys() as $key ) {
			$raw = isset( $options[ $key ] ) && is_scalar( $options[ $key ] )
				? trim( (string) $options[ $key ] )
				: '';

			$sanitized[ $key ] = '' !== $raw ? esc_url_raw( $raw ) : '';
		}

		foreach ( self::get_enum_option_keys() as $key => $allowed ) {
			$raw = isset( $options[ $key ] ) && is_scalar( $options[ $key ] )
				? (string) $options[ $key ]
				: '';

			$sanitized[ $key ] = in_array( $raw, $allowed, true ) ? $raw : $defaults[ $key ];
		}

		return wp_parse_args( $sanitized, $defaults );
	}

	/**
	 * Return sanitized and merged options.
	 *
	 * @return array
	 */
	public static function get_options() {
		$options = get_option( self::OPTION_NAME, array() );

		return self::sanitize_options( $options );
	}

	/**
	 * Register editable strings for WPML String Translation when available.
	 *
	 * @return void
	 */
	public static function register_wpml_strings() {
		$options = self::get_options();
		$strings = array(
			'banner_title'        => 'Banner title',
			'banner_text'         => 'Banner text',
			'accept_all_text'     => 'Accept all button',
			'necessary_only_text' => 'Necessary only button',
			'settings_text'       => 'Settings button',
			'save_settings_text'  => 'Save settings button',
			'reopen_text'         => 'Reopen settings button',
		);

		foreach ( $strings as $key => $label ) {
			do_action( 'wpml_register_single_string', 'Light Swiss Cookie Consent', $label, $options[ $key ] );

			if ( function_exists( 'pll_register_string' ) ) {
				pll_register_string( $label, $options[ $key ], 'Light Swiss Cookie Consent' );
			}
		}
	}

	/**
	 * Get an option translated by WPML if available.
	 *
	 * @param string $key   Option key.
	 * @param string $label WPML string label.
	 * @return string
	 */
	public static function get_translated_option( $key, $label ) {
		$options = self::get_options();
		$value   = isset( $options[ $key ] ) ? $options[ $key ] : '';

		// A stored value that is empty or merely one of our shipped default
		// texts (in any bundled language) is not a deliberate operator override.
		// In that case follow the active locale so the banner never mixes
		// languages (e.g. a persisted English default shown on a German page).
		if ( '' === $value || self::is_shipped_default_text( $key, $value ) ) {
			$value = self::get_neutral_text( $key );
		}

		$value = apply_filters( 'wpml_translate_single_string', $value, 'Light Swiss Cookie Consent', $label );

		if ( function_exists( 'pll__' ) ) {
			return pll__( $value );
		}

		return $value;
	}

	/**
	 * Whether a stored text equals one of the bundled default texts in any
	 * bundled language. Such values are treated as non-overrides so the banner
	 * follows the active locale instead of a persisted default string.
	 *
	 * @param string $key   Option key.
	 * @param string $value Stored value.
	 * @return bool
	 */
	private static function is_shipped_default_text( $key, $value ) {
		foreach ( self::get_default_text_table() as $texts ) {
			if ( isset( $texts[ $key ] ) && $texts[ $key ] === $value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue front-end assets.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'mcb-banner',
			MCB_PLUGIN_URL . 'assets/css/banner.css',
			array(),
			MCB_VERSION
		);

		wp_enqueue_script(
			'mcb-banner',
			MCB_PLUGIN_URL . 'assets/js/banner.js',
			array(),
			MCB_VERSION,
			true
		);

		$options = self::get_options();

		wp_localize_script(
			'mcb-banner',
			'mcbSettings',
			array(
				'cookieName'     => self::COOKIE_NAME,
				'storageKey'     => self::COOKIE_NAME,
				'consentVersion' => (int) MCB_CONSENT_VERSION,
				'lifetimeDays'   => (int) $options['consent_lifetime_days'],
				'debug'          => (bool) MCB_DEBUG,
				'locale'         => function_exists( 'determine_locale' ) ? determine_locale() : get_locale(),
				'categories'     => array(
					'necessary',
					'statistics',
					'marketing',
					'external_media',
				),
			)
		);
	}

	/**
	 * Return CSS variables for front-end elements.
	 *
	 * @param array $options Plugin options.
	 * @return string
	 */
	public static function get_css_variables( $options ) {
		$overlay_rgba = self::hex_with_opacity_to_rgba(
			isset( $options['overlay_color'] ) ? $options['overlay_color'] : '#000000',
			isset( $options['overlay_opacity'] ) ? (float) $options['overlay_opacity'] : 0.45
		);

		return sprintf(
			'--lscc-bg:%1$s;--lscc-text:%2$s;--lscc-primary:%3$s;--lscc-primary-text:%4$s;--lscc-secondary:%5$s;--lscc-border:%6$s;--lscc-overlay-bg:%7$s;--lscc-blur:%8$dpx;--lscc-reopen-ox:%9$dpx;--lscc-reopen-oy:%10$dpx;',
			$options['background_color'],
			$options['text_color'],
			$options['primary_button_color'],
			$options['primary_text_color'],
			$options['secondary_button_color'],
			$options['border_color'],
			$overlay_rgba,
			(int) $options['blur_strength'],
			(int) $options['reopen_offset_x'],
			(int) $options['reopen_offset_y']
		);
	}

	/**
	 * Convert hex + opacity to a CSS rgba() string.
	 *
	 * @param string $hex     Hex color like `#000000` or `#FFF`.
	 * @param float  $opacity Float between 0 and 1.
	 * @return string
	 */
	private static function hex_with_opacity_to_rgba( $hex, $opacity ) {
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
			$r = 0;
			$g = 0;
			$b = 0;
		}
		$opacity = max( 0.0, min( 1.0, (float) $opacity ) );

		return sprintf( 'rgba(%d,%d,%d,%.2f)', $r, $g, $b, $opacity );
	}

	/**
	 * Get the privacy URL for the banner.
	 *
	 * Order: manual override -> WordPress core privacy page -> empty.
	 *
	 * @param array $options Resolved options array.
	 * @return string
	 */
	public static function get_privacy_url( $options ) {
		if ( ! empty( $options['privacy_url_override'] ) ) {
			return (string) $options['privacy_url_override'];
		}
		if ( function_exists( 'get_privacy_policy_url' ) ) {
			$url = get_privacy_policy_url();
			if ( $url ) {
				return $url;
			}
		}
		return '';
	}

	/**
	 * Get the imprint URL for the banner.
	 *
	 * Order: manual override -> cached detection (transient) -> empty.
	 * The detection itself runs only in the admin (see maybe_refresh_imprint_detection).
	 *
	 * @param array $options Resolved options array.
	 * @return string
	 */
	public static function get_imprint_url( $options ) {
		if ( ! empty( $options['imprint_url_override'] ) ) {
			return (string) $options['imprint_url_override'];
		}
		$cached = get_transient( 'lscc_detected_imprint_url' );
		return is_string( $cached ) ? $cached : '';
	}

	/**
	 * Populate the imprint detection cache. Admin-only by convention.
	 *
	 * @return string Detected URL or empty string.
	 */
	public static function refresh_imprint_detection() {
		$url = self::scan_imprint_pages();
		set_transient( 'lscc_detected_imprint_url', $url, DAY_IN_SECONDS );
		return $url;
	}

	/**
	 * Refresh the detection cache once per admin session if it is empty.
	 *
	 * Hooked on `admin_init` so the cost is paid only inside the admin and only
	 * once per cache lifetime. The frontend never runs this scan.
	 *
	 * @return void
	 */
	public static function maybe_refresh_imprint_detection() {
		if ( ! is_admin() ) {
			return;
		}
		if ( false === get_transient( 'lscc_detected_imprint_url' ) ) {
			self::refresh_imprint_detection();
		}
	}

	/**
	 * Search for an imprint-style page by typical slugs and titles.
	 *
	 * Lightweight: a handful of `get_page_by_path` lookups and a few
	 * single-row WP_Query calls by title. No frontend scanning.
	 *
	 * @return string Detected URL or empty string.
	 */
	private static function scan_imprint_pages() {
		$slug_candidates = array(
			'impressum',
			'impressum-datenschutz',
			'datenschutz-impressum',
			'datenschutz-und-impressum',
			'legal',
			'legal-notice',
			'mentions-legales',
			'note-legali',
		);

		foreach ( $slug_candidates as $slug ) {
			$page = get_page_by_path( $slug, OBJECT, array( 'page' ) );
			if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
				return (string) get_permalink( $page );
			}
		}

		$title_candidates = array(
			'Impressum',
			'Datenschutz & Impressum',
			'Datenschutz und Impressum',
			'Legal Notice',
			'Mentions légales',
			'Note legali',
		);

		foreach ( $title_candidates as $title ) {
			$query = new WP_Query(
				array(
					'post_type'              => 'page',
					'post_status'            => 'publish',
					'title'                  => $title,
					'posts_per_page'         => 1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'fields'                 => 'ids',
				)
			);
			if ( ! empty( $query->posts ) ) {
				return (string) get_permalink( (int) $query->posts[0] );
			}
		}

		return '';
	}

	/**
	 * Render a front-end button that opens the consent settings.
	 *
	 * @return string
	 */
	public static function render_settings_shortcode() {
		$options     = self::get_options();
		$style       = self::get_css_variables( $options );
		$reopen_text  = self::get_translated_option( 'reopen_text', 'Reopen settings button' );
		$preset_class = 'lscc--preset-' . $options['design_preset'];

		return sprintf(
			'<button type="button" class="lscc-settings-button %3$s" style="%1$s" data-lscc-open-consent-settings aria-controls="lscc-root">%2$s</button>',
			esc_attr( $style ),
			esc_html( $reopen_text ),
			esc_attr( $preset_class )
		);
	}

	/**
	 * Render the consent banner.
	 *
	 * @return void
	 */
	public static function render_banner() {
		$options = self::get_options();
		$style   = self::get_css_variables( $options );

		$preset_class = 'lscc--preset-' . $options['design_preset'];

		$banner_title        = self::get_translated_option( 'banner_title', 'Banner title' );
		$banner_text         = self::get_translated_option( 'banner_text', 'Banner text' );
		$accept_all_text     = self::get_translated_option( 'accept_all_text', 'Accept all button' );
		$necessary_only_text = self::get_translated_option( 'necessary_only_text', 'Necessary only button' );
		$settings_text       = self::get_translated_option( 'settings_text', 'Settings button' );
		$save_settings_text  = self::get_translated_option( 'save_settings_text', 'Save settings button' );
		$reopen_text         = self::get_translated_option( 'reopen_text', 'Reopen settings button' );

		$privacy_url = self::get_privacy_url( $options );
		$imprint_url = self::get_imprint_url( $options );
		?>
		<?php if ( $options['overlay_enabled'] ) : ?>
			<div class="lscc-overlay <?php echo esc_attr( $preset_class ); ?><?php echo $options['blur_enabled'] ? ' lscc-overlay--blur' : ''; ?>" style="<?php echo esc_attr( $style ); ?>" data-lscc-overlay aria-hidden="true" hidden></div>
		<?php endif; ?>
		<div id="lscc-root" class="lscc <?php echo esc_attr( $preset_class ); ?>" style="<?php echo esc_attr( $style ); ?>" data-lscc-root aria-hidden="true" hidden>
			<div class="lscc__panel" role="dialog" aria-labelledby="lscc-title" aria-describedby="lscc-description">
				<div class="lscc__content">
					<div class="lscc__intro">
						<h2 id="lscc-title" class="lscc__title"><?php echo esc_html( $banner_title ); ?></h2>
						<p id="lscc-description" class="lscc__text"><?php echo esc_html( $banner_text ); ?></p>
					</div>

					<div class="lscc__actions" data-lscc-main-actions>
						<button type="button" class="lscc__button lscc__button--primary" data-lscc-accept-all aria-pressed="false">
							<?php echo esc_html( $accept_all_text ); ?>
						</button>
						<button type="button" class="lscc__button lscc__button--secondary" data-lscc-necessary aria-pressed="false">
							<?php echo esc_html( $necessary_only_text ); ?>
						</button>
						<button type="button" class="lscc__button lscc__button--ghost" data-lscc-open-settings>
							<?php echo esc_html( $settings_text ); ?>
						</button>
					</div>

					<form class="lscc__settings" data-lscc-settings hidden>
						<div class="lscc__categories">
							<label class="lscc__category">
								<span class="lscc__category-copy">
									<span class="lscc__category-title"><?php echo esc_html__( 'Notwendig', 'macs-cookie-banner' ); ?></span>
									<span class="lscc__category-text"><?php echo esc_html__( 'Erforderlich für Grundfunktionen der Website.', 'macs-cookie-banner' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="necessary" autocomplete="off" checked disabled>
							</label>

							<label class="lscc__category">
								<span class="lscc__category-copy">
									<span class="lscc__category-title"><?php echo esc_html__( 'Statistik', 'macs-cookie-banner' ); ?></span>
									<span class="lscc__category-text"><?php echo esc_html__( 'Hilft uns, die Nutzung der Website zu verstehen.', 'macs-cookie-banner' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="statistics" autocomplete="off">
							</label>

							<label class="lscc__category">
								<span class="lscc__category-copy">
									<span class="lscc__category-title"><?php echo esc_html__( 'Marketing', 'macs-cookie-banner' ); ?></span>
									<span class="lscc__category-text"><?php echo esc_html__( 'Erlaubt Marketing- und Tracking-Dienste.', 'macs-cookie-banner' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="marketing" autocomplete="off">
							</label>

							<label class="lscc__category">
								<span class="lscc__category-copy">
									<span class="lscc__category-title"><?php echo esc_html__( 'Externe Medien', 'macs-cookie-banner' ); ?></span>
									<span class="lscc__category-text"><?php echo esc_html__( 'Lädt eingebettete Inhalte von externen Plattformen.', 'macs-cookie-banner' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="external_media" autocomplete="off">
							</label>
						</div>

						<div class="lscc__actions lscc__actions--settings">
							<button type="submit" class="lscc__button lscc__button--primary">
								<?php echo esc_html( $save_settings_text ); ?>
							</button>
						</div>
					</form>

					<?php if ( $options['show_legal_links'] && ( $privacy_url || $imprint_url ) ) : ?>
						<div class="lscc__legal" data-lscc-legal>
							<?php if ( $privacy_url && $imprint_url && $privacy_url === $imprint_url ) : ?>
								<a class="lscc__legal-link" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html__( 'Datenschutz & Impressum', 'macs-cookie-banner' ); ?></a>
							<?php else : ?>
								<?php if ( $privacy_url ) : ?>
									<a class="lscc__legal-link" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html__( 'Datenschutz', 'macs-cookie-banner' ); ?></a>
								<?php endif; ?>
								<?php if ( $imprint_url ) : ?>
									<a class="lscc__legal-link" href="<?php echo esc_url( $imprint_url ); ?>"><?php echo esc_html__( 'Impressum', 'macs-cookie-banner' ); ?></a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<button type="button" class="lscc-reopen <?php echo esc_attr( $preset_class ); ?>" style="<?php echo esc_attr( $style ); ?>" data-lscc-reopen data-lscc-open-consent-settings aria-controls="lscc-root" data-position="<?php echo esc_attr( $options['reopen_position'] ); ?>" hidden>
			<?php echo esc_html( $reopen_text ); ?>
			<span class="lscc-reopen-dismiss" data-lscc-reopen-dismiss role="button" tabindex="-1" aria-label="<?php echo esc_attr__( 'Cookie-Einstellungen-Button ausblenden', 'macs-cookie-banner' ); ?>">&times;</span>
		</button>
		<?php
		// FRONTEND RUNTIME-PROOF (v0.5.10-debug3): admin-only, read-only box. Shows
		// what the rendered banner actually received. Visitors never see it. The
		// banner output above is unchanged; RENDER_SOURCE is the exact inline style
		// emitted to #lscc-root / .lscc-reopen ($style), the computed values are
		// read live from the DOM via getComputedStyle.
		if ( current_user_can( 'manage_options' ) ) :
			?>
			<div id="mcb-fe-proof" style="position:fixed;left:12px;bottom:12px;z-index:2147483647;background:#fff;color:#111;border:2px solid #d63638;border-radius:6px;padding:10px 12px;font:12px/1.5 monospace;max-width:560px;box-shadow:0 4px 16px rgba(0,0,0,.25);">
				<strong>MCB Frontend Runtime-Proof</strong><br>
				ROOT_PRIMARY: <span data-mcb-root-primary>…</span><br>
				ROOT_BORDER: <span data-mcb-root-border>…</span><br>
				REOPEN_PRIMARY: <span data-mcb-reopen-primary>…</span><br>
				RENDER_SOURCE:
				<code style="display:block;margin-top:4px;white-space:pre-wrap;word-break:break-all;background:#f6f7f7;padding:6px;border:1px solid #ccd0d4;"><?php echo esc_html( $style ); ?></code>
			</div>
			<script>
			( function () {
				function val( sel, prop ) {
					var el = document.querySelector( sel );
					if ( ! el ) { return '(Element fehlt)'; }
					var v = getComputedStyle( el ).getPropertyValue( prop );
					return v ? v.trim() : '(leer)';
				}
				function fill() {
					var box = document.getElementById( 'mcb-fe-proof' );
					if ( ! box ) { return; }
					box.querySelector( '[data-mcb-root-primary]' ).textContent   = val( '#lscc-root', '--lscc-primary' );
					box.querySelector( '[data-mcb-root-border]' ).textContent    = val( '#lscc-root', '--lscc-border' );
					box.querySelector( '[data-mcb-reopen-primary]' ).textContent = val( '.lscc-reopen', '--lscc-primary' );
				}
				if ( 'loading' !== document.readyState ) { fill(); }
				else { document.addEventListener( 'DOMContentLoaded', fill ); }
			} )();
			</script>
			<?php
		endif;
		?>
		<?php
	}
}

Macs_Cookie_Banner::init();
