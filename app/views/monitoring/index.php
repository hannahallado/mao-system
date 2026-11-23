<?php
$sections = [
    [
        'title' => 'Production monitoring',
        'description' => 'Review crop and commodity production records.',
        'path' => '/monitoring/production',
        'icon' => 'bi-graph-up-arrow',
        'action' => 'Open production',
    ],
    [
        'title' => 'Damage and pest',
        'description' => 'Review reported crop damage, pests, and responses.',
        'path' => '/monitoring/damage',
        'icon' => 'bi-bug',
        'action' => 'Open damage and pest',
    ],
    [
        'title' => 'Training and activities',
        'description' => 'Review trainings, seminars, and technical assistance.',
        'path' => '/monitoring/training',
        'icon' => 'bi-calendar-event',
        'action' => 'Open training and activities',
    ],
];
?>

<?= Ui::pageHead('Monitoring Overview', 'Agricultural production, damage, and field activities in Pulupandan.') ?>

<div class="row g-3 mb-4" aria-label="Recorded activity summary">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Activities</div>
                <div class="h3 mb-0"><?= count($activities) ?></div>
                <div class="small text-muted mt-2">Matching current filters</div>
            </div>
        </div>
    </div>
    <?php foreach ([
        ['Production', 'bi-graph-up-arrow', 'production'],
        ['Damage / Pest', 'bi-bug', 'damage'],
        ['Training / Activities', 'bi-calendar-event', 'training'],
    ] as [$label, $icon, $type]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="text-muted small"><?= e($label) ?></div>
                        <i class="bi <?= e($icon) ?> text-muted" aria-hidden="true"></i>
                    </div>
                    <div class="h3 mb-0"><?= (int) $summary[$type] ?></div>
                    <div class="small text-muted mt-2">Entries</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<section class="card mb-4" aria-labelledby="monitoring-filters-title">
    <div class="card-header">
        <h2 class="h6 mb-0" id="monitoring-filters-title">Filter activities</h2>
    </div>
    <div class="card-body">
        <form method="get" action="<?= e(url('/monitoring')) ?>">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg">
                    <label class="form-label" for="filter-date-from">From date</label>
                    <input class="form-control" id="filter-date-from" name="date_from" type="date" value="<?= e($filters['date_from']) ?>">
                </div>
                <div class="col-sm-6 col-lg">
                    <label class="form-label" for="filter-date-to">To date</label>
                    <input class="form-control" id="filter-date-to" name="date_to" type="date" value="<?= e($filters['date_to']) ?>">
                </div>
                <div class="col-sm-6 col-lg">
                    <label class="form-label" for="filter-barangay">Barangay</label>
                    <select class="form-select" id="filter-barangay" name="barangay_id">
                        <option value="">All barangays</option>
                        <?php foreach ($barangays as $id => $name): ?>
                            <option value="<?= e($id) ?>"<?= $filters['barangay_id'] === (string) $id ? ' selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg">
                    <label class="form-label" for="filter-activity-type">Activity type</label>
                    <select class="form-select" id="filter-activity-type" name="activity_type">
                        <option value="">All activity types</option>
                        <?php foreach ($activityTypes as $value => $label): ?>
                            <option value="<?= e($value) ?>"<?= $filters['activity_type'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg">
                    <label class="form-label" for="filter-commodity">Commodity</label>
                    <input class="form-control" id="filter-commodity" name="commodity" type="search" value="<?= e($filters['commodity']) ?>" placeholder="Search commodity">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Apply filters</button>
                    <?php if (array_filter($filters, fn ($value) => $value !== '') !== []): ?>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('/monitoring')) ?>">Clear filters</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</section>

<section class="mb-4" aria-labelledby="activity-summary-title">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 mb-1" id="activity-summary-title">Activity summary</h2>
            <p class="text-muted small mb-0"><?= count($activities) ?> matching <?= count($activities) === 1 ? 'entry' : 'entries' ?></p>
        </div>
    </div>
    <div class="card">
        <?php if ($activities): ?>
            <div class="table-wrap">
                <div class="table-scroll">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Activity</th>
                                <th scope="col">Type</th>
                                <th scope="col">Barangay</th>
                                <th scope="col">Commodity</th>
                                <th scope="col">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?= e(date('M j, Y', strtotime($activity['date']))) ?></td>
                                    <td class="fw-semibold"><?= e($activity['title']) ?></td>
                                    <td><?= Ui::badge($activityTypes[$activity['type']], $activity['type'] === 'production' ? 'success' : ($activity['type'] === 'damage' ? 'danger' : 'info')) ?></td>
                                    <td><?= e($activity['barangay']) ?></td>
                                    <td><?= e($activity['commodity']) ?></td>
                                    <td><?= e($activity['details']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <?= Ui::emptyState('No activities match these filters', 'Try changing or clearing the selected filters.') ?>
        <?php endif; ?>
    </div>
</section>

<section aria-labelledby="monitoring-sections-title">
    <div class="mb-3">
        <h2 class="h5 mb-1" id="monitoring-sections-title">Monitoring sections</h2>
        <p class="text-muted small mb-0">Open a section to view its records or continue to its activity page.</p>
    </div>
    <div class="row g-3">
        <?php foreach ($sections as $section): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= e($section['icon']) ?> text-primary" aria-hidden="true"></i>
                            <h3 class="h6 mb-0"><?= e($section['title']) ?></h3>
                        </div>
                        <p class="text-muted small flex-grow-1"><?= e($section['description']) ?></p>
                        <a class="btn btn-outline-primary btn-sm align-self-start" href="<?= e(url($section['path'])) ?>">
                            <?= e($section['action']) ?><i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
