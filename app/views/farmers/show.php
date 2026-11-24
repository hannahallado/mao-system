<?php
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
$add = fn (string $path, string $label) => '<a class="btn btn-outline-secondary btn-sm" href="' . e(url($path . '/create?profile_id=' . $id)) . '">+ ' . e($label) . '</a>';
$farms     = Farm::forProfile($id);
$fishery   = FisheryAsset::forProfile($id);
$livestock = LivestockRecord::forProfile($id);
$orgs      = OrganizationMember::forProfile($id);
?>
<?= Ui::pageHead(Profile::fullName($profile),
    'registered ' . format_date($profile['created_at']),
    Ui::statusBadge($profile['status'], Profile::STATUSES[$profile['status']] ?? null)
    . '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/farmers')) . '">Back to list</a>'
    . '<a class="btn btn-primary btn-sm" href="' . e(url('/farmers/' . $id . '/edit')) . '">Edit profile</a>'
    . (Auth::isAgriculturist() ? ' <form method="post" action="' . e(url('/farmers/' . $id . '/delete')) . '" style="display:inline;" onsubmit="return confirm(\'Remove ' . e(Profile::fullName($profile)) . ' from the registry? This cannot be undone.\')"><input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '"><button type="submit" class="btn btn-outline-danger btn-sm">Remove</button></form>' : '')) ?>

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
                        'Full name' => Profile::fullName($profile),
                        'Sex'       => $profile['sex'],
                        'Birthdate' => $profile['birthdate'] ? format_date($profile['birthdate']) : null,
                        'Contact number' => $profile['contact_no'],
                        'Barangay' => $profile['barangay_name'],
                        'Address' => $profile['address_detail'],
                    ]) ?>
                </div></div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-body">
                    <h6 class="card-title">Livelihoods</h6>
                    <?php if ($profile['has_farming']): ?>
                        <div class="mb-3">
                            <h6 class="text-primary mb-2"><span class="badge badge-soft-success">Farmer</span></h6>
                            <?= Ui::detailList(['RSBSA Number' => $profile['farming_rsbsa']]) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($profile['has_fishery']): ?>
                        <div>
                            <h6 class="text-primary mb-2"><span class="badge badge-soft-success">Fisherfolk</span></h6>
                            <?= Ui::detailList(['RSBSA Number' => $profile['fishery_rsbsa'], 'FishR Number' => $profile['fishr_no']]) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$profile['has_farming'] && !$profile['has_fishery']): ?>
                        <p class="text-muted mb-0">No livelihoods recorded.</p>
                    <?php endif; ?>
                </div></div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-farms" role="tabpanel">
        <?= Ui::dataTable([
            ['label' => 'Parcel', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/farms/' . $r['id'])) . '">' . e($r['parcel_name']) . '</a>'],
            ['label' => 'Barangay', 'key' => 'barangay_name'],
            ['label' => 'Commodity', 'key' => 'commodity_name'],
            ['label' => 'Ecosystem', 'render' => fn ($r) => e(Farm::ECOSYSTEMS[$r['ecosystem']] ?? $r['ecosystem'])],
            ['label' => 'Area (ha)', 'class' => 'num', 'render' => fn ($r) => number_format((float) $r['area_ha'], 2)],
        ], $farms, ['empty' => Ui::emptyState('No farm records yet', 'Farm parcels linked to this profile will be listed here.', $add('/farms', 'Add farm'))]) ?>
    </div>

    <div class="tab-pane fade" id="tab-fishery" role="tabpanel">
        <?= Ui::dataTable([
            ['label' => 'Description', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/fishery/' . $r['id'])) . '">' . e($r['description']) . '</a>'],
            ['label' => 'Type', 'render' => fn ($r) => e(FisheryAsset::TYPES[$r['asset_type']] ?? $r['asset_type'])],
            ['label' => 'Barangay', 'key' => 'barangay_name'],
            ['label' => 'Reg. no.', 'key' => 'registration_no'],
        ], $fishery, ['empty' => Ui::emptyState('No fishery records yet', 'Boats, gear and ponds linked to this profile will be listed here.', $add('/fishery', 'Add fishery record'))]) ?>
    </div>

    <div class="tab-pane fade" id="tab-livestock" role="tabpanel">
        <?= Ui::dataTable([
            ['label' => 'Species', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/livestock/' . $r['id'])) . '">' . e($r['species']) . '</a>'],
            ['label' => 'Count', 'class' => 'num', 'render' => fn ($r) => number_format((int) $r['animal_count'])],
            ['label' => 'Purpose', 'render' => fn ($r) => e(LivestockRecord::PURPOSES[$r['purpose']] ?? '—')],
            ['label' => 'As of', 'render' => fn ($r) => e(format_date($r['as_of_date']))],
        ], $livestock, ['empty' => Ui::emptyState('No livestock and poultry records yet', 'Animals raised by this profile will be listed here.', $add('/livestock', 'Add record'))]) ?>
    </div>

    <div class="tab-pane fade" id="tab-organizations" role="tabpanel">
        <?= Ui::dataTable([
            ['label' => 'Organization', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/organizations/' . $r['organization_id'])) . '">' . e($r['org_name']) . '</a>'],
            ['label' => 'Type', 'render' => fn ($r) => e(Organization::TYPES[$r['org_type']] ?? $r['org_type'])],
            ['label' => 'Position', 'key' => 'position'],
            ['label' => 'Joined', 'render' => fn ($r) => e(format_date($r['date_joined']))],
        ], $orgs, ['empty' => Ui::emptyState('No organization memberships yet', 'Open an organization to add this person as a member.', '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/organizations')) . '">Browse organizations</a>')]) ?>
    </div>

    <div class="tab-pane fade" id="tab-training" role="tabpanel">
        <div class="card"><?= Ui::emptyState('No training attendance yet', 'Training attendance linked to this profile will be listed here.') ?></div>
    </div>

    <div class="tab-pane fade" id="tab-assistance" role="tabpanel">
        <div class="card"><?= Ui::emptyState('Assistance history', 'Everything this person has received, newest first.',
            '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/profiles/' . $id . '/assistance-history')) . '">Open full history</a>') ?></div>
    </div>
</div>
