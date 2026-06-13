# RANDALE-WUERFELN - Phase 1 Regeln

## Grundidee

RANDALE-WUERFELN ist ein schnelles Zwei-Wuerfel-Trinkspiel im RandaleFUNK-Stil.
Der Spieler tritt gegen Burg, den KI-Gegner, an. Beide wuerfeln gleichzeitig mit
je zwei sechsseitigen Wuerfeln. Die niedrigere Gesamtsumme verliert die Runde.

## Spieler

- Spieler: menschlicher Spieler
- Burg: KI-Gegner

## Rundenablauf

1. Spieler und Burg wuerfeln gleichzeitig mit je 2W6.
2. Spezialwuerfe werden zuerst ausgewertet.
3. Wenn kein Spezialwurf aktiv ist, entscheidet die Gesamtsumme.
4. Die niedrigere Gesamtsumme verliert die Runde.
5. Der Verlierer trinkt 1 Schluck.
6. Bei Gleichstand ohne Spezialwurf startet automatisch eine Stechrunde.

## Prioritaet der Spezialregeln

Spezialregeln werden in dieser Reihenfolge geprueft:

1. 1-1: Katastrophe
2. 6-6: RANDALE
3. anderer Pasch: Punkquiz
4. normale Summenwertung
5. Gleichstand: Stechrunde

Wenn mehrere Spezialwuerfe gleichzeitig auftreten, gewinnt die hoehere Prioritaet.

Beispiele:

- Spieler wuerfelt 1-1, Burg wuerfelt 6-6: Katastrophe wird ausgeloest.
- Spieler wuerfelt 6-6, Burg wuerfelt 3-3: RANDALE wird ausgeloest.
- Spieler wuerfelt 2-2, Burg wuerfelt 4-4: Punkquiz wird ausgeloest.
- Spieler wuerfelt 4-2, Burg wuerfelt 3-3: Punkquiz wird ausgeloest.

## Basiswertung

Wenn keine Spezialregel greift:

- niedrigere Gesamtsumme verliert
- Verlierer trinkt 1 Schluck
- waehrend RANDALE trinkt der Verlierer doppelt

Beispiel:

- Spieler: 4 + 3 = 7
- Burg: 5 + 1 = 6
- Burg verliert und trinkt 1 Schluck

## Gleichstand

Wenn beide dieselbe Gesamtsumme wuerfeln und kein Spezialwurf vorliegt:

- die Runde wird nicht gewertet
- niemand trinkt
- es startet automatisch eine Stechrunde

Beispiel:

- Spieler: 4 + 2 = 6
- Burg: 5 + 1 = 6
- Stechrunde

## Pasch

Ein Pasch liegt vor, wenn beide Wuerfel eines Spielers denselben Wert zeigen.

Beispiele:

- 1-1
- 2-2
- 3-3
- 4-4
- 5-5
- 6-6

1-1 und 6-6 sind Sonderfaelle und werden nicht als normales Punkquiz behandelt.

## Punkquiz

Wenn irgendein normaler Pasch gewuerfelt wird, startet ein Punkquiz.

Normale Pasche:

- 2-2
- 3-3
- 4-4
- 5-5

Phase-1-Verhalten:

- Immer der Spieler muss eine Frage beantworten.
- Burg nimmt nicht am Quiz teil.
- Jede Frage hat drei Auswahlmoeglichkeiten.
- Die Quizfragen werden aus `quizfragen.JSON` geladen.
- Die drei Antworten werden vor der Anzeige zufaellig gemischt.
- Die korrekte Antwort wird ueber `correct: true` erkannt, nicht ueber die Position.
- Bei richtiger Antwort trinkt niemand.
- Bei falscher Antwort trinkt der Spieler 1 Schluck.

Wenn beide Spieler einen normalen Pasch wuerfeln:

- Es startet ebenfalls eine normale Punkquizfrage fuer den Spieler.

## RANDALE

Wenn irgendein Spieler 6-6 wuerfelt, startet RANDALE.

Soforteffekt:

- Spieler trinkt sofort 2 Schlucke.
- Burg trinkt sofort 2 Schlucke.
- RANDALE-Zaehler wird auf 3 Runden gesetzt.

Aktiver Effekt:

- Fuer die naechsten 3 gewerteten Runden trinkt jeder Verlierer doppelt.
- Ein normaler Verlust kostet dadurch 2 Schlucke statt 1 Schluck.
- Der RANDALE-Zaehler sinkt nach jeder gewerteten Runde um 1.

Ende:

- Nach 3 gewerteten Runden endet RANDALE automatisch.
- Das UI zeigt waehrenddessen die verbleibenden Runden an.

Anzeigevorschlag:

```text
RANDALE: 3 Runden uebrig
Verlierer trinkt x2
```

## Katastrophe

Wenn irgendein Spieler 1-1 wuerfelt, startet das "Bier leer"-Event.

Phase-1-Verhalten:

- Das Spiel endet sofort.
- Es erscheint ein Game-Over-Screen.
- Die Statistik wird angezeigt.

Option fuer spaetere Versionen:

- 1-1 koennte erst beim zweiten Auftreten das Spiel beenden.
- Beim ersten 1-1 koennte ein einmaliger Rettungszustand ausgeloest werden.
- Fuer Phase 1 bleibt die Regel bewusst hart und einfach.

## Statistik

Am Ende des Spiels werden mindestens diese Werte angezeigt:

- gespielte Runden
- Schlucke Spieler
- Schlucke Burg
- Anzahl RANDALE-Ausloesungen
- Anzahl Punkquizfragen
- Anzahl richtige Quizantworten Spieler
- Anzahl falsche Quizantworten Spieler

Optionale spaetere Werte:

- hoechster Wurf
- laengste RANDALE-Serie
- Quizquote
- Anzahl Stechrunden

## Phase-1 Features

### Startscreen

- RandaleFUNK Branding
- Titelbild
- Startbutton

### Hauptbildschirm

- Burg sitzt am Tisch
- Wuerfelbecher
- Bierflaschen
- Wuerfelanimation
- Rundenlog
- sichtbarer RANDALE-Zaehler, wenn aktiv

### Spielsystem

- Wuerfeln
- Rundenauswertung
- Spezialregeln
- RANDALE-Modus
- Punkquiz
- Game Over
- Statistik

### Audio

- Wuerfelgeraeusche
- Kneipenatmo
- Punk-/Chaos-Sounds fuer Spezialwuerfe
- eigener Sound fuer RANDALE
- eigener Sound fuer Katastrophe

### Grafikstil

- Comic/Cartoon
- Punkkneipen-Stimmung
- dreckige DIY-Optik
- warme Farben
- VHS-/Analog-Stoerungen
- mobile-first Layout

## Technische Notizen fuer die Umsetzung

Wichtige Spielzustaende:

- start
- rolling
- evaluating
- quiz
- randale
- gameOver

Wichtige Zaehler:

- roundCount
- playerSips
- burgSips
- randaleCount
- randaleRoundsLeft
- quizQuestions
- playerQuizCorrect
- playerQuizWrong
- tieBreakCount

Empfohlene Event-Reihenfolge pro Runde:

1. Wuerfelwerte erzeugen.
2. Spezialprioritaet pruefen.
3. Event ausloesen.
4. Statistik aktualisieren.
5. UI-Animationen und Sounds abspielen.
6. Naechste Runde freigeben oder Game Over anzeigen.
