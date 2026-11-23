<?php
$u        = Auth::user();
$name     = $u['full_name'] ?? '';
$initials = strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2))));
$roleName = Auth::isAgriculturist() ? 'Municipal Agriculturist' : 'MAO Technician';
?>
<header class="app-header">
    <div class="user-chip">
        <span class="avatar"><?= e($initials) ?></span>
        <div>
            <div class="user-name"><?= e($name) ?></div>
            <div class="user-sub"><?= e($roleName) ?></div>
        </div>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal">
        Log out
    </button>
</header>

<!-- Logout confirmation -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalTitle">Log out?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                You will need to log in again to access the system.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="<?= url('/logout') ?>" class="m-0">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-primary">Log out</button>
                </form>
            </div>
        </div>
    </div>
</div>
