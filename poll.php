<?php
declare(strict_types=1);

require_once __DIR__ . '/stats/lib.php';

const RF_POLLS_TABLE = 'rf_polls';
const RF_POLL_OPTIONS_TABLE = 'rf_poll_options';
const RF_POLL_VOTES_TABLE = 'rf_poll_votes';
const RF_POLL_COOKIE = 'rf_poll_token';
const RF_DEFAULT_POLL_SLUG = 'weekly-what-do-you-buy-june-2026';
const RF_MONTHLY_DURATION_DAYS = 7;

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

function rf_poll_column_exists(PDO $pdo, string $column): bool
{
    $statement = $pdo->query('SHOW COLUMNS FROM ' . RF_POLLS_TABLE . ' LIKE ' . $pdo->quote($column));

    return $statement !== false && count($statement->fetchAll()) > 0;
}

function rf_poll_index_exists(PDO $pdo, string $index): bool
{
    $statement = $pdo->query('SHOW INDEX FROM ' . RF_POLLS_TABLE . ' WHERE Key_name = ' . $pdo->quote($index));

    return $statement !== false && count($statement->fetchAll()) > 0;
}

function rf_poll_add_column(PDO $pdo, string $column, string $definition): void
{
    if (!rf_poll_column_exists($pdo, $column)) {
        $pdo->exec('ALTER TABLE ' . RF_POLLS_TABLE . ' ADD COLUMN ' . $definition);
    }
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
            poll_scope VARCHAR(32) NOT NULL DEFAULT "weekly",
            award_year SMALLINT UNSIGNED NULL,
            award_month TINYINT UNSIGNED NULL,
            award_type VARCHAR(24) NULL,
            starts_at DATETIME NULL,
            ends_at DATETIME NULL,
            closed_at DATETIME NULL,
            archived_at DATETIME NULL,
            winner_option_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_slug (slug),
            KEY idx_active (is_active, created_at),
            KEY idx_poll_scope (poll_scope, award_year, award_month, award_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    rf_poll_add_column($pdo, 'slug', 'slug VARCHAR(80) NULL AFTER id');
    rf_poll_add_column($pdo, 'poll_scope', 'poll_scope VARCHAR(32) NOT NULL DEFAULT "weekly" AFTER is_active');
    rf_poll_add_column($pdo, 'award_year', 'award_year SMALLINT UNSIGNED NULL AFTER poll_scope');
    rf_poll_add_column($pdo, 'award_month', 'award_month TINYINT UNSIGNED NULL AFTER award_year');
    rf_poll_add_column($pdo, 'award_type', 'award_type VARCHAR(24) NULL AFTER award_month');
    rf_poll_add_column($pdo, 'starts_at', 'starts_at DATETIME NULL AFTER award_type');
    rf_poll_add_column($pdo, 'ends_at', 'ends_at DATETIME NULL AFTER starts_at');
    rf_poll_add_column($pdo, 'closed_at', 'closed_at DATETIME NULL AFTER ends_at');
    rf_poll_add_column($pdo, 'archived_at', 'archived_at DATETIME NULL AFTER closed_at');
    rf_poll_add_column($pdo, 'winner_option_id', 'winner_option_id INT UNSIGNED NULL AFTER archived_at');

    if (!rf_poll_index_exists($pdo, 'uniq_slug')) {
        $pdo->exec('ALTER TABLE ' . RF_POLLS_TABLE . ' ADD UNIQUE KEY uniq_slug (slug)');
    }

    if (!rf_poll_index_exists($pdo, 'idx_poll_scope')) {
        $pdo->exec('ALTER TABLE ' . RF_POLLS_TABLE . ' ADD KEY idx_poll_scope (poll_scope, award_year, award_month, award_type)');
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

    rf_poll_seed($pdo, RF_DEFAULT_POLL_SLUG, 'Umfrage der Woche', 'Was kaufst du?', true, [
        'Vinyl',
        'CD',
        'Digital',
        'Bier!',
    ]);
    rf_poll_seed($pdo, 'pennywise-keller-staub', 'Abstimmung', 'Pennywise im Keller oder im Staub?', false, [
        'Bin vor Ort!',
        'Ueberlege noch.',
        'Ueberlege noch, aber nicht wegen Pennywise.',
        'Haeh?!',
    ]);
    rf_poll_seed_article($pdo, 'eric-melvin-nofx-album-versaut', 'Abstimmung', 'Welches NOFX-Album hat euch damals endgültig versaut?', [
        'Liberal Animation (1988)',
        'S&M Airlines (1989)',
        'Ribbed (1991)',
        'White Trash, Two Heebs and a Bean (1992)',
        'Punk in Drublic (1994)',
        'Heavy Petting Zoo (1996)',
        'So Long and Thanks for All the Shoes (1997)',
        'Pump Up the Valuum (2000)',
        'The War on Errorism (2003)',
        'Wolves in Wolves\' Clothing (2006)',
        'Coaster (2009)',
        'Self/Entitled (2012)',
        'First Ditch Effort (2016)',
        'Single Album (2021)',
        'Double Album (2022)',
    ]);

    rf_poll_seed_monthly_january_2026($pdo);
    rf_poll_seed_monthly_february_2026($pdo);
    rf_poll_seed_monthly_march_2026($pdo);
    rf_poll_seed_monthly_april_2026($pdo);
    rf_poll_seed_monthly_may_2026($pdo);
    rf_poll_seed_monthly_june_2026($pdo);
    rf_poll_seed_monthly_july_2026($pdo);
    rf_poll_seed_monthly_august_2026($pdo);
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
             WHERE question = :question AND poll_scope = "weekly"
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
        );
        $fallback->execute([':question' => $question]);
        $pollId = (int) $fallback->fetchColumn();

        if ($pollId > 0) {
            $update = $pdo->prepare(
                'UPDATE ' . RF_POLLS_TABLE . '
                 SET slug = :slug, title = :title, is_active = :is_active, poll_scope = "weekly"
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
            'INSERT INTO ' . RF_POLLS_TABLE . ' (slug, title, question, is_active, poll_scope)
             VALUES (:slug, :title, :question, :is_active, "weekly")'
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
             WHERE id <> :id AND poll_scope = "weekly"'
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

function rf_poll_seed_article(PDO $pdo, string $slug, string $title, string $question, array $options): void
{
    $statement = $pdo->prepare(
        'SELECT id
         FROM ' . RF_POLLS_TABLE . '
         WHERE slug = :slug
         LIMIT 1'
    );
    $statement->execute([':slug' => $slug]);
    $pollId = (int) $statement->fetchColumn();

    if ($pollId === 0) {
        $now = new DateTimeImmutable('now');
        $endsAt = $now->modify('+90 days');
        $insert = $pdo->prepare(
            'INSERT INTO ' . RF_POLLS_TABLE . ' (slug, title, question, is_active, poll_scope, starts_at, ends_at)
             VALUES (:slug, :title, :question, 1, "article", :starts_at, :ends_at)'
        );
        $insert->execute([
            ':slug' => $slug,
            ':title' => $title,
            ':question' => $question,
            ':starts_at' => $now->format('Y-m-d H:i:s'),
            ':ends_at' => $endsAt->format('Y-m-d H:i:s'),
        ]);
        $pollId = (int) $pdo->lastInsertId();
    } else {
        $update = $pdo->prepare(
            'UPDATE ' . RF_POLLS_TABLE . '
             SET title = :title,
                 question = :question,
                 poll_scope = "article"
             WHERE id = :id'
        );
        $update->execute([
            ':title' => $title,
            ':question' => $question,
            ':id' => $pollId,
        ]);
    }

    if (rf_poll_option_count($pdo, $pollId) > 0) {
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

function rf_poll_seed_monthly_options(PDO $pdo, int $year, int $month, string $awardType, array $options, bool $startNow = false): void
{
    if ($options === [] || count($options) > 10) {
        return;
    }

    $poll = rf_poll_monthly_by_period($pdo, $year, $month, $awardType);
    $pollId = $poll !== null ? (int) $poll['id'] : rf_poll_create_monthly($pdo, $year, $month, $awardType);
    $poll = $poll ?? rf_poll_by_slug($pdo, rf_poll_monthly_slug($awardType, $year, $month));

    if ($poll !== null && ($poll['starts_at'] ?? null) !== null) {
        return;
    }

    $existingOptions = rf_poll_options($pdo, $pollId);
    $existingTexts = array_map(static fn (array $option): string => (string) $option['option_text'], $existingOptions);

    if (count($existingOptions) < 10) {
        $statement = $pdo->prepare(
            'INSERT INTO ' . RF_POLL_OPTIONS_TABLE . ' (poll_id, option_text, sort_order)
             VALUES (:poll_id, :option_text, :sort_order)'
        );
        $sortOrder = count($existingOptions);

        foreach ($options as $optionText) {
            if (in_array($optionText, $existingTexts, true) || $sortOrder >= 10) {
                continue;
            }

            $sortOrder++;
            $statement->execute([
                ':poll_id' => $pollId,
                ':option_text' => $optionText,
                ':sort_order' => $sortOrder,
            ]);
            $existingTexts[] = $optionText;
        }
    }

    if ($startNow && rf_poll_option_count($pdo, $pollId) === 10) {
        rf_poll_start_monthly($pdo, $year, $month, $awardType);
    }
}

function rf_poll_seed_monthly_february_2026(PDO $pdo): void
{
    rf_poll_seed_monthly_options($pdo, 2026, 2, 'album_ep', [
        'FJØRT - belle époque',
        'pADDELNoHNEkANU - niemand liebt dich mehr',
        'Der Butterwegge - Liebe & Revolte',
        'A Wilhelm Scream - Cheap Heat',
        'Melonball - Take Care',
        'The Busters - Calling',
        'Volores - Shores of Scorpio',
        'The Sensitives - Ride It Like You Stole It',
        'New Found Glory - Listen Up!',
        'Gogol Bordello - We Mean It, Man!',
    ], true);

    rf_poll_seed_monthly_options($pdo, 2026, 2, 'single_song', [
        'STROM GEGENAN - Ferne Galaxie',
        'Neckarions - Sometimes Drunk - Always Antifascist',
        'Good Riddance - There’s Still Tonight',
        'Knocked Loose feat. Denzel Curry - Hive Mind',
        'Deadweight - Conviction',
        'Kreftich - Kopf auf Pause',
        'Tanzig - Fresse!',
        'Faded Polaroids - It’s Okay',
        'Zwakkelmann - Jammerlappen',
        'Sister Ghost - Not Your Toy',
    ], true);
}

function rf_poll_seed_monthly_march_2026(PDO $pdo): void
{
    rf_poll_seed_monthly_options($pdo, 2026, 3, 'album_ep', [
        'Dorfterror - Schreikinder',
        'Good Riddance - Before The World Caves In',
        'The Casualties - Detonate',
        'Rantanplan - Geschwedet',
        'Teenage Bottlerocket - The Invisible Man',
        'Savage Beat - Bright Lights, Tall Shadows',
        'Shoreline - Is This The Low Point Or The Moment After?',
        'Poison The Well - Peace In Place',
        'Dan Ganove - Sexunfall',
        'Vitamin X - Ride The Apocalypse',
    ], true);

    rf_poll_seed_monthly_options($pdo, 2026, 3, 'single_song', [
        'Turbolent - Ich hab alles',
        'Noi!se - You Versus You',
        'Western Addiction - Let’s Keep The Circle Small',
        'Shatten - Paranoia',
        'Deutsche Laichen - Punk ist scheiße, Punk ist geil',
        'Vor die Hunde - Hochgeschwindigkeitsgeballer',
        'Nein Danke - Wo soll das alles enden?',
        'The Nø - Unerhoert',
        'Pet Needs - Elbows Out! This Is Capitalism',
        'World I Hate - Total Nuclear Annihilation',
    ], true);
}

function rf_poll_seed_monthly_april_2026(PDO $pdo): void
{
    rf_poll_seed_monthly_options($pdo, 2026, 4, 'album_ep', [
        'Noi!se - Fate Of The Union',
        'Terror - Still Suffer',
        'Oxo86 - Die Hoffnung stirbt zuletzt...',
        'Codefendants - Lifers',
        'Grade 2 - Talk About It',
        'Division Of Mind - Exoterror',
        'Poison Ruïn - Hymns From The Hills',
        'Iron Snag Joe - Kellerkalt',
        'Portrayal Of Guilt - ...Beginning Of The End',
        'NoFuture - Bizarre Zeiten',
    ], true);

    rf_poll_seed_monthly_options($pdo, 2026, 4, 'single_song', [
        'NEVVER - Fake a Smile',
        'Social Distortion - Partners In Crime',
        'Kreftich - Sonne und Wind',
        'Versus You - Perfectly Still',
        'Waves Like Walls feat. Downpour - Never Enough',
        'Unified Move - Peace Of Mind',
        'August Burns Red feat. Jamie Hails - Sonic Salvation',
        'Dillinger Four - Don’t Happy Be Worry',
        'April Art - Panic Stations',
        'Zebrahead - Smoke Signals from My Couch',
    ], true);
}

function rf_poll_seed_monthly_may_2026(PDO $pdo): void
{
    rf_poll_seed_monthly_options($pdo, 2026, 5, 'album_ep', [
        'Thin Ice - Happiness Ain’t Meant For All',
        'Don Gordo - Viva la Escalacion',
        'ERECTION - Plug It In',
        'Koyo - Barely Here',
        'Social Distortion - Born To Kill',
        'The Flatliners - Cold World',
        'Kreftich - Keine Angst',
        'Angora Club - Herz voran',
        'The Croax - Drown In Deep',
        'Schütze - ERFOLG',
    ], true);

    rf_poll_seed_monthly_options($pdo, 2026, 5, 'single_song', [
        'The Iron Roses - Dead Eyes',
        'Madball - Rebel Kids',
        'Zebrahead - I Know What U Did Last Summer',
        'To The Wire - Every Day',
        'Pro-Pain - Stone Cold Anger',
        'Frachter - Gleich wird es besser',
        'Dwarves - We Are The Scene',
        'Popperklopper - Halbmast',
        'Risk It! - Numbskull',
        'Cancer Bats - Stay Stuck',
    ], true);
}

function rf_poll_seed_monthly_january_2026(PDO $pdo): void
{
    rf_poll_seed_monthly_options($pdo, 2026, 1, 'album_ep', [
        'Capillary - In Remembrance',
        'Vier Meter Hustensaft - Dreckige Kohle',
        'Lionheart - Valley of Death II',
        'NOFX - Quarter Album',
        'I Promised The World - I Promised The World',
        'Goldfinger - Nine Lives',
        'Dagger Threat - bleed///reboot',
        'pADDELNoHNEkANU - kein + aber',
        'Minus Youth - Lines Crossed',
        'Buzzcocks - Attitude Adjustment',
    ], true);

    rf_poll_seed_monthly_options($pdo, 2026, 1, 'single_song', [
        'Der Butterwegge - Der Osten bleibt stabil',
        'ERECTION - Ich will mehr',
        'Poison the Well - Thoroughbreds',
        'New Found Glory - Beer and Blood Stains',
        'XCOMM - Fake ID',
        'Static Dress - human props',
        'Buzzcocks - Poetic Machine Gun',
        'In Balance - Two Steps Behind',
        'A Wilhelm Scream - Let It Ride',
        'NOFX - Minnesota Nazis',
    ], true);
}

function rf_poll_seed_monthly_june_2026(PDO $pdo): void
{
    rf_poll_seed_monthly_options($pdo, 2026, 6, 'album_ep', [
        'The Cloverhearts - Germaniac!',
        'Massenkarambolage - Kauf das jetzt!',
        'HARSH - Feels',
        'LIYO - ich will ganz laut schreien',
        'Kontrollverlust - Druck (Encanto)',
        'Midfielder - This Should Feel Like Walking Up',
        'Fiddlehead - Baby I\'ll Change',
        'The Wolf I Feed - Brainwashed',
        'Downtown Boys - Public Luxury',
        'The Bouncing Souls - Born to Be',
    ], true);

    rf_poll_seed_monthly_options($pdo, 2026, 6, 'single_song', [
        'VIVA PUNK! - Im Durst',
        'SICK OF SOCIETY - Sabotage',
        'Champagner Punx - Scheiss Champagner Punx',
        'MISSSTAND - 2026',
        'SOKO LiNX - Gewaltenteilung',
        'EXAT - Durst nach Freiheit Unplugged',
        'ALARMSIGNAL - Fresse auf!',
        'Ronny Platte - Dagegen',
        'Ein Punk Band - Letzter Versuch',
        'The Feelgood McLouds - Here We Go',
    ], true);
}

function rf_poll_seed_monthly_july_2026(PDO $pdo): void
{
    rf_poll_seed_monthly_options($pdo, 2026, 7, 'album_ep', [
        'MakaBar - Hinlänglich bekannt',
        'Ronny Platte - Dagegen',
        'IROKÄSE - Macht kaputt',
        'NEVVER - Heart On Your Sleeve',
        'Risk It! - Mercy For None',
        'The Suicide Machines - Stop This Self-Doubt',
        'The Menzingers - Everything I Ever Saw',
        'No Pressure - NP Style',
        'Madball - Not Your Kingdom',
        'The Hidden Knives - The Hidden Knives',
    ], true);

    rf_poll_seed_monthly_options($pdo, 2026, 7, 'single_song', [
        'Plastic Mars - Kein Zurück',
        'Der Ole - Pianomann',
        'Dan Ganove & Schockromantik - Ultra Tolerant (Kein Punk)',
        'TURBOLENT - Ich will los',
        'KASSENGIPHT - Sag Ja',
        'NO BRAKES - Sag mir wo',
        'The Suicide Machines - Never Go Quietly',
        'Kackbratze - Blaues Wunder',
        'Pluto The Racer - Next Time It\'s Personal',
        'The Clinch - Times Up',
    ], true);
}

function rf_poll_seed_monthly_august_2026(PDO $pdo): void
{
    rf_poll_seed_monthly_options($pdo, 2026, 8, 'album_ep', [
        'SOKO LiNX - Punk für Leute, die Punk haszen',
        'Harte Worte - Soundtrack zum Untergang',
        'According To Jack - Plugged',
        'Edelweisspiraten - Wir sind der Widerstand!',
        'NOFX - 40 Years Of Fuckin\' Up: Soundtrack + Score',
        'State Power - Hyperstition',
        'Nothing Works - Some Folks Are Getting Way Too Comfortable',
        'Phantasmagorie - Laterna Magica',
        'Phantom Corporation / Catbreath - Commando / Die By The Claw',
        'The Barbarians of California - MEGATONS',
    ], true);

    rf_poll_seed_monthly_options($pdo, 2026, 8, 'single_song', [
        'No Guidance - Second Half',
        'Oi!Gebroi - Antifascist till we die',
        'Wasted Zippo - Alles wie immer',
        'Popperklopper - Nicht allein',
        'The Pill - Nighttime Routine',
        'Abfluss - Ruf nach Freiheit',
        'Jennifer Rostock - Alles muss man selber hassen',
        'Plastic Mars - Willst du so sterben?',
        'Raskob Rails - The Loop',
        'Bildunxlücke - Hunde',
    ], true);
}

function rf_poll_select_columns(): string
{
    return 'id, slug, title, question, is_active, poll_scope, award_year, award_month, award_type, starts_at, ends_at, closed_at, archived_at, winner_option_id, created_at';
}

function rf_poll_active(PDO $pdo): ?array
{
    $statement = $pdo->prepare(
        'SELECT ' . rf_poll_select_columns() . '
         FROM ' . RF_POLLS_TABLE . '
         WHERE slug = :slug AND is_active = 1 AND poll_scope = "weekly"
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
        'SELECT ' . rf_poll_select_columns() . '
         FROM ' . RF_POLLS_TABLE . '
         WHERE slug = :slug
         LIMIT 1'
    );
    $statement->execute([':slug' => $slug]);
    $poll = $statement->fetch();

    return is_array($poll) ? rf_poll_maybe_close($pdo, $poll) : null;
}

function rf_poll_monthly_slug(string $awardType, int $year, int $month): string
{
    return sprintf('monthly-%s-%04d-%02d', str_replace('_', '-', $awardType), $year, $month);
}

function rf_poll_yearly_slug(string $awardType, int $year): string
{
    return sprintf('yearly-%s-%04d', str_replace('_', '-', $awardType), $year);
}

function rf_poll_monthly_title(string $awardType, int $year, int $month): string
{
    $monthNames = rf_poll_month_names();
    $label = $awardType === 'single_song' ? 'Single/Song' : 'Album/EP';

    return $label . ' des Monats ' . $monthNames[$month] . ' ' . $year;
}

function rf_poll_yearly_title(string $awardType, int $year): string
{
    $label = $awardType === 'single_song' ? 'Single/Song' : 'Album/EP';

    return 'Der Rostige Kronkorken - ' . $label . ' des Jahres ' . $year;
}

function rf_poll_month_names(): array
{
    return [
        1 => 'Januar',
        2 => 'Februar',
        3 => 'Maerz',
        4 => 'April',
        5 => 'Mai',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'August',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Dezember',
    ];
}

function rf_poll_monthly_by_period(PDO $pdo, int $year, int $month, string $awardType): ?array
{
    $statement = $pdo->prepare(
        'SELECT ' . rf_poll_select_columns() . '
         FROM ' . RF_POLLS_TABLE . '
         WHERE poll_scope = "monthly"
           AND award_year = :award_year
           AND award_month = :award_month
           AND award_type = :award_type
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute([
        ':award_year' => $year,
        ':award_month' => $month,
        ':award_type' => $awardType,
    ]);
    $poll = $statement->fetch();

    return is_array($poll) ? rf_poll_maybe_close($pdo, $poll) : null;
}

function rf_poll_create_monthly(PDO $pdo, int $year, int $month, string $awardType): int
{
    $slug = rf_poll_monthly_slug($awardType, $year, $month);
    $title = rf_poll_monthly_title($awardType, $year, $month);

    $insert = $pdo->prepare(
        'INSERT INTO ' . RF_POLLS_TABLE . ' (slug, title, question, poll_scope, award_year, award_month, award_type, is_active)
         VALUES (:slug, :title, :question, "monthly", :award_year, :award_month, :award_type, 0)'
    );
    $insert->execute([
        ':slug' => $slug,
        ':title' => $title,
        ':question' => $title,
        ':award_year' => $year,
        ':award_month' => $month,
        ':award_type' => $awardType,
    ]);

    return (int) $pdo->lastInsertId();
}

function rf_poll_yearly_by_period(PDO $pdo, int $year, string $awardType): ?array
{
    $statement = $pdo->prepare(
        'SELECT ' . rf_poll_select_columns() . '
         FROM ' . RF_POLLS_TABLE . '
         WHERE poll_scope = "yearly"
           AND award_year = :award_year
           AND award_type = :award_type
         ORDER BY id DESC
         LIMIT 1'
    );
    $statement->execute([
        ':award_year' => $year,
        ':award_type' => $awardType,
    ]);
    $poll = $statement->fetch();

    return is_array($poll) ? rf_poll_maybe_close($pdo, $poll) : null;
}

function rf_poll_create_yearly(PDO $pdo, int $year, string $awardType): int
{
    $slug = rf_poll_yearly_slug($awardType, $year);
    $title = rf_poll_yearly_title($awardType, $year);

    $insert = $pdo->prepare(
        'INSERT INTO ' . RF_POLLS_TABLE . ' (slug, title, question, poll_scope, award_year, award_type, is_active)
         VALUES (:slug, :title, :question, "yearly", :award_year, :award_type, 0)'
    );
    $insert->execute([
        ':slug' => $slug,
        ':title' => $title,
        ':question' => $title,
        ':award_year' => $year,
        ':award_type' => $awardType,
    ]);

    return (int) $pdo->lastInsertId();
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

function rf_poll_option_count(PDO $pdo, int $pollId): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM ' . RF_POLL_OPTIONS_TABLE . '
         WHERE poll_id = :poll_id'
    );
    $statement->execute([':poll_id' => $pollId]);

    return (int) $statement->fetchColumn();
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

function rf_poll_winner_option_id(array $options): ?int
{
    $topVotes = 0;
    $winnerId = null;
    $isTie = false;

    foreach ($options as $option) {
        $votes = (int) $option['votes'];

        if ($votes > $topVotes) {
            $topVotes = $votes;
            $winnerId = (int) $option['id'];
            $isTie = false;
            continue;
        }

        if ($votes > 0 && $votes === $topVotes) {
            $isTie = true;
        }
    }

    return !$isTie && $topVotes > 0 ? $winnerId : null;
}

function rf_poll_sync_yearly_candidates(PDO $pdo, int $year, string $awardType): void
{
    $statement = $pdo->prepare(
        'SELECT p.award_month, o.option_text
         FROM ' . RF_POLLS_TABLE . ' p
         INNER JOIN ' . RF_POLL_OPTIONS_TABLE . ' o ON o.id = p.winner_option_id
         WHERE p.poll_scope = "monthly"
           AND p.award_year = :award_year
           AND p.award_type = :award_type
           AND p.winner_option_id IS NOT NULL
         ORDER BY p.award_month ASC'
    );
    $statement->execute([
        ':award_year' => $year,
        ':award_type' => $awardType,
    ]);
    $winners = $statement->fetchAll();

    if (count($winners) === 0) {
        return;
    }

    $yearly = rf_poll_yearly_by_period($pdo, $year, $awardType);
    $yearlyId = $yearly !== null ? (int) $yearly['id'] : rf_poll_create_yearly($pdo, $year, $awardType);
    $yearly = $yearly ?? rf_poll_by_slug($pdo, rf_poll_yearly_slug($awardType, $year));

    if ($yearly !== null && ($yearly['starts_at'] ?? null) !== null) {
        return;
    }

    $pdo->beginTransaction();

    try {
        $delete = $pdo->prepare('DELETE FROM ' . RF_POLL_OPTIONS_TABLE . ' WHERE poll_id = :poll_id');
        $delete->execute([':poll_id' => $yearlyId]);

        $insert = $pdo->prepare(
            'INSERT INTO ' . RF_POLL_OPTIONS_TABLE . ' (poll_id, option_text, sort_order)
             VALUES (:poll_id, :option_text, :sort_order)'
        );

        foreach ($winners as $index => $winner) {
            $insert->execute([
                ':poll_id' => $yearlyId,
                ':option_text' => (string) $winner['option_text'],
                ':sort_order' => $index + 1,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function rf_poll_maybe_close(PDO $pdo, array $poll): array
{
    $scope = (string) ($poll['poll_scope'] ?? 'weekly');
    $endsAt = (string) ($poll['ends_at'] ?? '');

    if (!in_array($scope, ['monthly', 'yearly'], true) || $endsAt === '' || $poll['closed_at'] !== null) {
        return $poll;
    }

    $now = new DateTimeImmutable('now');
    $end = new DateTimeImmutable($endsAt);

    if ($now < $end) {
        return $poll;
    }

    $winnerId = rf_poll_winner_option_id(rf_poll_options($pdo, (int) $poll['id']));
    $update = $pdo->prepare(
        'UPDATE ' . RF_POLLS_TABLE . '
         SET is_active = 0,
             closed_at = :closed_at,
             archived_at = COALESCE(archived_at, :archived_at),
             winner_option_id = :winner_option_id
         WHERE id = :id'
    );
    $closedAt = $now->format('Y-m-d H:i:s');
    $update->bindValue(':closed_at', $closedAt);
    $update->bindValue(':archived_at', $closedAt);
    $update->bindValue(':winner_option_id', $winnerId, $winnerId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $update->bindValue(':id', (int) $poll['id'], PDO::PARAM_INT);
    $update->execute();

    if ($scope === 'monthly' && $winnerId !== null) {
        rf_poll_sync_yearly_candidates($pdo, (int) $poll['award_year'], (string) $poll['award_type']);
    }

    return rf_poll_by_slug($pdo, (string) $poll['slug']) ?? $poll;
}

function rf_poll_close_expired(PDO $pdo): void
{
    $statement = $pdo->query(
        'SELECT ' . rf_poll_select_columns() . '
         FROM ' . RF_POLLS_TABLE . '
         WHERE poll_scope IN ("monthly", "yearly")
           AND ends_at IS NOT NULL
           AND closed_at IS NULL'
    );

    foreach ($statement->fetchAll() as $poll) {
        rf_poll_maybe_close($pdo, $poll);
    }
}

function rf_poll_status(array $poll): string
{
    $scope = (string) ($poll['poll_scope'] ?? 'weekly');

    if ($scope === 'weekly') {
        return ((int) ($poll['is_active'] ?? 0)) === 1 ? 'Jetzt abstimmen' : 'Abgeschlossen';
    }

    if (($poll['winner_option_id'] ?? null) !== null) {
        return 'Gewinner';
    }

    if (($poll['closed_at'] ?? null) !== null) {
        return 'Abgeschlossen';
    }

    if (($poll['starts_at'] ?? null) === null) {
        return 'Folgt';
    }

    return rf_poll_is_open($poll) ? 'Jetzt abstimmen' : 'Abgeschlossen';
}

function rf_poll_is_open(array $poll): bool
{
    $scope = (string) ($poll['poll_scope'] ?? 'weekly');

    if ($scope === 'weekly') {
        return ((int) ($poll['is_active'] ?? 0)) === 1;
    }

    if (($poll['closed_at'] ?? null) !== null || ($poll['starts_at'] ?? null) === null || ($poll['ends_at'] ?? null) === null) {
        return false;
    }

    $now = new DateTimeImmutable('now');

    return $now >= new DateTimeImmutable((string) $poll['starts_at'])
        && $now < new DateTimeImmutable((string) $poll['ends_at']);
}

function rf_poll_record_vote(PDO $pdo, array $poll, int $optionId): void
{
    if (!rf_poll_is_open($poll)) {
        return;
    }

    $pollId = (int) $poll['id'];
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

function rf_poll_start_monthly(PDO $pdo, int $year, int $month, string $awardType): array
{
    if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12 || !in_array($awardType, ['album_ep', 'single_song'], true)) {
        throw new InvalidArgumentException('Ungueltige Monatsumfrage.');
    }

    $poll = rf_poll_monthly_by_period($pdo, $year, $month, $awardType);
    $pollId = $poll !== null ? (int) $poll['id'] : rf_poll_create_monthly($pdo, $year, $month, $awardType);

    if (rf_poll_option_count($pdo, $pollId) !== 10) {
        throw new RuntimeException('Diese Monatsumfrage braucht genau 10 Kandidaten.');
    }

    $now = new DateTimeImmutable('now');
    $endsAt = $now->modify('+' . RF_MONTHLY_DURATION_DAYS . ' days');
    $update = $pdo->prepare(
        'UPDATE ' . RF_POLLS_TABLE . '
         SET starts_at = :starts_at,
             ends_at = :ends_at,
             closed_at = NULL,
             archived_at = NULL,
             winner_option_id = NULL,
             is_active = 1
         WHERE id = :id'
    );
    $update->execute([
        ':starts_at' => $now->format('Y-m-d H:i:s'),
        ':ends_at' => $endsAt->format('Y-m-d H:i:s'),
        ':id' => $pollId,
    ]);

    return rf_poll_by_slug($pdo, rf_poll_monthly_slug($awardType, $year, $month)) ?? [];
}

function rf_poll_render(array $poll, array $options, bool $showResults, string $message = ''): string
{
    $pollId = (int) $poll['id'];
    $pollSlug = (string) ($poll['slug'] ?? '');
    $pollAction = '/poll.php' . ($pollSlug !== '' ? '?poll=' . rawurlencode($pollSlug) : '');
    $totalVotes = array_reduce($options, static fn (int $sum, array $option): int => $sum + (int) $option['votes'], 0);
    $canVote = rf_poll_is_open($poll);
    $scope = (string) ($poll['poll_scope'] ?? 'weekly');
    $isMonthly = $scope === 'monthly';
    $awardType = (string) ($poll['award_type'] ?? '');
    $monthlyLabel = $awardType === 'single_song' ? 'Single/Song des Monats' : 'Album/EP des Monats';
    $submitLabel = $awardType === 'single_song' ? 'Stimme für Song ab' : 'Stimme für Album/EP ab';
    $heading = (string) $poll['question'];

    if ($isMonthly) {
        $heading = $canVote ? 'Wähle einen Kandidaten.' : 'Ergebnis';
    }

    $scopeClass = $scope === 'monthly' ? ' poll-widget--monthly' : ($scope === 'yearly' ? ' poll-widget--monthly poll-widget--yearly' : '');
    $html = '<section class="poll-widget' . $scopeClass . '" aria-label="' . rf_poll_escape((string) $poll['title']) . '">';
    $html .= '<p class="poll-widget__kicker">' . rf_poll_escape($isMonthly ? $monthlyLabel : (string) $poll['title']) . '</p>';
    $html .= '<h2>' . rf_poll_escape($heading) . '</h2>';

    if ($message !== '') {
        $html .= '<p class="poll-widget__message">' . rf_poll_escape($message) . '</p>';
    }

    if ($scope !== 'weekly' && ($poll['starts_at'] ?? null) === null) {
        $html .= '<p class="poll-widget__message">Diese Umfrage wartet noch auf den Start.</p>';
        $html .= '</section>';

        return $html;
    }

    if ($showResults || !$canVote) {
        $html .= '<div class="poll-results" aria-label="Umfrageergebnisse">';

        foreach ($options as $option) {
            $votes = (int) $option['votes'];
            $percent = $totalVotes > 0 ? (int) round(($votes / $totalVotes) * 100) : 0;
            $isWinner = (int) ($poll['winner_option_id'] ?? 0) === (int) $option['id'];
            $html .= '<div class="poll-result' . ($isWinner ? ' is-winner' : '') . '">';
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
            $optionText = (string) $option['option_text'];
            $html .= '<label class="poll-option">';
            $html .= '<input type="radio" name="option_id" value="' . $optionId . '" required>';

            if ($isMonthly && strpos($optionText, ' - ') !== false) {
                [$artist, $title] = explode(' - ', $optionText, 2);
                $html .= '<span class="poll-option__text"><strong class="poll-option__artist">' . rf_poll_escape($artist) . '</strong><span class="poll-option__title">' . rf_poll_escape($title) . '</span></span>';
            } else {
                $html .= '<span>' . rf_poll_escape($optionText) . '</span>';
            }

            $html .= '</label>';
        }

        if ($isMonthly && ($poll['ends_at'] ?? null) !== null) {
            $endsAt = new DateTimeImmutable((string) $poll['ends_at']);
            $html .= '<p class="poll-widget__deadline">Offen bis ' . rf_poll_escape($endsAt->format('d.m.Y, H:i')) . ' Uhr.</p>';
        }

        $html .= '<button class="poll-submit" type="submit">' . rf_poll_escape($isMonthly ? $submitLabel : 'Abstimmen') . '</button>';
        $html .= '</form>';

        if ($scope === 'weekly') {
            $html .= '<button class="poll-results-link" type="button" data-poll-results>Ergebnisse ansehen</button>';
        }
    }

    $html .= '</section>';

    return $html;
}

function rf_poll_is_fragment_request(): bool
{
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $fetchMode = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));
    $fetchDest = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));

    return $requestedWith === 'xmlhttprequest'
        || $fetchMode === 'cors'
        || $fetchDest === 'empty';
}

function rf_poll_page_url(string $pollSlug, bool $results = false): string
{
    $url = 'poll.php' . ($pollSlug !== '' ? '?poll=' . rawurlencode($pollSlug) : '');

    if ($results) {
        $url .= ($pollSlug !== '' ? '&' : '?') . 'action=results';
    }

    return $url;
}

function rf_poll_render_page(array $poll, string $widgetHtml): string
{
    $title = (string) ($poll['title'] ?? 'Umfrage');
    $slug = (string) ($poll['slug'] ?? '');
    $backHref = ((string) ($poll['poll_scope'] ?? 'weekly')) === 'weekly'
        ? 'index.html'
        : 'der-rostige-kronkorken.html';

    $html = '<!doctype html><html lang="de"><head>';
    $html .= '<meta charset="utf-8">';
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
    $html .= '<title>' . rf_poll_escape($title) . ' - RandaleFUNK.de</title>';
    $html .= '<meta name="description" content="' . rf_poll_escape($title) . ' bei RandaleFUNK.de">';
    $html .= '<link rel="icon" href="assets/favicon/favicon.ico" sizes="any">';
    $html .= '<link rel="stylesheet" href="style.css?v=20260713-poll-page">';
    $html .= '</head><body data-active-section="sonstiges" data-disable-weekly-poll="true">';
    $html .= '<div class="category-strip" aria-label="Magazin-Kategorien">PUNK &middot; FANZINE &middot; INTERVIEWS &middot; REVIEWS &middot; NEWS</div>';
    $html .= '<header class="site-header" aria-label="RandaleFUNK Kopfbereich">';
    $html .= '<a class="brand" href="index.html" aria-label="RandaleFUNK.de Startseite"><img src="assets/randalefunk-logo.png" alt="RandaleFUNK.de Logo" width="160" height="160"></a>';
    $html .= '<p class="tagline">Irgendwas mit Punk seit 2022</p>';
    $html .= '</header>';
    $html .= '<main class="poll-page-shell">';
    $html .= '<section class="issue-board poll-page-board">';
    $html .= '<p class="issue-label">RandaleFUNK-Umfrage</p>';
    $html .= $widgetHtml;
    $html .= '<p class="poll-page-actions"><a href="' . rf_poll_escape($backHref) . '">Zurueck</a>';

    if ($slug !== '') {
        $html .= '<a href="' . rf_poll_escape(rf_poll_page_url($slug, true)) . '">Ergebnis</a>';
    }

    $html .= '</p></section></main>';
    $html .= '<footer class="site-footer"><p>&copy; <span id="current-year"></span> RandaleFUNK.de</p><p>DIY bleibt DIY.</p></footer>';
    $html .= '<script src="script.js?v=20260713-poll-page"></script>';
    $html .= '</body></html>';

    return $html;
}

function rf_poll_handle_request(): void
{
    header('Content-Type: text/html; charset=utf-8');

    if (!rf_stats_is_configured()) {
        echo '<section class="poll-widget poll-widget--quiet" aria-label="Umfrage der Woche"><p class="poll-widget__kicker">Umfrage der Woche</p><h2>Umfrage gerade im Proberaum.</h2></section>';
        return;
    }

    try {
        $pdo = rf_stats_pdo();
        rf_poll_ensure_schema($pdo);
        rf_poll_close_expired($pdo);
        $requestedPoll = (string) ($_GET['poll'] ?? '');
        $poll = $requestedPoll !== '' ? rf_poll_by_slug($pdo, $requestedPoll) : rf_poll_active($pdo);

        if ($poll === null) {
            echo '<section class="poll-widget poll-widget--quiet" aria-label="Umfrage der Woche"><p class="poll-widget__kicker">Umfrage der Woche</p><h2>Gerade keine Umfrage aktiv.</h2></section>';
            return;
        }

        $pollId = (int) $poll['id'];
        $action = (string) ($_POST['action'] ?? $_GET['action'] ?? 'widget');
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'vote') {
            if (rf_poll_is_open($poll)) {
                rf_poll_record_vote($pdo, $poll, (int) ($_POST['option_id'] ?? 0));
                $message = 'Danke. Hier ist der Zwischenstand.';
            } else {
                $message = 'Diese Umfrage ist abgeschlossen.';
            }
            $poll = rf_poll_by_slug($pdo, (string) $poll['slug']) ?? $poll;
        }

        $hasVoted = rf_poll_has_voted($pdo, $pollId);
        $scope = (string) ($poll['poll_scope'] ?? 'weekly');
        $showResults = $message !== ''
            || $hasVoted
            || !rf_poll_is_open($poll)
            || ($action === 'results' && $scope === 'weekly');

        $widgetHtml = rf_poll_render($poll, rf_poll_options($pdo, $pollId), $showResults, $message);

        echo rf_poll_is_fragment_request() ? $widgetHtml : rf_poll_render_page($poll, $widgetHtml);
    } catch (Throwable) {
        http_response_code(500);
        echo '<section class="poll-widget poll-widget--quiet" aria-label="Umfrage der Woche"><p class="poll-widget__kicker">Umfrage der Woche</p><h2>Die Umfrage klemmt gerade.</h2></section>';
    }
}

if (!defined('RF_POLL_LIBRARY_ONLY')) {
    rf_poll_handle_request();
}
