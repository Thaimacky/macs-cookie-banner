# Release Checklist

## Version 0.1.0

- [ ] Plugin in einer echten WordPress-Testinstallation aktivieren.
- [ ] PHP-Fehlerlog nach Aktivierung pruefen.
- [ ] Banner erscheint ohne gespeicherten Consent.
- [ ] Standardzustand ist nur notwendige Cookies.
- [ ] `type="text/plain"` mit `data-cookie-category="statistics"` bleibt vor Zustimmung blockiert.
- [ ] "Alle akzeptieren" aktiviert Statistik, Marketing und externe Medien.
- [ ] "Nur notwendige" blockiert Statistik, Marketing und externe Medien.
- [ ] Consent bleibt nach Reload erhalten.
- [ ] Widerruf ueber `[simple_cookie_settings]` funktioniert ohne Seitenreload.
- [ ] Fester Widerruf-Button oeffnet Einstellungen erneut.
- [ ] Buttons sind per Tastatur erreichbar und bedienbar.
- [ ] Fokuszustand ist sichtbar.
- [ ] ESC schliesst die Einstellungen nicht automatisch.
- [ ] Chrome testen.
- [ ] Firefox testen.
- [ ] Mobile Safari testen.
- [ ] Mobile Layout pruefen: keine abgeschnittenen Texte, kein horizontaler Overflow.
- [ ] HTTPS testen: Consent-Cookie enthaelt `Secure`.
- [ ] HTTP testen: Consent-Cookie wird ohne `Secure` gesetzt.
- [ ] Consent-Cookie enthaelt `SameSite=Lax`.
- [ ] `LSCC_DEBUG` false: keine Console-Ausgaben.
- [ ] `LSCC_DEBUG` true: minimale Console-Ausgaben sichtbar.
- [ ] WPML-String-Registrierung in Testumgebung pruefen.
- [ ] Polylang-String-Registrierung in Testumgebung pruefen.
- [ ] Sprachdateien bei Bedarf aus `.po` nach `.mo` kompilieren.
- [ ] Privacy Check im Admin oeffnen.
- [ ] Privacy Check mit Google Fonts auf einer Testseite pruefen.
- [ ] Privacy Check mit YouTube/Vimeo auf einer Testseite pruefen.
- [ ] Privacy Check mit Google Analytics oder Google Tag Manager auf einer Testseite pruefen.
- [ ] Privacy Check bestaetigt nur Hinweise und veraendert keine Inhalte.
- [ ] `[lscc_youtube id="..."]` zeigt vor Consent nur den Platzhalter.
- [ ] YouTube-Video laedt erst nach Zustimmung zu externen Medien.
- [ ] `[lscc_vimeo id="..."]` zeigt vor Consent nur den Platzhalter.
- [ ] Vimeo-Video laedt erst nach Zustimmung zu externen Medien.
- [ ] `[lscc_google_map url="..."]` zeigt vor Consent nur den Platzhalter.
- [ ] Google Maps laedt erst nach Zustimmung zu externen Medien.
- [ ] Consent fuer externe Medien bleibt nach Reload erhalten.
- [ ] Responsive Verhalten der Service-Komponenten pruefen.

## Nicht Teil von 0.1.0

- Auto-Scanner
- GeoIP
- Vendor-Listen
- IAB TCF
- Google-Cloud-Abhaengigkeiten
- Statistik-Dashboard
- Auto-Block-Engine
