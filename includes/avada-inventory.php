<?php
/**
 * Read-only Avada inventory scan admin page.
 *
 * Measures the distribution of video / map / embed types across existing
 * content so the realistic automatic coverage of a future Avada compatibility
 * module can be estimated. This page is strictly passive: it reads local
 * content via WP_Query and counts string / regex matches. It performs no
 * external requests, writes nothing, and never changes any post or page.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Passive Avada inventory counter.
 */
final class Macs_Cookie_Banner_Avada_Inventory {
	/**
	 * Maximum number of posts inspected per scan run.
	 */
	const SCAN_LIMIT = 500;

	/**
	 * Render the inventory scan page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$results = null;
		if ( isset( $_POST['mcb_run_avada_inventory'] ) ) {
			check_admin_referer( 'mcb_avada_inventory', 'mcb_avada_inventory_nonce' );
			$results = self::run_inventory_scan();
		}

		$action_url = admin_url( 'admin.php?page=macs-cookie-banner-avada-inventory' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Avada Inventar-Scan', 'macs-cookie-banner' ); ?></h1>
			<p>
				<?php echo esc_html__( 'Passive, rein lesende Messung der eingebundenen Video-, Map- und Embed-Typen in lokalen Inhalten. Es werden keine Inhalte geändert, keine Migration durchgeführt und keine externen Requests gesendet. Der Scan dient ausschliesslich der Abschätzung, wie viele Einbindungen ein künftiges Avada-Kompatibilitätsmodul automatisch abdecken könnte.', 'macs-cookie-banner' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( $action_url ); ?>">
				<?php wp_nonce_field( 'mcb_avada_inventory', 'mcb_avada_inventory_nonce' ); ?>
				<?php submit_button( esc_html__( 'Inventar-Scan starten', 'macs-cookie-banner' ), 'primary', 'mcb_run_avada_inventory', false ); ?>
			</form>
			<?php if ( null !== $results ) : ?>
				<?php self::render_results( $results ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the full result output.
	 *
	 * @param array $results Scan results.
	 * @return void
	 */
	private static function render_results( $results ) {
		$counts    = $results['counts'];
		$scanned   = (int) $results['scanned'];
		$total     = (int) $results['total'];
		$truncated = (bool) $results['truncated'];

		// Element-level (deduplicated) third-party buckets.
		$buckets = array(
			'fusion_youtube'      => __( 'fusion_youtube', 'macs-cookie-banner' ),
			'fusion_vimeo'        => __( 'fusion_vimeo', 'macs-cookie-banner' ),
			'oembed'              => __( 'oEmbed (YouTube/Vimeo, nackte URL)', 'macs-cookie-banner' ),
			'fusion_map'          => __( 'fusion_map (Google Maps)', 'macs-cookie-banner' ),
			'bg_video_3p'         => __( 'Background-Video (YouTube/Vimeo)', 'macs-cookie-banner' ),
			'fusion_code_embed'   => __( 'fusion_code (mit Drittanbieter-Embed)', 'macs-cookie-banner' ),
			'raw_iframe_3p'       => __( 'Rohe iframes (Drittanbieter)', 'macs-cookie-banner' ),
		);

		$thirdparty_total = 0;
		foreach ( array_keys( $buckets ) as $key ) {
			$thirdparty_total += (int) $counts[ $key ];
		}

		$automatic = (int) $counts['fusion_youtube'] + (int) $counts['fusion_vimeo'] + (int) $counts['oembed'];
		$bedingt   = (int) $counts['fusion_map'];
		$manuell   = (int) $counts['bg_video_3p'] + (int) $counts['fusion_code_embed'] + (int) $counts['raw_iframe_3p'];

		$coverage_min = self::percent( $automatic, $thirdparty_total );
		$coverage_max = self::percent( $automatic + $bedingt, $thirdparty_total );
		?>
		<p>
			<strong>
				<?php
				printf(
					/* translators: 1: scanned posts, 2: total matching posts. */
					esc_html__( 'Geprüft: %1$d von %2$d Inhalten.', 'macs-cookie-banner' ),
					$scanned,
					$total
				);
				?>
			</strong>
			<?php if ( $truncated ) : ?>
				<br />
				<em>
					<?php
					printf(
						/* translators: %d: scan limit. */
						esc_html__( 'Hinweis: Begrenzung auf %d Inhalte erreicht. Die restlichen Inhalte wurden NICHT gemessen.', 'macs-cookie-banner' ),
						(int) self::SCAN_LIMIT
					);
					?>
				</em>
			<?php endif; ?>
		</p>

		<h2><?php echo esc_html__( '1. Verteilung (Drittanbieter-Embeds)', 'macs-cookie-banner' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Typ', 'macs-cookie-banner' ); ?></th>
					<th scope="col" style="text-align:right;"><?php echo esc_html__( 'Anzahl', 'macs-cookie-banner' ); ?></th>
					<th scope="col" style="text-align:right;"><?php echo esc_html__( 'Prozent', 'macs-cookie-banner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $buckets as $key => $label ) : ?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td style="text-align:right;"><?php echo esc_html( (string) (int) $counts[ $key ] ); ?></td>
						<td style="text-align:right;"><?php echo esc_html( self::percent( (int) $counts[ $key ], $thirdparty_total ) . ' %' ); ?></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<td><strong><?php echo esc_html__( 'Summe Drittanbieter-Embeds', 'macs-cookie-banner' ); ?></strong></td>
					<td style="text-align:right;"><strong><?php echo esc_html( (string) $thirdparty_total ); ?></strong></td>
					<td style="text-align:right;"><strong>100 %</strong></td>
				</tr>
				<tr>
					<td><?php echo esc_html__( 'Background-Video (self-hosted, kein Drittanbieter)', 'macs-cookie-banner' ); ?></td>
					<td style="text-align:right;"><?php echo esc_html( (string) (int) $counts['bg_video_self'] ); ?></td>
					<td style="text-align:right;">&mdash;</td>
				</tr>
			</tbody>
		</table>

		<h2><?php echo esc_html__( '2. Automatisch abfangbar', 'macs-cookie-banner' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Kategorie', 'macs-cookie-banner' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Automatisch', 'macs-cookie-banner' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Manuell', 'macs-cookie-banner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$coverage_rows = array(
					array( __( 'fusion_youtube', 'macs-cookie-banner' ), true, false ),
					array( __( 'fusion_vimeo', 'macs-cookie-banner' ), true, false ),
					array( __( 'oEmbed (YouTube/Vimeo)', 'macs-cookie-banner' ), true, false ),
					array( __( 'fusion_map (Google Maps)', 'macs-cookie-banner' ), false, true ),
					array( __( 'Background-Video (YouTube/Vimeo)', 'macs-cookie-banner' ), false, true ),
					array( __( 'fusion_code (mit Embed)', 'macs-cookie-banner' ), false, true ),
					array( __( 'Rohe iframes', 'macs-cookie-banner' ), false, true ),
				);
				$yes = esc_html__( 'Ja', 'macs-cookie-banner' );
				$no  = '&mdash;';
				foreach ( $coverage_rows as $row ) :
					?>
					<tr>
						<td><?php echo esc_html( $row[0] ); ?></td>
						<td><?php echo $row[1] ? esc_html( $yes ) : $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><?php echo $row[2] ? esc_html( $yes ) : $no; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php echo esc_html__( '3. KPI: Automatische Abdeckung', 'macs-cookie-banner' ); ?></h2>
		<ul>
			<li>
				<strong><?php echo esc_html__( 'Abdeckung_min', 'macs-cookie-banner' ); ?>:</strong>
				<?php echo esc_html( $coverage_min . ' %' ); ?>
				<?php echo esc_html__( '(fusion_youtube + fusion_vimeo + oEmbed)', 'macs-cookie-banner' ); ?>
			</li>
			<li>
				<strong><?php echo esc_html__( 'Abdeckung_max', 'macs-cookie-banner' ); ?>:</strong>
				<?php echo esc_html( $coverage_max . ' %' ); ?>
				<?php echo esc_html__( '(zusätzlich fusion_map, sofern Google-Maps-Script-Gating ergänzt wird)', 'macs-cookie-banner' ); ?>
			</li>
		</ul>

		<h2><?php echo esc_html__( '4. Top-Sonderfälle (manuelle Prüfung nötig)', 'macs-cookie-banner' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Sonderfall', 'macs-cookie-banner' ); ?></th>
					<th scope="col" style="text-align:right;"><?php echo esc_html__( 'Anzahl', 'macs-cookie-banner' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Bemerkung', 'macs-cookie-banner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>fusion_code</td>
					<td style="text-align:right;"><?php echo esc_html( (int) $counts['fusion_code_embed'] . ' / ' . (int) $counts['fusion_code_total'] ); ?></td>
					<td><?php echo esc_html__( 'Code-Blöcke mit Drittanbieter-Embed / Code-Blöcke gesamt. Inhalt ist base64-kodiert; nicht automatisch gatebar.', 'macs-cookie-banner' ); ?></td>
				</tr>
				<tr>
					<td><?php echo esc_html__( 'Background-Video', 'macs-cookie-banner' ); ?></td>
					<td style="text-align:right;"><?php echo esc_html( (string) (int) $counts['bg_video_3p'] ); ?></td>
					<td><?php echo esc_html__( 'YouTube/Vimeo als Container-/Spalten-Hintergrund. Attribut am Container, nicht sauber einzeln gatebar.', 'macs-cookie-banner' ); ?></td>
				</tr>
				<tr>
					<td><?php echo esc_html__( 'Rohe iframes', 'macs-cookie-banner' ); ?></td>
					<td style="text-align:right;"><?php echo esc_html( (int) $counts['raw_iframe_3p'] . ' / ' . (int) $counts['raw_iframe_total'] ); ?></td>
					<td><?php echo esc_html__( 'Drittanbieter-iframes / iframes gesamt. Handgepastete iframes werden vom Render-Filter nicht erfasst.', 'macs-cookie-banner' ); ?></td>
				</tr>
				<tr>
					<td>Maps</td>
					<td style="text-align:right;"><?php echo esc_html( (string) (int) $counts['fusion_map'] ); ?></td>
					<td><?php echo esc_html__( 'Avada Maps nutzt meist die Google-Maps-JS-API; benötigt zusätzliches Script-Gating.', 'macs-cookie-banner' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h2><?php echo esc_html__( 'Diagnostik (Rohtreffer, mögliche Überschneidungen)', 'macs-cookie-banner' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Muster', 'macs-cookie-banner' ); ?></th>
					<th scope="col" style="text-align:right;"><?php echo esc_html__( 'Vorkommen', 'macs-cookie-banner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$diag = array(
					'youtube.com'         => (int) $counts['diag_youtube_com'],
					'youtu.be'            => (int) $counts['diag_youtu_be'],
					'player.vimeo.com'    => (int) $counts['diag_player_vimeo'],
					'vimeo.com'           => (int) $counts['diag_vimeo_com'],
					'google.com/maps'     => (int) $counts['diag_google_maps'],
					'maps.googleapis.com' => (int) $counts['diag_maps_api'],
					'<iframe'             => (int) $counts['diag_iframe'],
					'video_url='          => (int) $counts['diag_video_url'],
				);
				foreach ( $diag as $needle => $value ) :
					?>
					<tr>
						<td><code><?php echo esc_html( $needle ); ?></code></td>
						<td style="text-align:right;"><?php echo esc_html( (string) $value ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><em><?php echo esc_html__( 'Hinweis: Slider (Fusion/Revolution/LayerSlider) und externe Speicher ausserhalb des post_content werden nicht gemessen. Mehrsprachige Duplikate (WPML/Polylang) können Mehrfachzählungen erzeugen.', 'macs-cookie-banner' ); ?></em></p>
		<?php
	}

	/**
	 * Run the local, read-only inventory scan.
	 *
	 * @return array
	 */
	private static function run_inventory_scan() {
		$query = new WP_Query(
			array(
				'post_type'              => self::get_inventory_post_types(),
				'post_status'            => 'publish',
				'posts_per_page'         => self::SCAN_LIMIT,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$counts = array(
			'fusion_youtube'    => 0,
			'fusion_vimeo'      => 0,
			'oembed'            => 0,
			'fusion_map'        => 0,
			'bg_video_3p'       => 0,
			'bg_video_self'     => 0,
			'fusion_code_total' => 0,
			'fusion_code_embed' => 0,
			'raw_iframe_total'  => 0,
			'raw_iframe_3p'     => 0,
			// Diagnostics (raw occurrence counts).
			'diag_youtube_com'  => 0,
			'diag_youtu_be'     => 0,
			'diag_player_vimeo' => 0,
			'diag_vimeo_com'    => 0,
			'diag_google_maps'  => 0,
			'diag_maps_api'     => 0,
			'diag_iframe'       => 0,
			'diag_video_url'    => 0,
		);

		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );

		foreach ( $query->posts as $post ) {
			self::analyze_content( (string) $post->post_content, $counts, $home_host );
		}

		$total = (int) $query->found_posts;

		return array(
			'counts'    => $counts,
			'scanned'   => count( $query->posts ),
			'total'     => $total,
			'truncated' => $total > self::SCAN_LIMIT,
		);
	}

	/**
	 * Analyze a single content string and accumulate counts.
	 *
	 * @param string $content   Raw post content.
	 * @param array  $counts    Accumulator (by reference).
	 * @param string $home_host Site host for same-origin iframe detection.
	 * @return void
	 */
	private static function analyze_content( $content, &$counts, $home_host ) {
		$lc = strtolower( $content );

		// Element-level shortcode counts.
		$counts['fusion_youtube'] += substr_count( $lc, '[fusion_youtube' );
		$counts['fusion_vimeo']   += substr_count( $lc, '[fusion_vimeo' );

		$map_open   = substr_count( $lc, '[fusion_map' );
		$map_marker = substr_count( $lc, '[fusion_map_marker' );
		$counts['fusion_map'] += max( 0, $map_open - $map_marker );

		// Diagnostics (raw occurrences).
		$counts['diag_youtube_com']  += substr_count( $lc, 'youtube.com' );
		$counts['diag_youtu_be']     += substr_count( $lc, 'youtu.be' );
		$counts['diag_player_vimeo'] += substr_count( $lc, 'player.vimeo.com' );
		$counts['diag_vimeo_com']    += substr_count( $lc, 'vimeo.com' );
		$counts['diag_google_maps']  += substr_count( $lc, 'google.com/maps' ) + substr_count( $lc, 'maps.google.' );
		$counts['diag_maps_api']     += substr_count( $lc, 'maps.googleapis.com' );
		$counts['diag_iframe']       += substr_count( $lc, '<iframe' );
		$counts['diag_video_url']    += substr_count( $lc, 'video_url=' );

		// Background videos: classify each video_url="..." value.
		if ( preg_match_all( '/video_url=["\']([^"\']*)["\']/i', $content, $m ) ) {
			foreach ( $m[1] as $url ) {
				$url_lc = strtolower( $url );
				if ( '' === trim( $url_lc ) ) {
					continue;
				}
				if ( false !== strpos( $url_lc, 'youtube' ) || false !== strpos( $url_lc, 'youtu.be' ) || false !== strpos( $url_lc, 'vimeo' ) ) {
					$counts['bg_video_3p']++;
				}
			}
		}

		// Self-hosted background video sources.
		$counts['bg_video_self'] += substr_count( $lc, 'video_mp4=' ) + substr_count( $lc, 'video_webm=' ) + substr_count( $lc, 'video_ogv=' );

		// Raw iframes: total and third-party (cross-origin) classification.
		if ( preg_match_all( '/<iframe\b[^>]*\bsrc=["\']([^"\']*)["\']/i', $content, $mi ) ) {
			foreach ( $mi[1] as $src ) {
				$counts['raw_iframe_total']++;
				$host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );
				if ( '' === $host ) {
					continue; // Relative/self src.
				}
				if ( '' === $home_host || $host !== $home_host ) {
					$counts['raw_iframe_3p']++;
				}
			}
		}

		// fusion_code blocks: count and detect third-party embeds (base64 or raw).
		if ( preg_match_all( '/\[fusion_code\](.*?)\[\/fusion_code\]/is', $content, $mc ) ) {
			foreach ( $mc[1] as $inner ) {
				$counts['fusion_code_total']++;
				$decoded = base64_decode( trim( $inner ), true );
				$haystack = strtolower( $inner . ' ' . ( false !== $decoded ? $decoded : '' ) );
				if (
					false !== strpos( $haystack, 'youtube' ) ||
					false !== strpos( $haystack, 'youtu.be' ) ||
					false !== strpos( $haystack, 'vimeo' ) ||
					false !== strpos( $haystack, '<iframe' ) ||
					false !== strpos( $haystack, 'google.com/maps' ) ||
					false !== strpos( $haystack, 'maps.googleapis.com' )
				) {
					$counts['fusion_code_embed']++;
				}
			}
		}

		// oEmbed: bare YouTube/Vimeo URL on its own line.
		$lines = preg_split( '/\r\n|\r|\n/', $content );
		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				if ( preg_match( '#^(https?://)?(www\.)?(youtube\.com/watch\?\S+|youtu\.be/\S+|vimeo\.com/\d+\S*)$#i', $line ) ) {
					$counts['oembed']++;
				}
			}
		}
	}

	/**
	 * Return the post types eligible for the inventory scan.
	 *
	 * Includes posts, pages, public CPTs and known Avada layout / builder CPTs
	 * when they exist on the site.
	 *
	 * @return array
	 */
	private static function get_inventory_post_types() {
		$defaults    = array( 'post', 'page' );
		$public_cpts = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'names'
		);

		$avada_cpts = array();
		foreach ( array( 'fusion_tb_section', 'fusion_tb_layout', 'fusion_template', 'slide', 'fusion_element' ) as $cpt ) {
			if ( post_type_exists( $cpt ) ) {
				$avada_cpts[] = $cpt;
			}
		}

		return array_values( array_unique( array_merge( $defaults, array_values( (array) $public_cpts ), $avada_cpts ) ) );
	}

	/**
	 * Compute a rounded percentage with divide-by-zero protection.
	 *
	 * @param int $part  Numerator.
	 * @param int $total Denominator.
	 * @return string
	 */
	private static function percent( $part, $total ) {
		if ( $total <= 0 ) {
			return '0.0';
		}

		return number_format_i18n( ( $part / $total ) * 100, 1 );
	}
}
