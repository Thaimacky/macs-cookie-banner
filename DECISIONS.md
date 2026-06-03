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
