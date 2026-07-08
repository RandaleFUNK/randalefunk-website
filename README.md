# randalefunk-website

Statische Website-Struktur für RandaleFUNK.

## Aufbau

- `index.html` ist der Einstiegspunkt der Website.
- `style.css` enthält die grundlegenden Styles.
- `script.js` schaltet die Rubriken im Magazinlayout um und lädt die Umfrage in die linke Navigation.
- `poll.php` liefert die Umfrage der Woche per PHP/MySQL aus.
- `data/randalf-sprueche.json` enthält die statische Spruchliste für die Randalf-Box.
- `AGENTS.md` sammelt Projektregeln, Designnotizen und spätere Codex-Aufgaben.

Hinweis: Online wird die komplette Randalf-Spruchliste aus `data/randalf-sprueche.json` geladen. Für lokale Tests ohne Webserver enthält `script.js` zusätzlich eine kleine Fallback-Liste, weil Browser JSON-Dateien beim direkten Öffnen per Datei manchmal blockieren.

## Layout

Die Startseite ist als digitales Punk-Fanzine aufgebaut:

- schmaler Kategorienbalken
- kleiner Header mit Logo und Claim
- linke Rubriknavigation mit kompakter Umfrage der Woche
- zentraler Inhaltsbereich mit News, Reviews, Interviews und Kolumnen
- rechte Wegweiser-Spalte mit externen Links

NEWS ist beim Laden der Seite aktiv. Die anderen Rubriken sind aktuell statische Platzhalter.

## Deployment

Das Hauptlayout ist statisch und benötigt kein Framework und kein Build-System. Die Umfrage und die Statistik benötigen PHP + MySQL und nutzen die Datenbank-Konfiguration in `stats/config.php`.

Für rein statische Deployments wird die Umfrage nicht angezeigt. Auf dem Live-Webspace sollte PHP aktiv sein und `poll.php` erreichbar bleiben.

## Lokaler Review-Workflow

Freigegebene Reviews liegen als Markdown außerhalb des produktiven Website-Ordners:

`../Arbeitsmaterial/Review-System/Reviews/Freigegeben/`

Der Generator liegt ebenfalls außerhalb des produktiven Website-Ordners:

`../Generatoren/tools/publish-reviews.ps1`

Verarbeitung:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File ..\Generatoren\tools\publish-reviews.ps1
```

Das Skript erzeugt:

- statische Review-Seiten in `reviews/`
- die Review-Übersicht `reviews/index.html`
- die aktuellen Review-Teaser auf der Startseite

Der Generator lässt sichtbare Texte als echte UTF-8-Umlaute stehen. Nur technisch notwendige Zeichen wie `&`, `<`, `>` und Anführungszeichen in Attributen werden escaped. Slugs und Dateinamen bleiben bewusst ASCII.

Optionales ZIP für manuellen Upload:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File ..\Generatoren\tools\publish-reviews.ps1 -Zip
```
