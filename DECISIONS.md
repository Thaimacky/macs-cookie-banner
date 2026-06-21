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

## ADR-24: Scanner-Ausbau „Drittanbieter-Oberfläche" mit Gating-Status (umgesetzt in v0.3.1)

**Status:** Umgesetzt (v0.3.1). Phase 2 der Produktiv-Roadmap. Freigabe erteilt.

**Entscheidung:** Die Startseiten-Prüfung (`privacy-check.php`) wird um eine Sektion „Drittanbieter-Oberfläche" erweitert, die pro Dienst nicht nur **ob** gefunden, sondern den **Gating-Status auf der gerenderten Seite** ausweist. Erfasst: GA4, GTM, Meta Pixel, Hotjar, reCAPTCHA, Calendly, YouTube, Vimeo, Google Maps, externe Google Fonts. **5-Status-Modell:** Nicht gefunden / Verwaltet / Teilweise verwaltet / Ungegatet / Nicht prüfbar. Zusätzlich eine Cross-Reference-Spalte „Im Consent-Code-Manager registriert" und eine **eigene Test-URL** (gleicher Host). Reine Lese-/Hinweisfunktion — keine Gating-Umsetzung für Maps/Vimeo.

**Begründung:**

- **Richtiger Aussichtspunkt:** Der bestehende `wp_remote_get` auf eine Seite sieht das gerenderte `<head>` → Scripts/Embeds/Fonts sind sichtbar. Ein Fetch, beide Sektionen.
- **Gating-Klassifizierung On-Page:** `<script>` mit `type="text/plain"`+`data-cookie-category` = verwaltet; rohes `<script>`/`<iframe>` = ungegatet; `data-lscc-service`-Platzhalter = verwaltetes Embed. „Verwaltet/ungegatet" ist damit am Live-Zustand belegt, nicht nur an der Konfiguration.
- **„Nicht prüfbar"** macht die Server-Sicht-Grenze ehrlich sichtbar (GTM-gefeuerte Tags, klick-/JS-geladene Widgets wie Calendly) statt falsche Entwarnung zu geben.
- **DRY:** Vendor-Muster zentral in `Light_Swiss_Cookie_Consent_Codes::match_vendor()` — eine Quelle für Manager-Badge und Scanner.
- **SSRF-Schutz:** eigene Test-URL nur für den **eigenen Host** (Fremd-Hosts → Fallback Startseite + Notice).
- **Google Fonts** sind nicht consent-gate-bar → eigener Status + klare Empfehlung „lokal hosten; Consent ersetzt kein Local Hosting".

**Folgen / offene Punkte:** Server-Sicht ohne JS → von GTM gefeuerte Tags, klick-/JS-geladene Widgets und Unterseiten werden nicht erfasst (ADR-4-Linie). Cache-/Minify-Plugins können Script-Tags verändern → Heuristik, bewusst konservativ. „Verwaltet" = auf der Seite gegated, nicht „korrekt kategorisiert" (bleibt Betreiber-Verantwortung). Maps/Vimeo-Gating sind Folgephasen.

## ADR-25: Avada-Google-Maps Consent-Gating via Embed-Umlenkung (umgesetzt in v0.3.2)

**Status:** Umgesetzt (v0.3.2). Phase 3A, Variante **3A-i**. Freigabe erteilt (kein weiterer Spike verlangt).

**Entscheidung:** Avadas `fusion_map` (JS-API-basiert) wird auf die **bestehende** LSCC-Architektur umgelenkt: `pre_do_shortcode_tag` ersetzt `fusion_map` durch den vorhandenen LSCC-Platzhalter, dessen `data-lscc-src` eine **keyless Google-Maps-Embed-URL** (`maps.google.com/maps?q=<adresse>&output=embed`) ist; das iframe wird von `banner.js::createMediaIframe()` erst nach `external_media`-Consent gebaut. Zusätzlich wird die **Google-Maps-JS-API** `maps.googleapis.com/maps/api/js` über `script_loader_tag` **SRC-basiert** (handle-agnostisch) als `type="text/plain"` blockiert. Opt-in Modul `avada_maps_block`, Default **AUS**, reversibel. `[lscc_google_map]` erhält ein `address`-Attribut (gleiche Embed-Mechanik).

**Begründung:**

- **Reine Wiederverwendung:** Render-Interception (ADR-17-Muster) + Script-Gating (ADR-20-Muster) + bestehender Platzhalter/`createMediaIframe`/Script-Blockade. **Keine** neue Consent-Logik, **kein** DOM-Hijack, **kein** MutationObserver, **kein** Avada-Reinit, **kein** JS-Lifecycle-Management.
- **Handle-agnostisches SRC-Gating** ist robust über Avada-Versionen/Drittplugins und macht den Spike-Punkt „exakter Script-Handle" überflüssig.
- **Kein Reinit nötig:** Der LSCC-Platzhalter trägt **keine** `.fusion-google-map`-Klasse → Avadas Karten-Init findet keine Container und no-op't; die gegatete JS-API lädt vor Consent nicht.
- **Nur eine Consent-Schicht:** Avada besitzt ein eigenes Privacy-Feature für Embeds. Das Modul ist Default AUS und im Admin mit fetter Warnung versehen, Avada-Privacy-Maps und LSCC-Maps **nicht** parallel zu aktivieren.

**Folgen / offene Punkte:** Nach Consent erscheint die Google-**Embed**-Karte (Standort-Pin), **nicht** Avadas voll gestylte JS-API-Karte (eigene Marker/Styles/Zoom-Config gehen verloren) — bewusster Trade-off. Multi-Marker → nur die **primäre** Adresse. Adress-Extraktion ist versionsabhängig → robuster Fallback (kein Ersatz, Avada rendert normal; API bleibt via SRC-Gating geblockt; Restposten via Scanner sichtbar, Empfehlung `[lscc_google_map]`). Keyless `output=embed` ist inoffiziell — beobachten. Cache-/Optimierungs-Plugins können das SRC-Gating stören → auf realem Stack testen. Verbindliche Referenz: `MASTER_HANDBUCH.md`.

## ADR-26: Validierung vor weiteren Features — 5-Site-Gate (Prozess-Entscheidung, ab v0.3.3)

**Status:** Aktiv ab v0.3.3 (2026-06-16). Prozess-/Priorisierungs-Entscheidung, keine Code-Änderung.

**Entscheidung:** Nach Fertigstellung der Infrastruktur (Consent-Code-Manager, Scanner/Drittanbieter-Oberfläche, YouTube-/YOTU-/Maps-Gating, GitHub-Auto-Updates) werden **keine neuen Dienst-Features** entwickelt, bevor LSCC auf **mindestens 5 echten Websites** produktiv analysiert wurde. Ablauf in drei Phasen, dokumentiert/erhoben in `VALIDIERUNG.md`:

- **Phase A — Live-Test:** Pro Site Banner-/Widerruf-Funktion, Auto-Update, Privacy Check, Drittanbieter-Oberfläche (Gating-Status) und **Netzwerk-Monitor vor Consent** (Leak-Prüfung) erfassen; Cache-/Optimierungs-Konflikte notieren.
- **Phase B — Inventur-Auswertung:** Aggregat-Matrix `Dienst | Prävalenz | Risiko | Aktuelle LSCC-Abdeckung | Priorität` über alle Sites. Diese Matrix — nicht Vermutung — bestimmt die nächste Entwicklungsphase.
- **Phase C — wahrscheinlichste nächste Entwicklung (Hypothese):** Vimeo/oEmbed, reCAPTCHA, Calendly, Google-Fonts-Report (Letzteres voraussichtlich nur Reporting). Reihenfolge wird durch Phase B bestätigt oder verworfen.

**Begründung:**

- **Risiko liegt jetzt in der Realität, nicht im Code:** Die offenen Fragen (echte Leaks vor Consent, Cache-/Minify-Konflikte, tatsächliche Dienst-Prävalenz) lassen sich nur auf echten Stacks beantworten — wiederkehrendes „Folgen/offene Punkte" in ADR-23/24/25.
- **Datengetriebene Priorisierung:** Weiterentwicklung an Diensten, die real selten vorkommen, wäre verschwendet. Die Matrix verhindert das.
- **Stand reicht aus:** v0.3.3 ist funktional vollständig genug, um produktiv Daten zu sammeln; kein weiteres Feature ist Voraussetzung für die Validierung.

**Folgen / offene Punkte:** Bekannte Abdeckungslücken vor der Erhebung: reCAPTCHA, Vimeo, Calendly (heute nur Scanner-Erkennung, kein Gating); Google Fonts ist nicht consent-gate-bar (nur Reporting/Local-Hosting-Empfehlung). Diese Lücken sind in `VALIDIERUNG.md` als Matrix-Vorbefüllung hinterlegt und beim Ausfüllen gegen die Realität zu bestätigen. Verbindliche Referenz: `MASTER_HANDBUCH.md`.

## ADR-27: „Bestehende Einstellungen beibehalten" als Default bei darstellungsverändernden Features (Architektur-/UX-Richtlinie, ab 2026-06-20)

**Status:** Aktiv ab 2026-06-20. Architektur-/UX-Richtlinie, **keine** Code-Änderung in v0.5.1. Verbindlich für **alle** künftigen UX-/Design-Features.

**Kontext:** Beim Rollout auf ≈40 Avada-Sites verliert der Betreiber später den Überblick, welche individuelle Darstellung (Farben, Reopen-Position, Design-Optionen) je Site bewusst gesetzt wurde. Ein Feature, das bestehende Darstellung automatisch ändert, übernimmt oder migriert, würde unbemerkt site-spezifische Konfigurationen überschreiben — bei 40 Sites nicht rekonstruierbar.

**Entscheidung:** Jedes Feature, das die bestehende Darstellung beeinflussen könnte (Farben, Position, Layout, Presets, Effekte), MUSS:

1. **Default = bestehende Einstellungen unverändert beibehalten.** Kein Auto-Apply, keine Auto-Migration, keine Auto-Übernahme bei Update oder Aktivierung.
2. **Opt-in nur auf ausdrückliche Operator-Aktion** (Button/Checkbox), nie automatisch, nie als Live-Sync, nie über einen permanenten Hook.
3. **Post-Update-Hinweis statt stiller Änderung** — Muster:
   - „Neue Funktion verfügbar: …"
   - ☑ **Bestehende Einstellungen beibehalten (empfohlen)** ← Vorauswahl/Default
   - ☐ Neue Funktion aktiv nutzen
4. **Fokus = bewusste Beibehaltung vs. bewusste Übernahme**, nicht das Feintuning selbst (z. B. nicht „links/rechts", sondern „aktuelle Konfiguration behalten oder neu setzen").
5. **Reversibel**; keine destruktive Überschreibung ohne expliziten Klick. Standardempfehlung ist **immer** „Bestehende Einstellungen beibehalten".

**Anwendung (Beispiele):**

- **Avada-Farbimport (v0.5.1):** Das Update ändert keine Farben. Nur der Button „Avada-Farben übernehmen" überträgt Werte — auf Wunsch, reversibel.
- **Reopen-Positionen inkl. „Versteckt" (v0.5.0):** Bestehende Position bleibt; neue Werte greifen nur bei aktiver Auswahl. Default `bottom-right`.
- **Design-Presets (geplant):** Default = `classic` (= bisheriges Aussehen); kein automatischer Wechsel.

**Begründung:** Bei 40 Sites ist „nichts ungewollt verändern" wertvoller als Automatik-Komfort. Stille Darstellungsänderungen erzeugen Support-Fälle und Vertrauensverlust; die sichere Beibehaltung ist die einzig vertretbare Vorauswahl. Konsistent mit den ABSOLUTEN NO-GOS (keine automatische Migration) und der Projektphilosophie.

**Folgen / offene Punkte:** Optional ein **wiederverwendbares Admin-Pattern** „Neue Funktion / Funktionsübernahme" (dismissible Notice mit den zwei Optionen, sichere Vorauswahl) als gemeinsame Komponente künftiger Features. **Nicht** Teil von v0.5.1 (dort nur der Avada-Import-Button gemäß dieser Richtlinie). Verbindliche Referenz: `MASTER_HANDBUCH.md`.

## ADR-28: Locale-aware Banner-Anzeige bei Sprachwechsel (umgesetzt in v0.5.4)

**Status:** Aktiv ab v0.5.4 (2026-06-20).

**Kontext:** Auf mehrsprachigen Sites (WPML/Polylang) blieb das Banner nach einem Sprachwechsel verschwunden, sobald ein gültiger Consent existierte — der Besucher sah die Cookie-Informationen nicht in der neu gewählten Sprache. Gleichzeitig darf ein Sprachwechsel **kein** Re-Consent erzwingen.

**Entscheidung:** Das Banner wird **erneut angezeigt**, wenn sich die aktive Front-End-Locale gegenüber der zuletzt angezeigten/bestätigten Locale geändert hat — **ohne** den Consent zu verändern:

- PHP übergibt `locale` (`determine_locale()`, Fallback `get_locale()`) an `mcbSettings`.
- `banner.js` persistiert die zuletzt bestätigte Locale in einem **separaten, leichten** localStorage-Key `mcb_consent_locale` (Metadaten). **`lscc_consent`, Cookie, localStorage-Consent und `MCB_CONSENT_VERSION` bleiben unverändert.**
- Bei gültigem Consent: `currentLocale !== mcb_consent_locale` → Banner erneut anzeigen (Auswahl bleibt vorausgewählt). Update der Locale in `saveAndClose()`. Bestehender Consent **ohne** gespeicherte Locale → aktuelle still übernehmen (kein erzwungenes Wiedererscheinen).

**Begründung:** Erfüllt die rechtliche/UX-Erwartung („Cookie-Infos in der aktuellen Sprache sichtbar") ohne Re-Consent-Welle und ohne Consent-Migration. Konsistent mit ADR-8 (Consent clientseitig) und ADR-27 (keine ungewollte automatische Änderung — der Consent selbst bleibt unangetastet).

**Folgen / offene Punkte:** Greift nur bei verfügbarem localStorage (Fallback: Feature inaktiv, kein Fehler). Locale-Granularität = `determine_locale()` (z. B. `de_CH` vs. `en_US`). Verbindliche Referenz: `MASTER_HANDBUCH.md`.

## ADR-29: Avada/Fusion-Cache nach Farbimport automatisch leeren (umgesetzt in v0.5.9)

**Status:** Aktiv ab v0.5.9 (2026-06-21).

**Kontext:** Der Avada-Farbimport (ADR-27, ab 0.5.8 inkl. Client-Resolver-Fallback) schreibt die Markenfarbe nachweislich korrekt in die DB (`PRIMARY_COLOR_RESOLVED = #1e4884`, `AFTER_UPDATE = #1e4884`). Trotzdem blieb das Banner in der alten Farbe (`#e11d48`), bis Avada-/Browser-Cache manuell geleert wurde. **Root Cause:** Avada/Fusion cacht das generierte Inline-CSS und lieferte die alte CSS-Variable `--lscc-primary:#e11d48` weiter aus. Nicht der Resolver, nicht die DB, nicht die Banner-Ausgabe waren betroffen.

**Entscheidung:** Nach erfolgreichem Import wird der Avada/Fusion-Cache **über Avadas eigene API** geleert — keine eigene Cache-Lösung. `Macs_Cookie_Banner_Avada_Colors::reset_caches()` ruft defensiv die bekannten Einstiegspunkte in Reihenfolge auf: (1) `fusion_reset_all_caches()`, (2) `Fusion_Cache::reset_all_caches()`. Erster vorhandener gewinnt; ist keiner verfügbar → `false`, kein Fehler. Aufruf in `import_avada_colors()` unmittelbar nach `update_option()`. Bei Erfolg Admin-Notice: „Avada-Farben übernommen. Fusion/Avada Cache wurde automatisch geleert."

**Begründung:** Behebt die letzte Lücke der Farbübernahme (kein Ctrl+F5 mehr nötig) mit minimalem, versionssicherem Eingriff. Kein Eingriff am Resolver, an Consent, Locale, Reopen, Presets oder Frontend.

**Folgen / offene Punkte:** Greift nur, wenn die Avada/Fusion-Cache-API existiert (sonst stille Degradation, Farbe ist gespeichert, ggf. weiterhin manueller Flush nötig). Verbindliche Referenz: `MASTER_HANDBUCH.md`.

## ADR-30: Farbimport ausschließlich an die Avada Primary Color binden (umgesetzt in v0.5.10)

**Status:** Aktiv ab v0.5.10 (2026-06-21). Verschärft/ersetzt die Farbquellen-Logik aus ADR-27 (Importauslösung bleibt unverändert: nur auf Klick).

**Kontext (bewiesen):** Der Import übernahm nicht die aktuell aktive Primary Color. `get_brand_color()` lief eine Prioritätskette `BRAND_KEYS = primary_color → accent_color → link_color → button_gradient_top_color` ab und löste `var(--awb-colorN)` **positionsbasiert** über die Palette auf. Nachdem die Avada Primary Color von `var(--awb-color5)` (= `#1e4884`) auf den direkten Hex `#2ecc4e` geändert wurde, übernahm das Banner weiterhin `#1e4884`: Sobald `primary_color` serverseitig nicht als direkter Hex auflöste, fiel die Kette auf `accent_color`/`link_color`/`button_gradient_top_color` zurück, die noch `var(--awb-color5)` → 5. Palette-Eintrag „Dark Blue" `#1e4884` lieferten. Der Client-Fallback scannte dieselben Sekundärschlüssel und lieferte ebenfalls `#1e4884`.

**Entscheidung (Fachregel):** Das Banner übernimmt **ausschließlich** die aktuell aktive Avada **Primary Color**. **Kein** `accent_color`/`link_color`/`button_gradient_top_color`, **kein** Palette-/`awb-colorN`-Matching, **keine** Brand-Key-Prioritätskette.

- `import_avada_colors()` nutzt `resolve_primary( read_raw('primary_color') )` statt `get_brand_color()`.
- `resolve_primary()` akzeptiert nur einen **direkten** Farbwert (`#hex` oder `rgb()/rgba()` via `color_value_to_hex()`); eine `var(--…)`-Referenz ergibt bewusst ''.
- Client-Fallback (`get_brand_css_vars()`) scannt **nur** `primary_color`. Greift ausschließlich, wenn `primary_color` selbst eine `var(--awb-colorX)` ist, die der Server nicht auflöst → der Browser löst genau diese Variable auf. Bei direktem Hex: kein Fallback.

**Begründung:** Direkte, vorhersagbare Bindung an die eine Farbe, die der Betreiber als Primary Color setzt. Beseitigt das positionsbasierte Palette-Matching als Fehlerquelle vollständig.

**Folgen / offene Punkte:** `get_brand_color()`, `resolve_color()`, `get_palette()`, `BRAND_KEYS` bleiben im Code vorhanden, werden vom Import aber **nicht mehr aufgerufen** (Legacy, später entfernbar). Cache-Reset (ADR-29), `map_to_banner`, Speicherung, Consent, Locale, Reopen, Presets, Frontend, Scanner, CCM, Updater unverändert. Verbindliche Referenz: `MASTER_HANDBUCH.md`.

## ADR-31: Sichtbarer Reopen-/Settings-Button folgt der Primary Color in ALLEN Presets (umgesetzt in v0.5.11)

**Status:** Aktiv ab v0.5.11 (2026-06-21).

**Kontext (bewiesen):** Import, Speicherung, DB und `get_options()` liefern die importierte Primary Color nachweislich korrekt (`#2ecc4e`). Der sichtbare Button blieb dennoch in der alten Farbe. CSS-/Render-Analyse: Im **Classic-Preset** (Default `design_preset = classic`) war die Button-**Füllung** nicht an `--lscc-primary` gebunden — `.lscc-reopen` nutzte `background: var(--lscc-bg)` (= `background_color`), `.lscc-settings-button` nutzte `background: var(--lscc-secondary)` (= `secondary_button_color`); nur Modern/Premium banden die Füllung an `var(--lscc-primary)`. Der Avada-Import schreibt aber `primary_button_color`/`border_color`/`primary_text_color`, also Variablen, die der sichtbare Classic-Button für die Füllung nicht verwendet → die importierte Farbe konnte im Default-Preset nicht sichtbar werden.

**Entscheidung:** Der sichtbare Cookie-Einstellungen-/Reopen-Button folgt in **allen** Presets der importierten Primary Color. Die Basis-Regeln `.lscc-reopen` und `.lscc-settings-button` in `assets/css/banner.css` nutzen jetzt `background: var(--lscc-primary)`, `border-color: var(--lscc-primary)`, `color: var(--lscc-primary-text)` (WCAG-Auto-Kontrast aus dem Import). Modern/Premium-Overrides bleiben unverändert (gleiche Füllvariable + ihr spezifisches Radius/Shadow).

**Begründung:** Erfüllt die Erwartung „Button trägt die Markenfarbe" unabhängig vom Preset. Rein CSS-seitig, keine Render-/Datenänderung.

**Folgen / offene Punkte:** Reine Darstellung; Consent, Locale, Scanner, CCM, Updater, Avada-Import, Cache-Reset unberührt. Mit v0.5.11 wurden zudem die temporären Runtime-Proofs `0.5.10-debug2` (Admin-Speicherkette) und `0.5.10-debug3` (Frontend-Box) wieder entfernt. Verbindliche Referenz: `MASTER_HANDBUCH.md`.

## ADR-32: Avada Auto-Sync — opt-in, der User entscheidet (umgesetzt in v0.5.12)

**Status:** Aktiv ab v0.5.12 (2026-06-21). Baut auf ADR-27 (Opt-in, „bestehende Einstellungen beibehalten"), ADR-29 (Cache-Reset), ADR-30 (nur `primary_color`) und ADR-31 (sichtbarer Button) auf.

**Kontext:** Bisher war die Avada-Farbübernahme rein manuell (Klick). Gewünscht ist, dass der Betreiber **selbst entscheidet**, ob das Banner dauerhaft der Avada Primary Color folgt (Auto-Sync) oder die Farben manuell verwaltet werden — ohne dass Updates jemals ungefragt manuelle Farben überschreiben.

**Entscheidung:**
- **Persistente Entscheidung** in zwei eigenständigen Optionen (außerhalb von `lscc_options`, damit die Farb-/Sanitize-Logik unberührt bleibt): `mcb_avada_autosync` (`on`/`off`, Default `off`) und `mcb_avada_sync_decided` (`1`, sobald entschieden).
- **Erstabfrage:** Solange Avada aktiv ist und noch keine Entscheidung getroffen wurde (erste Erkennung **oder** nach Update ohne frühere Entscheidung), erscheint eine Admin-Notice mit „Ja, automatisch synchronisieren" / „Nein, manuell verwalten". Antwort setzt beide Optionen; bei „Ja" wird sofort synchronisiert.
- **Einstellung** im Bereich *Avada-Integration*: Checkbox „Banner-Farben automatisch mit Avada synchronisieren" + Button „Jetzt synchronisieren" (= bestehender manueller Import, inkl. Client-Resolver-Fallback).
- **Auto-Sync EIN:** Bei jedem Admin-Load (`admin_init`, `manage_options`) gleicht `run_avada_sync()` die Avada Primary Color mit der Banner-Farbe ab und übernimmt Abweichungen automatisch (mit Cache-Reset, ADR-29). Server-seitig, read-only auf Avada.
- **Auto-Sync AUS:** Keine automatische Änderung — Farben bleiben vollständig manuell.

**Wichtige Regel:** `maybe_auto_sync()` läuft **ausschließlich**, wenn Auto-Sync aktiv ist. Bei AUS wird **nie** automatisch überschrieben — Updates lassen manuelle Bannerfarben unangetastet.

**Begründung:** Klares Opt-in, ein Schalter, keine fragilen Avada-Save-Hooks (Abgleich beim Admin-Besuch statt Event-Hook). Wiederverwendung der bestehenden Bausteine (`resolve_primary()`, `map_to_banner()`, `reset_caches()`); die manuelle Importfunktion `import_avada_colors()` bleibt **unverändert**.

**Folgen / offene Punkte:** `run_avada_sync()` ist serverseitig — eine Primary Color als `var(--awb-colorX)`, die nur im Browser auflöst, kann der Auto-Sync nicht still übernehmen (Status `unresolved`); der manuelle „Jetzt synchronisieren"-Button (mit Client-Fallback) deckt diesen Fall ab. Keine Änderung an Consent, Locale, Scanner, CCM, Updater, Presets oder der bestehenden Importlogik. Verbindliche Referenz: `MASTER_HANDBUCH.md`.
