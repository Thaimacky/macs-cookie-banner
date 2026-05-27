# CLAUDE_CONTINUITY_MASTER.md

## Versionshistorie

- 2026-05-28 — Datei erstellt (initial in WordPad, gespeichert als RTF mit `.md`-Endung).
- 2026-05-28 — Format-Migration: RTF in echtes UTF-8 Markdown konvertiert. Inhalt unverändert; nur RTF-Steuerzeichen entfernt, Unicode-Umlaute hergestellt, typografische Anführungszeichen normalisiert.

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
