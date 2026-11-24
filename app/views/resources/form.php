<?php
$action = $mode === 'create' ? url($c['path']) : url($c['path'] . '/' . $values['id']);
$back   = $mode === 'create' ? url($c['path']) : url($c['path'] . '/' . $values['id']);
$sections = [];
$current = '';
foreach ($c['fields'] as $f) {
    $current = $f['section'] ?? $current;
    $n = $f['name'];
    $o = ['value' => $values[$n] ?? '', 'col' => $f['col'] ?? 'col-md-6', 'required' => str_contains($f['rules'], 'required')];
    foreach (['placeholder', 'help', 'maxlength', 'min', 'max', 'step', 'inputmode', 'rows'] as $k) {
        if (isset($f[$k])) { $o[$k] = $f[$k]; }
    }
    $type = $f['type'] ?? 'text';
    if ($type === 'select') {
        $html = Ui::select($n, $f['label'], $f['options'], $o);
    } elseif ($type === 'textarea') {
        $html = Ui::textarea($n, $f['label'], $o);
    } else {
        $html = Ui::input($n, $f['label'], $o + ['type' => $type]);
    }
    $sections[$current] = ($sections[$current] ?? '') . $html;
}
?>
<?= Ui::pageHead($title, 'Fields marked * are required.', '<a class="btn btn-outline-secondary btn-sm" href="' . e($back) . '">Back</a>') ?>
<form method="post" action="<?= e($action) ?>" novalidate>
    <?= Csrf::field() ?>
    <?php foreach ($sections as $name => $html): ?>
        <?= Ui::section($name, $html) ?>
    <?php endforeach; ?>
    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="<?= e($back) ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><?= $mode === 'create' ? 'Save' : 'Save changes' ?></button>
    </div>
</form>
