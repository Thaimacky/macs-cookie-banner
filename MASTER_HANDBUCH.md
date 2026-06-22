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
- 2026-06-16 — v0.3.3: GitHub-Auto-Updates via Plugin-Update-Checker (PUC v5.6), Updates aus Release-ZIP-Asset (`enableReleaseAssets`); `.gitignore`-Härtung (verankerte Dev-Ignores, damit gebündeltes PUC-`vendor/` tracked bleibt). Versionshistorie ergänzt; keine Änderung an Philosophie/No-Gos.
- 2026-06-21 — Additive Erweiterung: Sektion „PFLICHT: DEBUGGING / RUNTIME-PROOFS — KEINE ARBEITSVERLAGERUNG AUF DEN USER" ergänzt. Marcel ist Entscheider/Tester, nie Debug-Operator: keine FTP/SFTP/Server-/debug.log-/wp-config-/DB-/Quellcode-Suche, keine WP-CLI/mu-plugins. Runtime-Werte primär per Admin-Notice/UI sichtbar machen; Server-Logs nur als letztes Mittel. Antwortformat bei Problemen: Problem/Ursache/Fix/Nächster Schritt. Priorität HOCH, verbindlich. Inhalt sonst unverändert.
- 2026-06-16 — Prozess-Entscheidung ADR-26: „Validierung vor weiteren Features — 5-Site-Gate". Vor neuen Dienst-Features wird LSCC auf mind. 5 echten Websites produktiv analysiert (Phase A Live-Test → Phase B Aggregat-Matrix → Phase C nächste Entwicklung). Erhebungsinstrument `VALIDIERUNG.md` angelegt. Keine Code-/Philosophie-/No-Go-Änderung; reine Priorisierung.
- 2026-06-16 — Additive Erweiterung/Verschärfung: Sektion „PFLICHT: KOPIERMARKIERUNG FÜR BERICHTE" auf harte Pflichtregel angehoben — exakter Markertext (🚨 KOPIEREN AB/BIS HIER FÜR CHATGPT 🚨), Konsequenzen bei Fehlen (unvollständig/nicht abnahmefähig/nicht freigegeben/erneut erstellen) und expliziter Geltungsbereich (Analysen, Abschlussberichte, Patches, Releases, Inventuren, Roadmaps, ADR-Berichte, Debug-Reports, Validierungsberichte). Inhalt sonst unverändert.
- 2026-06-12 — v0.3.2: Avada-Google-Maps Consent-Gating (Phase 3A, Variante 3A-i, ADR-25) — `fusion_map` → LSCC-Platzhalter → Google-Maps-Embed nach Consent; Maps-JS-API SRC-basiert gegated; `[lscc_google_map address="…"]`. Opt-in, Default AUS. Reuse bestehender Muster (Render-Interception + Script-Gating), kein Avada-Reinit/Observer/DOM-Hijack. Avada-Privacy-Maps und LSCC-Maps nicht parallel (eine Consent-Schicht). Versionshistorie ergänzt; keine Änderung an Philosophie/No-Gos.
- 2026-06-20 — v0.5.4 / ADR-28: Locale-aware Banner-Anzeige bei Sprachwechsel — Banner erscheint in der neuen Sprache erneut, ohne Re-Consent (Consent/Cookie/`lscc_consent`/`MCB_CONSENT_VERSION` unverändert; Locale-Metadaten im separaten Key `mcb_consent_locale`). Zusätzlich: Reopen-/Cookie-Einstellungs-Button trägt in Modern/Premium sichtbar die Markenfarbe; temporärer X-Dismiss des Reopen-Buttons (session-only, kein Storage, ersetzt nicht die `hidden`-Option). Keine Consent-/Scanner-/CCM-/Updater-/Datenstruktur-Änderung.
- 2026-06-20 — Architektur-/UX-Richtlinie ADR-27: „Bestehende Einstellungen beibehalten" als Default bei darstellungsverändernden Features. Features, die Farben/Position/Layout/Presets beeinflussen könnten, ändern bei Update/Aktivierung nichts automatisch; Übernahme nur per ausdrücklicher Operator-Aktion (Opt-in), Post-Update-Hinweis mit sicherer Vorauswahl „Bestehende Einstellungen beibehalten (empfohlen)". Verbindlich für künftige UX-/Design-Features (Avada-Farbimport v0.5.1, Design-Presets geplant). Reine Richtlinie, keine Code-/No-Go-Änderung.
- 2026-06-21 — v0.5.9 / ADR-29: Avada/Fusion-Cache nach „Avada-Farben übernehmen" automatisch über Avadas eigene API leeren (`fusion_reset_all_caches()` → `Fusion_Cache::reset_all_caches()`). Root Cause der „Farbe kommt nicht an"-Meldung war ausschließlich der Fusion-Cache (DB/Resolver/Banner-Ausgabe korrekt), der das alte Inline-CSS `--lscc-primary:#e11d48` weiter auslieferte. Admin-Notice bei Erfolg, kein Ctrl+F5 mehr nötig. Keine eigene Cache-Lösung; keine Änderung an Resolver/Consent/Locale/Reopen/Presets/Frontend.
- 2026-06-23 — **v1.0.5 (Brevo-Erkennung + reCAPTCHA-Feinschliff, ADR-38).** Brevo (Sendinblue) als Vendor `brevo` erkannt/benannt, Default-Kategorie marketing, im Privacy-Check sichtbar (Surface + Content Scan). Google reCAPTCHA: Erkennung erweitert (`gstatic.com/recaptcha`, `recaptcha/api.js`, `recaptcha/enterprise.js`), Empfehlungstext geschärft. **Bewusst kein aktives reCAPTCHA-Gating** (hartes Vor-Consent-Blockieren bricht Formulare über CF7/WPForms/Fluent/Gravity/Elementor/Avada) — nur Erkennung/Beratung; aktives „Consent-on-Interaction"-Gating als spätere Roadmap (ADR-38). Reine additive Detektion: kein Modul/Hook/Option, keine Formular-Manipulation, keine neue Consent-Kategorie. `MCB_CONSENT_VERSION` unverändert.
- 2026-06-23 — **v1.0.4 (Meta Social Embeds: Facebook/Instagram, ADR-37).** Facebook-/Instagram-Social-Embeds in die `external_media`-Architektur integriert: neue Vendoren `facebook_embed`/`instagram_embed` (geprüft **vor** `meta_pixel` → strikte Trennung Social↔Pixel; `fbevents.js`/`fbq` bleiben `meta_pixel`/marketing). SDK-Gating-Modul `includes/meta-social-compat.php` (SRC-basiert: `sdk.js`/`embed.js`/`embeds.js` → `external_media`, **nie** `fbevents.js`), Reaktivierung über bestehende `banner.js`-Mechanik. Kontrollierte Shortcodes `[lscc_facebook]`/`[lscc_instagram]`. Privacy Check meldet FB/IG + Feed-Plugins (Smash Balloon/Spotlight/EmbedSocial/Elfsight) **report-only**. Safe-by-Default (ADR-36): `meta_social_block` recommended EIN (Neuinstallation), Bestand unverändert, Restore übernimmt es. **Kein** iframe-Rewrite, **kein** Feed-Plugin-Gating, keine neue Consent-Kategorie. `MCB_CONSENT_VERSION` unverändert.
- 2026-06-23 — **v1.0.3 (Safe by Default + Restore-Button + Maps-Geometrie, ADR-36).** Produktprinzip „Neuinstallation = maximal sicher, Bestand = nie ungefragt". Fresh-Install-Seeding (`on_activate` → `add_option` mit `get_recommended_defaults()`, nur wenn Options-Eintrag fehlt) + Entkopplung von Fresh-Default und Read-Time-Fallback (`get_baseline_fallback()` für fehlende Keys ⇒ Updates aktivieren neue Schutzoptionen nie still). Empfohlener Neuinstall-Default u. a. `avada_code_maps_block=true`. Zentraler Button „Empfohlene Datenschutzeinstellungen wiederherstellen" (oben, opt-in, Nonce, `confirm()`) setzt nur die 5 Datenschutz-/Blockier-Keys; Texte/Farben/URLs/Snippets/Avada-Sync bleiben unverändert. `avada_maps_block`/`yotu_consent_gating` bewusst nicht im Safe-Set. Zusätzlich: Maps-Code-Block-Fix erhält Original-Geometrie (`width`/`height`/`title` → Inline-Style auf `.lscc-media`; ohne Dimensionen 16:9 unverändert; kein banner.js-Eingriff). `MCB_CONSENT_VERSION` unverändert.
- 2026-06-23 — **v1.0.2 (Raw-Maps-iframe im Avada Code Block, ADR-35).** Schliesst die bewiesene Consent-Lücke: ein rohes `<iframe …maps/embed…>` in einem Avada Code Block (`fusion_code`) lud consent-unabhängig (auch nach Widerruf), weil es keine MCB-Marker trägt. Neues opt-in-Modul `includes/avada-code-compat.php` (Default AUS) fängt `fusion_code` via `pre_do_shortcode_tag` ab und ersetzt **nur** den reinen Google-Maps-Embed-Fall durch den bestehenden `render_google_map()`-Platzhalter (`external_media`); alle anderen Inhalte bleiben unangetastet (Passthrough, kein Inhaltsverlust). Reuse der vorhandenen Komponente + Widerruf-Pipeline; Privacy-Check-Content-Scan meldet den Fall base64-aware. **Nicht** angefasst: `banner.js`, Consent/Widerruf, Vendor-Erkennung, Color Sync, `fusion_map`. Keine generische iframe-Blockade, keine DB-Migration, `MCB_CONSENT_VERSION` unverändert. Bekannte Grenze: gemischte Code Blocks (Karte + weiterer Inhalt) bleiben bewusst ungegated → `[lscc_google_map]` nutzen.
- 2026-06-22 — **v1.0.1 (Vendor-Abdeckung).** Additiv auf eingefrorenem 1.0.0 RC2: Google Ads (Conversion Tracking / Remarketing) und Mailchimp werden erkannt, klassifiziert und im Privacy-Check ausgegeben. Google-Ads-Prüfung läuft **vor** GA4 (Ads nutzt ebenfalls `gtag()` → bisher GA4-Fehlklassifikation); reines GA4 (`G-…`) bleibt GA4. Default-Kategorie nicht mehr pauschal `statistics`: GA4/GTM→statistics, Meta Pixel/Google Ads/Mailchimp→marketing, als Auto-Vorschlag **nur** für neu erkannte Vendoren (bestehende Snippets unverändert). Nicht angefasst: Consent Mode v2, Governance, GTM-Granularität, LinkedIn/TikTok/Clarity/HubSpot/Brevo, YouTube/Maps-oEmbed, Banner, Consent-Logik, Avada, Scanner-Architektur. Keine Migration, `MCB_CONSENT_VERSION` unverändert.
- 2026-06-22 — **v1.0.0 (erste offizielle Stable-Freigabe / Release-Kandidat).** Nach abgeschlossener Compliance-/Governance-/Freigabe-Bewertung umgesetzt: Routing-Hinweis im Consent-Code-Manager (Tracking über CCM, YouTube/Maps über MCB-Komponenten/Shortcodes/Avada) und Disclaimer im Privacy-Check („Risiko-Indikator, kein Compliance-Beweis"). Reine Hinweistexte, keine neue Logik/Architektur. Version 0.5.13 → 1.0.0. Bekannte dokumentierte Grenze: rohe iframes/oEmbed/Avada-Vimeo/Background/fusion_code nicht auto-geblockt. Offen als QA: Avada-Auto-Sync-Live-Bestätigung + Pilot-Livegang.
- 2026-06-22 — v0.5.13 / ADR-33: **Auto-Sync-Entscheidung bei Aktivierung/Update erzwungen.** Fix des UX-Bugs aus 0.5.12 (passive Notice erschien nach Update nicht). Echte Trigger: `register_activation_hook` (`on_activate()`) + Versions-Stamp `mcb_seen_version` (Upgrade-Erkennung in `maybe_force_avada_decision()`, `admin_init`). Bei `mcb_avada_decision_pending` + nicht entschieden + Avada: einmaliger Redirect auf die Einstellungsseite + nicht schließbarer Prompt bis zur Entscheidung; danach dauerhaft gespeichert, nie wieder. Keine Falle (Deaktivieren bleibt möglich). Checkbox + „Jetzt synchronisieren" unverändert; Consent/Locale/Scanner/CCM/Updater/Presets/Importlogik unberührt.
- 2026-06-21 — v0.5.12 / ADR-32: **Avada Auto-Sync (opt-in).** Der Betreiber entscheidet per Erstabfrage (Admin-Notice „Avada erkannt … automatisch synchronisieren?") und per Checkbox im Bereich „Avada-Integration", ob das Banner dauerhaft der Avada Primary Color folgt. EIN: Abgleich bei jedem Admin-Load (`admin_init`) inkl. Cache-Reset. AUS: keine automatische Änderung. Harte Regel: Updates überschreiben **nie ungefragt** manuelle Bannerfarben; automatische Übernahme nur bei aktivem Auto-Sync. Eigene Optionen `mcb_avada_autosync`/`mcb_avada_sync_decided`; bestehende Importfunktion + „Jetzt synchronisieren"-Button unverändert. Consent/Locale/Scanner/CCM/Updater/Presets unberührt.
- 2026-06-21 — v0.5.11 / ADR-31: Der sichtbare Reopen-/Settings-Button folgt der importierten **Primary Color in allen Presets**. Root Cause (CSS): im Default-Preset *Classic* füllte `.lscc-reopen` mit `var(--lscc-bg)` und `.lscc-settings-button` mit `var(--lscc-secondary)` — nur Modern/Premium nutzten `var(--lscc-primary)`; der Import schreibt aber `primary_button_color`, daher im Default unsichtbar. Fix: Basisregeln in `banner.css` auf `var(--lscc-primary)`/`var(--lscc-primary-text)`. Temporäre Runtime-Proofs 0.5.10-debug2/-debug3 wieder entfernt. Rein Darstellung; Consent/Locale/Scanner/CCM/Updater/Avada-Import/Cache-Reset unberührt.
- 2026-06-21 — v0.5.10 / ADR-30: Avada-Farbimport bindet **ausschließlich** an die aktuell aktive **Primary Color**. Bewiesener Root Cause: die bisherige Brand-Key-Prioritätskette (`primary_color → accent_color → link_color → button_gradient_top_color`) mit positionsbasiertem Palette-Matching übernahm nach Wechsel der Primary Color auf direktes `#2ecc4e` weiterhin den alten `var(--awb-color5)`-Wert `#1e4884` (5. Palette-Eintrag) aus den Sekundärschlüsseln. Neu: `resolve_primary(read_raw('primary_color'))`, kein accent/link/gradient, kein Palette-/`awb-colorN`-Matching; Client-Fallback nur für `primary_color`. Temporäre `0.5.9-debug`-Notice entfernt. Keine Änderung an Consent/Locale/Reopen/Presets/Frontend/Cache-Reset/Speicherung/Scanner/CCM/Updater.

## Verbindliche Learnings & Arbeitsregeln (Stand v0.5.13, 2026-06-22)

Konsolidierter, verbindlicher Stand aus der Avada-/Auto-Sync-/Debugging-Phase. Details in den jeweiligen ADRs (DECISIONS.md).

### A) Avada Primary Color ist die einzige Farbquelle (ADR-30)
- Das Banner folgt **ausschließlich** der aktuell aktiven Avada `primary_color`.
- **Keine** Ableitung über `accent_color`, `link_color`, `button_gradient_top_color`.
- **Kein** Palette-Matching, **keine** positionsbasierte `color1/color5/colorN`-Logik, **keine** Heuristik, **keine** Fallback-Farbkette.

### B) Sichtbarer Button nutzt die Primary Color (ADR-31)
- Der sichtbare Reopen-/Cookie-Einstellungen-Button verwendet in **allen** Presets die importierte Primary Color.
- Classic fällt **nicht** mehr auf `background_color`/`secondary_button_color` zurück; Modern/Premium bleiben optisch erweitert, aber primary-color-basiert.
- Wenn Backend-Wert korrekt, Frontend aber falsch: Prüfreihenfolge **Quelle → Speicherung → get_options → Render → sichtbares Element → Cache**.

### C) Avada Auto-Sync (ADR-32)
- Opt-in, Default **AUS**. Bestehende Kundenfarben werden **nie ungefragt** überschrieben.
- Bei aktivem Auto-Sync darf automatisch aktualisiert werden; im manuellen Modus niemals.
- Entscheidung bleibt gespeichert, ist später jederzeit änderbar; „Jetzt synchronisieren" bleibt dauerhaft verfügbar.

### D) Aktivierungs-/Update-UX (ADR-33)
- Zwingende Betreiberentscheidungen dürfen **nicht** nur über passive `admin_notices` laufen.
- Aktivierung **und** Update müssen die Entscheidung zuverlässig auslösen; Update-Flow, ZIP-Upload und direkter Datei-Replace sind zu berücksichtigen.
- Passive Hinweise gelten **nicht** als ausreichende UX.

### E) Avada/Fusion Cache (ADR-29)
- Nach erfolgreichem Import/Auto-Sync wird der Avada/Fusion-Cache über die **vorhandene** Avada/Fusion-API defensiv geleert. **Keine** eigene Cache-Lösung.
- Kein Cache-Reset verfügbar → **kein Fatal**, nur Degradation/Hinweis.
- DB-Wert, Render-Wert und sichtbarer Frontend-Wert sind **getrennte Ebenen**.

### F) Marcel ist Entscheider/Tester, NICHT Debug-Operator (verbindlich)
Marcel macht **keine**: FTP-/SFTP-Analysen, debug.log-Auswertungen, wp-config-Änderungen, WP-CLI-Aufrufe, DB-Abfragen, PHP-Codeinspektionen, Server-Diagnosen, manuelle Code-Suchen, manuelle Prompt-Zusammenstellungen.
Runtime-Proofs werden bevorzugt über **Admin-Notice / Debug-Panel / UI-Ausgabe / sichtbaren Diagnoseblock** bereitgestellt. Debugging wird **nicht** auf Marcel ausgelagert.

### G) Antwort- und Lieferformat (verbindlich)
- Standardformat für Antworten: **Problem · Ursache · Fix · Nächster Schritt**. Keine langen Analyseblöcke.
- Sagt Marcel „Prompt": **vollständigen Copy/Paste-Prompt** liefern — keine Fragmente, keine zusammenzusetzenden Zusatzteile.
- Berichte zur ChatGPT-Weitergabe enden in der Pflicht-Kopiermarkierung (siehe Lieferregeln unten).

### H) Root-Cause-First (verbindlich)
Vor jedem Fix: **1) Runtime-Pfad beweisen, 2) verantwortliche Stelle beweisen, 3) erst dann patchen.**
Verboten: Blindfixes, Vermutungen, mehrere Resolver-Versionen „auf Verdacht", zusätzliche Fallback-Ketten ohne fachliche Freigabe, Debug-Zyklen ohne klare Entscheidungsfrage.

### I) Nächste Phase — Compliance DE/CH-Livegang (Arbeitspaket)
Vor Livegang müssen Tracking-/Consent-Integrationen vollständig erkannt, klassifiziert und consent-gesteuert sein.
- **Priorität 1:** Google Analytics 4, Google Tag Manager, Meta Pixel, YouTube Embeds, Google Maps, Google Ads / Conversion / Remarketing.
- **Priorität 2:** Hotjar, Microsoft Clarity, LinkedIn Insight, TikTok Pixel.
- **Priorität 3:** HubSpot, Brevo/Sendinblue, Mailchimp, Calendly, Vimeo, Typeform, Trustpilot, weitere Drittanbieter.
- Technischer Fokus: Scanner / Blockierung / Consent Mode / echte Live-Site-Validierung.

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

# PFLICHT: KOPIERMARKIERUNG FÜR BERICHTE (HARTE PFLICHTREGEL)

VERBINDLICH (ab 2026-06-12, verschärft 2026-06-16):

Die Kopiermarkierung ist **kein UX-Wunsch, sondern Bestandteil des Lieferformats.**

Jeder Bericht MUSS direkt vor dem kopierbaren Bereich mit dem Start-Block beginnen und mit dem End-Block abschliessen. Exakter, unveränderlicher Wortlaut:

```
================================================================================
🚨🚨🚨 KOPIEREN AB HIER FÜR CHATGPT 🚨🚨🚨
================================================================================

... vollständiger, in sich verständlicher Bericht (inkl. etwaigem AKTION-USER-Block) ...

================================================================================
🚨🚨🚨 KOPIEREN BIS HIER FÜR CHATGPT 🚨🚨🚨
================================================================================
```

Fehlt einer der beiden Blöcke, gilt:

* Bericht = unvollständig
* Bericht = nicht abnahmefähig
* Bericht = nicht freigegeben
* Bericht = erneut erstellen

Geltungsbereich (zwingend in allen): Analysen, Abschlussberichten, Patches, Releases, Inventuren, Roadmaps, ADR-Berichten, Debug-Reports, Validierungsberichten.

Regeln:
* Die Markierung beginnt direkt vor dem kopierbaren Bereich — der User darf nie suchen müssen, wo der Kopierbereich startet.
* Keine Einleitung innerhalb des Kopierbereichs vor dem Start-Block; nach dem End-Block folgt KEIN weiterer Berichtsinhalt.
* Prozess-/Meta-Hinweise dürfen ausserhalb (vor dem Start- bzw. nach dem End-Block) stehen.

Diese Regel gilt zusätzlich zur Regel „PFLICHT: AKTION USER / PROMPT-BLÖCKE".

---

# PFLICHT: DEBUGGING / RUNTIME-PROOFS — KEINE ARBEITSVERLAGERUNG AUF DEN USER

VERBINDLICH (ab 2026-06-21). Priorität HOCH. Gilt für ChatGPT, Claude, Codex, Claude Code und alle zukünftigen Entwickler-Agenten.

**Grundsatz:** Marcel ist **Entscheider und Tester, nicht Debug-Operator.** Der technische Agent trägt die Verantwortung für Ursachenanalyse, Runtime-Proofs, Debugging, Eingrenzung, Dokumentation und Beweisführung. Der User übernimmt **niemals** technische Ermittlungsarbeit.

**Marcel wird NIEMALS geschickt auf:** FTP, SFTP, Server-Dateisuche, `debug.log`-Suche, `wp-config`-Suche/-Änderung, manuelle Log-Auswertung, Quellcode-Suche, Datenbank-Suche, WP-CLI, mu-plugins.

**Wenn Runtime-Werte für eine Fehleranalyse nötig sind — in dieser Reihenfolge:**
1. Werte direkt im WordPress-Admin anzeigen.
2. Werte direkt im sichtbaren UI anzeigen.
3. Werte über temporäre Admin-Notices ausgeben.
4. Werte über temporäre Diagnose-Dialoge ausgeben.
5. Erst wenn technisch absolut unmöglich, auf Server-Logs ausweichen.

Temporärer Diagnose-Code wird nach dem Test wieder vollständig entfernt.

**Antwortformat bei Problemen (knapp):**
1. Problem
2. Ursache
3. Fix
4. Nächster Schritt

Keine langen Analysen. Keine mehrseitigen Berichte. Keine Server-Schnitzeljagden. Keine Arbeitsverlagerung auf den User.

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
