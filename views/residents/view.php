<?php
// residents/view - Comprehensive Resident Profile & Assistance History
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'treasurer', 'bhw', 'resident');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid resident ID.');
    redirect('residents');
}

// Fetch Resident Record
$stmt = $db->prepare("
    SELECT r.*, s.name AS sitio_name, s.code AS sitio_code,
           ub.full_name AS assigned_bhw_name,
           a.full_name AS approver_name,
           arch.full_name AS archiver_name
    FROM residents r
    JOIN sitios s ON r.sitio_id = s.id
    LEFT JOIN users ub ON s.id = ub.assigned_sitio_id AND ub.role = 'bhw'
    LEFT JOIN users a ON r.approved_by_user_id = a.id
    LEFT JOIN users arch ON r.archived_by_user_id = arch.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$resident = $stmt->fetch();

if (!$resident) {
    set_flash('danger', 'Resident not found.');
    redirect('residents');
}

// Handle Resident Archive, Restore, and Delete from Profile View
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = clean($_POST['action']);
    
    if ($role === 'bhw') {
        set_flash('danger', 'BHW accounts are not authorized to archive or delete resident records.');
        redirect('residents/view?id=' . $id);
    }

    if ($action === 'archive_resident') {
        $reason = clean($_POST['archive_reason'] ?? 'Relocated / Transferred');
        if ($reason === 'Other' && !empty($_POST['custom_archive_reason'])) {
            $reason = clean($_POST['custom_archive_reason']);
        }
        $db->prepare("UPDATE residents SET is_archived = 1, archived_at = NOW(), archived_reason = ?, archived_by_user_id = ? WHERE id = ?")
           ->execute([$reason, $user['id'], $id]);

        if (!empty($resident['user_id'])) {
            $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?")->execute([$resident['user_id']]);
        }
        if (!empty($resident['household_no']) && $resident['is_head_of_family']) {
            $db->prepare("UPDATE households SET head_resident_name = NULL WHERE household_no = ? AND head_resident_name LIKE ?")
               ->execute([$resident['household_no'], '%' . $resident['last_name'] . '%']);
        }
        log_activity('Archive Resident', "Resident {$resident['first_name']} {$resident['last_name']} (Code: {$resident['resident_code']}) archived. Reason: {$reason}");
        set_flash('warning', "Resident profile moved to Archives (Reason: {$reason}).");
        redirect('residents?tab=archived');

    } elseif ($action === 'restore_resident') {
        $db->prepare("UPDATE residents SET is_archived = 0, archived_at = NULL, archived_reason = NULL, archived_by_user_id = NULL WHERE id = ?")
           ->execute([$id]);

        if (!empty($resident['user_id'])) {
            $db->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$resident['user_id']]);
        }
        log_activity('Restore Resident', "Resident {$resident['first_name']} {$resident['last_name']} (Code: {$resident['resident_code']}) restored to active masterlist.");
        set_flash('success', "Resident profile has been restored to the Active Resident Masterlist.");
        redirect('residents/view?id=' . $id);

    } elseif ($action === 'delete_resident') {
        if (!in_array($role, ['captain', 'secretary'])) {
            set_flash('danger', 'Permanent deletion requires Barangay Captain or Secretary authorization.');
            redirect('residents/view?id=' . $id);
        }
        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM assistance_beneficiaries WHERE resident_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM barangay_certificates WHERE resident_id = ?")->execute([$id]);
            if (!empty($resident['user_id'])) {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$resident['user_id']]);
            }
            if (!empty($resident['household_no']) && $resident['is_head_of_family']) {
                $db->prepare("UPDATE households SET head_resident_name = NULL WHERE household_no = ? AND head_resident_name LIKE ?")
                   ->execute([$resident['household_no'], '%' . $resident['last_name'] . '%']);
            }
            $db->prepare("DELETE FROM residents WHERE id = ?")->execute([$id]);
            $db->commit();

            log_activity('Delete Resident', "Permanently deleted resident {$resident['first_name']} {$resident['last_name']} (Code: {$resident['resident_code']}).");
            set_flash('danger', "Resident profile for {$resident['first_name']} {$resident['last_name']} has been permanently deleted.");
            redirect('residents');
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Failed to delete resident: ' . $e->getMessage());
            redirect('residents/view?id=' . $id);
        }
    }
}

// If resident role, only allow viewing their own profile
if ($role === 'resident' && $resident['user_id'] != $user['id']) {
    set_flash('danger', 'Access denied.');
    redirect('dashboard');
}

// Fetch Ayuda / Assistance Received History
$aidStmt = $db->prepare("
    SELECT b.*, p.title AS program_title, p.category, p.distribution_date, u.full_name AS releaser_name
    FROM assistance_beneficiaries b
    JOIN assistance_programs p ON b.program_id = p.id
    LEFT JOIN users u ON b.released_by_user_id = u.id
    WHERE b.resident_id = ?
    ORDER BY p.distribution_date DESC
");
$aidStmt->execute([$id]);
$assistanceHistory = $aidStmt->fetchAll();

// Fetch Linked Household Record & BHW Documentation
$householdInfo = null;
if (!empty($resident['household_no'])) {
    $hhStmt = $db->prepare("
        SELECT h.*, u.full_name AS bhw_name 
        FROM households h 
        LEFT JOIN users u ON h.bhw_assessed_by_user_id = u.id 
        WHERE h.household_no = ?
    ");
    $hhStmt->execute([$resident['household_no']]);
    $householdInfo = $hhStmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch Issued Certificates
$certStmt = $db->prepare("
    SELECT c.*, u.full_name AS issuer_name
    FROM barangay_certificates c
    LEFT JOIN users u ON c.issued_by_user_id = u.id
    WHERE c.resident_id = ?
    ORDER BY c.issue_date DESC
");
$certStmt->execute([$id]);
$certificates = $certStmt->fetchAll();

$pageTitle = 'Resident Profile: ' . $resident['first_name'] . ' ' . $resident['last_name'];
// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?= url('residents') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix']) ?></h3>
            <?= status_badge($resident['status']) ?>
            <?php if (!empty($resident['is_archived'])): ?>
                <span class="badge bg-secondary"><i class="bi bi-archive-fill me-1"></i>Archived</span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0">
            Resident Code: <strong class="text-success"><?= htmlspecialchars($resident['resident_code']) ?></strong> &bull; 
            <?= htmlspecialchars($resident['sitio_name']) ?>, <?= BRGY_NAME ?>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= url('residents/id_card?id=' . $resident['id']) ?>" class="btn btn-outline-success" target="_blank">
            <i class="bi bi-person-vcard me-1"></i> Print Resident ID
        </a>
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-file-earmark-medical me-1"></i> Issue Certificate
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="<?= url('certificates/print_indigency?resident_id=' . $resident['id']) ?>"><i class="bi bi-file-earmark-text me-2 text-warning"></i>Cert. of Indigency</a></li>
                <li><a class="dropdown-item" href="<?= url('certificates/print_clearance?resident_id=' . $resident['id']) ?>"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Barangay Clearance</a></li>
                <li><a class="dropdown-item" href="<?= url('certificates/print_residency?resident_id=' . $resident['id']) ?>"><i class="bi bi-geo-alt me-2 text-success"></i>Cert. of Residency</a></li>
            </ul>
        </div>
        <?php if ($role === 'bhw'): ?>
            <a href="<?= url('residents/edit?id=' . $resident['id']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        <?php endif; ?>
            
        <?php if (in_array($role, ['captain', 'secretary'])): ?>
            <?php if (!empty($resident['is_archived'])): ?>
                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Restore this resident profile to the active roster?');">
                    <input type="hidden" name="action" value="restore_resident">
                    <button type="submit" class="btn btn-outline-success">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                    </button>
                </form>
            <?php else: ?>
                <button type="button" class="btn btn-outline-warning text-dark" data-bs-toggle="modal" data-bs-target="#archiveResidentModal">
                    <i class="bi bi-archive me-1"></i> Archive
                </button>
            <?php endif; ?>

            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteResidentModal">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Archived Alert Banner if Archived -->
<?php if (!empty($resident['is_archived'])): ?>
    <div class="alert alert-secondary border border-secondary shadow-sm p-4 rounded-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-archive-fill text-secondary me-2"></i>Archived Resident Profile</h5>
                <p class="mb-1 text-muted">
                    This profile was archived on <strong><?= format_datetime($resident['archived_at']) ?></strong>
                    <?php if (!empty($resident['archiver_name'])): ?> by <strong><?= htmlspecialchars($resident['archiver_name']) ?></strong><?php endif; ?>.
                </p>
                <div class="badge bg-light text-dark border px-3 py-2 fs-6">
                    <strong>Reason:</strong> <?= htmlspecialchars($resident['archived_reason'] ?: 'Relocated / Transferred Out') ?>
                </div>
            </div>
            <?php if (in_array($role, ['captain', 'secretary'])): ?>
                <form action="" method="POST" onsubmit="return confirm('Restore this resident profile back to active masterlist?');">
                    <input type="hidden" name="action" value="restore_resident">
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore to Active Roster
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Approval Action Banner if Pending -->
<?php if ($resident['status'] === 'pending'): ?>
    <div class="alert alert-warning border border-warning shadow-sm p-4 rounded-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-clock-history me-2"></i>Registration Pending Approval</h5>
                <p class="mb-0 text-dark">
                    Registered on <?= format_datetime($resident['registered_at']) ?> for <strong><?= htmlspecialchars($resident['sitio_name']) ?></strong>.
                </p>
            </div>
            <?php if (in_array($role, ['captain', 'secretary'])): ?>
                <form action="<?= url('residents/approvals') ?>" method="POST" class="d-flex gap-2">
                    <input type="hidden" name="resident_id" value="<?= $resident['id'] ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-success fw-bold px-4" onclick="return confirm('Approve this resident profile?');">
                        <i class="bi bi-check-lg me-1"></i> Approve Resident
                    </button>
                    <button type="button" class="btn btn-outline-danger px-3" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-lg me-1"></i> Reject
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- LEFT: Personal & Demographics -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-badge text-success me-2"></i>Personal & Household Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="small text-muted text-uppercase fw-semibold">First Name</label>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($resident['first_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted text-uppercase fw-semibold">Middle Name</label>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($resident['middle_name'] ?: 'None') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted text-uppercase fw-semibold">Last Name & Suffix</label>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($resident['last_name'] . ' ' . $resident['suffix']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted text-uppercase fw-semibold">Alias / Palayaw</label>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($resident['nickname'] ?: 'None') ?></div>
                    </div>

                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Birthdate</label>
                        <div class="fw-bold text-dark"><?= format_date($resident['birth_date']) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Age</label>
                        <div class="fw-bold text-dark"><?= $resident['age'] ?> years old</div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Gender</label>
                        <div class="fw-bold text-dark"><?= $resident['gender'] ?></div>
                    </div>

                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Civil Status</label>
                        <div class="fw-bold text-dark"><?= $resident['civil_status'] ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Contact Number</label>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($resident['contact_no'] ?: 'Not Provided') ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Email Address</label>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($resident['email'] ?: 'None') ?></div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-success mb-3"><i class="bi bi-geo-alt-fill me-1"></i>Sitio & Household Residency</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="small text-muted text-uppercase fw-semibold">Sitio Assignment</label>
                        <div class="fw-bold text-success fs-6"><?= htmlspecialchars($resident['sitio_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted text-uppercase fw-semibold">Assigned Sitio BHW</label>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($resident['assigned_bhw_name'] ?? 'Barangay Health Center') ?></div>
                    </div>
                    <div class="col-12">
                        <label class="small text-muted text-uppercase fw-semibold">Exact Address / House / Purok</label>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($resident['address_line']) ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Household No.</label>
                        <div class="fw-bold text-dark">
                            <?php if (!empty($resident['household_no'])): ?>
                                <a href="<?= url('households/view?no=' . urlencode($resident['household_no'])) ?>" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 font-monospace fs-6 text-decoration-none" title="View Household Profile & BHW Documentation">
                                    <i class="bi bi-houses-fill me-1"></i><?= htmlspecialchars($resident['household_no']) ?>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border">Unassigned</span>
                                <?php if (in_array($role, ['captain', 'secretary', 'bhw'])): ?>
                                    <a href="<?= url('households/add') ?>" class="small text-decoration-none ms-1"><i class="bi bi-plus-circle"></i> Register HH</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Relationship to Head</label>
                        <div class="fw-bold text-dark">
                            <?= htmlspecialchars($resident['family_role'] ?: 'Member') ?>
                            <?php if (!$resident['is_head_of_family'] && !empty($householdInfo['head_resident_name'])): ?>
                                <small class="text-muted d-block" style="font-size: 0.78rem;">to <strong><?= htmlspecialchars($householdInfo['head_resident_name']) ?></strong></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Head of Household?</label>
                        <div class="fw-bold text-dark"><?= $resident['is_head_of_family'] ? '<span class="badge bg-success"><i class="bi bi-star-fill me-1"></i>Yes (Head)</span>' : '<span class="badge bg-light text-dark border">No</span>' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health & Emergency Contact -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-heart-pulse text-danger me-2"></i>Health & Emergency Profile</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Blood Type</label>
                        <div class="fw-bold text-danger"><?= htmlspecialchars($resident['blood_type'] ?: 'Unknown') ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">PhilHealth No.</label>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($resident['philhealth_no'] ?: 'None') ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="small text-muted text-uppercase fw-semibold">Employment Status</label>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($resident['employment_status']) ?> (<?= htmlspecialchars($resident['occupation'] ?: 'None') ?>)</div>
                    </div>
                    <div class="col-12">
                        <label class="small text-muted text-uppercase fw-semibold">Known Medical Conditions / Illnesses</label>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($resident['medical_conditions'] ?: 'No chronic illness or disability reported.') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted text-uppercase fw-semibold">Emergency Contact Person</label>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($resident['emergency_contact_name'] ?: 'Not Stated') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="small text-muted text-uppercase fw-semibold">Emergency Contact Phone</label>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($resident['emergency_contact_no'] ?: 'Not Stated') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Social Welfare Profiling & Identity Documents -->
    <div class="col-lg-5">
        <!-- Identity Documents & Live Selfie -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-bounding-box text-success me-2"></i>Identification Documents</h5>
                <span class="badge bg-light text-dark border"><i class="bi bi-shield-check text-success me-1"></i>Identity Assets</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-6 text-center">
                        <div class="border rounded-3 p-2 bg-light h-100 d-flex flex-column align-items-center justify-content-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">
                                <i class="bi bi-camera me-1"></i>Live Selfie
                            </span>
                            <?php if (!empty($resident['photo_path']) && file_exists(BASE_PATH . '/' . $resident['photo_path'])): ?>
                                <a href="<?= url($resident['photo_path']) ?>" class="preview-attachment-btn d-block" data-preview-title="Live Selfie Profile Photo" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; <?= htmlspecialchars($resident['resident_code']) ?>" title="View Photo">
                                    <img src="<?= url($resident['photo_path']) ?>" alt="Selfie" class="img-fluid rounded border shadow-sm" style="max-height: 140px; object-fit: contain;">
                                </a>
                                <div class="mt-2"><button type="button" class="btn btn-xs btn-outline-success preview-attachment-btn py-0 px-2" data-preview-url="<?= url($resident['photo_path']) ?>" data-preview-title="Live Selfie Profile Photo" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" style="font-size: 0.75rem;"><i class="bi bi-arrows-fullscreen me-1"></i>View Modal</button></div>
                            <?php else: ?>
                                <div class="py-3 text-muted">
                                    <i class="bi bi-person fs-1 text-secondary d-block mb-1"></i>
                                    <span class="small">No photo uploaded</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6 text-center">
                        <div class="border rounded-3 p-2 bg-light h-100 d-flex flex-column align-items-center justify-content-center">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">
                                <i class="bi bi-card-heading me-1"></i>Valid ID
                            </span>
                            <?php if (!empty($resident['id_card_path']) && file_exists(BASE_PATH . '/' . $resident['id_card_path'])): ?>
                                <?php 
                                    $ext = strtolower(pathinfo($resident['id_card_path'], PATHINFO_EXTENSION));
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])):
                                ?>
                                    <a href="<?= url($resident['id_card_path']) ?>" class="preview-attachment-btn d-block" data-preview-title="Government Valid ID" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; <?= htmlspecialchars($resident['resident_code']) ?>" title="View valid ID">
                                        <img src="<?= url($resident['id_card_path']) ?>" alt="Valid ID" class="img-fluid rounded border shadow-sm" style="max-height: 140px; object-fit: contain;">
                                    </a>
                                <?php else: ?>
                                    <div class="py-2">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-danger d-block mb-1"></i>
                                        <span class="small text-muted">PDF Attached</span>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2"><button type="button" class="btn btn-xs btn-outline-primary preview-attachment-btn py-0 px-2" data-preview-url="<?= url($resident['id_card_path']) ?>" data-preview-title="Government Valid ID" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" style="font-size: 0.75rem;"><i class="bi bi-arrows-fullscreen me-1"></i>View ID Modal</button></div>
                            <?php else: ?>
                                <div class="py-3 text-muted">
                                    <i class="bi bi-file-earmark-x fs-1 text-secondary d-block mb-1"></i>
                                    <span class="small">No ID attached</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-danger mb-0"><i class="bi bi-shield-heart text-danger me-2"></i>Social Welfare Profiling</h5>
            </div>
            <div class="card-body p-4">

                <!-- PWD Card -->
                <div class="p-3 rounded-3 mb-3 border <?= $resident['is_pwd'] ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold text-primary"><i class="bi bi-person-wheelchair me-2"></i>Person with Disability (PWD)</span>
                        <?= $resident['is_pwd'] ? '<span class="badge bg-primary">Verified PWD</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </div>
                    <?php if ($resident['is_pwd']): ?>
                        <div class="small mt-2">
                            <div><strong>Disability Type:</strong> <?= htmlspecialchars($resident['pwd_type'] ?: 'General Disability') ?></div>
                            <div><strong>PWD ID Number:</strong> <code><?= htmlspecialchars($resident['pwd_id_no'] ?: 'On File') ?></code></div>
                            <?php if (!empty($resident['pwd_id_photo']) && file_exists(BASE_PATH . '/' . $resident['pwd_id_photo'])): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($resident['pwd_id_photo']) ?>" data-preview-title="PWD ID Card Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; PWD ID: <?= htmlspecialchars($resident['pwd_id_no'] ?: 'On File') ?>" style="font-size: 0.78rem;">
                                        <i class="bi bi-paperclip me-1"></i>View PWD ID in Modal
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Solo Mother / Parent -->
                <div class="p-3 rounded-3 mb-3 border <?= $resident['is_solo_parent'] ? 'border-danger bg-danger bg-opacity-10' : 'bg-light' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold text-danger"><i class="bi bi-person-heart me-2"></i>Solo Mother / Solo Parent</span>
                        <?= $resident['is_solo_parent'] ? '<span class="badge bg-danger">Verified Solo Parent</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </div>
                    <?php if ($resident['is_solo_parent']): ?>
                        <div class="small mt-2">
                            <div><strong>Dependent Children:</strong> <?= $resident['dependent_children_count'] ?> child(ren)</div>
                            <div><strong>Solo Parent ID:</strong> <code><?= htmlspecialchars($resident['solo_parent_id_no'] ?: 'On File') ?></code></div>
                            <?php if (!empty($resident['solo_parent_id_photo']) && file_exists(BASE_PATH . '/' . $resident['solo_parent_id_photo'])): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($resident['solo_parent_id_photo']) ?>" data-preview-title="Solo Parent ID Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; Solo Parent ID: <?= htmlspecialchars($resident['solo_parent_id_no'] ?: 'On File') ?>" style="font-size: 0.78rem;">
                                        <i class="bi bi-paperclip me-1"></i>View Solo Parent ID in Modal
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Indigent Household -->
                <div class="p-3 rounded-3 mb-3 border <?= $resident['is_indigent'] ? 'border-warning bg-warning bg-opacity-10' : 'bg-light' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold text-warning-emphasis"><i class="bi bi-wallet2 me-2"></i>Indigent / Low-Income Household</span>
                        <?= $resident['is_indigent'] ? '<span class="badge bg-warning text-dark">Verified Indigent</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </div>
                    <?php if ($resident['is_indigent']): ?>
                        <div class="small mt-2">
                            <div><strong>Monthly Household Income:</strong> <span class="fw-bold text-success"><?= format_peso($resident['monthly_income']) ?></span></div>
                            <div><strong>Livelihood Source:</strong> <?= htmlspecialchars($resident['income_source'] ?: 'Manual Labor / Informal Sector') ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Senior Citizen -->
                <div class="p-3 rounded-3 mb-3 border <?= $resident['is_senior'] ? 'border-info bg-info bg-opacity-10' : 'bg-light' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold text-info-emphasis"><i class="bi bi-person-cane me-2"></i>Senior Citizen (60+ yrs)</span>
                        <?= $resident['is_senior'] ? '<span class="badge bg-info text-dark">Senior Citizen</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </div>
                    <?php if ($resident['is_senior']): ?>
                        <div class="small mt-2">
                            <div><strong>OSCA Senior ID:</strong> <code><?= htmlspecialchars($resident['senior_id_no'] ?: 'On File') ?></code></div>
                            <?php if (!empty($resident['senior_id_photo']) && file_exists(BASE_PATH . '/' . $resident['senior_id_photo'])): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-info preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($resident['senior_id_photo']) ?>" data-preview-title="Senior Citizen / OSCA ID Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; OSCA ID: <?= htmlspecialchars($resident['senior_id_no'] ?: 'On File') ?>" style="font-size: 0.78rem;">
                                        <i class="bi bi-paperclip me-1"></i>View OSCA / Senior ID in Modal
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Student / Enrolled Learner -->
                <div class="p-3 rounded-3 mb-3 border <?= !empty($resident['is_student']) ? 'border-info bg-info bg-opacity-10' : 'bg-light' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold text-info-emphasis"><i class="bi bi-mortarboard-fill me-2"></i>Student / Enrolled Learner</span>
                        <?= !empty($resident['is_student']) ? '<span class="badge bg-info text-dark">Verified Student</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </div>
                    <?php if (!empty($resident['is_student'])): ?>
                        <div class="small mt-2">
                            <div><strong>Student ID / LRN:</strong> <code><?= htmlspecialchars($resident['student_id_no'] ?: 'On File') ?></code></div>
                            <div><strong>School / Institution:</strong> <?= htmlspecialchars($resident['school_name'] ?: 'Not Specified') ?></div>
                            <?php if (!empty($resident['school_id_photo']) && file_exists(BASE_PATH . '/' . $resident['school_id_photo'])): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-info preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($resident['school_id_photo']) ?>" data-preview-title="Student School ID / Enrollment Slip" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; School: <?= htmlspecialchars($resident['school_name'] ?: 'Enrolled') ?>" style="font-size: 0.78rem;">
                                        <i class="bi bi-paperclip me-1"></i>View School ID in Modal
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 4Ps Beneficiary -->
                <div class="p-3 rounded-3 mb-3 border <?= $resident['is_4ps'] ? 'border-success bg-success bg-opacity-10' : 'bg-light' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold text-success"><i class="bi bi-check-circle me-2"></i>4Ps Beneficiary</span>
                        <?= $resident['is_4ps'] ? '<span class="badge bg-success">4Ps Member</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </div>
                    <?php if ($resident['is_4ps']): ?>
                        <div class="small mt-2">
                            <div><strong>Household 4Ps ID:</strong> <code><?= htmlspecialchars($resident['four_ps_id_no'] ?: 'Registered') ?></code></div>
                            <?php if (!empty($resident['four_ps_id_photo']) && file_exists(BASE_PATH . '/' . $resident['four_ps_id_photo'])): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-success preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($resident['four_ps_id_photo']) ?>" data-preview-title="4Ps Household ID Card" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; 4Ps ID: <?= htmlspecialchars($resident['four_ps_id_no'] ?: 'Registered') ?>" style="font-size: 0.78rem;">
                                        <i class="bi bi-paperclip me-1"></i>View 4Ps ID in Modal
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Voter Information -->
                <div class="p-3 rounded-3 border bg-light">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold text-secondary"><i class="bi bi-card-checklist me-2"></i>Voter in Brgy Lublub</span>
                        <?= $resident['is_voter'] ? '<span class="badge bg-secondary">Registered Voter</span>' : '<span class="badge bg-light text-muted border">Non-Voter</span>' ?>
                    </div>
                    <?php if ($resident['is_voter']): ?>
                        <div class="small mt-1">
                            <div><strong>Precinct No:</strong> <code><?= htmlspecialchars($resident['precinct_no'] ?: 'Assigned at Lublub Elementary School') ?></code></div>
                            <?php if (!empty($resident['voter_id_photo']) && file_exists(BASE_PATH . '/' . $resident['voter_id_photo'])): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($resident['voter_id_photo']) ?>" data-preview-title="Voter's ID / Registration Stub" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; Precinct: <?= htmlspecialchars($resident['precinct_no'] ?: 'Lublub') ?>" style="font-size: 0.78rem;">
                                        <i class="bi bi-paperclip me-1"></i>View Voter's ID in Modal
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Social Welfare & Identity Attachments Gallery -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-paperclip text-primary me-2"></i>Attached Identity & Welfare Documents
                </h5>
                <span class="badge bg-primary bg-opacity-10 text-primary">Verification Proofs</span>
            </div>
            <div class="card-body p-4">
                <?php 
                $attachedDocs = [
                    ['title' => 'Primary Valid ID', 'badge' => 'Government ID', 'path' => $resident['id_card_path'] ?? null],
                    ['title' => 'PWD ID Card', 'badge' => 'PWD Registry', 'path' => $resident['pwd_id_photo'] ?? null],
                    ['title' => 'Senior / OSCA ID', 'badge' => 'Senior Citizen', 'path' => $resident['senior_id_photo'] ?? null],
                    ['title' => 'School ID / Slip', 'badge' => 'Student Proof', 'path' => $resident['school_id_photo'] ?? null],
                    ['title' => 'Solo Parent ID', 'badge' => 'Solo Parent', 'path' => $resident['solo_parent_id_photo'] ?? null],
                    ['title' => '4Ps ID Card', 'badge' => 'DSWD 4Ps', 'path' => $resident['four_ps_id_photo'] ?? null],
                    ['title' => 'Voter\'s ID / Stub', 'badge' => 'COMELEC Voter', 'path' => $resident['voter_id_photo'] ?? null],
                ];
                $activeDocs = array_filter($attachedDocs, function($d) {
                    return !empty($d['path']) && file_exists(BASE_PATH . '/' . $d['path']);
                });
                ?>

                <?php if (empty($activeDocs)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-file-earmark-x display-6 d-block mb-2 text-secondary opacity-50"></i>
                        <span class="small">No specialized ID documents attached to this resident record yet.</span>
                        <?php if (in_array($role, ['captain', 'secretary', 'bhw'])): ?>
                            <div class="mt-2">
                                <a href="<?= url('residents/edit?id=' . $resident['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-upload me-1"></i> Upload Attachments
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($activeDocs as $ad): 
                            $ext = strtolower(pathinfo($ad['path'], PATHINFO_EXTENSION));
                            $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                        ?>
                            <div class="col-sm-6 text-center">
                                <div class="p-2 border rounded-3 bg-light shadow-sm h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-bold text-dark text-truncate"><?= $ad['title'] ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;"><?= $ad['badge'] ?></span>
                                        </div>
                                        <?php if ($isImg): ?>
                                            <a href="<?= asset($ad['path']) ?>" class="preview-attachment-btn d-block" data-preview-title="<?= htmlspecialchars($ad['title']) ?>" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; <?= htmlspecialchars($ad['badge']) ?>" title="View in Modal">
                                                <img src="<?= asset($ad['path']) ?>" alt="<?= $ad['title'] ?>" class="img-fluid rounded border mb-2" style="max-height: 100px; width: 100%; object-fit: cover;">
                                            </a>
                                        <?php else: ?>
                                            <div class="py-3 text-center">
                                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-1 d-block mb-1"></i>
                                                <span class="small text-muted">PDF Document</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-primary preview-attachment-btn w-100 py-1" data-preview-url="<?= asset($ad['path']) ?>" data-preview-title="<?= htmlspecialchars($ad['title']) ?>" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; <?= htmlspecialchars($ad['badge']) ?>" style="font-size: 0.72rem;">
                                        <i class="bi bi-arrows-fullscreen me-1"></i>View Document Modal
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Household Dwelling & BHW Field Documentation -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-houses-fill text-success me-2"></i>Dwelling & BHW Field Documentation
                </h5>
                <?php if ($householdInfo): ?>
                    <a href="<?= url('households/view?id=' . $householdInfo['id']) ?>" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-eye me-1"></i> Full Dossier
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php 
                $housePhoto = $householdInfo['house_photo_path'] ?? $resident['house_photo_path'] ?? null;
                $bhwPhoto   = $householdInfo['bhw_documentation_photo'] ?? $resident['bhw_documentation_photo'] ?? null;
                $bhwNotes   = $householdInfo['bhw_notes'] ?? $resident['bhw_notes'] ?? null;
                ?>
                <div class="row g-3 mb-3">
                    <div class="col-6 text-center">
                        <label class="small text-muted fw-semibold text-uppercase d-block mb-1">Dwelling Photo</label>
                        <?php if ($housePhoto && file_exists(__DIR__ . '/../../' . $housePhoto)): ?>
                            <a href="<?= asset($housePhoto) ?>" class="preview-attachment-btn d-block" data-preview-title="Dwelling Exterior Photo" data-preview-subtitle="Household #<?= htmlspecialchars($resident['household_no'] ?? '') ?> &bull; <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" title="View House Photo in Modal">
                                <img src="<?= asset($housePhoto) ?>" alt="House Photo" class="img-fluid rounded-3 border shadow-sm" style="max-height: 140px; width: 100%; object-fit: cover;">
                            </a>
                            <div class="mt-1"><button type="button" class="btn btn-xs btn-outline-info preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($housePhoto) ?>" data-preview-title="Dwelling Exterior Photo" data-preview-subtitle="Household #<?= htmlspecialchars($resident['household_no'] ?? '') ?>" style="font-size: 0.72rem;"><i class="bi bi-arrows-fullscreen me-1"></i>Modal View</button></div>
                        <?php else: ?>
                            <div class="p-3 bg-light rounded-3 border text-muted small">
                                <i class="bi bi-house fs-3 d-block mb-1 text-secondary"></i>
                                No Dwelling Photo
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6 text-center">
                        <label class="small text-muted fw-semibold text-uppercase d-block mb-1">BHW Field Proof</label>
                        <?php if ($bhwPhoto && file_exists(__DIR__ . '/../../' . $bhwPhoto)): ?>
                            <a href="<?= asset($bhwPhoto) ?>" class="preview-attachment-btn d-block" data-preview-title="BHW Field Visitation Proof" data-preview-subtitle="Assessed for Household #<?= htmlspecialchars($resident['household_no'] ?? '') ?>" title="View BHW Field Proof in Modal">
                                <img src="<?= asset($bhwPhoto) ?>" alt="BHW Doc" class="img-fluid rounded-3 border border-success shadow-sm" style="max-height: 140px; width: 100%; object-fit: cover;">
                            </a>
                            <div class="mt-1"><button type="button" class="btn btn-xs btn-outline-success preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($bhwPhoto) ?>" data-preview-title="BHW Field Visitation Proof" data-preview-subtitle="Household #<?= htmlspecialchars($resident['household_no'] ?? '') ?>" style="font-size: 0.72rem;"><i class="bi bi-arrows-fullscreen me-1"></i>Modal View</button></div>
                        <?php else: ?>
                            <div class="p-3 bg-light rounded-3 border text-muted small">
                                <i class="bi bi-clipboard-x fs-3 d-block mb-1 text-secondary"></i>
                                No BHW Proof
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($householdInfo): ?>
                    <div class="p-3 bg-light rounded-3 border small">
                        <div class="row g-2">
                            <div class="col-sm-6"><strong>Structure:</strong> <?= htmlspecialchars($householdInfo['house_structure']) ?></div>
                            <div class="col-sm-6"><strong>Sanitation:</strong> <?= htmlspecialchars($householdInfo['sanitation_facility']) ?></div>
                            <div class="col-sm-6"><strong>Water Source:</strong> <?= htmlspecialchars($householdInfo['water_source']) ?></div>
                            <div class="col-sm-6"><strong>Power:</strong> <?= $householdInfo['electricity_access'] ? '<span class="text-success fw-bold">Connected</span>' : '<span class="text-danger fw-bold">No Power</span>' ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bhwNotes)): ?>
                    <div class="mt-3 p-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25 small">
                        <div class="fw-bold text-success mb-1"><i class="bi bi-chat-quote-fill me-1"></i>BHW Field Assessment Notes:</div>
                        <div class="text-dark"><?= nl2br(htmlspecialchars($bhwNotes)) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- BOTTOM SECTION: AYUDA / ASSISTANCE HISTORY & CERTIFICATES -->
<div class="row g-4">
    <!-- Ayuda / Social Services History -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-box2-heart-fill text-danger me-2"></i>Ayuda & Assistance Received</h5>
                <span class="badge bg-light text-dark border"><?= count($assistanceHistory) ?> Distributions</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($assistanceHistory)): ?>
                    <div class="p-4 text-center text-muted">
                        No aid distributions logged for this resident yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Program Title</th>
                                    <th>Date</th>
                                    <th>Aid Received</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assistanceHistory as $aid): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($aid['program_title']) ?></div>
                                            <small class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($aid['category']) ?></small>
                                        </td>
                                        <td class="small text-muted"><?= format_date($aid['distribution_date']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-success"><?= format_peso($aid['received_amount']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($aid['received_items'] ?: '') ?></small>
                                        </td>
                                        <td><?= status_badge($aid['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Barangay Certificates Issued -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-medical-fill text-success me-2"></i>Barangay Documents Issued</h5>
                <span class="badge bg-light text-dark border"><?= count($certificates) ?> Certificates</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($certificates)): ?>
                    <div class="p-4 text-center text-muted">
                        No clearances or certificates issued yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Cert No.</th>
                                    <th>Type & Purpose</th>
                                    <th>Issue Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificates as $cert): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($cert['cert_no']) ?></code></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($cert['cert_type']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($cert['purpose']) ?></small>
                                        </td>
                                        <td class="small text-muted"><?= format_date($cert['issue_date']) ?></td>
                                        <td class="text-end">
                                            <?php 
                                                switch ($cert['cert_type']) {
                                                    case 'Certificate of Indigency': $printScript = 'print_indigency.php'; break;
                                                    case 'Certificate of Residency': $printScript = 'print_residency.php'; break;
                                                    default:                         $printScript = 'print_clearance.php'; break;
                                                }
                                            ?>
                                            <a href="<?= url('modules/certificates/' . $printScript . '?resident_id=' . $resident['id']) ?>" target="_blank" class="btn btn-xs btn-outline-success py-1 px-2">
                                                <i class="bi bi-printer"></i> Print
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

<?php if (in_array($role, ['captain', 'secretary'])): ?>
    <!-- Archive Resident Modal -->
    <div class="modal fade" id="archiveResidentModal" tabindex="-1" aria-labelledby="archiveResidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form action="" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <input type="hidden" name="action" value="archive_resident">
                <div class="modal-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 py-3 px-4 flex-shrink-0">
                    <h5 class="modal-title fw-bold text-dark" id="archiveResidentModalLabel">
                        <i class="bi bi-archive-fill text-warning me-2"></i>Archive Resident Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <p class="text-muted">
                        You are about to archive <strong><?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?></strong>.
                        Archiving safely preserves certificates and historical logs while excluding the resident from active lists and social services quotas.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Archiving <span class="text-danger">*</span></label>
                        <select name="archive_reason" class="form-select" id="archive_reason_select" required onchange="toggleCustomReason(this)">
                            <option value="Relocated / Transferred Out">Relocated / Transferred Out</option>
                            <option value="Deceased">Deceased</option>
                            <option value="Left Barangay Permanently">Left Barangay Permanently</option>
                            <option value="Duplicate / Erroneous Entry">Duplicate / Erroneous Entry</option>
                            <option value="Non-Resident / Invalid">Non-Resident / Invalid</option>
                            <option value="Other">Other (Specify)</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="custom_reason_group">
                        <label class="form-label fw-semibold">Specify Custom Reason <span class="text-danger">*</span></label>
                        <input type="text" name="custom_archive_reason" id="custom_archive_reason" class="form-control" placeholder="Provide specific reason...">
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
    <!-- Permanent Delete Resident Modal -->
    <div class="modal fade" id="deleteResidentModal" tabindex="-1" aria-labelledby="deleteResidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form action="" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <input type="hidden" name="action" value="delete_resident">
                <div class="modal-header bg-danger text-white py-3 px-4 flex-shrink-0">
                    <h5 class="modal-title fw-bold" id="deleteResidentModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Permanently Delete Resident
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <div class="text-danger display-4 mb-3"><i class="bi bi-trash-fill"></i></div>
                    <h5 class="fw-bold text-dark mb-2">Delete <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>?</h5>
                    <p class="text-muted small mb-3">
                        Resident Code: <strong><?= htmlspecialchars($resident['resident_code']) ?></strong><br>
                        This will permanently erase this resident's profile and remove all associated history. This operation <strong>cannot be undone</strong>.
                    </p>
                    <div class="alert alert-danger py-2 small text-start">
                        <i class="bi bi-shield-lock-fill me-1"></i> Captain / Secretary authorization required.
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
function toggleCustomReason(sel) {
    const grp = document.getElementById('custom_reason_group');
    const inp = document.getElementById('custom_archive_reason');
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
