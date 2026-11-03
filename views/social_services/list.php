<?php
// social_services - Social Assistance & Ayuda Programs Directory
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'treasurer', 'resident');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'Social Services & Ayuda Programs';

// Fetch Programs with stats
$sql = "
    SELECT p.*, s.name AS target_sitio_name, u.full_name AS creator_name,
           COUNT(b.id) AS enrolled_count,
           COUNT(CASE WHEN b.status = 'Claimed' THEN 1 END) AS claimed_count,
           SUM(CASE WHEN b.status = 'Claimed' THEN b.received_amount ELSE 0 END) AS total_disbursed
    FROM assistance_programs p
    LEFT JOIN sitios s ON p.target_sitio_id = s.id
    LEFT JOIN users u ON p.created_by_user_id = u.id
    LEFT JOIN assistance_beneficiaries b ON p.id = b.program_id
    GROUP BY p.id
    ORDER BY p.distribution_date DESC
";
$programs = $db->query($sql)->fetchAll();

// Global Stats
$totalDisbursed = (float)$db->query("SELECT SUM(received_amount) FROM assistance_beneficiaries WHERE status = 'Claimed'")->fetchColumn();
$totalClaims    = (int)$db->query("SELECT COUNT(*) FROM assistance_beneficiaries WHERE status = 'Claimed'")->fetchColumn();
$ongoingDrives  = (int)$db->query("SELECT COUNT(*) FROM assistance_programs WHERE status = 'Ongoing'")->fetchColumn();

// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-0">Social Welfare & Ayuda Programs</h3>
        <p class="text-muted small mb-0">Local Relief, Financial Assistance, PWD, Senior & Solo Mother Support Initiatives</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($role === 'secretary'): ?>
            <a href="<?= url('social_services/create') ?>" class="btn btn-success fw-bold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Launch Assistance Drive
            </a>
        <?php endif; ?>
        <?php if ($role === 'treasurer'): ?>
            <a href="<?= url('social_services/distribute') ?>" class="btn btn-primary fw-bold shadow-sm">
                <i class="bi bi-hand-index-thumb me-1"></i> Ayuda Distribution Desk
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Social Services Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-success text-white" style="background: linear-gradient(135deg, #0d6338 0%, #15803d 100%);">
            <span class="small text-white-50 text-uppercase fw-semibold">Total Ayuda Released</span>
            <h2 class="fw-bold mb-0 mt-1"><?= format_peso($totalDisbursed) ?></h2>
            <small class="text-white-50"><i class="bi bi-check2-all me-1"></i><?= number_format($totalClaims) ?> disbursements to date</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <span class="small text-muted text-uppercase fw-semibold">Active Assistance Programs</span>
            <h2 class="fw-bold text-dark mb-0 mt-1"><?= $ongoingDrives ?> Ongoing Drives</h2>
            <small class="text-success fw-semibold"><i class="bi bi-calendar-check me-1"></i>Active in Barangay Lublub</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <span class="small text-muted text-uppercase fw-semibold">Focus Sectors</span>
            <div class="d-flex flex-wrap gap-1 mt-2">
                <span class="badge badge-pwd">PWD Aid</span>
                <span class="badge badge-solo">Solo Mothers</span>
                <span class="badge badge-indigent">Indigent AICS</span>
                <span class="badge badge-senior">Seniors</span>
                <span class="badge badge-4ps">4Ps Relief</span>
                <span class="badge bg-primary">Students</span>
                <span class="badge bg-info text-dark">Flooded / Calamity HH</span>
            </div>
        </div>
    </div>
</div>

<!-- Programs Listing -->
<div class="row g-4">
    <?php if (empty($programs)): ?>
        <div class="col-12">
            <div class="card shadow-sm border-0 p-5 text-center">
                <i class="bi bi-box2-heart text-muted display-4 mb-2"></i>
                <h5 class="fw-bold">No Assistance Programs Launched Yet</h5>
                <p class="text-muted">Click the button above to create and distribute social aid.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($programs as $p): ?>
            <?php 
                $pct = $p['enrolled_count'] > 0 ? round(($p['claimed_count'] / $p['enrolled_count']) * 100) : 0;
            ?>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden">
                    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                <?= htmlspecialchars($p['category']) ?>
                            </span>
                            <span class="text-muted small font-monospace"><?= htmlspecialchars($p['program_code']) ?></span>
                        </div>
                        <?php if ($p['status'] === 'Pending Approval'): ?>
                            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock-history me-1"></i>Pending Approval</span>
                        <?php elseif ($p['status'] === 'Ongoing'): ?>
                            <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Approved (Ongoing)</span>
                        <?php elseif ($p['status'] === 'Rejected'): ?>
                            <span class="badge bg-danger px-2 py-1"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-2 py-1"><?= htmlspecialchars($p['status']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-4">
                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($p['title']) ?></h5>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($p['description']) ?></p>

                        <div class="p-3 rounded-3 bg-light border mb-3 small">
                            <div class="row g-2">
                                <div class="col-6">
                                    <span class="text-muted">Benefit per Resident:</span>
                                    <div class="fw-bold text-success fs-6"><?= format_peso($p['amount_per_beneficiary']) ?></div>
                                    <?php if ($p['in_kind_details']): ?>
                                        <div class="text-muted small">+ <?= htmlspecialchars($p['in_kind_details']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">Distribution Date:</span>
                                    <div class="fw-bold text-dark fs-6"><?= format_date($p['distribution_date']) ?></div>
                                    <div class="text-muted small">Target: <?= htmlspecialchars($p['target_sitio_name'] ?: 'All 7 Sitios') ?></div>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <span class="text-muted">Fund Source:</span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($p['fund_source']) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Distribution Status:</span>
                                <span class="fw-bold text-dark"><?= $p['claimed_count'] ?> of <?= $p['enrolled_count'] ?> Beneficiaries Claimed (<?= $pct ?>%)</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light p-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Disbursed: <strong class="text-success"><?= format_peso($p['total_disbursed']) ?></strong></span>
                        <div class="d-flex gap-2">
                            <a href="<?= url('social_services/view?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i> Beneficiaries
                            </a>
                            <?php if ($role === 'captain' && $p['status'] === 'Pending Approval'): ?>
                                <a href="<?= url('approvals?tab=pending') ?>" class="btn btn-sm btn-warning text-dark fw-bold">
                                    <i class="bi bi-shield-check me-1"></i> Authorize [777]
                                </a>
                            <?php endif; ?>
                            <?php if ($role === 'treasurer'): ?>
                                <?php if ($p['status'] === 'Ongoing'): ?>
                                    <a href="<?= url('social_services/distribute?program_id=' . $p['id']) ?>" class="btn btn-sm btn-primary fw-bold">
                                        <i class="bi bi-hand-index-thumb me-1"></i> Distribution Desk
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary align-self-center">
                                        <i class="bi bi-lock-fill me-1"></i> Awaiting Captain Approval
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

// Handled by index.php container

