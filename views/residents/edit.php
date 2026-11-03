<?php
// residents/edit - Update Resident Profiling Information
require_once __DIR__ . '/../../config/app.php';

require_role('bhw');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid resident ID.');
    redirect('residents');
}

$stmt = $db->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->execute([$id]);
$resident = $stmt->fetch();

if (!$resident) {
    set_flash('danger', 'Resident record not found.');
    redirect('residents');
}

// BHW restriction: only edit residents in their own Sitio
if ($role === 'bhw' && $userSitioId && $resident['sitio_id'] != $userSitioId) {
    set_flash('danger', 'You can only edit residents in your assigned Sitio.');
    redirect('residents');
}

$pageTitle = 'Edit Resident: ' . $resident['first_name'] . ' ' . $resident['last_name'];
$errors = [];

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

$currentHeadName = '';
if (!empty($resident['household_no'])) {
    try {
        $chStmt = $db->prepare("SELECT head_resident_name FROM households WHERE household_no = ?");
        $chStmt->execute([$resident['household_no']]);
        $currentHeadName = $chStmt->fetchColumn() ?: '';
    } catch (Exception $e) {
        $currentHeadName = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName   = clean($_POST['first_name'] ?? '');
    $middleName  = clean($_POST['middle_name'] ?? '');
    $lastName    = clean($_POST['last_name'] ?? '');
    $suffix      = clean($_POST['suffix'] ?? '');
    $nickname    = clean($_POST['nickname'] ?? '');
    $gender      = clean($_POST['gender'] ?? '');
    $civilStatus = clean($_POST['civil_status'] ?? 'Single');
    $birthDate   = clean($_POST['birth_date'] ?? '');
    $age         = (int)($_POST['age'] ?? 0);
    $contactNo   = clean($_POST['contact_no'] ?? '');
    $email       = clean($_POST['email'] ?? '');
    $sitioId     = ($role === 'bhw' && $userSitioId) ? $userSitioId : (int)($_POST['sitio_id'] ?? $resident['sitio_id']);
    $addressLine = clean($_POST['address_line'] ?? '');
    $householdNo = clean($_POST['household_no'] ?? '');
    if (empty($householdNo) || in_array(strtolower($householdNo), ['n/a', 'na', 'none', 'null', '0', '-'])) {
        $householdNo = null;
    }
    $familyRole  = clean($_POST['family_role'] ?? 'Member');
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
    $empStatus      = clean($_POST['employment_status'] ?? 'Unemployed');
    $occupation     = clean($_POST['occupation'] ?? '');
    $education      = clean($_POST['education_level'] ?? '');

    $bloodType         = clean($_POST['blood_type'] ?? '');
    $medicalConditions = clean($_POST['medical_conditions'] ?? '');
    $philhealthNo      = clean($_POST['philhealth_no'] ?? '');
    $emergName         = clean($_POST['emergency_contact_name'] ?? '');
    $emergNo           = clean($_POST['emergency_contact_no'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First Name and Last Name are required.';
    }

    if (empty($errors)) {
        try {
            // Process Identity & Welfare Attachments (keep existing if no new file uploaded)
            $photoPath       = upload_attachment('photo_file', 'residents', 'res_') ?? $resident['photo_path'];
            $idCardPath      = upload_attachment('id_card_file', 'ids', 'id_') ?? $resident['id_card_path'];
            $housePhotoPath  = upload_attachment('house_photo_file', 'households', 'house_') ?? $resident['house_photo_path'];
            $pwdIdPhoto      = upload_attachment('pwd_id_file', 'documents', 'pwd_') ?? $resident['pwd_id_photo'];
            $seniorIdPhoto   = upload_attachment('senior_id_file', 'documents', 'senior_') ?? $resident['senior_id_photo'];
            $schoolIdPhoto   = upload_attachment('school_id_file', 'documents', 'school_') ?? $resident['school_id_photo'];
            $soloParentPhoto = upload_attachment('solo_parent_id_file', 'documents', 'solo_') ?? $resident['solo_parent_id_photo'];
            $fourPsPhoto     = upload_attachment('four_ps_id_file', 'documents', '4ps_') ?? $resident['four_ps_id_photo'];
            $voterIdPhoto    = upload_attachment('voter_id_file', 'documents', 'voter_') ?? $resident['voter_id_photo'];

            $updateStmt = $db->prepare("
                UPDATE residents SET
                    first_name = ?, middle_name = ?, last_name = ?, suffix = ?, nickname = ?,
                    gender = ?, civil_status = ?, birth_date = ?, age = ?, contact_no = ?, email = ?,
                    sitio_id = ?, address_line = ?, household_no = ?, is_head_of_family = ?, family_role = ?, family_members_count = ?,
                    is_pwd = ?, pwd_type = ?, pwd_id_no = ?, pwd_id_photo = ?,
                    is_solo_parent = ?, solo_parent_id_no = ?, solo_parent_id_photo = ?, dependent_children_count = ?,
                    is_indigent = ?, monthly_income = ?, income_source = ?,
                    is_senior = ?, senior_id_no = ?, senior_id_photo = ?,
                    is_4ps = ?, four_ps_id_no = ?, four_ps_id_photo = ?,
                    is_youth = ?, is_student = ?, school_name = ?, student_id_no = ?, school_id_photo = ?,
                    is_voter = ?, precinct_no = ?, voter_id_photo = ?,
                    employment_status = ?, occupation = ?, education_level = ?,
                    blood_type = ?, medical_conditions = ?, philhealth_no = ?,
                    emergency_contact_name = ?, emergency_contact_no = ?,
                    photo_path = ?, id_card_path = ?, house_photo_path = ?
                WHERE id = ?
            ");

            $updateStmt->execute([
                $firstName, $middleName, $lastName, $suffix, $nickname,
                $gender, $civilStatus, $birthDate, $age, $contactNo, $email,
                $sitioId, $addressLine, $householdNo, $isHead, $familyRole, $familyCount,
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
                $id
            ]);

            // Synchronize Household Head if marked as Head of Family
            if ($isHead && !empty($householdNo)) {
                $fullName = trim("$firstName $middleName $lastName $suffix");
                $syncHh = $db->prepare("UPDATE households SET head_resident_name = ? WHERE household_no = ?");
                $syncHh->execute([$fullName, $householdNo]);
            }

            log_activity('Update Resident', "Updated resident profile {$firstName} {$lastName} by {$user['full_name']}");
            set_flash('success', "Resident profile for <strong>{$firstName} {$lastName}</strong> has been updated.");
            redirect('residents/view?id=' . $id);
        } catch (Exception $e) {
            $errors[] = 'Failed to update resident: ' . $e->getMessage();
        }
    }
}

// Handled by index.php container
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <a href="<?= url('residents/view?id=' . $resident['id']) ?>" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Back to Profile</a>
        <h3 class="fw-bold text-dark mb-0">Edit Resident Profile</h3>
        <p class="text-muted small mb-0">Updating profile of <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> (<?= htmlspecialchars($resident['resident_code']) ?>)</p>
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

<form action="" method="POST" enctype="multipart/form-data" class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    <div class="card-body p-4 p-md-5">

        <h5 class="fw-bold text-success mb-3"><i class="bi bi-geo-alt-fill me-1"></i>Sitio & Address</h5>
        <div class="row g-3 mb-4 p-3 rounded-3 bg-light border border-success border-opacity-25">
            <div class="col-md-6">
                <label class="form-label fw-bold">Sitio</label>
                <?php if ($role === 'bhw' && $userSitioId): ?>
                    <input type="text" class="form-control bg-white" readonly value="<?= htmlspecialchars(SITIOS_LIST[$userSitioId]) ?>">
                    <input type="hidden" name="sitio_id" value="<?= $userSitioId ?>">
                <?php else: ?>
                    <select name="sitio_id" class="form-select border-success" required>
                        <?php foreach (SITIOS_LIST as $sId => $sName): ?>
                            <option value="<?= $sId ?>" <?= ($resident['sitio_id'] == $sId) ? 'selected' : '' ?>><?= htmlspecialchars($sName) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Street / Purok / House No. <span class="text-danger">*</span></label>
                <input type="text" name="address_line" class="form-control" value="<?= htmlspecialchars($resident['address_line']) ?>" required>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">Household / Family No.</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-houses"></i></span>
                    <input list="householdOptions" name="household_no" id="household_no_input" class="form-control font-monospace" placeholder="Pick or type Household No" value="<?= htmlspecialchars($resident['household_no'] ?? '') ?>">
                    <datalist id="householdOptions">
                        <?php foreach ($householdsInSitio as $hOpt): ?>
                            <option value="<?= htmlspecialchars($hOpt['household_no']) ?>" data-address="<?= htmlspecialchars($hOpt['address_line']) ?>" data-head="<?= htmlspecialchars($hOpt['head_resident_name'] ?: '') ?>">
                                <?= htmlspecialchars($hOpt['household_no']) ?> &bull; <?= htmlspecialchars($hOpt['head_resident_name'] ?: 'No Head Name') ?> (<?= htmlspecialchars($hOpt['address_line']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <!-- Household Head Status Banner -->
                <div id="householdHeadBanner" class="alert <?= !empty($currentHeadName) ? 'alert-info' : 'alert-light border' ?> py-2 px-3 rounded-3 mt-2 small <?= empty($resident['household_no']) ? 'd-none' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                        <div>
                            <i class="bi bi-person-badge-fill text-primary me-1"></i>
                            <strong>Assigned Head:</strong> 
                            <span id="currentHeadName" class="fw-bold text-dark"><?= !empty($currentHeadName) ? htmlspecialchars($currentHeadName) : 'None Designated Yet' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold" id="relationshipLabel">Relationship to Household Head</label>
                <?php 
                    $curRole = $resident['family_role'] ?? 'Member';
                    $knownRoles = ['Head','Spouse','Son','Daughter','Stepson','Stepdaughter','Father','Mother','Grandfather','Grandmother','Grandson','Granddaughter','Brother','Sister','Son-in-law','Daughter-in-law','Father-in-law','Mother-in-law','Uncle','Aunt','Nephew','Niece','Cousin','Relative','Domestic Helper','Boarder','Member'];
                    $isCustomRole = (!empty($curRole) && !in_array($curRole, $knownRoles));
                ?>
                <select name="family_role" id="family_role_select" class="form-select" onchange="handleRelationshipChange(this)">
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
                    <input class="form-check-input" type="checkbox" name="is_head_of_family" id="is_head_of_family" value="1" <?= ($resident['is_head_of_family'] || $curRole === 'Head') ? 'checked' : '' ?> onchange="handleHeadCheckboxChange(this)">
                    <label class="form-check-label fw-bold text-dark" for="is_head_of_family">
                        <i class="bi bi-star-fill text-warning me-1"></i> Designated Head
                    </label>
                    <div class="small text-muted">Official family head.</div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-lines-fill me-1"></i>Personal Details</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($resident['first_name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Middle Name</label>
                <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($resident['middle_name'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($resident['last_name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Suffix</label>
                <input type="text" name="suffix" class="form-control" value="<?= htmlspecialchars($resident['suffix'] ?? '') ?>">
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Birthdate</label>
                <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= htmlspecialchars($resident['birth_date']) ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Age</label>
                <input type="number" name="age" id="age" class="form-control bg-light" readonly value="<?= $resident['age'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Gender</label>
                <select name="gender" class="form-select">
                    <option value="Male" <?= ($resident['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($resident['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= ($resident['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Civil Status</label>
                <select name="civil_status" class="form-select">
                    <option value="Single" <?= ($resident['civil_status'] === 'Single') ? 'selected' : '' ?>>Single</option>
                    <option value="Married" <?= ($resident['civil_status'] === 'Married') ? 'selected' : '' ?>>Married</option>
                    <option value="Solo Parent" <?= ($resident['civil_status'] === 'Solo Parent') ? 'selected' : '' ?>>Solo Parent / Mother</option>
                    <option value="Widowed" <?= ($resident['civil_status'] === 'Widowed') ? 'selected' : '' ?>>Widowed</option>
                    <option value="Separated" <?= ($resident['civil_status'] === 'Separated') ? 'selected' : '' ?>>Separated</option>
                </select>
            </div>
        </div>

        <h5 class="fw-bold text-danger mb-3"><i class="bi bi-shield-heart-fill me-1"></i>Social Welfare Profiling</h5>
        <div class="row g-3 mb-4">
            <!-- PWD -->
            <div class="col-md-4">
                <div class="card p-3 h-100 bg-light border-primary-subtle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_pwd" id="is_pwd" value="1" <?= $resident['is_pwd'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-primary" for="is_pwd">PWD (Disability)</label>
                    </div>
                    <div id="pwd_details_container" class="mt-2 <?= $resident['is_pwd'] ? '' : 'd-none' ?>">
                        <input type="text" name="pwd_type" class="form-control form-control-sm mb-2" placeholder="Disability Type" value="<?= htmlspecialchars($resident['pwd_type'] ?? '') ?>">
                        <input type="text" name="pwd_id_no" class="form-control form-control-sm mb-2" placeholder="PWD ID No." value="<?= htmlspecialchars($resident['pwd_id_no'] ?? '') ?>">
                        <div class="mt-2">
                            <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>PWD ID Attachment</label>
                            <?php if (!empty($resident['pwd_id_photo']) && file_exists(BASE_PATH . '/' . $resident['pwd_id_photo'])): ?>
                                <div class="mb-1">
                                    <button type="button" class="btn btn-xs btn-outline-primary preview-attachment-btn py-0 px-2" data-preview-url="<?= url($resident['pwd_id_photo']) ?>" data-preview-title="PWD ID Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; PWD ID: <?= htmlspecialchars($resident['pwd_id_no'] ?: 'On File') ?>" style="font-size: 0.72rem;">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Preview PWD ID
                                    </button>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="pwd_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Solo Parent -->
            <div class="col-md-4">
                <div class="card p-3 h-100 bg-light border-danger-subtle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_solo_parent" id="is_solo_parent" value="1" <?= $resident['is_solo_parent'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-danger" for="is_solo_parent">Solo Mother / Parent</label>
                    </div>
                    <div id="solo_parent_details_container" class="mt-2 <?= $resident['is_solo_parent'] ? '' : 'd-none' ?>">
                        <input type="number" name="dependent_children_count" class="form-control form-control-sm mb-2" placeholder="No. of Children" value="<?= $resident['dependent_children_count'] ?>">
                        <input type="text" name="solo_parent_id_no" class="form-control form-control-sm mb-2" placeholder="Solo Parent ID No." value="<?= htmlspecialchars($resident['solo_parent_id_no'] ?? '') ?>">
                        <div class="mt-2">
                            <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Solo Parent ID Attachment</label>
                            <?php if (!empty($resident['solo_parent_id_photo']) && file_exists(BASE_PATH . '/' . $resident['solo_parent_id_photo'])): ?>
                                <div class="mb-1">
                                    <button type="button" class="btn btn-xs btn-outline-danger preview-attachment-btn py-0 px-2" data-preview-url="<?= url($resident['solo_parent_id_photo']) ?>" data-preview-title="Solo Parent ID Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" style="font-size: 0.72rem;">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Preview Solo Parent ID
                                    </button>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="solo_parent_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Indigent -->
            <div class="col-md-4">
                <div class="card p-3 h-100 bg-light border-warning-subtle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_indigent" id="is_indigent" value="1" <?= $resident['is_indigent'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-warning-emphasis" for="is_indigent">Indigent Household</label>
                    </div>
                    <div class="mt-2">
                        <input type="number" step="0.01" name="monthly_income" class="form-control form-control-sm mb-2" placeholder="Income (PHP)" value="<?= $resident['monthly_income'] ?>">
                        <input type="text" name="income_source" class="form-control form-control-sm" placeholder="Income Source" value="<?= htmlspecialchars($resident['income_source'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Senior -->
            <div class="col-md-4">
                <div class="card p-3 h-100 bg-light border-info-subtle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_senior" id="is_senior" value="1" <?= $resident['is_senior'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-info-emphasis" for="is_senior">Senior Citizen (60+)</label>
                    </div>
                    <div id="senior_details_container" class="mt-2 <?= $resident['is_senior'] ? '' : 'd-none' ?>">
                        <input type="text" name="senior_id_no" class="form-control form-control-sm mb-2" placeholder="OSCA ID No." value="<?= htmlspecialchars($resident['senior_id_no'] ?? '') ?>">
                        <div class="mt-2">
                            <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Senior Citizen / OSCA ID</label>
                            <?php if (!empty($resident['senior_id_photo']) && file_exists(BASE_PATH . '/' . $resident['senior_id_photo'])): ?>
                                <div class="mb-1">
                                    <button type="button" class="btn btn-xs btn-outline-info preview-attachment-btn py-0 px-2" data-preview-url="<?= url($resident['senior_id_photo']) ?>" data-preview-title="Senior Citizen / OSCA ID Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; OSCA ID: <?= htmlspecialchars($resident['senior_id_no'] ?: 'On File') ?>" style="font-size: 0.72rem;">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Preview OSCA ID
                                    </button>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="senior_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4Ps -->
            <div class="col-md-4">
                <div class="card p-3 h-100 bg-light border-success-subtle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_4ps" id="is_4ps" value="1" <?= $resident['is_4ps'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-success" for="is_4ps">4Ps Beneficiary</label>
                    </div>
                    <div id="four_ps_details_container" class="mt-2 <?= $resident['is_4ps'] ? '' : 'd-none' ?>">
                        <input type="text" name="four_ps_id_no" class="form-control form-control-sm mb-2" placeholder="4Ps Household ID" value="<?= htmlspecialchars($resident['four_ps_id_no'] ?? '') ?>">
                        <div class="mt-2">
                            <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>4Ps ID Attachment</label>
                            <?php if (!empty($resident['four_ps_id_photo']) && file_exists(BASE_PATH . '/' . $resident['four_ps_id_photo'])): ?>
                                <div class="mb-1">
                                    <button type="button" class="btn btn-xs btn-outline-success preview-attachment-btn py-0 px-2" data-preview-url="<?= url($resident['four_ps_id_photo']) ?>" data-preview-title="4Ps ID Card Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" style="font-size: 0.72rem;">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Preview 4Ps ID
                                    </button>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="four_ps_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student -->
            <div class="col-md-4">
                <div class="card p-3 h-100 bg-light border-info border-opacity-50">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_student" id="is_student" value="1" <?= !empty($resident['is_student']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-info-emphasis" for="is_student">Student / Learner</label>
                    </div>
                    <div id="student_details_container" class="mt-2 <?= !empty($resident['is_student']) ? '' : 'd-none' ?>">
                        <input type="text" name="student_id_no" class="form-control form-control-sm mb-2" placeholder="Student ID / LRN No." value="<?= htmlspecialchars($resident['student_id_no'] ?? '') ?>">
                        <input type="text" name="school_name" class="form-control form-control-sm mb-2" placeholder="School Name" value="<?= htmlspecialchars($resident['school_name'] ?? '') ?>">
                        <div class="mt-2">
                            <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>School ID / Enrollment Slip</label>
                            <?php if (!empty($resident['school_id_photo']) && file_exists(BASE_PATH . '/' . $resident['school_id_photo'])): ?>
                                <div class="mb-1">
                                    <button type="button" class="btn btn-xs btn-outline-info preview-attachment-btn py-0 px-2" data-preview-url="<?= url($resident['school_id_photo']) ?>" data-preview-title="Student School ID Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?> &bull; School: <?= htmlspecialchars($resident['school_name'] ?: 'Enrolled') ?>" style="font-size: 0.72rem;">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Preview School ID
                                    </button>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="school_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Voter -->
            <div class="col-md-4">
                <div class="card p-3 h-100 bg-light border-secondary-subtle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_voter" id="is_voter" value="1" <?= $resident['is_voter'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-secondary" for="is_voter">Registered Voter</label>
                    </div>
                    <div class="mt-2">
                        <input type="text" name="precinct_no" class="form-control form-control-sm mb-2" placeholder="Precinct No." value="<?= htmlspecialchars($resident['precinct_no'] ?? '') ?>">
                        <div class="mt-2">
                            <label class="small text-muted fw-semibold mb-1"><i class="bi bi-paperclip me-1"></i>Voter's ID Attachment</label>
                            <?php if (!empty($resident['voter_id_photo']) && file_exists(BASE_PATH . '/' . $resident['voter_id_photo'])): ?>
                                <div class="mb-1">
                                    <button type="button" class="btn btn-xs btn-outline-secondary preview-attachment-btn py-0 px-2" data-preview-url="<?= url($resident['voter_id_photo']) ?>" data-preview-title="Voter's ID Attachment" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" style="font-size: 0.72rem;">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Preview Voter's ID
                                    </button>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="voter_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Health & Medical Profiling -->
        <h5 class="fw-bold text-danger d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-heart-pulse-fill"></i> 4. Health, Medical & Nutrition Profiling (BHW Record)
        </h5>
        <div class="row g-3 mb-4 p-3 rounded-3 bg-light border">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Blood Type</label>
                <select name="blood_type" class="form-select">
                    <option value="">-- Unknown --</option>
                    <?php foreach (['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'] as $bt): ?>
                        <option value="<?= $bt ?>" <?= ($resident['blood_type'] === $bt) ? 'selected' : '' ?>><?= $bt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">PhilHealth Identification No.</label>
                <input type="text" name="philhealth_no" class="form-control font-monospace" placeholder="12-digit PhilHealth ID" value="<?= htmlspecialchars($resident['philhealth_no'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Emergency Contact Person</label>
                <input type="text" name="emergency_contact_name" class="form-control" placeholder="Contact Person Name" value="<?= htmlspecialchars($resident['emergency_contact_name'] ?? '') ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-semibold">Chronic Illnesses & Medical Conditions</label>
                <input type="text" name="medical_conditions" class="form-control" placeholder="e.g. Hypertension, Diabetes, Asthma, Bedridden" value="<?= htmlspecialchars($resident['medical_conditions'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Emergency Contact Number</label>
                <input type="text" name="emergency_contact_no" class="form-control" placeholder="09XX XXX XXXX" value="<?= htmlspecialchars($resident['emergency_contact_no'] ?? '') ?>">
            </div>
        </div>

        <!-- 5. Resident Photo, Valid ID & Dwelling Attachments -->
        <h5 class="fw-bold text-primary d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-card-image"></i> 5. Resident Photo, Valid ID & Dwelling Attachments
        </h5>
        <div class="row g-3 mb-4 p-3 rounded-3 bg-light border">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted"><i class="bi bi-person-bounding-box me-1"></i>Profile Photo</label>
                <?php if (!empty($resident['photo_path']) && file_exists(BASE_PATH . '/' . $resident['photo_path'])): ?>
                    <div class="mb-2">
                        <a href="<?= asset($resident['photo_path']) ?>" class="preview-attachment-btn d-inline-block" data-preview-title="Profile Photo" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" title="Click to view full photo">
                            <img src="<?= asset($resident['photo_path']) ?>" alt="Photo" class="rounded border shadow-sm" style="max-height: 80px; object-fit: cover; cursor: pointer;">
                        </a>
                    </div>
                <?php endif; ?>
                <input type="file" name="photo_file" class="form-control form-control-sm" accept="image/*">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted"><i class="bi bi-person-vcard me-1"></i>Primary Government Valid ID</label>
                <?php if (!empty($resident['id_card_path']) && file_exists(BASE_PATH . '/' . $resident['id_card_path'])): ?>
                    <div class="mb-2">
                        <button type="button" class="btn btn-xs btn-outline-primary preview-attachment-btn py-0 px-2" data-preview-url="<?= asset($resident['id_card_path']) ?>" data-preview-title="Government Valid ID" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" style="font-size: 0.75rem;">
                            <i class="bi bi-arrows-fullscreen me-1"></i> Preview Valid ID
                        </button>
                    </div>
                <?php endif; ?>
                <input type="file" name="id_card_file" class="form-control form-control-sm" accept="image/*,.pdf">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted"><i class="bi bi-house me-1"></i>Dwelling Exterior Photo</label>
                <?php if (!empty($resident['house_photo_path']) && file_exists(BASE_PATH . '/' . $resident['house_photo_path'])): ?>
                    <div class="mb-2">
                        <a href="<?= asset($resident['house_photo_path']) ?>" class="preview-attachment-btn d-inline-block" data-preview-title="Dwelling Exterior Photo" data-preview-subtitle="<?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?>" title="Click to view house photo">
                            <img src="<?= asset($resident['house_photo_path']) ?>" alt="House Photo" class="rounded border shadow-sm" style="max-height: 80px; object-fit: cover; cursor: pointer;">
                        </a>
                    </div>
                <?php endif; ?>
                <input type="file" name="house_photo_file" class="form-control form-control-sm" accept="image/*">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 border-top pt-4">
            <a href="<?= url('residents/view?id=' . $resident['id']) ?>" class="btn btn-light border px-4">Cancel</a>
            <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow-sm">
                <i class="bi bi-check2-circle me-2"></i> Update Resident Profile
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    // Household head auto-detection
    const hhInput = document.getElementById('household_no_input');
    const headBanner = document.getElementById('householdHeadBanner');
    const headNameSpan = document.getElementById('currentHeadName');
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
            const head = option.getAttribute('data-head');
            if (head && head.trim().length > 0) {
                if (headNameSpan) headNameSpan.textContent = head;
                if (relationshipLabel) relationshipLabel.textContent = `Relationship to Household Head (${head})`;
            } else {
                if (headNameSpan) headNameSpan.textContent = 'None Designated Yet';
                if (relationshipLabel) relationshipLabel.textContent = 'Relationship to Household Head';
            }
        }
    }

    if (hhInput) {
        hhInput.addEventListener('input', updateHouseholdHeadInfo);
        hhInput.addEventListener('change', updateHouseholdHeadInfo);
    }
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
            roleSelect.value = 'Member';
        }
    }
}
</script>

