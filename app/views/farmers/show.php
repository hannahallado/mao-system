<?php
/** Full profile page (direct links, e.g. from duplicate warnings or programs). The Registry list shows the same details in a modal. */
$id = (int) $profile['id'];
?>
<?= Ui::pageHead(Profile::fullName($profile),
    'registered ' . format_date($profile['created_at']),
    Ui::statusBadge($profile['status'], Profile::STATUSES[$profile['status']] ?? null)
    . '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/farmers')) . '">Back to list</a>'
    . '<a class="btn btn-primary btn-sm" href="' . e(url('/farmers?edit=' . $id)) . '">Edit profile</a>') ?>

<?= partial('farmers/_details', ['profile' => $profile]) ?>
