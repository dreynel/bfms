<?php
// views/reports/distribution.php - Ayuda Distribution & Disbursement Reports
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'treasurer');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'Reports of Ayuda Distribution';

// Filter by Sitio or Category
$filterSitio = isset($_GET['sitio']) ? (int)$_GET['sitio'] : 0;
$filterCategory = clean($_GET['category'] ?? 'all');

// Global Treasury Disbursement Metrics
$totalDisbursed = (float)$db->query("SELECT SUM(received_amount) FROM assistance_beneficiaries WHERE status = 'Claimed'")->fetchColumn();
$totalClaimsCount = (int)$db->query("SELECT COUNT(*) FROM assistance_beneficiaries WHERE status = 'Claimed'")->fetchColumn();
$totalEnrolledCount = (int)$db->query("SELECT COUNT(*) FROM assistance_beneficiaries")->fetchColumn();
$totalOngoingPrograms = (int)$db->query("SELECT COUNT(*) FROM assistance_programs WHERE status = 'Ongoing'")->fetchColumn();

// Fetch programs with distribution statistics
$sql = "
    SELECT p.*, s.name AS target_sitio_name, u.full_name AS creator_name,
           COUNT(b.id) AS enrolled_count,
           COUNT(CASE WHEN b.status = 'Claimed' THEN 1 END) AS claimed_count,
           SUM(CASE WHEN b.status = 'Claimed' THEN b.received_amount ELSE 0 END) AS total_disbursed,
           SUM(CASE WHEN b.status != 'Claimed' THEN b.received_amount ELSE 0 END) AS unclaimed_balance
    FROM assistance_programs p
    LEFT JOIN sitios s ON p.target_sitio_id = s.id
    LEFT JOIN users u ON p.created_by_user_id = u.id
    LEFT JOIN assistance_beneficiaries b ON p.id = b.program_id
    WHERE 1=1
";
$params = [];

if ($filterSitio > 0) {
    $sql .= " AND p.target_sitio_id = ?";
    $params[] = $filterSitio;
}

if ($filterCategory !== 'all') {
    $sql .= " AND p.category = ?";
    $params[] = $filterCategory;
}

$sql .= " GROUP BY p.id ORDER BY p.distribution_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Sitios for filter
$sitios = $db->query("SELECT id, name FROM sitios ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary text-white px-2 py-1">
                    <i class="bi bi-cash-stack me-1"></i> Office of the Barangay Treasurer
                </span>
                <span class="text-muted small">&bull; Financial Audits & Liquidation</span>
            </div>
            <h3 class="fw-bold text-dark mb-0">Reports of Ayuda Distribution</h3>
            <p class="text-muted small mb-0">Disbursement Masterlists, Beneficiary Claim Audit Trails & Fund Liquidations</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('social_services/distribute') ?>" class="btn btn-primary fw-bold shadow-sm">
                <i class="bi bi-hand-index-thumb me-1"></i> Open Distribution Desk
            </a>
            <button type="button" class="btn btn-outline-dark fw-semibold" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Summary
            </button>
        </div>
    </div>

    <!-- Treasury Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-success text-white" style="background: linear-gradient(135deg, #0d6338 0%, #15803d 100%);">
                <span class="small text-white-50 text-uppercase fw-semibold">Total Ayuda Disbursed</span>
                <h3 class="fw-bold text-white mb-0 mt-1"><?= format_peso($totalDisbursed) ?></h3>
                <small class="text-white-50"><i class="bi bi-check2-all me-1"></i>Liquidated to date</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-primary border-4">
                <span class="small text-muted text-uppercase fw-semibold">Beneficiaries Served</span>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($totalClaimsCount) ?></h3>
                <small class="text-muted"><?= number_format($totalEnrolledCount) ?> total pre-enrolled</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-warning border-4">
                <span class="small text-muted text-uppercase fw-semibold">Unclaimed Balance</span>
                <?php 
                    $unclaimedCount = max($totalEnrolledCount - $totalClaimsCount, 0); 
                ?>
                <h3 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= number_format($unclaimedCount) ?></h3>
                <small class="text-muted">Awaiting resident claim</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-info border-4">
                <span class="small text-muted text-uppercase fw-semibold">Active Ayuda Programs</span>
                <h3 class="fw-bold text-info mb-0 mt-1"><?= $totalOngoingPrograms ?></h3>
                <small class="text-muted">Ongoing in Barangay Lublub</small>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="<?= url('reports/distribution') ?>" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted mb-1">Filter by Sitio Jurisdiction:</label>
                    <select name="sitio" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0">All Sitios (Barangay-wide)</option>
                        <?php foreach ($sitios as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($filterSitio == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted mb-1">Filter by Aid Category:</label>
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($filterCategory === 'all') ? 'selected' : '' ?>>All Assistance Categories</option>
                        <option value="Financial Assistance" <?= ($filterCategory === 'Financial Assistance') ? 'selected' : '' ?>>Financial Assistance (AICS)</option>
                        <option value="Food Assistance" <?= ($filterCategory === 'Food Assistance') ? 'selected' : '' ?>>Food Assistance</option>
                        <option value="Educational Assistance" <?= ($filterCategory === 'Educational Assistance') ? 'selected' : '' ?>>Educational Assistance</option>
                        <option value="Medical/Health Aid" <?= ($filterCategory === 'Medical/Health Aid') ? 'selected' : '' ?>>Medical / Health Aid</option>
                        <option value="PWD Welfare" <?= ($filterCategory === 'PWD Welfare') ? 'selected' : '' ?>>PWD Welfare</option>
                        <option value="Senior Citizen Aid" <?= ($filterCategory === 'Senior Citizen Aid') ? 'selected' : '' ?>>Senior Citizen Aid</option>
                        <option value="Calamity Relief" <?= ($filterCategory === 'Calamity Relief') ? 'selected' : '' ?>>Calamity Relief</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="<?= url('reports/distribution') ?>" class="btn btn-sm btn-outline-secondary w-100" style="margin-top: 22px;">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Distribution Masterlist Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Ayuda Program Disbursement Masterlist
            </h5>
            <span class="badge bg-light text-dark border"><?= count($programs) ?> Program Records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th class="ps-3">Program & Category</th>
                        <th>Target Scope</th>
                        <th>Rate / In-Kind</th>
                        <th>Enrolled vs Claimed</th>
                        <th>Total Disbursed</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Audit Reports & Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($programs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                No assistance distribution records match the selected filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($programs as $p): ?>
                            <?php 
                                $pct = $p['enrolled_count'] > 0 ? round(($p['claimed_count'] / $p['enrolled_count']) * 100) : 0;
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                                        <?= htmlspecialchars($p['category']) ?>
                                    </span>
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($p['title']) ?></div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($p['program_code']) ?> &bull; Date: <?= format_date($p['distribution_date']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= $p['target_sitio_name'] ? htmlspecialchars($p['target_sitio_name']) : 'All 7 Sitios' ?>
                                    </span>
                                    <small class="d-block text-muted mt-1"><?= htmlspecialchars($p['fund_source']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-success"><?= format_peso($p['amount_per_beneficiary']) ?></div>
                                    <?php if ($p['in_kind_details']): ?>
                                        <small class="text-muted d-block">+ <?= htmlspecialchars($p['in_kind_details']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="min-width: 180px;">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span><strong><?= $p['claimed_count'] ?></strong> of <?= $p['enrolled_count'] ?></span>
                                        <span class="fw-bold text-dark"><?= $pct ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>
                                    <h6 class="fw-bold text-success mb-0"><?= format_peso($p['total_disbursed']) ?></h6>
                                    <small class="text-muted">Unclaimed: <?= format_peso($p['unclaimed_balance']) ?></small>
                                </td>
                                <td>
                                    <?php if ($p['status'] === 'Ongoing'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Ongoing</span>
                                    <?php elseif ($p['status'] === 'Completed'): ?>
                                        <span class="badge bg-primary">Completed</span>
                                    <?php elseif ($p['status'] === 'Pending Approval'): ?>
                                        <span class="badge bg-warning text-dark">Pending Approval</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($p['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('reports/print?program_id=' . $p['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print Audit Payroll with Signatures">
                                            <i class="bi bi-printer me-1"></i> Audit Payroll
                                        </a>
                                        <a href="<?= url('social_services/view?id=' . $p['id']) ?>" class="btn btn-outline-secondary" title="View Enrolled Roster">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($role === 'treasurer' && $p['status'] === 'Ongoing'): ?>
                                            <a href="<?= url('social_services/distribute?program_id=' . $p['id']) ?>" class="btn btn-primary fw-bold" title="Open Ayuda Distribution Desk">
                                                <i class="bi bi-hand-index-thumb"></i> Desk
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

