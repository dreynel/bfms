<?php
// pages/sitios.php - 7 Sitios Directory & Health Worker Jurisdictions
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'The 7 Sitios of Barangay Lublub';

// Handle BHW Re-assignment (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['captain', 'secretary']) && isset($_POST['action']) && $_POST['action'] === 'assign_bhw') {
    $sitioId = (int)($_POST['sitio_id'] ?? 0);
    $bhwId   = (int)($_POST['bhw_user_id'] ?? 0);

    if ($sitioId > 0 && $bhwId > 0) {
        try {
            // Unassign current bhw from this sitio if any
            $db->prepare("UPDATE users SET assigned_sitio_id = NULL WHERE assigned_sitio_id = ? AND role = 'bhw'")->execute([$sitioId]);
            // Assign selected bhw
            $db->prepare("UPDATE users SET assigned_sitio_id = ? WHERE id = ? AND role = 'bhw'")->execute([$sitioId, $bhwId]);

            $bhwName = $db->query("SELECT full_name FROM users WHERE id = {$bhwId}")->fetchColumn();
            log_activity('Reassign Sitio BHW', "Sitio ID {$sitioId} assigned to BHW {$bhwName}");
            set_flash('success', "Sitio jurisdiction updated successfully! <strong>{$bhwName}</strong> is now assigned to <strong>" . SITIOS_LIST[$sitioId] . "</strong>.");
        } catch (Exception $e) {
            set_flash('danger', 'Error updating assignment: ' . $e->getMessage());
        }
        redirect('sitios');
    }
}

// Fetch all 7 Sitios with statistics and assigned BHWs
$sql = "
    SELECT s.*, 
           ub.id AS bhw_user_id, ub.full_name AS bhw_name, ub.contact_number AS bhw_contact,
           COUNT(CASE WHEN r.status = 'approved' THEN 1 END) AS approved_count,
           COUNT(CASE WHEN r.status = 'pending' THEN 1 END) AS pending_count,
           COUNT(CASE WHEN r.is_pwd = 1 AND r.status = 'approved' THEN 1 END) AS pwd_count,
           COUNT(CASE WHEN r.is_solo_parent = 1 AND r.status = 'approved' THEN 1 END) AS solo_count,
           COUNT(CASE WHEN r.is_indigent = 1 AND r.status = 'approved' THEN 1 END) AS indigent_count,
           COUNT(CASE WHEN r.is_senior = 1 AND r.status = 'approved' THEN 1 END) AS senior_count,
           COUNT(CASE WHEN r.is_student = 1 AND r.status = 'approved' THEN 1 END) AS student_count
    FROM sitios s
    LEFT JOIN users ub ON s.id = ub.assigned_sitio_id AND ub.role = 'bhw'
    LEFT JOIN residents r ON s.id = r.sitio_id
    GROUP BY s.id, ub.id, ub.full_name, ub.contact_number
    ORDER BY s.id ASC
";
$sitios = $db->query($sql)->fetchAll();

// List of all BHW users for assignment dropdown
$bhws = $db->query("SELECT id, full_name FROM users WHERE role = 'bhw' ORDER BY full_name ASC")->fetchAll();

// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-0">The 7 Sitios of Barangay Lublub</h3>
        <p class="text-muted small mb-0">Territorial Jurisdictions, Assigned Health Workers & Social Demographics</p>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($sitios as $s): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden stat-card">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success bg-opacity-10 text-success rounded-circle p-2 fw-bold" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                            <?= $s['id'] ?>
                        </span>
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($s['name']) ?></h6>
                            <small class="text-muted font-monospace"><?= htmlspecialchars($s['code']) ?></small>
                        </div>
                    </div>

                    <?php if ($s['pending_count'] > 0): ?>
                        <a href="<?= url('residents/approvals?sitio=' . $s['id']) ?>" class="badge bg-danger text-white text-decoration-none px-2 py-1">
                            <?= $s['pending_count'] ?> Pending
                        </a>
                    <?php else: ?>
                        <span class="badge bg-light text-secondary border">0 Pending</span>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4">
                    <!-- Assigned BHW Details -->
                    <div class="p-3 rounded-3 bg-light border border-success border-opacity-25 mb-3">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.68rem;">Assigned Barangay Health Worker (BHW):</small>
                        <div class="fw-bold text-danger fs-6 mt-1">
                            <i class="bi bi-heart-pulse-fill me-1"></i><?= htmlspecialchars($s['bhw_name'] ?? 'Not Yet Assigned') ?>
                        </div>
                        <?php if (!empty($s['bhw_contact'])): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($s['bhw_contact']) ?></div>
                        <?php endif; ?>
                    </div>

                    <p class="text-muted small mb-3"><?= htmlspecialchars($s['description']) ?></p>

                    <!-- Demographic Metrics in this Sitio -->
                    <div class="row g-2 text-center small mb-3">
                        <div class="col-4">
                            <a href="<?= url('residents?sitio=' . $s['id']) ?>" class="p-2 border rounded bg-white text-decoration-none d-block hover-shadow">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Approved</span>
                                <strong class="text-dark fs-6"><?= $s['approved_count'] ?></strong>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="<?= url('residents?sitio=' . $s['id'] . '&category=pwd') ?>" class="p-2 border rounded bg-white text-decoration-none d-block hover-shadow">
                                <span class="text-primary d-block" style="font-size: 0.65rem;">PWD</span>
                                <strong class="text-primary fs-6"><?= $s['pwd_count'] ?></strong>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="<?= url('residents?sitio=' . $s['id'] . '&category=student') ?>" class="p-2 border rounded bg-white text-decoration-none d-block hover-shadow">
                                <span class="text-info d-block" style="font-size: 0.65rem;">Students</span>
                                <strong class="text-info fs-6"><?= $s['student_count'] ?? 0 ?></strong>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="<?= url('residents?sitio=' . $s['id'] . '&category=solo_parent') ?>" class="p-2 border rounded bg-white text-decoration-none d-block hover-shadow">
                                <span class="text-danger d-block" style="font-size: 0.65rem;">Solo Mo.</span>
                                <strong class="text-danger fs-6"><?= $s['solo_count'] ?></strong>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="<?= url('residents?sitio=' . $s['id'] . '&category=indigent') ?>" class="p-2 border rounded bg-white text-decoration-none d-block hover-shadow">
                                <span class="text-warning-emphasis d-block" style="font-size: 0.65rem;">Indigent</span>
                                <strong class="text-warning-emphasis fs-6"><?= $s['indigent_count'] ?></strong>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="<?= url('residents?sitio=' . $s['id'] . '&category=senior') ?>" class="p-2 border rounded bg-white text-decoration-none d-block hover-shadow">
                                <span class="text-secondary d-block" style="font-size: 0.65rem;">Seniors</span>
                                <strong class="text-secondary fs-6"><?= $s['senior_count'] ?></strong>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 border-top d-flex justify-content-between align-items-center">
                    <a href="<?= url('residents?sitio=' . $s['id']) ?>" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-people me-1"></i> View Residents
                    </a>

                    <?php if (in_array($role, ['captain', 'secretary'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#reassignModal<?= $s['id'] ?>">
                            <i class="bi bi-person-gear"></i> Reassign BHW
                        </button>

                        <!-- Reassign Modal -->
                        <div class="modal fade text-start" id="reassignModal<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="" method="POST">
                                        <input type="hidden" name="action" value="assign_bhw">
                                        <input type="hidden" name="sitio_id" value="<?= $s['id'] ?>">
                                        <div class="modal-header bg-dark text-white">
                                            <h5 class="modal-title fw-bold">Assign BHW to <?= htmlspecialchars($s['name']) ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <label class="form-label fw-bold">Select Barangay Health Worker</label>
                                            <select name="bhw_user_id" class="form-select" required>
                                                <?php foreach ($bhws as $bhw): ?>
                                                    <option value="<?= $bhw['id'] ?>" <?= ($bhw['id'] == $s['bhw_user_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($bhw['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success fw-bold">Save Assignment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

