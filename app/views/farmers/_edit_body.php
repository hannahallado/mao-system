<?php
/** Edit form fields for the Edit modal on the Registry list. Vars: $profile, $barangays */
?>
<?= Csrf::field() ?>
<input type="hidden" name="exclude_id" value="<?= e($profile['id']) ?>">
<?= partial('farmers/_form', ['profile' => $profile, 'mode' => 'edit', 'barangays' => $barangays]) ?>
