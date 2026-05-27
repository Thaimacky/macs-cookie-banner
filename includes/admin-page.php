<?php
/**
 * Admin settings page.
 *
 * @package LightSwissCookieConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_options_page(
			esc_html__( 'Light Swiss Cookie Consent', 'light-swiss-cookie-consent' ),
			esc_html__( 'Light Swiss Cookie Consent', 'light-swiss-cookie-consent' ),
			'manage_options',
			'light-swiss-cookie-consent',
			array( __CLASS__, 'render_settings_page' )
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
			wp_die( esc_html__( 'Ungueltige Sicherheitspruefung.', 'light-swiss-cookie-consent' ) );
		}

		$posted  = isset( $_POST['lscc_options'] ) && is_array( $_POST['lscc_options'] ) ? wp_unslash( $_POST['lscc_options'] ) : array();
		$options = Light_Swiss_Cookie_Consent::sanitize_options( $posted );

		update_option( Light_Swiss_Cookie_Consent::OPTION_NAME, $options );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'light-swiss-cookie-consent',
					'updated' => 'true',
				),
				admin_url( 'options-general.php' )
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
					<?php self::render_color_field( 'primary_button_color', esc_html__( 'Primaerbutton', 'light-swiss-cookie-consent' ), $options['primary_button_color'] ); ?>
					<?php self::render_color_field( 'primary_text_color', esc_html__( 'Primaerbutton Text', 'light-swiss-cookie-consent' ), $options['primary_text_color'] ); ?>
					<?php self::render_color_field( 'secondary_button_color', esc_html__( 'Sekundaerbutton', 'light-swiss-cookie-consent' ), $options['secondary_button_color'] ); ?>
					<?php self::render_color_field( 'border_color', esc_html__( 'Rahmenfarbe', 'light-swiss-cookie-consent' ), $options['border_color'] ); ?>
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
}
