# Release Checklist

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
- [ ] `LSCC_DEBUG` false: keine Console-Ausgaben.
- [ ] `LSCC_DEBUG` true: minimale Console-Ausgaben sichtbar.
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
- [ ] **Upgrade-Test (Browser hatte v1-Consent aus v0.1.0 – v0.1.4):** Banner erscheint erneut, alter Consent wird durch `LSCC_CONSENT_VERSION = 2` invalidiert.
- [ ] Nach „Alle akzeptieren": Banner verschwindet, Reopen-Button erscheint, `lscc_consent`-Cookie enthält `version: 2` und ein `expiresAt`.
- [ ] Ctrl+F5 / Hard-Reload löscht den gespeicherten Consent nicht.
- [ ] Plugin-Deinstallation löscht den Browser-Storage nicht (Cookie + localStorage bleiben).
- [ ] Admin-Wert `Consent-Gültigkeit (Tage)` ist clamped auf 1 – 365.
- [ ] Wenn der Admin die `Consent-Gültigkeit` von 180 auf z. B. 1 reduziert, gelten existierende Consents (älter als 1 Tag) sofort als ungültig und das Banner erscheint erneut.
- [ ] Reopen-Button ist nicht sichtbar, wenn kein gültiger Consent vorhanden ist (auch nicht bei Browser ohne JS-Init).
- [ ] Nach Aktivierung der neuen Version sind die Defaults `overlay_enabled`, `blur_enabled`, `show_legal_links` jeweils `true` — auch wenn vorher eine alte Plugin-Version installiert war.

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
