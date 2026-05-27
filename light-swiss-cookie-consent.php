<?php
/**
 * Plugin Name: Light Swiss Cookie Consent
 * Plugin URI:  https://example.com/light-swiss-cookie-consent
 * Description: Lightweight cookie consent banner with script blocking for WordPress.
 * Version:     0.1.0
 * Author:      Light Swiss Cookie Consent
 * Text Domain: light-swiss-cookie-consent
 * Domain Path: /languages
 *
 * @package LightSwissCookieConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LSCC_VERSION', '0.1.0' );
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
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_banner' ), 10 );
		add_shortcode( 'simple_cookie_settings', array( __CLASS__, 'render_settings_shortcode' ) );

		require_once LSCC_PLUGIN_DIR . 'includes/service-components.php';
		Light_Swiss_Cookie_Consent_Service_Components::init();

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
			'banner_title'          => __( 'Cookie-Einstellungen', 'light-swiss-cookie-consent' ),
			'banner_text'           => __( 'Wir verwenden notwendige Cookies, damit diese Website funktioniert. Statistik, Marketing und externe Medien werden erst nach Ihrer Zustimmung geladen.', 'light-swiss-cookie-consent' ),
			'accept_all_text'       => __( 'Alle akzeptieren', 'light-swiss-cookie-consent' ),
			'necessary_only_text'   => __( 'Nur notwendige', 'light-swiss-cookie-consent' ),
			'settings_text'         => __( 'Einstellungen', 'light-swiss-cookie-consent' ),
			'save_settings_text'    => __( 'Auswahl speichern', 'light-swiss-cookie-consent' ),
			'reopen_text'           => __( 'Cookie-Einstellungen', 'light-swiss-cookie-consent' ),
			'background_color'      => '#111827',
			'text_color'            => '#f9fafb',
			'primary_button_color'  => '#e11d48',
			'primary_text_color'    => '#ffffff',
			'secondary_button_color' => '#1f2937',
			'border_color'          => '#374151',
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

		wp_localize_script(
			'lscc-banner',
			'lsccSettings',
			array(
				'cookieName'     => self::COOKIE_NAME,
				'storageKey'     => self::COOKIE_NAME,
				'consentVersion' => '1',
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
		return sprintf(
			'--lscc-bg:%1$s;--lscc-text:%2$s;--lscc-primary:%3$s;--lscc-primary-text:%4$s;--lscc-secondary:%5$s;--lscc-border:%6$s;',
			$options['background_color'],
			$options['text_color'],
			$options['primary_button_color'],
			$options['primary_text_color'],
			$options['secondary_button_color'],
			$options['border_color']
		);
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
		?>
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
									<span class="lscc__category-text"><?php echo esc_html__( 'Erforderlich fuer Grundfunktionen der Website.', 'light-swiss-cookie-consent' ); ?></span>
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
									<span class="lscc__category-text"><?php echo esc_html__( 'Laedt eingebettete Inhalte von externen Plattformen.', 'light-swiss-cookie-consent' ); ?></span>
								</span>
								<input type="checkbox" data-lscc-category="external_media">
							</label>
						</div>

						<div class="lscc__actions lscc__actions--settings">
							<button type="submit" class="lscc__button lscc__button--primary">
								<?php echo esc_html( $save_settings_text ); ?>
							</button>
							<button type="button" class="lscc__button lscc__button--secondary" data-lscc-settings-necessary>
								<?php echo esc_html( $necessary_only_text ); ?>
							</button>
							<button type="button" class="lscc__button lscc__button--ghost" data-lscc-settings-accept-all>
								<?php echo esc_html( $accept_all_text ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<button type="button" class="lscc-reopen" style="<?php echo esc_attr( $style ); ?>" data-lscc-reopen data-lscc-open-consent-settings aria-controls="lscc-root" hidden>
			<?php echo esc_html( $reopen_text ); ?>
		</button>
		<?php
	}
}

Light_Swiss_Cookie_Consent::init();
