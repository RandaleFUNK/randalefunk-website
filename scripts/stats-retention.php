<?php
declare(strict_types=1);

// Never expose maintenance over HTTP, even if copied to a public directory by mistake.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once dirname(__DIR__) . '/stats/lib.php';

function rf_retention_schema(PDO $pdo, bool $apply): bool
{
    $available = rf_stats_rollups_available($pdo);
    if ($apply && !$available) {
        throw new RuntimeException('Zuerst die getrennte, freigegebene Schema-Vorbereitung ausfuehren.');
    }
    $tables = $available
        ? [RF_STATS_TABLE, 'rf_stats_daily_totals', 'rf_stats_daily_dimensions']
        : [RF_STATS_TABLE];
    foreach ($tables as $table) {
        $rows = rf_stats_rows($pdo,
            'SELECT ENGINE FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :name', [':name' => $table]);
        if (strtoupper((string) ($rows[0]['ENGINE'] ?? '')) !== 'INNODB') {
            throw new RuntimeException('Alle beteiligten Tabellen muessen InnoDB verwenden.');
        }
    }
    if ($available) {
        $expected = [
            'rf_stats_daily_totals' => ['event_date', 'event_type', 'event_count', 'visitor_day_values'],
            'rf_stats_daily_dimensions' => ['event_date', 'event_type', 'path', 'section', 'event_count'],
        ];
        foreach ($expected as $table => $columns) {
            $actual = rf_stats_rows($pdo,
                'SELECT COLUMN_NAME FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = :name ORDER BY ORDINAL_POSITION',
                [':name' => $table]);
            if (array_column($actual, 'COLUMN_NAME') !== $columns) {
                throw new RuntimeException('Unerwartetes Aggregationsschema; keine Migration.');
            }
        }
        $mismatches = rf_stats_scalar($pdo,
            'SELECT COUNT(*) FROM information_schema.columns a
             JOIN information_schema.columns r
               ON r.table_schema = a.table_schema AND r.column_name = a.column_name
              AND r.table_name = "rf_stats_events"
             WHERE a.table_schema = DATABASE()
               AND a.table_name IN ("rf_stats_daily_totals", "rf_stats_daily_dimensions")
               AND a.column_name IN ("event_type", "path", "section")
               AND NOT (a.collation_name <=> r.collation_name)');
        if ($mismatches > 0) {
            throw new RuntimeException('Unterschiedliche Textsortierung im Schema; keine Migration.');
        }
    }
    return $available;
}

function rf_retention_normalize(array $rows): array
{
    foreach ($rows as &$row) {
        foreach (['event_count', 'visitor_day_values'] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = (int) $row[$key];
            }
        }
    }
    unset($row);
    return $rows;
}

function rf_retention_run(PDO $pdo, bool $apply = false, ?callable $showPlan = null): array
{
    if ($pdo->inTransaction()) {
        throw new RuntimeException('Keine Migration innerhalb einer bestehenden Transaktion.');
    }
    $available = rf_retention_schema($pdo, $apply);
    $cutoff = rf_stats_now()->setTime(0, 0)->modify('-90 days')->format('Y-m-d');
    $params = [':cutoff' => $cutoff];
    $lockName = 'rf_stats_retention_' . substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
    $locked = false;

    try {
        if ($apply) {
            if (rf_stats_scalar($pdo, 'SELECT GET_LOCK(:name, 0)', [':name' => $lockName]) !== 1) {
                throw new RuntimeException('Ein anderer Wartungslauf ist aktiv; keine Migration.');
            }
            $locked = true;
        }
        $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->exec($apply ? 'SET TRANSACTION READ WRITE' : 'SET TRANSACTION READ ONLY');
        $pdo->beginTransaction();

        if ($apply) {
            // Lock the entire eligible date range before taking the consistent aggregation snapshot.
            $statement = $pdo->prepare(
                'SELECT id FROM rf_stats_events WHERE event_date < :cutoff ORDER BY event_date, id FOR UPDATE'
            );
            $statement->execute($params);
            while ($statement->fetchColumn() !== false) {
                // Consume the locking read; no raw identifiers enter a report.
            }
        }

        $days = 'SELECT DISTINCT event_date FROM rf_stats_events WHERE event_date < :cutoff';
        if ($available) {
            $overlap = rf_stats_scalar($pdo,
                'SELECT COUNT(*) FROM (' . $days . ') raw_days
                 JOIN (SELECT event_date FROM rf_stats_daily_totals
                       UNION SELECT event_date FROM rf_stats_daily_dimensions) archived USING (event_date)', $params);
            if ($overlap > 0) {
                throw new RuntimeException('Rohdaten fuer bereits aggregierte Tage gefunden; nicht verlustfrei zusammenfuehrbar. Keine Migration.');
            }
        }
        $summary = rf_stats_rows($pdo,
            'SELECT COUNT(*) AS rows_to_delete, MIN(event_date) AS first_day, MAX(event_date) AS last_day
             FROM rf_stats_events WHERE event_date < :cutoff', $params)[0];
        $totalSelect = 'SELECT event_date, event_type, COUNT(*) AS event_count,
                              COUNT(DISTINCT visitor_day_hash) AS visitor_day_values
                       FROM rf_stats_events WHERE event_date < :cutoff GROUP BY event_date, event_type';
        $dimensionSelect = 'SELECT event_date, event_type, path, section, COUNT(*) AS event_count
                           FROM rf_stats_events WHERE event_date < :cutoff GROUP BY event_date, event_type, path, section';
        $totals = rf_retention_normalize(rf_stats_rows($pdo, $totalSelect . ' ORDER BY event_date, event_type', $params));
        $dimensions = rf_retention_normalize(rf_stats_rows($pdo, $dimensionSelect . ' ORDER BY event_date, event_type, path, section', $params));
        $count = (int) $summary['rows_to_delete'];
        if (array_sum(array_column($totals, 'event_count')) !== $count
            || array_sum(array_column($dimensions, 'event_count')) !== $count) {
            throw new RuntimeException('Summenpruefung fehlgeschlagen; keine Migration.');
        }
        $plan = [
            'mode' => $apply ? 'apply' : 'dry-run',
            'cutoff_exclusive' => $cutoff,
            'timezone' => 'Europe/Berlin',
            'rows_to_delete' => $count,
            'first_day' => $summary['first_day'],
            'last_day' => $summary['last_day'],
            'schema_ready' => $available,
            'daily_totals' => $totals,
            'daily_dimensions' => $dimensions,
        ];
        if ($showPlan !== null) {
            $showPlan($plan);
        }
        if (!$apply || $count === 0) {
            $pdo->rollBack();
            return ['plan' => $plan, 'deleted' => 0, 'committed' => false];
        }

        // No DDL here: both rollups and raw-row removal belong to one InnoDB transaction.
        $insert = $pdo->prepare(
            'INSERT INTO rf_stats_daily_totals (event_date, event_type, event_count, visitor_day_values) ' . $totalSelect
        );
        $insert->execute($params);
        $insert = $pdo->prepare(
            'INSERT INTO rf_stats_daily_dimensions (event_date, event_type, path, section, event_count) ' . $dimensionSelect
        );
        $insert->execute($params);

        $savedTotals = rf_retention_normalize(rf_stats_rows($pdo,
            'SELECT a.event_date, a.event_type, a.event_count, a.visitor_day_values
             FROM rf_stats_daily_totals a JOIN (' . $days . ') d USING (event_date)
             ORDER BY a.event_date, a.event_type', $params));
        $savedDimensions = rf_retention_normalize(rf_stats_rows($pdo,
            'SELECT a.event_date, a.event_type, a.path, a.section, a.event_count
             FROM rf_stats_daily_dimensions a JOIN (' . $days . ') d USING (event_date)
             ORDER BY a.event_date, a.event_type, a.path, a.section', $params));
        if ($savedTotals !== $totals || $savedDimensions !== $dimensions) {
            throw new RuntimeException('Gespeicherte Summen weichen vom Plan ab; Rollback.');
        }

        $delete = $pdo->prepare('DELETE FROM rf_stats_events WHERE event_date < :cutoff');
        $delete->execute($params);
        if ($delete->rowCount() !== $count) {
            throw new RuntimeException('Unerwartete Anzahl Einzelereignisse; Rollback.');
        }
        $pdo->commit();
        return ['plan' => $plan, 'deleted' => $count, 'committed' => true];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    } finally {
        if ($locked) {
            rf_stats_scalar($pdo, 'SELECT RELEASE_LOCK(:name)', [':name' => $lockName]);
        }
    }
}

if (!defined('RF_STATS_RETENTION_LIBRARY_ONLY')) {
    $args = array_slice($argv, 1);
    $allowed = ['--dry-run', '--apply', '--confirm=AGGREGATE_AND_DELETE_OLD_EVENTS'];
    $apply = in_array('--apply', $args, true);
    if (array_diff($args, $allowed) !== []
        || ($apply && in_array('--dry-run', $args, true))
        || ($apply !== in_array('--confirm=AGGREGATE_AND_DELETE_OLD_EVENTS', $args, true))) {
        fwrite(STDERR, "Dry-Run: php scripts/stats-retention.php --dry-run\n");
        fwrite(STDERR, "Nur nach separater Freigabe: --apply --confirm=AGGREGATE_AND_DELETE_OLD_EVENTS\n");
        exit(2);
    }
    try {
        $result = rf_retention_run(rf_stats_pdo(), $apply, static function (array $plan): void {
            echo json_encode(['before_changes' => $plan], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
            fflush(STDOUT);
        });
        echo json_encode(['deleted' => $result['deleted'], 'committed' => $result['committed']], JSON_THROW_ON_ERROR) . PHP_EOL;
    } catch (Throwable $error) {
        $message = $error instanceof PDOException ? 'Datenbankfehler; Zustand vor einem erneuten Lauf pruefen.' : $error->getMessage();
        fwrite(STDERR, 'Retention abgebrochen: ' . $message . PHP_EOL);
        exit(1);
    }
}
