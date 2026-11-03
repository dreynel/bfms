<?php
// views/residents/list.php - Resident Profiling Directory with Multi-Category Filters & Dual View
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'bhw');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;
$pageTitle = 'Resident Profiling Directory';

// Filters
$search      = clean($_GET['search'] ?? '');
$filterSitio = isset($_GET['sitio']) ? (int)$_GET['sitio'] : 0;
$category    = clean($_GET['category'] ?? '');
$viewMode    = clean($_GET['view'] ?? 'table'); // 'table', 'cards'
$activeTab   = clean($_GET['tab'] ?? 'active'); // 'active', 'archived'

// If BHW, default to their Sitio
if ($role === 'bhw' && $userSitioId && empty($_GET['sitio'])) {
    $filterSitio = $userSitioId;
}

// Handle Resident Archive, Restore, and Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $resId = (int)($_POST['resident_id'] ?? 0);

    $resStmt = $db->prepare("SELECT * FROM residents WHERE id = ?");
    $resStmt->execute([$resId]);
    $resRecord = $resStmt->fetch(PDO::FETCH_ASSOC);

    if (!$resRecord) {
        set_flash('danger', 'Resident record not found.');
        redirect('residents');
    }

    if ($role === 'bhw') {
        set_flash('danger', 'BHW role has view-only permissions for resident archiving and deletion.');
        redirect('residents');
    }

    if ($action === 'archive') {
        $reason = clean($_POST['archive_reason'] ?? 'Relocated / Transferred');
        $stmt = $db->prepare("UPDATE residents SET is_archived = 1, archived_at = NOW(), archived_reason = ?, archived_by_user_id = ? WHERE id = ?");
        $stmt->execute([$reason, $user['id'], $resId]);

        if (!empty($resRecord['user_id'])) {
            $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?")->execute([$resRecord['user_id']]);
        }

        if (!empty($resRecord['household_no']) && $resRecord['is_head_of_family']) {
            $db->prepare("UPDATE households SET head_resident_name = NULL WHERE household_no = ? AND head_resident_name LIKE ?")
               ->execute([$resRecord['household_no'], '%' . $resRecord['last_name'] . '%']);
        }

        log_activity('Archive Resident', "Resident {$resRecord['first_name']} {$resRecord['last_name']} (Code: {$resRecord['resident_code']}) archived by {$user['full_name']}. Reason: {$reason}");
        set_flash('warning', "Resident <strong>{$resRecord['first_name']} {$resRecord['last_name']}</strong> has been moved to Archives (Reason: {$reason}).");
        redirect('residents?tab=archived');

    } elseif ($action === 'restore') {
        $stmt = $db->prepare("UPDATE residents SET is_archived = 0, archived_at = NULL, archived_reason = NULL, archived_by_user_id = NULL WHERE id = ?");
        $stmt->execute([$resId]);

        if (!empty($resRecord['user_id'])) {
            $db->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$resRecord['user_id']]);
        }

        log_activity('Restore Resident', "Resident {$resRecord['first_name']} {$resRecord['last_name']} (Code: {$resRecord['resident_code']}) restored to active masterlist by {$user['full_name']}.");
        set_flash('success', "Resident <strong>{$resRecord['first_name']} {$resRecord['last_name']}</strong> has been restored to the Active Resident Masterlist.");
        redirect('residents');

    } elseif ($action === 'delete') {
        if (!in_array($role, ['captain', 'secretary'])) {
            set_flash('danger', 'Permanent deletion requires Barangay Captain or Secretary authorization.');
            redirect('residents');
        }

        try {
            $db->beginTransaction();

            // Clear related dependent records safely
            $db->prepare("DELETE FROM assistance_beneficiaries WHERE resident_id = ?")->execute([$resId]);
            $db->prepare("DELETE FROM barangay_certificates WHERE resident_id = ?")->execute([$resId]);

            if (!empty($resRecord['user_id'])) {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$resRecord['user_id']]);
            }

            if (!empty($resRecord['household_no']) && $resRecord['is_head_of_family']) {
                $db->prepare("UPDATE households SET head_resident_name = NULL WHERE household_no = ? AND head_resident_name LIKE ?")
                   ->execute([$resRecord['household_no'], '%' . $resRecord['last_name'] . '%']);
            }

            $db->prepare("DELETE FROM residents WHERE id = ?")->execute([$resId]);
            $db->commit();

            log_activity('Delete Resident', "Permanently deleted resident {$resRecord['first_name']} {$resRecord['last_name']} (Code: {$resRecord['resident_code']}) by {$user['full_name']}.");
            set_flash('danger', "Resident profile for <strong>{$resRecord['first_name']} {$resRecord['last_name']}</strong> has been permanently removed.");
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Failed to delete resident: ' . $e->getMessage());
        }
        redirect('residents' . ($activeTab === 'archived' ? '?tab=archived' : ''));
    }
}

// Build Query
if ($activeTab === 'archived') {
    $sql = "
        SELECT r.*, s.name AS sitio_name, s.code AS sitio_code, u.username,
               arch.full_name AS archiver_name
        FROM residents r
        JOIN sitios s ON r.sitio_id = s.id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users arch ON r.archived_by_user_id = arch.id
        WHERE r.is_archived = 1
    ";
} else {
    $sql = "
        SELECT r.*, s.name AS sitio_name, s.code AS sitio_code, u.username
        FROM residents r
        JOIN sitios s ON r.sitio_id = s.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL)
    ";
}
$params = [];

if ($filterSitio > 0) {
    $sql .= " AND r.sitio_id = ?";
    $params[] = $filterSitio;
}

if (!empty($search)) {
    $sql .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.resident_code LIKE ? OR r.contact_no LIKE ? OR r.household_no LIKE ? OR r.medical_conditions LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

// Social Vulnerability Filters
switch ($category) {
    case 'pwd':
        $sql .= " AND r.is_pwd = 1";
        break;
    case 'solo_parent':
        $sql .= " AND r.is_solo_parent = 1";
        break;
    case 'indigent':
        $sql .= " AND r.is_indigent = 1";
        break;
    case 'senior':
        $sql .= " AND r.is_senior = 1";
        break;
    case '4ps':
        $sql .= " AND r.is_4ps = 1";
        break;
    case 'voter':
        $sql .= " AND r.is_voter = 1";
        break;
    case 'youth':
        $sql .= " AND r.is_youth = 1";
        break;
    case 'student':
        $sql .= " AND r.is_student = 1";
        break;
    case 'medical':
        $sql .= " AND (r.medical_conditions IS NOT NULL AND r.medical_conditions != '')";
        break;
}

$sql .= " ORDER BY r.last_name ASC, r.first_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Category Counts for Quick Statistics (Active Only)
$countQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_pwd = 1 THEN 1 ELSE 0 END) as pwd_count,
    SUM(CASE WHEN is_solo_parent = 1 THEN 1 ELSE 0 END) as solo_count,
    SUM(CASE WHEN is_indigent = 1 THEN 1 ELSE 0 END) as indigent_count,
    SUM(CASE WHEN is_senior = 1 THEN 1 ELSE 0 END) as senior_count,
    SUM(CASE WHEN is_4ps = 1 THEN 1 ELSE 0 END) as fourps_count,
    SUM(CASE WHEN is_student = 1 THEN 1 ELSE 0 END) as student_count,
    SUM(CASE WHEN medical_conditions IS NOT NULL AND medical_conditions != '' THEN 1 ELSE 0 END) as medical_count
    FROM residents WHERE status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)";
if ($role === 'bhw' && $userSitioId) {
    $countQuery .= " AND sitio_id = {$userSitioId}";
}
$counts = $db->query($countQuery)->fetch(PDO::FETCH_ASSOC);

// Archived Count
$archivedCountQuery = "SELECT COUNT(*) FROM residents WHERE is_archived = 1";
if ($role === 'bhw' && $userSitioId) {
    $archivedCountQuery .= " AND sitio_id = {$userSitioId}";
}
$archivedCount = (int)$db->query($archivedCountQuery)->fetchColumn();

// Category Label Definitions
$categoryLabels = [
    'pwd'         => 'Persons with Disability (PWD)',
    'solo_parent' => 'Solo Parents / Single Mothers',
    'indigent'    => 'Indigent Families',
    'senior'      => 'Senior Citizens (60+ yrs)',
    '4ps'         => '4Ps Program Beneficiaries',
    'student'     => 'Students / Learners',
    'voter'       => 'Registered Voters',
    'youth'       => 'Youth (15-30 yrs)',
    'medical'     => 'Medical / Chronic Illness Alerts'
];
?>

<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Residents Directory</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-people-fill text-success me-2"></i>Resident Socio-Economic Masterlist
            </h3>
            <p class="text-muted small mb-0">
                <?= BRGY_NAME ?>, <?= BRGY_SUBTITLE ?>
                <?php if ($role === 'bhw' && $userSitioId): ?>
                    &bull; <span class="text-success fw-bold">Sitio <?= $userSitioId ?>: <?= htmlspecialchars(SITIOS_LIST[$userSitioId]) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- View Mode Switcher -->
            <div class="btn-group shadow-sm me-2" role="group">
                <a href="<?= url('residents?' . http_build_query(array_merge($_GET, ['view' => 'table']))) ?>" class="btn btn-sm <?= $viewMode === 'table' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>" title="Tabular View">
                    <i class="bi bi-table me-1"></i> Table View
                </a>
                <a href="<?= url('residents?' . http_build_query(array_merge($_GET, ['view' => 'cards']))) ?>" class="btn btn-sm <?= $viewMode === 'cards' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>" title="Card View">
                    <i class="bi bi-grid me-1"></i> Card View
                </a>
            </div>

            <?php if ($role === 'bhw'): ?>
                <a href="<?= url('residents/add') ?>" class="btn btn-success fw-semibold shadow-sm">
                    <i class="bi bi-person-plus-fill me-1"></i> Enroll Resident
                </a>
                <a href="<?= url('households/add') ?>" class="btn btn-outline-success">
                    <i class="bi bi-houses me-1"></i> New Household
                </a>
            <?php endif; ?>
            <a href="<?= url('reports/demographic') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Print Masterlist
            </a>
        </div>
    </div>

    <!-- Masterlist vs Archive Navigation Tabs -->
    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'active' ? 'active bg-success fw-bold' : 'bg-white text-dark border' ?> px-3 py-2 rounded-3" href="<?= url('residents?tab=active' . ($filterSitio ? "&sitio=$filterSitio" : '') . ($viewMode ? "&view=$viewMode" : '')) ?>">
                <i class="bi bi-people-fill me-1"></i> Active Residents (<?= number_format($counts['total'] ?? 0) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'archived' ? 'active bg-warning text-dark fw-bold' : 'bg-white text-secondary border' ?> px-3 py-2 rounded-3 position-relative" href="<?= url('residents?tab=archived' . ($filterSitio ? "&sitio=$filterSitio" : '') . ($viewMode ? "&view=$viewMode" : '')) ?>">
                <i class="bi bi-archive-fill me-1 <?= $activeTab === 'archived' ? 'text-dark' : 'text-warning' ?>"></i> Archived Records
                <span class="badge <?= $activeTab === 'archived' ? 'bg-dark text-white' : 'bg-secondary' ?> ms-1"><?= number_format($archivedCount) ?></span>
            </a>
        </li>
    </ul>

    <?php if ($activeTab === 'archived'): ?>
        <div class="alert alert-warning border border-warning shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-archive-fill text-warning-emphasis fs-4"></i>
                <div>
                    <strong class="text-dark">Barangay Lublub Archived Resident Records (Deceased, Relocated, Transferred)</strong>
                    <div class="small text-muted">Archived profiles are safely preserved for historical census records, audit trails, and certification history.</div>
                </div>
            </div>
            <span class="badge bg-warning text-dark fw-bold px-3 py-2"><?= count($residents) ?> Archived Profiles</span>
        </div>
    <?php endif; ?>

    <!-- Category Stat Pills -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <a href="<?= url('residents?tab=' . $activeTab . '&view=' . $viewMode . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="card p-2 text-decoration-none text-dark border h-100 <?= empty($category) ? 'border-success shadow-sm bg-success bg-opacity-10' : 'bg-white' ?>">
                <div class="small text-muted">All <?= $activeTab === 'archived' ? 'Archived' : 'Profiles' ?></div>
                <div class="h5 fw-bold mb-0 text-dark"><?= $activeTab === 'archived' ? number_format($archivedCount) : number_format($counts['total'] ?? 0) ?></div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="<?= url('residents?tab=' . $activeTab . '&category=pwd&view=' . $viewMode . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="card p-2 text-decoration-none border h-100 <?= ($category === 'pwd') ? 'border-primary shadow-sm bg-primary bg-opacity-10' : 'bg-white' ?>">
                <div class="small text-primary"><i class="bi bi-person-wheelchair"></i> PWDs</div>
                <div class="h5 fw-bold mb-0 text-primary"><?= number_format($counts['pwd_count'] ?? 0) ?></div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="<?= url('residents?tab=' . $activeTab . '&category=solo_parent&view=' . $viewMode . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="card p-2 text-decoration-none border h-100 <?= ($category === 'solo_parent') ? 'border-danger shadow-sm bg-danger bg-opacity-10' : 'bg-white' ?>">
                <div class="small text-danger"><i class="bi bi-person-heart"></i> Solo Parents</div>
                <div class="h5 fw-bold mb-0 text-danger"><?= number_format($counts['solo_count'] ?? 0) ?></div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="<?= url('residents?tab=' . $activeTab . '&category=indigent&view=' . $viewMode . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="card p-2 text-decoration-none border h-100 <?= ($category === 'indigent') ? 'border-warning shadow-sm bg-warning bg-opacity-10' : 'bg-white' ?>">
                <div class="small text-warning-emphasis"><i class="bi bi-wallet2"></i> Indigents</div>
                <div class="h5 fw-bold mb-0 text-warning-emphasis"><?= number_format($counts['indigent_count'] ?? 0) ?></div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="<?= url('residents?tab=' . $activeTab . '&category=senior&view=' . $viewMode . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="card p-2 text-decoration-none border h-100 <?= ($category === 'senior') ? 'border-info shadow-sm bg-info bg-opacity-10' : 'bg-white' ?>">
                <div class="small text-info-emphasis"><i class="bi bi-person-cane"></i> Seniors (60+)</div>
                <div class="h5 fw-bold mb-0 text-info-emphasis"><?= number_format($counts['senior_count'] ?? 0) ?></div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="<?= url('residents?tab=' . $activeTab . '&category=4ps&view=' . $viewMode . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="card p-2 text-decoration-none border h-100 <?= ($category === '4ps') ? 'border-success shadow-sm bg-success bg-opacity-10' : 'bg-white' ?>">
                <div class="small text-success"><i class="bi bi-card-checklist"></i> 4Ps Members</div>
                <div class="h5 fw-bold mb-0 text-success"><?= number_format($counts['fourps_count'] ?? 0) ?></div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="<?= url('residents?tab=' . $activeTab . '&category=student&view=' . $viewMode . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="card p-2 text-decoration-none border h-100 <?= ($category === 'student') ? 'border-info shadow-sm bg-info bg-opacity-10' : 'bg-white' ?>">
                <div class="small text-info-emphasis"><i class="bi bi-mortarboard"></i> Students</div>
                <div class="h5 fw-bold mb-0 text-info-emphasis"><?= number_format($counts['student_count'] ?? 0) ?></div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="<?= url('residents?tab=' . $activeTab . '&category=medical&view=' . $viewMode . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="card p-2 text-decoration-none border h-100 <?= ($category === 'medical') ? 'border-danger shadow-sm bg-danger bg-opacity-10' : 'bg-white' ?>">
                <div class="small text-danger"><i class="bi bi-heart-pulse"></i> Medical Alerts</div>
                <div class="h5 fw-bold mb-0 text-danger"><?= number_format($counts['medical_count'] ?? 0) ?></div>
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-3">
            <form action="<?= url('residents') ?>" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="residents">
                <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search name, code, contact, medical condition..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                    <div class="col-md-3">
                        <select name="sitio" class="form-select">
                            <option value="0">-- All 7 Sitios --</option>
                            <?php foreach (SITIOS_LIST as $id => $name): ?>
                                <option value="<?= $id ?>" <?= ($filterSitio === $id) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="sitio" value="<?= $filterSitio ?>">
                <?php endif; ?>

                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">-- All Welfare Sectors --</option>
                        <option value="pwd" <?= ($category === 'pwd') ? 'selected' : '' ?>>Persons with Disability (PWD)</option>
                        <option value="solo_parent" <?= ($category === 'solo_parent') ? 'selected' : '' ?>>Solo Mother / Parent</option>
                        <option value="indigent" <?= ($category === 'indigent') ? 'selected' : '' ?>>Indigent Families</option>
                        <option value="senior" <?= ($category === 'senior') ? 'selected' : '' ?>>Senior Citizens (60+)</option>
                        <option value="4ps" <?= ($category === '4ps') ? 'selected' : '' ?>>4Ps Beneficiaries</option>
                        <option value="student" <?= ($category === 'student') ? 'selected' : '' ?>>Students / Learners</option>
                        <option value="voter" <?= ($category === 'voter') ? 'selected' : '' ?>>Registered Voters</option>
                        <option value="youth" <?= ($category === 'youth') ? 'selected' : '' ?>>Youth (15-30 yrs)</option>
                        <option value="medical" <?= ($category === 'medical') ? 'selected' : '' ?>>Chronic Health Alert</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-success flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="<?= url('residents?view=' . $viewMode) ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Sector / Sitio Filter Banner -->
    <?php if (!empty($category) || ($filterSitio > 0 && !($role === 'bhw' && empty($_GET['sitio'])))): ?>
        <div class="alert alert-primary border border-primary-subtle shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary text-white p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                    <i class="bi bi-funnel-fill fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark fs-6">
                        Filtered Sector: 
                        <?php if (!empty($category)): ?>
                            <span class="badge bg-primary fs-6"><?= htmlspecialchars($categoryLabels[$category] ?? ucfirst($category)) ?></span>
                        <?php endif; ?>
                        <?php if ($filterSitio > 0): ?>
                            <span class="badge bg-success fs-6 ms-1"><i class="bi bi-geo-alt-fill me-1"></i>Sitio <?= $filterSitio ?>: <?= htmlspecialchars(SITIOS_LIST[$filterSitio] ?? '') ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary fs-6 ms-1">All 7 Sitios</span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted mt-1">
                        Displaying <strong><?= count($residents) ?></strong> verified resident profile(s) under this category with their exact Sitio location.
                    </div>
                </div>
            </div>
            <div>
                <a href="<?= url('residents?tab=' . $activeTab . '&view=' . $viewMode . ($role === 'bhw' && $userSitioId ? "&sitio=$userSitioId" : '')) ?>" class="btn btn-sm btn-outline-danger shadow-sm">
                    <i class="bi bi-x-circle me-1"></i> Clear Sector Filter
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- VIEW 1: TABULAR DATA VIEW -->
    <?php if ($viewMode === 'table'): ?>
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0">Registered Residents (<?= count($residents) ?>)</h5>
                    <span class="badge bg-light text-muted border">Tabular Dataset</span>
                </div>
                <input type="text" class="form-control form-control-sm table-search-input w-auto" data-target-table="residentsTable" placeholder="Instant table filter...">
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="residentsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Resident Code</th>
                            <th>Full Name</th>
                            <th>Sitio & Address</th>
                            <th>Age & Gender</th>
                            <th><?= $activeTab === 'archived' ? 'Archive Status & Details' : 'Social Welfare Eligibility' ?></th>
                            <th><?= $activeTab === 'archived' ? 'Contact / Medical' : 'Health & Contact' ?></th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residents)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                                    <?= $activeTab === 'archived' ? 'No archived resident records found.' : 'No residents match the specified filter criteria.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($residents as $r): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($r['resident_code']) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($r['photo_path']) && file_exists(BASE_PATH . '/' . $r['photo_path'])): ?>
                                                <a href="<?= url($r['photo_path']) ?>" class="preview-attachment-btn d-inline-block text-decoration-none" data-preview-title="Profile Photo: <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>" data-preview-subtitle="<?= htmlspecialchars($r['resident_code']) ?> &bull; Sitio <?= htmlspecialchars($r['sitio_name']) ?>" title="Click to view photo modal">
                                                    <img src="<?= url($r['photo_path']) ?>" alt="Photo" class="rounded-circle border border-success object-fit-cover shadow-sm" style="width: 38px; height: 38px; cursor: pointer;">
                                                </a>
                                            <?php else: ?>
                                                <div class="rounded-circle bg-light border border-success d-flex align-items-center justify-content-center fw-bold text-success" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                                    <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark">
                                                    <a href="<?= url('residents/view?id=' . $r['id']) ?>" class="text-decoration-none text-dark hover-underline">
                                                        <?= htmlspecialchars($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name'] . ' ' . $r['suffix']) ?>
                                                    </a>
                                                </div>
                                                <?php if ($r['nickname']): ?>
                                                    <small class="text-muted">Alias: "<?= htmlspecialchars($r['nickname']) ?>"</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mb-1">
                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($r['sitio_name']) ?>
                                        </span>
                                        <div class="small text-muted text-truncate" style="max-width: 180px;"><?= htmlspecialchars($r['address_line']) ?></div>
                                        <div class="mb-1">
                                            <span class="badge bg-success text-white py-1 px-2 shadow-sm" style="font-size: 0.78rem;">
                                                <i class="bi bi-geo-alt-fill me-1"></i>Sitio <?= $r['sitio_id'] ?>: <?= htmlspecialchars($r['sitio_name']) ?>
                                            </span>
                                        </div>
                                        <div class="small text-dark fw-semibold text-truncate" style="max-width: 190px;" title="<?= htmlspecialchars($r['address_line']) ?>">
                                            <?= htmlspecialchars($r['address_line']) ?>
                                        </div>
                                        <?php if (!empty($r['household_no'])): ?>
                                            <div class="small text-secondary font-monospace" style="font-size: 0.72rem;">
                                                <i class="bi bi-house me-1"></i><?= htmlspecialchars($r['household_no']) ?>
                                            <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                                                <i class="bi bi-house me-1"></i>HH: <?= htmlspecialchars($r['household_no']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-semibold"><?= $r['age'] ?> yrs old</div>
                                        <div class="small text-muted"><?= $r['gender'] ?> &bull; <?= $r['civil_status'] ?></div>
                                    </td>
                                    <td>
                                        <?php if ($activeTab === 'archived'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning mb-1">
                                                <i class="bi bi-archive-fill me-1"></i><?= htmlspecialchars($r['archived_reason'] ?: 'Archived') ?>
                                            </span>
                                            <div class="small text-muted" style="font-size: 0.72rem;">
                                                <?= !empty($r['archived_at']) ? date('M d, Y h:i A', strtotime($r['archived_at'])) : 'Archived' ?>
                                            </div>
                                            <?php if (!empty($r['archiver_name'])): ?>
                                                <div class="text-secondary small" style="font-size: 0.70rem;">By: <?= htmlspecialchars($r['archiver_name']) ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php if ($r['is_pwd']): ?>
                                                    <span class="badge badge-pwd" title="<?= htmlspecialchars($r['pwd_type'] ?? '') ?>"><i class="bi bi-person-wheelchair me-1"></i>PWD</span>
                                                    <span class="badge badge-pwd <?= ($category === 'pwd') ? 'ring-2 ring-primary fw-bold shadow-sm' : '' ?>" title="<?= htmlspecialchars($r['pwd_type'] ?? '') ?>"><i class="bi bi-person-wheelchair me-1"></i>PWD<?= !empty($r['pwd_type']) ? ' (' . htmlspecialchars($r['pwd_type']) . ')' : '' ?></span>
                                                <?php endif; ?>
                                                <?php if ($r['is_solo_parent']): ?>
                                                    <span class="badge badge-solo"><i class="bi bi-person-heart me-1"></i>Solo Parent</span>
                                                    <span class="badge badge-solo <?= ($category === 'solo_parent') ? 'ring-2 ring-danger fw-bold shadow-sm' : '' ?>"><i class="bi bi-person-heart me-1"></i>Solo Parent</span>
                                                <?php endif; ?>
                                                <?php if ($r['is_indigent']): ?>
                                                    <span class="badge badge-indigent"><i class="bi bi-wallet2 me-1"></i>Indigent</span>
                                                    <span class="badge badge-indigent <?= ($category === 'indigent') ? 'ring-2 ring-warning fw-bold shadow-sm' : '' ?>"><i class="bi bi-wallet2 me-1"></i>Indigent</span>
                                                <?php endif; ?>
                                                <?php if ($r['is_senior']): ?>
                                                    <span class="badge badge-senior"><i class="bi bi-person-cane me-1"></i>Senior</span>
                                                    <span class="badge badge-senior <?= ($category === 'senior') ? 'ring-2 ring-info fw-bold shadow-sm' : '' ?>"><i class="bi bi-person-cane me-1"></i>Senior (60+)</span>
                                                <?php endif; ?>
                                                <?php if ($r['is_4ps']): ?>
                                                    <span class="badge badge-4ps"><i class="bi bi-check-circle me-1"></i>4Ps</span>
                                                    <span class="badge badge-4ps <?= ($category === '4ps') ? 'ring-2 ring-success fw-bold shadow-sm' : '' ?>"><i class="bi bi-check-circle me-1"></i>4Ps</span>
                                                <?php endif; ?>
                                                <?php if (!empty($r['is_student'])): ?>
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-mortarboard-fill me-1"></i>Student</span>
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle <?= ($category === 'student') ? 'ring-2 ring-info fw-bold shadow-sm' : '' ?>"><i class="bi bi-mortarboard-fill me-1"></i>Student</span>
                                                <?php endif; ?>
                                                <?php if ($r['is_voter']): ?>
                                                    <span class="badge bg-light text-secondary border">Voter</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <div><i class="bi bi-telephone text-muted me-1"></i><?= htmlspecialchars($r['contact_no'] ?: 'No phone') ?></div>
                                        <?php if (!empty($r['medical_conditions'])): ?>
                                            <div class="text-danger small mt-1 text-truncate" style="max-width: 160px;" title="<?= htmlspecialchars($r['medical_conditions']) ?>">
                                                <i class="bi bi-heart-pulse-fill me-1"></i><?= htmlspecialchars($r['medical_conditions']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url('residents/view?id=' . $r['id']) ?>" class="btn btn-outline-secondary" title="View Full Profile">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($activeTab === 'archived'): ?>
                                                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Restore <?= htmlspecialchars(addslashes($r['first_name'] . ' ' . $r['last_name'])) ?> back to active registry?');">
                                                        <input type="hidden" name="resident_id" value="<?= $r['id'] ?>">
                                                        <input type="hidden" name="action" value="restore">
                                                        <button type="submit" class="btn btn-outline-success" title="Restore Resident">
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteResidentModal<?= $r['id'] ?>" title="Permanently Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($role === 'bhw'): ?>
                                                    <a href="<?= url('residents/edit?id=' . $r['id']) ?>" class="btn btn-outline-secondary" title="Edit Profiling">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= url('certificates?resident_id=' . $r['id']) ?>" class="btn btn-outline-success" title="Issue Clearance / Indigency">
                                                    <i class="bi bi-file-earmark-medical"></i>
                                                </a>
                                                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#archiveResidentModal<?= $r['id'] ?>" title="Move to Archives">
                                                        <i class="bi bi-archive"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteResidentModal<?= $r['id'] ?>" title="Permanently Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
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

    <!-- VIEW 2: VISUAL CARD / GRID VIEW -->
    <?php else: ?>
        <div class="row g-4 mb-4">
            <?php if (empty($residents)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                    <?= $activeTab === 'archived' ? 'No archived residents found.' : 'No residents match the specified filter criteria.' ?>
                </div>
            <?php else: ?>
                <?php foreach ($residents as $r): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 hover-shadow transition p-4 position-relative">
                            <?php if ($activeTab === 'archived'): ?>
                                <div class="badge bg-warning text-dark border border-warning position-absolute top-0 end-0 m-3 px-2 py-1">
                                    <i class="bi bi-archive-fill me-1"></i>Archived: <?= htmlspecialchars($r['archived_reason'] ?: 'Archived') ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <?php if (!empty($r['photo_path']) && file_exists(BASE_PATH . '/' . $r['photo_path'])): ?>
                                    <a href="<?= url($r['photo_path']) ?>" class="preview-attachment-btn d-inline-block text-decoration-none" data-preview-title="Profile Photo: <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>" data-preview-subtitle="<?= htmlspecialchars($r['resident_code']) ?> &bull; Sitio <?= htmlspecialchars($r['sitio_name']) ?>" title="Click to view photo modal">
                                        <img src="<?= url($r['photo_path']) ?>" alt="Photo" class="rounded-circle border border-success object-fit-cover shadow-sm" style="width: 58px; height: 58px; cursor: pointer;">
                                    </a>
                                <?php else: ?>
                                    <div class="rounded-circle bg-success bg-opacity-10 border border-success d-flex align-items-center justify-content-center fw-bold text-success" style="width: 58px; height: 58px; font-size: 1.3rem;">
                                        <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flex-grow-1">
                                    <span class="badge bg-light text-dark border font-monospace small mb-1"><?= htmlspecialchars($r['resident_code']) ?></span>
                                    <h5 class="fw-bold text-dark mb-0">
                                        <a href="<?= url('residents/view?id=' . $r['id']) ?>" class="text-decoration-none text-dark hover-underline">
                                            <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'] . ' ' . $r['suffix']) ?>
                                        </a>
                                    </h5>
                                    <span class="badge bg-success bg-opacity-10 text-success fw-semibold mt-1">
                                        Sitio <?= $r['sitio_id'] ?>: <?= htmlspecialchars($r['sitio_name']) ?>
                                    <span class="badge bg-success text-white py-1 px-2 fw-semibold mt-1 shadow-sm">
                                        <i class="bi bi-geo-alt-fill me-1"></i>Sitio <?= $r['sitio_id'] ?>: <?= htmlspecialchars($r['sitio_name']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 mb-3 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Age & Gender:</span>
                                    <strong class="text-dark"><?= $r['age'] ?> yrs old &bull; <?= $r['gender'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Civil Status:</span>
                                    <span><?= $r['civil_status'] ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Household No:</span>
                                    <span class="font-monospace"><?= htmlspecialchars($r['household_no'] ?: 'Independent') ?></span>
                                </div>
                                <?php if (!empty($r['medical_conditions'])): ?>
                                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                        <span class="text-danger fw-semibold"><i class="bi bi-heart-pulse-fill me-1"></i>Health Alert:</span>
                                        <span class="text-danger fw-semibold text-truncate" style="max-width: 160px;"><?= htmlspecialchars($r['medical_conditions']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Welfare Badges -->
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <?php if ($r['is_pwd']): ?>
                                    <span class="badge badge-pwd"><i class="bi bi-person-wheelchair me-1"></i>PWD</span>
                                <?php endif; ?>
                                <?php if ($r['is_solo_parent']): ?>
                                    <span class="badge badge-solo"><i class="bi bi-person-heart me-1"></i>Solo Parent</span>
                                <?php endif; ?>
                                <?php if ($r['is_indigent']): ?>
                                    <span class="badge badge-indigent"><i class="bi bi-wallet2 me-1"></i>Indigent</span>
                                <?php endif; ?>
                                <?php if ($r['is_senior']): ?>
                                    <span class="badge badge-senior"><i class="bi bi-person-cane me-1"></i>Senior</span>
                                <?php endif; ?>
                                <?php if ($r['is_4ps']): ?>
                                    <span class="badge badge-4ps"><i class="bi bi-check-circle me-1"></i>4Ps</span>
                                <?php endif; ?>
                                <?php if (!empty($r['is_student'])): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-mortarboard-fill me-1"></i>Student</span>
                                <?php endif; ?>
                            </div>

                            <!-- Footer Actions -->
                            <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="<?= url('residents/view?id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View Profile
                                </a>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($activeTab === 'archived'): ?>
                                        <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Restore <?= htmlspecialchars(addslashes($r['first_name'] . ' ' . $r['last_name'])) ?> to active masterlist?');">
                                                <input type="hidden" name="resident_id" value="<?= $r['id'] ?>">
                                                <input type="hidden" name="action" value="restore">
                                                <button type="submit" class="btn btn-outline-success" title="Restore">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteResidentModal<?= $r['id'] ?>" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($role === 'bhw'): ?>
                                            <a href="<?= url('residents/edit?id=' . $r['id']) ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= url('certificates?resident_id=' . $r['id']) ?>" class="btn btn-outline-success" title="Issue Clearance">
                                            <i class="bi bi-file-earmark-medical"></i>
                                        </a>
                                        <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#archiveResidentModal<?= $r['id'] ?>" title="Archive">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteResidentModal<?= $r['id'] ?>" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Resident Archive & Delete Modals -->
    <?php foreach ($residents as $r): ?>
        <!-- Archive Modal -->
        <?php if ($activeTab !== 'archived' && in_array($role, ['captain', 'secretary'])): ?>
            <div class="modal fade text-start" id="archiveResidentModal<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <form action="" method="POST">
                            <input type="hidden" name="resident_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="archive">
                            <div class="modal-header bg-warning text-dark py-3">
                                <h6 class="modal-title fw-bold">
                                    <i class="bi bi-archive-fill me-2"></i>Archive Resident Record
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded-3 border">
                                    <div class="rounded-circle bg-warning bg-opacity-25 text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px;">
                                        <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'] . ' ' . $r['suffix']) ?></div>
                                        <div class="small text-muted font-monospace"><?= htmlspecialchars($r['resident_code']) ?> &bull; Sitio <?= htmlspecialchars($r['sitio_name']) ?></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">Reason for Archiving <span class="text-danger">*</span></label>
                                    <select name="archive_reason" class="form-select" required>
                                        <option value="Relocated / Transferred Out">Relocated / Transferred out of Barangay Lublub</option>
                                        <option value="Deceased">Deceased (Recorded for Civil Registry / Death Records)</option>
                                        <option value="Left Barangay Permanently">Left Barangay Permanently</option>
                                        <option value="Duplicate / Erroneous Entry">Duplicate / Erroneous Entry</option>
                                        <option value="Non-Resident / Invalid">Non-Resident / Invalid Entry</option>
                                        <option value="Other">Other / Reason on File</option>
                                    </select>
                                    <div class="form-text small text-muted mt-2">
                                        Archived records remain preserved for historical certificates and verification, but will be excluded from active aid distributions and population metrics.
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light py-2 px-4 justify-content-between">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-warning btn-sm fw-bold px-3">
                                    <i class="bi bi-archive-fill me-1"></i>Confirm Archive
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Permanent Delete Modal (Captain / Secretary) -->
        <?php if (in_array($role, ['captain', 'secretary'])): ?>
            <div class="modal fade text-start" id="deleteResidentModal<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <form action="" method="POST">
                            <input type="hidden" name="resident_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <div class="modal-header bg-danger text-white py-3">
                                <h6 class="modal-title fw-bold">
                                    <i class="bi bi-trash-fill me-2"></i>Permanent Delete Resident
                                </h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 text-center">
                                <div class="display-5 text-danger mb-2"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                <h5 class="fw-bold text-dark">Permanently Delete Record?</h5>
                                <p class="text-muted small mb-3">
                                    Are you sure you want to permanently delete resident <strong><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></strong> (<code><?= htmlspecialchars($r['resident_code']) ?></code>)?
                                </p>
                                <div class="p-3 bg-danger bg-opacity-10 border border-danger rounded-3 text-start text-danger small">
                                    <i class="bi bi-shield-exclamation me-1"></i><strong>Caution:</strong> This action cannot be undone. All associated aid beneficiary records, certificate records, and linked user logins will be permanently purged.
                                </div>
                            </div>
                            <div class="modal-footer bg-light py-2 px-4 justify-content-between">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger btn-sm fw-bold px-3">
                                    <i class="bi bi-trash-fill me-1"></i>Yes, Permanently Delete
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
