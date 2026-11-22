<?php
$id      = (int) $program['id'];
$target  = $program['target_beneficiaries'] !== null ? (int) $program['target_beneficiaries'] : null;
$served  = (int) $program['served_count'];
$onList  = (int) $program['beneficiary_count'];
$pct     = $target ? min(100, (int) round($served / $target * 100)) : null;
$tabs    = ['overview' => 'Overview', 'beneficiaries' => 'Beneficiaries (' . $onList . ')', 'distributions' => 'Distributions (' . count($distributions) . ')'];
$tabLink = fn (string $t) => '#tab-' . $t;

$actions = Ui::statusBadge($program['status'], Program::STATUSES[$program['status']] ?? null)
    . '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/programs')) . '">Back to list</a>'
    . (Auth::isAgriculturist() ? '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/programs/' . $id . '/edit')) . '">Edit program</a>' : '')
    . '<a class="btn btn-primary btn-sm" href="' . e(url('/assistance/create?program_id=' . $id)) . '">Record distribution</a>'
    . (Auth::isAgriculturist() ? ' <form method="post" action="' . e(url('/programs/' . $id . '/delete')) . '" style="display:inline;" onsubmit="return confirm(\'Remove ' . e($program['name']) . '? This cannot be undone.\')"><input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '"><button type="submit" class="btn btn-outline-danger btn-sm">Remove</button></form>' : '');
?>
<?= Ui::pageHead($program['name'], $program['program_no'] . ' · ' . $program['source_agency'] . ' · ' . $program['year'], $actions) ?>

<ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $key => $label): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $key === $tab ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-<?= e($key) ?>" type="button" role="tab"><?= e($label) ?></button>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
    <!-- Overview -->
    <div class="tab-pane fade <?= $tab === 'overview' ? 'show active' : '' ?>" id="tab-overview" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card h-100"><div class="card-body">
                    <h6 class="card-title">Progress</h6>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span><?= e($served) ?> served of <?= e($onList) ?> on the list</span>
                        <span><?= $target ? e($served) . ' / ' . e($target) . ' target' : 'No target set' ?></span>
                    </div>
                    <div class="progress" style="height: 10px;"><div class="progress-bar" style="width: <?= (int) ($pct ?? 0) ?>%; background-color: var(--c-secondary, #2d5a3d);"></div></div>
                    <p class="small text-muted mt-2 mb-0"><?= $target ? e($pct) . '% of the target beneficiaries have received assistance.' : 'Set a target on the program to track progress.' ?></p>
                </div></div>
            </div>
            <div class="col-lg-7">
                <div class="card h-100"><div class="card-body">
                    <h6 class="card-title">Program details</h6>
                    <?= Ui::detailList([
                        'Source agency' => $program['source_agency'],
                        'Year'          => $program['year'],
                        'Schedule'      => ($program['start_date'] || $program['end_date']) ? (format_date($program['start_date']) ?: '?') . ' – ' . (format_date($program['end_date']) ?: 'open') : null,
                        'Allocation'    => $program['allocation_amount'] !== null ? '₱ ' . number_format((float) $program['allocation_amount'], 2) : null,
                        'Description'   => $program['description'],
                        'Objectives'    => $program['objectives'],
                    ]) ?>
                </div></div>
            </div>
        </div>
    </div>

    <!-- Beneficiaries: master list + "add from registered profiles" picker -->
    <div class="tab-pane fade <?= $tab === 'beneficiaries' ? 'show active' : '' ?>" id="tab-beneficiaries" role="tabpanel">
        <?php if ($program['status'] !== 'completed'): ?>
        <div class="card mb-3" data-beneficiary-picker
             data-search-url="<?= e(url('/beneficiaries/search')) ?>?program_id=<?= $id ?>"
             data-add-url="<?= e(url('/programs/' . $id . '/beneficiaries')) ?>">
            <div class="card-body">
                <h6 class="card-title">Add from registered profiles</h6>
                <input type="search" class="form-control" data-picker="input" placeholder="Type at least 2 letters of a name, RSBSA or FishR number" autocomplete="off">
                <div class="form-text">Only active profiles from the Farmer/Fisherfolk Registry can be added. To register someone new, use the registry.</div>
                <div class="match-list mt-2" data-picker="results"></div>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $cols = [
            ['label' => 'Farmer / Fisherfolk', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/farmers/' . $r['profile_id'])) . '">' . e(Profile::fullName($r)) . '</a>'],
            ['label' => 'Barangay', 'key' => 'barangay_name'],
            ['label' => 'Livelihood', 'render' => fn ($r) => ProgramBeneficiary::livelihoodBadges($r)],
            ['label' => 'Registry no.', 'render' => fn ($r) => e(ProgramBeneficiary::registryLabel($r) ?: '—')],
            ['label' => 'Status', 'render' => function ($r) use ($id) {
                if ($r['status'] === 'served') {
                    return Ui::statusBadge('served', 'Served');
                }
                $html = '<form method="post" action="' . e(url('/programs/' . $id . '/beneficiaries/' . $r['id'] . '/status')) . '" class="d-flex gap-1 align-items-center">' . Csrf::field()
                    . '<select name="status" class="form-select form-select-sm" style="min-width:120px" onchange="this.form.submit()" aria-label="Status">';
                foreach (ProgramBeneficiary::MANUAL_STATUSES as $k => $label) {
                    $html .= '<option value="' . e($k) . '"' . ($k === $r['status'] ? ' selected' : '') . '>' . e($label) . '</option>';
                }
                return $html . '</select></form>';
            }],
            ['label' => 'Assistance', 'class' => 'num', 'render' => fn ($r) => $r['assistance_count'] > 0
                ? '<a href="' . e(url('/profiles/' . $r['profile_id'] . '/assistance-history')) . '">' . e($r['assistance_count']) . ' received</a>' : '<span class="text-muted">None yet</span>'],
            ['label' => '', 'class' => 'table-actions', 'render' => function ($r) use ($id) {
                $give = '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/assistance/create?program_id=' . $id . '&beneficiary=' . $r['id'])) . '">Give assistance</a> ';
                if ($r['assistance_count'] > 0) {
                    return $give;
                }
                return $give . '<form method="post" class="d-inline" action="' . e(url('/programs/' . $id . '/beneficiaries/' . $r['id'] . '/remove')) . '" onsubmit="return confirm(\'Remove this beneficiary from the program?\')">'
                    . Csrf::field() . '<button class="btn btn-outline-secondary btn-sm" type="submit">Remove</button></form>';
            }],
        ];
        $emptyB = Ui::emptyState('No beneficiaries yet', 'Search the registry above and add the people who qualify for this program.');
        ?>
        <?= Ui::dataTable($cols, $beneficiaries, ['empty' => $emptyB]) ?>
    </div>

    <!-- Distributions -->
    <div class="tab-pane fade <?= $tab === 'distributions' ? 'show active' : '' ?>" id="tab-distributions" role="tabpanel">
        <?php
        $dcols = [
            ['label' => 'Date', 'render' => fn ($r) => e(format_date($r['date_distributed']))],
            ['label' => 'Assistance ID', 'render' => fn ($r) => '<code>' . e($r['assistance_no']) . '</code>'],
            ['label' => 'Beneficiary', 'render' => fn ($r) => '<a href="' . e(url('/profiles/' . $r['profile_id'] . '/assistance-history')) . '">' . e(Profile::fullName($r)) . '</a>'],
            ['label' => 'Assistance', 'key' => 'type_name'],
            ['label' => 'Quantity', 'class' => 'num', 'render' => fn ($r) => e(AssistanceRecord::qty($r))],
            ['label' => '', 'render' => fn ($r) => $r['duplicate_flag'] ? '<span title="' . e($r['override_reason']) . '">' . Ui::badge('Duplicate override', 'warning') . '</span>' : ''],
        ];
        ?>
        <?= Ui::dataTable($dcols, $distributions, ['empty' => Ui::emptyState('No distributions yet', 'Recorded assistance for this program will appear here.',
            '<a class="btn btn-primary btn-sm" href="' . e(url('/assistance/create?program_id=' . $id)) . '">Record distribution</a>')]) ?>
    </div>
</div>
