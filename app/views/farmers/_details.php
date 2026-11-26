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
$pending = ['farms' => 'Farm records', 'fishery' => 'Fishery records', 'livestock' => 'Livestock and poultry records',
            'organizations' => 'Organization memberships', 'training' => 'Training attendance'];
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

    <?php foreach ($pending as $key => $label): ?>
        <div class="tab-pane fade" id="tab-<?= e($key) ?>" role="tabpanel">
            <div class="card"><?= Ui::emptyState('No ' . strtolower($label) . ' yet', $label . ' linked to this profile will be listed here.') ?></div>
        </div>
    <?php endforeach; ?>

    <div class="tab-pane fade" id="tab-assistance" role="tabpanel">
        <div class="card"><?= Ui::emptyState('Assistance history', 'Everything this person has received, newest first.',
            '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/profiles/' . $id . '/assistance-history')) . '">Open full history</a>') ?></div>
    </div>
</div>
