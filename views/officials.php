<?php
// pages/officials.php - Barangay Council Directory & Committee Assignments
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary', 'bhw', 'resident');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'Barangay Officials Directory';

$officials = $db->query("
    SELECT o.*, 
           COALESCE(o.official_name, u.full_name) AS full_name,
           COALESCE(o.email, u.email) AS email,
           COALESCE(o.contact_number, u.contact_number) AS contact_number,
           s.name AS sitio_name, s.code AS sitio_code
    FROM officials o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN sitios s ON o.sitio_id = s.id
    WHERE o.position NOT LIKE '%Kagawad%' AND o.position NOT LIKE '%kagawad%'
    ORDER BY o.id ASC
")->fetchAll();

// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-0">Barangay Council & Officials Directory</h3>
        <p class="text-muted small mb-0">
            <?= BRGY_NAME ?> &bull; <?= BRGY_SUBTITLE ?> (Term: 2023 - 2026)
        </p>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($officials as $off): ?>
        <?php 
            $isCaptain = ($off['position'] === 'Punong Barangay');
            $isSecretary = ($off['position'] === 'Barangay Secretary');
        ?>
        <div class="<?= $isCaptain ? 'col-12' : ($isSecretary ? 'col-md-6 col-lg-4' : 'col-md-6 col-lg-4') ?>">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100 <?= $isCaptain ? 'border-start border-4 border-success bg-white' : '' ?>">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle <?= $isCaptain ? 'bg-success text-white' : 'bg-light text-success border border-success' ?> d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 54px; height: 54px; font-size: 1.3rem;">
                                <?= strtoupper(substr($off['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <span class="badge bg-<?= $isCaptain ? 'success' : ($isSecretary ? 'primary' : 'warning text-dark') ?> mb-1">
                                    <?= htmlspecialchars($off['position']) ?>
                                </span>
                                <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($off['full_name']) ?></h5>
                            </div>
                        </div>

                        <div class="p-2 rounded bg-light border mb-3 small">
                            <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase;">Committee / Assignment:</div>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($off['committee']) ?></div>

                            <?php if ($off['sitio_name']): ?>
                                <div class="mt-2 pt-2 border-top">
                                    <span class="text-muted" style="font-size: 0.7rem; text-transform: uppercase;">Sitio Jurisdiction:</span>
                                    <div class="fw-bold text-success"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($off['sitio_name']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="small text-muted">
                            <div><i class="bi bi-telephone me-2 text-success"></i><?= htmlspecialchars($off['contact_number'] ?: '(033) 361-2045') ?></div>
                            <div><i class="bi bi-envelope me-2 text-primary"></i><?= htmlspecialchars($off['email'] ?: 'brgy.lublub@gmail.com') ?></div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size: 0.75rem;">
                        <span>Term: <?= format_date($off['term_start'], 'Y') ?> - <?= format_date($off['term_end'], 'Y') ?></span>
                        <span class="badge bg-light text-secondary border">Active</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

// Handled by index.php container

