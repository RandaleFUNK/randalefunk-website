<?php
declare(strict_types=1);

require_once __DIR__ . '/stats/lib.php';

const RF_POLLS_TABLE = 'rf_polls';
const RF_POLL_OPTIONS_TABLE = 'rf_poll_options';
const RF_POLL_VOTES_TABLE = 'rf_poll_votes';
const RF_POLL_COOKIE = 'rf_poll_token';
const RF_DEFAULT_POLL_SLUG = 'weekly-festivals-june-2026';

function rf_poll_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rf_poll_token(): string
{
    $token = (string) ($_COOKIE[RF_POLL_COOKIE] ?? '');

    if (preg_match('/^[a-f0-9]{64}$/', $token) === 1) {
        return $token;
    }

    $token = bin2hex(random_bytes(32));
    setcookie(RF_POLL_COOKIE, $token, [
        'expires' => time() + 31536000,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[RF_POLL_COOKIE] = $token;

    return $token;
}

function rf_poll_token_hash(): string
{
    $config = rf_stats_config();
    $salt = (string) ($config['hash_salt'] ?? 'randalefunk-poll');

    return hash('sha256', $salt . '|poll|' . rf_poll_token());
}

function rf_poll_ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ' . RF_POLLS_TABLE . ' (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(80) NULL,
            title VARCHAR(120) NOT NULL,
            question VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_slug (slug),
            KEY idx_active (is_active, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $columns = $pdo->query('SHOW COLUMNS FROM ' . RF_POLLS_TABLE . " LIKE 'slug'")->fetchAll();

    if (count($columns) === 0) {
        $pdo->exec('ALTER TABLE ' . RF_POLLS_TABLE . ' ADD COLUMN slug VARCHAR(80) NULL AFTER id');
    }

    $indexes = $pdo->query('SHOW INDEX FROM ' . RF_POLLS_TABLE . " WHERE Key_name = 'uniq_slug'")->fetchAll();

    if (count($indexes) === 0) {
        $pdo->exec('ALTER TABLE ' . RF_POLLS_TABLE . ' ADD UNIQUE KEY uniq_slug (slug)');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ' . RF_POLL_OPTIONS_TABLE . ' (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            poll_id INT UNSIGNED NOT NULL,
            option_text VARCHAR(255) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_poll_sort (poll_id, sort_order),
            CONSTRAINT fk_rf_poll_options_poll
                FOREIGN KEY (poll_id) REFERENCES ' . RF_POLLS_TABLE . ' (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ' . RF_POLL_VOTES_TABLE . ' (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            poll_id INT UNSIGNED NOT NULL,
            option_id INT UNSIGNED NOT NULL,
            voter_hash CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_poll_voter (poll_id, voter_hash),
            KEY idx_poll_option (poll_id, option_id),
            CONSTRAINT fk_rf_poll_votes_poll
                FOREIGN KEY (poll_id) REFERENCES ' . RF_POLLS_TABLE . ' (id)
                ON DELETE CASCADE,
            CONSTRAINT fk_rf_poll_votes_option
                FOREIGN KEY (option_id) REFERENCES ' . RF_POLL_OPTIONS_TABLE . ' (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    rf_poll_seed($pdo, RF_DEFAULT_POLL_SLUG, 'Umfrage der Woche', 'Welches Festival hast du am Wochenende besucht?', true, [
        'Hellfest',
        'Hurricane / Southside',
        'Pinkpop',
        'Keines davon',
        'Verstehe nur Bier',
    ]);
    rf_poll_seed($pdo, 'pennywise-keller-staub', 'Abstimmung', 'Pennywise im Keller oder im Staub?', false, [
        'Bin vor Ort!',
        'Ueberlege noch.',
        'Ueberlege noch, aber nicht wegen Pennywise.',
        'Haeh?!',
    ]);
}

function rf_poll_seed(PDO $pdo, string $slug, string $title, string $question, bool $isActive, array $options): void
{
    $statement = $pdo->prepare(
        'SELECT id
         FROM ' . RF_POLLS_TABLE . '
         WHERE slug = :slug
         LIMIT 1'
    );
    $statement->execute([':slug' => $slug]);
    $pollId = (int) $statement->fetchColumn();

    if ($pollId === 0 && $slug === RF_DEFAULT_POLL_SLUG) {
        $fallback = $pdo->prepare(
            'SELECT id
             FROM ' . RF_POLLS_TABLE . '
             WHERE question = :question
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
        );
        $fallback->execute([':question' => $question]);
        $pollId = (int) $fallback->fetchColumn();

        if ($pollId > 0) {
            $update = $pdo->prepare(
                'UPDATE ' . RF_POLLS_TABLE . '
                 SET slug = :slug, title = :title, is_active = :is_active
                 WHERE id = :id'
            );
            $update->execute([
                ':slug' => $slug,
                ':title' => $title,
                ':is_active' => $isActive ? 1 : 0,
                ':id' => $pollId,
            ]);
        }
    }

    if ($pollId === 0) {
        $insert = $pdo->prepare(
            'INSERT INTO ' . RF_POLLS_TABLE . ' (slug, title, question, is_active)
             VALUES (:slug, :title, :question, :is_active)'
        );
        $insert->execute([
            ':slug' => $slug,
            ':title' => $title,
            ':question' => $question,
            ':is_active' => $isActive ? 1 : 0,
        ]);
        $pollId = (int) $pdo->lastInsertId();
    }

    if ($isActive) {
        $deactivate = $pdo->prepare(
            'UPDATE ' . RF_POLLS_TABLE . '
             SET is_active = 0
             WHERE id <> :id'
        );
        $deactivate->execute([':id' => $pollId]);
    }

    $optionCount = $pdo->prepare(
        'SELECT COUNT(*)
         FROM ' . RF_POLL_OPTIONS_TABLE . '
         WHERE poll_id = :poll_id'
    );
    $optionCount->execute([':poll_id' => $pollId]);

    if ((int) $optionCount->fetchColumn() > 0) {
        return;
    }

    $optionStatement = $pdo->prepare(
        'INSERT INTO ' . RF_POLL_OPTIONS_TABLE . ' (poll_id, option_text, sort_order)
         VALUES (:poll_id, :option_text, :sort_order)'
    );

    foreach ($options as $index => $optionText) {
        $optionStatement->execute([
            ':poll_id' => $pollId,
            ':option_text' => $optionText,
            ':sort_order' => $index + 1,
        ]);
    }
}

function rf_poll_active(PDO $pdo): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, slug, title, question
         FROM ' . RF_POLLS_TABLE . '
         WHERE slug = :slug AND is_active = 1
         ORDER BY created_at DESC, id DESC
         LIMIT 1'
    );
    $statement->execute([':slug' => RF_DEFAULT_POLL_SLUG]);
    $poll = $statement->fetch();

    return is_array($poll) ? $poll : null;
}

function rf_poll_by_slug(PDO $pdo, string $slug): ?array
{
    if (preg_match('/^[a-z0-9-]{1,80}$/', $slug) !== 1) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id, slug, title, question
         FROM ' . RF_POLLS_TABLE . '
         WHERE slug = :slug
         LIMIT 1'
    );
    $statement->execute([':slug' => $slug]);
    $poll = $statement->fetch();

    return is_array($poll) ? $poll : null;
}

function rf_poll_options(PDO $pdo, int $pollId): array
{
    $statement = $pdo->prepare(
        'SELECT o.id, o.option_text, COUNT(v.id) AS votes
         FROM ' . RF_POLL_OPTIONS_TABLE . ' o
         LEFT JOIN ' . RF_POLL_VOTES_TABLE . ' v ON v.option_id = o.id
         WHERE o.poll_id = :poll_id
         GROUP BY o.id, o.option_text, o.sort_order
         ORDER BY o.sort_order ASC, o.id ASC'
    );
    $statement->execute([':poll_id' => $pollId]);

    return $statement->fetchAll();
}

function rf_poll_has_voted(PDO $pdo, int $pollId): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM ' . RF_POLL_VOTES_TABLE . '
         WHERE poll_id = :poll_id AND voter_hash = :voter_hash'
    );
    $statement->execute([
        ':poll_id' => $pollId,
        ':voter_hash' => rf_poll_token_hash(),
    ]);

    return (int) $statement->fetchColumn() > 0;
}

function rf_poll_record_vote(PDO $pdo, int $pollId, int $optionId): void
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM ' . RF_POLL_OPTIONS_TABLE . '
         WHERE id = :option_id AND poll_id = :poll_id'
    );
    $statement->execute([
        ':option_id' => $optionId,
        ':poll_id' => $pollId,
    ]);

    if ((int) $statement->fetchColumn() === 0) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT IGNORE INTO ' . RF_POLL_VOTES_TABLE . ' (poll_id, option_id, voter_hash)
         VALUES (:poll_id, :option_id, :voter_hash)'
    );
    $insert->execute([
        ':poll_id' => $pollId,
        ':option_id' => $optionId,
        ':voter_hash' => rf_poll_token_hash(),
    ]);
}

function rf_poll_render(array $poll, array $options, bool $showResults, string $message = ''): string
{
    $pollId = (int) $poll['id'];
    $pollSlug = (string) ($poll['slug'] ?? '');
    $pollAction = '/poll.php' . ($pollSlug !== '' ? '?poll=' . rawurlencode($pollSlug) : '');
    $totalVotes = array_reduce($options, static fn (int $sum, array $option): int => $sum + (int) $option['votes'], 0);
    $html = '<section class="poll-widget" aria-label="Umfrage der Woche">';
    $html .= '<p class="poll-widget__kicker">' . rf_poll_escape((string) $poll['title']) . '</p>';
    $html .= '<h2>' . rf_poll_escape((string) $poll['question']) . '</h2>';

    if ($message !== '') {
        $html .= '<p class="poll-widget__message">' . rf_poll_escape($message) . '</p>';
    }

    if ($showResults) {
        $html .= '<div class="poll-results" aria-label="Umfrageergebnisse">';

        foreach ($options as $option) {
            $votes = (int) $option['votes'];
            $percent = $totalVotes > 0 ? (int) round(($votes / $totalVotes) * 100) : 0;
            $html .= '<div class="poll-result">';
            $html .= '<div class="poll-result__line"><span>' . rf_poll_escape((string) $option['option_text']) . '</span><strong>' . $percent . '%</strong></div>';
            $html .= '<div class="poll-result__bar" aria-hidden="true"><span style="width: ' . $percent . '%"></span></div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '<p class="poll-widget__total">' . $totalVotes . ' Stimme' . ($totalVotes === 1 ? '' : 'n') . '</p>';
    } else {
        $html .= '<form class="poll-form" method="post" action="' . rf_poll_escape($pollAction) . '" data-poll-form>';
        $html .= '<input type="hidden" name="action" value="vote">';
        $html .= '<input type="hidden" name="poll_id" value="' . $pollId . '">';

        foreach ($options as $option) {
            $optionId = (int) $option['id'];
            $html .= '<label class="poll-option">';
            $html .= '<input type="radio" name="option_id" value="' . $optionId . '" required>';
            $html .= '<span>' . rf_poll_escape((string) $option['option_text']) . '</span>';
            $html .= '</label>';
        }

        $html .= '<button class="poll-submit" type="submit">Abstimmen</button>';
        $html .= '</form>';
        $html .= '<button class="poll-results-link" type="button" data-poll-results>Ergebnisse ansehen</button>';
    }

    $html .= '</section>';

    return $html;
}

header('Content-Type: text/html; charset=utf-8');

if (!rf_stats_is_configured()) {
    echo '<section class="poll-widget poll-widget--quiet" aria-label="Umfrage der Woche"><p class="poll-widget__kicker">Umfrage der Woche</p><h2>Umfrage gerade im Proberaum.</h2></section>';
    exit;
}

try {
    $pdo = rf_stats_pdo();
    rf_poll_ensure_schema($pdo);
    $requestedPoll = (string) ($_GET['poll'] ?? '');
    $poll = $requestedPoll !== '' ? rf_poll_by_slug($pdo, $requestedPoll) : rf_poll_active($pdo);

    if ($poll === null) {
        echo '<section class="poll-widget poll-widget--quiet" aria-label="Umfrage der Woche"><p class="poll-widget__kicker">Umfrage der Woche</p><h2>Gerade keine Umfrage aktiv.</h2></section>';
        exit;
    }

    $pollId = (int) $poll['id'];
    $action = (string) ($_POST['action'] ?? $_GET['action'] ?? 'widget');
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'vote') {
        rf_poll_record_vote($pdo, $pollId, (int) ($_POST['option_id'] ?? 0));
        $message = 'Danke. Hier ist der Zwischenstand.';
    }

    $showResults = $action === 'results' || $message !== '' || rf_poll_has_voted($pdo, $pollId);

    echo rf_poll_render($poll, rf_poll_options($pdo, $pollId), $showResults, $message);
} catch (Throwable) {
    http_response_code(500);
    echo '<section class="poll-widget poll-widget--quiet" aria-label="Umfrage der Woche"><p class="poll-widget__kicker">Umfrage der Woche</p><h2>Die Umfrage klemmt gerade.</h2></section>';
}
