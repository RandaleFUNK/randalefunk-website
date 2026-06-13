# AGENTS.md

Arbeitsnotizen und Projektregeln fuer Codex-Aufgaben an der RandaleFUNK-Website.

## Projektregeln

- Statische Website ohne Backend, Datenbank oder Build-System.
- Neue Dateien und Aenderungen klein, nachvollziehbar und direkt deploybar halten.
- Externe Abhaengigkeiten nur einfuehren, wenn sie ausdruecklich gewuenscht sind.

## Designstil

- Platzhalter fuer spaetere Entscheidungen zu Farben, Typografie, Bildsprache und Layout.
- Noch kein finales Design festlegen.
- RandaleFUNK soll eigenstaendig wirken, ohne die erste technische Struktur zu ueberladen.

## Inhalte

- Platzhalter fuer Seitenstruktur, Texte, Kontaktinformationen und rechtliche Seiten.
- Spaeter klaeren: Startseite, Ueber uns, Sendungen/Formate, Kontakt, Impressum, Datenschutz.

## News-Workflow

Wenn der Nutzer einen Screenshot, Link oder kurzen Hinweis zu einer Band-News liefert, daraus eine kurze RandaleFUNK-News fuer den mittleren NEWS-Bereich erstellen.

Grundregeln:

- Nicht 1:1 abschreiben.
- Keine Screenshots ungefragt als Website-Bild verwenden.
- Keine kompletten Facebook- oder Instagram-Grafiken uebernehmen.
- Keine Review schreiben und keine Bewertung abgeben, wenn Song, Video oder Album noch nicht gehoert oder gesehen wurde.
- Keine PR-Sprache. Kurz, direkt, RandaleFUNK-Ticker-Stil.

Inhalt zuerst erkennen:

- Welche Band?
- Was wird angekuendigt?
- Wann erscheint es?
- Geht es um Titel, Video, Single, Album, Konzert oder Festival?
- Gibt es einen Original-Link?

Textformat:

- 1 Ueberschrift.
- 2 bis 4 kurze Saetze.
- Gern mit kurzem frechem Burg-Kommentar.
- Quellenhinweis bei Social-Media-Fund einbauen, zum Beispiel: "Die Band kuendigt auf Facebook an, dass ..." oder "Laut Band-Ankuendigung erscheint ...".

Bildmaterial pruefen:

- Wenn moeglich offizielle Band-Website, Pressebereich, Media-Kit oder Downloads pruefen.
- Nur offizielles Pressefoto oder Cover verwenden, wenn klar erkennbar freigegeben.
- Bildquelle sauber notieren.
- Freigegebenes News-Bildmaterial lokal unter `assets/news/` ablegen und die Quelle in `assets/news/README.md` dokumentieren.
- Wenn kein eindeutig freigegebenes Bild vorhanden ist: kein Bild uebernehmen, Platzhalterbild oder Randalf-Grafik nutzen und auf den Originalpost verlinken.

Button/Link:

- Wenn Originalpost oder offizieller Link vorhanden ist, passenden Button verwenden.
- Geeignete Button-Texte: "Originalpost ansehen", "Zur Band", "Zum Video", "Anhoeren", "Mehr Krach".

Ticker-Sortierung:

- Echte News-Karten im NEWS-Ticker bekommen `data-ticker-news` und ein vollstaendiges `data-published` im Format `YYYY-MM-DDTHH:MM:SS`.
- Neueste Meldung steht immer oben, aelteste echte Meldung unten.
- Bei gleichem Datum entscheidet die Uhrzeit.
- Die feste INFO-Karte "EUREN KRACH HIER REINWERFEN" bekommt `data-ticker-static`, ist keine News und bleibt dauerhaft am Ende der Liste.
- Alle echten News erscheinen oberhalb dieser INFO-Karte.

Beispielformat:

```text
Kategorie: SINGLE
Ueberschrift: ALARMSIGNAL KUENDIGEN "FRESSE AUF!" AN

Text:
Alarmsignal hauen am 24.06. ueber Aggropunk ihre neue Single "Fresse auf!" raus. Die Band verpackt die Ankuendigung gewohnt dezent zwischen Boulevard-Parodie und gepflegtem Mittelfinger.

Gehoert haben wir den Song noch nicht - aber der Titel klingt schon mal nicht nach Sitzkreis.

Button: Originalpost ansehen
Bild: Nur verwenden, wenn Band/Label/Pressebereich das Material offiziell freigegeben hat. Sonst RandaleFUNK-Platzhalter nutzen.
```

## Randalf

Randalf ist wichtiger als einzelne Comics, Pointen oder dekorative Ideen.

- Die Hauptreferenz liegt in `../Randalf/Referenzbild_Randalf.png`.
- Die Charakterkonsistenz hat Vorrang vor einer schoeneren Einzelzeichnung.
- Wenn ein Comic eine Pointe braucht, darf die Pointe geaendert werden, damit Randalf erkennbar bleibt.
- Immer beibehalten: lila Fell, schwarze Gesichtsmaske, Waschbaer, Jeansweste, schwarzes Shirt, gestreifter Schwanz, Stift hinter dem Ohr und Festivalbaendchen.
- Randalf kommentiert das Geschehen; der Burg fuehrt Interviews.
- Randalf-"Sticker" sind freigestellte kleine Kommentar- oder Gag-Motive direkt an einzelnen News, Karten oder Seitenbereichen.
- Sticker duerfen Randalf situationsbezogen verkleiden oder ueberzeichnen, solange lila Fell, schwarze Maske, Waschbaer-Gesicht, skeptischer Blick und Randalf-Haltung erkennbar bleiben.

## YouTube-Einbindung und Datenschutz

YouTube-Videos sollen datenschutzfreundlich eingebunden werden.

- Keine direkten Standard-YouTube-Embeds verwenden.
- Ausschliesslich `youtube-nocookie.com` verwenden (Privacy Enhanced Mode).
- Keine automatischen Video-Loads beim Seitenaufruf.
- Videos zunaechst nur als Vorschaubild darstellen.
- Das eigentliche YouTube-Video erst nach aktivem Klick des Besuchers laden.
- Vor dem Laden diesen kurzen Hinweis anzeigen: "Mit dem Laden des Videos werden Daten an YouTube bzw. Google uebertragen."
- Das Layout soll zum RandaleFUNK-Stil passen.
- Vorschaubilder sollen wie Artikel- oder Magazinbilder wirken.
- Die Loesung soll ohne zusaetzliche Plugins funktionieren.
- Mobile Darstellung beruecksichtigen.

Ziel: Besucher sollen Videos direkt auf `randalefunk.de` ansehen koennen, ohne dass beim blossen Seitenaufruf automatisch eine Verbindung zu YouTube aufgebaut wird.

## Spaetere Codex-Aufgaben

- Inhalte sammeln und sortieren.
- Erste visuelle Richtung entwickeln.
- Netlify-Deploy pruefen.
- Domain-Anbindung vorbereiten.
- Barrierefreiheit und mobile Darstellung testen.
