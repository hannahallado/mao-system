<?php
$sel = fn (string $name, string $fallback = '') => (string) old($name, $fallback);
$today = date('Y-m-d');
$beneficiarySelected = (string) old('program_beneficiary_id', $beneficiaryId);
$programSelected = (string) old('program_id', $programId);
?>
<?= Ui::pageHead('Record Distribution', 'Log assistance given to a beneficiary. Similar earlier assistance triggers a warning.',
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/assistance')) . '">Back to list</a>') ?>

<form method="post" action="<?= url('/assistance') ?>" data-duplicate-check="<?= url('/assistance/check-duplicate') ?>" novalidate>
    <?= Csrf::field() ?>

    <?= Ui::section('Who received what',
        Ui::select('program_id', 'Program', $programs, ['value' => $programId, 'required' => true, 'col' => 'col-md-6', 'placeholder' => 'Select program'])
      . '<div class="col-md-6"><label class="form-label" for="f_program_beneficiary_id">Beneficiary <span class="text-danger">*</span></label>'
      . '<select class="form-select' . (errors('program_beneficiary_id') ? ' is-invalid' : '') . '" id="f_program_beneficiary_id" name="program_beneficiary_id"'
      . ' data-options-url="' . e(url('/programs/__ID__/beneficiaries/options')) . '" data-selected="' . e($beneficiarySelected) . '">'
      . '<option value="">' . ($programSelected === '' ? 'Select a program first' : 'Select beneficiary...') . '</option>'
      . implode('', array_map(fn ($bid, $label) => '<option value="' . e($bid) . '"' . ((string) $bid === $beneficiarySelected ? ' selected' : '') . '>' . e($label) . '</option>', array_keys($beneficiaries), $beneficiaries))
      . '</select>'
      . (errors('program_beneficiary_id') ? '<div class="invalid-feedback d-block">' . e(errors('program_beneficiary_id')) . '</div>' : '<div class="form-text">Only people on the program\'s beneficiary list appear here.</div>')
      . '</div>'
      . Ui::select('assistance_type_id', 'Assistance type', $types, ['value' => '', 'required' => true, 'col' => 'col-md-4', 'placeholder' => 'Select type'])
      . Ui::input('quantity', 'Quantity', ['required' => true, 'col' => 'col-md-3', 'inputmode' => 'decimal', 'placeholder' => '0'])
      . '<div class="col-md-2"><label class="form-label" for="f_unit">Unit</label>'
      . '<select class="form-select" id="f_unit" name="unit">'
      . '<option value="">Select unit...</option>'
      . implode('', array_map(fn ($u) => '<option value="' . e($u) . '"' . ($sel('unit') === $u ? ' selected' : '') . '>' . e($u) . '</option>', array_unique(array_values($typeUnits))))
      . '</select></div>'
      . Ui::input('date_distributed', 'Date distributed', ['value' => $today, 'type' => 'date', 'max' => $today, 'required' => true, 'col' => 'col-md-3'])
    ) ?>

    <?= Ui::section('Release details',
        Ui::input('released_by', 'Releasing personnel', ['value' => Auth::user()['full_name'] ?? '', 'col' => 'col-md-4', 'maxlength' => 120])
      . Ui::input('ack_receipt_no', 'Acknowledgment receipt no.', ['col' => 'col-md-4', 'maxlength' => 40, 'help' => 'Optional. From the signed receipt or distribution sheet.'])
      . Ui::input('unit_value', 'Value per unit (PHP)', ['col' => 'col-md-4', 'inputmode' => 'decimal', 'placeholder' => '0.00', 'help' => 'Optional. Used to compute the total value.'])
    ) ?>

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="<?= url('/assistance') ?>">Cancel</a>
        <button class="btn btn-primary" type="submit">Save distribution</button>
    </div>
</form>

<?= partial('duplicate-modal') ?>
<script>window.__assistanceTypes = <?= json_encode($typeUnits, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php if (!empty($duplicatePayload)): ?>
<script>window.__duplicatePayload = <?= json_encode($duplicatePayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<?php endif; ?>
