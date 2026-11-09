<#
.SYNOPSIS
    Baut das Plugin-ZIP fuer Mac's Cookie Banner - rechnerunabhaengig.

.DESCRIPTION
    Erzeugt ein ZIP aus `git ls-files`, gefiltert durch eine POSITIVLISTE (ADR-45,
    v1.1.1): ins ZIP kommt nur, was das Plugin zur Laufzeit braucht - Hauptdatei,
    uninstall.php / readme.txt (falls vorhanden), includes/, assets/ und
    languages/*.mo|*.po|*.pot. Entwickler-Dateien (*.md, Punkt-Dateien, tools/,
    composer.json ...) kommen NIE mit, weil der Plugin-Ordner auf den Websites
    oeffentlich per URL abrufbar ist. Nach dem Schreiben wird das fertige ZIP
    erneut geoeffnet und hart geprueft; bei einem Verstoss wird es geloescht und
    der Build bricht ab.

    Top-Level-Ordner im ZIP ist immer `macs-cookie-banner/`, alle Pfade mit
    Forward-Slash (Pflicht laut RELEASE_GUIDE.md).

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

# --- Positivliste + Sperrliste (ADR-45) ---------------------------------------
# Positivliste: nur Laufzeit-Dateien des Plugins. Pfade relativ zum Repo, '/'.
function Test-RuntimeFile([string]$path) {
    $p = $path.Replace([char]92, '/')
    if ($p -ceq 'macs-cookie-banner.php') { return $true }
    if ($p -ceq 'uninstall.php')          { return $true }
    if ($p -ceq 'readme.txt')             { return $true }
    if ($p.StartsWith('includes/'))       { return $true }
    if ($p.StartsWith('assets/'))         { return $true }
    if ($p -match '^languages/[^/]+\.(mo|po|pot)$') { return $true }
    return $false
}

# Sperrliste: gilt auch INNERHALB der Positivliste (z. B. README.md und
# composer.json von plugin-update-checker unter includes/). Liefert den Grund
# oder $null. Dieselbe Funktion prueft am Ende das fertige ZIP.
function Get-ForbiddenReason([string]$path) {
    $p = $path.Replace([char]92, '/')
    $segments = @($p.Split('/') | Where-Object { $_ -ne '' })
    $name = $segments[-1]
    if ($name -match '(?i)\.md$') { return '.md-Datei' }
    foreach ($s in $segments) {
        if ($s.StartsWith('.')) { return 'Datei/Ordner beginnt mit Punkt' }
        if ($s -match '^(?i)(tools|tests|node_modules|_BUILD_OUTPUT)$') { return "Ordner $s/" }
    }
    if ($name -match '^(?i)(composer\.json|composer\.lock|package\.json|package-lock\.json)$') { return $name }
    return $null
}

$allFiles = $files
$files = @($allFiles | Where-Object { (Test-RuntimeFile $_) -and -not (Get-ForbiddenReason $_) })
$skipped = @($allFiles | Where-Object { $files -notcontains $_ })

if (-not ($files -ccontains 'macs-cookie-banner.php')) { throw 'Positivliste: Hauptdatei macs-cookie-banner.php fehlt.' }
if (-not $files) { throw 'Positivliste ergab keine Dateien.' }

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
            # Eintrag manuell anlegen: der Zeitstempel MUSS gesetzt werden, BEVOR der
            # Entry-Stream geoeffnet wird (im Create-Mode ist er danach schreibgeschuetzt).
            # Deshalb hier kein CreateEntryFromFile.
            $entry = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
            $entry.LastWriteTime = $entryStamp

            $entryStream = $entry.Open()
            try {
                $sourceStream = [System.IO.File]::OpenRead($source)
                try {
                    $sourceStream.CopyTo($entryStream)
                } finally {
                    $sourceStream.Dispose()
                }
            } finally {
                $entryStream.Dispose()
            }
            $added++
        }
    } finally {
        $archive.Dispose()
    }
} finally {
    $fs.Dispose()
}

# --- Harte Pruefung des FERTIGEN ZIPs (ADR-45) -------------------------------
# Unabhaengig von der Filterung oben: das geschriebene ZIP wird neu geoeffnet.
# Jeder Eintrag muss unter macs-cookie-banner/ liegen, auf der Positivliste
# stehen und darf keine Sperrregel verletzen. Sonst: ZIP loeschen, Abbruch.
$violations = @()
$zipEntries = @()
$check = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    foreach ($e in $check.Entries) {
        $full = $e.FullName
        $zipEntries += $full
        if (-not $full.StartsWith('macs-cookie-banner/')) { $violations += "$full (ausserhalb macs-cookie-banner/)"; continue }
        if ($full.Contains([string][char]92)) { $violations += "$full (Backslash im Pfad)"; continue }
        $rel = $full.Substring('macs-cookie-banner/'.Length)
        $reason = Get-ForbiddenReason $rel
        if ($reason) { $violations += "$full ($reason)"; continue }
        if (-not (Test-RuntimeFile $rel)) { $violations += "$full (nicht auf der Positivliste)" }
    }
} finally {
    $check.Dispose()
}
if (-not ($zipEntries -ccontains 'macs-cookie-banner/macs-cookie-banner.php')) {
    $violations += 'macs-cookie-banner/macs-cookie-banner.php fehlt'
}
if ($zipEntries.Count -ne $added) { $violations += "Eintragszahl $($zipEntries.Count) != geschrieben $added" }
if ($violations) {
    Remove-Item -LiteralPath $zipPath -Force
    $violations | ForEach-Object { Write-Warning "VERSTOSS: $_" }
    throw "ZIP-Inhaltspruefung fehlgeschlagen ($($violations.Count) Verstoss/Verstoesse) - ZIP wurde geloescht."
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
Write-Output ("Inhaltspruefung (Positivliste + Sperrliste): BESTANDEN | nicht aufgenommen: $($skipped.Count) Entwickler-Dateien")
Write-Output ("Reproduzierbar: alle Eintraege auf Commit-Zeit " + $entryStamp.ToString('u'))
Write-Output '================================================================================'
