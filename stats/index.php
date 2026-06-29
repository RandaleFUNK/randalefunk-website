<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

rf_stats_require_auth();

$error = null;
$data = [
    'visitors_today' => 0,
    'visitors_month' => 0,
    'visitors_total' => 0,
    'pageviews_total' => 0,
    'kofi_clicks' => 0,
    'kofi_clickers_total' => 0,
    'support_clicks' => 0,
    'support_clickers_total' => 0,
    'wuerfel_clicks' => 0,
    'top_pages' => [],
    'top_sections' => [],
];

try {
    $pdo = rf_stats_pdo();
    rf_stats_ensure_schema($pdo);
    $data = rf_stats_dashboard_data($pdo);
} catch (Throwable) {
    error_log('RandaleFUNK statistics dashboard failed to load.');
    $error = 'Statistik konnte nicht geladen werden.';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>RandaleFUNK Statistik</title>
    <style>
      :root {
        color-scheme: dark;
        --background: #070707;
        --panel: #141310;
        --paper: #e1d4b8;
        --paper-dark: #b9a77f;
        --ink: #f1ead8;
        --muted: #b4aa95;
        --red: #e52d26;
        --border: #403a2e;
        --black: #050505;
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        min-height: 100vh;
        padding: 24px;
        background:
          linear-gradient(90deg, rgb(255 255 255 / 0.035) 1px, transparent 1px),
          linear-gradient(0deg, rgb(255 255 255 / 0.025) 1px, transparent 1px),
          var(--background);
        background-size: 24px 24px;
        color: var(--ink);
        font-family: Arial, Helvetica, sans-serif;
      }

      main {
        width: min(100%, 1120px);
        margin: 0 auto;
      }

      header {
        margin-bottom: 20px;
        border-bottom: 3px solid var(--red);
      }

      h1,
      h2 {
        margin: 0;
        text-transform: uppercase;
      }

      h1 {
        color: var(--paper);
        font-size: clamp(2rem, 6vw, 4rem);
        line-height: 0.95;
        text-shadow: 4px 4px 0 var(--black), 6px 6px 0 var(--red);
      }

      header p {
        max-width: 70ch;
        color: var(--muted);
        font-weight: 700;
      }

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
      }

      .stat-card,
      .stats-panel {
        border: 2px solid var(--border);
        background:
          linear-gradient(rgb(20 19 16 / 0.9), rgb(20 19 16 / 0.96));
        box-shadow: 6px 6px 0 var(--black);
      }

      .stat-card {
        padding: 16px;
      }

      .stat-card span {
        display: block;
        color: var(--paper-dark);
        font-size: 0.76rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
      }

      .stat-card strong {
        display: block;
        margin-top: 8px;
        color: var(--paper);
        font-size: clamp(2rem, 5vw, 3.2rem);
        line-height: 1;
      }

      .stats-columns {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 18px;
      }

      .stats-panel {
        padding: 16px;
      }

      .stats-panel h2 {
        margin-bottom: 14px;
        color: var(--red);
        font-size: 1rem;
        letter-spacing: 0.08em;
      }

      table {
        width: 100%;
        border-collapse: collapse;
      }

      th,
      td {
        padding: 10px 8px;
        border-bottom: 1px solid var(--border);
        text-align: left;
        vertical-align: top;
      }

      th {
        color: var(--paper-dark);
        font-size: 0.78rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
      }

      td:last-child,
      th:last-child {
        text-align: right;
      }

      .error {
        margin-bottom: 18px;
        padding: 14px;
        border-left: 6px solid var(--red);
        background: #22100f;
        color: var(--paper);
        font-weight: 800;
      }

      .note {
        margin-top: 22px;
        color: var(--muted);
        font-size: 0.9rem;
      }

      @media (max-width: 820px) {
        body {
          padding: 14px;
        }

        .stats-grid,
        .stats-columns {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>
  <body>
    <main>
      <header>
        <h1>RandaleFUNK Statistik</h1>
        <p>Interne, datensparsame Reichweitenmessung ohne Cookies, ohne externe Dienste und ohne dauerhaft gespeicherte IP-Adressen.</p>
      </header>

      <?php if ($error !== null): ?>
        <div class="error"><?= e($error) ?></div>
      <?php endif; ?>

      <section class="stats-grid" aria-label="Kennzahlen">
        <article class="stat-card">
          <span>Besucher heute</span>
          <strong><?= (int) $data['visitors_today'] ?></strong>
        </article>
        <article class="stat-card">
          <span>Besucher Monat</span>
          <strong><?= (int) $data['visitors_month'] ?></strong>
        </article>
        <article class="stat-card">
          <span>Besucher insgesamt</span>
          <strong><?= (int) $data['visitors_total'] ?></strong>
        </article>
        <article class="stat-card">
          <span>Seitenaufrufe</span>
          <strong><?= (int) $data['pageviews_total'] ?></strong>
        </article>
        <article class="stat-card">
          <span>Ko-fi-Klicks</span>
          <strong><?= (int) $data['kofi_clicks'] ?></strong>
        </article>
        <article class="stat-card">
          <span>Ko-fi-Klickende</span>
          <strong><?= (int) $data['kofi_clickers_total'] ?></strong>
        </article>
        <article class="stat-card">
          <span>Warum unterstützen?</span>
          <strong><?= (int) $data['support_clicks'] ?></strong>
        </article>
        <article class="stat-card">
          <span>Warum-Klickende</span>
          <strong><?= (int) $data['support_clickers_total'] ?></strong>
        </article>
        <article class="stat-card">
          <span>Wuerfel-App-Klicks</span>
          <strong><?= (int) $data['wuerfel_clicks'] ?></strong>
        </article>
      </section>

      <section class="stats-columns">
        <article class="stats-panel">
          <h2>Top 10 Seiten</h2>
          <table>
            <thead>
              <tr>
                <th>Seite</th>
                <th>Aufrufe</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($data['top_pages'] as $row): ?>
                <tr>
                  <td><?= e((string) $row['path']) ?></td>
                  <td><?= (int) $row['count'] ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($data['top_pages']) === 0): ?>
                <tr><td colspan="2">Noch keine Daten.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </article>

        <article class="stats-panel">
          <h2>Top Rubriken</h2>
          <table>
            <thead>
              <tr>
                <th>Rubrik</th>
                <th>Aufrufe</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($data['top_sections'] as $row): ?>
                <tr>
                  <td><?= e((string) $row['section']) ?></td>
                  <td><?= (int) $row['count'] ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($data['top_sections']) === 0): ?>
                <tr><td colspan="2">Noch keine Daten.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </article>
      </section>

      <p class="note">Hinweis: Besucher werden datensparsam als taegliche Hashes gezaehlt. Monats- und Gesamtwerte sind deshalb bewusst grobe Besuchswerte, keine Nutzerprofile.</p>
    </main>
  </body>
</html>
