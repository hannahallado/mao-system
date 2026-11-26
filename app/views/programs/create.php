<?= Ui::pageHead('Create Program', null,
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/programs')) . '">Back to list</a>') ?>

<form method="post" action="<?= url('/programs') ?>" novalidate>
    <?= Csrf::field() ?>
    <?= partial('programs/_form', ['program' => $program, 'assistanceTypes' => $assistanceTypes]) ?>
    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="<?= url('/programs') ?>">Cancel</a>
        <button class="btn btn-primary" type="submit">Create program</button>
    </div>
</form>
