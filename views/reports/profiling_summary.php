<?php
// reports/profiling - Visual Demographic Profiling & Audit Summary
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary');

$db = getDB();
$user = auth_user();
$pageTitle = 'Profiling Analytics Summary';

// Total residents breakdown
$stats = $db->query("
    SELECT 
        COUNT(*) AS total_pop,
        SUM(CASE WHEN is_pwd = 1 THEN 1 ELSE 0 END) AS pwds,
        SUM(CASE WHEN is_solo_parent = 1 THEN 1 ELSE 0 END) AS solos,
        SUM(CASE WHEN is_indigent = 1 THEN 1 ELSE 0 END) AS indigents,
        SUM(CASE WHEN is_senior = 1 THEN 1 ELSE 0 END) AS seniors,
        SUM(CASE WHEN is_4ps = 1 THEN 1 ELSE 0 END) AS fourps,
        SUM(CASE WHEN is_voter = 1 THEN 1 ELSE 0 END) AS voters
    FROM residents WHERE status = 'approved'
")->fetch();

$sitioStats = $db->query("
    SELECT s.name, COUNT(r.id) AS count,
           SUM(CASE WHEN r.is_pwd = 1 THEN 1 ELSE 0 END) AS pwds,
           SUM(CASE WHEN r.is_solo_parent = 1 THEN 1 ELSE 0 END) AS solos,
           SUM(CASE WHEN r.is_indigent = 1 THEN 1 ELSE 0 END) AS indigents
    FROM sitios s
    LEFT JOIN residents r ON s.id = r.sitio_id AND r.status = 'approved'
    GROUP BY s.id, s.name
    ORDER BY s.id ASC
")->fetchAll();

// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-0">Executive Profiling & Analytics Summary</h3>
        <p class="text-muted small mb-0">Demographic Analytics for Decision Making & LGU Social Protection Allocation</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('reports/demographic') ?>" class="btn btn-outline-success">
            <i class="bi bi-table me-1"></i> Full Masterlist View
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Vulnerability Sector Proportions -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-shield-heart-fill text-danger me-2"></i>Social Vulnerability Ratios</h5>
            </div>
            <div class="card-body p-4">
                <?php 
                    $total = max(1, (int)$stats['total_pop']);
                    $pwdPct     = round(($stats['pwds'] / $total) * 100, 1);
                    $soloPct    = round(($stats['solos'] / $total) * 100, 1);
                    $indigentPct = round(($stats['indigents'] / $total) * 100, 1);
                    $seniorPct  = round(($stats['seniors'] / $total) * 100, 1);
                    $fourpsPct  = round(($stats['fourps'] / $total) * 100, 1);
                ?>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span class="text-primary"><i class="bi bi-person-wheelchair me-1"></i> Persons with Disability (PWD)</span>
                        <span><?= $stats['pwds'] ?> residents (<?= $pwdPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pwdPct ?>%;"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span class="text-danger"><i class="bi bi-person-heart me-1"></i> Solo Mothers / Solo Parents</span>
                        <span><?= $stats['solos'] ?> residents (<?= $soloPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $soloPct ?>%;"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span class="text-warning-emphasis"><i class="bi bi-wallet2 me-1"></i> Indigent Low-Income Families</span>
                        <span><?= $stats['indigents'] ?> residents (<?= $indigentPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $indigentPct ?>%;"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span class="text-info-emphasis"><i class="bi bi-person-cane me-1"></i> Senior Citizens (60+ yrs)</span>
                        <span><?= $stats['seniors'] ?> residents (<?= $seniorPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= $seniorPct ?>%;"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-bold mb-1">
                        <span class="text-success"><i class="bi bi-card-checklist me-1"></i> DSWD 4Ps Beneficiaries</span>
                        <span><?= $stats['fourps'] ?> residents (<?= $fourpsPct ?>%)</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $fourpsPct ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sitio Distribution Chart List -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-geo-alt-fill text-success me-2"></i>Population Distribution Across 7 Sitios</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($sitioStats as $st): ?>
                        <?php $sPct = round(($st['count'] / $total) * 100, 1); ?>
                        <div>
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span class="text-dark"><?= htmlspecialchars($st['name']) ?></span>
                                <span class="text-muted"><?= $st['count'] ?> residents (<?= $sPct ?>%)</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $sPct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

// Handled by index.php container

