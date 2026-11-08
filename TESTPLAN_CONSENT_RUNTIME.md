# Testplan: Consent-Runtime (Tracking vor/nach Zustimmung)

Stand: v1.0.8 (2026-11-09, ADR-41). Werkzeug: `tools/consent-runtime-test.js` (Headless-Chrome via `playwright-core`, Installation ausserhalb des Repositorys, siehe Dateikopf).

> Ausgefüllte Testergebnisse mit Kunden-Domains gehören **nicht** ins Repository (gleiche Regel wie `INVENTUR_VORLAGE.md`). Ergebnisse landen im Bericht bzw. unter `%TEMP%\mcb-consent-results\`.

## Ziel

Kein Tracking-, Statistik- oder Marketing-Skript und kein zugehöriges Cookie vor der ausdrücklichen Zustimmung zur passenden Kategorie. „Nur notwendige" lässt alles blockiert. Ein Widerruf über „Cookie-Einstellungen" wirkt spätestens ab dem nächsten Seitenaufruf.

## Was als Tracking zählt

**Request-Hosts:** `googletagmanager.com`, `google-analytics.com`, `analytics.google.com`, `googleadservices.com`, `doubleclick.net`, `facebook.net`, `facebook.com`, `chimpstatic.com`, `list-manage.com`, `hotjar.com`, `clarity.ms`, `bing.com/action`, `tiktok.com`, `linkedin.com/px`, sowie `admin-ajax.php` mit `action=pys_*`.

**Cookies (Präfix):** `_ga`, `_gid`, `_gat`, `_gcl`, `_fbp`, `_fbc`, `fr`, `pys*`, `last_pys*`, `pbid`, `_hj`, `_clck`, `_clsk`, `mailchimp*`, `_uet`, `_ttp`, `li_`.

## Schritte und Erwartungswerte

Jeder Schritt startet mit leerem Browserprofil, sofern nicht anders angegeben.

| Schritt | Aktion | Erwartung (bestanden) |
|---|---|---|
| 1a | Erstbesuch, 8 s warten, **keine** Interaktion | 0 Tracking-Requests, 0 Tracking-Cookies, `fbq`/`gtag` undefined |
| 1b | Wie 1a, dann Maus bewegen + scrollen (**kein** Banner-Klick), 8 s warten | wie 1a. **Pflichtschritt:** WP Rocket „Delay JS" und ähnliche Optimierer starten verzögerte Skripte erst bei Interaktion — ohne 1b bleibt ein Bypass unsichtbar. |
| 2a | „Nur notwendige" klicken, 8 s warten | wie 1a |
| 2b | Reload + Interaktion, 8 s warten | wie 1a |
| 3a | Neues Profil: „Alle akzeptieren" klicken, 8 s warten | Skripte der zugestimmten Kategorien laden (z. B. `gtag/js` + `g/collect`, `fbevents.js` + `facebook.com/tr`), zugehörige Cookies werden gesetzt |
| 3b | Reload + Interaktion | wie 3a (Consent bleibt erhalten) |
| 4a | Im selben Profil: „Cookie-Einstellungen" (oder Link `#cookie-settings`) öffnen → „Nur notwendige" | Seite lädt sofort neu (ab v1.0.8); danach keine Tracking-Cookies der Liste mehr (`_ga*`, `_gid`, `_gat*`, `_gcl_*`, `_fbp`, `_fbc`, `pys*`, `pbid`, `mailchimp*`, `_mcid`), `fbq`/`gtag` undefined |
| 4b | Reload + Interaktion, 8 s warten | 0 neue Tracking-Requests, gegatete Scripts wieder `type="text/plain"`. Drittanbieter-Cookies (z. B. `fr` auf facebook.com) sind von der Seite aus nicht löschbar und gelten nicht als Fehler. |
| 4c | Teil-Widerruf: alles akzeptieren, dann nur „Marketing" abwählen und speichern | Reload; Marketing-Cookies weg, Statistik-Cookies bleiben, GA läuft, Meta Pixel/Mailchimp nicht |
| 4d | Seite mit `#cookie-settings` aufrufen bzw. Link „Cookie Einstellungen" klicken | Einstellungen öffnen sich auf der aktuellen Seite, kein Tracking |

**Bewertung 4a/4b:** Ab v1.0.8 setzt der Widerruf vor dem Reload `ga-disable-<ID>`; ein `POST …/g/collect` (GA-`pagehide`-Beacon der vorherigen Seite) nach dem Widerruf gilt daher als Fehler.

## Pro Site zusätzlich prüfen

- **Seitenoptimierer:** WP Rocket (Delay JS / Defer), Cloudflare Rocket Loader, LiteSpeed, Perfmatters, Autoptimize — im HTML nachsehen, dass gegatete Tags `type="text/plain"` als **erstes** `type`-Attribut tragen und kein `data-rocket-src` haben.
- **Page-Cache:** Test einmal mit warmem Cache (zweiter Aufruf) wiederholen.
- **Quellen ausserhalb des Plugins:** WPCode/„Insert Headers and Footers", Theme-Header-Felder, Google Site Kit, PixelYourSite, Mailchimp for WooCommerce, GTM-Plugins.

## Ausführung

```
set NODE_PATH=%TEMP%\mcb-pw\node_modules
node tools\consent-runtime-test.js https://site-a.example/ https://site-b.example/
```

Ausgabe pro Schritt: Anzahl Tracking-Requests, Liste der Hosts/Pfade (Query entfernt), Tracking-Cookies mit Domain, `fbq`/`gtag`-Typ, Anzahl gegateter Scripts, Anzahl noch verzögerter WP-Rocket-Scripts, Banner sichtbar ja/nein. Vollständige Rohdaten als JSON.

## Lokale Regressionsprüfung (ohne Live-Site)

Für Code-Änderungen am Gating: Fixture-Seite aus der echten Pipeline erzeugen (Plugin-Gating → WP-Rocket-`DelayJS\HTML::delay_js()` → WP-Rocket-Runtime), lokal ausliefern und mit `ABORT_TRACKING=1` testen (Tracking-Requests werden protokolliert und abgebrochen; es gehen keine Testdaten an Google/Meta). Vorgehen und Ergebnisse der v1.0.8-Validierung: `DEV_LOG.md` → „1.0.8".
