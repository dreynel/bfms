<?php
// social_services/distribute - Releasing Desk & Beneficiary Distribution Tracking
require_once __DIR__ . '/../../config/app.php';

require_role('treasurer');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;
$pageTitle = 'Ayuda Distribution Desk';

// Handle Claim Releasing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'release_aid') {
        $beneficiaryId = (int)($_POST['beneficiary_id'] ?? 0);
        $programId     = (int)($_POST['program_id'] ?? 0);
        $remarks       = clean($_POST['remarks'] ?? '');
        $receivedAmt   = (float)($_POST['received_amount'] ?? 0.00);

        // Verification: Ensure program is Ongoing (Approved [777])
        $progCheck = $db->prepare("SELECT status, title FROM assistance_programs WHERE id = ?");
        $progCheck->execute([$programId]);
        $progRow = $progCheck->fetch(PDO::FETCH_ASSOC);

        if (!$progRow || $progRow['status'] !== 'Ongoing') {
            set_flash('danger', 'Disbursement Locked: Program is pending executive authorization by the Punong Barangay (Status: ' . htmlspecialchars($progRow['status'] ?? 'Unknown') . ').');
            redirect('social_services/distribute?program_id=' . $programId);
        }

        $updateStmt = $db->prepare("
            UPDATE assistance_beneficiaries 
            SET status = 'Claimed', claimed_at = NOW(), released_by_user_id = ?, remarks = ?, received_amount = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$user['id'], $remarks, $receivedAmt, $beneficiaryId]);

        log_activity('Release Aid', "Ayuda released to beneficiary ID {$beneficiaryId} by {$user['full_name']}");
        set_flash('success', 'Ayuda disbursement marked as CLAIMED successfully!');
        redirect('social_services/distribute?program_id=' . (int)$_POST['program_id']);
    }

    if ($_POST['action'] === 'add_beneficiary') {
        $programId  = (int)($_POST['program_id'] ?? 0);
        $residentId = (int)($_POST['resident_id'] ?? 0);
        $amount     = (float)($_POST['amount'] ?? 0.00);

        if ($residentId <= 0) {
            set_flash('danger', 'Please select a valid resident from the list.');
            redirect('social_services/distribute?program_id=' . $programId);
        }

        // Fetch program details for defaults
        $chkProg = $db->prepare("SELECT * FROM assistance_programs WHERE id = ?");
        $chkProg->execute([$programId]);
        $progInfo = $chkProg->fetch();

        if ($amount <= 0 && $progInfo) {
            $amount = (float)($progInfo['amount_per_beneficiary'] ?? 0.00);
        }
        $inKindItems = $progInfo['in_kind_details'] ?? null;

        try {
            $insert = $db->prepare("
                INSERT INTO assistance_beneficiaries (program_id, resident_id, status, received_amount, received_items)
                VALUES (?, ?, 'Enrolled', ?, ?)
            ");
            $insert->execute([$programId, $residentId, $amount, $inKindItems]);

            $resName = $db->query("SELECT CONCAT(first_name, ' ', last_name) FROM residents WHERE id = " . (int)$residentId)->fetchColumn();
            log_activity('Enroll Beneficiary', "Enrolled resident {$resName} (ID: {$residentId}) into program ID {$programId} with benefit amount of " . format_peso($amount));

            set_flash('success', "Resident <strong>" . htmlspecialchars($resName) . "</strong> enrolled into the beneficiary roster successfully!");
        } catch (Exception $e) {
            set_flash('danger', 'Resident is already enrolled in this program or enrollment failed.');
        }
        redirect('social_services/distribute?program_id=' . $programId);
    }
}

// Fetch all programs for selector
$programs = $db->query("SELECT id, title, program_code, category, status FROM assistance_programs ORDER BY distribution_date DESC")->fetchAll();

$selectedProgramId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : ($programs[0]['id'] ?? 0);

// Fetch selected program details
$stmtProg = $db->prepare("
    SELECT p.*, s.name AS target_sitio_name, u.full_name AS creator_name
    FROM assistance_programs p
    LEFT JOIN sitios s ON p.target_sitio_id = s.id
    LEFT JOIN users u ON p.created_by_user_id = u.id
    WHERE p.id = ?
");
$stmtProg->execute([$selectedProgramId]);
$program = $stmtProg->fetch();

// Filter by Sitio
$filterSitio = isset($_GET['sitio']) ? (int)$_GET['sitio'] : 0;
if ($role === 'bhw' && $userSitioId && !isset($_GET['sitio'])) {
    $filterSitio = $userSitioId;
}

// Fetch Beneficiaries
$beneficiaries = [];
if ($program) {
    $benSql = "
        SELECT b.*, r.resident_code, r.first_name, r.middle_name, r.last_name, r.suffix,
               r.contact_no, r.sitio_id, r.address_line, r.household_no, r.is_head_of_family,
               r.is_pwd, r.pwd_type, r.is_solo_parent, r.is_indigent, r.is_senior, r.is_4ps, r.is_student,
               s.name AS sitio_name,
               u.full_name AS releaser_name
        FROM assistance_beneficiaries b
        JOIN residents r ON b.resident_id = r.id
        JOIN sitios s ON r.sitio_id = s.id
        LEFT JOIN users u ON b.released_by_user_id = u.id
        WHERE b.program_id = ?
    ";
    $benParams = [$selectedProgramId];

    if ($filterSitio > 0) {
        $benSql .= " AND r.sitio_id = ?";
        $benParams[] = $filterSitio;
    }

    $benSql .= " ORDER BY b.status ASC, r.last_name ASC";
    $stmtBen = $db->prepare($benSql);
    $stmtBen->execute($benParams);
    $beneficiaries = $stmtBen->fetchAll();
}

// Stats for selected program
$enrolledCount = count($beneficiaries);
$claimedCount  = count(array_filter($beneficiaries, fn($b) => $b['status'] === 'Claimed'));
$totalAmount   = array_sum(array_map(fn($b) => (float)$b['received_amount'], $beneficiaries));

// Parse and compute program target criteria for beneficiary matching
$programTargetSectors = [];
$programTargetSitioId = null;
$targetCriteriaDesc = [];

if ($program) {
    $criteriaJson = !empty($program['target_criteria']) ? json_decode($program['target_criteria'], true) : [];
    $rawSectors = $criteriaJson['sectors'] ?? [];
    $programTargetSitioId = $program['target_sitio_id'] ?: ($criteriaJson['target_sitio_id'] ?? null);

    foreach (['pwd', 'solo_parent', 'indigent', 'senior', '4ps', 'student'] as $k) {
        if (!empty($rawSectors[$k])) {
            $programTargetSectors[] = $k;
        }
    }

    // Infer target sector from category if criteria was not set or empty
    if (empty($programTargetSectors) && !empty($program['category'])) {
        $cat = strtolower($program['category']);
        if (str_contains($cat, 'education') || str_contains($cat, 'student')) {
            $programTargetSectors[] = 'student';
        } elseif (str_contains($cat, 'pwd') || str_contains($cat, 'disability')) {
            $programTargetSectors[] = 'pwd';
        } elseif (str_contains($cat, 'senior') || str_contains($cat, 'elderly')) {
            $programTargetSectors[] = 'senior';
        } elseif (str_contains($cat, 'solo')) {
            $programTargetSectors[] = 'solo_parent';
        }
    }

    $sectorDisplayNames = [
        'student'     => 'Students',
        'pwd'         => 'PWD Residents',
        'senior'      => 'Senior Citizens',
        'solo_parent' => 'Solo Parents',
        '4ps'         => '4Ps Beneficiaries',
        'indigent'    => 'Indigent Families'
    ];

    foreach ($programTargetSectors as $sec) {
        if (isset($sectorDisplayNames[$sec])) {
            $targetCriteriaDesc[] = $sectorDisplayNames[$sec];
        }
    }

    if (!empty($criteriaJson['selected_households'])) {
        $targetCriteriaDesc[] = count($criteriaJson['selected_households']) . ' Specific Dwellings';
    }
}

// Build SQL expression to match target
$targetMatchSql = "1";
if (!empty($programTargetSectors)) {
    $secClauses = [];
    if (in_array('student', $programTargetSectors))     $secClauses[] = "r.is_student = 1";
    if (in_array('pwd', $programTargetSectors))         $secClauses[] = "r.is_pwd = 1";
    if (in_array('senior', $programTargetSectors))      $secClauses[] = "r.is_senior = 1";
    if (in_array('solo_parent', $programTargetSectors)) $secClauses[] = "r.is_solo_parent = 1";
    if (in_array('4ps', $programTargetSectors))         $secClauses[] = "r.is_4ps = 1";
    if (in_array('indigent', $programTargetSectors))    $secClauses[] = "r.is_indigent = 1";

    $targetMatchSql = "(" . implode(" OR ", $secClauses) . ")";
} elseif (!empty($criteriaJson['selected_households'])) {
    $hhEscaped = array_map(fn($h) => "'" . addslashes(trim($h)) . "'", (array)$criteriaJson['selected_households']);
    if (!empty($hhEscaped)) {
        $targetMatchSql = "TRIM(r.household_no) IN (" . implode(',', $hhEscaped) . ")";
    }
}

if ($programTargetSitioId) {
    $targetMatchSql .= " AND r.sitio_id = " . (int)$programTargetSitioId;
}

// List of non-enrolled approved residents for manual enrollment modal (sorted by target match first)
$nonEnrolledResidents = [];
if ($program) {
    $nonEnrolledStmt = $db->prepare("
        SELECT r.id, r.resident_code, r.first_name, r.middle_name, r.last_name, r.suffix,
               r.gender, r.age, r.civil_status, r.sitio_id, r.address_line, r.household_no,
               r.is_head_of_family, r.family_role,
               r.is_pwd, r.pwd_type, r.pwd_id_no, r.pwd_id_photo,
               r.is_senior, r.senior_id_no, r.senior_id_photo,
               r.is_solo_parent, r.solo_parent_id_no, r.solo_parent_id_photo,
               r.is_4ps, r.four_ps_id_no, r.four_ps_id_photo,
               r.is_student, r.school_name, r.student_id_no, r.school_id_photo,
               r.is_indigent, r.photo_path, r.id_card_path, r.house_photo_path,
               s.name AS sitio_name,
               CASE WHEN ($targetMatchSql) THEN 1 ELSE 0 END AS is_target_match
        FROM residents r
        LEFT JOIN sitios s ON r.sitio_id = s.id
        WHERE r.status = 'approved' AND (r.is_archived = 0 OR r.is_archived IS NULL)
          AND r.id NOT IN (SELECT resident_id FROM assistance_beneficiaries WHERE program_id = ?)
        ORDER BY is_target_match DESC, r.last_name ASC, r.first_name ASC
    ");
    $nonEnrolledStmt->execute([$selectedProgramId]);
    $nonEnrolledResidents = $nonEnrolledStmt->fetchAll(PDO::FETCH_ASSOC);
}

$matchingTargetCount = count(array_filter($nonEnrolledResidents, fn($r) => (int)$r['is_target_match'] === 1));

// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mb-1 px-3 py-1 fw-bold">
            <i class="bi bi-shield-check me-1"></i> Office of the Barangay Treasurer
        </div>
        <h3 class="fw-bold text-dark mb-0">Ayuda Distribution & Disbursement Desk</h3>
        <p class="text-muted small mb-0">Official Treasury Cash Releasing, In-Kind Aid Distribution & Audit Roster</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($program): ?>
            <a href="<?= url('reports/print?type=assistance&program_id=' . $program['id']) ?>" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer me-1"></i> Print Distribution Payroll
            </a>
            <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addBeneficiaryModal">
                <i class="bi bi-person-plus me-1"></i> Enroll Resident
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Program Selector Bar -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <form action="<?= url('social_services/distribute') ?>" method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="social_services/distribute">
            <div class="col-md-6">
                <label class="form-label small fw-bold mb-1">Select Assistance Drive:</label>
                <select name="program_id" class="form-select form-select-lg border-success fw-bold text-success" onchange="this.form.submit()">
                    <?php foreach ($programs as $prog): ?>
                        <option value="<?= $prog['id'] ?>" <?= ($prog['id'] == $selectedProgramId) ? 'selected' : '' ?>>
                            [<?= $prog['program_code'] ?>] <?= htmlspecialchars($prog['title']) ?> (<?= $prog['category'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold mb-1">Filter by Sitio:</label>
                <select name="sitio" class="form-select" onchange="this.form.submit()">
                    <option value="0">-- All Sitios --</option>
                    <?php foreach (SITIOS_LIST as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($filterSitio === $id) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <a href="<?= url('social_services/distribute?program_id=' . $selectedProgramId) ?>" class="btn btn-outline-secondary w-100" style="margin-top: 22px;">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if ($program): ?>
    <?php if ($program['status'] !== 'Ongoing'): ?>
        <div class="alert alert-warning border border-warning shadow-sm d-flex align-items-center gap-3 mb-4 rounded-4">
            <i class="bi bi-shield-lock-fill fs-2 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-1">Disbursement Locked: Program Pending Approval</h6>
                <small class="text-dark">
                    This assistance drive has status <strong><?= htmlspecialchars($program['status']) ?></strong> and is currently subject to executive review and authorization by the <strong>Punong Barangay</strong>. Disbursements can only proceed once approved (Code: 777).
                </small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Program Summary Card -->
    <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
        <div class="card-body p-4 bg-light border-bottom">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1"><?= htmlspecialchars($program['category']) ?></span>
                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($program['title']) ?></h4>
                    <p class="text-muted small mb-0">Fund: <strong><?= htmlspecialchars($program['fund_source']) ?></strong> &bull; Schedule: <strong><?= format_date($program['distribution_date']) ?></strong></p>
                </div>
                <div class="text-md-end">
                    <span class="text-muted small">Standard Benefit:</span>
                    <div class="h4 fw-bold text-success mb-0"><?= format_peso($program['amount_per_beneficiary']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($program['in_kind_details'] ?: 'Cash Aid') ?></small>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white p-3 d-flex flex-wrap justify-content-around text-center border-0">
            <div>
                <span class="text-muted small">Total Beneficiaries</span>
                <h4 class="fw-bold text-dark mb-0"><?= $enrolledCount ?></h4>
            </div>
            <div class="border-start ps-4">
                <span class="text-muted small">Successfully Claimed</span>
                <h4 class="fw-bold text-success mb-0"><?= $claimedCount ?></h4>
            </div>
            <div class="border-start ps-4">
                <span class="text-muted small">Pending Claims</span>
                <h4 class="fw-bold text-warning-emphasis mb-0"><?= $enrolledCount - $claimedCount ?></h4>
            </div>
            <div class="border-start ps-4">
                <span class="text-muted small">Total Disbursed</span>
                <h4 class="fw-bold text-primary mb-0"><?= format_peso($totalAmount) ?></h4>
            </div>
        </div>
    </div>

    <!-- Beneficiary Masterlist Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Enrolled Beneficiaries Roster</h5>
            <input type="text" class="form-control form-control-sm table-search-input w-auto" data-target-table="distributionTable" placeholder="Search resident in table...">
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="distributionTable">
                <thead class="table-light">
                    <tr>
                        <th>Resident</th>
                        <th>Sitio & Address</th>
                        <th>Vulnerability Group</th>
                        <th>Benefit Value</th>
                        <th>Status</th>
                        <th>Claim Details</th>
                        <th class="text-end">Release Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($beneficiaries)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No beneficiaries listed for this program and filter. Click "Enroll Resident" to add.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($beneficiaries as $b): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name'] . ' ' . $b['suffix']) ?>
                                    </div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($b['resident_code']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border mb-1"><?= htmlspecialchars($b['sitio_name']) ?></span>
                                    <div class="small text-muted"><?= htmlspecialchars($b['address_line']) ?></div>
                                    <?php if (!empty($b['household_no'])): ?>
                                        <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                                            <i class="bi bi-houses text-primary me-1"></i><?= htmlspecialchars($b['household_no']) ?><?= $b['is_head_of_family'] ? ' <span class="badge bg-primary-subtle text-primary border" style="font-size:0.65rem;">Head</span>' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php if ($b['is_pwd']): ?><span class="badge badge-pwd">PWD</span><?php endif; ?>
                                        <?php if ($b['is_solo_parent']): ?><span class="badge badge-solo">Solo Mother</span><?php endif; ?>
                                        <?php if ($b['is_indigent']): ?><span class="badge badge-indigent">Indigent</span><?php endif; ?>
                                        <?php if ($b['is_senior']): ?><span class="badge badge-senior">Senior</span><?php endif; ?>
                                        <?php if ($b['is_4ps']): ?><span class="badge badge-4ps">4Ps</span><?php endif; ?>
                                        <?php if (!empty($b['is_student'])): ?><span class="badge bg-primary">Student</span><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-success"><?= format_peso($b['received_amount']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($b['received_items'] ?: '') ?></small>
                                </td>
                                <td><?= status_badge($b['status']) ?></td>
                                <td class="small">
                                    <?php if ($b['status'] === 'Claimed'): ?>
                                        <div class="text-success fw-semibold"><i class="bi bi-clock-history me-1"></i><?= format_datetime($b['claimed_at']) ?></div>
                                        <div class="text-muted">By: <?= htmlspecialchars($b['releaser_name'] ?? 'Staff') ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">Not yet claimed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($b['status'] !== 'Claimed'): ?>
                                        <?php if ($program['status'] === 'Ongoing'): ?>
                                            <button type="button" class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#claimModal<?= $b['id'] ?>">
                                                <i class="bi bi-check2-circle me-1"></i> Release Aid
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-secondary disabled" title="Program awaiting executive approval">
                                                <i class="bi bi-lock-fill me-1"></i> Locked
                                            </button>
                                        <?php endif; ?>

                                        <!-- Claim Modal -->
                                        <div class="modal fade text-start" id="claimModal<?= $b['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                                <form action="" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                    <input type="hidden" name="action" value="release_aid">
                                                    <input type="hidden" name="beneficiary_id" value="<?= $b['id'] ?>">
                                                    <input type="hidden" name="program_id" value="<?= $selectedProgramId ?>">

                                                    <div class="modal-header bg-success text-white py-3 px-4 flex-shrink-0">
                                                        <h5 class="modal-title fw-bold">Disburse Ayuda / Confirm Release</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
                                                        <p class="mb-3">
                                                            Releasing assistance to: <strong><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></strong><br>
                                                            <small class="text-muted"><?= htmlspecialchars($b['sitio_name']) ?> &bull; <?= htmlspecialchars($b['resident_code']) ?></small>
                                                        </p>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Amount to Disburse (PHP)</label>
                                                            <input type="number" step="0.01" name="received_amount" class="form-control form-control-lg text-success fw-bold" value="<?= $b['received_amount'] ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Verification Remarks</label>
                                                            <input type="text" name="remarks" class="form-control" placeholder="e.g. Claimed in person with valid Barangay ID / PWD ID" value="" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light py-3 px-4 flex-shrink-0">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success fw-bold px-4">
                                                            <i class="bi bi-check-lg me-1"></i> Confirm Claimed
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success p-2">
                                            <i class="bi bi-check-all me-1"></i> Released
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Add Resident to Program with Datalist, Attachments Preview & Confirmation -->
    <style>
    #attachmentPreviewModal {
        z-index: 1070 !important;
    }
    .modal-backdrop.show:nth-of-type(even) {
        z-index: 1065 !important;
    }
    .attachment-preview-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        background: #f8fafc;
    }
    .attachment-preview-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        background: #ffffff;
    }
    #addBeneficiaryModal .modal-dialog {
        max-height: calc(100vh - 2rem);
        max-height: calc(100dvh - 2rem);
    }
    #addBeneficiaryModal .modal-body {
        max-height: calc(85vh - 130px);
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
    }
    #addBeneficiaryModal .modal-footer {
        position: sticky;
        bottom: 0;
        background: #f8fafc;
        z-index: 10;
        border-top: 1px solid #e2e8f0;
    }
    </style>

    <div class="modal fade" id="addBeneficiaryModal" tabindex="-1" aria-labelledby="addBeneficiaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form action="" method="POST" id="enrollBeneficiaryForm" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <input type="hidden" name="action" value="add_beneficiary">
                <input type="hidden" name="program_id" value="<?= $selectedProgramId ?>">
                <input type="hidden" name="resident_id" id="enrolledResidentId" required>

                <div class="modal-header bg-dark text-white py-3 px-4 flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                            <i class="bi bi-person-plus-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="addBeneficiaryModalLabel">Enroll Resident to Program</h5>
                            <small class="text-white-50">Datalist Search &bull; Verification &bull; Attachments Preview</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                        <!-- Top Program Info & Target Criteria Banner -->
                        <div class="alert alert-light border border-success-subtle bg-success bg-opacity-10 py-3 px-3 rounded-3 mb-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <div>
                                    <span class="badge bg-success me-1">Program</span>
                                    <strong class="fs-6"><?= htmlspecialchars($program['title']) ?></strong>
                                    <span class="text-muted small ms-1">(<?= htmlspecialchars($program['program_code']) ?> &bull; <?= htmlspecialchars($program['category']) ?>)</span>
                                </div>
                                <div class="small fw-bold text-success">
                                    Baseline Aid: <?= format_peso($program['amount_per_beneficiary']) ?> <?= htmlspecialchars($program['in_kind_details'] ? '+ ' . $program['in_kind_details'] : '') ?>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top border-success-subtle small">
                                <div>
                                    <span class="text-muted"><i class="bi bi-bullseye text-danger me-1"></i>Target Audience:</span>
                                    <strong><?= !empty($targetCriteriaDesc) ? htmlspecialchars(implode(', ', $targetCriteriaDesc)) : 'All General Sectors' ?></strong>
                                    <span class="text-muted ms-2">&bull; Jurisdiction:</span>
                                    <strong><?= $programTargetSitioId ? htmlspecialchars(SITIOS_LIST[$programTargetSitioId] ?? 'Sitio ' . $programTargetSitioId) : 'All 7 Sitios' ?></strong>
                                </div>
                                <div>
                                    <span class="badge bg-success px-2 py-1"><i class="bi bi-check2-circle me-1"></i><?= $matchingTargetCount ?> Matching Non-Enrolled</span>
                                </div>
                            </div>
                        </div>

                        <!-- 1. Search Resident with Datalist & Target Filter -->
                        <div class="mb-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label fw-bold text-dark mb-0">
                                    <i class="bi bi-search me-1 text-primary"></i> Select Resident (Searchable Datalist) <span class="text-danger">*</span>
                                </label>
                                <?php if (!empty($programTargetSectors) || $programTargetSitioId): ?>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="filterTargetOnlySwitch" checked>
                                        <label class="form-check-label small fw-bold text-success" for="filterTargetOnlySwitch">
                                            <i class="bi bi-funnel-fill me-1"></i>Filter to matching program targets (<?= $matchingTargetCount ?>)
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light text-primary"><i class="bi bi-person-badge"></i></span>
                                <input list="nonEnrolledResidentsList" id="residentDatalistInput" class="form-control font-monospace fs-6" placeholder="Type name, resident code (e.g. LUB-2026-0005), or Sitio..." autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="btnClearResidentSelection" title="Clear selection">
                                    <i class="bi bi-x-circle me-1"></i> Clear
                                </button>
                            </div>
                            <datalist id="nonEnrolledResidentsList">
                                <!-- Populated dynamically by JavaScript to match target criteria filter -->
                            </datalist>
                            <div class="form-text small text-muted" id="datalistNotice">
                                <i class="bi bi-info-circle me-1"></i>Displaying residents matching the program's target profile.
                            </div>
                        </div>

                        <!-- 2. Benefit Amount -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted text-uppercase">Entitled Benefit Amount (PHP) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-success fw-bold">₱</span>
                                <input type="number" step="0.01" name="amount" id="enrollBenefitAmount" class="form-control fw-bold text-success" value="<?= $program['amount_per_beneficiary'] ?>" required>
                            </div>
                            <div class="form-text small">Standard program entitlement. Can be adjusted if necessary.</div>
                        </div>

                        <!-- 3. Dynamic Resident Preview Container -->
                        <!-- Notice when no resident selected -->
                        <div id="noResidentSelectedNotice" class="text-center py-4 px-3 bg-light rounded-4 border border-dashed mb-3">
                            <i class="bi bi-person-bounding-box display-5 text-secondary opacity-50 d-block mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">No Resident Selected</h6>
                            <p class="small text-muted mb-0">Select a resident above from the datalist to inspect their personal info, vulnerability classification, and preview uploaded attachments.</p>
                        </div>

                        <!-- Loaded Resident Profile & Attachments Preview Card -->
                        <div id="residentProfilePreview" class="d-none">
                            <div class="card border border-primary border-opacity-25 shadow-sm rounded-4 mb-3 overflow-hidden">
                                <!-- Resident Header Card -->
                                <div class="card-header bg-primary bg-opacity-10 py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div id="previewAvatarBox" class="rounded-circle overflow-hidden bg-white border border-2 border-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; flex-shrink: 0;">
                                            <i class="bi bi-person-fill fs-2 text-primary" id="previewDefaultIcon"></i>
                                            <img id="previewAvatarImg" src="" alt="Photo" class="img-fluid d-none" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-5 text-dark" id="previewFullName">Resident Name</div>
                                            <div class="d-flex flex-wrap align-items-center gap-1 small text-muted">
                                                <span class="badge bg-dark font-monospace text-white" id="previewResCode">LUB-2026-0000</span>
                                                <span id="previewDemographics">35 yrs &bull; Male &bull; Married</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-md-end">
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1" id="previewSitioBadge">Sitio</span>
                                        <div class="small text-muted font-monospace mt-1" id="previewHouseholdInfo">HH-LUB-2026-0000</div>
                                    </div>
                                </div>

                                <div class="card-body p-3">
                                    <!-- Program Target Matching Status Alert -->
                                    <div id="previewTargetMatchStatus" class="mb-3"></div>

                                    <!-- Sectoral Badges Row -->
                                    <div class="mb-3">
                                        <div class="small fw-bold text-muted text-uppercase mb-1"><i class="bi bi-tags-fill me-1 text-danger"></i>Vulnerability & Sectoral Profile:</div>
                                        <div class="d-flex flex-wrap gap-1" id="previewSectorBadges">
                                            <!-- Dynamically generated badges -->
                                        </div>
                                    </div>

                                    <!-- ATTACHMENTS & DOCUMENTATION GALLERY -->
                                    <div class="mt-3 border-top pt-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="small fw-bold text-dark text-uppercase">
                                                <i class="bi bi-paperclip text-primary me-1"></i>Verified Identity & Eligibility Attachments:
                                            </div>
                                            <small class="text-muted"><i class="bi bi-arrows-fullscreen me-1"></i>Click attachment to open modal</small>
                                        </div>

                                        <div class="row g-2" id="previewAttachmentsRow">
                                            <!-- Thumbnails dynamically rendered -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CONFIRMATION BOX -->
                            <div class="card border-0 bg-warning bg-opacity-10 rounded-4 p-3 mb-2 border border-warning border-opacity-25">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-shield-exclamation text-warning fs-4 flex-shrink-0 mt-1"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Confirm Enrollment Verification</div>
                                        <p class="small text-muted mb-2">
                                            You are enrolling <strong id="confirmResidentNameText">Resident</strong> into <strong><?= htmlspecialchars($program['title']) ?></strong>.
                                        </p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="confirmEnrollmentCheck">
                                            <label class="form-check-label fw-bold text-dark small" for="confirmEnrollmentCheck">
                                                I confirm that I have verified this resident's identity, eligibility, and attachments for aid disbursement.
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-3 px-4 justify-content-between flex-shrink-0 sticky-bottom-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm" id="btnSubmitEnrollBeneficiary" disabled>
                            <i class="bi bi-check-circle-fill me-2"></i> Confirm & Enroll Resident
                        </button>
                    </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Dynamic Selection, Attachment Previews, and Confirmation -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const residentsData = <?= json_encode($nonEnrolledResidents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const programSectors = <?= json_encode($programTargetSectors) ?>;
        const targetDescText = <?= json_encode(!empty($targetCriteriaDesc) ? implode(', ', $targetCriteriaDesc) : 'All General Sectors') ?>;
        const residentsMap = {};
        const residentsCodeMap = {};

        residentsData.forEach(r => {
            residentsMap[r.id] = r;
            residentsCodeMap[r.resident_code.toUpperCase()] = r;
        });

        const datalistInput = document.getElementById('residentDatalistInput');
        const datalistEl = document.getElementById('nonEnrolledResidentsList');
        const datalistNotice = document.getElementById('datalistNotice');
        const filterSwitch = document.getElementById('filterTargetOnlySwitch');
        const clearBtn = document.getElementById('btnClearResidentSelection');
        const hiddenIdInput = document.getElementById('enrolledResidentId');
        const emptyNotice = document.getElementById('noResidentSelectedNotice');
        const previewCard = document.getElementById('residentProfilePreview');
        const confirmCheck = document.getElementById('confirmEnrollmentCheck');
        const submitBtn = document.getElementById('btnSubmitEnrollBeneficiary');

        const baseUrl = '<?= url('') ?>';

        // Rebuild datalist options dynamically to match program targets
        function rebuildDatalist(onlyTargets) {
            if (!datalistEl) return;
            datalistEl.innerHTML = '';

            const filtered = onlyTargets 
                ? residentsData.filter(r => parseInt(r.is_target_match) === 1)
                : residentsData;

            filtered.forEach(ner => {
                const mid = ner.middle_name ? ' ' + ner.middle_name[0] + '.' : '';
                const matchFlag = parseInt(ner.is_target_match) === 1 ? ' ★ Matches Target' : '';

                // Extract sector tags
                let tags = [];
                if (parseInt(ner.is_student) === 1) tags.push('Student');
                if (parseInt(ner.is_pwd) === 1) tags.push('PWD');
                if (parseInt(ner.is_senior) === 1) tags.push('Senior');
                if (parseInt(ner.is_solo_parent) === 1) tags.push('Solo Parent');
                if (parseInt(ner.is_4ps) === 1) tags.push('4Ps');
                if (parseInt(ner.is_indigent) === 1) tags.push('Indigent');
                const tagStr = tags.length > 0 ? ` [${tags.join(', ')}]` : '';

                const label = `[${ner.resident_code}] ${ner.last_name}, ${ner.first_name}${mid} (${ner.sitio_name || ('Sitio ' + ner.sitio_id)})${tagStr}${matchFlag}`;
                const opt = document.createElement('option');
                opt.value = label;
                opt.setAttribute('data-id', ner.id);
                opt.setAttribute('data-code', ner.resident_code);
                datalistEl.appendChild(opt);
            });

            if (datalistNotice) {
                if (onlyTargets) {
                    datalistNotice.innerHTML = `<i class="bi bi-funnel-fill text-success me-1"></i>Displaying <strong>${filtered.length}</strong> residents matching program targets (${targetDescText}).`;
                } else {
                    datalistNotice.innerHTML = `<i class="bi bi-info-circle me-1"></i>Displaying all <strong>${filtered.length}</strong> un-enrolled residents (Matching targets listed first with ★).`;
                }
            }

            if (datalistInput) {
                datalistInput.placeholder = onlyTargets 
                    ? `Type to search ${filtered.length} matching target residents...` 
                    : `Type name, code (e.g. LUB-2026-0005), or Sitio...`;
            }
        }

        // Initialize datalist filtered to targets if any match exists
        const initialFilterState = (filterSwitch && filterSwitch.checked);
        rebuildDatalist(initialFilterState);

        if (filterSwitch) {
            filterSwitch.addEventListener('change', function () {
                rebuildDatalist(this.checked);
                // Re-evaluate current input selection
                handleDatalistSelection();
            });
        }

        function resolveUrl(path) {
            if (!path) return '';
            if (path.startsWith('http://') || path.startsWith('https://')) return path;
            const b = baseUrl.replace(/\/$/, '');
            const p = path.replace(/^\//, '');
            return b + '/' + p;
        }

        function updateSubmitButtonState() {
            if (submitBtn) {
                const hasValidResident = hiddenIdInput && hiddenIdInput.value.trim().length > 0;
                const isConfirmed = confirmCheck && confirmCheck.checked;
                submitBtn.disabled = !(hasValidResident && isConfirmed);
            }
        }

        if (confirmCheck) {
            confirmCheck.addEventListener('change', updateSubmitButtonState);
        }

        function renderResidentPreview(res) {
            if (!res) {
                hiddenIdInput.value = '';
                previewCard.classList.add('d-none');
                emptyNotice.classList.remove('d-none');
                if (confirmCheck) confirmCheck.checked = false;
                updateSubmitButtonState();
                return;
            }

            hiddenIdInput.value = res.id;

            // Full Name
            const fullName = `${res.first_name} ${res.middle_name ? res.middle_name + ' ' : ''}${res.last_name}${res.suffix ? ' ' + res.suffix : ''}`;
            document.getElementById('previewFullName').textContent = fullName;
            document.getElementById('confirmResidentNameText').textContent = fullName;

            // Code & Demographics
            document.getElementById('previewResCode').textContent = res.resident_code;
            document.getElementById('previewDemographics').textContent = `${res.age} yrs • ${res.gender} • ${res.civil_status || 'Single'}`;

            // Sitio & Household
            document.getElementById('previewSitioBadge').textContent = res.sitio_name || ('Sitio ' + res.sitio_id);
            let hhText = res.household_no ? res.household_no : 'No Household Assigned';
            if (res.is_head_of_family == 1) {
                hhText += ' [Head of Family]';
            } else if (res.family_role) {
                hhText += ` [${res.family_role}]`;
            }
            document.getElementById('previewHouseholdInfo').textContent = hhText;

            // Program Target Match Status Banner
            const targetStatusBox = document.getElementById('previewTargetMatchStatus');
            if (targetStatusBox) {
                if (parseInt(res.is_target_match) === 1) {
                    let matchLabels = [];
                    if (parseInt(res.is_student) === 1 && (programSectors.length === 0 || programSectors.includes('student'))) matchLabels.push('Student');
                    if (parseInt(res.is_pwd) === 1 && (programSectors.length === 0 || programSectors.includes('pwd'))) matchLabels.push('PWD');
                    if (parseInt(res.is_senior) === 1 && (programSectors.length === 0 || programSectors.includes('senior'))) matchLabels.push('Senior Citizen');
                    if (parseInt(res.is_solo_parent) === 1 && (programSectors.length === 0 || programSectors.includes('solo_parent'))) matchLabels.push('Solo Parent');
                    if (parseInt(res.is_4ps) === 1 && (programSectors.length === 0 || programSectors.includes('4ps'))) matchLabels.push('4Ps Beneficiary');
                    if (parseInt(res.is_indigent) === 1 && (programSectors.length === 0 || programSectors.includes('indigent'))) matchLabels.push('Indigent');
                    if (matchLabels.length === 0) matchLabels.push('Sector Eligible');

                    targetStatusBox.innerHTML = `
                        <div class="alert alert-success py-2 px-3 rounded-3 small mb-0 d-flex align-items-center gap-2 border border-success-subtle shadow-sm">
                            <i class="bi bi-patch-check-fill text-success fs-5 flex-shrink-0"></i>
                            <div>
                                <strong class="text-success-emphasis">Program Target Criteria Matched:</strong>
                                This resident directly qualifies under this program's profile: <strong>${matchLabels.join(', ')}</strong>.
                            </div>
                        </div>
                    `;
                } else {
                    targetStatusBox.innerHTML = `
                        <div class="alert alert-warning py-2 px-3 rounded-3 small mb-0 d-flex align-items-center gap-2 border border-warning-subtle shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0"></i>
                            <div>
                                <strong class="text-warning-emphasis">Outside Primary Target Profile:</strong>
                                This resident does not meet the primary target criteria (${targetDescText}). Enrolling will be recorded as a manual discretionary entry.
                            </div>
                        </div>
                    `;
                }
            }

            // Avatar / Photo
            const avatarImg = document.getElementById('previewAvatarImg');
            const defaultIcon = document.getElementById('previewDefaultIcon');
            if (res.photo_path) {
                avatarImg.src = resolveUrl(res.photo_path);
                avatarImg.classList.remove('d-none');
                defaultIcon.classList.add('d-none');
            } else {
                avatarImg.classList.add('d-none');
                defaultIcon.classList.remove('d-none');
            }

            // Sectoral Badges
            const sectorContainer = document.getElementById('previewSectorBadges');
            sectorContainer.innerHTML = '';
            let badgesCount = 0;

            if (parseInt(res.is_pwd) === 1) {
                badgesCount++;
                const b = document.createElement('span');
                b.className = 'badge bg-primary text-white p-2';
                b.innerHTML = `<i class="bi bi-person-wheelchair me-1"></i>PWD: ${res.pwd_type || 'Special Needs'} (${res.pwd_id_no || 'On File'})`;
                sectorContainer.appendChild(b);
            }
            if (parseInt(res.is_senior) === 1) {
                badgesCount++;
                const b = document.createElement('span');
                b.className = 'badge bg-info text-dark p-2';
                b.innerHTML = `<i class="bi bi-person-cane me-1"></i>Senior Citizen (${res.senior_id_no || 'OSCA Validated'})`;
                sectorContainer.appendChild(b);
            }
            if (parseInt(res.is_solo_parent) === 1) {
                badgesCount++;
                const b = document.createElement('span');
                b.className = 'badge bg-danger text-white p-2';
                b.innerHTML = `<i class="bi bi-person-heart me-1"></i>Solo Parent / Mother (${res.solo_parent_id_no || 'Registered'})`;
                sectorContainer.appendChild(b);
            }
            if (parseInt(res.is_4ps) === 1) {
                badgesCount++;
                const b = document.createElement('span');
                b.className = 'badge bg-success text-white p-2';
                b.innerHTML = `<i class="bi bi-card-checklist me-1"></i>4Ps DSWD (${res.four_ps_id_no || 'Beneficiary'})`;
                sectorContainer.appendChild(b);
            }
            if (parseInt(res.is_student) === 1) {
                badgesCount++;
                const b = document.createElement('span');
                b.className = 'badge bg-primary-subtle text-primary border border-primary-subtle p-2';
                b.innerHTML = `<i class="bi bi-mortarboard-fill me-1"></i>Student: ${res.school_name || 'Enrolled'} (${res.student_id_no || 'Student'})`;
                sectorContainer.appendChild(b);
            }
            if (parseInt(res.is_indigent) === 1) {
                badgesCount++;
                const b = document.createElement('span');
                b.className = 'badge bg-warning text-dark p-2';
                b.innerHTML = `<i class="bi bi-wallet2 me-1"></i>Indigent Low-Income`;
                sectorContainer.appendChild(b);
            }

            if (badgesCount === 0) {
                sectorContainer.innerHTML = '<span class="badge bg-light text-muted border p-2">General Resident (No Special Sector Flags)</span>';
            }

            // ATTACHMENT PREVIEW CARDS
            const attachmentsContainer = document.getElementById('previewAttachmentsRow');
            attachmentsContainer.innerHTML = '';

            const attachments = [
                {
                    title: '2x2 / Portrait Photo',
                    icon: 'bi-person-bounding-box text-success',
                    path: res.photo_path,
                    badge: 'Photo ID'
                },
                {
                    title: 'Primary Government ID',
                    icon: 'bi-card-heading text-primary',
                    path: res.id_card_path,
                    badge: 'Govt ID'
                },
                {
                    title: 'Sectoral Eligibility ID',
                    icon: 'bi-shield-check text-danger',
                    path: res.pwd_id_photo || res.senior_id_photo || res.solo_parent_id_photo || res.four_ps_id_photo || res.school_id_photo,
                    badge: res.is_pwd ? 'PWD ID' : (res.is_senior ? 'OSCA ID' : (res.is_4ps ? '4Ps Card' : (res.is_student ? 'School ID' : 'Sector Proof')))
                },
                {
                    title: 'Dwelling Exterior Photo',
                    icon: 'bi-house text-warning',
                    path: res.house_photo_path,
                    badge: 'Household Proof'
                }
            ];

            attachments.forEach(att => {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-3';

                if (att.path && att.path.trim().length > 0) {
                    const fullUrl = resolveUrl(att.path);
                    col.innerHTML = `
                        <div class="attachment-preview-card p-2 rounded-3 border text-center h-100 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge bg-light text-dark border mb-1" style="font-size: 0.68rem;">${att.badge}</span>
                                <div class="small fw-semibold text-truncate mb-2" title="${att.title}">${att.title}</div>
                                <div class="position-relative overflow-hidden rounded border bg-white mb-2" style="height: 85px;">
                                    <img src="${fullUrl}" alt="${att.title}" class="img-fluid w-100 h-100" style="object-fit: cover;">
                                </div>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-primary preview-attachment-btn w-100 py-1" 
                                    data-preview-url="${fullUrl}" 
                                    data-preview-title="${att.title}" 
                                    data-preview-subtitle="${fullName} &bull; ${res.resident_code}"
                                    style="font-size: 0.72rem;">
                                <i class="bi bi-arrows-fullscreen me-1"></i>View Modal
                            </button>
                        </div>
                    `;
                } else {
                    col.innerHTML = `
                        <div class="attachment-preview-card p-2 rounded-3 border text-center h-100 d-flex flex-column justify-content-center opacity-75">
                            <span class="badge bg-light text-muted border mb-1" style="font-size: 0.68rem;">${att.badge}</span>
                            <div class="small fw-semibold text-muted text-truncate mb-2" title="${att.title}">${att.title}</div>
                            <div class="py-2 text-muted">
                                <i class="bi bi-file-earmark-x fs-2 text-secondary opacity-50 d-block mb-1"></i>
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">No Upload</span>
                            </div>
                        </div>
                    `;
                }
                attachmentsContainer.appendChild(col);
            });

            // Show preview, hide notice
            emptyNotice.classList.add('d-none');
            previewCard.classList.remove('d-none');
            if (confirmCheck) confirmCheck.checked = false;
            updateSubmitButtonState();
        }

        function handleDatalistSelection() {
            const inputVal = datalistInput.value.trim();
            if (!inputVal) {
                renderResidentPreview(null);
                return;
            }

            // Check if exact code exists e.g. [LUB-2026-0005] or direct LUB-2026-0005
            const codeMatch = inputVal.match(/LUB-\d{4}-\d{4}/i);
            if (codeMatch) {
                const code = codeMatch[0].toUpperCase();
                if (residentsCodeMap[code]) {
                    renderResidentPreview(residentsCodeMap[code]);
                    return;
                }
            }

            // Check datalist option match
            const option = document.querySelector(`#nonEnrolledResidentsList option[value="${inputVal.replace(/"/g, '\\"')}"]`);
            if (option) {
                const resId = option.getAttribute('data-id');
                if (resId && residentsMap[resId]) {
                    renderResidentPreview(residentsMap[resId]);
                    return;
                }
            }

            // If partial typing matches exactly one resident
            const lower = inputVal.toLowerCase();
            const matches = residentsData.filter(r => 
                r.resident_code.toLowerCase().includes(lower) || 
                (r.first_name + ' ' + r.last_name).toLowerCase().includes(lower) ||
                (r.last_name + ', ' + r.first_name).toLowerCase().includes(lower)
            );

            if (matches.length === 1) {
                renderResidentPreview(matches[0]);
            } else {
                // Keep selected if already loaded and code still matches, otherwise reset
                if (hiddenIdInput.value && residentsMap[hiddenIdInput.value]) {
                    const current = residentsMap[hiddenIdInput.value];
                    if (inputVal.includes(current.resident_code)) {
                        return; // Still the same resident
                    }
                }
                renderResidentPreview(null);
            }
        }

        if (datalistInput) {
            datalistInput.addEventListener('input', handleDatalistSelection);
            datalistInput.addEventListener('change', handleDatalistSelection);
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                datalistInput.value = '';
                renderResidentPreview(null);
                datalistInput.focus();
            });
        }
    });
    </script>
<?php endif; ?>

// Handled by index.php container

