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
| 4a | Im selben Profil: „Cookie-Einstellungen" öffnen → „Nur notwendige" | Consent gespeichert; bereits laufende Skripte laufen bis zum Seitenwechsel weiter (bekannte Grenze, siehe unten) |
| 4b | Reload + Interaktion, 8 s warten | 0 neue Tracking-Requests der Seite, `fbq`/`gtag` undefined, gated-Scripts wieder `type="text/plain"`. Vor dem Widerruf gesetzte Cookies dürfen noch existieren (werden nicht gelöscht, aber von keinem Skript mehr gelesen). |

**Bewertung 4b:** Ein einzelner `POST …/g/collect` direkt nach dem Reload ist der `pagehide`-Beacon der **vorherigen** Seite (GA sendet `user_engagement` beim Verlassen). Er entsteht vor dem Laden der neuen Seite; massgeblich ist, dass `gtag` auf der neuen Seite undefined bleibt und kein `gtag/js` geladen wird.

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
