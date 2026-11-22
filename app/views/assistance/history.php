<?php
$seg = fn (string $key, string $label) => '<li class="nav-item"><a class="nav-link ' . ($by === $key ? 'active' : '') . '" href="' . e(url('/assistance/history?by=' . $key)) . '">' . e($label) . '</a></li>';
?>
<?= Ui::pageHead('Assistance History', 'See everything a farmer or fisherfolk received, or everything a program gave out.',
    '<ul class="nav nav-segmented">' . $seg('farmer', 'By farmer') . $seg('program', 'By program') . '</ul>') ?>

<?php if ($by === 'farmer'): ?>
    <form method="get" action="<?= url('/assistance/history') ?>" class="filter-bar">
        <input type="hidden" name="by" value="farmer">
        <input type="search" class="form-control filter-search" name="q" value="<?= e($q) ?>" placeholder="Search a name, RSBSA/FishR number or contact" autofocus>
        <button class="btn btn-outline-secondary btn-sm" type="submit">Search</button>
    </form>

    <?php if ($q === ''): ?>
        <div class="table-wrap"><?= Ui::emptyState('Search for a farmer or fisherfolk', 'Their full assistance timeline opens from the results.') ?></div>
    <?php else: ?>
        <?= Ui::dataTable([
            ['label' => 'Name', 'render' => fn ($r) => '<span class="fw-semibold">' . e(Profile::fullName($r)) . '</span>'],
            ['label' => 'Barangay', 'key' => 'barangay_name'],
            ['label' => 'Status', 'render' => fn ($r) => Ui::statusBadge($r['status'], Profile::STATUSES[$r['status']] ?? null)],
            ['label' => 'Assistance received', 'class' => 'num', 'render' => fn ($r) => e($counts[$r['id']] ?? 0)],
            ['label' => '', 'class' => 'table-actions', 'render' => fn ($r) => '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/profiles/' . $r['id'] . '/assistance-history')) . '">View history</a>'],
        ], $people, ['empty' => Ui::emptyState('No matching profiles', 'Check the spelling, or search by RSBSA/FishR number.')]) ?>
    <?php endif; ?>

<?php else: ?>
    <form method="get" action="<?= url('/assistance/history') ?>" class="filter-bar" data-auto-submit>
        <input type="hidden" name="by" value="program">
        <select class="form-select" name="program_id" aria-label="Program" style="min-width:300px">
            <option value="">Select a program</option>
            <?php foreach ($programs as $pid => $label): ?>
                <option value="<?= e($pid) ?>" <?= (int) $pid === $programId ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (!$programId): ?>
        <div class="table-wrap"><?= Ui::emptyState('Choose a program', 'Everything given under it appears here, newest first.') ?></div>
    <?php else: ?>
        <?= Ui::dataTable([
            ['label' => 'Date', 'render' => fn ($r) => e(format_date($r['date_distributed']))],
            ['label' => 'Beneficiary', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/profiles/' . $r['profile_id'] . '/assistance-history')) . '">' . e(Profile::fullName($r)) . '</a>'],
            ['label' => 'Barangay', 'key' => 'barangay_name'],
            ['label' => 'Assistance', 'key' => 'type_name'],
            ['label' => 'Quantity', 'class' => 'num', 'render' => fn ($r) => e(AssistanceRecord::qty($r))],
            ['label' => '', 'render' => fn ($r) => $r['duplicate_flag'] ? Ui::badge('Duplicate override', 'warning') : ''],
        ], $records, ['empty' => Ui::emptyState('Nothing distributed yet', 'No assistance has been recorded under this program.')]) ?>
    <?php endif; ?>
<?php endif; ?>
