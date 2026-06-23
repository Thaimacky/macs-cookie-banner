# Release Guide — Mac's Cookie Banner

Kurzanleitung, um ein neues Release über GitHub auszuliefern (Auto-Update für alle
WordPress-Sites). Reproduzierbar auf jedem Windows-Laptop mit Git + PowerShell.

Voraussetzungen (einmalig am Laptop):
- Git installiert, Git-Auth für GitHub eingerichtet (PAT oder SSH) — `git push` muss funktionieren.
- Repo geklont: `git clone https://github.com/Thaimacky/macs-cookie-banner.git`
  (Repo ist **public** → kein Token zum Lesen/Updaten der Sites nötig.)
- Remote-Name in dieser Arbeitskopie ist `macs` (siehe `git remote -v`); bei frischem Clone heisst er `origin` — Befehle entsprechend anpassen.

## 1. Version setzen
- In `macs-cookie-banner.php`: Header `Version:` **und** `define( 'MCB_VERSION', ... )` auf die neue Version (z. B. `1.0.6`).
- Doku aktualisieren (CHANGELOG.md, DEV_LOG.md, ggf. ACTIVE_CODE_MAP/MASTER_HANDBUCH/RELEASE_CHECKLIST/DECISIONS).
- Committen.

## 2. Push
```
git push macs main
```

## 3. Tag (Tag = Version; PUC vergleicht den Tag)
```
git tag -a 1.0.6 -m "v1.0.6"
git push macs 1.0.6
```
Falls der Tag schon existiert: NICHT blind ueberschreiben — Zustand pruefen.

## 4. Release-ZIP bauen (NICHT Compress-Archive — Forward-Slash-Pflicht)
```
$repo = "G:\Cookie Banner Plugin\light-swiss-cookie-consent"   # ggf. Laptop-Pfad anpassen
$zip  = "G:\Cookie Banner Plugin\macs-cookie-banner.zip"
Push-Location $repo; $files = git ls-files; Pop-Location
Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
$fs = [System.IO.File]::Open($zip, [System.IO.FileMode]::Create)
$a  = New-Object System.IO.Compression.ZipArchive($fs, [System.IO.Compression.ZipArchiveMode]::Create)
foreach ($f in $files) {
  $s = Join-Path $repo $f
  if (Test-Path -LiteralPath $s) {
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($a, $s, 'macs-cookie-banner/' + ($f -replace '\\','/'), [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
  }
}
$a.Dispose(); $fs.Dispose()
```
ZIP-Anforderung: Asset-Name `macs-cookie-banner.zip`, Top-Level-Ordner `macs-cookie-banner/`,
Hauptdatei `macs-cookie-banner/macs-cookie-banner.php`, ausschliesslich Forward-Slash-Pfade,
nur `git ls-files` (kein `.git/`, kein `.claude/`).

## 5. GitHub-Release erstellen (Web-UI)
GitHub -> Repo -> Releases -> "Draft a new release":
- Choose a tag: `1.0.6`
- Title: `1.0.6`
- Notes: Kurzfassung aus CHANGELOG.md
- Attach binaries: `macs-cookie-banner.zip` (genau EIN ZIP; NICHT das "Source code"-ZIP, NICHT ein altes light-swiss-cookie-consent-ZIP)
- Publish release.

(Optional mit GitHub CLI: `gh release create 1.0.6 "...\macs-cookie-banner.zip" -t 1.0.6 -F notes.md`.)

## 6. Update testen (eine Testsite, vor breitem Rollout)
1. Vorversion installieren/aktivieren, ein paar Einstellungen setzen.
2. WP -> Dashboard -> Aktualisierungen -> "Nach Updates suchen" (PUC pollt sonst ~12 h).
3. "Mac's Cookie Banner" zeigt das Update -> "Aktualisieren".
4. Pruefen: Plugin aktiv, Version korrekt, Einstellungen erhalten, keine Fehler, kein "Plugindatei existiert nicht".

## Hinweise
- Updates werden aus dem **Release-Asset-ZIP** gezogen (`enableReleaseAssets`), nicht aus dem Source-Archiv.
- Plugin-Slug/Ordner `macs-cookie-banner` muss stabil bleiben (Auto-Update in-place).
- DB-Keys (`lscc_*`) bleiben ueber Updates erhalten -> keine Datenverluste.
