<?php $firstName = explode(' ', trim($user['full_name'] ?? ''))[0] ?? ''; ?>

<div class="page-head">
    <div>
        <h1><?= e($greeting) ?>, <?= e($firstName) ?></h1>
        <p class="page-sub"><?= e(date('l, F j, Y')) ?> · Choose a process to begin.</p>
    </div>
</div>

<h2 class="home-section-title">Processes</h2>

<div class="row g-3">
    <?php foreach ($tiles as [$tag, $label, $path, $functional, $icon, $desc]): ?>
        <div class="col-md-6 col-xl-4">
            <a href="<?= url($path) ?>" class="process-card">
                <span class="process-icon"><i class="bi bi-<?= e($icon) ?>" aria-hidden="true"></i></span>
                <span class="process-body">
                    <span class="process-head">
                        <span class="process-tag">PROCESS <?= e($tag) ?></span>
                        <span class="process-status <?= $functional ? 'is-ready' : 'is-mock' ?>">
                            <?= $functional ? 'Functional' : 'Mockup' ?>
                        </span>
                    </span>
                    <span class="process-title"><?= e($label) ?></span>
                    <span class="process-desc"><?= e($desc) ?></span>
                </span>
            </a>
        </div>
    <?php endforeach; ?>
</div>
