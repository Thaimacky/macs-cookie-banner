# Release Guide — Mac's Cookie Banner

Kurzanleitung, um ein neues Release über GitHub auszuliefern (Auto-Update für alle
WordPress-Sites). Reproduzierbar auf jedem Windows-Laptop mit Git + PowerShell.

> **VERBINDLICH — zweiphasiger Release-Workflow** (MASTER_HANDBUCH „PFLICHT: ZWEIPHASIGER RELEASE-WORKFLOW"):
> **Phase 1** (Schritte 1–4) = Version setzen, committen, pushen, **Produktions-ZIP bauen + verifizieren + dem User zum Test bereitstellen**. Hier **KEIN** Tag, **KEIN** Release, **KEIN** Asset-Upload. Auftrag endet mit „Produktions-ZIP bereit zum Test."
> **Phase 2** (Schritte 3.-Tag, 5, 5b) = **erst nach ausdrücklicher User-Freigabe** („Release freigegeben" / „Jetzt veröffentlichen"): Tag, Release, genau ein Asset, API-Verifikation.
> Das getestete ZIP **muss byte-identisch** mit dem Release-ZIP sein. Jede Dateiänderung nach dem Test ⇒ Phase 1 neu mit neuem ZIP.

Voraussetzungen (einmalig am Laptop):
- Git installiert, Git-Auth für GitHub eingerichtet (PAT oder SSH) — `git push` muss funktionieren.
- Repo geklont (Ordnername bewusst festlegen, siehe `MULTI_PC_SETUP.md`):
  `git clone https://github.com/Thaimacky/macs-cookie-banner.git light-swiss-cookie-consent`
  (Repo ist **public** → kein Token zum Lesen/Updaten der Sites nötig.)
- **Remote-Name NIEMALS annehmen** (verbindlich, MASTER_HANDBUCH „PFLICHT: GIT-REMOTE NIEMALS HARTCODIEREN"). Vor jedem Push/Tag zuerst ermitteln und in den folgenden Befehlen `<REMOTE>` durch den echten Namen ersetzen:
  ```
  git remote -v
  ```
  (Je nach Arbeitskopie z. B. `origin` oder `macs`.)

## 1. Version setzen
- In `macs-cookie-banner.php`: Header `Version:` **und** `define( 'MCB_VERSION', ... )` auf die neue Version (z. B. `1.0.6`).
- Doku aktualisieren (CHANGELOG.md, DEV_LOG.md, ggf. ACTIVE_CODE_MAP/MASTER_HANDBUCH/RELEASE_CHECKLIST/DECISIONS).
- Committen.

## 2. Push
```
git push <REMOTE> main
```

## 3. Tag (Tag = Version; PUC vergleicht den Tag)
```
git tag -a 1.0.6 -m "v1.0.6"
git push <REMOTE> 1.0.6
```
Falls der Tag schon existiert: NICHT blind ueberschreiben — Zustand pruefen.

## 4. Release-ZIP bauen (Skript — keine festen Laufwerkspfade)

ZIPs werden **ausschliesslich** mit dem versionierten Build-Skript erzeugt. Es ermittelt
den Repository-Pfad selbst, laeuft dadurch auf **jedem** Rechner unveraendert (Desktop wie
Laptop) und benoetigt **keine** Pfadanpassung mehr.

Im Repository-Ordner ausfuehren:

```
powershell -ExecutionPolicy Bypass -File toolsuild-zip.ps1                 # Test-ZIP  (Phase 1)
powershell -ExecutionPolicy Bypass -File toolsuild-zip.ps1 -Kind release   # Release-ZIP (Phase 2)
```

Ausgabeordner ist **immer** `_BUILD_OUTPUT\` **relativ zum Repository** (gitignored) —
nie der Projekt-Root, nie ein fester Pfad wie `G:\...`, `D:\...` oder `F:\...`.

Dateinamen (vom Skript gesetzt, nicht von Hand):

| Aufruf | Dateiname |
|---|---|
| `-Kind test` (Standard) | `macs-cookie-banner-vX.Y.Z-test.zip` |
| `-Kind debug` | `macs-cookie-banner-vX.Y.Z-debug.zip` |
| `-Kind release` | `macs-cookie-banner.zip` (Pflichtname des Release-Assets) |

**Test-ZIPs tragen immer die Version im Dateinamen.** Versionslose Test-ZIPs sind unzulaessig.

Das Skript erledigt automatisch:

- Version aus `define( 'MCB_VERSION', ... )` lesen und **gegen den Plugin-Header pruefen**
  (Abbruch bei Mismatch — verhindert falsch versionierte Releases),
- Dateiliste strikt aus `git ls-files` (kein `.git/`, kein `.claude/`, kein `_BUILD_OUTPUT/`),
- Top-Level-Ordner `macs-cookie-banner/` und ausschliesslich Forward-Slash-Pfade
  (deshalb **kein** `Compress-Archive`),
- Warnung, wenn der Arbeitsbaum nicht sauber ist,
- Ausgabe von **vollstaendigem absolutem Pfad, Dateiname, Groesse und SHA-256** im
  Pflichtformat des MASTER_HANDBUCH (Sektion „PFLICHT: VOLLSTAENDIGER ZIP-DATEIPFAD IN
  BERICHTEN") — diese Angaben gehoeren unveraendert in den Abschlussbericht.

ZIP-Anforderung (vom Skript garantiert): Asset-Name `macs-cookie-banner.zip`,
Top-Level-Ordner `macs-cookie-banner/`, Hauptdatei
`macs-cookie-banner/macs-cookie-banner.php`, ausschliesslich Forward-Slash-Pfade.

## 5. GitHub-Release erstellen (Web-UI)
GitHub -> Repo -> Releases -> "Draft a new release":
- Choose a tag: `1.0.6`
- Title: `1.0.6`
- Notes: Kurzfassung aus CHANGELOG.md
- Attach binaries: `macs-cookie-banner.zip` (genau EIN ZIP; NICHT das "Source code"-ZIP, NICHT ein altes light-swiss-cookie-consent-ZIP)
- Publish release.

(Optional mit GitHub CLI: `gh release create 1.0.6 "...\macs-cookie-banner.zip" -t 1.0.6 -F notes.md`.)

## 5b. Release-Verifikation (PFLICHT — Tag ≠ veröffentlichtes Release)
Ein Release gilt **erst dann als abgeschlossen**, wenn die öffentliche Kette verifiziert ist (MASTER_HANDBUCH „PFLICHT: RELEASE-VERIFIKATION"). Ein **Draft** verhält sich für PUC wie ein **nicht existierendes** Release. Prüfen (read-only, ohne Token):
```
# Muss das Release liefern, draft=false, genau EIN .zip-Asset:
curl -s https://api.github.com/repos/Thaimacky/macs-cookie-banner/releases/tags/1.0.6
# Muss exakt 1.0.6 liefern:
curl -s https://api.github.com/repos/Thaimacky/macs-cookie-banner/releases/latest
```
Checkliste: (1) Tag auf GitHub vorhanden, (2) Release **veröffentlicht** (nicht Draft), (3) **genau ein** ZIP-Asset, (4) Asset = Produktions-ZIP (kein Source-/Test-ZIP), (5) API liefert das neue Release als `latest`. Erst danach dürfen „Auto-Update bereit" / „Rollout bereit" / „Produktionsfreigabe" ausgesprochen werden.

## 6. Update testen (PFLICHT-Gate vor jedem Vollrollout)
**Verbindlich** (MASTER_HANDBUCH „PFLICHT: VOLLROLLOUT ERST NACH ERFOLGREICHEM UPDATE-TEST"): Ein veröffentlichtes Release ist **nicht** automatisch produktionsreif. Vor jedem Vollrollout zuerst dieser Auto-Update-Test:
1. Vorversion installieren/aktivieren, ein paar Einstellungen setzen.
2. WP -> Dashboard -> Aktualisierungen -> "Nach Updates suchen" (PUC pollt sonst ~12 h).
3. "Mac's Cookie Banner" zeigt das Update -> "Aktualisieren".
4. Pruefen: **Update wird gefunden**, Update läuft fehlerfrei, Plugin bleibt aktiv, Version korrekt, Einstellungen erhalten, **keine PHP-/JS-Fehler**, kein "Plugindatei existiert nicht".

**Erst nach bestandenem Test** darf „bereit für 40 Websites" / Vollrollout empfohlen werden — vorher niemals.

## Hinweise
- Updates werden aus dem **Release-Asset-ZIP** gezogen (`enableReleaseAssets`), nicht aus dem Source-Archiv.
- Plugin-Slug/Ordner `macs-cookie-banner` muss stabil bleiben (Auto-Update in-place).
- DB-Keys (`lscc_*`) bleiben ueber Updates erhalten -> keine Datenverluste.
