<?php
$u        = Auth::user();
$name     = $u['full_name'] ?? '';
$roleName = Auth::isAgriculturist() ? 'Municipal Agriculturist' : 'Agricultural Technologist';
$notifications = NotificationService::forCurrentUser();
$unread        = NotificationService::unreadCount();
$readsReady    = NotificationService::readsReady();
?>
<header class="app-header">
    <div class="app-header-left">
        <button type="button" class="sidebar-toggle" id="sidebarToggle"
                aria-label="Toggle navigation" aria-controls="appSidebar" aria-expanded="true">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <a href="<?= url('/home') ?>" class="app-brand">
            <span class="app-brand-text">
                <span class="app-brand-name"><?= e(config('app_name')) ?></span>
            </span>
        </a>
    </div>

    <div class="app-header-right">
        <!-- Notifications: registrations, assistance, programs/activities, reports and reminders -->
        <div class="dropdown">
            <button type="button" class="notif-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                    aria-label="Notifications<?= $unread ? ' (' . $unread . ' unread)' : '' ?>">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <?php if ($unread): ?><span class="notif-dot" aria-hidden="true"></span><?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end notif-menu">
                <div class="notif-head">
                    <span class="notif-head-title">Notifications</span>
                    <?php if ($unread): ?><span class="notif-unread"><?= $unread ?> Unread</span><?php endif; ?>
                    <?php if ($unread && $readsReady): ?>
                        <form method="post" action="<?= url('/notifications/read-all') ?>" class="ms-auto">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="back" value="<?= e(current_path() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
                            <button type="submit" class="notif-readall">Mark all as read</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!$notifications): ?>
                    <div class="notif-empty"><i class="bi bi-check2-circle" aria-hidden="true"></i> You're all caught up.</div>
                <?php else: ?>
                    <div class="notif-list">
                        <?php foreach ($notifications as $n): ?>
                            <a class="notif-item<?= $n['unread'] ? ' is-unread' : '' ?>" href="<?= e(url('/notifications/open?key=' . rawurlencode($n['key']))) ?>">
                                <span class="notif-icon cat-<?= e($n['category']) ?>"><i class="bi bi-<?= e($n['icon']) ?>" aria-hidden="true"></i></span>
                                <span class="notif-body">
                                    <span class="notif-title"><?= e($n['title']) ?></span>
                                    <span class="notif-text"><?= e($n['text']) ?></span>
                                    <span class="notif-meta">
                                        <span class="notif-date"><?= e(time_ago((string) $n['when'])) ?></span>
                                        <?php if ($n['tag']): ?><span class="notif-tag is-<?= e($n['tagTone']) ?>"><?= e($n['tag']) ?></span><?php endif; ?>
                                    </span>
                                </span>
                                <?php if ($n['unread']): ?><span class="notif-unread-dot" aria-label="Unread"></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <span class="header-divider" aria-hidden="true"></span>

        <!-- Profile: display only (log out is in the sidebar) -->
        <div class="profile" title="<?= e($name) ?>">
            <span class="profile-icon" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
            <span class="profile-copy">
                <span class="profile-name"><?= e($name) ?></span>
                <span class="profile-role"><?= e($roleName) ?></span>
            </span>
        </div>
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
