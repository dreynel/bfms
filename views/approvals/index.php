<?php
// views/approvals/index.php - Centralized Approvals Hub & Chart of Authority
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'Centralized Approvals Hub';

// Active Tab
$activeTab = clean($_GET['tab'] ?? 'pending');

// 1. Metrics for Summary Cards
$myPendingCount = get_pending_approvals_count_for_role($role, $db);

$stmtTotalPending = $db->query("SELECT COUNT(*) FROM approvals WHERE approve_order NOT IN (0, 777, 555)");
$totalPendingHierarchy = (int)$stmtTotalPending->fetchColumn();

$stmtTotalApproved = $db->query("SELECT COUNT(*) FROM approvals WHERE approve_order = 777");
$totalApproved = (int)$stmtTotalApproved->fetchColumn();

$stmtTotalRejected = $db->query("SELECT COUNT(*) FROM approvals WHERE approve_order = 555");
$totalRejected = (int)$stmtTotalRejected->fetchColumn();

// 2. Fetch Pending Items Awaiting Current User
if ($role === 'captain') {
    // Captain can see items specifically assigned to captain, or review all pending hierarchy
    $pendingQuery = $db->prepare("
        SELECT a.*, u.full_name AS submitter_name, u.role AS submitter_role
        FROM approvals a
        LEFT JOIN users u ON a.submitted_by_user_id = u.id
        WHERE a.approve_order NOT IN (0, 777, 555)
          AND (a.current_approver_role = 'captain' OR a.current_approver_role IS NULL)
        ORDER BY a.created_at DESC
    ");
    $pendingQuery->execute();
} else {
    // Secretary sees items assigned to secretary (e.g. resident signups)
    $pendingQuery = $db->prepare("
        SELECT a.*, u.full_name AS submitter_name, u.role AS submitter_role
        FROM approvals a
        LEFT JOIN users u ON a.submitted_by_user_id = u.id
        WHERE a.approve_order NOT IN (0, 777, 555)
          AND a.current_approver_role = ?
        ORDER BY a.created_at DESC
    ");
    $pendingQuery->execute([$role]);
}
$myPendingItems = $pendingQuery->fetchAll(PDO::FETCH_ASSOC);

// Enrich pending items with document-specific data
foreach ($myPendingItems as &$item) {
    if ($item['document_type'] === 'resident_signup') {
        $stmtRes = $db->prepare("
            SELECT r.*, s.name AS sitio_name, s.code AS sitio_code 
            FROM residents r 
            JOIN sitios s ON r.sitio_id = s.id 
            WHERE r.id = ?
        ");
        $stmtRes->execute([$item['document_id']]);
        $item['doc_details'] = $stmtRes->fetch(PDO::FETCH_ASSOC);
    } elseif ($item['document_type'] === 'assistance_program') {
        $stmtProg = $db->prepare("
            SELECT p.*, s.name AS sitio_name,
                   (SELECT COUNT(*) FROM assistance_beneficiaries b WHERE b.program_id = p.id) AS beneficiary_count
            FROM assistance_programs p
            LEFT JOIN sitios s ON p.target_sitio_id = s.id
            WHERE p.id = ?
        ");
        $stmtProg->execute([$item['document_id']]);
        $item['doc_details'] = $stmtProg->fetch(PDO::FETCH_ASSOC);
    }
}
unset($item);

// 3. Fetch Master Tracking List (Tab 2)
$filterDoc = clean($_GET['doc_filter'] ?? 'all');
$filterStatus = clean($_GET['status_filter'] ?? 'all');

$masterSql = "
    SELECT a.*, u.full_name AS submitter_name, u.role AS submitter_role,
           ua.full_name AS actor_name, ua.role AS actor_role
    FROM approvals a
    LEFT JOIN users u ON a.submitted_by_user_id = u.id
    LEFT JOIN users ua ON a.action_by_user_id = ua.id
    WHERE 1=1
";
$masterParams = [];

if ($filterDoc !== 'all') {
    $masterSql .= " AND a.document_type = ?";
    $masterParams[] = $filterDoc;
}

if ($filterStatus === 'draft') {
    $masterSql .= " AND a.approve_order = 0";
} elseif ($filterStatus === 'pending') {
    $masterSql .= " AND a.approve_order NOT IN (0, 777, 555)";
} elseif ($filterStatus === 'approved') {
    $masterSql .= " AND a.approve_order = 777";
} elseif ($filterStatus === 'rejected') {
    $masterSql .= " AND a.approve_order = 555";
}

$masterSql .= " ORDER BY a.updated_at DESC LIMIT 100";
$masterStmt = $db->prepare($masterSql);
$masterStmt->execute($masterParams);
$allApprovals = $masterStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch Chart of Authority (Approvers Setting) (Tab 3)
$chartQuery = $db->query("
    SELECT * FROM approvers 
    ORDER BY document_type ASC, approve_order ASC
");
$chartOfAuthority = $chartQuery->fetchAll(PDO::FETCH_ASSOC);

// Group by document type
$groupedChart = [];
foreach ($chartOfAuthority as $coa) {
    $groupedChart[$coa['document_type']][] = $coa;
}
?>

<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-shield-check text-success me-2"></i>Centralized Approvals Hub
            </h3>
            <p class="text-muted small mb-0">
                <?= BRGY_NAME ?> &bull; Multi-Tier Document Verification, Approval Hierarchy & Chart of Authority
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($role === 'secretary'): ?>
                <a href="<?= url('social_services/create') ?>" class="btn btn-sm btn-success fw-semibold shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Assistance Program
                </a>
            <?php endif; ?>
            <a href="<?= url('approvals?tab=' . $activeTab) ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </a>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-warning border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Awaiting My Action</span>
                        <h3 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= $myPendingCount ?></h3>
                        <small class="text-muted">Assigned to <?= ucfirst($role) ?></small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3">
                        <i class="bi bi-bell-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">In Hierarchy</span>
                        <h3 class="fw-bold text-primary mb-0 mt-1"><?= $totalPendingHierarchy ?></h3>
                        <small class="text-muted">Stages 1, 2, 3...</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                        <i class="bi bi-diagram-3-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Approved Total</span>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= $totalApproved ?></h3>
                        <small class="text-success fw-semibold"><i class="bi bi-check-all"></i> Code: 777</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-danger border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Rejected Total</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1"><?= $totalRejected ?></h3>
                        <small class="text-danger fw-semibold"><i class="bi bi-x-octagon"></i> Code: 555</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                        <i class="bi bi-x-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-4 shadow-sm border">
        <li class="nav-item">
            <a class="nav-link rounded-3 fw-bold <?= $activeTab === 'pending' ? 'active bg-success text-white' : 'text-dark' ?>" 
               href="<?= url('approvals?tab=pending') ?>">
                <i class="bi bi-inbox-fill me-1"></i> Awaiting My Review
                <?php if ($myPendingCount > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $myPendingCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-3 fw-bold <?= $activeTab === 'tracking' ? 'active bg-success text-white' : 'text-dark' ?>" 
               href="<?= url('approvals?tab=tracking') ?>">
                <i class="bi bi-list-check me-1"></i> Master Tracking Log
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-3 fw-bold <?= $activeTab === 'authority' ? 'active bg-success text-white' : 'text-dark' ?>" 
               href="<?= url('approvals?tab=authority') ?>">
                <i class="bi bi-diagram-3 me-1"></i> Chart of Authority (Approvers Setting)
            </a>
        </li>
    </ul>

    <!-- ============================================================== -->
    <!-- TAB 1: PENDING MY REVIEW                                       -->
    <!-- ============================================================== -->
    <?php if ($activeTab === 'pending'): ?>
        <?php if (empty($myPendingItems)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <div class="text-success mb-3">
                    <i class="bi bi-check2-circle display-1"></i>
                </div>
                <h4 class="fw-bold text-dark">You're All Caught Up!</h4>
                <p class="text-muted mb-0">No documents or programs currently pending your approval authority at this time.</p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($myPendingItems as $item): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-start border-warning border-5">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <?= render_approval_order_badge($item['approve_order']) ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-file-earmark-text me-1"></i>
                                            <?= $item['document_type'] === 'resident_signup' ? 'Resident Registration' : 'Assistance Program' ?>
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi bi-person-badge me-1"></i> Stage Role: <?= strtoupper($item['current_approver_role']) ?>
                                        </span>
                                        <small class="text-muted ms-auto"><i class="bi bi-clock me-1"></i>Submitted <?= format_datetime($item['created_at']) ?></small>
                                    </div>

                                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($item['title']) ?></h4>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($item['remarks'] ?? 'No submission remarks') ?></p>

                                    <!-- Document Details Preview -->
                                    <?php if ($item['document_type'] === 'assistance_program' && !empty($item['doc_details'])): ?>
                                        <?php $p = $item['doc_details']; ?>
                                        <div class="bg-light p-3 rounded-3 mb-3 border">
                                            <div class="row g-2 small">
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block">Program Category:</span>
                                                    <strong class="text-dark"><?= htmlspecialchars($p['category']) ?></strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block">Aid Amount / In-Kind:</span>
                                                    <strong class="text-success"><?= $p['amount_per_beneficiary'] > 0 ? format_peso($p['amount_per_beneficiary']) : htmlspecialchars($p['in_kind_details']) ?></strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block">Target Scope:</span>
                                                    <strong class="text-dark"><?= $p['sitio_name'] ? htmlspecialchars($p['sitio_name']) : 'All Sitios (Barangay-wide)' ?></strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block">Pre-Enrolled Beneficiaries:</span>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><?= $p['beneficiary_count'] ?> Beneficiaries</span>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <span class="text-muted d-block">Description & Budget Purpose:</span>
                                                    <div class="text-dark"><?= nl2br(htmlspecialchars($p['description'])) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif ($item['document_type'] === 'resident_signup' && !empty($item['doc_details'])): ?>
                                        <?php $r = $item['doc_details']; ?>
                                        <div class="bg-light p-3 rounded-3 mb-3 border">
                                            <div class="row g-2 small">
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block">Demographics:</span>
                                                    <strong class="text-dark"><?= $r['age'] ?> yrs old &bull; <?= $r['gender'] ?> &bull; <?= $r['civil_status'] ?></strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block">Sitio & Address:</span>
                                                    <strong class="text-dark"><?= htmlspecialchars($r['sitio_name']) ?> (<?= htmlspecialchars($r['address_line'] ?: 'No address specified') ?>)</strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block">Vulnerability Profile:</span>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        <?php if ($r['is_pwd']): ?><span class="badge badge-pwd">PWD</span><?php endif; ?>
                                                        <?php if ($r['is_solo_parent']): ?><span class="badge badge-solo">Solo Parent</span><?php endif; ?>
                                                        <?php if ($r['is_indigent']): ?><span class="badge badge-indigent">Indigent</span><?php endif; ?>
                                                        <?php if ($r['is_senior']): ?><span class="badge badge-senior">Senior</span><?php endif; ?>
                                                        <?php if ($r['is_student']): ?><span class="badge badge-student">Student</span><?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <span class="text-muted d-block">Attachments:</span>
                                                    <?php if (!empty($r['id_card_path'])): ?>
                                                        <a href="<?= asset($r['id_card_path']) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2 mt-1">
                                                            <i class="bi bi-file-earmark-image"></i> View Valid ID
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted fst-italic">No ID uploaded</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex flex-column gap-2" style="min-width: 220px;">
                                    <?php if ($item['document_type'] === 'assistance_program'): ?>
                                        <button type="button" class="btn btn-success fw-bold py-2 shadow-sm" 
                                                data-bs-toggle="modal" data-bs-target="#modalApproveProg<?= $item['id'] ?>">
                                            <i class="bi bi-check-circle-fill me-1"></i> Authorize & Approve [777]
                                        </button>
                                        <button type="button" class="btn btn-outline-danger fw-semibold py-2" 
                                                data-bs-toggle="modal" data-bs-target="#modalReject<?= $item['id'] ?>">
                                            <i class="bi bi-x-circle me-1"></i> Reject Program [555]
                                        </button>
                                        <a href="<?= url('social_services/view?id=' . $item['document_id']) ?>" class="btn btn-light border btn-sm text-secondary">
                                            <i class="bi bi-eye me-1"></i> Inspect Full Roster
                                        </a>

                                    <?php elseif ($item['document_type'] === 'resident_signup'): ?>
                                        <button type="button" class="btn btn-success fw-bold py-2 shadow-sm" 
                                                data-bs-toggle="modal" data-bs-target="#modalApproveRes<?= $item['id'] ?>">
                                            <i class="bi bi-check-circle-fill me-1"></i> Verify & Approve [777]
                                        </button>
                                        <button type="button" class="btn btn-outline-danger fw-semibold py-2" 
                                                data-bs-toggle="modal" data-bs-target="#modalReject<?= $item['id'] ?>">
                                            <i class="bi bi-x-circle me-1"></i> Reject Signup [555]
                                        </button>
                                        <a href="<?= url('residents/view?id=' . $item['document_id']) ?>" class="btn btn-light border btn-sm text-secondary">
                                            <i class="bi bi-eye me-1"></i> View Full Profile
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Approve Program (Captain) -->
                    <div class="modal fade" id="modalApproveProg<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form action="<?= url('approvals/action') ?>" method="POST" class="modal-content border-0 shadow-lg rounded-4">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="approval_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="redirect_to" value="approvals?tab=pending">

                                <div class="modal-header bg-success text-white border-0 py-3">
                                    <h5 class="modal-title fw-bold">
                                        <i class="bi bi-check-circle-fill me-2"></i>Authorize Assistance Program
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="alert alert-success-subtle border border-success-subtle mb-3">
                                        <strong>Punong Barangay Executive Authorization</strong><br>
                                        Approving this program sets its status to <strong>Ongoing</strong> (Code: 777), opening it to the <strong>Office of the Barangay Treasurer</strong> for disbursement desk execution.
                                    </div>
                                    <p class="mb-2"><strong>Program:</strong> <?= htmlspecialchars($item['title']) ?></p>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Executive Remarks / Approval Notes (Optional):</label>
                                        <textarea name="remarks" class="form-control" rows="3" placeholder="e.g. Budget authorized under Resolution No. 2026-12. Proceeds may be disbursed to pre-enrolled beneficiaries."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-3 bg-light">
                                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success fw-bold px-4">
                                        <i class="bi bi-shield-check me-1"></i> Grant Official Approval [777]
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal: Approve Resident (Secretary) -->
                    <div class="modal fade" id="modalApproveRes<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form action="<?= url('approvals/action') ?>" method="POST" class="modal-content border-0 shadow-lg rounded-4">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="approval_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="redirect_to" value="approvals?tab=pending">

                                <div class="modal-header bg-success text-white border-0 py-3">
                                    <h5 class="modal-title fw-bold">
                                        <i class="bi bi-check-circle-fill me-2"></i>Verify Resident Registration
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="alert alert-success-subtle border border-success-subtle mb-3">
                                        <strong>Barangay Secretary Verification</strong><br>
                                        Approving this application marks the resident as <strong>Approved [Code: 777]</strong>, generates their sequential resident code, and activates their citizen account.
                                    </div>
                                    <p class="mb-2"><strong>Applicant:</strong> <?= htmlspecialchars($item['title']) ?></p>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Assign Household Number (Optional):</label>
                                        <input type="text" name="assigned_household_no" class="form-control" 
                                               value="<?= htmlspecialchars($item['doc_details']['household_no'] ?? '') ?>" 
                                               placeholder="e.g. HH-LUB-2026-0001">
                                        <div class="form-text small">Leave as-is if already correct or to keep existing household linkage.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Verification Remarks (Optional):</label>
                                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Identity verified against Government ID."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-3 bg-light">
                                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success fw-bold px-4">
                                        <i class="bi bi-check2-circle me-1"></i> Confirm & Approve [777]
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal: Reject Document (Common) -->
                    <div class="modal fade" id="modalReject<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form action="<?= url('approvals/action') ?>" method="POST" class="modal-content border-0 shadow-lg rounded-4">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="approval_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="redirect_to" value="approvals?tab=pending">

                                <div class="modal-header bg-danger text-white border-0 py-3">
                                    <h5 class="modal-title fw-bold">
                                        <i class="bi bi-x-circle-fill me-2"></i>Reject Application [Code: 555]
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <p class="mb-2"><strong>Target:</strong> <?= htmlspecialchars($item['title']) ?></p>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-danger">Reason for Rejection (Required) <span class="text-danger">*</span></label>
                                        <textarea name="remarks" class="form-control" rows="3" required placeholder="Specify why this submission is being rejected..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-3 bg-light">
                                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger fw-bold px-4">
                                        <i class="bi bi-x-octagon me-1"></i> Confirm Rejection [555]
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- ============================================================== -->
    <!-- TAB 2: MASTER TRACKING LOG                                     -->
    <!-- ============================================================== -->
    <?php elseif ($activeTab === 'tracking'): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-list-columns-reverse text-success me-2"></i>Master Approval Transaction Registry</h5>

                <!-- Filter Controls -->
                <form action="<?= url('approvals') ?>" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="tab" value="tracking">
                    
                    <select name="doc_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= $filterDoc === 'all' ? 'selected' : '' ?>>All Documents</option>
                        <option value="resident_signup" <?= $filterDoc === 'resident_signup' ? 'selected' : '' ?>>Resident Sign-ups</option>
                        <option value="assistance_program" <?= $filterDoc === 'assistance_program' ? 'selected' : '' ?>>Assistance Programs</option>
                    </select>

                    <select name="status_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>0: Draft</option>
                        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>1..N: In Hierarchy</option>
                        <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>777: Approved</option>
                        <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>555: Rejected</option>
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Document & Target</th>
                            <th>Category</th>
                            <th>Approve Order</th>
                            <th>Current Stage Approver</th>
                            <th>Submitted By</th>
                            <th>Last Action</th>
                            <th>Remarks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allApprovals)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No records match the filter criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allApprovals as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></div>
                                        <small class="text-muted">Doc ID: #<?= $row['document_id'] ?> &bull; Created <?= format_datetime($row['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= $row['document_type'] === 'resident_signup' ? 'Resident' : 'Ayuda Program' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= render_approval_order_badge($row['approve_order']) ?>
                                    </td>
                                    <td>
                                        <?php if ($row['current_approver_role']): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <?= strtoupper($row['current_approver_role']) ?>
                                            </span>
                                        <?php elseif ($row['approve_order'] == 777): ?>
                                            <span class="badge bg-success-subtle text-success">Completed</span>
                                        <?php elseif ($row['approve_order'] == 555): ?>
                                            <span class="badge bg-danger-subtle text-danger">Terminated</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-dark fw-semibold d-block"><?= htmlspecialchars($row['submitter_name'] ?? 'System') ?></small>
                                        <small class="text-muted"><?= ucfirst($row['submitter_role'] ?? 'user') ?></small>
                                    </td>
                                    <td>
                                        <?php if ($row['actor_name']): ?>
                                            <small class="d-block text-dark fw-semibold"><?= htmlspecialchars($row['actor_name']) ?></small>
                                            <small class="text-muted"><?= format_datetime($row['action_at']) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted fst-italic">Pending action</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 180px;" title="<?= htmlspecialchars($row['remarks'] ?? '') ?>">
                                            <?= htmlspecialchars($row['remarks'] ?: '-') ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($row['document_type'] === 'resident_signup'): ?>
                                            <a href="<?= url('residents/view?id=' . $row['document_id']) ?>" class="btn btn-xs btn-outline-secondary py-1 px-2">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        <?php elseif ($row['document_type'] === 'assistance_program'): ?>
                                            <a href="<?= url('social_services/view?id=' . $row['document_id']) ?>" class="btn btn-xs btn-outline-secondary py-1 px-2">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ============================================================== -->
    <!-- TAB 3: CHART OF AUTHORITY (APPROVERS SETTING)                  -->
    <!-- ============================================================== -->
    <?php elseif ($activeTab === 'authority'): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-diagram-3-fill text-success me-2"></i>Chart of Authority & Approver Matrix</h5>
                    <small class="text-muted">Configured document approver hierarchy and delegated signing limits</small>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info border border-info shadow-sm d-flex align-items-center gap-3 mb-4 rounded-4">
                    <i class="bi bi-info-circle-fill fs-3 text-info"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Centralized Approver Hierarchy Rules</h6>
                        <small class="text-dark">
                            <strong>Approveorder 0</strong>: Draft &bull; 
                            <strong>Approveorder 1, 2, 3...</strong>: Sequential Stage Approvers &bull; 
                            <strong>Approveorder 777</strong>: Final Approved &bull; 
                            <strong>Approveorder 555</strong>: Rejected.
                        </small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Document / Process Type</th>
                                <th class="text-center" style="width: 120px;">Approve Order</th>
                                <th>Designated Approver Role</th>
                                <th>Stage Title / Position</th>
                                <th>Delegated Scope & Authority Description</th>
                                <th class="text-center" style="width: 140px;">Next Hierarchy</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupedChart as $docType => $stages): ?>
                                <?php $count = count($stages); ?>
                                <?php foreach ($stages as $idx => $stage): ?>
                                    <tr>
                                        <?php if ($idx === 0): ?>
                                            <td rowspan="<?= $count ?>" class="fw-bold align-middle bg-light">
                                                <i class="bi <?= $docType === 'resident_signup' ? 'bi-person-check-fill text-primary' : 'bi-gift-fill text-success' ?> me-2"></i>
                                                <?= $docType === 'resident_signup' ? 'Resident Registration' : 'Assistance Program' ?>
                                                <div class="small text-muted fw-normal"><code><?= htmlspecialchars($docType) ?></code></div>
                                            </td>
                                        <?php endif; ?>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark px-3 py-2 fs-6">Order <?= $stage['approve_order'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary px-2 py-1 text-uppercase"><?= htmlspecialchars($stage['approver_role']) ?></span>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars($stage['approver_title']) ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= htmlspecialchars($stage['description'] ?? 'Standard stage validation') ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($idx + 1 < $count): ?>
                                                <span class="badge bg-info text-dark">Stage <?= $stages[$idx + 1]['approve_order'] ?> <i class="bi bi-arrow-right"></i></span>
                                            <?php else: ?>
                                                <span class="badge bg-success">777 (Approved)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

