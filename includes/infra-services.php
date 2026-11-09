<?php
/**
 * Infrastructure services without consent (v1.1.0, ADR-44): detect, list,
 * disclose.
 *
 * Some services process visitor data (at least the IP address) without being
 * subject to consent, because the website cannot be delivered without them -
 * an image CDN, a reverse proxy/CDN, a page cache. They must NEVER be blocked
 * or gated, but they must be disclosed in the privacy policy. This module:
 *
 * - detects them server-side: active plugins (register: plugin slug ->
 *   service) and the signals of the ONE page the Privacy Check already
 *   fetches (HTML needles, response headers) - no additional request;
 * - keeps them in the list "Dienste ohne Einwilligung (offenlegungspflichtig)"
 *   (option lscc_infra_services): detected services are added automatically
 *   ("automatisch erkannt" + date), manual entries are never touched by the
 *   detection, a service that is no longer detected is only marked
 *   ("zuletzt erkannt am ..."), never removed;
 * - shows them in the frontend ONLY after the operator confirmed them
 *   (decision Marcel 2026-11-10: architecture rule 3 - no automatic hints in
 *   the frontend - stays valid): shortcode [lscc_privacy_services] for the
 *   privacy policy and an optional line in the banner details;
 * - texts DE/EN/FR (language follows WPML/Polylang via the active locale),
 *   every text overridable per language. The texts are templates, not legal
 *   advice - the admin page says so prominently.
 *
 * Nothing here blocks, gates, rewrites or fetches anything in the frontend.
 *
 * @package MacsCookieBanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disclosure of infrastructure services that run without consent.
 */
final class Macs_Cookie_Banner_Infra_Services {
	/**
	 * Option holding the list and detection state.
	 */
	const OPTION = 'lscc_infra_services';

	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'macs-cookie-banner-disclosure';

	/**
	 * Transient throttling the plugin scan in wp-admin.
	 */
	const SCAN_TRANSIENT = 'mcb_infra_plugin_scan';

	/**
	 * Languages with shipped templates. Other languages fall back to English.
	 */
	const LANGS = array( 'de', 'en', 'fr' );

	/**
	 * Text fields of a service section.
	 */
	const FIELDS = array( 'label', 'provider', 'purpose', 'data', 'legal_basis', 'transfer', 'privacy_url' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'lscc_privacy_services', array( __CLASS__, 'render_shortcode' ) );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( __CLASS__, 'maybe_scan_plugins' ) );
		add_action( 'activated_plugin', array( __CLASS__, 'reset_scan_throttle' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'reset_scan_throttle' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_new_services_notice' ) );
		add_action( 'admin_post_mcb_save_infra_services', array( __CLASS__, 'save' ) );
	}

	/* --------------------------------------------------------------------- */
	/* Register                                                               */
	/* --------------------------------------------------------------------- */

	/**
	 * Register of known infrastructure services (slug -> service).
	 *
	 * Extend via the filter `mcb_infra_service_registry` (same structure).
	 * Provider facts are taken from the providers' official pages only; the
	 * sources are noted per entry. Re-check them when the texts are updated.
	 *
	 * @return array
	 */
	public static function registry() {
		$registry = array(
			/*
			 * Optimole - image optimisation + image CDN.
			 * Sources (retrieved 2026-11-10):
			 * - Operator + address: https://optimole.com/privacy-policy/
			 *   "This site is owned and/or operated by Vertigo Studio SA" /
			 *   "20 Povernei Street, 4th Floor, Flat no. 9, 010641 Bucharest, Romania".
			 * - CDN: https://docs.optimole.com/image-processing/what-content-delivery-network-cdn-do-you-use
			 *   "all users are on CloudFront CDN from Amazon".
			 * - Data: https://docs.optimole.com/account/how-optimole-counts-the-number-of-visitors
			 *   "the request is logged using the visitor's IP address and user agent".
			 * - AWS DPF: https://aws.amazon.com/compliance/eu-us-data-privacy-framework/
			 *   "AWS has certified to the EU-U.S. Data Privacy Framework (DPF)" /
			 *   "AWS has certified to the Swiss-U.S. DPF".
			 */
			'optimole'   => array(
				'name'     => 'Optimole',
				'plugins'  => array( 'optimole-wp' ),
				'html'     => array( '.i.optimole.com' ),
				'headers'  => array(),
				'texts'    => array(
					'de' => array(
						'label'       => 'Bild-CDN',
						'provider'    => 'Vertigo Studio SA, 20 Povernei Street, 010641 Bukarest, Rumänien',
						'purpose'     => 'Optimierung und Auslieferung der Bilder dieser Website über ein Content Delivery Network (CDN), damit die Bilder in passender Grösse und schnell geladen werden.',
						'data'        => 'Ihre IP-Adresse, die Kennung Ihres Browsers (User-Agent), die abgerufene Bilddatei und der Zeitpunkt des Abrufs. Optimole protokolliert diese Angaben bei jedem Bildabruf.',
						'legal_basis' => 'Unser berechtigtes Interesse an einer schnellen und stabilen Darstellung der Website (Art. 6 Abs. 1 lit. f DSGVO, soweit anwendbar). Für die Schweiz erfolgt die Bearbeitung gestützt auf das Datenschutzgesetz (DSG).',
						'transfer'    => 'Optimole hat seinen Sitz in der EU (Rumänien). Die Bilder werden über das CDN Amazon CloudFront der Amazon Web Services, Inc. (USA) ausgeliefert, in der Regel vom nächstgelegenen Serverstandort. Eine Übermittlung in die USA ist daher möglich. Amazon Web Services ist unter dem EU-U.S. Data Privacy Framework und dem Swiss-U.S. Data Privacy Framework zertifiziert.',
						'privacy_url' => 'https://optimole.com/privacy-policy/',
					),
					'en' => array(
						'label'       => 'image CDN',
						'provider'    => 'Vertigo Studio SA, 20 Povernei Street, 010641 Bucharest, Romania',
						'purpose'     => 'Optimising and delivering the images of this website via a content delivery network (CDN), so that images load quickly and in a suitable size.',
						'data'        => 'Your IP address, your browser identifier (user agent), the requested image file and the time of the request. Optimole logs this information for every image request.',
						'legal_basis' => 'Our legitimate interest in displaying the website quickly and reliably (Art. 6(1)(f) GDPR, where applicable). For Switzerland, processing is based on the Federal Act on Data Protection (FADP).',
						'transfer'    => 'Optimole is based in the EU (Romania). Images are delivered via the Amazon CloudFront CDN of Amazon Web Services, Inc. (USA), usually from the nearest server location. A transfer to the USA is therefore possible. Amazon Web Services is certified under the EU-U.S. Data Privacy Framework and the Swiss-U.S. Data Privacy Framework.',
						'privacy_url' => 'https://optimole.com/privacy-policy/',
					),
					'fr' => array(
						'label'       => 'CDN d’images',
						'provider'    => 'Vertigo Studio SA, 20 Povernei Street, 010641 Bucarest, Roumanie',
						'purpose'     => 'Optimisation et diffusion des images de ce site via un réseau de diffusion de contenu (CDN), afin que les images se chargent rapidement et dans une taille adaptée.',
						'data'        => 'Votre adresse IP, l’identifiant de votre navigateur (user agent), le fichier image demandé et l’heure de la demande. Optimole enregistre ces informations lors de chaque chargement d’image.',
						'legal_basis' => 'Notre intérêt légitime à afficher le site de manière rapide et fiable (art. 6, par. 1, let. f RGPD, dans la mesure où il est applicable). Pour la Suisse, le traitement se fonde sur la loi fédérale sur la protection des données (LPD).',
						'transfer'    => 'Optimole a son siège dans l’UE (Roumanie). Les images sont diffusées via le CDN Amazon CloudFront d’Amazon Web Services, Inc. (États-Unis), en règle générale depuis le serveur le plus proche. Un transfert vers les États-Unis est donc possible. Amazon Web Services est certifiée selon le EU-U.S. Data Privacy Framework et le Swiss-U.S. Data Privacy Framework.',
						'privacy_url' => 'https://optimole.com/privacy-policy/',
					),
				),
			),
			/*
			 * Cloudflare - reverse proxy / CDN / security in front of the site.
			 * Sources (retrieved 2026-11-10):
			 * - Entity + address + DPF: https://www.cloudflare.com/privacypolicy/
			 *   (effective November 4th, 2025): "Cloudflare, Inc., 101 Townsend St.,
			 *   San Francisco, CA 94107"; "Cloudflare complies with the EU-U.S. DPF,
			 *   the UK Extension to the EU-U.S. DPF," and certified adherence to the
			 *   Swiss-U.S. Data Privacy Framework Principles; end-user data "may
			 *   include but is not limited to IP addresses, traffic routing data".
			 * - Cookies: https://developers.cloudflare.com/fundamentals/reference/policies-compliances/cloudflare-cookies/
			 *   cookies are "strictly necessary to provide the services requested by
			 *   our customers"; __cf_bm "expires after 30 minutes of continuous
			 *   inactivity"; "Cloudflare does not track users from site to site or
			 *   from session to session"; Cloudflare "encourages our customers to
			 *   disclose the use of these cookies to their end users".
			 */
			'cloudflare' => array(
				'name'     => 'Cloudflare',
				'plugins'  => array( 'cloudflare' ),
				'html'     => array( '/cdn-cgi/' ),
				'headers'  => array(
					'server' => 'cloudflare',
					'cf-ray' => '',
				),
				'texts'    => array(
					'de' => array(
						'label'       => 'CDN und Sicherheit',
						'provider'    => 'Cloudflare, Inc., 101 Townsend St., San Francisco, CA 94107, USA',
						'purpose'     => 'Auslieferung der Website über ein weltweites Netzwerk (Content Delivery Network) sowie Schutz der Website vor Angriffen und missbräuchlichem Datenverkehr.',
						'data'        => 'Ihre IP-Adresse sowie technische Verbindungs- und Routingdaten Ihres Abrufs. Cloudflare kann technisch notwendige Cookies setzen, z. B. «__cf_bm» zur Erkennung von Bots (gültig bis 30 Minuten nach der letzten Aktivität); damit werden Sie nach Angaben von Cloudflare nicht über verschiedene Websites hinweg verfolgt.',
						'legal_basis' => 'Unser berechtigtes Interesse an einer sicheren, schnellen und stabilen Bereitstellung der Website (Art. 6 Abs. 1 lit. f DSGVO, soweit anwendbar). Für die Schweiz erfolgt die Bearbeitung gestützt auf das Datenschutzgesetz (DSG).',
						'transfer'    => 'Cloudflare, Inc. hat ihren Sitz in den USA. Eine Übermittlung in die USA ist möglich. Cloudflare ist unter dem EU-U.S. Data Privacy Framework, dessen Erweiterung für das Vereinigte Königreich und dem Swiss-U.S. Data Privacy Framework zertifiziert.',
						'privacy_url' => 'https://www.cloudflare.com/privacypolicy/',
					),
					'en' => array(
						'label'       => 'CDN and security',
						'provider'    => 'Cloudflare, Inc., 101 Townsend St., San Francisco, CA 94107, USA',
						'purpose'     => 'Delivering the website via a global network (content delivery network) and protecting the website against attacks and abusive traffic.',
						'data'        => 'Your IP address and technical connection and routing data of your request. Cloudflare may set strictly necessary cookies, e.g. "__cf_bm" for bot detection (valid until 30 minutes after your last activity); according to Cloudflare, these are not used to track you across websites.',
						'legal_basis' => 'Our legitimate interest in providing the website securely, quickly and reliably (Art. 6(1)(f) GDPR, where applicable). For Switzerland, processing is based on the Federal Act on Data Protection (FADP).',
						'transfer'    => 'Cloudflare, Inc. is based in the USA. A transfer to the USA is possible. Cloudflare is certified under the EU-U.S. Data Privacy Framework, its UK Extension and the Swiss-U.S. Data Privacy Framework.',
						'privacy_url' => 'https://www.cloudflare.com/privacypolicy/',
					),
					'fr' => array(
						'label'       => 'CDN et sécurité',
						'provider'    => 'Cloudflare, Inc., 101 Townsend St., San Francisco, CA 94107, États-Unis',
						'purpose'     => 'Diffusion du site via un réseau mondial (réseau de diffusion de contenu) et protection du site contre les attaques et le trafic abusif.',
						'data'        => 'Votre adresse IP ainsi que les données techniques de connexion et d’acheminement de votre demande. Cloudflare peut déposer des cookies strictement nécessaires, p. ex. « __cf_bm » pour la détection des robots (valable jusqu’à 30 minutes après votre dernière activité) ; selon Cloudflare, ils ne servent pas à vous suivre d’un site à l’autre.',
						'legal_basis' => 'Notre intérêt légitime à mettre le site à disposition de manière sûre, rapide et fiable (art. 6, par. 1, let. f RGPD, dans la mesure où il est applicable). Pour la Suisse, le traitement se fonde sur la loi fédérale sur la protection des données (LPD).',
						'transfer'    => 'Cloudflare, Inc. a son siège aux États-Unis. Un transfert vers les États-Unis est possible. Cloudflare est certifiée selon le EU-U.S. Data Privacy Framework, son extension au Royaume-Uni et le Swiss-U.S. Data Privacy Framework.',
						'privacy_url' => 'https://www.cloudflare.com/privacypolicy/',
					),
				),
			),
			/*
			 * WP Rocket - page cache / minification on the own web server.
			 * Sources (retrieved 2026-11-10):
			 * - Publisher + address: https://wp-rocket.me/fr/mentions-legales/
			 *   "la société SAS WP MEDIA" / "Siège social : 4 rue de la République,
			 *   69001 LYON, France".
			 * - No visitor data to third parties in the frontend: operator
			 *   statement in the assignment (2026-11-10) and the plugin's own
			 *   Headless-Chrome measurements on three WP Rocket sites (no request
			 *   to a WP Rocket/WP Media host). RocketCDN is a separate service and
			 *   NOT covered by this entry.
			 */
			'wp_rocket'  => array(
				'name'     => 'WP Rocket',
				'plugins'  => array( 'wp-rocket' ),
				'html'     => array(),
				'headers'  => array(),
				'texts'    => array(
					'de' => array(
						'label'       => 'Seiten-Cache',
						'provider'    => 'Software der SAS WP MEDIA, 4 rue de la République, 69001 Lyon, Frankreich',
						'purpose'     => 'Zwischenspeicherung (Caching) und Optimierung der Seiten auf unserem eigenen Webserver für kürzere Ladezeiten.',
						'data'        => 'Die Verarbeitung erfolgt auf unserem eigenen Webserver. Dabei werden keine personenbezogenen Daten von Ihnen an den Hersteller übermittelt.',
						'legal_basis' => 'Unser berechtigtes Interesse an kurzen Ladezeiten und einem stabilen Betrieb der Website (Art. 6 Abs. 1 lit. f DSGVO, soweit anwendbar). Für die Schweiz erfolgt die Bearbeitung gestützt auf das Datenschutzgesetz (DSG).',
						'transfer'    => 'Keine. Es findet keine Übermittlung an den Hersteller oder in ein Drittland statt.',
						'privacy_url' => 'https://wp-rocket.me/privacy-policy/',
					),
					'en' => array(
						'label'       => 'page cache',
						'provider'    => 'Software by SAS WP MEDIA, 4 rue de la République, 69001 Lyon, France',
						'purpose'     => 'Caching and optimising the pages on our own web server for shorter loading times.',
						'data'        => 'Processing takes place on our own web server. No personal data about you is transmitted to the publisher.',
						'legal_basis' => 'Our legitimate interest in short loading times and stable operation of the website (Art. 6(1)(f) GDPR, where applicable). For Switzerland, processing is based on the Federal Act on Data Protection (FADP).',
						'transfer'    => 'None. No data is transferred to the publisher or to a third country.',
						'privacy_url' => 'https://wp-rocket.me/privacy-policy/',
					),
					'fr' => array(
						'label'       => 'cache de pages',
						'provider'    => 'Logiciel de la SAS WP MEDIA, 4 rue de la République, 69001 Lyon, France',
						'purpose'     => 'Mise en cache et optimisation des pages sur notre propre serveur web afin de réduire les temps de chargement.',
						'data'        => 'Le traitement a lieu sur notre propre serveur web. Aucune donnée personnelle vous concernant n’est transmise à l’éditeur.',
						'legal_basis' => 'Notre intérêt légitime à des temps de chargement courts et à un fonctionnement stable du site (art. 6, par. 1, let. f RGPD, dans la mesure où il est applicable). Pour la Suisse, le traitement se fonde sur la loi fédérale sur la protection des données (LPD).',
						'transfer'    => 'Aucun. Aucune donnée n’est transférée à l’éditeur ni vers un pays tiers.',
						'privacy_url' => 'https://wp-rocket.me/privacy-policy/',
					),
				),
			),
		);

		$filtered = apply_filters( 'mcb_infra_service_registry', $registry );

		return is_array( $filtered ) ? $filtered : $registry;
	}

	/* --------------------------------------------------------------------- */
	/* Data model                                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * Stored list + detection state, normalised.
	 *
	 * @return array
	 */
	public static function get_data() {
		$data = get_option( self::OPTION, array() );
		$data = is_array( $data ) ? $data : array();

		return array(
			'entries'          => isset( $data['entries'] ) && is_array( $data['entries'] ) ? $data['entries'] : array(),
			'last_run'         => isset( $data['last_run'] ) && is_array( $data['last_run'] ) ? $data['last_run'] : array(),
			'new'              => isset( $data['new'] ) && is_array( $data['new'] ) ? array_values( $data['new'] ) : array(),
			'show_banner_line' => isset( $data['show_banner_line'] ) ? (bool) $data['show_banner_line'] : true,
		);
	}

	/**
	 * Persist the list (no autoload: only read in admin and when rendering).
	 *
	 * @param array $data Data.
	 * @return void
	 */
	private static function put_data( $data ) {
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, $data, '', 'no' );
			return;
		}

		update_option( self::OPTION, $data );
	}

	/**
	 * Today's date in site time.
	 *
	 * @return string Y-m-d
	 */
	private static function today() {
		return function_exists( 'current_time' ) ? current_time( 'Y-m-d' ) : gmdate( 'Y-m-d' );
	}

	/**
	 * Record the result of one detection run.
	 *
	 * Per scan type ('plugin' | 'page') an entry remembers the date of the last
	 * run in which that type saw it; an entry counts as "currently detected"
	 * when ANY type saw it in its latest run. This way a plugin scan never
	 * marks a header-only service (e.g. Cloudflare) as gone, and vice versa.
	 *
	 * Only 'auto' entries are ever created or updated here. Manual entries are
	 * never changed. Nothing is ever deleted.
	 *
	 * @param string $type    Scan type.
	 * @param array  $found   Map service key => list of signal strings.
	 * @return string[] Keys added in this run.
	 */
	public static function record_scan( $type, $found ) {
		$data     = self::get_data();
		$registry = self::registry();
		$today    = self::today();
		$added    = array();

		$data['last_run'][ $type ] = $today;

		foreach ( $found as $key => $signals ) {
			if ( ! isset( $registry[ $key ] ) ) {
				continue;
			}

			if ( ! isset( $data['entries'][ $key ] ) ) {
				$data['entries'][ $key ] = array(
					'source'         => 'auto',
					'confirmed'      => false,
					'first_detected' => $today,
					'seen'           => array(),
					'signals'        => array(),
					'texts'          => array(),
				);
				$added[]                 = $key;

				if ( ! in_array( $key, $data['new'], true ) ) {
					$data['new'][] = $key;
				}
			}

			if ( 'auto' !== $data['entries'][ $key ]['source'] ) {
				continue; // Never touch manual entries.
			}

			$data['entries'][ $key ]['seen'][ $type ]    = $today;
			$data['entries'][ $key ]['signals'][ $type ] = array_values( array_unique( array_map( 'strval', (array) $signals ) ) );
		}

		self::put_data( $data );

		return $added;
	}

	/**
	 * Whether an auto entry was seen in the latest run of any scan type.
	 *
	 * @param array $entry Entry.
	 * @param array $data  Data (last_run).
	 * @return bool
	 */
	public static function is_currently_detected( $entry, $data ) {
		if ( empty( $entry['seen'] ) || ! is_array( $entry['seen'] ) ) {
			return false;
		}

		foreach ( $entry['seen'] as $type => $date ) {
			if ( isset( $data['last_run'][ $type ] ) && $data['last_run'][ $type ] === $date ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Date an auto entry was last detected by any scan type.
	 *
	 * @param array $entry Entry.
	 * @return string Y-m-d or ''.
	 */
	public static function last_detected( $entry ) {
		$dates = isset( $entry['seen'] ) && is_array( $entry['seen'] ) ? array_values( $entry['seen'] ) : array();
		rsort( $dates );

		return isset( $dates[0] ) ? (string) $dates[0] : '';
	}

	/* --------------------------------------------------------------------- */
	/* Detection                                                              */
	/* --------------------------------------------------------------------- */

	/**
	 * Active plugin slugs (folder names), incl. network-activated plugins.
	 *
	 * @return string[]
	 */
	public static function active_plugin_slugs() {
		$plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$plugins = array_merge( $plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$slugs = array();

		foreach ( $plugins as $file ) {
			$parts   = explode( '/', (string) $file );
			$slugs[] = strtolower( $parts[0] );
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Detect registered services from the active plugins (no request).
	 *
	 * @param string[]|null $slugs Active slugs (null = read from WordPress).
	 * @return array Map key => signals.
	 */
	public static function detect_from_plugins( $slugs = null ) {
		$slugs = null === $slugs ? self::active_plugin_slugs() : $slugs;
		$found = array();

		foreach ( self::registry() as $key => $service ) {
			foreach ( (array) $service['plugins'] as $plugin ) {
				if ( in_array( strtolower( $plugin ), $slugs, true ) ) {
					$found[ $key ][] = 'plugin:' . $plugin;
				}
			}
		}

		return $found;
	}

	/**
	 * Detect registered services from the page the Privacy Check fetched
	 * (HTML + response headers) - no additional request.
	 *
	 * @param string $body    HTML.
	 * @param array  $headers Lower-cased header map name => value.
	 * @return array Map key => signals.
	 */
	public static function detect_from_page( $body, $headers ) {
		$lc      = strtolower( (string) $body );
		$headers = array_change_key_case( (array) $headers, CASE_LOWER );
		$found   = array();

		foreach ( self::registry() as $key => $service ) {
			foreach ( (array) $service['html'] as $needle ) {
				if ( '' !== $needle && false !== strpos( $lc, strtolower( $needle ) ) ) {
					$found[ $key ][] = 'html:' . $needle;
				}
			}

			foreach ( (array) $service['headers'] as $name => $contains ) {
				if ( ! isset( $headers[ $name ] ) ) {
					continue;
				}

				$value = strtolower( is_array( $headers[ $name ] ) ? implode( ',', $headers[ $name ] ) : (string) $headers[ $name ] );

				if ( '' === $contains || false !== strpos( $value, strtolower( $contains ) ) ) {
					$found[ $key ][] = 'header:' . $name;
				}
			}
		}

		return $found;
	}

	/**
	 * Google Fonts loaded from Google servers (a PROBLEM finding, not a
	 * disclosure case - recommendation: load fonts locally).
	 *
	 * @param string $body HTML.
	 * @return bool
	 */
	public static function external_google_fonts( $body ) {
		$lc = strtolower( (string) $body );

		return false !== strpos( $lc, 'fonts.googleapis.com' ) || false !== strpos( $lc, 'fonts.gstatic.com' );
	}

	/**
	 * Avada setting "Google Fonts Mode" set to CDN (warning).
	 *
	 * @return bool
	 */
	public static function avada_fonts_from_cdn() {
		$value = '';

		if ( class_exists( 'Macs_Cookie_Banner_Avada_Colors' ) ) {
			$value = Macs_Cookie_Banner_Avada_Colors::read_raw( 'gfonts_load_method' );
		} else {
			$options = get_option( 'fusion_options' );
			$value   = is_array( $options ) && isset( $options['gfonts_load_method'] ) ? (string) $options['gfonts_load_method'] : '';
		}

		return 'cdn' === strtolower( (string) $value );
	}

	/**
	 * Throttled plugin scan in wp-admin (cheap: reads active_plugins only).
	 *
	 * @return void
	 */
	public static function maybe_scan_plugins() {
		if ( ! current_user_can( 'manage_options' ) || get_transient( self::SCAN_TRANSIENT ) ) {
			return;
		}

		set_transient( self::SCAN_TRANSIENT, 1, 12 * HOUR_IN_SECONDS );
		self::record_scan( 'plugin', self::detect_from_plugins() );
	}

	/**
	 * Plugin (de)activated: rescan on the next admin request.
	 *
	 * @return void
	 */
	public static function reset_scan_throttle() {
		delete_transient( self::SCAN_TRANSIENT );
	}

	/* --------------------------------------------------------------------- */
	/* Texts                                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * Active frontend language (de/en/fr; others fall back to en).
	 *
	 * @return string
	 */
	public static function current_lang() {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$lang   = Macs_Cookie_Banner::extract_language_prefix( (string) $locale );

		return in_array( $lang, self::LANGS, true ) ? $lang : 'en';
	}

	/**
	 * Display name of an entry.
	 *
	 * @param string $key   Entry key.
	 * @param array  $entry Entry.
	 * @return string
	 */
	public static function entry_name( $key, $entry ) {
		if ( 'manual' === ( isset( $entry['source'] ) ? $entry['source'] : '' ) ) {
			return isset( $entry['name'] ) ? (string) $entry['name'] : $key;
		}

		$registry = self::registry();

		return isset( $registry[ $key ]['name'] ) ? (string) $registry[ $key ]['name'] : $key;
	}

	/**
	 * Shipped template text (registry) for a field.
	 *
	 * @param string $key   Service key.
	 * @param string $field Field.
	 * @param string $lang  Language.
	 * @return string
	 */
	public static function default_text( $key, $field, $lang ) {
		$registry = self::registry();

		if ( isset( $registry[ $key ]['texts'][ $lang ][ $field ] ) ) {
			return (string) $registry[ $key ]['texts'][ $lang ][ $field ];
		}

		if ( isset( $registry[ $key ]['texts']['en'][ $field ] ) ) {
			return (string) $registry[ $key ]['texts']['en'][ $field ];
		}

		return '';
	}

	/**
	 * Effective text: operator override (non-empty) wins over the template.
	 *
	 * @param string $key   Entry key.
	 * @param array  $entry Entry.
	 * @param string $field Field.
	 * @param string $lang  Language.
	 * @return string
	 */
	public static function text( $key, $entry, $field, $lang ) {
		if ( isset( $entry['texts'][ $lang ][ $field ] ) && '' !== trim( (string) $entry['texts'][ $lang ][ $field ] ) ) {
			return (string) $entry['texts'][ $lang ][ $field ];
		}

		return self::default_text( $key, $field, $lang );
	}

	/**
	 * Section labels per language.
	 *
	 * @param string $lang Language.
	 * @return array
	 */
	public static function labels( $lang ) {
		$labels = array(
			'de' => array(
				'provider'    => 'Anbieter',
				'purpose'     => 'Zweck',
				'data'        => 'Verarbeitete Daten',
				'legal_basis' => 'Rechtsgrundlage',
				'transfer'    => 'Übermittlung in Drittländer',
				'privacy_url' => 'Datenschutzerklärung des Anbieters',
				'banner_line' => 'Technisch notwendige Dienste (ohne Einwilligung):',
			),
			'en' => array(
				'provider'    => 'Provider',
				'purpose'     => 'Purpose',
				'data'        => 'Data processed',
				'legal_basis' => 'Legal basis',
				'transfer'    => 'Transfer to third countries',
				'privacy_url' => 'Provider’s privacy policy',
				'banner_line' => 'Technically necessary services (no consent required):',
			),
			'fr' => array(
				'provider'    => 'Fournisseur',
				'purpose'     => 'Finalité',
				'data'        => 'Données traitées',
				'legal_basis' => 'Base juridique',
				'transfer'    => 'Transfert vers des pays tiers',
				'privacy_url' => 'Politique de confidentialité du fournisseur',
				'banner_line' => 'Services techniquement nécessaires (sans consentement) :',
			),
		);

		return isset( $labels[ $lang ] ) ? $labels[ $lang ] : $labels['en'];
	}

	/**
	 * Entries the operator released for the frontend, in list order.
	 *
	 * @return array Map key => entry.
	 */
	public static function confirmed_entries() {
		$data = self::get_data();

		return array_filter(
			$data['entries'],
			static function ( $entry ) {
				return ! empty( $entry['confirmed'] );
			}
		);
	}

	/* --------------------------------------------------------------------- */
	/* Frontend output                                                        */
	/* --------------------------------------------------------------------- */

	/**
	 * Shortcode [lscc_privacy_services heading="h3"]: one section per
	 * confirmed service, in the active language.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts    = shortcode_atts( array( 'heading' => 'h3' ), (array) $atts, 'lscc_privacy_services' );
		$heading = in_array( $atts['heading'], array( 'h2', 'h3', 'h4', 'h5' ), true ) ? $atts['heading'] : 'h3';
		$lang    = self::current_lang();
		$labels  = self::labels( $lang );
		$out     = '';

		foreach ( self::confirmed_entries() as $key => $entry ) {
			$name = self::entry_name( $key, $entry );
			$out .= '<section class="lscc-privacy-service" data-lscc-service="' . esc_attr( $key ) . '">';
			$out .= '<' . $heading . '>' . esc_html( $name ) . '</' . $heading . '>';

			foreach ( array( 'provider', 'purpose', 'data', 'legal_basis', 'transfer' ) as $field ) {
				$value = self::text( $key, $entry, $field, $lang );

				if ( '' !== $value ) {
					$out .= '<p><strong>' . esc_html( $labels[ $field ] ) . ':</strong> ' . esc_html( $value ) . '</p>';
				}
			}

			$url = self::text( $key, $entry, 'privacy_url', $lang );

			if ( '' !== $url ) {
				$out .= '<p><strong>' . esc_html( $labels['privacy_url'] ) . ':</strong> <a href="' . esc_url( $url ) . '" rel="noopener" target="_blank">' . esc_html( $url ) . '</a></p>';
			}

			$out .= '</section>';
		}

		return '' === $out ? '' : '<div class="lscc-privacy-services">' . $out . '</div>';
	}

	/**
	 * Line for the banner details listing the confirmed services (no
	 * toggle - these services cannot be deselected). Empty when the option is
	 * off or nothing is confirmed, so existing banners stay unchanged.
	 *
	 * @return string HTML or ''.
	 */
	public static function banner_line_html() {
		$data = self::get_data();

		if ( empty( $data['show_banner_line'] ) ) {
			return '';
		}

		$lang  = self::current_lang();
		$items = array();

		foreach ( self::confirmed_entries() as $key => $entry ) {
			$label   = self::text( $key, $entry, 'label', $lang );
			$items[] = esc_html( self::entry_name( $key, $entry ) . ( '' !== $label ? ' (' . $label . ')' : '' ) );
		}

		if ( empty( $items ) ) {
			return '';
		}

		$labels = self::labels( $lang );

		return '<p class="lscc__infra" data-lscc-infra>' . esc_html( $labels['banner_line'] ) . ' ' . implode( ', ', $items ) . '</p>';
	}

	/* --------------------------------------------------------------------- */
	/* Privacy policy check                                                   */
	/* --------------------------------------------------------------------- */

	/**
	 * Resolve the privacy policy page (override URL, else WP privacy page).
	 *
	 * @return int Post ID or 0.
	 */
	public static function privacy_page_id() {
		$options  = Macs_Cookie_Banner::get_options();
		$override = isset( $options['privacy_url_override'] ) ? trim( (string) $options['privacy_url_override'] ) : '';

		if ( '' !== $override ) {
			$id = (int) url_to_postid( $override );

			if ( $id > 0 ) {
				return $id;
			}
		}

		return (int) get_option( 'wp_page_for_privacy_policy', 0 );
	}

	/**
	 * Disclosure status per entry: does the privacy page name the service, or
	 * contain the shortcode AND the entry is released for the frontend?
	 *
	 * @return array { page_id:int, page_title:string, rows: array }
	 */
	public static function disclosure_status() {
		$data    = self::get_data();
		$page_id = self::privacy_page_id();
		$post    = $page_id > 0 ? get_post( $page_id ) : null;
		$content = $post ? strtolower( (string) $post->post_content ) : '';
		$has_sc  = false !== strpos( $content, '[lscc_privacy_services' );
		$rows    = array();

		foreach ( $data['entries'] as $key => $entry ) {
			$name      = self::entry_name( $key, $entry );
			$by_name   = '' !== $content && false !== strpos( $content, strtolower( $name ) );
			$by_sc     = $has_sc && ! empty( $entry['confirmed'] );
			$rows[]    = array(
				'key'       => $key,
				'name'      => $name,
				'status'    => ( $by_name || $by_sc ) ? 'ok' : 'missing',
				'via'       => $by_name ? 'name' : ( $by_sc ? 'shortcode' : '' ),
				'sc_hidden' => $has_sc && empty( $entry['confirmed'] ) && ! $by_name,
				'detected'  => 'manual' === $entry['source'] ? null : self::is_currently_detected( $entry, $data ),
			);
		}

		return array(
			'page_id'    => $post ? (int) $post->ID : 0,
			'page_title' => $post ? (string) get_the_title( $post ) : '',
			'rows'       => $rows,
		);
	}

	/* --------------------------------------------------------------------- */
	/* Admin                                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * Admin notice for newly detected services (until the list was opened).
	 *
	 * @return void
	 */
	public static function render_new_services_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && false !== strpos( (string) $screen->id, self::PAGE_SLUG ) ) {
			return;
		}

		$data = self::get_data();

		if ( empty( $data['new'] ) ) {
			return;
		}

		$names = array();

		foreach ( $data['new'] as $key ) {
			if ( isset( $data['entries'][ $key ] ) ) {
				$names[] = self::entry_name( $key, $data['entries'][ $key ] );
			}
		}

		if ( empty( $names ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php echo esc_html__( 'Mac\'s Cookie Banner:', 'macs-cookie-banner' ); ?></strong>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: comma separated service names. */
						__( 'Neu erkannte Dienste ohne Einwilligung, die in der Datenschutzerklärung offengelegt werden müssen: %s.', 'macs-cookie-banner' ),
						implode( ', ', $names )
					)
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>"><?php echo esc_html__( 'Offenlegung prüfen', 'macs-cookie-banner' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Admin page "Offenlegung".
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Opening the list acknowledges the "new services" notice.
		self::record_scan( 'plugin', self::detect_from_plugins() );
		$data = self::get_data();

		if ( ! empty( $data['new'] ) ) {
			$data['new'] = array();
			self::put_data( $data );
		}

		$lang_names = array(
			'de' => __( 'Deutsch', 'macs-cookie-banner' ),
			'en' => __( 'Englisch', 'macs-cookie-banner' ),
			'fr' => __( 'Französisch', 'macs-cookie-banner' ),
		);
		$field_names = array(
			'label'       => __( 'Kurzbezeichnung (Bannerzeile)', 'macs-cookie-banner' ),
			'provider'    => __( 'Anbieter (Firma, Sitz)', 'macs-cookie-banner' ),
			'purpose'     => __( 'Zweck', 'macs-cookie-banner' ),
			'data'        => __( 'Verarbeitete Daten', 'macs-cookie-banner' ),
			'legal_basis' => __( 'Rechtsgrundlage', 'macs-cookie-banner' ),
			'transfer'    => __( 'Drittlandübermittlung', 'macs-cookie-banner' ),
			'privacy_url' => __( 'Link Datenschutzerklärung des Anbieters', 'macs-cookie-banner' ),
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag after redirect.
		$saved = isset( $_GET['mcb_saved'] );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Dienste ohne Einwilligung (offenlegungspflichtig)', 'macs-cookie-banner' ); ?></h1>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Gespeichert. Der Seiten-Cache wurde geleert.', 'macs-cookie-banner' ); ?></p></div>
			<?php endif; ?>

			<div class="notice notice-warning inline" style="margin:12px 0;"><p>
				<strong><?php echo esc_html__( 'Wichtig: Die Texte sind Vorlagen, keine Rechtsberatung.', 'macs-cookie-banner' ); ?></strong>
				<?php echo esc_html__( 'Bitte lassen Sie die Texte vor der Veröffentlichung rechtlich prüfen und passen Sie sie bei Bedarf an. Anbieterangaben wurden am 2026-11-10 aus den offiziellen Seiten der Anbieter übernommen und können sich ändern.', 'macs-cookie-banner' ); ?>
			</p></div>

			<p><?php echo esc_html__( 'Diese Dienste verarbeiten Daten Ihrer Besucher (mindestens die IP-Adresse) ohne Einwilligung, weil die Website ohne sie nicht ausgeliefert werden kann. Sie werden nie blockiert, müssen aber in der Datenschutzerklärung genannt werden. Erkannte Dienste werden automatisch eingetragen, erscheinen aber erst auf der Website, wenn Sie „Auf der Website anzeigen" aktivieren.', 'macs-cookie-banner' ); ?></p>
			<p><?php echo esc_html__( 'Ausgabe für die Datenschutzerklärung: Shortcode', 'macs-cookie-banner' ); ?> <code>[lscc_privacy_services]</code> <?php echo esc_html__( '(optional heading="h2" … "h5"). Sprache folgt der aktiven Seitensprache (Deutsch, Englisch, Französisch).', 'macs-cookie-banner' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mcb_save_infra_services">
				<?php wp_nonce_field( 'mcb_save_infra_services', 'mcb_infra_nonce' ); ?>

				<p>
					<label>
						<input type="checkbox" name="show_banner_line" value="1" <?php checked( $data['show_banner_line'] ); ?>>
						<?php echo esc_html__( 'Im Banner (Einstellungen) eine Zeile „Technisch notwendige Dienste" mit den angezeigten Diensten ausgeben', 'macs-cookie-banner' ); ?>
					</label>
				</p>

				<?php if ( empty( $data['entries'] ) ) : ?>
					<p><em><?php echo esc_html__( 'Noch keine Dienste erkannt oder eingetragen. Der Privacy Check prüft zusätzlich die Startseite (HTML und Server-Header, z. B. Cloudflare).', 'macs-cookie-banner' ); ?></em></p>
				<?php endif; ?>

				<?php foreach ( $data['entries'] as $key => $entry ) : ?>
					<?php
					$is_manual = 'manual' === $entry['source'];
					$detected  = $is_manual ? null : self::is_currently_detected( $entry, $data );
					$last      = $is_manual ? '' : self::last_detected( $entry );
					?>
					<div class="postbox" style="padding:8px 16px;margin-top:16px;">
						<h2 style="padding-left:0;"><?php echo esc_html( self::entry_name( $key, $entry ) ); ?></h2>
						<p>
							<?php if ( $is_manual ) : ?>
								<span class="dashicons dashicons-edit"></span> <?php echo esc_html__( 'Manuell eingetragen', 'macs-cookie-banner' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-search"></span>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: date. */
										__( 'Automatisch erkannt am %s', 'macs-cookie-banner' ),
										isset( $entry['first_detected'] ) ? $entry['first_detected'] : ''
									)
								);
								?>
								&middot;
								<?php if ( $detected ) : ?>
									<?php echo esc_html__( 'aktuell erkannt', 'macs-cookie-banner' ); ?>
								<?php else : ?>
									<strong><?php echo esc_html( sprintf( /* translators: %s: date. */ __( 'nicht mehr erkannt – zuletzt erkannt am %s', 'macs-cookie-banner' ), $last ) ); ?></strong>
								<?php endif; ?>
								<?php
								$signals = array();
								foreach ( (array) ( isset( $entry['signals'] ) ? $entry['signals'] : array() ) as $list ) {
									$signals = array_merge( $signals, (array) $list );
								}
								if ( $signals ) {
									echo ' &middot; <code>' . esc_html( implode( ', ', array_unique( $signals ) ) ) . '</code>';
								}
								?>
							<?php endif; ?>
						</p>
						<p>
							<label>
								<input type="checkbox" name="entries[<?php echo esc_attr( $key ); ?>][confirmed]" value="1" <?php checked( ! empty( $entry['confirmed'] ) ); ?>>
								<strong><?php echo esc_html__( 'Auf der Website anzeigen', 'macs-cookie-banner' ); ?></strong>
								<?php echo esc_html__( '(Shortcode und Bannerzeile)', 'macs-cookie-banner' ); ?>
							</label>
							<?php if ( $is_manual ) : ?>
								&nbsp; <label><input type="checkbox" name="entries[<?php echo esc_attr( $key ); ?>][delete]" value="1"> <?php echo esc_html__( 'Eintrag löschen', 'macs-cookie-banner' ); ?></label>
							<?php endif; ?>
						</p>
						<?php if ( $is_manual ) : ?>
							<p><label><?php echo esc_html__( 'Name', 'macs-cookie-banner' ); ?><br><input type="text" class="regular-text" name="entries[<?php echo esc_attr( $key ); ?>][name]" value="<?php echo esc_attr( isset( $entry['name'] ) ? $entry['name'] : '' ); ?>"></label></p>
						<?php endif; ?>
						<?php foreach ( self::LANGS as $lang ) : ?>
							<details style="margin:8px 0;">
								<summary><?php echo esc_html( sprintf( /* translators: %s: language. */ __( 'Texte %s', 'macs-cookie-banner' ), $lang_names[ $lang ] ) ); ?></summary>
								<?php if ( ! $is_manual ) : ?>
									<p class="description"><?php echo esc_html__( 'Leeres Feld = Vorlage (grau angezeigt). Ein eigener Text ersetzt die Vorlage.', 'macs-cookie-banner' ); ?></p>
								<?php endif; ?>
								<?php foreach ( self::FIELDS as $field ) : ?>
									<?php $value = isset( $entry['texts'][ $lang ][ $field ] ) ? (string) $entry['texts'][ $lang ][ $field ] : ''; ?>
									<p>
										<label><?php echo esc_html( $field_names[ $field ] ); ?><br>
											<?php if ( 'privacy_url' === $field || 'label' === $field ) : ?>
												<input type="text" class="large-text" name="entries[<?php echo esc_attr( $key ); ?>][texts][<?php echo esc_attr( $lang ); ?>][<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $is_manual ? '' : self::default_text( $key, $field, $lang ) ); ?>">
											<?php else : ?>
												<textarea class="large-text" rows="3" name="entries[<?php echo esc_attr( $key ); ?>][texts][<?php echo esc_attr( $lang ); ?>][<?php echo esc_attr( $field ); ?>]" placeholder="<?php echo esc_attr( $is_manual ? '' : self::default_text( $key, $field, $lang ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
											<?php endif; ?>
										</label>
									</p>
								<?php endforeach; ?>
							</details>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>

				<h2><?php echo esc_html__( 'Dienst manuell hinzufügen', 'macs-cookie-banner' ); ?></h2>
				<p><input type="text" class="regular-text" name="new_manual_name" placeholder="<?php echo esc_attr__( 'Name des Dienstes, z. B. Bunny CDN', 'macs-cookie-banner' ); ?>"> <span class="description"><?php echo esc_html__( 'Texte nach dem Speichern ergänzen.', 'macs-cookie-banner' ); ?></span></p>

				<?php submit_button( esc_html__( 'Speichern', 'macs-cookie-banner' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save handler (admin-post). Detection data (dates, signals) is never
	 * taken from the form; only operator decisions and texts are.
	 *
	 * @return void
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'macs-cookie-banner' ) );
		}

		check_admin_referer( 'mcb_save_infra_services', 'mcb_infra_nonce' );

		$data   = self::get_data();
		$posted = isset( $_POST['entries'] ) && is_array( $_POST['entries'] ) ? wp_unslash( $_POST['entries'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.

		$data['show_banner_line'] = ! empty( $_POST['show_banner_line'] );

		foreach ( $data['entries'] as $key => $entry ) {
			$in = isset( $posted[ $key ] ) && is_array( $posted[ $key ] ) ? $posted[ $key ] : array();

			if ( 'manual' === $entry['source'] && ! empty( $in['delete'] ) ) {
				unset( $data['entries'][ $key ] );
				continue;
			}

			$data['entries'][ $key ]['confirmed'] = ! empty( $in['confirmed'] );

			if ( 'manual' === $entry['source'] && isset( $in['name'] ) ) {
				$name = sanitize_text_field( (string) $in['name'] );

				if ( '' !== $name ) {
					$data['entries'][ $key ]['name'] = $name;
				}
			}

			$texts = array();

			foreach ( self::LANGS as $lang ) {
				foreach ( self::FIELDS as $field ) {
					$raw = isset( $in['texts'][ $lang ][ $field ] ) ? (string) $in['texts'][ $lang ][ $field ] : '';
					$val = 'privacy_url' === $field ? esc_url_raw( trim( $raw ) ) : ( 'label' === $field ? sanitize_text_field( $raw ) : sanitize_textarea_field( $raw ) );

					if ( '' !== trim( $val ) ) {
						$texts[ $lang ][ $field ] = $val;
					}
				}
			}

			$data['entries'][ $key ]['texts'] = $texts;
		}

		$new_name = isset( $_POST['new_manual_name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['new_manual_name'] ) ) : '';

		if ( '' !== $new_name ) {
			$data['entries'][ 'manual_' . substr( md5( $new_name . microtime() ), 0, 8 ) ] = array(
				'source'    => 'manual',
				'name'      => $new_name,
				'confirmed' => false,
				'texts'     => array(),
			);
		}

		self::put_data( $data );

		// The delivered HTML (shortcode output, banner line) may have changed.
		if ( class_exists( 'Macs_Cookie_Banner_Cache_Purge' ) ) {
			Macs_Cookie_Banner_Cache_Purge::request();
		}

		wp_safe_redirect( add_query_arg( 'mcb_saved', '1', admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) );
		exit;
	}
}
