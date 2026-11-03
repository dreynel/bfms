<?php
// views/households/add.php - Pure Dwelling & Environmental Documentation (Head registered separately in Residents)
require_once __DIR__ . '/../../config/app.php';

require_role('bhw');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;
$pageTitle = 'Register Household & BHW Documentation';

$errors = [];

// Auto-generate suggested Household Number
try {
    $suggestedHhNo = generate_household_no($db);
} catch (Exception $e) {
    $suggestedHhNo = 'HH-LUB-' . date('Y') . '-0001';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $householdNo        = clean($_POST['household_no'] ?? '');
    $sitioId            = ($role === 'bhw' && $userSitioId) ? $userSitioId : (int)($_POST['sitio_id'] ?? 0);
    $addressLine        = clean($_POST['address_line'] ?? '');
    $totalMembers       = !empty($_POST['total_members']) ? max(1, (int)$_POST['total_members']) : 1;
    $houseStructure     = clean($_POST['house_structure'] ?? '');
    $sanitationFacility = clean($_POST['sanitation_facility'] ?? '');
    $waterSource        = clean($_POST['water_source'] ?? '');
    $electricityAccess  = isset($_POST['electricity_access']) ? 1 : 0;
    $wasteManagement    = clean($_POST['waste_management'] ?? '');
    $bhwNotes           = clean($_POST['bhw_notes'] ?? '');

    if (empty($householdNo)) {
        $householdNo = $suggestedHhNo;
    }

    if (empty($addressLine)) {
        $errors[] = 'Address line (Street / Purok) is required.';
    }

    if ($sitioId <= 0 || !isset(SITIOS_LIST[$sitioId])) {
        $errors[] = 'Please select a valid Sitio.';
    }

    // Check duplicate household number
    $chkStmt = $db->prepare("SELECT id FROM households WHERE household_no = ?");
    $chkStmt->execute([$householdNo]);
    if ($chkStmt->fetch()) {
        $errors[] = "Household Number <strong>" . htmlspecialchars($householdNo) . "</strong> is already registered. Please use a unique number.";
    }

    // Upload 1: House / Dwelling Photo
    $housePhotoPath = upload_attachment('house_photo', 'households', 'house_');

    // Upload 2: BHW Documentation / Field Survey Proof Photo
    $bhwDocPhotoPath = upload_attachment('bhw_documentation_photo', 'bhw_docs', 'bhwdoc_');

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO households (
                    household_no, sitio_id, address_line, head_resident_name, total_members,
                    house_structure, sanitation_facility, water_source, electricity_access, waste_management,
                    house_photo_path, bhw_documentation_photo, bhw_notes,
                    bhw_assessed_by_user_id, bhw_assessment_date
                ) VALUES (
                    ?, ?, ?, NULL, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, NOW()
                )
            ");
            $stmt->execute([
                $householdNo, $sitioId, $addressLine, $totalMembers,
                $houseStructure, $sanitationFacility, $waterSource, $electricityAccess, $wasteManagement,
                $housePhotoPath, $bhwDocPhotoPath, $bhwNotes,
                $user['id']
            ]);

            $householdId = $db->lastInsertId();

            log_activity('Register Household', "Household {$householdNo} registered in Sitio {$sitioId} by {$user['full_name']}");
            
            // Stick to current page: show success with dossier and member enrollment links
            $successMsg = "Household <strong>" . htmlspecialchars($householdNo) . "</strong> recorded successfully with BHW field survey documentation!" 
                        . " <a href='" . url('households/view?id=' . $householdId) . "' class='btn btn-xs btn-outline-success fw-bold ms-2 py-1 px-2 text-decoration-none'><i class='bi bi-eye me-1'></i>View Dossier</a>"
                        . " <a href='" . url('residents/add?household_no=' . urlencode($householdNo) . '&sitio_id=' . $sitioId . '&is_head=1') . "' class='btn btn-xs btn-success fw-bold ms-1 py-1 px-2 text-decoration-none'><i class='bi bi-person-plus-fill me-1'></i>Enroll Household Head</a>";
            set_flash('success', $successMsg);

            redirect('households/add');
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<style>
/* Mobile-first touch enhancements */
@media (max-width: 767.98px) {
    .form-control, .form-select {
        font-size: 16px !important;
        min-height: 46px;
    }
    .dropzone-box {
        padding: 1.25rem 0.75rem !important;
    }
    .tag-btn {
        padding: 0.45rem 0.75rem !important;
        font-size: 0.85rem !important;
    }
}
.touch-target {
    min-height: 48px;
}
</style>

<div class="container-fluid px-0">
    <!-- Header & Breadcrumbs (Responsive Stacking) -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 mb-md-4 gap-2 gap-md-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('households') ?>">Households</a></li>
                    <li class="breadcrumb-item active" aria-current="page">New Household Survey</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-gray-900 fs-4 fs-md-3">
                <i class="bi bi-houses-fill text-success me-2"></i>Household Dwelling & Sanitation Registry
            </h3>
            <p class="text-muted small mb-0">Record physical dwelling characteristics, water & toilet facilities, and BHW field survey visual proof.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto mt-2 mt-md-0">
            <a href="<?= url('households') ?>" class="btn btn-outline-secondary btn-sm flex-fill flex-md-grow-0 touch-target d-flex align-items-center justify-content-center">
                <i class="bi bi-arrow-left me-1"></i> Directory
            </a>
            <a href="<?= url('residents/add') ?>" class="btn btn-outline-success btn-sm flex-fill flex-md-grow-0 touch-target d-flex align-items-center justify-content-center">
                <i class="bi bi-person-plus me-1"></i> Resident Form
            </a>
        </div>
    </div>

    <!-- Active Sitio Jurisdiction Banner for BHW -->
    <?php if ($role === 'bhw' && $userSitioId): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-3 mb-md-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between p-3 gap-2" style="background: linear-gradient(90deg, #dcfce7 0%, #f0fdf4 100%);">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success text-white p-2 me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                    <i class="bi bi-heart-pulse-fill fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold text-success-emphasis small text-uppercase">BHW Field Survey Mode &bull; <?= htmlspecialchars(SITIOS_LIST[$userSitioId] ?? 'Assigned Sitio') ?></div>
                    <div class="small text-muted">Documenting dwellings and environmental sanitation for your Sitio community.</div>
                </div>
            </div>
            <span class="badge bg-success px-3 py-2 fs-6 align-self-start align-self-sm-center">Sitio <?= $userSitioId ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm border-0 alert-dismissible fade show mb-4 rounded-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-octagon-fill fs-4 me-3 text-danger flex-shrink-0"></i>
                <div>
                    <strong>Please resolve the following:</strong>
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

    <form method="POST" enctype="multipart/form-data" id="householdForm">
        <div class="row g-3 g-md-4">
            <!-- Left Column: Household Dwelling & Socio-Environmental Info -->
            <div class="col-12 col-lg-7">
                <!-- Section 1: Household Identifiers (No resident head here; treated separately) -->
                <div class="card border-0 shadow-sm rounded-4 mb-3 mb-md-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-geo-alt-fill me-2"></i>1. Dwelling Location & Identification
                        </h6>
                        <span class="badge bg-light text-muted border">Physical Unit</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Household Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-hash"></i></span>
                                    <input type="text" name="household_no" class="form-control font-monospace fw-bold" placeholder="<?= htmlspecialchars($suggestedHhNo) ?>" value="<?= htmlspecialchars($_POST['household_no'] ?? '') ?>" required>
                                </div>
                                <div class="form-text small">Unique barangay household identifier code (e.g. <?= htmlspecialchars($suggestedHhNo) ?>).</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Sitio Territory <span class="text-danger">*</span></label>
                                <?php if ($role === 'bhw' && $userSitioId): ?>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-success"><i class="bi bi-geo-alt-fill"></i></span>
                                        <input type="text" class="form-control bg-light fw-semibold" value="<?= htmlspecialchars(SITIOS_LIST[$userSitioId] ?? 'Assigned Sitio') ?>" readonly>
                                        <input type="hidden" name="sitio_id" value="<?= $userSitioId ?>">
                                    </div>
                                    <div class="form-text small text-success"><i class="bi bi-lock-fill me-1"></i>Locked to your assigned Sitio jurisdiction.</div>
                                <?php else: ?>
                                    <select name="sitio_id" class="form-select" required>
                                        <option value="">-- Select Sitio (1 of 7) --</option>
                                        <?php foreach (SITIOS_LIST as $id => $sName): ?>
                                            <option value="<?= $id ?>" <?= (($_POST['sitio_id'] ?? '') == $id) ? 'selected' : '' ?>>
                                                Sitio <?= $id ?> - <?= htmlspecialchars($sName) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 col-md-8">
                                <label class="form-label fw-semibold small text-muted">Street / Purok / House Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-signpost-2"></i></span>
                                    <input type="text" name="address_line" class="form-control" placeholder="e.g. Purok 2, Near Chapel, Barangay Lublub" value="<?= htmlspecialchars($_POST['address_line'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small text-muted">Total Living Members</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-people-fill"></i></span>
                                    <input type="number" name="total_members" class="form-control text-center fw-bold" min="1" max="30" placeholder="e.g. 1" value="<?= htmlspecialchars($_POST['total_members'] ?? '') ?>">
                                </div>
                                <div class="form-text small">Count of residents living here.</div>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="p-3 bg-light rounded-3 border d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill text-primary fs-5 flex-shrink-0"></i>
                                    <div class="small text-muted">
                                        <strong>Household Head Separation:</strong> Head of Household is a resident profile and is designated separately under <strong><a href="<?= url('residents/add') ?>" class="text-success fw-bold text-decoration-none">Resident Registration</a></strong>.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Dwelling Structure & Environmental Sanitation -->
                <div class="card border-0 shadow-sm rounded-4 mb-3 mb-md-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-building me-2"></i>2. Dwelling & Sanitation Assessment
                        </h6>
                        <span class="badge bg-light text-muted border">Health & Sanitation</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Housing / Wall Structure</label>
                                <select name="house_structure" class="form-select">
                                    <option value="">-- Select Housing Structure --</option>
                                    <option value="Concrete" <?= (($_POST['house_structure'] ?? '') === 'Concrete') ? 'selected' : '' ?>>🧱 Concrete / Sturdy</option>
                                    <option value="Semi-Concrete" <?= (($_POST['house_structure'] ?? '') === 'Semi-Concrete') ? 'selected' : '' ?>>🏠 Semi-Concrete</option>
                                    <option value="Light Materials / Wood" <?= (($_POST['house_structure'] ?? '') === 'Light Materials / Wood') ? 'selected' : '' ?>>🪵 Light Materials / Bamboo / Wood</option>
                                    <option value="Makeshift" <?= (($_POST['house_structure'] ?? '') === 'Makeshift') ? 'selected' : '' ?>>🏚️ Makeshift / Salvaged Materials</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Sanitation / Toilet Facility</label>
                                <select name="sanitation_facility" class="form-select border-primary-subtle">
                                    <option value="">-- Select Sanitation Facility --</option>
                                    <option value="Water-sealed Toilet" <?= (($_POST['sanitation_facility'] ?? '') === 'Water-sealed Toilet') ? 'selected' : '' ?>>🚽 Water-sealed Toilet (Exclusive)</option>
                                    <option value="Pit Latrine" <?= (($_POST['sanitation_facility'] ?? '') === 'Pit Latrine') ? 'selected' : '' ?>>🕳️ Pit Latrine / Antipolo</option>
                                    <option value="Shared / Communal" <?= (($_POST['sanitation_facility'] ?? '') === 'Shared / Communal') ? 'selected' : '' ?>>👥 Shared / Communal Toilet</option>
                                    <option value="No Facility" <?= (($_POST['sanitation_facility'] ?? '') === 'No Facility') ? 'selected' : '' ?>>⚠️ No Facility / Open Defecation Risk</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Main Drinking Water Source</label>
                                <select name="water_source" class="form-select">
                                    <option value="">-- Select Drinking Water Source --</option>
                                    <option value="Piped Water / Tap" <?= (($_POST['water_source'] ?? '') === 'Piped Water / Tap') ? 'selected' : '' ?>>🚰 Piped Water / Tap (Level III)</option>
                                    <option value="Deep Well" <?= (($_POST['water_source'] ?? '') === 'Deep Well') ? 'selected' : '' ?>>🏗️ Deep Well / Hand Pump</option>
                                    <option value="Protected Spring" <?= (($_POST['water_source'] ?? '') === 'Protected Spring') ? 'selected' : '' ?>>⛰️ Protected Spring</option>
                                    <option value="Communal Faucet" <?= (($_POST['water_source'] ?? '') === 'Communal Faucet') ? 'selected' : '' ?>>⛲ Communal Faucet (Level II)</option>
                                    <option value="Mineral/Bottled" <?= (($_POST['water_source'] ?? '') === 'Mineral/Bottled') ? 'selected' : '' ?>>🧴 Refilling Station / Mineral</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-muted">Waste Management Practice</label>
                                <select name="waste_management" class="form-select">
                                    <option value="">-- Select Waste Practice --</option>
                                    <option value="Barangay Garbage Collection" <?= (($_POST['waste_management'] ?? '') === 'Barangay Garbage Collection') ? 'selected' : '' ?>>🚛 Barangay Garbage Collection</option>
                                    <option value="Composting" <?= (($_POST['waste_management'] ?? '') === 'Composting') ? 'selected' : '' ?>>🌱 Composting / Organic Pit</option>
                                    <option value="Segregated Disposal" <?= (($_POST['waste_management'] ?? '') === 'Segregated Disposal') ? 'selected' : '' ?>>♻️ Segregated Materials Recovery</option>
                                    <option value="Burning / Open" <?= (($_POST['waste_management'] ?? '') === 'Burning / Open') ? 'selected' : '' ?>>🔥 Burning / Open Dumping</option>
                                </select>
                            </div>

                            <div class="col-12 mt-3">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" name="electricity_access" id="electricityAccess" value="1" <?= isset($_POST['electricity_access']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="electricityAccess">
                                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Access to Electricity / Power Connection (ILECO)
                                    </label>
                                    <div class="form-text small ps-sm-5">Check if the dwelling has an active electrical service connection.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: BHW Field Notes & Quick Health Observation Tags -->
                <div class="card border-0 shadow-sm rounded-4 mb-3 mb-md-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-clipboard-pulse me-2"></i>3. BHW Field Survey Notes & Observations
                        </h6>
                        <span class="badge bg-success bg-opacity-10 text-success">Field Duty</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <label class="form-label fw-semibold small text-muted">BHW Field Remarks & Health Assessment</label>
                        <textarea name="bhw_notes" id="bhwNotesArea" rows="3" class="form-control" placeholder="Describe living conditions, hygiene, bedridden seniors, infants present, or any urgent barangay assistance needed..."><?= htmlspecialchars($_POST['bhw_notes'] ?? '') ?></textarea>
                        
                        <!-- Quick Click Tags (Touch-Friendly Flex Wrap) -->
                        <div class="mt-2">
                            <span class="small text-muted d-block mb-1"><i class="bi bi-tags me-1"></i>Quick Tags (Tap to add to notes):</span>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-secondary tag-btn py-1 px-2" data-tag="[Infant / Newborn Present]">👶 Infant Present</button>
                                <button type="button" class="btn btn-outline-secondary tag-btn py-1 px-2" data-tag="[Bedridden Senior Citizen]">👴 Bedridden Senior</button>
                                <button type="button" class="btn btn-outline-secondary tag-btn py-1 px-2" data-tag="[PWD Member in House]">♿ PWD Member</button>
                                <button type="button" class="btn btn-outline-secondary tag-btn py-1 px-2" data-tag="[Sanitation Hazard: No Sanitary Toilet]">🚽 Toilet Hazard</button>
                                <button type="button" class="btn btn-outline-secondary tag-btn py-1 px-2" data-tag="[Malnutrition / Feeding Priority]">🥣 Feeding Priority</button>
                                <button type="button" class="btn btn-outline-secondary tag-btn py-1 px-2" data-tag="[High Flood Hazard Zone]">🌊 Flood Risk</button>
                            </div>
                        </div>

                        <div class="mt-3 p-3 bg-light rounded-3 border d-flex align-items-center">
                            <div class="bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                <i class="bi bi-person-badge fs-5"></i>
                            </div>
                            <div class="small">
                                <div class="fw-bold text-dark">Survey Assessor: <?= htmlspecialchars($user['full_name']) ?></div>
                                <div class="text-muted small">Account: <span class="badge bg-secondary"><?= strtoupper($role) ?></span> &bull; <?= date('M d, Y') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Dual Drag & Drop Upload Dropzones & Save Actions -->
            <div class="col-12 col-lg-5">
                <!-- Dropzone 1: House / Dwelling Photo -->
                <div class="card border-0 shadow-sm rounded-4 mb-3 mb-md-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-primary">
                            <i class="bi bi-camera-fill me-2"></i>Dwelling Exterior Photo
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary">House Proof</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <p class="text-muted small mb-3">Upload exterior photograph of the dwelling for validation and disaster mapping.</p>
                        
                        <!-- Drag-and-Drop Area (Touch and Camera Enabled) -->
                        <div class="dropzone-box text-center p-3 p-md-4 rounded-4 border-2 border-dashed bg-light position-relative" id="dropzoneHouse" style="border-color: #cbd5e1; cursor: pointer; transition: all 0.2s ease;">
                            <input type="file" name="house_photo" id="houseFileInput" accept="image/jpeg,image/png,image/webp" capture="environment" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                            <div class="dz-prompt py-2 py-md-3" id="housePrompt">
                                <div class="mb-2">
                                    <i class="bi bi-camera-fill fs-1 text-primary"></i>
                                </div>
                                <div class="fw-bold text-dark mb-1">Tap to Open Camera or Choose Photo</div>
                                <div class="text-muted small">JPG, PNG, WEBP (Max 5MB)</div>
                            </div>
                            <div class="dz-preview d-none" id="housePreviewBox">
                                <img src="" alt="House Preview" class="img-fluid rounded-3 shadow-sm mb-2" id="housePreviewImg" style="max-height: 180px; object-fit: cover; width: 100%;">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge bg-success" id="houseFileName">Photo Selected</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btnRemoveHousePhoto">
                                        <i class="bi bi-x-circle me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropzone 2: BHW Duty Documentation / Field Survey Proof -->
                <div class="card border-0 shadow-sm rounded-4 mb-3 mb-md-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-success">
                            <i class="bi bi-clipboard2-check-fill me-2"></i>BHW Duty Documentation Proof
                        </h6>
                        <span class="badge bg-success bg-opacity-10 text-success">Field Duty Proof</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <p class="text-muted small mb-3">Upload photo of the BHW conducting the household visitation or signed survey sheet.</p>
                        
                        <!-- Drag-and-Drop Area (Touch and Camera Enabled) -->
                        <div class="dropzone-box text-center p-3 p-md-4 rounded-4 border-2 border-dashed bg-light position-relative" id="dropzoneBhw" style="border-color: #cbd5e1; cursor: pointer; transition: all 0.2s ease;">
                            <input type="file" name="bhw_documentation_photo" id="bhwFileInput" accept="image/jpeg,image/png,image/webp" capture="environment" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                            <div class="dz-prompt py-2 py-md-3" id="bhwPrompt">
                                <div class="mb-2">
                                    <i class="bi bi-shield-check fs-1 text-success"></i>
                                </div>
                                <div class="fw-bold text-dark mb-1">Tap to Take BHW Field Proof Photo</div>
                                <div class="text-muted small">JPG, PNG, WEBP (Max 5MB)</div>
                            </div>
                            <div class="dz-preview d-none" id="bhwPreviewBox">
                                <img src="" alt="BHW Doc Preview" class="img-fluid rounded-3 shadow-sm mb-2" id="bhwPreviewImg" style="max-height: 180px; object-fit: cover; width: 100%;">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge bg-success" id="bhwFileName">Proof Selected</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btnRemoveBhwPhoto">
                                        <i class="bi bi-x-circle me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button Card (Sticks to current page, no redirect prompts) -->
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 mb-4">
                    <button type="submit" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 touch-target">
                        <i class="bi bi-check2-circle fs-5"></i>
                        <span>Save Household & Documentation</span>
                    </button>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <a href="<?= url('households') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Directory
                        </a>
                        <span class="text-muted small"><i class="bi bi-arrow-repeat me-1"></i>Stays on this form</span>
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

        if (!dropzone || !input) return;

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

    // Quick tag insertion into BHW Notes
    const notesArea = document.getElementById('bhwNotesArea');
    document.querySelectorAll('.tag-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tag = this.getAttribute('data-tag');
            if (notesArea) {
                if (notesArea.value.trim().length > 0) {
                    notesArea.value += ' ' + tag;
                } else {
                    notesArea.value = tag;
                }
                notesArea.focus();
            }
        });
    });
});
</script>
