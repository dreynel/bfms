<?php
// views/households/edit.php - Edit Household Profiling & Update BHW Documentation
require_once __DIR__ . '/../../config/app.php';

require_role('bhw');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('error', 'Invalid household identifier.');
    redirect(url('households'));
}

$stmt = $db->prepare("SELECT * FROM households WHERE id = ?");
$stmt->execute([$id]);
$hh = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hh) {
    set_flash('error', 'Household not found.');
    redirect(url('households'));
}

// Security: BHW can only edit within their assigned Sitio
if ($role === 'bhw' && $userSitioId && $hh['sitio_id'] != $userSitioId) {
    set_flash('error', 'You can only edit households in your assigned Sitio.');
    redirect(url('households'));
}

$errors = [];
$pageTitle = 'Edit Household - ' . htmlspecialchars($hh['household_no']);

// Fetch designated resident head if one exists
$headStmt = $db->prepare("SELECT id, first_name, last_name, suffix FROM residents WHERE household_no = ? AND is_head_of_family = 1 LIMIT 1");
$headStmt->execute([$hh['household_no']]);
$headPerson = $headStmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $householdNo        = clean($_POST['household_no'] ?? '');
    $sitioId            = ($role === 'bhw' && $userSitioId) ? $userSitioId : (int)($_POST['sitio_id'] ?? 0);
    $addressLine        = clean($_POST['address_line'] ?? '');
    $totalMembers       = max(1, (int)($_POST['total_members'] ?? 1));
    $houseStructure     = clean($_POST['house_structure'] ?? 'Concrete');
    $sanitationFacility = clean($_POST['sanitation_facility'] ?? 'Water-sealed Toilet');
    $waterSource        = clean($_POST['water_source'] ?? 'Piped Water / Tap');
    $electricityAccess  = isset($_POST['electricity_access']) ? 1 : 0;
    $wasteManagement    = clean($_POST['waste_management'] ?? 'Barangay Garbage Collection');
    $bhwNotes           = clean($_POST['bhw_notes'] ?? '');

    if (empty($householdNo)) {
        $errors[] = 'Household number cannot be empty.';
    }

    if (empty($addressLine)) {
        $errors[] = 'Address line is required.';
    }

    if ($sitioId <= 0 || !isset(SITIOS_LIST[$sitioId])) {
        $errors[] = 'Please select a valid Sitio.';
    }

    // Check duplicate household number excluding self
    $chkStmt = $db->prepare("SELECT id FROM households WHERE household_no = ? AND id != ?");
    $chkStmt->execute([$householdNo, $id]);
    if ($chkStmt->fetch()) {
        $errors[] = "Household Number <strong>" . htmlspecialchars($householdNo) . "</strong> is already taken by another household.";
    }

    $housePhotoPath = $hh['house_photo_path'];
    $newHousePhoto = upload_attachment('house_photo', 'households', 'house_');
    if ($newHousePhoto) {
        $housePhotoPath = $newHousePhoto;
    }

    $bhwDocPhotoPath = $hh['bhw_documentation_photo'];
    $newBhwDoc = upload_attachment('bhw_documentation_photo', 'bhw_docs', 'bhwdoc_');
    if ($newBhwDoc) {
        $bhwDocPhotoPath = $newBhwDoc;
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE households SET
                    household_no = ?,
                    sitio_id = ?,
                    address_line = ?,
                    total_members = ?,
                    house_structure = ?,
                    sanitation_facility = ?,
                    water_source = ?,
                    electricity_access = ?,
                    waste_management = ?,
                    house_photo_path = ?,
                    bhw_documentation_photo = ?,
                    bhw_notes = ?,
                    bhw_assessed_by_user_id = ?,
                    bhw_assessment_date = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $householdNo, $sitioId, $addressLine, $totalMembers,
                $houseStructure, $sanitationFacility, $waterSource, $electricityAccess, $wasteManagement,
                $housePhotoPath, $bhwDocPhotoPath, $bhwNotes,
                $user['id'],
                $id
            ]);

            // If household_no changed, also update linked residents
            if ($householdNo !== $hh['household_no']) {
                $updRes = $db->prepare("UPDATE residents SET household_no = ? WHERE household_no = ?");
                $updRes->execute([$householdNo, $hh['household_no']]);
            }

            log_activity('Update Household', "Updated Household {$householdNo} by {$user['full_name']}");
            set_flash('success', "Household <strong>{$householdNo}</strong> updated successfully!");
            redirect(url('households/view?id=' . $id));
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('households') ?>">Households</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('households/view?id=' . $hh['id']) ?>"><?= htmlspecialchars($hh['household_no']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-gray-900">
                <i class="bi bi-pencil-square text-success me-2"></i>Edit Household & BHW Documentation
            </h3>
            <p class="text-muted small mb-0">Modify socio-environmental characteristics or replace dwelling / survey photos.</p>
        </div>
        <div>
            <a href="<?= url('households/view?id=' . $hh['id']) ?>" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Cancel & Return
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm border-0 alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                <div>
                    <strong>Please review the following errors:</strong>
                    <ul class="mb-0 mt-1 small">
                        <?php foreach ($errors as $err): ?>
                            <li><?= $err ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="editHouseholdForm" class="needs-validation" novalidate>
        <div class="row g-4">
            <!-- Left Column: Household Identity & Socio-Environmental Info -->
            <div class="col-lg-7">
                <!-- Card 1: Identifiers -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-geo-alt-fill me-2"></i>1. Location & Identification
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Household Number <span class="text-danger">*</span></label>
                                <input type="text" name="household_no" class="form-control" value="<?= htmlspecialchars($_POST['household_no'] ?? $hh['household_no']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Sitio Territory <span class="text-danger">*</span></label>
                                <?php if ($role === 'bhw' && $userSitioId): ?>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(SITIOS_LIST[$userSitioId] ?? 'Assigned Sitio') ?>" readonly>
                                    <input type="hidden" name="sitio_id" value="<?= $userSitioId ?>">
                                <?php else: ?>
                                    <select name="sitio_id" class="form-select" required>
                                        <?php foreach (SITIOS_LIST as $sId => $sName): ?>
                                            <option value="<?= $sId ?>" <?= ((int)($_POST['sitio_id'] ?? $hh['sitio_id']) === $sId) ? 'selected' : '' ?>>
                                                Sitio <?= $sId ?> - <?= htmlspecialchars($sName) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-muted">Street / Purok Address <span class="text-danger">*</span></label>
                                <input type="text" name="address_line" class="form-control" value="<?= htmlspecialchars($_POST['address_line'] ?? $hh['address_line']) ?>" required>
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label fw-semibold small text-muted">Head of Household</label>
                                <div class="p-2 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                                    <div>
                                        <?php if ($headPerson): ?>
                                            <span class="fw-bold text-success"><i class="bi bi-person-check-fill me-1"></i><?= htmlspecialchars($headPerson['first_name'] . ' ' . $headPerson['last_name'] . ' ' . $headPerson['suffix']) ?></span>
                                            <span class="badge bg-success bg-opacity-10 text-success ms-1">Resident Head</span>
                                        <?php elseif (!empty($hh['head_resident_name'])): ?>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($hh['head_resident_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic"><i class="bi bi-person-x me-1"></i>No head designated yet</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= url('residents/add?household_no=' . urlencode($hh['household_no']) . '&sitio_id=' . $hh['sitio_id'] . '&is_head=1') ?>" class="btn btn-xs btn-outline-success py-1 px-2 small">
                                        <i class="bi bi-person-plus me-1"></i> <?= ($headPerson || !empty($hh['head_resident_name'])) ? 'Change / Add Member' : 'Enroll Head' ?>
                                    </a>
                                </div>
                                <div class="form-text small text-muted">Heads of Household are managed separately in the Resident Registry.</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small text-muted">Total Living Members</label>
                                <input type="number" name="total_members" class="form-control" min="1" max="30" value="<?= htmlspecialchars($_POST['total_members'] ?? $hh['total_members']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Dwelling Characteristics -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-building me-2"></i>2. Dwelling Characteristics & Sanitation
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Housing / Wall Structure</label>
                                <?php $selStruct = $_POST['house_structure'] ?? $hh['house_structure']; ?>
                                <select name="house_structure" class="form-select">
                                    <option value="Concrete" <?= ($selStruct === 'Concrete') ? 'selected' : '' ?>>Concrete / Sturdy</option>
                                    <option value="Semi-Concrete" <?= ($selStruct === 'Semi-Concrete') ? 'selected' : '' ?>>Semi-Concrete</option>
                                    <option value="Light Materials / Wood" <?= ($selStruct === 'Light Materials / Wood') ? 'selected' : '' ?>>Light Materials / Bamboo / Wood</option>
                                    <option value="Makeshift" <?= ($selStruct === 'Makeshift') ? 'selected' : '' ?>>Makeshift / Salvaged Materials</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Sanitation / Toilet Facility</label>
                                <?php $selSanit = $_POST['sanitation_facility'] ?? $hh['sanitation_facility']; ?>
                                <select name="sanitation_facility" class="form-select">
                                    <option value="Water-sealed Toilet" <?= ($selSanit === 'Water-sealed Toilet') ? 'selected' : '' ?>>Water-sealed Toilet (Exclusive)</option>
                                    <option value="Pit Latrine" <?= ($selSanit === 'Pit Latrine') ? 'selected' : '' ?>>Pit Latrine / Antipolo</option>
                                    <option value="Shared / Communal" <?= ($selSanit === 'Shared / Communal') ? 'selected' : '' ?>>Shared / Communal Toilet</option>
                                    <option value="No Facility" <?= ($selSanit === 'No Facility') ? 'selected' : '' ?>>No Facility / Open Defecation Risk</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Main Drinking Water Source</label>
                                <?php $selWater = $_POST['water_source'] ?? $hh['water_source']; ?>
                                <select name="water_source" class="form-select">
                                    <option value="Piped Water / Tap" <?= ($selWater === 'Piped Water / Tap') ? 'selected' : '' ?>>Piped Water / Tap (Level III)</option>
                                    <option value="Deep Well" <?= ($selWater === 'Deep Well') ? 'selected' : '' ?>>Deep Well / Hand Pump</option>
                                    <option value="Protected Spring" <?= ($selWater === 'Protected Spring') ? 'selected' : '' ?>>Protected Spring</option>
                                    <option value="Communal Faucet" <?= ($selWater === 'Communal Faucet') ? 'selected' : '' ?>>Communal Faucet (Level II)</option>
                                    <option value="Mineral/Bottled" <?= ($selWater === 'Mineral/Bottled') ? 'selected' : '' ?>>Refilling Station / Mineral</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Waste Management Practice</label>
                                <?php $selWaste = $_POST['waste_management'] ?? $hh['waste_management']; ?>
                                <select name="waste_management" class="form-select">
                                    <option value="Barangay Garbage Collection" <?= ($selWaste === 'Barangay Garbage Collection') ? 'selected' : '' ?>>Barangay Garbage Collection</option>
                                    <option value="Composting" <?= ($selWaste === 'Composting') ? 'selected' : '' ?>>Composting / Pit</option>
                                    <option value="Segregated Disposal" <?= ($selWaste === 'Segregated Disposal') ? 'selected' : '' ?>>Segregated Materials Recovery</option>
                                    <option value="Burning / Open" <?= ($selWaste === 'Burning / Open') ? 'selected' : '' ?>>Burning / Open Dumping</option>
                                </select>
                            </div>

                            <div class="col-12 mt-3">
                                <?php $hasElec = isset($_POST['electricity_access']) ? 1 : (int)($hh['electricity_access'] ?? 1); ?>
                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" name="electricity_access" id="electricityAccess" value="1" <?= $hasElec ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="electricityAccess">
                                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Access to Electricity / Power Connection (ILECO)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: BHW Notes -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-clipboard-pulse me-2"></i>3. BHW Field Notes & Duty Assessment
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <label class="form-label fw-semibold small text-muted">BHW Field Remarks & Living Conditions</label>
                        <textarea name="bhw_notes" rows="3" class="form-control"><?= htmlspecialchars($_POST['bhw_notes'] ?? $hh['bhw_notes']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Right Column: Dual Drag & Drop Upload Dropzones -->
            <div class="col-lg-5">
                <!-- Dropzone 1: House Photo -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-primary">
                            <i class="bi bi-camera-fill me-2"></i>House / Dwelling Photo
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">Dwelling Proof</span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($hh['house_photo_path']) && file_exists(__DIR__ . '/../../' . $hh['house_photo_path'])): ?>
                            <div class="text-center mb-3 p-2 bg-light rounded-3 border">
                                <div class="small text-muted mb-1">Current Dwelling Photo:</div>
                                <a href="<?= asset($hh['house_photo_path']) ?>" class="preview-attachment-btn d-inline-block" data-preview-title="Current Dwelling Photo" data-preview-subtitle="Household #<?= htmlspecialchars($hh['household_no']) ?>" title="Click to enlarge in modal">
                                    <img src="<?= asset($hh['house_photo_path']) ?>" alt="Current House" class="img-fluid rounded-2 shadow-sm" style="max-height: 140px; object-fit: cover; cursor: pointer;">
                                </a>
                                <div class="mt-1"><span class="badge bg-white text-primary border" style="font-size: 0.72rem;"><i class="bi bi-arrows-fullscreen me-1"></i>Click image to preview modal</span></div>
                            </div>
                        <?php endif; ?>

                        <!-- Dropzone Box -->
                        <div class="dropzone-box text-center p-3 rounded-4 border-2 border-dashed bg-light position-relative" id="dropzoneHouse" style="border-color: #cbd5e1; cursor: pointer;">
                            <input type="file" name="house_photo" id="houseFileInput" accept="image/jpeg,image/png,image/webp" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                            <div class="dz-prompt py-2" id="housePrompt">
                                <div class="mb-1"><i class="bi bi-cloud-arrow-up-fill fs-2 text-primary"></i></div>
                                <div class="fw-bold text-dark small">Drag & Drop new image to replace</div>
                                <div class="text-muted" style="font-size: 0.75rem;">JPG, PNG, WEBP max 5MB</div>
                            </div>
                            <div class="dz-preview d-none" id="housePreviewBox">
                                <img src="" alt="New House Preview" class="img-fluid rounded-3 shadow-sm mb-2" id="housePreviewImg" style="max-height: 160px; object-fit: cover; width: 100%;">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge bg-success" id="houseFileName">New Photo</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btnRemoveHousePhoto">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropzone 2: BHW Doc Photo -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-clipboard2-check-fill me-2"></i>BHW Duty Documentation Proof
                        </h6>
                        <span class="badge bg-success bg-opacity-10 text-success">Survey Proof</span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($hh['bhw_documentation_photo']) && file_exists(__DIR__ . '/../../' . $hh['bhw_documentation_photo'])): ?>
                            <div class="text-center mb-3 p-2 bg-light rounded-3 border">
                                <div class="small text-muted mb-1">Current BHW Field Proof:</div>
                                <a href="<?= asset($hh['bhw_documentation_photo']) ?>" class="preview-attachment-btn d-inline-block" data-preview-title="Current BHW Field Proof" data-preview-subtitle="Household #<?= htmlspecialchars($hh['household_no']) ?>" title="Click to enlarge in modal">
                                    <img src="<?= asset($hh['bhw_documentation_photo']) ?>" alt="Current BHW Proof" class="img-fluid rounded-2 shadow-sm border border-success" style="max-height: 140px; object-fit: cover; cursor: pointer;">
                                </a>
                                <div class="mt-1"><span class="badge bg-white text-success border" style="font-size: 0.72rem;"><i class="bi bi-arrows-fullscreen me-1"></i>Click image to preview modal</span></div>
                            </div>
                        <?php endif; ?>

                        <!-- Dropzone Box -->
                        <div class="dropzone-box text-center p-3 rounded-4 border-2 border-dashed bg-light position-relative" id="dropzoneBhw" style="border-color: #cbd5e1; cursor: pointer;">
                            <input type="file" name="bhw_documentation_photo" id="bhwFileInput" accept="image/jpeg,image/png,image/webp" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                            <div class="dz-prompt py-2" id="bhwPrompt">
                                <div class="mb-1"><i class="bi bi-shield-check fs-2 text-success"></i></div>
                                <div class="fw-bold text-dark small">Drag & Drop new proof to replace</div>
                                <div class="text-muted" style="font-size: 0.75rem;">JPG, PNG, WEBP max 5MB</div>
                            </div>
                            <div class="dz-preview d-none" id="bhwPreviewBox">
                                <img src="" alt="New BHW Preview" class="img-fluid rounded-3 shadow-sm mb-2" id="bhwPreviewImg" style="max-height: 160px; object-fit: cover; width: 100%;">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge bg-success" id="bhwFileName">New Proof</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btnRemoveBhwPhoto">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Button Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-light">
                    <div class="card-body p-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm mb-2">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                        <a href="<?= url('households/view?id=' . $hh['id']) ?>" class="btn btn-outline-secondary w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupDropzone(dropzoneId, inputId, promptId, previewBoxId, previewImgId, fileNameId, removeBtnId, activeColor) {
        const dropzone = document.getElementById(dropzoneId);
        const input = document.getElementById(inputId);
        const prompt = document.getElementById(promptId);
        const previewBox = document.getElementById(previewBoxId);
        const previewImg = document.getElementById(previewImgId);
        const fileNameSpan = document.getElementById(fileNameId);
        const removeBtn = document.getElementById(removeBtnId);

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.borderColor = activeColor;
                dropzone.style.backgroundColor = '#f0fdf4';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.borderColor = '#cbd5e1';
                dropzone.style.backgroundColor = '#f8fafc';
            }, false);
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                input.files = files;
                handleFiles(files[0]);
            }
        });

        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                handleFiles(this.files[0]);
            }
        });

        function handleFiles(file) {
            if (!file.type.match('image.*')) {
                alert('Please upload an image file (JPG, PNG, or WEBP).');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                fileNameSpan.textContent = file.name;
                prompt.classList.add('d-none');
                previewBox.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }

        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            input.value = '';
            previewImg.src = '';
            previewBox.classList.add('d-none');
            prompt.classList.remove('d-none');
        });
    }

    setupDropzone('dropzoneHouse', 'houseFileInput', 'housePrompt', 'housePreviewBox', 'housePreviewImg', 'houseFileName', 'btnRemoveHousePhoto', '#3b82f6');
    setupDropzone('dropzoneBhw', 'bhwFileInput', 'bhwPrompt', 'bhwPreviewBox', 'bhwPreviewImg', 'bhwFileName', 'btnRemoveBhwPhoto', '#10b981');
});
</script>

