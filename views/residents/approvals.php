<?php
// residents/approvals - Resident Approvals Queue for Barangay Secretary & Administration
require_once __DIR__ . '/../../config/app.php';

require_role('secretary', 'captain');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'Resident Approval Queue';

// Handle Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $residentId = (int)($_POST['resident_id'] ?? 0);
    $action = $_POST['action'];

    // Verify resident exists
    $stmt = $db->prepare("SELECT r.*, u.id AS linked_user_id FROM residents r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([$residentId]);
    $res = $stmt->fetch();

    if (!$res) {
        set_flash('danger', 'Resident record not found.');
        redirect('residents/approvals');
    }

    // Permission check: Only Secretary and Captain have approval authority
    if (!in_array($role, ['secretary', 'captain'])) {
        set_flash('danger', 'Access Restricted: Only the Barangay Secretary has authority to verify and approve resident signups.');
        redirect('residents/approvals');
    }

    if ($action === 'approve') {
        $assignedHh = clean($_POST['assigned_household_no'] ?? '');
        try {
            $db->beginTransaction();

            // 0. Ensure resident has a valid, unique resident code
            if (empty($res['resident_code'])) {
                $uniqueCode = generate_resident_code($db);
                $db->prepare("UPDATE residents SET resident_code = ? WHERE id = ?")->execute([$uniqueCode, $residentId]);
            }

            // 1. Update resident status (and assign household_no if provided)
            if (!empty($assignedHh) && !in_array(strtolower($assignedHh), ['n/a', 'na', 'none', 'null', '-'])) {
                $updateRes = $db->prepare("
                    UPDATE residents 
                    SET status = 'approved', approved_at = NOW(), approved_by_user_id = ?, rejection_reason = NULL,
                        household_no = ?
                    WHERE id = ?
                ");
                $updateRes->execute([$user['id'], $assignedHh, $residentId]);
            } else {
                $updateRes = $db->prepare("
                    UPDATE residents 
                    SET status = 'approved', approved_at = NOW(), approved_by_user_id = ?, rejection_reason = NULL 
                    WHERE id = ?
                ");
                $updateRes->execute([$user['id'], $residentId]);
            }

            // 2. Activate user account if linked
            if ($res['linked_user_id']) {
                $updateUser = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $updateUser->execute([$res['linked_user_id']]);
            }

            // 3. Sync Centralized Approvals table (Code: 777)
            $db->prepare("
                UPDATE approvals 
                SET approve_order = 777, current_approver_role = NULL, action_by_user_id = ?, action_at = NOW(), remarks = 'Approved via Resident Verification Queue', updated_at = NOW() 
                WHERE document_type = 'resident_signup' AND document_id = ?
            ")->execute([$user['id'], $residentId]);

            $db->commit();

            log_activity('Approve Resident', "Resident {$res['first_name']} {$res['last_name']} approved by {$user['full_name']} for Sitio " . SITIOS_LIST[$res['sitio_id']]);
            set_flash('success', "Resident <strong>{$res['first_name']} {$res['last_name']}</strong> has been approved and activated successfully!");
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Error approving resident: ' . $e->getMessage());
        }
        redirect('residents/approvals');

    } elseif ($action === 'reject') {
        $reason = clean($_POST['rejection_reason'] ?? 'Registration details could not be verified by Barangay Secretary.');
        try {
            $db->beginTransaction();

            $updateRes = $db->prepare("
                UPDATE residents 
                SET status = 'rejected', approved_at = NOW(), approved_by_user_id = ?, rejection_reason = ? 
                WHERE id = ?
            ");
            $updateRes->execute([$user['id'], $reason, $residentId]);

            if ($res['linked_user_id']) {
                $updateUser = $db->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
                $updateUser->execute([$res['linked_user_id']]);
            }

            // Sync Centralized Approvals table (Code: 555)
            $db->prepare("
                UPDATE approvals 
                SET approve_order = 555, current_approver_role = NULL, action_by_user_id = ?, action_at = NOW(), remarks = ?, updated_at = NOW() 
                WHERE document_type = 'resident_signup' AND document_id = ?
            ")->execute([$user['id'], $reason, $residentId]);

            $db->commit();

            log_activity('Reject Resident', "Resident {$res['first_name']} {$res['last_name']} rejected by {$user['full_name']}. Reason: {$reason}");
            set_flash('warning', "Resident registration for {$res['first_name']} {$res['last_name']} has been rejected.");
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Error updating resident: ' . $e->getMessage());
        }
        redirect('residents/approvals');
    }
}

// Filter by Sitio: Secretary and Captain can view all or filter by Sitio
$filterSitio = isset($_GET['sitio']) ? (int)$_GET['sitio'] : 0;

// Fetch Pending Residents
$sql = "
    SELECT r.*, s.name AS sitio_name, s.code AS sitio_code, u.username, u.email AS user_email
    FROM residents r
    JOIN sitios s ON r.sitio_id = s.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.status = 'pending'
";
$params = [];

if ($filterSitio > 0) {
    $sql .= " AND r.sitio_id = ?";
    $params[] = $filterSitio;
}

$sql .= " ORDER BY r.registered_at ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pendingResidents = $stmt->fetchAll();

// Count of pending per Sitio for tabs
$countsQuery = $db->query("
    SELECT sitio_id, COUNT(*) as count 
    FROM residents 
    WHERE status = 'pending' 
    GROUP BY sitio_id
");
$sitioCounts = [];
while ($row = $countsQuery->fetch()) {
    $sitioCounts[$row['sitio_id']] = (int)$row['count'];
}

// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-0">Resident Verification & Approvals</h3>
        <p class="text-muted small mb-0">
            Secretary Review Queue: Review, verify, and approve resident signups across all 7 Sitios of Barangay Lublub
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('residents') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-people me-1"></i> Approved Resident Directory
        </a>
    </div>
</div>

<!-- Sitio Selector Tabs (Visible to Captain and Secretary) -->
<?php if (in_array($role, ['captain', 'secretary'])): ?>
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-2 bg-white rounded-3">
            <ul class="nav nav-pills flex-nowrap overflow-x-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($filterSitio === 0) ? 'active bg-success' : 'text-dark' ?>" href="<?= url('residents/approvals?sitio=0') ?>">
                        All Sitios (<?= array_sum($sitioCounts) ?>)
                    </a>
                </li>
                <?php foreach (SITIOS_LIST as $id => $name): ?>
                    <?php $count = $sitioCounts[$id] ?? 0; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($filterSitio === $id) ? 'active bg-success' : 'text-dark' ?>" href="<?= url('residents/approvals?sitio=' . $id) ?>">
                            <?= htmlspecialchars($name) ?>
                            <?php if ($count > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?= $count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Pending List Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-check-fill text-warning fs-5"></i>
            <h5 class="fw-bold mb-0">
                Pending Registrations 
                <?php if ($filterSitio > 0 && isset(SITIOS_LIST[$filterSitio])): ?>
                    in <?= htmlspecialchars(SITIOS_LIST[$filterSitio]) ?>
                <?php endif; ?>
            </h5>
        </div>
        <span class="badge bg-warning text-dark px-3 py-2 fw-semibold">
            <?= count($pendingResidents) ?> Awaiting Approval
        </span>
    </div>

    <?php if (empty($pendingResidents)): ?>
        <div class="card-body p-5 text-center">
            <div class="display-3 text-success mb-3"><i class="bi bi-check-circle"></i></div>
            <h4 class="fw-bold text-dark">No Pending Registrations Found</h4>
            <p class="text-muted mb-0">
                All resident signups have been verified and processed by the Barangay Secretary.
            </p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Resident Info</th>
                        <th>Sitio & Address</th>
                        <th>Social Vulnerability Profiling</th>
                        <th>Registered Date</th>
                        <th class="text-end" style="min-width: 220px;">Review & Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingResidents as $r): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($r['photo_path']) && file_exists(BASE_PATH . '/' . $r['photo_path'])): ?>
                                        <img src="<?= url($r['photo_path']) ?>" alt="Selfie" class="rounded-circle border border-2 border-success object-fit-cover shadow-sm" style="width: 48px; height: 48px;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light border border-2 border-success d-flex align-items-center justify-content-center fw-bold text-success" style="width: 48px; height: 48px; font-size: 1.1rem;">
                                            <?= strtoupper(substr($r['first_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-dark fs-6">
                                            <?= htmlspecialchars($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name'] . ' ' . $r['suffix']) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= $r['age'] ?> yrs old &bull; <?= $r['gender'] ?> &bull; <?= $r['civil_status'] ?>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($r['contact_no'] ?: 'No phone') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold mb-1">
                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($r['sitio_name']) ?>
                                </span>
                                <div class="small text-dark"><?= htmlspecialchars($r['address_line']) ?></div>
                                <div class="small text-muted">
                                    HH No: <?= !empty($r['household_no']) ? '<strong>' . htmlspecialchars($r['household_no']) . '</strong>' : '<span class="badge bg-warning-subtle text-warning-emphasis border">Unassigned</span>' ?> 
                                    (<?= $r['is_head_of_family'] ? 'Head of Family' : $r['family_role'] ?>)
                                </div>
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if ($r['is_pwd']): ?>
                                        <span class="badge badge-pwd" data-bs-toggle="tooltip" title="<?= htmlspecialchars($r['pwd_type'] ?: 'Disability') ?>">
                                            <i class="bi bi-person-wheelchair me-1"></i>PWD: <?= htmlspecialchars($r['pwd_type'] ?: 'Yes') ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($r['is_solo_parent']): ?>
                                        <span class="badge badge-solo">
                                            <i class="bi bi-person-heart me-1"></i>Solo Mother (<?= $r['dependent_children_count'] ?> Dep)
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($r['is_indigent']): ?>
                                        <span class="badge badge-indigent">
                                            <i class="bi bi-wallet2 me-1"></i>Indigent (<?= format_peso($r['monthly_income']) ?>/mo)
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($r['is_senior']): ?>
                                        <span class="badge badge-senior">
                                            <i class="bi bi-person-cane me-1"></i>Senior Citizen
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($r['is_4ps']): ?>
                                        <span class="badge badge-4ps">
                                            <i class="bi bi-check-circle me-1"></i>4Ps Member
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($r['is_student'])): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                            <i class="bi bi-mortarboard-fill me-1"></i>Student: <?= htmlspecialchars($r['school_name'] ?: 'Enrolled') ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($r['is_voter']): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <i class="bi bi-card-checklist me-1"></i>Voter (Precinct <?= htmlspecialchars($r['precinct_no'] ?: 'N/A') ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="small text-muted">
                                <div><?= format_date($r['registered_at']) ?></div>
                                <div style="font-size: 0.72rem;"><?= date('h:i A', strtotime($r['registered_at'])) ?></div>
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <!-- Full Details Modal Trigger -->
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalDetails<?= $r['id'] ?>" title="View Complete Profiling Information">
                                        <i class="bi bi-eye"></i> Details
                                    </button>

                                    <?php if (in_array($role, ['secretary', 'captain'])): ?>
                                        <!-- Quick Approve Form (Barangay Secretary / Captain) -->
                                        <form action="<?= url('residents/approvals') ?>" method="POST" onsubmit="return confirm('Approve registration for <?= htmlspecialchars(addslashes($r['first_name'] . ' ' . $r['last_name'])) ?>?');">
                                            <input type="hidden" name="resident_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success fw-bold">
                                                <i class="bi bi-check-lg me-1"></i> Approve
                                            </button>
                                        </form>

                                        <!-- Reject Modal Trigger -->
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalReject<?= $r['id'] ?>">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- MODAL 1: COMPLETE DETAILS -->
                                <div class="modal fade text-start" id="modalDetails<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title fw-bold">
                                                    <i class="bi bi-person-lines-fill me-2"></i>Resident Profiling: <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <!-- Visual Verification: Live Selfie vs Valid ID -->
                                                <div class="card bg-light border-0 mb-4 p-3 rounded-3">
                                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-success me-2"></i>Visual Identity & Dwelling Verification (Selfie, ID Card, House Photo)</h6>
                                                    <div class="row g-3">
                                                        <div class="<?= !empty($r['house_photo_path']) ? 'col-sm-4' : 'col-sm-6' ?> text-center">
                                                            <div class="border rounded-3 p-2 bg-white shadow-sm h-100 d-flex flex-column align-items-center justify-content-center">
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">
                                                                    <i class="bi bi-camera me-1"></i>Realtime Selfie Capture
                                                                </span>
                                                                <?php if (!empty($r['photo_path']) && file_exists(BASE_PATH . '/' . $r['photo_path'])): ?>
                                                                    <a href="<?= url($r['photo_path']) ?>" class="preview-attachment-btn d-block" data-preview-title="Realtime Selfie Capture" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?> &bull; Resident Verification" title="View in Modal">
                                                                        <img src="<?= url($r['photo_path']) ?>" alt="Selfie" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;">
                                                                    </a>
                                                                    <div class="mt-2 small text-muted"><button type="button" class="btn btn-xs btn-outline-success preview-attachment-btn py-0 px-2" data-preview-url="<?= url($r['photo_path']) ?>" data-preview-title="Realtime Selfie Capture" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>"><i class="bi bi-arrows-fullscreen me-1"></i>Enlarge Modal</button></div>
                                                                <?php else: ?>
                                                                    <div class="py-4 text-muted">
                                                                        <i class="bi bi-person-bounding-box fs-1 d-block mb-1 text-secondary"></i>
                                                                        <span class="small">No live selfie uploaded</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="<?= !empty($r['house_photo_path']) ? 'col-sm-4' : 'col-sm-6' ?> text-center">
                                                            <div class="border rounded-3 p-2 bg-white shadow-sm h-100 d-flex flex-column align-items-center justify-content-center">
                                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">
                                                                    <i class="bi bi-card-heading me-1"></i>Government / Resident ID Card
                                                                </span>
                                                                <?php if (!empty($r['id_card_path']) && file_exists(BASE_PATH . '/' . $r['id_card_path'])): ?>
                                                                    <?php 
                                                                        $ext = strtolower(pathinfo($r['id_card_path'], PATHINFO_EXTENSION));
                                                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])):
                                                                    ?>
                                                                        <a href="<?= url($r['id_card_path']) ?>" class="preview-attachment-btn d-block" data-preview-title="Government / Resident ID Card" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>" title="Click to view full ID document">
                                                                            <img src="<?= url($r['id_card_path']) ?>" alt="ID Document" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;">
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <div class="py-3">
                                                                            <i class="bi bi-file-earmark-pdf fs-1 text-danger d-block mb-1"></i>
                                                                            <span class="small text-muted d-block mb-2">PDF Document Attached</span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="mt-2 small text-muted">
                                                                        <button type="button" class="btn btn-xs btn-outline-primary preview-attachment-btn py-1 px-2" data-preview-url="<?= url($r['id_card_path']) ?>" data-preview-title="Government / Resident ID Card" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>">
                                                                            <i class="bi bi-arrows-fullscreen me-1"></i>Open ID Modal
                                                                        </button>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="py-4 text-muted">
                                                                        <i class="bi bi-file-earmark-x fs-1 d-block mb-1 text-secondary"></i>
                                                                        <span class="small">No ID document attached</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <?php if (!empty($r['house_photo_path'])): ?>
                                                            <div class="col-sm-4 text-center">
                                                                <div class="border rounded-3 p-2 bg-white shadow-sm h-100 d-flex flex-column align-items-center justify-content-center">
                                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle mb-2">
                                                                        <i class="bi bi-houses me-1"></i>Dwelling Exterior Photo
                                                                    </span>
                                                                    <?php if (file_exists(BASE_PATH . '/' . $r['house_photo_path'])): ?>
                                                                        <a href="<?= url($r['house_photo_path']) ?>" class="preview-attachment-btn d-block" data-preview-title="Dwelling Exterior Photo" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?> &bull; Sitio <?= htmlspecialchars($r['sitio_name']) ?>" title="Click to view full house photo">
                                                                            <img src="<?= url($r['house_photo_path']) ?>" alt="House Photo" class="img-fluid rounded border shadow-sm" style="max-height: 180px; object-fit: contain;">
                                                                        </a>
                                                                        <div class="mt-2 small text-muted">
                                                                            <button type="button" class="btn btn-xs btn-outline-info preview-attachment-btn py-1 px-2" data-preview-url="<?= url($r['house_photo_path']) ?>" data-preview-title="Dwelling Exterior Photo" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>">
                                                                                <i class="bi bi-arrows-fullscreen me-1"></i>Open House Modal
                                                                            </button>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="py-4 text-muted">
                                                                            <i class="bi bi-house-door fs-1 d-block mb-1 text-secondary"></i>
                                                                            <span class="small">House photo file</span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <h6 class="fw-bold text-success border-bottom pb-1">Personal Details</h6>
                                                        <div><strong>Full Name:</strong> <?= htmlspecialchars($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name'] . ' ' . $r['suffix']) ?></div>
                                                        <div><strong>Alias / Nickname:</strong> <?= htmlspecialchars($r['nickname'] ?: 'None') ?></div>
                                                        <div><strong>Gender:</strong> <?= $r['gender'] ?></div>
                                                        <div><strong>Civil Status:</strong> <?= $r['civil_status'] ?></div>
                                                        <div><strong>Birthdate:</strong> <?= format_date($r['birth_date']) ?> (<?= $r['age'] ?> years old)</div>
                                                        <div><strong>Contact:</strong> <?= htmlspecialchars($r['contact_no'] ?: 'None') ?></div>
                                                        <div><strong>Email:</strong> <?= htmlspecialchars($r['email'] ?: 'None') ?></div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="fw-bold text-success border-bottom pb-1">Sitio & Residence</h6>
                                                        <div><strong>Sitio:</strong> <?= htmlspecialchars($r['sitio_name']) ?> (Sitio <?= $r['sitio_id'] ?>)</div>
                                                        <div><strong>Address:</strong> <?= htmlspecialchars($r['address_line']) ?></div>
                                                        <div><strong>Household No:</strong> <?= !empty($r['household_no']) ? '<span class="fw-bold text-success">' . htmlspecialchars($r['household_no']) . '</span>' : '<span class="badge bg-warning-subtle text-warning-emphasis border">Unassigned (Can set below)</span>' ?></div>
                                                        <div><strong>Role in Family:</strong> <?= htmlspecialchars($r['family_role']) ?> (Head: <?= $r['is_head_of_family'] ? 'Yes' : 'No' ?>)</div>
                                                    </div>
                                                </div>

                                                <h6 class="fw-bold text-danger border-bottom pb-1 mt-4">Social Welfare & Vulnerability Profile (with Attached IDs)</h6>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-4">
                                                        <div class="p-2 border rounded bg-light h-100">
                                                            <strong>PWD Status:</strong> <?= $r['is_pwd'] ? '<span class="text-danger fw-bold">YES</span>' : 'No' ?><br>
                                                            <small class="text-muted">Type: <?= htmlspecialchars($r['pwd_type'] ?: 'N/A') ?></small><br>
                                                            <small class="text-muted">ID: <?= htmlspecialchars($r['pwd_id_no'] ?: 'N/A') ?></small>
                                                            <?php if (!empty($r['pwd_id_photo']) && file_exists(BASE_PATH . '/' . $r['pwd_id_photo'])): ?>
                                                                <div class="mt-1">
                                                                    <button type="button" class="btn btn-xs btn-outline-primary preview-attachment-btn py-0 px-2" data-preview-url="<?= url($r['pwd_id_photo']) ?>" data-preview-title="PWD ID Attachment" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?> &bull; PWD ID: <?= htmlspecialchars($r['pwd_id_no'] ?: 'On File') ?>" style="font-size: 0.72rem;">
                                                                        <i class="bi bi-arrows-fullscreen me-1"></i>View PWD ID Modal
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-2 border rounded bg-light h-100">
                                                            <strong>Solo Parent:</strong> <?= $r['is_solo_parent'] ? '<span class="text-danger fw-bold">YES</span>' : 'No' ?><br>
                                                            <small class="text-muted">Children: <?= $r['dependent_children_count'] ?></small><br>
                                                            <small class="text-muted">ID: <?= htmlspecialchars($r['solo_parent_id_no'] ?: 'N/A') ?></small>
                                                            <?php if (!empty($r['solo_parent_id_photo']) && file_exists(BASE_PATH . '/' . $r['solo_parent_id_photo'])): ?>
                                                                <div class="mt-1">
                                                                    <button type="button" class="btn btn-xs btn-outline-danger preview-attachment-btn py-0 px-2" data-preview-url="<?= url($r['solo_parent_id_photo']) ?>" data-preview-title="Solo Parent ID Attachment" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>" style="font-size: 0.72rem;">
                                                                        <i class="bi bi-arrows-fullscreen me-1"></i>View Solo Parent Modal
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-2 border rounded bg-light h-100">
                                                            <strong>Indigent Household:</strong> <?= $r['is_indigent'] ? '<span class="text-warning-emphasis fw-bold">YES</span>' : 'No' ?><br>
                                                            <small class="text-muted">Income: <?= format_peso($r['monthly_income']) ?></small><br>
                                                            <small class="text-muted">Source: <?= htmlspecialchars($r['income_source'] ?: 'N/A') ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-2 border rounded bg-light h-100">
                                                            <strong>Senior Citizen:</strong> <?= $r['is_senior'] ? '<span class="text-primary fw-bold">YES</span>' : 'No' ?><br>
                                                            <small class="text-muted">ID: <?= htmlspecialchars($r['senior_id_no'] ?: 'N/A') ?></small>
                                                            <?php if (!empty($r['senior_id_photo']) && file_exists(BASE_PATH . '/' . $r['senior_id_photo'])): ?>
                                                                <div class="mt-1">
                                                                    <button type="button" class="btn btn-xs btn-outline-info preview-attachment-btn py-0 px-2" data-preview-url="<?= url($r['senior_id_photo']) ?>" data-preview-title="Senior Citizen / OSCA ID Attachment" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?> &bull; OSCA ID: <?= htmlspecialchars($r['senior_id_no'] ?: 'On File') ?>" style="font-size: 0.72rem;">
                                                                        <i class="bi bi-arrows-fullscreen me-1"></i>View OSCA ID Modal
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-2 border rounded bg-light h-100">
                                                            <strong>4Ps Member:</strong> <?= $r['is_4ps'] ? '<span class="text-success fw-bold">YES</span>' : 'No' ?><br>
                                                            <small class="text-muted">4Ps ID: <?= htmlspecialchars($r['four_ps_id_no'] ?: 'N/A') ?></small>
                                                            <?php if (!empty($r['four_ps_id_photo']) && file_exists(BASE_PATH . '/' . $r['four_ps_id_photo'])): ?>
                                                                <div class="mt-1">
                                                                    <button type="button" class="btn btn-xs btn-outline-success preview-attachment-btn py-0 px-2" data-preview-url="<?= url($r['four_ps_id_photo']) ?>" data-preview-title="4Ps ID Card Attachment" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>" style="font-size: 0.72rem;">
                                                                        <i class="bi bi-arrows-fullscreen me-1"></i>View 4Ps ID Modal
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-2 border rounded bg-light h-100">
                                                            <strong>Student:</strong> <?= !empty($r['is_student']) ? '<span class="text-info fw-bold">YES</span>' : 'No' ?><br>
                                                            <small class="text-muted">School: <?= htmlspecialchars($r['school_name'] ?: 'N/A') ?></small><br>
                                                            <small class="text-muted">ID/LRN: <?= htmlspecialchars($r['student_id_no'] ?: 'N/A') ?></small>
                                                            <?php if (!empty($r['school_id_photo']) && file_exists(BASE_PATH . '/' . $r['school_id_photo'])): ?>
                                                                <div class="mt-1">
                                                                    <button type="button" class="btn btn-xs btn-outline-info preview-attachment-btn py-0 px-2" data-preview-url="<?= url($r['school_id_photo']) ?>" data-preview-title="Student School ID Attachment" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?> &bull; School: <?= htmlspecialchars($r['school_name'] ?: 'Enrolled') ?>" style="font-size: 0.72rem;">
                                                                        <i class="bi bi-arrows-fullscreen me-1"></i>View School ID Modal
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="p-2 border rounded bg-light h-100">
                                                            <strong>Voter Registration:</strong> <?= $r['is_voter'] ? '<span class="text-success fw-bold">YES</span>' : 'No' ?><br>
                                                            <small class="text-muted">Precinct: <?= htmlspecialchars($r['precinct_no'] ?: 'N/A') ?></small>
                                                            <?php if (!empty($r['voter_id_photo']) && file_exists(BASE_PATH . '/' . $r['voter_id_photo'])): ?>
                                                                <div class="mt-1">
                                                                    <button type="button" class="btn btn-xs btn-outline-secondary preview-attachment-btn py-0 px-2" data-preview-url="<?= url($r['voter_id_photo']) ?>" data-preview-title="Voter's ID / Registration Stub" data-preview-subtitle="<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>" style="font-size: 0.72rem;">
                                                                        <i class="bi bi-arrows-fullscreen me-1"></i>View Voter's ID Modal
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <h6 class="fw-bold text-dark border-bottom pb-1 mt-3">Health & Emergency Contact</h6>
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <div><strong>Blood Type:</strong> <?= htmlspecialchars($r['blood_type'] ?: 'Not Stated') ?></div>
                                                        <div><strong>Medical Conditions:</strong> <?= htmlspecialchars($r['medical_conditions'] ?: 'None Reported') ?></div>
                                                        <div><strong>PhilHealth No:</strong> <?= htmlspecialchars($r['philhealth_no'] ?: 'None') ?></div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div><strong>Emergency Contact:</strong> <?= htmlspecialchars($r['emergency_contact_name'] ?: 'None') ?></div>
                                                        <div><strong>Contact Phone:</strong> <?= htmlspecialchars($r['emergency_contact_no'] ?: 'None') ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light d-block p-3">
                                                <?php if (in_array($role, ['secretary', 'captain'])): ?>
                                                    <form action="<?= url('residents/approvals') ?>" method="POST" class="w-100">
                                                        <input type="hidden" name="resident_id" value="<?= $r['id'] ?>">
                                                        <input type="hidden" name="action" value="approve">

                                                        <div class="p-3 bg-success bg-opacity-10 border border-success-subtle rounded-3 mb-3">
                                                            <label class="form-label fw-bold text-success mb-1">
                                                                <i class="bi bi-house-door-fill me-1"></i> Official Household / Family Number Profiling
                                                            </label>
                                                            <div class="row g-2 align-items-center">
                                                                <div class="col-md-7">
                                                                    <input type="text" name="assigned_household_no" class="form-control" placeholder="e.g. HH-<?= htmlspecialchars($r['sitio_code'] ?? 'LUB') ?>-045" value="<?= htmlspecialchars($r['household_no'] ?: '') ?>">
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <span class="small text-muted">Secretary can assign or refine the household number now, or leave blank to update later.</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="d-flex justify-content-end gap-2">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-success fw-bold px-4">
                                                                <i class="bi bi-check-lg me-1"></i> Approve & Save Household No.
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- MODAL 2: REJECT REASON -->
                                <div class="modal fade text-start" id="modalReject<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="<?= url('residents/approvals') ?>" method="POST">
                                                <input type="hidden" name="resident_id" value="<?= $r['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title fw-bold">Reject Resident Registration</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to reject the registration of <strong><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></strong>?</p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
                                                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="e.g. Not an actual resident of Sitio, incomplete identification documents, duplicate entry..." required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger fw-bold">Confirm Rejection</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php // Handled by index.php container ?>

