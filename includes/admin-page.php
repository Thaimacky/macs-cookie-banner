<?php
/**
 * Admin settings page.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once MCB_PLUGIN_DIR . 'includes/privacy-check.php';
require_once MCB_PLUGIN_DIR . 'includes/avada-inventory.php';

/**
 * Admin settings controller.
 */
final class Macs_Cookie_Banner_Admin {
	/**
	 * Boot admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_post_mcb_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_mcb_import_avada_colors', array( __CLASS__, 'import_avada_colors' ) );
	}

	/**
	 * Register options page.
	 *
	 * @return void
	 */
	public static function add_settings_page() {
		add_menu_page(
			esc_html__( 'Mac\'s Cookie Banner', 'macs-cookie-banner' ),
			esc_html__( 'Mac\'s Cookie Banner', 'macs-cookie-banner' ),
			'manage_options',
			'macs-cookie-banner',
			array( __CLASS__, 'render_settings_page' ),
			'dashicons-shield-alt',
			81
		);

		add_submenu_page(
			'macs-cookie-banner',
			esc_html__( 'Einstellungen', 'macs-cookie-banner' ),
			esc_html__( 'Einstellungen', 'macs-cookie-banner' ),
			'manage_options',
			'macs-cookie-banner',
			array( __CLASS__, 'render_settings_page' )
		);

		add_submenu_page(
			'macs-cookie-banner',
			esc_html__( 'Privacy Check', 'macs-cookie-banner' ),
			esc_html__( 'Privacy Check', 'macs-cookie-banner' ),
			'manage_options',
			'macs-cookie-banner-privacy-check',
			array( 'Macs_Cookie_Banner_Privacy_Check', 'render_page' )
		);

		add_submenu_page(
			'macs-cookie-banner',
			esc_html__( 'Avada Inventar-Scan', 'macs-cookie-banner' ),
			esc_html__( 'Avada Inventar-Scan', 'macs-cookie-banner' ),
			'manage_options',
			'macs-cookie-banner-avada-inventory',
			array( 'Macs_Cookie_Banner_Avada_Inventory', 'render_page' )
		);

		add_submenu_page(
			'macs-cookie-banner',
			esc_html__( 'Consent-Code-Manager', 'macs-cookie-banner' ),
			esc_html__( 'Consent-Code-Manager', 'macs-cookie-banner' ),
			'manage_options',
			Macs_Cookie_Banner_Codes::PAGE_SLUG,
			array( 'Macs_Cookie_Banner_Codes', 'render_page' )
		);
	}

	/**
	 * Save settings posted from the plugin page.
	 *
	 * @return void
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Einstellungen zu speichern.', 'macs-cookie-banner' ) );
		}

		$nonce = isset( $_POST['mcb_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mcb_settings_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'mcb_save_settings' ) ) {
			wp_die( esc_html__( 'Ungültige Sicherheitsprüfung.', 'macs-cookie-banner' ) );
		}

		$posted = isset( $_POST['lscc_options'] ) && is_array( $_POST['lscc_options'] ) ? wp_unslash( $_POST['lscc_options'] ) : array();

		// Unchecked checkboxes are not submitted at all. Mark them explicitly as
		// empty so sanitize_options() can distinguish "form submitted, unchecked"
		// from "key missing during migration" and apply the right value.
		foreach ( Macs_Cookie_Banner::get_bool_option_keys() as $bool_key ) {
			if ( ! isset( $posted[ $bool_key ] ) ) {
				$posted[ $bool_key ] = '';
			}
		}

		$options = Macs_Cookie_Banner::sanitize_options( $posted );

		update_option( Macs_Cookie_Banner::OPTION_NAME, $options );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'macs-cookie-banner',
					'updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Import the Avada brand color into the banner accent colors (ADR-27).
	 *
	 * Runs only on the explicit "Avada-Farben übernehmen" click. Read-only on
	 * the Avada side; writes only the mapped accent color keys into the existing
	 * options. Background, text, overlay and the secondary button stay untouched.
	 *
	 * @return void
	 */
	public static function import_avada_colors() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Aktion auszuführen.', 'macs-cookie-banner' ) );
		}

		$nonce = isset( $_POST['mcb_avada_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mcb_avada_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'mcb_import_avada_colors' ) ) {
			wp_die( esc_html__( 'Ungültige Sicherheitsprüfung.', 'macs-cookie-banner' ) );
		}

		$result = 'empty';

		if ( Macs_Cookie_Banner_Avada_Colors::is_active() ) {
			$mapped = Macs_Cookie_Banner_Avada_Colors::map_to_banner( Macs_Cookie_Banner_Avada_Colors::get_brand_color() );

			if ( ! empty( $mapped ) ) {
				$current = Macs_Cookie_Banner::get_options();
				$merged  = Macs_Cookie_Banner::sanitize_options( array_merge( $current, $mapped ) );
				update_option( Macs_Cookie_Banner::OPTION_NAME, $merged );
				$result = 'imported';
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'macs-cookie-banner',
					'mcb_avada' => $result,
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

		$options = Macs_Cookie_Banner::get_options();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Mac\'s Cookie Banner', 'macs-cookie-banner' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Einstellungen gespeichert.', 'macs-cookie-banner' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['mcb_avada'] ) ) : ?>
				<?php $mcb_avada_result = sanitize_text_field( wp_unslash( $_GET['mcb_avada'] ) ); ?>
				<?php if ( 'imported' === $mcb_avada_result ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo esc_html__( 'Avada-Farben übernommen. Bei Bedarf manuell anpassen und speichern.', 'macs-cookie-banner' ); ?></p>
					</div>
				<?php elseif ( 'empty' === $mcb_avada_result ) : ?>
					<div class="notice notice-warning is-dismissible">
						<p><?php echo esc_html__( 'Keine Avada-Markenfarbe gefunden. Farben unverändert.', 'macs-cookie-banner' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( Macs_Cookie_Banner_Avada_Colors::is_active() ) : ?>
				<h2><?php echo esc_html__( 'Avada-Farben', 'macs-cookie-banner' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:1em;">
					<input type="hidden" name="action" value="mcb_import_avada_colors">
					<?php wp_nonce_field( 'mcb_import_avada_colors', 'mcb_avada_nonce' ); ?>
					<p class="description" style="margin:0 0 .5em;"><?php echo esc_html__( 'Übernimmt die Markenfarbe aus Avada in Primärbutton und Rahmen (Button-Text wird automatisch lesbar kontrastiert). Bestehende Farben bleiben, bis Sie hier klicken.', 'macs-cookie-banner' ); ?></p>
					<?php submit_button( esc_html__( 'Avada-Farben übernehmen', 'macs-cookie-banner' ), 'secondary', 'mcb_import_avada', false ); ?>
				</form>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mcb_save_settings">
				<?php wp_nonce_field( 'mcb_save_settings', 'mcb_settings_nonce' ); ?>

				<h2><?php echo esc_html__( 'Darstellung', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::render_select_field(
						'design_preset',
						esc_html__( 'Design-Preset', 'macs-cookie-banner' ),
						$options['design_preset'],
						array(
							'classic' => esc_html__( 'Classic', 'macs-cookie-banner' ),
							'modern'  => esc_html__( 'Modern', 'macs-cookie-banner' ),
							'premium' => esc_html__( 'Premium', 'macs-cookie-banner' ),
						)
					);
					?>
					<tr>
						<td colspan="2">
							<p class="description" style="margin:0;"><?php echo esc_html__( 'Presets verändern nur Form, Radius, Schatten, Glow und Abstände — nicht die Farben. Farben kommen weiterhin aus den Farb-Feldern bzw. dem Avada-Farbimport.', 'macs-cookie-banner' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Texte', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_text_field( 'banner_title', esc_html__( 'Titel', 'macs-cookie-banner' ), $options['banner_title'] ); ?>
					<?php self::render_text_field( 'banner_text', esc_html__( 'Banner-Text', 'macs-cookie-banner' ), $options['banner_text'] ); ?>
					<?php self::render_text_field( 'accept_all_text', esc_html__( 'Button: Alle akzeptieren', 'macs-cookie-banner' ), $options['accept_all_text'] ); ?>
					<?php self::render_text_field( 'necessary_only_text', esc_html__( 'Button: Nur notwendige', 'macs-cookie-banner' ), $options['necessary_only_text'] ); ?>
					<?php self::render_text_field( 'settings_text', esc_html__( 'Button: Einstellungen', 'macs-cookie-banner' ), $options['settings_text'] ); ?>
					<?php self::render_text_field( 'save_settings_text', esc_html__( 'Button: Auswahl speichern', 'macs-cookie-banner' ), $options['save_settings_text'] ); ?>
					<?php self::render_text_field( 'reopen_text', esc_html__( 'Button: Widerruf', 'macs-cookie-banner' ), $options['reopen_text'] ); ?>
				</table>

				<h2><?php echo esc_html__( 'Farben', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_color_field( 'background_color', esc_html__( 'Hintergrund', 'macs-cookie-banner' ), $options['background_color'] ); ?>
					<?php self::render_color_field( 'text_color', esc_html__( 'Text', 'macs-cookie-banner' ), $options['text_color'] ); ?>
					<?php self::render_color_field( 'primary_button_color', esc_html__( 'Primärbutton', 'macs-cookie-banner' ), $options['primary_button_color'] ); ?>
					<?php self::render_color_field( 'primary_text_color', esc_html__( 'Primärbutton Text', 'macs-cookie-banner' ), $options['primary_text_color'] ); ?>
					<?php self::render_color_field( 'secondary_button_color', esc_html__( 'Sekundärbutton', 'macs-cookie-banner' ), $options['secondary_button_color'] ); ?>
					<?php self::render_color_field( 'border_color', esc_html__( 'Rahmenfarbe', 'macs-cookie-banner' ), $options['border_color'] ); ?>
				</table>

				<h2><?php echo esc_html__( 'Overlay & Blur', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'overlay_enabled', esc_html__( 'Overlay aktivieren', 'macs-cookie-banner' ), $options['overlay_enabled'] ); ?>
					<?php self::render_color_field( 'overlay_color', esc_html__( 'Overlay-Farbe', 'macs-cookie-banner' ), $options['overlay_color'] ); ?>
					<?php self::render_number_field( 'overlay_opacity', esc_html__( 'Overlay-Deckkraft (0.0 - 1.0)', 'macs-cookie-banner' ), $options['overlay_opacity'], 0, 1, 0.05 ); ?>
					<?php self::render_checkbox_field( 'blur_enabled', esc_html__( 'Blur aktivieren', 'macs-cookie-banner' ), $options['blur_enabled'] ); ?>
					<?php self::render_number_field( 'blur_strength', esc_html__( 'Blur-Stärke (0 - 20 px)', 'macs-cookie-banner' ), $options['blur_strength'], 0, 20, 1 ); ?>
				</table>

				<h2><?php echo esc_html__( 'Floating-Button', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::render_select_field(
						'reopen_position',
						esc_html__( 'Position', 'macs-cookie-banner' ),
						$options['reopen_position'],
						array(
							'bottom-right' => esc_html__( 'Unten rechts', 'macs-cookie-banner' ),
							'bottom-left'  => esc_html__( 'Unten links', 'macs-cookie-banner' ),
							'top-right'    => esc_html__( 'Oben rechts', 'macs-cookie-banner' ),
							'top-left'     => esc_html__( 'Oben links', 'macs-cookie-banner' ),
							'hidden'       => esc_html__( 'Versteckt', 'macs-cookie-banner' ),
						)
					);
					?>
					<?php if ( 'hidden' === $options['reopen_position'] ) : ?>
						<tr>
							<td colspan="2">
								<div class="notice notice-warning inline" style="margin:0;">
									<p><?php echo esc_html__( 'Bei verstecktem Cookie-Einstellungs-Button muss ein alternativer Widerrufsweg vorhanden sein (z. B. [simple_cookie_settings] im Footer).', 'macs-cookie-banner' ); ?></p>
								</div>
							</td>
						</tr>
					<?php endif; ?>
					<?php self::render_number_field( 'reopen_offset_x', esc_html__( 'Offset X (px)', 'macs-cookie-banner' ), $options['reopen_offset_x'], 0, 200, 1 ); ?>
					<?php self::render_number_field( 'reopen_offset_y', esc_html__( 'Offset Y (px)', 'macs-cookie-banner' ), $options['reopen_offset_y'], 0, 200, 1 ); ?>
				</table>

				<h2><?php echo esc_html__( 'Rechtliche Links', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'show_legal_links', esc_html__( 'Rechtliche Links im Banner anzeigen', 'macs-cookie-banner' ), $options['show_legal_links'] ); ?>
					<?php self::render_url_field( 'privacy_url_override', esc_html__( 'Datenschutz-URL (manuell, überschreibt Auto-Erkennung)', 'macs-cookie-banner' ), $options['privacy_url_override'] ); ?>
					<?php self::render_url_field( 'imprint_url_override', esc_html__( 'Impressum-URL (manuell, überschreibt Auto-Erkennung)', 'macs-cookie-banner' ), $options['imprint_url_override'] ); ?>
					<?php $detected_imprint = get_transient( 'lscc_detected_imprint_url' ); ?>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Auto-erkannte Impressum-URL', 'macs-cookie-banner' ); ?></th>
						<td>
							<?php if ( is_string( $detected_imprint ) && '' !== $detected_imprint ) : ?>
								<code><?php echo esc_html( $detected_imprint ); ?></code>
							<?php else : ?>
								<em><?php echo esc_html__( 'Nicht gefunden. Manuelles Feld nutzen.', 'macs-cookie-banner' ); ?></em>
							<?php endif; ?>
							<p class="description"><?php echo esc_html__( 'Erkannt über typische Seiten-Slugs und -Titel. Lokale Suche im Admin, keine Frontend-Crawls. Cache: 24 Stunden.', 'macs-cookie-banner' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Consent-Speicherung', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_number_field( 'consent_lifetime_days', esc_html__( 'Consent-Gültigkeit (Tage)', 'macs-cookie-banner' ), $options['consent_lifetime_days'], 1, 365, 1 ); ?>
					<tr>
						<th scope="row"></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Default: 180 Tage. Erlaubt: 1 – 365. Ein kürzerer Wert (z. B. 60) lässt das Banner früher wieder erscheinen. Der Consent wird im Browser des Besuchers gespeichert (localStorage + Cookie); ein Plugin-Update oder eine Plugin-Deinstallation löscht diesen Browser-Speicher nicht automatisch. Bei strukturellen Änderungen wird stattdessen die Konstante MCB_CONSENT_VERSION erhöht.', 'macs-cookie-banner' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Externe Medien', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'youtube_remote_thumbnails', esc_html__( 'YouTube-Thumbnails vor Consent laden', 'macs-cookie-banner' ), $options['youtube_remote_thumbnails'] ); ?>
					<tr>
						<th scope="row"></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Default: AUS (maximaler Datenschutz). AUS = lokaler Platzhalter ohne externe Bildanfrage. AN = YouTube-Vorschaubild von i.ytimg.com. WICHTIG: Bei AN wird bereits VOR der Zustimmung ein Bild von Google geladen (überträgt die Besucher-IP an Google). Auch bei AN entsteht kein iframe, kein iframe_api und keine youtube.com-Cookies vor Consent. Ein per [lscc_youtube thumbnail_id="..."] gesetztes lokales Bild hat immer Vorrang.', 'macs-cookie-banner' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Avada-Kompatibilität', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'avada_youtube_block', esc_html__( 'Avada-YouTube (fusion_youtube) vor Consent blockieren', 'macs-cookie-banner' ), $options['avada_youtube_block'] ); ?>
					<tr>
						<th scope="row"></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Wenn aktiviert, werden Avada/Fusion-Builder-YouTube-Elemente serverseitig durch einen Platzhalter ersetzt. Das YouTube-Video lädt erst nach Zustimmung zur Kategorie „Externe Medien". Es findet keine Inhaltsänderung statt; bei deaktiviertem Schalter rendert Avada wie gewohnt. Nur YouTube; Vimeo, Maps und Hintergrundvideos sind nicht betroffen.', 'macs-cookie-banner' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'YOTU-Kompatibilität', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'yotu_consent_gating', esc_html__( 'YOTU-YouTube-Galerie (Yotuwp) vor Consent blockieren', 'macs-cookie-banner' ), $options['yotu_consent_gating'] ); ?>
					<tr>
						<th scope="row"></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Default: AUS. Wenn aktiviert, wird das Frontend-Script des Plugins „Yotuwp – Easy YouTube Embed" über die LSCC-Script-Blockade an die Kategorie „Externe Medien" gekoppelt und die Galerie-Vorschaubilder werden neutralisiert. Vor Zustimmung entsteht dann KEIN Request an youtube.com, youtube-nocookie.com, das iframe_api/www-widgetapi und KEIN Vorschaubild von i.ytimg.com (keine IP-Übertragung an Google). Über der Galerie erscheint ein Zustimmungs-Hinweis. Nach Zustimmung funktioniert YOTU normal. Reversibel: bei deaktiviertem Schalter rendert YOTU wie gewohnt. Hinweis: greift bei per Shortcode eingebundenen Galerien; reine Block-/Widget-Einbindungen sind separat zu prüfen. Inline-Script-Gating benötigt WordPress 5.7+.', 'macs-cookie-banner' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Avada-Google-Maps', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'avada_maps_block', esc_html__( 'Avada-Karten (fusion_map) vor Consent blockieren', 'macs-cookie-banner' ), $options['avada_maps_block'] ); ?>
					<tr>
						<th scope="row"></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Wenn aktiviert, werden Avada/Fusion-Builder-Karten (fusion_map) serverseitig durch einen LSCC-Platzhalter ersetzt und die Google-Maps-JS-API (maps.googleapis.com/maps/api/js) wird vor Consent blockiert. Vor Zustimmung zur Kategorie „Externe Medien" entsteht KEIN Google-Kontakt. Nach Zustimmung wird die Karte als Google-Maps-Embed (Standort) geladen — nicht als Avadas voll gestylte JS-Karte (bewusster Trade-off). Bei nicht erkennbarer Adresse rendert Avada wie gewohnt; reversibel (Schalter aus).', 'macs-cookie-banner' ); ?></p>
							<p class="description"><strong><?php echo esc_html__( 'Wichtig: Nur eine Consent-Schicht verwenden. Avada Privacy Maps und LSCC Maps nicht parallel aktivieren.', 'macs-cookie-banner' ); ?></strong></p>
						</td>
					</tr>
				</table>

				<?php submit_button( esc_html__( 'Einstellungen speichern', 'macs-cookie-banner' ) ); ?>
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
