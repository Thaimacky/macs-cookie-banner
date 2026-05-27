# Project Brief

## Pflichtlektuere und Vorrangs-Quelle

Vor jeder Analyse, jedem Patch und jeder Architekturaenderung gilt die Datei `MASTER_HANDBUCH.md` im selben Plugin-Ordner als verpflichtende Lektuere. Sie hat Vorrang bei:

- Architekturentscheidungen
- Scope-Kontrolle
- Performance-Philosophie
- Consent-Philosophie
- WPML- und Polylang-Zielen
- Vermeidung von Overengineering

Die Datei darf nicht umbenannt, nicht ueberschrieben und nicht gekuerzt werden. Erweiterungen erfolgen ausschliesslich additiv mit klarer Versionshistorie. Bei Konflikten zwischen diesem Brief und der Master-Datei gewinnt die Master-Datei.

## Ziel

Light Swiss Cookie Consent ist ein leichtes, konservatives WordPress-Plugin fuer Cookie-Consent. Es zeigt ein modernes dunkles Banner an, speichert die Auswahl in `localStorage` und einem Cookie und laedt bewusst markierte Skripte erst nach Zustimmung.

Die Architektur ist klein, kontrollierbar und nachvollziehbar. Sie verzichtet bewusst auf ueberladene Consent-Suiten.

## Nicht-Ziele

Folgende Funktionen sind in Version 0.1.0 bewusst nicht enthalten und auch nicht geplant fuer den Kurzfristhorizont:

- kein Auto-Scanner fuer Cookies oder Drittanbieter-Skripte
- kein automatisches Umschreiben bestehender `<script>`-Tags
- keine automatische Erkennung oder Ersetzung bestehender iframes
- keine GeoIP-Logik
- keine Vendor-Listen
- keine IAB-TCF-Integration
- keine Cloud-Abhaengigkeiten
- kein Statistik-Dashboard
- keine Auto-Block-Engine
- kein React, Vue oder jQuery
- kein npm, kein Composer
- kein Build-Prozess
- keine externen JavaScript-Libraries oder CDNs
- keine aggressiven MutationObserver oder Hintergrund-Crawler

## Zielgruppe

Kleine und mittlere Business-Websites, primaer in der Schweiz und in Deutschland. Typische Nutzer betreiben eine WordPress-Installation mit einer ueberschaubaren Zahl externer Dienste (z. B. YouTube, Vimeo, Google Maps, Google Analytics, Google Tag Manager, Facebook).

## Konservativer Standard fuer DE / EU / CH

Das Plugin ist konservativ ausgelegt fuer den Einsatz in der Schweiz, der EU und Deutschland. Es bietet keine Garantie auf absolute Rechtssicherheit, folgt aber den verbreiteten Grundprinzipien:

- Default ist `nur notwendige Cookies`.
- Statistik, Marketing und externe Medien sind ohne Zustimmung blockiert.
- "Nur notwendige" ist gleichwertig zu "Alle akzeptieren" erreichbar.
- Keine vorausgewaehlten optionalen Kategorien.
- Widerruf ist jederzeit moeglich.
- Consent wird versioniert gespeichert (`version` 1).

## Bewusst kein Auto-Scanner

Es findet kein automatischer Scan der Seite und keine automatische Klassifikation gefundener Skripte oder Cookies statt. Der `Privacy Check` ist eine passive Hinweis-Liste, die einmal die Startseite via `wp_remote_get` abruft und gegen eine kurze statische Mustertabelle prueft. Er aendert keine Inhalte.

## Bewusst kein Auto-Rewrite

Normale `<script>`-Tags werden nicht automatisch blockiert oder umgeschrieben. Nur Skripte mit `type="text/plain"` und `data-cookie-category` werden vom Banner aktiviert, sobald die zugehoerige Kategorie zugestimmt ist. Bestehende iframes bleiben unangetastet; nur Shortcode-Komponenten zeigen vor Zustimmung Platzhalter.

## WPML- und Polylang-Ziel

Texte im Banner sind ueber den Admin pflegbar und werden zusaetzlich fuer WPML String Translation und Polylang registriert. Die Funktionen `wpml_register_single_string`, `pll_register_string`, `wpml_translate_single_string` und `pll__` werden defensiv via `do_action` bzw. `function_exists` angesprochen, damit das Plugin auch ohne installiertes WPML oder Polylang funktioniert.

## Unterstuetzte Sprachen

Die Sprachstruktur ist vorbereitet fuer:

- `de_CH` (Deutsch Schweiz, ohne ß)
- `en_US`
- `fr_FR`
- `it_IT`
- `tr_TR`
- `hu_HU`

Das Template liegt unter `languages/light-swiss-cookie-consent.pot`. Die `.po`-Dateien existieren als Skelette und werden vor produktivem Einsatz befuellt und zu `.mo` kompiliert.

## Erfolgskriterien

Das Projekt ist erfolgreich, wenn es auf einer typischen kleinen WordPress-Seite:

- ohne Build-Schritt installierbar und sofort lauffaehig ist,
- keine spuerbare Performance-Last erzeugt,
- mit Caching-Plugins kompatibel bleibt,
- mit WPML und Polylang ohne Sonderkonfiguration zusammenarbeitet,
- nach Aktivierung keine bestehenden Inhalte automatisch veraendert,
- die vier Standardkategorien sauber durchsetzt und persistiert,
- den Widerruf jederzeit ueber Shortcode oder festen Button erlaubt.
