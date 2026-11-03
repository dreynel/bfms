<?php
// certificates - Barangay Clearances & Indigency Issuance
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'resident');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'Barangay Certificates & Clearances';

// Handle Direct Issuance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue_certificate') {
    $residentId = (int)($_POST['resident_id'] ?? 0);
    $certType   = clean($_POST['cert_type'] ?? 'Barangay Clearance');
    $purpose    = clean($_POST['purpose'] ?? 'General Requirements');
    $amount     = (float)($_POST['amount_paid'] ?? 0.00);
    $orNumber   = clean($_POST['or_number'] ?? 'FREE');

    if ($residentId > 0 && !empty($certType)) {
        switch ($certType) {
            case 'Certificate of Indigency': $prefix = 'IND'; break;
            case 'Certificate of Residency': $prefix = 'RES'; break;
            case 'First Time Jobseeker':     $prefix = 'FTJ'; break;
            default:                         $prefix = 'CLR'; break;
        }
        $certNo = $prefix . '-' . date('Y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("
            INSERT INTO barangay_certificates (
                cert_no, resident_id, cert_type, purpose, or_number, amount_paid,
                issued_by_user_id, signed_by_official, issue_date, valid_until
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Hon. Rafael M. Monfort', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH))
        ");
        $stmt->execute([$certNo, $residentId, $certType, $purpose, $orNumber, $amount, $user['id']]);

        log_activity('Issue Certificate', "Issued {$certType} #{$certNo} to resident ID {$residentId}");
        set_flash('success', "Certificate <strong>{$certNo}</strong> generated successfully!");
        
        switch ($certType) {
            case 'Certificate of Indigency': $script = 'print_indigency.php'; break;
            case 'Certificate of Residency': $script = 'print_residency.php'; break;
            default:                         $script = 'print_clearance.php'; break;
        }
        redirect("modules/certificates/{$script}?resident_id={$residentId}&cert_no={$certNo}");
    }
}

// Fetch list of certificates
$sql = "
    SELECT c.*, r.resident_code, r.first_name, r.last_name, r.sitio_id, s.name AS sitio_name,
           u.full_name AS issuer_name
    FROM barangay_certificates c
    JOIN residents r ON c.resident_id = r.id
    JOIN sitios s ON r.sitio_id = s.id
    LEFT JOIN users u ON c.issued_by_user_id = u.id
";
$params = [];

if ($role === 'resident') {
    $sql .= " WHERE r.user_id = ?";
    $params[] = $user['id'];
}

$sql .= " ORDER BY c.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$certificates = $stmt->fetchAll();

$allResidents = $db->query("SELECT id, first_name, last_name, resident_code, sitio_id FROM residents WHERE status = 'approved' ORDER BY last_name ASC")->fetchAll();

// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-0">Barangay Certifications & Clearances</h3>
        <p class="text-muted small mb-0">Official Clearances, Certificates of Indigency & Proof of Residency</p>
    </div>
    <div>
        <?php if (in_array($role, ['captain', 'secretary'])): ?>
            <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#issueCertModal">
                <i class="bi bi-file-earmark-plus me-1"></i> Issue New Certificate
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Template Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-white border-start border-4 border-warning h-100">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-file-earmark-text text-warning fs-1"></i>
                <div>
                    <h6 class="fw-bold mb-1">Certificate of Indigency</h6>
                    <p class="small text-muted mb-2">Used for Hospital/PhilHealth, PAO, Scholarships & DSWD AICS aid.</p>
                    <span class="badge bg-warning text-dark">FREE for Indigents / PWDs</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-white border-start border-4 border-success h-100">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-file-earmark-check text-success fs-1"></i>
                <div>
                    <h6 class="fw-bold mb-1">Barangay Clearance</h6>
                    <p class="small text-muted mb-2">Required for local employment, police clearance, and postal ID.</p>
                    <span class="badge bg-light text-secondary border">Php 50.00 / Free FTJ</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm bg-white border-start border-4 border-info h-100">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-geo-alt text-info fs-1"></i>
                <div>
                    <h6 class="fw-bold mb-1">Certificate of Residency</h6>
                    <p class="small text-muted mb-2">Certifies resident status in 1 of the 7 Sitios of Lublub.</p>
                    <span class="badge bg-info-subtle text-info border">Official Proof</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Issued Certificates Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Issued Documents (<?= count($certificates) ?>)</h5>
        <input type="text" class="form-control form-control-sm table-search-input w-auto" data-target-table="certsTable" placeholder="Filter certificates...">
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="certsTable">
            <thead class="table-light">
                <tr>
                    <th>Certificate No.</th>
                    <th>Document Type</th>
                    <th>Resident Name</th>
                    <th>Sitio</th>
                    <th>Purpose</th>
                    <th>Issued Date</th>
                    <th>Amount Paid</th>
                    <th class="text-end">Print Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($certificates)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            No certificates issued yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($certificates as $cert): ?>
                        <tr>
                            <td><code class="fw-bold text-dark"><?= htmlspecialchars($cert['cert_no']) ?></code></td>
                            <td>
                                <span class="badge bg-<?= str_contains($cert['cert_type'], 'Indigency') ? 'warning text-dark' : 'success' ?>">
                                    <?= htmlspecialchars($cert['cert_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($cert['resident_code']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($cert['sitio_name']) ?></td>
                            <td class="small text-muted" style="max-width: 180px;"><?= htmlspecialchars($cert['purpose']) ?></td>
                            <td class="small"><?= format_date($cert['issue_date']) ?></td>
                            <td>
                                <span class="fw-semibold text-<?= ($cert['amount_paid'] > 0) ? 'dark' : 'success' ?>">
                                    <?= ($cert['amount_paid'] > 0) ? format_peso($cert['amount_paid']) : 'FREE' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php 
                                    switch ($cert['cert_type']) {
                                        case 'Certificate of Indigency': $script = 'print_indigency.php'; break;
                                        case 'Certificate of Residency': $script = 'print_residency.php'; break;
                                        default:                         $script = 'print_clearance.php'; break;
                                    }
                                ?>
                                <a href="<?= url("modules/certificates/{$script}?resident_id={$cert['resident_id']}&cert_no={$cert['cert_no']}") ?>" target="_blank" class="btn btn-sm btn-outline-success fw-bold">
                                    <i class="bi bi-printer me-1"></i> Print
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Issue New Certificate -->
<div class="modal fade" id="issueCertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form action="" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <input type="hidden" name="action" value="issue_certificate">
            <div class="modal-header bg-success text-white py-3 px-4 flex-shrink-0">
                <h5 class="modal-title fw-bold">Issue Official Barangay Certificate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Resident <span class="text-danger">*</span></label>
                    <select name="resident_id" class="form-select" required>
                        <option value="">-- Choose Resident --</option>
                        <?php foreach ($allResidents as $res): ?>
                            <option value="<?= $res['id'] ?>">
                                <?= htmlspecialchars($res['last_name'] . ', ' . $res['first_name']) ?> (<?= htmlspecialchars(SITIOS_LIST[$res['sitio_id']] ?? '') ?>) - <?= $res['resident_code'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Document Type <span class="text-danger">*</span></label>
                    <select name="cert_type" class="form-select" required>
                        <option value="Certificate of Indigency">Certificate of Indigency (Free)</option>
                        <option value="Barangay Clearance">Barangay Clearance</option>
                        <option value="Certificate of Residency">Certificate of Residency</option>
                        <option value="First Time Jobseeker">First Time Jobseeker Certificate (RA 11261)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Purpose <span class="text-danger">*</span></label>
                    <input type="text" name="purpose" class="form-control" placeholder="e.g. Hospitalization & Medical Aid, DSWD Assistance..." required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Amount Paid (PHP)</label>
                        <input type="number" step="0.01" name="amount_paid" class="form-control" value="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">OR Number</label>
                        <input type="text" name="or_number" class="form-control" value="FREE-INDIGENT">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-3 px-4 flex-shrink-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Generate & Print</button>
            </div>
        </form>
    </div>
</div>
