<?php
$programs = count(array_unique(array_column($records, 'program_id')));
$flagged  = count(array_filter($records, fn ($r) => $r['duplicate_flag']));
$sub = implode(' · ', array_filter([$profile['barangay_name'], strip_tags(ProgramBeneficiary::livelihoodBadges($profile))]));
?>
<?= Ui::pageHead(Profile::fullName($profile), 'Assistance history · ' . ($profile['barangay_name'] ?? ''),
    '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/farmers/' . $profile['id'])) . '">View profile</a>'
  . '<a class="btn btn-outline-secondary btn-sm" href="' . e(url('/assistance/history')) . '">Search another</a>'
  . '<button class="btn btn-outline-secondary btn-sm no-print" type="button" onclick="window.print()">Print</button>') ?>

<div class="card mb-3"><div class="card-body">
    <div class="row g-3">
        <?php foreach ([['Assistance received', count($records)], ['Programs', $programs], ['Duplicate overrides', $flagged]] as [$label, $value]): ?>
            <div class="col-4"><div class="stat-label"><?= e($label) ?></div><div class="stat-value"><?= e($value) ?></div></div>
        <?php endforeach; ?>
    </div>
</div></div>

<div class="card"><div class="card-body">
    <?php if (!$records): ?>
        <?= Ui::emptyState('No assistance received yet', 'Assistance recorded for this person will appear here, newest first.') ?>
    <?php else: ?>
        <ul class="timeline mb-0">
            <?php foreach ($records as $r): ?>
                <li>
                    <div class="fw-semibold">
                        <?= e($r['type_name']) ?>
                        <?= Ui::badge(AssistanceType::CATEGORIES[$r['category']] ?? $r['category'], 'muted') ?>
                        <?php if ($r['duplicate_flag']): ?><?= Ui::badge('Duplicate override', 'warning') ?><?php endif; ?>
                    </div>
                    <div class="text-muted small">
                        <?= e(AssistanceRecord::qty($r)) ?> ·
                        <a href="<?= e(url('/programs/' . $r['program_id'])) ?>"><?= e($r['program_name']) ?></a> ·
                        <?= e(format_date($r['date_distributed'])) ?> · <?= e($r['assistance_no']) ?>
                        <?php if ($r['ack_receipt_no']): ?> · Receipt <?= e($r['ack_receipt_no']) ?><?php endif; ?>
                    </div>
                    <?php if ($r['duplicate_flag'] && $r['override_reason']): ?>
                        <div class="small text-muted">Override reason: <?= e($r['override_reason']) ?></div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div></div>
