# Support

## Unterstuetzte WordPress-Versionen

Fuer die erste Testversion wird eine aktuelle WordPress-Installation empfohlen.

- Empfohlen: WordPress 6.5 oder neuer
- Ziel fuer Tests: aktuelle WordPress-Hauptversion und die jeweils vorherige Hauptversion

## Unterstuetzte PHP-Versionen

- Empfohlen: PHP 8.0 oder neuer
- Mindestziel fuer Tests: PHP 7.4

## Getestete Plugins

Diese Integrationen sind vorbereitet und sollen vor produktivem Einsatz in einer echten WordPress-Testumgebung geprueft werden:

- WPML
- PolyLang

## Bekannte Einschraenkungen

- Normale `<script>`-Tags werden nicht automatisch blockiert.
- Nur bewusst markierte Skripte werden blockiert.
- Blockierte Skripte muessen `type="text/plain"` und `data-cookie-category` verwenden.
- Es gibt keine Auto-Scanner, Vendor-Listen, GeoIP-Funktion, IAB-TCF-Integration oder Statistik-Dashboard.
