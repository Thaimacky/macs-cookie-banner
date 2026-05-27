<?php
/**
 * Lightweight privacy check admin page.
 *
 * @package LightSwissCookieConsent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Passive privacy hint checker.
 */
final class Light_Swiss_Cookie_Consent_Privacy_Check {
	/**
	 * Render the privacy check page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$source_url       = home_url( '/' );
		$headline_results = array_merge(
			array(
				array(
					'status'         => 'info',
					'problem'        => sprintf(
						/* translators: %s: Checked homepage URL. */
						__( 'Geprüfte Startseite: %s', 'light-swiss-cookie-consent' ),
						$source_url
					),
					'recommendation' => __( 'Nur diese einzelne URL wird geprüft. Es findet kein Crawl statt.', 'light-swiss-cookie-consent' ),
				),
			),
			self::run_check( $source_url )
		);

		$content_scan_results = null;
		if ( isset( $_POST['lscc_run_content_scan'] ) ) {
			check_admin_referer( 'lscc_content_scan', 'lscc_content_scan_nonce' );
			$content_scan_results = self::run_content_scan();
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Privacy Check', 'light-swiss-cookie-consent' ); ?></h1>

			<h2><?php echo esc_html__( 'Startseiten-Prüfung', 'light-swiss-cookie-consent' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Status', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Problem', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Empfehlung', 'light-swiss-cookie-consent' ); ?></th>
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
	 * Run a single passive check against the homepage output.
	 *
	 * @param string $source_url URL to check.
	 * @return array
	 */
	private static function run_check( $source_url ) {
		$response = wp_remote_get(
			$source_url,
			array(
				'timeout'             => 5,
				'redirection'         => 2,
				'limit_response_size' => 500000,
				'user-agent'          => 'Light Swiss Cookie Consent Privacy Check/' . LSCC_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				array(
					'status'         => 'info',
					'problem'        => __( 'Startseite konnte nicht geprüft werden.', 'light-swiss-cookie-consent' ),
					'recommendation' => __( 'Bitte prüfen Sie die Seite manuell oder starten Sie den Check später erneut.', 'light-swiss-cookie-consent' ),
				),
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( 400 <= $status_code || '' === $body ) {
			return array(
				array(
					'status'         => 'info',
					'problem'        => __( 'Startseite lieferte keine prüfbare Ausgabe.', 'light-swiss-cookie-consent' ),
					'recommendation' => __( 'Bitte prüfen Sie die eingebundenen externen Dienste manuell.', 'light-swiss-cookie-consent' ),
				),
			);
		}

		return self::detect_services( $body );
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
				'problem'        => __( 'Keine der einfachen Privacy-Check-Muster gefunden.', 'light-swiss-cookie-consent' ),
				'recommendation' => __( 'Prüfen Sie externe Dienste trotzdem manuell, besonders eingebettete Medien, Tracking und Fonts.', 'light-swiss-cookie-consent' ),
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
				'problem'        => __( 'Externe Google Fonts erkannt.', 'light-swiss-cookie-consent' ),
				'recommendation' => __( 'Fonts lokal hosten oder ohne externe Google-Requests einbinden.', 'light-swiss-cookie-consent' ),
			),
			array(
				'status'         => 'kritisch',
				'patterns'       => array(
					'google-analytics.com',
					'googletagmanager.com',
				),
				'problem'        => __( 'Google Analytics oder Google Tag Manager erkannt.', 'light-swiss-cookie-consent' ),
				'recommendation' => __( 'Nur nach Zustimmung laden und korrekt einer Consent-Kategorie zuordnen.', 'light-swiss-cookie-consent' ),
			),
			array(
				'status'         => 'kritisch',
				'patterns'       => array(
					'facebook.net',
					'connect.facebook.net',
				),
				'problem'        => __( 'Facebook-Script oder Meta-Dienst erkannt.', 'light-swiss-cookie-consent' ),
				'recommendation' => __( 'Nur nach Zustimmung laden und Marketing-Scripts bewusst blockieren.', 'light-swiss-cookie-consent' ),
			),
			array(
				'status'         => 'wichtig',
				'patterns'       => array(
					'youtube.com',
					'youtu.be',
				),
				'problem'        => __( 'YouTube-Inhalte oder YouTube-Links erkannt.', 'light-swiss-cookie-consent' ),
				'recommendation' => __( 'Externe Medien erst nach Zustimmung laden oder datenschutzfreundliche Einbettung prüfen.', 'light-swiss-cookie-consent' ),
			),
			array(
				'status'         => 'wichtig',
				'patterns'       => array(
					'vimeo.com',
				),
				'problem'        => __( 'Vimeo-Inhalte oder Vimeo-Links erkannt.', 'light-swiss-cookie-consent' ),
				'recommendation' => __( 'Externe Medien erst nach Zustimmung laden oder datenschutzfreundliche Einbettung prüfen.', 'light-swiss-cookie-consent' ),
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
			'kritisch' => __( 'Kritisch', 'light-swiss-cookie-consent' ),
			'wichtig'  => __( 'Wichtig', 'light-swiss-cookie-consent' ),
			'info'     => __( 'Info', 'light-swiss-cookie-consent' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['info'];
	}

	/**
	 * Render the content scan section incl. trigger button.
	 *
	 * @param array|null $results Scan results or null if not run yet.
	 * @return void
	 */
	private static function render_content_scan_section( $results ) {
		$action_url = admin_url( 'admin.php?page=light-swiss-cookie-consent-privacy-check' );
		?>
		<h2><?php echo esc_html__( 'Content Scan', 'light-swiss-cookie-consent' ); ?></h2>
		<p>
			<?php echo esc_html__( 'Lokale Suche in veröffentlichten Beiträgen, Seiten und öffentlichen Custom Post Types (maximal 200 Inhalte pro Scan) nach bekannten externen Diensten. Kein Crawl, keine externen Requests, kein automatischer Scan.', 'light-swiss-cookie-consent' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>">
			<?php wp_nonce_field( 'lscc_content_scan', 'lscc_content_scan_nonce' ); ?>
			<?php submit_button( esc_html__( 'Content Scan starten', 'light-swiss-cookie-consent' ), 'primary', 'lscc_run_content_scan', false ); ?>
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
					esc_html__( 'Geprüft: %1$d Inhalte. Treffer: %2$d.', 'light-swiss-cookie-consent' ),
					$scanned,
					count( $findings )
				);
				?>
			</strong>
		</p>
		<?php if ( empty( $findings ) ) : ?>
			<p><?php echo esc_html__( 'Keine bekannten externen Embeds in den geprüften Inhalten gefunden.', 'light-swiss-cookie-consent' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Risiko', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Dienst', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Inhaltstyp', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Titel', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Domain', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Empfehlung', 'light-swiss-cookie-consent' ); ?></th>
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
			return __( 'Beitrag', 'light-swiss-cookie-consent' );
		}
		if ( 'page' === $post_type ) {
			return __( 'Seite', 'light-swiss-cookie-consent' );
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
				'service'        => __( 'YouTube', 'light-swiss-cookie-consent' ),
				'risk'           => 'wichtig',
				'needles'        => array( 'youtube-nocookie.com', 'youtube.com', 'youtu.be' ),
				'recommendation' => __( 'Normales WordPress-YouTube-Embed gefunden. Für consent-sichere Einbettung [lscc_youtube id="VIDEO_ID"] verwenden.', 'light-swiss-cookie-consent' ),
			),
			array(
				'service'        => __( 'Vimeo', 'light-swiss-cookie-consent' ),
				'risk'           => 'wichtig',
				'needles'        => array( 'player.vimeo.com', 'vimeo.com' ),
				'recommendation' => __( '[lscc_vimeo id="VIDEO_ID"] verwenden.', 'light-swiss-cookie-consent' ),
			),
			array(
				'service'        => __( 'Google Maps', 'light-swiss-cookie-consent' ),
				'risk'           => 'wichtig',
				'needles'        => array( 'google.com/maps', 'maps.google.' ),
				'recommendation' => __( '[lscc_google_map url="..."] verwenden.', 'light-swiss-cookie-consent' ),
			),
			array(
				'service'        => __( 'Google Fonts', 'light-swiss-cookie-consent' ),
				'risk'           => 'kritisch',
				'needles'        => array( 'fonts.googleapis.com', 'fonts.gstatic.com' ),
				'recommendation' => __( 'Fonts lokal hosten oder im Theme/Builder deaktivieren.', 'light-swiss-cookie-consent' ),
			),
			array(
				'service'        => __( 'Google Tag Manager', 'light-swiss-cookie-consent' ),
				'risk'           => 'kritisch',
				'needles'        => array( 'googletagmanager.com' ),
				'recommendation' => __( 'Vor Consent blockieren oder über kontrollierte Script-Kategorie einbinden.', 'light-swiss-cookie-consent' ),
			),
			array(
				'service'        => __( 'Google Analytics', 'light-swiss-cookie-consent' ),
				'risk'           => 'kritisch',
				'needles'        => array( 'google-analytics.com' ),
				'recommendation' => __( 'Vor Consent blockieren oder über kontrollierte Script-Kategorie einbinden.', 'light-swiss-cookie-consent' ),
			),
			array(
				'service'        => __( 'Facebook / Meta', 'light-swiss-cookie-consent' ),
				'risk'           => 'kritisch',
				'needles'        => array( 'connect.facebook.net', 'facebook.net' ),
				'recommendation' => __( 'Vor Consent blockieren oder über kontrollierte Script-Kategorie einbinden.', 'light-swiss-cookie-consent' ),
			),
		);
	}
}
