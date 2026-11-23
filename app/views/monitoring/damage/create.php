<?= Ui::pageHead('Record Damage/Pest', 'Enter a sample damage or pest report for Pulupandan.',
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/monitoring/damage')) . '">Back to records</a>') ?>

<form method="post" action="<?= e(url('/monitoring/damage/create')) ?>" novalidate>
    <?= Csrf::field() ?>
    <?= Ui::section('Report details',
        Ui::select('farmer_farm', 'Affected farmer / farm', $farmReferences, [
            'required' => true,
            'col' => 'col-md-6',
            'placeholder' => 'Select a sample farmer / farm',
        ])
      . Ui::input('record_date', 'Report date', [
            'type' => 'date',
            'required' => true,
            'max' => date('Y-m-d'),
            'col' => 'col-md-6',
            'help' => 'Future dates are not accepted.',
        ])
      . Ui::select('record_type', 'Report type', $recordTypes, [
            'required' => true,
            'col' => 'col-md-4',
            'placeholder' => 'Select damage or pest',
        ])
      . Ui::select('commodity', 'Affected commodity', $commodities, [
            'required' => true,
            'col' => 'col-md-4',
            'placeholder' => 'Select a commodity',
        ])
      . Ui::input('area_ha', 'Affected area (hectares)', [
            'type' => 'number',
            'required' => true,
            'min' => '0.01',
            'step' => '0.01',
            'col' => 'col-md-4',
            'help' => 'Enter a number greater than zero.',
        ])
      . Ui::select('severity', 'Severity', $severities, [
            'required' => true,
            'col' => 'col-md-6',
            'placeholder' => 'Select severity',
        ])
      . Ui::input('calamity_name', 'Calamity name', [
            'col' => 'col-md-6',
            'maxlength' => 120,
            'placeholder' => 'Optional',
            'help' => 'Leave blank if the report is not related to a calamity.',
        ])
    ) ?>

    <?= Ui::section('Response and action',
        Ui::textarea('response_notes', 'Response / action notes', [
            'col' => 'col-12',
            'rows' => 4,
            'help' => 'Optional; up to 1,000 characters.',
        ])
    ) ?>

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="<?= e(url('/monitoring/damage')) ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Add report</button>
    </div>
</form>
