<?php
// auth/logout - Session destruction & logout handler
require_once __DIR__ . '/../../config/app.php';

if (is_logged_in()) {
    $fullName = auth_user()['full_name'] ?? 'User';
    log_activity('User Logout', "User {$fullName} signed out");
}

session_unset();
session_destroy();

session_start();
set_flash('info', 'You have been logged out safely.');
redirect('auth/login');

