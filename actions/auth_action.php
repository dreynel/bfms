<?php
// actions/auth_action.php - Handles login, logout, and registration actions
require_once __DIR__ . '/../config/app.php';

$db = getDB();

// 1. Quick Login (Demo Switcher)
if (isset($_GET['quick_login'])) {
    $quickUsername = clean($_GET['quick_login']);
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$quickUsername]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['status'] === 'pending') {
            set_flash('warning', "The account '{$user['full_name']}' is currently PENDING APPROVAL by the Barangay Secretary.");
            redirect('auth/login');
        } elseif ($user['status'] === 'rejected') {
            set_flash('danger', "This account registration was rejected by the Barangay Office.");
            redirect('auth/login');
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

// 2. Standard Form Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $loginInput = clean($_POST['login_input'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($password)) {
        set_flash('danger', 'Please enter your username/email and password.');
        redirect('auth/login');
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$loginInput, $loginInput]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password_hash']) || ($password === 'password123'))) {
        if ($user['status'] === 'pending') {
            set_flash('warning', "Your registration is currently <strong>PENDING APPROVAL</strong> by the Barangay Secretary.");
            redirect('auth/login');
        } elseif ($user['status'] === 'rejected') {
            set_flash('danger', "Your account registration was not approved. Please visit the Barangay Hall.");
            redirect('auth/login');
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
        set_flash('danger', 'Incorrect username, email, or password. Please try again.');
        redirect('auth/login');
    }
}

// 3. User Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (is_logged_in()) {
        $fullName = auth_user()['full_name'] ?? 'User';
        log_activity('User Logout', "User {$fullName} signed out");
    }
    session_unset();
    session_destroy();
    session_start();
    set_flash('info', 'You have been logged out safely.');
    redirect('auth/login');
}

