# Architekturentscheidungen

Dieses Dokument haelt zentrale Entscheidungen fest, die die Form des Plugins erklaeren. Jede Entscheidung steht unter dem Vorbehalt, dass sich Anforderungen aendern koennen; eine Aenderung waere aber eine bewusste neue Entscheidung und kein "Bugfix".

**Vorrangs-Quelle:** Bei Konflikten zwischen Entscheidungen in diesem Dokument und der uebergeordneten AI-Handover-Datei `MASTER_HANDBUCH.md` (im selben Plugin-Ordner) gewinnt die Master-Datei. Sie definiert Projektphilosophie, absolute No-Gos, Scope-Grenzen und Sicherheitsregeln und darf nicht umbenannt, ueberschrieben oder gekuerzt werden. Erweiterungen erfolgen ausschliesslich additiv mit klarer Versionshistorie.

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

## ADR-11: Neutraler Consent-Ton und Locale-Fallback

**Entscheidung:** Der Default-Banner-Text ist neutral formuliert (kein „Ihre Zustimmung" / „deine Zustimmung") und wird je nach Site-Locale dynamisch aus einer Tabelle aufgelöst. Die Locale-Lookup-Logik nutzt einen 2- bis 3-Buchstaben-Sprachpräfix und akzeptiert Varianten wie `de_CH`, `de-CH`, `de_DE`, `de_AT`, `en_GB`, `pt_BR`.

**Kontext:** Der bisherige deutsche Default war auf Sie formuliert („nach Ihrer Zustimmung geladen") und brach für Du-Sites und englischsprachige Besucher. Zugleich liefern Setups mit WPML, Polylang oder gemischten Locales häufig generische Codes wie `de` oder Varianten wie `de_AT` / `de-CH`, für die kein eigener `.mo`-Match existiert.

**Gründe:**

- Neutraler Wortlaut passt sowohl für Du- als auch Sie-Seiten und muss vom Admin in der Regel nicht angepasst werden.
- Sprachpräfix-Fallback macht die Logik robust gegen Locale-Varianten; keine eigene `.mo` pro Variante nötig.
- Englisch als finaler Fallback ist international verständlich.
- Schweizer Deutsch wird über den Sprachpräfix `de` automatisch mit-bedient; das ß bleibt explizit ausgespart, Umlaute werden voll genutzt.

**Folgen:** Die Default-Texte für die sechs Hauptsprachen liegen direkt als UTF-8-Strings im PHP-Code (`get_neutral_banner_text_table()`). Sie laufen nicht mehr durch `__()` und erscheinen nicht im POT-Template. Admins können den Banner-Text weiterhin über die Einstellungen frei ändern; WPML- und Polylang-Übersetzung des Admin-Wertes bleibt unverändert.

## ADR-12: Konfigurierbares Overlay und rechtliche Link-Erkennung ohne Footer-Scraping

**Entscheidung:** Das Banner kann optional ein Overlay mit Blur anzeigen. Rechtliche Links (Datenschutz, Impressum) werden im Banner angezeigt; ihre URLs werden im Admin per leichtgewichtigem Slug-/Titel-Lookup erkannt und als Transient gecached. Das Frontend führt selbst keine Erkennung und keine DOM-Scans aus, sondern liest ausschliesslich den Cache. Die Position des Widerrufsbuttons ist über vier feste Positionen plus Offsets konfigurierbar.

**Kontext:** Cookie-Banner-Plugins haben oft entweder gar kein Overlay (zu unauffällig) oder ein modales Overlay mit Body-Scroll-Lock (zerstört UX und Core Web Vitals). Ähnlich problematisch ist Auto-Erkennung von rechtlichen Links via Footer-DOM-Crawl im Frontend — das macht jeden Seitenaufruf teurer und kann Themes brechen.

**Gründe:**

- Overlay als `position: fixed` mit `pointer-events: none` blockiert weder Klicks noch Scrolling.
- Das Overlay-Element wird nur in den DOM geschrieben, wenn der Admin es aktiviert; initial ist es `hidden` (kein `backdrop-filter`-Rendering-Cost wenn ungenutzt).
- Reopen-Button: Position über vier feste Werte plus CSS-Variablen für Offset — kein JS-Reposition-Loop, keine Layout-Jumps.
- Legal-Link-Detection nutzt im Admin maximal acht `get_page_by_path()`-Lookups und sechs `WP_Query`-Lookups auf den Post-Title; cacht das Ergebnis im Transient mit `DAY_IN_SECONDS` TTL. Frontend liest nur den Transient.
- Manueller Override (`privacy_url_override`, `imprint_url_override`) hat Vorrang über die Auto-Erkennung — Editoren behalten die Kontrolle.

**Folgen:** Bei deaktiviertem Overlay fällt im Frontend kein zusätzliches DOM-Element an. Bei aktiviertem Overlay ist die einzige zusätzliche Render-Last das Blur — konservativ voreingestellt auf 4 px. Die Link-Auto-Erkennung hat einen Cold-Start-Cost im Admin (≤ 14 DB-Queries) und ist danach 24 Stunden lang ein einziger Transient-Read pro Seitenaufruf. Bei aktiviertem Persistent-Object-Cache (Redis/Memcached) ist auch dieser Read praktisch kostenlos. Editoren können den Cache jederzeit zurücksetzen, indem sie den manuellen Override entfernen und die Detection neu triggern.

## ADR-13: Separate Consent-Schema-Version (`LSCC_CONSENT_VERSION`)

**Entscheidung:** Die Schema-Version des clientseitig gespeicherten Consents wird in einer eigenen Konstante `LSCC_CONSENT_VERSION` gehalten und ist von der Plugin-Version (`LSCC_VERSION`) entkoppelt. Sie wird nur dann erhöht, wenn die Consent-Struktur sich semantisch ändert (neue Pflichtfelder, geänderte Bedeutung, neue Kategorien, etc.) — nicht bei jedem Patch. Ein Versions-Mismatch invalidiert bestehende Consents im Browser und führt zum erneuten Erscheinen des Banners. Zusätzlich wird die Gültigkeit über ein gespeichertes `expiresAt` und ein adminseitig konfigurierbares `consent_lifetime_days` (1 – 365 Tage, Default 180) zeitlich begrenzt.

**Kontext:** In v0.1.0 – v0.1.4 war `consentVersion` hartkodiert als `'1'` im JS-Localization-Block. Da diese Version mit jedem Plugin-Update unverändert blieb, akzeptierten neuere Plugin-Builds bestehende Browser-Consents älterer Builds weiterhin als gültig. Beim Wechsel auf v0.1.4 mit erweiterten Optionen (Overlay, Blur, Legal-Links) blieb das Banner im Browser des Testers daher unsichtbar — der „alte" v1-Consent galt unverändert. Das Problem wurde auch durch Plugin-Deinstallation und -Neuinstallation nicht behoben, weil der Browser-Storage clientseitig liegt.

**Gründe:**

- Plugin-Version und Consent-Schema-Version sollen unabhängig sein, damit kosmetische Patches keine sinnlosen Re-Consents auslösen und strukturelle Änderungen zuverlässig zu einem Re-Consent führen.
- `expiresAt` im gespeicherten Consent ist explizit (statt nur Cookie-Max-Age) und überlebt auch eine `localStorage`-Persistenz, in der Cookies bereits abgelaufen wären.
- Admin-konfigurierbare Lifetime (`consent_lifetime_days`) macht Verkürzungen sofort wirksam — auch retroaktiv für bestehende Consents über den `createdAt + lifetimeDays`-Check.

**Folgen:** Bei jedem strukturellen Schema-Change muss `LSCC_CONSENT_VERSION` manuell hochgezählt werden. Bei v0.1.5 wurde sie von `1` auf `2` erhöht, weil das Schema um `expiresAt` erweitert wurde und alte v1-Consents nicht verlässlich migrierbar sind. Der Reopen-Button erscheint nur dann, wenn `hasStoredConsent()` einen *gültigen* Consent zurückgibt — bei ungültigem oder fehlendem Consent zeigt sich das Banner und der Reopen-Button bleibt versteckt.

## ADR-14: Lokale Thumbnails für Service-Komponenten

**Entscheidung:** Die Service-Komponenten können vor Consent optional ein Vorschaubild zeigen. Als Bildquelle wird **ausschliesslich** eine numerische WordPress-Mediathek-ID über das Shortcode-Attribut `thumbnail_id` akzeptiert (zuerst umgesetzt für `[lscc_youtube]`). Das Bild wird serverseitig über `wp_get_attachment_image()` gerendert. Es gibt **keine** freie Bild-URL (`thumbnail="..."`), **keine** automatische URL-Erkennung, **keine** Ermittlung des Thumbnails aus der Video-ID und **keinen** Zugriff auf `img.youtube.com`, `ytimg.com`, Google oder andere externe Bildquellen.

**Kontext:** Der bisherige Platzhalter ist technisch korrekt, aber visuell schlicht. Ein Vorschaubild plus Play-Button hebt die wahrgenommene Qualität deutlich, besonders mobil. Die offensichtliche „Bequemlichkeitslösung" wäre, das offizielle YouTube-Thumbnail automatisch aus der Video-ID zu laden (`https://img.youtube.com/vi/ID/...`). Genau das ist aber ein Request an Google **vor** jeder Zustimmung und widerspricht der Kernphilosophie (`MASTER_HANDBUCH.md`: keine externen Requests, kein Consent-Bypass, kein Auto-Fetch).

**Gründe:**

- **Datenschutz vor Komfort.** Nur eine lokale Attachment-ID kann garantieren, dass vor Consent kein Drittanbieter-Request entsteht. Eine freie URL könnte (versehentlich) auf einen externen Host zeigen und die Garantie per Konstruktion brechen; sie wird daher bewusst nicht unterstützt.
- `wp_get_attachment_image()` liefert Dimensionen, `srcset` und alt-Text aus dem WordPress-Kern — responsive, barrierearm, CLS-sicher (Platz ist über `aspect-ratio: 16/9` ohnehin reserviert) und ohne externe Library.
- Das Feature ist rein serverseitig. Der Consent-Flow und das Frontend-JS (`createMediaIframe`, `syncMediaComponents`, `acceptExternalMedia`) bleiben unverändert; der Play-Button trägt lediglich dasselbe `data-lscc-accept-media`-Attribut wie der bestehende Accept-Button und wird vom vorhandenen `bindMediaComponents()` automatisch gebunden.
- Robuster Fallback: ungültige IDs oder Nicht-Bild-Attachments fallen still auf den bisherigen Platzhalter zurück — kein Fehler, keine kaputte Darstellung.

**Folgen:** Editoren müssen das Vorschaubild bewusst in die Mediathek laden und die ID setzen (kein Copy-Paste einer fremden URL). Das ist der akzeptierte Trade-off für die Datenschutz-Garantie. WPML-/Polylang-Media-Übersetzung greift über die Attachment-ID. Die Mechanik (gleicher Helper `get_local_thumbnail_html()`, gleiches Attribut, gleiche CSS-Klassen) wurde in v0.1.7 auf Vimeo ausgeweitet; Google Maps bleibt vorerst bewusst ausgenommen.

## ADR-15: Test-ZIPs liegen im Überordner des Repositories

**Entscheidung:** Installierbare Test-ZIPs werden ausschliesslich im **Parent-Verzeichnis des Git-Repositories** abgelegt (`G:\Cookie Banner Plugin\`), nicht im Repository-Ordner selbst (`...\light-swiss-cookie-consent\`). Der Ablageort ist fest definiert und wird vom Agent nicht gesucht, geraten oder interpretiert.

**Kontext:** Das Repository enthält ausschliesslich den installierbaren Plugin-Quellstand. ZIP-Build-Artefakte sind keine Repo-Inhalte (per `.gitignore` `*.zip` ohnehin nicht versioniert). Die etablierte Projektpraxis legt alle bisherigen Builds (`v0.1.0` … `v0.1.7-test`) bereits im Überordner ab. Ein versehentliches Ablegen im Repo-Ordner durch den Agent würde von dieser Praxis abweichen.

**Gründe:**

- Saubere Trennung zwischen Repository (Quellcode) und Build-Artefakten (ZIPs).
- Die ZIPs liegen gesammelt und versioniert nebeneinander im Parent — leicht auffindbar und vergleichbar.
- Eindeutiger, nicht zu interpretierender Zielpfad verhindert Streuung über mehrere Orte.

**Folgen:** Beim ZIP-Build (`git archive ... -o <Parent>\light-swiss-cookie-consent-v<VERSION>-test.zip HEAD`) ist der Zielpfad immer das Parent-Verzeichnis. Eine Änderung des Ablageorts erfordert eine ausdrückliche Anweisung des Auftraggebers. Bestehende Projektpraxis hat Vorrang vor Annahmen des Agents. Verbindliche Referenz: `MASTER_HANDBUCH.md`, Sektion „Release-Artefakte / Ablageort für Test-ZIPs".

## ADR-16: Avada-Massenkompatibilität via Render-Layer-Interception (Richtungsentscheidung, Umsetzung freigabepflichtig)

**Status:** Richtungsentscheidung dokumentiert; Implementierung steht unter ausdrücklichem Freigabevorbehalt (technischer Spike + Scope-Freigabe erforderlich).

**Entscheidung:** Für den geplanten Einsatz auf ≈40 bestehenden Avada-Websites ist der bevorzugte Weg, bestehende Avada-Video-Elemente über **serverseitige Render-Layer-Interception** consent-zu-gaten — als **opt-in Modul** über WordPress-Filter (`pre_do_shortcode_tag` für `fusion_youtube`/`fusion_vimeo`, `embed_oembed_html` für oEmbeds), das statt des Avada-iframes das bestehende LSCC-Platzhalter-Markup (Kategorie `external_media`) ausgibt. **Serverseitige Content-Migration** (`post_content`-Rewrite) wird verworfen.

**Kontext:** Shortcode-only (ADR-2/ADR-6) deckt Neuinhalte ab, aber nicht den Bestand von potenziell tausenden bereits gebauten Avada-Seiten. Eine manuelle Umstellung ist nicht zumutbar. Avada speichert Videos als Fusion-Shortcodes im `post_content` und rendert sie serverseitig — das bietet einen sauberen Interception-Punkt vor der iframe-Erzeugung.

**Gründe:**

- Skaliert automatisch über alle Seiten/Sites ohne manuelles Editieren; `post_content` bleibt unangetastet; vollständig reversibel (Modul deaktivieren).
- No-Go-konform: kein MutationObserver, kein DOM-Hijacking fertiger iframes, kein Frontend-Scanner, kein Crawler, kein `<script>`-Rewrite. Interception der eigenen Render-Pipeline ≠ DOM-Manipulation.
- Datenschutz: kein Drittanbieter-Request vor Consent (nur Platzhalter), gleiche Mechanik wie die bestehenden Service-Komponenten.
- Content-Migration verworfen wegen Fusion-Builder-Desync-Risiko, Destruktivität und schlechter Reversibilität über 40 Sites.

**Folgen / offene Punkte:** Abdeckungslücken (Hintergrundvideos, `fusion_code`-Roh-Embeds, handgepastete iframes) werden nicht automatisch erfasst und nur über den passiven Privacy-Check sichtbar gemacht. Vor Umsetzung: Spike an realer Avada-Seite (exakte, versionsabhängige Shortcode-Atts; Hintergrundvideo-Pfad; Konflikt mit Avadas eigener Privacy-/Embed-Funktion — nur EINE Consent-Schicht). Erst danach formelle Freigabe und Implementierung. Verbindliche Referenz: `MASTER_HANDBUCH.md`, Sektion „Avada-Massenkompatibilität (Strategie)".

## ADR-17: Avada-`fusion_youtube` Consent-Gating via `pre_do_shortcode_tag` (umgesetzt in v0.1.9)

**Status:** Umgesetzt (v0.1.9). Konkretisiert und aktiviert die in ADR-16 beschlossene Richtung für den ersten Dienst (YouTube). Freigabe durch den Auftraggeber erteilt nach Spike (v0.1.8) und realem Test (Avada/Daniela-Baumann).

**Entscheidung:** Avadas `fusion_youtube`-Shortcode wird im Frontend über den Filter `pre_do_shortcode_tag` abgefangen und durch das bestehende LSCC-Platzhalter-Markup ersetzt. Das gilt **gate über die Kategorie `external_media`** — identisch zu `[lscc_youtube]`/`[lscc_vimeo]` —, damit dieselbe Consent-Schaltfläche alle YouTube-Einbettungen unabhängig von der Herkunft steuert. Das Feature ist **opt-in** über die Admin-Option `avada_youtube_block` (Default `true`) und betrifft **nur YouTube**.

**Kontext:** Test auf einer echten Avada-Seite zeigte: Consent wurde korrekt gespeichert (`external_media`/`marketing` = false), aber Avada-YouTube-Elemente luden trotzdem `iframe_api`, `www-widgetapi.js` und YouTube-Cookies, weil `fusion_youtube` sein iframe ungated rendert. Der Inventar-Scan (v0.1.8) bestätigte 100 % automatische Erkennbarkeit der `fusion_youtube`-Elemente.

**Begründung der Kategorie-Wahl (`external_media` statt `marketing`):** Die bestehenden Video-Komponenten nutzen `external_media`. Würde Avada-YouTube an `marketing` hängen, steuerten herkunftsgleiche Videos je nach Quelle unterschiedliche Schalter — inkonsistent und für Betreiber/Besucher verwirrend. `external_media` hält das Verhalten einheitlich. (Auftraggeber-Entscheid.)

**Begründung des Mechanismus:** `pre_do_shortcode_tag` greift vor der iframe-Erzeugung → kein Drittanbieter-Request vor Consent, kein DOM-Hijacking, kein MutationObserver, kein Scanner, kein `<script>`-Rewrite, keine `post_content`-Migration. Wiederverwendung von `Service_Components::render_youtube()` vermeidet einen zweiten Platzhalter-/Consent-Pfad. Greift nur im Frontend; das Builder-Backend bleibt unberührt. Bei nicht parsebarer Video-ID wird die Original-Avada-Ausgabe durchgelassen (kein Layout-Bruch).

**Folgen / offene Punkte:** Das LSCC-Platzhalter-Layout (16:9) kann von der ursprünglichen Avada-Größe/-Ausrichtung leicht abweichen — visuell unkritisch, real zu prüfen. Vimeo (`fusion_vimeo`), Maps (`fusion_map`, zusätzlich Script-Gating nötig), Background-Videos, `fusion_code` und rohe iframes sind bewusst noch nicht abgedeckt und Kandidaten für Folgeversionen. Ein möglicher Konflikt mit Avadas eigener Privacy-/Embed-Funktion ist beim Einsatz zu prüfen (nur EINE Consent-Schicht aktiv).

## ADR-18: Nativer LSCC-YouTube-Block für neue Websites; optionales Remote-Thumbnail (schränkt ADR-14 ein)

**Status:** Umgesetzt (v0.2.0).

**Entscheidung:** `[lscc_youtube]` ist der empfohlene native Weg für neue Websites (statt Avada `fusion_youtube`). Erweiterungen in v0.2.0: `id` akzeptiert auch YouTube-URLs, neues `title`-Attribut, Play-Button immer sichtbar (auch ohne Thumbnail), Autostart nach Play-Klick. Zusätzlich gibt es eine **opt-in Admin-Option `youtube_remote_thumbnails` (Default AUS)**, die — und nur dann — ein Vorschaubild von `i.ytimg.com` aus der Video-ID vor Consent lädt.

**Kontext / Verhältnis zu ADR-14:** ADR-14 verbot externe Bildquellen und das Ableiten eines Thumbnails aus der Video-ID ausdrücklich („Datenschutz vor Komfort"). Der Auftraggeber verlangt für v0.2.0 explizit ein optionales YouTube-Vorschaubild und akzeptiert die Abwägung. ADR-14 gilt weiterhin als **Default** (lokales `thumbnail_id` bzw. reiner Platzhalter); ADR-18 fügt lediglich einen **bewusst aktivierbaren** Override hinzu.

**Begründung:**

- Default bleibt maximal datenschutzfreundlich (Option AUS → keine externe Bildanfrage). Lokales `thumbnail_id` hat immer Vorrang.
- Auch bei aktiviertem Remote-Thumbnail entsteht **vor Consent** kein iframe, kein `iframe_api`, kein `www-widgetapi.js` und keine youtube.com-Cookies. Es wird ausschliesslich ein statisches Bild von `i.ytimg.com` geladen.
- URL-Akzeptanz und `title` senken die Einstiegshürde für Redakteure; der zentrale Helper `extract_youtube_id()` wird auch von der Avada-Interception (ADR-17) genutzt (keine Code-Dublette).
- Autostart nach Play-Klick ist eine erwartbare UX und nutzt eine minimale, gekapselte `banner.js`-Ergänzung (`autoplay=1` nur für YouTube/Vimeo, nur wenn der Play-Button die Aktivierung auslöste).

**Folgen:** Betreiber, die `youtube_remote_thumbnails` aktivieren, müssen wissen, dass `i.ytimg.com` vor Consent die Besucher-IP an Google überträgt — je nach Rechtsraum (CH/EU/DE) ggf. selbst zustimmungspflichtig. Die Option ist daher bewusst per Default deaktiviert und im Admin klar beschriftet. Bestehende `[lscc_youtube]`-Nutzungen bleiben kompatibel.

## ADR-19: Zwei-Schichten-Mehrsprachigkeit — Locale-Default-Tabelle + kompilierte `.mo` (umgesetzt in v0.2.1)

**Status:** Umgesetzt (v0.2.1). Behebt den im Live-Test (v0.2.0) gefundenen Sprach-Mix (Befund 1 + 2).

**Entscheidung:** Die Banner-Mehrsprachigkeit wird über **zwei klar getrennte Schichten** gelöst, die beide der aktiven WPML-/Polylang-Sprache folgen:

1. **Editierbare Strings** (die sieben Admin-pflegbaren Texte: `banner_title`, `banner_text`, `accept_all_text`, `necessary_only_text`, `settings_text`, `save_settings_text`, `reopen_text`) erhalten ihre **Defaults aus einer Locale-Tabelle** `get_default_text_table()`, aufgelöst zur Render-Zeit über `determine_locale()` (Helper `get_neutral_text()`). Erweitert das bereits für `banner_text` etablierte Muster (ADR-11) auf alle sieben Strings.
2. **Fixe Textdomain-Strings** (Kategorie-Labels/-Beschreibungen, Rechtslinks, Service-Komponenten-Texte) werden über **kompilierte `.mo`-Dateien** übersetzt — die zuvor fehlten.

Die bestehende **WPML-/Polylang-String-Translation-Registrierung bleibt als Override** erhalten und hat Vorrang, sobald ein Admin einen Text anpasst oder eine Sprache jenseits der sechs Bundle-Sprachen pflegt.

**Kontext / Root Cause:** Im Plugin existierten zwei Übersetzungs-Mechanismen, die auseinanderliefen. Mechanismus B (Locale-Tabelle) bediente nur `banner_text` → folgte der Sprache und zeigte z. B. auf EN englisch. Mechanismus A (`__()`/`esc_html__()`) bediente alle übrigen Strings **und die Defaults der editierbaren Strings**, lieferte aber mangels kompilierter `.mo` immer den **deutschen Quelltext**. Ergebnis: englischer Einleitungstext neben deutschen Labels (Sprach-Mix). Die `.po` lagen nur als leere Skelette vor, `.mo` fehlten ganz.

**Begründung:**

- **Defaults via Locale-Tabelle** statt `__()` entkoppelt die editierbaren Strings von der `.mo`-Pflege und macht sie ab Werk (unsaved install) sprachrichtig — robust gegen beliebige WPML-Sprachen, die auf einen der Präfixe (`de`/`en`/`fr`/`it`/`tr`/`hu`) mappen; Fallback Englisch.
- **`.mo` für fixe Strings** ist der WordPress-Standardweg; WPML schaltet die Locale pro Frontend-Sprache und WordPress lädt das passende `.mo`. Damit folgen Kategorie-Labels etc. automatisch der Besuchersprache.
- **Scope-Entscheid Frontend vs. Admin:** Übersetzt werden in allen sechs Sprachen die **frontend-/besucherseitigen** Strings (das Banner, das der Besucher in seiner WPML-Sprache sieht — Gegenstand von Befund 1+2). **Admin-only-Strings** (Einstellungsseiten, Privacy Check, Avada-Inventar) bleiben deutsche Quelle: wp-admin rendert in der **Operator-Sprache**, nicht in der Besucher-WPML-Sprache. Das hält die Übersetzungsqualität dort hoch, wo sie sichtbar ist, und vermeidet riskante Teilübersetzungen langer technischer Admin-Texte.
- **POT-Audit:** Das `.pot` wird aus den realen Quelltext-Callsites generiert (158 msgids). Die vier editierbaren Strings ohne verbleibenden `__()`-Callsite (`Cookie-Einstellungen`, `Alle akzeptieren`, `Nur notwendige`, `Auswahl speichern`) entfallen dadurch korrekt aus dem Template.

**Folgen / offene Punkte:** Die `.mo`-Dateien sind kompilierte Artefakte im Repo (kein Build-System; erzeugt über ein einmaliges Generator-Skript ausserhalb des Repos, `.po` bleiben die lesbare Quelle). Eine **siebte** WPML-Sprache jenseits der sechs Bundle-Sprachen wird für die editierbaren Strings über WPML String Translation bedient (Override-Pfad) und für die fixen Strings über eine ergänzte `.po`/`.mo`. Das tatsächliche WPML-`switch_to_locale()`-Verhalten (lädt WP das `.mo` pro Frontend-Sprache nach) ist auf der Live-Seite gegenzuprüfen. Verbindliche Referenz: `MASTER_HANDBUCH.md`, Sektion „Sprachstrategie".

## ADR-20: YOTU (Yotuwp) YouTube-Galerie Consent-Gating via Script-Blockade + Thumbnail-Neutralisierung (umgesetzt in v0.2.2)

**Status:** Umgesetzt (v0.2.2). Behebt den im Live-Test gefundenen Befund 3 (oben klickbares YouTube trotz „Nur notwendige"). Freigabe nach Spike erteilt.

**Kontext / Root Cause:** Die „Podcast"-Galerie auf `plugins.svogellisi.ch` stammt vom Fremd-Plugin **Yotuwp – Easy YouTube Embed**, nicht von Avada. Spike-Evidenz (Roh-HTML + `frontend.min.js`): das Plugin-Script (`yotu-script` + Inline `-extra`/`-after`) injiziert beim Klick `https://www.youtube.com/iframe_api` und baut `YT.Player` — ausserhalb der LSCC-Consent-Schicht. Zusätzlich werden die Galerie-Thumbnails per `data-orig-src` von `i.ytimg.com` geladen; den Swap macht **Avadas Lazy-Load**, nicht Yotu. Die übrigen Videos sind `[lscc_youtube]` und korrekt gegated → der Mischzustand erzeugte die Inkonsistenz.

**Entscheidung:** Ein **opt-in Modul „YOTU Consent Gating"** (`includes/yotu-compat.php`, Option `yotu_consent_gating`, Default **AUS**) gated die Galerie über die **bestehenden** LSCC-Mechanismen — kein DOM-Hijacking, kein MutationObserver, kein Scanner:

- **Phase 1 (Scripts):** `yotu-script` und seine Inline-Teile `-extra`/`-after` werden über die offiziellen Filter `script_loader_tag` und `wp_inline_script_attributes` als `type="text/plain"` + `data-cookie-category="external_media"` markiert. Die vorhandene `banner.js::activateBlockedScripts()` reaktiviert sie erst nach Consent.
- **Phase 2 (Thumbnails + Hinweis):** Im Yotu-Shortcode-Output (`do_shortcode_tag`, nur wenn `yotu-video-thumb` enthalten) wird `data-orig-src` → `data-lscc-orig-src` umbenannt (Avada-Lazy-Load findet nichts zu laden) und ein Consent-Hinweis (`data-lscc-gated-notice`, `data-lscc-accept-media`) über der Galerie vorangestellt. `banner.js::restoreExternalMediaThumbnails()` stellt nach Consent `src`/`data-orig-src` wieder her und versteckt den Hinweis.

**Begründung:**

- **Wiederverwendung statt Sonderweg:** Phase 1 nutzt die dokumentierte `type="text/plain"`-Script-Blockade (ADR-6); Phase 2 nutzt das Consent-Event/`acceptExternalMedia` und denselben `data-lscc-accept-media`-Hook wie die Service-Komponenten. Reines serverseitiges Tag-/Output-Filtern über offizielle WP-Hooks — gleiche Familie wie Avada (ADR-17), No-Go-konform.
- **Abgrenzung zu ADR-17 (Avada):** Avada rendert ein iframe **serverseitig** → `pre_do_shortcode_tag`-Ersatz. Yotu lädt **clientseitig** per JS → Script-Gating ist der richtige Hebel. Daher ein eigenes Modul statt Erweiterung von avada-compat.
- **Default AUS** (anders als Avada AN): Yotu läuft nicht auf allen Sites; das Modul wird nur dort aktiviert, wo Yotu im Einsatz ist.
- **Reihenfolge-Korrektheit:** `-after` (`yotuwp.data.videos[...]`) hängt von `frontend.min.js` ab. Deshalb wurde `activateBlockedScripts()` auf **sequenzielle** Aktivierung umgestellt (externe Scripts `async=false`, nächster Knoten erst nach `load`) — eine generische, für alle gegateten Abhängigkeiten korrekte Verbesserung.
- **Datenschutz:** Vor Consent kein youtube.com / youtube-nocookie.com / `iframe_api` / `www-widgetapi` / `i.ytimg.com`. Nach Consent funktioniert Yotu normal.

**Folgen / offene Punkte:** Die Thumbnail-Neutralisierung greift bei **per Shortcode** gerenderten Galerien (`do_shortcode_tag`); reine **Block-/Widget**-Einbindungen sind eine bekannte Coverage-Grenze und separat zu prüfen. Das Inline-Script-Gating benötigt **WordPress ≥ 5.7** (`wp_inline_script_attributes`); auf älteren Cores bleibt das Haupt-Script trotzdem geblockt (kein Drittanbieter-Request), nur die harmlosen Inline-Teile liefen. Vollständig reversibel (Option AUS → alle Filter entfallen). Re-Test auf `plugins.svogellisi.ch` ausstehend (siehe RELEASE_CHECKLIST). Verbindliche Referenz: `MASTER_HANDBUCH.md`.

## ADR-21: Gespeicherter Consent ist die alleinige Quelle der Wahrheit für die Checkbox-Anzeige (umgesetzt in v0.2.3)

**Status:** Umgesetzt (v0.2.3). Behebt Bug 1 (Consent-UI lief auseinander).

**Entscheidung:** Die Anzeige der Consent-Checkboxen wird **ausschliesslich** aus dem gespeicherten Consent abgeleitet und an drei Punkten synchronisiert: (1) beim Laden in `initBanner()` via `updateInputs(getStoredConsent())`, (2) bei jedem Öffnen des Banners (`setBannerVisible(..., visible=true)`, bestehend), (3) nach jedem Speichern in `saveAndClose()`. Zusätzlich tragen die Checkboxen `autocomplete="off"`.

**Kontext / Root Cause:** Bislang lief `updateInputs` nur beim Öffnen des Banners. Bei vorhandenem Consent ist das Banner beim Laden versteckt → keine Sync. Ohne `autocomplete="off"` stellt der Browser (v. a. Firefox) den Checkbox-Zustand von vor dem Reload wieder her. Folge: korrekt gespeicherter Consent (Videos blockiert), aber falsch angezeigte Häkchen. Die Top-Buttons („Alle akzeptieren" / „Nur notwendige") schrieben den Consent zudem ohne Sync der sichtbaren Inputs.

**Begründung:**

- **Single Source of Truth:** Der persistierte Consent (`localStorage`/Cookie) ist die Wahrheit; das DOM-Formular ist nur eine Ansicht davon. Sync beim Laden + nach jedem Write schliesst jede Drift.
- **`autocomplete="off"`** verhindert die Browser-Formular-Wiederherstellung, die sonst die programmatische Sync überschreiben könnte.
- Minimal-invasiv: keine Schema-Änderung (`LSCC_CONSENT_VERSION` bleibt `2`), keine Änderung an der Persistenz, an `activateBlockedScripts` oder an den Service-/YOTU-Modulen.

**Folgen:** UI und gespeicherter Consent können nicht mehr auseinanderlaufen — weder über Reloads (Browser-Restore) noch innerhalb einer Sitzung (Top-Buttons). Re-Test in Firefox UND Chrome empfohlen (siehe RELEASE_CHECKLIST).

## ADR-22: Aktiver Consent-Zustand an den Schnellbuttons (umgesetzt in v0.2.4)

**Status:** Umgesetzt (v0.2.4). Schliesst den als UX-Thema dokumentierten Punkt ab; reine Darstellung.

**Kontext / Root Cause (bewiesen):** Speicher-, Sync- und Checkbox-Ebene sind korrekt (ADR-21). Die Schnellbuttons „Alle akzeptieren" / „Nur notwendige" trugen jedoch **statische** Präsentationsklassen (`--primary` rot / `--secondary` grau) aus dem Server-Markup; ihr Aktiv-Zustand wurde **nie** aus dem gespeicherten Consent abgeleitet (kein Codepfad in `banner.js` berührte die Button-Optik consent-abhängig). Die permanente rote Prominenz von „Alle akzeptieren" wurde als aktiver Zustand fehlinterpretiert.

**Entscheidung:** Eine neue, **rein darstellende** Funktion `updateQuickButtons()` leitet den aktiven Zustand der beiden Schnellbuttons aus `getStoredConsent()` ab und setzt `is-active`/`is-inactive` + `aria-pressed`. Drei Zustände: **neutral** (kein gespeicherter Consent → gleichwertige Prominenz vor der ersten Wahl), **aktiv** (Preset entspricht exakt allOn bzw. allOff), **inaktiv** (anderer/gemischter Zustand). Aufruf parallel zu `updateInputs()` beim Laden, Öffnen und nach jedem Speichern.

**Begründung:**

- **Single Source of Truth:** Auch die Button-Anzeige wird aus dem gespeicherten Consent rekonstruiert — konsistent mit ADR-21. Kein Schreibzugriff auf Consent/Storage/`writeConsent`.
- **Kein Dark Pattern:** Vor der ersten Wahl bleiben beide Buttons neutral und gleichwertig prominent (DE/EU/CH-konform); die Hervorhebung erscheint erst **nach** einer Entscheidung als Status.
- **Barrierearm:** `aria-pressed` kommuniziert den Zustand an Screenreader; `is-active` (Ring + „✓") und `is-inactive` (`opacity`) liefern die visuelle Unterscheidung.
- **Minimal-invasiv:** keine Schema-/Persistenz-Änderung (`LSCC_CONSENT_VERSION` bleibt `2`), keine neuen Features, kein Eingriff in Service-/YOTU-/Script-Mechanik.

**Folgen:** Der aktuelle Consent ist beim Öffnen sofort an den Schnellbuttons erkennbar. Gemischte (individuelle) Auswahl zeigt beide Buttons inaktiv; die genaue Auswahl bleibt über die Checkboxen sichtbar.

## ADR-23: Consent-Code-Manager — zentrale, gegatete Verwaltung von Tracking-Snippets (umgesetzt in v0.3.0)

**Status:** Umgesetzt (v0.3.0). Phase 1 der Produktiv-Roadmap (≈40 Avada-Sites). Freigabe erteilt.

**Entscheidung:** Tracking-/Marketing-Snippets (GA4, GTM, Meta Pixel, Hotjar, weitere) werden zentral in LSCC verwaltet (`includes/consent-codes.php`, Option `lscc_consent_codes`). Der Betreiber fügt komplette Vendor-Snippets ein („paste-as-is"), wählt **Kategorie** und **Position** (Head/Body-Anfang/Footer). Beim Rendern werden alle `<script>`-Tags in die bestehende LSCC-Script-Blockade umgeschrieben (`type="text/plain"` + `data-cookie-category`) und `<noscript>`-Teile **entfernt**. Aktivierung erst nach Consent über die vorhandene `activateBlockedScripts()`-Mechanik — **keine** neue Frontend-Logik. Konservativ blockend (kein Google Consent Mode v2).

**Begründung:**

- **Skalierung über 40 Sites:** Statt Snippets in Theme-/Avada-Dateien zu pflegen, eine zentrale, exportierbare Konfiguration. **Versioniertes Export/Import-Envelope** (`type:'lscc-config'`, `data.consent_codes`), bewusst erweiterbar auf die **gesamte** LSCC-Konfiguration (nicht nur Codes) für späteren Rollout.
- **Wiederverwendung statt Sonderweg:** Reuse der dokumentierten Script-Blockade (ADR-6) und der sequenziellen Aktivierung (v0.2.2); kein neues Frontend-JS. Tag-Transform verallgemeinert `yotu-compat::convert_tag_to_blocked`.
- **Cache-sicher:** serverseitig immer als `text/plain` ausgegeben, Consent clientseitig → identisches HTML mit/ohne Consent (Full-Page-Cache unkritisch).
- **Scannerfähiges Datenmodell** (`vendor/source/category/location`): `detect_vendor()` setzt den Vendor automatisch; der Phase-2-Scanner kann ungegatete Snippets erkennen und „→ in den Consent-Code-Manager verschieben" empfehlen.
- **Sicherheit:** Roh-`code` nur mit `unfiltered_html` (sonst verworfen + Notice), `manage_options` + Nonce, Enum-validierte Attribute. Entspricht dem WP-Muster für „Additional CSS"/Custom-HTML; Multisite: nur Super-Admins.

**Folgen / offene Punkte:** `<noscript>`-Strip bedeutet, dass No-JS-Besucher kein GTM-noscript erhalten (bewusst). Bei der Migration muss der Snippet aus dem alten Ort (z. B. Avada Global Options) **entfernt** werden (sonst Doppel-Laden). **Cache-/Optimierungs-Plugins** (Delay/Combine/Minify JS) können `type="text/plain"`-Inline-Scripts umschreiben/zusammenführen → vor dem 40-Site-Rollout auf dem realen Stack testen und LSCC-gegatete Scripts von der Optimierung ausschliessen. Kein Consent-Nachweis-Log (ADR-3). Drittland-Transfer nach Consent ist über Banner-Text/Datenschutzerklärung abzudecken. Google Consent Mode v2 bewusst nicht umgesetzt.
