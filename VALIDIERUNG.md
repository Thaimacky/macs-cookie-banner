# VALIDIERUNG — Realdaten vor weiteren Features

**Gate-Regel (ADR-26):** Vor der Entwicklung weiterer Dienst-Features wird LSCC auf **mindestens 5 echten Websites** produktiv analysiert. Der aktuelle Stand (v0.3.3) reicht aus, um diese Daten zu sammeln. Erst die Aggregat-Matrix aus Phase B bestimmt die nächste Entwicklungsphase — nicht Vermutung.

Status-Vokabular (identisch zum Scanner, ADR-24): **Nicht gefunden / Verwaltet / Teilweise verwaltet / Ungegatet / Nicht prüfbar.**

---

## PHASE A — Live-Test (höchste Priorität)

Pro Website durchführen und unten eintragen:

1. LSCC installieren (per GitHub-Auto-Update-ZIP oder manuell), aktivieren.
2. **Privacy Check** laufen lassen (Startseite + eine eigene Test-URL gleichen Hosts).
3. **Drittanbieter-Oberfläche** dokumentieren (Gating-Status pro Dienst).
4. **Netzwerk-Monitor VOR Consent** prüfen: DevTools → Network → Seite frisch laden, **ohne** Consent. Notieren, welche Drittanbieter-Requests trotzdem feuern (= Leak).
5. Banner-Funktion, Widerruf, Auto-Update-Anzeige im WP-Backend gegenprüfen.

### Leitfragen je Site
- Funktioniert das Banner (Anzeige, Akzeptieren, Ablehnen, Widerruf)?
- Funktioniert das Auto-Update (Update-Hinweis sichtbar, Installation sauber)?
- Gibt es echte Leaks (Requests vor Consent)?
- Gibt es Cache-/Optimierungs-Konflikte (Delay/Combine/Minify schreibt `type="text/plain"`-Scripts um)?
- Welche Dienste kommen real überhaupt vor?

### Site-Erhebung (5× ausfüllen)

#### Site 1 — `__________________`
- Stack (Theme/Builder/Cache-Plugin): 
- Banner OK (Anzeige/Akzept/Ablehn/Widerruf): 
- Auto-Update OK: 
- Leaks vor Consent (welche Hosts feuern?): 
- Cache-/Optimierungs-Konflikt: 
- Gefundene Dienste + Gating-Status: 
- Sonstige Auffälligkeiten: 

#### Site 2 — `__________________`
- Stack: 
- Banner OK: 
- Auto-Update OK: 
- Leaks vor Consent: 
- Cache-/Optimierungs-Konflikt: 
- Gefundene Dienste + Gating-Status: 
- Sonstige Auffälligkeiten: 

#### Site 3 — `__________________`
- Stack: 
- Banner OK: 
- Auto-Update OK: 
- Leaks vor Consent: 
- Cache-/Optimierungs-Konflikt: 
- Gefundene Dienste + Gating-Status: 
- Sonstige Auffälligkeiten: 

#### Site 4 — `__________________`
- Stack: 
- Banner OK: 
- Auto-Update OK: 
- Leaks vor Consent: 
- Cache-/Optimierungs-Konflikt: 
- Gefundene Dienste + Gating-Status: 
- Sonstige Auffälligkeiten: 

#### Site 5 — `__________________`
- Stack: 
- Banner OK: 
- Auto-Update OK: 
- Leaks vor Consent: 
- Cache-/Optimierungs-Konflikt: 
- Gefundene Dienste + Gating-Status: 
- Sonstige Auffälligkeiten: 

---

## PHASE B — Inventur-Auswertung (Aggregat-Matrix)

Pro Dienst über alle Sites zusammenführen. **Prävalenz** = auf wie vielen der 5 Sites gefunden. **Risiko** = datenschutzrechtliche Schwere bei Ungegatet (hoch/mittel/niedrig). **Priorität** ergibt sich aus Prävalenz × Risiko × Abdeckungslücke.

Spalte „Aktuelle LSCC-Abdeckung" ist mit dem heutigen Ist-Stand (v0.3.3) vorbefüllt — beim Ausfüllen gegen die Realität bestätigen/korrigieren.

| Dienst         | Prävalenz (x/5) | Risiko | Aktuelle LSCC-Abdeckung                              | Priorität |
|----------------|-----------------|--------|-----------------------------------------------------|-----------|
| GA4            |                 |        | Gegated (Consent-Code-Manager)                      |           |
| GTM            |                 |        | Gegated (Consent-Code-Manager)                      |           |
| Meta Pixel     |                 |        | Gegated (Consent-Code-Manager)                      |           |
| Hotjar         |                 |        | Gegated (Consent-Code-Manager)                      |           |
| reCAPTCHA      |                 |        | Nur Scanner-Erkennung — **kein Gating** (Lücke)     |           |
| Google Fonts   |                 |        | Nur Reporting/Empfehlung „lokal hosten" (nicht gate-bar) |       |
| YouTube        |                 |        | Gegated (Platzhalter + `external_media`-Consent)    |           |
| Vimeo          |                 |        | Nur Scanner-Erkennung — **kein Gating** (Lücke)     |           |
| Google Maps    |                 |        | Gegated (Avada-Embed-Umlenkung + `[lscc_google_map]`) |         |
| Calendly       |                 |        | Nur Scanner-Erkennung („Nicht prüfbar") — Lücke     |           |
| Sonstige: ____ |                 |        |                                                     |           |

---

## PHASE C — Wahrscheinlichste nächste Entwicklung

Reihenfolge **wird durch die Matrix oben bestätigt oder verworfen** — folgende Kandidaten sind die Hypothese vor den Realdaten:

1. **Vimeo / oEmbed** — Embed-Gating analog YouTube (bestehende Platzhalter-/`createMediaIframe`-Mechanik wiederverwenden).
2. **reCAPTCHA** — Gating-Strategie klären (Formular-Abhängigkeit; höheres Konfliktrisiko).
3. **Calendly** — klick-/JS-geladenes Widget; Gating-Ansatz prüfen.
4. **Google Fonts Report** — voraussichtlich nur Reporting/Checkliste, kein echtes Gating (nicht consent-gate-bar; Empfehlung bleibt Local Hosting).

Infrastruktur (Consent-Code-Manager, GitHub-Auto-Updates) gilt als erledigt und ist nicht Teil dieser Priorisierung.
