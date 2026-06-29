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
- Ko-fi-Klicks
- Ko-fi-Klickende als grobe Tageshash-Zählung
- Klicks auf `Warum unterstützen?`
- Klickende auf `Warum unterstützen?` als grobe Tageshash-Zählung
- Würfel-App-Klicks
