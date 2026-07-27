<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

rf_stats_require_auth();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function rf_stats_format_number(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function rf_stats_render_chart(array $series, string $ariaLabel): string
{
    $width = 1000;
    $height = 330;
    $left = 64;
    $right = 24;
    $top = 42;
    $bottom = 48;
    $chartWidth = $width - $left - $right;
    $chartHeight = $height - $top - $bottom;
    $pointCount = count($series);
    $maximum = 1;

    foreach ($series as $row) {
        $maximum = max(
            $maximum,
            (int) ($row['visitor_day_values'] ?? 0),
            (int) ($row['pageviews'] ?? 0)
        );
    }

    $roundedMaximum = (int) (ceil($maximum / 10) * 10);
    $roundedMaximum = max(10, $roundedMaximum);
    $xPosition = static function (int $index) use ($left, $chartWidth, $pointCount): float {
        return $pointCount <= 1
            ? $left + ($chartWidth / 2)
            : $left + (($chartWidth / ($pointCount - 1)) * $index);
    };
    $yPosition = static function (int $value) use ($top, $chartHeight, $roundedMaximum): float {
        return $top + $chartHeight - (($value / $roundedMaximum) * $chartHeight);
    };
    $visitorPoints = [];
    $pageviewPoints = [];

    foreach ($series as $index => $row) {
        $x = $xPosition($index);
        $visitorPoints[] = sprintf(
            '%.2f,%.2f',
            $x,
            $yPosition((int) ($row['visitor_day_values'] ?? 0))
        );
        $pageviewPoints[] = sprintf(
            '%.2f,%.2f',
            $x,
            $yPosition((int) ($row['pageviews'] ?? 0))
        );
    }

    $svg = '<svg class="stats-chart" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . e($ariaLabel) . '">';
    $svg .= '<rect class="chart-background" x="0" y="0" width="' . $width . '" height="' . $height . '" rx="4"/>';

    for ($gridLine = 0; $gridLine <= 4; $gridLine++) {
        $value = (int) round(($roundedMaximum / 4) * (4 - $gridLine));
        $y = $top + (($chartHeight / 4) * $gridLine);
        $svg .= sprintf(
            '<line class="chart-grid-line" x1="%d" y1="%.2f" x2="%d" y2="%.2f"/>',
            $left,
            $y,
            $width - $right,
            $y
        );
        $svg .= sprintf(
            '<text class="chart-axis-label" x="%d" y="%.2f" text-anchor="end">%s</text>',
            $left - 10,
            $y + 4,
            e(rf_stats_format_number($value))
        );
    }

    $labelStep = $pointCount > 20 ? 5 : 1;

    foreach ($series as $index => $row) {
        if ($index % $labelStep !== 0 && $index !== $pointCount - 1) {
            continue;
        }

        $svg .= sprintf(
            '<text class="chart-axis-label" x="%.2f" y="%d" text-anchor="middle">%s</text>',
            $xPosition($index),
            $height - 18,
            e((string) ($row['label'] ?? ''))
        );
    }

    $svg .= '<polyline class="chart-line chart-line--visitors" points="' . implode(' ', $visitorPoints) . '"/>';
    $svg .= '<polyline class="chart-line chart-line--pageviews" points="' . implode(' ', $pageviewPoints) . '"/>';

    foreach ($series as $index => $row) {
        $x = $xPosition($index);
        $label = (string) ($row['period_key'] ?? $row['label'] ?? '');
        $visitors = (int) ($row['visitor_day_values'] ?? 0);
        $pageviews = (int) ($row['pageviews'] ?? 0);
        $svg .= sprintf(
            '<circle class="chart-point chart-point--visitors" cx="%.2f" cy="%.2f" r="3"><title>%s: %s Besucher-Tageswerte</title></circle>',
            $x,
            $yPosition($visitors),
            e($label),
            e(rf_stats_format_number($visitors))
        );
        $svg .= sprintf(
            '<circle class="chart-point chart-point--pageviews" cx="%.2f" cy="%.2f" r="3"><title>%s: %s Seitenaufrufe</title></circle>',
            $x,
            $yPosition($pageviews),
            e($label),
            e(rf_stats_format_number($pageviews))
        );
    }

    $svg .= '<g class="chart-legend">';
    $svg .= '<line class="chart-line chart-line--visitors" x1="65" y1="20" x2="100" y2="20"/>';
    $svg .= '<text x="108" y="24">Besucher-Tageswerte</text>';
    $svg .= '<line class="chart-line chart-line--pageviews" x1="285" y1="20" x2="320" y2="20"/>';
    $svg .= '<text x="328" y="24">Seitenaufrufe</text>';
    $svg .= '</g></svg>';

    return $svg;
}

$requestedRange = (string) ($_GET['range'] ?? '30d');
$selectedRange = rf_stats_valid_range($requestedRange);
$periods = rf_stats_periods();
$emptySummary = ['visitor_day_values' => 0, 'pageviews' => 0];
$data = [
    'selected_range' => $selectedRange,
    'selected_range_label' => $periods[$selectedRange]['label'],
    'periods' => $periods,
    'summaries' => [
        'today' => $emptySummary,
        'yesterday' => $emptySummary,
        'week' => $emptySummary,
        'month' => $emptySummary,
        '30d' => $emptySummary,
        'all' => $emptySummary,
    ],
    'selected_totals' => $emptySummary,
    'pageviews_per_visitor_day' => 0.0,
    'randalf_pageviews' => 0,
    'kofi_clicks' => 0,
    'kofi_clickers_total' => 0,
    'support_clicks' => 0,
    'support_clickers_total' => 0,
    'wuerfel_clicks' => 0,
    'top_pages' => [],
    'top_sections' => [],
    'daily_series' => [],
    'monthly_series' => [],
];
$error = null;

try {
    $pdo = rf_stats_pdo();
    rf_stats_ensure_schema($pdo);
    $data = rf_stats_dashboard_data($pdo, $selectedRange);
} catch (Throwable) {
    error_log('RandaleFUNK statistics dashboard failed to load.');
    $error = 'Statistik konnte nicht geladen werden.';
}

$summaryLabels = [
    'today' => 'Heute',
    'yesterday' => 'Gestern',
    'week' => 'Diese Woche',
    'month' => 'Diesen Monat',
    '30d' => 'Letzte 30 Tage',
    'all' => 'Insgesamt',
];
$filterRanges = ['today', 'yesterday', '7d', '30d', 'month', 'all'];
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
        --visitor: #e1d4b8;
        --pageview: #e52d26;
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
        width: min(100%, 1180px);
        margin: 0 auto;
      }

      header {
        margin-bottom: 20px;
        border-bottom: 3px solid var(--red);
      }

      h1,
      h2,
      h3 {
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
        max-width: 76ch;
        color: var(--muted);
        font-weight: 700;
      }

      .section-heading {
        margin: 30px 0 14px;
        color: var(--red);
        font-size: 1.05rem;
        letter-spacing: 0.08em;
      }

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
      }

      .stats-grid--compact {
        grid-template-columns: repeat(5, minmax(0, 1fr));
      }

      .stat-card,
      .stats-panel,
      .filter-panel {
        border: 2px solid var(--border);
        background: linear-gradient(rgb(20 19 16 / 0.9), rgb(20 19 16 / 0.96));
        box-shadow: 6px 6px 0 var(--black);
      }

      .stat-card {
        min-width: 0;
        padding: 16px;
      }

      .stat-card span,
      .stat-card small {
        display: block;
      }

      .stat-card span {
        min-height: 2.1em;
        color: var(--paper-dark);
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.06em;
        line-height: 1.35;
        text-transform: uppercase;
      }

      .stat-card strong {
        display: block;
        margin-top: 8px;
        overflow-wrap: anywhere;
        color: var(--paper);
        font-size: clamp(1.8rem, 4vw, 2.9rem);
        line-height: 1;
      }

      .stat-card small {
        margin-top: 8px;
        color: var(--muted);
        line-height: 1.35;
      }

      .filter-panel {
        margin-bottom: 18px;
        padding: 14px 16px;
      }

      .filter-panel h2 {
        margin-bottom: 10px;
        color: var(--paper-dark);
        font-size: 0.78rem;
        letter-spacing: 0.08em;
      }

      .range-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }

      .range-filter a {
        padding: 8px 11px;
        border: 1px solid var(--border);
        color: var(--paper);
        font-size: 0.82rem;
        font-weight: 900;
        text-decoration: none;
        text-transform: uppercase;
      }

      .range-filter a:hover,
      .range-filter a:focus-visible,
      .range-filter a.is-active {
        border-color: var(--red);
        background: var(--red);
        color: white;
      }

      .stats-columns,
      .chart-grid {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 18px;
      }

      .chart-grid {
        grid-template-columns: 1fr;
      }

      .stats-panel {
        min-width: 0;
        padding: 16px;
      }

      .stats-panel h2 {
        margin-bottom: 14px;
        color: var(--red);
        font-size: 1rem;
        letter-spacing: 0.08em;
      }

      .panel-note {
        margin: -6px 0 14px;
        color: var(--muted);
        font-size: 0.86rem;
      }

      .chart-frame {
        margin: 0;
        overflow-x: auto;
      }

      .stats-chart {
        display: block;
        width: 100%;
        min-width: 680px;
        height: auto;
      }

      .chart-background {
        fill: #0d0d0b;
      }

      .chart-grid-line {
        stroke: #403a2e;
        stroke-width: 1;
      }

      .chart-axis-label,
      .chart-legend {
        fill: var(--muted);
        font: 13px Arial, Helvetica, sans-serif;
      }

      .chart-line {
        fill: none;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;
      }

      .chart-line--visitors {
        stroke: var(--visitor);
      }

      .chart-line--pageviews {
        stroke: var(--pageview);
      }

      .chart-point {
        stroke-width: 2;
      }

      .chart-point--visitors {
        fill: var(--visitor);
        stroke: #0d0d0b;
      }

      .chart-point--pageviews {
        fill: var(--pageview);
        stroke: #0d0d0b;
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
        line-height: 1.5;
      }

      @media (max-width: 900px) {
        .stats-grid,
        .stats-grid--compact,
        .stats-columns {
          grid-template-columns: repeat(2, minmax(0, 1fr));
        }
      }

      @media (max-width: 620px) {
        body {
          padding: 14px;
        }

        .stats-grid,
        .stats-grid--compact,
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

      <h2 class="section-heading">Besucher-Tageswerte</h2>
      <section class="stats-grid" aria-label="Besucher-Tageswerte">
        <?php foreach ($summaryLabels as $periodKey => $label): ?>
          <article class="stat-card">
            <span><?= e($label) ?></span>
            <strong><?= rf_stats_format_number((int) $data['summaries'][$periodKey]['visitor_day_values']) ?></strong>
          </article>
        <?php endforeach; ?>
      </section>

      <h2 class="section-heading">Seitenaufrufe</h2>
      <section class="stats-grid" aria-label="Seitenaufrufe">
        <?php foreach ($summaryLabels as $periodKey => $label): ?>
          <article class="stat-card">
            <span><?= e($label) ?></span>
            <strong><?= rf_stats_format_number((int) $data['summaries'][$periodKey]['pageviews']) ?></strong>
          </article>
        <?php endforeach; ?>
      </section>

      <section class="filter-panel" id="zeitraum" aria-labelledby="zeitraum-heading">
        <h2 id="zeitraum-heading">Zeitraum für Auswertung und Toplisten</h2>
        <nav class="range-filter" aria-label="Zeitraum auswählen">
          <?php foreach ($filterRanges as $rangeKey): ?>
            <a
              href="?range=<?= e($rangeKey) ?>#zeitraum"
              class="<?= $data['selected_range'] === $rangeKey ? 'is-active' : '' ?>"
              <?= $data['selected_range'] === $rangeKey ? 'aria-current="page"' : '' ?>
            ><?= e((string) $periods[$rangeKey]['label']) ?></a>
          <?php endforeach; ?>
        </nav>
      </section>

      <section class="stats-grid" aria-label="Kennzahlen für den gewählten Zeitraum">
        <article class="stat-card">
          <span>Besucher-Tageswerte · <?= e((string) $data['selected_range_label']) ?></span>
          <strong><?= rf_stats_format_number((int) $data['selected_totals']['visitor_day_values']) ?></strong>
        </article>
        <article class="stat-card">
          <span>Seitenaufrufe · <?= e((string) $data['selected_range_label']) ?></span>
          <strong><?= rf_stats_format_number((int) $data['selected_totals']['pageviews']) ?></strong>
        </article>
        <article class="stat-card">
          <span>Seitenaufrufe pro Besucher-Tageswert</span>
          <strong><?= e(number_format((float) $data['pageviews_per_visitor_day'], 2, ',', '.')) ?></strong>
          <small>Grobe Verhältniszahl, keine Sitzungskennzahl und keine Sitzungsdauer.</small>
        </article>
        <article class="stat-card">
          <span>Randalf-Aufrufe · <?= e((string) $data['selected_range_label']) ?></span>
          <strong><?= rf_stats_format_number((int) $data['randalf_pageviews']) ?></strong>
          <small>Aufrufe der Randalf-Seite beziehungsweise Rubrik.</small>
        </article>
      </section>

      <section class="stats-columns" aria-label="Toplisten für den gewählten Zeitraum">
        <article class="stats-panel">
          <h2>Top 10 Seiten · <?= e((string) $data['selected_range_label']) ?></h2>
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
                  <td><?= rf_stats_format_number((int) $row['count']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($data['top_pages']) === 0): ?>
                <tr><td colspan="2">Noch keine Daten in diesem Zeitraum.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </article>

        <article class="stats-panel">
          <h2>Top Rubriken · <?= e((string) $data['selected_range_label']) ?></h2>
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
                  <td><?= rf_stats_format_number((int) $row['count']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (count($data['top_sections']) === 0): ?>
                <tr><td colspan="2">Noch keine Daten in diesem Zeitraum.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </article>
      </section>

      <h2 class="section-heading">Verlauf</h2>
      <section class="chart-grid" aria-label="Zeitliche Entwicklung">
        <article class="stats-panel">
          <h2>Tagesverlauf · letzte 30 Tage</h2>
          <p class="panel-note">Fehlende Tage werden als 0 dargestellt. Beide Linien verwenden dieselbe Skala.</p>
          <figure class="chart-frame">
            <?= rf_stats_render_chart($data['daily_series'], 'Besucher-Tageswerte und Seitenaufrufe der letzten 30 Tage') ?>
          </figure>
        </article>
        <article class="stats-panel">
          <h2>Monatsvergleich · letzte 12 Monate</h2>
          <p class="panel-note">Der aktuelle Monat ist noch nicht abgeschlossen. Fehlende Monate werden als 0 dargestellt.</p>
          <figure class="chart-frame">
            <?= rf_stats_render_chart($data['monthly_series'], 'Besucher-Tageswerte und Seitenaufrufe der letzten 12 Monate') ?>
          </figure>
        </article>
      </section>

      <h2 class="section-heading">Weitere Ereignisse · insgesamt</h2>
      <section class="stats-grid stats-grid--compact" aria-label="Weitere Ereignisse">
        <article class="stat-card">
          <span>Ko-fi-Klicks</span>
          <strong><?= rf_stats_format_number((int) $data['kofi_clicks']) ?></strong>
        </article>
        <article class="stat-card">
          <span>Ko-fi-Klickende Tageswerte</span>
          <strong><?= rf_stats_format_number((int) $data['kofi_clickers_total']) ?></strong>
        </article>
        <article class="stat-card">
          <span>Warum unterstützen?</span>
          <strong><?= rf_stats_format_number((int) $data['support_clicks']) ?></strong>
        </article>
        <article class="stat-card">
          <span>Warum-Klickende Tageswerte</span>
          <strong><?= rf_stats_format_number((int) $data['support_clickers_total']) ?></strong>
        </article>
        <article class="stat-card">
          <span>Würfel-App-Klicks</span>
          <strong><?= rf_stats_format_number((int) $data['wuerfel_clicks']) ?></strong>
        </article>
      </section>

      <p class="note">
        <strong>Was bedeutet Besucher-Tageswert?</strong>
        Innerhalb eines Kalendertages wird ein datensparsamer Tages-Hash gezählt. Derselbe Mensch kann an verschiedenen Tagen erneut gezählt werden. Wochen-, Monats- und Gesamtwerte sind deshalb Summen täglicher Näherungswerte und keine Zahl eindeutig identifizierter Personen.
      </p>
    </main>
  </body>
</html>
