<?php
// auth/signup - Step-by-Step Resident Registration with Live Selfie & ID Attachment
require_once __DIR__ . '/../../config/app.php';

$db = getDB();
$errors = [];
$success = null;

// Ensure upload folders exist
$residentsUploadDir = dirname(__DIR__, 2) . '/uploads/residents';
$idsUploadDir       = dirname(__DIR__, 2) . '/uploads/ids';
if (!is_dir($residentsUploadDir)) @mkdir($residentsUploadDir, 0777, true);
if (!is_dir($idsUploadDir)) @mkdir($idsUploadDir, 0777, true);

// Fetch Sitios
$sitiosQuery = $db->query("SELECT id, name, code FROM sitios ORDER BY id ASC");
$sitios = $sitiosQuery->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sitio & Address
    $sitioId     = (int)($_POST['sitio_id'] ?? 0);
    $addressLine = clean($_POST['address_line'] ?? '');
    $householdNo = clean($_POST['household_no'] ?? '');
    // Allow blank or N/A; treat as unassigned for profiling
    if (empty($householdNo) || in_array(strtolower($householdNo), ['n/a', 'na', 'none', 'null', '0', '-', 'unknown', 'wla', 'wala'])) {
        $householdNo = null;
    }
    $familyRole  = clean($_POST['family_role'] ?? 'Member');
    $isHead      = isset($_POST['is_head_of_family']) ? 1 : 0;
    $familyCount = (int)($_POST['family_members_count'] ?? 1);

    // 2. Personal Info
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

    // 3. Social Welfare Flags
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
    if ($isStudent && $empStatus === 'Unemployed') {
        $empStatus = 'Student';
    }
    $occupation     = clean($_POST['occupation'] ?? '');
    $education      = clean($_POST['education_level'] ?? '');

    // Health
    $bloodType         = clean($_POST['blood_type'] ?? '');
    $medicalConditions = clean($_POST['medical_conditions'] ?? '');
    $philhealthNo      = clean($_POST['philhealth_no'] ?? '');
    $emergName         = clean($_POST['emergency_contact_name'] ?? '');
    $emergNo           = clean($_POST['emergency_contact_no'] ?? '');

    // 4. Account Credentials
    $username        = clean($_POST['username'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validations
    if ($sitioId <= 0 || !isset(SITIOS_LIST[$sitioId])) {
        $errors[] = 'Please select a valid Sitio in Barangay Lublub.';
    }
    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First Name and Last Name are required.';
    }
    if (empty($birthDate)) {
        $errors[] = 'Date of birth is required.';
    }
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }

    // Password Uniqueness & Security Validation
    if (empty($password) || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long for account uniqueness and security.';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain a combination of both letters and numbers.';
        }
        if (!empty($username) && stripos($password, $username) !== false) {
            $errors[] = 'Password cannot contain your username.';
        }
        if (!empty($firstName) && strlen($firstName) >= 3 && stripos($password, $firstName) !== false) {
            $errors[] = 'Password cannot contain your first name.';
        }
        if (!empty($lastName) && strlen($lastName) >= 3 && stripos($password, $lastName) !== false) {
            $errors[] = 'Password cannot contain your last name.';
        }
        $weakList = ['password', 'password123', '12345678', '123456789', 'admin123', 'qwerty123', 'barangay123', 'lublub123', 'pass1234', 'iloveyou'];
        if (in_array(strtolower($password), $weakList)) {
            $errors[] = 'Password is too common and predictable. Please choose a unique password.';
        }
    }

    // Step 3: Required ID & profiling verification for each selected category
    if ($isPwd) {
        if (empty($pwdIdNo)) {
            $errors[] = 'PWD ID Number is required when Person with Disability (PWD) is selected.';
        }
        if (empty($pwdType)) {
            $errors[] = 'Disability Type is required when Person with Disability (PWD) is selected.';
        }
    }
    if ($isStudent) {
        if (empty($studentIdNo)) {
            $errors[] = 'Student ID Number or LRN is required when Student is selected.';
        }
        if (empty($schoolName)) {
            $errors[] = 'School Name is required when Student is selected.';
        }
    }
    if ($isSenior && empty($seniorIdNo)) {
        $errors[] = 'Senior Citizen / OSCA ID Number is required when Senior Citizen is selected.';
    }
    if ($isSoloParent && empty($soloParentIdNo)) {
        $errors[] = 'Solo Parent ID Number is required when Solo Parent is selected.';
    }
    if ($isIndigent && empty($incomeSource)) {
        $errors[] = 'Main Livelihood / Income Source is required when Indigent Family is selected.';
    }
    if ($is4ps && empty($fourPsIdNo)) {
        $errors[] = '4Ps Household ID Number is required when DSWD 4Ps Member is selected.';
    }
    if ($isVoter && empty($precinctNo)) {
        $errors[] = 'Precinct Number is required when Registered Voter is selected.';
    }

    // Check username/email uniqueness
    if (empty($errors)) {
        $checkUser = $db->prepare("SELECT id FROM users WHERE username = ?");
        $checkUser->execute([$username]);
        if ($checkUser->fetch()) {
            $errors[] = 'Username is already taken. Please choose another.';
        }

        if (!empty($email)) {
            $checkEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->execute([$email]);
            if ($checkEmail->fetch()) {
                $errors[] = 'Email address is already registered.';
            }
        }
    }

    // 5. Process Photo / Realtime Selfie
    $savedPhotoPath = null;
    $selfieBase64 = $_POST['selfie_data'] ?? '';

    if (!empty($selfieBase64) && strpos($selfieBase64, 'data:image') === 0) {
        // Realtime camera snapshot captured via canvas
        $dataParts = explode(',', $selfieBase64);
        $decoded = base64_decode(end($dataParts));
        if ($decoded) {
            $filename = 'selfie_' . uniqid() . '_' . time() . '.jpg';
            $targetFile = $residentsUploadDir . '/' . $filename;
            if (file_put_contents($targetFile, $decoded)) {
                $savedPhotoPath = 'uploads/residents/' . $filename;
            }
        }
    } elseif (!empty($_FILES['selfie_file']['name']) && $_FILES['selfie_file']['error'] === UPLOAD_ERR_OK) {
        // Uploaded file fallback
        $ext = strtolower(pathinfo($_FILES['selfie_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $filename = 'selfie_' . uniqid() . '_' . time() . '.' . $ext;
            $targetFile = $residentsUploadDir . '/' . $filename;
            if (move_uploaded_file($_FILES['selfie_file']['tmp_name'], $targetFile)) {
                $savedPhotoPath = 'uploads/residents/' . $filename;
            }
        }
    }

    // 6. Process Valid ID Attachment
    $savedIdPath = null;
    if (!empty($_FILES['id_file']['name']) && $_FILES['id_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['id_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'])) {
            $filename = 'id_' . uniqid() . '_' . time() . '.' . $ext;
            $targetFile = $idsUploadDir . '/' . $filename;
            if (move_uploaded_file($_FILES['id_file']['tmp_name'], $targetFile)) {
                $savedIdPath = 'uploads/ids/' . $filename;
            }
        } else {
            $errors[] = 'Invalid ID file format. Allowed formats: JPG, PNG, WEBP, or PDF.';
        }
    }

    // 7. Process Optional House / Dwelling Photo Dropzone
    $savedHousePhotoPath = null;
    if (!empty($_FILES['house_photo']['name']) && $_FILES['house_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['house_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $houseUploadDir = __DIR__ . '/../../uploads/households';
            if (!is_dir($houseUploadDir)) {
                mkdir($houseUploadDir, 0777, true);
            }
            $filename = 'house_' . uniqid() . '_' . time() . '.' . $ext;
            $targetFile = $houseUploadDir . '/' . $filename;
            if (move_uploaded_file($_FILES['house_photo']['tmp_name'], $targetFile)) {
                $savedHousePhotoPath = 'uploads/households/' . $filename;
            }
        }
    }

    // 8. Process Categorical Welfare ID Attachments
    $savedPwdIdPath        = $isPwd ? upload_attachment('pwd_id_file', 'documents', 'pwd_id_') : null;
    $savedSeniorIdPath     = $isSenior ? upload_attachment('senior_id_file', 'documents', 'senior_id_') : null;
    $savedSchoolIdPath     = $isStudent ? upload_attachment('school_id_file', 'documents', 'school_id_') : null;
    $savedSoloParentIdPath = $isSoloParent ? upload_attachment('solo_parent_id_file', 'documents', 'solo_parent_id_') : null;
    $savedFourPsIdPath     = $is4ps ? upload_attachment('four_ps_id_file', 'documents', '4ps_id_') : null;
    $savedVoterIdPath      = $isVoter ? upload_attachment('voter_id_file', 'documents', 'voter_id_') : null;

    // Process Database Insert
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $fullName = trim("{$firstName} {$middleName} {$lastName} {$suffix}");
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Insert User
            $stmtUser = $db->prepare("
                INSERT INTO users (username, email, password_hash, role, full_name, contact_number, status, assigned_sitio_id)
                VALUES (?, ?, ?, 'resident', ?, ?, 'pending', ?)
            ");
            $stmtUser->execute([$username, $email ?: $username . '@lublub.portal', $passwordHash, $fullName, $contactNo, $sitioId]);
            $userId = (int)$db->lastInsertId();

            // Generate guaranteed unique Resident Code: LUB-YYYY-XXXX
            $randCode = generate_resident_code($db);

            // Insert Resident Profile
            $stmtRes = $db->prepare("
                INSERT INTO residents (
                    user_id, resident_code, first_name, middle_name, last_name, suffix, nickname,
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
                    status, registered_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
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
                    'pending', NOW()
                )
            ");

            $inserted = false;
            $retries = 3;
            while (!$inserted && $retries > 0) {
                try {
                    $stmtRes->execute([
                        $userId, $randCode, $firstName, $middleName, $lastName, $suffix, $nickname,
                        $gender, $civilStatus, $birthDate, $age, $contactNo, $email, $sitioId, $addressLine,
                        $householdNo, $isHead, $familyRole, $familyCount,
                        $isPwd, $pwdType, $pwdIdNo, $savedPwdIdPath,
                        $isSoloParent, $soloParentIdNo, $savedSoloParentIdPath, $depChildren,
                        $isIndigent, $monthlyIncome, $incomeSource,
                        $isSenior, $seniorIdNo, $savedSeniorIdPath,
                        $is4ps, $fourPsIdNo, $savedFourPsIdPath,
                        $isYouth, $isStudent, $schoolName, $studentIdNo, $savedSchoolIdPath,
                        $isVoter, $precinctNo, $savedVoterIdPath,
                        $empStatus, $occupation, $education,
                        $bloodType, $medicalConditions, $philhealthNo,
                        $emergName, $emergNo,
                        $savedPhotoPath, $savedIdPath, $savedHousePhotoPath
                    ]);
                    $inserted = true;
                } catch (PDOException $pe) {
                    if ($pe->getCode() == 23000 || strpos($pe->getMessage(), 'Duplicate entry') !== false) {
                        $randCode = generate_resident_code($db);
                        $retries--;
                    } else {
                        throw $pe;
                    }
                }
            }

            $newResId = (int)$db->lastInsertId();
            // Centralized Approval Registration (Order 1: Barangay Secretary)
            create_approval_request(
                'resident_signup',
                $newResId,
                "Resident Registration: {$fullName}",
                $userId,
                false,
                "Online resident self-registration awaiting Barangay Secretary verification"
            );

            $db->commit();

            log_activity('Resident Registration', "Resident {$fullName} submitted registration for Sitio " . SITIOS_LIST[$sitioId]);

            $success = [
                'name'    => $fullName,
                'sitio'   => SITIOS_LIST[$sitioId],
                'code'    => $randCode
            ];
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to submit registration: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Online Registration | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        .step-progress {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 30px;
        }
        .step-progress::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 3px;
            background: #e2e8f0;
            transform: translateY(-50%);
            z-index: 1;
        }
        .step-item {
            position: relative;
            z-index: 2;
            text-align: center;
            background: #fff;
            padding: 0 8px;
        }
        .step-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 6px;
            border: 2px solid #cbd5e1;
            transition: all 0.3s;
        }
        .step-item.active .step-circle {
            background: #0d6338;
            color: #fff;
            border-color: #0d6338;
            box-shadow: 0 0 0 4px rgba(13, 99, 56, 0.2);
        }
        .step-item.completed .step-circle {
            background: #10b981;
            color: #fff;
            border-color: #10b981;
        }
        .step-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
        }
        .step-item.active .step-label {
            color: #0d6338;
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .camera-container {
            max-width: 420px;
            margin: 0 auto;
            border: 2px dashed #0d6338;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px;
            text-align: center;
            position: relative;
        }
        #webcamVideo, #selfieCanvas {
            width: 100%;
            max-width: 400px;
            height: 280px;
            object-fit: cover;
            border-radius: 8px;
            background: #111;
        }
    </style>
</head>
<body class="bg-light">

<!-- Top Brand Bar -->
<div class="bg-dark text-white py-2 px-3 border-bottom border-success border-3">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="<?= url() ?>" class="text-white text-decoration-none d-flex align-items-center gap-2">
            <span class="badge bg-success rounded-circle p-2">BL</span>
            <div>
                <strong class="d-block lh-1"><?= BRGY_NAME ?></strong>
                <small class="text-secondary" style="font-size: 0.72rem;"><?= BRGY_SUBTITLE ?></small>
            </div>
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white-50 small d-none d-md-inline">Already verified?</span>
            <a href="<?= url('auth/login') ?>" class="btn btn-sm btn-outline-success">
                <i class="bi bi-box-arrow-in-right me-1"></i> Official / Resident Login
            </a>
        </div>
    </div>
</div>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <?php if ($success): ?>
                <!-- Registration Success Banner -->
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4">
                    <div class="card-body p-4 p-md-5 text-center bg-white">
                        <div class="display-3 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
                        <h2 class="fw-bold text-dark">Registration Submitted Successfully!</h2>
                        <p class="lead text-muted mb-4">Welcome, <strong><?= htmlspecialchars($success['name']) ?></strong>.</p>

                        <div class="card border border-warning bg-warning bg-opacity-10 text-start p-4 mx-auto mb-4" style="max-width: 650px;">
                            <div class="d-flex gap-3">
                                <i class="bi bi-hourglass-split text-warning-emphasis fs-2"></i>
                                <div>
                                    <h5 class="fw-bold text-warning-emphasis mb-1">Awaiting Barangay Secretary Verification</h5>
                                    <p class="mb-2 text-dark">
                                        Your registration and uploaded identity documents under <strong><?= htmlspecialchars($success['sitio']) ?></strong> have been submitted to the Barangay Secretary for verification.
                                    </p>
                                    <div class="p-2 rounded bg-white border d-inline-block fw-semibold text-success mb-2">
                                        <i class="bi bi-person-badge me-1"></i> Office of the Barangay Secretary
                                    </div>
                                    <p class="small text-muted mb-0">
                                        The Barangay Secretary will review your live selfie snapshot, valid ID, and residency address. Once approved, you can log in to access your digital resident ID and apply for social services.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <a href="<?= url('auth/login') ?>" class="btn btn-success px-4 py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Proceed to Login Page
                            </a>
                            <a href="<?= url() ?>" class="btn btn-outline-secondary px-4 py-2">
                                <i class="bi bi-house me-1"></i> Back to Homepage
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>

                <!-- Multi-Step Registration Form -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-body p-4 p-md-5 bg-white border-bottom">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="rounded-circle bg-success text-white p-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.5rem;">
                                <i class="bi bi-person-lines-fill"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-dark mb-1">Step-by-Step Resident Registration</h3>
                                <p class="text-muted mb-0"><?= BRGY_NAME ?>, <?= BRGY_SUBTITLE ?></p>
                            </div>
                        </div>

                        <!-- Stepper Indicator -->
                        <div class="step-progress">
                            <div class="step-item active" id="stepIndicator1">
                                <div class="step-circle"><i class="bi bi-geo-alt"></i></div>
                                <div class="step-label">1. Sitio</div>
                            </div>
                            <div class="step-item" id="stepIndicator2">
                                <div class="step-circle"><i class="bi bi-person"></i></div>
                                <div class="step-label">2. Personal</div>
                            </div>
                            <div class="step-item" id="stepIndicator3">
                                <div class="step-circle"><i class="bi bi-heart-pulse"></i></div>
                                <div class="step-label">3. Welfare</div>
                            </div>
                            <div class="step-item" id="stepIndicator4">
                                <div class="step-circle"><i class="bi bi-camera"></i></div>
                                <div class="step-label">4. Photo & ID</div>
                            </div>
                            <div class="step-item" id="stepIndicator5">
                                <div class="step-circle"><i class="bi bi-shield-lock"></i></div>
                                <div class="step-label">5. Account</div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="p-4 pt-3">
                            <div class="alert alert-danger shadow-sm mb-0">
                                <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the following errors:</h6>
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= htmlspecialchars($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form with file upload support -->
                    <form action="" method="POST" enctype="multipart/form-data" id="signupWizardForm" class="p-4 p-md-5 pt-3">

                        <!-- ============================================ -->
                        <!-- STEP 1: SITIO & ADDRESS                      -->
                        <!-- ============================================ -->
                        <div class="form-step active" id="step1">
                            <h5 class="fw-bold text-success d-flex align-items-center gap-2 mb-3">
                                <i class="bi bi-geo-alt-fill"></i> Step 1: Sitio & Residence Location
                            </h5>
                            <p class="text-muted small mb-4">
                                Select which of the 7 Sitios of Barangay Lublub you permanently reside in:
                            </p>

                            <div class="row g-3 mb-4">
                                <div class="col-md-7">
                                    <label class="form-label fw-bold">Select Your Sitio <span class="text-danger">*</span></label>
                                    <select name="sitio_id" id="sitio_id" class="form-select form-select-lg border-success" required>
                                        <option value="">-- Choose 1 of 7 Sitios --</option>
                                        <?php foreach ($sitios as $s): ?>
                                            <option value="<?= $s['id'] ?>" <?= (isset($_POST['sitio_id']) && $_POST['sitio_id'] == $s['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['code']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text small text-muted">
                                        Your registration and uploaded ID will be routed to the Barangay Secretary for verification and approval.
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold mb-0">Household / Family No.</label>
                                        <span class="badge bg-light text-secondary border">Optional</span>
                                    </div>
                                    <input type="text" name="household_no" id="household_no" class="form-control form-control-lg" placeholder="e.g. HH-00142 (or leave blank / N/A)" value="<?= htmlspecialchars($_POST['household_no'] ?? '') ?>">
                                    <div class="form-text small text-muted">
                                        <i class="bi bi-info-circle text-primary me-1"></i>If you don't know your household number, leave this blank or type <code>N/A</code>. It will be verified by the Barangay Secretary during profiling.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Street / Purok / House No. / Landmark <span class="text-danger">*</span></label>
                                    <input type="text" name="address_line" id="address_line" class="form-control form-control-lg" placeholder="e.g. Purok Manga, House No. 24, Near Elementary School" value="<?= htmlspecialchars($_POST['address_line'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Role in Family</label>
                                    <select name="family_role" class="form-select">
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Son">Son</option>
                                        <option value="Daughter">Daughter</option>
                                        <option value="Grandparent">Grandparent / Senior</option>
                                        <option value="Guardian">Guardian</option>
                                        <option value="Member" selected>Member</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4 pt-2">
                                        <input class="form-check-input" type="checkbox" name="is_head_of_family" id="is_head_of_family" value="1" <?= isset($_POST['is_head_of_family']) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold" for="is_head_of_family">
                                            I am the Head of Household / Family
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-success btn-lg px-4 next-step-btn" data-next="step2">
                                    Next: Personal Information <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- STEP 2: PERSONAL INFORMATION                 -->
                        <!-- ============================================ -->
                        <div class="form-step" id="step2">
                            <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3">
                                <i class="bi bi-person-badge"></i> Step 2: Personal Information
                            </h5>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Suffix</label>
                                    <input type="text" name="suffix" class="form-control" placeholder="Jr., Sr., III" value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Age (Auto)</label>
                                    <input type="number" name="age" id="age" class="form-control bg-light fw-bold" readonly value="<?= htmlspecialchars($_POST['age'] ?? '0') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" id="gender" class="form-select" required>
                                        <option value="">-- Select Gender --</option>
                                        <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Civil Status <span class="text-danger">*</span></label>
                                    <select name="civil_status" class="form-select" required>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Solo Parent">Solo Parent / Mother</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Separated">Separated</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Contact / Mobile Number</label>
                                    <input type="text" name="contact_no" class="form-control" placeholder="0917-xxx-xxxx" value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email Address (Optional)</label>
                                    <input type="email" name="email" class="form-control" placeholder="your.email@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary prev-step-btn" data-prev="step1">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <button type="button" class="btn btn-success btn-lg px-4 next-step-btn" data-next="step3">
                                    Next: Social Welfare Profiling <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- STEP 3: SOCIAL SERVICES PROFILING            -->
                        <!-- ============================================ -->
                        <div class="form-step" id="step3">
                            <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-heart-pulse-fill text-danger"></i> Step 3: Social Welfare Profiling (Social Services Eligibility)
                            </h5>
                            <p class="small text-muted mb-4">
                                Tick all categories that apply to you. This enables targeted Ayuda, subsidies, and medical benefits in Barangay Lublub:
                            </p>

                            <div class="row g-3 mb-4">
                                <!-- Senior Citizen Card (60+) -->
                                <div class="col-md-6">
                                    <div class="card p-3 h-100 border-info-subtle bg-white shadow-sm">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_senior" id="is_senior" value="1" <?= isset($_POST['is_senior']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-info-emphasis" for="is_senior">
                                                <i class="bi bi-person-cane me-1"></i> Senior Citizen (60+ yrs)
                                            </label>
                                        </div>
                                        <div id="senior_details_container" class="mt-2 <?= isset($_POST['is_senior']) ? '' : 'd-none' ?>">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">OSCA / Senior ID Number <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="senior_id_no" class="form-control form-control-sm" placeholder="OSCA / Senior Citizen ID Number" value="<?= htmlspecialchars($_POST['senior_id_no'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-paperclip me-1"></i>Senior Citizen / OSCA ID Photo Attachment</label>
                                                <input type="file" name="senior_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                                                <div class="form-text small text-muted">Upload photo/scan of OSCA ID Card (JPG, PNG, PDF max 5MB).</div>
                                            </div>
                                            <div class="form-text small text-info"><i class="bi bi-info-circle me-1"></i>Qualifies for Quarterly Social Pension, Vitamins, and Medical Aid.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PWD Card -->
                                <div class="col-md-6">
                                    <div class="card p-3 h-100 border-primary border-opacity-75 bg-white shadow-sm">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_pwd" id="is_pwd" value="1" <?= isset($_POST['is_pwd']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-primary" for="is_pwd">
                                                <i class="bi bi-person-wheelchair me-1"></i> Person with Disability (PWD)
                                            </label>
                                        </div>
                                        <div id="pwd_details_container" class="mt-2 <?= isset($_POST['is_pwd']) ? '' : 'd-none' ?>">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">Disability Type <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="pwd_type" class="form-control form-control-sm" placeholder="e.g. Mobility, Visual, Hearing, Psychosocial" value="<?= htmlspecialchars($_POST['pwd_type'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">PWD ID Number <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="pwd_id_no" class="form-control form-control-sm" placeholder="Official PWD ID Card No." value="<?= htmlspecialchars($_POST['pwd_id_no'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-paperclip me-1"></i>PWD ID Document / Card Attachment</label>
                                                <input type="file" name="pwd_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                                                <div class="form-text small text-muted">Upload photo/scan of PWD ID Card (JPG, PNG, PDF max 5MB).</div>
                                            </div>
                                            <div class="form-text small text-danger"><i class="bi bi-shield-lock-fill me-1"></i>PWD ID is strictly required for official DOH/LGU PWD registry verification & benefits.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Student / Enrolled Learner -->
                                <div class="col-md-6">
                                    <div class="card p-3 h-100 border-info border-opacity-75 bg-white shadow-sm">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_student" id="is_student" value="1" <?= isset($_POST['is_student']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-info-emphasis" for="is_student">
                                                <i class="bi bi-mortarboard-fill me-1"></i> Student / Enrolled Learner
                                            </label>
                                        </div>
                                        <div id="student_details_container" class="mt-2 <?= isset($_POST['is_student']) ? '' : 'd-none' ?>">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">Student ID / LRN Number <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="student_id_no" class="form-control form-control-sm" placeholder="e.g. 123456789012 or School ID No." value="<?= htmlspecialchars($_POST['student_id_no'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">School / College / Institution <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="school_name" class="form-control form-control-sm" placeholder="e.g. Lublub Elem / Dumangas NSHS / ISAT U" value="<?= htmlspecialchars($_POST['school_name'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-paperclip me-1"></i>School ID / Enrollment Slip Attachment</label>
                                                <input type="file" name="school_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                                                <div class="form-text small text-muted">Upload photo/scan of current School ID or Certificate of Registration.</div>
                                            </div>
                                            <div class="form-text small text-info"><i class="bi bi-info-circle me-1"></i>Qualifies for LGU Educational Assistance, Student Fare Subsidy, and Free School Supplies.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Solo Mother / Parent -->
                                <div class="col-md-6">
                                    <div class="card p-3 h-100 border-danger-subtle bg-white shadow-sm">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_solo_parent" id="is_solo_parent" value="1" <?= isset($_POST['is_solo_parent']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-danger" for="is_solo_parent">
                                                <i class="bi bi-person-heart me-1"></i> Solo Mother / Solo Parent
                                            </label>
                                        </div>
                                        <div id="solo_parent_details_container" class="mt-2 <?= isset($_POST['is_solo_parent']) ? '' : 'd-none' ?>">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">Solo Parent ID Number <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="solo_parent_id_no" class="form-control form-control-sm" placeholder="Solo Parent ID Card Number" value="<?= htmlspecialchars($_POST['solo_parent_id_no'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">No. of Dependent Children <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="number" name="dependent_children_count" class="form-control form-control-sm" placeholder="e.g. 1, 2, 3" min="1" value="<?= htmlspecialchars($_POST['dependent_children_count'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-paperclip me-1"></i>Solo Parent ID / Certification Attachment</label>
                                                <input type="file" name="solo_parent_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                                                <div class="form-text small text-muted">Upload photo/scan of Solo Parent ID or DSWD Certificate.</div>
                                            </div>
                                            <div class="form-text small text-danger"><i class="bi bi-info-circle me-1"></i>Qualifies for RA 11861 Monthly Cash Subsidy and Milk/Rice Nutritional Aid.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Indigent Household -->
                                <div class="col-md-6">
                                    <div class="card p-3 h-100 border-warning-subtle bg-white shadow-sm">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_indigent" id="is_indigent" value="1" <?= isset($_POST['is_indigent']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-warning-emphasis" for="is_indigent">
                                                <i class="bi bi-wallet2 me-1"></i> Indigent Low-Income Family
                                            </label>
                                        </div>
                                        <div id="indigent_details_container" class="mt-2 <?= isset($_POST['is_indigent']) ? '' : 'd-none' ?>">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">Est. Monthly Family Income (PHP) <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="number" step="0.01" name="monthly_income" class="form-control form-control-sm" placeholder="e.g. 3500.00" value="<?= htmlspecialchars($_POST['monthly_income'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">Main Livelihood / Income Source <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="income_source" class="form-control form-control-sm" placeholder="e.g. Farming, Labor, Vendor, Tricycle" value="<?= htmlspecialchars($_POST['income_source'] ?? '') ?>">
                                            </div>
                                            <div class="form-text small text-warning-emphasis"><i class="bi bi-info-circle me-1"></i>Qualifies for Free Certificate of Indigency, Emergency Ayuda, and Medical Subsidy.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4Ps Beneficiary -->
                                <div class="col-md-6">
                                    <div class="card p-3 h-100 border-success-subtle bg-white shadow-sm">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_4ps" id="is_4ps" value="1" <?= isset($_POST['is_4ps']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-success" for="is_4ps">
                                                <i class="bi bi-card-checklist me-1"></i> DSWD 4Ps Member
                                            </label>
                                        </div>
                                        <div id="four_ps_details_container" class="mt-2 <?= isset($_POST['is_4ps']) ? '' : 'd-none' ?>">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">4Ps Household ID Number <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="four_ps_id_no" class="form-control form-control-sm" placeholder="e.g. 4PS-ILO-DUM-10029" value="<?= htmlspecialchars($_POST['four_ps_id_no'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-paperclip me-1"></i>4Ps Household ID Card Attachment</label>
                                                <input type="file" name="four_ps_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                                                <div class="form-text small text-muted">Upload photo/scan of 4Ps ID Card or Pantawid passbook.</div>
                                            </div>
                                            <div class="form-text small text-success"><i class="bi bi-info-circle me-1"></i>Enables cross-verification with DSWD Pantawid Pamilyang Pilipino Program.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Registered Voter -->
                                <div class="col-md-6">
                                    <div class="card p-3 h-100 border-secondary-subtle bg-white shadow-sm">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_voter" id="is_voter" value="1" <?= isset($_POST['is_voter']) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-secondary" for="is_voter">
                                                <i class="bi bi-check2-square me-1"></i> Registered Voter (Lublub)
                                            </label>
                                        </div>
                                        <div id="voter_details_container" class="mt-2 <?= isset($_POST['is_voter']) ? '' : 'd-none' ?>">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1">COMELEC Precinct Number <span class="badge bg-danger-subtle text-danger ms-1">Required</span></label>
                                                <input type="text" name="precinct_no" class="form-control form-control-sm" placeholder="e.g. 0014B or Precinct cluster" value="<?= htmlspecialchars($_POST['precinct_no'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-paperclip me-1"></i>Voter's ID / Registration Stub Attachment</label>
                                                <input type="file" name="voter_id_file" class="form-control form-control-sm" accept="image/*,.pdf">
                                                <div class="form-text small text-muted">Upload photo/scan of Voter's ID or COMELEC registration form.</div>
                                            </div>
                                            <div class="form-text small text-secondary"><i class="bi bi-info-circle me-1"></i>Qualifies for Barangay Voter Certificate and Community Electoral Services.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary prev-step-btn" data-prev="step2">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <button type="button" class="btn btn-success btn-lg px-4 next-step-btn" data-next="step4">
                                    Next: Realtime Selfie & ID Upload <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- STEP 4: REALTIME SELFIE & ID ATTACHMENT      -->
                        <!-- ============================================ -->
                        <div class="form-step" id="step4">
                            <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-camera-fill text-success"></i> Step 4: Identity Verification (Live Selfie & Valid ID Attachment)
                            </h5>
                            <p class="small text-muted mb-4">
                                To protect your resident identity and prevent unauthorized registrations, capture a live picture of yourself and attach any valid government / school / senior / barangay ID:
                            </p>

                            <div class="row g-4 mb-4">
                                <!-- LEFT: LIVE WEBCAM SELFIE -->
                                <div class="col-lg-6">
                                    <div class="card p-3 h-100 border-success shadow-sm bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-success"><i class="bi bi-camera-video-fill me-1"></i> Real-time Selfie Capture</span>
                                            <span class="badge bg-success small">Camera</span>
                                        </div>

                                        <div class="camera-container mb-3">
                                            <!-- Video Stream -->
                                            <video id="webcamVideo" autoplay playsinline></video>
                                            <!-- Snapshot Canvas -->
                                            <canvas id="selfieCanvas" class="d-none"></canvas>
                                            <!-- Captured Preview Image -->
                                            <img id="selfiePreview" src="" alt="Captured Selfie" class="d-none w-100 rounded" style="max-height: 280px; object-fit: cover;">
                                        </div>

                                        <!-- Hidden input to store captured base64 image data -->
                                        <input type="hidden" name="selfie_data" id="selfie_data" value="">

                                        <div class="d-flex justify-content-center gap-2 mb-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="startCamBtn">
                                                <i class="bi bi-camera me-1"></i> Open Camera
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm fw-bold d-none" id="snapCamBtn">
                                                <i class="bi bi-camera-fill me-1"></i> Take Photo
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-sm d-none" id="retakeCamBtn">
                                                <i class="bi bi-arrow-clockwise me-1"></i> Retake Photo
                                            </button>
                                        </div>

                                        <div class="text-center pt-2 border-top">
                                            <span class="small text-muted d-block mb-1">No camera available or on mobile?</span>
                                            <input type="file" name="selfie_file" id="selfie_file" class="form-control form-control-sm" accept="image/*">
                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT: VALID ID ATTACHMENT -->
                                <div class="col-lg-6">
                                    <div class="card p-3 h-100 border-primary shadow-sm bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-primary"><i class="bi bi-person-vcard-fill me-1"></i> Valid ID Attachment</span>
                                            <span class="badge bg-primary small">Document</span>
                                        </div>
                                        <p class="small text-muted mb-2">
                                            Upload a clear photo or scan of your Senior ID, PWD ID, Barangay ID, Voters ID, PhilHealth, Driver's License, or Student ID:
                                        </p>

                                        <div class="mb-3">
                                            <input type="file" name="id_file" id="id_file" class="form-control form-control-lg border-primary" accept="image/*,.pdf" onchange="previewIdAttachment(this)">
                                            <div class="form-text small text-muted">Accepted formats: JPG, PNG, WEBP, PDF (Max 5MB)</div>
                                        </div>

                                        <!-- ID Preview Container -->
                                        <div class="p-2 border rounded bg-light text-center" id="idPreviewBox" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <div class="text-muted small" id="idPreviewPlaceholder">
                                                <i class="bi bi-file-earmark-arrow-up display-4 d-block text-secondary mb-2"></i>
                                                ID document preview will appear here
                                            </div>
                                            <img id="idPreviewImg" src="" alt="ID Preview" class="img-fluid rounded d-none" style="max-height: 220px; object-fit: contain;">
                                        </div>
                                    </div>
                                </div>

                                <!-- FULL WIDTH: HOUSE / DWELLING EXTERIOR PHOTO DROPZONE -->
                                <div class="col-12 mt-3">
                                    <div class="card p-3 border-secondary border-opacity-50 shadow-sm bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-dark"><i class="bi bi-houses-fill text-success me-1"></i> House / Dwelling Exterior Photo</span>
                                            <span class="badge bg-secondary small">Optional / Socio-Economic Profiling</span>
                                        </div>
                                        <p class="small text-muted mb-3">
                                            Upload an exterior photograph of your house or residence in Barangay Lublub. Assists the Barangay Officials and your assigned BHW to locate and map your household for social welfare and calamity assistance.
                                        </p>

                                        <!-- Drag and Drop Dropzone -->
                                        <div class="dropzone-box text-center p-3 rounded-4 border-2 border-dashed bg-light position-relative" id="dropzoneHouseSignup" style="border-color: #cbd5e1; cursor: pointer; transition: all 0.2s ease;">
                                            <input type="file" name="house_photo" id="housePhotoInputSignup" accept="image/jpeg,image/png,image/webp" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                                            <div class="dz-prompt py-2" id="housePromptSignup">
                                                <div class="mb-1"><i class="bi bi-cloud-arrow-up-fill fs-2 text-success"></i></div>
                                                <div class="fw-bold text-dark small mb-1">Drag & Drop House Exterior Photo Here</div>
                                                <div class="text-muted" style="font-size: 0.78rem;">or click to browse from device (JPG, PNG, WEBP max 5MB)</div>
                                            </div>
                                            <div class="dz-preview d-none" id="housePreviewBoxSignup">
                                                <img src="" alt="House Preview" class="img-fluid rounded-3 shadow-sm mb-2" id="housePreviewImgSignup" style="max-height: 180px; object-fit: cover;">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <span class="badge bg-success" id="houseFileNameSignup">Photo Selected</span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btnRemoveHouseSignup">
                                                        <i class="bi bi-x-circle me-1"></i>Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary prev-step-btn" data-prev="step3">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <button type="button" class="btn btn-success btn-lg px-4 next-step-btn" data-next="step5">
                                    Next: Account Credentials <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- STEP 5: ACCOUNT CREDENTIALS & SUBMIT         -->
                        <!-- ============================================ -->
                        <div class="form-step" id="step5">
                            <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3">
                                <i class="bi bi-shield-lock-fill"></i> Step 5: Resident Portal Credentials & Final Confirmation
                            </h5>

                            <div class="card p-4 border bg-light mb-4">
                                <h6 class="fw-bold text-success mb-3"><i class="bi bi-check2-circle me-1"></i>Registration Summary Review:</h6>
                                <div class="row g-2 small text-dark">
                                    <div class="col-sm-4 text-muted">Full Name:</div>
                                    <div class="col-sm-8 fw-bold" id="summaryName">-</div>

                                    <div class="col-sm-4 text-muted">Assigned Sitio:</div>
                                    <div class="col-sm-8 fw-bold text-success" id="summarySitio">-</div>

                                    <div class="col-sm-4 text-muted">Address:</div>
                                    <div class="col-sm-8" id="summaryAddress">-</div>

                                    <div class="col-sm-4 text-muted">Age & Status:</div>
                                    <div class="col-sm-8" id="summaryAge">-</div>

                                    <div class="col-sm-4 text-muted">Verification Docs:</div>
                                    <div class="col-sm-8 text-primary fw-semibold" id="summaryDocs">Selfie & ID attached</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Desired Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username" class="form-control form-control-lg" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Min. 8 characters" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirm" id="password_confirm" class="form-control form-control-lg" placeholder="Repeat password" required>
                                </div>
                            </div>

                            <!-- Password Uniqueness & Strength Indicator -->
                            <div class="p-3 bg-light border rounded-3 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark"><i class="bi bi-shield-lock-fill text-primary me-1"></i> Password Uniqueness & Strength:</span>
                                    <span id="pwdStrengthLabel" class="badge bg-secondary">Waiting for input</span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div id="pwdStrengthBar" class="progress-bar bg-secondary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="row g-1 small text-muted" id="pwdChecklist" style="font-size: 0.78rem;">
                                    <div class="col-sm-6" id="chk-len"><i class="bi bi-circle me-1"></i> At least 8 characters</div>
                                    <div class="col-sm-6" id="chk-mix"><i class="bi bi-circle me-1"></i> Letters and numbers combination</div>
                                    <div class="col-sm-6" id="chk-unique"><i class="bi bi-circle me-1"></i> Unique (not matching name or username)</div>
                                    <div class="col-sm-6" id="chk-match"><i class="bi bi-circle me-1"></i> Passwords match</div>
                                </div>
                            </div>

                            <div class="form-check p-3 bg-white border rounded-3 mb-4">
                                <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                                <label class="form-check-label small" for="termsCheck">
                                    I certify under penalty of perjury that I am a bonafide resident of <strong>Barangay Lublub (P.D. Monfort North)</strong>, and that all information, live photo, and uploaded identification documents submitted herein are genuine and true.
                                </label>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary prev-step-btn" data-prev="step4">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <button type="submit" name="submit" class="btn btn-success btn-lg px-5 shadow fw-bold">
                                    <i class="bi bi-send-check me-2"></i> Submit Resident Registration
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Multi-Step Wizard Navigation
    const nextButtons = document.querySelectorAll('.next-step-btn');
    const prevButtons = document.querySelectorAll('.prev-step-btn');
    const formSteps = document.querySelectorAll('.form-step');
    const stepIndicators = {
        'step1': document.getElementById('stepIndicator1'),
        'step2': document.getElementById('stepIndicator2'),
        'step3': document.getElementById('stepIndicator3'),
        'step4': document.getElementById('stepIndicator4'),
        'step5': document.getElementById('stepIndicator5')
    };

    function showStep(stepId) {
        formSteps.forEach(fs => fs.classList.remove('active'));
        const target = document.getElementById(stepId);
        if (target) {
            target.classList.add('active');
            window.scrollTo({ top: target.offsetTop - 50, behavior: 'smooth' });
        }

        // Update step indicators
        let stepNumber = parseInt(stepId.replace('step', ''));
        for (let i = 1; i <= 5; i++) {
            const ind = stepIndicators['step' + i];
            if (!ind) continue;
            ind.classList.remove('active', 'completed');
            if (i < stepNumber) {
                ind.classList.add('completed');
            } else if (i === stepNumber) {
                ind.classList.add('active');
            }
        }

        // If step 5, update summary preview
        if (stepId === 'step5') {
            const fName = document.getElementById('first_name')?.value || '';
            const lName = document.getElementById('last_name')?.value || '';
            document.getElementById('summaryName').textContent = (fName + ' ' + lName).trim() || 'Not specified';

            const sitioSelect = document.getElementById('sitio_id');
            const sitioText = sitioSelect?.options[sitioSelect.selectedIndex]?.text || '';
            document.getElementById('summarySitio').textContent = sitioText || 'Not selected';

            document.getElementById('summaryAddress').textContent = document.getElementById('address_line')?.value || 'Not provided';
            
            const ageVal = document.getElementById('age')?.value || 0;
            const genderVal = document.getElementById('gender')?.value || '';
            document.getElementById('summaryAge').textContent = `${ageVal} years old • ${genderVal}`;
        }
    }

    nextButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const nextStepId = this.dataset.next;
            // Validate required inputs in current active step
            const currentStep = this.closest('.form-step');
            const requiredInputs = currentStep.querySelectorAll('input[required], select[required]');
            let isValid = true;

            requiredInputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.reportValidity();
                    isValid = false;
                }
            });

            if (isValid) {
                showStep(nextStepId);
            }
        });
    });

    prevButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const prevStepId = this.dataset.prev;
            showStep(prevStepId);
        });
    });

    // 2. Realtime Webcam Selfie Logic
    const startCamBtn = document.getElementById('startCamBtn');
    const snapCamBtn = document.getElementById('snapCamBtn');
    const retakeCamBtn = document.getElementById('retakeCamBtn');
    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('selfieCanvas');
    const preview = document.getElementById('selfiePreview');
    const selfieDataInput = document.getElementById('selfie_data');
    let videoStream = null;

    if (startCamBtn && video) {
        startCamBtn.addEventListener('click', async function () {
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                    audio: false
                });
                video.srcObject = videoStream;
                video.classList.remove('d-none');
                preview.classList.add('d-none');
                startCamBtn.classList.add('d-none');
                snapCamBtn.classList.remove('d-none');
            } catch (err) {
                alert('Could not access camera: ' + err.message + '\nYou can use the file upload option below instead.');
            }
        });
    }

    if (snapCamBtn && canvas && preview) {
        snapCamBtn.addEventListener('click', function () {
            canvas.width = video.videoWidth || 400;
            canvas.height = video.videoHeight || 300;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            selfieDataInput.value = dataUrl;

            preview.src = dataUrl;
            preview.classList.remove('d-none');
            video.classList.add('d-none');
            snapCamBtn.classList.add('d-none');
            retakeCamBtn.classList.remove('d-none');

            // Stop camera stream
            if (videoStream) {
                videoStream.getTracks().forEach(t => t.stop());
            }
        });
    }

    if (retakeCamBtn && startCamBtn) {
        retakeCamBtn.addEventListener('click', function () {
            selfieDataInput.value = '';
            preview.classList.add('d-none');
            retakeCamBtn.classList.add('d-none');
            startCamBtn.click();
        });
    }

    // 3. Dynamic Age Calculation from Birthdate
    const birthDateInput = document.getElementById('birth_date');
    const ageInput = document.getElementById('age');

    if (birthDateInput && ageInput) {
        birthDateInput.addEventListener('change', function () {
            if (this.value) {
                const dob = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                ageInput.value = age >= 0 ? age : 0;

                // Auto flag senior citizen if >= 60
                const seniorCheckbox = document.getElementById('is_senior');
                const seniorContainer = document.getElementById('senior_details_container');
                if (seniorCheckbox) {
                    seniorCheckbox.checked = (age >= 60);
                    if (seniorContainer) {
                        if (age >= 60) {
                            seniorContainer.classList.remove('d-none');
                        } else {
                            seniorContainer.classList.add('d-none');
                        }
                    }
                }
            }
        });
    }

    // 4. Conditional toggles for welfare fields & dynamic required attribute enforcement
    function setupToggle(checkId, containerId, requiredFieldNames = []) {
        const check = document.getElementById(checkId);
        const container = document.getElementById(containerId);
        if (check && container) {
            function syncWelfareState() {
                if (check.checked) {
                    container.classList.remove('d-none');
                    requiredFieldNames.forEach(fieldName => {
                        const input = container.querySelector(`[name="${fieldName}"]`);
                        if (input) {
                            input.required = true;
                        }
                    });
                } else {
                    container.classList.add('d-none');
                    requiredFieldNames.forEach(fieldName => {
                        const input = container.querySelector(`[name="${fieldName}"]`);
                        if (input) {
                            input.required = false;
                            input.setCustomValidity('');
                        }
                    });
                }
            }

            check.addEventListener('change', syncWelfareState);
            syncWelfareState(); // Run once on load to sync any pre-checked POST values
        }
    }

    setupToggle('is_senior', 'senior_details_container', ['senior_id_no']);
    setupToggle('is_pwd', 'pwd_details_container', ['pwd_type', 'pwd_id_no']);
    setupToggle('is_student', 'student_details_container', ['student_id_no', 'school_name']);
    setupToggle('is_solo_parent', 'solo_parent_details_container', ['solo_parent_id_no', 'dependent_children_count']);
    setupToggle('is_indigent', 'indigent_details_container', ['monthly_income', 'income_source']);
    setupToggle('is_4ps', 'four_ps_details_container', ['four_ps_id_no']);
    setupToggle('is_voter', 'voter_details_container', ['precinct_no']);

    // House Photo Dropzone Handlers
    const houseDz = document.getElementById('dropzoneHouseSignup');
    const houseInput = document.getElementById('housePhotoInputSignup');
    const housePrompt = document.getElementById('housePromptSignup');
    const housePreviewBox = document.getElementById('housePreviewBoxSignup');
    const housePreviewImg = document.getElementById('housePreviewImgSignup');
    const houseFileName = document.getElementById('houseFileNameSignup');
    const btnRemoveHouse = document.getElementById('btnRemoveHouseSignup');

    if (houseDz && houseInput) {
        ['dragenter', 'dragover'].forEach(eName => {
            houseDz.addEventListener(eName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                houseDz.style.borderColor = '#10b981';
                houseDz.style.backgroundColor = '#ecfdf5';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eName => {
            houseDz.addEventListener(eName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                houseDz.style.borderColor = '#cbd5e1';
                houseDz.style.backgroundColor = '#f8fafc';
            }, false);
        });

        houseDz.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt.files && dt.files.length > 0) {
                houseInput.files = dt.files;
                handleHouseFile(dt.files[0]);
            }
        });

        houseInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                handleHouseFile(this.files[0]);
            }
        });

        function handleHouseFile(file) {
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
                housePreviewImg.src = e.target.result;
                houseFileName.textContent = file.name;
                housePrompt.classList.add('d-none');
                housePreviewBox.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }

        if (btnRemoveHouse) {
            btnRemoveHouse.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                houseInput.value = '';
                housePreviewImg.src = '';
                housePreviewBox.classList.add('d-none');
                housePrompt.classList.remove('d-none');
            });
        }
    }
});

// ID attachment preview helper
function previewIdAttachment(input) {
    const previewImg = document.getElementById('idPreviewImg');
    const placeholder = document.getElementById('idPreviewPlaceholder');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('d-none');
                placeholder.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            // PDF file
            placeholder.innerHTML = `<i class="bi bi-file-earmark-pdf-fill display-4 text-danger d-block mb-1"></i><strong>${file.name}</strong><br><small class="text-muted">PDF Attachment Ready</small>`;
            placeholder.classList.remove('d-none');
            previewImg.classList.add('d-none');
        }
    }
}
// Password Uniqueness & Real-Time Strength Meter
document.addEventListener('DOMContentLoaded', function () {
    const pwdInput = document.getElementById('password');
    const pwdConfirm = document.getElementById('password_confirm');
    const userInput = document.getElementById('username');
    const firstNameInput = document.querySelector('input[name="first_name"]');
    const lastNameInput = document.querySelector('input[name="last_name"]');

    const bar = document.getElementById('pwdStrengthBar');
    const label = document.getElementById('pwdStrengthLabel');

    const chkLen = document.getElementById('chk-len');
    const chkMix = document.getElementById('chk-mix');
    const chkUnique = document.getElementById('chk-unique');
    const chkMatch = document.getElementById('chk-match');

    function updateItem(el, passed) {
        if (!el) return;
        if (passed) {
            el.className = 'col-sm-6 text-success fw-semibold';
            el.innerHTML = '<i class="bi bi-check-circle-fill me-1 text-success"></i> ' + el.textContent.trim();
        } else {
            el.className = 'col-sm-6 text-muted';
            el.innerHTML = '<i class="bi bi-circle me-1 text-secondary"></i> ' + el.textContent.trim();
        }
    }

    function checkPassword() {
        if (!pwdInput) return;
        const pwd = pwdInput.value || '';
        const confirm = pwdConfirm ? pwdConfirm.value || '' : '';
        const user = userInput ? (userInput.value || '').toLowerCase() : '';
        const fname = firstNameInput ? (firstNameInput.value || '').toLowerCase() : '';
        const lname = lastNameInput ? (lastNameInput.value || '').toLowerCase() : '';

        const hasLen = pwd.length >= 8;
        const hasMix = /[A-Za-z]/.test(pwd) && /[0-9]/.test(pwd);
        
        let isUnique = true;
        const lowerPwd = pwd.toLowerCase();
        if (user.length >= 3 && lowerPwd.includes(user)) isUnique = false;
        if (fname.length >= 3 && lowerPwd.includes(fname)) isUnique = false;
        if (lname.length >= 3 && lowerPwd.includes(lname)) isUnique = false;
        const commonWeak = ['password', 'password123', '12345678', '123456789', 'admin123', 'qwerty123', 'barangay123', 'lublub123'];
        if (commonWeak.includes(lowerPwd)) isUnique = false;

        const hasMatch = (pwd.length > 0 && confirm.length > 0 && pwd === confirm);

        updateItem(chkLen, hasLen);
        updateItem(chkMix, hasMix);
        updateItem(chkUnique, isUnique && pwd.length > 0);
        updateItem(chkMatch, hasMatch);

        // Score
        let score = 0;
        if (pwd.length >= 8) score += 25;
        if (pwd.length >= 12) score += 15;
        if (hasMix) score += 25;
        if (/[^A-Za-z0-9]/.test(pwd)) score += 15;
        if (isUnique && pwd.length >= 8) score += 20;

        if (pwd.length === 0) {
            bar.style.width = '0%';
            bar.className = 'progress-bar bg-secondary';
            label.className = 'badge bg-secondary';
            label.textContent = 'Waiting for input';
        } else if (score < 40) {
            bar.style.width = Math.max(20, score) + '%';
            bar.className = 'progress-bar bg-danger';
            label.className = 'badge bg-danger';
            label.textContent = 'Weak / Not Unique';
        } else if (score < 75) {
            bar.style.width = score + '%';
            bar.className = 'progress-bar bg-warning';
            label.className = 'badge bg-warning text-dark';
            label.textContent = 'Moderate';
        } else {
            bar.style.width = '100%';
            bar.className = 'progress-bar bg-success';
            label.className = 'badge bg-success';
            label.textContent = 'Strong & Unique';
        }
    }

    if (pwdInput) pwdInput.addEventListener('input', checkPassword);
    if (pwdConfirm) pwdConfirm.addEventListener('input', checkPassword);
    if (userInput) userInput.addEventListener('input', checkPassword);
});
</script>
</body>
</html>
