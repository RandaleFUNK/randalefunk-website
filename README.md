# randalefunk-website

Statische Website-Struktur fuer RandaleFUNK.

## Aufbau

- `index.html` ist der Einstiegspunkt der Website.
- `style.css` enthaelt die grundlegenden Styles.
- `script.js` schaltet die Rubriken im Magazinlayout um und laedt die Umfrage in die linke Navigation.
- `poll.php` liefert die Umfrage der Woche per PHP/MySQL aus.
- `data/randalf-sprueche.json` enthaelt die statische Spruchliste fuer die Randalf-Box.
- `AGENTS.md` sammelt Projektregeln, Designnotizen und spaetere Codex-Aufgaben.

Hinweis: Online wird die komplette Randalf-Spruchliste aus `data/randalf-sprueche.json` geladen. Fuer lokale Tests ohne Webserver enthaelt `script.js` zusaetzlich eine kleine Fallback-Liste, weil Browser JSON-Dateien beim direkten Oeffnen per Datei manchmal blockieren.

## Layout

Die Startseite ist als digitales Punk-Fanzine aufgebaut:

- schmaler Kategorienbalken
- kleiner Header mit Logo und Claim
- linke Rubriknavigation mit kompakter Umfrage der Woche
- zentraler Inhaltsbereich mit News, Reviews, Interviews und Kolumnen
- rechte Wegweiser-Spalte mit externen Links

NEWS ist beim Laden der Seite aktiv. Die anderen Rubriken sind aktuell statische Platzhalter.

## Deployment

Das Hauptlayout ist statisch und benoetigt kein Framework und kein Build-System. Die Umfrage und die Statistik benoetigen PHP + MySQL und nutzen die Datenbank-Konfiguration in `stats/config.php`.

Fuer rein statische Deployments wird die Umfrage nicht angezeigt. Auf dem Live-Webspace sollte PHP aktiv sein und `poll.php` erreichbar bleiben.

## Lokaler Review-Workflow

Freigegebene Reviews liegen als Markdown in `../Reviews/Freigegeben/`.

Verarbeitung:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File ..\tools\publish-reviews.ps1
```

Das Skript erzeugt:

- statische Review-Seiten in `reviews/`
- die Review-Uebersicht `reviews/index.html`
- die aktuellen Review-Teaser auf der Startseite

Optionales ZIP fuer manuellen Upload:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File ..\tools\publish-reviews.ps1 -Zip
```
