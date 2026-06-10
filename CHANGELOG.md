# Changelog

Alle nennenswerten Aenderungen an Light Swiss Cookie Consent werden in dieser Datei dokumentiert.

Das Format orientiert sich an "Keep a Changelog". Die Versionierung folgt semantischer Versionierung:

- `PATCH` fuer Bugfixes und Sicherheitskorrekturen
- `MINOR` fuer neue Features
- `MAJOR` fuer Architektur- oder Kompatibilitaetsaenderungen

## 0.2.1-test - 2026-06-10

### Fixed

- **WPML / Sprach-Mix behoben (Live-Test-Befund 1 + 2).** Auf anderssprachigen Seiten erschienen Banner-Labels deutsch, während der Einleitungstext der aktiven Sprache folgte. Ursache: nur `banner_text` nutzte die Locale-Tabelle; alle übrigen Strings liefen über `__()`/`esc_html__()` und gaben mangels kompilierter `.mo` immer den deutschen Quelltext zurück. Jetzt folgen **alle** Banner-Texte der aktiven WPML-/Polylang-Sprache.

### Changed

- Die Defaults **aller sieben** editierbaren Texte (`banner_title`, `banner_text`, `accept_all_text`, `necessary_only_text`, `settings_text`, `save_settings_text`, `reopen_text`) kommen jetzt aus der Locale-Tabelle `get_default_text_table()` (Helper `get_neutral_text()`), aufgelöst über `determine_locale()`. Vorher nur `banner_text`.
- WPML-/Polylang-String-Translation bleibt unverändert als **Override** vorrangig.
- Plugin-Header und `LSCC_VERSION` auf `0.2.1`. `LSCC_CONSENT_VERSION` bleibt `2`.

### i18n

- Alle sechs `.po` befüllt und sechs `.mo` **kompiliert** (vorher leere Skelette, keine `.mo`). Frontend-/besucherseitige Strings in `de_CH`, `en_US`, `fr_FR`, `it_IT`, `tr_TR`, `hu_HU` übersetzt; Admin-only-Strings bleiben deutsche Quelle (Operator-Sprache).
- `.pot` aus den realen Quelltext-Callsites neu auditiert (158 msgids); obsolete editierbare Strings entfernt, fehlende Admin-Strings (v0.1.9/v0.2.0) ergänzt.

### Bewusst nicht enthalten

- Befund 3 (YouTube-Konsistenz) und Befund 4 (Modal-Design) — separat. Keine Änderung an `banner.js`, CSS, Consent-Schema oder Avada-Interception.

## 0.2.0-test - 2026-06-03

### Added

- **Nativer LSCC-YouTube-Block ausgebaut** als empfohlener Weg für neue Websites: `[lscc_youtube id="VIDEO_ID" title="Optionaler Titel"]`.
  - `id` akzeptiert jetzt zusätzlich **YouTube-URLs** (`youtube.com/watch?v=…`, `youtu.be/…`, `/embed/…`, `/v/…`) — neuer öffentlicher Helper `Service_Components::extract_youtube_id()`.
  - Neues optionales Attribut `title` (wird als iframe-/a11y-Titel verwendet).
  - Play-Button erscheint jetzt **immer** bei YouTube/Vimeo (auch ohne Thumbnail), zusammen mit Hinweistext und „Externe Medien akzeptieren"-Button. Responsive 16:9.
  - **Autoplay nach Play-Klick:** Wird das Video über den Play-Button freigegeben, startet es nach Zustimmung automatisch (`autoplay=1` für YouTube/Vimeo). Über den reinen Accept-Button: kein Autostart.
- Neue Admin-Option **„YouTube-Thumbnails vor Consent laden"** (`youtube_remote_thumbnails`, Default **AUS**) in neuer Sektion „Externe Medien".
  - AUS (Default): lokaler Platzhalter, keine externe Bildanfrage.
  - AN: YouTube-Vorschaubild von `i.ytimg.com`. Ein per `thumbnail_id` gesetztes lokales Bild hat immer Vorrang.

### Security / Datenschutz

- Vor Consent entsteht **kein** iframe, **kein** `iframe_api`, **kein** `www-widgetapi.js` und **keine** youtube.com-Cookies — unabhängig von der Thumbnail-Option.
- **Hinweis (ADR-18, schränkt ADR-14 ein):** Bei aktivierter Option „YouTube-Thumbnails vor Consent laden" wird bereits vor Consent ein Bild von `i.ytimg.com` geladen (überträgt die Besucher-IP an Google). Das ist eine bewusste, per Default deaktivierte Opt-in-Abwägung des Betreibers.
- Kein DOM-Hijacking, kein MutationObserver. Die v0.1.9-Avada-Kompatibilität bleibt unverändert und nutzt denselben (jetzt URL-fähigen) Helper.

### Changed

- `assets/js/banner.js`: `createMediaIframe()` hängt `autoplay=1` an, wenn die Aktivierung über den Play-Button kam; `bindMediaComponents()` markiert die zugehörige Komponente. Keine Änderung am Consent-Schema.
- `render_component()` zeigt den Play-Button für YouTube/Vimeo immer (vorher nur mit Thumbnail).
- Plugin-Header und `LSCC_VERSION` auf `0.2.0` gesetzt. `LSCC_CONSENT_VERSION` bleibt `2`.

### Migration

- Bestehende `[lscc_youtube id="VIDEO_ID"]`- und `[lscc_youtube id="VIDEO_ID" thumbnail_id="123"]`-Shortcodes bleiben voll kompatibel (gleiche Attribute, gleiche Kategorie `external_media`); sie erhalten zusätzlich den Play-Button. Keine Inhaltsmigration nötig.

## 0.1.9-test - 2026-06-03

### Added

- **Avada-Kompatibilität: `fusion_youtube` wird vor Consent blockiert.** Neues Modul `includes/avada-compat.php` fängt Avadas `fusion_youtube`-Shortcode serverseitig über den WordPress-Filter `pre_do_shortcode_tag` ab (bevor das iframe erzeugt wird) und gibt stattdessen das bestehende LSCC-Platzhalter-Markup (Kategorie `external_media`) aus. Das YouTube-iframe wird erst nach Zustimmung über die vorhandene `banner.js`-Mechanik gebaut. Bei „Nur notwendige" entsteht kein YouTube-Request und kein YouTube-Cookie.
- Neue Admin-Option **„Avada-YouTube (fusion_youtube) vor Consent blockieren"** (`avada_youtube_block`, Default `true`) in neuer Sektion „Avada-Kompatibilität".
- Video-ID-Erkennung aus `id` (rohe ID oder YouTube-URL: `youtu.be/…`, `watch?v=…`, `/embed/…`, `/v/…`).

### Security / Datenschutz

- Render-Layer-Interception, **kein** DOM-Hijacking, **kein** MutationObserver, **kein** Frontend-Scanner, **kein** `<script>`-Rewrite. `post_content` bleibt unverändert (keine Migration). Vollständig reversibel über den Admin-Schalter. Kein neuer Consent-Code; Consent-Flow unverändert. Scope bewusst nur YouTube — Vimeo, Maps, Background-Videos, `fusion_code` und rohe iframes werden nicht behandelt. Greift nur im Frontend (nicht im Admin/Builder-Backend). Bei nicht parsebarer Video-ID rendert Avada unverändert weiter (kein Layout-Bruch).

### Changed

- Plugin-Header und `LSCC_VERSION` auf `0.1.9` gesetzt. `LSCC_CONSENT_VERSION` bleibt `2`.

## 0.1.8-test - 2026-06-03

### Added

- Neue Admin-Seite **„Avada Inventar-Scan"** (Submenu unter Light Swiss Cookie Consent). Rein lesende, lokale Messung der Verteilung von Video-/Map-/Embed-Typen über `post`, `page`, öffentliche CPTs und vorhandene Avada-CPTs (`fusion_tb_section`, `fusion_tb_layout`, `fusion_template`, `slide`, `fusion_element`). Zählt `fusion_youtube`, `fusion_vimeo`, `fusion_map`, `fusion_code` (inkl. base64-Tiefpass), Background-Videos (`video_url`), rohe iframes (mit Same-Origin-Klassifizierung), oEmbed-Fälle (nackte URL) und Diagnostik-Rohtreffer. Ausgabe: Verteilungstabelle, Abfangbarkeits-Matrix, KPIs `Abdeckung_min`/`Abdeckung_max`, Top-Sonderfälle.
- Zweck: Abschätzung der realistisch automatisch abdeckbaren Quote (Ziel 80–95 %) vor dem Bau eines Avada-Kompatibilitätsmoduls.

### Security / Datenschutz

- Der Scan ist strikt passiv: nur `WP_Query`-Lesezugriffe und String-/Regex-Auswertung, **keine** externen Requests, **keine** Schreibzugriffe, **keine** Inhaltsänderung, **keine** Migration, **kein** Blocking/Consent. `manage_options` + Nonce. Begrenzung auf 500 Inhalte pro Lauf mit transparentem Truncation-Hinweis. Keine Änderung an bestehenden Funktionen (Consent, Service-Komponenten, Privacy Check unberührt).

### Changed

- Plugin-Header und `LSCC_VERSION` auf `0.1.8` gesetzt. `LSCC_CONSENT_VERSION` bleibt `2`.

## 0.1.7-test - 2026-06-03

### Added

- Optionales lokales Thumbnail jetzt auch für die Vimeo-Service-Komponente: `[lscc_vimeo id="VIDEO_ID" thumbnail_id="123"]`. Verhalten identisch zu YouTube (v0.1.6) — Mediathek-Bild plus grosser Play-Button vor Consent, iframe erst nach Zustimmung zu `external_media`. Ohne `thumbnail_id` bleibt das Verhalten exakt wie bisher.

### Security / Datenschutz

- Vimeo nutzt denselben Mechanismus wie YouTube: ausschliesslich `thumbnail_id` (numerische Attachment-ID), **keine** Bild-URL, **kein** Auto-Fetch, **keine** Vimeo-API, **keine** externe Bildquelle. Kein zweiter Thumbnail-Mechanismus; `get_local_thumbnail_html()` und die CSS-Klassen `.lscc-media__thumb` / `.lscc-media__play` werden wiederverwendet. JS und Consent-Flow unverändert.

### Changed

- Plugin-Header und `LSCC_VERSION` auf `0.1.7` gesetzt. `LSCC_CONSENT_VERSION` bleibt `2` (kein Consent-Schema-Wechsel).

## 0.1.6-test - 2026-06-03

### Added

- Optionales lokales Thumbnail für die YouTube-Service-Komponente. Neues Shortcode-Attribut `thumbnail_id`: `[lscc_youtube id="VIDEO_ID" thumbnail_id="123"]`. Vor Zustimmung wird das WordPress-Mediathek-Bild mit Dimensionen, `srcset` und alt-Text über `wp_get_attachment_image()` angezeigt, darüber ein grosser zentrierter Play-Button. Hinweistext und „Externe Medien akzeptieren"-Button bleiben sichtbar. Das iframe wird weiterhin erst nach Consent zu `external_media` geladen.
- Neue CSS-Klassen `.lscc-media__thumb` (füllt die 16:9-Fläche, `object-fit: cover`) und `.lscc-media__play` (grosser runder Play-Button, `:focus-visible`, keine Animationen).

### Security / Datenschutz

- Es wird ausschliesslich `thumbnail_id` (numerische Attachment-ID) unterstützt. **Keine** freie Bild-URL, **kein** Auto-Fetch aus der Video-ID, **keine** Anfrage an `img.youtube.com`, `ytimg.com`, Google oder andere externe Bildquellen. Ungültige oder nicht-Bild-Attachments fallen still auf den bisherigen Platzhalter zurück. Der Consent-Flow und das JS (`createMediaIframe`, `syncMediaComponents`, `acceptExternalMedia`) sind unverändert.

### Changed

- Plugin-Header und `LSCC_VERSION` auf `0.1.6` gesetzt. `LSCC_CONSENT_VERSION` bleibt `2` (keine Änderung am Consent-Schema → kein erzwungenes Re-Consent).

## 0.1.5-test - 2026-05-28

### Fixed

- **Banner erschien nach Plugin-Update nicht erneut.** Root Cause: die `consentVersion` war in `enqueue_assets()` hartkodiert als `'1'` — identisch zu jedem älteren Build. Browser mit einem v1-Consent (aus v0.1.0 – v0.1.4) zeigten daher nach Update auf v0.1.4 weiterhin nur den Floating-Reopen-Button statt des Banners. Plugin-Deinstallation und -Neuinstallation halfen nicht, weil der Browser-Storage clientseitig liegt.
- **Bool-Defaults wurden überschrieben.** `sanitize_options()` setzte für jeden Bool-Key `! empty( $options[ $key ] )` ohne zu prüfen, ob der Key überhaupt im Input vorhanden war. Bei einer Migration alter gespeicherter Optionen (ohne die neuen Bool-Keys) wurden Overlay/Blur/Legal-Links daher fälschlich auf `false` gekippt statt auf den dokumentierten Default `true`.

### Added

- Neue Konstante `LSCC_CONSENT_VERSION` (ab v0.1.5: `2`), getrennt von `LSCC_VERSION`. Sie wird nur bei strukturellen Änderungen am Consent-Schema erhöht; alte Consents im Browser werden dadurch ungültig und das Banner erscheint erneut.
- Neue Admin-Option `consent_lifetime_days` (Default 180, Range 1 – 365) zur Konfiguration der Consent-Gültigkeit. Wird im Banner-JS sowohl in `Max-Age` des Cookies als auch über `createdAt + lifetimeDays` strikt durchgesetzt — auch retroaktiv, wenn der Admin den Wert verkürzt.
- Neues Consent-Feld `expiresAt` als ISO-Timestamp; wird in `isValidConsent()` zusätzlich zum Versions- und Createdat-Check geprüft.

### Changed

- `Light_Swiss_Cookie_Consent_Admin::save_settings()` füllt fehlende Bool-Keys vor dem Sanitize explizit als leeren String, damit unchecked Checkboxen sicher als `false` interpretiert werden, während fehlende Keys bei Migrationen weiterhin auf die Defaults fallen.
- `enqueue_assets()` übergibt jetzt `consentVersion = LSCC_CONSENT_VERSION` und `lifetimeDays` an `lsccSettings`.
- Reopen-Button-Visibility präzisiert: erscheint nur dann, wenn ein *gültiger* Consent gespeichert ist (Versions-, expiresAt- und Lifetime-Check). Bei Inkognito ohne gespeicherten Consent ist der Button versteckt und das Banner erscheint.
- Master-Datei `CLAUDE_CONTINUITY_MASTER.md` umbenannt zu `MASTER_HANDBUCH.md`. Inhalt vollständig erhalten, Versionshistorie ergänzt, Referenzen in `PROJECT_BRIEF.md`, `DECISIONS.md` und `DEV_LOG.md` aktualisiert.
- Plugin-Header und `LSCC_VERSION` auf `0.1.5` gesetzt.
- POT um Strings für „Consent-Speicherung" und „Consent-Gültigkeit (Tage)" sowie die Beschreibung erweitert.

## 0.1.4-test - 2026-05-28

### Added

- Konfigurierbares Overlay mit optionalem Blur. Neue Admin-Optionen `Overlay aktivieren`, `Overlay-Farbe`, `Overlay-Deckkraft`, `Blur aktivieren`, `Blur-Stärke`. Overlay-Element wird nur ausgegeben, wenn aktiviert; initial `hidden`, ohne `backdrop-filter`-Render-Cost. `pointer-events: none`, kein Body-Scroll-Lock.
- Konfigurierbare Position für den Floating-Widerrufsbutton („Cookie-Einstellungen") nach Consent. Vier Positionen (`bottom-right`, `bottom-left`, `top-right`, `top-left`) plus Offsets `Offset X` / `Offset Y` (0 – 200 px). Reine CSS-/Daten-Attribut-Lösung, kein JS-Reposition-Loop.
- Rechtliche Links im Banner: dezente Links zu Datenschutz und Impressum unten im Banner. Neue Admin-Optionen `Rechtliche Links im Banner anzeigen`, `Datenschutz-URL (manuell, überschreibt Auto-Erkennung)`, `Impressum-URL (manuell, überschreibt Auto-Erkennung)`.
- Auto-Erkennung der Impressum-URL über typische Seiten-Slugs (`impressum`, `datenschutz-und-impressum`, `legal`, `legal-notice`, `mentions-legales`, `note-legali`, ...) und Seiten-Titel. Lokale DB-Lookups; läuft ausschliesslich im Admin (`admin_init`), Cache via Transient `lscc_detected_imprint_url` mit `DAY_IN_SECONDS` TTL. Datenschutz-URL nutzt `get_privacy_policy_url()` aus dem WordPress-Core.
- Neue Sanitization-Typen (`get_bool_option_keys`, `get_int_option_keys`, `get_float_option_keys`, `get_url_option_keys`, `get_enum_option_keys`) im zentralen `sanitize_options()`. Werte werden clamp'd auf ihre erlaubten Bereiche (Opacity 0–1, Blur 0–20 px, Offsets 0–200 px).

### Changed

- UX-Fix Settings-Modus: Im geöffneten Einstellungszustand bleibt der obere Action-Block sichtbar (Alle akzeptieren / Nur notwendige), aber der `Einstellungen`-Button wird gezielt ausgeblendet. Unten gibt es weiterhin nur `Auswahl speichern`.
- Plugin-Header und `LSCC_VERSION` auf `0.1.4` gesetzt.
- POT-Datei um 23 neue i18n-Strings (Sektionsüberschriften, Position-Labels, Legal-Links) erweitert.

## 0.1.3-test - 2026-05-28

### Added

- Privacy Check Content Scan v0.2: neuer Abschnitt auf der Privacy-Check-Admin-Seite. Lokale Suche in maximal 200 veroeffentlichten Beitraegen, Seiten und oeffentlichen Custom Post Types nach bekannten Drittanbieter-Domains (YouTube, Vimeo, Google Maps, Google Fonts, GTM, GA, Facebook). Button-getriggert, Nonce-geschuetzt, ausschliesslich lokale Datenbankzugriffe, keine externen Requests.
- Treffer-Tabelle mit Risiko, Dienst, Inhaltstyp, Titel mit Bearbeiten-Link, Domain und Empfehlung.

### Changed

- UX-Bugfix Banner-Settings: im geoeffneten Einstellungszustand stand der untere Button-Block doppelt mit `Alle akzeptieren` / `Nur notwendige`. Diese Duplikate sind entfernt; im Settings-Block bleibt nur noch der primaere `Auswahl speichern`-Button.
- Plugin-Header und `LSCC_VERSION` auf `0.1.3` gesetzt.
- POT um neue Content-Scan-i18n-Strings erweitert.
- Neutraler Default-Consent-Text mit Locale-Fallback: Banner-Default ist nicht mehr per Sie / per Du formuliert, sondern neutral („nach Zustimmung geladen") und je nach Site-Locale für `de`, `en`, `fr`, `it`, `tr`, `hu` automatisch passend ausgewählt. Sprachpräfix-Fallback (z. B. `de_AT` -> `de`, `en_GB` -> `en`) und Englisch als Fallback bei unbekannten Sprachen.
- Bestehende ASCII-Workarounds in deutschen UI-Texten (`Prüfung`, `Für`, `Lädt`, `Über`, `Primärbutton`, `Sekundärbutton`, `veröffentlichten`, `Beiträgen`, `öffentlichen`, `später`, `geprüft`, `prüfen`, ...) auf echte Umlaute umgestellt. POT-Strings synchron aktualisiert. Schweizer Schreibweise weiterhin ohne ß.

## 0.1.1-test - 2026-05-28

### Added

- Privacy Check v0.1: passive Admin-Seite mit statischer Mustererkennung fuer Google Fonts, Google Analytics, Google Tag Manager, Facebook, YouTube und Vimeo. Kein Crawl, keine automatische Blockierung.
- Service Components v0.1: kontrollierte Shortcodes `[lscc_youtube]`, `[lscc_vimeo]`, `[lscc_google_map]` mit Placeholder-Mechanik vor Zustimmung zur Kategorie `external_media`.
- Admin-Menue als Top-Level-Eintrag mit Submenues `Einstellungen` und `Privacy Check`.

### Changed

- Doku- und Continuity-Struktur konsolidiert: `PROJECT_BRIEF.md`, `ACTIVE_CODE_MAP.md` und `DECISIONS.md` im Plugin-Ordner angelegt.
- `CLAUDE_CONTINUITY_MASTER.md` als verpflichtende Vorrangs- und Kontinuitaetsquelle eingefuehrt und von RTF nach echtes UTF-8-Markdown migriert.
- Plugin-Header und `LSCC_VERSION` auf `0.1.1` gesetzt.

## 0.1.0 - 2026-05-27

### Added

- Erste installierbare Testversion des Plugins vorbereitet.
- Leichtes Cookie-Consent-Banner ohne externe Libraries.
- Kategorien: notwendig, Statistik, Marketing, externe Medien.
- Script-Blocking fuer bewusst markierte Skripte mit `type="text/plain"` und `data-cookie-category`.
- Admin-Seite fuer Texte und Farben.
- Consent-Speicherung in `localStorage` und Cookie.
- Shortcode `[simple_cookie_settings]` zum erneuten Oeffnen der Einstellungen.
- Sprachstruktur fuer `de_CH`, `en_US`, `fr_FR`, `it_IT`, `tr_TR` und `hu_HU`.
- Release- und Sicherheitsdokumentation vorbereitet.

### Security

- Basis-Haertung fuer Sanitizing, Escaping, Nonce-Pruefung und Consent-Struktur umgesetzt.
- Consent-Cookie mit `SameSite=Lax` und `Secure` bei HTTPS.
