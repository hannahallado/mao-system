<?php
// Cell helpers (guarded so the view can be rendered more than once per request)
if (!function_exists('livelihoodBadges')) {
function livelihoodBadges($r) {
    $badges = [];
    if ($r['has_farming']) {
        $badges[] = '<span class="badge badge-soft-success">Farmer</span>';
    }
    if ($r['has_fishery']) {
        $badges[] = '<span class="badge badge-soft-success">Fisherfolk</span>';
    }
    return implode(' ', $badges) ?: '<span class="text-muted">—</span>';
}
}

if (!function_exists('registryNumbers')) {
function registryNumbers($r) {
    $nums = [];
    if ($r['farming_rsbsa']) {
        $nums[] = '<code>' . e($r['farming_rsbsa']) . '</code>';
    }
    if ($r['fishery_rsbsa']) {
        $nums[] = '<code>' . e($r['fishery_rsbsa']) . '</code>';
    }
    if ($r['fishr_no']) {
        $nums[] = '<code>' . e($r['fishr_no']) . '</code>';
    }
    return implode(' ', $nums) ?: '<span class="text-muted">—</span>';
}
}

$hasFilters = array_filter($filters, fn ($v) => $v !== '') !== [];
$canDelete  = Auth::isAgriculturist();   // role matrix: archive is hidden for technicians

// Icon-only row actions; data-* tell registry.js which record to open
$actions = function (array $r) use ($canDelete): string {
    $id   = (int) $r['id'];
    $name = e(Farmer::fullName($r));
    $html = '<div class="row-actions">'
        . '<button type="button" class="btn-icon-action" title="View" aria-label="View ' . $name . '"'
        . ' data-action="view" data-name="' . $name . '" data-url="' . e(url('/farmers/' . $id)) . '"'
        . ' data-edit-url="' . e(url('/farmers/' . $id . '/edit')) . '" data-update-url="' . e(url('/farmers/' . $id)) . '">'
        . '<i class="bi bi-eye" aria-hidden="true"></i></button>'
        . '<button type="button" class="btn-icon-action" title="Edit" aria-label="Edit ' . $name . '"'
        . ' data-action="edit" data-name="' . $name . '" data-url="' . e(url('/farmers/' . $id . '/edit')) . '"'
        . ' data-update-url="' . e(url('/farmers/' . $id)) . '">'
        . '<i class="bi bi-pencil-square" aria-hidden="true"></i></button>';
    if ($canDelete) {
        $html .= '<button type="button" class="btn-icon-action is-danger" title="Delete" aria-label="Delete ' . $name . '"'
            . ' data-action="delete" data-name="' . $name . '" data-url="' . e(url('/farmers/' . $id . '/delete')) . '">'
            . '<i class="bi bi-trash3" aria-hidden="true"></i></button>';
    }
    return $html . '</div>';
};

$columns = [
    ['label' => 'Name', 'render' => fn ($r) => '<button type="button" class="link-button fw-semibold" data-action="view"'
        . ' data-name="' . e(Farmer::fullName($r)) . '" data-url="' . e(url('/farmers/' . $r['id'])) . '"'
        . ' data-edit-url="' . e(url('/farmers/' . $r['id'] . '/edit')) . '" data-update-url="' . e(url('/farmers/' . $r['id'])) . '">'
        . e(Farmer::fullName($r)) . '</button>'],
    ['label' => 'Barangay', 'key' => 'barangay_name'],
    ['label' => 'Livelihood', 'render' => fn ($r) => livelihoodBadges($r)],
    ['label' => 'RSBSA / FishR', 'render' => fn ($r) => registryNumbers($r)],
    ['label' => 'Contact', 'key' => 'contact_no'],
    ['label' => 'Status', 'render' => fn ($r) => Ui::statusBadge($r['status'], Profile::STATUSES[$r['status']] ?? null)],
    ['label' => 'Actions', 'class' => 'table-actions', 'render' => $actions],
];

$registerBtn = '<div class="d-flex gap-2">'
    . '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload" aria-hidden="true"></i> Import Profiles</button>'
    . '<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#registerModal">+ Register Profile</button>'
    . '</div>';

$empty = $hasFilters
    ? Ui::emptyState('No matching profiles', 'Try a different name or number, or clear the filters.',
        '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/farmers')) . '">Clear filters</a>')
    : Ui::emptyState('No profiles registered yet', 'Registered profiles will appear here.', $registerBtn);

// Which form a failed save belongs to: the Edit modal (?edit={id}) or Register.
// Old input and field errors are shared, so only the form they belong to uses them.
$editing      = !empty($editProfile);
$openRegister = !$editing && (($openRegister ?? false) || errors() !== []);
?>
<?= Ui::pageHead('Registry', null, $registerBtn) ?>

<?= Ui::filterBar('/farmers', [
    ['type' => 'search', 'name' => 'q', 'placeholder' => 'Search name, RSBSA/FishR no. or contact'],
    ['name' => 'barangay_id', 'all' => 'All barangays', 'options' => $barangays],
    ['name' => 'livelihood_type', 'all' => 'All livelihoods', 'options' => Farmer::LIVELIHOODS],
    ['name' => 'status', 'all' => 'All statuses', 'options' => Profile::STATUSES],
], $filters) ?>

<?= Ui::dataTable($columns, $result['data'], ['empty' => $empty]) ?>
<?= Ui::pagination($result, '/farmers', array_filter($filters, fn ($v) => $v !== '')) ?>

<!-- Import Profiles -->
<div class="modal fade register-modal" id="importModal" tabindex="-1" aria-labelledby="importModalTitle" aria-hidden="true" data-bs-backdrop="static" data-import-url="<?= e(url('/farmers/import')) ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="importModalTitle">Import Profiles</h5>
                    <p class="register-modal-sub">Upload a CSV or Excel file with one person per row.</p>
                </div>
                <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close"><span class="bar"></span><span class="bar"></span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    Required columns: <strong>last_name, first_name, sex, barangay</strong>. Optional: <strong>middle_name, suffix, birthdate, contact_no, address_detail, livelihood_type, rsbsa_no, fishr_no</strong>.
                </div>
                <div class="d-flex gap-2 mb-3">
                    <a class="btn btn-outline-secondary btn-sm" href="data:text/csv;charset=utf-8,last_name,first_name,middle_name,suffix,sex,birthdate,contact_no,barangay,address_detail,livelihood_type,rsbsa_no,fishr_no%0ADela%20Cruz,Juan,,,Male,,09171234567,Barangay%20Zone%201,Purok%201,farmer,,"
                       download="profile-import-template.csv"><i class="bi bi-download" aria-hidden="true"></i> Download CSV template</a>
                </div>
                <label class="form-label" for="profileImportFile">CSV or Excel file</label>
                <input class="form-control" type="file" id="profileImportFile" accept=".csv,.xlsx,.xls">
                <div class="form-text">Files with more than 1,000 rows will only import the first 1,000 rows.</div>
                <div class="mt-3" id="profileImportPreview" hidden>
                    <strong>Preview</strong>
                    <div class="table-scroll mt-2"><table class="table table-sm mb-0"><thead><tr id="profileImportPreviewHead"></tr></thead><tbody id="profileImportPreviewBody"></tbody></table></div>
                </div>
                <div id="profileImportResult" class="mt-3" hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="profileImportSubmit" disabled><i class="bi bi-upload" aria-hidden="true"></i> Import profiles</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js" defer></script>

<?php
// Edit modal body when coming back from a failed save (uses old input + errors)
$editBody = $editing ? partial('farmers/_edit_body', ['profile' => $editProfile, 'barangays' => $barangays]) : '';
if ($editing) {
    // Keep the Register form clean: the old input/errors belong to the edit
    $_SESSION['old_now'] = [];
    $_SESSION['errors_now'] = [];
}
?>

<!-- Register Profile -->
<div class="modal fade register-modal" id="registerModal" tabindex="-1" aria-labelledby="registerModalTitle" aria-hidden="true"
     data-bs-backdrop="static"<?= $openRegister ? ' data-open-on-load="1"' : '' ?>>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <form class="modal-content" method="post" action="<?= url('/farmers') ?>"
              data-duplicate-check="<?= url('/farmers/check-duplicate') ?>" data-duplicate-key="register" novalidate>
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="registerModalTitle">Register Profile</h5>
                    <p class="register-modal-sub">Create one profile per person. Fields marked * are required.</p>
                </div>
                <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <span class="bar"></span><span class="bar"></span>
                </button>
            </div>
            <div class="modal-body">
                <?= Csrf::field() ?>
                <?= partial('farmers/_form', ['profile' => [], 'mode' => 'create', 'barangays' => $barangays]) ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
        </form>
    </div>
</div>

<!-- View Profile (body loaded from /farmers/{id}) -->
<div class="modal fade register-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="viewModalTitle" data-role="title">Profile</h5>
                    <p class="register-modal-sub">Profile details and linked records.</p>
                </div>
                <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <span class="bar"></span><span class="bar"></span>
                </button>
            </div>
            <div class="modal-body" data-role="body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-role="edit">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit profile
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile (body loaded from /farmers/{id}/edit, or rendered here after a failed save) -->
<div class="modal fade register-modal" id="editModal" tabindex="-1" aria-labelledby="editModalTitle" aria-hidden="true"
     data-bs-backdrop="static"<?= $editing ? ' data-open-on-load="1"' : '' ?>>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <form class="modal-content" method="post" action="<?= $editing ? e(url('/farmers/' . $editProfile['id'])) : '' ?>"
              data-duplicate-check="<?= url('/farmers/check-duplicate') ?>" data-duplicate-key="edit" novalidate>
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="editModalTitle">Edit Profile</h5>
                    <p class="register-modal-sub" data-role="title"><?= $editing ? e(Profile::fullName($editProfile)) : '' ?></p>
                </div>
                <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <span class="bar"></span><span class="bar"></span>
                </button>
            </div>
            <div class="modal-body" data-role="body"><?= $editBody ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</div>

<?php if ($canDelete): ?>
<!-- Delete confirmation (same style as the logout modal) -->
<div class="modal fade logout-modal" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalTitle">Delete Profile</h5>
                <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <span class="bar"></span><span class="bar"></span>
                </button>
            </div>
            <div class="modal-body">
                <span class="logout-modal-icon is-danger" aria-hidden="true"><i class="bi bi-trash3"></i></span>
                <p class="logout-modal-text">
                    Delete <strong data-role="name"></strong> from the registry? The profile is archived and
                    will no longer appear in lists.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-pill btn-pill-outline" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="" class="m-0" data-role="form">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-pill btn-pill-danger">
                        <i class="bi bi-trash3" aria-hidden="true"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= partial('duplicate-modal') ?>
<?php if (!empty($duplicatePayload)): ?>
<script>
window.__duplicatePayload = <?= json_encode($duplicatePayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__duplicateFor = <?= json_encode($duplicateFor ?? 'register') ?>;
</script>
<?php endif; ?>
