<div class="page-head">
    <div>
        <h1>System Settings</h1>
        <p class="page-sub">Configure office identity, report headers, reporting dates, and production seasons.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/system/settings')) ?>">
    <?= Csrf::field() ?>
    <section class="form-section">
        <h2 class="form-section-title">Office identity and report header</h2>
        <div class="row g-3">
            <?= Ui::input('office_name', 'Office name', [
                'value' => $settings['office_name'],
                'required' => true,
                'maxlength' => 120,
                'col' => 'col-md-6',
            ]) ?>
            <?= Ui::input('municipality', 'Municipality', [
                'value' => $settings['municipality'],
                'required' => true,
                'maxlength' => 120,
                'col' => 'col-md-6',
            ]) ?>
            <?= Ui::input('report_header', 'Report header line', [
                'value' => $settings['report_header'],
                'required' => true,
                'maxlength' => 200,
                'col' => 'col-12',
                'help' => 'Additional text shown below the office name and municipality on generated reports.',
            ]) ?>
        </div>
    </section>

    <section class="form-section">
        <h2 class="form-section-title">Annual reporting period</h2>
        <p class="text-muted small">Set the date range displayed in report headers.</p>
        <div class="row g-3">
            <?= Ui::input('reporting_start', 'Start date', [
                'type' => 'date',
                'value' => $settings['reporting_start'],
                'required' => true,
                'col' => 'col-md-6',
            ]) ?>
            <?= Ui::input('reporting_end', 'End date', [
                'type' => 'date',
                'value' => $settings['reporting_end'],
                'required' => true,
                'col' => 'col-md-6',
            ]) ?>
        </div>
    </section>

    <section class="form-section">
        <h2 class="form-section-title">Production seasons</h2>
        <p class="text-muted small">These labels appear in the production entry form.</p>
        <div class="row g-3">
            <?= Ui::input('wet_season_label', 'Wet season label', [
                'value' => $settings['wet_season_label'],
                'required' => true,
                'maxlength' => 60,
                'col' => 'col-md-6',
            ]) ?>
            <?= Ui::input('dry_season_label', 'Dry season label', [
                'value' => $settings['dry_season_label'],
                'required' => true,
                'maxlength' => 60,
                'col' => 'col-md-6',
            ]) ?>
        </div>
    </section>

    <div class="d-flex justify-content-end">
        <button class="btn btn-primary" type="submit">Save settings</button>
    </div>
</form>
