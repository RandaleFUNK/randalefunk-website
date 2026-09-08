# RandaleFUNK Umfrage

Die Umfrage nutzt dieselbe Datenbank-Konfiguration wie die Statistik:

```php
website/stats/config.php
```

## Initialisierung und Wartung

Schema-Prüfung, Schema-Ergänzungen und die im Code hinterlegten Startumfragen werden nur beim authentifizierten Aufruf von monthly-polls-admin.php ausgeführt. Dort werden auch abgelaufene Umfragen dauerhaft abgeschlossen und Monatsgewinner als Kandidaten der Jahreswahl übernommen. Das ist die bestehende geschützte Verwaltungsfunktion, kein neuer öffentlicher Wartungs-Endpunkt.

Bei einer Neuinstallation oder nach Änderungen an Schema bzw. Startumfragen muss die Verwaltung deshalb einmal berechtigt aufgerufen werden. Für die Übernahme neuer Monatsgewinner in die Jahreswahl ebenfalls die Verwaltung für das betreffende Jahr aufrufen. Es gibt keinen neu eingerichteten Zeitplan oder Cronjob.

Öffentliche GET-Aufrufe von poll.php und monthly-polls.php lesen nur. Abgelaufene Ergebnisse und Gewinner werden bei Bedarf für die Anzeige berechnet, ohne Umfragen oder Jahreskandidaten zu verändern. Ein gültiger Abstimmungs-POST darf weiterhin die Stimme speichern. Abstimmungen nach Ablauf bleiben gesperrt. Ohne initialisierte Tabellen erscheint die vorhandene allgemeine Fehlermeldung; öffentliche Aufrufe legen keine Tabellen an.

## Poll-Kennung

rf_poll_token wird erst bei einer gültigen Stimme für eine offene Umfrage erzeugt. Lesen, ungültige Optionen und Abstimmungen nach Ablauf erzeugen keine neue Kennung. Vorhandene gültige Cookies und ihre bisherige Hash-Bildung bleiben kompatibel.

Laufzeit: 365 Tage ab Erstellung, ohne Verlängerung beim Lesen oder Wiederverwenden. HttpOnly, SameSite=Lax, Secure bei HTTPS. Die Datenbank speichert pro Stimme den gesalzenen Hash; die bestehende Eindeutigkeitsregel pro Umfrage verhindert Wiederholungsstimmen. Ohne Cookie beziehungsweise mit einem anderen Browser greift diese Begrenzung nicht zuverlässig.

Cookie-Ablauf ist keine Löschung von Datenbankstimmen. Historische Stimmen und Statistikereignisse werden durch C1 weder gelöscht noch migriert.

## Neue Umfrage anlegen

Nur eine Umfrage sollte `is_active = 1` haben.

```sql
UPDATE rf_polls SET is_active = 0;

INSERT INTO rf_polls (title, question, is_active)
VALUES ('Umfrage der Woche', 'Deine Frage?', 1);

SET @poll_id = LAST_INSERT_ID();

INSERT INTO rf_poll_options (poll_id, option_text, sort_order) VALUES
(@poll_id, 'Antwort A', 1),
(@poll_id, 'Antwort B', 2),
(@poll_id, 'Antwort C', 3),
(@poll_id, 'Antwort D', 4);
```

Alte Umfragen bleiben samt Stimmen gespeichert.
