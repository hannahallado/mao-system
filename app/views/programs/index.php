<?php
$hasFilters = array_filter($filters, fn ($v) => $v !== '') !== [];

$columns = [
    ['label' => 'Program ID', 'render' => fn ($r) => '<code>' . e($r['program_no']) . '</code>'],
    ['label' => 'Program', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/programs/' . $r['id'])) . '">' . e($r['name']) . '</a>'],
    ['label' => 'Source agency', 'key' => 'source_agency'],
    ['label' => 'Year', 'key' => 'year'],
    ['label' => 'Schedule', 'render' => fn ($r) => $r['start_date'] || $r['end_date']
        ? e(format_date($r['start_date'], 'M d, Y') ?: '?') . ' &ndash; ' . e(format_date($r['end_date'], 'M d, Y') ?: 'open') : '<span class="text-muted">&mdash;</span>'],
    ['label' => 'Beneficiaries', 'class' => 'num', 'render' => fn ($r) => e($r['served_count']) . ' served / ' . e($r['beneficiary_count']) . ($r['target_beneficiaries'] !== null ? ' of ' . e($r['target_beneficiaries']) . ' target' : '')],
    ['label' => 'Status', 'render' => fn ($r) => Ui::statusBadge($r['status'], Program::STATUSES[$r['status']] ?? null)],
    ['label' => '', 'class' => 'table-actions', 'render' => fn ($r) =>
        '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/programs/' . $r['id'])) . '">View</a>'
      . (Auth::isAgriculturist() ? ' <a class="btn btn-outline-secondary btn-sm" href="' . e(url('/programs/' . $r['id'] . '/edit')) . '">Edit</a>' : '')],
];

$createBtn = Auth::isAgriculturist() ? '<a class="btn btn-primary btn-sm" href="' . e(url('/programs/create')) . '">+ Create Program</a>' : '';

$empty = $hasFilters
    ? Ui::emptyState('No matching programs', 'Try different filters.', '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/programs')) . '">Clear filters</a>')
    : Ui::emptyState('No programs yet', Auth::isAgriculturist() ? 'Create the first program to start listing beneficiaries.' : 'The Municipal Agriculturist creates programs.', $createBtn);
?>
<?= Ui::pageHead('Programs and Projects', 'Programs received from the LGU, DA and partner agencies.', $createBtn) ?>

<?= Ui::filterBar('/programs', [
    ['type' => 'search', 'name' => 'q', 'placeholder' => 'Search program name or ID'],
    ['name' => 'year', 'all' => 'All years', 'options' => $years],
    ['name' => 'source_agency', 'all' => 'All agencies', 'options' => Program::AGENCIES],
    ['name' => 'status', 'all' => 'All statuses', 'options' => Program::STATUSES],
], $filters) ?>

<?= Ui::dataTable($columns, $result['data'], ['empty' => $empty]) ?>
<?= Ui::pagination($result, '/programs', array_filter($filters, fn ($v) => $v !== '')) ?>
