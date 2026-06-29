<?php
declare(strict_types=1);

const RF_STATS_TABLE = 'rf_stats_events';

function rf_stats_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $configPath = __DIR__ . '/config.php';

    if (!is_file($configPath)) {
        return $config = [];
    }

    $loadedConfig = require $configPath;

    return $config = is_array($loadedConfig) ? $loadedConfig : [];
}

function rf_stats_is_configured(): bool
{
    $config = rf_stats_config();
    $db = $config['db'] ?? [];

    return !empty($db['host']) && !empty($db['name']) && !empty($db['user']);
}

function rf_stats_pdo(): PDO
{
    $config = rf_stats_config();
    $db = $config['db'] ?? [];

    if (!rf_stats_is_configured()) {
        throw new RuntimeException('Statistik-Datenbank ist nicht konfiguriert.');
    }

    $charset = $db['charset'] ?? 'utf8mb4';
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $charset);

    return new PDO($dsn, (string) $db['user'], (string) ($db['password'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function rf_stats_ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ' . RF_STATS_TABLE . ' (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            event_date DATE NOT NULL,
            event_type VARCHAR(32) NOT NULL,
            path VARCHAR(255) NOT NULL,
            section VARCHAR(48) NOT NULL,
            visitor_day_hash CHAR(64) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_event_date (event_date),
            KEY idx_event_type (event_type),
            KEY idx_path (path),
            KEY idx_section (section),
            KEY idx_visitor_day (event_date, visitor_day_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function rf_stats_section_from_path(string $path): string
{
    $path = strtolower($path);

    if (str_contains($path, 'vorab-gehoert') || str_contains($path, '#vorab')) {
        return 'vorab';
    }

    if (str_contains($path, 'reviews') || str_contains($path, '#reviews')) {
        return 'reviews';
    }

    if (str_contains($path, 'randalf')) {
        return 'randalf';
    }

    if (str_contains($path, 'wuerfel')) {
        return 'wuerfel';
    }

    if (str_contains($path, '#interviews')) {
        return 'interviews';
    }

    if (str_contains($path, '#kolumnen')) {
        return 'kolumnen';
    }

    return 'news';
}

function rf_stats_clean_event_type(string $eventType): string
{
    $allowedTypes = ['pageview', 'kofi_click', 'support_click', 'wuerfel_click'];

    return in_array($eventType, $allowedTypes, true) ? $eventType : 'pageview';
}

function rf_stats_clean_path(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        return '/';
    }

    if (preg_match('/^https?:\/\//i', $path) === 1) {
        $parts = parse_url($path);
        $path = ($parts['path'] ?? '/') . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }

    $path = preg_replace('/[^\pL\pN\-._~\/#]/u', '', $path) ?: '/';

    return substr($path, 0, 255);
}

function rf_stats_clean_section(string $section, string $path): string
{
    $section = strtolower(trim($section));
    $allowedSections = ['news', 'vorab', 'reviews', 'interviews', 'kolumnen', 'randalf', 'wuerfel', 'sonstiges'];

    if (in_array($section, $allowedSections, true)) {
        return $section;
    }

    return rf_stats_section_from_path($path);
}

function rf_stats_client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

function rf_stats_visitor_day_hash(string $date): string
{
    $config = rf_stats_config();
    $salt = (string) ($config['hash_salt'] ?? 'randalefunk-stats');
    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    return hash('sha256', $salt . '|' . $date . '|' . rf_stats_client_ip() . '|' . $userAgent);
}

function rf_stats_record_event(PDO $pdo, string $eventType, string $path, string $section): void
{
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');
    $statement = $pdo->prepare(
        'INSERT INTO ' . RF_STATS_TABLE . ' (event_date, event_type, path, section, visitor_day_hash)
         VALUES (:event_date, :event_type, :path, :section, :visitor_day_hash)'
    );

    $statement->execute([
        ':event_date' => $today,
        ':event_type' => rf_stats_clean_event_type($eventType),
        ':path' => rf_stats_clean_path($path),
        ':section' => rf_stats_clean_section($section, $path),
        ':visitor_day_hash' => rf_stats_visitor_day_hash($today),
    ]);
}

function rf_stats_scalar(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return (int) $statement->fetchColumn();
}

function rf_stats_rows(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function rf_stats_dashboard_data(PDO $pdo): array
{
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');
    $monthStart = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');

    return [
        'visitors_today' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(DISTINCT visitor_day_hash) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "pageview" AND event_date = :today',
            [':today' => $today]
        ),
        'visitors_month' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(DISTINCT CONCAT(event_date, ":", visitor_day_hash)) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "pageview" AND event_date >= :month_start',
            [':month_start' => $monthStart]
        ),
        'visitors_total' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(DISTINCT CONCAT(event_date, ":", visitor_day_hash)) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "pageview"'
        ),
        'pageviews_total' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(*) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "pageview"'
        ),
        'kofi_clicks' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(*) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "kofi_click"'
        ),
        'kofi_clickers_total' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(DISTINCT CONCAT(event_date, ":", visitor_day_hash)) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "kofi_click"'
        ),
        'support_clicks' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(*) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "support_click"'
        ),
        'support_clickers_total' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(DISTINCT CONCAT(event_date, ":", visitor_day_hash)) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "support_click"'
        ),
        'wuerfel_clicks' => rf_stats_scalar(
            $pdo,
            'SELECT COUNT(*) FROM ' . RF_STATS_TABLE . ' WHERE event_type = "wuerfel_click"'
        ),
        'top_pages' => rf_stats_rows(
            $pdo,
            'SELECT path, COUNT(*) AS count FROM ' . RF_STATS_TABLE . ' WHERE event_type = "pageview" GROUP BY path ORDER BY count DESC, path ASC LIMIT 10'
        ),
        'top_sections' => rf_stats_rows(
            $pdo,
            'SELECT section, COUNT(*) AS count FROM ' . RF_STATS_TABLE . ' WHERE event_type = "pageview" GROUP BY section ORDER BY count DESC, section ASC'
        ),
    ];
}
