# Changelog

Alle nennenswerten Aenderungen an Mac's Cookie Banner (vormals Light Swiss Cookie Consent) werden in dieser Datei dokumentiert.

Das Format orientiert sich an "Keep a Changelog". Die Versionierung folgt semantischer Versionierung:

- `PATCH` fuer Bugfixes und Sicherheitskorrekturen
- `MINOR` fuer neue Features
- `MAJOR` fuer Architektur- oder Kompatibilitaetsaenderungen

## 0.5.9-debug - 2026-06-21

### Debug (temporär — nicht für Produktion)

- **Beweis-Build: welchen `primary_color`-Wert liest der Import wirklich?** Reine, lesende Admin-Notice nach Klick auf „Avada-Farben übernehmen". Zeigt nebeneinander: `fusion_get_option('primary_color')`, `Avada()->settings->get('primary_color')`, `fusion_options['primary_color']`, den tatsächlich verwendeten Import-Wert (`IMPORT_FINAL_VALUE`) und den aufgelösten Hex (`IMPORT_RESOLVED_HEX`). Kein `debug.log`, kein FTP/WP-CLI.
- **Keine** Änderung an Resolver, Cache-Logik, Import-Speicherung, Consent, Locale, Reopen, Presets oder Frontend. `MCB_VERSION` bleibt 0.5.9. Wird nach der Diagnose wieder entfernt.

## 0.5.9-test - 2026-06-21

### Fixed

- **Avada-Farbimport: Cache-Variable hielt die alte Farbe fest (Root Cause behoben).** Der Import schrieb die Markenfarbe nachweislich korrekt (`PRIMARY_COLOR_RESOLVED = #1e4884`, `AFTER_UPDATE = #1e4884`), aber Avada/Fusion lieferte das gecachte Inline-CSS mit der alten Variable `--lscc-primary:#e11d48` weiter aus → Banner blieb rot, bis Avada-/Browser-Cache manuell geleert wurde. Nicht Resolver, nicht DB, nicht Banner-Ausgabe.
- Neu wird nach erfolgreichem „Avada-Farben übernehmen" der Avada/Fusion-Cache **automatisch über Avadas eigene API** geleert: `Macs_Cookie_Banner_Avada_Colors::reset_caches()` ruft defensiv `fusion_reset_all_caches()`, sonst `Fusion_Cache::reset_all_caches()` (erster vorhandener gewinnt; keiner → kein Fehler). Aufruf in `import_avada_colors()` direkt nach `update_option()`. Kein Ctrl+F5 mehr nötig.
- Admin-Notice bei erfolgreichem Cache-Reset: „Avada-Farben übernommen. Fusion/Avada Cache wurde automatisch geleert." (ADR-29).
- Keine eigene Cache-Lösung. Keine Änderungen am Import-Resolver, an Consent, Locale, Reopen, Presets oder Frontend.
- Version 0.5.8 → 0.5.9 (Header + `MCB_VERSION`). `MCB_CONSENT_VERSION` unverändert.

## 0.5.8-test - 2026-06-21

### Fixed

- **Avada-Farbimport übernimmt jetzt zuverlässig die aktive Primary Color (Client-Resolver-Fallback).** Server-Pfad bleibt (direktes Hex + Palette-Auflösung). Wenn PHP `var(--awb-colorX)` nicht serverseitig auflösen kann (Avada-Palette serverseitig nicht zuverlässig lesbar), endet der Import nicht mehr still mit „keine gefunden": Der Browser löst die **tatsächlich aktive** CSS-Variable per `getComputedStyle(document.documentElement).getPropertyValue('--awb-colorX')` auf (mit verstecktem, gleich-origin Frontend-iframe als Fallback, wo Avada die `:root`-Global-Colors garantiert ausgibt), normalisiert `rgb()/rgba()` zu Hex und sendet den Wert als Hidden-Feld mit. PHP akzeptiert ihn **nur** nach `manage_options` + Nonce und **nur** als gültiges `sanitize_hex_color()`; dann werden `primary_button_color`, `border_color`, `primary_text_color` geschrieben.
- Greift für beliebige `var(--awb-colorX)` (color1/5/12/27 …), keine feste Nummer, keine feste Anzahl, **kein** Raten über `color_palette`. Bei ungültigem/leerem Wert: keine Änderung + bestehende Warnung. Keine Frontend-Auswirkung, reiner Admin-Import.
- Neuer Helfer `Macs_Cookie_Banner_Avada_Colors::get_brand_css_vars()` (liefert die referenzierten CSS-Variablen in Prioritätsreihenfolge).
- Keine Änderungen an Consent, Locale, Presets, Reopen, Scanner, Consent-Code-Manager oder Auto-Update.
- Version 0.5.7 → 0.5.8 (Header + `MCB_VERSION`).

## 0.5.7-test - 2026-06-21

### Fixed

- **Avada-Farbimport übernahm nichts (Root Cause behoben).** Der Palette-Resolver in `includes/avada-colors.php` suchte die Farbe per `isset( $palette['awb-color5'] )`, baute die Map aber nach dem `id`/`slug`-Feld der Avada-Einträge — das verbatim-Token `awb-colorN` existierte dort nie, daher lieferte `resolve_color()` leer, `map_to_banner()` blieb `[]` und `update_option()` lief nie → Banner behielt den Default `#e11d48`. Neu wird `--awb-colorN` **positionsbasiert** (N-ter Palette-Eintrag, exakt wie Avada die `:root`-Variablen erzeugt) plus normalisierte `id`/`slug`-Treffer aufgelöst. Greift für beliebige Nummern (`--awb-color1/5/12/27…`), beliebig viele Global Colors und `--awb-custom_color_N`; `rgb()/rgba()`-Globals werden zu Hex konvertiert. Keine feste Farbanzahl, kein hartcodiertes Token.
- Neue private Helfer: `read_palette_raw()`, `normalize_token()`, `color_value_to_hex()`. `resolve_color()`/`get_palette()` ersetzt.
- **Entfernt:** der temporäre `0.5.6-debug`-Runtime-Proof (`debug_runtime_proof()` + TEMP-Aufruf in `import_avada_colors()`).
- Nur der Avada-Resolver wurde angefasst — keine Änderung an Consent, Locale, Presets, Reopen, Admin-UI, Scanner, Consent-Code-Manager, Auto-Update, Brand-Keys, `map_to_banner` oder Importlogik.
- Version 0.5.6 → 0.5.7 (Header + `MCB_VERSION`).

## 0.5.6-debug - 2026-06-21

### Debug (temporär — nicht für Produktion)

- **Avada-Palette Runtime-Proof.** Temporäre, rein lesende Diagnose `Macs_Cookie_Banner_Avada_Colors::debug_runtime_proof()`, ausgelöst beim Klick auf „Avada-Farben übernehmen". Loggt nach `wp-content/debug.log` (Präfix `MCB-AVADA-PROOF`): (1) vollständiger `color_palette`-Inhalt, (2) alle Palette-Keys + je Eintrag dessen Keys/Wert, (3) roher `primary_color`, (4) Regex-Auflösung `var(--awb-colorX)` → `awb-colorX`, (5) der vom aktuellen Resolver gefundene Palette-Eintrag, (6) finale Hex-Farbe (`resolve_color`/`get_brand_color`), (7) `map_to_banner()`-Ergebnis. **Keine** Änderung an Consent/Locale/Presets/Reopen/Admin-UI/Importlogik; Resolver-Logik unverändert (0.5.5-Baseline). Voraussetzung: `WP_DEBUG_LOG=true`. Wird nach der Diagnose wieder entfernt.

## 0.5.5-test - 2026-06-20

### Fixed

- **Locale-Re-Display zu aggressiv (Problem 1).** Bisher löste **jeder** Sprachwechsel ein erneutes Banner aus (Vergleich gegen *eine* zuletzt gesehene Locale). Jetzt wird je Sprache **nur einmal** erneut angezeigt: `banner.js` führt eine **Liste gesehener Locales** im separaten Key `mcb_consent_locales_seen` (z. B. `["de_CH","en_US"]`). Bei `currentLocale` **nicht** in der Liste → Banner einmal zeigen; nach Speichern/Schliessen wird die Locale aufgenommen. Rückwechsel auf bereits gesehene Sprachen zeigt das Banner **nicht** erneut. Der 0.5.4-Einzelkey `mcb_consent_locale` wird einmalig migriert. `lscc_consent`/Cookie/localStorage-Consent/`MCB_CONSENT_VERSION` unverändert, kein Re-Consent.
- **Weiße 1px-Outline am Reopen-/Cookie-Einstellungs-Button (Problem 2).** Für **Modern/Premium** jetzt **klar sichtbar**: `border: 1px solid rgba(255,255,255,0.95)` **plus** `box-shadow: inset 0 0 0 1px rgba(255,255,255,0.85)` (auch im Hover erhalten) — wirkt auf jeder Markenfarbe deutlich. Primary-Hintergrund + Auto-Kontrast-Text bleiben; dezenter Schatten/Hover; Radius je Preset. Classic unverändert.

### Changed

- **Position des Cookie-Einstellungen-Buttons leichter auffindbar (Problem 3).** Das Positions-Dropdown ist jetzt **prominent in der Sektion „Darstellung"** (statt unten unter „Floating-Button") mit klarer Beschriftung **„Cookie-Einstellungen-Button Position"** (Unten rechts/links, Oben rechts/links, Versteckt) und Hinweis: „Für Websites mit Chat-Buttons oder WhatsApp-Buttons kann unten links sinnvoll sein." Die Feinjustierung (Offsets) bleibt in der umbenannten Sektion „Floating-Button — Feinjustierung". **Nur eine** Positions-Option (kein Duplikat). **Keine automatische Positionsänderung, keine Rücksetzung** — bestehende Werte bleiben (ADR-27).
- Plugin-Header und `MCB_VERSION` auf `0.5.5`. `MCB_CONSENT_VERSION` bleibt `2`.

### Bewusst unverändert

- `reopen_position`-Werte/-Logik (bottom/top-right/-left, hidden) inhaltlich unverändert — nur prominenter platziert; alle Positionen funktionieren weiter, Werte bleiben nach Update erhalten.
- Keine neuen Presets/Features; keine Consent-Logik-Änderung; kein Eingriff in Scanner/CCM/Privacy/Updater; keine DB-Migration; `lscc_consent` unangetastet.

## 0.5.4-test - 2026-06-20

### Added

- **Locale-aware Banner-Anzeige bei Sprachwechsel (ADR-28).** Wechselt der Besucher die Front-End-Sprache (z. B. de_CH → en_US), erscheint das Banner **erneut in der neuen Sprache** — **ohne** Re-Consent: bestehende Auswahl bleibt erhalten und vorausgewählt, `lscc_consent`/Cookie/localStorage und `MCB_CONSENT_VERSION` werden **nicht** verändert. Umsetzung: PHP übergibt `locale` (`determine_locale()`) an `mcbSettings`; `banner.js` merkt sich in einem **separaten, leichten** localStorage-Key `mcb_consent_locale`, in welcher Locale der Consent zuletzt angezeigt/bestätigt wurde, und zeigt das Banner erneut, wenn aktuelle ≠ gespeicherte Locale. Nach Speichern/Schliessen wird die Locale aktualisiert. Bestehende Consents ohne gespeicherte Locale übernehmen die aktuelle **still** (kein erzwungenes Wiedererscheinen).
- **Temporäres Ausblenden des Reopen-Buttons (X).** Kleines „×" im Reopen-Button; Klick blendet den Button **für diese Seitenansicht** aus (reine Komfortfunktion). **Keine** Speicherung, kein Consent-/Cookie-/Einstellungs-Eingriff — nach einem normalen Reload erscheint der Button wieder. Ersetzt **nicht** die `hidden`-Position (dauerhaft über Plugin-Einstellung).

### Changed

- **Reopen-/Cookie-Einstellungs-Button trägt jetzt sichtbar die Markenfarbe** in den Presets **Modern** und **Premium**: Primary als Hintergrund, Auto-Kontrast-Textfarbe (`--lscc-primary-text`), dezente 1px-weisse Outline, markenfarbener Schatten + dezenter Hover, Radius passend zum Preset. **Classic** bleibt unverändert (dezent/dunkel). Nach Avada-Farbimport tragen Haupt- **und** Reopen-Button sichtbar dieselbe Markenfarbe. Kein Glass/Transparenz/Blur/Neon/Animation; Popup-Hintergrund unverändert.
- Plugin-Header und `MCB_VERSION` auf `0.5.4`. `MCB_CONSENT_VERSION` bleibt `2`.
- Neuer i18n-String „Cookie-Einstellungen-Button ausblenden" (aria-label des X) in **allen 6 Locales** ergänzt (de_CH/en_US/fr_FR/it_IT/tr_TR/hu_HU) + `.pot`; `.mo` neu kompiliert (Round-Trip verifiziert, kein Sprach-Mix).

### Bewusst unverändert

- `reopen_position` (bottom-right/-left, top-right/-left, hidden) **unverändert** — alle Positionen funktionieren weiter, Werte bleiben nach Update erhalten, Default `bottom-right`, ADR-27 (keine automatische Positionsänderung/Rücksetzung). Kein neuer Positionsmodus.
- Keine Consent-Logik-/Kategorie-Änderung; keine DB-Migration; kein Eingriff in Scanner/CCM/Auto-Updater/Privacy Check; `lscc_consent` unangetastet (Locale-Metadaten in separatem Key `mcb_consent_locale`).

## 0.5.3-test - 2026-06-20

### Fixed

- **Sprach-Mix im Banner behoben (kritisch).** Auf mehrsprachigen Sites erschienen Titel, Beschreibung, die Buttons „Accept all/Necessary only/Save selection" und der Reopen-Button „Cookie settings" weiter auf Englisch, während die Kategorien bereits lokalisiert waren. Ursache: die **sieben editierbaren Texte** sind option-basiert (`get_translated_option()`) und lieferten den **gespeicherten** Wert (einmal in der Admin-Sprache gespeichert), statt der aktiven Front-End-Sprache zu folgen. `get_translated_option()` erkennt jetzt, ob der gespeicherte Wert nur einem mitgelieferten **Default-Text (in irgendeiner Sprache)** entspricht; falls ja (oder leer), folgt er der aktiven Locale über `get_default_text_table()` (de/en/fr/it/tr/hu). Echte, vom Operator angepasste Texte sowie WPML/Polylang-String-Translation behalten weiterhin Vorrang.
- **Folge:** auf einer DE-Seite vollständig Deutsch, FR vollständig Französisch, IT/EN/TR/HU analog — kein gemischtes Banner mehr.

### Changed

- **Sprachdateien `.mo` neu kompiliert** (alle 6 Locales: de_CH, en_US, fr_FR, it_IT, tr_TR, hu_HU) aus den `.po`, damit `.po`/`.mo` wieder konsistent sind (der beim 0.4.0-Rebrand in den `.po` aktualisierte Admin-Hilfetext `MCB_CONSENT_VERSION` war noch nicht in die `.mo` übernommen). Round-Trip-verifiziert, 0 Mismatches; Frontend-Übersetzungen unverändert.
- **Premium-Preset, Reopen-Button:** optisch an den Premium-Look angeglichen — dezenter 1px-Rand in der Markenfarbe, passender Radius (8px), markenfarbener Glow, minimal hochwertigerer Hover. Kein Glass/Transparenz/Blur/Neon/Animation; Popup-Hintergrund unverändert.
- Plugin-Header und `MCB_VERSION` auf `0.5.3`. `MCB_CONSENT_VERSION` bleibt `2`.

### Sprachinventur (Stand 0.5.3)

- Vorhandene Locales (einzige Quelle der Wahrheit = `languages/`): **de_CH, en_US, fr_FR, it_IT, tr_TR, hu_HU** (je `.po` + `.mo`) + `macs-cookie-banner.pot`. **Keine** weiteren Varianten (kein de_DE, de_*_formal, fr_CH, es_ES, th_TH).
- Besucherseitige Frontend-Strings (Kategorien, Beschreibungen, Rechtslinks, Service-Hinweise) sind in **allen 6** Locales vollständig übersetzt → kein englischer Fallback. Die übrigen msgids sind **Admin-/Operator-Strings** und bewusst deutsche Quelle (ADR-19), nicht besucherseitig.

### Bewusst unverändert

- Keine Consent-Logik-, Scanner-, Privacy-Check-, CCM-, Updater-Änderung; keine Datenstruktur-/Cookie-/Storage-/Shortcode-Änderung; kein neues Feature/Preset; Popup-Hintergrund unverändert (kein Glass).

## 0.5.2-test - 2026-06-20

### Added

- **Design-Presets (1-Dropdown-UX).** Neue Admin-Option „Design-Preset" mit drei Werten: **Classic** (Default = bisheriges Banner, keine optische Änderung), **Modern** (größere Radien, mehr Luft, weichere Schatten, Pill-Buttons), **Premium** (stärkere Elevation, hochwertige Schatten, dezenter Glow mit der Markenfarbe am Hauptbutton). Auswählen → speichern → Banner sieht sofort anders aus, **ohne** Farb-/CSS-Kenntnisse.
- Presets verändern **ausschliesslich** Form/Radius/Schatten/Glow/Abstände/Button-Stil. **Farben bleiben** aus den manuellen Farbfeldern bzw. dem Avada-Farbimport (v0.5.1); Premium-Glow nutzt `var(--lscc-primary)`.

### Changed

- Plugin-Header und `MCB_VERSION` auf `0.5.2`. `MCB_CONSENT_VERSION` bleibt `2`.

### Technisch / Architektur (additiv, ADR-27-konform)

- `macs-cookie-banner.php`: neue Enum-Option `design_preset` (`classic|modern|premium`, Default `classic`) über die bestehende typisierte Options-Mechanik (`get_default_options()` + `get_enum_option_keys()`); `render_banner()` und `render_settings_shortcode()` hängen die Klasse `lscc--preset-<wert>` an Root, Overlay, Reopen-Button und Settings-Shortcode-Button. `get_css_variables()` unverändert.
- `assets/css/banner.css`: **additive**, class-gescopte Blöcke `.lscc--preset-modern` / `.lscc--preset-premium` (Classic = Baseline). Größere Paddings nur ab Tablet (`min-width: 761px`) → Mobile behält das kompakte Basis-Layout.
- `includes/admin-page.php`: Select-Feld „Design-Preset" (Sektion „Darstellung") + Hinweis, dass Presets keine Farben ändern.

### Bewusst unverändert / nicht enthalten

- **Glass-Preset bewusst verschoben** (späterer Release).
- **Keine** Farbänderung durch Presets; Default `classic` → kein Auto-Visual-Change beim Update (ADR-27).
- Kein Eingriff in Consent, Scanner, Privacy Check, Consent-Code-Manager, Auto-Update, Cookies/Storage, Shortcodes, Avada-Import, Reopen-Button-Logik. Keine Migration, keine Änderung der Consent-/Storage-Datenstruktur.

## 0.5.1-test - 2026-06-20

### Added

- **Avada-Farbimport (1-Klick, Agentur-UX).** Neuer Admin-Button „Avada-Farben übernehmen" (Sektion Farben) — **nur sichtbar, wenn Avada aktiv ist**. Übernimmt **eine** Markenfarbe aus den Avada-Theme-Optionen in den **Primärbutton** und die **Rahmenfarbe** des Banners; die **Button-Textfarbe** wird automatisch per WCAG-Kontrast lesbar gesetzt. Ziel: „Banner wirkt sofort wie die Website".
- **Markenfarb-Prioritätskette** (erster gültiger Treffer gewinnt): `primary_color` → `accent_color` → `link_color` → `button_gradient_top_color` (nur Notnagel). `var(--awb-colorN)`-Referenzen werden gegen die Avada-Palette aufgelöst.

### Changed

- Plugin-Header und `MCB_VERSION` auf `0.5.1`. `MCB_CONSENT_VERSION` bleibt `2`.

### Technisch / Architektur (ADR-27-konform)

- Neue read-only-Klasse `includes/avada-colors.php` (`Macs_Cookie_Banner_Avada_Colors`): `is_active()`, `get_brand_color()`, `read_raw()`, `resolve_color()`, `map_to_banner()`, `contrast_color()`. **Keine** Frontend-Hooks, **kein** Avada-Schreibzugriff, **kein** Versions-Gating, **kein** Legacy-Pfad.
- `includes/admin-page.php`: admin-post-Action `mcb_import_avada_colors` (+ Nonce `mcb_import_avada_colors`), Button + Erfolg-/Warn-Notice. Import schreibt **nur** auf bewussten Klick (Modell A: Klick → sofort speichern → Meldung).
- `macs-cookie-banner.php`: `require_once includes/avada-colors.php` **nur im Admin**.

### Bewusst unverändert

- **Kein** Auto-Import, Live-Sync, Hook, Wizard, Popup oder Post-Update-Dialog (ADR-27): Farben ändern sich **ausschliesslich** auf Klick.
- Importiert werden nur Primärbutton-/Rahmenfarbe (+ berechneter Button-Text). **Nicht** importiert: `secondary_button_color`, `background_color`, `text_color`, `overlay_color`, komplette Palette.
- Kein neuer Options-Key, keine Migration. Consent, Scanner, Privacy Check, Consent-Code-Manager, GitHub-Updater, Shortcodes, Cookies/Storage, Reopen-Button, Maps/YouTube unverändert.

## 0.5.0-test - 2026-06-20

### Added

- **Reopen-Button: neue Position „Versteckt" + frei wählbare Ecken.** Die bestehende Option `reopen_position` (Enum) wird um den Wert `hidden` erweitert (zusätzlich zu `bottom-right`/`bottom-left`/`top-right`/`top-left`). Bei `hidden` wird der „Cookie-Einstellungen"-Button **nie** angezeigt — kein automatisches Wiedererscheinen, kein „nach Reload anzeigen". Der Consent-Widerruf erfolgt dann ausschliesslich über den Shortcode `[simple_cookie_settings]`.
- **DSGVO-Admin-Hinweis:** Ist `Versteckt` aktiv, erscheint in den Einstellungen ein Warnhinweis: „Bei verstecktem Cookie-Einstellungs-Button muss ein alternativer Widerrufsweg vorhanden sein (z. B. `[simple_cookie_settings]` im Footer)."

### Changed

- Plugin-Header und `MCB_VERSION` auf `0.5.0`. `MCB_CONSENT_VERSION` bleibt `2`.

### Technisch (minimal-invasiv, bestehende Mechanik wiederverwendet)

- `macs-cookie-banner.php`: `get_enum_option_keys()` um `hidden` ergänzt. Render unverändert — der Button trägt weiterhin `data-position`.
- `assets/js/banner.js`: `setBannerVisible()` hält den Button bei `data-position="hidden"` dauerhaft versteckt (`reopenButton` bleibt im DOM, da `initBanner` ihn voraussetzt).
- `includes/admin-page.php`: Select-Option „Versteckt" + bedingter DSGVO-Warnhinweis.

### Bewusst unverändert

- Keine Consent-Logik-Änderung, keine DB-Migration, keine Shortcode-Änderung. `lscc_options`/`lscc_consent`/`CONSENT_VERSION` unberührt. Bestehende Installationen erhalten `hidden` als zusätzliche Wahlmöglichkeit; Default bleibt `bottom-right`.

## 0.4.0-test - 2026-06-20

### Changed (Vollständiger Rebrand — Variante B-minus)

Vollständiger sichtbarer **und** struktureller Rebrand auf **Mac's Cookie Banner**. Umbenannt wurden ausschliesslich **Code-Identifier**; alle persistenten Daten-/Content-/Consent-Identitäten bleiben als String-Literal erhalten → **kein Migrator, keine DB-/Consent-/Cookie-/localStorage-Migration**.

**Umbenannt:**
- Plugin-Ordner/Slug + Hauptdatei → `macs-cookie-banner` / `macs-cookie-banner.php`
- Textdomain → `macs-cookie-banner`; Sprachdateien → `languages/macs-cookie-banner-*.{po,mo,pot}`
- PHP-Klassen `Light_Swiss_Cookie_Consent*` → `Macs_Cookie_Banner*`
- Konstanten `LSCC_*` → `MCB_*` (`MCB_CONSENT_VERSION`-**Wert bleibt 2**)
- Admin-Menü-Slugs → `macs-cookie-banner*`
- Nonces/Actions → `mcb_*`; Script-/Style-Handles `lscc-banner`→`mcb-banner`, `lscc-admin-consent-codes`→`mcb-admin-consent-codes`; JS-Global `lsccSettings`→`mcbSettings`
- PUC-`SLUG` → `macs-cookie-banner`; `REPOSITORY_URL` = `Thaimacky/macs-cookie-banner`
- Version → `0.4.0`

**Bewusst unverändert (keine Migration, keine Brüche):**
- DB-Optionen `lscc_options`, `lscc_consent_codes`; Transient `lscc_detected_imprint_url`
- Consent-Cookie + localStorage `lscc_consent`; `CONSENT_VERSION`-Wert `2`
- Export-Envelope `lscc_export_version` / `type:'lscc-config'` (Alt-Exporte importierbar)
- WPML-/Polylang-Kontext `Light Swiss Cookie Consent`
- CSS-Klassen `.lscc*`, `data-lscc-*`-Attribute, `--lscc-*`-Variablen
- Shortcodes `[lscc_youtube]`/`[lscc_vimeo]`/`[lscc_google_map]`/`[simple_cookie_settings]`
- Event `lscc:consentChanged`

→ Bestehende Websites, Einstellungen, Consents, Shortcodes, WPML-Übersetzungen und CSS-Anpassungen funktionieren unverändert. Bestands-Installationen ziehen 0.4.0 in-place (alter Ordner bleibt cosmetisch); der neue Ordner `macs-cookie-banner` entsteht bei Neuinstallation.

## 0.3.4-test - 2026-06-19

### Changed (Rebranding — nur sichtbare Bezeichnung)

- **Produktname umgestellt: „Light Swiss Cookie Consent" → „Mac's Cookie Banner".** Betroffen ausschliesslich sichtbare Stellen im WordPress-Backend und in der Doku:
  - Plugin-Header `Plugin Name`, `Author`, `Plugin URI`.
  - Admin-Hauptmenue und Seitentitel (`<h1>`) der Einstellungsseite.
  - Plugin-Liste in WordPress zeigt jetzt **Mac's Cookie Banner**.
  - `README.md` und dieser Changelog.
  - HTTP-User-Agent des Privacy-Check-Scanners.
- **Auto-Update-Ziel umgestellt:** `includes/updater.php` `REPOSITORY_URL` → `https://github.com/Thaimacky/macs-cookie-banner/`.
- Plugin-Header und `LSCC_VERSION` auf `0.3.4`. `LSCC_CONSENT_VERSION` bleibt `2`.

### Bridge-Release-Hinweis

- **0.3.4 ist ein Bruecken-Release.** Es wird einmalig zusaetzlich im alten Repo `Thaimacky/light-swiss-cookie-consent` veroeffentlicht. Bestehende Installationen (auf 0.3.3) sehen das Update dort, ziehen es automatisch und pollen ab dann das neue Repo `Thaimacky/macs-cookie-banner`. Ab 0.3.5 erscheinen Releases nur noch im neuen Repo.

### Explizit unveraendert (keine Migration, kein Datenverlust)

- Slug/Ordnername `light-swiss-cookie-consent`, Hauptdateiname, PUC-`SLUG`, Textdomain `light-swiss-cookie-consent`.
- DB-Keys `lscc_options`, `lscc_consent_codes`, Transient `lscc_detected_imprint_url`.
- Cookie-/Storage-Name `lscc_consent`, `LSCC_CONSENT_VERSION = 2`.
- Konstanten-/Hook-/CSS-Praefix `LSCC_`/`lscc_`, PHP-Klassennamen `Light_Swiss_Cookie_Consent*`, WPML/Polylang-String-Kontext `Light Swiss Cookie Consent`.
- Folge: bestehende Einstellungen und Consents bleiben vollstaendig erhalten; die DB wird durch das Update nicht veraendert.

## 0.3.3-test - 2026-06-16

### Added

- **GitHub-basierte Auto-Updates** — neues Modul `includes/updater.php`. Bindet die mitgelieferte Plugin-Update-Checker-Library (PUC v5.6, `includes/plugin-update-checker/`) an das GitHub-Repo `Thaimacky/light-swiss-cookie-consent`. Updates werden aus dem **ZIP-Asset** des jeweiligen GitHub-Releases gezogen (`enableReleaseAssets()`), damit das installierte Paket frei von Build-/Dev-Cruft bleibt. Das Plugin meldet neue Versionen kuenftig direkt im WordPress-Update-Screen.

### Changed

- Plugin-Header und `LSCC_VERSION` auf `0.3.3`. `LSCC_CONSENT_VERSION` bleibt `2`.
- `.gitignore`: Dev-Ignores `vendor/`/`node_modules/` auf Repo-Root verankert (`/vendor/`, `/node_modules/`), damit das gebuendelte `plugin-update-checker/vendor/` (u.a. `PucReadmeParser.php`, zur Laufzeit benoetigt) tracked bleibt.

### Hinweis zum Release-Workflow

- Damit Auto-Updates greifen, muss pro Version ein **GitHub-Release mit angehaengtem Plugin-ZIP-Asset** veroeffentlicht werden (Tag = Versionsnummer). Ohne Release-Asset zeigt PUC kein Update an.

## 0.3.2-test - 2026-06-12

### Added

- **Avada-Google-Maps Consent-Gating** (opt-in, Default AUS) — neues Modul `includes/avada-maps-compat.php`. `fusion_map` wird serverseitig durch einen LSCC-Platzhalter ersetzt; nach „Externe Medien"-Consent lädt die Karte als Google-Maps-Embed. Die Google-Maps-JS-API (`maps.googleapis.com/maps/api/js`) wird SRC-basiert (handle-agnostisch) vor Consent blockiert. Kein Avada-Reinit, kein Observer, kein DOM-Hijack.
- **`[lscc_google_map address="…"]`** — Adress-Eingabe; intern keyless Embed (`maps.google.com/maps?q=…&output=embed`). `url=` bleibt unverändert nutzbar.
- Admin-Sektion „Avada-Google-Maps" inkl. deutlicher Warnung: nur eine Consent-Schicht (Avada Privacy Maps und LSCC Maps nicht parallel).

### Changed

- `service-components.php`: neuer public Helper `build_maps_embed_url()` (auch vom Avada-Modul genutzt).
- Plugin-Header und `LSCC_VERSION` auf `0.3.2`. `LSCC_CONSENT_VERSION` bleibt `2`.

### Bekannte Trade-offs

- Nach Consent erscheint die Google-Embed-Karte (Standort), nicht Avadas voll gestylte JS-Karte; Multi-Marker → nur primäre Adresse. Bei nicht parsebarer Adresse rendert Avada normal (API bleibt via SRC-Gating geblockt).

## 0.3.1-test - 2026-06-12

### Added

- **Scanner-Ausbau „Drittanbieter-Oberfläche"** (Phase 2) in der Privacy-Check-Seite: zeigt pro Dienst den **Gating-Status** auf der gerenderten Seite.
  - Dienste: GA4, GTM, Meta Pixel, Hotjar, reCAPTCHA, Calendly, YouTube, Vimeo, Google Maps, externe Google Fonts.
  - **5-Status-Modell:** Nicht gefunden / Verwaltet / Teilweise verwaltet / Ungegatet / **Nicht prüfbar** (GTM-Tags, klick-/JS-geladene Widgets).
  - **Cross-Reference-Spalte „Im Consent-Code-Manager"** zusätzlich zum On-Page-Status.
  - **Eigene Test-URL** (gleicher Host; SSRF-Schutz) statt nur Startseite.
  - **Google Fonts** separat: „Externe Google Fonts erkannt – Empfehlung: lokal hosten – Consent ersetzt kein Local Hosting."

### Changed

- `consent-codes.php`: Vendor-Erkennung als geteiltes `match_vendor()` (Manager-Badge + Scanner), Calendly ergänzt.
- Plugin-Header und `LSCC_VERSION` auf `0.3.1`. `LSCC_CONSENT_VERSION` bleibt `2`.

### Bewusst nicht enthalten

- Keine Maps/Vimeo-Umsetzung (nur Erkennung), kein Crawl, keine JS-Ausführung, kein Frontend-Code.

## 0.3.0-test - 2026-06-11

### Added

- **Consent-Code-Manager** (`includes/consent-codes.php`) — zentrale, consent-gegatete Verwaltung von Tracking-/Marketing-Snippets (GA4, GTM, Meta Pixel, Hotjar, weitere). Phase 1 der Produktiv-Roadmap.
  - „Paste-as-is": kompletter Vendor-Snippet einfügen, **Kategorie** + **Position** (Head/Body-Anfang/Footer) wählen. Beim Rendern werden `<script>`-Tags geblockt (`type="text/plain"` + `data-cookie-category`), `<noscript>` entfernt; Aktivierung nach Consent über die bestehende `banner.js`-Mechanik (kein neues Frontend-JS).
  - **Scannerfähiges Datenmodell** (`vendor/source/category/location`) mit automatischer Vendor-Erkennung + Badge.
  - **Repeater-Admin-UI** (minimales, dependency-freies Admin-JS) mit add/remove/↑/↓.
  - **Export/Import** als versioniertes JSON-Envelope (erweiterbar auf die gesamte LSCC-Konfiguration) für den Rollout über mehrere Websites.
- Neue Admin-Option-Quelle `lscc_consent_codes` (getrennt von `lscc_options`).

### Security / Datenschutz

- Roh-Code nur mit `unfiltered_html` speicherbar (sonst verworfen + Hinweis), `manage_options` + Nonce, Enum-validierte Attribute. Cache-sicher (serverseitig immer `text/plain`, Consent clientseitig). Konservativ blockend, **kein** Google Consent Mode v2.

### Changed

- Plugin-Header und `LSCC_VERSION` auf `0.3.0`. `LSCC_CONSENT_VERSION` bleibt `2`.

### Bewusst nicht enthalten

- Kein Scanner-Ausbau (Phase 2), kein Maps/Vimeo, keine neue Frontend-Logik, keine Änderung am Consent-Schema oder an `banner.js`.

## 0.2.4-test - 2026-06-11

### Added / UX

- **Aktiver Consent an den Schnellbuttons sichtbar.** Beim Öffnen des Dialogs zeigen „Alle akzeptieren" / „Nur notwendige" jetzt den aktuell gespeicherten Zustand: aktiver Button hervorgehoben (Ring + „✓"), inaktiver abgeschwächt. Vor der ersten Wahl bleiben beide neutral/gleichwertig.
  - `banner.js`: neue Anzeige-Funktion `updateQuickButtons()` (liest `getStoredConsent()`, rein darstellend; kein Schreibzugriff). Aufruf beim Laden, Öffnen und nach jedem Speichern.
  - `light-swiss-cookie-consent.php`: `aria-pressed` an den zwei Schnellbuttons.
  - `banner.css`: `.is-active` / `.is-inactive`.

### Changed

- Plugin-Header und `LSCC_VERSION` auf `0.2.4`. `LSCC_CONSENT_VERSION` bleibt `2`.

### Bewusst nicht enthalten

- Keine Änderung am Consent-Modell, an localStorage/Cookies, an `writeConsent()` oder an der Checkbox-Synchronisation. Kein Scanner, kein Maps/Vimeo.

## 0.2.3-test - 2026-06-11

### Fixed

- **Consent-UI lief auseinander (Bug 1).** Nach „Alle akzeptieren" → „Nur notwendige" → Speichern → Reload zeigten die Cookie-Einstellungen den falschen Häkchen-Zustand, obwohl der Consent korrekt gespeichert war. Ursache: Die Checkboxen wurden nur beim Öffnen des Banners synchronisiert; ohne `autocomplete="off"` stellte der Browser den alten Zustand nach dem Reload wieder her.
  - `banner.js`: `updateInputs(getStoredConsent())` läuft jetzt **beim Laden** und zusätzlich nach **jedem** Speichern (`saveAndClose`) → gespeicherter Consent ist die alleinige Quelle der Wahrheit; Top-Buttons und Checkboxen bleiben synchron.
  - `light-swiss-cookie-consent.php`: `autocomplete="off"` an allen vier Consent-Checkboxen.

### Changed

- Plugin-Header und `LSCC_VERSION` auf `0.2.3`. `LSCC_CONSENT_VERSION` bleibt `2`.

### Bewusst nicht enthalten

- Keine Änderungen an YOTU, Vimeo, Maps, Scanner oder i18n.

## 0.2.2-test - 2026-06-11

### Added

- **YOTU Consent Gating** (opt-in, Default AUS) — neues Modul `includes/yotu-compat.php` für das Plugin „Yotuwp – Easy YouTube Embed". Behebt Befund 3 (oben klickbares YouTube trotz „Nur notwendige").
  - **Phase 1:** Das Yotu-Frontend-Script (`yotu-script` + Inline `-extra`/`-after`) wird über `script_loader_tag` / `wp_inline_script_attributes` an die bestehende LSCC-Script-Blockade (`external_media`) gekoppelt.
  - **Phase 2:** Die Galerie-Thumbnails werden im Shortcode-Output neutralisiert (`data-orig-src` → `data-lscc-orig-src`), ein Consent-Hinweis wird über der Galerie angezeigt; `banner.js` stellt beides nach Consent wieder her.
- Neue Admin-Option **„YOTU-YouTube-Galerie (Yotuwp) vor Consent blockieren"** (`yotu_consent_gating`, Default **AUS**) in neuer Sektion „YOTU-Kompatibilität".
- Neuer Frontend-i18n-String „Diese YouTube-Galerie wird erst nach Zustimmung zu externen Medien geladen." in allen sechs Sprachen.

### Changed

- `assets/js/banner.js`: `activateBlockedScripts()` aktiviert gegatete Scripts jetzt **sequenziell** (externe Scripts `async=false`, nächster Knoten erst nach `load`) → korrekte Ausführungsreihenfolge bei Abhängigkeiten. Neue Funktion `restoreExternalMediaThumbnails()` (Thumbnail-Wiederherstellung + Hinweis ausblenden). Kein Consent-Schema-Wechsel.
- Plugin-Header und `LSCC_VERSION` auf `0.2.2`. `LSCC_CONSENT_VERSION` bleibt `2`.

### Security / Datenschutz

- Vor `external_media`-Consent entsteht bei aktiviertem Modul **kein** Request an youtube.com, youtube-nocookie.com, `iframe_api`, `www-widgetapi` oder `i.ytimg.com`. Nach Consent funktioniert YOTU normal.
- Kein DOM-Hijacking, kein MutationObserver, kein Scanner, keine `post_content`-Migration. Vollständig reversibel.

### Bekannte Grenzen

- Die Thumbnail-Neutralisierung greift bei per Shortcode gerenderten Galerien; reine Block-/Widget-Einbindungen sind separat zu prüfen. Inline-Script-Gating benötigt WordPress 5.7+.

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
