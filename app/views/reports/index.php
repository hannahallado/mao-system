<?= Ui::pageHead('Reports') ?>

<div class="row g-3">
    <?php $tones = ['farmer-resource' => 'green', 'production-monitoring' => 'amber', 'programs-assistance' => 'purple', 'summary' => 'blue']; ?>
    <?php foreach ($groups as $gkey => $group): ?>
        <div class="col-lg-6">
            <div class="card h-100 report-group tone-<?= e($tones[$gkey] ?? 'green') ?>">
                <div class="card-header"><span class="card-title-icon"><span class="group-icon"><i class="bi bi-<?= e($group['icon'] ?? 'file-earmark-text') ?>" aria-hidden="true"></i></span> <?= e($group['title']) ?></span><span class="text-muted small fw-normal"><?= count($group['reports']) ?> reports</span></div>
                <div class="report-list">
                    <?php foreach ($group['reports'] as $slug => [$rtitle, $desc]): ?>
                        <a href="<?= url('/reports/' . $slug) ?>" class="report-row">
                            <span class="report-row-text">
                                <span class="report-row-title"><?= e($rtitle) ?></span>
                                <span class="report-row-desc"><?= e($desc) ?></span>
                            </span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
