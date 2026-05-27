# Light Swiss Cookie Consent

Ein leichtes, konservatives WordPress Cookie-Consent-Plugin ohne React, Vue, jQuery, npm, Composer oder Build-System.

## Ziel

Light Swiss Cookie Consent zeigt ein modernes dunkles Cookie-Banner an, speichert die Auswahl in `localStorage` und als Cookie und laedt blockierte Skripte erst nach Zustimmung.

## Kategorien

- Notwendig
- Statistik
- Marketing
- Externe Medien

Standard: Nur notwendige Cookies sind aktiv. Statistik, Marketing und externe Medien bleiben blockiert, bis der Besucher zustimmt.

## Script-Blocking

Skripte koennen mit `type="text/plain"` und `data-cookie-category` blockiert werden:

```html
<script type="text/plain" data-cookie-category="statistics">
	console.log('Wird erst nach Statistik-Zustimmung ausgefuehrt.');
</script>
```

Externe Skripte funktionieren ebenfalls:

```html
<script
	type="text/plain"
	data-cookie-category="marketing"
	src="https://example.com/tracking.js">
</script>
```

Falls ein Skript als Modul geladen werden soll:

```html
<script
	type="text/plain"
	data-cookie-type="module"
	data-cookie-category="external_media"
	src="https://example.com/embed.js">
</script>
```

## Admin-Einstellungen

Unter `Light Swiss Cookie Consent > Einstellungen` koennen Texte und Farben gepflegt werden.

Einstellbare Farben:

- Hintergrund
- Text
- Primaerbutton
- Primaerbutton Text
- Sekundaerbutton
- Rahmenfarbe

## Widerruf / Einstellungen erneut oeffnen

Der Shortcode oeffnet die Consent-Einstellungen ohne Seitenreload:

```text
[simple_cookie_settings]
```

## Privacy Check

Unter `Light Swiss Cookie Consent > Privacy Check` stehen zwei passive Hinweis-Werkzeuge bereit:

1. **Startseiten-Pruefung** — laedt einmal `home_url('/')` via `wp_remote_get` (Timeout 5 s, max. 500 KB) und prueft das HTML gegen eine kurze statische Mustertabelle.
2. **Content Scan** — durchsucht ausschliesslich lokal die letzten 200 veroeffentlichten Beitraege, Seiten und oeffentlichen Custom Post Types nach bekannten Drittanbieter-Domains. Wird ausschliesslich auf Klick auf den Button `Content Scan starten` ausgefuehrt (nicht automatisch beim Aufruf der Admin-Seite). Keine externen Requests, kein Crawl, kein Auto-Block, keine Aenderung der Inhalte.

Erkannte Dienste:

- YouTube (`youtube.com`, `youtu.be`, `youtube-nocookie.com`)
- Vimeo (`vimeo.com`, `player.vimeo.com`)
- Google Maps (`google.com/maps`, `maps.google.*`)
- Google Fonts (`fonts.googleapis.com`, `fonts.gstatic.com`)
- Google Tag Manager (`googletagmanager.com`)
- Google Analytics (`google-analytics.com`)
- Facebook / Meta (`facebook.net`, `connect.facebook.net`)

Treffer werden mit Risiko, Dienst, Inhaltstyp, Titel mit Bearbeiten-Link, gefundener Domain und Empfehlung angezeigt. Das Plugin ersetzt nichts automatisch und blockiert nichts; der Hinweis weist Sie nur darauf hin, das jeweilige Embed bewusst auf den entsprechenden Shortcode oder eine andere consent-sichere Variante umzustellen.

## Kontrollierte Service-Komponenten

Externe Medien koennen freiwillig ueber Shortcodes eingebunden werden. Vor der Zustimmung zur Kategorie `external_media` wird nur ein Platzhalter angezeigt.

YouTube:

```text
[lscc_youtube id="VIDEO_ID"]
```

Vimeo:

```text
[lscc_vimeo id="VIDEO_ID"]
```

Google Maps:

```text
[lscc_google_map url="https://www.google.com/maps/embed?..."]
```

Die Komponenten erkennen keine bestehenden iframes automatisch und ersetzen keine Inhalte. Sie laden nur den iframe, der ueber den jeweiligen Shortcode bewusst eingebunden wurde.

## Consent-Speicherung

Die Auswahl wird in `localStorage` und als Cookie gespeichert.

- Cookie-Name: `lscc_consent`
- Ablaufzeit (Default): 180 Tage, im Admin auf 1 – 365 Tage konfigurierbar (`Consent-Speicherung > Consent-Gültigkeit (Tage)`)
- SameSite: `Lax`
- Secure: wird bei HTTPS gesetzt
- Consent-Schema-Version: `LSCC_CONSENT_VERSION` (ab v0.1.5: `2`), getrennt von der Plugin-Version

Ein gespeicherter Consent gilt nur dann als gültig, wenn er:

- JSON-parsebar ist,
- eine `categories`-Struktur enthält,
- dieselbe `version` wie `LSCC_CONSENT_VERSION` hat,
- weder das gespeicherte `expiresAt` noch `createdAt + lifetimeDays` in der Vergangenheit liegt.

Andernfalls erscheint das Banner erneut. Der `Cookie-Einstellungen`-Reopen-Button erscheint ausschliesslich nach gültig gespeichertem Consent.

### Wann erscheint das Banner erneut?

- Bei jeder Erhöhung von `LSCC_CONSENT_VERSION` (z. B. nach einem strukturellen Update der Consent-Schema). v0.1.5 hat die Version auf `2` angehoben, dadurch werden alle älteren Consent-Werte aus v0.1.0 – v0.1.4 invalidiert und das Banner erscheint erneut.
- Wenn der Admin die `Consent-Gültigkeit (Tage)` verkürzt (z. B. von 180 auf 60). Bestehende Consents, deren `createdAt` älter als der neue Wert ist, werden ungültig.
- In einer wirklich frischen Browser-Session ohne `localStorage` und ohne Cookie (z. B. echtes Inkognito).

### Was löscht der Consent nicht?

- **Ctrl+F5 / Hard-Reload** löscht weder Cookies noch `localStorage`. Wenn ein gültiger Consent existiert, bleibt er bestehen.
- **Plugin-Deinstallation** löscht ausschliesslich die WordPress-Optionen des Plugins. Der bereits auf dem Client gespeicherte Browser-Storage (Cookie + `localStorage`) liegt im Browser des Besuchers und wird vom Plugin nicht ferngesteuert. Bei einem strukturellen Reset hilft daher das Erhöhen von `LSCC_CONSENT_VERSION` zuverlässiger als das Neuinstallieren des Plugins.

## Overlay, Blur und Floating-Button

## Debug-Modus

Standardmaessig ist der Debug-Modus deaktiviert:

```php
define( 'LSCC_DEBUG', false );
```

Wenn `LSCC_DEBUG` auf `true` gesetzt wird, darf das Frontend minimale `console.log`-Ausgaben schreiben. Bei `false` schreibt das Plugin keine Debug-Ausgaben.

## Installation

1. Den Ordner `light-swiss-cookie-consent` nach `wp-content/plugins/` kopieren.
2. Das Plugin in WordPress aktivieren.
3. Unter `Light Swiss Cookie Consent > Einstellungen` die Darstellung anpassen.

## Overlay, Blur und Floating-Button

Das Banner kann optional die Seite leicht abdunkeln und dezent blurren. Beides ist über die Admin-Einstellungen pflegbar:

- `Overlay aktivieren` (Checkbox) und `Overlay-Farbe` (Hex), `Overlay-Deckkraft` (0.0 – 1.0)
- `Blur aktivieren` (Checkbox) und `Blur-Stärke` (0 – 20 px)

Performance:

- Das Overlay-Element wird nur in den DOM geschrieben, wenn `Overlay aktivieren` gesetzt ist.
- Initial ist es `hidden` und löst dadurch keinen `backdrop-filter`-Rendering-Aufwand aus. Sobald das Banner sichtbar wird, blendet das JS das Overlay ein; nach Consent verschwindet es wieder.
- Das Overlay hat `pointer-events: none`, blockiert also keine Klicks oder Scroll-Interaktion.
- Blur ist konservativ voreingestellt (Default 4 px).

Der kleine Widerrufsbutton („Cookie-Einstellungen") nach Consent ist über `Position`, `Offset X` und `Offset Y` konfigurierbar. Vier Positionen stehen zur Wahl: `bottom-right`, `bottom-left`, `top-right`, `top-left`. Damit kollidiert der Button nicht zwangsläufig mit Chat- oder WhatsApp-Widgets unten rechts. Die Positionierung läuft rein über CSS-Klassen und CSS-Variablen — kein JS-Reposition-Loop.

## Rechtliche Links im Banner

Im Banner können optional Links zu Datenschutz und Impressum angezeigt werden.

- `Rechtliche Links im Banner anzeigen` (Checkbox)
- `Datenschutz-URL (manuell, überschreibt Auto-Erkennung)` — Vorrang vor Auto-Erkennung
- `Impressum-URL (manuell, überschreibt Auto-Erkennung)` — Vorrang vor Auto-Erkennung

Die Auto-Erkennung läuft ausschliesslich im WordPress-Admin:

- **Datenschutz**: nutzt `get_privacy_policy_url()` aus dem WordPress-Core.
- **Impressum**: leichtgewichtige Suche nach typischen Seiten-Slugs (`impressum`, `datenschutz-und-impressum`, `legal-notice`, `mentions-legales`, `note-legali`, ...) und -Titeln. Ergebnis wird als WordPress-Transient für 24 Stunden gecached. Das Frontend liest nur den Cache — keine Frontend-Crawls, keine Footer-DOM-Analysen, keine externen Requests.

Anzeige-Logik:

- Wenn Datenschutz- und Impressum-URL identisch sind, erscheint nur ein Link „Datenschutz & Impressum".
- Wenn nur eine der beiden URLs gefunden wird, erscheint nur dieser eine Link.
- Wenn keine URL gefunden wird, erscheinen keine leeren Links.

## Sprach- und Locale-Strategie

Der Default-Banner-Text ist bewusst neutral formuliert (kein „Ihre Zustimmung", kein „deine Zustimmung") und wird je nach Site-Locale automatisch in passender Sprache ausgegeben.

Unterstützte Sprachen mit eigenem Default-Text:

- Deutsch (`de`, `de_CH`, `de_DE`, `de_AT`, ...)
- Englisch (`en`, `en_US`, `en_GB`, ...)
- Französisch (`fr`, `fr_FR`, ...)
- Italienisch (`it`, `it_IT`, ...)
- Türkisch (`tr`, `tr_TR`, ...)
- Ungarisch (`hu`, `hu_HU`, ...)

Die Locale-Lookup-Logik in `Light_Swiss_Cookie_Consent::extract_language_prefix()` akzeptiert sowohl `de_CH` als auch `de-CH`, `de`, `DE`, `de_AT`, `pt_BR` und ähnliche Varianten und mappt sie auf den passenden 2- bis 3-Buchstaben-Sprachpräfix. Wenn keine der unterstützten Sprachen passt, fällt der Default auf Englisch zurück.

Deutsche Texte verwenden Schweizer Schreibweise: echte Umlaute (`ä`, `ö`, `ü`) sind erlaubt, das ß wird nicht verwendet.

Der Admin kann den Banner-Text in den Einstellungen jederzeit überschreiben; in diesem Fall gewinnt der Admin-Wert über den lokalisierten Default, und WPML / Polylang können ihn wie gewohnt übersetzen.

## Technische Basis

- PHP
- CSS mit CSS-Variablen
- Vanilla JavaScript
- Keine externen Libraries
- Kein Build-Prozess
- Uebersetzbare Strings mit WordPress-i18n-Funktionen
- WPML- und Polylang-freundliche String-Registrierung

## Nicht enthalten in 0.1.0

- Kein Auto-Scanner
- Kein GeoIP
- Keine Vendor-Listen
- Kein IAB TCF
- Keine Google-Cloud-Abhaengigkeiten
- Kein Statistik-Dashboard
- Keine Auto-Block-Engine
