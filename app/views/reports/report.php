<?php
$isFunctional = $status === 'functional';
$period = format_date($settings['reporting_start']) . ' – ' . format_date($settings['reporting_end']);
?>
<div class="page-head">
    <div>
        <h1><?= e($title) ?></h1>
        <p class="page-sub"><?= e($isFunctional ? $group . ' · Generated report' : ($subtitle ?? $group)) ?></p>
    </div>
    <div class="page-actions no-print">
        <?php if ($isFunctional): ?>
            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.print()">Print</button>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/reports/masterlist') . '?' . http_build_query(array_filter($filters) + ['export' => 'csv'])) ?>">Export CSV</a>
        <?php else: ?>
            <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Print</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" disabled>Export</button>
        <?php endif; ?>
    </div>
</div>

<div class="card report-sheet mb-3">
    <div class="card-body">
        <div class="report-heading">
            <div>
                <div class="report-kicker"><?= e($settings['office_name']) ?></div>
                <div class="text-muted small"><?= e($settings['municipality']) ?></div>
                <div class="text-muted small"><?= e($settings['report_header']) ?></div>
                <h2><?= e($title) ?></h2>
            </div>
            <div class="report-meta">
                <div><span>Reporting period</span><strong><?= e($period) ?></strong></div>
                <div><span>Generated</span><strong><?= e($generated_at) ?></strong></div>
            </div>
        </div>

        <?php if ($isFunctional): ?>
            <?= Ui::filterBar('/reports/masterlist', [
                ['type' => 'search', 'name' => 'q', 'placeholder' => 'Search name, RSBSA, FishR or contact'],
                ['name' => 'barangay_id', 'all' => 'All barangays', 'options' => $barangays],
                ['name' => 'livelihood_type', 'all' => 'All livelihoods', 'options' => $livelihoods],
                ['name' => 'status', 'all' => 'All statuses', 'options' => $statuses],
            ], $filters) ?>

            <div class="report-summary">
                <div><span>Total records</span><strong><?= number_format(count($rows)) ?></strong></div>
                <div><span>Farmers</span><strong><?= number_format(count(array_filter($rows, fn ($r) => $r['has_farming']))) ?></strong></div>
                <div><span>Fisherfolk</span><strong><?= number_format(count(array_filter($rows, fn ($r) => $r['has_fishery']))) ?></strong></div>
            </div>

            <?php if (!$rows): ?>
                <?= Ui::emptyState('No records match this report', 'Change or clear the filters to try again.') ?>
            <?php else: ?>
                <div class="table-scroll report-table">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Sex</th>
                                <th>Barangay</th>
                                <th>Livelihood</th>
                                <th>RSBSA / FishR</th>
                                <th>Contact</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><a class="fw-semibold" href="<?= e(url('/farmers/' . $row['id'])) ?>"><?= e(Profile::fullName($row)) ?></a></td>
                                    <td><?= e($row['sex'] ?? '—') ?></td>
                                    <td><?= e($row['barangay_name'] ?? '—') ?></td>
                                    <td>
                                        <?php if ($row['has_farming']): ?><span class="badge badge-soft-success">Farmer</span><?php endif; ?>
                                        <?php if ($row['has_fishery']): ?><span class="badge badge-soft-success">Fisherfolk</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $numbers = array_filter([$row['farming_rsbsa'] ?? null, $row['fishr_no'] ?? null]);
                                        echo $numbers ? e(implode(' / ', $numbers)) : '<span class="text-muted">—</span>';
                                        ?>
                                    </td>
                                    <td><?= e($row['contact_no'] ?: '—') ?></td>
                                    <td><?= Ui::statusBadge($row['status'], Profile::STATUSES[$row['status']] ?? null) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <form class="filter-bar no-print" method="get" action="<?= e(url('/reports/' . $slug)) ?>">
                <?php foreach ($filter_labels as $name => $label): ?>
                    <select class="form-select" name="<?= e($name) ?>" aria-label="<?= e($label) ?>">
                        <option value=""><?= e($label) ?></option>
                    </select>
                <?php endforeach; ?>
                <button class="btn btn-outline-secondary btn-sm" type="submit" disabled>Search</button>
            </form>

            <div class="report-summary">
                <div><span>Total records</span><strong>0</strong></div>
                <div><span>Reporting period</span><strong><?= e($period) ?></strong></div>
                <div><span>Report status</span><strong>Preview</strong></div>
            </div>

            <div class="table-scroll report-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><?php foreach ($mock_columns as $column): ?><th><?= e($column) ?></th><?php endforeach; ?></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="<?= count($mock_columns) ?>">
                                <?= Ui::emptyState('Report preview', 'The report layout is ready. Records will appear when this module is implemented.') ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
