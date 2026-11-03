<?php
// views/social_services/view.php - View Assistance Program Roster & Authorization Status
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'treasurer');

$db = getDB();
$user = auth_user();
$role = $user['role'];

$programId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT p.*, s.name AS target_sitio_name, u.full_name AS creator_name, u.role AS creator_role
    FROM assistance_programs p
    LEFT JOIN sitios s ON p.target_sitio_id = s.id
    LEFT JOIN users u ON p.created_by_user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$programId]);
$program = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$program) {
    set_flash('danger', 'Assistance program record not found.');
    redirect('social_services');
}

$pageTitle = 'Assistance Program: ' . $program['title'];

// Fetch Centralized Approval Status
$approval = get_approval_for('assistance_program', $programId, $db);
$approvalOrder = $approval ? (int)$approval['approve_order'] : 777;

// Fetch Beneficiaries
$stmtBen = $db->prepare("
    SELECT b.*, r.resident_code, r.first_name, r.middle_name, r.last_name, r.suffix,
           r.gender, r.age, r.is_pwd, r.is_solo_parent, r.is_indigent, r.is_senior, r.is_4ps, r.is_student,
           r.household_no, r.is_head_of_family,
           s.name AS sitio_name,
           u.full_name AS releaser_name
    FROM assistance_beneficiaries b
    JOIN residents r ON b.resident_id = r.id
    JOIN sitios s ON r.sitio_id = s.id
    LEFT JOIN users u ON b.released_by_user_id = u.id
    WHERE b.program_id = ?
    ORDER BY s.id ASC, r.last_name ASC
");
$stmtBen->execute([$programId]);
$beneficiaries = $stmtBen->fetchAll(PDO::FETCH_ASSOC);

$totalEnrolled = count($beneficiaries);
$totalClaimed = count(array_filter($beneficiaries, fn($b) => $b['status'] === 'Claimed'));
$totalDisbursed = array_sum(array_map(fn($b) => ($b['status'] === 'Claimed') ? (float)$b['received_amount'] : 0, $beneficiaries));
?>

<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <?php if ($role === 'secretary'): ?>
                    <a href="<?= url('social_services') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Programs
                    </a>
                <?php else: ?>
                    <a href="<?= url('approvals') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Approvals Hub
                    </a>
                <?php endif; ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                    <?= htmlspecialchars($program['category']) ?>
                </span>
                <span class="text-muted small font-monospace"><?= htmlspecialchars($program['program_code']) ?></span>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($program['title']) ?></h3>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('reports/print?program_id=' . $program['id']) ?>" target="_blank" class="btn btn-outline-dark fw-semibold shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Audit Payroll
            </a>
            <?php if ($role === 'treasurer' && $program['status'] === 'Ongoing'): ?>
                <a href="<?= url('social_services/distribute?program_id=' . $program['id']) ?>" class="btn btn-primary fw-bold shadow-sm">
                    <i class="bi bi-hand-index-thumb me-1"></i> Open Ayuda Desk
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approval Status Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden border-start border-4 <?= ($approvalOrder == 777) ? 'border-success' : (($approvalOrder == 555) ? 'border-danger' : 'border-warning') ?>">
        <div class="card-body p-4 bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fw-bold text-dark">Executive Approval Workflow:</span>
                    <?= render_approval_order_badge($approvalOrder) ?>
                </div>
                <?php if ($approvalOrder == 777): ?>
                    <small class="text-success fw-semibold">
                        <i class="bi bi-check-circle-fill me-1"></i> Authorized by Punong Barangay. Open for Treasurer disbursement.
                    </small>
                <?php elseif ($approvalOrder == 555): ?>
                    <small class="text-danger fw-semibold">
                        <i class="bi bi-x-circle-fill me-1"></i> Rejected. <?= htmlspecialchars($approval['remarks'] ?? '') ?>
                    </small>
                <?php else: ?>
                    <small class="text-warning-emphasis fw-semibold">
                        <i class="bi bi-clock-history me-1"></i> Created by Barangay Secretary (<?= htmlspecialchars($program['creator_name']) ?>). Awaiting Punong Barangay authorization.
                    </small>
                <?php endif; ?>
            </div>

            <!-- Captain Direct Action in View -->
            <?php if ($role === 'captain' && $approval && $approvalOrder != 777 && $approvalOrder != 555): ?>
                <div class="d-flex gap-2">
                    <form action="<?= url('approvals/action') ?>" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="approval_id" value="<?= $approval['id'] ?>">
                        <input type="hidden" name="redirect_to" value="social_services/view?id=<?= $program['id'] ?>">
                        <button type="submit" class="btn btn-success fw-bold px-3 shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Grant Approval [777]
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-danger fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalRejectView">
                        <i class="bi bi-x-circle me-1"></i> Reject [555]
                    </button>
                </div>

                <!-- Modal Reject -->
                <div class="modal fade" id="modalRejectView" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form action="<?= url('approvals/action') ?>" method="POST" class="modal-content border-0 shadow-lg rounded-4">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="approval_id" value="<?= $approval['id'] ?>">
                            <input type="hidden" name="redirect_to" value="social_services/view?id=<?= $program['id'] ?>">

                            <div class="modal-header bg-danger text-white border-0 py-3">
                                <h5 class="modal-title fw-bold">Reject Program [555]</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <label class="form-label fw-bold text-danger">Reason for Rejection <span class="text-danger">*</span></label>
                                <textarea name="remarks" class="form-control" rows="3" required placeholder="Specify reason for rejection..."></textarea>
                            </div>
                            <div class="modal-footer border-0 p-3 bg-light">
                                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger fw-bold px-4">Confirm Rejection</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Details Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4 bg-light">
            <div class="row g-3">
                <div class="col-md-3">
                    <span class="text-muted small d-block">Scheduled Distribution Date:</span>
                    <strong class="text-dark fs-6"><?= format_date($program['distribution_date']) ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Target Sitio / Jurisdiction:</span>
                    <strong class="text-dark fs-6"><?= $program['target_sitio_name'] ? htmlspecialchars($program['target_sitio_name']) : 'All Sitios (Barangay-wide)' ?></strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Benefit Rate per Head:</span>
                    <strong class="text-success fs-6"><?= format_peso($program['amount_per_beneficiary']) ?></strong>
                    <?php if ($program['in_kind_details']): ?>
                        <div class="text-muted small">+ <?= htmlspecialchars($program['in_kind_details']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block">Fund Source:</span>
                    <strong class="text-dark fs-6"><?= htmlspecialchars($program['fund_source']) ?></strong>
                </div>
                <div class="col-12 border-top pt-2 mt-2">
                    <span class="text-muted small d-block">Program Purpose / Objectives:</span>
                    <div class="text-dark"><?= nl2br(htmlspecialchars($program['description'])) ?></div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white p-3 border-0 d-flex flex-wrap justify-content-around text-center">
            <div>
                <span class="text-muted small">Total Pre-Enrolled</span>
                <h4 class="fw-bold text-dark mb-0"><?= $totalEnrolled ?></h4>
            </div>
            <div class="border-start ps-4">
                <span class="text-muted small">Disbursed Claims</span>
                <h4 class="fw-bold text-success mb-0"><?= $totalClaimed ?></h4>
            </div>
            <div class="border-start ps-4">
                <span class="text-muted small">Unclaimed Balance</span>
                <h4 class="fw-bold text-warning-emphasis mb-0"><?= $totalEnrolled - $totalClaimed ?></h4>
            </div>
            <div class="border-start ps-4">
                <span class="text-muted small">Total Liquidated Aid</span>
                <h4 class="fw-bold text-primary mb-0"><?= format_peso($totalDisbursed) ?></h4>
            </div>
        </div>
    </div>

    <!-- Beneficiary Masterlist -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-people-fill text-success me-2"></i>Enrolled Beneficiaries Roster (<?= $totalEnrolled ?>)
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>#</th>
                        <th>Resident Code & Name</th>
                        <th>Sitio & Address</th>
                        <th>Household</th>
                        <th>Vulnerability Profiling</th>
                        <th>Benefit Entitlement</th>
                        <th>Claim Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($beneficiaries)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No beneficiaries enrolled in this program yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($beneficiaries as $idx => $b): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name'] . ' ' . $b['middle_name'] . ' ' . $b['suffix']) ?>
                                    </div>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($b['resident_code']) ?> &bull; <?= $b['age'] ?>y &bull; <?= $b['gender'] ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($b['sitio_name']) ?></span>
                                </td>
                                <td>
                                    <?php if ($b['household_no']): ?>
                                        <small class="text-dark font-monospace"><?= htmlspecialchars($b['household_no']) ?></small>
                                        <?php if ($b['is_head_of_family']): ?>
                                            <span class="badge bg-primary-subtle text-primary border" style="font-size:0.65rem;">Head</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php if ($b['is_pwd']): ?><span class="badge badge-pwd">PWD</span><?php endif; ?>
                                        <?php if ($b['is_solo_parent']): ?><span class="badge badge-solo">Solo Parent</span><?php endif; ?>
                                        <?php if ($b['is_indigent']): ?><span class="badge badge-indigent">Indigent</span><?php endif; ?>
                                        <?php if ($b['is_senior']): ?><span class="badge badge-senior">Senior</span><?php endif; ?>
                                        <?php if ($b['is_4ps']): ?><span class="badge badge-4ps">4Ps</span><?php endif; ?>
                                        <?php if ($b['is_student']): ?><span class="badge bg-primary">Student</span><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success"><?= format_peso($b['received_amount']) ?></strong>
                                    <?php if ($b['received_items']): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($b['received_items']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= status_badge($b['status']) ?>
                                    <?php if ($b['status'] === 'Claimed'): ?>
                                        <small class="d-block text-muted"><?= format_datetime($b['claimed_at']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

