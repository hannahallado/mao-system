<?php
$recordButton = '<a class="btn btn-primary btn-sm" href="' . e(url('/monitoring/production/create')) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Record Production</a>';
?>

<?= Ui::pageHead('Production Monitoring', 'Search production records by commodity, barangay, period, or date.', $recordButton) ?>

<?= Ui::filterBar('/monitoring/production', [
    ['type' => 'search', 'name' => 'q', 'placeholder' => 'Search commodity, barangay, date, or period'],
], ['q' => $query]) ?>

<div class="d-flex justify-content-between align-items-center gap-2 mb-2">
    <h2 class="h5 mb-0">Production records</h2>
    <span class="text-muted small"><?= count($records) ?> matching <?= count($records) === 1 ? 'record' : 'records' ?></span>
</div>

<?php
$columns = [
    ['label' => 'Date', 'key' => 'display_date'],
    ['label' => 'Period', 'key' => 'period'],
    ['label' => 'Commodity', 'render' => fn ($record) => '<span class="fw-semibold">' . e($record['commodity']) . '</span>'],
    ['label' => 'Barangay', 'key' => 'barangay'],
    ['label' => 'Farmer / farm', 'key' => 'farmer_farm'],
    ['label' => 'Area (ha)', 'class' => 'text-end', 'key' => 'area_ha'],
    ['label' => 'Production volume', 'render' => fn ($record) => e($record['volume']) . ' ' . e($record['unit'])],
];
$empty = $query !== ''
    ? Ui::emptyState('No matching production records', 'Try another search term or clear the search.')
    : Ui::emptyState('No production records', 'Production entries will appear here when available.');
?>
<?= Ui::dataTable($columns, $records, ['empty' => $empty]) ?>
