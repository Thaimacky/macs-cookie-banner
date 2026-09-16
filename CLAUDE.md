# CLAUDE.md — Projektregeln für Mac's Cookie Banner

Diese Datei wird von Claude Code beim Öffnen des Repositories automatisch geladen.
Sie ist die **versionierte** Quelle der dauerhaften Projektregeln und ersetzt die
früheren, nur lokal auf einem einzelnen Rechner gespeicherten Claude-Memories.

> **Grundsatz:** Dauerhafte Projektregeln dürfen niemals ausschliesslich im lokalen
> Claude-Memory eines Rechners liegen. Sie gehören hierher, damit jeder frisch
> geklonte Rechner (Desktop wie Laptop) denselben Stand hat.
> Rechnerwechsel-Ablauf: siehe `MULTI_PC_SETUP.md`.

---

## 1. Vorrangs-Quelle und Pflichtlektüre

`MASTER_HANDBUCH.md` (früher `CLAUDE_CONTINUITY_MASTER.md`, umbenannt 2026-05-28 in
v0.1.5-test, Inhalt vollständig erhalten) ist **Pflichtlektüre und Vorrangs-Quelle**.

Sie hat Vorrang bei: Architekturentscheidungen, Scope-Kontrolle, Performance-Philosophie,
Consent-Philosophie, WPML-/Polylang-Zielen, Vermeidung von Overengineering.

**Pflicht-Lese-Reihenfolge vor jeder Änderung:**

1. `MASTER_HANDBUCH.md`
2. `PROJECT_BRIEF.md`
3. `ACTIVE_CODE_MAP.md`
4. `DECISIONS.md`
5. `DEV_LOG.md`

Regeln zur Master-Datei:

* Bei Konflikt mit anderen Doku-Files **gewinnt die Master-Datei**.
* **Niemals umbenennen** ohne Hinweis im `DEV_LOG.md` und ohne Aktualisierung aller Referenzen.
* **Niemals überschreiben oder kürzen.** Nur additive Erweiterungen mit klarer Versionshistorie.
* Format: echtes UTF-8 Markdown, BOM-frei.

---

## 2. Bewusste Architekturregeln (sehen aus wie Lücken, sind aber Absicht)

Dieses Plugin folgt einer konservativen Architektur. Die folgenden „Lücken" sind
**bewusste Designentscheidungen, keine Bugs**:

1. **Normale `<script>`-Tags werden NICHT pauschal automatisch blockiert oder
   umgeschrieben.** Grundmodell bleibt: nur Skripte mit `type="text/plain"` und
   `data-cookie-category` werden vom Banner verwaltet. Nicht „verbessern" durch
   generischen MutationObserver, Vendor-Listen oder Auto-Rewrite aller Skripte.

   *Bewusste, eng begrenzte Ausnahme (v1.0.7, ADR-40):*
   `includes/google-tracking-shield.php` gated GA4/UA/GTM/Google-Ads-Skripte
   quellenunabhängig im gerenderten Frontend-HTML — weil diese Pfade nicht über
   `wp_enqueue_script()` laufen und daher für die `script_loader_tag`-Module
   strukturell unsichtbar sind. Das Modul ist **per Option zuschaltbar** und
   zusätzlich per Konstante `MCB_DISABLE_GOOGLE_SHIELD` hart abschaltbar.
   Es ist ausdrücklich **kein** Einstieg in generische Universal Tracking
   Protection: keine generische Vendor-Liste, kein Netzwerk-Guard, kein CSP.
   Diese Ausnahme nicht als Präzedenzfall für weitere Auto-Rewrites verwenden.
2. **Bestehende iframes werden NICHT automatisch erkannt oder ersetzt.**
   Service-Komponenten (`[lscc_youtube]`, `[lscc_vimeo]`, `[lscc_google_map]`) wirken
   ausschliesslich auf bewusst eingebundene Shortcodes.
   *Ausnahme, bewusst und opt-in:* die Avada-/YOTU-Kompatibilitätsmodule
   (`includes/avada-*.php`, `includes/meta-social-compat.php`) fangen auf der
   Render-Layer-Ebene ab; sie sind per Einstellung abschaltbar (siehe `DECISIONS.md`).
3. **Privacy Check ist passiv.** Einmalige Prüfung via `wp_remote_get` mit statischer
   Mustererkennung — kein Crawl, keine Auto-Blockierung, keine Auto-Hinweise im Frontend.
4. **Keine externen Libraries, kein Build-System für den Plugin-Code.**
   Vanilla JS, plain PHP, plain CSS. Kein React/Vue/jQuery, kein npm/Composer, keine CDNs.
   (`tools/build-zip.ps1` ist ein reines Verpackungsskript, kein Build-System —
   es kompiliert nichts und erzeugt keine Artefakte im Plugin-Code.)
5. **Default = nur notwendige Cookies.** „Nur notwendige" und „Alle akzeptieren"
   müssen gleichwertig erreichbar sein.

**Warum:** Diese Regeln sind die zentrale Differenzierung gegenüber Real Cookie Banner
und vergleichbaren Suiten. Verstösse zerstören den Projektzweck.

**Anwendung:**

* Bei jeder Code-Änderung prüfen, ob sie eine dieser Regeln verletzt. Wenn ja:
  **nicht implementieren, sondern Rückfrage stellen.**
* Nicht „kreativ" werden mit Auto-Erkennung, Auto-Ersetzung, MutationObserver-Magie,
  GeoIP, Vendor-Listen, IAB TCF.
* WordPress-Sanitizing/Escaping immer verwenden: `esc_html`, `esc_attr`,
  `sanitize_text_field`, `sanitize_hex_color`, `wp_nonce_field`, `current_user_can`,
  `ABSPATH`-Check.

---

## 3. Projektlayout (wichtig: Repo liegt NICHT im Projekt-Root)

Der Projekt-Ordner, den der User in der IDE öffnet, ist **nicht** das Git-Repository.
Das Repository liegt eine Ebene tiefer im Plugin-Ordner:

```
<Projekt-Ordner>\                        <- KEIN Git-Repo
└─ light-swiss-cookie-consent\           <- HIER liegt .git\  (= Repository-Root)
   ├─ macs-cookie-banner.php             <- Hauptdatei
   ├─ MASTER_HANDBUCH.md, DECISIONS.md, ...
   ├─ includes\, assets\, languages\
   ├─ tools\build-zip.ps1
   └─ _BUILD_OUTPUT\                     <- gitignored
```

* Der **Ordnername `light-swiss-cookie-consent`** ist historisch (alter Produktname)
  und bleibt bewusst stehen. Das Plugin heisst seit v0.4.0 **Mac's Cookie Banner**,
  die Hauptdatei `macs-cookie-banner.php`, der WordPress-Slug `macs-cookie-banner`.
  Slug und ZIP-Ordnername müssen `macs-cookie-banner` bleiben (In-place-Auto-Update).
* **Alle** gepflegten Dokumente liegen im Repository-Ordner, nicht im Projekt-Root.
* Git-Befehle deshalb immer im Repository-Ordner ausführen (oder mit `git -C <repo>`).

---

## 4. Arbeits- und Lieferregeln (Kurzfassung — Details im MASTER_HANDBUCH)

* **Schweizer Schreibweise: KEIN `ß`.** Umlaute sind ausdrücklich erlaubt.
  Ausnahme: `.ps1`-Skripte werden **rein ASCII** gehalten (Windows PowerShell 5.1
  liest BOM-lose Dateien als ANSI und bricht sonst beim Parsen ab).
* **Commit-Philosophie:** 1 Feature = 1 Commit. Doku separat. Architektur separat.
* **Git-Remote niemals hartcodieren.** Vor jedem Push/Pull/Fetch/Tag zuerst
  `git remote -v` und den tatsächlichen Namen verwenden.
* **Kopiermarkierung ist Pflicht** für Berichte und Prompts (exakter Markertext im
  MASTER_HANDBUCH, Sektionen „PFLICHT: KOPIERMARKIERUNG FÜR BERICHTE" / „... FÜR PROMPTS").
* **Zweiphasiger Release-Workflow** ist verbindlich: Phase 1 endet mit
  „Produktions-ZIP bereit zum Test." — kein Tag, kein Release, kein Asset.
  Phase 2 erst nach ausdrücklicher Freigabe des Users.
* **Reine Doku-Änderungen werden standardmässig abgeschlossen** (Validierung → Commit →
  `git remote -v` → Push). „Nicht committet, da nicht angefragt" ist unzulässig.
* **Marcel ist Entscheider und Tester, nicht Debug-Operator.** Keine Arbeitsverlagerung
  auf den User, keine Server-Schnitzeljagden.

---

## 5. Build-/ZIP-Regeln

* ZIPs werden **ausschliesslich** mit `tools/build-zip.ps1` erzeugt — nie von Hand,
  nie mit `Compress-Archive` (verletzt die Forward-Slash-Pflicht).
* Ausgabeordner ist **immer** `_BUILD_OUTPUT\` **relativ zum Repository** (gitignored).
* Test-ZIPs tragen **immer die Version im Dateinamen**:
  `macs-cookie-banner-vX.Y.Z-test.zip`. Versionslose Test-ZIPs sind unzulässig.
* **Keine festen Laufwerkspfade** (`G:\`, `D:\`, `F:\`) in Skripten oder Anleitungen.
* Jeder Bericht zu einem erzeugten Artefakt enthält den Pflichtblock
  **ZIP-DATEI** mit vollständigem absolutem Pfad, Dateiname, Grösse und SHA-256.
