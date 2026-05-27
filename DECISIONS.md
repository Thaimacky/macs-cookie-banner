# Architekturentscheidungen

Dieses Dokument haelt zentrale Entscheidungen fest, die die Form des Plugins erklaeren. Jede Entscheidung steht unter dem Vorbehalt, dass sich Anforderungen aendern koennen; eine Aenderung waere aber eine bewusste neue Entscheidung und kein "Bugfix".

**Vorrangs-Quelle:** Bei Konflikten zwischen Entscheidungen in diesem Dokument und der uebergeordneten AI-Handover-Datei `CLAUDE_CONTINUITY_MASTER.md` (im selben Plugin-Ordner) gewinnt die Master-Datei. Sie definiert Projektphilosophie, absolute No-Gos, Scope-Grenzen und Sicherheitsregeln und darf nicht umbenannt, ueberschrieben oder gekuerzt werden. Erweiterungen erfolgen ausschliesslich additiv mit klarer Versionshistorie.

## ADR-1: Kein Auto-Scanner

**Entscheidung:** Light Swiss Cookie Consent enthaelt keinen automatischen Cookie-/Script-Scanner und keine automatische Klassifikation gefundener Dienste.

**Kontext:** Vergleichbare Consent-Suiten (z. B. Real Cookie Banner) liefern Scanner mit, die Seiten oder Datenbanken nach Cookies absuchen. Diese Scanner sind aufwendig, fehleranfaellig, und sie erfordern haeufig Vendor-Listen oder Cloud-Anbindungen.

**Gruende:**

- Die Zielgruppe (kleine bis mittlere Sites) hat einen ueberschaubaren Stack, der manuell besser beherrschbar ist.
- Scanner-Ergebnisse erzeugen Vertrauensilllusion. Eine falsche Klassifikation ist schlimmer als kein Scan.
- Crawler erzeugen Last und Wartungsaufwand.
- Kein Bedarf an Cloud-Diensten oder externen Vendor-Datenbanken.

**Folgen:** Der Betreiber muss seine Dienste selbst kennen oder den passiven Privacy Check und die Doku des verwendeten Tracking-Tools heranziehen. Akzeptierter Trade-off.

## ADR-2: Shortcodes statt iframe-Hijacking

**Entscheidung:** Externe Medien werden ueber dedizierte Shortcodes (`[lscc_youtube]`, `[lscc_vimeo]`, `[lscc_google_map]`) eingebunden. Das Plugin erkennt oder ersetzt keine bestehenden `<iframe>`-Tags automatisch.

**Kontext:** Viele Consent-Plugins scannen den DOM nach iframes von YouTube, Vimeo, Google Maps und ersetzen sie zur Laufzeit durch Platzhalter. Dieses Verhalten ist invasiv und schwer zu kontrollieren.

**Gruende:**

- Kein versehentliches Brechen bestehender Inhalte.
- Keine Race-Conditions zwischen Theme-/Plugin-Ausgaben und Consent-Layer.
- Keine MutationObserver, die staendig den DOM beobachten muessen.
- Vorhersehbares Verhalten: Wenn ein Editor einen Shortcode setzt, weiss er, was passiert.

**Folgen:** Bestehende iframes ausserhalb der Shortcodes bleiben unblockiert. Der Betreiber muss bewusst auf die Shortcodes umsteigen, wo Consent-Trennung erforderlich ist. Akzeptierter Trade-off.

## ADR-3: Clientseitiger Consent fuer v0.1.0

**Entscheidung:** Die Consent-Auswahl wird ausschliesslich clientseitig in `localStorage` und einem Cookie gespeichert. Es gibt keine serverseitige Consent-Datenbank.

**Kontext:** Manche Plugins speichern Consent-Logs serverseitig zur Nachweisfuehrung. Das ist nuetzlich, fuehrt aber zu DB-Tabellen, Migrations, Datenschutzfragen rund um den Speicher der Logs selbst und zur Komplexitaet bei Multi-Site / Caching.

**Gruende:**

- Caching-Plugins koennen den Footer ohne Sonderregeln ausliefern.
- Keine zusaetzliche Tabelle, keine Migrations.
- Kein Datenschutzpfad zweiter Ordnung (Speicher der Logs).
- Geringer Footprint, geringe Wartungslast.
- Consent wird versioniert (`version` 1) — bei Bedarf laesst sich ein Renewal triggern, indem die Versionszahl erhoeht wird.

**Folgen:** Es existiert kein zentrales Audit-Log. Falls spaeter ein Nachweis-/Logging-Feature benoetigt wird, ist das ein neues, optional aktivierbares Modul. Akzeptierter Trade-off fuer v0.1.x.

## ADR-4: Privacy Check ist passiv

**Entscheidung:** Der `Privacy Check` im Admin prueft genau eine URL (`home_url('/')`) via `wp_remote_get` mit Timeout 5 s und max. 500 KB Response gegen eine kurze statische Mustertabelle. Er aendert keine Inhalte und blockiert nichts automatisch.

**Kontext:** Ein einfacher Hinweis-Check hilft Betreibern, offensichtliche Probleme (z. B. Google Fonts im Theme, GA-Snippet ohne Consent-Gate) selbst zu erkennen.

**Gruende:**

- Ein Crawl waere ein eigenes Modul mit Hintergrund-Cron, Queueing, Robustheit, Permissions.
- Eine Pruefung der Startseite faengt die meisten typischen Funde ab, ohne Last zu erzeugen.
- Keine externen Services, keine Cloud-Datenbank.
- Hinweise sind klar als Empfehlungen formuliert; der Mensch entscheidet.

**Folgen:** Inhalte, die nur auf Unterseiten geladen werden, werden vom Privacy Check nicht erfasst. Akzeptierter Trade-off; eine Mehrseiten-Pruefung waere eine spaetere optionale Erweiterung.

## ADR-5: Keine externen Libraries

**Entscheidung:** Kein React, kein Vue, kein jQuery, kein Alpine, keine UI-Bibliothek, kein Color-Picker-Paket, kein npm, kein Composer, kein Build-Schritt, keine CDNs.

**Kontext:** Externe Libraries vereinfachen die Entwicklung, erzeugen aber Abhaengigkeiten, Build-Pipelines, Security-Updates, Supply-Chain-Risiken und Performance-Kosten.

**Gruende:**

- WordPress-Frontends sollen schnell, cachebar und stabil bleiben.
- Vanilla JS reicht fuer ein Banner mit klarer Logik.
- Hex-Farben lassen sich ueber ein Pattern-validiertes Text-Input pflegen — kein Color-Picker-Modul noetig.
- Kein Build heisst: kompilierte Plugin-Zips bestehen exakt aus dem, was im Repo liegt.
- WordPress-eigene i18n-Funktionen sind ausreichend; kein Tool-Chain-Aufbau.

**Folgen:** Die Entwickler-Ergonomie ist minimaler. Bestimmte Komfort-Features (z. B. Color-Picker im Admin) muessten manuell implementiert oder bewusst weggelassen werden. Akzeptiert.

## ADR-6: Script-Blocking nur fuer bewusst markierte Skripte

**Entscheidung:** Es werden ausschliesslich Skripte aktiviert, die `type="text/plain"` und `data-cookie-category` tragen. Beim Replace werden `on*`-Handler und unsichere Attribute nicht uebernommen.

**Kontext:** Ein automatisches Umschreiben bestehender `<script>`-Tags waere ein Auto-Rewrite und damit ADR-2-aequivalent invasiv.

**Gruende:**

- Vorhersehbares Verhalten.
- Keine Brechung bestehender Theme-/Plugin-Skripte.
- Klare, dokumentierbare Markup-Konvention fuer Redakteure und Entwickler.

**Folgen:** Drittanbieter-Snippets muessen einmalig auf die Markup-Konvention umgestellt werden. Das ist dokumentiert.

## ADR-7: Default "nur notwendige"

**Entscheidung:** Beim ersten Aufruf ohne gespeicherten Consent sind nur notwendige Cookies aktiv. "Nur notwendige" ist gleichwertig prominent zu "Alle akzeptieren" platziert.

**Gruende:**

- Aufsichtsbehoerden in DE / EU / CH lehnen vorausgewaehlte optionale Kategorien und versteckte Ablehnungs-Buttons ab.
- Eine klare gleichwertige Wahlmoeglichkeit reduziert Designdiskussionen pro Site.

## ADR-8: Cookie + localStorage statt nur einem

**Entscheidung:** Consent wird sowohl in `localStorage` als auch in einem Cookie (`lscc_consent`, 180 Tage, `SameSite=Lax`, `Secure` bei HTTPS) gespeichert.

**Gruende:**

- `localStorage` ist schnell auslesbar, aber in einigen Browser-Modi (z. B. Private Mode, Hardened Storage) blockiert.
- Cookies sind zuverlaessiger persistierbar, koennen aber durch Browser-Cleaner haeufiger entfernt werden.
- Die Kombination ist robust gegen beide Faelle. Bei Konflikt gewinnt `localStorage` (clientseitig naeher).

## ADR-9: Defensive WPML- und Polylang-Integration

**Entscheidung:** Texte werden via `do_action( 'wpml_register_single_string', ... )` und `pll_register_string()` registriert. Der Translator-Pfad ist via `apply_filters( 'wpml_translate_single_string', ... )` und `pll__()` (sofern `function_exists`).

**Gruende:**

- Funktioniert ohne WPML / Polylang ohne Fehler.
- Erzeugt keinen harten Linkage in den Code, der das Plugin von WPML abhaengig machen wuerde.
- Editierbare Texte aus dem Admin sind sofort uebersetzbar, sobald eine der Integrationen installiert ist.

## ADR-10: Hex-Farbpflege im Admin ohne JS-Picker

**Entscheidung:** Farben werden im Admin ueber Text-Inputs mit `pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"` gepflegt und serverseitig via `sanitize_hex_color` validiert.

**Gruende:**

- Kein Color-Picker-Asset, keine Library-Abhaengigkeit.
- WordPress-eigene Sanitization deckt den Wertebereich ab.
- Bei Bedarf laesst sich spaeter ein optionaler nativer Color-Picker andocken, ohne die bestehende Validation zu ersetzen.
