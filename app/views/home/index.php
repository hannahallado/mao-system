<?php
$damageOpts = RecordModule::module('monitoring')['tabs']['damage']['fields'];
$fmtValue = function (array $c): string {
    $v = $c['value'];
    $text = !empty($c['money']) ? number_format((float) $v, 2) : (is_float($v) ? rtrim(rtrim(number_format($v, 2), '0'), '.') : number_format($v));
    return (!empty($c['prefix']) ? '<small>' . e($c['prefix']) . '</small> ' : '') . e($text) . (!empty($c['suffix']) ? ' <small>' . e($c['suffix']) . '</small>' : '');
};
?>
<div class="page-head">
    <div>
        <h1>Dashboard</h1>
    </div>
    <span class="date-chip"><i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(date('l, F j, Y')) ?></span>
</div>

<?php if ($setupNeeded && $full): ?>
    <div class="alert alert-warning small">Resources and Monitoring are not set up yet, so their figures are hidden.
        Run <code>database/schema_modules.sql</code> on your database (phpMyAdmin &gt; SQL).</div>
<?php endif; ?>

<!-- Auto-fit grid: as many cards per row as fit, so the row count adapts to
     the sidebar state and screen width without an uneven last row -->
<div class="stat-grid mb-4">
    <?php foreach ($cards as $c): ?>
        <div>
            <a class="stat-card tone-<?= e($c['tone'] ?? 'green') ?>" href="<?= e(url($c['url'])) ?>">
                <span class="stat-card-icon"><i class="bi bi-<?= e($c['icon']) ?>" aria-hidden="true"></i></span>
                <span class="stat-card-body">
                    <span class="stat-card-label"><?= e($c['label']) ?></span>
                    <span class="stat-card-value"><?= $fmtValue($c) ?></span>
                    <span class="stat-card-note"><?= e($c['note']) ?></span>
                </span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($charts): ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-<?= isset($charts['commodity']) ? '7' : '12' ?>">
            <div class="card h-100">
                <div class="card-header"><span class="card-title-icon"><i class="bi bi-bar-chart-fill" aria-hidden="true"></i> Profiles by barangay</span> <span class="text-muted small fw-normal">top 10</span></div>
                <div class="card-body"><div class="chart-box"><canvas id="chartBarangay" aria-label="Profiles by barangay" role="img"></canvas></div></div>
            </div>
        </div>
        <?php if (isset($charts['commodity'])): ?>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header"><span class="card-title-icon"><i class="bi bi-pie-chart-fill" aria-hidden="true"></i> Farm area by commodity</span> <span class="text-muted small fw-normal">hectares</span></div>
                    <div class="card-body">
                        <?php if ($charts['commodity']['values']): ?>
                            <div class="chart-box"><canvas id="chartCommodity" aria-label="Farm area by commodity" role="img"></canvas></div>
                        <?php else: ?>
                            <?= Ui::emptyState('No farms yet', 'Add farms in Resources to see the breakdown.') ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script id="dashboardCharts" type="application/json"><?= json_encode($charts, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><span class="card-title-icon"><i class="bi bi-person-plus-fill" aria-hidden="true"></i> Recently registered</span> <a class="small fw-normal" href="<?= url('/farmers') ?>">Open Registry</a></div>
            <?php if (!$recentProfiles): ?>
                <?= Ui::emptyState('No profiles yet', 'Registered farmers and fisherfolk will appear here.') ?>
            <?php else: ?>
                <ul class="dash-list">
                    <?php foreach ($recentProfiles as $p): ?>
                        <li><span class="dash-person"><span class="initials"><?= e(strtoupper(mb_substr($p['name'], 0, 1) . mb_substr(trim((string) strstr($p['name'], ','), ', '), 0, 1))) ?></span><span><span class="fw-semibold"><?= e($p['name']) ?></span> <span class="text-muted">· <?= e($p['barangay']) ?></span></span>
                            </span><span class="text-muted small"><?= e(format_date($p['created_at'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><span class="card-title-icon is-red"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Recent damage and pest reports</span> <a class="small fw-normal" href="<?= url('/monitoring?tab=damage') ?>">Open Monitoring</a></div>
            <?php if (!$recentDamage): ?>
                <?= Ui::emptyState('No damage reports', 'Pest, disease and calamity reports will appear here.') ?>
            <?php else: ?>
                <ul class="dash-list">
                    <?php foreach ($recentDamage as $d): ?>
                        <li><span><span class="fw-semibold"><?= e($d['calamity_name'] ?: ($damageOpts['cause_type']['options'][$d['cause_type']] ?? $d['cause_type'])) ?></span>
                            <span class="text-muted">· <?= e($damageOpts['commodity']['options'][$d['commodity']] ?? $d['commodity']) ?>, <?= e($d['barangay']) ?></span></span>
                            <span class="text-nowrap"><?= Ui::badge($damageOpts['severity']['options'][$d['severity']] ?? $d['severity'],
                                ['low' => 'muted', 'moderate' => 'warning', 'high' => 'danger', 'total' => 'danger'][$d['severity']] ?? 'muted') ?>
                                <span class="text-muted small"><?= e(format_date($d['date_occurred'])) ?></span></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
