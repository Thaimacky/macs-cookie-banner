# LSCC Inventur-/Validierungs-Sprint — Erfassungsvorlage

Stand: 2026-06-12 · Geplant: 5 Websites · Werkzeuge: Privacy Check (Drittanbieter-Oberfläche, Muster-Schnellprüfung, Content Scan), Avada Inventar-Scan, Consent-Code-Manager, Browser-Netzwerk-Monitor.

> **Hinweis (2026-09-16):** Diese Datei ist eine **leere Erfassungsvorlage** und wird
> in Git versioniert, damit sie auf jedem Rechner verfuegbar ist (siehe `MULTI_PC_SETUP.md`).
> **Ausgefuellte Inventuren mit echten Site-Namen, URLs oder Kundendaten gehoeren NICHT
> in dieses Repository.** Diese Vorlage kopieren und die Kopie ausserhalb von Git ablegen.

---

> Ausfüllen: pro Site ein Erfassungsblatt (Abschnitt 1, fünf vorbereitet). Danach Aggregat-Matrix (Abschnitt 2) und Priorisierung (Abschnitt 4). Legende (3) und Statuswerte (5) sind die verbindlichen Definitionen.

---

## 0. Erfasste Dienste (fix)
GA4 · GTM · Meta Pixel · Hotjar · reCAPTCHA · Google Fonts · YouTube · Vimeo · Google Maps · Calendly · Sonstige Drittanbieter

---

## 1. Pro-Site-Erfassungsblätter

<!-- =================== Vorlagenblock je Site (5×) =================== -->

### SITE 1

**Kontext**

| Feld | Wert |
|---|---|
| Site-Name | |
| Primär-URL | |
| Geprüfte URLs (3–4: Start / Kontakt+Maps / Video / Buchung) | 1) ___  2) ___  3) ___  4) ___ |
| Avada-Version / WP-Version | |
| Cache-/Optimize-Plugins (WP Rocket / Autoptimize / LiteSpeed / Perfmatters / …) | |
| Avada Privacy aktiv? (gesamt / Maps / Video) | gesamt: J/N · Maps: J/N · Video: J/N |
| LSCC-Version | |
| LSCC-Module AN (avada_youtube_block / yotu_consent_gating / avada_maps_block) | |
| Consent-Code-Manager: Einträge (Vendor/Kategorie) | |
| Datum / Prüfer | |

**Werkzeug-Lauf (abhaken)**

- [ ] Surface-Scan je URL ausgeführt
- [ ] Netzwerk-Monitor **vor Consent** je URL (Inkognito, Cache leer)
- [ ] Content-Scan ausgeführt
- [ ] Avada Inventar-Scan ausgeführt
- [ ] DevTools: Consent-State / Script-`type` / Avada-Privacy-Platzhalter geprüft

**Dienst-Erfassung**

| Dienst | Vorhanden (J/N) | Variante / Quelle | Leak vor Consent (Netz: J/N/?) | LSCC-Status (Surface) | Kategorie | Aktion |
|---|---|---|---|---|---|---|
| GA4 | | gtag / über GTM | | | statistics | |
| GTM | | Container | | | (marketing) | |
| Meta Pixel | | fbevents | | | marketing | |
| Hotjar | | hj/_hjSettings | | | statistics | |
| reCAPTCHA | | v2/v3 | | | – | |
| Google Fonts | | link / @import / @font-face / preconnect | | | – | |
| YouTube | | lscc / fusion_youtube / yotu / roh-iframe / oEmbed | | | external_media | |
| Vimeo | | lscc / fusion_vimeo / roh-iframe / oEmbed | | | external_media | |
| Google Maps | | lscc / fusion_map / Embed-iframe / JS-API | | | external_media | |
| Calendly | | iframe / Widget-JS | | | external_media | |
| Sonstige | | (Host nennen) | | | | |

**Sonstige Drittanbieter-Hosts (Netzwerk-Monitor, vor Consent)**
_(z. B. X/Twitter, Instagram, SoundCloud, Spotify, Typeform, Chat-Widget, Adobe Fonts, CDNs)_
- ___

**Avada Inventar-Scan (Kennzahlen)**

| fusion_youtube | fusion_vimeo | fusion_map | Background-Video (3P) | rohe iframes (3P) | fusion_code (mit Embed) | oEmbed |
|---|---|---|---|---|---|---|
| | | | | | | |

**Site-Zusammenfassung**

| Frage | Antwort |
|---|---|
| Ist LSCC ausreichend? (alle vorhandenen Dienste „Verwaltet" oder abwesend) | J/N |
| In den Consent-Code-Manager nötig (welche Dienste)? | |
| Ungegatete Dienste (Liste) | |
| Avada-Privacy-Konflikt? (welche Schicht doppelt) | J/N: ___ |
| Cache/Optimize stört `text/plain`-Gating? | J/N/? |

---

### SITE 2

_(Identischer Block wie SITE 1 — bitte ausfüllen.)_

**Kontext**

| Feld | Wert |
|---|---|
| Site-Name | |
| Primär-URL | |
| Geprüfte URLs | 1) ___ 2) ___ 3) ___ 4) ___ |
| Avada / WP | |
| Cache/Optimize | |
| Avada Privacy (gesamt/Maps/Video) | |
| LSCC-Version / Module AN | |
| Consent-Code-Manager-Einträge | |
| Datum / Prüfer | |

| Dienst | Vorhanden | Variante/Quelle | Leak vor Consent | LSCC-Status | Kategorie | Aktion |
|---|---|---|---|---|---|---|
| GA4 | | | | | statistics | |
| GTM | | | | | (marketing) | |
| Meta Pixel | | | | | marketing | |
| Hotjar | | | | | statistics | |
| reCAPTCHA | | | | | – | |
| Google Fonts | | | | | – | |
| YouTube | | | | | external_media | |
| Vimeo | | | | | external_media | |
| Google Maps | | | | | external_media | |
| Calendly | | | | | external_media | |
| Sonstige | | | | | | |

Sonstige Hosts: ___
Inventar-Scan (fy/fv/fm/bg/iframe/code/oembed): ___
Zusammenfassung (ausreichend? / CCM-Dienste / ungegatet / Avada-Konflikt / Cache): ___

---

### SITE 3

_(Identischer Block — bitte ausfüllen.)_

| Feld | Wert |
|---|---|
| Site-Name / Primär-URL | |
| Geprüfte URLs | |
| Avada / WP / Cache / Avada-Privacy | |
| LSCC-Version / Module / CCM-Einträge | |

| Dienst | Vorhanden | Variante | Leak vor Consent | LSCC-Status | Kategorie | Aktion |
|---|---|---|---|---|---|---|
| GA4 | | | | | statistics | |
| GTM | | | | | (marketing) | |
| Meta Pixel | | | | | marketing | |
| Hotjar | | | | | statistics | |
| reCAPTCHA | | | | | – | |
| Google Fonts | | | | | – | |
| YouTube | | | | | external_media | |
| Vimeo | | | | | external_media | |
| Google Maps | | | | | external_media | |
| Calendly | | | | | external_media | |
| Sonstige | | | | | | |

Sonstige Hosts / Inventar-Scan / Zusammenfassung: ___

---

### SITE 4

_(Identischer Block — bitte ausfüllen.)_

| Feld | Wert |
|---|---|
| Site-Name / Primär-URL | |
| Geprüfte URLs | |
| Avada / WP / Cache / Avada-Privacy | |
| LSCC-Version / Module / CCM-Einträge | |

| Dienst | Vorhanden | Variante | Leak vor Consent | LSCC-Status | Kategorie | Aktion |
|---|---|---|---|---|---|---|
| GA4 | | | | | statistics | |
| GTM | | | | | (marketing) | |
| Meta Pixel | | | | | marketing | |
| Hotjar | | | | | statistics | |
| reCAPTCHA | | | | | – | |
| Google Fonts | | | | | – | |
| YouTube | | | | | external_media | |
| Vimeo | | | | | external_media | |
| Google Maps | | | | | external_media | |
| Calendly | | | | | external_media | |
| Sonstige | | | | | | |

Sonstige Hosts / Inventar-Scan / Zusammenfassung: ___

---

### SITE 5

_(Identischer Block — bitte ausfüllen.)_

| Feld | Wert |
|---|---|
| Site-Name / Primär-URL | |
| Geprüfte URLs | |
| Avada / WP / Cache / Avada-Privacy | |
| LSCC-Version / Module / CCM-Einträge | |

| Dienst | Vorhanden | Variante | Leak vor Consent | LSCC-Status | Kategorie | Aktion |
|---|---|---|---|---|---|---|
| GA4 | | | | | statistics | |
| GTM | | | | | (marketing) | |
| Meta Pixel | | | | | marketing | |
| Hotjar | | | | | statistics | |
| reCAPTCHA | | | | | – | |
| Google Fonts | | | | | – | |
| YouTube | | | | | external_media | |
| Vimeo | | | | | external_media | |
| Google Maps | | | | | external_media | |
| Calendly | | | | | external_media | |
| Sonstige | | | | | | |

Sonstige Hosts / Inventar-Scan / Zusammenfassung: ___

---

## 2. Aggregat-Matrix (Kernartefakt)

Zellinhalt-Konvention je Site: `Status | Leak(J/N) | Aktion-Kürzel`
Status-Kürzel: NG=Nicht gefunden · V=Verwaltet · TV=Teilweise verwaltet · U=Ungegatet · NP=Nicht prüfbar · (Fonts: EX=extern / KE=keine externen)
Aktion-Kürzel: – / CCM / SC (auf [lscc_*] umstellen) / LF (Fonts lokal hosten) / KF (Avada-Privacy-Konflikt) / VAL (nur validieren)

| Dienst | Site 1 | Site 2 | Site 3 | Site 4 | Site 5 | Prävalenz (/5) | # Ungegatet | # Verwaltet | LSCC-Abdeckung heute | Typische Aktion | Roadmap-Impact (↑/↓/=) |
|---|---|---|---|---|---|---|---|---|---|---|---|
| GA4 | | | | | | | | | Teils (CCM manuell) | CCM | |
| GTM | | | | | | | | | Teils (CCM) | CCM (+Tags) | |
| Meta Pixel | | | | | | | | | Teils (CCM) | CCM | |
| Hotjar | | | | | | | | | Teils (CCM) | CCM | |
| reCAPTCHA | | | | | | | | | Nein | offen | |
| Google Fonts | | | | | | | | | Nein (Local Hosting) | LF / Avada-Option | |
| YouTube | | | | | | | | | Ja (lscc/fusion/yotu) | VAL | |
| Vimeo | | | | | | | | | Teils ([lscc_vimeo]) | SC / Modul offen | |
| Google Maps | | | | | | | | | Ja (v0.3.2) | VAL | |
| Calendly | | | | | | | | | Nein | offen | |
| Sonstige | | | | | | | | | – | einzeln | |

**Gesamt-Kennzahlen**

| Kennzahl | Wert |
|---|---|
| Sites mit ≥1 ungegatetem Dienst | /5 |
| Sites, auf denen LSCC heute ausreicht | /5 |
| Sites mit Avada-Privacy-Konflikt-Risiko | /5 |
| Sites mit Cache/Optimize-Störung des Gatings | /5 |
| Häufigster ungegateter Dienst | |
| Verbreitung externer Google Fonts | /5 |

---

## 3. Feldlegende

- **Vorhanden:** auf mindestens einer geprüften URL nachgewiesen (Scanner ODER Netzwerk-Monitor).
- **Variante / Quelle:** wie eingebunden (siehe Beispiele je Zeile). Bestimmt die mögliche Aktion.
- **Leak vor Consent (Netz):** Ground Truth aus dem Browser-Netzwerk-Monitor **vor** jeder Zustimmung — lädt der Dritt-Host? `J` = Datenabfluss vor Consent, `N` = sauber, `?` = nicht eindeutig. **Überstimmt** im Zweifel den serverseitigen Scanner.
- **LSCC-Status (Surface):** Ausgabe der „Drittanbieter-Oberfläche" (Definitionen in Abschnitt 5).
- **Kategorie:** Consent-Kategorie für die Lösung (statistics/marketing/external_media). GTM in Klammern, da Container meist Marketing-lastig, aber tag-abhängig.
- **Aktion:** nächster Schritt — `–` (nichts) / `CCM` (in den Consent-Code-Manager) / `SC` (auf `[lscc_*]`-Shortcode umstellen) / `LF` (Fonts lokal hosten) / `KF` (Avada-Privacy-Konflikt auflösen, nur eine Schicht) / `VAL` (nur Funktionsvalidierung).
- **Prävalenz:** in wie vielen der 5 Sites vorhanden.
- **LSCC-Abdeckung heute:** deckt das ausgelieferte Plugin den Dienst grundsätzlich ab (Ja / Teils / Nein).
- **Roadmap-Impact:** steigt (↑), sinkt (↓) oder unverändert (=) die Priorität des zugehörigen Roadmap-Punkts angesichts der Realdaten.

---

## 4. Priorisierungsschema

**Score = Prävalenz × Rechtsrisiko × (1 − Abdeckung) ÷ Aufwand** — höher = wichtiger.

- **Prävalenz:** 0–5 (aus Aggregat-Matrix).
- **Rechtsrisiko:** 3 = Tracking/US-Transfer (GA/GTM/Pixel/Hotjar) · 2 = Embeds/Fonts (YouTube/Vimeo/Maps/Calendly/Fonts) · 1 = gering.
- **Abdeckung:** 1 = voll · 0.5 = teilweise (manuell/Shortcode) · 0 = keine.
- **Aufwand:** 1 = klein (Rollout/Config) · 2 = mittel (neues Modul) · 3 = gross.

Voreingestellte Konstanten (nur **Prävalenz** nach Inventur eintragen, dann Score berechnen):

| Roadmap-Punkt | Rechtsrisiko | Abdeckung | Aufwand | Prävalenz (eintragen) | Score |
|---|---|---|---|---|---|
| CCM-Rollout GA4/GTM/Pixel/Hotjar | 3 | 0.5 | 1 | | |
| Validierung Ausgeliefertes (Maps/YOTU/CCM/Scanner) auf realem Stack | 3 | 0.5 | 1 | | |
| Vimeo (fusion_vimeo/oEmbed-Interception) | 2 | 0.5 | 2 | | |
| Calendly-Gating | 2 | 0 | 2 | | |
| reCAPTCHA | 2 | 0 | 2 | | |
| Google Fonts (Report + Checkliste, 3B) | 2 | 0 | 1 | | |
| Cache/Optimize-Kompatibilität (falls Störung) | 3 | 0 | 2 | | |

Reihung nach Score → das ist die datenbasierte Roadmap.

---

## 5. Definition der Statuswerte (LSCC-Status)

- **Nicht gefunden (NG):** kein Treffer auf den geprüften URLs (Scanner + Netzwerk-Monitor).
- **Verwaltet (V):** auf der Seite als LSCC-geblocktes Script (`type="text/plain"` + `data-cookie-category`) **oder** LSCC-Platzhalter (`data-lscc-*`) erkannt → vor Consent kein Drittanbieter-Request.
- **Teilweise verwaltet (TV):** gemischt — mindestens eine Instanz gegated, mindestens eine ungegatet. **Handlungsbedarf.**
- **Ungegatet (U):** vorhanden, lädt vor Consent (Netzwerk-Leak), nicht über LSCC. **Handlungsbedarf.**
- **Nicht prüfbar (NP):** serverseitig nicht sicher bestimmbar — z. B. von GTM gefeuerte Tags, klick-/JS-geladene Widgets (Calendly), Inhalte hinter Interaktion. Netzwerk-Monitor heranziehen.
- **Fonts-Sonderwerte:** **EX** = externe Google Fonts erkannt (Empfehlung lokal hosten; Consent ersetzt kein Local Hosting) · **KE** = keine externen Google Fonts erkannt (vermutlich bereits lokalisiert).

> Entscheidungsregel „Ist LSCC ausreichend?" je Site: **Ja**, wenn jede vorhandene Zeile `V` oder `NG` ist **und** kein Netzwerk-Leak `J`. Sonst **Nein** (→ Aktion gemäss Spalte).
