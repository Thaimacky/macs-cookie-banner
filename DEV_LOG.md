# DEV LOG

## 0.2.1-test - 2026-06-10

- Patch-Bump 0.2.0 → 0.2.1. Plugin-Header und `LSCC_VERSION` auf `0.2.1`. `LSCC_CONSENT_VERSION` bleibt `2` (kein Consent-Schema-Wechsel).
- **Bugfix WPML / Sprach-Mix (Live-Test-Befund 1 + 2).** Auf der EN-Seite erschienen Banner-Labels deutsch, während der Einleitungstext englisch war. **Root Cause bestätigt:** zwei auseinanderlaufende Übersetzungs-Mechanismen — die Locale-Tabelle bediente nur `banner_text` (folgte der Sprache), alle übrigen Strings und die Defaults der editierbaren Strings liefen über `__()`/`esc_html__()` und gaben mangels kompilierter `.mo` immer den deutschen Quelltext zurück. `.po` lagen nur als leere Skelette vor, `.mo` fehlten ganz. Umsetzung gemäss neuem ADR-19.
- `light-swiss-cookie-consent.php`:
  - **Alle sieben Text-Defaults** in `get_default_options()` kommen jetzt aus der Locale-Tabelle (vorher nur `banner_text`): `banner_title`, `banner_text`, `accept_all_text`, `necessary_only_text`, `settings_text`, `save_settings_text`, `reopen_text`.
  - Neuer Helper `get_neutral_text( $key, $locale = null )` (key-basiert, Fallback Englisch → leerer String). Neue Tabelle `get_default_text_table()` (pro Sprache `de/en/fr/it/tr/hu` eine Map aller sieben Keys). Ersetzt `resolve_neutral_banner_text_for_locale()` und `get_neutral_banner_text_table()`; `get_neutral_banner_text()` bleibt als dünner Backwards-Compat-Wrapper. `extract_language_prefix()` unverändert.
  - WPML-/Polylang-String-Translation-Registrierung (`register_wpml_strings`) und `get_translated_option()` **unverändert** — bleiben als Override vorrangig.
- `languages/`:
  - Alle sechs `.po` befüllt und sechs `.mo` **kompiliert** (vorher nur leere Skelette, keine `.mo`). Frontend-/besucherseitige Strings (Kategorie-Labels/-Beschreibungen, Rechtslinks `Datenschutz`/`Impressum`/`Datenschutz & Impressum`, Service-Komponenten-Texte inkl. „Externe Medien akzeptieren") in allen sechs Sprachen übersetzt; Admin-only-Strings bleiben deutsche Quelle (Operator-Sprache, bewusster Scope-Entscheid — siehe ADR-19).
  - `.pot` neu aus den realen Quelltext-Callsites auditiert (158 msgids). Vier editierbare Strings ohne verbleibenden `__()`-Callsite entfielen korrekt (`Cookie-Einstellungen`, `Alle akzeptieren`, `Nur notwendige`, `Auswahl speichern`); v0.1.9/v0.2.0-Admin-Strings (Avada-/Externe-Medien-Sektion, Inventar-Scan) ergänzt.
  - `.mo`-Erzeugung über ein einmaliges Python-Generator-Skript **ausserhalb** des Repos (kein Build-System im Repo; `.po` bleiben die lesbare Quelle). Standard-GNU-`.mo`-Format, nach msgid sortiert; nur Einträge mit echter Übersetzung, fehlende fallen auf die deutsche Quelle zurück.
- **Bewusst NICHT angefasst:** Befund 3 (YouTube-Konsistenz), Befund 4 (Modal-Design), banner.js, CSS, Consent-Schema, Avada-Interception.
- Dokumentation aktualisiert: `MASTER_HANDBUCH.md` (Versionshistorie + neue Sektion „Dokumentationspflicht (Definition of Done)"), `ACTIVE_CODE_MAP.md` (neue Helfer/Tabelle, Languages-Dateien), `DECISIONS.md` (ADR-19), `RELEASE_CHECKLIST.md` (WPML/i18n-Testpunkte), `CHANGELOG.md` (0.2.1-test).
- Validierung: PHP-Lint lokal nicht ausführbar (kein PHP CLI); manuelle Syntax-/Brace-Prüfung der geänderten Methoden. Alle sechs `.mo` mit Pythons `gettext.GNUTranslations` gegengeparst — Übersetzungen lösen korrekt auf (de_CH identisch, en/fr/it/tr/hu Frontend übersetzt, Admin-Fallback liefert deutsche Quelle). Funktionaler Re-Test auf `plugins.svogellisi.ch/de|en` steht aus (siehe RELEASE_CHECKLIST): kein Sprach-Mix mehr, „Cookie-Einstellungen"-Button unten rechts in der aktiven Sprache.

## 0.2.0-test - 2026-06-03

- MINOR-Bump 0.1.9 → 0.2.0 (Feature-Set rund um den nativen LSCC-YouTube-Block). Plugin-Header und `LSCC_VERSION` auf `0.2.0`. `LSCC_CONSENT_VERSION` bleibt `2`.
- **Feature: nativer LSCC-YouTube-Block** als empfohlener Weg für neue Sites. Umsetzung gemäss neuem ADR-18 (schränkt ADR-14 für ein opt-in Remote-Thumbnail ein).
- `includes/service-components.php`:
  - `render_youtube()` akzeptiert `title` und (über neuen public Helper `extract_youtube_id()`) auch YouTube-URLs in `id`.
  - Neuer privater Helper `resolve_youtube_thumbnail_html()`: lokales `thumbnail_id` hat Vorrang; sonst nur bei aktivierter Option `youtube_remote_thumbnails` ein `i.ytimg.com`-Bild; sonst Platzhalter.
  - `render_component()` zeigt den Play-Button jetzt immer für YouTube/Vimeo (vorher nur mit Thumbnail) und markiert ihn mit `data-lscc-autoplay`.
- `assets/js/banner.js` (minimal, gekapselt): `bindMediaComponents()` setzt `data-lscc-autoplay-now` auf der Komponente, wenn der Play-Button geklickt wurde; `createMediaIframe()` hängt dann `autoplay=1` an (nur YouTube/Vimeo). `node --check` grün. Consent-Schema unverändert.
- `light-swiss-cookie-consent.php`: neue Bool-Option `youtube_remote_thumbnails` (Default `false`) in `get_default_options()` + `get_bool_option_keys()`.
- `includes/admin-page.php`: neue Sektion „Externe Medien" mit Checkbox + deutlicher Datenschutz-Beschreibung (i.ytimg.com lädt vor Consent → IP an Google).
- `includes/avada-compat.php`: nutzt jetzt den zentralen `Service_Components::extract_youtube_id()` statt eigener Kopie (DRY); v0.1.9-Verhalten unverändert.
- **Datenschutz:** Default-Verhalten bleibt ADR-14-konform (kein externes Bild). Remote-Thumbnail ist opt-in/Default-AUS; auch dann kein iframe/iframe_api/www-widgetapi/youtube.com-Cookie vor Consent.
- **Bewusst NICHT:** Vimeo-Remote-Thumbnail (bräuchte Vimeo-API), kein DOM-Hijacking/Observer, keine Consent-Schema-Änderung, kein Content-Rewrite.
- Dokumentation: `CHANGELOG.md` (0.2.0-test), `ACTIVE_CODE_MAP.md`, `DECISIONS.md` (ADR-18), `RELEASE_CHECKLIST.md`.
- Validierung: `node --check banner.js` OK; PHP-Lint lokal nicht ausführbar (kein PHP CLI), manuelle Prüfung + Brace/Paren-Balance grün. Funktionaler Test (siehe RELEASE_CHECKLIST) ausstehend: „Nur notwendige" → keine youtube/ytimg/iframe_api-Requests (bei Thumbnail AUS auch kein ytimg).

## 0.1.9-test - 2026-06-03

- Patch-Bump 0.1.8 → 0.1.9. Plugin-Header und `LSCC_VERSION` auf `0.1.9`. `LSCC_CONSENT_VERSION` bleibt `2`.
- **Feature: Avada-`fusion_youtube` Consent-Gating** (Umsetzung der ADR-16-Richtung für YouTube; siehe neuer ADR-17). Auslöser: realer Test (Avada/Daniela-Baumann) zeigte, dass Avada-YouTube trotz `external_media=false` lud (iframe_api, www-widgetapi.js, YouTube-Cookies).
- **Geklärt vor Umsetzung (Stop wegen Consent-/Architektur-Konflikt):** Consent-Kategorie. Auftraggeber-Entscheid = `external_media` (konsistent mit `[lscc_youtube]`), nicht `marketing`.
- Neue Datei `includes/avada-compat.php`, Klasse `Light_Swiss_Cookie_Consent_Avada_Compat`:
  - `init()` registriert nur im Frontend + bei aktivierter Option `pre_do_shortcode_tag`.
  - `intercept()` ersetzt `fusion_youtube` durch `Service_Components::render_youtube( array('id'=>$id) )` (Kurzschluss vor iframe-Erzeugung). Fallback: bei nicht parsebarer ID rendert Avada normal weiter.
  - `extract_video_id()` akzeptiert rohe IDs und YouTube-URLs.
- `light-swiss-cookie-consent.php`: neue Bool-Option `avada_youtube_block` (Default `true`) in `get_default_options()` + `get_bool_option_keys()`; Modul-Laden in `init()`.
- `includes/admin-page.php`: neue Sektion „Avada-Kompatibilität" mit Checkbox `avada_youtube_block` + Beschreibung.
- **Bewusst NICHT umgesetzt:** Vimeo, Maps, Background-Videos, `fusion_code`, rohe iframes; keine `post_content`-Migration; kein DOM-Hijacking/Observer/Scanner; kein neuer Consent-Code; banner.js/CSS unverändert.
- Reuse: bestehende Platzhalter-/JS-Mechanik (`syncMediaComponents`/`createMediaIframe`) baut das iframe erst nach `external_media`-Consent — kein JS-Change nötig.
- Dokumentation aktualisiert: `CHANGELOG.md` (0.1.9-test), `ACTIVE_CODE_MAP.md` (neue Datei/Klasse/Tabelle), `DECISIONS.md` (ADR-17), `RELEASE_CHECKLIST.md` (Testpunkte).
- Validierung: PHP-Lint lokal nicht ausführbar (kein PHP CLI). Manuelle Syntax-/Logikprüfung; statisch keine externen Requests/Schreibzugriffe im neuen Modul. Funktionaler Re-Test auf Avada/Daniela-Baumann steht aus (siehe RELEASE_CHECKLIST): „Nur notwendige" darf keine YouTube-Requests/Cookies mehr erzeugen.

## 0.1.8-test - 2026-06-03

- Patch-Bump 0.1.7 → 0.1.8. Plugin-Header und `LSCC_VERSION` auf `0.1.8`. `LSCC_CONSENT_VERSION` bleibt `2`.
- **Feature: Read-only „Avada Inventar-Scan"** (Admin-Submenu). Misst die Verteilung von Video-/Map-/Embed-Typen in bestehenden Inhalten, um die realistisch automatisch abdeckbare Quote (Ziel 80–95 %) vor dem Bau eines Avada-Kompatibilitätsmoduls zu beziffern. Umsetzung der zuvor freigegebenen Inventar-Scan-Spezifikation.
- Neue Datei `includes/avada-inventory.php`, Klasse `Light_Swiss_Cookie_Consent_Avada_Inventory`:
  - `render_page()` (admin-only, Nonce `lscc_avada_inventory`), `run_inventory_scan()` (`WP_Query`, `posts_per_page=500`, Truncation-Hinweis bei `found_posts > 500`), `analyze_content()` (Element-Klassifizierung + Diagnostik), `get_inventory_post_types()` (post/page/public CPTs + Avada-CPTs sofern vorhanden), `render_results()`, `percent()`.
  - Erfasst: `[fusion_youtube`, `[fusion_vimeo`, `[fusion_map` (minus `[fusion_map_marker`), Background-Video via `video_url="..."` (YT/Vimeo vs. self-hosted), rohe `<iframe>` mit Same-Origin-Klassifizierung, `[fusion_code]` inkl. base64-Tiefpass, oEmbed (nackte URL als eigene Zeile), Diagnostik-Rohtreffer.
  - KPIs: `Abdeckung_min` (YT+Vimeo+oEmbed), `Abdeckung_max` (+ fusion_map mit Script-Gating).
- `includes/admin-page.php`: `require_once` für `avada-inventory.php` + neues `add_submenu_page` „Avada Inventar-Scan" (Slug `light-swiss-cookie-consent-avada-inventory`).
- **Strikt read-only, ADR-4-konform:** keine externen Requests, keine Schreibzugriffe, keine Inhaltsänderung, keine Migration, kein Blocking/Consent. Bestehende Funktionen (Consent, Service-Komponenten, Privacy Check) unverändert. Es wurde **kein** Avada-Interception- oder Block-Mechanismus gebaut — nur Messung.
- Dokumentation aktualisiert: `CHANGELOG.md` (0.1.8-test), `ACTIVE_CODE_MAP.md` (neue Datei, Klasse, Menü), `RELEASE_CHECKLIST.md` (Testpunkte).
- Validierung: PHP-Lint lokal nicht ausführbar (kein PHP CLI). Manuelle Syntax-/Logikprüfung; statisch keine externen Requests/Schreibzugriffe im Modul. Funktionaler Test in echter Avada-WP-Installation steht aus (siehe RELEASE_CHECKLIST).

## 0.1.7-test - 2026-06-03

- Versionsbump 0.1.6 → 0.1.7. Plugin-Header und `LSCC_VERSION` auf `0.1.7`. `LSCC_CONSENT_VERSION` bleibt `2` (kein Consent-Schema-Wechsel).
- **Feature: Lokales Thumbnail für `[lscc_vimeo]`** — konsistente Ausweitung des YouTube-Musters aus v0.1.6 auf Vimeo. Neues Shortcode-Attribut `thumbnail_id`.
- `includes/service-components.php`: nur `render_vimeo()` geändert — `shortcode_atts` um `thumbnail_id` (Default `''`) erweitert und `self::get_local_thumbnail_html( $atts['thumbnail_id'] )` als 5. Argument an `render_component()` durchgereicht. **Kein** neuer Helper, **keine** neuen CSS-Klassen, **keine** JS-Änderung, **kein** Consent-Umbau — `get_local_thumbnail_html()`, `render_component()`, `.lscc-media__thumb` und `.lscc-media__play` werden 1:1 wiederverwendet.
- **Bewusst NICHT umgesetzt** (Scope-Disziplin): kein `thumbnail="URL"`, kein Auto-Fetch, keine Vimeo-API, keine externen Bildquellen, kein Maps-Thumbnail, kein Accessibility-Thema. Google Maps bleibt ohne `thumbnail_id`.
- ADR-14 „Folgen"-Abschnitt aktualisiert (Vimeo nun einbezogen); keine neue ADR nötig, da die Architekturentscheidung unverändert gilt.
- Dokumentation aktualisiert: `CHANGELOG.md` (0.1.7-test), `ACTIVE_CODE_MAP.md` (render_vimeo + Shortcode-Beispiel), `DECISIONS.md` (ADR-14-Folgen), `RELEASE_CHECKLIST.md` (Vimeo-Thumbnail-Testpunkte).
- Validierung: PHP-Lint lokal weiterhin nicht ausführbar (kein PHP CLI); Änderung ist strukturell identisch zur bereits validierten YouTube-Variante. Funktionaler Test in echter WP-Installation steht aus (siehe RELEASE_CHECKLIST).

## 0.1.6-test - 2026-06-03

- Versionsbump 0.1.5 → 0.1.6 (neues Feature, MINOR-würdig). Plugin-Header und `LSCC_VERSION` auf `0.1.6` gesetzt. `LSCC_CONSENT_VERSION` bleibt `2` — das Consent-Schema ist unverändert, daher kein erzwungenes Re-Consent.
- **Feature: Lokales Thumbnail für `[lscc_youtube]`.** Neues Shortcode-Attribut `thumbnail_id`. Vor Consent wird das WordPress-Mediathek-Bild + grosser Play-Button gezeigt; Hinweistext und Accept-Button bleiben sichtbar. Nach Consent unverändertes iframe-Verhalten. Umsetzung gemäss neuem ADR-14.
- `includes/service-components.php`:
  - `render_youtube()` akzeptiert zusätzlich `thumbnail_id` (Default `''`).
  - Neuer privater Helper `get_local_thumbnail_html()`: `absint()` + `get_post_type() === 'attachment'` + `wp_attachment_is_image()`; rendert via `wp_get_attachment_image( $id, 'large', false, [class, loading=lazy] )`. Liefert `''` bei ungültiger ID → stiller Fallback auf den bisherigen Platzhalter.
  - `render_component()` erhält optionalen 5. Parameter `$thumbnail_html`. Bei vorhandenem Thumbnail: Container-Klasse `lscc-media--has-thumb`, `<img>` als Hintergrundebene plus zentrierter `.lscc-media__play`-Button (trägt `data-lscc-accept-media`, wird vom bestehenden `bindMediaComponents()` gebunden — **kein** JS-Change).
- `assets/css/banner.css`: neue Klassen `.lscc-media__thumb` (absolut, `object-fit: cover`), `.lscc-media--has-thumb .lscc-media__placeholder` (Scrim `rgba(0,0,0,0.45)` für Lesbarkeit), `.lscc-media__play` (72×72 runder Button, CSS-Dreieck via `::before`, `:focus-visible`-Outline). Keine Animationen, keine Libraries.
- **Bewusst NICHT umgesetzt** (Scope-Disziplin): kein `thumbnail="URL"`, keine URL-Erkennung, kein Auto-Fetch aus der Video-ID, kein `img.youtube.com`/`ytimg.com`, keine externen Bildquellen, keine Änderung am Consent-System oder an `createMediaIframe`/`syncMediaComponents`/`acceptExternalMedia`. Vimeo/Maps unverändert.
- Dokumentation aktualisiert: `CHANGELOG.md` (0.1.6-test), `ACTIVE_CODE_MAP.md` (Helper, Attribut, CSS-Klassen, Shortcode-Beispiel), `DECISIONS.md` (ADR-14), `RELEASE_CHECKLIST.md` (Testpunkte Thumbnail).
- Validierung: PHP-Lint lokal nicht ausführbar (kein PHP CLI auf dem Dev-Rechner); `sprintf`-Platzhalter und Escaping manuell geprüft; statische Suche bestätigt keine externen Bild-Hosts/Fetch-Funktionen im Code. Funktionaler Test in echter WP-Installation steht noch aus (siehe RELEASE_CHECKLIST).

## Performance-Pruefplan - 2026-06-03

- Performance-/PageSpeed-Pruefplan fuer v0.1.5-test vorbereitet. **Keine Codeaenderungen durchgefuehrt** (kein Commit, kein Push, keine neuen Features) — ausschliesslich statische Analyse plus Dokumentation.
- Statische Asset-Messung (lokal): `assets/js/banner.js` 14.0 KB roh / ~3.4 KB gzip; `assets/css/banner.css` 6.5 KB roh / ~1.6 KB gzip. Zwei zusaetzliche Frontend-Requests (CSS + JS); Inline-CSS-Variablen und `wp_localize_script` erzeugen keine weiteren Requests.
- JS-Analyse: Script via `wp_enqueue_script(..., true)` im Footer (nicht render-blocking), einmalige Init bei `DOMContentLoaded`, kein MutationObserver, kein Polling, kein `setInterval`, nur ein `setTimeout(0)` fuer Fokus-Handling, keine Scroll-/Resize-/Mousemove-Listener. Keine Dauer-Runtime-Kosten nach Consent.
- CSS-Analyse: Banner-Root, Overlay und Reopen-Button sind `position: fixed` und initial `hidden` → kein CLS-Risiko. `backdrop-filter: blur()` nur auf `.lscc-overlay--blur` und nur bei sichtbarem Overlay (Default 4 px, konservativ). Einziger identifizierter Beobachtungspunkt: Blur-Repaint auf schwacher Mobile-Hardware → in Lighthouse-Szenario B separat zu pruefen.
- `RELEASE_CHECKLIST.md` um Sektion „Performance / PageSpeed (ab v0.1.5-test)" ergaenzt (Szenarien A–D, Mobile/Desktop, CLS- und TBT-Kriterien, Overlay/Blur-Separattest).
- Ergebnis der statischen Pruefung: keine Codeaenderung erforderlich. Das Plugin verschlechtert PageSpeed/CWV nach statischer Analyse nicht unnoetig. Optionale, nicht beauftragte Mikro-Optimierungen unten nur dokumentiert, nicht umgesetzt.

## 0.1.5-test - 2026-05-28

- Patch-Bump von 0.1.4 auf 0.1.5. Begründung: über reinen Bugfix hinaus wurde eine neue Konstante (`LSCC_CONSENT_VERSION`), eine neue Admin-Option (`consent_lifetime_days`), ein erweitertes Consent-Schema (`expiresAt`) und ein Defaults-Merge-Fix in `sanitize_options()` eingeführt; zusätzlich Master-Datei umbenannt. Eine saubere Patch-Iteration macht den Versionssprung im WP-Admin nachvollziehbar.
- **Root Cause für „Banner erscheint nach Plugin-Update nicht":** `consentVersion` war in `enqueue_assets()` hartkodiert als `'1'` — derselbe Wert wie in v0.1.0 – v0.1.4. Browser mit einem v1-Consent akzeptierten den neuen Build weiterhin als gültig. Plugin-Deinstallation hat den Browser-Storage nicht angefasst.
- Neue Konstante `LSCC_CONSENT_VERSION` (Default `2` für v0.1.5) eingeführt; getrennt von `LSCC_VERSION`. `enqueue_assets()` übergibt sie an `lsccSettings.consentVersion`. Alle v1-Browser-Consents werden dadurch invalidiert.
- Neue Admin-Option `consent_lifetime_days` (Default 180, Range 1 – 365) in `get_default_options()` und `get_int_option_keys()`. Sichtbar in neuer Admin-Sektion „Consent-Speicherung".
- `assets/js/banner.js`:
  - `consentVersion`-Default auf `2`, `lifetimeDays`-Default auf `180`, beide aus `lsccSettings` lesbar.
  - `isValidConsent()` prüft jetzt zusätzlich `expiresAt` (wenn vorhanden) und `createdAt + lifetimeDays * 86400000`. Damit lässt sich die Lifetime auch retroaktiv durch Admin-Änderung verkürzen.
  - `getDefaultConsent()` schreibt `expiresAt = now + lifetimeDays * 86400000` als ISO-String.
  - `normalizeConsent()` übernimmt ein vorhandenes `expiresAt` aus dem Eingangs-Consent.
  - `writeConsent()` setzt `Max-Age = lifetimeDays * 86400` statt fixer 180-Tage.
- `Light_Swiss_Cookie_Consent::sanitize_options()`:
  - Bool-Block prüft jetzt `array_key_exists()` und fällt bei fehlendem Key auf den Default zurück (Fix gegen ungewolltes `false` bei Migrationen alter Optionen).
- `Light_Swiss_Cookie_Consent_Admin::save_settings()`:
  - Vor `sanitize_options()` werden fehlende Bool-Keys explizit als leere Strings im POST eingefügt, damit unchecked Checkboxen verlässlich als `false` interpretiert werden.
- Master-Datei umbenannt: `CLAUDE_CONTINUITY_MASTER.md` → `MASTER_HANDBUCH.md`. Inhalt 1:1 erhalten, Versionshistorie um den Rename-Eintrag erweitert. Referenzen in `PROJECT_BRIEF.md`, `DECISIONS.md` und in dieser DEV_LOG-Datei aktualisiert.
- Dokumentation aktualisiert: `README.md` (Sektion „Consent-Speicherung" inkl. Hinweise zu Ctrl+F5 und Plugin-Deinstallation), `ACTIVE_CODE_MAP.md` (Konstanten-Abschnitt), `DECISIONS.md` (neuer ADR-13), `CHANGELOG.md` (Fixed + Added + Changed), `RELEASE_CHECKLIST.md` (Testpunkte für Consent-Reset, Lifetime, Inkognito).
- Installierbare Test-ZIP `light-swiss-cookie-consent-v0.1.5-test.zip` aus dem aktuellen Stand neu erzeugt.

## 0.1.4-test - 2026-05-28

- Testversion 0.1.4 vorbereitet.
- Plugin-Header `Version` und Konstante `LSCC_VERSION` auf `0.1.4` gesetzt.
- **Task A — Overlay/Blur:** Banner kann optional die Seite leicht abdunkeln und blurren. Konfigurierbar via fünf neue Admin-Optionen (`overlay_enabled`, `overlay_color`, `overlay_opacity`, `blur_enabled`, `blur_strength`). Overlay-Element wird im Markup nur ausgegeben, wenn aktiviert; initial `hidden`. `.lscc-overlay` ist `position: fixed; inset: 0; pointer-events: none;` und löst weder Scroll-Lock noch Klick-Blocking aus. `backdrop-filter: blur(var(--lscc-blur))` wird nur auf `.lscc-overlay--blur`-Variante angewendet — und auch nur wenn das Element sichtbar ist (display:none wenn hidden).
- **Task B — Floating-Button-Position:** Vier feste Positionen (`bottom-right` / `bottom-left` / `top-right` / `top-left`) plus `reopen_offset_x` und `reopen_offset_y` (clamp 0–200). Im Markup ist `data-position` am Reopen-Button gesetzt; `.lscc-reopen[data-position="..."]` definiert pro Position die richtigen `top`/`bottom`/`left`/`right`-Werte aus den CSS-Variablen `--lscc-reopen-ox` / `--lscc-reopen-oy`. Keine JS-Repositionierung.
- **Task C — Legal Links:** Neue Optionen `show_legal_links`, `privacy_url_override`, `imprint_url_override`. Privacy-URL nutzt zuerst den Override, sonst `get_privacy_policy_url()`. Imprint-URL nutzt zuerst den Override, sonst den Transient `lscc_detected_imprint_url`. Erkennung läuft ausschliesslich im Admin via `admin_init`-Hook → `maybe_refresh_imprint_detection()` → `scan_imprint_pages()` mit acht `get_page_by_path()`-Lookups und sechs Title-`WP_Query`-Lookups. Cache TTL `DAY_IN_SECONDS`. Anzeige-Logik: identische URLs → ein Link „Datenschutz & Impressum"; sonst je nach Verfügbarkeit ein oder zwei Links; nichts wenn keine URL vorhanden.
- **Task D — Settings-Button-UX:** In `assets/js/banner.js` setzt `setBannerVisible()` jetzt `mainActions.hidden = false` (statt Boolean(showSettings)) und blendet stattdessen gezielt `[data-lscc-open-settings]` aus, wenn das Settings-Panel geöffnet ist.
- Neue zentrale Sanitization in `Light_Swiss_Cookie_Consent::sanitize_options()`: zusätzlich zu Text und Color jetzt auch Bool, Int (mit min/max-Clamp), Float (mit min/max-Clamp), URL (`esc_url_raw`) und Enum.
- Neuer Helper `hex_with_opacity_to_rgba()` baut für das Overlay `rgba(r, g, b, a)` aus Hex + Float.
- `get_css_variables()` schreibt zusätzlich `--lscc-overlay-bg`, `--lscc-blur`, `--lscc-reopen-ox`, `--lscc-reopen-oy` in den Inline-Style.
- `assets/css/banner.css` um Overlay-, Position- und Legal-Link-Styles ergänzt (≤ 60 zusätzliche Zeilen). Mobile-Media-Query setzt jetzt die Reopen-Offsets via CSS-Variable auf 10 px.
- `includes/admin-page.php` um vier neue Render-Helper erweitert (`render_checkbox_field`, `render_number_field`, `render_select_field`, `render_url_field`). Drei neue Settings-Sektionen: `Overlay & Blur`, `Floating-Button`, `Rechtliche Links`.
- POT-Datei um 23 neue i18n-Strings erweitert.
- Dokumentation aktualisiert: `README.md` (zwei neue Sektionen), `ACTIVE_CODE_MAP.md` (neue Methoden + Optionstabelle), `DECISIONS.md` (neuer ADR-12), `RELEASE_CHECKLIST.md` (Testpunkte für die neuen Features).
- Performance-Vorgaben eingehalten: keine zusätzlichen Event-Listener, keine MutationObserver, kein Polling, kein Frontend-Scan; Overlay nur sichtbar wenn Banner sichtbar; Blur konservativ voreingestellt (4 px); Reopen-Position via CSS-Selektor; Imprint-Detection nur im Admin mit Cache.

## 0.1.3-test - 2026-05-28

- Testversion 0.1.3 vorbereitet.
- Plugin-Header `Version` und Konstante `LSCC_VERSION` auf `0.1.3` gesetzt.
- UX-Bugfix Banner-Settings: doppelte `Alle akzeptieren` / `Nur notwendige` Buttons im Settings-Modus entfernt. Im Markup (`light-swiss-cookie-consent.php`) bleibt im `.lscc__actions--settings`-Block nur der `Auswahl speichern`-Submit-Button; in `assets/js/banner.js` sind die zugehoerigen `data-lscc-settings-accept-all` / `data-lscc-settings-necessary` Event-Listener entfernt.
- Privacy Check Content Scan v0.2 in `includes/privacy-check.php` ergaenzt: neue private Methoden `render_content_scan_section`, `render_content_scan_results`, `run_content_scan`, `get_scannable_post_types`, `get_post_type_label`, `get_content_scan_patterns`. Button-getriggert ueber `lscc_run_content_scan` POST mit Nonce `lscc_content_scan` und `current_user_can('manage_options')`.
- Content Scan arbeitet ausschliesslich lokal: ein `WP_Query` ueber `post`, `page` und alle public Custom Post Types, `post_status=publish`, `posts_per_page=200`. Keine externen Requests, kein Crawler, kein Auto-Block.
- POT-Datei um 23 neue i18n-Strings fuer Content-Scan-UI, Post-Type-Labels, Service-Namen und Empfehlungen erweitert.
- `README.md` um Sektion `Privacy Check` ergaenzt (Startseiten-Pruefung + Content Scan).
- `RELEASE_CHECKLIST.md` um Content-Scan- und UX-Bugfix-Checkpunkte ergaenzt.
- `ACTIVE_CODE_MAP.md` im Abschnitt `includes/privacy-check.php` um die neuen Methoden erweitert.
- `CHANGELOG.md` um Eintrag `0.1.3-test` erweitert.
- Installierbare Test-ZIP `light-swiss-cookie-consent-v0.1.3-test.zip` aus dem aktuellen `main`-Stand neu erzeugt.
- i18n- / Locale-Task umgesetzt (immer noch v0.1.3, kein Versionsbump):
  - Neue Locale-Tabelle `Light_Swiss_Cookie_Consent::get_neutral_banner_text_table()` mit neutralen Default-Banner-Texten für `de`, `en`, `fr`, `it`, `tr`, `hu`.
  - Neue Helper `get_neutral_banner_text()`, `resolve_neutral_banner_text_for_locale()` und `extract_language_prefix()`. Akzeptieren Locale-Varianten wie `de_CH`, `de-CH`, `de_DE`, `de_AT`, `en_GB`, `pt_BR`; mappen auf 2- bis 3-Buchstaben-Sprachpräfix; Fallback auf Englisch bei unbekannten Sprachen.
  - `get_default_options()` ruft beim Plugin-Default `get_neutral_banner_text()` statt des hartcodierten Sie-Texts.
  - Alter Sie-Text aus PHP-Default entfernt; entsprechende msgid aus POT entfernt (verwaiste Übersetzung vermieden).
  - Bestehende ASCII-Workarounds in deutschen UI-Texten umgestellt: `Pruefen` -> `Prüfen`, `Sicherheitspruefung` -> `Sicherheitsprüfung`, `Primaerbutton` -> `Primärbutton`, `Sekundaerbutton` -> `Sekundärbutton`, `Laedt` -> `Lädt`, `Fuer` -> `Für`, `ueber` -> `über`, `veroeffentlichten` -> `veröffentlichten`, `Beitraegen` -> `Beiträgen`, `oeffentlichen` -> `öffentlichen`, `spaeter` -> `später`, `Ungueltige` -> `Ungültige`. POT-msgids synchron geändert.
  - Schweizer Schreibweise eingehalten: alle deutschen Texte mit echten Umlauten, kein ß.
  - `README.md`, `ACTIVE_CODE_MAP.md`, `DECISIONS.md` (neuer ADR-11), `CHANGELOG.md` ergänzt.

## 0.1.1-test - 2026-05-28

- Testversion 0.1.1 vorbereitet.
- Plugin-Header `Version` und Konstante `LSCC_VERSION` auf `0.1.1` gesetzt.
- `CHANGELOG.md` um Eintrag `0.1.1-test` erweitert.
- Installierbare Test-ZIP `light-swiss-cookie-consent-v0.1.1-test.zip` aus dem aktuellen `main`-Stand neu erzeugt (ohne `.git`, `.vscode`, andere ZIPs, temporaere Dateien).

## 0.1.0 - 2026-05-28

- Dokumentationsstruktur konsolidiert:
  - `PROJECT_BRIEF.md` im Plugin-Ordner angelegt.
  - `ACTIVE_CODE_MAP.md` im Plugin-Ordner angelegt.
  - `DECISIONS.md` im Plugin-Ordner angelegt.
- Root-Stubs bereinigt: leere RTF-Stubs (`PROJECT_BRIEF.md`, `ACTIVE_CODE_MAP.md`, `DECISIONS.md`, `DEV_LOG.md`, `RELEASE_CHECKLIST.md`) im Projekt-Root geloescht. Die echten Markdown-Dateien liegen ausschliesslich im Plugin-Ordner.
- `light-swiss-cookie-consent-v0.1.0.zip` im Root unveraendert belassen.
- `MASTER_HANDBUCH.md` von RTF nach echtes UTF-8-Markdown migriert. Inhalt unveraendert; RTF-Steuerzeichen entfernt, deutsche Umlaute und typografische Anfuehrungszeichen hergestellt; Versionssektion am Anfang ergaenzt.
- Master-Datei in `PROJECT_BRIEF.md` und `DECISIONS.md` als verpflichtende Lektuere referenziert.
- Hinweis: `MASTER_HANDBUCH.md` gilt ab jetzt als verpflichtende Vorrangs- und Kontinuitaetsquelle bei Architektur-, Scope- und Philosophie-Entscheidungen.

## 0.1.0 - 2026-05-27

- Sicherheits- und Architekturpruefung durchgefuehrt.
- Erste Haertungen umgesetzt:
  - zentrale Options-Sanitization
  - strengere Consent-Strukturpruefung
  - strengere Kategoriepruefung
  - sichereres Script-Attribut-Handling
  - `SameSite=Lax` und `Secure` bei HTTPS fuer Consent-Cookie
- Version `0.1.0` vorbereitet.
- `LSCC_DEBUG` vorbereitet.
- Shortcode `[simple_cookie_settings]` fuer Widerruf/Einstellungen vorbereitet.
- Sprachstruktur fuer `de_CH`, `en_US`, `fr_FR`, `it_IT`, `tr_TR` und `hu_HU` vorbereitet.
- Git-/Release-Grundlagen vorbereitet.
- Privacy Check v0.1 vorbereitet.
- Service-Komponenten v0.1 vorbereitet.
