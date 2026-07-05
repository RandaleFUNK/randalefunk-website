<?php
declare(strict_types=1);

define('RF_POLL_LIBRARY_ONLY', true);
require_once __DIR__ . '/poll.php';

function rf_monthly_poll_winner_text(PDO $pdo, ?int $optionId): string
{
    if ($optionId === null || $optionId <= 0) {
        return '';
    }

    $statement = $pdo->prepare(
        'SELECT option_text
         FROM ' . RF_POLL_OPTIONS_TABLE . '
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute([':id' => $optionId]);

    return (string) ($statement->fetchColumn() ?: '');
}

function rf_monthly_poll_month_html(PDO $pdo, int $year, int $month, string $awardType): string
{
    $monthNames = rf_poll_month_names();
    $poll = rf_poll_monthly_by_period($pdo, $year, $month, $awardType);
    $time = sprintf('<time datetime="%04d-%02d">%s</time>', $year, $month, rf_poll_escape($monthNames[$month]));

    if ($poll === null) {
        $now = new DateTimeImmutable('now');
        $isPast = $year < (int) $now->format('Y') || ($year === (int) $now->format('Y') && $month < (int) $now->format('n'));
        $status = $isPast ? 'keine Abstimmung' : 'folgt';
        $class = $isPast ? 'is-missed' : 'is-upcoming';

        return '<article class="award-month ' . $class . '">' . $time . '<span>' . $status . '</span></article>';
    }

    $status = rf_poll_status($poll);
    $slug = (string) $poll['slug'];

    if ($status === 'Jetzt abstimmen') {
        return '<article class="award-month is-active">' . $time . '<a href="poll.php?poll=' . rawurlencode($slug) . '">Jetzt abstimmen</a></article>';
    }

    if ($status === 'Gewinner') {
        $winner = rf_monthly_poll_winner_text($pdo, $poll['winner_option_id'] !== null ? (int) $poll['winner_option_id'] : null);
        $label = $winner !== '' ? 'Gewinner: ' . $winner : 'Gewinner';

        return '<article class="award-month is-winner">' . $time . '<span>' . rf_poll_escape($label) . '</span><a href="poll.php?poll=' . rawurlencode($slug) . '&action=results">Ergebnis</a></article>';
    }

    if ($status === 'Abgeschlossen') {
        return '<article class="award-month is-closed">' . $time . '<span>Abgeschlossen</span><a href="poll.php?poll=' . rawurlencode($slug) . '&action=results">Ergebnis</a></article>';
    }

    return '<article class="award-month is-upcoming">' . $time . '<span>folgt</span></article>';
}

function rf_monthly_poll_grid_html(PDO $pdo, int $year, string $awardType): string
{
    $html = '';

    for ($month = 1; $month <= 12; $month++) {
        $html .= rf_monthly_poll_month_html($pdo, $year, $month, $awardType);
    }

    return $html;
}

function rf_monthly_poll_year_award_html(PDO $pdo, int $year, string $awardType): string
{
    $poll = rf_poll_yearly_by_period($pdo, $year, $awardType);

    if ($poll === null || ($poll['starts_at'] ?? null) === null) {
        return '<p>Die Monatssieger treten am Jahresende um den Rostigen Kronkorken gegeneinander an.</p>';
    }

    $slug = (string) $poll['slug'];
    $status = rf_poll_status($poll);

    if ($status === 'Jetzt abstimmen') {
        return '<p><a href="poll.php?poll=' . rawurlencode($slug) . '">Jetzt abstimmen</a></p>';
    }

    return '<p><a href="poll.php?poll=' . rawurlencode($slug) . '&action=results">Ergebnis ansehen</a></p>';
}

header('Content-Type: application/json; charset=utf-8');

if (!rf_stats_is_configured()) {
    echo json_encode([
        'album_ep' => '',
        'single_song' => '',
    ]);
    exit;
}

try {
    $pdo = rf_stats_pdo();
    rf_poll_ensure_schema($pdo);
    rf_poll_close_expired($pdo);
    $year = (int) ($_GET['year'] ?? 2026);
    rf_poll_sync_yearly_candidates($pdo, $year, 'album_ep');
    rf_poll_sync_yearly_candidates($pdo, $year, 'single_song');

    echo json_encode([
        'album_ep' => rf_monthly_poll_grid_html($pdo, $year, 'album_ep'),
        'single_song' => rf_monthly_poll_grid_html($pdo, $year, 'single_song'),
        'year_album_ep' => rf_monthly_poll_year_award_html($pdo, $year, 'album_ep'),
        'year_single_song' => rf_monthly_poll_year_award_html($pdo, $year, 'single_song'),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode([
        'album_ep' => '',
        'single_song' => '',
    ]);
}
