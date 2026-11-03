<?php
// auth/login - Unified Login Portal with Quick Switcher for Testing
require_once __DIR__ . '/../../config/app.php';

if (is_logged_in()) {
    redirect('dashboard');
}

$db = getDB();
$error = null;
$pendingNotice = null;

// Handle quick-login demo helper
if (isset($_GET['quick_login'])) {
    $quickUsername = clean($_GET['quick_login']);
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$quickUsername]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['status'] === 'pending') {
            $pendingNotice = "The account '{$user['full_name']}' is currently PENDING APPROVAL by the Barangay Secretary.";
        } elseif ($user['status'] === 'rejected') {
            $error = "This account registration was rejected by the Barangay Office.";
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'assigned_sitio_id' => $user['assigned_sitio_id']
            ];
            log_activity('User Login', "User {$user['username']} logged in via demo switcher");
            set_flash('success', "Welcome back, <strong>{$user['full_name']}</strong>! Logged in as " . strtoupper($user['role']) . ".");
            redirect('dashboard');
        }
    }
}

// Standard Form Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = clean($_POST['login_input'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($password)) {
        $error = "Please enter your username/email and password.";
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$loginInput, $loginInput]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify password (allows password123 default or hash verification)
            $valid = password_verify($password, $user['password_hash']) || ($password === 'password123');

            if ($valid) {
                if ($user['status'] === 'pending') {
                    $pendingNotice = "Your registration is currently <strong>PENDING APPROVAL</strong> by the Barangay Secretary. Please check back later or visit the Barangay Hall.";
                } elseif ($user['status'] === 'rejected') {
                    $error = "Your account registration was not approved. Please visit the Barangay Hall for assistance.";
                } elseif ($user['status'] === 'suspended') {
                    $error = "Your account has been deactivated. Please contact the Barangay Secretary.";
                } else {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'role' => $user['role'],
                        'assigned_sitio_id' => $user['assigned_sitio_id']
                    ];
                    log_activity('User Login', "User {$user['username']} logged in successfully");
                    set_flash('success', "Welcome back, <strong>{$user['full_name']}</strong>!");
                    redirect('dashboard');
                }
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "Account not found with that username or email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-success border-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url() ?>">
            <span class="badge bg-success rounded-circle p-2">BL</span>
            <div>
                <strong class="d-block lh-1 text-white"><?= BRGY_NAME ?></strong>
                <small class="text-white-50" style="font-size: 0.72rem;"><?= BRGY_SUBTITLE ?></small>
            </div>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="<?= url() ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-house me-1"></i> Home</a>
            <a href="<?= url('auth/signup') ?>" class="btn btn-sm btn-success"><i class="bi bi-person-plus me-1"></i> Resident Sign Up</a>
        </div>
    </div>
</nav>

<div class="container my-auto py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">

            <?= render_flash() ?>

            <!-- Pending Notice Banner -->
            <?php if ($pendingNotice): ?>
                <div class="alert alert-warning border border-warning shadow-sm mb-4">
                    <div class="d-flex gap-3">
                        <i class="bi bi-clock-history fs-3 text-warning-emphasis"></i>
                        <div>
                            <h6 class="fw-bold text-warning-emphasis mb-1">Registration Awaiting Approval</h6>
                            <p class="mb-0 small text-dark"><?= $pendingNotice ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Card -->
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white text-center py-4 border-bottom">
                    <div class="rounded-circle bg-success text-white mx-auto d-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 58px; height: 58px; font-size: 1.6rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0">System Login</h4>
                    <p class="text-muted small mb-0">Barangay Officials, Health Workers & Residents</p>
                </div>

                <div class="card-body p-4 p-md-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 small d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                            <div><?= $error ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username or Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" name="login_input" class="form-control" placeholder="Enter username or email" value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                            </div>
                            <div class="form-text small text-muted">Default password for demo accounts: <code>password123</code></div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                            </button>
                        </div>

                        <div class="text-center pt-2 border-top">
                            <span class="text-muted small">New resident of Barangay Lublub?</span><br>
                            <a href="<?= url('auth/signup') ?>" class="text-success fw-bold text-decoration-none">
                                <i class="bi bi-pencil-square me-1"></i> Register here for Social Services Profiling
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- QUICK DEMO LOGIN SWITCHER -->
            <div class="card border border-success border-opacity-50 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-success bg-opacity-10 py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-success small"><i class="bi bi-lightning-charge-fill me-1"></i> Quick Demo Role Switcher</span>
                    <span class="badge bg-success small">1-Click Test</span>
                </div>
                <div class="card-body p-3">
                    <p class="small text-muted mb-3">Click any account below to instantly log in and test permissions per Sitio:</p>

                    <!-- Barangay Executives -->
                    <div class="mb-2">
                        <div class="text-muted small fw-bold text-uppercase d-flex align-items-center gap-1" style="font-size: 0.7rem;">
                            <i class="bi bi-shield-shaded text-dark"></i> Barangay Administration:
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <a href="<?= url('auth/login?quick_login=captain') ?>" class="btn btn-xs btn-outline-dark small py-1 px-2" title="Hon. Eliseo Rodriguez">
                                <i class="bi bi-award-fill text-warning me-1"></i> <strong>Captain</strong> (Hon. Eliseo Rodriguez)
                            </a>
                            <a href="<?= url('auth/login?quick_login=secretary') ?>" class="btn btn-xs btn-outline-dark small py-1 px-2" title="Ms. Annielor Gulbique">
                                <i class="bi bi-journal-check text-primary me-1"></i> <strong>Secretary</strong> (Ms. Annielor Gulbique)
                            </a>
                            <a href="<?= url('auth/login?quick_login=treasurer') ?>" class="btn btn-xs btn-outline-success small py-1 px-2 fw-bold" title="Ms. Joy Valiente (Disbursements)">
                                <i class="bi bi-cash-stack text-success me-1"></i> <strong>Treasurer</strong> (Ms. Joy Valiente)
                            </a>
                        </div>
                    </div>

                    <!-- 7 Barangay Health Workers (BHW) Per Sitio -->
                    <div class="mb-1">
                        <div class="text-muted small fw-bold text-uppercase d-flex align-items-center gap-1" style="font-size: 0.7rem;">
                            <i class="bi bi-heart-pulse-fill text-danger"></i> Barangay Health Workers (BHW Per Sitio):
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <a href="<?= url('auth/login?quick_login=bhw_proper') ?>" class="btn btn-xs btn-outline-danger small py-1 px-2" title="Sitio Proper">
                                <i class="bi bi-heart-pulse me-1"></i> BHW Proper (Elena Santos)
                            </a>
                            <a href="<?= url('auth/login?quick_login=bhw_centro') ?>" class="btn btn-xs btn-outline-danger small py-1 px-2" title="Sitio Centro">
                                <i class="bi bi-heart-pulse me-1"></i> BHW Centro (Jocelyn Ramirez)
                            </a>
                            <a href="<?= url('auth/login?quick_login=bhw_weapons') ?>" class="btn btn-xs btn-outline-danger small py-1 px-2" title="Sitio Weapons">
                                <i class="bi bi-heart-pulse me-1"></i> BHW Weapons (Teresa Villanueva)
                            </a>
                            <a href="<?= url('auth/login?quick_login=bhw_facoma') ?>" class="btn btn-xs btn-outline-danger small py-1 px-2" title="Sitio Ilaya Facoma">
                                <i class="bi bi-heart-pulse me-1"></i> BHW Facoma (Rosanna Dela Cruz)
                            </a>
                            <a href="<?= url('auth/login?quick_login=bhw_sabang') ?>" class="btn btn-xs btn-outline-danger small py-1 px-2" title="Sitio Sabang">
                                <i class="bi bi-heart-pulse me-1"></i> BHW Sabang (Carmela Gonzales)
                            </a>
                            <a href="<?= url('auth/login?quick_login=bhw_subdivision') ?>" class="btn btn-xs btn-outline-danger small py-1 px-2" title="Sitio Subdivision">
                                <i class="bi bi-heart-pulse me-1"></i> BHW Subd (Liezl Fernandez)
                            </a>
                            <a href="<?= url('auth/login?quick_login=bhw_lanag') ?>" class="btn btn-xs btn-outline-danger small py-1 px-2" title="Sitio Lanag">
                                <i class="bi bi-heart-pulse me-1"></i> BHW Lanag (Marites Castillo)
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<footer class="py-3 bg-white border-top text-center text-muted small mt-auto">
    &copy; <?= date('Y') ?> <?= BRGY_NAME ?>, <?= BRGY_SUBTITLE ?>. BFMS Social Services Platform.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

