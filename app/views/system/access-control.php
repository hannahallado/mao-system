<?php
$permissions = [
    [
        'area' => 'Farmer/Fisherfolk Registry',
        'technician' => 'View, register and edit profiles',
        'agriculturist' => 'View, register and edit profiles',
        'note' => 'Removing a profile is restricted to the Municipal Agriculturist.',
    ],
    [
        'area' => 'Farm Records (Crops)',
        'technician' => 'View all; create/edit with crops or all-sector assignment',
        'agriculturist' => 'View, create and edit all records',
        'note' => 'Record removal is restricted to the Municipal Agriculturist.',
    ],
    [
        'area' => 'Livestock and Poultry',
        'technician' => 'View all; create/edit with livestock/poultry or all-sector assignment',
        'agriculturist' => 'View, create and edit all records',
        'note' => 'Record removal is restricted to the Municipal Agriculturist.',
    ],
    [
        'area' => 'Fishery Records',
        'technician' => 'View all; create/edit with fisheries or all-sector assignment',
        'agriculturist' => 'View, create and edit all records',
        'note' => 'Record removal is restricted to the Municipal Agriculturist.',
    ],
    [
        'area' => 'Equipment and Organizations',
        'technician' => 'View, create and edit',
        'agriculturist' => 'View, create and edit',
        'note' => 'These shared resources are not assigned to a specific technician sector. Removal is administrator-only.',
    ],
    [
        'area' => 'Monitoring and Assistance',
        'technician' => 'Access and record activities',
        'agriculturist' => 'Access and record activities',
        'note' => 'These screens are currently available to authenticated users without sector-specific restrictions.',
    ],
    [
        'area' => 'Programs',
        'technician' => 'View programs and assistance records',
        'agriculturist' => 'Create, edit and remove programs',
        'note' => 'Program management actions are restricted to the Municipal Agriculturist.',
    ],
    [
        'area' => 'System Management',
        'technician' => 'No access',
        'agriculturist' => 'Manage users and system settings',
        'note' => 'User Management, Access Control, Backup and Security, and System Settings are administrator-only.',
    ],
];

$columns = [
    ['label' => 'Area', 'render' => static fn ($row) => '<span class="fw-semibold">' . e($row['area']) . '</span>'],
    ['label' => 'Technician', 'key' => 'technician'],
    ['label' => 'Municipal Agriculturist', 'key' => 'agriculturist'],
    ['label' => 'Notes', 'key' => 'note'],
];
?>

<?= Ui::pageHead('Access Control', 'Permissions are enforced by role and, for selected records, the technician’s assigned sector.') ?>

<div class="card mb-3">
    <div class="card-body">
        <h2 class="h6">Technician sectors</h2>
        <p class="mb-2">Technician accounts may be assigned one of these sectors:</p>
        <div class="d-flex flex-wrap gap-2">
            <?= Ui::badge('Crops', 'success') ?>
            <?= Ui::badge('Livestock and Poultry', 'info') ?>
            <?= Ui::badge('Fisheries', 'primary') ?>
            <?= Ui::badge('All sectors', 'muted') ?>
        </div>
        <p class="text-muted small mt-3 mb-0">All authenticated users can view records. A technician assigned to “All sectors” can create and edit the sector-scoped farm, livestock, and fishery records.</p>
    </div>
</div>

<?= Ui::dataTable($columns, $permissions) ?>
