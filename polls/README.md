# RandaleFUNK Umfrage

Die Umfrage nutzt dieselbe Datenbank-Konfiguration wie die Statistik:

```php
website/stats/config.php
```

`poll.php` legt die Tabellen bei Bedarf selbst an. Wenn noch keine Umfrage existiert, wird die Testumfrage automatisch angelegt.

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
