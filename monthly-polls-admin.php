<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

final class RfMonthlyAdminInputException extends RuntimeException
{
}

try {
    define('RF_POLL_LIBRARY_ONLY', true);
    require_once __DIR__ . '/poll.php';
    require_once __DIR__ . '/stats/auth.php';

    // Reject empty passwords here without changing the shared statistics login.
    [$adminUser, $adminPassword] = rf_stats_basic_auth_credentials();
    if ($adminPassword === '') {
        rf_stats_auth_required('RandaleFUNK Monatsumfragen');
    }
    rf_stats_require_auth();
    unset($adminPassword);

    session_name('rf_monthly_admin');
    if (!session_start([
        'use_strict_mode' => 1,
        'use_only_cookies' => 1,
        'use_trans_sid' => 0,
        'cookie_lifetime' => 0,
        'cookie_path' => '/monthly-polls-admin.php',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ])) {
        throw new RuntimeException('Admin session unavailable.');
    }

    if (($_SESSION['admin_user'] ?? null) !== $adminUser || !is_string($_SESSION['csrf_token'] ?? null)) {
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Admin session renewal failed.');
        }
        $_SESSION = ['admin_user' => $adminUser, 'csrf_token' => bin2hex(random_bytes(32))];
    }
    $csrfToken = $_SESSION['csrf_token'];
    if (!session_write_close()) {
        throw new RuntimeException('Admin session could not be saved.');
    }

    // Check before any database access, including schema and lifecycle helpers.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $submittedToken = $_POST['csrf_token'] ?? null;
        if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Sicherheitsprüfung fehlgeschlagen. Bitte die Verwaltungsseite neu laden und erneut versuchen.';
            exit;
        }
    }
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Die Umfrage-Verwaltung ist gerade nicht verfügbar. Bitte später erneut versuchen.';
    exit;
}

function rf_monthly_admin_lock_unstarted(PDO $pdo, int $pollId): void
{
    $statement = $pdo->prepare('SELECT starts_at FROM ' . RF_POLLS_TABLE . ' WHERE id = :id FOR UPDATE');
    $statement->execute([':id' => $pollId]);
    $poll = $statement->fetch();

    if (!is_array($poll)) {
        throw new RfMonthlyAdminInputException('Diese Monatsumfrage wurde nicht gefunden.');
    }
    if ($poll['starts_at'] !== null) {
        throw new RfMonthlyAdminInputException('Diese Monatsumfrage wurde bereits gestartet. Ein erneuter Start oder eine Kandidatenänderung ist nicht erlaubt.');
    }
}

function rf_monthly_admin_start(PDO $pdo, int $year, int $month, string $awardType): array
{
    $pdo->beginTransaction();
    try {
        $poll = rf_poll_monthly_by_period($pdo, $year, $month, $awardType);
        if ($poll === null) {
            throw new RfMonthlyAdminInputException('Bitte zuerst genau 10 Kandidaten speichern.');
        }
        rf_monthly_admin_lock_unstarted($pdo, (int) $poll['id']);
        if (rf_poll_option_count($pdo, (int) $poll['id']) !== 10) {
            throw new RfMonthlyAdminInputException('Diese Monatsumfrage braucht genau 10 Kandidaten.');
        }
        $poll = rf_poll_start_monthly($pdo, $year, $month, $awardType);
        $pdo->commit();
        return $poll;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

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
    $candidates = array_values(array_filter(array_map('trim', preg_split('/\R/u', $candidateText) ?: [])));

    if (count($candidates) !== 10) {
        throw new RfMonthlyAdminInputException('Bitte genau 10 Kandidaten eintragen, je Zeile einen.');
    }

    $pdo->beginTransaction();

    try {
        $poll = rf_monthly_admin_poll($pdo, $year, $month, $awardType);
        rf_monthly_admin_lock_unstarted($pdo, (int) $poll['id']);
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

function rf_monthly_admin_render_row(PDO $pdo, int $year, int $month, string $awardType, string $csrfToken): string
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
        $html .= '<input type="hidden" name="csrf_token" value="' . rf_poll_escape($csrfToken) . '">';
        $html .= '<input type="hidden" name="action" value="save_candidates">';
        $html .= '<input type="hidden" name="year" value="' . $year . '">';
        $html .= '<input type="hidden" name="month" value="' . $month . '">';
        $html .= '<input type="hidden" name="award_type" value="' . rf_poll_escape($awardType) . '">';
        $html .= '<label>Kandidaten, je Zeile einer<textarea name="candidates" rows="6">' . rf_poll_escape($poll !== null ? rf_monthly_admin_option_text($pdo, (int) $poll['id']) : '') . '</textarea></label>';
        $html .= '<button type="submit">Kandidaten speichern</button>';
        $html .= '</form>';
    }

    $html .= '<form method="post" class="monthly-admin-form">';
    $html .= '<input type="hidden" name="csrf_token" value="' . rf_poll_escape($csrfToken) . '">';
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
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$year = filter_var($isPost ? ($_POST['year'] ?? null) : ($_GET['year'] ?? 2026), FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 2020, 'max_range' => 2100]]);

try {
    if ($year === false) {
        $year = 2026;
        throw new RfMonthlyAdminInputException('Bitte ein Jahr zwischen 2020 und 2100 angeben.');
    }
    if ($isPost) {
        $month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $awardType = $_POST['award_type'] ?? null;
        $action = $_POST['action'] ?? null;
        if ($month === false || !in_array($awardType, ['album_ep', 'single_song'], true)
            || !in_array($action, ['save_candidates', 'start'], true)
            || ($action === 'save_candidates' && !is_string($_POST['candidates'] ?? null))) {
            throw new RfMonthlyAdminInputException('Ungültige Eingabe. Bitte das Formular erneut ausfüllen.');
        }
    }
    if (!rf_stats_is_configured()) {
        throw new RuntimeException('Statistik-Datenbank ist nicht konfiguriert.');
    }

    $pdo = rf_stats_pdo();
    rf_poll_ensure_schema($pdo);
    rf_poll_close_expired($pdo);
    rf_poll_sync_yearly_candidates($pdo, $year, 'album_ep');
    rf_poll_sync_yearly_candidates($pdo, $year, 'single_song');

    if ($isPost) {
        if ($action === 'save_candidates') {
            rf_monthly_admin_save_candidates($pdo, $year, $month, $awardType, (string) ($_POST['candidates'] ?? ''));
            $message = 'Kandidaten gespeichert.';
        }

        if ($action === 'start') {
            $poll = rf_monthly_admin_start($pdo, $year, $month, $awardType);
            $message = 'Umfrage gestartet. Laufzeit bis ' . rf_poll_escape((string) ($poll['ends_at'] ?? ''));
        }
    }
} catch (RfMonthlyAdminInputException $exception) {
    http_response_code(400);
    $error = $exception->getMessage();
} catch (Throwable $exception) {
    http_response_code(500);
    $error = 'Die Umfrage-Verwaltung ist gerade nicht verfügbar. Bitte später erneut versuchen.';
    unset($pdo);
}

// Render inside the error boundary so database failures cannot leak details.
$albumRows = '';
$singleRows = '';
try {
    if (isset($pdo)) {
        for ($month = 1; $month <= 12; $month++) {
            $albumRows .= rf_monthly_admin_render_row($pdo, $year, $month, 'album_ep', $csrfToken);
            $singleRows .= rf_monthly_admin_render_row($pdo, $year, $month, 'single_song', $csrfToken);
        }
    }
} catch (Throwable $exception) {
    http_response_code(500);
    $message = '';
    $error = 'Die Umfrage-Verwaltung ist gerade nicht verfügbar. Bitte später erneut versuchen.';
    $albumRows = '';
    $singleRows = '';
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
        <?php echo $albumRows; ?>
      </section>

      <section class="monthly-admin-grid" aria-label="Single/Song des Monats">
        <h2>Single/Song des Monats</h2>
        <?php echo $singleRows; ?>
      </section>
    </main>
  </body>
</html>
