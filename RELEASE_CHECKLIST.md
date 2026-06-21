# Release Checklist

## Release-Artefakte / Ablageort für Test-ZIPs (verbindlich)

- [ ] Test-ZIP wird im **Überordner des Repositories** abgelegt: `G:\Cookie Banner Plugin\` — **nicht** im Repo-Ordner `macs-cookie-banner\`.
- [ ] Liegen ältere ZIPs bereits im Parent-Ordner, wird die neue ZIP **im selben Ordner** erstellt.
- [ ] Dateiname-Schema: `macs-cookie-banner-v<VERSION>-test.zip`.
- [ ] Top-Level-Ordner im ZIP: `macs-cookie-banner/` (WordPress-installierbar).
- [ ] Kein automatisches Ausweichen auf andere Orte; Ablageort nur per ausdrücklicher Auftraggeber-Anweisung änderbar. Details siehe `MASTER_HANDBUCH.md`, Sektion „Release-Artefakte / Ablageort für Test-ZIPs".

## Auto-Sync-Entscheidung erzwungen bei Aktivierung/Update (ab v0.5.13, ADR-33)

- [ ] **Aktivierung:** Plugin auf Avada-Site neu aktivieren → unmittelbar danach landet man auf der Einstellungsseite mit der Entscheidungsfrage (Ja/Nein).
- [ ] **Update:** Bestehende Installation (entschieden noch nicht/zurückgesetzt) auf 0.5.13 updaten → beim nächsten Admin-Aufruf erzwungener Redirect zur Frage.
- [ ] Frage ist **nicht** schließbar und bleibt auf jeder Admin-Seite sichtbar, bis entschieden.
- [ ] Kein Redirect-Loop; man kann weiterhin zur Plugins-Seite (Deaktivieren möglich).
- [ ] Kein Redirect bei AJAX, `admin-post.php`, POST oder Bulk-Aktivierung.
- [ ] „Ja"/„Nein" → Entscheidung gespeichert; Frage erscheint **nie wieder** (auch nach Reload/erneutem Update derselben Version).
- [ ] Nicht-Avada-Site: keine Frage, kein Redirect.
- [ ] Checkbox „… automatisch synchronisieren" und „Jetzt synchronisieren" unverändert vorhanden.

## Avada Auto-Sync (ab v0.5.12, ADR-32)

**Erstabfrage:**
- [ ] Frische Site mit aktivem Avada, noch keine Entscheidung → Admin-Notice „Avada wurde erkannt … automatisch synchronisieren?" mit Ja/Nein erscheint.
- [ ] „Ja" → Auto-Sync EIN, Banner-Farben sofort = Avada Primary Color; Notice verschwindet dauerhaft.
- [ ] „Nein" → Auto-Sync AUS, keine Farbänderung; Notice verschwindet dauerhaft.
- [ ] Nach Entscheidung erscheint die Erstabfrage nicht erneut (auch nach Reload/Update).

**Einstellung (Avada-Integration):**
- [ ] Checkbox „Banner-Farben automatisch mit Avada synchronisieren" spiegelt den gespeicherten Zustand; „Einstellung speichern" persistiert.
- [ ] „Jetzt synchronisieren" übernimmt die aktuelle Primary Color sofort (manuell, unabhängig vom Schalter).

**Verhalten:**
- [ ] Auto-Sync EIN: Avada Primary Color ändern → nächster Admin-Seitenaufruf übernimmt die neue Farbe automatisch (Cache-Reset greift).
- [ ] Auto-Sync AUS: Avada Primary Color ändern → Banner-Farben bleiben unverändert.
- [ ] **Regel:** Bei AUS überschreibt nichts (auch kein Update) die manuell gesetzten Bannerfarben.
- [ ] Primary als Global Color `var(--awb-colorX)` + Auto-Sync EIN: Status-Hinweis „… einmal Jetzt synchronisieren" (Server kann var nicht auflösen); manueller Button übernimmt sie.
- [ ] Keine Änderung an Consent/Locale/Scanner/CCM/Updater/Presets/Importlogik.

## Sichtbarer Button folgt Primary Color in allen Presets (ab v0.5.11, ADR-31)

- [ ] **Classic** (Default): nach „Avada-Farben übernehmen" trägt der **Reopen-Button** sichtbar die Primary Color (Füllung + Rahmen), Text lesbar (Auto-Kontrast).
- [ ] **Classic**: der Shortcode-Button `[simple_cookie_settings]` (`.lscc-settings-button`) trägt ebenfalls die Primary Color.
- [ ] **Modern** und **Premium**: unverändert korrekt (Primary-Füllung, Outline/Glow).
- [ ] Primary-Button im Banner-Panel (`.lscc__button--primary`) unverändert in Primary Color.
- [ ] Kein Frontend-Debug-Kasten sichtbar (0.5.10-debug3 entfernt); keine Admin-Debug-Notice (0.5.10-debug2 entfernt).
- [ ] Keine Änderung an Consent/Locale/Scanner/CCM/Updater/Avada-Import/Cache-Reset.

## Avada-Farbe: ausschließlich Primary Color (ab v0.5.10, ADR-30)

- [ ] Avada Primary Color = direkter Hex (z. B. `#2ecc4e`). Klick „Avada-Farben übernehmen" → Banner-Primärbutton + Rahmen = **`#2ecc4e`** (nicht der alte/Palette-Wert).
- [ ] Avada Primary Color = Global Color `var(--awb-colorX)` → übernommen wird **deren** aktueller Wert (Browser-Auflösung der **Primary**-Variable), nicht eine andere Palette-Position.
- [ ] `accent_color`/`link_color`/`button_gradient_top_color` zeigen **andere** Farben/Global Colors → werden **ignoriert**; nur `primary_color` zählt.
- [ ] Primary Color als `rgb()/rgba()` → wird zu Hex übernommen.
- [ ] Kein Debug-Notice mehr sichtbar (`0.5.9-debug` entfernt).
- [ ] Keine Änderung an Consent/Locale/Reopen/Presets/Frontend/Cache-Reset/Speicherung.

## Avada-Farbe: Fusion-Cache-Reset nach Import (ab v0.5.9, ADR-29)

- [ ] Avada aktiv, Markenfarbe ≠ Banner-Default. Klick „Avada-Farben übernehmen".
- [ ] Banner zeigt **sofort** die neue Farbe — **ohne** Ctrl+F5 / manuelles Avada-Cache-Leeren.
- [ ] Admin-Notice erscheint: „Avada-Farben übernommen. Fusion/Avada Cache wurde automatisch geleert."
- [ ] Debug-Notice (falls aktiv) zeigt `CACHE_RESET: geleert (Fusion/Avada API)`.
- [ ] Theme **ohne** Fusion-Cache-API (Nicht-Avada / fehlende Funktion): Import speichert Farbe, **kein** Fehler; Notice fällt auf Standardtext zurück (`mcb_cache=0`).
- [ ] Keine Änderung an Consent/Locale/Reopen/Presets/Frontend; Resolver-Logik unverändert.

## Locale „einmal pro Sprache" + sichtbare Outline + Position prominent (ab v0.5.5)

**Locale (Banner nur einmal pro Sprache):**
- [ ] Consent auf **DE** speichern → auf **EN** wechseln: Banner erscheint **einmal** auf Englisch.
- [ ] EN speichern/schliessen → zurück auf **DE**: Banner erscheint **nicht** erneut.
- [ ] Erneut auf **EN**: Banner erscheint **nicht** erneut.
- [ ] Neue Sprache **FR** (erstmals): Banner erscheint **einmal**.
- [ ] localStorage-Key `mcb_consent_locales_seen` enthält die bestätigten Locales (z. B. `["de_CH","en_US","fr_FR"]`); `lscc_consent`/`MCB_CONSENT_VERSION` unverändert.
- [ ] Bestehender 0.5.4-Consent (Key `mcb_consent_locale`) wird migriert → kein doppeltes Wiedererscheinen.

**Reopen-Button weiße Outline:**
- [ ] **Modern/Premium:** Primary-Hintergrund sichtbar **und** weiße 1px-Outline **klar erkennbar** (auch auf hellen/dunklen Markenfarben); Text lesbar; Hover dezent (Outline bleibt); Mobile ok. Classic unverändert.

**Position prominent:**
- [ ] Admin → „Einstellungen": in der Sektion **„Darstellung"** steht direkt ein Dropdown **„Cookie-Einstellungen-Button Position"** mit Hinweis Chat/WhatsApp → unten links.
- [ ] **Nur ein** Positions-Dropdown (kein doppeltes); „Floating-Button — Feinjustierung" enthält nur die Offsets.
- [ ] Alle Positionen funktionieren (bottom/top-right/-left, hidden); gespeicherte Position bleibt nach Update erhalten (keine Rücksetzung, ADR-27).

## Locale-aware Anzeige + Reopen-Markenfarbe + X-Dismiss (ab v0.5.4)

**Sprachwechsel (alle vorhandenen Locales: de_CH, en_US, fr_FR, it_IT, tr_TR, hu_HU):**
- [ ] Consent auf **Deutsch** speichern → auf **Englisch** wechseln: Banner erscheint **erneut auf Englisch**; gespeicherte Auswahl bleibt erhalten (Häkchen vorausgewählt).
- [ ] Speichern/Schliessen → **Reload (Sprache bleibt Englisch):** Banner erscheint **nicht** erneut.
- [ ] Erneuter Wechsel zurück auf Deutsch → Banner erscheint erneut auf Deutsch; Auswahl bleibt.
- [ ] **Kein** Re-Consent, **kein** gelöschter Consent/Cookie/localStorage, `MCB_CONSENT_VERSION` unverändert (`2`).
- [ ] Bestehender Consent ohne gespeicherte Locale (Erstkontakt nach Update) → **kein** erzwungenes Wiedererscheinen.

**Reopen-/Cookie-Einstellungs-Button Markenfarbe:**
- [ ] **Classic:** unverändert/dezent.
- [ ] **Modern/Premium:** Button trägt **sichtbar die Markenfarbe** (Primary-Hintergrund), Text lesbar (Auto-Kontrast), 1px-weisse Outline, dezenter Schatten + Hover; nach **Avada-Farbimport** tragen Haupt- und Reopen-Button dieselbe Farbe.
- [ ] Kein Glass/Transparenz/Blur/Neon/Animation; Popup-Hintergrund unverändert; Button bleibt lesbar; Mobile ok.

**Temporärer X-Dismiss:**
- [ ] „×" im Reopen-Button sichtbar (Desktop + Mobile); Klick blendet den Button **sofort** aus.
- [ ] Klick auf „×" öffnet **nicht** das Consent-Modal (Interception ok).
- [ ] **Kein** Consent-/Cookie-/Einstellungs-Eingriff; nach **Reload** erscheint der Button wieder.
- [ ] X-Dismiss ≠ `hidden`-Option (letztere bleibt dauerhaft über Plugin-Einstellung).

**reopen_position (Regressionsprüfung, ab v0.5.0 unverändert):**
- [ ] Alle Positionen funktionieren: bottom-right/-left, top-right/-left, hidden.
- [ ] Gespeicherte Position bleibt nach Update unverändert (keine Rücksetzung auf Default); ADR-27.

## Lokalisierung Banner-Texte + Premium-Reopen (ab v0.5.3)

- [ ] **DE-Seite:** Banner **vollständig Deutsch** — Titel „Cookie-Einstellungen", Beschreibung, „Alle akzeptieren"/„Nur notwendige"/„Auswahl speichern", Reopen-Button „Cookie-Einstellungen", Kategorien, Hinweise. **Kein** englischer Resttext.
- [ ] **EN/FR/IT/TR/HU-Seite:** jeweils vollständig in der Sprache; kein Mix, kein englischer Fallback (sofern Übersetzung existiert).
- [ ] **Reopen-Button** folgt der aktiven Sprache (DE „Cookie-Einstellungen", FR „Paramètres des cookies", IT „Impostazioni dei cookie", EN „Cookie settings").
- [ ] **Operator-Override bleibt:** ein im Admin individuell geänderter Text wird **nicht** überschrieben und erscheint wie eingegeben; WPML/Polylang-String-Translation hat weiterhin Vorrang.
- [ ] Frisch gespeicherte Einstellungen (Admin in Operator-Sprache) führen **nicht** mehr zu fixiertem Banner-Text.
- [ ] **Sprachdateien:** `languages/` enthält de_CH/en_US/fr_FR/it_IT/tr_TR/hu_HU (je .po+.mo) + .pot; `.mo` aus `.po` kompiliert (konsistent).
- [ ] **Premium-Preset:** Reopen-Button mit dezentem 1px-Markenrand, Radius und Glow passend zum Banner; Hover dezent; **kein** Glass/Transparenz/Blur; Popup-Hintergrund unverändert.
- [ ] Consent/Scanner/Privacy/CCM/Updater/Cookies/Storage/Shortcodes unverändert.

## Design-Presets (ab v0.5.2)

- [ ] Plugin aktiviert/aktualisiert ohne PHP-Fehler; Bestands-`lscc_options` unverändert (Default-Preset `classic`).
- [ ] Admin → „Einstellungen" zeigt Sektion „Darstellung" mit Dropdown „Design-Preset" (Classic/Modern/Premium) + Hinweis „Presets verändern keine Farben".
- [ ] **Classic (Default):** Banner sieht **identisch** zur bisherigen Version aus (keine optische Änderung) — Desktop **und** Mobile.
- [ ] **Modern:** größere Radien, mehr Luft, Pill-Buttons, weicherer Schatten; auf Mobile **kein** Overflow / kein abgeschnittener Text (kompaktes Basis-Padding bleibt < 761 px).
- [ ] **Premium:** stärkere Elevation, Glow am Hauptbutton in der **Markenfarbe** (folgt manueller Farbe bzw. Avada-Import); Titel kräftiger.
- [ ] Preset-Wechsel ändert **keine Farben** (Hintergrund/Text/Buttons/Border-Farbwerte unverändert; nur Form/Schatten/Abstände).
- [ ] Klasse `lscc--preset-<wert>` liegt an Root, Overlay, **Reopen-Button** und **Settings-Shortcode-Button** ([simple_cookie_settings] übernimmt den Preset-Look).
- [ ] Reopen-Button und `[simple_cookie_settings]` funktionieren in allen drei Presets (Öffnen/Schliessen/Widerruf).
- [ ] Cross-Browser (Chrome/Firefox/Safari) Desktop + Mobile; keine Regressionen am Consent-Verhalten.
- [ ] **ADR-27:** Update auf 0.5.2 ändert ohne Operator-Auswahl **nichts** optisch (Default `classic`).
- [ ] Consent, Scanner, Privacy Check, CCM, Auto-Update, Cookies/Storage, Shortcodes, Avada-Import unverändert.

## Avada-Farbimport (ab v0.5.1)

- [ ] Plugin aktiviert/aktualisiert ohne PHP-Fehler; Bestands-`lscc_options` unverändert (Farben/Consent bleiben).
- [ ] **Moderne Avada-Site:** in „Einstellungen → Farben" erscheint die Sektion „Avada-Farben" mit Button „Avada-Farben übernehmen".
- [ ] **Nicht-Avada-Theme:** Button/Sektion **nicht** sichtbar; keine Konsole-/PHP-Fehler.
- [ ] Klick auf „Avada-Farben übernehmen": Primärbutton **und** Rahmenfarbe = Avada-Markenfarbe; Erfolg-Notice erscheint.
- [ ] Button-Textfarbe (`primary_text_color`) ist automatisch lesbar (heller Markenton → dunkler Text, dunkler Markenton → weisser Text).
- [ ] **Prioritätskette:** `primary_color` gewinnt; bei leerem `primary_color` greift `accent_color`, dann `link_color`, zuletzt `button_gradient_top_color`.
- [ ] `var(--awb-colorN)`-Wert wird korrekt zu Hex aufgelöst (Avada Global Colors).
- [ ] **Sekundärbutton, Hintergrund, Text, Overlay bleiben unverändert** (kein Import).
- [ ] Keine auflösbare Markenfarbe → Warn-Notice „Keine Avada-Markenfarbe gefunden. Farben unverändert."; **keine** Änderung.
- [ ] **ADR-27:** Update auf 0.5.1 ändert ohne Klick **keine** Farben; kein Auto-Import, kein Live-Sync.
- [ ] Nach Import manuell anpassbar (Farbfelder editieren + „Auswahl speichern") und persistent.
- [ ] Banner-Frontend, Consent, Auto-Update, Scanner, Consent-Code-Manager unverändert.

## Reopen-Button-Position „Versteckt" (ab v0.5.0)

- [ ] Plugin aktiviert/aktualisiert ohne PHP-Fehler; Bestands-`lscc_options` unverändert (Einstellungen + Consent bleiben).
- [ ] Admin → „Floating-Button": Dropdown „Position" zeigt zusätzlich **Versteckt**.
- [ ] Positionen `Unten rechts/links`, `Oben rechts/links` verhalten sich unverändert (Reopen-Button erscheint nach Consent an der gewählten Ecke).
- [ ] **Versteckt gewählt + gespeichert:** Reopen-Button erscheint **nicht** — auch nicht nach „Alle akzeptieren"/„Nur notwendige", nicht nach Reload, nicht nach Hard-Reload.
- [ ] **DSGVO-Hinweis** erscheint in den Einstellungen, sobald `Versteckt` aktiv ist (Warnbox mit `[simple_cookie_settings]`-Empfehlung).
- [ ] Im Hidden-Modus öffnet `[simple_cookie_settings]` (z. B. im Footer) weiterhin die Consent-Einstellungen ohne Seitenreload.
- [ ] Banner-Erscheinen ohne gespeicherten Consent unverändert; Consent-Speicherung/-Gültigkeit unverändert (`lscc_consent`, `version: 2`).
- [ ] Umschalten Versteckt → eine Ecke (und zurück) funktioniert ohne Reststörung; bei unbekanntem/leerem Wert greift Default `bottom-right`.
- [ ] Kein Konsolenfehler in Chrome/Firefox; `initBanner()` läuft (Button bleibt im DOM, nur versteckt).

## Version 0.1.0

- [ ] Plugin in einer echten WordPress-Testinstallation aktivieren.
- [ ] PHP-Fehlerlog nach Aktivierung pruefen.
- [ ] Banner erscheint ohne gespeicherten Consent.
- [ ] Standardzustand ist nur notwendige Cookies.
- [ ] `type="text/plain"` mit `data-cookie-category="statistics"` bleibt vor Zustimmung blockiert.
- [ ] "Alle akzeptieren" aktiviert Statistik, Marketing und externe Medien.
- [ ] "Nur notwendige" blockiert Statistik, Marketing und externe Medien.
- [ ] Consent bleibt nach Reload erhalten.
- [ ] Widerruf ueber `[simple_cookie_settings]` funktioniert ohne Seitenreload.
- [ ] Fester Widerruf-Button oeffnet Einstellungen erneut.
- [ ] Buttons sind per Tastatur erreichbar und bedienbar.
- [ ] Fokuszustand ist sichtbar.
- [ ] ESC schliesst die Einstellungen nicht automatisch.
- [ ] Chrome testen.
- [ ] Firefox testen.
- [ ] Mobile Safari testen.
- [ ] Mobile Layout pruefen: keine abgeschnittenen Texte, kein horizontaler Overflow.
- [ ] HTTPS testen: Consent-Cookie enthaelt `Secure`.
- [ ] HTTP testen: Consent-Cookie wird ohne `Secure` gesetzt.
- [ ] Consent-Cookie enthaelt `SameSite=Lax`.
- [ ] `MCB_DEBUG` false: keine Console-Ausgaben.
- [ ] `MCB_DEBUG` true: minimale Console-Ausgaben sichtbar.
- [ ] WPML-String-Registrierung in Testumgebung pruefen.
- [ ] Polylang-String-Registrierung in Testumgebung pruefen.
- [ ] Sprachdateien bei Bedarf aus `.po` nach `.mo` kompilieren.
- [ ] Privacy Check im Admin oeffnen.
- [ ] Privacy Check mit Google Fonts auf einer Testseite pruefen.
- [ ] Privacy Check mit YouTube/Vimeo auf einer Testseite pruefen.
- [ ] Privacy Check mit Google Analytics oder Google Tag Manager auf einer Testseite pruefen.
- [ ] Privacy Check bestaetigt nur Hinweise und veraendert keine Inhalte.
- [ ] `[lscc_youtube id="..."]` zeigt vor Consent nur den Platzhalter.
- [ ] YouTube-Video laedt erst nach Zustimmung zu externen Medien.
- [ ] `[lscc_vimeo id="..."]` zeigt vor Consent nur den Platzhalter.
- [ ] Vimeo-Video laedt erst nach Zustimmung zu externen Medien.
- [ ] `[lscc_google_map url="..."]` zeigt vor Consent nur den Platzhalter.
- [ ] Google Maps laedt erst nach Zustimmung zu externen Medien.
- [ ] Consent fuer externe Medien bleibt nach Reload erhalten.
- [ ] Responsive Verhalten der Service-Komponenten pruefen.
- [ ] Settings-Modus im Banner zeigt unten nur den Button `Auswahl speichern`.
- [ ] Keine doppelten `Alle akzeptieren`- oder `Nur notwendige`-Buttons im Settings-Modus.
- [ ] Privacy Check: Button `Content Scan starten` startet einen lokalen Scan.
- [ ] Content Scan findet eine YouTube-URL in einem Testbeitrag.
- [ ] Content Scan zeigt einen funktionierenden Bearbeiten-Link auf den Beitrag.
- [ ] Content Scan veraendert keine Beitrags-Inhalte und blockiert nichts automatisch.
- [ ] Content Scan macht keine externen HTTP-Requests (Netzwerk-Monitor leer).
- [ ] Content Scan beruecksichtigt `post`, `page` und mindestens einen public Custom Post Type.
- [ ] Content Scan respektiert die Begrenzung auf 200 Inhalte.
- [ ] Overlay erscheint, wenn `Overlay aktivieren` an ist UND das Banner sichtbar ist.
- [ ] Overlay verschwindet sofort nach Consent.
- [ ] Overlay blockiert keine Klicks und keinen Scroll auf der Seite.
- [ ] `Blur aktivieren` aus: kein `backdrop-filter` aktiv; visueller Vergleich.
- [ ] `Blur-Stärke` Default 4 fühlt sich auf Mobile flüssig an (keine spürbaren Janks).
- [ ] Reopen-Button erscheint nach Consent an der gewählten Position (`bottom-right`, `bottom-left`, `top-right`, `top-left`).
- [ ] `Offset X` / `Offset Y` werden 1:1 als px angewendet.
- [ ] Auf Mobile (< 420 px) verkleinert sich der Offset automatisch auf 10 px.
- [ ] Privacy-URL = WordPress-Privacy-Policy, wenn Override leer.
- [ ] Manueller `Datenschutz-URL`-Override hat Vorrang.
- [ ] Auto-Erkennung Impressum findet typische Slugs (`impressum`, `datenschutz-und-impressum`, ...) im Admin.
- [ ] Auto-Erkennung Impressum schreibt das Ergebnis als Transient `lscc_detected_imprint_url`.
- [ ] Frontend führt keine eigenen Imprint-Lookups aus (Netzwerk- und SQL-Monitor prüfen).
- [ ] Bei identischer Datenschutz-/Impressum-URL erscheint nur ein Link „Datenschutz & Impressum".
- [ ] Keine leeren Links, wenn beide URLs fehlen.
- [ ] Im Settings-Modus sind oben `Alle akzeptieren` und `Nur notwendige` weiterhin sichtbar.
- [ ] Im Settings-Modus ist der `Einstellungen`-Button oben ausgeblendet.
- [ ] Lighthouse Performance ohne aktiviertes Plugin vs. mit aktiviertem Plugin: keine signifikante Verschlechterung auf einer leeren WordPress-Seite.
- [ ] **Fresh-Install-Test (Inkognito-Fenster, neu geöffnet):** Banner erscheint, Reopen-Button ist versteckt.
- [ ] **Upgrade-Test (Browser hatte v1-Consent aus v0.1.0 – v0.1.4):** Banner erscheint erneut, alter Consent wird durch `MCB_CONSENT_VERSION = 2` invalidiert.
- [ ] Nach „Alle akzeptieren": Banner verschwindet, Reopen-Button erscheint, `lscc_consent`-Cookie enthält `version: 2` und ein `expiresAt`.
- [ ] Ctrl+F5 / Hard-Reload löscht den gespeicherten Consent nicht.
- [ ] Plugin-Deinstallation löscht den Browser-Storage nicht (Cookie + localStorage bleiben).
- [ ] Admin-Wert `Consent-Gültigkeit (Tage)` ist clamped auf 1 – 365.
- [ ] Wenn der Admin die `Consent-Gültigkeit` von 180 auf z. B. 1 reduziert, gelten existierende Consents (älter als 1 Tag) sofort als ungültig und das Banner erscheint erneut.
- [ ] Reopen-Button ist nicht sichtbar, wenn kein gültiger Consent vorhanden ist (auch nicht bei Browser ohne JS-Init).
- [ ] Nach Aktivierung der neuen Version sind die Defaults `overlay_enabled`, `blur_enabled`, `show_legal_links` jeweils `true` — auch wenn vorher eine alte Plugin-Version installiert war.

## Avada-Google-Maps Gating (ab v0.3.2-test)

Voraussetzung: Avada-Seite mit `fusion_map`; Option „Avada-Karten … blockieren" EIN; **Avada-Privacy-Maps AUS** (nur eine Schicht).

- [ ] **Vor Consent (Netzwerk-Monitor, Hard-Reload):** kein Request an `maps.googleapis.com`. Statt der Avada-Karte erscheint der LSCC-Platzhalter mit Hinweis + „Externe Medien akzeptieren".
- [ ] Die Maps-JS-API-`<script>`-Tags tragen `type="text/plain" data-cookie-category="external_media"`.
- [ ] **Nach Zustimmung:** das Google-Maps-Embed lädt und zeigt den Standort; Reload behält den Consent.
- [ ] `fusion_map` mit `address` wird korrekt erkannt; mit `[fusion_map_marker address="…"]` wird die primäre Adresse genutzt.
- [ ] Nicht parsebare Adresse → Avada rendert die Karte normal (kein Layout-Bruch); API bleibt trotzdem via SRC-Gating geblockt (kein Pre-Consent-Google).
- [ ] `[lscc_google_map address="Bahnhofstrasse 1, Zürich"]` zeigt vor Consent Platzhalter, nach Consent die Embed-Karte.
- [ ] `[lscc_google_map url="…/maps/embed?…"]` weiterhin unverändert funktionsfähig.
- [ ] Option AUS → Avada rendert die Original-JS-Karte (reversibel).
- [ ] **Nur eine Consent-Schicht:** kein Doppel-Platzhalter mit Avada-Privacy (Avada-Privacy-Maps muss aus sein).
- [ ] Optimierungs-Plugin-Test (Delay/Combine JS) → `type="text/plain"`-Maps-API nicht umgeschrieben.

## Scanner: Drittanbieter-Oberfläche (ab v0.3.1-test)

- [ ] Privacy-Check-Seite zeigt die neue Sektion „Drittanbieter-Oberfläche" mit Tabelle (Dienst / Status / Gegated / Ungegatet / Im Consent-Code-Manager / Empfehlung).
- [ ] Ungegatetes GA-Snippet auf der Seite → Status **Ungegatet**.
- [ ] Dasselbe GA über Consent-Code-Manager gegated → Status **Verwaltet**; Cross-Ref „Im Consent-Code-Manager" = Ja.
- [ ] Gemischt (1× gegated + 1× ungegatet) → **Teilweise verwaltet**.
- [ ] GTM vorhanden → Status **Nicht prüfbar** mit Hinweis „gefeuerte Tags nicht prüfbar".
- [ ] Calendly nicht im statischen HTML → **Nicht prüfbar** (nicht fälschlich „Nicht gefunden").
- [ ] `[lscc_youtube]`-Platzhalter → YouTube **Verwaltet**; rohes YouTube-`<iframe>` → **Ungegatet**.
- [ ] Externe Google Fonts → Sonderzeile „Externe Google Fonts erkannt" + „Empfehlung: lokal hosten. Consent ersetzt kein Local Hosting."
- [ ] **Eigene Test-URL** (gleicher Host) wird geprüft; Fremd-Host wird abgelehnt (Warn-Notice, Startseite stattdessen).
- [ ] Nur ein `wp_remote_get` pro Seitenaufruf (Netzwerk/Server prüfen); kein Crawl.
- [ ] Hinweistext zur Server-Sicht-Grenze (kein JS, GTM-Tags/Unterseiten nicht erfasst) ist sichtbar.
- [ ] Muster-Schnellprüfung und Content Scan unverändert funktionsfähig.

## Consent-Code-Manager (ab v0.3.0-test)

- [ ] Admin-Submenu „Consent-Code-Manager" erscheint unter „Light Swiss Cookie Consent"; nur mit `manage_options` erreichbar.
- [ ] „+ Snippet hinzufügen" fügt per Admin-JS eine Zeile hinzu; ↑/↓/Löschen funktionieren; Speichern persistiert.
- [ ] GA4-Snippet (mit `<script>`) in Position „Head", Kategorie „Statistik" → im gerenderten `<head>` steht `<script type="text/plain" data-cookie-category="statistics" data-cookie-type="text/javascript">`.
- [ ] **Vor Statistik-Consent (Netzwerk-Monitor):** kein Request an `googletagmanager.com`/`google-analytics.com`; **nach** Zustimmung lädt gtag (externer `src` vor Inline-Config — Reihenfolge korrekt).
- [ ] GTM-Snippet: `<noscript>`-Teil ist im Output **entfernt**.
- [ ] Vendor-Badge zeigt korrekt „Erkannt: Google Analytics 4 / Google Tag Manager / Meta Pixel / Hotjar".
- [ ] Meta Pixel (Kategorie Marketing) lädt erst nach Marketing-Consent; Hotjar (Statistik) erst nach Statistik-Consent.
- [ ] Position „Body-Anfang" wird über `wp_body_open` ausgegeben; „Footer" über `wp_footer`.
- [ ] **Capability-Gate:** Nutzer ohne `unfiltered_html` (z. B. Nicht-Super-Admin im Multisite) kann keinen Roh-Code speichern → Feld verworfen + Warn-Notice.
- [ ] **Export** lädt ein JSON-Envelope (`lscc_export_version`, `data.consent_codes`); **Import** desselben Envelopes stellt die Snippets wieder her (Roundtrip).
- [ ] **Cache-Safety:** HTML mit/ohne Consent identisch (Full-Page-Cache stört nicht).
- [ ] **Optimierungs-Plugin-Test:** mit dem realen Stack (WP Rocket/Autoptimize/LiteSpeed „Delay/Combine JS") prüfen, dass `type="text/plain"`-Snippets nicht umgeschrieben/zusammengeführt werden; ggf. LSCC-Scripts ausschliessen.
- [ ] Migration: Snippet aus altem Ort (Avada Global Options) entfernt → kein Doppel-Laden (1× gegated + 1× ungegated).
- [ ] Bestehende Funktionen unverändert (Banner, Service-Komponenten, YOTU, Schnellbutton-State).

## Aktiver Consent an den Schnellbuttons (ab v0.2.4-test)

- [ ] **Erster Besuch (kein Consent):** „Alle akzeptieren" und „Nur notwendige" sind beide neutral/gleichwertig (kein Button hervorgehoben, kein „✓").
- [ ] Nach „Nur notwendige" → Dialog erneut öffnen: „Nur notwendige" ist **aktiv** (Ring + „✓"), „Alle akzeptieren" ist **inaktiv** (abgeschwächt).
- [ ] Nach „Alle akzeptieren" → erneut öffnen: „Alle akzeptieren" aktiv, „Nur notwendige" inaktiv.
- [ ] Nach individueller Auswahl (gemischt, via Checkboxen + Speichern) → beide Schnellbuttons inaktiv; Checkboxen zeigen die genaue Auswahl.
- [ ] Zustand ist **sofort beim Öffnen** sichtbar (nicht erst nach Interaktion).
- [ ] Nach Reload bleibt der aktive Zustand korrekt (aus gespeichertem Consent rekonstruiert).
- [ ] Screenreader: aktiver Button meldet `aria-pressed="true"`, inaktiver `="false"`.
- [ ] Keine Änderung an gespeichertem Consent/Verhalten durch die Anzeige (Videos/Scripts unverändert gegated).

## Consent-UI Synchronisation (ab v0.2.3-test)

Reproduktion des behobenen Bugs — in **Firefox UND Chrome** prüfen:

- [ ] „Alle akzeptieren" → „Cookie-Einstellungen" öffnen → „Nur notwendige" → speichern → **Reload** → Einstellungen erneut öffnen: alle optionalen Häkchen sind **leer** (Statistik/Marketing/Externe Medien), passend zum gespeicherten Consent.
- [ ] Umgekehrt: „Nur notwendige" → „Alle akzeptieren" → Reload → Einstellungen zeigen alle optionalen Häkchen **gesetzt**.
- [ ] Direkt nach „Alle akzeptieren" bzw. „Nur notwendige" (ohne Reload) entsprechen die Checkboxen beim nächsten Öffnen exakt der getroffenen Wahl.
- [ ] Häkchen-Zustand und tatsächliches Verhalten (Videos blockiert/frei, Scripts aktiv/inaktiv) stimmen immer überein.
- [ ] Checkboxen tragen `autocomplete="off"` (Browser stellt nach Reload keinen alten Zustand wieder her).
- [ ] „Notwendig" bleibt immer gesetzt und deaktiviert.

## YOTU Consent Gating (ab v0.2.2-test)

Test auf einer Seite mit dem Plugin „Yotuwp – Easy YouTube Embed" (Live-Referenz: `plugins.svogellisi.ch/de/`, „Podcast"-Galerie). Option „YOTU-YouTube-Galerie … blockieren" muss EIN sein.

- [ ] **Vor Consent (Netzwerk-Monitor, Hard-Reload):** kein Request an `youtube.com`, `youtube-nocookie.com`, `iframe_api`, `www-widgetapi.js` UND **kein** `i.ytimg.com`-Thumbnail.
- [ ] Über der YOTU-Galerie erscheint der Consent-Hinweis mit „Externe Medien akzeptieren"-Button.
- [ ] Die Yotu-`<script>`-Tags (`yotu-script-js`, `-extra`, `-after`) tragen `type="text/plain"` + `data-cookie-category="external_media"`.
- [ ] **Nach „Externe Medien akzeptieren" (Hinweis-Button ODER Banner):** Galerie-Thumbnails laden (jetzt von `i.ytimg.com`), Galerie wird interaktiv, Video startet beim Klick.
- [ ] Reihenfolge korrekt: keine `yotuwp is not defined`-Konsolenfehler nach Consent; Galerie vollständig funktionsfähig (Titel/Beschreibungen vorhanden).
- [ ] Reload nach Consent: Galerie funktioniert sofort, kein erneuter Hinweis.
- [ ] Mehrere Galerien auf einer Seite: jede erhält einen Hinweis und wird nach Consent aktiv.
- [ ] **Option AUS:** YOTU rendert wieder original (Thumbnails + Klick-Player ohne LSCC-Eingriff) — reversibel.
- [ ] Bestehende `[lscc_youtube]`/Avada-Gating-Videos unverändert (gleiche `external_media`-Kategorie).
- [ ] Andere gegatete Scripts (statistics/marketing `type="text/plain"`) aktivieren nach Consent weiterhin korrekt (sequenzielle Aktivierung ohne Regression).
- [ ] Coverage-Grenze prüfen: ist die Galerie per Shortcode eingebunden? (Block-/Widget-Einbindung wird von der Thumbnail-Neutralisierung nicht erfasst.)
- [ ] WordPress-Version ≥ 5.7 (Inline-Script-Gating); sonst Haupt-Script trotzdem geblockt, Inline-Teile laufen (kein Leak, evtl. Konsolen-Hinweis).

## WPML / Mehrsprachigkeit (ab v0.2.1-test)

Test auf einer echten WPML-Seite mit mindestens DE + EN (ideal zusätzlich FR/IT/TR/HU). Live-Referenz: `plugins.svogellisi.ch/de/` und `/en/`.

- [ ] **Kein Sprach-Mix mehr:** Auf der EN-Seite sind Einleitungstext UND alle Labels englisch; auf der DE-Seite alles deutsch.
- [ ] Banner-Labels folgen der aktiven Sprache: `Notwendig/Statistik/Marketing/Externe Medien` + die vier Beschreibungen.
- [ ] Die sieben editierbaren Strings folgen der aktiven Sprache (Titel, Einleitungstext, „Alle akzeptieren", „Nur notwendige", „Einstellungen", „Auswahl speichern").
- [ ] **„Cookie-Einstellungen"-Button unten rechts** (Reopen) erscheint in der aktiven Sprache (EN: „Cookie settings", FR: „Paramètres des cookies", …).
- [ ] Rechtslinks im Banner (`Datenschutz`, `Impressum`, `Datenschutz & Impressum`) sind übersetzt.
- [ ] Service-Komponenten-Platzhalter (YouTube/Vimeo/Maps Hinweistext, „Externe Medien akzeptieren") sind in der aktiven Sprache.
- [ ] **Frischer Install ohne gespeicherte Optionen:** Defaults erscheinen automatisch in der aktiven Sprache (keine deutschen Hardcodes auf EN/FR/IT/TR/HU).
- [ ] **Admin-Override greift:** Ein im Admin geänderter Text bzw. eine WPML-String-Translation-Übersetzung hat Vorrang vor dem Locale-Default.
- [ ] WPML-Sprachwechsel lädt das passende `.mo` (kategoriale Labels wechseln tatsächlich mit der Sprache — `switch_to_locale()`-Verhalten prüfen).
- [ ] Eine Sprache jenseits der sechs Bundle-Sprachen fällt sauber auf Englisch zurück (kein deutscher Resttext, kein Fatal).
- [ ] `.mo`-Dateien sind im ZIP/Install vorhanden (`languages/macs-cookie-banner-<locale>.mo`).
- [ ] Admin-Seiten (Einstellungen, Privacy Check, Avada-Inventar) erscheinen in der Operator-Sprache (deutsche Quelle) — bewusster Scope (ADR-19), kein Bug.

## Nativer LSCC-YouTube-Block (ab v0.2.0-test)

- [ ] `[lscc_youtube id="VIDEO_ID"]` zeigt vor Consent Platzhalter + Play-Button + Hinweistext + „Externe Medien akzeptieren"-Button (auch ohne Thumbnail).
- [ ] `[lscc_youtube id="https://www.youtube.com/watch?v=VIDEO_ID"]` und `id="https://youtu.be/VIDEO_ID"` werden korrekt erkannt.
- [ ] `title="..."` wird als iframe-/a11y-Titel übernommen.
- [ ] **Mit „Nur notwendige":** keine Requests an youtube.com / youtube-nocookie.com / `iframe_api` / `www-widgetapi.js`; keine youtube.com-Cookies.
- [ ] Option „YouTube-Thumbnails vor Consent laden" = AUS (Default): kein `i.ytimg.com`-Request vor Consent (lokaler Platzhalter).
- [ ] Option = AN: `i.ytimg.com`-Vorschaubild erscheint vor Consent, aber weiterhin **kein** iframe/iframe_api/youtube.com-Cookie. (Hinweis: ytimg-Bild überträgt IP an Google — bewusst.)
- [ ] `thumbnail_id` (lokal) hat Vorrang vor dem Remote-Thumbnail.
- [ ] **Mit external_media-Zustimmung:** Video lädt korrekt.
- [ ] Play-Button → Video startet nach Zustimmung automatisch (`autoplay=1`). Reiner Accept-Button → kein Autostart.
- [ ] Mehrere Videos auf einer Seite funktionieren unabhängig (eigene Autoplay-Markierung pro Komponente).
- [ ] Responsive 16:9 auf Desktop und Mobile; kein Overflow.
- [ ] Bestehende `[lscc_youtube id="..."]` / `[lscc_youtube ... thumbnail_id="..."]` weiterhin kompatibel (erhalten zusätzlich den Play-Button).
- [ ] v0.1.9-Avada-Kompatibilität unverändert (fusion_youtube weiterhin gegated, nutzt denselben Helper).

## Avada fusion_youtube Consent-Gating (ab v0.1.9-test)

- [ ] Auf Avada/Daniela-Baumann: nach „Nur notwendige" werden **keine** YouTube-Ressourcen geladen (Netzwerk-Monitor: kein `iframe_api`, kein `www-widgetapi.js`, keine youtube.com-Cookies).
- [ ] Vor Consent erscheint statt des Avada-iframes der LSCC-Platzhalter mit Hinweistext und Accept-Button.
- [ ] Kein `<iframe>` von YouTube im DOM vor Consent.
- [ ] Nach Zustimmung zu „Externe Medien" lädt das YouTube-Video; Reload behält den Consent.
- [ ] Option „Avada-YouTube … blockieren" aus → Avada rendert wieder sein Original-iframe (reversibel).
- [ ] Mehrere `fusion_youtube` auf einer Seite werden alle ersetzt.
- [ ] `fusion_youtube` mit voller URL als `id` (statt reiner ID) wird korrekt erkannt.
- [ ] Nicht parsebare/leere `id` → Avada rendert unverändert (kein Layout-Bruch, kein Fehler).
- [ ] Layout wird durch den 16:9-Platzhalter nicht sichtbar zerstört (visuelle Stichprobe Desktop + Mobile).
- [ ] Builder-Backend (Fusion Builder Bearbeitung) bleibt unverändert; Interception nur im Frontend.
- [ ] `[lscc_youtube]`-Shortcode-Videos verhalten sich unverändert (gleiche `external_media`-Kategorie).
- [ ] Kein Konflikt mit Avadas eigener Privacy-/Embed-Funktion (nur EINE Consent-Schicht aktiv).

## Avada Inventar-Scan (ab v0.1.8-test)

- [ ] Admin-Submenu „Avada Inventar-Scan" erscheint unter „Light Swiss Cookie Consent".
- [ ] Seite ist nur mit `manage_options` erreichbar; Scan startet nur per Button (Nonce-geschützt).
- [ ] **Netzwerk-Monitor während des Scans leer:** keine externen HTTP-Requests.
- [ ] **SQL/Content unverändert:** keine Schreibzugriffe, keine Beitrags-/Seitenänderung, keine Migration (vorher/nachher vergleichen).
- [ ] Verteilungstabelle zeigt Anzahl + Prozent je Typ; Summenzeile = 100 %.
- [ ] Abfangbarkeits-Matrix korrekt (YouTube/Vimeo/oEmbed = automatisch; Maps/Background/fusion_code/iframe = manuell).
- [ ] KPIs `Abdeckung_min` und `Abdeckung_max` werden berechnet (kein Division-by-zero bei 0 Embeds).
- [ ] Auf einer echten Avada-Seite werden `[fusion_youtube]` / `[fusion_vimeo]` korrekt gezählt.
- [ ] `fusion_code` mit eingebettetem YouTube/iframe wird via base64-Tiefpass als „mit Embed" erkannt.
- [ ] Background-Video (`video_url` mit YouTube/Vimeo) wird als Drittanbieter, `video_mp4/webm` als self-hosted gezählt.
- [ ] Rohe Drittanbieter-iframes werden gegen Same-Origin korrekt klassifiziert.
- [ ] Bei > 500 Inhalten erscheint der Truncation-Hinweis („Rest NICHT gemessen").
- [ ] Avada-CPTs (`fusion_tb_section` etc.) werden einbezogen, falls vorhanden; fehlende werden übersprungen.
- [ ] Bestehende Funktionen unverändert: Consent-Banner, Service-Komponenten, Privacy Check.

## Lokales Thumbnail Vimeo (ab v0.1.7-test)

- [ ] `[lscc_vimeo id="..."]` **ohne** `thumbnail_id` verhält sich exakt wie bisher (einfacher Platzhalter, kein Bild).
- [ ] `[lscc_vimeo id="..." thumbnail_id="<gültige Bild-ID>"]` zeigt vor Consent das Mediathek-Bild, grossen Play-Button, Hinweistext und „Externe Medien akzeptieren"-Button.
- [ ] **Netzwerk-Monitor vor Consent:** kein Request an `vimeo.com`, `player.vimeo.com`, `i.vimeocdn.com` oder andere externe Hosts. Thumbnail kommt nur von der eigenen Domain / Uploads.
- [ ] Klick auf Play-Button **und** Accept-Button lädt je das Vimeo-iframe; Video spielt.
- [ ] Nach Consent unverändertes iframe-Verhalten; Reload behält den Consent.
- [ ] `thumbnail_id` ungültig / kein Bild / `0` / leer → stiller Fallback auf den bisherigen Platzhalter.
- [ ] Bild füllt die 16:9-Fläche (`object-fit: cover`), kein CLS, Play-Button per Tastatur fokussierbar (`:focus-visible`).
- [ ] Mobile (< 420 px): kein Overflow, Tap-Target ausreichend.
- [ ] YouTube-Komponente weiterhin unverändert; Google Maps weiterhin ohne `thumbnail_id`.

## Lokales Thumbnail YouTube (ab v0.1.6-test)

- [ ] `[lscc_youtube id="..."]` **ohne** `thumbnail_id` verhält sich exakt wie bisher (einfacher Platzhalter, kein Bild).
- [ ] `[lscc_youtube id="..." thumbnail_id="<gültige Bild-ID>"]` zeigt vor Consent das Mediathek-Bild, einen grossen zentrierten Play-Button, den Hinweistext und den „Externe Medien akzeptieren"-Button.
- [ ] **Netzwerk-Monitor vor Consent leer in Richtung Drittanbieter:** kein Request an `youtube.com`, `youtube-nocookie.com`, `img.youtube.com`, `ytimg.com` oder Google. Das Thumbnail kommt ausschliesslich von der eigenen Domain / Uploads.
- [ ] Klick auf den **Play-Button** akzeptiert externe Medien und lädt das iframe (gleiches Verhalten wie der Accept-Button).
- [ ] Klick auf den **Accept-Button** funktioniert weiterhin.
- [ ] Nach Consent ist das iframe-Verhalten unverändert; Reload behält den Consent.
- [ ] `thumbnail_id` mit nicht existierender ID → stiller Fallback auf den bisherigen Platzhalter (kein Fehler, kein leeres Bild).
- [ ] `thumbnail_id` mit einer Attachment-ID, die **kein** Bild ist (z. B. PDF) → Fallback auf den Platzhalter.
- [ ] `thumbnail_id="0"` / leer / nicht-numerisch → Fallback auf den Platzhalter.
- [ ] Bild füllt die 16:9-Fläche (`object-fit: cover`), kein Verzerren, **kein CLS** (Dimensionen aus der Mediathek).
- [ ] Play-Button ist per Tastatur fokussierbar und hat einen sichtbaren `:focus-visible`-Outline.
- [ ] Mobile (< 420 px): Bild und Play-Button gut bedienbar, Tap-Target ausreichend gross, kein horizontaler Overflow.
- [ ] WPML/Polylang: bei übersetztem Attachment greift die sprachabhängige Mediathek-ID (sofern pro Sprache gesetzt).
- [ ] Vimeo- und Google-Maps-Komponenten sind unverändert (kein `thumbnail_id`-Support, kein visueller Regress).

## Performance / PageSpeed (ab v0.1.5-test)

Vergleichsmessung auf einer echten WordPress-Testseite. Jeder Lauf einmal **Mobile** und einmal **Desktop** in Chrome DevTools Lighthouse, Inkognito-Fenster, Cache geleert.

Szenarien:

- [ ] **A — Plugin deaktiviert** (Baseline): Werte notieren.
- [ ] **B — Plugin aktiviert, kein Consent** (Banner + ggf. Overlay sichtbar): Werte notieren.
- [ ] **C — Plugin aktiviert, Consent akzeptiert** (Banner weg, Reopen-Button sichtbar): Werte notieren.
- [ ] **D — Plugin aktiviert, Floating-Button sichtbar** (= Zustand nach Consent, Reopen-Button an gewaehlter Position): Werte notieren.

Zu notierende Kennzahlen je Szenario (Mobile + Desktop):

- [ ] Performance Score
- [ ] LCP (Largest Contentful Paint)
- [ ] CLS (Cumulative Layout Shift)
- [ ] INP bzw. TBT (Total Blocking Time)
- [ ] JS Execution Time
- [ ] Total Blocking Time
- [ ] Network Requests (Anzahl)
- [ ] Transfer Size (gesamt)

Akzeptanzkriterien (Vergleich A vs. B/C/D):

- [ ] **PageSpeed-Vergleich deaktiviert vs. aktiviert**: keine signifikante Verschlechterung des Performance Scores (Richtwert: < 3 Punkte Differenz auf leerer Seite).
- [ ] **Mobile Lighthouse** durchgefuehrt und dokumentiert.
- [ ] **Desktop Lighthouse** durchgefuehrt und dokumentiert.
- [ ] **CLS darf nicht deutlich steigen**: Banner/Overlay/Reopen-Button sind `position: fixed` und initial `hidden` → CLS-Delta soll praktisch 0 bleiben.
- [ ] **JS/TBT darf nicht relevant steigen**: banner.js laeuft im Footer, einmalige Initialisierung, keine Dauerlast nach Consent.
- [ ] **Overlay/Blur separat testen**: Szenario B einmal mit `blur_enabled = true` und einmal mit `blur_enabled = false` vergleichen; `backdrop-filter`-Blur ist nur bei sichtbarem Banner aktiv. Auf schwacher Mobile-Hardware auf Jank/Frame-Drops beim Erscheinen achten.
- [ ] Anzahl zusaetzlicher Frontend-Requests durch das Plugin bleibt bei 2 (banner.css + banner.js); Inline-CSS-Variablen und `wp_localize_script` erzeugen keine zusaetzlichen Requests.

## Nicht Teil von 0.1.0

- Auto-Scanner
- GeoIP
- Vendor-Listen
- IAB TCF
- Google-Cloud-Abhaengigkeiten
- Statistik-Dashboard
- Auto-Block-Engine
