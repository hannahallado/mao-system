<?php
$addButton = '<a class="btn btn-primary btn-sm" href="' . e(url('/monitoring/training/create')) . '"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Training/Activity</a>';
$columns = [
    ['label' => 'Date', 'key' => 'display_date'],
    ['label' => 'Activity', 'render' => fn ($activity) => '<span class="fw-semibold">' . e($activity['title']) . '</span>'],
    ['label' => 'Type', 'render' => fn ($activity) => Ui::badge($activity['type'], $activity['type'] === 'Technical assistance' ? 'info' : 'success')],
    ['label' => 'Barangay', 'key' => 'barangay'],
    ['label' => 'Venue', 'key' => 'venue'],
    ['label' => 'Topic', 'key' => 'topic'],
    ['label' => 'Personnel', 'key' => 'personnel'],
    ['label' => 'Participants', 'class' => 'text-end', 'key' => 'participants'],
    ['label' => 'Status', 'render' => fn ($activity) => Ui::statusBadge(strtolower($activity['status']))],
];
?>

<?= Ui::pageHead('Training and Activities', 'Review trainings, seminars, and technical assistance activities.', $addButton) ?>

<div class="d-flex justify-content-between align-items-center gap-2 mb-2">
    <h2 class="h5 mb-0">Training and activity records</h2>
    <span class="text-muted small"><?= count($activities) ?> <?= count($activities) === 1 ? 'activity' : 'activities' ?></span>
</div>

<?= Ui::dataTable($columns, $activities, [
    'empty' => Ui::emptyState('No training or activity records', 'Entries will appear here when available.'),
]) ?>
