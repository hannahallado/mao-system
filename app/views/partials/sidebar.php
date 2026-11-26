<?php
$menuDef    = menu();
// $menu_active (set by some controllers) is treated as the page's path, so it
// resolves to whichever sidebar link owns it
$activePath = active_menu_path($menuDef, $menu_active ?? null);
$shown      = [];
// Labels are wrapped in .nav-text so the collapsed (icon-only) sidebar can hide
// them; title="" gives a hover tooltip in that mode.
?>
<aside class="sidebar" id="appSidebar" aria-label="Main navigation">
    <nav class="sidebar-nav">
        <?php foreach ($menuDef as $entry): ?>
            <?php if (!empty($entry['roles']) && !Auth::hasRole($entry['roles'])) { continue; } ?>

            <?php if (!empty($entry['section']) && !in_array($entry['section'], $shown, true)): ?>
                <?php $shown[] = $entry['section']; ?>
                <div class="nav-label"><span class="nav-text"><?= e($entry['section']) ?></span></div>
            <?php endif; ?>

            <?php if ($entry['type'] === 'link'): ?>
                <a href="<?= url($entry['path']) ?>" title="<?= e($entry['label']) ?>"
                   class="nav-link-item <?= $activePath === $entry['path'] ? 'active' : '' ?>"
                   <?= $activePath === $entry['path'] ? 'aria-current="page"' : '' ?>>
                    <span class="nav-icon"><i class="bi bi-<?= e($entry['icon'] ?? 'circle') ?>" aria-hidden="true"></i></span>
                    <span class="nav-text"><?= e($entry['label']) ?></span>
                </a>
                <?php continue; ?>
            <?php endif; ?>

            <?php $open = in_array($activePath, array_column($entry['items'], 1), true); ?>
            <button class="nav-group-toggle <?= $open ? 'has-active' : '' ?>" type="button" title="<?= e($entry['label']) ?>"
                    data-bs-toggle="collapse" data-bs-target="#grp-<?= e($entry['id']) ?>"
                    aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="grp-<?= e($entry['id']) ?>">
                <span class="nav-icon"><i class="bi bi-<?= e($entry['icon'] ?? 'circle') ?>" aria-hidden="true"></i></span>
                <span class="nav-text"><?= e($entry['label']) ?></span>
                <span class="nav-chevron"></span>
            </button>
            <div class="collapse <?= $open ? 'show' : '' ?>" id="grp-<?= e($entry['id']) ?>">
                <div class="nav-sub">
                    <?php foreach ($entry['items'] as [$label, $path, $icon]): ?>
                        <a href="<?= url($path) ?>" title="<?= e($label) ?>"
                           class="nav-link-item <?= $activePath === $path ? 'active' : '' ?>"
                           <?= $activePath === $path ? 'aria-current="page"' : '' ?>>
                            <span class="nav-sub-icon"><i class="bi bi-<?= e($icon ?? 'circle') ?>" aria-hidden="true"></i></span>
                            <span class="nav-text"><?= e($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <button type="button" class="nav-link-item" title="Log out" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <span class="nav-icon"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></span>
            <span class="nav-text">Log out</span>
        </button>
    </div>
</aside>
