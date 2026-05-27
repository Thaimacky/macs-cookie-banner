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

		$source_url = home_url( '/' );
		$results    = array_merge(
			array(
				array(
					'status'         => 'info',
					'problem'        => sprintf(
						/* translators: %s: Checked homepage URL. */
						__( 'Gepruefte Startseite: %s', 'light-swiss-cookie-consent' ),
						$source_url
					),
					'recommendation' => __( 'Nur diese einzelne URL wird geprueft. Es findet kein Crawl statt.', 'light-swiss-cookie-consent' ),
				),
			),
			self::run_check( $source_url )
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Privacy Check', 'light-swiss-cookie-consent' ); ?></h1>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Status', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Problem', 'light-swiss-cookie-consent' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Empfehlung', 'light-swiss-cookie-consent' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $results as $result ) : ?>
						<tr>
							<td><?php echo esc_html( self::get_status_label( $result['status'] ) ); ?></td>
							<td><?php echo esc_html( $result['problem'] ); ?></td>
							<td><?php echo esc_html( $result['recommendation'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
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
					'problem'        => __( 'Startseite konnte nicht geprueft werden.', 'light-swiss-cookie-consent' ),
					'recommendation' => __( 'Bitte pruefen Sie die Seite manuell oder starten Sie den Check spaeter erneut.', 'light-swiss-cookie-consent' ),
				),
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( 400 <= $status_code || '' === $body ) {
			return array(
				array(
					'status'         => 'info',
					'problem'        => __( 'Startseite lieferte keine pruefbare Ausgabe.', 'light-swiss-cookie-consent' ),
					'recommendation' => __( 'Bitte pruefen Sie die eingebundenen externen Dienste manuell.', 'light-swiss-cookie-consent' ),
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
				'recommendation' => __( 'Pruefen Sie externe Dienste trotzdem manuell, besonders eingebettete Medien, Tracking und Fonts.', 'light-swiss-cookie-consent' ),
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
				'recommendation' => __( 'Externe Medien erst nach Zustimmung laden oder datenschutzfreundliche Einbettung pruefen.', 'light-swiss-cookie-consent' ),
			),
			array(
				'status'         => 'wichtig',
				'patterns'       => array(
					'vimeo.com',
				),
				'problem'        => __( 'Vimeo-Inhalte oder Vimeo-Links erkannt.', 'light-swiss-cookie-consent' ),
				'recommendation' => __( 'Externe Medien erst nach Zustimmung laden oder datenschutzfreundliche Einbettung pruefen.', 'light-swiss-cookie-consent' ),
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
}
