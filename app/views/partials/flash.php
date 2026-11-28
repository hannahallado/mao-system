<?php foreach (pull_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?php
        $icon = match ($f['type']) {
            'success' => 'check-circle-fill',
            'danger' => 'exclamation-octagon-fill',
            'warning' => 'exclamation-triangle-fill',
            default => 'info-circle-fill',
        };
        ?>
        <i class="bi bi-<?= e($icon) ?> me-2" aria-hidden="true"></i>
        <span><?= e($f['message']) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endforeach; ?>
