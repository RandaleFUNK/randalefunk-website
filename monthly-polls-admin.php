<?php
declare(strict_types=1);

define('RF_POLL_LIBRARY_ONLY', true);
require_once __DIR__ . '/poll.php';
require_once __DIR__ . '/stats/auth.php';

rf_stats_require_auth();

function rf_monthly_admin_award_label(string $awardType): string
{
    return $awardType === 'single_song' ? 'Single/Song' : 'Album/EP';
}

function rf_monthly_admin_poll(PDO $pdo, int $year, int $month, string $awardType): array
{
    $poll = rf_poll_monthly_by_period($pdo, $year, $month, $awardType);

    if ($poll !== null) {
        return $poll;
    }

    $pollId = rf_poll_create_monthly($pdo, $year, $month, $awardType);
    $poll = rf_poll_by_slug($pdo, rf_poll_monthly_slug($awardType, $year, $month));

    if ($poll === null) {
        throw new RuntimeException('Monatsumfrage konnte nicht angelegt werden.');
    }

    return $poll;
}

function rf_monthly_admin_save_candidates(PDO $pdo, int $year, int $month, string $awardType, string $candidateText): void
{
    $poll = rf_monthly_admin_poll($pdo, $year, $month, $awardType);

    if (($poll['starts_at'] ?? null) !== null) {
        throw new RuntimeException('Kandidaten koennen nur vor dem Start geaendert werden.');
    }

    $candidates = array_values(array_filter(array_map('trim', preg_split('/\R/u', $candidateText) ?: [])));

    if (count($candidates) !== 10) {
        throw new RuntimeException('Bitte genau 10 Kandidaten eintragen, je Zeile einen.');
    }

    $pdo->beginTransaction();

    try {
        $delete = $pdo->prepare('DELETE FROM ' . RF_POLL_OPTIONS_TABLE . ' WHERE poll_id = :poll_id');
        $delete->execute([':poll_id' => (int) $poll['id']]);

        $insert = $pdo->prepare(
            'INSERT INTO ' . RF_POLL_OPTIONS_TABLE . ' (poll_id, option_text, sort_order)
             VALUES (:poll_id, :option_text, :sort_order)'
        );

        foreach ($candidates as $index => $candidate) {
            $insert->execute([
                ':poll_id' => (int) $poll['id'],
                ':option_text' => $candidate,
                ':sort_order' => $index + 1,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function rf_monthly_admin_option_text(PDO $pdo, int $pollId): string
{
    $options = rf_poll_options($pdo, $pollId);
    $lines = array_map(static fn (array $option): string => (string) $option['option_text'], $options);

    return implode("\n", $lines);
}

function rf_monthly_admin_render_row(PDO $pdo, int $year, int $month, string $awardType): string
{
    $monthNames = rf_poll_month_names();
    $poll = rf_poll_monthly_by_period($pdo, $year, $month, $awardType);
    $status = $poll === null ? 'Folgt' : rf_poll_status($poll);
    $optionCount = $poll === null ? 0 : rf_poll_option_count($pdo, (int) $poll['id']);
    $canEdit = $poll === null || ($poll['starts_at'] ?? null) === null;
    $canStart = $poll !== null && $optionCount === 10 && ($poll['starts_at'] ?? null) === null;
    $resultLink = $poll !== null && ($poll['starts_at'] ?? null) !== null
        ? '<a href="poll.php?poll=' . rawurlencode((string) $poll['slug']) . '&action=results">Ergebnis</a>'
        : '';

    $html = '<article class="monthly-admin-card">';
    $html .= '<h2>' . rf_poll_escape($monthNames[$month] . ' ' . $year . ' - ' . rf_monthly_admin_award_label($awardType)) . '</h2>';
    $html .= '<p>Status: <strong>' . rf_poll_escape($status) . '</strong> | Kandidaten: ' . $optionCount . '</p>';
    $html .= $resultLink !== '' ? '<p>' . $resultLink . '</p>' : '';

    if ($canEdit) {
        $html .= '<form method="post" class="monthly-admin-form">';
        $html .= '<input type="hidden" name="action" value="save_candidates">';
        $html .= '<input type="hidden" name="year" value="' . $year . '">';
        $html .= '<input type="hidden" name="month" value="' . $month . '">';
        $html .= '<input type="hidden" name="award_type" value="' . rf_poll_escape($awardType) . '">';
        $html .= '<label>Kandidaten, je Zeile einer<textarea name="candidates" rows="6">' . rf_poll_escape($poll !== null ? rf_monthly_admin_option_text($pdo, (int) $poll['id']) : '') . '</textarea></label>';
        $html .= '<button type="submit">Kandidaten speichern</button>';
        $html .= '</form>';
    }

    $html .= '<form method="post" class="monthly-admin-form">';
    $html .= '<input type="hidden" name="action" value="start">';
    $html .= '<input type="hidden" name="year" value="' . $year . '">';
    $html .= '<input type="hidden" name="month" value="' . $month . '">';
    $html .= '<input type="hidden" name="award_type" value="' . rf_poll_escape($awardType) . '">';
    $html .= '<button type="submit"' . ($canStart ? '' : ' disabled') . '>Umfrage starten</button>';
    $html .= '</form>';
    $html .= '</article>';

    return $html;
}

$message = '';
$error = '';
$year = (int) ($_GET['year'] ?? $_POST['year'] ?? 2026);

try {
    if (!rf_stats_is_configured()) {
        throw new RuntimeException('Statistik-Datenbank ist nicht konfiguriert.');
    }

    $pdo = rf_stats_pdo();
    rf_poll_ensure_schema($pdo);
    rf_poll_close_expired($pdo);
    rf_poll_sync_yearly_candidates($pdo, $year, 'album_ep');
    rf_poll_sync_yearly_candidates($pdo, $year, 'single_song');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $month = (int) ($_POST['month'] ?? 0);
        $awardType = (string) ($_POST['award_type'] ?? '');

        if ((string) ($_POST['action'] ?? '') === 'save_candidates') {
            rf_monthly_admin_save_candidates($pdo, $year, $month, $awardType, (string) ($_POST['candidates'] ?? ''));
            $message = 'Kandidaten gespeichert.';
        }

        if ((string) ($_POST['action'] ?? '') === 'start') {
            $poll = rf_poll_start_monthly($pdo, $year, $month, $awardType);
            $message = 'Umfrage gestartet. Laufzeit bis ' . rf_poll_escape((string) ($poll['ends_at'] ?? ''));
        }
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monatsumfragen verwalten - RandaleFUNK.de</title>
    <link rel="stylesheet" href="style.css?v=20260629-mobile-menu-fix">
  </head>
  <body class="monthly-admin-page">
    <main class="monthly-admin">
      <h1>Monatsumfragen verwalten</h1>
      <p>Kandidaten festlegen, danach die Umfrage manuell starten. Ab Start laeuft sie automatisch 7 Tage.</p>

      <?php if ($message !== ''): ?>
        <p class="monthly-admin-notice"><?php echo rf_poll_escape($message); ?></p>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <p class="monthly-admin-error"><?php echo rf_poll_escape($error); ?></p>
      <?php endif; ?>

      <form method="get" class="monthly-admin-year">
        <label>Jahr <input type="number" name="year" min="2020" max="2100" value="<?php echo $year; ?>"></label>
        <button type="submit">anzeigen</button>
      </form>

      <section class="monthly-admin-grid" aria-label="Album/EP des Monats">
        <h2>Album/EP des Monats</h2>
        <?php
        if (isset($pdo)) {
            for ($month = 1; $month <= 12; $month++) {
                echo rf_monthly_admin_render_row($pdo, $year, $month, 'album_ep');
            }
        }
        ?>
      </section>

      <section class="monthly-admin-grid" aria-label="Single/Song des Monats">
        <h2>Single/Song des Monats</h2>
        <?php
        if (isset($pdo)) {
            for ($month = 1; $month <= 12; $month++) {
                echo rf_monthly_admin_render_row($pdo, $year, $month, 'single_song');
            }
        }
        ?>
      </section>
    </main>
  </body>
</html>
