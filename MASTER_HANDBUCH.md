# MASTER_HANDBUCH.md

## Versionshistorie

- 2026-05-28 — Datei erstellt (initial in WordPad, gespeichert als RTF mit `.md`-Endung).
- 2026-05-28 — Format-Migration: RTF in echtes UTF-8 Markdown konvertiert. Inhalt unverändert; nur RTF-Steuerzeichen entfernt, Unicode-Umlaute hergestellt, typografische Anführungszeichen normalisiert.
- 2026-05-28 — Datei umbenannt von `CLAUDE_CONTINUITY_MASTER.md` zu `MASTER_HANDBUCH.md`. Inhalt vollständig erhalten. Referenzen in `PROJECT_BRIEF.md`, `DECISIONS.md` und `DEV_LOG.md` aktualisiert.
- 2026-06-03 — Additive Erweiterung: verbindliche Regel zum Ablageort von Test-ZIPs ergänzt (Sektion „Release-Artefakte / Ablageort für Test-ZIPs"). Inhalt sonst unverändert.
- 2026-06-03 — Additive Erweiterung: Sektion „Avada-Massenkompatibilität (Strategie)" ergänzt. Hält das Einsatzziel (≈40 Avada-Sites) und die empfohlene, No-Go-konforme Richtung fest. Reine Strategie-Dokumentation, keine Umsetzung.

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
