<?php
$backupAction = '<form method="post" action="' . e(url('/system/backup')) . '" onsubmit="return confirm(\'Create a new local database backup now?\')">'
    . Csrf::field()
    . '<button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-database-down me-1" aria-hidden="true"></i>Back up now</button>'
    . '</form>';
$backupColumns = [
    ['label' => 'Backup file', 'key' => 'filename'],
    ['label' => 'Created', 'key' => 'created_at'],
    ['label' => 'Size', 'key' => 'size'],
];
?>

<?= Ui::pageHead('Backup and Security', 'Create and review local database backups and security practices.', $backupAction) ?>

<section class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Backup history</h2>
        <p class="text-muted small">Successful SQL backups stored on this server. Backup files are not downloadable from the web application.</p>
        <?= Ui::dataTable($backupColumns, $backups, [
            'empty' => Ui::emptyState('No backups yet', 'Use “Back up now” to create the first local database backup.'),
        ]) ?>
    </div>
</section>

<section class="card">
    <div class="card-body">
        <h2 class="h5">Security and privacy</h2>
        <ul class="mb-0">
            <li>Access to this page and backup action is restricted to the Municipal Agriculturist role.</li>
            <li>Backups contain personal and operational records. Keep server and filesystem access limited to authorized personnel.</li>
            <li>Do not send backup files through email, personal storage, or other unapproved services. Follow LGU retention and secure off-site storage procedures.</li>
            <li>Use individual accounts, keep passwords private, and deactivate accounts when access is no longer required.</li>
            <li>Review role and sector assignments regularly, and test restoration using an approved recovery procedure.</li>
            <li>Backups are stored locally and are not encrypted by this application. Protect the host and storage media accordingly.</li>
        </ul>
    </div>
</section>
