<?php
/** Profile details + tabs. Used by the full profile page (show.php) and the View modal on the Registry list. */
$id   = (int) $profile['id'];
$tabs = [
    'overview'      => 'Overview',
    'farms'         => 'Farms',
    'fishery'       => 'Fishery',
    'livestock'     => 'Livestock',
    'organizations' => 'Organizations',
    'assistance'    => 'Assistance',
    'training'      => 'Training',
];
// Records linked to this person (Resources and Monitoring tables, database/schema_modules.sql)
$res = RecordModule::module('resources')['tabs'];
$mon = RecordModule::module('monitoring')['tabs'];
$opt = fn (array $tab, string $field, ?string $v) => $v === null || $v === '' ? '—' : ($tab['fields'][$field]['options'][$v] ?? $v);
$num = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 4), '0'), '.');
$rowsOf = fn (string $table, string $sql) => RecordModule::tableReady($table) ? Model::raw($sql, [$id]) : null;
$openTab = fn (string $module, string $tab, string $q = '') => url('/' . $module . '?' . http_build_query(['tab' => $tab] + ($q !== '' ? ['q' => $q] : [])));
$addBtn = fn (string $module, string $tab, string $label) =>
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/' . $module . '?' . http_build_query(['tab' => $tab, 'form' => 'add', 'profile_id' => $id]))) . '">+ ' . e($label) . '</a>';

$linked = [
    'farms' => [
        'rows' => $rowsOf('farms', 'SELECT f.farm_name, f.commodity, f.water_source, f.area_ha, b.name AS barangay FROM farms f
                                    JOIN barangays b ON b.id = f.barangay_id WHERE f.profile_id = ? AND f.deleted_at IS NULL ORDER BY f.farm_name'),
        'cols' => [
            ['label' => 'Farm / parcel', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e($openTab('resources', 'farms', $r['farm_name'])) . '">' . e($r['farm_name']) . '</a>'],
            ['label' => 'Barangay', 'key' => 'barangay'],
            ['label' => 'Commodity', 'render' => fn ($r) => e($opt($res['farms'], 'commodity', $r['commodity']))],
            ['label' => 'Water source', 'render' => fn ($r) => e($opt($res['farms'], 'water_source', $r['water_source']))],
            ['label' => 'Area (ha)', 'class' => 'num', 'render' => fn ($r) => e($num($r['area_ha']))],
        ],
        'empty' => ['No farm records yet', 'Farms owned or operated by this person will be listed here.', $addBtn('resources', 'farms', 'Add farm')],
    ],
    'fishery' => [
        'rows' => $rowsOf('fishery_assets', 'SELECT a.name, a.asset_type, a.registration_no, a.status, b.name AS barangay FROM fishery_assets a
                                             JOIN barangays b ON b.id = a.barangay_id WHERE a.profile_id = ? AND a.deleted_at IS NULL ORDER BY a.name'),
        'cols' => [
            ['label' => 'Name', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e($openTab('resources', 'fishery', $r['name'])) . '">' . e($r['name']) . '</a>'],
            ['label' => 'Type', 'render' => fn ($r) => e($opt($res['fishery'], 'asset_type', $r['asset_type']))],
            ['label' => 'Barangay', 'key' => 'barangay'],
            ['label' => 'Reg. no.', 'render' => fn ($r) => e($r['registration_no'] ?: '—')],
            ['label' => 'Status', 'render' => fn ($r) => e($opt($res['fishery'], 'status', $r['status']))],
        ],
        'empty' => ['No fishery records yet', 'Boats, gear and ponds of this person will be listed here.', $addBtn('resources', 'fishery', 'Add fishery asset')],
    ],
    'livestock' => [
        'rows' => $rowsOf('livestock_records', 'SELECT l.category, l.species, l.head_count, l.purpose, b.name AS barangay FROM livestock_records l
                                                JOIN barangays b ON b.id = l.barangay_id WHERE l.profile_id = ? AND l.deleted_at IS NULL ORDER BY l.id DESC'),
        'cols' => [
            ['label' => 'Species', 'render' => fn ($r) => '<span class="fw-semibold">' . e($opt($res['livestock'], 'species', $r['species'])) . '</span>'],
            ['label' => 'Category', 'render' => fn ($r) => e($opt($res['livestock'], 'category', $r['category']))],
            ['label' => 'Heads', 'class' => 'num', 'render' => fn ($r) => number_format((int) $r['head_count'])],
            ['label' => 'Purpose', 'render' => fn ($r) => e($opt($res['livestock'], 'purpose', $r['purpose']))],
            ['label' => 'Barangay', 'key' => 'barangay'],
        ],
        'empty' => ['No livestock and poultry records yet', 'Animals raised by this person will be listed here.', $addBtn('resources', 'livestock', 'Add livestock record')],
    ],
    'organizations' => [
        'rows' => RecordModule::tableReady('organizations') ? $rowsOf('organization_members',
            'SELECT o.name, o.org_type, o.status, m.position FROM organization_members m
             JOIN organizations o ON o.id = m.organization_id WHERE m.profile_id = ? AND o.deleted_at IS NULL ORDER BY o.name') : null,
        'cols' => [
            ['label' => 'Organization', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e($openTab('resources', 'organizations', $r['name'])) . '">' . e($r['name']) . '</a>'],
            ['label' => 'Type', 'render' => fn ($r) => e($opt($res['organizations'], 'org_type', $r['org_type']))],
            ['label' => 'Position', 'key' => 'position'],
            ['label' => 'Status', 'render' => fn ($r) => e($opt($res['organizations'], 'status', $r['status']))],
        ],
        'empty' => ['No organization memberships yet', 'Add this person as a member from Resources › Organizations.',
                    '<a class="btn btn-outline-secondary btn-sm" href="' . e($openTab('resources', 'organizations')) . '">Open organizations</a>'],
    ],
    'training' => [
        'rows' => RecordModule::tableReady('trainings') ? $rowsOf('training_participants',
            'SELECT t.title, t.activity_type, t.date_held, t.venue FROM training_participants tp
             JOIN trainings t ON t.id = tp.training_id WHERE tp.profile_id = ? AND t.deleted_at IS NULL ORDER BY t.date_held DESC') : null,
        'cols' => [
            ['label' => 'Date', 'render' => fn ($r) => e(format_date($r['date_held']))],
            ['label' => 'Title', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e($openTab('monitoring', 'training', $r['title'])) . '">' . e($r['title']) . '</a>'],
            ['label' => 'Type', 'render' => fn ($r) => e($opt($mon['training'], 'activity_type', $r['activity_type']))],
            ['label' => 'Venue', 'render' => fn ($r) => e($r['venue'] ?: '—')],
        ],
        'empty' => ['No training attendance yet', 'Trainings and activities this person attended will be listed here.', ''],
    ],
];
?>
<ul class="nav nav-tabs" role="tablist">
    <?php foreach ($tabs as $key => $label): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $key === 'overview' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-<?= e($key) ?>" type="button" role="tab"><?= e($label) ?></button>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-body">
                    <h6 class="card-title">Personal Information</h6>
                    <?= Ui::detailList([
                        'Full name'      => Profile::fullName($profile),
                        'Sex'            => $profile['sex'],
                        'Birthdate'      => $profile['birthdate'] ? format_date($profile['birthdate']) : null,
                        'Contact number' => $profile['contact_no'],
                        'Barangay'       => $profile['barangay_name'],
                        'Address'        => $profile['address_detail'],
                        'Status'         => Profile::STATUSES[$profile['status']] ?? $profile['status'],
                        'Registered'     => format_date($profile['created_at']),
                    ]) ?>
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-body">
                    <h6 class="card-title">Livelihoods</h6>
                    <?php if ($profile['has_farming']): ?>
                        <div class="mb-3">
                            <h6 class="mb-2"><span class="badge badge-soft-success">Farmer</span></h6>
                            <?= Ui::detailList(['RSBSA Number' => $profile['farming_rsbsa']]) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($profile['has_fishery']): ?>
                        <div>
                            <h6 class="mb-2"><span class="badge badge-soft-success">Fisherfolk</span></h6>
                            <?= Ui::detailList([
                                'RSBSA Number' => $profile['fishery_rsbsa'],
                                'FishR Number' => $profile['fishr_no'],
                            ]) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$profile['has_farming'] && !$profile['has_fishery']): ?>
                        <p class="text-muted mb-0">No livelihoods recorded.</p>
                    <?php endif; ?>
                </div></div>
            </div>
        </div>
    </div>

    <?php foreach ($linked as $key => $l): ?>
        <div class="tab-pane fade" id="tab-<?= e($key) ?>" role="tabpanel">
            <?php if ($l['rows'] === null): ?>
                <div class="card"><?= Ui::emptyState('Not set up yet', 'Run database/schema_modules.sql on the database to start recording these.') ?></div>
            <?php else: ?>
                <?= Ui::dataTable($l['cols'], $l['rows'], ['empty' => Ui::emptyState(...$l['empty'])]) ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="tab-pane fade" id="tab-assistance" role="tabpanel">
        <div class="card"><?= Ui::emptyState('Assistance history', 'Everything this person has received, newest first.',
            '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/profiles/' . $id . '/assistance-history')) . '">Open full history</a>') ?></div>
    </div>
</div>
