# Changelog

Alle nennenswerten Aenderungen an Light Swiss Cookie Consent werden in dieser Datei dokumentiert.

Das Format orientiert sich an "Keep a Changelog". Die Versionierung folgt semantischer Versionierung:

- `PATCH` fuer Bugfixes und Sicherheitskorrekturen
- `MINOR` fuer neue Features
- `MAJOR` fuer Architektur- oder Kompatibilitaetsaenderungen

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
