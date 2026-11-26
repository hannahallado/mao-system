<?php
/**
 * Tabbed record module page (Resources, Monitoring). One tab per record type,
 * Add / View / Edit / Delete in modals. Vars from ModuleController::index().
 */
$path = $module['path'];
$tabUrl = fn (string $key) => url($path . '?tab=' . $key);
$isOverview = $tabKey === 'overview';
$schemaHint = 'Run <code>database/schema_modules.sql</code> on your database (phpMyAdmin &gt; SQL) to set up this section.';

$addBtn = '';
if (!$isOverview && $ready && $canWrite) {
    $addBtn = '<button type="button" class="btn btn-primary btn-sm" data-module-action="add">'
            . '<i class="bi bi-plus-lg" aria-hidden="true"></i> Add ' . e($tab['singular']) . '</button>';
}
?>
<?= Ui::pageHead($module['title'], null, $addBtn) ?>

<ul class="nav nav-tabs module-tabs">
    <?php if (!empty($module['overview'])): ?>
        <li class="nav-item"><a class="nav-link <?= $isOverview ? 'active' : '' ?>" href="<?= e($tabUrl('overview')) ?>"
            <?= $isOverview ? 'aria-current="page"' : '' ?>><i class="bi bi-grid-1x2" aria-hidden="true"></i> Overview</a></li>
    <?php endif; ?>
    <?php foreach ($module['tabs'] as $key => $t): ?>
        <li class="nav-item"><a class="nav-link <?= $key === $tabKey ? 'active' : '' ?>" href="<?= e($tabUrl($key)) ?>"
            <?= $key === $tabKey ? 'aria-current="page"' : '' ?>><i class="bi bi-<?= e($t['icon']) ?>" aria-hidden="true"></i> <?= e($t['label']) ?>
            <?php if (($tabCounts[$key] ?? null) !== null): ?><span class="tab-count"><?= number_format($tabCounts[$key]) ?></span><?php endif; ?></a></li>
    <?php endforeach; ?>
</ul>

<?php if ($isOverview): ?>
    <?php // ----------------------------------------------------------------- Overview ?>
    <?php if ($overview['missing']): ?>
        <div class="alert alert-warning"><?= $schemaHint ?></div>
    <?php endif; ?>
    <div class="row g-3 mb-4">
        <?php foreach ($overview['cards'] as $c): ?>
            <div class="col-md-4">
                <a class="stat-card tone-<?= e($c['tone']) ?>" href="<?= e($tabUrl($c['key'])) ?>">
                    <span class="stat-card-icon"><i class="bi bi-<?= e($c['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="stat-card-body">
                        <span class="stat-card-label"><?= e($c['label']) ?></span>
                        <span class="stat-card-value"><?= number_format($c['count']) ?> <small><?= $c['count'] === 1 ? 'record' : 'records' ?></small></span>
                        <span class="stat-card-note"><?= e($c['note']) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header">Recent monitoring entries</div>
        <?php if (!$overview['recent']): ?>
            <?= Ui::emptyState('No monitoring entries yet', 'Production, damage and training records will be listed here as they are added.') ?>
        <?php else: ?>
            <div class="table-scroll">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Entry</th><th>Barangay</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($overview['recent'] as $r): ?>
                        <tr>
                            <td class="text-nowrap"><?= e(format_date($r['d'])) ?></td>
                            <td><?= e($r['title']) ?></td>
                            <td><?= e($r['barangay'] ?? '—') ?></td>
                            <td class="table-actions"><a class="btn btn-outline-secondary btn-sm" href="<?= e($tabUrl($r['tab'])) ?>">Open list</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php elseif (!$ready): ?>
    <div class="card"><?= Ui::emptyState($tab['label'] . ' is not set up yet', 'The database table for this section does not exist yet.') ?>
        <p class="text-center text-muted small pb-4 mb-0"><?= $schemaHint ?></p></div>

<?php else: ?>
    <?php
    // ----------------------------------------------------------------- Record list
    $fields = $tab['fields'];
    $filterFields = [['type' => 'search', 'name' => 'q', 'placeholder' => 'Search ' . strtolower($tab['label'])]];
    foreach ($tab['filters'] ?? [] as $fname) {
        $filterFields[] = ['name' => $fname, 'all' => 'All ' . plural(strtolower($fields[$fname]['label'])),
                           'options' => RecordModule::options($fields[$fname])];
    }
    $hasFilters = array_filter($filters, fn ($v) => $v !== '') !== [];

    $columns = [];
    foreach ($tab['list'] as $col) {
        if (str_starts_with($col, 'computed:')) {
            $ckey = substr($col, 9);
            $columns[] = ['label' => RecordModule::computedLabel($ckey), 'render' => fn ($r) => RecordModule::computed($ckey, $r, $tab)[1]];
            continue;
        }
        $f = $fields[$col];
        $numeric = in_array($f['type'], ['number', 'integer'], true);
        $columns[] = [
            'label'  => $f['label'],
            'class'  => $numeric ? 'num' : '',
            'render' => function ($r) use ($f, $col) {
                $text = RecordModule::display($f, $col, $r);
                return $text === '' ? '<span class="text-muted">—</span>' : e($text);
            },
        ];
    }
    // First column opens the View modal
    $firstRender = $columns[0]['render'];
    $columns[0]['render'] = fn ($r) => '<button type="button" class="link-button fw-semibold" data-module-action="view" data-id="' . (int) $r['id'] . '">'
        . $firstRender($r) . '</button>';

    $columns[] = ['label' => 'Actions', 'class' => 'table-actions', 'render' => function ($r) use ($canWrite, $canDelete) {
        $id = (int) $r['id'];
        $html = '<div class="row-actions">'
            . '<button type="button" class="btn-icon-action" title="View" aria-label="View" data-module-action="view" data-id="' . $id . '"><i class="bi bi-eye" aria-hidden="true"></i></button>';
        if ($canWrite) {
            $html .= '<button type="button" class="btn-icon-action" title="Edit" aria-label="Edit" data-module-action="edit" data-id="' . $id . '"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>';
        }
        if ($canDelete) {
            $html .= '<button type="button" class="btn-icon-action is-danger" title="Delete" aria-label="Delete" data-module-action="delete" data-id="' . $id . '"><i class="bi bi-trash3" aria-hidden="true"></i></button>';
        }
        return $html . '</div>';
    }];

    // Data for the modals: raw values (edit), readable pairs (view), a name (delete)
    $records = [];
    foreach ($result['data'] as $r) {
        $records[(int) $r['id']] = [
            'values' => RecordModule::formValues($tab, $r),
            'view'   => RecordModule::viewPairs($tab, $r),
            'name'   => html_entity_decode(strip_tags($firstRender($r)), ENT_QUOTES, 'UTF-8'),
        ];
    }
    if ($editRow && !isset($records[(int) $editRow['id']])) {
        $records[(int) $editRow['id']] = ['values' => RecordModule::formValues($tab, $editRow), 'view' => [], 'name' => ''];
    }

    $empty = $hasFilters
        ? Ui::emptyState('No matching ' . strtolower($tab['label']), 'Try a different search, or clear the filters.',
            '<a class="btn btn-outline-secondary btn-sm" href="' . e($tabUrl($tabKey)) . '">Clear filters</a>')
        : Ui::emptyState('No ' . strtolower($tab['label']) . ' records yet', 'Records you add will be listed here.', $addBtn);

    // GET forms drop the action's query string, so ?tab= also goes in a hidden field;
    // keeping it in the action makes the "Clear" link stay on this tab
    $filterAction = $path . '?tab=' . $tabKey;
    ?>
    <?php if (!$canWrite): ?>
        <div class="alert alert-info small py-2">You can view <?= e(strtolower($tab['label'])) ?> records. Adding and editing is limited to the
            <?= e(['crops' => 'Crops', 'livestock_poultry' => 'Livestock and Poultry', 'fisheries' => 'Fisheries'][$tab['sector']] ?? '') ?> sector.</div>
    <?php endif; ?>

    <?= str_replace('</form>', '<input type="hidden" name="tab" value="' . e($tabKey) . '"></form>',
        Ui::filterBar($filterAction, $filterFields, $filters)) ?>

    <?= Ui::dataTable($columns, $result['data'], ['empty' => $empty]) ?>
    <?= Ui::pagination($result, $path, array_filter($filters, fn ($v) => $v !== '') + ['tab' => $tabKey]) ?>

    <?php
    // ----------------------------------------------------------------- Form modal (add + edit)
    $editing   = $editRow !== null;
    $formUrl   = url($path . '/' . $tabKey . ($editing ? '/' . (int) $editRow['id'] : ''));
    $fieldHtml = '';
    foreach ($fields as $name => $f) {
        if (isset($tab['sections'][$name])) {
            $fieldHtml .= '<div class="col-12 form-subhead">' . e($tab['sections'][$name]) . '</div>';
        }
        $col = $f['col'] ?? 'col-md-6';
        $req = !empty($f['required']);
        $value = $editing ? RecordModule::formValues($tab, $editRow)[$name] : ($f['default'] ?? '');
        switch ($f['type']) {
            case 'textarea':
                $fieldHtml .= Ui::textarea($name, $f['label'], ['value' => $value, 'required' => $req, 'col' => $col, 'help' => $f['help'] ?? null, 'rows' => 2]);
                break;
            case 'select':
            case 'barangay':
            case 'profile':
            case 'farm':
            case 'organization':
                $fieldHtml .= Ui::select($name, $f['label'], RecordModule::options($f), [
                    'value' => $value, 'required' => $req, 'col' => $col, 'help' => $f['help'] ?? null,
                    'placeholder' => $req ? 'Select…' : '— None —']);
                break;
            case 'members':
                $selected = array_map('strval', (array) old($name, $value ?: []));
                $err = errors($name);
                $opts = '';
                foreach (RecordModule::options($f) as $oid => $olabel) {
                    $opts .= '<option value="' . e($oid) . '"' . (in_array((string) $oid, $selected, true) ? ' selected' : '') . '>' . e($olabel) . '</option>';
                }
                $fieldHtml .= '<div class="' . e($col) . '"><label class="form-label" for="f_' . e($name) . '">' . e($f['label']) . '</label>'
                    . '<select multiple size="6" class="form-select' . ($err ? ' is-invalid' : '') . '" id="f_' . e($name) . '" name="' . e($name) . '[]">' . $opts . '</select>'
                    . '<div class="invalid-feedback">' . e($err) . '</div>'
                    . (!empty($f['help']) ? '<div class="form-text">' . e($f['help']) . '</div>' : '') . '</div>';
                break;
            default:
                $type = ['number' => 'number', 'integer' => 'number', 'date' => 'date', 'month' => 'month'][$f['type']] ?? 'text';
                $o = ['value' => $value, 'required' => $req, 'col' => $col, 'help' => $f['help'] ?? null, 'type' => $type];
                if ($f['type'] === 'number') { $o['step'] = $f['step'] ?? 'any'; $o['inputmode'] = 'decimal'; }
                if ($f['type'] === 'integer') { $o['step'] = '1'; $o['inputmode'] = 'numeric'; }
                if (in_array($f['type'], ['number', 'integer'], true)) {
                    $o['min'] = $f['min'] ?? 0;
                    if (isset($f['max_value'])) { $o['max'] = $f['max_value']; }
                }
                if (!empty($f['max'])) { $o['maxlength'] = $f['max']; }
                if (!empty($f['not_future'])) { $o['max'] = date('Y-m-d'); }
                $fieldHtml .= Ui::input($name, $f['label'], $o);
        }
    }
    ?>
    <div class="modal fade register-modal" id="recordFormModal" tabindex="-1" aria-labelledby="recordFormTitle" aria-hidden="true"
         data-bs-backdrop="static" <?= $openForm ? 'data-open-on-load="1"' : '' ?>
         data-store-url="<?= e(url($path . '/' . $tabKey)) ?>" data-update-base="<?= e(url($path . '/' . $tabKey . '/')) ?>"
         data-singular="<?= e($tab['singular']) ?>" data-editing-id="<?= $editing ? (int) $editRow['id'] : '' ?>">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <form class="modal-content" method="post" action="<?= e($formUrl) ?>" novalidate>
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="recordFormTitle"><?= $editing ? 'Edit ' : 'Add ' ?><?= e($tab['singular']) ?></h5>
                        <p class="register-modal-sub">Fields marked * are required.</p>
                    </div>
                    <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close"><span class="bar"></span><span class="bar"></span></button>
                </div>
                <div class="modal-body">
                    <?= Csrf::field() ?>
                    <div class="row g-3"><?= $fieldHtml ?></div>
                    <?php if (!empty($tab['yield_preview'])): ?>
                        <div class="yield-preview" data-yield-preview hidden>
                            <i class="bi bi-calculator" aria-hidden="true"></i> Yield: <strong data-yield-value></strong>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-role="submit"><?= $editing ? 'Save changes' : 'Save' ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- View -->
    <div class="modal fade register-modal" id="recordViewModal" tabindex="-1" aria-labelledby="recordViewTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="recordViewTitle" data-role="title"></h5>
                        <p class="register-modal-sub"><?= e($tab['singular']) ?> details</p>
                    </div>
                    <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close"><span class="bar"></span><span class="bar"></span></button>
                </div>
                <div class="modal-body"><dl class="detail-list" data-role="body"></dl></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if ($canWrite): ?>
                        <button type="button" class="btn btn-primary" data-role="edit"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canDelete): ?>
    <!-- Delete -->
    <div class="modal fade logout-modal" id="recordDeleteModal" tabindex="-1" aria-labelledby="recordDeleteTitle" aria-hidden="true"
         data-delete-base="<?= e(url($path . '/' . $tabKey . '/')) ?>">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recordDeleteTitle">Delete <?= e($tab['singular']) ?></h5>
                    <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close"><span class="bar"></span><span class="bar"></span></button>
                </div>
                <div class="modal-body">
                    <span class="logout-modal-icon is-danger" aria-hidden="true"><i class="bi bi-trash3"></i></span>
                    <p class="logout-modal-text">Delete <strong data-role="name"></strong>? The record is archived and will no longer appear in lists or reports.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-pill btn-pill-outline" data-bs-dismiss="modal">Cancel</button>
                    <form method="post" action="" class="m-0" data-role="form">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-pill btn-pill-danger"><i class="bi bi-trash3" aria-hidden="true"></i> Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script id="moduleRecords" type="application/json"><?= json_encode($records, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>
