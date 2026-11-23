<?= Ui::pageHead('Record Production', 'Enter a sample production record for Pulupandan.',
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/monitoring/production')) . '">Back to production list</a>') ?>

<form method="post" action="<?= e(url('/monitoring/production/create')) ?>" novalidate>
    <?= Csrf::field() ?>
    <?= Ui::section('Production details',
        Ui::select('farmer_farm', 'Farmer / farm reference', $references, [
            'required' => true,
            'col' => 'col-md-6',
            'placeholder' => 'Select a sample farmer / farm',
        ])
      . Ui::select('commodity', 'Commodity', $commodities, [
            'required' => true,
            'col' => 'col-md-6',
            'placeholder' => 'Select a commodity',
        ])
      . Ui::select('period', 'Production period', $periods, [
            'required' => true,
            'col' => 'col-md-4',
            'placeholder' => 'Select 1st or 2nd half',
        ])
      . Ui::input('record_date', 'Production date', [
            'type' => 'date',
            'required' => true,
            'max' => date('Y-m-d'),
            'col' => 'col-md-4',
            'help' => 'Future dates are not accepted.',
        ])
      . Ui::input('area_ha', 'Area (hectares)', [
            'type' => 'number',
            'required' => true,
            'min' => '0.01',
            'step' => '0.01',
            'col' => 'col-md-4',
            'help' => 'Enter a number greater than zero.',
        ])
      . Ui::input('volume', 'Production volume', [
            'type' => 'number',
            'required' => true,
            'min' => '0.01',
            'step' => '0.01',
            'col' => 'col-md-6',
            'help' => 'Enter a number greater than zero.',
        ])
      . Ui::select('unit', 'Volume unit', $units, [
            'required' => true,
            'col' => 'col-md-6',
            'placeholder' => 'Select a unit',
        ])
    ) ?>

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="<?= e(url('/monitoring/production')) ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Add record</button>
    </div>
</form>
