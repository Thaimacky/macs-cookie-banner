<?php
/**
 * Admin settings page.
 *
 * @package LightSwissCookieConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LSCC_PLUGIN_DIR . 'includes/privacy-check.php';
require_once LSCC_PLUGIN_DIR . 'includes/avada-inventory.php';

/**
 * Admin settings controller.
 */
final class Light_Swiss_Cookie_Consent_Admin {
	/**
	 * Boot admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_post_lscc_save_settings', array( __CLASS__, 'save_settings' ) );
	}

	/**
	 * Register options page.
	 *
	 * @return void
	 */
	public static function add_settings_page() {
		add_menu_page(
			esc_html__( 'Light Swiss Cookie Consent', 'light-swiss-cookie-consent' ),
			esc_html__( 'Light Swiss Cookie Consent', 'light-swiss-cookie-consent' ),
			'manage_options',
			'light-swiss-cookie-consent',
			array( __CLASS__, 'render_settings_page' ),
			'dashicons-shield-alt',
			81
		);

		add_submenu_page(
			'light-swiss-cookie-consent',
			esc_html__( 'Einstellungen', 'light-swiss-cookie-consent' ),
			esc_html__( 'Einstellungen', 'light-swiss-cookie-consent' ),
			'manage_options',
			'light-swiss-cookie-consent',
			array( __CLASS__, 'render_settings_page' )
		);

		add_submenu_page(
			'light-swiss-cookie-consent',
			esc_html__( 'Privacy Check', 'light-swiss-cookie-consent' ),
			esc_html__( 'Privacy Check', 'light-swiss-cookie-consent' ),
			'manage_options',
			'light-swiss-cookie-consent-privacy-check',
			array( 'Light_Swiss_Cookie_Consent_Privacy_Check', 'render_page' )
		);

		add_submenu_page(
			'light-swiss-cookie-consent',
			esc_html__( 'Avada Inventar-Scan', 'light-swiss-cookie-consent' ),
			esc_html__( 'Avada Inventar-Scan', 'light-swiss-cookie-consent' ),
			'manage_options',
			'light-swiss-cookie-consent-avada-inventory',
			array( 'Light_Swiss_Cookie_Consent_Avada_Inventory', 'render_page' )
		);
	}

	/**
	 * Save settings posted from the plugin page.
	 *
	 * @return void
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Einstellungen zu speichern.', 'light-swiss-cookie-consent' ) );
		}

		$nonce = isset( $_POST['lscc_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['lscc_settings_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'lscc_save_settings' ) ) {
			wp_die( esc_html__( 'Ungültige Sicherheitsprüfung.', 'light-swiss-cookie-consent' ) );
		}

		$posted = isset( $_POST['lscc_options'] ) && is_array( $_POST['lscc_options'] ) ? wp_unslash( $_POST['lscc_options'] ) : array();

		// Unchecked checkboxes are not submitted at all. Mark them explicitly as
		// empty so sanitize_options() can distinguish "form submitted, unchecked"
		// from "key missing during migration" and apply the right value.
		foreach ( Light_Swiss_Cookie_Consent::get_bool_option_keys() as $bool_key ) {
			if ( ! isset( $posted[ $bool_key ] ) ) {
				$posted[ $bool_key ] = '';
			}
		}

		$options = Light_Swiss_Cookie_Consent::sanitize_options( $posted );

		update_option( Light_Swiss_Cookie_Consent::OPTION_NAME, $options );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'light-swiss-cookie-consent',
					'updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = Light_Swiss_Cookie_Consent::get_options();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Light Swiss Cookie Consent', 'light-swiss-cookie-consent' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Einstellungen gespeichert.', 'light-swiss-cookie-consent' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lscc_save_settings">
				<?php wp_nonce_field( 'lscc_save_settings', 'lscc_settings_nonce' ); ?>

				<h2><?php echo esc_html__( 'Texte', 'light-swiss-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_text_field( 'banner_title', esc_html__( 'Titel', 'light-swiss-cookie-consent' ), $options['banner_title'] ); ?>
					<?php self::render_text_field( 'banner_text', esc_html__( 'Banner-Text', 'light-swiss-cookie-consent' ), $options['banner_text'] ); ?>
					<?php self::render_text_field( 'accept_all_text', esc_html__( 'Button: Alle akzeptieren', 'light-swiss-cookie-consent' ), $options['accept_all_text'] ); ?>
					<?php self::render_text_field( 'necessary_only_text', esc_html__( 'Button: Nur notwendige', 'light-swiss-cookie-consent' ), $options['necessary_only_text'] ); ?>
					<?php self::render_text_field( 'settings_text', esc_html__( 'Button: Einstellungen', 'light-swiss-cookie-consent' ), $options['settings_text'] ); ?>
					<?php self::render_text_field( 'save_settings_text', esc_html__( 'Button: Auswahl speichern', 'light-swiss-cookie-consent' ), $options['save_settings_text'] ); ?>
					<?php self::render_text_field( 'reopen_text', esc_html__( 'Button: Widerruf', 'light-swiss-cookie-consent' ), $options['reopen_text'] ); ?>
				</table>

				<h2><?php echo esc_html__( 'Farben', 'light-swiss-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_color_field( 'background_color', esc_html__( 'Hintergrund', 'light-swiss-cookie-consent' ), $options['background_color'] ); ?>
					<?php self::render_color_field( 'text_color', esc_html__( 'Text', 'light-swiss-cookie-consent' ), $options['text_color'] ); ?>
					<?php self::render_color_field( 'primary_button_color', esc_html__( 'Primärbutton', 'light-swiss-cookie-consent' ), $options['primary_button_color'] ); ?>
					<?php self::render_color_field( 'primary_text_color', esc_html__( 'Primärbutton Text', 'light-swiss-cookie-consent' ), $options['primary_text_color'] ); ?>
					<?php self::render_color_field( 'secondary_button_color', esc_html__( 'Sekundärbutton', 'light-swiss-cookie-consent' ), $options['secondary_button_color'] ); ?>
					<?php self::render_color_field( 'border_color', esc_html__( 'Rahmenfarbe', 'light-swiss-cookie-consent' ), $options['border_color'] ); ?>
				</table>

				<h2><?php echo esc_html__( 'Overlay & Blur', 'light-swiss-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'overlay_enabled', esc_html__( 'Overlay aktivieren', 'light-swiss-cookie-consent' ), $options['overlay_enabled'] ); ?>
					<?php self::render_color_field( 'overlay_color', esc_html__( 'Overlay-Farbe', 'light-swiss-cookie-consent' ), $options['overlay_color'] ); ?>
					<?php self::render_number_field( 'overlay_opacity', esc_html__( 'Overlay-Deckkraft (0.0 - 1.0)', 'light-swiss-cookie-consent' ), $options['overlay_opacity'], 0, 1, 0.05 ); ?>
					<?php self::render_checkbox_field( 'blur_enabled', esc_html__( 'Blur aktivieren', 'light-swiss-cookie-consent' ), $options['blur_enabled'] ); ?>
					<?php self::render_number_field( 'blur_strength', esc_html__( 'Blur-Stärke (0 - 20 px)', 'light-swiss-cookie-consent' ), $options['blur_strength'], 0, 20, 1 ); ?>
				</table>

				<h2><?php echo esc_html__( 'Floating-Button', 'light-swiss-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::render_select_field(
						'reopen_position',
						esc_html__( 'Position', 'light-swiss-cookie-consent' ),
						$options['reopen_position'],
						array(
							'bottom-right' => esc_html__( 'Unten rechts', 'light-swiss-cookie-consent' ),
							'bottom-left'  => esc_html__( 'Unten links', 'light-swiss-cookie-consent' ),
							'top-right'    => esc_html__( 'Oben rechts', 'light-swiss-cookie-consent' ),
							'top-left'     => esc_html__( 'Oben links', 'light-swiss-cookie-consent' ),
						)
					);
					?>
					<?php self::render_number_field( 'reopen_offset_x', esc_html__( 'Offset X (px)', 'light-swiss-cookie-consent' ), $options['reopen_offset_x'], 0, 200, 1 ); ?>
					<?php self::render_number_field( 'reopen_offset_y', esc_html__( 'Offset Y (px)', 'light-swiss-cookie-consent' ), $options['reopen_offset_y'], 0, 200, 1 ); ?>
				</table>

				<h2><?php echo esc_html__( 'Rechtliche Links', 'light-swiss-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'show_legal_links', esc_html__( 'Rechtliche Links im Banner anzeigen', 'light-swiss-cookie-consent' ), $options['show_legal_links'] ); ?>
					<?php self::render_url_field( 'privacy_url_override', esc_html__( 'Datenschutz-URL (manuell, überschreibt Auto-Erkennung)', 'light-swiss-cookie-consent' ), $options['privacy_url_override'] ); ?>
					<?php self::render_url_field( 'imprint_url_override', esc_html__( 'Impressum-URL (manuell, überschreibt Auto-Erkennung)', 'light-swiss-cookie-consent' ), $options['imprint_url_override'] ); ?>
					<?php $detected_imprint = get_transient( 'lscc_detected_imprint_url' ); ?>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Auto-erkannte Impressum-URL', 'light-swiss-cookie-consent' ); ?></th>
						<td>
							<?php if ( is_string( $detected_imprint ) && '' !== $detected_imprint ) : ?>
								<code><?php echo esc_html( $detected_imprint ); ?></code>
							<?php else : ?>
								<em><?php echo esc_html__( 'Nicht gefunden. Manuelles Feld nutzen.', 'light-swiss-cookie-consent' ); ?></em>
							<?php endif; ?>
							<p class="description"><?php echo esc_html__( 'Erkannt über typische Seiten-Slugs und -Titel. Lokale Suche im Admin, keine Frontend-Crawls. Cache: 24 Stunden.', 'light-swiss-cookie-consent' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Consent-Speicherung', 'light-swiss-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_number_field( 'consent_lifetime_days', esc_html__( 'Consent-Gültigkeit (Tage)', 'light-swiss-cookie-consent' ), $options['consent_lifetime_days'], 1, 365, 1 ); ?>
					<tr>
						<th scope="row"></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Default: 180 Tage. Erlaubt: 1 – 365. Ein kürzerer Wert (z. B. 60) lässt das Banner früher wieder erscheinen. Der Consent wird im Browser des Besuchers gespeichert (localStorage + Cookie); ein Plugin-Update oder eine Plugin-Deinstallation löscht diesen Browser-Speicher nicht automatisch. Bei strukturellen Änderungen wird stattdessen die Konstante LSCC_CONSENT_VERSION erhöht.', 'light-swiss-cookie-consent' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( esc_html__( 'Einstellungen speichern', 'light-swiss-cookie-consent' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a text input row.
	 *
	 * @param string $key   Option key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	private static function render_text_field( $key, $label, $value ) {
		?>
		<tr>
			<th scope="row">
				<label for="lscc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="lscc-<?php echo esc_attr( $key ); ?>"
					name="lscc_options[<?php echo esc_attr( $key ); ?>]"
					value="<?php echo esc_attr( $value ); ?>"
					class="regular-text"
				>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a color input row.
	 *
	 * @param string $key   Option key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	private static function render_color_field( $key, $label, $value ) {
		?>
		<tr>
			<th scope="row">
				<label for="lscc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="lscc-<?php echo esc_attr( $key ); ?>"
					name="lscc_options[<?php echo esc_attr( $key ); ?>]"
					value="<?php echo esc_attr( $value ); ?>"
					class="regular-text"
					pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"
					placeholder="#111827"
				>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a checkbox row.
	 *
	 * @param string $key   Option key.
	 * @param string $label Field label.
	 * @param bool   $value Field value.
	 * @return void
	 */
	private static function render_checkbox_field( $key, $label, $value ) {
		?>
		<tr>
			<th scope="row">
				<label for="lscc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input
					type="checkbox"
					id="lscc-<?php echo esc_attr( $key ); ?>"
					name="lscc_options[<?php echo esc_attr( $key ); ?>]"
					value="1"
					<?php checked( (bool) $value ); ?>
				>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a numeric input row with min/max/step.
	 *
	 * @param string    $key   Option key.
	 * @param string    $label Field label.
	 * @param int|float $value Field value.
	 * @param int|float $min   Minimum.
	 * @param int|float $max   Maximum.
	 * @param int|float $step  Step.
	 * @return void
	 */
	private static function render_number_field( $key, $label, $value, $min, $max, $step ) {
		?>
		<tr>
			<th scope="row">
				<label for="lscc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input
					type="number"
					id="lscc-<?php echo esc_attr( $key ); ?>"
					name="lscc_options[<?php echo esc_attr( $key ); ?>]"
					value="<?php echo esc_attr( (string) $value ); ?>"
					min="<?php echo esc_attr( (string) $min ); ?>"
					max="<?php echo esc_attr( (string) $max ); ?>"
					step="<?php echo esc_attr( (string) $step ); ?>"
					class="small-text"
				>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a select row.
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $value   Current value.
	 * @param array  $choices Map of value => human label.
	 * @return void
	 */
	private static function render_select_field( $key, $label, $value, $choices ) {
		?>
		<tr>
			<th scope="row">
				<label for="lscc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<select
					id="lscc-<?php echo esc_attr( $key ); ?>"
					name="lscc_options[<?php echo esc_attr( $key ); ?>]"
				>
					<?php foreach ( $choices as $choice_value => $choice_label ) : ?>
						<option value="<?php echo esc_attr( $choice_value ); ?>" <?php selected( $value, $choice_value ); ?>><?php echo esc_html( $choice_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a URL input row (may be empty).
	 *
	 * @param string $key   Option key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	private static function render_url_field( $key, $label, $value ) {
		?>
		<tr>
			<th scope="row">
				<label for="lscc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input
					type="url"
					id="lscc-<?php echo esc_attr( $key ); ?>"
					name="lscc_options[<?php echo esc_attr( $key ); ?>]"
					value="<?php echo esc_attr( $value ); ?>"
					class="regular-text"
					placeholder="https://"
				>
			</td>
		</tr>
		<?php
	}
}
