# RandaleFUNK Statistik

Kleine interne Statistik fuer randalefunk.de.

## Grundsatz

- keine externen Trackingdienste
- keine Cookies fuer Besuchertracking
- keine Nutzerprofile
- keine dauerhafte Speicherung von IP-Adressen
- IP-Adresse wird nur waehrend der Anfrage verarbeitet und als taeglicher Hash gespeichert

## Dateien

- `track.php` nimmt Seitenaufrufe und Klick-Events entgegen.
- `stats/index.php` zeigt das interne Dashboard.
- `stats/lib.php` enthaelt Datenbankverbindung, Tabellenanlage und Auswertungen.
- `stats/auth.php` schuetzt das Dashboard per HTTP Basic Auth.
- `stats/schema.sql` dokumentiert die Datenbanktabelle.
- `stats/config.php` wird beim Deploy aus GitHub Secrets erzeugt und nicht ins Repository eingecheckt.

## Benoetigte GitHub Secrets

Vor dem Live-Deploy muessen diese Secrets existieren:

- `STATS_DB_HOST`
- `STATS_DB_NAME`
- `STATS_DB_USER`
- `STATS_DB_PASSWORD`
- `STATS_AUTH_USER`
- `STATS_AUTH_PASSWORD`

Optional:

- `STATS_HASH_SALT`

Wenn `STATS_AUTH_USER` oder `STATS_AUTH_PASSWORD` fehlt, bleibt `/stats/` gesperrt.

## Dashboard

Das Dashboard liegt unter:

`/stats/`

Es wird nicht in der Navigation verlinkt.

Ausgewertet werden unter anderem:

- Seitenaufrufe
- Besucher als tägliche Hashes
- PayPal-Klicks
- PayPal-Klickende als grobe Tageshash-Zählung
- Klicks auf `Warum unterstützen?`
- Klickende auf `Warum unterstützen?` als grobe Tageshash-Zählung
- Würfel-App-Klicks
- Klicks zur RandaleFUNK-Seite im Riot-Candy-Shop
- Shop-Klickende als grobe Tageshash-Zählung

## C2: Retention-Vorbereitung (noch nicht produktiv ausgeführt)

Neue Dateien: scripts/stats-retention.php und stats/retention-schema.sql.
stats/lib.php liest nach dieser Änderung sowohl aktuelle Einzelereignisse als auch ältere Summen. Ohne neue Tabellen funktioniert das bisherige Rohdaten-Dashboard weiter. Eine nur teilweise angelegte Summenstruktur oder sich überschneidende Rohdaten/Summen wird abgelehnt statt irreführend ausgewertet. stats/index.php und seine bisherigen Begriffe bleiben unverändert.

### Modell und erhaltene Kennzahlen

- rf_stats_events bleibt die Tabelle für Einzelereignisse: ID, Zeitstempel, Datum, Ereignistyp, Pfad, Rubrik und visitor_day_hash.
- rf_stats_daily_totals: pro Datum und Ereignistyp Anzahl Ereignisse und Anzahl unterschiedlicher Tageskennungen innerhalb genau dieses Typs.
- rf_stats_daily_dimensions: pro Datum, Ereignistyp, Pfad und Rubrik Anzahl Ereignisse.
- Beide neuen Tabellen enthalten keine Besucherkennung, keine IP-Adresse und keinen Einzelereignis-Zeitstempel. Es werden alle Ereignistypen aggregiert, auch ältere, derzeit nicht mehr aktiv erfasste Typen.
- Vollständig erhalten bleiben die bestehenden Dashboard-Kennzahlen: Besucher-Tageswerte, Pageviews, Zeitraumsummen, 30-Tage-Verlauf, 12-Monats-Verlauf, Top-10-Seiten, Rubriken, Randalf-Aufrufe, PayPal-/Unterstützen-/Würfel-/Shop-Klickzahlen und vorhandene Klickenden-Tageswerte sowie daraus berechnete Verhältniszahlen.
- Besucher-Tageswerte über mehrere Tage bleiben Summen täglicher Näherungen, keine Unique-Personen. Die Bedeutung wird nicht geändert. Besucherzahlen werden nicht aus Seiten- oder Rubriken-Einzelsummen zusammengerechnet.
- Bewusst nicht mehr rekonstruierbar: alte Einzelereignisse/IDs, Uhrzeiten, Tageskennungen, nachträgliche Besucherüberschneidungen zwischen beliebigen Seiten oder Ereignistypen. Diese Detailanalysen sind keine bestehenden Dashboard-Kennzahlen.
- Nachträgliche Rohdaten für bereits aggregierte Tage werden nicht einfach hinzugerechnet: ohne alte Kennungen wäre die Deduplizierung nicht verlustfrei. Dashboard und Wartung brechen dann ab. Kein Zurückimport alter Rohdaten neben vorhandene Aggregate; erst separat entscheiden, ohne weitere Löschung.

### Exakte Grenze

Verglichen wird event_date mit dem Berliner Kalenderdatum minus 90 Tage. Nur event_date < Grenzdatum wird aggregiert und entfernt; der Grenztag selbst und neuere Tage bleiben vollständig erhalten. Ausschlaggebend ist das bestehende Ereignisdatum, nicht created_at oder die Zeitzone des Datenbankservers.

Das ist eine kalendertägliche 90-Tage-Regel, kein sekundengenaues Maximum von 2.160 Stunden. Der volle Grenztag bleibt erhalten; tatsächliche Aufbewahrung hängt zusätzlich vom Wartungszeitpunkt und erfolgreichen regelmäßigen Läufen ab. Vor produktiver Aktivierung muss diese Definition bestätigt werden. Ein strikt rollierendes Stundenlimit wäre ein anderer Entwurf, weil angeschnittene Tage eine verlustfreie Tages-Deduplizierung erschweren.

Kein Cronjob ist eingerichtet. Ein bloßer Code-Deploy setzt die Frist nicht durch. Bei ausgefallener Wartung bleiben Rohdaten erhalten; Fehler müssen gemeldet und separat behoben werden.

### Dry-Run und Apply

Standardmäßig ausschließlich lesend:

    php scripts/stats-retention.php --dry-run

Der Dry-Run verwendet eine READ-ONLY-Transaktion und funktioniert auch vor Anlage der beiden Summen-Tabellen. Er zeigt Grenzdatum, ersten/letzten betroffenen Tag, Anzahl löschbarer Zeilen und die konkret berechneten Tages- und Dimensionssummen. Keine Hashes werden ausgegeben. Auch ohne Argument ist der Modus Dry-Run.

Erst nach eigener FREIGABE für eine produktive Migration:

    php scripts/stats-retention.php --apply --confirm=AGGREGATE_AND_DELETE_OLD_EVENTS

- Nur CLI, niemals HTTP. Bestehende stats/config.php als Konfiguration, keine Credentials im Code oder als Aufrufparameter.
- Tabellen müssen InnoDB sein; Aggregationsschema und Textsortierung werden geprüft. Fehlende/teilweise Tabellen, Überschneidungen oder ein aktiver paralleler Wartungslauf führen zum Abbruch.
- Schema separat vorbereiten: stats/retention-schema.sql legt nur die beiden leeren Tabellen an. DDL gehört bewusst nicht in die Datenmigration, da es implizit committen kann.
- Apply verwendet eine benannte Datenbanksperre sowie REPEATABLE READ und einen sperrenden Lesezugriff auf den betroffenen Rohdatenbereich. Die Erfassung aktueller Events wird nicht absichtlich abgeschaltet; ein größerer Erstlauf kann jedoch kurzzeitig blockieren.
- Vor Änderungen wird der Plan ausgegeben. Danach beide Summenstrukturen einfügen, gespeicherte Werte gegen den Plan vergleichen, erst dann passende Rohdaten entfernen und gemeinsam committen.
- Fehler vor Commit führen zum Rollback. Bei Verbindungsabbruch mit unklarem Commit-Ergebnis den Zustand zuerst prüfen; nicht blind wiederholen.
- Ein erneuter Lauf ohne neue berechtigte Rohdaten ist wirkungslos. Es gibt keine additive Überschreibung vorhandener Tagesaggregate.
- Keine Poll-Daten, Host-Logs oder fremden Tabellen werden verändert.

### Später separat freizugebende produktive Schritte

1. Schema/Engine, Datenumfang, Grenzdefinition und vorhandene Kennzahlen bestätigen. Einen frischen Datenbank-Backup samt Wiederherstellungstest vorbereiten; ein Projektdatei-Backup ersetzt keinen Datenbank-Backup. Auch Backup-Kopien können alte Kennungen enthalten und benötigen eine eigene Aufbewahrungsentscheidung.
2. Bestehenden Website-Deploy mit den gemischten Leseabfragen separat freigeben. Wartungsskript und SQL nicht ungeprüft in einen öffentlichen Upload aufnehmen; private CLI-/Cron-Ausführung und Zugang zu stats/config.php klären. Keine Allowlist-Änderung in C2.
3. Anlage der zwei leeren Summen-Tabellen gesondert freigeben. Ein Teilfehler bei der DDL-Vorbereitung verändert keine Rohdaten; erst nach vollständiger Vorbereitung fortfahren.
4. Produktiven Dry-Run gesondert freigeben, Bericht intern prüfen und mit dem bisherigen Dashboard vergleichen.
5. Ersten Apply-Lauf einschließlich der Entfernung alter Rohdaten ausdrücklich freigeben. Alle historischen Dashboard-Werte danach vergleichen. Bei Fehlern oder abweichenden Zahlen stoppen.
6. Regelmäßige, überwachte Wartung und verantwortliche Fehlerbehandlung separat einrichten und freigeben. Kein öffentlicher Wartungs-Endpunkt vorgesehen. Verfügbarkeit von CLI/Cron beim Hoster ist noch offen.
7. Erst bei tatsächlich aktiver Verarbeitung den vorbereiteten Datenschutz-Diff freigeben und veröffentlichen. Poll-Fristen und Betreiberangaben bleiben davon unberührt.

Bis dahin ist C2 nur lokal vorbereitet und getestet; keine produktive Löschung, Migration oder Fristdurchsetzung.

Technischer Hintergrund: [MariaDB-Transaktionen](https://mariadb.com/docs/server/reference/sql-statements/transactions/start-transaction), [InnoDB-Sperren](https://dev.mysql.com/doc/refman/8.0/en/innodb-locks-set.html).
