<#
.SYNOPSIS
    Baut das Plugin-ZIP fuer Mac's Cookie Banner - rechnerunabhaengig.

.DESCRIPTION
    Erzeugt ein ZIP ausschliesslich aus `git ls-files` (kein .git/, kein .claude/,
    keine Build-Artefakte). Top-Level-Ordner im ZIP ist immer `macs-cookie-banner/`,
    alle Pfade mit Forward-Slash (Pflicht laut RELEASE_GUIDE.md).

    Der Ausgabeordner ist IMMER `_BUILD_OUTPUT\` relativ zum Repository-Root.
    Es werden KEINE festen Laufwerksbuchstaben (G:, D:, F:) verwendet - das Skript
    ermittelt den Repo-Pfad selbst und laeuft so auf Desktop und Laptop identisch.

    Die Version wird aus `define( 'MCB_VERSION', ... )` gelesen, nicht geraten.

.PARAMETER Kind
    test    -> macs-cookie-banner-vX.Y.Z-test.zip   (Standard, Phase 1)
    release -> macs-cookie-banner.zip               (Phase 2, Release-Asset-Name)
    debug   -> macs-cookie-banner-vX.Y.Z-debug.zip

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tools\build-zip.ps1
    powershell -ExecutionPolicy Bypass -File tools\build-zip.ps1 -Kind release
#>

[CmdletBinding()]
param(
    [ValidateSet('test', 'release', 'debug')]
    [string]$Kind = 'test'
)

$ErrorActionPreference = 'Stop'

# --- Repo-Root selbst ermitteln (Skript liegt in <repo>\tools\) -------------
$repo = Split-Path -Parent $PSScriptRoot
$mainFile = Join-Path $repo 'macs-cookie-banner.php'

if (-not (Test-Path -LiteralPath $mainFile)) {
    throw "Hauptdatei nicht gefunden: $mainFile - liegt das Skript in <repo>\tools\ ?"
}

# --- Version aus MCB_VERSION lesen -----------------------------------------
$versionLine = Select-String -LiteralPath $mainFile -Pattern "define\(\s*'MCB_VERSION'\s*,\s*'([^']+)'" | Select-Object -First 1
if (-not $versionLine) { throw "MCB_VERSION nicht in $mainFile gefunden." }
$version = $versionLine.Matches[0].Groups[1].Value

# --- Header-Version gegenpruefen (muss identisch sein) ---------------------
$headerLine = Select-String -LiteralPath $mainFile -Pattern '^\s*\*\s*Version:\s*(\S+)' | Select-Object -First 1
if ($headerLine) {
    $headerVersion = $headerLine.Matches[0].Groups[1].Value
    if ($headerVersion -ne $version) {
        throw "Versions-Mismatch: Plugin-Header '$headerVersion' != MCB_VERSION '$version'. Erst korrigieren."
    }
}

# --- Ausgabeordner: IMMER <repo>\_BUILD_OUTPUT\ ----------------------------
$outDir = Join-Path $repo '_BUILD_OUTPUT'
if (-not (Test-Path -LiteralPath $outDir)) {
    New-Item -ItemType Directory -Path $outDir | Out-Null
}

switch ($Kind) {
    'release' { $zipName = 'macs-cookie-banner.zip' }
    'debug'   { $zipName = "macs-cookie-banner-v$version-debug.zip" }
    default   { $zipName = "macs-cookie-banner-v$version-test.zip" }
}
$zipPath = Join-Path $outDir $zipName

# --- Dateiliste strikt aus git ls-files ------------------------------------
Push-Location $repo
try {
    $files = & git ls-files
    if ($LASTEXITCODE -ne 0) { throw 'git ls-files fehlgeschlagen - ist dies ein Git-Repository?' }

    $dirty = & git status --porcelain

    # Commit-Zeitstempel von HEAD - macht das ZIP reproduzierbar: derselbe Commit
    # ergibt auf jedem Rechner dasselbe ZIP. Ohne dies wuerden die Mtimes der
    # Arbeitskopie einfliessen und Desktop-/Laptop-ZIPs waeren nie byte-identisch.
    $commitIso = & git log -1 --format=%cI
} finally {
    Pop-Location
}

if ($commitIso) {
    $entryStamp = [DateTimeOffset]::Parse($commitIso)
} else {
    # Kein Commit vorhanden (frisches Repo): fester Ersatzwert statt Arbeitskopie-Mtime.
    $entryStamp = [DateTimeOffset]::new(2020, 1, 1, 0, 0, 0, [TimeSpan]::Zero)
}

if ($dirty) {
    Write-Warning 'Arbeitsbaum ist NICHT sauber. Das ZIP enthaelt den Stand der Dateien auf der Platte,'
    Write-Warning 'die Dateiliste stammt aber aus git ls-files. Vor einem Release erst committen.'
}

if (-not $files) { throw 'git ls-files lieferte keine Dateien.' }

# --- ZIP schreiben (kein Compress-Archive: Forward-Slash-Pflicht) ----------
if (Test-Path -LiteralPath $zipPath) { Remove-Item -LiteralPath $zipPath -Force }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$fs = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create)
try {
    $archive = New-Object System.IO.Compression.ZipArchive($fs, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        $added = 0
        foreach ($f in $files) {
            $source = Join-Path $repo $f
            if (-not (Test-Path -LiteralPath $source)) { continue }
            $entryName = 'macs-cookie-banner/' + $f.Replace([char]92, '/')
            $entry = [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $archive, $source, $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal)
            $entry.LastWriteTime = $entryStamp
            $added++
        }
    } finally {
        $archive.Dispose()
    }
} finally {
    $fs.Dispose()
}

# --- Pflichtangaben laut MASTER_HANDBUCH ausgeben --------------------------
$item = Get-Item -LiteralPath $zipPath
$hash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash
$sizeKb = [math]::Round($item.Length / 1KB, 1)

Write-Output ''
Write-Output '================================================================================'
Write-Output 'ZIP-DATEI'
Write-Output '================================================================================'
Write-Output ''
Write-Output 'Vollstaendiger Dateipfad:'
Write-Output $item.FullName
Write-Output ''
Write-Output 'Dateiname:'
Write-Output $item.Name
Write-Output ''
Write-Output 'Groesse:'
Write-Output ("{0:N0} Bytes ({1} KB)" -f $item.Length, $sizeKb)
Write-Output ''
Write-Output 'SHA-256:'
Write-Output $hash
Write-Output ''
Write-Output '================================================================================'
Write-Output ("Version: $version | Dateien im ZIP: $added | Typ: $Kind")
Write-Output ("Reproduzierbar: alle Eintraege auf Commit-Zeit " + $entryStamp.ToString('u'))
Write-Output '================================================================================'
