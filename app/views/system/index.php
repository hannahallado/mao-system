<?php
/** System page (Municipal Agriculturist only). Vars from SystemController::index(). */
$tabUrl = fn (string $key, array $q = []) => url('/system?' . http_build_query(['tab' => $key] + $q));
?>
<?= Ui::pageHead('System', null,
    $tab === 'users' ? '<button type="button" class="btn btn-primary btn-sm" data-user-action="add"><i class="bi bi-person-plus" aria-hidden="true"></i> Add User</button>' : '') ?>

<ul class="nav nav-tabs module-tabs">
    <?php foreach (SystemController::TABS as $key => [$label, $icon]): ?>
        <li class="nav-item"><a class="nav-link <?= $key === $tab ? 'active' : '' ?>" href="<?= e($tabUrl($key)) ?>" <?= $key === $tab ? 'aria-current="page"' : '' ?>>
            <i class="bi bi-<?= e($icon) ?>" aria-hidden="true"></i> <?= e($label) ?></a></li>
    <?php endforeach; ?>
</ul>

<?php if ($tab === 'users'): ?>
    <?php
    $editing = $editId ? array_values(array_filter($users, fn ($u) => (int) $u['id'] === $editId))[0] ?? null : null;
    $userData = [];
    foreach ($users as $u) {
        $userData[$u['id']] = ['full_name' => $u['full_name'], 'username' => $u['username'], 'role' => $u['role'], 'sector' => $u['sector']];
    }
    ?>
    <?= Ui::dataTable([
        ['label' => 'Name', 'render' => fn ($u) => '<span class="fw-semibold">' . e($u['full_name']) . '</span>'
            . ((int) $u['id'] === Auth::id() ? ' <span class="badge badge-soft-muted">You</span>' : '')],
        ['label' => 'Username', 'render' => fn ($u) => '<code>' . e($u['username']) . '</code>'],
        ['label' => 'Role', 'render' => fn ($u) => e(SystemController::ROLES[$u['role']] ?? $u['role'])],
        ['label' => 'Sector', 'render' => fn ($u) => e(SystemController::SECTORS[$u['sector']] ?? $u['sector'])],
        ['label' => 'Last login', 'render' => fn ($u) => $u['last_login_at'] ? e(date('M j, Y g:i A', strtotime($u['last_login_at']))) : '<span class="text-muted">Never</span>'],
        ['label' => 'Status', 'render' => fn ($u) => $u['is_active'] ? Ui::badge('Active', 'success') : Ui::badge('Deactivated', 'muted')],
        ['label' => 'Actions', 'class' => 'table-actions', 'render' => function ($u) {
            $id = (int) $u['id'];
            $html = '<div class="row-actions"><button type="button" class="btn-icon-action" title="Edit" aria-label="Edit ' . e($u['full_name']) . '" data-user-action="edit" data-id="' . $id . '"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>';
            if ($id !== Auth::id()) {
                $html .= '<form method="post" action="' . e(url('/system/users/' . $id . '/status')) . '" class="d-inline">' . Csrf::field()
                    . '<button type="submit" class="btn-icon-action' . ($u['is_active'] ? ' is-danger' : '') . '" title="' . ($u['is_active'] ? 'Deactivate' : 'Reactivate') . '"'
                    . ' aria-label="' . ($u['is_active'] ? 'Deactivate ' : 'Reactivate ') . e($u['full_name']) . '"'
                    . ' data-confirm="' . e(($u['is_active'] ? 'Deactivate ' : 'Reactivate ') . $u['full_name'] . '?') . '">'
                    . '<i class="bi bi-' . ($u['is_active'] ? 'person-slash' : 'person-check') . '" aria-hidden="true"></i></button></form>';
            }
            return $html . '</div>';
        }],
    ], $users, ['empty' => Ui::emptyState('No users', 'Add the first account.')]) ?>
    <p class="text-muted small mt-2">Accounts are deactivated instead of deleted, so names stay in the audit log. Deactivated users can't log in.</p>

    <div class="modal fade register-modal" id="userModal" tabindex="-1" aria-labelledby="userModalTitle" aria-hidden="true" data-bs-backdrop="static"
         <?= $openForm ? 'data-open-on-load="1"' : '' ?> data-store-url="<?= e(url('/system/users')) ?>" data-update-base="<?= e(url('/system/users/')) ?>"
         data-editing-id="<?= $editing ? (int) $editing['id'] : '' ?>">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" action="<?= e($editing ? url('/system/users/' . (int) $editing['id']) : url('/system/users')) ?>" novalidate autocomplete="off">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="userModalTitle"><?= $editing ? 'Edit User' : 'Add User' ?></h5>
                        <p class="register-modal-sub" data-role="hint"><?= $editing ? 'Leave the password blank to keep the current one.' : 'The user logs in with this username and password.' ?></p>
                    </div>
                    <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close"><span class="bar"></span><span class="bar"></span></button>
                </div>
                <div class="modal-body">
                    <?= Csrf::field() ?>
                    <div class="row g-3">
                        <?= Ui::input('full_name', 'Full name', ['required' => true, 'col' => 'col-12', 'maxlength' => 120, 'value' => $editing['full_name'] ?? '']) ?>
                        <?= Ui::input('username', 'Username', ['required' => true, 'col' => 'col-md-6', 'maxlength' => 50, 'value' => $editing['username'] ?? '',
                            'help' => 'Letters, numbers, dots, dashes, underscores.']) ?>
                        <?= Ui::select('role', 'Role', SystemController::ROLES, ['required' => true, 'col' => 'col-md-6', 'value' => $editing['role'] ?? 'technician']) ?>
                        <?= Ui::select('sector', 'Sector', SystemController::SECTORS, ['required' => true, 'col' => 'col-md-6', 'value' => $editing['sector'] ?? 'all',
                            'help' => 'Technicians can only add and edit records for their sector.']) ?>
                        <div class="col-md-6"></div>
                        <?= Ui::input('password', 'Password', ['type' => 'password', 'col' => 'col-md-6', 'required' => !$editing, 'maxlength' => 72, 'help' => 'At least 8 characters.']) ?>
                        <?= Ui::input('password_confirm', 'Confirm password', ['type' => 'password', 'col' => 'col-md-6', 'required' => !$editing, 'maxlength' => 72]) ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-role="submit"><?= $editing ? 'Save changes' : 'Create account' ?></button>
                </div>
            </form>
        </div>
    </div>
    <script id="userRecords" type="application/json"><?= json_encode($userData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<?php elseif ($tab === 'access'): ?>
    <?php
    $yes = '<i class="bi bi-check-circle-fill text-success" aria-label="Allowed"></i>';
    $no  = '<i class="bi bi-x-circle text-muted" aria-label="Not allowed"></i>';
    $matrix = [
        ['Registry, Resources, GIS Map, Monitoring and Reports: view and search', $yes, $yes],
        ['Register and edit farmer/fisherfolk profiles', $yes, $yes],
        ['Add and edit resource and monitoring records', $yes . ' <span class="small text-muted">own sector only</span>', $yes],
        ['Program beneficiaries and assistance distribution', $yes, $yes],
        ['Create and edit programs (program setup)', $no, $yes],
        ['Duplicate assistance: enter an override reason', $yes, $yes],
        ['Delete (archive) records', $no, $yes],
        ['Full dashboard', $no . ' <span class="small text-muted">limited cards</span>', $yes],
        ['System: users, access control, audit log, backup, settings', $no, $yes],
    ];
    ?>
    <div class="card">
        <div class="card-header">Role capabilities</div>
        <div class="table-scroll">
            <table class="table mb-0 access-table">
                <thead><tr><th>Action</th><th class="text-center">Agricultural Technologist</th><th class="text-center">Municipal Agriculturist</th></tr></thead>
                <tbody>
                    <?php foreach ($matrix as [$label, $tech, $agri]): ?>
                        <tr><td><?= e($label) ?></td><td class="text-center"><?= $tech ?></td><td class="text-center"><?= $agri ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title">Sector restriction (technicians)</h6>
                <p class="small mb-2">Each technician has a sector: Crops, Livestock and Poultry, Fisheries, or All. They can view everything, but only add and edit records for their sector:</p>
                <ul class="small mb-0">
                    <li><strong>Crops:</strong> farms, production, damage and pest</li>
                    <li><strong>Livestock and Poultry:</strong> livestock records</li>
                    <li><strong>Fisheries:</strong> fishery assets</li>
                    <li>Equipment, organizations and trainings are open to every sector.</li>
                </ul>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title">Where it is enforced</h6>
                <p class="small mb-0">Buttons a role can't use are hidden, and the server checks the role and sector again on every save, so a direct link can't get around it.
                    Change a person's role or sector in the <a href="<?= e($tabUrl('users')) ?>">Users</a> tab.</p>
            </div></div>
        </div>
    </div>

<?php elseif ($tab === 'audit'): ?>
    <form method="get" action="<?= url('/system') ?>" class="report-filters">
        <input type="hidden" name="tab" value="audit">
        <label class="report-filter"><span>Record type</span>
            <select class="form-select" name="table"><option value="">All</option>
                <?php foreach ($tables as $t): ?><option value="<?= e($t) ?>" <?= $filters['table'] === $t ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $t))) ?></option><?php endforeach; ?>
            </select></label>
        <label class="report-filter"><span>Action</span>
            <select class="form-select" name="action"><option value="">All</option>
                <?php foreach ($actions as $k => $l): ?><option value="<?= e($k) ?>" <?= $filters['action'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select></label>
        <label class="report-filter"><span>User</span>
            <select class="form-select" name="user_id"><option value="">All</option>
                <?php foreach ($auditUsers as $k => $l): ?><option value="<?= e($k) ?>" <?= $filters['user_id'] === (string) $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select></label>
        <label class="report-filter"><span>From</span><input type="date" class="form-control" name="date_from" value="<?= e($filters['date_from']) ?>"></label>
        <label class="report-filter"><span>To</span><input type="date" class="form-control" name="date_to" value="<?= e($filters['date_to']) ?>"></label>
        <div class="report-filter-actions">
            <button class="btn btn-primary btn-sm" type="submit">Filter</button>
            <?php if (array_filter($filters)): ?><a class="btn btn-link btn-sm text-muted" href="<?= e($tabUrl('audit')) ?>">Reset</a><?php endif; ?>
        </div>
    </form>
    <?php
    $changes = function (array $r): string {
        $old = json_decode((string) $r['old_values'], true) ?: [];
        $new = json_decode((string) $r['new_values'], true) ?: [];
        $parts = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $k) {
            if (in_array($k, ['created_at', 'updated_at', 'created_by', 'password_hash'], true)) {
                continue;
            }
            $o = $old[$k] ?? null;
            $n = $new[$k] ?? null;
            $fmt = fn ($v) => is_array($v) ? implode(', ', $v) : (($v === null || $v === '') ? '—' : (string) $v);
            $parts[] = '<span class="audit-field">' . e(str_replace('_', ' ', $k)) . '</span> '
                . ($r['action'] === 'update' ? e($fmt($o)) . ' &rarr; ' : '') . e(mb_strimwidth($fmt($n), 0, 60, '…'));
        }
        $html = implode('<br>', array_slice($parts, 0, 6)) . (count($parts) > 6 ? '<br><span class="text-muted">+' . (count($parts) - 6) . ' more</span>' : '');
        return ($r['note'] ? '<div class="text-muted">' . e($r['note']) . '</div>' : '') . $html;
    };
    $actionBadge = ['create' => 'success', 'update' => 'info', 'archive' => 'danger', 'override' => 'warning'];
    ?>
    <?= Ui::dataTable([
        ['label' => 'When', 'render' => fn ($r) => '<span class="text-nowrap">' . e(date('M j, Y g:i A', strtotime($r['created_at']))) . '</span>'],
        ['label' => 'User', 'render' => fn ($r) => e($r['full_name'] ?? 'System')],
        ['label' => 'Action', 'render' => fn ($r) => Ui::badge($actions[$r['action']] ?? $r['action'], $actionBadge[$r['action']] ?? 'muted')],
        ['label' => 'Record', 'render' => fn ($r) => e(ucwords(str_replace('_', ' ', $r['table_name']))) . ($r['record_id'] ? ' <span class="text-muted">#' . (int) $r['record_id'] . '</span>' : '')],
        ['label' => 'Details', 'render' => $changes],
    ], $result['data'], ['empty' => Ui::emptyState('No audit entries', 'Changes to records will be listed here.')]) ?>
    <?= Ui::pagination($result, '/system', array_filter($filters) + ['tab' => 'audit']) ?>

<?php elseif ($tab === 'backup'): ?>
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title">Download a backup</h6>
                <p class="small">Saves the whole database (all tables and records) as one <code>.sql</code> file. To restore it, open phpMyAdmin, select the database, and use <strong>Import</strong>.</p>
                <form method="post" action="<?= url('/system/backup') ?>">
                    <?= Csrf::field() ?>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-download" aria-hidden="true"></i> Download backup (.sql)</button>
                </form>
                <hr>
                <h6 class="card-title">Keep backups safe</h6>
                <ul class="small mb-0">
                    <li>The file has personal data and password hashes. Store it on an office drive, not a personal phone or public cloud folder.</li>
                    <li>Make a backup at least once a week, and before any big change.</li>
                    <li>Keep a few older copies, and test restoring one now and then.</li>
                    <li>Each download is recorded in the Audit Log.</li>
                </ul>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">What the backup includes</div>
                <div class="table-scroll">
                    <table class="table mb-0">
                        <thead><tr><th>Table</th><th class="num">Records (approx.)</th><th class="num">Size</th></tr></thead>
                        <tbody>
                            <?php foreach ($tables as $t): ?>
                                <tr><td><code><?= e($t['name']) ?></code></td><td class="num"><?= number_format((int) $t['approx_rows']) ?></td><td class="num"><?= number_format((int) $t['size_kb']) ?> KB</td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'settings'): ?>
    <?php if (!$settingsReady): ?>
        <div class="alert alert-warning">Settings are not set up yet. Run <code>database/schema_modules.sql</code> on your database (phpMyAdmin &gt; SQL). The values below are the defaults.</div>
    <?php endif; ?>
    <form method="post" action="<?= url('/system/settings') ?>" class="card" novalidate>
        <div class="card-body">
            <?= Csrf::field() ?>
            <?php
            $field = fn (string $key, string $col) => Ui::input($key, Setting::FIELDS[$key][0], [
                'value' => $settings[$key] ?? '', 'col' => $col, 'maxlength' => Setting::FIELDS[$key][2],
                'help' => Setting::FIELDS[$key][1], 'required' => $key === 'office_name', 'type' => $key === 'email' ? 'email' : 'text']);
            ?>
            <?= Ui::section('Office', $field('office_name', 'col-md-6') . $field('municipality', 'col-md-3') . $field('province', 'col-md-3')
                . $field('contact_no', 'col-md-6') . $field('email', 'col-md-6')) ?>
            <?= Ui::section('Report signatories', $field('prepared_by', 'col-md-4') . $field('approved_by', 'col-md-4') . $field('approved_title', 'col-md-4'),
                'Printed at the bottom of every report. Leave "Prepared by" blank to use the name of whoever generates the report.') ?>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary" type="submit" <?= $settingsReady ? '' : 'disabled' ?>>Save settings</button>
        </div>
    </form>
<?php endif; ?>
