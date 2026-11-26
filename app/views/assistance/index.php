<?php
$hasFilters = array_filter($filters, fn ($v) => $v !== '') !== [];

$columns = [
    ['label' => 'Date', 'render' => fn ($r) => e(format_date($r['date_distributed']))],
    ['label' => 'Assistance ID', 'render' => fn ($r) => '<code>' . e($r['assistance_no']) . '</code>'],
    ['label' => 'Beneficiary', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/profiles/' . $r['profile_id'] . '/assistance-history')) . '">' . e(Profile::fullName($r)) . '</a>'],
    ['label' => 'Barangay', 'key' => 'barangay_name'],
    ['label' => 'Program', 'render' => fn ($r) => '<a href="' . e(url('/programs/' . $r['program_id'] . '?tab=distributions')) . '">' . e($r['program_name']) . '</a>'],
    ['label' => 'Assistance', 'key' => 'type_name'],
    ['label' => 'Quantity', 'class' => 'num', 'render' => fn ($r) => e(AssistanceRecord::qty($r))],
    ['label' => '', 'render' => fn ($r) => $r['duplicate_flag']
        ? '<span title="' . e($r['override_reason']) . '">' . Ui::badge('Duplicate override', 'warning') . '</span>' : ''],
];

$recordBtn = '<a class="btn btn-primary btn-sm" href="' . e(url('/assistance/create')) . '">+ Record Distribution</a>';
$empty = $hasFilters
    ? Ui::emptyState('No matching distributions', 'Try different filters.', '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/assistance')) . '">Clear filters</a>')
    : Ui::emptyState('No distributions recorded yet', 'Assistance given to beneficiaries will be listed here.', $recordBtn);
?>
<?= Ui::pageHead('Programs', null, $recordBtn) ?>
<?= partial('programs/_tabs', ['active' => 'distribution']) ?>

<?= Ui::filterBar('/assistance', [
    ['type' => 'search', 'name' => 'q', 'placeholder' => 'Search beneficiary, assistance ID or receipt no.'],
    ['name' => 'program_id', 'all' => 'All programs', 'options' => $programs],
    ['name' => 'barangay_id', 'all' => 'All barangays', 'options' => $barangays],
    ['name' => 'flagged', 'all' => 'All records', 'options' => ['1' => 'Flagged duplicates only']],
], $filters) ?>

<?= Ui::dataTable($columns, $result['data'], ['empty' => $empty]) ?>
<?= Ui::pagination($result, '/assistance', array_filter($filters, fn ($v) => $v !== '')) ?>
