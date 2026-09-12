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

Nur Burgs ausdrückliches Wort FREIGABE autorisiert Commit, Push, Deploy oder Live-Änderungen, jeweils ausschließlich im genannten Umfang. Eine Freigabe für Commit und Push ist keine Deploy-Freigabe. Der Hauptworkflow startet nicht bei Push, sondern manuell mit dem Pflichtfeld deploy_ref; vorzugsweise die vollständige freigegebene Commit-SHA angeben. Der separate LeonBau-Workflow bleibt unabhängig und unverändert.

Die vorbereiteten Security-Header und noch offenen Hoster-Voraussetzungen stehen in [docs/security-headers.md](docs/security-headers.md). Sie sind noch nicht veröffentlicht.


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

Neue Review-Quelldateien erhalten die Metadatenzeile `Autor: Name`. Daraus erzeugt der Generator direkt unter der Artikelüberschrift eine verlinkte Autorenzeile zur jeweiligen Sammelkarte unter `/rotte/#name`.

Der Generator lässt sichtbare Texte als echte UTF-8-Umlaute stehen. Nur technisch notwendige Zeichen wie `&`, `<`, `>` und Anführungszeichen in Attributen werden escaped. Slugs und Dateinamen bleiben bewusst ASCII.

Optionales ZIP für manuellen Upload:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File ..\Generatoren\tools\publish-reviews.ps1 -Zip
```

## Vorschauen und vertrauliche Entwürfe

Dieses Repository entspricht dem produktiven website/-Ordner. Das übergeordnete Projekt und private Arbeitsmaterialien gehören nicht automatisch ins öffentliche GitHub-Repository.

| Material | Ablage / Verfahren |
| --- | --- |
| Vertrauliche Entwürfe, Embargo-Texte und zugehörige Bilder/Exporte | Außerhalb des öffentlichen Checkouts, zum Beispiel ../RandaleFUNK_PRIVAT/Entwuerfe/ und ../RandaleFUNK_PRIVAT/Embargo/; alternativ wirklich privates Repo. |
| Bewusst öffentliche Vorschau | Darf hier liegen, sichtbar als Vorschau gekennzeichnet. noindex kann ergänzt werden, schützt aber nicht vor Zugriff. |
| Vertrauliche Vorschau für Dritte | Zunächst lokal; bei Bedarf gesondert freigegebener HTTPS-Bereich mit Authentifizierung für Seiten und Assets, aus privater Quelle. Nicht über öffentliche Branches oder öffentliche Actions-Artefakte. |

Minimaler Ablauf: Vertraulichkeit und Embargo-Termin vorab klären, lokal privat schreiben und prüfen, erst nach Aufhebung des Embargos und passender FREIGABE die konkret freigegebenen Dateien in den öffentlichen Checkout übernehmen. Commit/Push und Website-Deploy bleiben getrennte Schritte. Ein Branch eines öffentlichen Repositorys ist nicht privat.

Die ergänzten .gitignore-Muster für _private/, embargo/, drafts/, entsprechend markierte Dateien und .htpasswd verhindern nur versehentliches Hinzufügen neuer Dateien. Sie verstecken nichts auf einem Webserver, entfernen keine getrackten Dateien und schützen nicht vor erzwungenem Hinzufügen.

### Bestehende Vorschauen

- vorschau-lb-2609/: bewusst öffentliche LeonBau-Vorschau; noindex bleibt nur Suchmaschinenhinweis. Inhalte, Passwortfreiheit und separater manueller Workflow bleiben unverändert.
- kolumnen/wahlaufruf-vorschau.php: bestehende Link-Zugangsschranke und noindex bleiben unverändert. Der Artikeltext liegt bereits im öffentlichen GitHub-Repo. Die bisherige Bezeichnung als interne/nicht öffentliche Vorschau darf daher nicht als Vertraulichkeitszusage verstanden werden. Kein Muster für künftige Embargos.

Bereits veröffentlichte Quellen, Git-Objekte, Downloads, Forks und andere Kopien werden durch spätere Zugriffssperren oder Löschen nicht wieder geheim. In diesem Schritt keine Historienumschreibung, keine Löschung und kein Umbau bestehender Vorschauen.
