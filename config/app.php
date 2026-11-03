<?php
// config/app.php - Global application settings and helper utilities

if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Polyfills for PHP 7.4 compatibility
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
}

// Smart BASE_URL Auto-Detection (Works for XAMPP, php -S, & Hostinger)
function detect_base_url() {
    if (getenv('BASE_URL') !== false && getenv('BASE_URL') !== '') {
        return rtrim(getenv('BASE_URL'), '/');
    }
    
    // Built-in terminal server: php -S
    if (php_sapi_name() === 'cli-server') {
        return '';
    }

    if (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $knownDirs = [
            '/pages/auth', '/pages/residents', '/pages/social_services',
            '/pages/certificates', '/pages/reports', '/pages',
            '/modules/auth', '/modules/dashboard', '/modules/residents',
            '/modules/social_services', '/modules/sitios', '/modules/certificates',
            '/modules/officials', '/modules/reports', '/config', '/views'
        ];
        foreach ($knownDirs as $kd) {
            if (substr($scriptDir, -strlen($kd)) === $kd) {
                return rtrim(substr($scriptDir, 0, -strlen($kd)), '/');
            }
        }
        if ($scriptDir !== '/' && $scriptDir !== '\\' && $scriptDir !== '.') {
            return rtrim($scriptDir, '/');
        }
    }
    
    if (isset($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']);
        $projRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
        
        $docRoot  = rtrim($docRoot, '/');
        $projRoot = rtrim($projRoot, '/');
        
        if ($docRoot !== '' && stripos($projRoot, $docRoot) === 0) {
            $sub = substr($projRoot, strlen($docRoot));
            return rtrim($sub, '/');
        }
    }

    // Fallback: project folder name relative to web root
    $projDir = basename(dirname(__DIR__));
    if ($projDir && !in_array(strtolower($projDir), ['htdocs', 'html', 'public_html', 'www'])) {
        return '/' . $projDir;
    }
    
    return '';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', detect_base_url());
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', str_replace('\\', '/', dirname(__DIR__)));
}

// Barangay Details & Manuscript System Title
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Smart Barangay Profiling & Social Services System');
}
if (!defined('APP_SYSTEM_TITLE')) {
    define('APP_SYSTEM_TITLE', 'Smart Barangay Socio Economic Profiling and Social Services Allocation System');
}
if (!defined('BRGY_NAME')) {
    define('BRGY_NAME', 'Barangay Lublub');
}
if (!defined('BRGY_SUBTITLE')) {
    define('BRGY_SUBTITLE', 'P.D. Monfort North, Dumangas, Iloilo');
}
if (!defined('MUNICIPALITY')) {
    define('MUNICIPALITY', 'Dumangas');
}
if (!defined('PROVINCE')) {
    define('PROVINCE', 'Iloilo');
}
if (!defined('ZIP_CODE')) {
    define('ZIP_CODE', '5006');
}

// 7 Sitios of Brgy Lublub
const SITIOS_LIST = [
    1 => 'Sitio Proper',
    2 => 'Sitio Centro',
    3 => 'Sitio Weapons',
    4 => 'Sitio Ilaya Facoma',
    5 => 'Sitio Sabang',
    6 => 'Sitio Subdivision',
    7 => 'Sitio Lanag'
];

/**
 * Universal URL builder & dynamic front-controller route generator
 */
function url($path = '') {
    $path = trim($path, '/');
    $base = rtrim(BASE_URL, '/');

    // 1. Root / homepage
    if ($path === '' || $path === 'index.php') {
        return $base === '' ? 'index.php' : $base . '/';
    }

    // 2. Direct static assets, uploads, and installers
    if (str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/') || str_starts_with($path, 'install.php')) {
        return ($base === '' ? '' : $base . '/') . $path;
    }

    // 3. If already a query route (?page=...), prefix base index.php
    if (str_starts_with($path, '?page=') || str_starts_with($path, 'index.php?page=') || str_starts_with($path, '?')) {
        $clean = ltrim($path, 'index.php');
        return ($base === '' ? 'index.php' : $base . '/index.php') . $clean;
    }

    // 4. Map legacy modules/... paths to dynamic front-controller route
    if (str_starts_with($path, 'modules/')) {
        $rel = substr($path, strlen('modules/'));
        $query = '';
        if (str_contains($rel, '?')) {
            list($rel, $query) = explode('?', $rel, 2);
        }
        $rel = preg_replace('/\.php$/', '', $rel);
        $rel = preg_replace('/\/index$/', '', $rel);

        $routeParam = 'index.php?page=' . $rel;
        if ($query !== '') {
            $routeParam .= '&' . $query;
        }
        return ($base === '' ? '' : $base . '/') . $routeParam;
    }

    // 5. Clean page names (e.g. 'dashboard', 'sitios', 'residents', 'residents/view?id=1', 'auth/login')
    $query = '';
    if (str_contains($path, '?')) {
        list($path, $query) = explode('?', $path, 2);
    }
    $path = preg_replace('/\.php$/', '', $path);
    $path = preg_replace('/\/index$/', '', $path);
    $routeParam = 'index.php?page=' . $path;
    if ($query !== '') {
        $routeParam .= '&' . $query;
    }
    return ($base === '' ? '' : $base . '/') . $routeParam;
}

/**
 * Asset URL builder
 * Handles static assets (assets/css, assets/js) and user uploads (uploads/...)
 */
function asset($path = '') {
    $cleanPath = ltrim($path ?? '', '/');
    if ($cleanPath === '') {
        return url('assets/');
    }
    // If the path already points to uploads/ or assets/, do not double-prefix
    if (str_starts_with($cleanPath, 'uploads/') || str_starts_with($cleanPath, 'assets/')) {
        return url($cleanPath);
    }
    return url('assets/' . $cleanPath);
}

/**
 * Upload URL builder helper
 */
function upload_url($path = '') {
    if (empty($path)) return '';
    $cleanPath = ltrim($path, '/');
    if (str_starts_with($cleanPath, 'http://') || str_starts_with($cleanPath, 'https://')) {
        return $cleanPath;
    }
    if (!str_starts_with($cleanPath, 'uploads/')) {
        $cleanPath = 'uploads/' . $cleanPath;
    }
    return url($cleanPath);
}

/**
 * Clean & sanitize user input
 */
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message setter
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Flash message getter
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash alert component
 */
function render_flash() {
    $flash = get_flash();
    if (!$flash) return '';
    
    switch ($flash['type']) {
        case 'success':
            $icon = 'bi-check-circle-fill';
            break;
        case 'danger':
            $icon = 'bi-exclamation-triangle-fill';
            break;
        case 'warning':
            $icon = 'bi-exclamation-circle-fill';
            break;
        default:
            $icon = 'bi-info-circle-fill';
            break;
    }

    return '
    <div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
        <i class="bi ' . $icon . ' me-2 fs-5"></i>
        <div>' . $flash['message'] . '</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

/**
 * Redirect helper (header redirect with safe JavaScript/Meta fallback if headers were already sent)
 */
function redirect($path) {
    $targetUrl = url($path);
    if (!headers_sent()) {
        header("Location: " . $targetUrl);
        exit;
    }
    echo '<script>window.location.href = ' . json_encode($targetUrl) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($targetUrl) . '"></noscript>';
    exit;
}

/**
 * Current authenticated user
 */
function auth_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Is user logged in
 */
function is_logged_in() {
    return !empty($_SESSION['user']['id']);
}

/**
 * Check if logged-in user has specific role(s)
 */
function has_role(...$roles) {
    if (!is_logged_in()) return false;
    return in_array($_SESSION['user']['role'], $roles, true);
}

/**
 * Guard role - redirects to login or unauthorized page if not permitted
 */
function require_role(...$roles) {
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect('auth/login');
    }
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        set_flash('danger', 'Access denied. You do not have permission to view that page.');
        $currentPage = $_GET['page'] ?? '';
        if ($currentPage === 'dashboard') {
            http_response_code(403);
            die('<!DOCTYPE html><html><head><title>403 Access Denied</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center vh-100 text-center"><div class="p-5 bg-white shadow rounded-4"><h1 class="display-4 text-danger mb-3">403 Forbidden</h1><p class="lead">Your account does not have permission to access this resource.</p><a href="' . url('auth/logout') . '" class="btn btn-outline-danger me-2">Log Out</a><a href="' . url('home') . '" class="btn btn-secondary">Home</a></div></body></html>');
        }
        redirect('dashboard');
    }
}

/**
 * Format currency in Philippine Peso
 */
function format_peso($amount) {
    return '₱' . number_format((float)$amount, 2);
}

/**
 * Format date nicely
 */
function format_date($date, $format = 'M d, Y') {
    if (!$date || $date === '0000-00-00') return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format date and time
 */
function format_datetime($datetime, $format = 'M d, Y h:i A') {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') return 'N/A';
    return date($format, strtotime($datetime));
}

/**
 * Calculate age based on birthdate
 */
function calculate_age($birthdate) {
    if (!$birthdate) return 0;
    $dob = new DateTime($birthdate);
    $now = new DateTime();
    return $now->diff($dob)->y;
}

/**
 * Badge for resident / application status
 */
function status_badge($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'approved':
        case 'active':
        case 'completed':
        case 'released':
        case 'claimed':
            return '<span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>' . ucfirst($status) . '</span>';
        case 'pending':
        case 'enrolled':
        case 'ongoing':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1"><i class="bi bi-clock-history me-1"></i>' . ucfirst($status) . '</span>';
        case 'endorsed':
            return '<span class="badge bg-info-subtle text-info border border-info px-2 py-1"><i class="bi bi-hand-thumbs-up me-1"></i>Endorsed</span>';
        case 'rejected':
        case 'cancelled':
        case 'suspended':
            return '<span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1"><i class="bi bi-x-circle me-1"></i>' . ucfirst($status) . '</span>';
        case 'archived':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1"><i class="bi bi-archive me-1"></i>Archived</span>';
        default:
            return '<span class="badge bg-secondary px-2 py-1">' . ucfirst($status) . '</span>';
    }
}

/**
 * Log activity to DB
 */
function log_activity($action, $details = '') {
    try {
        $db = getDB();
        $userId = $_SESSION['user']['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // Silently ignore log errors to avoid blocking main workflow
    }
}

/**
 * Safe File Upload Helper for ID and Welfare Attachments
 * Handles single file upload from $_FILES[$fileKey]
 * Returns relative path e.g. "uploads/documents/filename.jpg" or null if not uploaded/failed
 */
function upload_attachment($fileKey, $subFolder = 'documents', $prefix = 'doc_') {
    if (empty($_FILES[$fileKey]['name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$fileKey];
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        return null;
    }
    $targetDir = BASE_PATH . '/uploads/' . trim($subFolder, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $cleanPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);
    $fileName = $cleanPrefix . uniqid() . '_' . time() . '.' . $ext;
    $targetPath = $targetDir . '/' . $fileName;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/' . trim($subFolder, '/') . '/' . $fileName;
    }
    return null;
}

/**
 * Generate a guaranteed collision-free, sequential Resident Code: LUB-YYYY-XXXX
 * - Accurately finds the true maximum sequence number across all existing codes for the given year
 * - Verifies uniqueness in a loop to guarantee zero duplicate keys
 */
function generate_resident_code($db = null, $year = null) {
    if (!$db) {
        $db = getDB();
    }
    $targetYear = $year ? (int)$year : (int)date('Y');
    $prefix = 'LUB-' . $targetYear . '-';

    // Query highest numerical suffix currently in use for this year
    // 'LUB-YYYY-' has length 9, so SUBSTRING(resident_code, 10) extracts 'XXXX'
    $stmt = $db->prepare("
        SELECT MAX(CAST(SUBSTRING(resident_code, 10) AS UNSIGNED)) 
        FROM residents 
        WHERE resident_code LIKE ?
    ");
    $stmt->execute([$prefix . '%']);
    $maxSeqFromCode = (int)$stmt->fetchColumn();

    $nextSeq = max($maxSeqFromCode + 1, 1);

    // Collision check loop
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM residents WHERE resident_code = ?");
    do {
        $candidateCode = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        $checkStmt->execute([$candidateCode]);
        $exists = ((int)$checkStmt->fetchColumn() > 0);
        if ($exists) {
            $nextSeq++;
        }
    } while ($exists);

    return $candidateCode;
}

/**
 * Generate a guaranteed collision-free, sequential Household Number: HH-LUB-YYYY-XXXX
 */
function generate_household_no($db = null, $year = null) {
    if (!$db) {
        $db = getDB();
    }
    $targetYear = $year ? (int)$year : (int)date('Y');
    $prefix = 'HH-LUB-' . $targetYear . '-';

    // 'HH-LUB-YYYY-' has length 12, so SUBSTRING(household_no, 13) extracts 'XXXX'
    $stmt = $db->prepare("
        SELECT MAX(CAST(SUBSTRING(household_no, 13) AS UNSIGNED)) 
        FROM households 
        WHERE household_no LIKE ?
    ");
    $stmt->execute([$prefix . '%']);
    $maxSeqFromHh = (int)$stmt->fetchColumn();

    $nextSeq = max($maxSeqFromHh + 1, 1);

    $checkStmt = $db->prepare("SELECT COUNT(*) FROM households WHERE household_no = ?");
    do {
        $candidateNo = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        $checkStmt->execute([$candidateNo]);
        $exists = ((int)$checkStmt->fetchColumn() > 0);
        if ($exists) {
            $nextSeq++;
        }
    } while ($exists);

    return $candidateNo;
}

// Centralized Approvals & Chart of Authority System
require_once __DIR__ . '/../includes/approvals.php';



