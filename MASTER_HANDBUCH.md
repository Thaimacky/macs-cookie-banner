# MASTER_HANDBUCH.md

## Versionshistorie

- 2026-05-28 — Datei erstellt (initial in WordPad, gespeichert als RTF mit `.md`-Endung).
- 2026-05-28 — Format-Migration: RTF in echtes UTF-8 Markdown konvertiert. Inhalt unverändert; nur RTF-Steuerzeichen entfernt, Unicode-Umlaute hergestellt, typografische Anführungszeichen normalisiert.
- 2026-05-28 — Datei umbenannt von `CLAUDE_CONTINUITY_MASTER.md` zu `MASTER_HANDBUCH.md`. Inhalt vollständig erhalten. Referenzen in `PROJECT_BRIEF.md`, `DECISIONS.md` und `DEV_LOG.md` aktualisiert.
- 2026-06-03 — Additive Erweiterung: verbindliche Regel zum Ablageort von Test-ZIPs ergänzt (Sektion „Release-Artefakte / Ablageort für Test-ZIPs"). Inhalt sonst unverändert.
- 2026-06-03 — Additive Erweiterung: Sektion „Avada-Massenkompatibilität (Strategie)" ergänzt. Hält das Einsatzziel (≈40 Avada-Sites) und die empfohlene, No-Go-konforme Richtung fest. Reine Strategie-Dokumentation, keine Umsetzung.
- 2026-06-10 — Additive Erweiterung: Sektion „Dokumentationspflicht (Definition of Done)" ergänzt — Doku-Dateien müssen nach jeder Plugin-Änderung sofort aktualisiert werden. Inhalt sonst unverändert.
- 2026-06-11 — Additive Erweiterung: Sektion „PFLICHT: AKTION USER / PROMPT-BLÖCKE" ergänzt (jeder handlungsrelevante Bericht endet mit einem klar gekennzeichneten Block). Zusätzlich Sektion „Fremd-Plugin-Kompatibilität (YOTU, ab v0.2.2)" ergänzt. Inhalt sonst unverändert.
- 2026-06-11 — Regel-Check (v0.2.3, Consent-UI-Bugfix): Sektionen „PFLICHT: AKTION USER / PROMPT-BLÖCKE" und „Dokumentationspflicht (Definition of Done)" sind vorhanden und unverändert gültig; keine inhaltliche Handbuch-Änderung für diesen Bugfix nötig.
- 2026-06-11 — Regel-Check (v0.2.4, UX-Fix Schnellbuttons): aktiver Consent-Zustand an den Schnellbuttons (ADR-22), reine Darstellung; keine inhaltliche Handbuch-Änderung nötig. Versionshistorie hier ergänzt (Definition of Done).
- 2026-06-11 — v0.3.0: Consent-Code-Manager (Phase 1 der Produktiv-Roadmap, ADR-23) — zentrale, consent-gegatete Verwaltung von Tracking-Snippets (GA4/GTM/Pixel/Hotjar). Nutzt die bestehende Script-Blockade (kein neues Frontend-JS). Versioniertes Export/Import-Envelope, scannerfähiges Datenmodell. Versionshistorie ergänzt; keine Änderung an Philosophie/No-Gos.
- 2026-06-12 — v0.3.1: Scanner-Ausbau „Drittanbieter-Oberfläche" (Phase 2, ADR-24) — Gating-Status pro Dienst (5-Status-Modell inkl. „Nicht prüfbar"), Cross-Reference zum Consent-Code-Manager, eigene gleicher-Host-Test-URL, Google-Fonts-Sonderhinweis. Reine Lese-/Hinweisfunktion, ADR-4-konform; keine Maps/Vimeo-Umsetzung. Versionshistorie ergänzt; keine Änderung an Philosophie/No-Gos.

## Zweck dieser Datei

Diese Datei dient als dauerhafte Übergabe-/Kontinuitätsdatei für zukünftige Claude-/Codex-/AI-Sessions.

Ziel:
Das Projekt soll langfristig konsistent bleiben, ohne dass Architektur, Philosophie oder Sicherheitsentscheidungen vergessen werden.

Diese Datei ist NICHT für Endkunden gedacht.
Sie ist nur für Entwickler-/AI-Kontinuität gedacht.

---

# Projekt

Name (Arbeitsname):
Light Swiss Cookie Consent

Status:
Aktive Entwicklung

Aktuelle Basis:
v0.1.0

Technologie:

* WordPress Plugin
* PHP
* Vanilla JavaScript
* CSS
* keine Frameworks
* keine externen Libraries
* kein Build-System

Git:
Vorhanden

GitHub:
Privates Repository vorhanden

---

# Hauptziel des Projekts

Sehr leichtes, schnelles, kontrollierbares Cookie-/Consent-Plugin als pragmatische Alternative zu überladenen Consent-Suiten wie:

* Real Cookie Banner
* Cookiebot
* Complianz
* ähnliche „Monster“-Plugins

Primäre Zielmärkte:

* Schweiz
* Deutschland
* EU

Wichtig:
Deutschland-konservative technische Architektur, aber ohne overengineerte Abmahn-Hysterie.

---

# WICHTIGSTE PROJEKTPHILOSOPHIE

Dieses Plugin soll bewusst:

* klein bleiben
* schnell bleiben
* kontrollierbar bleiben
* verständlich bleiben
* cachefreundlich bleiben
* WPML-freundlich bleiben
* PolyLang-freundlich bleiben
* updatebar bleiben

NICHT:

* automatisiert alles „hijacken“
* Themes kaputtmachen
* DOM aggressiv umschreiben
* Scanner-Hölle werden
* Monster-Vendor-System werden
* Performance zerstören

---

# ABSOLUTE NO-GOS

NIEMALS automatisch:

* iframes umschreiben
* fremde DOM-Strukturen manipulieren
* komplette Seiten scannen/crawlen
* Themes automatisch modifizieren
* aggressive MutationObserver einsetzen
* bestehende Inhalte „smart ersetzen“
* riesige Vendor-Listen einbauen
* IAB-TCF-Monster bauen
* unnötige Frameworks einführen

KEIN:

* React
* Vue
* Angular
* jQuery-Abhängigkeit
* npm
* Composer-Zwang
* Cloud-System
* Tracking-System
* externe JS-Libraries

---

# Consent-Philosophie

Default:
Nur notwendige Cookies aktiv.

Keine vorausgewählten optionalen Kategorien.

„Nur notwendige“ muss gleichwertig erreichbar sein.

Consent-Kategorien aktuell:

* necessary
* statistics
* marketing
* external_media

Script-Blocking bewusst nur kontrolliert über:
type="text/plain"
plus:
data-cookie-category

Normale <script>-Tags werden ABSICHTLICH NICHT automatisch umgeschrieben.

Das ist KEIN Bug.
Das ist eine Architekturentscheidung.

---

# Privacy-Check-Philosophie

Privacy Check ist:

* passiv
* leicht
* hinweisbasiert

NICHT:

* aggressiv
* automatisch blockierend
* umschreibend
* crawlerbasiert

Aktuelle Checks:

* Google Fonts
* Google Analytics
* GTM
* Facebook
* YouTube
* Vimeo

Wichtig:
Privacy Check soll helfen, aber NICHT Websites zerstören.

---

# Service-Komponenten-Philosophie

Externe Medien werden bewusst über kontrollierte Shortcodes gelöst.

Aktuell:

* [lscc_youtube]
* [lscc_vimeo]
* [lscc_google_map]

WARUM:
Shortcodes sind stabiler als aggressive iframe-Rewrites.

Die Architekturentscheidung lautet:
freiwillige kontrollierte Einbindung statt automatischer DOM-Magie.

---

# WPML / PolyLang

WICHTIG:
WPML-Kompatibilität ist Kernziel.

Das ursprüngliche Problem mit Real Cookie Banner war:

* WPML-Konflikte
* Überkomplexität
* langsame Performance
* aggressive Auto-Systeme

Dieses Projekt darf NIEMALS dieselben Fehler machen.

---

# Sprachstrategie

Aktuell vorbereitet:

* de_CH
* en_US
* fr_FR
* it_IT
* tr_TR
* hu_HU

Deutschregel:

* Schweizer Schreibweise
* KEIN ß
* Umlaute sind erlaubt

---

# Sicherheitsphilosophie

Wichtig:
Dieses Plugin soll konservativ und sauber entwickelt werden.

Immer verwenden:

* esc_html()
* esc_attr()
* sanitize_text_field()
* sanitize_hex_color()
* wp_nonce_field()
* current_user_can()
* wp_safe_redirect()
* ABSPATH-Checks
* WordPress i18n

Vermeiden:

* innerHTML
* eval
* unnötige globale Side-Effects
* aggressive DOM-Manipulation

---

# Aktuelle Architektur

## Hauptdateien

light-swiss-cookie-consent.php
Plugin-Bootstrap / Optionen / Banner / i18n

includes/admin-page.php
Admin-Oberfläche / Einstellungen

includes/privacy-check.php
Privacy-Check-System

includes/service-components.php
YouTube / Vimeo / Maps Komponenten

assets/js/banner.js
Consent-Logik / Speicherung / Script-Freigabe

assets/css/banner.css
Banner- und Komponenten-Styles

---

# Wichtige Entwicklungsregel

Vor Änderungen IMMER:

1. PROJECT_BRIEF.md lesen
2. ACTIVE_CODE_MAP.md lesen
3. DECISIONS.md lesen
4. DEV_LOG.md lesen

Danach erst implementieren.

---

# Dokumentationspflicht (Definition of Done)

VERBINDLICH (ab 2026-06-10):

Nach jeder Änderung am Plugin müssen MASTER_HANDBUCH.md, ACTIVE_CODE_MAP.md, DECISIONS.md, DEV_LOG.md und RELEASE_CHECKLIST.md sofort auf den aktuellen Stand gebracht werden. Dokumentation ist Teil der Definition von fertig.

Eine Änderung gilt erst dann als abgeschlossen, wenn der zugehörige Code UND die betroffene Dokumentation konsistent sind. Reine Code-Änderungen ohne synchrone Doku-Aktualisierung sind nicht zulässig.

---

# PFLICHT: AKTION USER / PROMPT-BLÖCKE

VERBINDLICH (ab 2026-06-11):

Wenn ein Bericht eine Aktion des Users erfordert, muss am Ende des Berichts immer ein klar gekennzeichneter Block stehen.

Verpflichtende Form:

```
==================================================
AKTION USER
===========
```

oder

```
==================================================
PROMPT FÜR CHATGPT
==================
```

oder

```
==================================================
PROMPT FÜR CLAUDE CODE
======================
```

Danach folgt ausschliesslich der relevante kopierbare Inhalt.

Der User darf niemals suchen müssen, wo der relevante Teil beginnt.

Analysen, Root Cause, Risiken, Validierung und Kommentare dürfen niemals mit dem kopierbaren Teil vermischt werden.

Diese Regel gilt für:

* Analysen
* Abschlussberichte
* Testberichte
* Freigaben
* Umsetzungspläne
* Release-Berichte

Definition of Done:
Ein Bericht ist erst fertig, wenn der User sofort erkennt, was er kopieren oder ausführen muss.

---

# PFLICHT: KOPIERMARKIERUNG FÜR BERICHTE

VERBINDLICH (ab 2026-06-12):

Jeder Analyse- und Abschlussbericht muss mit einer gut sichtbaren Kopiermarkierung **beginnen** und mit der Endmarkierung **abschliessen**:

```
============================
AB HIER AN CHATGPT KOPIEREN
============================

... Berichtsinhalt ...

============================
ENDE KOPIERBEREICH
============================
```

Zweck: Der User leitet Berichte an ChatGPT weiter und muss sofort erkennen, welcher Bereich kopierbar ist. Alles zwischen den Markierungen ist der vollständige, in sich verständliche Bericht (inkl. eines etwaigen `AKTION USER`-Blocks). Prozess-/Meta-Hinweise dürfen ausserhalb des Kopierbereichs stehen.

Diese Regel gilt zusätzlich zur Regel „PFLICHT: AKTION USER / PROMPT-BLÖCKE".

---

# Commit-Philosophie

Keine riesigen Misch-Commits.

Bevorzugt:

* 1 Feature = 1 Commit
* Doku separat
* Architektur separat
* UI separat

Saubere Git-Historie ist wichtig.

---

# Bekannte bewusste Grenzen

Das Plugin:

* blockiert keine normalen <script>-Tags automatisch
* scannt keine gesamte Website aggressiv
* ersetzt keine bestehenden iframes automatisch
* ist kein vollständiges Rechtsberatungsprodukt
* führt keine vollständigen DSGVO-Audits durch

Das ist ABSICHTLICH so.

---

# Wichtig für zukünftige AI-Sessions

Nicht kreativ „verbessern“.

Nicht plötzlich:

* React einführen
* Scanner bauen
* Auto-Rewrite-Systeme bauen
* Performance zerstören
* Architektur neu erfinden

Das Projekt lebt von:

* Kontrolle
* Einfachheit
* Stabilität
* Nachvollziehbarkeit

---

# Status Ende dieser Session

Vorhanden:

* Consent-System
* Privacy Check v0.1
* Service Components v0.1
* GitHub
* Git
* Release-Struktur
* PHP-Linting lokal
* WPML-/i18n-Basis
* Dokumentationsstruktur

Nächste sinnvolle Schritte:

* echte WPML-Tests
* PolyLang-Tests
* Mobile-UX
* Consent-Mode-v2-Basis
* bessere Privacy-Hinweise
* Script-Registry
* echte Release-Strategie

---

# Letzter wichtiger Hinweis

Dieses Plugin soll NICHT der nächste überladene Cookiebot-/Real-Cookie-Banner-Klon werden.

Lieber:

* kleiner
* stabiler
* kontrollierter
* verständlicher
* schneller

als:

* „100% automatisch“
* aber kaputt/unwartbar/langsam.

---

# Release-Artefakte / Ablageort für Test-ZIPs

VERBINDLICH (ab 2026-06-03):

Alle Test-ZIPs werden im **ÜBERORDNER (Parent-Verzeichnis) des Git-Repositories** abgelegt — NIEMALS im Repository selbst.

Repository:
`G:\Cookie Banner Plugin\light-swiss-cookie-consent\`

ZIP-Ziel (fest):
`G:\Cookie Banner Plugin\`

Korrekt:

```
G:\Cookie Banner Plugin
├─ light-swiss-cookie-consent\          (Repository)
├─ light-swiss-cookie-consent-v0.1.6-test.zip
├─ light-swiss-cookie-consent-v0.1.7-test.zip
└─ light-swiss-cookie-consent-v0.1.8-test.zip
```

Nicht zulässig:

```
G:\Cookie Banner Plugin\light-swiss-cookie-consent\
└─ light-swiss-cookie-consent-v0.1.8-test.zip   ← FALSCH
```

Regeln für den Agent:

* nicht suchen
* nicht raten
* nicht interpretieren
* keine alternativen Orte wählen

Standard-Ziel ist IMMER das Parent-Verzeichnis des Repositories. Liegen dort bereits ältere ZIPs, wird jede neue ZIP ebenfalls dort erstellt. Eine Änderung des Ablageorts ist nur mit ausdrücklicher Anweisung des Auftraggebers zulässig. Bestehende Projektpraxis hat Vorrang vor Annahmen des Agents.

---

# Avada-Massenkompatibilität (Strategie)

Stand 2026-06-03 — dokumentierte Strategie, noch keine Umsetzung.

## Einsatzziel

Das Plugin soll auf ca. **40 bestehenden Avada-Websites** bestehende Cookie-Banner ersetzen. Bestehende Avada-Video-Elemente (YouTube/Vimeo) müssen vor Consent geblockt werden, **ohne** dass hunderte/tausende Seiten manuell geprüft oder umgebaut werden. Shortcode-only reicht für diesen Bestand nicht.

## Empfohlene Richtung

**Render-Layer-Interception als opt-in Avada-Kompatibilitäts-Modul.** Avada (Fusion Builder) speichert Videos als Shortcodes im `post_content` und rendert sie serverseitig zu iframes. WordPress-Filter erlauben das Abfangen **vor** der iframe-Erzeugung:

- `pre_do_shortcode_tag` für `fusion_youtube` / `fusion_vimeo` → statt Avada-iframe das bestehende LSCC-Platzhalter-Markup (Kategorie `external_media`) ausgeben.
- `embed_oembed_html` für nackte oEmbed-Video-URLs.

Das iframe entsteht erst nach Consent über die **vorhandene** JS-Mechanik. Vorteile: skaliert automatisch über alle Seiten, `post_content` bleibt unangetastet, vollständig reversibel (Modul aus), cachebar.

## No-Go-Konformität

Dieser Ansatz verletzt KEINE der absoluten No-Gos: kein MutationObserver, kein DOM-Hijacking fertiger iframes, kein Frontend-Scanner, kein Crawler, kein automatisches Umschreiben von `<script>`-Tags. Es ist Interception der *eigenen Render-Pipeline*, nicht nachträgliche DOM-Manipulation.

## Ausdrücklich verworfen

**Serverseitige Content-Migration** (`post_content` umschreiben: `fusion_youtube` → `lscc_youtube`) wird NICHT empfohlen: Risiko des Fusion-Builder-Desyncs (Avada hält eigene Builder-Repräsentation/Meta), destruktiv, über 40 Sites schlecht reversibel — widerspricht „keine Inhalte/Themes kaputtmachen".

## Bekannte Abdeckungslücken

Hintergrundvideos (Container/Section), `fusion_code`-Roh-Embeds und handgepastete `<iframe>` werden von der Shortcode-Interception NICHT erfasst. Diese bleiben Restposten und werden über den passiven Privacy-Check nur sichtbar gemacht, nicht automatisch umgebaut.

## Vorbedingungen vor Umsetzung

1. Technischer Spike an einer echten Avada-Seite: exakte Fusion-Shortcode-Tags/Attribute (versionsabhängig!), Hintergrundvideo-Pfad, und Prüfung auf Konflikt mit Avadas eigener Privacy-/Embed-Funktion (es darf nur EINE Consent-Schicht aktiv sein).
2. Formelle Scope-Freigabe + neue ADR, da dies bewusst über „Shortcode-only" hinausgeht.

---

# Fremd-Plugin-Kompatibilität (YOTU, ab v0.2.2)

Stand 2026-06-11 — umgesetzt (ADR-20).

## Einsatzziel

Fremde YouTube-Plugins, die ihre Player **clientseitig per JS** laden (statt serverseitig ein iframe zu rendern), entziehen sich der Avada-Render-Interception (ADR-17). Beispiel: **Yotuwp – Easy YouTube Embed** lädt `youtube.com/iframe_api` per `frontend.min.js`; die Thumbnails kommen per Lazy-Load von `i.ytimg.com`.

## Richtung (No-Go-konform)

Für solche Plugins ist der korrekte Hebel das **Script-Gating über die bestehende `type="text/plain"`-Script-Blockade** (ADR-6), nicht eine Shortcode-Ersetzung: der registrierte Script-Handle wird via `script_loader_tag`/`wp_inline_script_attributes` an `external_media` gekoppelt; lazy-geladene Drittanbieter-Thumbnails werden im Shortcode-Output neutralisiert (`data-orig-src` → `data-lscc-orig-src`, Restore nach Consent durch `banner.js`). Kein DOM-Hijacking, kein MutationObserver, kein Scanner, keine `post_content`-Migration.

## Vorbedingungen vor Umsetzung (pro Plugin)

1. Spike: exakter Script-Handle, Inline-Abhängigkeiten (Localize/`after`), wer den Thumbnail-Abruf auslöst (Plugin-JS vs. Theme-Lazy-Load), Block-/Widget- vs. Shortcode-Rendering.
2. Opt-in (Default AUS), reversibel, eigene ADR. Nur EINE Consent-Schicht aktiv (Plugin-eigene Consent-Funktion prüfen).
