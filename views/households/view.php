<?php
// views/households/view.php - Comprehensive Household Profile Dossier & BHW Documentation
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'treasurer', 'bhw', 'resident');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$householdNoParam = clean($_GET['no'] ?? '');

if ($id > 0) {
    $stmt = $db->prepare("
        SELECT h.*, s.name AS sitio_name, s.code AS sitio_code, u.full_name AS bhw_name, u.role AS bhw_role, arch.full_name AS archiver_name
        FROM households h
        JOIN sitios s ON h.sitio_id = s.id
        LEFT JOIN users u ON h.bhw_assessed_by_user_id = u.id
        LEFT JOIN users arch ON h.archived_by_user_id = arch.id
        WHERE h.id = ?
    ");
    $stmt->execute([$id]);
} elseif (!empty($householdNoParam)) {
    $stmt = $db->prepare("
        SELECT h.*, s.name AS sitio_name, s.code AS sitio_code, u.full_name AS bhw_name, u.role AS bhw_role, arch.full_name AS archiver_name
        FROM households h
        JOIN sitios s ON h.sitio_id = s.id
        LEFT JOIN users u ON h.bhw_assessed_by_user_id = u.id
        LEFT JOIN users arch ON h.archived_by_user_id = arch.id
        WHERE h.household_no = ?
    ");
    $stmt->execute([$householdNoParam]);
} else {
    set_flash('error', 'No household selected.');
    redirect(url('households'));
}

$hh = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hh) {
    set_flash('error', 'Household record not found.');
    redirect(url('households'));
}

// Security: If BHW, must match Sitio
if ($role === 'bhw' && $userSitioId && $hh['sitio_id'] != $userSitioId) {
    set_flash('error', 'Access restricted to your assigned Sitio.');
    redirect(url('households'));
}

// Handle Household Archive, Restore, and Delete from View
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);

    if ($role === 'bhw') {
        set_flash('danger', 'BHW role has view-only permissions for household archiving and deletion.');
        redirect('households/view?id=' . $hh['id']);
    }

    if ($action === 'archive_household') {
        $reason = clean($_POST['archive_reason'] ?? 'Family Relocated Out of Barangay');
        if ($reason === 'Other' && !empty($_POST['custom_archive_reason'])) {
            $reason = clean($_POST['custom_archive_reason']);
        }
        $db->prepare("UPDATE households SET is_archived = 1, archived_at = NOW(), archived_reason = ?, archived_by_user_id = ? WHERE id = ?")
           ->execute([$reason, $user['id'], $hh['id']]);

        log_activity('Archive Household', "Household #{$hh['household_no']} ({$hh['head_resident_name']}) archived. Reason: {$reason}");
        set_flash('warning', "Household #{$hh['household_no']} moved to Archives (Reason: {$reason}).");
        redirect('households/view?id=' . $hh['id']);

    } elseif ($action === 'restore_household') {
        $db->prepare("UPDATE households SET is_archived = 0, archived_at = NULL, archived_reason = NULL, archived_by_user_id = NULL WHERE id = ?")
           ->execute([$hh['id']]);

        log_activity('Restore Household', "Household #{$hh['household_no']} restored to active directory.");
        set_flash('success', "Household #{$hh['household_no']} has been restored to the Active Directory.");
        redirect('households/view?id=' . $hh['id']);

    } elseif ($action === 'delete_household') {
        if (!in_array($role, ['captain', 'secretary'])) {
            set_flash('danger', 'Permanent deletion requires Barangay Captain or Secretary authorization.');
            redirect('households/view?id=' . $hh['id']);
        }

        try {
            $db->beginTransaction();
            $db->prepare("UPDATE residents SET household_no = NULL WHERE household_no = ?")->execute([$hh['household_no']]);
            $db->prepare("DELETE FROM households WHERE id = ?")->execute([$hh['id']]);
            $db->commit();

            log_activity('Delete Household', "Permanently deleted Household #{$hh['household_no']} ({$hh['head_resident_name']}).");
            set_flash('danger', "Household #{$hh['household_no']} has been permanently deleted.");
            redirect('households');
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Failed to delete household: ' . $e->getMessage());
            redirect('households/view?id=' . $hh['id']);
        }
    }
}

// Fetch residents registered under this household_no
$resStmt = $db->prepare("
    SELECT r.*, s.name AS sitio_name
    FROM residents r
    JOIN sitios s ON r.sitio_id = s.id
    WHERE r.household_no = ?
    ORDER BY r.is_head_of_family DESC, r.age DESC
");
$resStmt->execute([$hh['household_no']]);
$members = $resStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Household Dossier - ' . htmlspecialchars($hh['household_no']);
?>

<div class="container-fluid px-0">
    <!-- Header Navigation -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('households') ?>">Households</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($hh['household_no']) ?></li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold mb-0 text-gray-900"><?= htmlspecialchars($hh['household_no']) ?></h3>
                <span class="badge bg-success bg-opacity-10 text-success fs-6 fw-semibold">
                    Sitio <?= $hh['sitio_id'] ?>: <?= htmlspecialchars($hh['sitio_name']) ?>
                </span>
                <?php if (!empty($hh['is_archived'])): ?>
                    <span class="badge bg-secondary fs-6"><i class="bi bi-archive-fill me-1"></i>Archived</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($hh['address_line']) ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($role === 'bhw'): ?>
                <a href="<?= url('households/edit?id=' . $hh['id']) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Edit Details / Photos
                </a>
                <a href="<?= url('residents/add?household_no=' . urlencode($hh['household_no']) . '&sitio_id=' . $hh['sitio_id']) ?>" class="btn btn-success shadow-sm">
                    <i class="bi bi-person-plus-fill me-1"></i> Enroll Family Member
                </a>
            <?php endif; ?>

            <?php if (in_array($role, ['captain', 'secretary'])): ?>
                <?php if (!empty($hh['is_archived'])): ?>
                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Restore Household #<?= htmlspecialchars($hh['household_no']) ?> back to active directory?');">
                        <input type="hidden" name="action" value="restore_household">
                        <button type="submit" class="btn btn-outline-success">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-warning text-dark" data-bs-toggle="modal" data-bs-target="#archiveHouseholdModal">
                        <i class="bi bi-archive me-1"></i> Archive
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (in_array($role, ['captain', 'secretary'])): ?>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteHouseholdModal">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            <?php endif; ?>

            <a href="<?= url('households') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Archived Alert Banner if Archived -->
    <?php if (!empty($hh['is_archived'])): ?>
        <div class="alert alert-secondary border border-secondary shadow-sm p-4 rounded-4 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-archive-fill text-secondary me-2"></i>Archived Household Record</h5>
                    <p class="mb-1 text-muted">
                        This household was archived on <strong><?= format_datetime($hh['archived_at']) ?></strong>
                        <?php if (!empty($hh['archiver_name'])): ?> by <strong><?= htmlspecialchars($hh['archiver_name']) ?></strong><?php endif; ?>.
                    </p>
                    <div class="badge bg-light text-dark border px-3 py-2 fs-6">
                        <strong>Reason:</strong> <?= htmlspecialchars($hh['archived_reason'] ?: 'Family Relocated / Demolished') ?>
                    </div>
                </div>
                <?php if (in_array($role, ['captain', 'secretary'])): ?>
                    <form action="" method="POST" onsubmit="return confirm('Restore Household #<?= htmlspecialchars($hh['household_no']) ?> back to active directory?');">
                        <input type="hidden" name="action" value="restore_household">
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restore to Active Directory
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Row -->
    <div class="row g-4 mb-4">
        <!-- Left: Dual Photos & BHW Documentation -->
        <div class="col-lg-5">
            <!-- House Dwelling Photo Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-camera-fill me-2"></i>Dwelling Exterior Photo
                    </h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary">Dwelling Proof</span>
                </div>
                <div class="card-body p-3 text-center bg-light">
                    <?php if (!empty($hh['house_photo_path']) && file_exists(__DIR__ . '/../../' . $hh['house_photo_path'])): ?>
                        <a href="<?= asset($hh['house_photo_path']) ?>" class="preview-attachment-btn d-block position-relative group" data-preview-title="Dwelling Exterior Photo" data-preview-subtitle="Household #<?= htmlspecialchars($hh['household_no']) ?> &bull; Sitio <?= $hh['sitio_id'] ?>: <?= htmlspecialchars($hh['sitio_name']) ?>">
                            <img src="<?= asset($hh['house_photo_path']) ?>" alt="House Photo" class="img-fluid rounded-3 shadow-sm" style="max-height: 280px; width: 100%; object-fit: cover;">
                            <div class="position-absolute bottom-0 end-0 m-2 badge bg-dark bg-opacity-75 text-white">
                                <i class="bi bi-arrows-fullscreen me-1"></i>Modal Preview
                            </div>
                        </a>
                        <div class="mt-2 text-muted small"><i class="bi bi-eye-fill text-primary me-1"></i>Click photo to view modalized in high resolution</div>
                    <?php else: ?>
                        <div class="py-5 text-muted">
                            <i class="bi bi-house display-1 text-secondary opacity-50 mb-2"></i>
                            <div>No Dwelling Photograph Uploaded Yet</div>
                            <?php if (in_array($role, ['captain', 'secretary', 'bhw'])): ?>
                                <a href="<?= url('households/edit?id=' . $hh['id']) ?>" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="bi bi-upload me-1"></i> Upload House Photo
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- BHW Field Documentation Proof Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-success">
                        <i class="bi bi-clipboard2-check-fill me-2"></i>BHW Duty Field Documentation
                    </h6>
                    <span class="badge bg-success bg-opacity-10 text-success">Survey Proof</span>
                </div>
                <div class="card-body p-3 text-center bg-light">
                    <?php if (!empty($hh['bhw_documentation_photo']) && file_exists(__DIR__ . '/../../' . $hh['bhw_documentation_photo'])): ?>
                        <a href="<?= asset($hh['bhw_documentation_photo']) ?>" class="preview-attachment-btn d-block position-relative" data-preview-title="BHW Duty Survey Documentation Proof" data-preview-subtitle="Assessed by: <?= htmlspecialchars($hh['bhw_name'] ?? 'BHW') ?> &bull; Household #<?= htmlspecialchars($hh['household_no']) ?>">
                            <img src="<?= asset($hh['bhw_documentation_photo']) ?>" alt="BHW Documentation Photo" class="img-fluid rounded-3 shadow-sm border border-success" style="max-height: 280px; width: 100%; object-fit: cover;">
                            <div class="position-absolute bottom-0 end-0 m-2 badge bg-dark bg-opacity-75 text-white">
                                <i class="bi bi-arrows-fullscreen me-1"></i>Modal Preview
                            </div>
                        </a>
                        <div class="mt-2 text-muted small"><i class="bi bi-eye-fill text-success me-1"></i>Click photo to view survey proof in modal</div>
                    <?php else: ?>
                        <div class="py-5 text-muted">
                            <i class="bi bi-clipboard-x display-1 text-secondary opacity-50 mb-2"></i>
                            <div>No BHW Field Visitation Proof Uploaded</div>
                            <?php if (in_array($role, ['captain', 'secretary', 'bhw'])): ?>
                                <a href="<?= url('households/edit?id=' . $hh['id']) ?>" class="btn btn-sm btn-outline-success mt-2">
                                    <i class="bi bi-upload me-1"></i> Upload BHW Proof
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- BHW Field Details Footer -->
                <div class="card-footer bg-white p-3 border-top">
                    <div class="d-flex align-items-center">
                        <div class="bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-person-badge fs-5"></i>
                        </div>
                        <div class="small flex-grow-1">
                            <div class="fw-bold text-dark">Survey Assessor: <?= htmlspecialchars($hh['bhw_name'] ?? 'BHW Assigned') ?></div>
                            <div class="text-muted">
                                Assessed Date: <?= !empty($hh['bhw_assessment_date']) ? date('M d, Y h:i A', strtotime($hh['bhw_assessment_date'])) : 'Pending Assessment' ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($hh['bhw_notes'])): ?>
                        <div class="mt-3 p-3 bg-light rounded-3 border">
                            <div class="fw-semibold text-secondary small mb-1"><i class="bi bi-chat-left-quote-fill me-1"></i>BHW Duty Notes & Living Conditions:</div>
                            <p class="mb-0 small text-dark"><?= nl2br(htmlspecialchars($hh['bhw_notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Socio-Environmental Profiling & Registered Members -->
        <div class="col-lg-7">
            <!-- Household Socio-Environmental Characteristics Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-success">
                        <i class="bi bi-building me-2"></i>Household Dwelling & Socio-Environmental Profile
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="text-muted small fw-semibold">Head of Household</div>
                                <div class="fs-6 fw-bold text-dark mt-1 d-flex align-items-center flex-wrap gap-2">
                                    <?php if (!empty($hh['head_resident_name'])): ?>
                                        <i class="bi bi-person-badge-fill text-primary"></i>
                                        <span><?= htmlspecialchars($hh['head_resident_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Not yet designated</span>
                                        <?php if (in_array($role, ['captain', 'secretary', 'bhw'])): ?>
                                            <a href="<?= url('residents/add?household_no=' . urlencode($hh['household_no']) . '&sitio_id=' . $hh['sitio_id'] . '&is_head=1') ?>" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                                <i class="bi bi-plus-circle me-1"></i>Designate Head
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-semibold">Total Household Size</div>
                                <div class="fs-6 fw-bold text-dark mt-1">
                                    <?= (int)$hh['total_members'] ?> Family Members
                                    <span class="badge bg-primary bg-opacity-10 text-primary ms-1"><?= count($members) ?> Enrolled</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-semibold">Housing Structure / Walls</div>
                                <div class="fs-6 fw-bold text-dark mt-1">
                                    <?php if ($hh['house_structure'] === 'Concrete'): ?>
                                        <span class="badge bg-secondary"><?= $hh['house_structure'] ?></span>
                                    <?php elseif ($hh['house_structure'] === 'Semi-Concrete'): ?>
                                        <span class="badge bg-info text-dark"><?= $hh['house_structure'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i><?= $hh['house_structure'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-semibold">Sanitation / Toilet Facility</div>
                                <div class="fs-6 fw-bold text-dark mt-1">
                                    <?php if ($hh['sanitation_facility'] === 'Water-sealed Toilet'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Water-sealed</span>
                                    <?php elseif ($hh['sanitation_facility'] === 'No Facility'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>No Facility / High Risk</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><?= $hh['sanitation_facility'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-semibold">Drinking Water Source</div>
                                <div class="fs-6 fw-bold text-dark mt-1">
                                    <i class="bi bi-droplet-fill text-info me-1"></i><?= htmlspecialchars($hh['water_source']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-semibold">Electricity Service (ILECO)</div>
                                <div class="fs-6 fw-bold text-dark mt-1">
                                    <?php if ($hh['electricity_access']): ?>
                                        <span class="badge bg-success bg-opacity-25 text-success"><i class="bi bi-lightning-charge-fill me-1"></i>Connected</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger"><i class="bi bi-lightning-slash me-1"></i>No Connection</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-semibold">Waste Disposal / Management</div>
                                <div class="fs-6 fw-bold text-dark mt-1">
                                    <i class="bi bi-recycle text-success me-1"></i><?= htmlspecialchars($hh['waste_management']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registered Family Members Under this Household -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-people-fill me-2"></i>Enrolled Family Members (<?= count($members) ?>)
                        </h6>
                        <small class="text-muted">Residents recorded in barangay database under Household #<?= htmlspecialchars($hh['household_no']) ?></small>
                    </div>
                    <?php if (in_array($role, ['captain', 'secretary', 'bhw'])): ?>
                        <a href="<?= url('residents/add?household_no=' . urlencode($hh['household_no']) . '&sitio_id=' . $hh['sitio_id']) ?>" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-person-plus-fill me-1"></i> Add Member
                        </a>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Resident</th>
                                <th>Role / Age</th>
                                <th>Vulnerabilities & Social Flags</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-person-x display-6 d-block mb-2 text-secondary opacity-50"></i>
                                        No resident profiles currently linked to this household number.
                                        <?php if (in_array($role, ['captain', 'secretary', 'bhw'])): ?>
                                            <div class="mt-2">
                                                <a href="<?= url('residents/add?household_no=' . urlencode($hh['household_no']) . '&sitio_id=' . $hh['sitio_id']) ?>" class="btn btn-sm btn-success">
                                                    <i class="bi bi-person-plus-fill me-1"></i> Enroll First Member
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($members as $m): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 36px; height: 36px;">
                                                    <?= strtoupper(substr($m['first_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">
                                                        <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?>
                                                        <?php if ($m['is_head_of_family']): ?>
                                                            <span class="badge bg-primary" style="font-size: 0.68rem;"><i class="bi bi-star-fill me-1"></i>Family Head</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="font-monospace text-muted small"><?= htmlspecialchars($m['resident_code']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark">
                                                <?= htmlspecialchars($m['family_role'] ?? 'Member') ?>
                                                <?php if (!$m['is_head_of_family'] && !empty($hh['head_resident_name'])): ?>
                                                    <span class="text-muted" style="font-size: 0.72rem;">(to Head)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted"><?= (int)$m['age'] ?> yrs &bull; <?= $m['gender'] ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php if ($m['is_senior']): ?><span class="badge bg-secondary" style="font-size: 0.68rem;">Senior</span><?php endif; ?>
                                                <?php if ($m['is_pwd']): ?><span class="badge bg-danger" style="font-size: 0.68rem;">PWD</span><?php endif; ?>
                                                <?php if ($m['is_solo_parent']): ?><span class="badge bg-info text-dark" style="font-size: 0.68rem;">Solo Parent</span><?php endif; ?>
                                                <?php if ($m['is_indigent']): ?><span class="badge bg-warning text-dark" style="font-size: 0.68rem;">Indigent</span><?php endif; ?>
                                                <?php if ($m['is_4ps']): ?><span class="badge bg-success" style="font-size: 0.68rem;">4Ps</span><?php endif; ?>
                                                <?php if (!empty($m['is_student'])): ?><span class="badge bg-primary" style="font-size: 0.68rem;">Student</span><?php endif; ?>
                                                <?php if ($m['is_voter']): ?><span class="badge bg-light text-dark border" style="font-size: 0.68rem;">Voter</span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="<?= url('residents/view?id=' . $m['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Resident Profile">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (in_array($role, ['captain', 'secretary'])): ?>
    <!-- Archive Household Modal -->
    <div class="modal fade" id="archiveHouseholdModal" tabindex="-1" aria-labelledby="archiveHouseholdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form action="" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <input type="hidden" name="action" value="archive_household">
                <div class="modal-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 py-3 px-4 flex-shrink-0">
                    <h5 class="modal-title fw-bold text-dark" id="archiveHouseholdModalLabel">
                        <i class="bi bi-archive-fill text-warning me-2"></i>Archive Household Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <p class="text-muted">
                        You are about to archive household <strong>#<?= htmlspecialchars($hh['household_no']) ?></strong>
                        <?= !empty($hh['head_resident_name']) ? '(' . htmlspecialchars($hh['head_resident_name']) . ')' : '' ?>.
                        Archiving safely moves the household out of active profiling counts while preserving historical socio-economic records and resident relations.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Archiving <span class="text-danger">*</span></label>
                        <select name="archive_reason" class="form-select" id="hh_view_archive_reason_select" required onchange="toggleHhViewCustomReason(this)">
                            <option value="Demolished / Vacant Structure">Demolished / Vacant Structure</option>
                            <option value="Family Relocated Out of Barangay">Family Relocated Out of Barangay</option>
                            <option value="Merged with Another Household">Merged with Another Household</option>
                            <option value="Duplicate / Erroneous Entry">Duplicate / Erroneous Entry</option>
                            <option value="Non-Resident / Invalid">Non-Resident / Invalid</option>
                            <option value="Other">Other (Specify)</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="hh_view_custom_reason_group">
                        <label class="form-label fw-semibold">Specify Custom Reason <span class="text-danger">*</span></label>
                        <input type="text" name="custom_archive_reason" id="hh_view_custom_archive_reason" class="form-control" placeholder="Provide specific reason...">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4 flex-shrink-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark">
                        <i class="bi bi-archive-fill me-1"></i> Confirm Archive
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if (in_array($role, ['captain', 'secretary'])): ?>
    <!-- Permanent Delete Household Modal -->
    <div class="modal fade" id="deleteHouseholdModal" tabindex="-1" aria-labelledby="deleteHouseholdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form action="" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <input type="hidden" name="action" value="delete_household">
                <div class="modal-header bg-danger text-white py-3 px-4 flex-shrink-0">
                    <h5 class="modal-title fw-bold" id="deleteHouseholdModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Permanently Delete Household
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <div class="text-danger display-4 mb-3"><i class="bi bi-trash-fill"></i></div>
                    <h5 class="fw-bold text-dark mb-2">Delete Household #<?= htmlspecialchars($hh['household_no']) ?>?</h5>
                    <p class="text-muted small mb-3">
                        Head of Family: <strong><?= htmlspecialchars($hh['head_resident_name'] ?: 'Not listed') ?></strong><br>
                        This will permanently erase the dwelling record and BHW survey documentation. Any linked residents will have their household reference cleared. This action <strong>cannot be undone</strong>.
                    </p>
                    <div class="alert alert-danger py-2 small text-start">
                        <i class="bi bi-shield-lock-fill me-1"></i> Requires Captain / Secretary authorization.
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4 justify-content-between flex-shrink-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-trash-fill me-1"></i> Yes, Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleHhViewCustomReason(sel) {
    const grp = document.getElementById('hh_view_custom_reason_group');
    const inp = document.getElementById('hh_view_custom_archive_reason');
    if (!grp || !inp) return;
    if (sel.value === 'Other') {
        grp.classList.remove('d-none');
        inp.required = true;
    } else {
        grp.classList.add('d-none');
        inp.required = false;
    }
}
</script>

