<?php
$recordButton = '<a class="btn btn-primary btn-sm" href="' . e(url('/monitoring/damage/create')) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Record Damage/Pest</a>';
$columns = [
    ['label' => 'Date', 'key' => 'display_date'],
    ['label' => 'Record type', 'render' => fn ($record) => Ui::badge($record['record_type'], $record['record_type'] === 'Pest observation' ? 'warning' : 'danger')],
    ['label' => 'Issue', 'key' => 'issue'],
    ['label' => 'Commodity', 'render' => fn ($record) => '<span class="fw-semibold">' . e($record['commodity']) . '</span>'],
    ['label' => 'Barangay', 'key' => 'barangay'],
    ['label' => 'Affected farmer / farm', 'key' => 'farmer_farm'],
    ['label' => 'Affected area (ha)', 'class' => 'text-end', 'key' => 'area_ha'],
    ['label' => 'Severity', 'render' => fn ($record) => Ui::badge($record['severity'], $record['severity'] === 'Low' ? 'success' : 'warning')],
    ['label' => 'Calamity', 'key' => 'calamity_name'],
    ['label' => 'Response / action', 'key' => 'response_notes'],
    ['label' => 'Status', 'key' => 'status'],
];
?>

<?= Ui::pageHead('Damage and Pest Monitoring', 'Review sample crop damage and pest reports.', $recordButton) ?>

<div class="d-flex justify-content-between align-items-center gap-2 mb-2">
    <h2 class="h5 mb-0">Damage and pest records</h2>
    <span class="text-muted small"><?= count($records) ?> <?= count($records) === 1 ? 'record' : 'records' ?></span>
</div>

<?= Ui::dataTable($columns, $records, [
    'empty' => Ui::emptyState('No damage or pest records', 'Entries will appear here when available.'),
]) ?>
