<?php
// views/residents/add.php - Overhauled Modern Resident Profiling & Health Assessment Hub
require_once __DIR__ . '/../../config/app.php';

require_role('bhw');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;
$pageTitle = 'Register / Encode Resident Profile';

$errors = [];

// Pre-fill parameters if arriving from Household view or direct link
$prefillHouseholdNo = clean($_GET['household_no'] ?? '');
$prefillSitioId     = isset($_GET['sitio_id']) ? (int)$_GET['sitio_id'] : ($userSitioId ?? 0);
$prefillIsHead      = isset($_GET['is_head']) ? (int)$_GET['is_head'] : 0;

$prefillHeadName = '';
if (!empty($prefillHouseholdNo)) {
    try {
        $pStmt = $db->prepare("SELECT head_resident_name, address_line, sitio_id FROM households WHERE household_no = ?");
        $pStmt->execute([$prefillHouseholdNo]);
        $pHh = $pStmt->fetch(PDO::FETCH_ASSOC);
        if ($pHh) {
            $prefillHeadName = $pHh['head_resident_name'] ?? '';
            if (empty($_POST['address_line']) && !empty($pHh['address_line'])) {
                $_POST['address_line'] = $pHh['address_line'];
            }
            if (empty($prefillSitioId) && !empty($pHh['sitio_id'])) {
                $prefillSitioId = (int)$pHh['sitio_id'];
            }
        }
    } catch (Exception $e) {
        $prefillHeadName = '';
    }
}

// Fetch existing households for fast selection in Sitio
$householdsInSitio = [];
try {
    $hhSql = "SELECT id, household_no, head_resident_name, address_line, sitio_id FROM households WHERE 1=1";
    $hhParams = [];
    if ($role === 'bhw' && $userSitioId) {
        $hhSql .= " AND sitio_id = ?";
        $hhParams[] = $userSitioId;
    }
    $hhSql .= " ORDER BY id DESC LIMIT 200";
    $hhStmt = $db->prepare($hhSql);
    $hhStmt->execute($hhParams);
    $householdsInSitio = $hhStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $householdsInSitio = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName   = clean($_POST['first_name'] ?? '');
    $middleName  = clean($_POST['middle_name'] ?? '');
    $lastName    = clean($_POST['last_name'] ?? '');
    $suffix      = clean($_POST['suffix'] ?? '');
    $nickname    = clean($_POST['nickname'] ?? '');
    $gender      = clean($_POST['gender'] ?? '');
    $civilStatus = clean($_POST['civil_status'] ?? '');
    $birthDate   = clean($_POST['birth_date'] ?? '');
    $age         = (int)($_POST['age'] ?? 0);
    $contactNo   = clean($_POST['contact_no'] ?? '');
    $email       = clean($_POST['email'] ?? '');
    $sitioId     = ($role === 'bhw' && $userSitioId) ? $userSitioId : (int)($_POST['sitio_id'] ?? 0);
    $addressLine = clean($_POST['address_line'] ?? '');
    $householdNo = clean($_POST['household_no'] ?? '');
    if (empty($householdNo) || in_array(strtolower($householdNo), ['n/a', 'na', 'none', 'null', '0', '-'])) {
        $householdNo = null;
    }
    $familyRole  = clean($_POST['family_role'] ?? '');
    if ($familyRole === 'Other' && !empty($_POST['custom_family_role'])) {
        $familyRole = clean($_POST['custom_family_role']);
    }
    $isHead      = isset($_POST['is_head_of_family']) ? 1 : 0;
    if ($isHead) {
        $familyRole = 'Head';
    } elseif ($familyRole === 'Head') {
        $isHead = 1;
    }
    $familyCount = (int)($_POST['family_members_count'] ?? 1);
    $nextAction  = clean($_POST['next_action'] ?? 'view'); // 'view', 'add_another_member', 'list'

    // Social Welfare Flags
    $isPwd          = isset($_POST['is_pwd']) ? 1 : 0;
    $pwdType        = clean($_POST['pwd_type'] ?? '');
    $pwdIdNo        = clean($_POST['pwd_id_no'] ?? '');

    $isSoloParent   = isset($_POST['is_solo_parent']) ? 1 : 0;
    $soloParentIdNo = clean($_POST['solo_parent_id_no'] ?? '');
    $depChildren    = (int)($_POST['dependent_children_count'] ?? 0);

    $isIndigent     = isset($_POST['is_indigent']) ? 1 : 0;
    $monthlyIncome  = (float)($_POST['monthly_income'] ?? 0.00);
    $incomeSource   = clean($_POST['income_source'] ?? '');

    $isSenior       = ($age >= 60 || isset($_POST['is_senior'])) ? 1 : 0;
    $seniorIdNo     = clean($_POST['senior_id_no'] ?? '');

    $is4ps          = isset($_POST['is_4ps']) ? 1 : 0;
    $fourPsIdNo     = clean($_POST['four_ps_id_no'] ?? '');

    $isStudent      = isset($_POST['is_student']) ? 1 : 0;
    $schoolName     = clean($_POST['school_name'] ?? '');
    $studentIdNo    = clean($_POST['student_id_no'] ?? '');

    $isYouth        = ($age >= 15 && $age <= 30) ? 1 : (isset($_POST['is_youth']) ? 1 : 0);
    $isVoter        = isset($_POST['is_voter']) ? 1 : 0;
    $precinctNo     = clean($_POST['precinct_no'] ?? '');
    $empStatus      = clean($_POST['employment_status'] ?? '');
    $occupation     = clean($_POST['occupation'] ?? '');
    $education      = clean($_POST['education_level'] ?? '');

    // Health & Medical Profiling
    $bloodType         = clean($_POST['blood_type'] ?? '');
    $medicalConditions = clean($_POST['medical_conditions'] ?? '');
    $philhealthNo      = clean($_POST['philhealth_no'] ?? '');
    $emergName         = clean($_POST['emergency_contact_name'] ?? '');
    $emergNo           = clean($_POST['emergency_contact_no'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First Name and Last Name are required.';
    }
    if (empty($birthDate)) {
        $errors[] = 'Date of birth is required.';
    }
    if ($sitioId <= 0 || !isset(SITIOS_LIST[$sitioId])) {
        $errors[] = 'Please select a valid Sitio.';
    }

    if (empty($errors)) {
        try {
            // Process Identity & Welfare Attachments
            $photoPath        = upload_attachment('photo_file', 'residents', 'res_');
            $idCardPath       = upload_attachment('id_card_file', 'ids', 'id_');
            $housePhotoPath   = upload_attachment('house_photo_file', 'households', 'house_');
            $pwdIdPhoto       = $isPwd ? upload_attachment('pwd_id_file', 'documents', 'pwd_') : null;
            $seniorIdPhoto    = $isSenior ? upload_attachment('senior_id_file', 'documents', 'senior_') : null;
            $schoolIdPhoto    = $isStudent ? upload_attachment('school_id_file', 'documents', 'school_') : null;
            $soloParentPhoto  = $isSoloParent ? upload_attachment('solo_parent_id_file', 'documents', 'solo_') : null;
            $fourPsPhoto      = $is4ps ? upload_attachment('four_ps_id_file', 'documents', '4ps_') : null;
            $voterIdPhoto     = $isVoter ? upload_attachment('voter_id_file', 'documents', 'voter_') : null;

            // Generate unique resident code
            $residentCode = generate_resident_code($db);

            $stmt = $db->prepare("
                INSERT INTO residents (
                    resident_code, first_name, middle_name, last_name, suffix, nickname,
                    gender, civil_status, birth_date, age, contact_no, email, sitio_id, address_line,
                    household_no, is_head_of_family, family_role, family_members_count,
                    is_pwd, pwd_type, pwd_id_no, pwd_id_photo,
                    is_solo_parent, solo_parent_id_no, solo_parent_id_photo, dependent_children_count,
                    is_indigent, monthly_income, income_source,
                    is_senior, senior_id_no, senior_id_photo,
                    is_4ps, four_ps_id_no, four_ps_id_photo,
                    is_youth, is_student, school_name, student_id_no, school_id_photo,
                    is_voter, precinct_no, voter_id_photo,
                    employment_status, occupation, education_level,
                    blood_type, medical_conditions, philhealth_no,
                    emergency_contact_name, emergency_contact_no,
                    photo_path, id_card_path, house_photo_path,
                    status, registered_at, approved_at, approved_by_user_id
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?, ?,
                    'approved', NOW(), NOW(), ?
                )
            ");

            // Execute with retry loop in the rare event of concurrent duplicate
            $inserted = false;
            $retries = 3;
            while (!$inserted && $retries > 0) {
                try {
                    $stmt->execute([
                        $residentCode, $firstName, $middleName, $lastName, $suffix, $nickname,
                        $gender, $civilStatus, $birthDate, $age, $contactNo, $email, $sitioId, $addressLine,
                        $householdNo, $isHead, $familyRole, $familyCount,
                        $isPwd, $pwdType, $pwdIdNo, $pwdIdPhoto,
                        $isSoloParent, $soloParentIdNo, $soloParentPhoto, $depChildren,
                        $isIndigent, $monthlyIncome, $incomeSource,
                        $isSenior, $seniorIdNo, $seniorIdPhoto,
                        $is4ps, $fourPsIdNo, $fourPsPhoto,
                        $isYouth, $isStudent, $schoolName, $studentIdNo, $schoolIdPhoto,
                        $isVoter, $precinctNo, $voterIdPhoto,
                        $empStatus, $occupation, $education,
                        $bloodType, $medicalConditions, $philhealthNo,
                        $emergName, $emergNo,
                        $photoPath, $idCardPath, $housePhotoPath,
                        $user['id']
                    ]);
                    $inserted = true;
                } catch (PDOException $pe) {
                    if ($pe->getCode() == 23000 || strpos($pe->getMessage(), 'Duplicate entry') !== false) {
                        $residentCode = generate_resident_code($db);
                        $retries--;
                    } else {
                        throw $pe;
                    }
                }
            }

            $newResId = $db->lastInsertId();

            // Synchronize Household Head if marked as Head of Family
            if ($isHead && !empty($householdNo)) {
                $fullName = trim("$firstName $middleName $lastName $suffix");
                $syncHh = $db->prepare("UPDATE households SET head_resident_name = ? WHERE household_no = ?");
                $syncHh->execute([$fullName, $householdNo]);
            }

            log_activity('Encode Resident', "Resident {$firstName} {$lastName} ({$residentCode}) encoded and approved by {$user['full_name']}");
            set_flash('success', "Resident profile for <strong>{$firstName} {$lastName}</strong> ({$residentCode}) was registered successfully!");

            if ($nextAction === 'add_another_member' && !empty($householdNo)) {
                redirect(url('residents/add?household_no=' . urlencode($householdNo) . '&sitio_id=' . $sitioId));
            } elseif ($nextAction === 'list') {
                redirect(url('residents'));
            } else {
                redirect(url('residents/view?id=' . $newResId));
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to save resident record: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid px-0">
    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('residents') ?>">Residents</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Enroll Resident</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-gray-900">
                <i class="bi bi-person-vcard-fill text-success me-2"></i>Resident Socio-Economic & Health Profiling
            </h3>
            <p class="text-muted small mb-0">Record personal identity, household linkage, health & medical conditions, and welfare sector eligibility.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('residents') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Directory
            </a>
            <?php if (!empty($prefillHouseholdNo)): ?>
                <a href="<?= url('households/view?no=' . urlencode($prefillHouseholdNo)) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-houses me-1"></i> Back to Household <?= htmlspecialchars($prefillHouseholdNo) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Sitio Banner for BHW -->
    <?php if ($role === 'bhw' && $userSitioId): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center justify-content-between p-3" style="background: linear-gradient(90deg, #dcfce7 0%, #f0fdf4 100%);">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success text-white p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-heart-pulse-fill fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold text-success-emphasis">BHW RESIDENT ENCODING &bull; <?= htmlspecialchars(SITIOS_LIST[$userSitioId] ?? 'Assigned Sitio') ?></div>
                    <div class="small text-muted">Direct community entry. Residents encoded by BHW are pre-verified and approved.</div>
                </div>
            </div>
            <span class="badge bg-success px-3 py-2 fs-6">Sitio <?= $userSitioId ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm border-0 alert-dismissible fade show mb-4 rounded-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-octagon-fill fs-4 me-3 text-danger"></i>
                <div>
                    <strong>Please review the following errors:</strong>
                    <ul class="mb-0 mt-1 small">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" id="residentForm" class="needs-validation">
        <!-- 1. Sitio & Household Assignment -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-success">
                    <i class="bi bi-geo-alt-fill me-2"></i>1. Sitio & Household Linkage
                </h6>
                <span class="badge bg-light text-muted border">Dwelling Mapping</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Select Sitio <span class="text-danger">*</span></label>
                        <?php if ($role === 'bhw' && $userSitioId): ?>
                            <input type="text" class="form-control bg-light fw-semibold" readonly value="<?= htmlspecialchars(SITIOS_LIST[$userSitioId]) ?>">
                            <input type="hidden" name="sitio_id" id="sitioIdInput" value="<?= $userSitioId ?>">
                            <div class="form-text small text-success"><i class="bi bi-lock-fill me-1"></i>Locked to your assigned Sitio jurisdiction.</div>
                        <?php else: ?>
                            <select name="sitio_id" id="sitioIdInput" class="form-select border-success" required>
                                <option value="">-- Choose Sitio (1 of 7) --</option>
                                <?php foreach (SITIOS_LIST as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= (($prefillSitioId == $id) || (isset($_POST['sitio_id']) && $_POST['sitio_id'] == $id)) ? 'selected' : '' ?>>
                                        Sitio <?= $id ?> - <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Household / Family Link</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-houses"></i></span>
                            <input list="householdOptions" name="household_no" id="household_no_input" class="form-control font-monospace" placeholder="Pick or type Household No (e.g. HH-LUB-2026-0001)" value="<?= htmlspecialchars($_POST['household_no'] ?? $prefillHouseholdNo) ?>">
                            <datalist id="householdOptions">
                                <?php foreach ($householdsInSitio as $hOpt): ?>
                                    <option value="<?= htmlspecialchars($hOpt['household_no']) ?>" data-address="<?= htmlspecialchars($hOpt['address_line']) ?>" data-head="<?= htmlspecialchars($hOpt['head_resident_name'] ?: '') ?>">
                                        <?= htmlspecialchars($hOpt['household_no']) ?> &bull; <?= htmlspecialchars($hOpt['head_resident_name'] ?: 'No Head Name') ?> (<?= htmlspecialchars($hOpt['address_line']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-text small">Select from registered households in this Sitio to auto-fill address and detect assigned household head.</div>

                        <!-- Household Head Status Banner -->
                        <div id="householdHeadBanner" class="alert <?= !empty($prefillHeadName) ? 'alert-info' : 'alert-light border' ?> py-2 px-3 rounded-3 mt-2 small <?= empty($_POST['household_no'] ?? $prefillHouseholdNo) ? 'd-none' : '' ?>">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                                <div>
                                    <i class="bi bi-person-badge-fill text-primary me-1"></i>
                                    <strong>Assigned Household Head:</strong> 
                                    <span id="currentHeadName" class="fw-bold text-dark"><?= !empty($prefillHeadName) ? htmlspecialchars($prefillHeadName) : 'None Designated Yet' ?></span>
                                </div>
                                <small class="text-muted" id="headNoticeText">
                                    <?= !empty($prefillHeadName) ? 'Specify this resident\'s relationship to the head below.' : 'You can designate this resident as the household head.' ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Street / Purok / House No. <span class="text-danger">*</span></label>
                        <input type="text" name="address_line" id="address_line_input" class="form-control" placeholder="e.g. Purok 2, Near Chapel" value="<?= htmlspecialchars($_POST['address_line'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold" id="relationshipLabel">Relationship to Household Head</label>
                        <?php 
                            $curRole = $_POST['family_role'] ?? ($prefillIsHead ? 'Head' : '');
                            $knownRoles = ['Head','Spouse','Son','Daughter','Stepson','Stepdaughter','Father','Mother','Grandfather','Grandmother','Grandson','Granddaughter','Brother','Sister','Son-in-law','Daughter-in-law','Father-in-law','Mother-in-law','Uncle','Aunt','Nephew','Niece','Cousin','Relative','Domestic Helper','Boarder','Member'];
                            $isCustomRole = (!empty($curRole) && !in_array($curRole, $knownRoles));
                        ?>
                        <select name="family_role" id="family_role_select" class="form-select" onchange="handleRelationshipChange(this)">
                            <option value="">-- Select Relationship to Head --</option>
                            <optgroup label="Direct Family">
                                <option value="Head" <?= ($curRole === 'Head') ? 'selected' : '' ?>>Head (Designated Household Head)</option>
                                <option value="Spouse" <?= ($curRole === 'Spouse') ? 'selected' : '' ?>>Spouse (Husband / Wife)</option>
                                <option value="Son" <?= ($curRole === 'Son') ? 'selected' : '' ?>>Son</option>
                                <option value="Daughter" <?= ($curRole === 'Daughter') ? 'selected' : '' ?>>Daughter</option>
                                <option value="Stepson" <?= ($curRole === 'Stepson') ? 'selected' : '' ?>>Stepson</option>
                                <option value="Stepdaughter" <?= ($curRole === 'Stepdaughter') ? 'selected' : '' ?>>Stepdaughter</option>
                            </optgroup>
                            <optgroup label="Parents & Grandparents">
                                <option value="Father" <?= ($curRole === 'Father') ? 'selected' : '' ?>>Father</option>
                                <option value="Mother" <?= ($curRole === 'Mother') ? 'selected' : '' ?>>Mother</option>
                                <option value="Grandfather" <?= ($curRole === 'Grandfather') ? 'selected' : '' ?>>Grandfather</option>
                                <option value="Grandmother" <?= ($curRole === 'Grandmother') ? 'selected' : '' ?>>Grandmother</option>
                            </optgroup>
                            <optgroup label="Grandchildren">
                                <option value="Grandson" <?= ($curRole === 'Grandson') ? 'selected' : '' ?>>Grandson</option>
                                <option value="Granddaughter" <?= ($curRole === 'Granddaughter') ? 'selected' : '' ?>>Granddaughter</option>
                            </optgroup>
                            <optgroup label="Siblings & In-Laws">
                                <option value="Brother" <?= ($curRole === 'Brother') ? 'selected' : '' ?>>Brother</option>
                                <option value="Sister" <?= ($curRole === 'Sister') ? 'selected' : '' ?>>Sister</option>
                                <option value="Son-in-law" <?= ($curRole === 'Son-in-law') ? 'selected' : '' ?>>Son-in-law</option>
                                <option value="Daughter-in-law" <?= ($curRole === 'Daughter-in-law') ? 'selected' : '' ?>>Daughter-in-law</option>
                                <option value="Father-in-law" <?= ($curRole === 'Father-in-law') ? 'selected' : '' ?>>Father-in-law</option>
                                <option value="Mother-in-law" <?= ($curRole === 'Mother-in-law') ? 'selected' : '' ?>>Mother-in-law</option>
                            </optgroup>
                            <optgroup label="Other Relatives & Extended Family">
                                <option value="Uncle" <?= ($curRole === 'Uncle') ? 'selected' : '' ?>>Uncle</option>
                                <option value="Aunt" <?= ($curRole === 'Aunt') ? 'selected' : '' ?>>Aunt</option>
                                <option value="Nephew" <?= ($curRole === 'Nephew') ? 'selected' : '' ?>>Nephew</option>
                                <option value="Niece" <?= ($curRole === 'Niece') ? 'selected' : '' ?>>Niece</option>
                                <option value="Cousin" <?= ($curRole === 'Cousin') ? 'selected' : '' ?>>Cousin</option>
                                <option value="Relative" <?= ($curRole === 'Relative') ? 'selected' : '' ?>>Other Relative</option>
                            </optgroup>
                            <optgroup label="Non-Relatives / Other">
                                <option value="Domestic Helper" <?= ($curRole === 'Domestic Helper') ? 'selected' : '' ?>>Domestic Helper / Housekeeper</option>
                                <option value="Boarder" <?= ($curRole === 'Boarder') ? 'selected' : '' ?>>Boarder / Non-Relative</option>
                                <option value="Member" <?= ($curRole === 'Member') ? 'selected' : '' ?>>General Household Member</option>
                                <option value="Other" <?= $isCustomRole ? 'selected' : '' ?>>Other (Specify)</option>
                            </optgroup>
                        </select>
                        <div class="mt-2 <?= $isCustomRole ? '' : 'd-none' ?>" id="custom_relationship_group">
                            <input type="text" name="custom_family_role" id="custom_family_role" class="form-control form-control-sm" placeholder="Specify custom relationship..." value="<?= $isCustomRole ? htmlspecialchars($curRole) : '' ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" name="is_head_of_family" id="is_head_of_family" value="1" <?= (isset($_POST['is_head_of_family']) || ($prefillIsHead && !isset($_POST['first_name']))) ? 'checked' : '' ?> onchange="handleHeadCheckboxChange(this)">
                            <label class="form-check-label fw-bold text-dark" for="is_head_of_family">
                                <i class="bi bi-star-fill text-warning me-1"></i> Designated Household Head
                            </label>
                            <div class="small text-muted" id="headCheckboxHint">Official family head for profiling & aid allocation.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Personal Information & Demographics -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-success">
                    <i class="bi bi-person-lines-fill me-2"></i>2. Personal Demographics & Contact
                </h6>
                <span class="badge bg-light text-muted border">Civil Registry</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Suffix</label>
                        <input type="text" name="suffix" class="form-control" placeholder="Jr., Sr., III" value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Computed Age</label>
                        <div class="input-group">
                            <input type="number" name="age" id="computed_age" class="form-control bg-light fw-bold text-center" readonly placeholder="0" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
                            <span class="input-group-text bg-light small">yrs</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">-- Select Gender --</option>
                            <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Civil Status</label>
                        <select name="civil_status" class="form-select">
                            <option value="">-- Select Civil Status --</option>
                            <option value="Single" <?= (($_POST['civil_status'] ?? '') === 'Single') ? 'selected' : '' ?>>Single</option>
                            <option value="Married" <?= (($_POST['civil_status'] ?? '') === 'Married') ? 'selected' : '' ?>>Married</option>
                            <option value="Solo Parent" <?= (($_POST['civil_status'] ?? '') === 'Solo Parent') ? 'selected' : '' ?>>Solo Parent</option>
                            <option value="Widowed" <?= (($_POST['civil_status'] ?? '') === 'Widowed') ? 'selected' : '' ?>>Widowed</option>
                            <option value="Separated" <?= (($_POST['civil_status'] ?? '') === 'Separated') ? 'selected' : '' ?>>Separated</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mobile / Phone Number</label>
                        <input type="text" name="contact_no" class="form-control" placeholder="09XX XXX XXXX" value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email Address (Optional)</label>
                        <input type="email" name="email" class="form-control" placeholder="resident@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Alias / Nickname</label>
                        <input type="text" name="nickname" class="form-control" placeholder="Known alias" value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Social Welfare Profiling (Targeting Sector Aid) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-danger">
                    <i class="bi bi-shield-heart-fill me-2"></i>3. Social Welfare & Vulnerability Eligibility
                </h6>
                <span class="badge bg-danger bg-opacity-10 text-danger">Social Services Target</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- PWD -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 border-primary-subtle bg-light rounded-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_pwd" id="is_pwd" value="1" <?= isset($_POST['is_pwd']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-primary" for="is_pwd">
                                    <i class="bi bi-person-wheelchair me-1"></i> PWD (Person with Disability)
                                </label>
                            </div>
                            <div id="pwd_details_container" class="mt-2 <?= !isset($_POST['is_pwd']) ? 'd-none' : '' ?>">
                                <input type="text" name="pwd_type" class="form-control form-control-sm mb-2" placeholder="Disability Type (e.g. Visual, Orthopedic)" value="<?= htmlspecialchars($_POST['pwd_type'] ?? '') ?>">
                                <input type="text" name="pwd_id_no" class="form-control form-control-sm mb-2" placeholder="PWD ID Card No." value="<?= htmlspecialchars($_POST['pwd_id_no'] ?? '') ?>">
                                <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Attach PWD ID Document</label>
                                <input type="file" name="pwd_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>

                    <!-- Solo Parent -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 border-danger-subtle bg-light rounded-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_solo_parent" id="is_solo_parent" value="1" <?= isset($_POST['is_solo_parent']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-danger" for="is_solo_parent">
                                    <i class="bi bi-person-heart me-1"></i> Solo Parent / Mother
                                </label>
                            </div>
                            <div id="solo_parent_details_container" class="mt-2 <?= !isset($_POST['is_solo_parent']) ? 'd-none' : '' ?>">
                                <input type="number" name="dependent_children_count" class="form-control form-control-sm mb-2" placeholder="No. of Dependent Children" value="<?= htmlspecialchars($_POST['dependent_children_count'] ?? '') ?>">
                                <input type="text" name="solo_parent_id_no" class="form-control form-control-sm mb-2" placeholder="Solo Parent ID No." value="<?= htmlspecialchars($_POST['solo_parent_id_no'] ?? '') ?>">
                                <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Attach Solo Parent ID</label>
                                <input type="file" name="solo_parent_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>

                    <!-- Indigent -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 border-warning-subtle bg-light rounded-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_indigent" id="is_indigent" value="1" <?= isset($_POST['is_indigent']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-warning-emphasis" for="is_indigent">
                                    <i class="bi bi-wallet2 me-1"></i> Indigent Low-Income
                                </label>
                            </div>
                            <div id="indigent_details_container" class="mt-2 <?= !isset($_POST['is_indigent']) ? 'd-none' : '' ?>">
                                <input type="number" step="0.01" name="monthly_income" class="form-control form-control-sm mb-2" placeholder="Estimated Monthly Income (PHP)" value="<?= htmlspecialchars($_POST['monthly_income'] ?? '') ?>">
                                <input type="text" name="income_source" class="form-control form-control-sm" placeholder="Livelihood (e.g. Farming, Labor)" value="<?= htmlspecialchars($_POST['income_source'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Senior Citizen -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 border-info-subtle bg-light rounded-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_senior" id="is_senior" value="1" <?= isset($_POST['is_senior']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-info-emphasis" for="is_senior">
                                    <i class="bi bi-person-cane me-1"></i> Senior Citizen (60+ yrs)
                                </label>
                            </div>
                            <div id="senior_details_container" class="mt-2 <?= !isset($_POST['is_senior']) ? 'd-none' : '' ?>">
                                <input type="text" name="senior_id_no" class="form-control form-control-sm mb-2" placeholder="OSCA ID No." value="<?= htmlspecialchars($_POST['senior_id_no'] ?? '') ?>">
                                <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Attach OSCA ID</label>
                                <input type="file" name="senior_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>

                    <!-- 4Ps Beneficiary -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 border-success-subtle bg-light rounded-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_4ps" id="is_4ps" value="1" <?= isset($_POST['is_4ps']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-success" for="is_4ps">
                                    <i class="bi bi-card-checklist me-1"></i> 4Ps Beneficiary (DSWD)
                                </label>
                            </div>
                            <div id="four_ps_details_container" class="mt-2 <?= !isset($_POST['is_4ps']) ? 'd-none' : '' ?>">
                                <input type="text" name="four_ps_id_no" class="form-control form-control-sm mb-2" placeholder="4Ps Household ID" value="<?= htmlspecialchars($_POST['four_ps_id_no'] ?? '') ?>">
                                <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Attach 4Ps ID Card</label>
                                <input type="file" name="four_ps_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>

                    <!-- Student / Learner -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 border-info-subtle bg-light rounded-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_student" id="is_student" value="1" <?= isset($_POST['is_student']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-info-emphasis" for="is_student">
                                    <i class="bi bi-mortarboard-fill me-1"></i> Student / Learner
                                </label>
                            </div>
                            <div id="student_details_container" class="mt-2 <?= !isset($_POST['is_student']) ? 'd-none' : '' ?>">
                                <input type="text" name="school_name" class="form-control form-control-sm mb-2" placeholder="School / University Name" value="<?= htmlspecialchars($_POST['school_name'] ?? '') ?>">
                                <input type="text" name="student_id_no" class="form-control form-control-sm mb-2" placeholder="Student ID / LRN No." value="<?= htmlspecialchars($_POST['student_id_no'] ?? '') ?>">
                                <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Attach School ID / Slip</label>
                                <input type="file" name="school_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>

                    <!-- Registered Voter -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 border-secondary-subtle bg-light rounded-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_voter" id="is_voter" value="1" <?= isset($_POST['is_voter']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-secondary" for="is_voter">
                                    <i class="bi bi-check2-square me-1"></i> Registered Voter (Lublub)
                                </label>
                            </div>
                            <div id="voter_details_container" class="mt-2 <?= !isset($_POST['is_voter']) ? 'd-none' : '' ?>">
                                <input type="text" name="precinct_no" class="form-control form-control-sm mb-2" placeholder="Precinct Number" value="<?= htmlspecialchars($_POST['precinct_no'] ?? '') ?>">
                                <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Attach Voter's ID / Stub</label>
                                <input type="file" name="voter_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>

                    <!-- Employment -->
                    <div class="col-md-8">
                        <div class="card p-3 h-100 bg-light rounded-4 border">
                            <label class="form-label fw-bold text-dark small mb-2"><i class="bi bi-briefcase-fill me-1 text-primary"></i>Employment & Education Level</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <select name="employment_status" class="form-select form-select-sm">
                                        <option value="">-- Select Employment --</option>
                                        <option value="Employed" <?= (($_POST['employment_status'] ?? '') === 'Employed') ? 'selected' : '' ?>>Employed</option>
                                        <option value="Self-Employed" <?= (($_POST['employment_status'] ?? '') === 'Self-Employed') ? 'selected' : '' ?>>Self-Employed</option>
                                        <option value="Unemployed" <?= (($_POST['employment_status'] ?? '') === 'Unemployed') ? 'selected' : '' ?>>Unemployed</option>
                                        <option value="Student" <?= (($_POST['employment_status'] ?? '') === 'Student') ? 'selected' : '' ?>>Student</option>
                                        <option value="Retired" <?= (($_POST['employment_status'] ?? '') === 'Retired') ? 'selected' : '' ?>>Retired</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="occupation" class="form-control form-control-sm" placeholder="Occupation / Skill" value="<?= htmlspecialchars($_POST['occupation'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="education_level" class="form-control form-control-sm" placeholder="Attained Education" value="<?= htmlspecialchars($_POST['education_level'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Health & Medical Profiling (BHW Core Function) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-info-emphasis">
                    <i class="bi bi-heart-pulse-fill me-2 text-danger"></i>4. Health, Medical & Nutrition Profiling (BHW Domain)
                </h6>
                <span class="badge bg-danger bg-opacity-10 text-danger">Community Health Record</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Blood Type</label>
                        <select name="blood_type" class="form-select">
                            <option value="">-- Unknown / Not Tested --</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">PhilHealth Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-file-medical"></i></span>
                            <input type="text" name="philhealth_no" class="form-control font-monospace" placeholder="12-digit PhilHealth ID" value="<?= htmlspecialchars($_POST['philhealth_no'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Emergency Contact Person</label>
                        <input type="text" name="emergency_contact_name" class="form-control" placeholder="Relative Name" value="<?= htmlspecialchars($_POST['emergency_contact_name'] ?? '') ?>">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Chronic Illnesses & Special Medical Conditions</label>
                        <input type="text" name="medical_conditions" id="medConditionsInput" class="form-control" placeholder="e.g. Hypertension, Type 2 Diabetes, Asthma, Bedridden" value="<?= htmlspecialchars($_POST['medical_conditions'] ?? '') ?>">
                        <!-- Fast Health Condition Badges -->
                        <div class="mt-2">
                            <span class="small text-muted me-1">Fast Add:</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 mb-1 med-badge" data-val="Hypertension">Hypertension</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 mb-1 med-badge" data-val="Diabetes">Diabetes</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 mb-1 med-badge" data-val="Asthma">Asthma</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 mb-1 med-badge" data-val="Tuberculosis">Tuberculosis</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 mb-1 med-badge" data-val="Bedridden">Bedridden</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 mb-1 med-badge" data-val="Pregnant / Lactating">Pregnant</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 mb-1 med-badge" data-val="Malnutrition Risk">Malnutrition</button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Emergency Contact Number</label>
                        <input type="text" name="emergency_contact_no" class="form-control" placeholder="09XX XXX XXXX" value="<?= htmlspecialchars($_POST['emergency_contact_no'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Resident Photo, Valid ID & Dwelling Attachments -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-card-image me-2"></i>5. Photographic Verification & Government ID Attachments
                </h6>
                <span class="badge bg-primary bg-opacity-10 text-primary">Identity Validation</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted"><i class="bi bi-person-bounding-box me-1"></i>Resident 2x2 / Portrait Photo</label>
                        <input type="file" name="photo_file" class="form-control form-control-sm mb-2" accept="image/*" capture="user">
                        <div class="form-text small">Recent clear face photo (JPG, PNG).</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted"><i class="bi bi-person-vcard me-1"></i>Primary Government Valid ID</label>
                        <input type="file" name="id_card_file" class="form-control form-control-sm mb-2" accept="image/*,.pdf">
                        <div class="form-text small">Barangay ID, PhilHealth, Postal, Passport, etc.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted"><i class="bi bi-house me-1"></i>Dwelling Exterior Photo</label>
                        <input type="file" name="house_photo_file" class="form-control form-control-sm mb-2" accept="image/*" capture="environment">
                        <div class="form-text small">Exterior picture of the household dwelling.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit & Post-Save Actions -->
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="small fw-bold text-muted">After saving:</span>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="radio" name="next_action" id="nextView" value="view" checked>
                        <label class="form-check-label small" for="nextView">View Profile Dossier</label>
                    </div>
                    <?php if (!empty($prefillHouseholdNo)): ?>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="next_action" id="nextAddMember" value="add_another_member">
                            <label class="form-check-label small" for="nextAddMember">Enroll Next Member into Household</label>
                        </div>
                    <?php endif; ?>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="radio" name="next_action" id="nextList" value="list">
                        <label class="form-check-label small" for="nextList">Return to Directory</label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= url('residents') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow-sm">
                        <i class="bi bi-save me-2"></i> Save & Enroll Resident
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic Age Calculation & Senior/Youth Auto-tagging
    const birthDateInput = document.getElementById('birth_date');
    const ageInput = document.getElementById('computed_age');
    const seniorSwitch = document.getElementById('is_senior');
    const seniorBox = document.getElementById('senior_details_container');

    function calculateAge() {
        if (!birthDateInput.value) return;
        const bDate = new Date(birthDateInput.value);
        const today = new Date();
        let age = today.getFullYear() - bDate.getFullYear();
        const m = today.getMonth() - bDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < bDate.getDate())) {
            age--;
        }
        if (age >= 0) {
            ageInput.value = age;
            if (age >= 60) {
                if (seniorSwitch && !seniorSwitch.checked) {
                    seniorSwitch.checked = true;
                    if (seniorBox) seniorBox.classList.remove('d-none');
                }
            }
        }
    }

    if (birthDateInput) {
        birthDateInput.addEventListener('change', calculateAge);
        if (birthDateInput.value) calculateAge();
    }

    // Toggle container helper
    function setupSwitchToggle(switchId, containerId) {
        const sw = document.getElementById(switchId);
        const box = document.getElementById(containerId);
        if (sw && box) {
            sw.addEventListener('change', function() {
                if (this.checked) {
                    box.classList.remove('d-none');
                } else {
                    box.classList.add('d-none');
                }
            });
        }
    }

    setupSwitchToggle('is_pwd', 'pwd_details_container');
    setupSwitchToggle('is_solo_parent', 'solo_parent_details_container');
    setupSwitchToggle('is_senior', 'senior_details_container');
    setupSwitchToggle('is_4ps', 'four_ps_details_container');
    setupSwitchToggle('is_student', 'student_details_container');
    setupSwitchToggle('is_voter', 'voter_details_container');

    // Auto-fill address and detect Household Head from Household selector
    const hhInput = document.getElementById('household_no_input');
    const addrInput = document.getElementById('address_line_input');
    const headBanner = document.getElementById('householdHeadBanner');
    const headNameSpan = document.getElementById('currentHeadName');
    const headNoticeText = document.getElementById('headNoticeText');
    const relationshipLabel = document.getElementById('relationshipLabel');

    function updateHouseholdHeadInfo() {
        if (!hhInput) return;
        const val = hhInput.value.trim();
        if (!val) {
            if (headBanner) headBanner.classList.add('d-none');
            if (relationshipLabel) relationshipLabel.textContent = 'Relationship to Household Head';
            return;
        }

        const option = document.querySelector(`#householdOptions option[value="${val}"]`);
        if (headBanner) headBanner.classList.remove('d-none');

        if (option) {
            const addr = option.getAttribute('data-address');
            const head = option.getAttribute('data-head');

            if (addr && addrInput && !addrInput.value) {
                addrInput.value = addr;
            }

            if (head && head.trim().length > 0) {
                if (headNameSpan) headNameSpan.textContent = head;
                if (headNoticeText) headNoticeText.textContent = `Specify this resident's relationship to ${head}.`;
                if (relationshipLabel) relationshipLabel.textContent = `Relationship to Household Head (${head})`;
            } else {
                if (headNameSpan) headNameSpan.textContent = 'None Designated Yet';
                if (headNoticeText) headNoticeText.textContent = 'You can designate this resident as the Household Head.';
                if (relationshipLabel) relationshipLabel.textContent = 'Relationship to Household Head';
            }
        } else {
            if (headNameSpan) headNameSpan.textContent = 'Custom / Unsaved Household';
            if (headNoticeText) headNoticeText.textContent = 'Designate as Head or select relationship.';
            if (relationshipLabel) relationshipLabel.textContent = 'Relationship to Household Head';
        }
    }

    if (hhInput) {
        hhInput.addEventListener('input', updateHouseholdHeadInfo);
        hhInput.addEventListener('change', updateHouseholdHeadInfo);
        if (hhInput.value) updateHouseholdHeadInfo();
    }

    // Quick add medical tags
    const medInput = document.getElementById('medConditionsInput');
    document.querySelectorAll('.med-badge').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.getAttribute('data-val');
            if (medInput) {
                if (medInput.value.trim().length > 0) {
                    if (!medInput.value.includes(val)) {
                        medInput.value += ', ' + val;
                    }
                } else {
                    medInput.value = val;
                }
                medInput.focus();
            }
        });
    });
});

function handleRelationshipChange(sel) {
    const customGroup = document.getElementById('custom_relationship_group');
    const customInput = document.getElementById('custom_family_role');
    const headCheckbox = document.getElementById('is_head_of_family');

    if (sel.value === 'Other') {
        if (customGroup) customGroup.classList.remove('d-none');
        if (customInput) customInput.focus();
    } else {
        if (customGroup) customGroup.classList.add('d-none');
        if (customInput) customInput.value = '';
    }

    if (headCheckbox) {
        if (sel.value === 'Head') {
            headCheckbox.checked = true;
        } else {
            headCheckbox.checked = false;
        }
    }
}

function handleHeadCheckboxChange(cb) {
    const roleSelect = document.getElementById('family_role_select');
    const customGroup = document.getElementById('custom_relationship_group');
    const customInput = document.getElementById('custom_family_role');

    if (cb.checked) {
        if (roleSelect) roleSelect.value = 'Head';
        if (customGroup) customGroup.classList.add('d-none');
        if (customInput) customInput.value = '';
    } else {
        if (roleSelect && roleSelect.value === 'Head') {
            roleSelect.value = '';
        }
    }
}
</script>
