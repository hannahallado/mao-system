<?php
/** Tab bar shared by the Programs pages. Var: $active = 'programs' | 'distribution' | 'history' */
$tabs = [
    'programs'     => ['Programs', '/programs', 'clipboard-data'],
    'distribution' => ['Distribution', '/assistance', 'box-seam'],
    'history'      => ['History', '/assistance/history', 'clock-history'],
];
?>
<ul class="nav nav-tabs module-tabs">
    <?php foreach ($tabs as $key => [$label, $path, $icon]): ?>
        <li class="nav-item">
            <a class="nav-link <?= $key === $active ? 'active' : '' ?>" href="<?= e(url($path)) ?>" <?= $key === $active ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-<?= e($icon) ?>" aria-hidden="true"></i> <?= e($label) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
