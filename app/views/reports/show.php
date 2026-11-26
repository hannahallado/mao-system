<?php
/**
 * Reusable report view (all 12 reports): filters -> printable document with
 * header, applied filters, table with totals and signature lines.
 * Vars from ReportController::show().
 */
$fmt = function ($value, string $type): string {
    if ($value === null || $value === '') {
        return '';
    }
    return match ($type) {
        'num'   => number_format((float) $value),
        'dec'   => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.'),
        'money' => number_format((float) $value, 2),
        'date'  => format_date((string) $value),
        default => (string) $value,
    };
};

// Totals for columns flagged as totalled
$totals = [];
foreach ($columns as $c) {
    if (!empty($c[3])) {
        $totals[$c[1]] = array_sum(array_map(fn ($r) => (float) ($r[$c[1]] ?? 0), $rows));
    }
}

// Applied filters, as text for the document header
$applied = [];
foreach ($filters as $name => $value) {
    if ($value === '' || in_array($name, ['date_from', 'date_to'], true)) {
        continue;
    }
    $opts = ReportService::filterOptions($name);
    $applied[] = ReportService::filterLabel($name) . ': ' . ($opts[$value] ?? $value);
}
$from = $filters['date_from'] ?? '';
$to   = $filters['date_to'] ?? '';
$period = $from || $to
    ? ($from ? format_date($from) : 'Start') . ' to ' . ($to ? format_date($to) : 'present')
    : 'All records to date';

$hasDates = array_key_exists('date_from', $filters);
$exportName = $slug . '-' . date('Y-m-d');
?>
<div class="page-head no-print">
    <div>
        <h1><?= e($report['title']) ?></h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-secondary btn-sm" href="<?= url('/reports') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> All reports</a>
        <?php if (!$missing): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-report-action="print"><i class="bi bi-printer" aria-hidden="true"></i> Print</button>
            <button type="button" class="btn btn-primary btn-sm" data-report-action="excel" data-filename="<?= e($exportName) ?>" data-sheet="<?= e(mb_substr($report['title'], 0, 31)) ?>">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Export to Excel
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($missing): ?>
    <div class="card"><?= Ui::emptyState('This report is not set up yet', 'Its records are stored in tables that do not exist yet: ' . implode(', ', $missing) . '.') ?>
        <p class="text-center text-muted small pb-4 mb-0">Run <code>database/schema_modules.sql</code> on your database (phpMyAdmin &gt; SQL).</p></div>
    <?php return; ?>
<?php endif; ?>

<!-- Filters -->
<form method="get" action="<?= url('/reports/' . $slug) ?>" class="report-filters no-print">
    <?php foreach ($filters as $name => $value): ?>
        <?php if (in_array($name, ['date_from', 'date_to'], true)): ?>
            <label class="report-filter">
                <span><?= e(ReportService::filterLabel($name)) ?></span>
                <input type="date" class="form-control" name="<?= e($name) ?>" value="<?= e($value) ?>">
            </label>
        <?php elseif ($name === 'q'): ?>
            <label class="report-filter report-filter-wide">
                <span>Beneficiary name</span>
                <input type="search" class="form-control" name="q" value="<?= e($value) ?>" placeholder="Search a name">
            </label>
        <?php else: ?>
            <label class="report-filter">
                <span><?= e(ReportService::filterLabel($name)) ?></span>
                <select class="form-select" name="<?= e($name) ?>">
                    <option value="">All</option>
                    <?php foreach (ReportService::filterOptions($name) as $ov => $ol): ?>
                        <option value="<?= e($ov) ?>" <?= (string) $ov === $value ? 'selected' : '' ?>><?= e($ol) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="report-filter-actions">
        <button type="submit" class="btn btn-primary btn-sm">Generate</button>
        <?php if (array_filter($filters, fn ($v) => $v !== '')): ?>
            <a class="btn btn-link btn-sm text-muted" href="<?= url('/reports/' . $slug) ?>">Reset</a>
        <?php endif; ?>
    </div>
</form>

<!-- Printable document -->
<section class="report-doc">
    <header class="report-doc-head">
        <div class="report-doc-org">
            <div class="report-doc-office"><?= e($settings['office_name']) ?></div>
            <div class="report-doc-place"><?= e(($settings['report_header'] ?? '') !== '' ? $settings['report_header'] : trim($settings['municipality'] . ', ' . $settings['province'], ', ')) ?></div>
        </div>
        <h2 class="report-doc-title"><?= e($report['title']) ?></h2>
        <dl class="report-doc-meta">
            <div><dt>Reporting period</dt><dd><?= e($period) ?></dd></div>
            <?php if (($settings['reporting_start'] ?? '') !== '' && ($settings['reporting_end'] ?? '') !== ''): ?>
                <div><dt>Office reporting period</dt><dd><?= e(format_date($settings['reporting_start']) . ' to ' . format_date($settings['reporting_end'])) ?></dd></div>
            <?php endif; ?>
            <div><dt>Filters</dt><dd><?= e($applied ? implode(' · ', $applied) : 'None (all records)') ?></dd></div>
            <div><dt>Generated</dt><dd><?= e(date('M j, Y g:i A')) ?> by <?= e(Auth::user()['full_name'] ?? '') ?></dd></div>
            <div><dt>Records</dt><dd><?= number_format(count($rows)) ?></dd></div>
        </dl>
    </header>

    <?php if (!$rows): ?>
        <div class="report-empty">
            <i class="bi bi-search" aria-hidden="true"></i>
            <p><strong>No records match the selected filters.</strong><br>Try a wider date range or set a filter back to "All".</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="table report-table" id="reportTable">
                <thead>
                    <tr>
                        <th class="num">#</th>
                        <?php foreach ($columns as $c): ?>
                            <th class="<?= in_array($c[2] ?? 'text', ['num', 'dec', 'money'], true) ? 'num' : '' ?>"><?= e($c[0]) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td class="num"><?= $i + 1 ?></td>
                            <?php foreach ($columns as $c): ?>
                                <?php $type = $c[2] ?? 'text'; $text = $fmt($r[$c[1]] ?? null, $type); ?>
                                <td class="<?= in_array($type, ['num', 'dec', 'money'], true) ? 'num' : '' ?>"
                                    <?= in_array($type, ['num', 'dec', 'money'], true) && ($r[$c[1]] ?? null) !== null ? 'data-v="' . e((float) $r[$c[1]]) . '"' : '' ?>><?= $text === '' ? '—' : e($text) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if ($totals): ?>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <?php foreach ($columns as $c): ?>
                                <th class="num"><?= isset($totals[$c[1]]) ? e($fmt($totals[$c[1]], $c[2] ?? 'num')) : '' ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    <?php endif; ?>

    <footer class="report-doc-sign">
        <div>
            <span class="report-sign-label">Prepared by</span>
            <span class="report-sign-line"><?= e($settings['prepared_by'] ?: (Auth::user()['full_name'] ?? '')) ?></span>
        </div>
        <div>
            <span class="report-sign-label">Approved by</span>
            <span class="report-sign-line"><?= e($settings['approved_by']) ?></span>
            <span class="report-sign-title"><?= e($settings['approved_title']) ?></span>
        </div>
    </footer>
</section>

<!-- SheetJS builds the .xlsx in the browser (needs internet the first time) -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js" defer></script>
