<?php
// social_services/create - Launch Assistance Drive with Auto-Enrollment Targeting
require_once __DIR__ . '/../../config/app.php';

require_role('secretary');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$pageTitle = 'Launch Assistance / Ayuda Program';

$errors = [];

// Fetch all active households with details and member counts for targeting
$allHouseholds = [];
try {
    $hhSql = "
        SELECT h.id, h.household_no, h.sitio_id, h.address_line, h.head_resident_name, h.house_structure, h.sanitation_facility,
               s.name AS sitio_name,
               (SELECT COUNT(*) FROM residents r WHERE r.household_no = h.household_no AND (r.is_archived = 0 OR r.is_archived IS NULL) AND r.status = 'approved') AS member_count
        FROM households h
        JOIN sitios s ON h.sitio_id = s.id
        WHERE (h.is_archived = 0 OR h.is_archived IS NULL)
        ORDER BY s.id ASC, h.household_no ASC
    ";
    $allHouseholds = $db->query($hhSql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allHouseholds = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = clean($_POST['title'] ?? '');
    $category       = clean($_POST['category'] ?? '');
    $description    = clean($_POST['description'] ?? '');
    $fundSource     = clean($_POST['fund_source'] ?? '');
    $amount         = (float)($_POST['amount_per_beneficiary'] ?? 0.00);
    $inKind         = clean($_POST['in_kind_details'] ?? '');
    $targetSitioId  = !empty($_POST['target_sitio_id']) ? (int)$_POST['target_sitio_id'] : null;
    $distDate       = clean($_POST['distribution_date'] ?? '');

    // Targeting Criteria
    $targetPwd      = isset($_POST['target_pwd']) ? 1 : 0;
    $targetSolo     = isset($_POST['target_solo_parent']) ? 1 : 0;
    $targetIndigent = isset($_POST['target_indigent']) ? 1 : 0;
    $targetSenior   = isset($_POST['target_senior']) ? 1 : 0;
    $target4ps      = isset($_POST['target_4ps']) ? 1 : 0;
    $targetStudent  = isset($_POST['target_student']) ? 1 : 0;
    $autoEnroll     = isset($_POST['auto_enroll']) ? 1 : 0;

    // Household Targeting
    $targetByHousehold  = isset($_POST['target_by_household']) ? 1 : 0;
    $selectedHouseholds = isset($_POST['selected_households']) ? (array)$_POST['selected_households'] : [];
    $householdScope     = clean($_POST['household_enroll_scope'] ?? 'heads_only'); // 'heads_only' or 'all_members'

    if (empty($title) || empty($description)) {
        $errors[] = 'Program title and description are required.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $programCode = 'AID-' . date('Y') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);

            $targetCriteriaJson = json_encode([
                'target_sitio_id' => $targetSitioId,
                'sectors' => [
                    'pwd' => $targetPwd,
                    'solo_parent' => $targetSolo,
                    'indigent' => $targetIndigent,
                    'senior' => $targetSenior,
                    '4ps' => $target4ps,
                    'student' => $targetStudent
                ],
                'target_by_household' => (!empty($selectedHouseholds) ? 1 : $targetByHousehold),
                'household_scope' => $householdScope,
                'selected_households' => $selectedHouseholds,
                'selected_households_count' => count($selectedHouseholds)
            ]);

            $stmtProg = $db->prepare("
                INSERT INTO assistance_programs (
                    title, program_code, category, description, fund_source,
                    amount_per_beneficiary, in_kind_details, target_sitio_id, target_criteria,
                    distribution_date, status, created_by_user_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending Approval', ?)
            ");
            $stmtProg->execute([
                $title, $programCode, $category, $description, $fundSource,
                $amount, $inKind, $targetSitioId, $targetCriteriaJson, $distDate, $user['id']
            ]);
            $programId = (int)$db->lastInsertId();

            // Centralized Approval Registration (Order 1: Punong Barangay)
            create_approval_request(
                'assistance_program',
                $programId,
                "Assistance Drive: " . $title,
                $user['id'],
                false,
                "Created by Barangay Secretary (" . $user['full_name'] . ") and submitted for Punong Barangay executive approval."
            );

            $enrolledResidentIds = [];

            // 1. Process Household Targeting (e.g. Flooded Areas, Calamity Relief)
            // If households were selected, enroll them immediately into the roster
            if (!empty($selectedHouseholds)) {
                $cleanHhNos = array_values(array_filter(array_map('clean', $selectedHouseholds)));

                if (!empty($cleanHhNos)) {
                    if ($householdScope === 'heads_only') {
                        // Enroll 1 per household: designated head or oldest adult
                        foreach ($cleanHhNos as $hNo) {
                            $headStmt = $db->prepare("
                                SELECT id FROM residents 
                                WHERE TRIM(household_no) = TRIM(?) AND is_head_of_family = 1 AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)
                                LIMIT 1
                            ");
                            $headStmt->execute([$hNo]);
                            $resId = $headStmt->fetchColumn();

                            if (!$resId) {
                                $oldestStmt = $db->prepare("
                                    SELECT id FROM residents 
                                    WHERE TRIM(household_no) = TRIM(?) AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)
                                    ORDER BY age DESC LIMIT 1
                                ");
                                $oldestStmt->execute([$hNo]);
                                $resId = $oldestStmt->fetchColumn();
                            }

                            if ($resId) {
                                $enrolledResidentIds[(int)$resId] = true;
                            }
                        }
                    } else {
                        // Enroll ALL approved members in the selected households
                        $placeholders = implode(',', array_fill(0, count($cleanHhNos), '?'));
                        $memStmt = $db->prepare("
                            SELECT id FROM residents 
                            WHERE TRIM(household_no) IN ($placeholders) AND status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)
                        ");
                        $memStmt->execute($cleanHhNos);
                        $memIds = $memStmt->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($memIds as $mId) {
                            $enrolledResidentIds[(int)$mId] = true;
                        }
                    }
                }
            }

            // 2. Process Social Sector Auto-Enrollment (PWD, Solo Parent, Indigent, Senior, 4Ps, Student)
            $hasTargetSectors = ($targetPwd || $targetSolo || $targetIndigent || $targetSenior || $target4ps || $targetStudent);
            if ($autoEnroll || $hasTargetSectors) {
                $criteriaClauses = [];
                if ($targetPwd)      $criteriaClauses[] = "is_pwd = 1";
                if ($targetSolo)     $criteriaClauses[] = "is_solo_parent = 1";
                if ($targetIndigent) $criteriaClauses[] = "is_indigent = 1";
                if ($targetSenior)   $criteriaClauses[] = "is_senior = 1";
                if ($target4ps)      $criteriaClauses[] = "is_4ps = 1";
                if ($targetStudent)  $criteriaClauses[] = "is_student = 1";

                if (!empty($criteriaClauses)) {
                    $targetSql = "SELECT id FROM residents WHERE status = 'approved' AND (is_archived = 0 OR is_archived IS NULL)";
                    $targetParams = [];

                    if ($targetSitioId) {
                        $targetSql .= " AND sitio_id = ?";
                        $targetParams[] = $targetSitioId;
                    }

                    $targetSql .= " AND (" . implode(" OR ", $criteriaClauses) . ")";

                    $targetStmt = $db->prepare($targetSql);
                    $targetStmt->execute($targetParams);
                    $sectorResidents = $targetStmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($sectorResidents as $resId) {
                        $enrolledResidentIds[(int)$resId] = true;
                    }
                }
            }

            // Insert into assistance_beneficiaries
            $enrolledCount = 0;
            if (!empty($enrolledResidentIds)) {
                $stmtInsertBeneficiary = $db->prepare("
                    INSERT IGNORE INTO assistance_beneficiaries (program_id, resident_id, status, received_amount, received_items)
                    VALUES (?, ?, 'Enrolled', ?, ?)
                ");

                foreach (array_keys($enrolledResidentIds) as $resId) {
                    $stmtInsertBeneficiary->execute([$programId, $resId, $amount, $inKind]);
                    $enrolledCount++;
                }
            }

            $db->commit();

            log_activity('Launch Aid Program', "Program '{$title}' ({$programCode}) drafted and submitted for Punong Barangay approval with {$enrolledCount} pre-enrolled beneficiaries");
            set_flash('success', "Assistance Program <strong>{$title}</strong> has been created and submitted to <strong>Punong Barangay</strong> for official approval! <strong>{$enrolledCount}</strong> matching beneficiaries have been pre-enrolled.");
            redirect('social_services');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to create program: ' . $e->getMessage();
        }
    }
}

// Handled by index.php container
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <a href="<?= url('social_services') ?>" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Back to Programs</a>
        <h3 class="fw-bold text-dark mb-0">Launch Social Assistance / Ayuda Program</h3>
        <p class="text-muted small mb-0">Targeted Relief & Social Services Distribution for Barangay Lublub</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="" method="POST" class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    <div class="card-body p-4 p-md-5">

        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle-fill text-success me-2"></i>Program Information</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <label class="form-label fw-bold">Program Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. 2026 PWD & Senior Emergency Cash Subsidy" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                <select name="category" class="form-select form-select-lg" required>
                    <option value="">-- Select Category --</option>
                    <option value="Financial Assistance" <?= (($_POST['category'] ?? '') === 'Financial Assistance') ? 'selected' : '' ?>>Financial Assistance (Cash Aid)</option>
                    <option value="Food Assistance" <?= (($_POST['category'] ?? '') === 'Food Assistance') ? 'selected' : '' ?>>Food Assistance / Relief Packs</option>
                    <option value="PWD Welfare" <?= (($_POST['category'] ?? '') === 'PWD Welfare') ? 'selected' : '' ?>>PWD Welfare Subsidy</option>
                    <option value="Solo Parent Subsidy" <?= (($_POST['category'] ?? '') === 'Solo Parent Subsidy') ? 'selected' : '' ?>>Solo Parent / Mother Subsidy</option>
                    <option value="Senior Citizen Aid" <?= (($_POST['category'] ?? '') === 'Senior Citizen Aid') ? 'selected' : '' ?>>Senior Citizen Aid & Vitamins</option>
                    <option value="Medical/Health Aid" <?= (($_POST['category'] ?? '') === 'Medical/Health Aid') ? 'selected' : '' ?>>Medical & Health Assistance</option>
                    <option value="Calamity Relief" <?= (($_POST['category'] ?? '') === 'Calamity Relief') ? 'selected' : '' ?>>Calamity & Flood Recovery Aid</option>
                    <option value="Educational Assistance" <?= (($_POST['category'] ?? '') === 'Educational Assistance') ? 'selected' : '' ?>>Educational Assistance</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Description & Objective <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="3" placeholder="Provide background, guidelines, and qualifications for this assistance drive..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
        </div>

        <h5 class="fw-bold text-success mb-3"><i class="bi bi-gift-fill me-2"></i>Benefit & Schedule</h5>
        <div class="row g-3 mb-4 p-3 rounded-3 bg-light border">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Cash Amount (PHP)</label>
                <input type="number" step="0.01" name="amount_per_beneficiary" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($_POST['amount_per_beneficiary'] ?? '') ?>">
                <div class="form-text small">Set 0.00 if relief is purely goods.</div>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">In-Kind Relief Goods Details</label>
                <input type="text" name="in_kind_details" class="form-control" placeholder="e.g. 10kg Rice + Canned Goods + Vitamins" value="<?= htmlspecialchars($_POST['in_kind_details'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Distribution Date <span class="text-danger">*</span></label>
                <input type="date" name="distribution_date" class="form-control" value="<?= htmlspecialchars($_POST['distribution_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Fund Source</label>
                <input type="text" name="fund_source" class="form-control" placeholder="e.g. Barangay Lublub Calamity / GAD Fund" value="<?= htmlspecialchars($_POST['fund_source'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Target Sitio (Jurisdiction)</label>
                <select name="target_sitio_id" class="form-select">
                    <option value="">-- All 7 Sitios of Barangay Lublub --</option>
                    <?php foreach (SITIOS_LIST as $id => $name): ?>
                        <option value="<?= $id ?>" <?= (isset($_POST['target_sitio_id']) && $_POST['target_sitio_id'] == $id) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- AUTO-ENROLLMENT TARGETING SECTION -->
        <h5 class="fw-bold text-danger mb-2"><i class="bi bi-funnel-fill me-2"></i>Target Social Profiling Sectors (Auto-Enrollment)</h5>
        <p class="small text-muted mb-3">
            Check the beneficiary categories below to instantly target and auto-populate the masterlist from verified resident profiling records:
        </p>

        <div class="p-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="target_pwd" id="target_pwd" value="1" <?= isset($_POST['target_pwd']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-primary" for="target_pwd"><i class="bi bi-person-wheelchair me-1"></i> PWD Residents</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="target_solo_parent" id="target_solo_parent" value="1" <?= isset($_POST['target_solo_parent']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-danger" for="target_solo_parent"><i class="bi bi-person-heart me-1"></i> Solo Mothers / Parents</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="target_indigent" id="target_indigent" value="1" <?= isset($_POST['target_indigent']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-warning-emphasis" for="target_indigent"><i class="bi bi-wallet2 me-1"></i> Indigent Families</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="target_senior" id="target_senior" value="1" <?= isset($_POST['target_senior']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-info-emphasis" for="target_senior"><i class="bi bi-person-cane me-1"></i> Senior Citizens (60+)</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="target_4ps" id="target_4ps" value="1" <?= isset($_POST['target_4ps']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-success" for="target_4ps"><i class="bi bi-card-checklist me-1"></i> 4Ps Beneficiaries</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="target_student" id="target_student" value="1" <?= isset($_POST['target_student']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-primary" for="target_student"><i class="bi bi-mortarboard-fill me-1"></i> Students (Elementary / HS / College)</label>
                    </div>
                </div>
                <div class="col-md-12 border-top pt-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="auto_enroll" id="auto_enroll" value="1" <?= isset($_POST['auto_enroll']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-dark" for="auto_enroll"><i class="bi bi-magic me-1"></i> Auto-Populate Beneficiary Roster on Launch</label>
                        <div class="small text-muted ps-1">If enabled, residents matching the checked demographic sectors and/or selected households will be enrolled immediately.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PER-HOUSEHOLD & CALAMITY / FLOODED AREA SELECTION -->
        <div class="card border border-primary border-opacity-25 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-primary bg-opacity-10 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="target_by_household" id="target_by_household" value="1" onchange="toggleHouseholdTargeting(this)">
                    <label class="form-check-label fw-bold text-primary fs-6" for="target_by_household">
                        <i class="bi bi-houses-fill me-1"></i> Target Specific Households (Flooded Areas & Calamity Affected Dwellings)
                    </label>
                </div>
                <span class="badge bg-primary text-white fs-6 px-3 py-2" id="selectedHhBadge">0 Households Selected</span>
            </div>

            <div class="card-body p-4 d-none" id="householdTargetingContainer">
                <div class="alert alert-info py-2 px-3 rounded-3 small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Use this for localized calamity relief (e.g. flash floods, overflowing rivers, typhoons, fires) where only specific households in a Sitio or Purok are affected.
                </div>

                <!-- Household Enrollment Scope (Heads Only vs All Members) -->
                <div class="row g-3 mb-3 p-3 bg-light rounded-3 border">
                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Enrollment Mode for Selected Households:</label>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="household_enroll_scope" id="scope_heads" value="heads_only" checked>
                                <label class="form-check-label fw-bold text-dark" for="scope_heads">
                                    <i class="bi bi-person-badge text-primary me-1"></i> Household Heads Only (1 Relief Pack / Subsidy per Family)
                                </label>
                                <div class="small text-muted ps-4">Recommended for Food Packs, Grocery Relief, and Calamity Family Cash Aid.</div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="household_enroll_scope" id="scope_all" value="all_members">
                                <label class="form-check-label fw-bold text-dark" for="scope_all">
                                    <i class="bi bi-people-fill text-success me-1"></i> All Living Members in Selected Households
                                </label>
                                <div class="small text-muted ps-4">Enrolls every verified approved resident belonging to the selected dwellings.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controls: Quick Filter by Sitio, Search, Select All, Deselect All -->
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-geo-alt"></i></span>
                            <select id="hhSitioFilter" class="form-select" onchange="filterHouseholds()">
                                <option value="0">-- All Sitios (Filter Dwellings) --</option>
                                <?php foreach (SITIOS_LIST as $sId => $sName): ?>
                                    <option value="<?= $sId ?>">Sitio <?= $sId ?> - <?= htmlspecialchars($sName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="hhSearchInput" class="form-control" placeholder="Search HH No, Head Name, Street..." oninput="filterHouseholds()">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="selectAllVisibleHouseholds(true)">
                            <i class="bi bi-check-all me-1"></i> Select All Visible
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllVisibleHouseholds(false)">
                            <i class="bi bi-x-circle me-1"></i> Deselect All
                        </button>
                    </div>
                </div>

                <!-- Scrollable Household Grid with Checkboxes -->
                <div class="border rounded-3 p-3 bg-light overflow-auto" style="max-height: 400px;">
                    <div class="row g-2" id="householdListRow">
                        <?php if (empty($allHouseholds)): ?>
                            <div class="col-12 text-center py-4 text-muted">
                                <i class="bi bi-houses display-6 d-block mb-2 opacity-50"></i>
                                No active households registered yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($allHouseholds as $hh): ?>
                                <div class="col-md-6 col-lg-4 hh-item-card" 
                                     data-sitio-id="<?= $hh['sitio_id'] ?>" 
                                     data-text="<?= strtolower($hh['household_no'] . ' ' . ($hh['head_resident_name'] ?? '') . ' ' . $hh['address_line'] . ' ' . $hh['sitio_name']) ?>">
                                    <label class="card h-100 p-3 border shadow-none rounded-3 bg-white d-flex flex-row align-items-start gap-2 position-relative user-select-none" style="cursor: pointer;">
                                        <input type="checkbox" name="selected_households[]" value="<?= htmlspecialchars($hh['household_no']) ?>" class="form-check-input hh-checkbox mt-1" onchange="updateSelectedCount()">
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <span class="badge bg-dark bg-opacity-75 font-monospace text-white px-2" style="font-size: 0.72rem;"><?= htmlspecialchars($hh['household_no']) ?></span>
                                                <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 0.68rem;">Sitio <?= $hh['sitio_id'] ?></span>
                                            </div>
                                            <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($hh['head_resident_name'] ?: 'Head Not Listed') ?>">
                                                <?= !empty($hh['head_resident_name']) ? htmlspecialchars($hh['head_resident_name']) : '<span class="text-muted fst-italic">Head Not Listed</span>' ?>
                                            </div>
                                            <div class="small text-muted text-truncate" title="<?= htmlspecialchars($hh['address_line']) ?>" style="font-size: 0.75rem;">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($hh['address_line']) ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 mt-1" style="font-size: 0.7rem;">
                                                <span class="text-secondary"><i class="bi bi-people-fill text-primary me-1"></i><?= (int)$hh['member_count'] ?> member(s)</span>
                                                <span class="text-muted">&bull; <?= htmlspecialchars($hh['house_structure']) ?></span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div id="noHouseholdsMatch" class="text-center py-4 text-muted d-none">
                        <i class="bi bi-search fs-3 d-block mb-1 opacity-50"></i>
                        No households match the selected Sitio or search term.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 border-top pt-4">
            <a href="<?= url('social_services') ?>" class="btn btn-light border px-4">Cancel</a>
            <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow-sm">
                <i class="bi bi-rocket-takeoff me-2"></i> Launch & Initialize Distribution
            </button>
        </div>
    </div>
</form>

<script>
function toggleHouseholdTargeting(el) {
    const container = document.getElementById('householdTargetingContainer');
    if (container) {
        if (el.checked) {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }
}

function filterHouseholds() {
    const sitioFilter = document.getElementById('hhSitioFilter').value;
    const searchVal = document.getElementById('hhSearchInput').value.toLowerCase().trim();
    const items = document.querySelectorAll('.hh-item-card');
    let visibleCount = 0;

    items.forEach(item => {
        const itemSitio = item.getAttribute('data-sitio-id');
        const itemText = item.getAttribute('data-text') || '';

        const matchesSitio = (sitioFilter === '0' || itemSitio === sitioFilter);
        const matchesSearch = (searchVal === '' || itemText.includes(searchVal));

        if (matchesSitio && matchesSearch) {
            item.classList.remove('d-none');
            visibleCount++;
        } else {
            item.classList.add('d-none');
        }
    });

    const noMatch = document.getElementById('noHouseholdsMatch');
    if (noMatch) {
        if (visibleCount === 0 && items.length > 0) {
            noMatch.classList.remove('d-none');
        } else {
            noMatch.classList.add('d-none');
        }
    }
}

function selectAllVisibleHouseholds(selectBool) {
    const items = document.querySelectorAll('.hh-item-card:not(.d-none) .hh-checkbox');
    items.forEach(cb => {
        cb.checked = selectBool;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.hh-checkbox:checked').length;
    const badge = document.getElementById('selectedHhBadge');
    if (badge) {
        badge.textContent = checked + (checked === 1 ? ' Household Selected' : ' Households Selected');
    }
    const targetHhSwitch = document.getElementById('target_by_household');
    if (targetHhSwitch && checked > 0) {
        targetHhSwitch.checked = true;
    }
}

// Sync top Target Sitio with Household Sitio Filter
document.addEventListener('DOMContentLoaded', function() {
    const topSitioSelect = document.querySelector('select[name="target_sitio_id"]');
    const hhSitioFilter = document.getElementById('hhSitioFilter');

    if (topSitioSelect && hhSitioFilter) {
        topSitioSelect.addEventListener('change', function() {
            if (this.value) {
                hhSitioFilter.value = this.value;
                filterHouseholds();
            }
        });
    }
});
</script>

