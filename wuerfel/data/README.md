# Quizdaten

Die eindeutige Hauptquelle fuer Quizfragen ist:

- `data/quizfragen.json`

Die App laedt diese Datei zur Laufzeit per `fetch()`, wenn sie ueber einen
Webserver laeuft.

## Lokaler Fallback

Fuer direkte lokale Tests per `file://` gibt es zusaetzlich:

- `data/quizfragen-data.js`

Diese Datei ist ein generierter Spiegel von `data/quizfragen.json`, damit Browser
ohne Webserver nicht am JSON-`fetch()` scheitern. Sie ist keine zweite inhaltliche
Quelle und soll nicht per Hand bearbeitet werden.

Wichtig fuer lokale Tests:

- Direktes Oeffnen per `file://` kann das Laden der JSON-Datei blockieren.
- Deshalb die App lokal ueber einen kleinen Webserver starten.
- Beispiel aus dem Projektordner:

```powershell
python -m http.server 5173 --bind 127.0.0.1
```

`quizfragen.js` im App-Hauptordner wird nicht mehr verwendet und soll nicht parallel
gepflegt werden.
