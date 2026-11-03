<?php
// pages/dashboard.php - Dynamic Role-based Dashboard
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary', 'treasurer', 'bhw', 'resident');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'Dashboard';

$sitioId = $user['assigned_sitio_id'] ?? null;
$sitioName = ($sitioId && isset(SITIOS_LIST[$sitioId])) ? SITIOS_LIST[$sitioId] : null;

// Gather Metrics
if (in_array($role, ['captain', 'secretary', 'treasurer'])) {
    // 1. Master Stats for Captain & Secretary (Active Only)
    $totalResidents = (int)$db->query("SELECT COUNT(*) FROM residents WHERE status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $pendingResidents = (int)$db->query("SELECT COUNT(*) FROM residents WHERE status = 'pending' AND (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $totalHouseholds = (int)$db->query("SELECT COUNT(*) FROM households WHERE (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $totalPwds = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_pwd = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $totalSoloParents = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_solo_parent = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $totalIndigents = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_indigent = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $totalSeniors = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_senior = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $totalStudents = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_student = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $total4ps = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_4ps = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $totalAidPrograms = (int)$db->query("SELECT COUNT(*) FROM assistance_programs WHERE status = 'Ongoing'")->fetchColumn();

    // Sitio Summary (Active Only)
    $sitioStatsQuery = $db->query("
        SELECT s.id, s.name, s.code, u.full_name AS bhw_name,
               COUNT(CASE WHEN r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL) THEN 1 END) AS resident_count,
               COUNT(CASE WHEN r.status = 'pending' AND (r.is_archived = 0 OR r.is_archived IS NULL) THEN 1 END) AS pending_count,
               COUNT(CASE WHEN r.is_pwd = 1 AND r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL) THEN 1 END) AS pwd_count,
               COUNT(CASE WHEN r.is_solo_parent = 1 AND r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL) THEN 1 END) AS solo_count,
               COUNT(CASE WHEN r.is_indigent = 1 AND r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL) THEN 1 END) AS indigent_count,
               COUNT(CASE WHEN r.is_senior = 1 AND r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL) THEN 1 END) AS senior_count
               COUNT(CASE WHEN r.is_senior = 1 AND r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL) THEN 1 END) AS senior_count,
               COUNT(CASE WHEN r.is_student = 1 AND r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL) THEN 1 END) AS student_count
        FROM sitios s
        LEFT JOIN users u ON s.id = u.assigned_sitio_id AND u.role = 'bhw'
        LEFT JOIN residents r ON s.id = r.sitio_id
        GROUP BY s.id, s.name, s.code, u.full_name
        ORDER BY s.id ASC
    ");
    $sitioStats = $sitioStatsQuery->fetchAll();

    // Recent Pending Registrations
    $recentPending = $db->query("
        SELECT r.*, s.name AS sitio_name 
        FROM residents r 
        JOIN sitios s ON r.sitio_id = s.id 
        WHERE r.status = 'pending' AND (r.is_archived = 0 OR r.is_archived IS NULL)
        ORDER BY r.registered_at DESC 
        LIMIT 6
    ")->fetchAll();

    // Ongoing Aid Programs
    $activePrograms = $db->query("
        SELECT p.*, 
               COUNT(b.id) AS total_enrolled,
               COUNT(CASE WHEN b.status = 'Claimed' THEN 1 END) AS total_claimed
        FROM assistance_programs p
        LEFT JOIN assistance_beneficiaries b ON p.id = b.program_id
        WHERE p.status = 'Ongoing'
        GROUP BY p.id
        ORDER BY p.distribution_date ASC
        LIMIT 4
    ")->fetchAll();

} elseif ($role === 'bhw') {
    // 2. Sitio-Specific Stats for BHW (Active Only)
    $stmtSitioTotal = $db->prepare("SELECT COUNT(*) FROM residents WHERE sitio_id = ? AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)");
    $stmtSitioTotal->execute([$sitioId]);
    $sitioResidents = (int)$stmtSitioTotal->fetchColumn();

    $stmtSitioPending = $db->prepare("SELECT COUNT(*) FROM residents WHERE sitio_id = ? AND status = 'pending' AND (is_archived = 0 OR is_archived IS NULL)");
    $stmtSitioPending->execute([$sitioId]);
    $sitioPending = (int)$stmtSitioPending->fetchColumn();

    $stmtSitioPwds = $db->prepare("SELECT COUNT(*) FROM residents WHERE sitio_id = ? AND is_pwd = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)");
    $stmtSitioPwds->execute([$sitioId]);
    $sitioPwds = (int)$stmtSitioPwds->fetchColumn();

    $stmtSitioSolo = $db->prepare("SELECT COUNT(*) FROM residents WHERE sitio_id = ? AND is_solo_parent = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)");
    $stmtSitioSolo->execute([$sitioId]);
    $sitioSolo = (int)$stmtSitioSolo->fetchColumn();

    $stmtSitioIndigents = $db->prepare("SELECT COUNT(*) FROM residents WHERE sitio_id = ? AND is_indigent = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)");
    $stmtSitioIndigents->execute([$sitioId]);
    $sitioIndigents = (int)$stmtSitioIndigents->fetchColumn();

    $stmtSitioSeniors = $db->prepare("SELECT COUNT(*) FROM residents WHERE sitio_id = ? AND is_senior = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)");
    $stmtSitioSeniors->execute([$sitioId]);
    $sitioSeniors = (int)$stmtSitioSeniors->fetchColumn();

    $stmtSitioStudents = $db->prepare("SELECT COUNT(*) FROM residents WHERE sitio_id = ? AND is_student = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)");
    $stmtSitioStudents->execute([$sitioId]);
    $sitioStudents = (int)$stmtSitioStudents->fetchColumn();

    // Household & Environmental Stats in Sitio (Active Only)
    $stmtSitioHh = $db->prepare("SELECT COUNT(*) FROM households WHERE sitio_id = ? AND (is_archived = 0 OR is_archived IS NULL)");
    $stmtSitioHh->execute([$sitioId]);
    $sitioHouseholds = (int)$stmtSitioHh->fetchColumn();

    $stmtSitioDoc = $db->prepare("SELECT COUNT(*) FROM households WHERE sitio_id = ? AND (is_archived = 0 OR is_archived IS NULL) AND bhw_documentation_photo IS NOT NULL AND bhw_documentation_photo != ''");
    $stmtSitioDoc->execute([$sitioId]);
    $sitioDocumentedHh = (int)$stmtSitioDoc->fetchColumn();
    $sitioMissingDocHh = max(0, $sitioHouseholds - $sitioDocumentedHh);

    $stmtSitioSanitation = $db->prepare("SELECT COUNT(*) FROM households WHERE sitio_id = ? AND (is_archived = 0 OR is_archived IS NULL) AND sanitation_facility IN ('Pit Latrine', 'No Facility', 'Shared / Communal')");
    $stmtSitioSanitation->execute([$sitioId]);
    $sitioSanitationHazards = (int)$stmtSitioSanitation->fetchColumn();

    // Recent Households in this Sitio (Active Only)
    $stmtRecentHh = $db->prepare("
        SELECT h.*, (SELECT COUNT(*) FROM residents r WHERE r.household_no = h.household_no AND (r.is_archived = 0 OR r.is_archived IS NULL)) AS member_count
        FROM households h
        WHERE h.sitio_id = ? AND (h.is_archived = 0 OR h.is_archived IS NULL)
        ORDER BY h.id DESC LIMIT 5
    ");
    $stmtRecentHh->execute([$sitioId]);
    $sitioRecentHouseholds = $stmtRecentHh->fetchAll(PDO::FETCH_ASSOC);

    // Pending Signups in BHW's specific Sitio
    $stmtPendingList = $db->prepare("
        SELECT * FROM residents 
        WHERE sitio_id = ? AND status = 'pending' AND (is_archived = 0 OR is_archived IS NULL)
        ORDER BY registered_at DESC
    ");
    $stmtPendingList->execute([$sitioId]);
    $myPendingResidents = $stmtPendingList->fetchAll();

    // Recent Approved in this Sitio
    $stmtApprovedList = $db->prepare("
        SELECT * FROM residents 
        WHERE sitio_id = ? AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)
        ORDER BY id DESC LIMIT 5
    ");
    $stmtApprovedList->execute([$sitioId]);
    $myRecentApproved = $stmtApprovedList->fetchAll();

} else {
    // 3. Resident Portal Dashboard
    $stmtRes = $db->prepare("
        SELECT r.*, s.name AS sitio_name, u.full_name AS bhw_name 
        FROM residents r 
        JOIN sitios s ON r.sitio_id = s.id 
        LEFT JOIN users u ON s.id = u.assigned_sitio_id AND u.role = 'bhw'
        WHERE r.user_id = ?
    ");
    $stmtRes->execute([$user['id']]);
    $resident = $stmtRes->fetch();

    $myAidHistory = [];
    $activePostedPrograms = [];
    if ($resident) {
        $stmtAid = $db->prepare("
            SELECT b.*, p.title AS program_title, p.category, p.distribution_date 
            FROM assistance_beneficiaries b 
            JOIN assistance_programs p ON b.program_id = p.id 
            WHERE b.resident_id = ? 
            ORDER BY p.distribution_date DESC
        ");
        $stmtAid->execute([$resident['id']]);
        $myAidHistory = $stmtAid->fetchAll();

        // Active Posted Assistance Programs
        $activePostedPrograms = $db->query("
            SELECT id, title, category, description, amount_per_beneficiary, in_kind_details, distribution_date 
            FROM assistance_programs 
            WHERE status = 'Ongoing' 
            ORDER BY distribution_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Layout header and footer are loaded automatically by index.php container
?>

<!-- ========================================== -->
<!-- 1. CAPTAIN, SECRETARY & TREASURER DASHBOARD-->
<!-- ========================================== -->
<?php if (in_array($role, ['captain', 'secretary', 'treasurer'])): ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <?= $role === 'treasurer' ? 'Treasurer Financial & Ayuda Dashboard' : 'Executive Dashboard & Social Profiling' ?>
            </h3>
            <p class="text-muted small mb-0">
                <?= BRGY_NAME ?> &bull; <?= $role === 'treasurer' ? 'Barangay Treasury, Ayuda Disbursement & Demographic Financial Overview' : 'Municipal Social Welfare & Resident Demographics Overview' ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($role === 'treasurer'): ?>
                <a href="<?= url('social_services/distribute') ?>" class="btn btn-primary fw-bold shadow-sm">
                    <i class="bi bi-hand-index-thumb-fill me-1"></i> Ayuda Distribution Desk
                </a>
                <a href="<?= url('reports/distribution') ?>" class="btn btn-outline-secondary fw-semibold shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Reports of Distribution
                </a>
            <?php elseif ($role === 'captain'): ?>
                <a href="<?= url('approvals') ?>" class="btn btn-warning text-dark fw-bold shadow-sm">
                    <i class="bi bi-shield-check me-1"></i> Approvals Hub
                    <?php 
                        $capPending = get_pending_approvals_count_for_role('captain'); 
                        if ($capPending > 0) echo "({$capPending})";
                    ?>
                </a>
                <a href="<?= url('reports/distribution') ?>" class="btn btn-outline-secondary fw-semibold shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Reports of Distribution
                </a>
            <?php else: // secretary ?>
                <a href="<?= url('approvals') ?>" class="btn btn-warning text-dark fw-semibold shadow-sm">
                    <i class="bi bi-shield-check me-1"></i> Approvals Hub
                    <?php 
                        $secPending = get_pending_approvals_count_for_role('secretary'); 
                        if ($secPending > 0) echo "({$secPending})";
                    ?>
                </a>
                <a href="<?= url('social_services/create') ?>" class="btn btn-success fw-semibold shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Ayuda Program
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert for Pending Approvals in Centralized Hub -->
    <?php 
        $pendingApprovals = get_pending_approvals_count_for_role($role);
    ?>
    <?php if (in_array($role, ['captain', 'secretary']) && $pendingApprovals > 0): ?>
        <div class="alert alert-warning border border-warning shadow-sm d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-bell-fill fs-3 text-warning-emphasis"></i>
                <div>
                    <h6 class="fw-bold text-warning-emphasis mb-0">Attention: <?= $pendingApprovals ?> Submission(s) Awaiting Your Action in Centralized Approvals</h6>
                    <small class="text-dark"><?= $role === 'captain' ? 'Assistance programs requiring Punong Barangay executive authorization [Code: 777].' : 'Resident signups awaiting Barangay Secretary verification.' ?></small>
                </div>
            </div>
            <a href="<?= url('approvals?tab=pending') ?>" class="btn btn-sm btn-dark px-3">
                Open Approvals Hub <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics Cards -->
    <!-- Summary Metrics Cards (Interactive Sector Hotlinks) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Population</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($totalResidents) ?></h3>
                        <small class="text-muted"><?= $totalHouseholds ?> Households</small>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= url('residents') ?>" class="text-decoration-none">
                <div class="stat-card p-3 h-100 hover-shadow transition border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Population</span>
                            <h4 class="fw-bold text-success mb-0 mt-1"><?= number_format($totalResidents) ?></h4>
                            <small class="text-muted"><?= $totalHouseholds ?> Households</small>
                        </div>
                        <div class="stat-icon-box bg-success bg-opacity-10 text-success">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="stat-icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-people-fill"></i>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= url('residents?category=pwd') ?>" class="text-decoration-none">
                <div class="stat-card p-3 h-100 hover-shadow transition border border-primary-subtle bg-primary-subtle bg-opacity-10">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-primary small fw-semibold text-uppercase">PWD Sector</span>
                            <h4 class="fw-bold text-primary mb-0 mt-1"><?= number_format($totalPwds) ?></h4>
                            <small class="text-primary"><i class="bi bi-person-wheelchair me-1"></i>View PWDs &rarr;</small>
                        </div>
                        <div class="stat-icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-person-wheelchair"></i>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">PWD Residents</span>
                        <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($totalPwds) ?></h3>
                        <small class="text-primary"><i class="bi bi-person-wheelchair"></i> Priority Sector</small>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= url('residents?category=student') ?>" class="text-decoration-none">
                <div class="stat-card p-3 h-100 hover-shadow transition border border-info-subtle bg-info-subtle bg-opacity-10">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-info-emphasis small fw-semibold text-uppercase">Students</span>
                            <h4 class="fw-bold text-info-emphasis mb-0 mt-1"><?= number_format($totalStudents) ?></h4>
                            <small class="text-info-emphasis"><i class="bi bi-mortarboard-fill me-1"></i>Learners &rarr;</small>
                        </div>
                        <div class="stat-icon-box bg-info bg-opacity-10 text-info">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                    </div>
                    <div class="stat-icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-person-wheelchair"></i>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= url('residents?category=solo_parent') ?>" class="text-decoration-none">
                <div class="stat-card p-3 h-100 hover-shadow transition border border-danger-subtle bg-danger-subtle bg-opacity-10">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-danger small fw-semibold text-uppercase">Solo Parents</span>
                            <h4 class="fw-bold text-danger mb-0 mt-1"><?= number_format($totalSoloParents) ?></h4>
                            <small class="text-danger"><i class="bi bi-person-heart me-1"></i>View Sector &rarr;</small>
                        </div>
                        <div class="stat-icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-person-heart"></i>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Solo Mothers</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($totalSoloParents) ?></h3>
                        <small class="text-danger"><i class="bi bi-person-heart"></i> Solo Parents</small>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= url('residents?category=indigent') ?>" class="text-decoration-none">
                <div class="stat-card p-3 h-100 hover-shadow transition border border-warning-subtle bg-warning-subtle bg-opacity-10">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-warning-emphasis small fw-semibold text-uppercase">Indigents</span>
                            <h4 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= number_format($totalIndigents) ?></h4>
                            <small class="text-muted"><?= $total4ps ?> 4Ps Listed</small>
                        </div>
                        <div class="stat-icon-box bg-warning bg-opacity-10 text-warning-emphasis">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                    <div class="stat-icon-box bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-person-heart"></i>
                    </div>
                </div>
            </div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase">Indigent Families</span>
                        <h3 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= number_format($totalIndigents) ?></h3>
                        <small class="text-muted"><?= $total4ps ?> 4Ps Listed</small>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= url('residents?category=senior') ?>" class="text-decoration-none">
                <div class="stat-card p-3 h-100 hover-shadow transition border border-secondary-subtle">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold text-uppercase">Seniors (60+)</span>
                            <h4 class="fw-bold text-dark mb-0 mt-1"><?= number_format($totalSeniors) ?></h4>
                            <small class="text-secondary"><i class="bi bi-person-cane me-1"></i>Elderly &rarr;</small>
                        </div>
                        <div class="stat-icon-box bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-person-cane"></i>
                        </div>
                    </div>
                    <div class="stat-icon-box bg-warning bg-opacity-10 text-warning-emphasis">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
            </a>
        </div>
    </div>

    <!-- 7 SITIOS BREAKDOWN WITH ASSIGNED HEALTH WORKERS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-success fs-5"></i>
                <h5 class="fw-bold mb-0">The 7 Sitios of Barangay Lublub & Assigned Health Workers</h5>
                <h5 class="fw-bold mb-0">The 7 Sitios of Barangay Lublub & Demographic Breakdown</h5>
            </div>
            <a href="<?= url('sitios') ?>" class="btn btn-sm btn-outline-success">
                View Sitio Details <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Sitio Name</th>
                        <th>Assigned BHW</th>
                        <th class="text-center">Approved Pop.</th>
                        <th class="text-center">Pending Approvals</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">PWD</th>
                        <th class="text-center">Solo Mothers</th>
                        <th class="text-center">Students</th>
                        <th class="text-center">Solo Parents</th>
                        <th class="text-center">Indigents</th>
                        <th class="text-center">Seniors</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sitioStats as $idx => $s): ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= $s['id'] ?></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($s['name']) ?></strong>
                                <span class="badge bg-light text-secondary border ms-1"><?= htmlspecialchars($s['code']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem; font-weight: bold;">
                                        BHW
                                    </div>
                                    <span class="fw-semibold text-success"><?= htmlspecialchars($s['bhw_name'] ?? 'Not Yet Assigned') ?></span>
                                </div>
                            </td>
                            <td class="text-center fw-bold text-dark"><?= number_format($s['resident_count']) ?></td>
                            <td class="text-center">
                                <?php if ($s['pending_count'] > 0): ?>
                                    <a href="<?= url('residents/approvals?sitio=' . $s['id']) ?>" class="badge bg-danger text-white text-decoration-none px-2 py-1">
                                        <?= $s['pending_count'] ?> Pending
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border">0 Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge badge-pwd"><?= $s['pwd_count'] ?></span></td>
                            <td class="text-center"><span class="badge badge-solo"><?= $s['solo_count'] ?></span></td>
                            <td class="text-center"><span class="badge badge-indigent"><?= $s['indigent_count'] ?></span></td>
                            <td class="text-center"><span class="badge badge-senior"><?= $s['senior_count'] ?></span></td>
                            <td class="text-center">
                                <a href="<?= url('residents?sitio=' . $s['id'] . '&category=pwd') ?>" class="badge badge-pwd text-decoration-none" title="Filter PWDs in <?= htmlspecialchars($s['name']) ?>">
                                    <?= $s['pwd_count'] ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="<?= url('residents?sitio=' . $s['id'] . '&category=student') ?>" class="badge bg-info-subtle text-info-emphasis border text-decoration-none" title="Filter Students in <?= htmlspecialchars($s['name']) ?>">
                                    <i class="bi bi-mortarboard-fill me-1"></i><?= $s['student_count'] ?? 0 ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="<?= url('residents?sitio=' . $s['id'] . '&category=solo_parent') ?>" class="badge badge-solo text-decoration-none" title="Filter Solo Parents in <?= htmlspecialchars($s['name']) ?>">
                                    <?= $s['solo_count'] ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="<?= url('residents?sitio=' . $s['id'] . '&category=indigent') ?>" class="badge badge-indigent text-decoration-none" title="Filter Indigents in <?= htmlspecialchars($s['name']) ?>">
                                    <?= $s['indigent_count'] ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="<?= url('residents?sitio=' . $s['id'] . '&category=senior') ?>" class="badge badge-senior text-decoration-none" title="Filter Seniors in <?= htmlspecialchars($s['name']) ?>">
                                    <?= $s['senior_count'] ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('residents?sitio=' . $s['id']) ?>" class="btn btn-xs btn-outline-secondary py-1 px-2" title="View Residents in this Sitio">
                                    <i class="bi bi-people"></i> List
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Aid Programs & Recent Pending -->
    <div class="row g-4">
        <!-- Active Assistance Programs -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-box2-heart-fill text-danger me-2"></i>Active Ayuda & Social Programs</h5>
                    <a href="<?= url('social_services') ?>" class="btn btn-sm btn-link text-success p-0">View All</a>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($activePrograms)): ?>
                        <div class="text-center py-4 text-muted">No active assistance programs right now.</div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($activePrograms as $prog): ?>
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1"><?= htmlspecialchars($prog['category']) ?></span>
                                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($prog['title']) ?></h6>
                                            <small class="text-muted">Target Date: <?= format_date($prog['distribution_date']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success fs-6"><?= format_peso($prog['amount_per_beneficiary']) ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                                        <span>Distribution Progress:</span>
                                        <span class="fw-bold"><?= $prog['total_claimed'] ?> of <?= $prog['total_enrolled'] ?> Claimed</span>
                                    </div>
                                    <?php 
                                        $pct = $prog['total_enrolled'] > 0 ? round(($prog['total_claimed'] / $prog['total_enrolled']) * 100) : 0;
                                    ?>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="mt-2 text-end">
                                        <?php if ($role === 'treasurer'): ?>
                                            <a href="<?= url('social_services/distribute?program_id=' . $prog['id']) ?>" class="btn btn-xs btn-primary fw-bold py-1 px-2 small">
                                                <i class="bi bi-hand-index-thumb-fill me-1"></i> Open Distribution Desk
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= url('social_services/view?id=' . $prog['id']) ?>" class="btn btn-xs btn-outline-success py-1 px-2 small">
                                                <i class="bi bi-eye me-1"></i> View Beneficiaries
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Pending Applications Table -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-warning me-2"></i>Recent Signups for Verification</h5>
                    <?php if (in_array($role, ['captain', 'secretary'])): ?>
                        <a href="<?= url('residents/approvals') ?>" class="btn btn-sm btn-link text-warning-emphasis p-0">All Pending</a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentPending)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-check2-all text-success fs-1 d-block mb-2"></i>
                            All resident registrations have been reviewed and approved!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Resident Name</th>
                                        <th>Sitio</th>
                                        <th>Profiling</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPending as $p): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></div>
                                                <small class="text-muted"><?= $p['age'] ?> yrs &bull; <?= $p['gender'] ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['sitio_name']) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php if ($p['is_pwd']): ?><span class="badge badge-pwd">PWD</span><?php endif; ?>
                                                    <?php if ($p['is_solo_parent']): ?><span class="badge badge-solo">Solo Mother</span><?php endif; ?>
                                                    <?php if ($p['is_indigent']): ?><span class="badge badge-indigent">Indigent</span><?php endif; ?>
                                                    <?php if ($p['is_senior']): ?><span class="badge badge-senior">Senior</span><?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= url('residents/view?id=' . $p['id']) ?>" class="btn btn-xs btn-outline-success py-1 px-2">
                                                    Review
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


<!-- ========================================== -->
<!-- 2A. BARANGAY HEALTH WORKER (BHW) DASHBOARD  -->
<!-- ========================================== -->
<?php elseif ($role === 'bhw'): ?>
    <div class="card bg-success text-white p-4 rounded-4 shadow-sm mb-4 border-0" style="background: linear-gradient(135deg, #0d6338 0%, #15803d 100%);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-warning text-dark fw-bold mb-2">
                    <i class="bi bi-heart-pulse-fill me-1"></i> BARANGAY HEALTH WORKER (BHW) FIELD STATION
                </span>
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h2>
                <p class="mb-0 text-white-50 fs-5">
                    Field Survey Jurisdiction: <strong class="text-white"><?= htmlspecialchars($sitioName ?? 'Barangay Lublub') ?></strong>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('households/add') ?>" class="btn btn-warning text-dark fw-bold btn-lg shadow">
                    <i class="bi bi-camera-fill me-1"></i> New Household Survey & Proof
                </a>
                <a href="<?= url('residents/add') ?>" class="btn btn-light text-success fw-bold btn-lg shadow">
                    <i class="bi bi-person-plus-fill me-1"></i> Enroll Resident
                </a>
            </div>
        </div>
    </div>

    <!-- BHW Duty Notification: Households Missing Proof -->
    <?php if ($sitioMissingDocHh > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center p-3">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-warning text-dark p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-camera-video-off fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0"><?= $sitioMissingDocHh ?> Household<?= $sitioMissingDocHh > 1 ? 's' : '' ?> in <?= htmlspecialchars($sitioName) ?> Need BHW Field Documentation Proof</h6>
                    <small class="text-muted">Take or upload field survey visitation photos to complete their health and socio-economic validation.</small>
                </div>
            </div>
            <a href="<?= url('households?doc=missing&sitio=' . $sitioId) ?>" class="btn btn-sm btn-warning text-dark fw-bold mt-2 mt-md-0 px-3 py-2 shadow-sm">
                View Undocumented Households &rarr;
            </a>
        </div>
    <?php endif; ?>

    <!-- BHW 4-Stat Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="<?= url('households?sitio=' . $sitioId) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow transition">
                    <span class="text-muted small fw-semibold text-uppercase">Sitio Households</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($sitioHouseholds) ?></h3>
                    <small class="text-muted">Total Dwellings Profiled</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= url('households?doc=documented&sitio=' . $sitioId) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow transition">
                    <span class="text-muted small fw-semibold text-uppercase">BHW Field Documented</span>
                    <h3 class="fw-bold text-info mb-0 mt-1"><?= number_format($sitioDocumentedHh) ?></h3>
                    <small class="text-muted">
                        <?= $sitioHouseholds > 0 ? round(($sitioDocumentedHh / $sitioHouseholds) * 100) : 0 ?>% Verified with Proof
                    </small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= url('households?risk=sanitation&sitio=' . $sitioId) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow transition">
                    <span class="text-muted small fw-semibold text-uppercase">Sanitation Alerts</span>
                    <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($sitioSanitationHazards) ?></h3>
                    <small class="text-danger">Pit Latrine / No Toilet</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= url('residents?sitio=' . $sitioId) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow transition">
                    <span class="text-muted small fw-semibold text-uppercase">Sitio Residents</span>
                    <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($sitioResidents) ?></h3>
                    <small class="text-muted"><?= $sitioPwds ?> PWD &bull; <?= $sitioSeniors ?> Seniors</small>
                    <small class="text-muted"><?= $sitioPwds ?> PWD &bull; <?= $sitioStudents ?> Students</small>
                </div>
            </a>
        </div>
    </div>

    <!-- BHW Sector Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success bg-opacity-10 text-success fw-bold p-2"><i class="bi bi-funnel"></i> Quick Sector Profiling:</span>
                <small class="text-muted">Directly inspect profiled residents in <strong><?= htmlspecialchars($sitioName) ?></strong>:</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('residents?sitio=' . $sitioId . '&category=pwd') ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                    <i class="bi bi-person-wheelchair me-1"></i>PWDs (<?= $sitioPwds ?>)
                </a>
                <a href="<?= url('residents?sitio=' . $sitioId . '&category=student') ?>" class="btn btn-sm btn-outline-info fw-semibold">
                    <i class="bi bi-mortarboard-fill me-1"></i>Students (<?= $sitioStudents ?>)
                </a>
                <a href="<?= url('residents?sitio=' . $sitioId . '&category=solo_parent') ?>" class="btn btn-sm btn-outline-danger fw-semibold">
                    <i class="bi bi-person-heart me-1"></i>Solo Parents (<?= $sitioSolo ?>)
                </a>
                <a href="<?= url('residents?sitio=' . $sitioId . '&category=indigent') ?>" class="btn btn-sm btn-outline-warning text-dark fw-semibold">
                    <i class="bi bi-wallet2 me-1"></i>Indigents (<?= $sitioIndigents ?>)
                </a>
                <a href="<?= url('residents?sitio=' . $sitioId . '&category=senior') ?>" class="btn btn-sm btn-outline-secondary fw-semibold">
                    <i class="bi bi-person-cane me-1"></i>Seniors (<?= $sitioSeniors ?>)
                </a>
                <a href="<?= url('residents?sitio=' . $sitioId) ?>" class="btn btn-sm btn-success fw-semibold">
                    <i class="bi bi-people-fill me-1"></i>All <?= htmlspecialchars($sitioName) ?> Residents (<?= $sitioResidents ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Main Operational Workspace for BHW: Households & Residents Side by Side -->
    <div class="row g-4 mb-4">
        <!-- Column 1: Recent Household Field Surveys & Documentation -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-success">
                        <i class="bi bi-houses-fill me-2"></i>Recent Household Surveys in <?= htmlspecialchars($sitioName) ?>
                    </h6>
                    <a href="<?= url('households?sitio=' . $sitioId) ?>" class="btn btn-sm btn-link text-success p-0">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-3">Photo & HH No.</th>
                                <th>Head & Address</th>
                                <th>Sanitation / Water</th>
                                <th>BHW Proof</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sitioRecentHouseholds)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-houses fs-1 d-block mb-2 opacity-50"></i>
                                        No households surveyed yet in <?= htmlspecialchars($sitioName) ?>.
                                        <div class="mt-2">
                                            <a href="<?= url('households/add') ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-plus-circle me-1"></i> Register First Household
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sitioRecentHouseholds as $hh): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($hh['house_photo_path']) && file_exists(BASE_PATH . '/' . $hh['house_photo_path'])): ?>
                                                    <img src="<?= asset($hh['house_photo_path']) ?>" alt="House" class="rounded-2 shadow-sm border" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 40px; height: 40px;">
                                                        <i class="bi bi-house"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="fw-bold font-monospace small">
                                                    <a href="<?= url('households/view?id=' . $hh['id']) ?>" class="text-dark text-decoration-none hover-underline">
                                                        <?= htmlspecialchars($hh['household_no']) ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($hh['head_resident_name'] ?: 'No Head Specified') ?></div>
                                            <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($hh['address_line']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?= in_array($hh['sanitation_facility'], ['Pit Latrine', 'No Facility']) ? 'bg-danger' : 'bg-light text-dark border' ?> small" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($hh['sanitation_facility']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($hh['bhw_documentation_photo'])): ?>
                                                <span class="badge bg-success small" style="font-size: 0.7rem;"><i class="bi bi-check-circle me-1"></i>Proof OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark small" style="font-size: 0.7rem;"><i class="bi bi-camera-off me-1"></i>Need Proof</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('residents/add?household_no=' . urlencode($hh['household_no']) . '&sitio_id=' . $sitioId) ?>" class="btn btn-outline-success btn-xs py-1 px-2" title="Enroll Member">
                                                    <i class="bi bi-person-plus"></i> Member
                                                </a>
                                                <a href="<?= url('households/view?id=' . $hh['id']) ?>" class="btn btn-outline-secondary btn-xs py-1 px-2" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
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

        <!-- Column 2: Recent Residents & Medical Profiling -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-success">
                        <i class="bi bi-heart-pulse-fill me-2 text-danger"></i>Sitio Residents & Health Alerts
                    </h6>
                    <a href="<?= url('residents?sitio=' . $sitioId) ?>" class="btn btn-sm btn-link text-success p-0">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myRecentApproved)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-person-x fs-1 d-block mb-2 opacity-50"></i>
                            No residents enrolled yet in <?= htmlspecialchars($sitioName) ?>.
                            <div class="mt-2">
                                <a href="<?= url('residents/add') ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-person-plus me-1"></i> Enroll Resident Profile
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($myRecentApproved as $r): ?>
                                <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($r['photo_path']) && file_exists(BASE_PATH . '/' . $r['photo_path'])): ?>
                                            <img src="<?= url($r['photo_path']) ?>" alt="Photo" class="rounded-circle border border-success object-fit-cover" style="width: 38px; height: 38px;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-success bg-opacity-10 text-success fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                                <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold text-dark small">
                                                <a href="<?= url('residents/view?id=' . $r['id']) ?>" class="text-dark text-decoration-none hover-underline">
                                                    <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'] . ' ' . $r['suffix']) ?>
                                                </a>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                <?= $r['age'] ?> yrs &bull; <?= $r['gender'] ?> &bull; <?= htmlspecialchars($r['household_no'] ?: 'Independent') ?>
                                            </div>
                                            <?php if (!empty($r['medical_conditions'])): ?>
                                                <div class="text-danger small mt-1" style="font-size: 0.72rem;">
                                                    <i class="bi bi-heart-pulse-fill me-1"></i><?= htmlspecialchars($r['medical_conditions']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="<?= url('residents/view?id=' . $r['id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                        View
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<!-- ========================================== -->
<!-- 3. RESIDENT PORTAL DASHBOARD               -->
<!-- ========================================== -->
<?php else: ?>
    <?php if ($resident): ?>
        <div class="row g-4 mb-4">
            <!-- Digital Resident Profile Card -->
            <div class="col-lg-5">
                <div class="resident-id-card shadow-lg mx-auto">
                    <div class="id-header">
                        <div class="text-white-50 small fw-bold text-uppercase">Republic of the Philippines &bull; Province of Iloilo</div>
                        <h5 class="fw-bold mb-0">BARANGAY LUBLUB</h5>
                        <div class="small text-white-50">Municipality of Dumangas &bull; P.D. Monfort North</div>
                        <div class="mt-2 badge bg-white text-success fw-bold px-3 py-1">OFFICIAL RESIDENT ID</div>
                    </div>
                    <div class="p-4 bg-white text-center">
                        <div class="mx-auto rounded-circle bg-light border border-3 border-success d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 100px; height: 100px; font-size: 2.5rem; color: #0d6338;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix']) ?></h4>
                        <p class="text-muted small mb-2">Resident ID: <strong><?= htmlspecialchars($resident['resident_code']) ?></strong></p>

                        <div class="p-2 rounded bg-light border mb-3 text-start small">
                            <div class="row g-1">
                                <div class="col-4 text-muted">Sitio:</div>
                                <div class="col-8 fw-bold text-success"><?= htmlspecialchars($resident['sitio_name']) ?></div>
                                <div class="col-4 text-muted">Assigned BHW:</div>
                                <div class="col-8 fw-semibold text-dark"><?= htmlspecialchars($resident['bhw_name'] ?? 'Barangay Health Center') ?></div>
                                <div class="col-4 text-muted">Civil Status:</div>
                                <div class="col-8 fw-semibold"><?= htmlspecialchars($resident['civil_status']) ?> (Age: <?= $resident['age'] ?>)</div>
                                <div class="col-4 text-muted">Precinct No:</div>
                                <div class="col-8 fw-semibold"><?= htmlspecialchars($resident['precinct_no'] ?: 'Not Registered') ?></div>
                            </div>
                        </div>

                        <!-- Vulnerability Badges -->
                        <div class="d-flex flex-wrap justify-content-center gap-1 mb-3">
                            <?php if ($resident['is_pwd']): ?><span class="badge badge-pwd"><i class="bi bi-person-wheelchair me-1"></i>Verified PWD</span><?php endif; ?>
                            <?php if ($resident['is_solo_parent']): ?><span class="badge badge-solo"><i class="bi bi-person-heart me-1"></i>Solo Mother</span><?php endif; ?>
                            <?php if ($resident['is_indigent']): ?><span class="badge badge-indigent"><i class="bi bi-wallet2 me-1"></i>Indigent</span><?php endif; ?>
                            <?php if ($resident['is_senior']): ?><span class="badge badge-senior"><i class="bi bi-person-cane me-1"></i>Senior Citizen</span><?php endif; ?>
                            <?php if ($resident['is_4ps']): ?><span class="badge badge-4ps"><i class="bi bi-check-circle me-1"></i>4Ps Member</span><?php endif; ?>
                        </div>

                        <a href="<?= url('residents/my_profile?print_id=1') ?>" class="btn btn-outline-success btn-sm w-100" target="_blank">
                            <i class="bi bi-printer me-1"></i> Print Official Resident Card
                        </a>
                    </div>
                </div>
            </div>

            <!-- Resident Services & Posted Assistance Programs -->
            <div class="col-lg-7">
                <!-- Posted Assistance Programs Card -->
                <div class="card shadow-sm mb-4 border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-box2-heart-fill text-danger me-2"></i>Official Barangay Assistance Drives
                            </h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <?= count($activePostedPrograms) ?> Active Programs
                            </span>
                        </div>
                        <small class="text-muted">
                            Assistance is strictly provided through official barangay-posted programs. Eligible beneficiaries are targeted and enrolled automatically based on validated resident profiling.
                        </small>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($activePostedPrograms)): ?>
                            <div class="text-muted small text-center py-4 bg-light rounded-3">
                                <i class="bi bi-info-circle fs-3 text-muted d-block mb-1"></i>
                                There are currently no active assistance programs posted at this time.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($activePostedPrograms as $app): ?>
                                    <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                                                <?= htmlspecialchars($app['category']) ?>
                                            </span>
                                            <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($app['title']) ?></h6>
                                            <small class="text-muted d-block"><?= htmlspecialchars($app['description']) ?></small>
                                            <small class="text-secondary"><i class="bi bi-calendar-event me-1"></i>Target Date: <?= format_date($app['distribution_date']) ?></small>
                                        </div>
                                        <div class="text-end ps-3">
                                            <span class="badge bg-success fs-6 fw-bold">
                                                <?= $app['amount_per_beneficiary'] > 0 ? format_peso($app['amount_per_beneficiary']) : htmlspecialchars($app['in_kind_details']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Quick Certificate Services -->
                        <h6 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-file-earmark-text text-primary me-2"></i>Barangay Document Services</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="<?= url('certificates?request=indigency') ?>" class="btn btn-outline-success btn-sm w-100 py-2">
                                    <i class="bi bi-file-earmark-medical me-1"></i> Cert. of Indigency
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= url('certificates?request=clearance') ?>" class="btn btn-outline-primary btn-sm w-100 py-2">
                                    <i class="bi bi-file-earmark-check me-1"></i> Barangay Clearance
                                </a>
                            </div>
                        </div>

                        <!-- My Aid History -->
                        <h6 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-clock-history text-secondary me-2"></i>My Assistance Disbursements History</h6>
                        <?php if (empty($myAidHistory)): ?>
                            <div class="text-muted small text-center py-3 bg-light rounded-3">
                                No aid records or disbursements recorded for your profile yet.
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                                <?php foreach ($myAidHistory as $aid): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($aid['program_title']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($aid['category']) ?> &bull; <?= format_date($aid['distribution_date']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success"><?= format_peso($aid['received_amount']) ?></div>
                                            <?= status_badge($aid['status']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>


