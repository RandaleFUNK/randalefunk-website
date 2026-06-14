<?php
declare(strict_types=1);

require_once __DIR__ . '/stats/lib.php';

http_response_code(204);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

if ((string) ($_SERVER['HTTP_DNT'] ?? '') === '1') {
    exit;
}

if (!rf_stats_is_configured()) {
    exit;
}

$rawBody = file_get_contents('php://input') ?: '';
$payload = json_decode($rawBody, true);

if (!is_array($payload)) {
    $payload = $_POST;
}

$eventType = rf_stats_clean_event_type((string) ($payload['event_type'] ?? 'pageview'));
$path = rf_stats_clean_path((string) ($payload['path'] ?? '/'));
$section = rf_stats_clean_section((string) ($payload['section'] ?? ''), $path);

try {
    $pdo = rf_stats_pdo();
    rf_stats_ensure_schema($pdo);
    rf_stats_record_event($pdo, $eventType, $path, $section);
} catch (Throwable) {
    // Tracking darf die Website niemals kaputtmachen.
    exit;
}
