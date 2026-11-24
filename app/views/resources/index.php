<?php
$qs = array_filter($filters, fn ($v) => $v !== '');
$add = $canEdit ? '<a class="btn btn-primary btn-sm" href="' . e(url($c['path'] . '/create')) . '">+ Add ' . e($c['singular']) . '</a>' : '';

$cols = [];
foreach ($c['columns'] as $col) {
    if (!empty($col['link'])) {
        $key = $col['key'];
        $col['render'] = fn ($r) => '<a class="fw-semibold" href="' . e(url($c['path'] . '/' . $r['id'])) . '">' . e($r[$key]) . '</a>';
    }
    $cols[] = $col;
}
$cols[] = ['label' => '', 'class' => 'table-actions', 'render' => fn ($r) =>
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url($c['path'] . '/' . $r['id'])) . '">View</a>'
  . ($canEdit ? ' <a class="btn btn-outline-secondary btn-sm" href="' . e(url($c['path'] . '/' . $r['id'] . '/edit')) . '">Edit</a>' : '')];

$empty = $qs
    ? Ui::emptyState('No matching records', 'Try different filters.', '<a class="btn btn-outline-secondary btn-sm" href="' . e(url($c['path'])) . '">Clear filters</a>')
    : Ui::emptyState('No records yet', 'Added records will appear here.', $add);

$fields = [['type' => 'search', 'name' => 'q', 'placeholder' => 'Search']];
foreach ($c['filters'] as $name => $f) {
    $fields[] = ['name' => $name, 'all' => $f['label'], 'options' => $f['options']];
}
?>
<?= Ui::pageHead($c['title'], 'Agricultural resources recorded by the Municipal Agriculture Office.', $add) ?>

<?php if ($stats): ?>
<div class="card mb-3"><div class="card-body"><div class="row g-3">
    <?php foreach ($stats as $label => $value): ?>
        <div class="col-6 col-lg-3"><div class="stat-label"><?= e($label) ?></div><div class="stat-value"><?= e($value) ?></div></div>
    <?php endforeach; ?>
</div></div></div>
<?php endif; ?>

<?= Ui::filterBar($c['path'], $fields, $filters) ?>
<?= Ui::dataTable($cols, $result['data'], ['empty' => $empty]) ?>
<?= Ui::pagination($result, $c['path'], $qs) ?>
