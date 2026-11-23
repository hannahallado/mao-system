<?php
$u        = Auth::user();
$name     = $u['full_name'] ?? '';
$roleName = Auth::isAgriculturist() ? 'Municipal Agriculturist' : 'Agricultural Technologist';
?>
<header class="app-header">
    <div class="app-header-left">
        <button type="button" class="sidebar-toggle" id="sidebarToggle"
                aria-label="Toggle navigation" aria-controls="appSidebar" aria-expanded="true">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <a href="<?= url('/home') ?>" class="app-brand">
            <img src="<?= asset('img/logo.png') ?>" alt="" class="app-brand-logo" width="36" height="36">
            <span class="app-brand-text">
                <span class="app-brand-name"><?= e(config('app_name')) ?></span>
                <span class="app-brand-sub">Information Management System</span>
            </span>
        </a>
    </div>

    <!-- Profile: display only (log out is in the sidebar) -->
    <div class="profile" title="<?= e($name) ?>">
        <span class="profile-role"><?= e($roleName) ?></span>
        <span class="profile-icon" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
    </div>
</header>

<!-- Logout confirmation (opened from the sidebar) -->
<div class="modal fade logout-modal" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalTitle">Log Out</h5>
                <!-- Same X as the landing page login modal -->
                <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>
            </div>
            <div class="modal-body">
                <span class="logout-modal-icon" aria-hidden="true"><i class="bi bi-box-arrow-right"></i></span>
                <p class="logout-modal-text">Are you sure you want to log out of the <?= e(config('app_name')) ?> system?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-pill btn-pill-outline" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="<?= url('/logout') ?>" class="m-0">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-pill btn-pill-primary">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
