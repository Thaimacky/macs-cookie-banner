<?php
/**
 * Plugin Name: Light Swiss Cookie Consent
 * Plugin URI:  https://example.com/light-swiss-cookie-consent
 * Description: Lightweight cookie consent banner with script blocking for WordPress.
 * Version:     0.2.0
 * Author:      Light Swiss Cookie Consent
 * Text Domain: light-swiss-cookie-consent
 * Domain Path: /languages
 *
 * @package LightSwissCookieConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LSCC_VERSION', '0.2.0' );

/**
 * Consent schema version. Bump this whenever the stored consent shape
 * changes in a backwards-incompatible way. Existing client-side consents
 * with a different version are treated as invalid and the banner re-appears.
 */
if ( ! defined( 'LSCC_CONSENT_VERSION' ) ) {
	define( 'LSCC_CONSENT_VERSION', 2 );
}

define( 'LSCC_PLUGIN_FILE', __FILE__ );
define( 'LSCC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LSCC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'LSCC_DEBUG' ) ) {
	define( 'LSCC_DEBUG', false );
}

/**
 * Main plugin class.
 */
final class Light_Swiss_Cookie_Consent {
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

		require_once LSCC_PLUGIN_DIR . 'includes/service-components.php';
		Light_Swiss_Cookie_Consent_Service_Components::init();

		require_once LSCC_PLUGIN_DIR . 'includes/avada-compat.php';
		Light_Swiss_Cookie_Consent_Avada_Compat::init();

		if ( is_admin() ) {
			require_once LSCC_PLUGIN_DIR . 'includes/admin-page.php';
			Light_Swiss_Cookie_Consent_Admin::init();
		}
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain(
			'light-swiss-cookie-consent',
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
			'banner_title'           => __( 'Cookie-Einstellungen', 'light-swiss-cookie-consent' ),
			'banner_text'            => self::get_neutral_banner_text(),
			'accept_all_text'        => __( 'Alle akzeptieren', 'light-swiss-cookie-consent' ),
			'necessary_only_text'    => __( 'Nur notwendige', 'light-swiss-cookie-consent' ),
			'settings_text'          => __( 'Einstellungen', 'light-swiss-cookie-consent' ),
			'save_settings_text'     => __( 'Auswahl speichern', 'light-swiss-cookie-consent' ),
			'reopen_text'            => __( 'Cookie-Einstellungen', 'light-swiss-cookie-consent' ),
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
		);
	}

	/**
	 * Resolve the locale-aware neutral default banner text.
	 *
	 * Picks a language-neutral default consent text matching the current site
	 * locale. Falls back to language-prefix matching (e.g. `de_AT` -> `de`) and
	 * finally to English when no specific language entry exists.
	 *
	 * @return string
	 */
	public static function get_neutral_banner_text() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return self::resolve_neutral_banner_text_for_locale( (string) $locale );
	}

	/**
	 * Resolve a neutral banner text for a specific locale string.
	 *
	 * @param string $locale Locale code (e.g. `de_CH`, `en_US`, `pt_BR`).
	 * @return string
	 */
	public static function resolve_neutral_banner_text_for_locale( $locale ) {
		$table = self::get_neutral_banner_text_table();
		$lang  = self::extract_language_prefix( $locale );

		if ( '' !== $lang && isset( $table[ $lang ] ) ) {
			return $table[ $lang ];
		}

		return $table['en'];
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
	 * Neutral default banner texts per language prefix.
	 *
	 * The texts are deliberately phrased without `du`/`Sie` so that they fit
	 * Swiss, German and international audiences alike.
	 *
	 * @return array
	 */
	public static function get_neutral_banner_text_table() {
		return array(
			'de' => 'Wir verwenden notwendige Cookies für den Betrieb dieser Website. Statistik, Marketing und externe Medien werden erst nach Zustimmung geladen.',
			'en' => 'We use necessary cookies to operate this website. Statistics, marketing and external media are loaded only after consent.',
			'fr' => 'Nous utilisons des cookies nécessaires au fonctionnement de ce site. Les statistiques, le marketing et les médias externes sont chargés uniquement après consentement.',
			'it' => 'Utilizziamo cookie necessari per il funzionamento di questo sito. Statistiche, marketing e contenuti esterni vengono caricati solo dopo il consenso.',
			'tr' => 'Bu web sitesinin çalışması için gerekli çerezleri kullanıyoruz. İstatistik, pazarlama ve harici medya yalnızca onaydan sonra yüklenir.',
			'hu' => 'A weboldal működéséhez szükséges sütiket használunk. A statisztika, marketing és külső média csak hozzájárulás után töltődik be.',
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
		$value   = apply_filters( 'wpml_translate_single_string', $value, 'Light Swiss Cookie Consent', $label );

		if ( function_exists( 'pll__' ) ) {
			return pll__( $value );
		}

		return $value;
	}

	/**
	 * Enqueue front-end assets.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'lscc-banner',
			LSCC_PLUGIN_URL . 'assets/css/banner.css',
			array(),
			LSCC_VERSION
		);

		wp_enqueue_script(
			'lscc-banner',
			LSCC_PLUGIN_URL . 'assets/js/banner.js',
			array(),
			LSCC_VERSION,
			true
		);

		$options = self::get_options();

		wp_localize_script(
			'lscc-banner',
			'lsccSettings',
			array(
				'cookieName'     => self::COOKIE_NAME,
				'storageKey'     => self::COOKIE_NAME,
				'consentVersion' => (int) LSCC_CONSENT_VERSION,
				'lifetimeDays'   => (int) $options['consent_lifetime_days'],
				'debug'          => (bool) LSCC_DEBUG,
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
		$reopen_text = self::get_translated_option( 'reopen_text', 'Reopen settings button' );

		return sprintf(
			'<button type="button" class="lscc-settings-button" style="%1$s" data-lscc-open-consent-settings aria-controls="lscc-root">%2$s</button>',
			esc_attr( $style ),
			esc_html( $reopen_text )
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
			<div class="lscc-overlay<?php echo $options['blur_enabled'] ? ' lscc-overlay--blur' : ''; ?>" style="<?php echo esc_attr( $style ); ?>" data-lscc-overlay aria-hidden="true" hidden></div>
		<?php endif; ?>
		<div id="lscc-root" class="lscc" style="<?php echo esc_attr( $style ); ?>" data-lscc-root aria-hidden="true" hidden>
			<div class="lscc__panel" role="dialog" aria-labelledby="lscc-title" aria-describedby="lscc-description">
				<div class="lscc__content">
					<div class="lscc__intro">
						<h2 id="lscc-title" class="lscc__title"><?php echo esc_html( $banner_title ); ?></h2>
						<p id="lscc-description" class="lscc__text"><?php echo esc_html( $banner_text ); ?></p>
					</div>

					<div class="lscc__actions" data-lscc-main-actions>
						<button type="button" class="lscc__button lscc__button--primary" data-lscc-accept-all>
							<?php echo esc_html( $accept_all_text ); ?>
						</button>
						<button type="button" class="lscc__button lscc__button--secondary" data-lscc-necessary>
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
									<span class="lscc__category-title"><?php echo esc_html__( 'Notwendig', 'light-swiss-cookie-consent' ); ?></span>
									<span class="lscc__category-text"><?php echo esc_html__( 'Erforderlich für Grundfunktionen der Website.', 'light-swiss-cookie-consent' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="necessary" checked disabled>
							</label>

							<label class="lscc__category">
								<span class="lscc__category-copy">
									<span class="lscc__category-title"><?php echo esc_html__( 'Statistik', 'light-swiss-cookie-consent' ); ?></span>
									<span class="lscc__category-text"><?php echo esc_html__( 'Hilft uns, die Nutzung der Website zu verstehen.', 'light-swiss-cookie-consent' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="statistics">
							</label>

							<label class="lscc__category">
								<span class="lscc__category-copy">
									<span class="lscc__category-title"><?php echo esc_html__( 'Marketing', 'light-swiss-cookie-consent' ); ?></span>
									<span class="lscc__category-text"><?php echo esc_html__( 'Erlaubt Marketing- und Tracking-Dienste.', 'light-swiss-cookie-consent' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="marketing">
							</label>

							<label class="lscc__category">
								<span class="lscc__category-copy">
									<span class="lscc__category-title"><?php echo esc_html__( 'Externe Medien', 'light-swiss-cookie-consent' ); ?></span>
									<span class="lscc__category-text"><?php echo esc_html__( 'Lädt eingebettete Inhalte von externen Plattformen.', 'light-swiss-cookie-consent' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="external_media">
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
								<a class="lscc__legal-link" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html__( 'Datenschutz & Impressum', 'light-swiss-cookie-consent' ); ?></a>
							<?php else : ?>
								<?php if ( $privacy_url ) : ?>
									<a class="lscc__legal-link" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html__( 'Datenschutz', 'light-swiss-cookie-consent' ); ?></a>
								<?php endif; ?>
								<?php if ( $imprint_url ) : ?>
									<a class="lscc__legal-link" href="<?php echo esc_url( $imprint_url ); ?>"><?php echo esc_html__( 'Impressum', 'light-swiss-cookie-consent' ); ?></a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<button type="button" class="lscc-reopen" style="<?php echo esc_attr( $style ); ?>" data-lscc-reopen data-lscc-open-consent-settings aria-controls="lscc-root" data-position="<?php echo esc_attr( $options['reopen_position'] ); ?>" hidden>
			<?php echo esc_html( $reopen_text ); ?>
		</button>
		<?php
	}
}

Light_Swiss_Cookie_Consent::init();
