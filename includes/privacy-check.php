<?php
/**
 * Lightweight privacy check admin page.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Passive privacy hint checker.
 */
final class Macs_Cookie_Banner_Privacy_Check {
	/**
	 * Render the privacy check page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$scan     = self::resolve_scan_url();
		$scan_url = $scan['url'];
		$fetch    = self::fetch_html( $scan_url );

		$content_scan_results = null;
		if ( isset( $_POST['mcb_run_content_scan'] ) ) {
			check_admin_referer( 'mcb_content_scan', 'mcb_content_scan_nonce' );
			$content_scan_results = self::run_content_scan();
		}

		$headline_results = array(
			array(
				'status'         => 'info',
				'problem'        => sprintf(
					/* translators: %s: Checked URL. */
					__( 'Geprüfte URL: %s', 'macs-cookie-banner' ),
					$scan_url
				),
				'recommendation' => __( 'Nur diese eine URL wird geprüft (Server-Sicht, kein JavaScript). Es findet kein Crawl statt.', 'macs-cookie-banner' ),
			),
		);

		$surface = null;
		if ( ! $fetch['ok'] ) {
			$headline_results[] = array(
				'status'         => 'info',
				'problem'        => $fetch['error_problem'],
				'recommendation' => $fetch['error_reco'],
			);
		} else {
			$headline_results = array_merge( $headline_results, self::detect_services( $fetch['body'] ) );
			$surface          = self::detect_surface( $fetch['body'] );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Privacy Check', 'macs-cookie-banner' ); ?></h1>

			<?php if ( 'host_mismatch' === $scan['notice'] ) : ?>
				<div class="notice notice-warning"><p><?php echo esc_html__( 'Nur URLs dieser Website sind erlaubt. Es wurde die Startseite geprüft.', 'macs-cookie-banner' ); ?></p></div>
			<?php endif; ?>

			<h2><?php echo esc_html__( 'Drittanbieter-Oberfläche', 'macs-cookie-banner' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=macs-cookie-banner-privacy-check' ) ); ?>">
				<?php wp_nonce_field( 'mcb_surface_scan', 'mcb_surface_nonce' ); ?>
				<input type="url" name="mcb_scan_url" class="regular-text" value="<?php echo esc_attr( $scan_url ); ?>" />
				<?php submit_button( esc_html__( 'URL prüfen', 'macs-cookie-banner' ), 'secondary', 'mcb_check_url', false ); ?>
				<p class="description"><?php echo esc_html__( 'Nur URLs dieser Website. Server-Sicht ohne JavaScript — von GTM geladene Tags, klick-/JS-geladene Widgets und Unterseiten werden nicht erfasst.', 'macs-cookie-banner' ); ?></p>
			</form>

			<?php if ( null !== $surface ) : ?>
				<?php self::render_surface_section( $surface ); ?>
			<?php endif; ?>

			<h2><?php echo esc_html__( 'Muster-Schnellprüfung', 'macs-cookie-banner' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Status', 'macs-cookie-banner' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Problem', 'macs-cookie-banner' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Empfehlung', 'macs-cookie-banner' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $headline_results as $result ) : ?>
						<tr>
							<td><?php echo esc_html( self::get_status_label( $result['status'] ) ); ?></td>
							<td><?php echo esc_html( $result['problem'] ); ?></td>
							<td><?php echo esc_html( $result['recommendation'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php self::render_content_scan_section( $content_scan_results ); ?>
		</div>
		<?php
	}

	/**
	 * Resolve the URL to scan: a custom same-host URL (POST) or the homepage.
	 *
	 * @return array { url: string, notice: string }
	 */
	private static function resolve_scan_url() {
		$default = home_url( '/' );

		if ( ! isset( $_POST['mcb_scan_url'] ) ) {
			return array(
				'url'    => $default,
				'notice' => '',
			);
		}

		check_admin_referer( 'mcb_surface_scan', 'mcb_surface_nonce' );

		$candidate = esc_url_raw( trim( (string) wp_unslash( $_POST['mcb_scan_url'] ) ) );

		if ( '' === $candidate ) {
			return array(
				'url'    => $default,
				'notice' => '',
			);
		}

		$home_host = strtolower( (string) wp_parse_url( $default, PHP_URL_HOST ) );
		$cand_host = strtolower( (string) wp_parse_url( $candidate, PHP_URL_HOST ) );

		if ( '' === $cand_host || $cand_host !== $home_host ) {
			// Never let the admin page act as an SSRF proxy for foreign hosts.
			return array(
				'url'    => $default,
				'notice' => 'host_mismatch',
			);
		}

		return array(
			'url'    => $candidate,
			'notice' => '',
		);
	}

	/**
	 * Fetch a URL of this site once (server-side, no JS execution).
	 *
	 * @param string $url URL to fetch.
	 * @return array { ok: bool, body: string, error_problem: string, error_reco: string }
	 */
	private static function fetch_html( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'             => 5,
				'redirection'         => 2,
				'limit_response_size' => 500000,
				'user-agent'          => 'Mac\'s Cookie Banner Privacy Check/' . MCB_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'            => false,
				'body'          => '',
				'error_problem' => __( 'Die URL konnte nicht geprüft werden.', 'macs-cookie-banner' ),
				'error_reco'    => __( 'Bitte prüfen Sie die URL oder starten Sie den Check später erneut.', 'macs-cookie-banner' ),
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );

		if ( 400 <= $status_code || '' === $body ) {
			return array(
				'ok'            => false,
				'body'          => '',
				'error_problem' => __( 'Die URL lieferte keine prüfbare Ausgabe.', 'macs-cookie-banner' ),
				'error_reco'    => __( 'Bitte prüfen Sie die eingebundenen externen Dienste manuell.', 'macs-cookie-banner' ),
			);
		}

		return array(
			'ok'            => true,
			'body'          => $body,
			'error_problem' => '',
			'error_reco'    => '',
		);
	}

	/**
	 * Detect known external services in a small static pattern list.
	 *
	 * @param string $body Rendered homepage output.
	 * @return array
	 */
	private static function detect_services( $body ) {
		$body    = strtolower( $body );
		$results = array();
		$checks  = self::get_checks();

		foreach ( $checks as $check ) {
			foreach ( $check['patterns'] as $pattern ) {
				if ( false === strpos( $body, $pattern ) ) {
					continue;
				}

				$results[] = array(
					'status'         => $check['status'],
					'problem'        => $check['problem'],
					'recommendation' => $check['recommendation'],
				);
				break;
			}
		}

		if ( empty( $results ) ) {
			$results[] = array(
				'status'         => 'info',
				'problem'        => __( 'Keine der einfachen Privacy-Check-Muster gefunden.', 'macs-cookie-banner' ),
				'recommendation' => __( 'Prüfen Sie externe Dienste trotzdem manuell, besonders eingebettete Medien, Tracking und Fonts.', 'macs-cookie-banner' ),
			);
		}

		return $results;
	}

	/**
	 * Return passive checks.
	 *
	 * @return array
	 */
	private static function get_checks() {
		return array(
			array(
				'status'         => 'kritisch',
				'patterns'       => array(
					'fonts.googleapis.com',
					'fonts.gstatic.com',
				),
				'problem'        => __( 'Externe Google Fonts erkannt.', 'macs-cookie-banner' ),
				'recommendation' => __( 'Fonts lokal hosten oder ohne externe Google-Requests einbinden.', 'macs-cookie-banner' ),
			),
			array(
				'status'         => 'kritisch',
				'patterns'       => array(
					'google-analytics.com',
					'googletagmanager.com',
				),
				'problem'        => __( 'Google Analytics oder Google Tag Manager erkannt.', 'macs-cookie-banner' ),
				'recommendation' => __( 'Nur nach Zustimmung laden und korrekt einer Consent-Kategorie zuordnen.', 'macs-cookie-banner' ),
			),
			array(
				'status'         => 'kritisch',
				'patterns'       => array(
					'facebook.net',
					'connect.facebook.net',
				),
				'problem'        => __( 'Facebook-Script oder Meta-Dienst erkannt.', 'macs-cookie-banner' ),
				'recommendation' => __( 'Nur nach Zustimmung laden und Marketing-Scripts bewusst blockieren.', 'macs-cookie-banner' ),
			),
			array(
				'status'         => 'wichtig',
				'patterns'       => array(
					'youtube.com',
					'youtu.be',
				),
				'problem'        => __( 'YouTube-Inhalte oder YouTube-Links erkannt.', 'macs-cookie-banner' ),
				'recommendation' => __( 'Externe Medien erst nach Zustimmung laden oder datenschutzfreundliche Einbettung prüfen.', 'macs-cookie-banner' ),
			),
			array(
				'status'         => 'wichtig',
				'patterns'       => array(
					'vimeo.com',
				),
				'problem'        => __( 'Vimeo-Inhalte oder Vimeo-Links erkannt.', 'macs-cookie-banner' ),
				'recommendation' => __( 'Externe Medien erst nach Zustimmung laden oder datenschutzfreundliche Einbettung prüfen.', 'macs-cookie-banner' ),
			),
		);
	}

	/**
	 * Return translated status label.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	private static function get_status_label( $status ) {
		$labels = array(
			'kritisch' => __( 'Kritisch', 'macs-cookie-banner' ),
			'wichtig'  => __( 'Wichtig', 'macs-cookie-banner' ),
			'info'     => __( 'Info', 'macs-cookie-banner' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['info'];
	}

	/* --------------------------------------------------------------------- */
	/* Third-party surface scan (ab v0.3.1)                                    */
	/* --------------------------------------------------------------------- */

	/**
	 * Build the third-party surface report from fetched HTML.
	 *
	 * @param string $body Fetched HTML.
	 * @return array List of per-service rows.
	 */
	private static function detect_surface( $body ) {
		$scripts = self::classify_scripts( $body );
		$embeds  = self::classify_embeds( $body );
		$fonts   = self::detect_fonts( $body );
		$managed = self::registered_vendors();
		$rows    = array();

		foreach ( self::get_surface_services() as $svc ) {
			$key = $svc['key'];

			if ( 'font' === $svc['kind'] ) {
				$rows[] = array(
					'label'              => $svc['label'],
					'status'             => $fonts ? 'fonts_found' : 'nicht_gefunden',
					'gated'              => 0,
					'ungated'            => 0,
					'managed'            => false,
					'managed_applicable' => false,
					'recommendation'     => $fonts
						? __( 'Empfehlung: lokal hosten. Consent ersetzt kein Local Hosting.', 'macs-cookie-banner' )
						: __( 'Keine externen Google Fonts auf dieser URL gefunden.', 'macs-cookie-banner' ),
					'note'               => '',
				);
				continue;
			}

			$counts = ( 'embed' === $svc['kind'] )
				? ( isset( $embeds[ $key ] ) ? $embeds[ $key ] : array( 'gated' => 0, 'ungated' => 0 ) )
				: ( isset( $scripts[ $key ] ) ? $scripts[ $key ] : array( 'gated' => 0, 'ungated' => 0 ) );

			$found = ( $counts['gated'] + $counts['ungated'] ) > 0;

			$rows[] = array(
				'label'              => $svc['label'],
				'status'             => self::surface_status( $svc, $found, $counts ),
				'gated'              => (int) $counts['gated'],
				'ungated'            => (int) $counts['ungated'],
				'managed'            => in_array( $key, $managed, true ),
				'managed_applicable' => ( 'script' === $svc['kind'] ),
				'recommendation'     => $svc['recommend'],
				'note'               => $svc['note'],
			);
		}

		return $rows;
	}

	/**
	 * Determine the surface status for a service.
	 *
	 * @param array $svc    Service definition.
	 * @param bool  $found  Whether any instance was found.
	 * @param array $counts gated/ungated counts.
	 * @return string
	 */
	private static function surface_status( $svc, $found, $counts ) {
		if ( $found && ! empty( $svc['opaque'] ) ) {
			return 'nicht_pruefbar';
		}
		if ( $found ) {
			if ( 0 === (int) $counts['ungated'] ) {
				return 'verwaltet';
			}
			if ( 0 === (int) $counts['gated'] ) {
				return 'ungegatet';
			}
			return 'teilweise';
		}

		return empty( $svc['server_visible'] ) ? 'nicht_pruefbar' : 'nicht_gefunden';
	}

	/**
	 * Classify every <script> by vendor and gated/ungated state.
	 *
	 * @param string $body HTML.
	 * @return array vendor => { gated:int, ungated:int }
	 */
	private static function classify_scripts( $body ) {
		$counts = array();

		if ( preg_match_all( '#<script\b([^>]*)>(.*?)</script>#is', $body, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $block ) {
				$vendor = Macs_Cookie_Banner_Codes::match_vendor( $block[1] . ' ' . $block[2] );

				if ( '' === $vendor || 'custom' === $vendor ) {
					continue;
				}

				$gated = self::tag_is_gated( $block[1] );

				if ( ! isset( $counts[ $vendor ] ) ) {
					$counts[ $vendor ] = array(
						'gated'   => 0,
						'ungated' => 0,
					);
				}

				$counts[ $vendor ][ $gated ? 'gated' : 'ungated' ]++;
			}
		}

		return $counts;
	}

	/**
	 * Is a <script> opening tag gated by the LSCC script blockade?
	 *
	 * @param string $attrs Tag attributes.
	 * @return bool
	 */
	private static function tag_is_gated( $attrs ) {
		return ( false !== stripos( $attrs, 'text/plain' ) ) && ( false !== stripos( $attrs, 'data-cookie-category' ) );
	}

	/**
	 * Classify YouTube/Vimeo/Maps embeds: raw iframe = ungated, LSCC placeholder = gated.
	 *
	 * @param string $body HTML.
	 * @return array
	 */
	private static function classify_embeds( $body ) {
		$counts = array(
			'youtube' => array( 'gated' => 0, 'ungated' => 0 ),
			'vimeo'   => array( 'gated' => 0, 'ungated' => 0 ),
			'maps'    => array( 'gated' => 0, 'ungated' => 0 ),
		);

		if ( preg_match_all( '#<iframe\b[^>]*\bsrc=("|\')([^"\']*)\1#i', $body, $iframes, PREG_SET_ORDER ) ) {
			foreach ( $iframes as $iframe ) {
				$src = strtolower( $iframe[2] );

				if ( false !== strpos( $src, 'youtube' ) || false !== strpos( $src, 'youtu.be' ) ) {
					$counts['youtube']['ungated']++;
				} elseif ( false !== strpos( $src, 'vimeo' ) ) {
					$counts['vimeo']['ungated']++;
				} elseif ( false !== strpos( $src, 'google.com/maps' ) || false !== strpos( $src, 'maps.google' ) ) {
					$counts['maps']['ungated']++;
				}
			}
		}

		if ( preg_match_all( '#data-lscc-service=("|\')([^"\']*)\1#i', $body, $placeholders, PREG_SET_ORDER ) ) {
			foreach ( $placeholders as $placeholder ) {
				$service = strtolower( $placeholder[2] );

				if ( 'youtube' === $service ) {
					$counts['youtube']['gated']++;
				} elseif ( 'vimeo' === $service ) {
					$counts['vimeo']['gated']++;
				} elseif ( 'google-map' === $service ) {
					$counts['maps']['gated']++;
				}
			}
		}

		return $counts;
	}

	/**
	 * Detect external Google Fonts.
	 *
	 * @param string $body HTML.
	 * @return bool
	 */
	private static function detect_fonts( $body ) {
		$lc = strtolower( $body );

		return ( false !== strpos( $lc, 'fonts.googleapis.com' ) || false !== strpos( $lc, 'fonts.gstatic.com' ) );
	}

	/**
	 * Distinct vendor keys currently registered in the Consent-Code-Manager.
	 *
	 * @return array
	 */
	private static function registered_vendors() {
		if ( ! class_exists( 'Macs_Cookie_Banner_Codes' ) ) {
			return array();
		}

		$vendors = array();
		foreach ( Macs_Cookie_Banner_Codes::get_codes() as $entry ) {
			if ( ! empty( $entry['vendor'] ) && 'custom' !== $entry['vendor'] ) {
				$vendors[] = $entry['vendor'];
			}
		}

		return array_values( array_unique( $vendors ) );
	}

	/**
	 * Service catalogue for the surface scan.
	 *
	 * @return array
	 */
	private static function get_surface_services() {
		return array(
			array( 'key' => 'ga4', 'label' => 'Google Analytics 4', 'kind' => 'script', 'server_visible' => true, 'opaque' => false, 'note' => '', 'recommend' => __( 'Über den Consent-Code-Manager (Kategorie Statistik) laden.', 'macs-cookie-banner' ) ),
			array( 'key' => 'gtm', 'label' => 'Google Tag Manager', 'kind' => 'script', 'server_visible' => true, 'opaque' => true, 'note' => __( 'Container erkannt; die von GTM gefeuerten Tags sind serverseitig nicht prüfbar.', 'macs-cookie-banner' ), 'recommend' => __( 'Über den Consent-Code-Manager laden und in GTM gefeuerte Tags separat prüfen.', 'macs-cookie-banner' ) ),
			array( 'key' => 'meta_pixel', 'label' => 'Meta / Facebook Pixel', 'kind' => 'script', 'server_visible' => true, 'opaque' => false, 'note' => '', 'recommend' => __( 'Über den Consent-Code-Manager (Kategorie Marketing) laden.', 'macs-cookie-banner' ) ),
			array( 'key' => 'hotjar', 'label' => 'Hotjar', 'kind' => 'script', 'server_visible' => true, 'opaque' => false, 'note' => '', 'recommend' => __( 'Über den Consent-Code-Manager (Kategorie Statistik) laden.', 'macs-cookie-banner' ) ),
			array( 'key' => 'recaptcha', 'label' => 'Google reCAPTCHA', 'kind' => 'script', 'server_visible' => true, 'opaque' => false, 'note' => __( 'Rechtliche Einordnung im Einzelfall prüfen.', 'macs-cookie-banner' ), 'recommend' => __( 'Vor Consent blockieren bzw. v2-on-submit prüfen.', 'macs-cookie-banner' ) ),
			array( 'key' => 'calendly', 'label' => 'Calendly', 'kind' => 'script', 'server_visible' => false, 'opaque' => false, 'note' => __( 'Wird teils erst nach Interaktion geladen.', 'macs-cookie-banner' ), 'recommend' => __( 'Über den Consent-Code-Manager (Kategorie Externe Medien) bzw. als gegatetes Embed lösen.', 'macs-cookie-banner' ) ),
			array( 'key' => 'youtube', 'label' => 'YouTube', 'kind' => 'embed', 'server_visible' => true, 'opaque' => false, 'note' => '', 'recommend' => __( '[lscc_youtube] verwenden oder Avada-/YOTU-Gating aktivieren.', 'macs-cookie-banner' ) ),
			array( 'key' => 'vimeo', 'label' => 'Vimeo', 'kind' => 'embed', 'server_visible' => true, 'opaque' => false, 'note' => '', 'recommend' => __( '[lscc_vimeo] verwenden.', 'macs-cookie-banner' ) ),
			array( 'key' => 'maps', 'label' => 'Google Maps', 'kind' => 'embed', 'server_visible' => true, 'opaque' => false, 'note' => __( 'Maps-JS-API lädt teils clientseitig.', 'macs-cookie-banner' ), 'recommend' => __( '[lscc_google_map] verwenden; Maps-JS separat prüfen.', 'macs-cookie-banner' ) ),
			array( 'key' => 'google_fonts', 'label' => __( 'Externe Google Fonts', 'macs-cookie-banner' ), 'kind' => 'font', 'server_visible' => true, 'opaque' => false, 'note' => '', 'recommend' => '' ),
		);
	}

	/**
	 * Translated surface status label.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	private static function get_surface_status_label( $status ) {
		$labels = array(
			'nicht_gefunden' => __( 'Nicht gefunden', 'macs-cookie-banner' ),
			'verwaltet'      => __( 'Verwaltet', 'macs-cookie-banner' ),
			'teilweise'      => __( 'Teilweise verwaltet', 'macs-cookie-banner' ),
			'ungegatet'      => __( 'Ungegatet', 'macs-cookie-banner' ),
			'nicht_pruefbar' => __( 'Nicht prüfbar', 'macs-cookie-banner' ),
			'fonts_found'    => __( 'Externe Google Fonts erkannt', 'macs-cookie-banner' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	/**
	 * Render the third-party surface table.
	 *
	 * @param array $surface Surface rows.
	 * @return void
	 */
	private static function render_surface_section( $surface ) {
		$yes = __( 'Ja', 'macs-cookie-banner' );
		$no  = __( 'Nein', 'macs-cookie-banner' );
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Dienst', 'macs-cookie-banner' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Status', 'macs-cookie-banner' ); ?></th>
					<th scope="col" style="text-align:right;"><?php echo esc_html__( 'Gegated', 'macs-cookie-banner' ); ?></th>
					<th scope="col" style="text-align:right;"><?php echo esc_html__( 'Ungegatet', 'macs-cookie-banner' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Im Consent-Code-Manager', 'macs-cookie-banner' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Empfehlung / Hinweis', 'macs-cookie-banner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $surface as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['label'] ); ?></td>
						<td><strong><?php echo esc_html( self::get_surface_status_label( $row['status'] ) ); ?></strong></td>
						<td style="text-align:right;"><?php echo esc_html( (string) (int) $row['gated'] ); ?></td>
						<td style="text-align:right;"><?php echo esc_html( (string) (int) $row['ungated'] ); ?></td>
						<td><?php echo $row['managed_applicable'] ? esc_html( $row['managed'] ? $yes : $no ) : '&mdash;'; ?></td>
						<td><?php echo esc_html( trim( $row['recommendation'] . ( '' !== $row['note'] ? ' ' . $row['note'] : '' ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php echo esc_html__( 'Hinweis: Server-Sicht ohne JavaScript. „Verwaltet" = auf dieser URL als LSCC-geblocktes Script/Platzhalter erkannt. „Nicht prüfbar" = serverseitig nicht sicher bestimmbar (z. B. GTM-gefeuerte Tags, klick-/JS-geladene Widgets). Externe Google Fonts lassen sich nicht per Consent lösen — lokal hosten.', 'macs-cookie-banner' ); ?></p>
		<?php
	}

	/**
	 * Render the content scan section incl. trigger button.
	 *
	 * @param array|null $results Scan results or null if not run yet.
	 * @return void
	 */
	private static function render_content_scan_section( $results ) {
		$action_url = admin_url( 'admin.php?page=macs-cookie-banner-privacy-check' );
		?>
		<h2><?php echo esc_html__( 'Content Scan', 'macs-cookie-banner' ); ?></h2>
		<p>
			<?php echo esc_html__( 'Lokale Suche in veröffentlichten Beiträgen, Seiten und öffentlichen Custom Post Types (maximal 200 Inhalte pro Scan) nach bekannten externen Diensten. Kein Crawl, keine externen Requests, kein automatischer Scan.', 'macs-cookie-banner' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>">
			<?php wp_nonce_field( 'mcb_content_scan', 'mcb_content_scan_nonce' ); ?>
			<?php submit_button( esc_html__( 'Content Scan starten', 'macs-cookie-banner' ), 'primary', 'mcb_run_content_scan', false ); ?>
		</form>
		<?php if ( null !== $results ) : ?>
			<?php self::render_content_scan_results( $results ); ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the content scan results table.
	 *
	 * @param array $results Scan results.
	 * @return void
	 */
	private static function render_content_scan_results( $results ) {
		$findings = isset( $results['findings'] ) ? $results['findings'] : array();
		$scanned  = isset( $results['scanned'] ) ? (int) $results['scanned'] : 0;
		?>
		<p>
			<strong>
				<?php
				printf(
					/* translators: 1: Number of scanned posts, 2: Number of findings. */
					esc_html__( 'Geprüft: %1$d Inhalte. Treffer: %2$d.', 'macs-cookie-banner' ),
					$scanned,
					count( $findings )
				);
				?>
			</strong>
		</p>
		<?php if ( empty( $findings ) ) : ?>
			<p><?php echo esc_html__( 'Keine bekannten externen Embeds in den geprüften Inhalten gefunden.', 'macs-cookie-banner' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Risiko', 'macs-cookie-banner' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Dienst', 'macs-cookie-banner' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Inhaltstyp', 'macs-cookie-banner' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Titel', 'macs-cookie-banner' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Domain', 'macs-cookie-banner' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Empfehlung', 'macs-cookie-banner' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $findings as $f ) : ?>
						<tr>
							<td><?php echo esc_html( self::get_status_label( $f['risk'] ) ); ?></td>
							<td><?php echo esc_html( $f['service'] ); ?></td>
							<td><?php echo esc_html( $f['post_type'] ); ?></td>
							<td>
								<?php if ( ! empty( $f['edit_url'] ) ) : ?>
									<a href="<?php echo esc_url( $f['edit_url'] ); ?>"><?php echo esc_html( $f['title'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $f['title'] ); ?>
								<?php endif; ?>
							</td>
							<td><code><?php echo esc_html( $f['domain'] ); ?></code></td>
							<td><?php echo esc_html( $f['recommendation'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Run a local content scan against published posts, pages and public CPTs.
	 *
	 * Reads only local WordPress content. Performs no external requests.
	 *
	 * @return array
	 */
	private static function run_content_scan() {
		$query = new WP_Query(
			array(
				'post_type'              => self::get_scannable_post_types(),
				'post_status'            => 'publish',
				'posts_per_page'         => 200,
				'no_found_rows'          => true,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$patterns = self::get_content_scan_patterns();
		$findings = array();

		foreach ( $query->posts as $post ) {
			$content_lc = strtolower( (string) $post->post_content );
			$type_label = self::get_post_type_label( $post->post_type );

			foreach ( $patterns as $pattern ) {
				foreach ( $pattern['needles'] as $needle ) {
					if ( false !== strpos( $content_lc, $needle ) ) {
						$findings[] = array(
							'risk'           => $pattern['risk'],
							'service'        => $pattern['service'],
							'post_type'      => $type_label,
							'title'          => get_the_title( $post->ID ),
							'edit_url'       => get_edit_post_link( $post->ID, 'raw' ),
							'domain'         => $needle,
							'recommendation' => $pattern['recommendation'],
						);
						break;
					}
				}
			}
		}

		return array(
			'scanned'  => count( $query->posts ),
			'findings' => $findings,
		);
	}

	/**
	 * Return the post types eligible for the local content scan.
	 *
	 * @return array
	 */
	private static function get_scannable_post_types() {
		$defaults    = array( 'post', 'page' );
		$public_cpts = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'names'
		);

		return array_values( array_unique( array_merge( $defaults, array_values( (array) $public_cpts ) ) ) );
	}

	/**
	 * Translate a post type slug into a human label for display.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private static function get_post_type_label( $post_type ) {
		if ( 'post' === $post_type ) {
			return __( 'Beitrag', 'macs-cookie-banner' );
		}
		if ( 'page' === $post_type ) {
			return __( 'Seite', 'macs-cookie-banner' );
		}
		$obj = get_post_type_object( $post_type );
		if ( $obj && isset( $obj->labels->singular_name ) && '' !== $obj->labels->singular_name ) {
			return (string) $obj->labels->singular_name;
		}
		return $post_type;
	}

	/**
	 * Return the content scan patterns.
	 *
	 * @return array
	 */
	private static function get_content_scan_patterns() {
		return array(
			array(
				'service'        => __( 'YouTube', 'macs-cookie-banner' ),
				'risk'           => 'wichtig',
				'needles'        => array( 'youtube-nocookie.com', 'youtube.com', 'youtu.be' ),
				'recommendation' => __( 'Normales WordPress-YouTube-Embed gefunden. Für consent-sichere Einbettung [lscc_youtube id="VIDEO_ID"] verwenden.', 'macs-cookie-banner' ),
			),
			array(
				'service'        => __( 'Vimeo', 'macs-cookie-banner' ),
				'risk'           => 'wichtig',
				'needles'        => array( 'player.vimeo.com', 'vimeo.com' ),
				'recommendation' => __( '[lscc_vimeo id="VIDEO_ID"] verwenden.', 'macs-cookie-banner' ),
			),
			array(
				'service'        => __( 'Google Maps', 'macs-cookie-banner' ),
				'risk'           => 'wichtig',
				'needles'        => array( 'google.com/maps', 'maps.google.' ),
				'recommendation' => __( '[lscc_google_map url="..."] verwenden.', 'macs-cookie-banner' ),
			),
			array(
				'service'        => __( 'Google Fonts', 'macs-cookie-banner' ),
				'risk'           => 'kritisch',
				'needles'        => array( 'fonts.googleapis.com', 'fonts.gstatic.com' ),
				'recommendation' => __( 'Fonts lokal hosten oder im Theme/Builder deaktivieren.', 'macs-cookie-banner' ),
			),
			array(
				'service'        => __( 'Google Tag Manager', 'macs-cookie-banner' ),
				'risk'           => 'kritisch',
				'needles'        => array( 'googletagmanager.com' ),
				'recommendation' => __( 'Vor Consent blockieren oder über kontrollierte Script-Kategorie einbinden.', 'macs-cookie-banner' ),
			),
			array(
				'service'        => __( 'Google Analytics', 'macs-cookie-banner' ),
				'risk'           => 'kritisch',
				'needles'        => array( 'google-analytics.com' ),
				'recommendation' => __( 'Vor Consent blockieren oder über kontrollierte Script-Kategorie einbinden.', 'macs-cookie-banner' ),
			),
			array(
				'service'        => __( 'Facebook / Meta', 'macs-cookie-banner' ),
				'risk'           => 'kritisch',
				'needles'        => array( 'connect.facebook.net', 'facebook.net' ),
				'recommendation' => __( 'Vor Consent blockieren oder über kontrollierte Script-Kategorie einbinden.', 'macs-cookie-banner' ),
			),
		);
	}
}
