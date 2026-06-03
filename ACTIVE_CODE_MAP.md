# Active Code Map

Diese Karte beschreibt die aktiven Dateien, ihre Zustaendigkeiten und die wichtigsten Funktionen. Stand: Version 0.1.0.

## Datei-Uebersicht

| Datei | Zweck |
|---|---|
| `light-swiss-cookie-consent.php` | Plugin-Bootstrap, Konstanten, Hauptklasse, Banner-Markup, Asset-Enqueue, WPML/Polylang-Registrierung, Shortcode `[simple_cookie_settings]`. |
| `includes/admin-page.php` | Admin-Menue, Einstellungsseite, Speichern via `admin-post.php` mit Nonce-Pruefung, Render-Helpers fuer Text- und Color-Felder. |
| `includes/privacy-check.php` | Passive Admin-Seite, die einmal die Startseite via `wp_remote_get` abruft und gegen eine statische Mustertabelle prueft. |
| `includes/avada-inventory.php` | Passive, rein lesende Admin-Seite „Avada Inventar-Scan" (ab 0.1.8): zählt Video-/Map-/Embed-Typen in lokalen Inhalten zur Abschätzung der automatischen Abdeckung. Keine externen Requests, keine Schreibzugriffe, keine Inhaltsänderung. |
| `includes/avada-compat.php` | Render-Layer-Interception (ab 0.1.9): fängt Avadas `fusion_youtube` via `pre_do_shortcode_tag` ab und ersetzt es durch das LSCC-Platzhalter-Markup (Kategorie `external_media`). Nur Frontend, opt-in via `avada_youtube_block`. |
| `includes/service-components.php` | Shortcodes `[lscc_youtube]`, `[lscc_vimeo]`, `[lscc_google_map]` mit Placeholder-Markup. |
| `assets/js/banner.js` | Frontend-Logik: Consent-Speicherung, Banner-Steuerung, Script-Aktivierung, Media-Sync. |
| `assets/css/banner.css` | Styles fuer Banner, Reopen-Button, Settings-Button und Media-Komponenten. |
| `languages/light-swiss-cookie-consent.pot` | i18n-Template. |
| `languages/light-swiss-cookie-consent-*.po` | Locale-Skelette fuer `de_CH`, `en_US`, `fr_FR`, `it_IT`, `tr_TR`, `hu_HU`. |

## `light-swiss-cookie-consent.php`

**Konstanten:**

- `LSCC_VERSION` (ab 0.1.5: `'0.1.5'`) — Plugin-Versionsstring
- `LSCC_CONSENT_VERSION` (ab 0.1.5: `2`) — Schema-Version des gespeicherten Consents; getrennt von `LSCC_VERSION`, wird nur bei strukturellen Änderungen erhöht und invalidiert dann clientseitige Consents älterer Schema-Versionen
- `LSCC_PLUGIN_FILE`, `LSCC_PLUGIN_DIR`, `LSCC_PLUGIN_URL`
- `LSCC_DEBUG` (Default `false`, kann via `wp-config.php` ueberschrieben werden)

**Hauptklasse `Light_Swiss_Cookie_Consent`:**

- `OPTION_NAME = 'lscc_options'`
- `COOKIE_NAME = 'lscc_consent'`
- `init()` registriert Hooks und laedt Subklassen
- `load_textdomain()` — Hook `plugins_loaded`
- `register_wpml_strings()` — Hook `init`, ruft `do_action( 'wpml_register_single_string', ... )` und `pll_register_string()` fuer alle Banner-Texte
- `get_default_options()` — Defaults fuer Texte und Hex-Farben; der `banner_text`-Default kommt aus `get_neutral_banner_text()`
- `get_neutral_banner_text()` — liest aktuellen Locale via `determine_locale()` (Fallback `get_locale()`) und delegiert an `resolve_neutral_banner_text_for_locale()`
- `resolve_neutral_banner_text_for_locale( $locale )` — extrahiert Sprachpräfix und liest passenden Eintrag aus `get_neutral_banner_text_table()`; fällt bei unbekannten Sprachen auf Englisch zurück
- `extract_language_prefix( $locale )` — robuster Helper, akzeptiert `de_CH`, `de-CH`, `de_DE`, `de_AT`, `en_GB`, `pt_BR` und ähnliche Varianten und liefert den 2- bis 3-Buchstaben-Sprachcode in Kleinbuchstaben
- `get_neutral_banner_text_table()` — neutrale Default-Banner-Texte je Sprachpräfix (`de`, `en`, `fr`, `it`, `tr`, `hu`); UTF-8 mit echten Umlauten und diakritischen Zeichen
- `get_bool_option_keys()`, `get_int_option_keys()`, `get_float_option_keys()`, `get_url_option_keys()`, `get_enum_option_keys()` — Schlüssellisten für neue Optionstypen ab v0.1.4 (Overlay/Blur/Position/Legal-Links)
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
| `reopen_position` | enum | `bottom-right` | `bottom-right` / `bottom-left` / `top-right` / `top-left` |
| `reopen_offset_x` | int | `24` | clamp 0–200 |
| `reopen_offset_y` | int | `24` | clamp 0–200 |
| `show_legal_links` | bool | `true` | Checkbox |
| `privacy_url_override` | url | `''` | `esc_url_raw` |
| `imprint_url_override` | url | `''` | `esc_url_raw` |

**Render-Verhalten:**

- Overlay-Element wird nur ausgegeben, wenn `overlay_enabled` true ist. Initial `hidden`. JS toggelt den Zustand parallel zum Banner.
- Reopen-Button erhält das Attribut `data-position` und positioniert sich rein per CSS-Klassen und CSS-Variablen.
- Legal-Links erscheinen unten in `.lscc__content` als `.lscc__legal`-Block, nur wenn `show_legal_links` true ist UND mindestens eine der beiden URLs aufgeloest werden konnte.
- `get_text_option_keys()` / `get_color_option_keys()` — Schluessellisten
- `sanitize_options()` — zentrale Sanitization mit `sanitize_text_field` und `sanitize_hex_color`
- `get_options()` — gesetzte Optionen + Defaults
- `get_translated_option( $key, $label )` — bevorzugt WPML, dann Polylang, sonst Klartext
- `enqueue_assets()` — registriert `lscc-banner` Style und Script und uebergibt Settings via `wp_localize_script` an `lsccSettings`
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

**Klasse `Light_Swiss_Cookie_Consent_Admin`:**

- `init()` — registriert `admin_menu` und `admin_post_lscc_save_settings`
- `add_settings_page()` — Top-Level-Menue `light-swiss-cookie-consent` plus Submenues `Einstellungen`, `Privacy Check` und (ab 0.1.8) `Avada Inventar-Scan`. `admin-page.php` lädt `privacy-check.php` und `avada-inventory.php` via `require_once`.
- `save_settings()` — prueft `current_user_can( 'manage_options' )` und `wp_verify_nonce( ..., 'lscc_save_settings' )`, sanitisiert und speichert, dann `wp_safe_redirect`
- `render_settings_page()` — rendert Formular mit Text- und Color-Feldern
- `render_text_field()`, `render_color_field()` — Tabellenzeilen-Renderer

**Sicherheitspruefungen:**

- `ABSPATH`-Guard ganz oben
- `current_user_can( 'manage_options' )` in `save_settings` und `render_settings_page`
- `wp_nonce_field( 'lscc_save_settings', 'lscc_settings_nonce' )` im Formular
- `wp_verify_nonce(...)` beim Speichern
- Color-Inputs mit `pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"`

## `includes/privacy-check.php`

**Klasse `Light_Swiss_Cookie_Consent_Privacy_Check`:**

- `render_page()` — Admin-Seite mit zwei Sektionen: `Startseiten-Pruefung` (Tabelle) und `Content Scan` (Button + optionale Ergebnis-Tabelle). Prueft `current_user_can` und (bei POST) den Content-Scan-Nonce.
- `run_check( $source_url )` — einmaliger `wp_remote_get` auf `home_url('/')` mit Timeout 5 s, max. 500 KB Response, eigener User-Agent
- `detect_services( $body )` — sucht im Lowercase-HTML nach statischen Mustern
- `get_checks()` — Mustertabelle der Startseiten-Pruefung (siehe unten)
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
| Kritisch | `facebook.net`, `connect.facebook.net` | Facebook / Meta |
| Wichtig | `youtube.com`, `youtu.be` | YouTube-Inhalte |
| Wichtig | `vimeo.com` | Vimeo-Inhalte |

Der Check fuehrt keinen Crawl durch, prueft nur diese eine URL und veraendert nichts.

## `includes/avada-inventory.php`

**Klasse `Light_Swiss_Cookie_Consent_Avada_Inventory`** (ab 0.1.8):

- Konstante `SCAN_LIMIT = 500` — max. geprüfte Inhalte pro Lauf.
- `render_page()` — Admin-Seite; prüft `current_user_can( 'manage_options' )`, Button-getriggert über `lscc_run_avada_inventory` POST mit Nonce `lscc_avada_inventory`. Rein lesend.
- `run_inventory_scan()` — `WP_Query` über die Inventar-Post-Typen, `post_status=publish`, `posts_per_page=SCAN_LIMIT`, akkumuliert Zählungen; meldet `scanned`, `total`, `truncated`. Keine externen Requests.
- `analyze_content( $content, &$counts, $home_host )` — zählt pro Inhalt: `[fusion_youtube`, `[fusion_vimeo`, `[fusion_map` (abzüglich `[fusion_map_marker`), Background-Videos via `video_url="..."` (YouTube/Vimeo vs. self-hosted `video_mp4/webm/ogv`), rohe `<iframe src=...>` (Same-Origin-Klassifizierung gegen `home_url`-Host), `[fusion_code]…[/fusion_code]` inkl. base64-Tiefpass auf Drittanbieter-Embed, oEmbed (nackte YouTube/Vimeo-URL als eigene Zeile) sowie Diagnostik-Rohtreffer.
- `get_inventory_post_types()` — `post`, `page`, public CPTs plus vorhandene Avada-CPTs (`fusion_tb_section`, `fusion_tb_layout`, `fusion_template`, `slide`, `fusion_element`).
- `render_results()` — rendert Verteilungstabelle, Abfangbarkeits-Matrix, KPIs `Abdeckung_min`/`Abdeckung_max`, Top-Sonderfälle und Diagnostik.
- `percent( $part, $total )` — gerundete Prozentangabe mit Divide-by-zero-Schutz.

Element-Klassifizierung (überschneidungsfrei) als Entscheidungsbasis; Diagnostik-Rohtreffer separat. Automatisch abfangbar = `fusion_youtube + fusion_vimeo + oEmbed`; bedingt = `fusion_map`; manuell = Background-Video(YT/Vimeo) + `fusion_code`(mit Embed) + rohe Drittanbieter-iframes.

## `includes/avada-compat.php`

**Klasse `Light_Swiss_Cookie_Consent_Avada_Compat`** (ab 0.1.9):

- `init()` — registriert nur im Frontend (`! is_admin()`) und nur bei aktivierter Option `avada_youtube_block` den Filter `pre_do_shortcode_tag` (Prio 10, 4 Argumente).
- `intercept( $output, $tag, $attr, $m )` — bei `$tag === 'fusion_youtube'`: extrahiert die Video-ID, gibt `Light_Swiss_Cookie_Consent_Service_Components::render_youtube( array( 'id' => $video_id ) )` als Ersatz-Markup zurück (Kurzschluss der Avada-Ausgabe). Bei nicht parsebarer ID oder leerem Markup wird `$output` (false) zurückgegeben → Avada rendert normal weiter.
- `extract_video_id( $raw )` — akzeptiert rohe IDs und YouTube-URLs (`youtu.be/ID`, `watch?v=ID`, `/embed/ID`, `/v/ID`); liefert `''` bei nicht parsebarem Wert.

Reine Server-Interception: kein DOM-Hijacking, kein MutationObserver, kein Scanner, keine Inhaltsänderung. Das iframe wird erst nach `external_media`-Consent über die bestehende `banner.js`-Mechanik gebaut. Wiederverwendung von `Service_Components::render_youtube()` → identisches Platzhalter-/Consent-Verhalten wie `[lscc_youtube]`. Steuerung über `avada_youtube_block` (bool, Default `true`).

## `includes/service-components.php`

**Klasse `Light_Swiss_Cookie_Consent_Service_Components`:**

- `init()` — registriert die drei Shortcodes
- `render_youtube( $atts )` — `youtube-nocookie.com`-Embed; Attribute `id` (ab 0.2.0 auch YouTube-URLs), `title` (ab 0.2.0, a11y-Titel) und `thumbnail_id` (lokales Mediathek-Bild)
- `extract_youtube_id( $raw )` (public, ab 0.2.0) — Video-ID aus roher ID oder YouTube-URL (`youtu.be/`, `watch?v=`, `/embed/`, `/v/`); rohe ID via `sanitize_media_id`. Wird auch von `avada-compat.php` genutzt.
- `resolve_youtube_thumbnail_html( $thumbnail_id, $video_id )` (ab 0.2.0) — lokales `thumbnail_id` hat Vorrang; sonst nur bei aktivierter Option `youtube_remote_thumbnails` ein `i.ytimg.com`-Bild; sonst leer (Platzhalter).
- `render_vimeo( $atts )` — `player.vimeo.com`-Embed; akzeptiert ab 0.1.7 zusätzlich `thumbnail_id` (lokales Mediathek-Bild, gleiche Mechanik wie YouTube)
- `render_google_map( $atts )` — Google-Maps-Embed mit Host-Allowlist
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
   - aktiviert blockierte Skripte (`activateBlockedScripts`),
   - synchronisiert Medienkomponenten (`syncMediaComponents`),
   - zeigt das Banner nur, wenn kein gueltiger Consent existiert.
4. Bei Auswahl ruft das Banner `saveAndClose` → `writeConsent` (schreibt `localStorage` + Cookie mit `Max-Age=180 Tage`, `SameSite=Lax`, `Secure` bei HTTPS) und `activateBlockedScripts` + `syncMediaComponents`.
5. `writeConsent` feuert ein `lscc:consentChanged`-CustomEvent auf `window`.

Kategorien (fix): `necessary`, `statistics`, `marketing`, `external_media`. `necessary` ist immer `true`.

## Script-Aktivierung

`activateBlockedScripts` ersetzt jedes Element `script[type="text/plain"][data-cookie-category]`, dessen Kategorie zugestimmt ist, durch ein neues `<script>`:

- `data-cookie-type` wird in `type` umgewandelt (`text/javascript`, `application/javascript`, `module`, sonst `text/javascript`).
- Es werden nur sichere Attribute kopiert. `on*`-Handler, `type`, `data-cookie-category` und `data-cookie-type` werden ausgelassen (`shouldCopyScriptAttribute`).

Normale `<script>`-Tags ohne diese Markierung bleiben unangetastet.

## Privacy Check

Die Admin-Seite `Privacy Check` ruft `Light_Swiss_Cookie_Consent_Privacy_Check::render_page()` auf. Sie ist passiv: sie prueft genau eine URL, schreibt keine Inhalte um und blockiert nichts automatisch.

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
- Debug-Logging hinter `LSCC_DEBUG`-Flag

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
