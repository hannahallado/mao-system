<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e($title) ?> | <?= e(config('app_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap (grid + modal/collapse JS). Copy to public/assets/vendor for offline demos. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php if (in_array($title ?? '', ['Map Overview', 'Farm Locations', 'Fishery Locations'], true)): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <?php endif; ?>
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<?php
// Play the entry animation once, on the first page after logging in
$pageEnter = !empty($_SESSION['just_logged_in']);
unset($_SESSION['just_logged_in']);
?>
<body class="page-anim<?= $pageEnter ? ' page-enter' : '' ?>"<?= $pageEnter ? ' data-fresh-login="1"' : '' ?>
      data-logout-url="<?= e(url('/logout')) ?>">
<script>
    // Restore a collapsed sidebar before the first paint (no flash)
    try {
        if (localStorage.getItem('mao_sidebar') === 'collapsed') {
            document.body.classList.add('sidebar-collapsed');
        }
    } catch (e) {}
</script>
<div class="app">
    <?php require APP_PATH . '/views/partials/topbar.php'; ?>
    <div class="app-body">
        <?php require APP_PATH . '/views/partials/sidebar.php'; ?>
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <div class="app-main">
            <main class="app-content">
                <?php require APP_PATH . '/views/partials/breadcrumbs.php'; ?>
                <?php require APP_PATH . '/views/partials/flash.php'; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/session-guard.js') ?>"></script>
<?php foreach (($scripts ?? []) as $script): ?>
<script src="<?= asset($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
