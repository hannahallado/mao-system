<?= Ui::pageHead('Add Training/Activity', 'Add a training, seminar, or technical assistance activity.',
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/monitoring/training')) . '">Back to activities</a>') ?>

<form method="post" action="<?= e(url('/monitoring/training/create')) ?>" novalidate>
    <?= Csrf::field() ?>
    <?= Ui::section('Activity details',
        Ui::input('title', 'Activity title', [
            'required' => true,
            'maxlength' => 150,
            'col' => 'col-md-6',
        ])
      . Ui::select('type', 'Activity type', $activityTypes, [
            'required' => true,
            'col' => 'col-md-6',
            'placeholder' => 'Select activity type',
        ])
      . Ui::input('record_date', 'Date', [
            'type' => 'date',
            'required' => true,
            'col' => 'col-md-4',
        ])
      . Ui::input('venue', 'Venue', [
            'required' => true,
            'maxlength' => 150,
            'col' => 'col-md-4',
        ])
      . Ui::select('barangay_id', 'Barangay', $barangays, [
            'required' => true,
            'col' => 'col-md-4',
            'placeholder' => 'Select barangay',
        ])
      . Ui::input('topic', 'Topic', [
            'required' => true,
            'maxlength' => 500,
            'col' => 'col-md-8',
        ])
      . Ui::input('personnel', 'Personnel in charge', [
            'required' => true,
            'maxlength' => 120,
            'value' => $personnel,
            'col' => 'col-md-4',
        ])
    ) ?>

    <?php
    $selectedParticipantIds = old('participants', []);
    if (!is_array($selectedParticipantIds)) {
        $selectedParticipantIds = [];
    }
    $selectedParticipantIds = array_filter($selectedParticipantIds, static fn ($id) => is_string($id) || is_int($id));
    $selectedParticipantIds = array_map('strval', $selectedParticipantIds);
    ?>
    <section class="form-section" aria-labelledby="participants-title">
        <h2 class="form-section-title" id="participants-title">Participants</h2>
        <p class="text-muted small">Choose one or more participants from the D1 Farmer/Fisherfolk Registry. Hold Ctrl while selecting multiple profiles.</p>
        <label class="form-label" for="participants">D1 profiles <span class="text-danger">*</span></label>
        <select class="form-select<?= errors('participants') ? ' is-invalid' : '' ?>" id="participants" name="participants[]" multiple size="8" required aria-describedby="participants-help">
            <?php foreach ($participants as $id => $profile): ?>
                <option value="<?= e($id) ?>"<?= in_array((string) $id, $selectedParticipantIds, true) ? ' selected' : '' ?>>
                    <?= e($profile['name']) ?><?= $profile['barangay'] !== '' ? ' - ' . e($profile['barangay']) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text" id="participants-help">
            <?= $participants ? 'Selected profiles will be linked to this activity.' : 'No active D1 profiles are currently available.' ?>
        </div>
        <?php if (errors('participants')): ?>
            <div class="invalid-feedback d-block"><?= e(errors('participants')) ?></div>
        <?php endif; ?>
    </section>

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="<?= e(url('/monitoring/training')) ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Add activity</button>
    </div>
</form>
