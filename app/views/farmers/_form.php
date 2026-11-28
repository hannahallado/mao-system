<?php
/** Shared by create.php and edit.php. Vars: $profile (stored values or []), $mode, $barangays */
$v = fn (string $k) => $profile[$k] ?? '';
?>
<?= Ui::section('Personal Information',
    Ui::input('last_name',   'Last name',   ['value' => $v('last_name'),   'required' => true, 'col' => 'col-md-4', 'maxlength' => 60])
  . Ui::input('first_name',  'First name',  ['value' => $v('first_name'),  'required' => true, 'col' => 'col-md-4', 'maxlength' => 60])
  . Ui::input('middle_name', 'Middle name', ['value' => $v('middle_name'), 'col' => 'col-md-3', 'maxlength' => 60])
  . Ui::select('suffix', 'Suffix', ['' => 'None', 'Jr.' => 'Jr.', 'Sr.' => 'Sr.', 'II' => 'II', 'III' => 'III', 'IV' => 'IV', 'V' => 'V'], ['value' => $v('suffix'), 'col' => 'col-md-1', 'placeholder' => 'None'])
  . Ui::select('sex', 'Sex', Profile::SEXES, ['value' => $v('sex'), 'required' => true, 'col' => 'col-md-4'])
  . Ui::input('birthdate',   'Birthdate',   ['value' => $v('birthdate'),   'type' => 'date', 'max' => date('Y-m-d'), 'col' => 'col-md-4',
        'help' => 'Helps tell people with the same name apart.'])
  . Ui::input('contact_no',  'Contact number', ['value' => $v('contact_no'), 'col' => 'col-md-4', 'inputmode' => 'tel', 'maxlength' => 20, 'placeholder' => '09XX XXX XXXX'])
) ?>

<?= Ui::section('Address and Classification',
    Ui::select('barangay_id', 'Barangay', $barangays, ['value' => $v('barangay_id'), 'required' => true, 'col' => 'col-md-4', 'placeholder' => 'Select barangay'])
  . Ui::input('address_detail', 'Purok / Sitio / Street', ['value' => $v('address_detail'), 'col' => 'col-md-4', 'maxlength' => 150])
  . Ui::select('livelihood_type', 'Livelihood type', Farmer::LIVELIHOODS, ['value' => $v('livelihood_type'), 'required' => true, 'col' => 'col-md-4', 'placeholder' => 'Select type'])
  . ($mode === 'edit'
      ? Ui::select('status', 'Profile Status', Profile::STATUSES, ['value' => $v('status'), 'required' => true, 'col' => 'col-md-4'])
      : '')
) ?>

<?= Ui::section('Registry Numbers',
    Ui::input('rsbsa_no', 'RSBSA number', ['value' => $v('rsbsa_no'), 'col' => 'col-md-4', 'maxlength' => 30, 'help' => 'Used for both farming and fishery. Leave blank if none.'])
  . Ui::input('fishr_no', 'FishR number', ['value' => $v('fishr_no'), 'col' => 'col-md-4', 'maxlength' => 30, 'help' => 'For fisherfolk only. Leave blank if none. Must be unique.'])
) ?>
