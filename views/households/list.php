<?php
// views/households/list.php - Household Profiling Directory & BHW Documentation Hub
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'bhw');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;
$pageTitle = 'Household Profiling & BHW Documentation Hub';

// Filters
$search      = clean($_GET['search'] ?? '');
$filterSitio = isset($_GET['sitio']) ? (int)$_GET['sitio'] : 0;
$structure   = clean($_GET['structure'] ?? '');
$sanitation  = clean($_GET['sanitation'] ?? '');
$filterDoc   = clean($_GET['doc'] ?? ''); // 'missing', 'documented'
$filterRisk  = clean($_GET['risk'] ?? ''); // 'sanitation', 'makeshift', 'nopower'
$viewMode    = clean($_GET['view'] ?? 'table'); // 'table', 'cards'
$activeTab   = clean($_GET['tab'] ?? 'active'); // 'active', 'archived'

// If BHW, default to their Sitio unless overridden
if ($role === 'bhw' && $userSitioId && empty($_GET['sitio'])) {
    $filterSitio = $userSitioId;
}

// Handle Household Archive, Restore, and Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    $hhId = (int)($_POST['household_id'] ?? 0);

    $hhCheckStmt = $db->prepare("SELECT * FROM households WHERE id = ?");
    $hhCheckStmt->execute([$hhId]);
    $hhRecord = $hhCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$hhRecord) {
        set_flash('danger', 'Household record not found.');
        redirect('households');
    }

    if ($role === 'bhw') {
        set_flash('danger', 'BHW accounts have view-only permissions for household archiving and deletion.');
        redirect('households');
    }

    if ($action === 'archive') {
        $reason = clean($_POST['archive_reason'] ?? 'Family Relocated Out of Barangay');
        if ($reason === 'Other' && !empty($_POST['custom_archive_reason'])) {
            $reason = clean($_POST['custom_archive_reason']);
        }
        $stmt = $db->prepare("UPDATE households SET is_archived = 1, archived_at = NOW(), archived_reason = ?, archived_by_user_id = ? WHERE id = ?");
        $stmt->execute([$reason, $user['id'], $hhId]);

        log_activity('Archive Household', "Household #{$hhRecord['household_no']} ({$hhRecord['head_resident_name']}) archived by {$user['full_name']}. Reason: {$reason}");
        set_flash('warning', "Household <strong>#{$hhRecord['household_no']}</strong> moved to Archives (Reason: {$reason}).");
        redirect('households?tab=archived');

    } elseif ($action === 'restore') {
        $stmt = $db->prepare("UPDATE households SET is_archived = 0, archived_at = NULL, archived_reason = NULL, archived_by_user_id = NULL WHERE id = ?");
        $stmt->execute([$hhId]);

        log_activity('Restore Household', "Household #{$hhRecord['household_no']} restored to active directory by {$user['full_name']}.");
        set_flash('success', "Household <strong>#{$hhRecord['household_no']}</strong> has been restored to the Active Directory.");
        redirect('households');

    } elseif ($action === 'delete') {
        if (!in_array($role, ['captain', 'secretary'])) {
            set_flash('danger', 'Permanent deletion requires Barangay Captain or Secretary authorization.');
            redirect('households');
        }

        try {
            $db->beginTransaction();

            // Unlink residents belonging to this household so no orphan data breaks
            $db->prepare("UPDATE residents SET household_no = NULL WHERE household_no = ?")->execute([$hhRecord['household_no']]);

            // Delete household record
            $db->prepare("DELETE FROM households WHERE id = ?")->execute([$hhId]);

            $db->commit();

            log_activity('Delete Household', "Permanently deleted Household #{$hhRecord['household_no']} ({$hhRecord['head_resident_name']}) by {$user['full_name']}.");
            set_flash('danger', "Household <strong>#{$hhRecord['household_no']}</strong> has been permanently deleted from records.");
            redirect('households');
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Failed to delete household: ' . $e->getMessage());
            redirect('households');
        }
    }
}

// Build query
$sql = "
    SELECT h.*, s.name AS sitio_name, s.code AS sitio_code, u.full_name AS bhw_name, arch.full_name AS archiver_name,
           (SELECT COUNT(*) FROM residents r WHERE r.household_no = h.household_no AND (r.is_archived = 0 OR r.is_archived IS NULL)) AS registered_residents_count
    FROM households h
    JOIN sitios s ON h.sitio_id = s.id
    LEFT JOIN users u ON h.bhw_assessed_by_user_id = u.id
    LEFT JOIN users arch ON h.archived_by_user_id = arch.id
    WHERE 1=1
";
$params = [];

// Tab filter (Active vs Archived)
if ($activeTab === 'archived') {
    $sql .= " AND h.is_archived = 1";
} else {
    $sql .= " AND (h.is_archived = 0 OR h.is_archived IS NULL)";
}

if ($filterSitio > 0) {
    $sql .= " AND h.sitio_id = ?";
    $params[] = $filterSitio;
}

if (!empty($search)) {
    $sql .= " AND (h.household_no LIKE ? OR h.head_resident_name LIKE ? OR h.address_line LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

if (!empty($structure)) {
    $sql .= " AND h.house_structure = ?";
    $params[] = $structure;
}

if (!empty($sanitation)) {
    $sql .= " AND h.sanitation_facility = ?";
    $params[] = $sanitation;
}

if ($filterDoc === 'missing') {
    $sql .= " AND (h.bhw_documentation_photo IS NULL OR h.bhw_documentation_photo = '')";
} elseif ($filterDoc === 'documented') {
    $sql .= " AND (h.bhw_documentation_photo IS NOT NULL AND h.bhw_documentation_photo != '')";
}

if ($filterRisk === 'sanitation') {
    $sql .= " AND h.sanitation_facility IN ('Pit Latrine', 'No Facility', 'Shared / Communal')";
} elseif ($filterRisk === 'makeshift') {
    $sql .= " AND h.house_structure IN ('Makeshift', 'Light Materials / Wood')";
} elseif ($filterRisk === 'nopower') {
    $sql .= " AND h.electricity_access = 0";
}

$sql .= " ORDER BY h.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$households = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overall Statistics for Scope (Active Only for Masterlist counts)
try {
    $sitioScope = ($role === 'bhw' && $userSitioId) ? "sitio_id = {$userSitioId} AND" : "";
    $totalHh = (int)$db->query("SELECT COUNT(*) FROM households WHERE {$sitioScope} (is_archived = 0 OR is_archived IS NULL)")->fetchColumn();
    $archivedHhCount = (int)$db->query("SELECT COUNT(*) FROM households WHERE {$sitioScope} is_archived = 1")->fetchColumn();
    $makeshiftHh = (int)$db->query("SELECT COUNT(*) FROM households WHERE {$sitioScope} (is_archived = 0 OR is_archived IS NULL) AND house_structure IN ('Makeshift', 'Light Materials / Wood')")->fetchColumn();
    $sanitationAlert = (int)$db->query("SELECT COUNT(*) FROM households WHERE {$sitioScope} (is_archived = 0 OR is_archived IS NULL) AND sanitation_facility IN ('Pit Latrine', 'No Facility', 'Shared / Communal')")->fetchColumn();
    $documentedCount = (int)$db->query("SELECT COUNT(*) FROM households WHERE {$sitioScope} (is_archived = 0 OR is_archived IS NULL) AND bhw_documentation_photo IS NOT NULL AND bhw_documentation_photo != ''")->fetchColumn();
    $missingDocCount = max(0, $totalHh - $documentedCount);
} catch (Exception $e) {
    $totalHh = count($households);
    $archivedHhCount = 0;
    $makeshiftHh = 0;
    $sanitationAlert = 0;
    $documentedCount = 0;
    $missingDocCount = 0;
}
?>

<div class="container-fluid px-0">
    <!-- Header with Action Button -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Household & BHW Registry</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-gray-900">
                <i class="bi bi-houses-fill text-success me-2"></i>Household Profiling & BHW Documentation Hub
            </h3>
            <p class="text-muted small mb-0">
                <?= $role === 'bhw' && $userSitioId ? 'Jurisdiction: <strong>' . htmlspecialchars(SITIOS_LIST[$userSitioId]) . '</strong> &bull; ' : '' ?>
                Manage dwelling structures, sanitation hazards, and visual field survey documentation proof.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- View Mode Switcher -->
            <div class="btn-group shadow-sm me-2" role="group">
                <a href="<?= url('households?' . http_build_query(array_merge($_GET, ['view' => 'table']))) ?>" class="btn btn-sm <?= $viewMode === 'table' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>" title="Tabular Data View">
                    <i class="bi bi-table me-1"></i> Table View
                </a>
                <a href="<?= url('households?' . http_build_query(array_merge($_GET, ['view' => 'cards']))) ?>" class="btn btn-sm <?= $viewMode === 'cards' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>" title="Visual Dossier Cards">
                    <i class="bi bi-grid me-1"></i> Card View
                </a>
            </div>

            <?php if ($role === 'bhw'): ?>
                <a href="<?= url('households/add') ?>" class="btn btn-success shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Household Survey
                </a>
                <a href="<?= url('residents/add') ?>" class="btn btn-outline-success">
                    <i class="bi bi-person-plus me-1"></i> Enroll Resident
                </a>
            <?php endif; ?>
            <?php if (in_array($role, ['captain', 'secretary'])): ?>
                <a href="<?= url('reports/profiling') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i> Report
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active vs Archive Navigation Tabs -->
    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'active' ? 'active bg-success fw-bold' : 'bg-white text-dark border' ?> px-3 py-2 rounded-3" href="<?= url('households?tab=active' . ($filterSitio ? "&sitio=$filterSitio" : '') . ($viewMode ? "&view=$viewMode" : '')) ?>">
                <i class="bi bi-houses-fill me-1"></i> Active Households (<?= number_format($totalHh) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'archived' ? 'active bg-warning text-dark fw-bold' : 'bg-white text-secondary border' ?> px-3 py-2 rounded-3 position-relative" href="<?= url('households?tab=archived' . ($filterSitio ? "&sitio=$filterSitio" : '') . ($viewMode ? "&view=$viewMode" : '')) ?>">
                <i class="bi bi-archive-fill me-1 <?= $activeTab === 'archived' ? 'text-dark' : 'text-warning' ?>"></i> Archived Households
                <span class="badge <?= $activeTab === 'archived' ? 'bg-dark text-white' : 'bg-secondary' ?> ms-1"><?= number_format($archivedHhCount) ?></span>
            </a>
        </li>
    </ul>

    <?php if ($activeTab === 'archived'): ?>
        <div class="alert alert-warning border border-warning shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="fs-3 text-warning"><i class="bi bi-archive-fill"></i></div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Archived Households Repository</h6>
                    <small class="text-muted">Viewing archived dwellings (demolished, relocated, or merged). Archived records are excluded from current census statistics and aid allocations, but preserved for historical integrity.</small>
                </div>
            </div>
            <a href="<?= url('households?tab=active') ?>" class="btn btn-sm btn-dark">
                <i class="bi bi-arrow-left me-1"></i> Return to Active Directory
            </a>
        </div>
    <?php endif; ?>

    <?php if ($activeTab !== 'archived'): ?>
    <!-- Quick Stats Cards with 1-Click Filter Links -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <a href="<?= url('households') ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow transition">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 me-3">
                            <i class="bi bi-houses fs-3"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-gray-900"><?= number_format($totalHh) ?></div>
                            <div class="text-muted small">Total Households</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="<?= url('households?doc=missing' . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow transition <?= ($filterDoc === 'missing') ? 'border-warning border-2' : '' ?>">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 text-warning-emphasis rounded-3 p-3 me-3">
                            <i class="bi bi-camera-video-off fs-3 text-warning"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-warning-emphasis"><?= number_format($missingDocCount) ?></div>
                            <div class="text-muted small">Missing BHW Proof</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="<?= url('households?risk=sanitation' . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow transition <?= ($filterRisk === 'sanitation') ? 'border-danger border-2' : '' ?>">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3 me-3">
                            <i class="bi bi-droplet-half fs-3"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-danger"><?= number_format($sanitationAlert) ?></div>
                            <div class="text-muted small">Sanitation Hazards</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="<?= url('households?doc=documented' . ($filterSitio ? "&sitio=$filterSitio" : '')) ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 hover-shadow transition <?= ($filterDoc === 'documented') ? 'border-info border-2' : '' ?>">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 text-info rounded-3 p-3 me-3">
                            <i class="bi bi-clipboard-check fs-3"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-gray-900"><?= number_format($documentedCount) ?></div>
                            <div class="text-muted small">BHW Documented</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('households') ?>" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="households">
                <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search Household No, Head, Address..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <?php if ($role === 'bhw' && $userSitioId): ?>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted small"><i class="bi bi-geo-alt"></i></span>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(SITIOS_LIST[$userSitioId] ?? 'Assigned Sitio') ?>" readonly>
                            <input type="hidden" name="sitio" value="<?= $userSitioId ?>">
                        </div>
                    <?php else: ?>
                        <select name="sitio" class="form-select">
                            <option value="0">All Sitios (1 - 7)</option>
                            <?php foreach (SITIOS_LIST as $id => $sName): ?>
                                <option value="<?= $id ?>" <?= ($filterSitio == $id) ? 'selected' : '' ?>>
                                    Sitio <?= $id ?> - <?= htmlspecialchars($sName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="col-md-2">
                    <select name="structure" class="form-select">
                        <option value="">All Structures</option>
                        <option value="Concrete" <?= ($structure === 'Concrete') ? 'selected' : '' ?>>Concrete</option>
                        <option value="Semi-Concrete" <?= ($structure === 'Semi-Concrete') ? 'selected' : '' ?>>Semi-Concrete</option>
                        <option value="Light Materials / Wood" <?= ($structure === 'Light Materials / Wood') ? 'selected' : '' ?>>Light / Wood</option>
                        <option value="Makeshift" <?= ($structure === 'Makeshift') ? 'selected' : '' ?>>Makeshift (Hazard)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="sanitation" class="form-select">
                        <option value="">All Sanitation</option>
                        <option value="Water-sealed Toilet" <?= ($sanitation === 'Water-sealed Toilet') ? 'selected' : '' ?>>Water-sealed (Safe)</option>
                        <option value="Pit Latrine" <?= ($sanitation === 'Pit Latrine') ? 'selected' : '' ?>>Pit Latrine</option>
                        <option value="Shared / Communal" <?= ($sanitation === 'Shared / Communal') ? 'selected' : '' ?>>Shared / Communal</option>
                        <option value="No Facility" <?= ($sanitation === 'No Facility') ? 'selected' : '' ?>>No Facility (Alert)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="doc" class="form-select">
                        <option value="">All BHW Status</option>
                        <option value="documented" <?= ($filterDoc === 'documented') ? 'selected' : '' ?>>Field Documented</option>
                        <option value="missing" <?= ($filterDoc === 'missing') ? 'selected' : '' ?>>⚠️ Missing BHW Proof</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100" title="Filter"><i class="bi bi-funnel"></i></button>
                    <?php if (!empty($search) || $filterSitio > 0 || !empty($structure) || !empty($sanitation) || !empty($filterDoc) || !empty($filterRisk)): ?>
                        <a href="<?= url('households?view=' . $viewMode) ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW 1: TABULAR DATA VIEW -->
    <?php if ($viewMode === 'table'): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0">Household Master Records (<?= count($households) ?>)</h5>
                    <span class="badge bg-light text-muted border">Tabular Dataset</span>
                </div>
                <input type="text" class="form-control form-control-sm table-search-input w-auto" data-target-table="householdsTable" placeholder="Instant filter table...">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="householdsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">House & Proof</th>
                            <th>Household No.</th>
                            <th>Sitio & Address</th>
                            <th>Head of Family</th>
                            <th>Dwelling / Sanitation</th>
                            <th>BHW Field Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($households)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-houses display-4 d-block mb-3 text-secondary opacity-50"></i>
                                    <h5>No Household Records Found</h5>
                                    <p class="small mb-3">No households match the selected filter criteria or none have been registered yet.</p>
                                    <a href="<?= url('households/add') ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-plus-circle me-1"></i> Register First Household
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($households as $hh): ?>
                                <tr>
                                    <!-- Dual Thumbnails -->
                                    <td class="ps-4" style="width: 140px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <!-- House Photo Thumbnail -->
                                            <?php if (!empty($hh['house_photo_path']) && file_exists(BASE_PATH . '/' . $hh['house_photo_path'])): ?>
                                                <a href="<?= asset($hh['house_photo_path']) ?>" class="preview-attachment-btn d-inline-block" data-preview-title="Dwelling Photo" data-preview-subtitle="Household #<?= htmlspecialchars($hh['household_no']) ?> &bull; Sitio <?= $hh['sitio_id'] ?>: <?= htmlspecialchars($hh['sitio_name']) ?>" title="Click to view dwelling photo in modal">
                                                    <img src="<?= asset($hh['house_photo_path']) ?>" alt="House" class="rounded-2 shadow-sm border" style="width: 48px; height: 48px; object-fit: cover; cursor: pointer;">
                                                </a>
                                            <?php else: ?>
                                                <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;" title="No House Photo">
                                                    <i class="bi bi-house fs-5"></i>
                                                </div>
                                            <?php endif; ?>

                                            <!-- BHW Doc Thumbnail -->
                                            <?php if (!empty($hh['bhw_documentation_photo']) && file_exists(BASE_PATH . '/' . $hh['bhw_documentation_photo'])): ?>
                                                <a href="<?= asset($hh['bhw_documentation_photo']) ?>" class="preview-attachment-btn d-inline-block" data-preview-title="BHW Duty Survey Documentation Proof" data-preview-subtitle="Household #<?= htmlspecialchars($hh['household_no']) ?> &bull; Assessed by <?= htmlspecialchars($hh['bhw_name'] ?? 'BHW') ?>" title="Click to view BHW field proof in modal">
                                                    <img src="<?= asset($hh['bhw_documentation_photo']) ?>" alt="BHW Doc" class="rounded-2 shadow-sm border border-success" style="width: 48px; height: 48px; object-fit: cover; cursor: pointer;">
                                                </a>
                                            <?php else: ?>
                                                <div class="rounded-2 bg-warning bg-opacity-10 border border-warning text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" title="Missing BHW Field Proof">
                                                    <i class="bi bi-camera-video-off fs-5"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Household No & Registered Members -->
                                    <td>
                                        <div class="fw-bold text-dark font-monospace">
                                            <a href="<?= url('households/view?id=' . $hh['id']) ?>" class="text-decoration-none text-dark hover-underline">
                                                <?= htmlspecialchars($hh['household_no']) ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="bi bi-people-fill me-1 text-primary"></i><?= (int)$hh['registered_residents_count'] ?> registered
                                            <span class="text-secondary">(<?= (int)$hh['total_members'] ?> total)</span>
                                        </div>
                                    </td>

                                    <!-- Sitio & Address -->
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold mb-1">
                                            Sitio <?= $hh['sitio_id'] ?>: <?= htmlspecialchars($hh['sitio_name']) ?>
                                        </span>
                                        <div class="small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($hh['address_line']) ?>">
                                            <?= htmlspecialchars($hh['address_line']) ?>
                                        </div>
                                    </td>

                                    <!-- Head of Family -->
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <?= !empty($hh['head_resident_name']) ? htmlspecialchars($hh['head_resident_name']) : '<span class="text-muted fst-italic">Not specified</span>' ?>
                                        </div>
                                        <?php if ($hh['electricity_access']): ?>
                                            <span class="badge bg-warning bg-opacity-25 text-dark small" style="font-size: 0.7rem;"><i class="bi bi-lightning-fill"></i> Power OK</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger small" style="font-size: 0.7rem;"><i class="bi bi-plug"></i> No Power</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Dwelling / Sanitation -->
                                    <td>
                                        <?php if (!empty($hh['is_archived'])): ?>
                                            <span class="badge bg-secondary mb-1"><i class="bi bi-archive-fill me-1"></i>Archived</span>
                                            <div class="small text-danger fw-semibold"><?= htmlspecialchars($hh['archived_reason'] ?: 'Archived') ?></div>
                                            <div class="small text-muted" style="font-size:0.72rem;"><?= format_date($hh['archived_at']) ?><?= !empty($hh['archiver_name']) ? ' by ' . htmlspecialchars($hh['archiver_name']) : '' ?></div>
                                        <?php else: ?>
                                            <div>
                                                <?php if ($hh['house_structure'] === 'Concrete'): ?>
                                                    <span class="badge bg-secondary"><?= $hh['house_structure'] ?></span>
                                                <?php elseif ($hh['house_structure'] === 'Semi-Concrete'): ?>
                                                    <span class="badge bg-info text-dark"><?= $hh['house_structure'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><?= $hh['house_structure'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small mt-1 <?= in_array($hh['sanitation_facility'], ['Pit Latrine', 'No Facility']) ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                                <i class="bi bi-droplet me-1"></i><?= htmlspecialchars($hh['sanitation_facility']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- BHW Documentation Status -->
                                    <td>
                                        <?php if (!empty($hh['bhw_documentation_photo'])): ?>
                                            <span class="badge bg-success text-white small">
                                                <i class="bi bi-check-circle-fill me-1"></i> Documented
                                            </span>
                                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                                By <?= htmlspecialchars($hh['bhw_name'] ?? 'BHW') ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark small">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Needs Proof
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url('households/view?id=' . $hh['id']) ?>" class="btn btn-outline-primary" title="View Household Dossier">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if ($activeTab === 'archived'): ?>
                                                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Restore Household #<?= htmlspecialchars($hh['household_no']) ?> back to active directory?');">
                                                        <input type="hidden" name="action" value="restore">
                                                        <input type="hidden" name="household_id" value="<?= $hh['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-success" title="Restore Household">
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-delete-hh" title="Permanently Delete Household"
                                                            data-bs-toggle="modal" data-bs-target="#deleteHouseholdModal"
                                                            data-hh-id="<?= $hh['id'] ?>" data-hh-no="<?= htmlspecialchars($hh['household_no']) ?>" data-hh-head="<?= htmlspecialchars($hh['head_resident_name'] ?: 'Not listed') ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($role === 'bhw'): ?>
                                                    <a href="<?= url('residents/add?household_no=' . urlencode($hh['household_no']) . '&sitio_id=' . $hh['sitio_id']) ?>" class="btn btn-outline-success" title="Enroll Member to this Household">
                                                        <i class="bi bi-person-plus"></i> Member
                                                    </a>
                                                    <a href="<?= url('households/edit?id=' . $hh['id']) ?>" class="btn btn-outline-secondary" title="Edit Household / Upload Photos">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                                    <button type="button" class="btn btn-outline-warning text-dark btn-archive-hh" title="Archive Household"
                                                            data-bs-toggle="modal" data-bs-target="#archiveHouseholdModal"
                                                            data-hh-id="<?= $hh['id'] ?>" data-hh-no="<?= htmlspecialchars($hh['household_no']) ?>" data-hh-head="<?= htmlspecialchars($hh['head_resident_name'] ?: 'Not listed') ?>">
                                                        <i class="bi bi-archive"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-delete-hh" title="Permanently Delete Household"
                                                            data-bs-toggle="modal" data-bs-target="#deleteHouseholdModal"
                                                            data-hh-id="<?= $hh['id'] ?>" data-hh-no="<?= htmlspecialchars($hh['household_no']) ?>" data-hh-head="<?= htmlspecialchars($hh['head_resident_name'] ?: 'Not listed') ?>">
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
            <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between">
                <span class="text-muted small">Showing <strong><?= count($households) ?></strong> household records</span>
                <span class="text-muted small">Barangay Socio-Economic Profiling Database &bull; Brgy. Lublub</span>
            </div>
        </div>

    <!-- VIEW 2: VISUAL CARD / GRID VIEW -->
    <?php else: ?>
        <div class="row g-4 mb-4">
            <?php if (empty($households)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-houses display-4 d-block mb-3 text-secondary opacity-50"></i>
                    <h5>No Household Records Found</h5>
                    <p class="small mb-3">No households match your filter criteria.</p>
                    <a href="<?= url('households/add') ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-plus-circle me-1"></i> Register First Household
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($households as $hh): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden hover-shadow transition">
                            <!-- Visual Header: House Photo & Status Overlay -->
                            <div class="position-relative bg-light" style="height: 180px;">
                                <?php if (!empty($hh['house_photo_path']) && file_exists(BASE_PATH . '/' . $hh['house_photo_path'])): ?>
                                    <a href="<?= asset($hh['house_photo_path']) ?>" class="preview-attachment-btn d-block w-100 h-100" data-preview-title="Dwelling Exterior Photo" data-preview-subtitle="Household #<?= htmlspecialchars($hh['household_no']) ?> &bull; Sitio <?= $hh['sitio_id'] ?>: <?= htmlspecialchars($hh['sitio_name']) ?>" title="Click to enlarge in modal">
                                        <img src="<?= asset($hh['house_photo_path']) ?>" alt="House Photo" class="w-100 h-100 object-fit-cover" style="cursor: pointer;">
                                    </a>
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted bg-light">
                                        <i class="bi bi-house fs-1 opacity-50 mb-1"></i>
                                        <span class="small">No House Photo Uploaded</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Sitio & Household No Badges -->
                                <div class="position-absolute top-0 start-0 m-3 d-flex flex-column gap-1">
                                    <span class="badge bg-dark bg-opacity-75 font-monospace text-white px-2 py-1">
                                        <?= htmlspecialchars($hh['household_no']) ?>
                                    </span>
                                    <span class="badge bg-success text-white px-2 py-1">
                                        Sitio <?= $hh['sitio_id'] ?>: <?= htmlspecialchars($hh['sitio_name']) ?>
                                    </span>
                                </div>

                                <!-- BHW Documentation / Archived Badge -->
                                <div class="position-absolute top-0 end-0 m-3">
                                    <?php if (!empty($hh['is_archived'])): ?>
                                        <span class="badge bg-secondary shadow-sm px-2 py-1">
                                            <i class="bi bi-archive-fill me-1"></i> Archived
                                        </span>
                                    <?php elseif (!empty($hh['bhw_documentation_photo'])): ?>
                                        <span class="badge bg-success shadow-sm px-2 py-1">
                                            <i class="bi bi-shield-check me-1"></i> Field Documented
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark shadow-sm px-2 py-1">
                                            <i class="bi bi-camera-video-off me-1"></i> Missing Proof
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body p-4">
                                <?php if (!empty($hh['is_archived'])): ?>
                                    <div class="alert alert-warning py-2 px-3 rounded-3 small mb-2">
                                        <i class="bi bi-archive me-1"></i> <strong>Archived:</strong> <?= htmlspecialchars($hh['archived_reason'] ?: 'Archived') ?>
                                        <div class="text-muted" style="font-size:0.7rem;"><?= format_date($hh['archived_at']) ?><?= !empty($hh['archiver_name']) ? ' by ' . htmlspecialchars($hh['archiver_name']) : '' ?></div>
                                    </div>
                                <?php endif; ?>

                                <h5 class="fw-bold text-dark mb-1">
                                    <?= !empty($hh['head_resident_name']) ? htmlspecialchars($hh['head_resident_name']) : '<span class="text-muted fst-italic">Head Not Listed</span>' ?>
                                </h5>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($hh['address_line']) ?>
                                </p>

                                <div class="row g-2 mb-3 small">
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded-3">
                                            <span class="text-muted d-block" style="font-size: 0.72rem;">STRUCTURE</span>
                                            <strong class="text-dark"><?= htmlspecialchars($hh['house_structure']) ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded-3">
                                            <span class="text-muted d-block" style="font-size: 0.72rem;">SANITATION</span>
                                            <strong class="<?= in_array($hh['sanitation_facility'], ['Pit Latrine', 'No Facility']) ? 'text-danger' : 'text-dark' ?>">
                                                <?= htmlspecialchars($hh['sanitation_facility']) ?>
                                            </strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded-3">
                                            <span class="text-muted d-block" style="font-size: 0.72rem;">WATER SOURCE</span>
                                            <span class="text-truncate d-block fw-semibold text-dark"><?= htmlspecialchars($hh['water_source']) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded-3">
                                            <span class="text-muted d-block" style="font-size: 0.72rem;">POWER & MEMBERS</span>
                                            <span class="fw-semibold text-dark">
                                                <?= $hh['electricity_access'] ? '⚡ Connected' : '❌ No Power' ?> &bull; <?= (int)$hh['registered_residents_count'] ?>/<?= (int)$hh['total_members'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($hh['bhw_notes'])): ?>
                                    <div class="p-2 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 mb-3 small text-muted">
                                        <i class="bi bi-chat-left-dots-fill text-success me-1"></i>
                                        <span class="fst-italic text-dark"><?= htmlspecialchars(mb_strimwidth($hh['bhw_notes'], 0, 80, '...')) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Card Footer -->
                            <div class="card-footer bg-white border-top p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <a href="<?= url('households/view?id=' . $hh['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View Dossier
                                </a>
                                <div class="d-flex gap-1">
                                    <?php if ($activeTab === 'archived'): ?>
                                        <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Restore Household #<?= htmlspecialchars($hh['household_no']) ?> back to active directory?');">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="household_id" value="<?= $hh['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-hh"
                                                    data-bs-toggle="modal" data-bs-target="#deleteHouseholdModal"
                                                    data-hh-id="<?= $hh['id'] ?>" data-hh-no="<?= htmlspecialchars($hh['household_no']) ?>" data-hh-head="<?= htmlspecialchars($hh['head_resident_name'] ?: 'Not listed') ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($role === 'bhw'): ?>
                                            <a href="<?= url('residents/add?household_no=' . urlencode($hh['household_no']) . '&sitio_id=' . $hh['sitio_id']) ?>" class="btn btn-sm btn-success" title="Enroll Member">
                                                <i class="bi bi-person-plus-fill me-1"></i> Member
                                            </a>
                                            <a href="<?= url('households/edit?id=' . $hh['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning text-dark btn-archive-hh" title="Archive"
                                                    data-bs-toggle="modal" data-bs-target="#archiveHouseholdModal"
                                                    data-hh-id="<?= $hh['id'] ?>" data-hh-no="<?= htmlspecialchars($hh['household_no']) ?>" data-hh-head="<?= htmlspecialchars($hh['head_resident_name'] ?: 'Not listed') ?>">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array($role, ['captain', 'secretary'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-hh" title="Delete"
                                                    data-bs-toggle="modal" data-bs-target="#deleteHouseholdModal"
                                                    data-hh-id="<?= $hh['id'] ?>" data-hh-no="<?= htmlspecialchars($hh['household_no']) ?>" data-hh-head="<?= htmlspecialchars($hh['head_resident_name'] ?: 'Not listed') ?>">
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
</div>

<?php if (in_array($role, ['captain', 'secretary'])): ?>
    <!-- Archive Household Modal -->
    <div class="modal fade" id="archiveHouseholdModal" tabindex="-1" aria-labelledby="archiveHouseholdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="archive">
                    <input type="hidden" name="household_id" id="archive_hh_id" value="">
                    <div class="modal-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 py-3">
                        <h5 class="modal-title fw-bold text-dark" id="archiveHouseholdModalLabel">
                            <i class="bi bi-archive-fill text-warning me-2"></i>Archive Household Record
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted">
                            You are about to archive household <strong id="archive_hh_no_label">#0000</strong> (<span id="archive_hh_head_label">Family</span>).
                            Archiving safely moves the household out of active profiling counts while preserving historical socio-economic records and resident relations.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for Archiving <span class="text-danger">*</span></label>
                            <select name="archive_reason" class="form-select" id="hh_archive_reason_select" required onchange="toggleHhCustomReason(this)">
                                <option value="Demolished / Vacant Structure">Demolished / Vacant Structure</option>
                                <option value="Family Relocated Out of Barangay">Family Relocated Out of Barangay</option>
                                <option value="Merged with Another Household">Merged with Another Household</option>
                                <option value="Duplicate / Erroneous Entry">Duplicate / Erroneous Entry</option>
                                <option value="Non-Resident / Invalid">Non-Resident / Invalid</option>
                                <option value="Other">Other (Specify)</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="hh_custom_reason_group">
                            <label class="form-label fw-semibold">Specify Custom Reason <span class="text-danger">*</span></label>
                            <input type="text" name="custom_archive_reason" id="hh_custom_archive_reason" class="form-control" placeholder="Provide specific reason...">
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark">
                            <i class="bi bi-archive-fill me-1"></i> Confirm Archive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (in_array($role, ['captain', 'secretary'])): ?>
    <!-- Permanent Delete Household Modal -->
    <div class="modal fade" id="deleteHouseholdModal" tabindex="-1" aria-labelledby="deleteHouseholdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="household_id" id="delete_hh_id" value="">
                    <div class="modal-header bg-danger text-white py-3">
                        <h5 class="modal-title fw-bold" id="deleteHouseholdModalLabel">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Permanently Delete Household
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="text-danger display-4 mb-3"><i class="bi bi-trash-fill"></i></div>
                        <h5 class="fw-bold text-dark mb-2">Delete Household <span id="delete_hh_no_label"></span>?</h5>
                        <p class="text-muted small mb-3">
                            Head of Family: <strong id="delete_hh_head_label"></strong><br>
                            This will permanently erase the dwelling record and BHW survey documentation. Any linked residents will have their household reference cleared. This action <strong>cannot be undone</strong>.
                        </p>
                        <div class="alert alert-danger py-2 small text-start">
                            <i class="bi bi-shield-lock-fill me-1"></i> Requires Captain / Secretary authorization.
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger fw-bold">
                            <i class="bi bi-trash-fill me-1"></i> Yes, Delete Permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleHhCustomReason(sel) {
    const grp = document.getElementById('hh_custom_reason_group');
    const inp = document.getElementById('hh_custom_archive_reason');
    if (!grp || !inp) return;
    if (sel.value === 'Other') {
        grp.classList.remove('d-none');
        inp.required = true;
    } else {
        grp.classList.add('d-none');
        inp.required = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Populate Archive Modal
    document.querySelectorAll('.btn-archive-hh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-hh-id');
            const no = this.getAttribute('data-hh-no');
            const head = this.getAttribute('data-hh-head');
            
            const idInput = document.getElementById('archive_hh_id');
            const noLabel = document.getElementById('archive_hh_no_label');
            const headLabel = document.getElementById('archive_hh_head_label');
            
            if (idInput) idInput.value = id;
            if (noLabel) noLabel.textContent = '#' + no;
            if (headLabel) headLabel.textContent = head;
        });
    });

    // Populate Delete Modal
    document.querySelectorAll('.btn-delete-hh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-hh-id');
            const no = this.getAttribute('data-hh-no');
            const head = this.getAttribute('data-hh-head');
            
            const idInput = document.getElementById('delete_hh_id');
            const noLabel = document.getElementById('delete_hh_no_label');
            const headLabel = document.getElementById('delete_hh_head_label');
            
            if (idInput) idInput.value = id;
            if (noLabel) noLabel.textContent = '#' + no;
            if (headLabel) headLabel.textContent = head;
        });
    });
});
</script>
