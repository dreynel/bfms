<?php
// residents/my_profile - Logged-in Resident Self-Service Profile
require_once __DIR__ . '/../../config/app.php';

require_role('resident', 'captain', 'secretary', 'bhw');

$db = getDB();
$user = auth_user();
$pageTitle = 'My Resident Profile & ID';

$stmt = $db->prepare("
    SELECT r.*, s.name AS sitio_name, s.code AS sitio_code,
           ub.full_name AS assigned_bhw_name
    FROM residents r
    JOIN sitios s ON r.sitio_id = s.id
    LEFT JOIN users ub ON s.id = ub.assigned_sitio_id AND ub.role = 'bhw'
    WHERE r.user_id = ?
");
$stmt->execute([$user['id']]);
$resident = $stmt->fetch();

if (!$resident) {
    set_flash('info', 'Your user account is not linked to a resident record yet.');
    redirect('dashboard');
}

// Handle Contact Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_contact') {
    $contactNo = clean($_POST['contact_no'] ?? '');
    $emergName = clean($_POST['emergency_contact_name'] ?? '');
    $emergNo   = clean($_POST['emergency_contact_no'] ?? '');

    $db->prepare("
        UPDATE residents 
        SET contact_no = ?, emergency_contact_name = ?, emergency_contact_no = ?
        WHERE id = ?
    ")->execute([$contactNo, $emergName, $emergNo, $resident['id']]);

    set_flash('success', 'Your contact information has been updated.');
    redirect('residents/my_profile');
}

// Handled by index.php container
?>

<div class="row g-4 justify-content-center">
    <div class="col-lg-5">
        <!-- Digital ID Card Display -->
        <div class="resident-id-card shadow-lg mx-auto">
            <div class="id-header">
                <div class="text-white-50 small fw-bold text-uppercase">Republic of the Philippines &bull; Province of Iloilo</div>
                <h5 class="fw-bold mb-0">BARANGAY LUBLUB</h5>
                <div class="small text-white-50">Municipality of Dumangas &bull; P.D. Monfort North</div>
                <div class="mt-2 badge bg-white text-success fw-bold px-3 py-1">OFFICIAL DIGITAL RESIDENT ID</div>
            </div>
            <div class="p-4 bg-white text-center">
                <div class="mx-auto rounded-circle bg-light border border-3 border-success d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 100px; height: 100px; font-size: 2.5rem; color: #0d6338;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix']) ?></h4>
                <p class="text-muted small mb-2">Resident Code: <strong><?= htmlspecialchars($resident['resident_code']) ?></strong></p>

                <div class="p-3 rounded bg-light border mb-3 text-start small">
                    <div class="row g-1">
                        <div class="col-4 text-muted">Sitio:</div>
                        <div class="col-8 fw-bold text-success"><?= htmlspecialchars($resident['sitio_name']) ?></div>
                        <div class="col-4 text-muted">Assigned BHW:</div>
                        <div class="col-8 fw-semibold text-dark"><?= htmlspecialchars($resident['assigned_bhw_name'] ?? 'Barangay Health Center') ?></div>
                        <div class="col-4 text-muted">Address:</div>
                        <div class="col-8"><?= htmlspecialchars($resident['address_line']) ?></div>
                        <div class="col-4 text-muted">Civil Status:</div>
                        <div class="col-8"><?= htmlspecialchars($resident['civil_status']) ?> (Age: <?= $resident['age'] ?>)</div>
                    </div>
                </div>

                <!-- Special Status Badges -->
                <div class="d-flex flex-wrap justify-content-center gap-1 mb-3">
                    <?php if ($resident['is_pwd']): ?><span class="badge badge-pwd">PWD</span><?php endif; ?>
                    <?php if ($resident['is_solo_parent']): ?><span class="badge badge-solo">Solo Mother</span><?php endif; ?>
                    <?php if ($resident['is_indigent']): ?><span class="badge badge-indigent">Indigent</span><?php endif; ?>
                    <?php if ($resident['is_senior']): ?><span class="badge badge-senior">Senior Citizen</span><?php endif; ?>
                    <?php if (!empty($resident['is_student'])): ?><span class="badge bg-info text-dark">Student</span><?php endif; ?>
                    <?php if ($resident['is_4ps']): ?><span class="badge badge-4ps">4Ps Member</span><?php endif; ?>
                </div>

                <a href="<?= url('residents/id_card?id=' . $resident['id']) ?>" target="_blank" class="btn btn-outline-success btn-sm w-100">
                    <i class="bi bi-printer me-1"></i> Print Laminated ID Card
                </a>
            </div>
        </div>
    </div>

    <!-- Contact Details Update Form -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-telephone-outbound text-success me-2"></i>My Emergency & Contact Info</h5>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_contact">

                    <div class="mb-3">
                        <label class="form-label fw-bold">My Mobile Number</label>
                        <input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($resident['contact_no'] ?? '') ?>" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Emergency Contact Person</label>
                            <input type="text" name="emergency_contact_name" class="form-control" value="<?= htmlspecialchars($resident['emergency_contact_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact_no" class="form-control" value="<?= htmlspecialchars($resident['emergency_contact_no'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-paperclip text-success me-1"></i>My Uploaded ID Attachments & Documents</h6>
                <div class="row g-2 mb-4">
                    <?php 
                    $docList = [
                        ['label' => 'Primary Valid ID', 'path' => $resident['id_card_path'] ?? null, 'badge' => 'Government ID'],
                        ['label' => 'PWD ID Card', 'path' => $resident['pwd_id_photo'] ?? null, 'badge' => 'PWD Proof'],
                        ['label' => 'Senior Citizen / OSCA ID', 'path' => $resident['senior_id_photo'] ?? null, 'badge' => 'OSCA Proof'],
                        ['label' => 'School ID / Enrollment', 'path' => $resident['school_id_photo'] ?? null, 'badge' => 'Student Proof'],
                        ['label' => 'Solo Parent ID', 'path' => $resident['solo_parent_id_photo'] ?? null, 'badge' => 'Solo Parent'],
                        ['label' => '4Ps ID Card', 'path' => $resident['four_ps_id_photo'] ?? null, 'badge' => '4Ps Proof'],
                        ['label' => 'Voter\'s ID', 'path' => $resident['voter_id_photo'] ?? null, 'badge' => 'COMELEC'],
                        ['label' => 'Dwelling Exterior Photo', 'path' => $resident['house_photo_path'] ?? null, 'badge' => 'Residence'],
                    ];
                    $hasAnyDoc = false;
                    foreach ($docList as $doc):
                        if (!empty($doc['path']) && file_exists(BASE_PATH . '/' . $doc['path'])):
                            $hasAnyDoc = true;
                    ?>
                        <div class="col-sm-6">
                            <div class="p-2 border rounded bg-white shadow-sm d-flex align-items-center justify-content-between">
                                <div class="overflow-hidden me-2">
                                    <div class="small fw-bold text-dark text-truncate"><?= $doc['label'] ?></div>
                                    <span class="badge bg-light text-muted border" style="font-size: 0.68rem;"><?= $doc['badge'] ?></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary preview-attachment-btn py-1 px-2" data-preview-url="<?= asset($doc['path']) ?>" data-preview-title="<?= htmlspecialchars($doc['label']) ?>" data-preview-subtitle="My Resident Documents &bull; <?= htmlspecialchars($resident['resident_code']) ?>" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrows-fullscreen me-1"></i> View
                                </button>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    if (!$hasAnyDoc):
                    ?>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded text-muted small text-center">
                                <i class="bi bi-info-circle me-1"></i> No special welfare ID documents attached to your profile yet.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <h6 class="fw-bold text-dark mb-3">Barangay Certifications & Clearances</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= url('certificates') ?>" class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-medical me-1"></i> Request Certificate of Indigency / Clearance
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

// Handled by index.php container

