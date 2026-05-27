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

## Consent-Speicherung

Die Auswahl wird in `localStorage` und als Cookie gespeichert.

- Cookie-Name: `lscc_consent`
- Ablaufzeit: 180 Tage
- SameSite: `Lax`
- Secure: wird bei HTTPS gesetzt
- Consent-Version: `1`

Ungueltige oder nicht parsebare Consent-Daten werden verworfen. In diesem Fall wird das Banner erneut angezeigt.

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
