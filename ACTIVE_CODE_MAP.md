# Active Code Map

Diese Karte beschreibt die aktiven Dateien, ihre Zustaendigkeiten und die wichtigsten Funktionen. Stand: Version 0.1.0.

> **Offizieller Avada-Stand (ab v0.5.13):** Farbquelle ist **ausschliesslich** Avada `primary_color` (ADR-30); der sichtbare Reopen-/Settings-Button nutzt die Primary Color in allen Presets (ADR-31); Auto-Sync ist opt-in (ADR-32) mit bei Aktivierung/Update **erzwungener** Entscheidung (ADR-33); Cache-Reset ueber die vorhandene Avada/Fusion-API (ADR-29). Arbeits-/Lieferregeln: ADR-34 und `MASTER_HANDBUCH.md` (Abschnitt „Verbindliche Learnings & Arbeitsregeln").

## Datei-Uebersicht

| Datei | Zweck |
|---|---|
| `macs-cookie-banner.php` | Plugin-Bootstrap, Konstanten, Hauptklasse, Banner-Markup, Asset-Enqueue, WPML/Polylang-Registrierung, Shortcode `[simple_cookie_settings]`. |
| `includes/admin-page.php` | Admin-Menue, Einstellungsseite, Speichern via `admin-post.php` mit Nonce-Pruefung, Render-Helpers fuer Text- und Color-Felder. Ab 0.5.1: Button „Jetzt synchronisieren" (vormals „Avada-Farben übernehmen") + Handler `import_avada_colors()` (admin-post `mcb_import_avada_colors`). **Ab 0.5.12 (ADR-32) Avada Auto-Sync:** Optionen `mcb_avada_autosync`/`mcb_avada_sync_decided`; `run_avada_sync()` (server-seitig, reuse `resolve_primary`/`map_to_banner`/`reset_caches`), `maybe_auto_sync()` (`admin_init`, nur wenn autosync on), Erstabfrage `maybe_render_sync_decision_notice()` (`admin_notices`) + Handler `save_avada_sync_decision()`/`save_avada_sync()`. Checkbox „Banner-Farben automatisch mit Avada synchronisieren" im Abschnitt „Avada-Integration". `import_avada_colors()` unverändert. **Ab 0.5.13 (ADR-33) erzwungene Entscheidung:** `register_activation_hook` → `Macs_Cookie_Banner::on_activate()` + Upgrade-Erkennung via `mcb_seen_version` in `maybe_force_avada_decision()` (`admin_init`, Prio 1) setzen `mcb_avada_decision_pending`; einmaliger Redirect auf die Einstellungsseite + persistenter, nicht schließbarer Prompt bis zur Entscheidung; danach nie wieder. |
| `includes/avada-colors.php` | Avada-Farbimport (ab 0.5.1), rein lesend, admin-only, **keine** Hooks. Klasse `Macs_Cookie_Banner_Avada_Colors`: ermittelt **eine** Markenfarbe via Prioritätskette `primary_color → accent_color → link_color → button_gradient_top_color` (erster gültiger Hit; `var(--awb-colorN)` über Palette aufgelöst) und mappt sie auf `primary_button_color` + `border_color`; `primary_text_color` = WCAG-Auto-Kontrast. Import nur auf bewussten Klick (ADR-27); kein Auto-Sync, kein Avada-Schreibzugriff, kein Versions-/Legacy-Pfad. Ab 0.5.9: `reset_caches()` leert nach dem Import den Avada/Fusion-Cache über Avadas eigene API (`fusion_reset_all_caches()` → `Fusion_Cache::reset_all_caches()`), damit das gecachte Inline-CSS die neue Farbe sofort zeigt (ADR-29). **Ab 0.5.10 (ADR-30): der Import bindet ausschließlich an `primary_color` via `resolve_primary()` (direktes `#hex`/`rgb()/rgba()`); kein Brand-Key-Chain, kein Palette-/`awb-colorN`-Matching. `get_brand_css_vars()` scannt nur `primary_color`. `get_brand_color()`/`resolve_color()`/`get_palette()`/`BRAND_KEYS` sind Legacy (vom Import nicht mehr aufgerufen).** |
| `includes/privacy-check.php` | Passive Admin-Seite, die einmal die Startseite via `wp_remote_get` abruft und gegen eine statische Mustertabelle prueft. |
| `includes/avada-inventory.php` | Passive, rein lesende Admin-Seite „Avada Inventar-Scan" (ab 0.1.8): zählt Video-/Map-/Embed-Typen in lokalen Inhalten zur Abschätzung der automatischen Abdeckung. Keine externen Requests, keine Schreibzugriffe, keine Inhaltsänderung. |
| `includes/avada-compat.php` | Render-Layer-Interception (ab 0.1.9): fängt Avadas `fusion_youtube` via `pre_do_shortcode_tag` ab und ersetzt es durch das LSCC-Platzhalter-Markup (Kategorie `external_media`). Nur Frontend, opt-in via `avada_youtube_block`. |
| `includes/meta-social-compat.php` | Meta-Social-Embed-SDK-Gating (ab 1.0.4, ADR-37): SRC-basiertes `script_loader_tag`-Gating der Facebook-/Instagram-Embed-SDKs (`connect.facebook.net …/sdk.js`, `instagram.com/embed.js`, `platform.instagram.com …/embeds.js`) → `type="text/plain" data-cookie-category="external_media"`. **`fbevents.js`/`fbq` (Meta Pixel) werden nie angefasst** (strikte Trennung). Reaktivierung nach `external_media`-Consent über die bestehende `banner.js`-Mechanik (kein Frontend-Code). Nur Frontend, opt-in via `meta_social_block` (Safe-by-Default: recommended EIN für Neuinstallation, Bestand unverändert). Methoden `block_social_sdk()`, `is_social_sdk()`. |
| `includes/avada-code-compat.php` | Avada-Code-Block-Maps-Gating (ab 1.0.2, ADR-35): fängt `fusion_code` via `pre_do_shortcode_tag` ab und ersetzt **ausschliesslich** den Fall „genau ein Google-Maps-Embed-iframe (`google.com/maps/embed`)" durch das bestehende LSCC-Platzhalter-Markup (`Service_Components::render_google_map()`, `external_media`). `decode_fusion_code()` (base64 oder raw HTML), `extract_maps_embed()` (ab 1.0.3; genau ein iframe, kein `<script>`, kein Rest-Inhalt → sonst Passthrough; liefert `{src,width,height,title}` für Geometrie-Erhalt; Helfer `tag_attr()`), `content_has_code_block_map()` (base64-aware Scanner-Helfer). Nur Frontend, opt-in via `avada_code_maps_block` (Default AUS). Kein globaler iframe-Filter, kein Observer, kein `the_content`-Rewrite, kein neuer Frontend-Code. |
| `includes/avada-maps-compat.php` | Avada-Google-Maps-Gating (ab 0.3.2): fängt `fusion_map` via `pre_do_shortcode_tag` ab und ersetzt es durch das LSCC-Platzhalter-Markup mit Google-Maps-**Embed**-URL (Adresse → `output=embed`); blockiert die Maps-JS-API (`maps.googleapis.com/maps/api/js`) SRC-basiert via `script_loader_tag`. Nur Frontend, opt-in via `avada_maps_block` (Default AUS). |
| `includes/yotu-compat.php` | YOTU-Consent-Gating (ab 0.2.2): koppelt das Frontend-Script des Plugins „Yotuwp – Easy YouTube Embed" (`yotu-script` + Inline `-extra`/`-after`) via `script_loader_tag`/`wp_inline_script_attributes` an die LSCC-Script-Blockade (`external_media`) und neutralisiert die `i.ytimg.com`-Thumbnails im Shortcode-Output (`do_shortcode_tag`) durch Umbenennen von `data-orig-src` → `data-lscc-orig-src`, plus Consent-Hinweis über der Galerie. Nur Frontend, opt-in via `yotu_consent_gating` (Default AUS). |
| `includes/consent-codes.php` | Consent-Code-Manager (ab 0.3.0): zentrale Verwaltung von Tracking-/Marketing-Snippets (GA4/GTM/Meta Pixel/Hotjar/…). Option `lscc_consent_codes`; gibt Snippets in Head/Body-Anfang/Footer als LSCC-geblockte Scripts aus (Script-Tag-Transform + `<noscript>`-Strip), Aktivierung über bestehende `banner.js`-Mechanik. Scannerfähiges Datenmodell, Vendor-Detektion, versioniertes Export/Import-Envelope. |
| `includes/service-components.php` | Shortcodes `[lscc_youtube]`, `[lscc_vimeo]`, `[lscc_google_map]` mit Placeholder-Markup. |
| `assets/js/banner.js` | Frontend-Logik: Consent-Speicherung, Banner-Steuerung, Script-Aktivierung, Media-Sync. |
| `assets/css/banner.css` | Styles fuer Banner, Reopen-Button, Settings-Button und Media-Komponenten. Ab 0.5.11 (ADR-31): die Basisregeln `.lscc-reopen` und `.lscc-settings-button` fuellen mit `var(--lscc-primary)` (Border `--lscc-primary`, Text `--lscc-primary-text`) → der sichtbare Button traegt die importierte Primary Color in **allen** Presets (vorher Classic: `--lscc-bg` bzw. `--lscc-secondary`). |
| `languages/macs-cookie-banner.pot` | i18n-Template. Ab v0.2.1 vollständig aus den realen Quelltext-Callsites generiert (158 msgids; Audit). |
| `languages/macs-cookie-banner-*.po` | Vollständiger Katalog für `de_CH`, `en_US`, `fr_FR`, `it_IT`, `tr_TR`, `hu_HU`. Ab v0.2.1 befüllt: **frontend-/besucherseitige** Strings (Kategorie-Labels/-Beschreibungen, Rechtslinks, Service-Komponenten-Texte) in allen sechs Sprachen; Admin-only-Strings bleiben deutsche Quelle (Operator-Sprache). |
| `languages/macs-cookie-banner-*.mo` | Ab v0.2.1 kompiliert (sechs Locales). Nur Einträge mit echter Übersetzung; fehlende fallen auf die deutsche Quelle zurück. Notwendig, damit `__()`/`esc_html__()` der aktiven WPML-Sprache folgen. |

## `macs-cookie-banner.php`

**Konstanten:**

- `MCB_VERSION` (ab 0.1.5: `'0.1.5'`) — Plugin-Versionsstring
- `MCB_CONSENT_VERSION` (ab 0.1.5: `2`) — Schema-Version des gespeicherten Consents; getrennt von `MCB_VERSION`, wird nur bei strukturellen Änderungen erhöht und invalidiert dann clientseitige Consents älterer Schema-Versionen
- `MCB_PLUGIN_FILE`, `MCB_PLUGIN_DIR`, `MCB_PLUGIN_URL`
- `MCB_DEBUG` (Default `false`, kann via `wp-config.php` ueberschrieben werden)

**Hauptklasse `Macs_Cookie_Banner`:**

- `OPTION_NAME = 'lscc_options'`
- `COOKIE_NAME = 'lscc_consent'`
- `init()` registriert Hooks und laedt Subklassen
- `load_textdomain()` — Hook `plugins_loaded`
- `register_wpml_strings()` — Hook `init`, ruft `do_action( 'wpml_register_single_string', ... )` und `pll_register_string()` fuer alle Banner-Texte
- `get_default_options()` — Defaults fuer Texte und Hex-Farben; **alle sieben Text-Defaults** (`banner_title`, `banner_text`, `accept_all_text`, `necessary_only_text`, `settings_text`, `save_settings_text`, `reopen_text`) kommen ab v0.2.1 aus `get_neutral_text()` (vorher nur `banner_text`). Damit folgen die Defaults automatisch der aktiven WPML-/Polylang-Sprache, ohne `__()`-/`.mo`-Lookup und ohne deutschen Hardcode-Default.
- `get_neutral_text( $key, $locale = null )` (ab v0.2.1) — liest die aktive Locale via `determine_locale()` (Fallback `get_locale()`), extrahiert den Sprachpräfix und liefert den passenden Default-Text aus `get_default_text_table()`; Fallback auf Englisch, dann leerer String. Ersetzt `resolve_neutral_banner_text_for_locale()`.
- `get_neutral_banner_text()` — bleibt als dünner Backwards-Compat-Wrapper erhalten und delegiert an `get_neutral_text( 'banner_text' )`.
- `extract_language_prefix( $locale )` — robuster Helper, akzeptiert `de_CH`, `de-CH`, `de_DE`, `de_AT`, `en_GB`, `pt_BR` und ähnliche Varianten und liefert den 2- bis 3-Buchstaben-Sprachcode in Kleinbuchstaben (unverändert)
- `get_default_text_table()` (ab v0.2.1, ersetzt `get_neutral_banner_text_table()`) — neutrale Default-UI-Texte je Sprachpräfix (`de`, `en`, `fr`, `it`, `tr`, `hu`); pro Sprache eine Map aller sieben Text-Keys. UTF-8 mit echten Umlauten/diakritischen Zeichen, Schweizer Schreibweise (kein ß). WPML-/Polylang-String-Translation bleibt als Override vorrangig.
- `get_bool_option_keys()`, `get_int_option_keys()`, `get_float_option_keys()`, `get_url_option_keys()`, `get_enum_option_keys()` — Schlüssellisten für neue Optionstypen ab v0.1.4 (Overlay/Blur/Position/Legal-Links)
- **Safe-by-Default (ab 1.0.3, ADR-36):** `get_recommended_defaults()` = sichere Werte (Quelle für Fresh-Install-Seeding **und** Restore; `avada_code_maps_block`/`avada_youtube_block`/`show_legal_links`=true, `youtube_remote_thumbnails`=false, `consent_lifetime_days`=180; `avada_maps_block`/`yotu_consent_gating` bewusst **nicht** im Set). `get_baseline_fallback()` = konservative Struktur-Defaults; **`sanitize_options()` nutzt für fehlende Keys den Baseline-Fallback** → Updates aktivieren neue Schutzoptionen nie still. `get_restore_recommended_keys()` = die 5 vom Restore betroffenen Keys. `on_activate()` seedet `get_recommended_defaults()` via `add_option` **nur** bei fehlendem Options-Eintrag (Fresh-Install); Bestand unberührt.
- `get_css_variables()` — schreibt zusätzlich `--lscc-overlay-bg`, `--lscc-blur`, `--lscc-reopen-ox`, `--lscc-reopen-oy` in den Inline-Style
- `hex_with_opacity_to_rgba( $hex, $opacity )` — privater Helper für Overlay-Hintergrund (`rgba(...)`)
- `get_privacy_url( $options )` — manueller Override hat Vorrang, sonst `get_privacy_policy_url()` aus WordPress-Core
- `get_imprint_url( $options )` — manueller Override hat Vorrang, sonst Transient `lscc_detected_imprint_url`. Frontend liest ausschliesslich den Cache.
- `refresh_imprint_detection()` — fuehrt die Slug-/Titel-Erkennung aus und speichert das Ergebnis als Transient (TTL `DAY_IN_SECONDS`)
- `maybe_refresh_imprint_detection()` — Hook auf `admin_init`; loest die Erkennung nur im Admin und nur bei leerem Cache aus
- `scan_imprint_pages()` — privat, sucht via `get_page_by_path()` und einzelnen `WP_Query`-Lookups; keine externen Requests, keine DOM-Scans

**Neue Optionen ab 0.1.4:**

| Schluessel | Typ | Default | Sanitization |
|---|---|---|---|
| `overlay_enabled` | bool | `true` | Checkbox |
| `overlay_color` | hex | `#000000` | `sanitize_hex_color` |
| `overlay_opacity` | float | `0.45` | clamp 0.0–1.0 |
| `blur_enabled` | bool | `true` | Checkbox |
| `blur_strength` | int | `4` | clamp 0–20 |
| `reopen_position` | enum | `bottom-right` | `bottom-right` / `bottom-left` / `top-right` / `top-left` / `hidden` (ab 0.5.0) |
| `reopen_offset_x` | int | `24` | clamp 0–200 |
| `reopen_offset_y` | int | `24` | clamp 0–200 |
| `show_legal_links` | bool | `true` | Checkbox |
| `privacy_url_override` | url | `''` | `esc_url_raw` |
| `imprint_url_override` | url | `''` | `esc_url_raw` |

**Render-Verhalten:**

- Overlay-Element wird nur ausgegeben, wenn `overlay_enabled` true ist. Initial `hidden`. JS toggelt den Zustand parallel zum Banner.
- Reopen-Button erhält das Attribut `data-position` und positioniert sich rein per CSS-Klassen und CSS-Variablen. Ab 0.5.0: Wert `hidden` → `banner.js::setBannerVisible()` hält den Button dauerhaft versteckt (Button bleibt im DOM, da `initBanner()` ihn voraussetzt); Widerruf dann nur über `[simple_cookie_settings]`.
- Ab 0.5.2: Enum-Option `design_preset` (`classic`/`modern`/`premium`, Default `classic`). Render hängt die Klasse `lscc--preset-<wert>` an `#lscc-root`, `.lscc-overlay`, `.lscc-reopen` und `.lscc-settings-button`. Additive, class-gescopte CSS-Blöcke in `banner.css` ändern nur Form/Radius/Schatten/Glow/Abstände/Button-Stil — **keine** Farben (die kommen weiter aus den `--lscc-*`-Variablen). Größere Paddings nur ab `min-width: 761px`. Glass-Preset bewusst verschoben.
- Legal-Links erscheinen unten in `.lscc__content` als `.lscc__legal`-Block, nur wenn `show_legal_links` true ist UND mindestens eine der beiden URLs aufgeloest werden konnte.
- `get_text_option_keys()` / `get_color_option_keys()` — Schluessellisten
- `sanitize_options()` — zentrale Sanitization mit `sanitize_text_field` und `sanitize_hex_color`
- `get_options()` — gesetzte Optionen + Defaults
- `get_translated_option( $key, $label )` — ab 0.5.3: folgt der **aktiven Locale**, wenn der gespeicherte Wert leer ist oder einem mitgelieferten Default-Text entspricht (`is_shipped_default_text()` → `get_neutral_text()`); echter Operator-Text und WPML/Polylang behalten Vorrang. Behebt den Sprach-Mix der 7 option-basierten Texte (Titel/Beschreibung/Buttons/Reopen).
- `is_shipped_default_text( $key, $value )` (ab 0.5.3) — prüft, ob `$value` einem Default aus `get_default_text_table()` (irgendeiner Sprache) entspricht.
- `enqueue_assets()` — registriert `mcb-banner` Style und Script und uebergibt Settings via `wp_localize_script` an `mcbSettings`. Ab 0.5.4 zusätzlich `locale` (`determine_locale()`) für die locale-aware Banner-Anzeige (ADR-28).
- **Locale-aware Display (ab 0.5.4, verfeinert 0.5.5, ADR-28):** `banner.js` führt ab 0.5.5 eine **Liste gesehener Locales** im separaten localStorage-Key `mcb_consent_locales_seen` (JSON-Array; `getSeenLocales()/addSeenLocale()`). Das Banner erscheint **pro Sprache nur einmal**: ist `currentLocale` nicht in der Liste → einmal zeigen; `saveAndClose()` nimmt die Locale auf. Rückwechsel auf bereits gesehene Sprachen → kein Re-Show. Migration des 0.5.4-Einzelkeys `mcb_consent_locale` via `getLegacyLocale()`. Consent/Cookie/`lscc_consent`/`CONSENT_VERSION` unverändert; bestehender Consent ohne Eintrag → aktuelle Locale still aufgenommen.
- **Temporärer Reopen-Dismiss (ab 0.5.4):** `<span data-lscc-reopen-dismiss>` (X) im Reopen-Button; JS setzt In-Memory-`reopenDismissed` (kein Storage) → Button bis zum Reload versteckt. Unabhängig von der `hidden`-Position. `setBannerVisible()` berücksichtigt `reopenDismissed`.
- `get_css_variables( $options )` — baut das `--lscc-*` Style-String fuer Inline-Styles
- `render_settings_shortcode()` — gibt den Reopen-Button als HTML zurueck (Shortcode `[simple_cookie_settings]`)
- `render_banner()` — gibt das Banner-DOM und den festen Reopen-Button im Footer aus

**Hooks (in `init()`):**

- `plugins_loaded` → `load_textdomain`
- `init` → `register_wpml_strings`
- `wp_enqueue_scripts` → `enqueue_assets`
- `wp_footer` (Prio 10) → `render_banner`
- Shortcode `simple_cookie_settings` → `render_settings_shortcode`

## `includes/admin-page.php`

**Klasse `Macs_Cookie_Banner_Admin`:**

- `init()` — registriert `admin_menu` und `admin_post_lscc_save_settings`
- `add_settings_page()` — Top-Level-Menue `macs-cookie-banner` plus Submenues `Einstellungen`, `Privacy Check` und (ab 0.1.8) `Avada Inventar-Scan`. `admin-page.php` lädt `privacy-check.php` und `avada-inventory.php` via `require_once`.
- `save_settings()` — prueft `current_user_can( 'manage_options' )` und `wp_verify_nonce( ..., 'lscc_save_settings' )`, sanitisiert und speichert, dann `wp_safe_redirect`
- `restore_recommended()` (ab 1.0.3, ADR-36) — admin-post `mcb_restore_recommended` (Nonce `mcb_restore_recommended`, `manage_options`); überschreibt **nur** `get_restore_recommended_keys()` mit `get_recommended_defaults()`, dann `sanitize_options`+`update_option`+Redirect (`mcb_restored=1`). Button oben auf der Einstellungsseite mit JS-`confirm()`. Lässt Texte/Farben/Design/URLs/Reopen/Consent-Codes/Avada-Sync unberührt.
- `render_settings_page()` — rendert Formular mit Text- und Color-Feldern
- `render_text_field()`, `render_color_field()` — Tabellenzeilen-Renderer

**Sicherheitspruefungen:**

- `ABSPATH`-Guard ganz oben
- `current_user_can( 'manage_options' )` in `save_settings` und `render_settings_page`
- `wp_nonce_field( 'lscc_save_settings', 'lscc_settings_nonce' )` im Formular
- `wp_verify_nonce(...)` beim Speichern
- Color-Inputs mit `pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"`

## `includes/privacy-check.php`

**Klasse `Macs_Cookie_Banner_Privacy_Check`:**

- `render_page()` — Admin-Seite mit drei Sektionen (ab 0.3.1): `Drittanbieter-Oberfläche` (URL-Formular + Status-Tabelle), `Muster-Schnellprüfung` (alte Mustertabelle) und `Content Scan`. Prueft `current_user_can` und Nonces (`lscc_surface_scan`, `lscc_content_scan`). Holt die zu prüfende URL **einmal** und speist beide Sektionen.
- `resolve_scan_url()` (ab 0.3.1) — liefert die zu prüfende URL: eigene **gleicher-Host**-URL (POST + Nonce) oder Startseite. SSRF-Schutz: Fremd-Hosts → Fallback Startseite + `host_mismatch`-Notice.
- `fetch_html( $url )` (ab 0.3.1) — ein `wp_remote_get` (Timeout 5 s, max. 500 KB, eigener UA); liefert `{ ok, body, error_problem, error_reco }`. Ersetzt das frühere `run_check()`.
- `detect_services( $body )` — sucht im Lowercase-HTML nach statischen Mustern (Muster-Schnellprüfung)
- **Surface-Scan (ab 0.3.1; ab 1.0.1 zusätzlich Google Ads + Mailchimp im Dienst-Katalog):** `detect_surface( $body )` baut pro Dienst eine Zeile; `classify_scripts()` (Vendor via `Consent_Codes::match_vendor()`, gegated via `tag_is_gated()` = `type="text/plain"`+`data-cookie-category`), `classify_embeds()` (rohes `<iframe>`=ungegatet, `data-lscc-service`-Platzhalter=gegated), `detect_fonts()`, `registered_vendors()` (Cross-Ref aus `lscc_consent_codes`), `get_surface_services()` (Dienst-Katalog), `surface_status()` (5-Status-Modell: nicht_gefunden/verwaltet/teilweise/ungegatet/nicht_pruefbar), `get_surface_status_label()`, `render_surface_section()`. Google Fonts als Sonderzeile („lokal hosten; Consent ersetzt kein Local Hosting").
- `get_checks()` — Mustertabelle der Muster-Schnellprüfung (siehe unten)
- `get_status_label()` — uebersetzte Status-Labels (`Kritisch`, `Wichtig`, `Info`)
- `render_content_scan_section( $results )` — rendert Hinweistext, Trigger-Form (`lscc_content_scan` Nonce, `lscc_run_content_scan` Submit) und ggf. die Ergebnis-Tabelle
- `render_content_scan_results( $results )` — Tabellen-Renderer fuer Treffer (Risiko, Dienst, Inhaltstyp, Titel + Edit-Link, Domain, Empfehlung)
- `run_content_scan()` — `WP_Query` ueber `post`, `page` und public CPTs, `post_status=publish`, `posts_per_page=200`, sucht in `post_content` per `strpos` nach den definierten Needles. Keine externen Requests.
- `get_scannable_post_types()` — `post`, `page` plus alle nicht-builtin public Custom Post Types
- `get_post_type_label( $post_type )` — Mensch-lesbare Bezeichnung (`Beitrag`, `Seite`, sonst `singular_name` des Objekts)
- `get_content_scan_patterns()` — Liste der Needle-Gruppen pro Dienst inklusive Risiko-Level und Empfehlungstext

**Content-Scan-Dienste:**

| Risiko | Dienst | Needles |
|---|---|---|
| Wichtig | YouTube | `youtube-nocookie.com`, `youtube.com`, `youtu.be` |
| Wichtig | Vimeo | `player.vimeo.com`, `vimeo.com` |
| Wichtig | Google Maps | `google.com/maps`, `maps.google.` |
| Kritisch | Google Fonts | `fonts.googleapis.com`, `fonts.gstatic.com` |
| Kritisch | Google Tag Manager | `googletagmanager.com` |
| Kritisch | Google Analytics | `google-analytics.com` |
| Kritisch | Facebook / Meta | `connect.facebook.net`, `facebook.net` |

**Geprueft werden aktuell:**

| Status | Muster | Hinweis |
|---|---|---|
| Kritisch | `fonts.googleapis.com`, `fonts.gstatic.com` | Externe Google Fonts |
| Kritisch | `google-analytics.com`, `googletagmanager.com` | GA / GTM |
| Kritisch | `googleadservices.com`, `googleads.g.doubleclick.net`, `google_conversion_id` | Google Ads (Conversion / Remarketing) — ab 1.0.1 |
| Kritisch | `facebook.net`, `connect.facebook.net` | Facebook / Meta |
| Kritisch | `chimpstatic.com`, `list-manage.com` | Mailchimp — ab 1.0.1 |
| Wichtig | `youtube.com`, `youtu.be` | YouTube-Inhalte |
| Wichtig | `vimeo.com` | Vimeo-Inhalte |

Der Check fuehrt keinen Crawl durch, prueft nur diese eine URL und veraendert nichts.

## `includes/avada-inventory.php`

**Klasse `Macs_Cookie_Banner_Avada_Inventory`** (ab 0.1.8):

- Konstante `SCAN_LIMIT = 500` — max. geprüfte Inhalte pro Lauf.
- `render_page()` — Admin-Seite; prüft `current_user_can( 'manage_options' )`, Button-getriggert über `lscc_run_avada_inventory` POST mit Nonce `lscc_avada_inventory`. Rein lesend.
- `run_inventory_scan()` — `WP_Query` über die Inventar-Post-Typen, `post_status=publish`, `posts_per_page=SCAN_LIMIT`, akkumuliert Zählungen; meldet `scanned`, `total`, `truncated`. Keine externen Requests.
- `analyze_content( $content, &$counts, $home_host )` — zählt pro Inhalt: `[fusion_youtube`, `[fusion_vimeo`, `[fusion_map` (abzüglich `[fusion_map_marker`), Background-Videos via `video_url="..."` (YouTube/Vimeo vs. self-hosted `video_mp4/webm/ogv`), rohe `<iframe src=...>` (Same-Origin-Klassifizierung gegen `home_url`-Host), `[fusion_code]…[/fusion_code]` inkl. base64-Tiefpass auf Drittanbieter-Embed, oEmbed (nackte YouTube/Vimeo-URL als eigene Zeile) sowie Diagnostik-Rohtreffer.
- `get_inventory_post_types()` — `post`, `page`, public CPTs plus vorhandene Avada-CPTs (`fusion_tb_section`, `fusion_tb_layout`, `fusion_template`, `slide`, `fusion_element`).
- `render_results()` — rendert Verteilungstabelle, Abfangbarkeits-Matrix, KPIs `Abdeckung_min`/`Abdeckung_max`, Top-Sonderfälle und Diagnostik.
- `percent( $part, $total )` — gerundete Prozentangabe mit Divide-by-zero-Schutz.

Element-Klassifizierung (überschneidungsfrei) als Entscheidungsbasis; Diagnostik-Rohtreffer separat. Automatisch abfangbar = `fusion_youtube + fusion_vimeo + oEmbed`; bedingt = `fusion_map`; manuell = Background-Video(YT/Vimeo) + `fusion_code`(mit Embed) + rohe Drittanbieter-iframes.

## `includes/avada-compat.php`

**Klasse `Macs_Cookie_Banner_Avada_Compat`** (ab 0.1.9):

- `init()` — registriert nur im Frontend (`! is_admin()`) und nur bei aktivierter Option `avada_youtube_block` den Filter `pre_do_shortcode_tag` (Prio 10, 4 Argumente).
- `intercept( $output, $tag, $attr, $m )` — bei `$tag === 'fusion_youtube'`: extrahiert die Video-ID, gibt `Macs_Cookie_Banner_Service_Components::render_youtube( array( 'id' => $video_id ) )` als Ersatz-Markup zurück (Kurzschluss der Avada-Ausgabe). Bei nicht parsebarer ID oder leerem Markup wird `$output` (false) zurückgegeben → Avada rendert normal weiter.
- `extract_video_id( $raw )` — akzeptiert rohe IDs und YouTube-URLs (`youtu.be/ID`, `watch?v=ID`, `/embed/ID`, `/v/ID`); liefert `''` bei nicht parsebarem Wert.

Reine Server-Interception: kein DOM-Hijacking, kein MutationObserver, kein Scanner, keine Inhaltsänderung. Das iframe wird erst nach `external_media`-Consent über die bestehende `banner.js`-Mechanik gebaut. Wiederverwendung von `Service_Components::render_youtube()` → identisches Platzhalter-/Consent-Verhalten wie `[lscc_youtube]`. Steuerung über `avada_youtube_block` (bool, Default `true`).

## `includes/yotu-compat.php`

**Klasse `Macs_Cookie_Banner_Yotu_Compat`** (ab 0.2.2):

- Konstante `SCRIPT_HANDLE = 'yotu-script'` — Frontend-Script-Handle des Plugins „Yotuwp – Easy YouTube Embed".
- `init()` — registriert nur im Frontend (`! is_admin()`) und nur bei aktivierter Option `yotu_consent_gating` die Filter `script_loader_tag` (Prio 10/3), `wp_inline_script_attributes` (10/1) und `do_shortcode_tag` (10/4).
- `block_script_tag( $tag, $handle, $src )` — bei `$handle === 'yotu-script'`: setzt das `<script src>`-Tag auf `type="text/plain"` + `data-cookie-category="external_media"` + `data-cookie-type="text/javascript"` (Phase 1).
- `block_inline_attributes( $attributes )` — bei Inline-IDs, die mit `yotu-script-js` beginnen (`-extra` Localize, `-after` Init): gleiche Blocking-Attribute. WP ≥ 5.7. (Phase 1, Inline-Teile.)
- `convert_tag_to_blocked( $tag )` — privater Helfer, entfernt ein bestehendes `type`-Attribut und injiziert die LSCC-Blocking-Attribute in das `<script`-Opening-Tag.
- `gate_shortcode_output( $output, $tag, $attr, $m )` — nur wenn `$output` `yotu-video-thumb` enthält: benennt `data-orig-src` → `data-lscc-orig-src` (verhindert den `i.ytimg.com`-Lazy-Load-Abruf des Themes vor Consent) und stellt `render_consent_notice()` voran (Phase 2).
- `render_consent_notice()` — privat; baut den `lscc-yotu-consent`-Hinweis (`data-lscc-gated-notice`) mit `data-lscc-accept-media`-Button. Wird von `banner.js::bindMediaComponents()` gebunden; nach Consent von `restoreExternalMediaThumbnails()` versteckt.

Vor `external_media`-Consent: kein youtube.com, kein youtube-nocookie.com, kein `iframe_api`, kein `www-widgetapi`, kein `i.ytimg.com`. Nach Consent baut `banner.js` die Thumbnails wieder auf und aktiviert die Yotu-Scripts in korrekter Reihenfolge → Galerie funktioniert normal. Kein DOM-Hijacking/Observer/Scanner, keine `post_content`-Änderung, vollständig reversibel (`yotu_consent_gating` = bool, Default `false`). Coverage-Grenze: greift bei per Shortcode gerenderten Galerien; reine Block-/Widget-Einbindungen sind separat zu prüfen.

## `includes/avada-maps-compat.php`

**Klasse `Macs_Cookie_Banner_Avada_Maps_Compat`** (ab 0.3.2, Variante 3A-i):

- Konstante `MAPS_API_NEEDLE = 'maps.googleapis.com/maps/api/js'`.
- `init()` — nur Frontend + nur bei `avada_maps_block`: Filter `pre_do_shortcode_tag` (10/4) und `script_loader_tag` (10/3).
- `intercept( $output, $tag, $attr, $m )` — bei `fusion_map`: erste Adresse via `extract_address()` (aus `address`-Att bzw. erstem `[fusion_map_marker]` in `$m[5]`) → `Service_Components::build_maps_embed_url()` → `Service_Components::render_google_map( ['url'=>…] )` als Ersatz. Fallback: nicht parsebare Adresse → `$output` (Avada rendert).
- `extract_address( $atts, $content )` — primäre Adresse (mehrzeilig/Pipe → erste).
- `block_maps_api( $tag, $handle, $src )` — SRC-basiert (handle-agnostisch): enthält `$src` die Maps-API → Tag auf `type="text/plain" data-cookie-category="external_media" data-cookie-type="text/javascript"` umschreiben.

Reine Server-Interception + Script-Gating; **kein** Avada-Reinit, kein DOM-Hijack, kein Observer, kein Frontend-Code. Nach Consent baut `createMediaIframe` das Embed-iframe. Trade-off: Embed-Karte statt Avada-JS-Karte; Multi-Marker → primäre Adresse. Avada-Privacy-Maps darf **nicht** parallel aktiv sein (eine Consent-Schicht).

## `includes/consent-codes.php`

**Klasse `Macs_Cookie_Banner_Codes`** (ab 0.3.0):

- Konstanten: `OPTION_NAME = 'lscc_consent_codes'`, `NONCE_ACTION = 'lscc_save_consent_codes'`, `PAGE_SLUG`, `EXPORT_VERSION = 1`.
- `init()` — im Admin: `admin_post_lscc_save_consent_codes` → `save()`, `admin_post_lscc_export_consent_codes` → `export()`, `admin_enqueue_scripts` → `enqueue_admin()` (lädt `assets/js/admin-consent-codes.js` nur auf der Manager-Seite). Im Frontend: `wp_head` (99), `wp_body_open`, `wp_footer` (5) → `render_location()`.
- **Datenmodell (scannerfähig):** Option = Array von Einträgen `{ id, label, vendor, source, category, location, enabled, order, code }`. `category` ∈ {necessary, statistics, marketing, external_media}; `location` ∈ {head, body_open, footer}; `vendor` wird beim Speichern via `detect_vendor()` automatisch gesetzt (ga4/gtm/google_ads/meta_pixel/mailchimp/hotjar/recaptcha/calendly/custom — ab 1.0.1 google_ads + mailchimp); `source` = Provenienz (manual/import).
- `get_codes()` / `normalize_entry()` / `sanitize_enum()` — gelesene, validierte Einträge.
- `detect_vendor( $code )` / `match_vendor( $text )` — Mustererkennung des Drittanbieters (für Scanner/Badge). **Ab 1.0.1:** `google_ads` (AW-…/`google_conversion_id`/`googleadservices.com`/`googleads.g.doubleclick.net`) wird **vor** GA4 geprüft (Ads nutzt ebenfalls `gtag()`; sonst GA4-Fehlklassifikation); `mailchimp` (`chimpstatic.com`/`list-manage.com`/`mailchimp`). **Ab 1.0.4:** `facebook_embed` (`facebook.com/plugins/`, `connect.facebook.net`+`sdk.js`, `fbAsyncInit`, `FB.init`, `fb-page/-post/-video`) und `instagram_embed` (`instagram.com/embed`, `platform.instagram.com`, `instagram-media`, `instgrm`) werden **vor** `meta_pixel` geprüft → strikte Trennung Social↔Pixel; `meta_pixel` (`fbevents.js`/`fbq`) unverändert. Default-Kategorie beider Social-Vendoren = `external_media`.
- `vendor_default_category( $vendor )` (ab 1.0.1) — Auto-Vorschlag der Consent-Kategorie pro Vendor (ga4/gtm→statistics, meta_pixel/google_ads/mailchimp→marketing). Wird in `build_entries()` **nur** für neu hinzugefügte Rows (leere `id`) eines erkannten Vendors angewandt, solange der generische Default `statistics` gesetzt ist; bestehende Snippets bleiben unverändert.
- `render_location( $loc )` — gibt aktive Snippets der Position in `order`-Reihenfolge aus (roh = bereits geblockt).
- `transform_snippet( $code, $category )` — entfernt `<noscript>…</noscript>` und schreibt jeden `<script …>` auf `type="text/plain" data-cookie-category=<cat> data-cookie-type=<orig>` um (verallgemeinert `yotu-compat::convert_tag_to_blocked`).
- `render_page()` / `render_row()` / `render_template_row()` — Admin-Repeater-UI; `category_labels()`/`location_labels()`.
- `save()` — `manage_options` + Nonce; Roh-`code` nur mit `unfiltered_html` (sonst verworfen + Notice, Zähler `rejected`); `build_entries()` baut/validiert; `update_option`; `redirect()`.
- `export()` — versioniertes JSON-Envelope `{ lscc_export_version, plugin_version, type:'lscc-config', data:{ consent_codes:[…] } }` als Download (erweiterbar auf weitere Konfig-Teile). `parse_import()` akzeptiert Envelope oder nackte Liste.

**Frontend-JS unverändert:** Reuse der sequenziellen `activateBlockedScripts()` (v0.2.2) — kein neuer Frontend-Code. Admin-Repeater-JS (`assets/js/admin-consent-codes.js`) ist admin-only, dependency-frei (add/remove/↑/↓), mit No-JS-Fallback (bestehende Zeilen bleiben editierbar/speicherbar).

## `includes/service-components.php`

**Klasse `Macs_Cookie_Banner_Service_Components`:**

- `init()` — registriert die drei Shortcodes
- `render_youtube( $atts )` — `youtube-nocookie.com`-Embed; Attribute `id` (ab 0.2.0 auch YouTube-URLs), `title` (ab 0.2.0, a11y-Titel) und `thumbnail_id` (lokales Mediathek-Bild)
- `extract_youtube_id( $raw )` (public, ab 0.2.0) — Video-ID aus roher ID oder YouTube-URL (`youtu.be/`, `watch?v=`, `/embed/`, `/v/`); rohe ID via `sanitize_media_id`. Wird auch von `avada-compat.php` genutzt.
- `resolve_youtube_thumbnail_html( $thumbnail_id, $video_id )` (ab 0.2.0) — lokales `thumbnail_id` hat Vorrang; sonst nur bei aktivierter Option `youtube_remote_thumbnails` ein `i.ytimg.com`-Bild; sonst leer (Platzhalter).
- `render_vimeo( $atts )` — `player.vimeo.com`-Embed; akzeptiert ab 0.1.7 zusätzlich `thumbnail_id` (lokales Mediathek-Bild, gleiche Mechanik wie YouTube)
- `render_facebook( $atts )` / `render_instagram( $atts )` (ab 1.0.4) — kontrollierte Facebook-/Instagram-Social-Embeds über `render_component()` (Kategorie `external_media`); Host-Allowlist `sanitize_facebook_url()`/`sanitize_instagram_url()` (`*.facebook.com`/`*.instagram.com`); optionale `width`/`height`/`title` (Geometrie wie 1.0.3). Shortcodes `[lscc_facebook]`/`[lscc_instagram]`.
- `render_google_map( $atts )` — Google-Maps-Embed mit Host-Allowlist. Ab 1.0.3 optionale Attribute `width`/`height`/`title` (Geometrie-Erhalt für den Avada-Code-Block-Fall); leer → Standard-16:9 (bestehende Nutzung unverändert). Helfer `sanitize_css_dimension()` + `build_geometry_style()` legen `width/max-width/height/min-height:0/aspect-ratio:auto` als Inline-Style auf `.lscc-media`; `render_component()` hat dafür den 6. Param `$dimensions`.
- `sanitize_media_id()` — beschraenkt IDs auf `[A-Za-z0-9_-]`
- `sanitize_google_maps_url()` — akzeptiert nur Hosts unter `google.*` oder `maps.google.com` und Pfade mit `/maps`
- `get_local_thumbnail_html( $thumbnail_id )` (ab 0.1.6) — validiert `thumbnail_id` via `absint()`, prüft `get_post_type() === 'attachment'` und `wp_attachment_is_image()`, und gibt das `<img class="lscc-media__thumb" loading="lazy">`-Markup von `wp_get_attachment_image( $id, 'large' )` zurück. Liefert `''` bei ungültiger/nicht-Bild-ID → stiller Fallback auf den bisherigen Platzhalter. **Keine** externen Bildquellen, **kein** Auto-Fetch aus der Video-ID.
- `render_component()` — baut das Placeholder-Markup mit `data-lscc-media`, `data-lscc-category="external_media"`, `data-lscc-src`, `data-lscc-title`, `data-lscc-service` und einem Accept-Button. Optionaler 5. Parameter `$thumbnail_html`: ist er nicht leer, erhält der Container zusätzlich die Klasse `lscc-media--has-thumb`, das `<img>` wird als Hintergrundebene gerendert und ein zentrierter `.lscc-media__play`-Button (ebenfalls `data-lscc-accept-media`) ergänzt. Hinweistext und Accept-Button bleiben sichtbar.

Komponenten zeigen vor Zustimmung nur den Platzhalter und werden vom JS erst nach Zustimmung zur Kategorie `external_media` zum iframe aufgebaut.

## Consent-Flow

Der Consent-Flow ist clientseitig und versioniert:

1. `enqueue_assets` uebergibt `cookieName`, `storageKey`, `consentVersion = '1'`, `debug`-Flag und Kategorie-Liste an `window.lsccSettings`.
2. `render_banner` schreibt das Banner-DOM (Root `#lscc-root` mit `hidden`) und den Reopen-Button in den Footer.
3. `banner.js` startet bei `DOMContentLoaded`:
   - liest `localStorage` und Cookie via `getStoredConsent`,
   - prueft Version und Struktur via `parseStoredConsent` / `isValidConsent`,
   - synchronisiert die Settings-Checkboxen aus dem gespeicherten Consent via `updateInputs( getStoredConsent() )` (ab 0.2.3 **beim Laden**, unabhaengig von der Banner-Sichtbarkeit → gespeicherter Consent = alleinige Quelle der Wahrheit, robust gegen Browser-Formular-Wiederherstellung) und spiegelt den aktiven Zustand auf die Schnellbuttons via `updateQuickButtons()` (ab 0.2.4),
   - aktiviert blockierte Skripte (`activateBlockedScripts`),
   - synchronisiert Medienkomponenten (`syncMediaComponents`),
   - zeigt das Banner nur, wenn kein gueltiger Consent existiert.
4. Bei Auswahl ruft das Banner `saveAndClose` → `writeConsent` (schreibt `localStorage` + Cookie mit `Max-Age=180 Tage`, `SameSite=Lax`, `Secure` bei HTTPS), danach `updateInputs( consent )` + `updateQuickButtons()` (ab 0.2.3/0.2.4, halten Checkboxen und Schnellbuttons nach „Alle akzeptieren" / „Nur notwendige" / „Auswahl speichern" synchron), dann `activateBlockedScripts` + `syncMediaComponents`. Die vier Checkboxen tragen `autocomplete="off"` (ab 0.2.3).

`updateQuickButtons()` (ab 0.2.4, reine Darstellung): liest `getStoredConsent()` und setzt an `[data-lscc-accept-all]` / `[data-lscc-necessary]` die Klassen `is-active`/`is-inactive` + `aria-pressed`. Zustände: kein gespeicherter Consent → beide **neutral** (gleichwertige Prominenz); alle optionalen Kategorien `true` → „Alle akzeptieren" aktiv; alle `false` → „Nur notwendige" aktiv; gemischt → beide inaktiv. Helfer `setQuickButtonState()`. CSS: `.lscc__button.is-active` (Ring + „✓"), `.lscc__button.is-inactive` (`opacity:0.6`). **Kein** Schreibzugriff auf Consent/Storage.
5. `writeConsent` feuert ein `lscc:consentChanged`-CustomEvent auf `window`.

Kategorien (fix): `necessary`, `statistics`, `marketing`, `external_media`. `necessary` ist immer `true`.

## Script-Aktivierung

`activateBlockedScripts` ersetzt jedes Element `script[type="text/plain"][data-cookie-category]`, dessen Kategorie zugestimmt ist, durch ein neues `<script>`:

- `data-cookie-type` wird in `type` umgewandelt (`text/javascript`, `application/javascript`, `module`, sonst `text/javascript`).
- Es werden nur sichere Attribute kopiert. `on*`-Handler, `type`, `data-cookie-category` und `data-cookie-type` werden ausgelassen (`shouldCopyScriptAttribute`).
- Ab 0.2.2 erfolgt die Aktivierung **sequenziell** (`activateNext`): externe Scripts werden mit `async=false` eingefügt und das nächste (ggf. inline) Script erst nach deren `load`/`error` aktiviert. Das garantiert die Ausführungsreihenfolge bei Abhängigkeiten (z. B. Yotu: `-extra` → `frontend.min.js` → `-after`). Der Element-Aufbau steckt im Helfer `buildActiveScript()`.
- Ab 0.2.2 ruft `activateBlockedScripts()` zuerst `restoreExternalMediaThumbnails()` auf: bei vorliegendem `external_media`-Consent werden `img[data-lscc-orig-src]` wiederhergestellt (`src` + `data-orig-src` gesetzt, `data-lscc-orig-src` entfernt, `lazyload`-Klasse entfernt) und `[data-lscc-gated-notice]`-Hinweise versteckt. Damit lädt das ytimg-Thumbnail erst nach Consent, vor der Yotu-Script-Aktivierung.

Normale `<script>`-Tags ohne diese Markierung bleiben unangetastet.

## Privacy Check

Die Admin-Seite `Privacy Check` ruft `Macs_Cookie_Banner_Privacy_Check::render_page()` auf. Sie ist passiv: sie prueft genau eine URL, schreibt keine Inhalte um und blockiert nichts automatisch.

## Service-Komponenten

- `[lscc_youtube id="VIDEO_ID"]` — youtube-nocookie-Embed.
- `[lscc_youtube id="VIDEO_ID" thumbnail_id="123"]` — wie oben, zeigt aber vor Consent das lokale Mediathek-Bild mit ID `123` plus Play-Button. Nur numerische Attachment-IDs, keine externen Bildquellen.
- `[lscc_youtube id="https://youtu.be/VIDEO_ID" title="..."]` (ab 0.2.0) — `id` akzeptiert auch URLs; `title` optionaler a11y-Titel. Play-Button erscheint immer; Autostart nach Play-Klick. Optionales `i.ytimg.com`-Thumbnail nur bei aktivierter Option `youtube_remote_thumbnails`.
- `[lscc_vimeo id="VIDEO_ID"]` — Vimeo-Player-Embed.
- `[lscc_vimeo id="VIDEO_ID" thumbnail_id="123"]` — wie oben, zeigt aber vor Consent das lokale Mediathek-Bild mit ID `123` plus Play-Button (ab 0.1.7, gleiche Mechanik wie YouTube). Nur numerische Attachment-IDs, keine externen Bildquellen.
- `[lscc_google_map url="https://www.google.com/maps/embed?..."]` — Google-Maps-Embed mit Host-Pruefung.

Jede Komponente rendert vor Zustimmung nur einen Platzhalter mit Hinweistext und Akzeptier-Button. Der Klick auf `data-lscc-accept-media` setzt die Kategorie `external_media = true` und laedt die Komponente sofort.

## JS-Verantwortung

`assets/js/banner.js` ist als IIFE im Strict-Mode organisiert. Verantwortlichkeiten:

- Consent-Validierung und Normalisierung (`isValidConsent`, `parseStoredConsent`, `normalizeConsent`)
- Persistenz in `localStorage` und Cookie (`writeConsent`, `readCookie`, `getStoredConsent`, `hasStoredConsent`)
- Banner-Steuerung (`setBannerVisible`, `syncSettingsTriggers`, `focusSettings`, `bindSettingsTriggers`)
- Consent-Aktionen (`createConsent`, `collectConsent`, `updateInputs`, `saveAndClose`)
- Script-Aktivierung (`activateBlockedScripts`, `shouldCopyScriptAttribute`, `normalizeScriptType`)
- Media-Komponenten (`createMediaIframe`, `setMediaComponentLoaded`, `syncMediaComponents`, `bindMediaComponents`, `acceptExternalMedia`). Ab 0.2.0: `bindMediaComponents` markiert die Komponente mit `data-lscc-autoplay-now`, wenn der Play-Button (`data-lscc-autoplay`) geklickt wurde; `createMediaIframe` hängt dann `autoplay=1` an (nur YouTube/Vimeo).
- Event `lscc:consentChanged`
- Debug-Logging hinter `MCB_DEBUG`-Flag

Kein externer Code, keine Libraries, kein jQuery.

## CSS-Verantwortung

`assets/css/banner.css` definiert:

- CSS-Variablen `--lscc-bg`, `--lscc-text`, `--lscc-primary`, `--lscc-primary-text`, `--lscc-secondary`, `--lscc-border` auf den Container-Klassen
- Layout des fixierten Banner-Panels (`.lscc`, `.lscc__panel`, `.lscc__content`)
- Buttons (`.lscc__button`, `.lscc__button--primary/--secondary/--ghost`, `.lscc-reopen`, `.lscc-settings-button`) inkl. `:focus-visible`-Outline
- Settings-Block (`.lscc__settings`, `.lscc__categories`, `.lscc__category`)
- Media-Komponenten (`.lscc-media`, `.lscc-media__placeholder`, `.lscc-media__notice`, `.lscc-media__button`, `.lscc-media__iframe`) mit `aspect-ratio: 16 / 9`
- Thumbnail-Ebene ab 0.1.6: `.lscc-media__thumb` (absolut, `object-fit: cover`), `.lscc-media--has-thumb .lscc-media__placeholder` (halbtransparenter Scrim für Lesbarkeit) und `.lscc-media__play` (grosser runder Play-Button mit CSS-Dreieck, `:focus-visible`, keine Animationen)
- Responsive Breakpoints bei `760px` und `420px`

Es gibt keine Animationen oder Effekt-Spielereien. Farben werden ausschliesslich ueber die CSS-Variablen gesteuert, deren Werte aus den Admin-Optionen via Inline-Style gesetzt werden.
