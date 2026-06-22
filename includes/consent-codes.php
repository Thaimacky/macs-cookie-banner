<?php
/**
 * Consent-Code-Manager: zentrale, consent-gegatete Verwaltung von Tracking-/
 * Marketing-Snippets (GA4, GTM, Meta Pixel, Hotjar, ...).
 *
 * Der Betreiber fügt komplette Vendor-Snippets ("paste-as-is") ein und ordnet
 * jedem eine Consent-Kategorie und eine Position (Head / Body-Anfang / Footer)
 * zu. Beim Rendern werden alle enthaltenen <script>-Tags in die bestehende
 * LSCC-Script-Blockade umgeschrieben (type="text/plain" + data-cookie-category);
 * <noscript>-Teile werden konservativ entfernt. Die vorhandene banner.js-
 * Mechanik (activateBlockedScripts) aktiviert sie erst nach Zustimmung — es gibt
 * KEINE neue Frontend-Logik.
 *
 * Datenmodell ist scannerfähig (vendor/source/category/location). Export/Import
 * nutzt ein versioniertes Envelope, das später die gesamte LSCC-Konfiguration
 * aufnehmen kann (nicht nur Consent-Codes).
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consent-Code-Manager controller.
 */
final class Macs_Cookie_Banner_Codes {
	/**
	 * Option name holding the snippet entries.
	 */
	const OPTION_NAME = 'lscc_consent_codes';

	/**
	 * Nonce action for saving.
	 */
	const NONCE_ACTION = 'mcb_save_consent_codes';

	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'macs-cookie-banner-codes';

	/**
	 * Export envelope format version.
	 */
	const EXPORT_VERSION = 1;

	/**
	 * Allowed consent categories.
	 *
	 * @return array
	 */
	public static function categories() {
		return array( 'necessary', 'statistics', 'marketing', 'external_media' );
	}

	/**
	 * Allowed output locations.
	 *
	 * @return array
	 */
	public static function locations() {
		return array( 'head', 'body_open', 'footer' );
	}

	/**
	 * Known vendor labels for the detection badge.
	 *
	 * @return array
	 */
	public static function vendor_labels() {
		return array(
			'ga4'         => 'Google Analytics 4',
			'gtm'         => 'Google Tag Manager',
			'google_ads'  => 'Google Ads',
			'meta_pixel'  => 'Meta / Facebook Pixel',
			'mailchimp'   => 'Mailchimp',
			'hotjar'      => 'Hotjar',
			'recaptcha'   => 'Google reCAPTCHA',
			'calendly'    => 'Calendly',
			'custom'      => __( 'Eigenes Snippet', 'macs-cookie-banner' ),
		);
	}

	/**
	 * Suggested default consent category for a detected vendor.
	 *
	 * Used only as an auto-suggestion for *newly added* snippets of a recognized
	 * vendor (see build_entries()); existing snippets keep their stored category.
	 *
	 * @param string $vendor Vendor key.
	 * @return string Consent category.
	 */
	public static function vendor_default_category( $vendor ) {
		$map = array(
			'ga4'        => 'statistics',
			'gtm'        => 'statistics',
			'meta_pixel' => 'marketing',
			'google_ads' => 'marketing',
			'mailchimp'  => 'marketing',
		);

		return isset( $map[ $vendor ] ) ? $map[ $vendor ] : 'statistics';
	}

	/**
	 * Register hooks. Loaded from the main plugin init in both contexts.
	 *
	 * @return void
	 */
	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_post_' . self::NONCE_ACTION, array( __CLASS__, 'save' ) );
			add_action( 'admin_post_mcb_export_consent_codes', array( __CLASS__, 'export' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
			return;
		}

		add_action( 'wp_head', array( __CLASS__, 'render_head' ), 99 );
		add_action( 'wp_body_open', array( __CLASS__, 'render_body_open' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_footer' ), 5 );
	}

	/**
	 * Return the sanitized snippet entries.
	 *
	 * @return array
	 */
	public static function get_codes() {
		$stored = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $stored ) ) {
			return array();
		}

		$clean = array();
		foreach ( $stored as $entry ) {
			if ( is_array( $entry ) ) {
				$clean[] = self::normalize_entry( $entry );
			}
		}

		return $clean;
	}

	/**
	 * Normalize a stored entry (does not re-apply the capability gate; stored
	 * code is assumed to have passed the gate at save time).
	 *
	 * @param array $entry Raw stored entry.
	 * @return array
	 */
	private static function normalize_entry( $entry ) {
		$code = isset( $entry['code'] ) ? (string) $entry['code'] : '';

		return array(
			'id'       => isset( $entry['id'] ) && '' !== $entry['id'] ? sanitize_key( $entry['id'] ) : self::new_id(),
			'label'    => isset( $entry['label'] ) ? sanitize_text_field( $entry['label'] ) : '',
			'vendor'   => self::detect_vendor( $code ),
			'source'   => isset( $entry['source'] ) && '' !== $entry['source'] ? sanitize_key( $entry['source'] ) : 'manual',
			'category' => self::sanitize_enum( isset( $entry['category'] ) ? $entry['category'] : '', self::categories(), 'statistics' ),
			'location' => self::sanitize_enum( isset( $entry['location'] ) ? $entry['location'] : '', self::locations(), 'head' ),
			'enabled'  => ! empty( $entry['enabled'] ),
			'order'    => isset( $entry['order'] ) ? (int) $entry['order'] : 0,
			'code'     => $code,
		);
	}

	/**
	 * Generate a new stable-ish entry id.
	 *
	 * @return string
	 */
	private static function new_id() {
		return 'mcb_code_' . wp_generate_password( 10, false, false );
	}

	/**
	 * Validate a value against an allowlist.
	 *
	 * @param string $value    Raw value.
	 * @param array  $allowed  Allowed values.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private static function sanitize_enum( $value, $allowed, $fallback ) {
		$value = is_scalar( $value ) ? (string) $value : '';

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Detect the third-party vendor from a snippet (for the badge / data model).
	 *
	 * @param string $code Raw snippet.
	 * @return string Vendor key or '' when empty.
	 */
	public static function detect_vendor( $code ) {
		return self::match_vendor( (string) $code );
	}

	/**
	 * Match a text against the known vendor patterns. Single source of truth used
	 * by the Consent-Code-Manager (badge) and the Privacy-Check scanner.
	 *
	 * @param string $text Haystack (snippet code or a <script> tag + content).
	 * @return string Vendor key, 'custom' for unmatched non-empty text, '' for empty.
	 */
	public static function match_vendor( $text ) {
		$text = (string) $text;
		$lc   = strtolower( $text );

		if ( '' === trim( $lc ) ) {
			return '';
		}

		if ( false !== strpos( $lc, 'googletagmanager.com/gtm.js' ) || false !== strpos( $lc, "'gtm.start'" ) || preg_match( '/\bgtm-[a-z0-9]+\b/i', $text ) ) {
			return 'gtm';
		}
		// Google Ads (Conversion Tracking / Remarketing) MUST be checked before GA4:
		// Ads tags also use gtag()/googletagmanager gtag.js, but with an AW-… account id,
		// and would otherwise be misclassified as GA4. The AW- id is present in both the
		// conversion event snippet and the remarketing tag.
		if (
			preg_match( '/\baw-[0-9]{6,}\b/i', $text )                 // gtag/js?id=AW-… + send_to: 'AW-…' (Conversion + Remarketing)
			|| false !== strpos( $lc, 'google_conversion_id' )        // legacy Conversion Tracking
			|| false !== strpos( $lc, 'googleadservices.com' )        // conversion.js / /pagead/conversion
			|| false !== strpos( $lc, 'googleads.g.doubleclick.net' ) // Remarketing-Pixel
		) {
			return 'google_ads';
		}
		if ( false !== strpos( $lc, 'googletagmanager.com/gtag/js' ) || false !== strpos( $lc, 'gtag(' ) || preg_match( '/\bg-[a-z0-9]{6,}\b/i', $text ) ) {
			return 'ga4';
		}
		if ( false !== strpos( $lc, 'connect.facebook.net' ) || false !== strpos( $lc, 'fbq(' ) ) {
			return 'meta_pixel';
		}
		if ( false !== strpos( $lc, 'chimpstatic.com' ) || false !== strpos( $lc, 'list-manage.com' ) || false !== strpos( $lc, 'mailchimp' ) ) {
			return 'mailchimp';
		}
		if ( false !== strpos( $lc, 'static.hotjar.com' ) || false !== strpos( $lc, '_hjsettings' ) || false !== strpos( $lc, 'hotjar' ) ) {
			return 'hotjar';
		}
		if ( false !== strpos( $lc, 'google.com/recaptcha' ) || false !== strpos( $lc, 'grecaptcha' ) ) {
			return 'recaptcha';
		}
		if ( false !== strpos( $lc, 'assets.calendly.com' ) || false !== strpos( $lc, 'calendly.com' ) ) {
			return 'calendly';
		}

		return 'custom';
	}

	/* --------------------------------------------------------------------- */
	/* Frontend rendering                                                     */
	/* --------------------------------------------------------------------- */

	/**
	 * Output gated snippets registered for the head.
	 *
	 * @return void
	 */
	public static function render_head() {
		self::render_location( 'head' );
	}

	/**
	 * Output gated snippets registered for the body open hook.
	 *
	 * @return void
	 */
	public static function render_body_open() {
		self::render_location( 'body_open' );
	}

	/**
	 * Output gated snippets registered for the footer.
	 *
	 * @return void
	 */
	public static function render_footer() {
		self::render_location( 'footer' );
	}

	/**
	 * Echo all enabled snippets for a location, gated by consent category.
	 *
	 * The transformed snippet is intentionally emitted raw — it is the gated
	 * (type="text/plain") markup. Category/location are enum-validated.
	 *
	 * @param string $location Location key.
	 * @return void
	 */
	private static function render_location( $location ) {
		$codes = self::get_codes();

		// Stable ordering by the stored order field, then by array position.
		usort(
			$codes,
			static function ( $a, $b ) {
				return (int) $a['order'] - (int) $b['order'];
			}
		);

		foreach ( $codes as $entry ) {
			if ( empty( $entry['enabled'] ) || $entry['location'] !== $location ) {
				continue;
			}
			if ( '' === trim( (string) $entry['code'] ) ) {
				continue;
			}

			echo "\n<!-- LSCC Consent-Code (" . esc_html( $entry['category'] ) . ") -->\n";
			echo self::transform_snippet( $entry['code'], $entry['category'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gated snippet, see docblock.
			echo "\n";
		}
	}

	/**
	 * Transform a pasted vendor snippet into LSCC-gated markup.
	 *
	 * - <noscript>...</noscript> is removed (cannot be JS-gated → conservative).
	 * - every <script ...> gets type="text/plain" + data-cookie-category and the
	 *   original type is preserved in data-cookie-type for banner.js.
	 *
	 * @param string $code     Raw snippet.
	 * @param string $category Validated consent category.
	 * @return string
	 */
	public static function transform_snippet( $code, $category ) {
		$code = (string) $code;
		$cat  = esc_attr( $category );

		// Drop noscript blocks (GTM body iframe etc.).
		$code = preg_replace( '#<noscript\b[^>]*>.*?</noscript>#is', '', $code );

		// Rewrite every <script> opening tag.
		$code = preg_replace_callback(
			'#<script\b([^>]*)>#i',
			static function ( $m ) use ( $cat ) {
				$attrs = $m[1];
				$type  = 'text/javascript';

				if ( preg_match( '/\btype\s*=\s*("|\')(.*?)\1/i', $attrs, $tm ) ) {
					$type  = '' !== $tm[2] ? $tm[2] : $type;
					$attrs = preg_replace( '/\btype\s*=\s*("|\').*?\1/i', '', $attrs, 1 );
				}

				return '<script' . $attrs . ' type="text/plain" data-cookie-category="' . $cat . '" data-cookie-type="' . esc_attr( $type ) . '">';
			},
			$code
		);

		return $code;
	}

	/* --------------------------------------------------------------------- */
	/* Admin: assets, page, save, export/import                               */
	/* --------------------------------------------------------------------- */

	/**
	 * Enqueue the minimal repeater script on the manager page only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue_admin( $hook ) {
		unset( $hook );

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page check.

		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		wp_enqueue_script(
			'mcb-admin-consent-codes',
			MCB_PLUGIN_URL . 'assets/js/admin-consent-codes.js',
			array(),
			MCB_VERSION,
			true
		);
	}

	/**
	 * Render the manager admin page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Seite zu öffnen.', 'macs-cookie-banner' ) );
		}

		$codes    = self::get_codes();
		$can_raw  = current_user_can( 'unfiltered_html' );
		$notice   = isset( $_GET['mcb_notice'] ) ? sanitize_key( wp_unslash( $_GET['mcb_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rejected = isset( $_GET['mcb_rejected'] ) ? (int) $_GET['mcb_rejected'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Consent-Code-Manager', 'macs-cookie-banner' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Snippets gespeichert.', 'macs-cookie-banner' ); ?></p></div>
			<?php elseif ( 'imported' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Konfiguration importiert.', 'macs-cookie-banner' ); ?></p></div>
			<?php elseif ( 'import_error' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html__( 'Import fehlgeschlagen: ungültiges JSON-Format.', 'macs-cookie-banner' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $rejected > 0 ) : ?>
				<div class="notice notice-warning is-dismissible"><p>
					<?php
					printf(
						/* translators: %d: number of rejected snippets. */
						esc_html__( '%d Snippet(s) ohne Code gespeichert: Zum Speichern von rohem Code wird die Berechtigung „unfiltered_html" benötigt.', 'macs-cookie-banner' ),
						(int) $rejected
					);
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( ! $can_raw ) : ?>
				<div class="notice notice-info"><p><?php echo esc_html__( 'Hinweis: Ihr Benutzerkonto besitzt nicht die Berechtigung „unfiltered_html". Code-Felder werden beim Speichern verworfen. (Bei Multisite nur Super-Admins.)', 'macs-cookie-banner' ); ?></p></div>
			<?php endif; ?>

			<p><?php echo esc_html__( 'Hier eingefügte Tracking-/Marketing-Snippets laden erst nach Zustimmung zur gewählten Kategorie. Vollständigen Vendor-Code einfügen (mit <script>-Tags); <noscript>-Teile werden entfernt.', 'macs-cookie-banner' ); ?></p>

			<div class="notice notice-info inline" style="margin:12px 0;"><p>
				<strong><?php echo esc_html__( 'Empfohlener Einbauweg:', 'macs-cookie-banner' ); ?></strong>
				<?php echo esc_html__( 'Tracking-/Marketing-Snippets (z. B. GA4, GTM, Meta Pixel) über diesen Consent-Code-Manager einbinden und YouTube- bzw. Google-Maps-Einbindungen über die vorgesehenen MCB-Komponenten/Shortcodes ([lscc_youtube], [lscc_google_map]) bzw. die Avada-Integration führen. Nur so können sie zuverlässig vor der Einwilligung blockiert werden; direkt im Theme/Footer oder als rohes iframe eingebundene Dienste laden ggf. bereits vor dem Consent.', 'macs-cookie-banner' ); ?>
			</p></div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::NONCE_ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION, 'mcb_codes_nonce' ); ?>

				<div data-lscc-codes-list>
					<?php
					$i = 0;
					foreach ( $codes as $entry ) {
						self::render_row( $entry, (string) $i, $can_raw );
						$i++;
					}
					?>
				</div>

				<p>
					<button type="button" class="button" data-lscc-code-add><?php echo esc_html__( '+ Snippet hinzufügen', 'macs-cookie-banner' ); ?></button>
				</p>

				<?php submit_button( esc_html__( 'Snippets speichern', 'macs-cookie-banner' ) ); ?>

				<hr />
				<h2><?php echo esc_html__( 'Import / Export (für Rollout über mehrere Websites)', 'macs-cookie-banner' ); ?></h2>
				<p class="description"><?php echo esc_html__( 'Export liefert ein versioniertes JSON-Envelope (aktuell die Consent-Codes; später erweiterbar auf die gesamte LSCC-Konfiguration). Beim Import ersetzt das Envelope die aktuellen Snippets.', 'macs-cookie-banner' ); ?></p>
				<p>
					<textarea name="mcb_import_json" rows="4" class="large-text code" placeholder="<?php echo esc_attr__( 'JSON-Envelope hier einfügen, um zu importieren …', 'macs-cookie-banner' ); ?>"></textarea>
				</p>
			</form>

			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mcb_export_consent_codes' ), 'mcb_export_consent_codes' ) ); ?>"><?php echo esc_html__( 'Export herunterladen (JSON)', 'macs-cookie-banner' ); ?></a>
			</p>

			<?php self::render_template_row( $can_raw ); ?>
		</div>
		<?php
	}

	/**
	 * Render a single snippet row.
	 *
	 * @param array  $entry   Entry data.
	 * @param string $index   Field index token.
	 * @param bool   $can_raw Whether the user may edit raw code.
	 * @return void
	 */
	private static function render_row( $entry, $index, $can_raw ) {
		$base    = 'mcb_codes[' . $index . ']';
		$vendors = self::vendor_labels();
		$vendor  = isset( $entry['vendor'] ) ? $entry['vendor'] : '';
		$badge   = ( '' !== $vendor && isset( $vendors[ $vendor ] ) ) ? $vendors[ $vendor ] : '';
		?>
		<div class="postbox" data-lscc-code-row style="padding:12px;margin-top:12px;">
			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo esc_attr( $entry['id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[source]" value="<?php echo esc_attr( isset( $entry['source'] ) ? $entry['source'] : 'manual' ); ?>" />

			<p>
				<label><strong><?php echo esc_html__( 'Name', 'macs-cookie-banner' ); ?></strong>
					<input type="text" class="regular-text" name="<?php echo esc_attr( $base ); ?>[label]" value="<?php echo esc_attr( $entry['label'] ); ?>" />
				</label>
				<?php if ( '' !== $badge ) : ?>
					<span class="dashicons dashicons-yes" style="color:#46b450;"></span>
					<em><?php echo esc_html( sprintf( /* translators: %s: vendor name. */ __( 'Erkannt: %s', 'macs-cookie-banner' ), $badge ) ); ?></em>
				<?php endif; ?>
			</p>

			<p>
				<label><strong><?php echo esc_html__( 'Kategorie', 'macs-cookie-banner' ); ?></strong>
					<select name="<?php echo esc_attr( $base ); ?>[category]">
						<?php foreach ( self::category_labels() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $entry['category'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				&nbsp;
				<label><strong><?php echo esc_html__( 'Position', 'macs-cookie-banner' ); ?></strong>
					<select name="<?php echo esc_attr( $base ); ?>[location]">
						<?php foreach ( self::location_labels() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $entry['location'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				&nbsp;
				<label><input type="checkbox" name="<?php echo esc_attr( $base ); ?>[enabled]" value="1" <?php checked( ! empty( $entry['enabled'] ) ); ?> /> <?php echo esc_html__( 'Aktiv', 'macs-cookie-banner' ); ?></label>
			</p>

			<p>
				<label><strong><?php echo esc_html__( 'Code', 'macs-cookie-banner' ); ?></strong><br />
					<textarea name="<?php echo esc_attr( $base ); ?>[code]" rows="6" class="large-text code" <?php disabled( ! $can_raw ); ?>><?php echo esc_textarea( $entry['code'] ); ?></textarea>
				</label>
			</p>

			<p>
				<button type="button" class="button" data-lscc-code-up><?php echo esc_html__( '↑', 'macs-cookie-banner' ); ?></button>
				<button type="button" class="button" data-lscc-code-down><?php echo esc_html__( '↓', 'macs-cookie-banner' ); ?></button>
				<button type="button" class="button button-link-delete" data-lscc-code-remove><?php echo esc_html__( 'Löschen', 'macs-cookie-banner' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the hidden template row used by the repeater JS.
	 *
	 * @param bool $can_raw Whether the user may edit raw code.
	 * @return void
	 */
	private static function render_template_row( $can_raw ) {
		$empty = array(
			'id'       => '',
			'label'    => '',
			'vendor'   => '',
			'source'   => 'manual',
			'category' => 'statistics',
			'location' => 'head',
			'enabled'  => true,
			'order'    => 0,
			'code'     => '',
		);
		echo '<template data-lscc-code-template>';
		self::render_row( $empty, '__INDEX__', $can_raw );
		echo '</template>';
	}

	/**
	 * Human-readable category labels.
	 *
	 * @return array
	 */
	private static function category_labels() {
		return array(
			'necessary'      => __( 'Notwendig', 'macs-cookie-banner' ),
			'statistics'     => __( 'Statistik', 'macs-cookie-banner' ),
			'marketing'      => __( 'Marketing', 'macs-cookie-banner' ),
			'external_media' => __( 'Externe Medien', 'macs-cookie-banner' ),
		);
	}

	/**
	 * Human-readable location labels.
	 *
	 * @return array
	 */
	private static function location_labels() {
		return array(
			'head'      => __( 'Head', 'macs-cookie-banner' ),
			'body_open' => __( 'Body-Anfang', 'macs-cookie-banner' ),
			'footer'    => __( 'Footer', 'macs-cookie-banner' ),
		);
	}

	/**
	 * Save the posted snippets (or an imported envelope).
	 *
	 * @return void
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Einstellungen zu speichern.', 'macs-cookie-banner' ) );
		}

		$nonce = isset( $_POST['mcb_codes_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mcb_codes_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Ungültige Sicherheitsprüfung.', 'macs-cookie-banner' ) );
		}

		$can_raw  = current_user_can( 'unfiltered_html' );
		$rejected = 0;

		// Import branch takes precedence when JSON is provided.
		$import_raw = isset( $_POST['mcb_import_json'] ) ? trim( (string) wp_unslash( $_POST['mcb_import_json'] ) ) : '';
		if ( '' !== $import_raw ) {
			$imported = self::parse_import( $import_raw );
			if ( null === $imported ) {
				self::redirect( 'import_error', 0 );
			}
			$entries = self::build_entries( $imported, $can_raw, $rejected, 'import' );
			update_option( self::OPTION_NAME, $entries );
			self::redirect( 'imported', $rejected );
		}

		$posted  = isset( $_POST['mcb_codes'] ) && is_array( $_POST['mcb_codes'] ) ? wp_unslash( $_POST['mcb_codes'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		$entries = self::build_entries( $posted, $can_raw, $rejected, 'manual' );

		update_option( self::OPTION_NAME, $entries );
		self::redirect( 'saved', $rejected );
	}

	/**
	 * Build sanitized entries from a raw array (posted or imported).
	 *
	 * @param array  $rows          Raw rows.
	 * @param bool   $can_raw       Whether raw code may be stored.
	 * @param int    $rejected      Counter (by reference) for dropped code.
	 * @param string $default_source Default provenance.
	 * @return array
	 */
	private static function build_entries( $rows, $can_raw, &$rejected, $default_source ) {
		$entries = array();
		$order   = 0;

		foreach ( $rows as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$code  = isset( $raw['code'] ) ? (string) $raw['code'] : '';
			$label = isset( $raw['label'] ) ? sanitize_text_field( $raw['label'] ) : '';

			if ( ! $can_raw && '' !== trim( $code ) ) {
				$code = '';
				$rejected++;
			}

			// Skip fully empty rows.
			if ( '' === trim( $label ) && '' === trim( $code ) ) {
				continue;
			}

			$source = isset( $raw['source'] ) && '' !== $raw['source'] ? sanitize_key( $raw['source'] ) : $default_source;

			$has_id   = isset( $raw['id'] ) && '' !== $raw['id'];
			$vendor   = self::detect_vendor( $code );
			$category = self::sanitize_enum( isset( $raw['category'] ) ? $raw['category'] : '', self::categories(), 'statistics' );

			// Auto-suggest the category only for *newly added* rows (no stored id) of a
			// recognized vendor, and only when the form still carries the generic
			// 'statistics' default. Existing snippets are never re-categorized.
			if ( ! $has_id && 'statistics' === $category && '' !== $vendor && 'custom' !== $vendor ) {
				$category = self::vendor_default_category( $vendor );
			}

			$entries[] = array(
				'id'       => $has_id ? sanitize_key( $raw['id'] ) : self::new_id(),
				'label'    => $label,
				'vendor'   => $vendor,
				'source'   => $source,
				'category' => $category,
				'location' => self::sanitize_enum( isset( $raw['location'] ) ? $raw['location'] : '', self::locations(), 'head' ),
				'enabled'  => ! empty( $raw['enabled'] ),
				'order'    => $order,
				'code'     => $code,
			);

			$order += 10;
		}

		return $entries;
	}

	/**
	 * Parse an import string into a list of raw entries.
	 *
	 * Accepts the LSCC envelope ({lscc_export_version, data:{consent_codes:[]}})
	 * or a bare array of entries.
	 *
	 * @param string $json Raw JSON.
	 * @return array|null List of raw entries, or null on parse error.
	 */
	private static function parse_import( $json ) {
		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return null;
		}

		if ( isset( $decoded['data'] ) && is_array( $decoded['data'] ) && isset( $decoded['data']['consent_codes'] ) && is_array( $decoded['data']['consent_codes'] ) ) {
			return $decoded['data']['consent_codes'];
		}

		// Bare list fallback.
		if ( isset( $decoded[0] ) && is_array( $decoded[0] ) ) {
			return $decoded;
		}

		return null;
	}

	/**
	 * Export the configuration as a versioned JSON envelope (download).
	 *
	 * @return void
	 */
	public static function export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Aktion auszuführen.', 'macs-cookie-banner' ) );
		}

		check_admin_referer( 'mcb_export_consent_codes' );

		$envelope = array(
			'lscc_export_version' => self::EXPORT_VERSION,
			'plugin_version'      => MCB_VERSION,
			'type'                => 'lscc-config',
			// Erweiterbar: hier können später weitere Konfigurationsteile ergänzt
			// werden (z. B. 'options'), ohne das Envelope-Format zu brechen.
			'data'                => array(
				'consent_codes' => self::get_codes(),
			),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="lscc-config-export.json"' );
		echo wp_json_encode( $envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Redirect back to the manager page with a notice.
	 *
	 * @param string $notice   Notice key.
	 * @param int    $rejected Number of rejected snippets.
	 * @return void
	 */
	private static function redirect( $notice, $rejected ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => self::PAGE_SLUG,
					'mcb_notice'   => $notice,
					'mcb_rejected' => (int) $rejected,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
