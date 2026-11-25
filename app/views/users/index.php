<?php
$fields = [
    ['type' => 'search', 'name' => 'q', 'placeholder' => 'Search name or username'],
    ['name' => 'role', 'all' => 'All roles', 'options' => $roles],
    ['name' => 'sector', 'all' => 'All sectors', 'options' => $sectors],
    ['name' => 'status', 'all' => 'All account statuses', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
];
$currentUserId = Auth::id();
$columns = [
    ['label' => 'User', 'render' => static fn ($user) => '<span class="fw-semibold">' . e($user['full_name']) . '</span><br><span class="text-muted small">@' . e($user['username']) . '</span>'],
    ['label' => 'Role', 'render' => static fn ($user) => e($roles[$user['role']] ?? $user['role'])],
    ['label' => 'Sector', 'render' => static fn ($user) => e($sectors[$user['sector']] ?? $user['sector'])],
    ['label' => 'Status', 'render' => static fn ($user) => Ui::statusBadge((int) $user['is_active'] === 1 ? 'active' : 'inactive')],
    ['label' => 'Last login', 'render' => static fn ($user) => e($user['last_login_at'] ? format_date($user['last_login_at'], 'M d, Y g:i A') : 'Never')],
    ['label' => 'Account action', 'class' => 'table-actions', 'render' => static function ($user) use ($currentUserId, $activeAdministrators) {
        $isActive = (int) $user['is_active'] === 1;
        if ($isActive && (int) $user['id'] === $currentUserId) {
            return '<span class="text-muted small">Current account</span>';
        }
        if ($isActive && $user['role'] === 'agriculturist' && $activeAdministrators <= 1) {
            return '<span class="text-muted small">Required administrator</span>';
        }
        $action = $isActive ? 'deactivate' : 'activate';
        $label = $isActive ? 'Deactivate' : 'Activate';
        $class = $isActive ? 'btn-outline-danger' : 'btn-outline-success';
        return '<form method="post" action="' . e(url('/system/users/' . $user['id'] . '/status')) . '" class="d-inline">'
            . Csrf::field()
            . '<input type="hidden" name="action" value="' . e($action) . '">'
            . '<button class="btn ' . $class . ' btn-sm" type="submit">' . $label . '</button>'
            . '</form>';
    }],
];
$officeStaff = [
    ['name' => 'Pearl L. Teodoro', 'position' => 'Municipal Agriculturist'],
    ['name' => 'Girlie L. Gamboa', 'position' => 'Agricultural Technologist'],
    ['name' => 'Federico V. Dela Cruz III', 'position' => 'Agricultural Technician'],
    ['name' => 'Mary Grace M. Miravalles', 'position' => 'Agricultural Technologist'],
    ['name' => 'Gene Mae N. Sumangad', 'position' => 'Admin Aide'],
    ['name' => 'Michael Roy L. Reyes', 'position' => 'AWW'],
    ['name' => 'Julie Abby G. Quijano', 'position' => 'AWW'],
];
?>

<?= Ui::pageHead('User Management', 'Review user accounts, roles, sector assignments, and account access.') ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-lg-4">
                <div class="stat-label">Users shown</div>
                <div class="stat-value"><?= number_format(count($users)) ?></div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="stat-label">Active</div>
                <div class="stat-value"><?= number_format($activeCount) ?></div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="stat-label">Inactive</div>
                <div class="stat-value"><?= number_format($inactiveCount) ?></div>
            </div>
        </div>
    </div>
</div>

<?= Ui::filterBar('/system/users', $fields, $filters) ?>
<?= Ui::dataTable($columns, $users, [
    'empty' => Ui::emptyState(
        'No matching user accounts',
        'Try changing or clearing the filters.',
        '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/system/users')) . '">Clear filters</a>'
    ),
]) ?>

<section class="mt-4">
    <h2 class="h5 mb-1">Municipal Agriculture Office staff</h2>
    <p class="text-muted small mb-2">Reference roster from the organizational chart. These entries are not login accounts; the mayor is excluded.</p>
    <?= Ui::dataTable([
        ['label' => 'Staff member', 'key' => 'name'],
        ['label' => 'Position', 'key' => 'position'],
    ], $officeStaff) ?>
</section>
