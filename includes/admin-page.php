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
		add_action( 'admin_post_mcb_restore_recommended', array( __CLASS__, 'restore_recommended' ) );
		add_action( 'admin_post_mcb_import_avada_colors', array( __CLASS__, 'import_avada_colors' ) );

		// Avada Auto-Sync (ADR-32 / forced decision ADR-33).
		add_action( 'admin_post_mcb_avada_sync_decision', array( __CLASS__, 'save_avada_sync_decision' ) );
		add_action( 'admin_post_mcb_save_avada_sync', array( __CLASS__, 'save_avada_sync' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_force_avada_decision' ), 1 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_auto_sync' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_render_sync_decision_notice' ) );
	}

	/**
	 * Option name: Avada auto-sync toggle ('on' | 'off'). ADR-32.
	 */
	const OPTION_AVADA_AUTOSYNC = 'mcb_avada_autosync';

	/**
	 * Option name: whether the user has made the initial auto-sync decision. ADR-32.
	 */
	const OPTION_AVADA_SYNC_DECIDED = 'mcb_avada_sync_decided';

	/**
	 * Option name: a decision must be forced on the next admin load (set by the
	 * activation hook and by upgrade detection). ADR-33.
	 */
	const OPTION_AVADA_DECISION_PENDING = 'mcb_avada_decision_pending';

	/**
	 * Option name: last plugin version seen in admin (upgrade detection). ADR-33.
	 */
	const OPTION_SEEN_VERSION = 'mcb_seen_version';

	/**
	 * Whether Avada auto-sync is enabled.
	 *
	 * @return bool
	 */
	public static function is_avada_autosync_enabled() {
		return 'on' === get_option( self::OPTION_AVADA_AUTOSYNC, 'off' );
	}

	/**
	 * Whether the user has already decided about auto-sync (so we stop prompting).
	 *
	 * @return bool
	 */
	public static function is_avada_sync_decided() {
		return '1' === (string) get_option( self::OPTION_AVADA_SYNC_DECIDED, '' );
	}

	/**
	 * Force the Avada Auto-Sync decision right after activation or update (ADR-33).
	 *
	 * Real trigger, not a passive notice: the activation hook and an upgrade
	 * detection (version stamp) set a pending flag; on the next normal admin load
	 * this redirects the operator once to the settings page, where a persistent,
	 * non-dismissible decision panel is shown until they choose. After the choice
	 * the decision is stored permanently and never asked again.
	 *
	 * Guards: admins only, never on AJAX / admin-post / POST / bulk-activation, and
	 * never on the target page itself (no redirect loop). The pending flag is
	 * cleared on redirect so the operator is shown the screen but never trapped
	 * (e.g. they can still reach the Plugins page to deactivate); the persistent
	 * notice keeps the prompt reachable until decided.
	 *
	 * @return void
	 */
	public static function maybe_force_avada_decision() {
		if ( ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
			return;
		}

		// Upgrade / first-load detection: stamp version, flag pending if undecided.
		$seen = (string) get_option( self::OPTION_SEEN_VERSION, '' );
		if ( MCB_VERSION !== $seen ) {
			if ( ! self::is_avada_sync_decided() ) {
				update_option( self::OPTION_AVADA_DECISION_PENDING, '1' );
			}
			update_option( self::OPTION_SEEN_VERSION, MCB_VERSION );
		}

		if ( self::is_avada_sync_decided() ) {
			return;
		}
		if ( '1' !== (string) get_option( self::OPTION_AVADA_DECISION_PENDING, '' ) ) {
			return;
		}
		if ( ! Macs_Cookie_Banner_Avada_Colors::is_active() ) {
			return;
		}

		// Never break form posts or bulk plugin activation, and only on GET.
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( 'GET' !== $method || isset( $_GET['activate-multi'] ) ) {
			return;
		}
		$GLOBALS_pagenow = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';
		if ( 'admin-post.php' === $GLOBALS_pagenow || 'admin-ajax.php' === $GLOBALS_pagenow ) {
			return;
		}

		// Already on the settings page? Let the persistent panel handle it.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'macs-cookie-banner' === $page ) {
			return;
		}

		// Show once via redirect; clear pending so the operator is never trapped.
		update_option( self::OPTION_AVADA_DECISION_PENDING, '0' );
		wp_safe_redirect( admin_url( 'admin.php?page=macs-cookie-banner' ) );
		exit;
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

		// Single save path: the Avada Auto-Sync toggle is part of this settings
		// save. Only persisted when Avada is active (the checkbox is rendered then),
		// so non-Avada sites stay untouched. The Auto-Sync logic itself is unchanged.
		if ( Macs_Cookie_Banner_Avada_Colors::is_active() ) {
			update_option( self::OPTION_AVADA_AUTOSYNC, isset( $_POST['mcb_avada_autosync'] ) ? 'on' : 'off' );
			update_option( self::OPTION_AVADA_SYNC_DECIDED, '1' );
		}

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
	 * Restore the recommended privacy/blocking defaults (ADR-36).
	 *
	 * Operator-driven, explicit click only. Overrides ONLY the privacy/blocking +
	 * consent-lifetime keys with the recommended safe values; every other option
	 * (texts, colors, design preset, legal URLs, reopen position/offsets) keeps its
	 * stored value, and separate options (consent-code snippets, Avada auto-sync
	 * decision, visitor consents) are not touched at all.
	 *
	 * @return void
	 */
	public static function restore_recommended() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Aktion auszuführen.', 'macs-cookie-banner' ) );
		}

		$nonce = isset( $_POST['mcb_restore_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mcb_restore_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'mcb_restore_recommended' ) ) {
			wp_die( esc_html__( 'Ungültige Sicherheitsprüfung.', 'macs-cookie-banner' ) );
		}

		$options     = Macs_Cookie_Banner::get_options();
		$recommended = Macs_Cookie_Banner::get_recommended_defaults();

		foreach ( Macs_Cookie_Banner::get_restore_recommended_keys() as $key ) {
			if ( array_key_exists( $key, $recommended ) ) {
				$options[ $key ] = $recommended[ $key ];
			}
		}

		update_option( Macs_Cookie_Banner::OPTION_NAME, Macs_Cookie_Banner::sanitize_options( $options ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'macs-cookie-banner',
					'mcb_restored' => '1',
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

		$result        = 'empty';
		$cache_cleared = false;

		if ( Macs_Cookie_Banner_Avada_Colors::is_active() ) {
			// Bind the import STRICTLY to the Avada Primary Color (ADR-30):
			// no brand-key chain, no palette/awb-colorN matching. The banner
			// adopts ONLY the currently active primary_color.
			$raw_primary = Macs_Cookie_Banner_Avada_Colors::read_raw( 'primary_color' );
			$brand       = Macs_Cookie_Banner_Avada_Colors::resolve_primary( $raw_primary );

			// Fallback ONLY when primary_color is itself a var(--awb-colorX) the
			// server cannot resolve: use the value the browser resolved for that
			// same primary variable (submitted as a hidden field). Guarded by the
			// manage_options + nonce checks above; only a valid hex is ever used.
			// A direct-hex primary never triggers this — no accent/link/gradient.
			$client_raw = isset( $_POST['mcb_avada_client_color'] ) ? wp_unslash( $_POST['mcb_avada_client_color'] ) : '';
			$client_hex = '' !== $client_raw ? (string) sanitize_hex_color( $client_raw ) : '';

			if ( '' === $brand && '' !== $client_hex ) {
				$brand = $client_hex;
			}

			$mapped = Macs_Cookie_Banner_Avada_Colors::map_to_banner( $brand );

			if ( ! empty( $mapped ) ) {
				$current = Macs_Cookie_Banner::get_options();
				$merged  = Macs_Cookie_Banner::sanitize_options( array_merge( $current, $mapped ) );

				update_option( Macs_Cookie_Banner::OPTION_NAME, $merged );

				// Avada/Fusion caches the generated inline CSS (the previous
				// --lscc-primary value), so flush it via Avada's own API right
				// after the new color is stored (ADR-29).
				$cache_cleared = Macs_Cookie_Banner_Avada_Colors::reset_caches();

				$result = 'imported';
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'macs-cookie-banner',
					'mcb_avada' => $result,
					'mcb_cache' => $cache_cleared ? '1' : '0',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Apply the current Avada Primary Color to the banner (ADR-32 auto-sync core).
	 *
	 * Server-side only and read-only on the Avada side. Reuses the v0.5.10/0.5.11
	 * primary binding: resolve_primary() (direct hex / rgb-rgba), map_to_banner(),
	 * reset_caches() (ADR-29). The existing manual import (import_avada_colors())
	 * is left untouched. A var(--awb-colorX) primary that only resolves in the
	 * browser cannot be synced silently here → 'unresolved' (the manual button,
	 * which has the client-side fallback, still covers it).
	 *
	 * @return string One of: 'synced', 'nochange', 'unresolved', 'inactive'.
	 */
	public static function run_avada_sync() {
		if ( ! Macs_Cookie_Banner_Avada_Colors::is_active() ) {
			return 'inactive';
		}

		$raw = Macs_Cookie_Banner_Avada_Colors::read_raw( 'primary_color' );
		$hex = Macs_Cookie_Banner_Avada_Colors::resolve_primary( $raw );
		if ( '' === $hex ) {
			return 'unresolved';
		}

		$current = Macs_Cookie_Banner::get_options();
		if ( isset( $current['primary_button_color'] ) && strtolower( (string) $current['primary_button_color'] ) === strtolower( $hex ) ) {
			return 'nochange';
		}

		$mapped = Macs_Cookie_Banner_Avada_Colors::map_to_banner( $hex );
		if ( empty( $mapped ) ) {
			return 'unresolved';
		}

		$merged = Macs_Cookie_Banner::sanitize_options( array_merge( $current, $mapped ) );
		update_option( Macs_Cookie_Banner::OPTION_NAME, $merged );

		// Flush the Avada/Fusion cache so the new color shows immediately (ADR-29).
		Macs_Cookie_Banner_Avada_Colors::reset_caches();

		return 'synced';
	}

	/**
	 * Auto-sync on admin load when enabled (ADR-32).
	 *
	 * Only runs when auto-sync is ON, so manual banner colors are NEVER changed
	 * unless the user opted in. Silent: it only writes when the Avada Primary
	 * Color actually differs from the stored banner color.
	 *
	 * @return void
	 */
	public static function maybe_auto_sync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! self::is_avada_autosync_enabled() ) {
			return;
		}

		self::run_avada_sync();
	}

	/**
	 * Handle the initial auto-sync decision (Ja/Nein) from the detection notice.
	 *
	 * @return void
	 */
	public static function save_avada_sync_decision() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Aktion auszuführen.', 'macs-cookie-banner' ) );
		}

		$nonce = isset( $_POST['mcb_avada_decision_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mcb_avada_decision_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mcb_avada_sync_decision' ) ) {
			wp_die( esc_html__( 'Ungültige Sicherheitsprüfung.', 'macs-cookie-banner' ) );
		}

		$choice = isset( $_POST['mcb_sync_choice'] ) ? sanitize_text_field( wp_unslash( $_POST['mcb_sync_choice'] ) ) : 'off';
		$choice = ( 'on' === $choice ) ? 'on' : 'off';

		update_option( self::OPTION_AVADA_AUTOSYNC, $choice );
		update_option( self::OPTION_AVADA_SYNC_DECIDED, '1' );
		update_option( self::OPTION_AVADA_DECISION_PENDING, '0' );

		$sync = ( 'on' === $choice ) ? self::run_avada_sync() : 'off';

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'macs-cookie-banner',
					'mcb_sync' => $sync,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Save the auto-sync checkbox from the Avada integration section.
	 *
	 * @return void
	 */
	public static function save_avada_sync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie haben keine Berechtigung, diese Aktion auszuführen.', 'macs-cookie-banner' ) );
		}

		$nonce = isset( $_POST['mcb_avada_sync_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mcb_avada_sync_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mcb_save_avada_sync' ) ) {
			wp_die( esc_html__( 'Ungültige Sicherheitsprüfung.', 'macs-cookie-banner' ) );
		}

		$choice = isset( $_POST['mcb_avada_autosync'] ) ? 'on' : 'off';

		update_option( self::OPTION_AVADA_AUTOSYNC, $choice );
		update_option( self::OPTION_AVADA_SYNC_DECIDED, '1' );
		update_option( self::OPTION_AVADA_DECISION_PENDING, '0' );

		$sync = ( 'on' === $choice ) ? self::run_avada_sync() : 'off';

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'macs-cookie-banner',
					'mcb_sync' => $sync,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Show the one-time "Avada detected — enable auto-sync?" decision notice.
	 *
	 * Shown to admins when Avada is active and the decision has never been made
	 * (first detection or after an update without a prior decision). ADR-32.
	 *
	 * @return void
	 */
	public static function maybe_render_sync_decision_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! Macs_Cookie_Banner_Avada_Colors::is_active() || self::is_avada_sync_decided() ) {
			return;
		}
		?>
		<div class="notice notice-info">
			<p><strong><?php echo esc_html__( 'Avada wurde erkannt.', 'macs-cookie-banner' ); ?></strong></p>
			<p><?php echo esc_html__( 'Soll das Cookie-Banner künftig automatisch die Avada Primary Color übernehmen?', 'macs-cookie-banner' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:.5em;flex-wrap:wrap;margin:.5em 0;">
				<input type="hidden" name="action" value="mcb_avada_sync_decision">
				<?php wp_nonce_field( 'mcb_avada_sync_decision', 'mcb_avada_decision_nonce' ); ?>
				<button type="submit" name="mcb_sync_choice" value="on" class="button button-primary"><?php echo esc_html__( 'Ja, automatisch synchronisieren', 'macs-cookie-banner' ); ?></button>
				<button type="submit" name="mcb_sync_choice" value="off" class="button"><?php echo esc_html__( 'Nein, Banner-Farben manuell verwalten', 'macs-cookie-banner' ); ?></button>
			</form>
		</div>
		<?php
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

			<?php if ( isset( $_GET['mcb_restored'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['mcb_restored'] ) ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Empfohlene Datenschutzeinstellungen wiederhergestellt. Texte, Farben, URLs, Tracking-Snippets und die Avada-Sync-Entscheidung blieben unverändert.', 'macs-cookie-banner' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="notice notice-info inline" style="margin:12px 0;padding:10px 12px;">
				<p style="margin:0 0 .5em;"><strong><?php echo esc_html__( 'Sichere Standardwerte', 'macs-cookie-banner' ); ?></strong> &mdash;
					<?php echo esc_html__( 'Setzt die empfohlenen Datenschutz- und Blockier-Einstellungen (Avada-YouTube-/Code-Block-Maps-Blockierung EIN, Facebook-/Instagram-Social-Embeds EIN, Remote-Thumbnails AUS, Rechtslinks EIN, Consent-Gültigkeit 180 Tage). Texte, Farben, Design, URLs, Tracking-Snippets, Reopen-Position und die Avada-Sync-Entscheidung bleiben unverändert.', 'macs-cookie-banner' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
					<input type="hidden" name="action" value="mcb_restore_recommended">
					<?php wp_nonce_field( 'mcb_restore_recommended', 'mcb_restore_nonce' ); ?>
					<button type="submit" class="button button-secondary" onclick="return confirm('<?php echo esc_js( __( 'Alle Datenschutz- und Blockier-Einstellungen werden auf die empfohlenen sicheren Standardwerte zurückgesetzt. Texte, Farben, URLs und Tracking-Snippets bleiben unverändert. Fortfahren?', 'macs-cookie-banner' ) ); ?>');">
						<?php echo esc_html__( 'Empfohlene Datenschutzeinstellungen wiederherstellen', 'macs-cookie-banner' ); ?>
					</button>
				</form>
			</div>

			<?php if ( isset( $_GET['mcb_avada'] ) ) : ?>
				<?php $mcb_avada_result = sanitize_text_field( wp_unslash( $_GET['mcb_avada'] ) ); ?>
				<?php if ( 'imported' === $mcb_avada_result ) : ?>
					<?php $mcb_cache_cleared = isset( $_GET['mcb_cache'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['mcb_cache'] ) ); ?>
					<div class="notice notice-success is-dismissible">
						<?php if ( $mcb_cache_cleared ) : ?>
							<p><?php echo esc_html__( 'Avada-Farben übernommen. Fusion/Avada Cache wurde automatisch geleert.', 'macs-cookie-banner' ); ?></p>
						<?php else : ?>
							<p><?php echo esc_html__( 'Avada-Farben übernommen. Bei Bedarf manuell anpassen und speichern.', 'macs-cookie-banner' ); ?></p>
						<?php endif; ?>
					</div>
				<?php elseif ( 'empty' === $mcb_avada_result ) : ?>
					<div class="notice notice-warning is-dismissible">
						<p><?php echo esc_html__( 'Keine Avada-Markenfarbe gefunden. Farben unverändert.', 'macs-cookie-banner' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( isset( $_GET['mcb_sync'] ) ) : ?>
				<?php $mcb_sync_result = sanitize_text_field( wp_unslash( $_GET['mcb_sync'] ) ); ?>
				<?php if ( 'synced' === $mcb_sync_result ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo esc_html__( 'Auto-Sync gespeichert. Banner-Farben mit der Avada Primary Color synchronisiert (Cache geleert).', 'macs-cookie-banner' ); ?></p>
					</div>
				<?php elseif ( 'nochange' === $mcb_sync_result ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo esc_html__( 'Auto-Sync gespeichert. Banner-Farben sind bereits aktuell.', 'macs-cookie-banner' ); ?></p>
					</div>
				<?php elseif ( 'off' === $mcb_sync_result ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo esc_html__( 'Einstellung gespeichert. Banner-Farben werden manuell verwaltet.', 'macs-cookie-banner' ); ?></p>
					</div>
				<?php elseif ( 'unresolved' === $mcb_sync_result ) : ?>
					<div class="notice notice-warning is-dismissible">
						<p><?php echo esc_html__( 'Auto-Sync aktiviert, aber die Avada Primary Color konnte serverseitig nicht aufgelöst werden (Global Color). Bitte einmal „Jetzt synchronisieren" klicken — danach greift der Auto-Sync.', 'macs-cookie-banner' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( Macs_Cookie_Banner_Avada_Colors::is_active() ) : ?>
				<?php $mcb_avada_vars = Macs_Cookie_Banner_Avada_Colors::get_brand_css_vars(); ?>
				<h2><?php echo esc_html__( 'Avada-Integration', 'macs-cookie-banner' ); ?></h2>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:1em;" data-mcb-avada-form>
					<input type="hidden" name="action" value="mcb_import_avada_colors">
					<input type="hidden" name="mcb_avada_client_color" value="" data-mcb-avada-client>
					<?php wp_nonce_field( 'mcb_import_avada_colors', 'mcb_avada_nonce' ); ?>
					<p class="description" style="margin:0 0 .5em;"><?php echo esc_html__( 'Übernimmt die aktuelle Avada Primary Color sofort in Primärbutton und Rahmen (Button-Text wird automatisch lesbar kontrastiert). Bestehende Farben bleiben, bis Sie hier klicken.', 'macs-cookie-banner' ); ?></p>
					<?php submit_button( esc_html__( 'Jetzt synchronisieren', 'macs-cookie-banner' ), 'secondary', 'mcb_import_avada', false ); ?>
				</form>
				<?php if ( ! empty( $mcb_avada_vars ) ) : ?>
				<script>
				( function () {
					var VARS = <?php echo wp_json_encode( array_values( $mcb_avada_vars ) ); ?>;
					var HOME = <?php echo wp_json_encode( esc_url_raw( home_url( '/' ) ) ); ?>;
					var field = document.querySelector( '[data-mcb-avada-client]' );
					if ( ! field || ! VARS.length ) { return; }

					function clamp( n ) { n = parseInt( n, 10 ); return ( isNaN( n ) ? 0 : Math.max( 0, Math.min( 255, n ) ) ); }
					function toHex( value ) {
						value = ( value || '' ).trim();
						if ( ! value ) { return ''; }
						if ( /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test( value ) ) { return value; }
						var m = value.match( /rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i );
						if ( m ) {
							var h = function ( x ) { x = clamp( x ).toString( 16 ); return x.length === 1 ? '0' + x : x; };
							return '#' + h( m[1] ) + h( m[2] ) + h( m[3] );
						}
						return '';
					}
					function resolveFrom( doc ) {
						if ( ! doc || ! doc.documentElement ) { return ''; }
						var cs = ( doc.defaultView || window ).getComputedStyle( doc.documentElement );
						for ( var i = 0; i < VARS.length; i++ ) {
							var hex = toHex( cs.getPropertyValue( VARS[ i ] ) );
							if ( hex ) { return hex; }
						}
						return '';
					}

					// 1) Resolve against the current admin document.
					var hex = resolveFrom( document );
					if ( hex ) { field.value = hex; return; }

					// 2) Fallback: a hidden, same-origin iframe of the front-end,
					// where Avada always emits the global color custom properties.
					try {
						var ifr = document.createElement( 'iframe' );
						ifr.setAttribute( 'aria-hidden', 'true' );
						ifr.style.cssText = 'position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;border:0;opacity:0;';
						ifr.addEventListener( 'load', function () {
							try {
								var h = resolveFrom( ifr.contentDocument );
								if ( h ) { field.value = h; }
							} catch ( e ) {}
							window.setTimeout( function () { if ( ifr.parentNode ) { ifr.parentNode.removeChild( ifr ); } }, 200 );
						} );
						ifr.src = HOME;
						document.body.appendChild( ifr );
					} catch ( e ) {}
				} )();
				</script>
				<?php endif; ?>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mcb_save_settings">
				<?php wp_nonce_field( 'mcb_save_settings', 'mcb_settings_nonce' ); ?>

				<?php submit_button( esc_html__( 'Einstellungen speichern', 'macs-cookie-banner' ), 'primary', 'mcb_save_top', false ); ?>

				<?php if ( Macs_Cookie_Banner_Avada_Colors::is_active() ) : ?>
					<h2><?php echo esc_html__( 'Avada-Synchronisierung', 'macs-cookie-banner' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php echo esc_html__( 'Auto-Sync', 'macs-cookie-banner' ); ?></th>
							<td>
								<label style="display:inline-flex;align-items:center;gap:.5em;">
									<input type="checkbox" name="mcb_avada_autosync" value="on" <?php checked( self::is_avada_autosync_enabled() ); ?>>
									<?php echo esc_html__( 'Banner-Farben automatisch mit Avada synchronisieren', 'macs-cookie-banner' ); ?>
								</label>
								<p class="description"><?php echo esc_html__( 'Aktiv: Änderungen der Avada Primary Color werden automatisch ins Banner übernommen (inkl. Cache-Reset). Inaktiv: Banner-Farben bleiben vollständig manuell — Updates überschreiben nichts. (Wird mit „Einstellungen speichern" übernommen.)', 'macs-cookie-banner' ); ?></p>
							</td>
						</tr>
					</table>
				<?php endif; ?>

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
										<?php
						self::render_select_field(
							'reopen_position',
							esc_html__( 'Cookie-Einstellungen-Button Position', 'macs-cookie-banner' ),
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
						<tr>
							<td colspan="2">
								<p class="description" style="margin:0;"><?php echo esc_html__( 'Für Websites mit Chat-Buttons oder WhatsApp-Buttons kann unten links sinnvoll sein.', 'macs-cookie-banner' ); ?></p>
							</td>
						</tr>
						<?php if ( 'hidden' === $options['reopen_position'] ) : ?>
							<tr>
								<td colspan="2">
									<div class="notice notice-warning inline" style="margin:0;">
										<p><?php echo esc_html__( 'Bei verstecktem Cookie-Einstellungs-Button muss ein alternativer Widerrufsweg vorhanden sein (z. B. [simple_cookie_settings] im Footer).', 'macs-cookie-banner' ); ?></p>
									</div>
								</td>
							</tr>
						<?php endif; ?>
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

				<h2><?php echo esc_html__( 'Floating-Button — Feinjustierung', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					
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
					<?php self::render_checkbox_field( 'avada_code_maps_block', esc_html__( 'Google Maps in Avada Code Blocks blockieren', 'macs-cookie-banner' ), $options['avada_code_maps_block'] ); ?>
					<tr>
						<th scope="row"></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Ersetzt Google-Maps-iframes in Avada Code Blocks durch den bestehenden MCB-Platzhalter. Empfohlen, wenn Google Maps per Embed-Code eingefügt wurde.', 'macs-cookie-banner' ); ?></p>
							<p class="description"><?php echo esc_html__( 'Greift nur, wenn ein Code Block ausschliesslich ein einzelnes Google-Maps-Embed-iframe (google.com/maps/embed) enthält. Andere Inhalte, Scripts, weitere iframes, YouTube/Vimeo werden NICHT verändert. Vor Zustimmung zu „Externe Medien" entsteht kein Google-Kontakt; nach Widerruf auf „Nur notwendige" verschwindet die Karte beim Reload wieder. Reversibel (Schalter aus).', 'macs-cookie-banner' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Social-Media-Embeds (Facebook / Instagram)', 'macs-cookie-banner' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_checkbox_field( 'meta_social_block', esc_html__( 'Facebook-/Instagram-Social-Embeds (SDK) vor Consent blockieren', 'macs-cookie-banner' ), $options['meta_social_block'] ); ?>
					<tr>
						<th scope="row"></th>
						<td>
							<p class="description"><?php echo esc_html__( 'Blockiert die Facebook-/Instagram-Embed-SDKs (connect.facebook.net/sdk.js, instagram.com/embed.js, platform.instagram.com/embeds.js) bis zur Zustimmung zu „Externe Medien". XFBML-Widgets (fb-page/-post/-video), Facebook-Livestreams und Instagram-Posts/-Reels rendern erst nach Consent. Kategorie: Externe Medien.', 'macs-cookie-banner' ); ?></p>
							<p class="description"><strong><?php echo esc_html__( 'Meta Pixel (fbevents.js / fbq) ist davon NICHT betroffen', 'macs-cookie-banner' ); ?></strong> <?php echo esc_html__( '— das Pixel wird weiterhin separat über den Consent-Code-Manager (Kategorie Marketing) verwaltet.', 'macs-cookie-banner' ); ?></p>
							<p class="description"><?php echo esc_html__( 'Hinweis: greift bei korrekt registrierten (enqueued) SDK-Scripts. Direkt im Theme hartcodierte SDKs bzw. rohe plugins/*.php-iframes über den Consent-Code-Manager bzw. [lscc_facebook]/[lscc_instagram] führen. Social-Feed-Plugins (Smash Balloon, Spotlight, EmbedSocial, Elfsight) werden nur im Privacy Check gemeldet, nicht automatisch blockiert.', 'macs-cookie-banner' ); ?></p>
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
