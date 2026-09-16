# MULTI_PC_SETUP — Arbeiten auf Desktop UND Laptop

Verbindlich ab 2026-09-16.

Ziel: **GitHub ist die einzige Übertragungsstrecke** zwischen den Rechnern.
Sourcecode, Claude-Projektregeln, Dokumentation, Tests/Validierungsvorlagen und
Build-Werkzeuge werden **niemals** manuell von Rechner zu Rechner kopiert.

---

## 1. Verbindliche Rollenverteilung

| Ort | Wofür | Beispiele |
|---|---|---|
| **GitHub** | zentrale Entwicklungswahrheit | Sourcecode, `CLAUDE.md`, `MASTER_HANDBUCH.md`, Doku, Validierungsvorlagen, `tools/` |
| **Lokal (gitignored)** | rechnerspezifisch, reproduzierbar | `_BUILD_OUTPUT\`, `.claude\` (Permission-Cache), `.vscode\`, PHP-Binary |
| **Dropbox** | nur was aus gutem Grund nicht in Git gehört | *(für dieses Projekt: nichts — siehe Abschnitt 5)* |

**Regel:** Entsteht ein dauerhafter, nicht geheimer Entwicklungsbestandteil, gehört er
nach Sicherheitsprüfung **in Git**. Dauerhafte Claude-Projektregeln dürfen niemals nur
im lokalen Claude-Memory eines einzelnen Rechners liegen — sie gehören in `CLAUDE.md`.

---

## 2. Das Repository liegt NICHT im Projekt-Root

Wichtig für jeden neuen Rechner:

```
<Projekt-Ordner>\                        <- KEIN Git-Repo (nur Container)
└─ light-swiss-cookie-consent\           <- HIER liegt .git\  (= Repository)
```

Der Ordnername `light-swiss-cookie-consent` ist historisch (alter Produktname) und
bleibt bewusst stehen. Beim Klonen deshalb den Zielordnernamen explizit setzen,
damit beide Rechner dieselbe Struktur haben.

---

## 3. Kanonisches Repository

Es existieren zwei GitHub-Repositories aus der Umbenennungs-Historie:

| Repository | Status |
|---|---|
| `https://github.com/Thaimacky/macs-cookie-banner.git` | **AKTUELL — einzige gültige Quelle** |
| `https://github.com/Thaimacky/light-swiss-cookie-consent.git` | **VERALTET** — alter Produktname, eingefrorener Altstand. Nicht klonen, nicht pushen. |

> **Achtung, echte Stolperfalle:** Auf dem Desktop hiess der Remote des *veralteten*
> Repos historisch `origin`, der des *aktuellen* Repos `macs`. Ein Klon des falschen
> Repos liefert einen veralteten Stand. Deshalb gilt weiterhin die Regel
> „**Git-Remote niemals hartcodieren**": vor jedem Push/Pull/Fetch/Tag zuerst
> `git remote -v` ausführen und den **tatsächlichen** Namen verwenden.
> Entscheidend ist nicht der Remote-**Name**, sondern die Remote-**URL**:
> sie muss auf `macs-cookie-banner.git` zeigen.

---

## 4. Rechnerwechsel — verbindlicher Ablauf

### 4.1 Rechner A (der die Arbeit abgibt) — VOR dem Wechsel

```
git -C <repo> status                      # Arbeitsbaum muss sauber sein
git -C <repo> remote -v                   # tatsaechlichen Remote ermitteln
git -C <repo> add <dateien>               # keine Secrets, kein _BUILD_OUTPUT
git -C <repo> commit -m "..."
git -C <repo> push <REMOTE> main
git -C <repo> fetch <REMOTE>
git -C <repo> rev-list --left-right --count <REMOTE>/main...HEAD
```

Der Wechsel ist erst freigegeben, wenn der letzte Befehl **`0   0`** liefert
(nichts ausstehend, nichts fehlend) und `git status` sauber ist.

### 4.2 Rechner B (der die Arbeit übernimmt) — NACH dem Wechsel

**Einmalig einrichten:**

1. Git installieren, GitHub-Auth einrichten (PAT oder SSH) — `git push` muss funktionieren.
2. Projekt-Ordner anlegen und Repo mit korrektem Ordnernamen klonen:
   ```
   git clone https://github.com/Thaimacky/macs-cookie-banner.git light-swiss-cookie-consent
   ```
3. PHP-CLI installieren (nur für Syntaxprüfung `php -l`, kein Plugin-Build).
   Pfad **nicht** ins Repo schreiben — `.vscode\settings.json` ist gitignored und
   wird pro Rechner lokal angelegt.
4. Claude Code im **Projekt-Ordner** öffnen. `CLAUDE.md` wird automatisch geladen;
   es sind **keine** Claude-Memories vom anderen Rechner zu kopieren.

**Vor jeder Arbeitssitzung:**

```
git -C <repo> remote -v
git -C <repo> fetch <REMOTE>
git -C <repo> status
git -C <repo> pull <REMOTE> main
```

Erst danach mit der Arbeit beginnen. Das gilt in **beide** Richtungen:
Desktop → Laptop **und** Laptop → Desktop.

---

## 5. Braucht dieses Projekt Dropbox?

**Nein.**

> **Für den normalen Multi-PC-Entwicklungsworkflow von Mac's Cookie Banner ist keine
> Dropbox-Synchronisation erforderlich.**

Begründung — geprüft wurde der komplette lokale Bestand:

* **Sourcecode, Doku, Validierungsvorlagen, Build-Werkzeug** → vollständig in Git.
* **Keine Secrets, Tokens, Keys, `.env`-Dateien** im Projekt. Die GitHub-Auth liegt
  im Git-Credential-Manager des jeweiligen Rechners, nicht im Projekt.
* **Keine personenbezogenen Kundendaten** im Projekt. `LSCC_INVENTUR_VORLAGE.md` ist
  eine **leere Vorlage** ohne Site-Namen/URLs und deshalb unbedenklich versioniert.
* **Keine grossen Binärartefakte, die geteilt werden müssten.** Test-/Release-ZIPs sind
  aus jedem Clone mit `tools\build-zip.ps1` reproduzierbar und bleiben gitignored.
* **Keine Lizenz-/Vertragsdateien** im Projekt.

**Falls das später kippt** — sobald ausgefüllte Kunden-Inventuren mit echten
Site-Namen/URLs, Zugangsdaten oder Vertragsunterlagen entstehen, gehören genau diese
Dateien in den Dropbox-Ordner und **niemals** in Git. Der Dropbox-Ordner bleibt bis
dahin bewusst leer. Sein Laufwerksbuchstabe unterscheidet sich je Rechner und darf
deshalb **nirgends** in Skripten oder Anleitungen hartcodiert werden.

---

## 6. Was bewusst lokal bleibt (gitignored, pro Rechner neu)

| Pfad | Warum lokal | Wie auf einem neuen Rechner herstellen |
|---|---|---|
| `_BUILD_OUTPUT\` | Build-Artefakte, reproduzierbar | entsteht automatisch beim ersten `tools\build-zip.ps1` |
| `.claude\` | rechnerlokaler Permission-Cache von Claude Code | entsteht automatisch; Regeln stehen in `CLAUDE.md` |
| `.vscode\` | enthält rechnerspezifischen PHP-Pfad | bei Bedarf lokal anlegen |
| `*.zip` im Projekt-Root | historische Test-ZIPs des Desktops | werden **nicht** übertragen; bei Bedarf neu bauen |

---

## 7. Build-Artefakte

Ausgabeordner ist **immer** `_BUILD_OUTPUT\` relativ zum Repository — nie der
Projekt-Root, nie ein fester Laufwerkspfad:

```
powershell -ExecutionPolicy Bypass -File tools\build-zip.ps1            # Test-ZIP
powershell -ExecutionPolicy Bypass -File tools\build-zip.ps1 -Kind release
```

Das Skript ermittelt den Repo-Pfad selbst, liest die Version aus `MCB_VERSION`,
prüft sie gegen den Plugin-Header und gibt Pfad, Dateiname, Grösse und SHA-256 aus.
Test-ZIPs tragen **immer** die Version im Namen: `macs-cookie-banner-vX.Y.Z-test.zip`.
