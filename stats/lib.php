<?php
declare(strict_types=1);

const RF_STATS_TABLE = 'rf_stats_events';

function rf_stats_now(): DateTimeImmutable
{
    static $timezone = null;

    if (!$timezone instanceof DateTimeZone) {
        $timezone = new DateTimeZone('Europe/Berlin');
    }

    return new DateTimeImmutable('now', $timezone);
}

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

function rf_stats_clean_event_type(string $eventType): ?string
{
    $allowedTypes = ['pageview', 'kofi_click', 'support_click', 'wuerfel_click', 'riot_shop_click'];

    return in_array($eventType, $allowedTypes, true) ? $eventType : null;
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
    $cleanEventType = rf_stats_clean_event_type($eventType);

    if ($cleanEventType === null) {
        return;
    }

    $today = rf_stats_now()->format('Y-m-d');
    $statement = $pdo->prepare(
        'INSERT INTO ' . RF_STATS_TABLE . ' (event_date, event_type, path, section, visitor_day_hash)
         VALUES (:event_date, :event_type, :path, :section, :visitor_day_hash)'
    );

    $statement->execute([
        ':event_date' => $today,
        ':event_type' => $cleanEventType,
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

function rf_stats_periods(): array
{
    $today = rf_stats_now()->setTime(0, 0);
    $tomorrow = $today->modify('+1 day');
    $weekStart = $today->modify('-' . ((int) $today->format('N') - 1) . ' days');
    $monthStart = $today->modify('first day of this month');

    return [
        'today' => [
            'label' => 'Heute',
            'start' => $today->format('Y-m-d'),
            'end' => $tomorrow->format('Y-m-d'),
        ],
        'yesterday' => [
            'label' => 'Gestern',
            'start' => $today->modify('-1 day')->format('Y-m-d'),
            'end' => $today->format('Y-m-d'),
        ],
        '7d' => [
            'label' => '7 Tage',
            'start' => $today->modify('-6 days')->format('Y-m-d'),
            'end' => $tomorrow->format('Y-m-d'),
        ],
        '30d' => [
            'label' => '30 Tage',
            'start' => $today->modify('-29 days')->format('Y-m-d'),
            'end' => $tomorrow->format('Y-m-d'),
        ],
        'week' => [
            'label' => 'Diese Woche',
            'start' => $weekStart->format('Y-m-d'),
            'end' => $tomorrow->format('Y-m-d'),
        ],
        'month' => [
            'label' => 'Aktueller Monat',
            'start' => $monthStart->format('Y-m-d'),
            'end' => $monthStart->modify('+1 month')->format('Y-m-d'),
        ],
        'all' => [
            'label' => 'Gesamt',
            'start' => null,
            'end' => null,
        ],
    ];
}

function rf_stats_valid_range(string $requestedRange): string
{
    $allowedRanges = ['today', 'yesterday', '7d', '30d', 'month', 'all'];

    return in_array($requestedRange, $allowedRanges, true) ? $requestedRange : '30d';
}

function rf_stats_period_condition(array $period): array
{
    if ($period['start'] === null || $period['end'] === null) {
        return ['', []];
    }

    return [
        ' AND event_date >= :period_start AND event_date < :period_end',
        [
            ':period_start' => (string) $period['start'],
            ':period_end' => (string) $period['end'],
        ],
    ];
}

function rf_stats_pageview_totals(PDO $pdo, array $period): array
{
    [$condition, $params] = rf_stats_period_condition($period);
    $rows = rf_stats_rows(
        $pdo,
        'SELECT
            COUNT(*) AS pageviews,
            COUNT(DISTINCT CONCAT(event_date, ":", visitor_day_hash)) AS visitor_day_values
         FROM ' . RF_STATS_TABLE . '
         WHERE event_type = "pageview"' . $condition,
        $params
    );
    $row = $rows[0] ?? [];

    return [
        'visitor_day_values' => (int) ($row['visitor_day_values'] ?? 0),
        'pageviews' => (int) ($row['pageviews'] ?? 0),
    ];
}

function rf_stats_top_pages(PDO $pdo, array $period): array
{
    [$condition, $params] = rf_stats_period_condition($period);

    return rf_stats_rows(
        $pdo,
        'SELECT path, COUNT(*) AS count
         FROM ' . RF_STATS_TABLE . '
         WHERE event_type = "pageview"' . $condition . '
         GROUP BY path
         ORDER BY count DESC, path ASC
         LIMIT 10',
        $params
    );
}

function rf_stats_top_sections(PDO $pdo, array $period): array
{
    [$condition, $params] = rf_stats_period_condition($period);

    return rf_stats_rows(
        $pdo,
        'SELECT section, COUNT(*) AS count
         FROM ' . RF_STATS_TABLE . '
         WHERE event_type = "pageview"' . $condition . '
         GROUP BY section
         ORDER BY count DESC, section ASC',
        $params
    );
}

function rf_stats_randalf_pageviews(PDO $pdo, array $period): int
{
    [$condition, $params] = rf_stats_period_condition($period);

    return rf_stats_scalar(
        $pdo,
        'SELECT COUNT(*)
         FROM ' . RF_STATS_TABLE . '
         WHERE event_type = "pageview" AND section = "randalf"' . $condition,
        $params
    );
}

function rf_stats_event_totals(PDO $pdo, string $eventType, array $period): array
{
    [$condition, $params] = rf_stats_period_condition($period);
    $params[':event_type'] = $eventType;
    $rows = rf_stats_rows(
        $pdo,
        'SELECT
            COUNT(*) AS clicks,
            COUNT(DISTINCT CONCAT(event_date, ":", visitor_day_hash)) AS clicker_day_values
         FROM ' . RF_STATS_TABLE . '
         WHERE event_type = :event_type' . $condition,
        $params
    );
    $row = $rows[0] ?? [];

    return [
        'clicks' => (int) ($row['clicks'] ?? 0),
        'clicker_day_values' => (int) ($row['clicker_day_values'] ?? 0),
    ];
}

function rf_stats_daily_series(PDO $pdo): array
{
    $today = rf_stats_now()->setTime(0, 0);
    $start = $today->modify('-29 days');
    $end = $today->modify('+1 day');
    $rows = rf_stats_rows(
        $pdo,
        'SELECT
            event_date AS period_key,
            COUNT(*) AS pageviews,
            COUNT(DISTINCT visitor_day_hash) AS visitor_day_values
         FROM ' . RF_STATS_TABLE . '
         WHERE event_type = "pageview"
           AND event_date >= :period_start
           AND event_date < :period_end
         GROUP BY event_date
         ORDER BY event_date ASC',
        [
            ':period_start' => $start->format('Y-m-d'),
            ':period_end' => $end->format('Y-m-d'),
        ]
    );
    $indexedRows = [];

    foreach ($rows as $row) {
        $indexedRows[(string) $row['period_key']] = $row;
    }

    $series = [];

    for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
        $key = $date->format('Y-m-d');
        $row = $indexedRows[$key] ?? [];
        $series[] = [
            'period_key' => $key,
            'label' => $date->format('d.m.'),
            'visitor_day_values' => (int) ($row['visitor_day_values'] ?? 0),
            'pageviews' => (int) ($row['pageviews'] ?? 0),
        ];
    }

    return $series;
}

function rf_stats_monthly_series(PDO $pdo): array
{
    $currentMonth = rf_stats_now()->setTime(0, 0)->modify('first day of this month');
    $start = $currentMonth->modify('-11 months');
    $end = $currentMonth->modify('+1 month');
    $rows = rf_stats_rows(
        $pdo,
        'SELECT
            DATE_FORMAT(event_date, "%Y-%m") AS period_key,
            COUNT(*) AS pageviews,
            COUNT(DISTINCT CONCAT(event_date, ":", visitor_day_hash)) AS visitor_day_values
         FROM ' . RF_STATS_TABLE . '
         WHERE event_type = "pageview"
           AND event_date >= :period_start
           AND event_date < :period_end
         GROUP BY DATE_FORMAT(event_date, "%Y-%m")
         ORDER BY period_key ASC',
        [
            ':period_start' => $start->format('Y-m-d'),
            ':period_end' => $end->format('Y-m-d'),
        ]
    );
    $indexedRows = [];

    foreach ($rows as $row) {
        $indexedRows[(string) $row['period_key']] = $row;
    }

    $series = [];

    for ($month = $start; $month < $end; $month = $month->modify('+1 month')) {
        $key = $month->format('Y-m');
        $row = $indexedRows[$key] ?? [];
        $series[] = [
            'period_key' => $key,
            'label' => $month->format('m/y'),
            'visitor_day_values' => (int) ($row['visitor_day_values'] ?? 0),
            'pageviews' => (int) ($row['pageviews'] ?? 0),
        ];
    }

    return $series;
}

function rf_stats_dashboard_data(PDO $pdo, string $requestedRange = '30d'): array
{
    $periods = rf_stats_periods();
    $selectedRange = rf_stats_valid_range($requestedRange);
    $selectedPeriod = $periods[$selectedRange];
    $summaryPeriods = ['today', 'yesterday', 'week', 'month', '30d', 'all'];
    $summaries = [];

    foreach ($summaryPeriods as $periodKey) {
        $summaries[$periodKey] = rf_stats_pageview_totals($pdo, $periods[$periodKey]);
    }

    $selectedTotals = $summaries[$selectedRange]
        ?? rf_stats_pageview_totals($pdo, $selectedPeriod);
    $pageviewsPerVisitorDay = $selectedTotals['visitor_day_values'] > 0
        ? $selectedTotals['pageviews'] / $selectedTotals['visitor_day_values']
        : 0.0;
    $riotShopTotals = rf_stats_event_totals($pdo, 'riot_shop_click', $selectedPeriod);
    $riotShopInterestRate = $selectedTotals['visitor_day_values'] > 0
        ? ($riotShopTotals['clicker_day_values'] / $selectedTotals['visitor_day_values']) * 100
        : 0.0;

    return [
        'selected_range' => $selectedRange,
        'selected_range_label' => $selectedPeriod['label'],
        'periods' => $periods,
        'summaries' => $summaries,
        'selected_totals' => $selectedTotals,
        'pageviews_per_visitor_day' => $pageviewsPerVisitorDay,
        'randalf_pageviews' => rf_stats_randalf_pageviews($pdo, $selectedPeriod),
        'riot_shop_clicks' => $riotShopTotals['clicks'],
        'riot_shop_clicker_day_values' => $riotShopTotals['clicker_day_values'],
        'riot_shop_interest_rate' => $riotShopInterestRate,
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
        'top_pages' => rf_stats_top_pages($pdo, $selectedPeriod),
        'top_sections' => rf_stats_top_sections($pdo, $selectedPeriod),
        'daily_series' => rf_stats_daily_series($pdo),
        'monthly_series' => rf_stats_monthly_series($pdo),
    ];
}
