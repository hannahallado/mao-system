<?= Ui::pageHead('Edit Program', $program['name'] . ' · ' . $program['program_no'],
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/programs/' . $program['id'])) . '">Back to program</a>') ?>

<form method="post" action="<?= url('/programs/' . $program['id']) ?>" novalidate>
    <?= Csrf::field() ?>
    <?= partial('programs/_form', ['program' => $program, 'assistanceTypes' => $assistanceTypes]) ?>
    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="<?= url('/programs/' . $program['id']) ?>">Cancel</a>
        <button class="btn btn-primary" type="submit">Save changes</button>
        <?php if (Auth::isAgriculturist()): ?>
            <form method="post" action="<?= url('/programs/' . $program['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Remove <?= e($program['name']) ?>? This cannot be undone.')">
                <input type="hidden" name="_csrf" value="<?= e(Csrf::token()) ?>">
                <button type="submit" class="btn btn-outline-danger">Remove</button>
            </form>
        <?php endif; ?>
    </div>
</form>
