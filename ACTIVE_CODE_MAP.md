# Active Code Map

Diese Karte beschreibt die aktiven Dateien, ihre Zustaendigkeiten und die wichtigsten Funktionen. Stand: Version 0.1.0.

## Datei-Uebersicht

| Datei | Zweck |
|---|---|
| `light-swiss-cookie-consent.php` | Plugin-Bootstrap, Konstanten, Hauptklasse, Banner-Markup, Asset-Enqueue, WPML/Polylang-Registrierung, Shortcode `[simple_cookie_settings]`. |
| `includes/admin-page.php` | Admin-Menue, Einstellungsseite, Speichern via `admin-post.php` mit Nonce-Pruefung, Render-Helpers fuer Text- und Color-Felder. |
| `includes/privacy-check.php` | Passive Admin-Seite, die einmal die Startseite via `wp_remote_get` abruft und gegen eine statische Mustertabelle prueft. |
| `includes/service-components.php` | Shortcodes `[lscc_youtube]`, `[lscc_vimeo]`, `[lscc_google_map]` mit Placeholder-Markup. |
| `assets/js/banner.js` | Frontend-Logik: Consent-Speicherung, Banner-Steuerung, Script-Aktivierung, Media-Sync. |
| `assets/css/banner.css` | Styles fuer Banner, Reopen-Button, Settings-Button und Media-Komponenten. |
| `languages/light-swiss-cookie-consent.pot` | i18n-Template. |
| `languages/light-swiss-cookie-consent-*.po` | Locale-Skelette fuer `de_CH`, `en_US`, `fr_FR`, `it_IT`, `tr_TR`, `hu_HU`. |

## `light-swiss-cookie-consent.php`

**Konstanten:**

- `LSCC_VERSION` (`0.1.0`)
- `LSCC_PLUGIN_FILE`, `LSCC_PLUGIN_DIR`, `LSCC_PLUGIN_URL`
- `LSCC_DEBUG` (Default `false`, kann via `wp-config.php` ueberschrieben werden)

**Hauptklasse `Light_Swiss_Cookie_Consent`:**

- `OPTION_NAME = 'lscc_options'`
- `COOKIE_NAME = 'lscc_consent'`
- `init()` registriert Hooks und laedt Subklassen
- `load_textdomain()` — Hook `plugins_loaded`
- `register_wpml_strings()` — Hook `init`, ruft `do_action( 'wpml_register_single_string', ... )` und `pll_register_string()` fuer alle Banner-Texte
- `get_default_options()` — Defaults fuer Texte und Hex-Farben
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
- `add_settings_page()` — Top-Level-Menue `light-swiss-cookie-consent` plus Submenues `Einstellungen` und `Privacy Check`
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

- `render_page()` — Admin-Seite mit Ergebnis-Tabelle, prueft `current_user_can`
- `run_check( $source_url )` — einmaliger `wp_remote_get` auf `home_url('/')` mit Timeout 5 s, max. 500 KB Response, eigener User-Agent
- `detect_services( $body )` — sucht im Lowercase-HTML nach statischen Mustern
- `get_checks()` — Mustertabelle (siehe unten)
- `get_status_label()` — uebersetzte Status-Labels (`Kritisch`, `Wichtig`, `Info`)

**Geprueft werden aktuell:**

| Status | Muster | Hinweis |
|---|---|---|
| Kritisch | `fonts.googleapis.com`, `fonts.gstatic.com` | Externe Google Fonts |
| Kritisch | `google-analytics.com`, `googletagmanager.com` | GA / GTM |
| Kritisch | `facebook.net`, `connect.facebook.net` | Facebook / Meta |
| Wichtig | `youtube.com`, `youtu.be` | YouTube-Inhalte |
| Wichtig | `vimeo.com` | Vimeo-Inhalte |

Der Check fuehrt keinen Crawl durch, prueft nur diese eine URL und veraendert nichts.

## `includes/service-components.php`

**Klasse `Light_Swiss_Cookie_Consent_Service_Components`:**

- `init()` — registriert die drei Shortcodes
- `render_youtube( $atts )` — `youtube-nocookie.com`-Embed
- `render_vimeo( $atts )` — `player.vimeo.com`-Embed
- `render_google_map( $atts )` — Google-Maps-Embed mit Host-Allowlist
- `sanitize_media_id()` — beschraenkt IDs auf `[A-Za-z0-9_-]`
- `sanitize_google_maps_url()` — akzeptiert nur Hosts unter `google.*` oder `maps.google.com` und Pfade mit `/maps`
- `render_component()` — baut das Placeholder-Markup mit `data-lscc-media`, `data-lscc-category="external_media"`, `data-lscc-src`, `data-lscc-title`, `data-lscc-service` und einem Accept-Button

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
- `[lscc_vimeo id="VIDEO_ID"]` — Vimeo-Player-Embed.
- `[lscc_google_map url="https://www.google.com/maps/embed?..."]` — Google-Maps-Embed mit Host-Pruefung.

Jede Komponente rendert vor Zustimmung nur einen Platzhalter mit Hinweistext und Akzeptier-Button. Der Klick auf `data-lscc-accept-media` setzt die Kategorie `external_media = true` und laedt die Komponente sofort.

## JS-Verantwortung

`assets/js/banner.js` ist als IIFE im Strict-Mode organisiert. Verantwortlichkeiten:

- Consent-Validierung und Normalisierung (`isValidConsent`, `parseStoredConsent`, `normalizeConsent`)
- Persistenz in `localStorage` und Cookie (`writeConsent`, `readCookie`, `getStoredConsent`, `hasStoredConsent`)
- Banner-Steuerung (`setBannerVisible`, `syncSettingsTriggers`, `focusSettings`, `bindSettingsTriggers`)
- Consent-Aktionen (`createConsent`, `collectConsent`, `updateInputs`, `saveAndClose`)
- Script-Aktivierung (`activateBlockedScripts`, `shouldCopyScriptAttribute`, `normalizeScriptType`)
- Media-Komponenten (`createMediaIframe`, `setMediaComponentLoaded`, `syncMediaComponents`, `bindMediaComponents`, `acceptExternalMedia`)
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
- Responsive Breakpoints bei `760px` und `420px`

Es gibt keine Animationen oder Effekt-Spielereien. Farben werden ausschliesslich ueber die CSS-Variablen gesteuert, deren Werte aus den Admin-Optionen via Inline-Style gesetzt werden.
