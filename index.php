<?php
// index.php - Front Controller & Main Application Container for BFMS
// Barangay Lublub (P.D. Monfort North, Dumangas, Iloilo)
require_once __DIR__ . '/config/app.php';

$db = getDB();

// 1. Determine requested route
$page = $_GET['page'] ?? '';

// Support quick_login anywhere: if ?quick_login is present, route to auth/login to authenticate
if (isset($_GET['quick_login']) && empty($page)) {
    $page = 'auth/login';
}

// Fallback to route if PATH_INFO or clean URI is passed
if (empty($page) && isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '/') {
    $page = trim($_SERVER['PATH_INFO'], '/');
}

// Fallback: If page is empty but resident filtering query parameters are present, route directly to residents
if (empty($page) && (isset($_GET['category']) || isset($_GET['tab']) || isset($_GET['search']))) {
    $page = 'residents';
}

// Default route
if (empty($page) || $page === 'home') {
    if (is_logged_in() && !isset($_GET['home'])) {
        $page = 'dashboard';
    } else {
        $page = 'home';
    }
}

// Clean and sanitize
$page = trim($page, '/');
if (str_starts_with($page, 'modules/')) {
    $page = substr($page, strlen('modules/'));
}
$page = preg_replace('/\.php$/i', '', $page);
$page = preg_replace('/\/index$/i', '', $page);
$page = str_replace(['..', '\\'], '', $page);

// 2. Central Route Registry
$routes = [
    // Core & Dashboards
    'home'                         => ['file' => 'views/home.php', 'layout' => 'standalone'],
    'dashboard'                    => ['file' => 'views/dashboard.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'bhw', 'resident']],
    'sitios'                       => ['file' => 'views/sitios.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary']],
    'officials'                    => ['file' => 'views/officials.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary']],

    // Authentication
    'auth/login'                   => ['file' => 'views/auth/login.php', 'layout' => 'standalone'],
    'auth/signup'                  => ['file' => 'views/auth/signup.php', 'layout' => 'standalone'],
    'auth/logout'                  => ['file' => 'views/auth/logout.php', 'layout' => 'standalone'],

    // Resident & Household Profiling (Profiling encoded & edited strictly by BHW)
    'residents'                    => ['file' => 'views/residents/list.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'bhw']],
    'residents/list'               => ['file' => 'views/residents/list.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'bhw']],
    'residents/view'               => ['file' => 'views/residents/view.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'bhw', 'resident']],
    'residents/add'                => ['file' => 'views/residents/add.php', 'layout' => 'portal', 'roles' => ['bhw']],
    'residents/edit'               => ['file' => 'views/residents/edit.php', 'layout' => 'portal', 'roles' => ['bhw']],
    'residents/approvals'          => ['file' => 'views/residents/approvals.php', 'layout' => 'portal', 'roles' => ['secretary']],
    'residents/id_card'            => ['file' => 'prints/resident_id_card.php', 'layout' => 'standalone'],
    'residents/my_profile'         => ['file' => 'views/residents/my_profile.php', 'layout' => 'portal', 'roles' => ['resident', 'captain', 'secretary', 'bhw']],

    // Households & BHW Documentation (Strictly BHW)
    'households'                   => ['file' => 'views/households/list.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'bhw']],
    'households/list'              => ['file' => 'views/households/list.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'bhw']],
    'households/add'               => ['file' => 'views/households/add.php', 'layout' => 'portal', 'roles' => ['bhw']],
    'households/view'              => ['file' => 'views/households/view.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'bhw', 'resident']],
    'households/edit'              => ['file' => 'views/households/edit.php', 'layout' => 'portal', 'roles' => ['bhw']],

    // Centralized Approvals & Chart of Authority
    'approvals'                    => ['file' => 'views/approvals/index.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary']],
    'approvals/list'               => ['file' => 'views/approvals/index.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary']],
    'approvals/action'             => ['file' => 'actions/approval_action.php', 'layout' => 'standalone', 'roles' => ['captain', 'secretary']],

    // Social Services & Ayuda
    'social_services'              => ['file' => 'views/social_services/list.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'resident']],
    'social_services/list'         => ['file' => 'views/social_services/list.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer', 'resident']],
    'social_services/create'       => ['file' => 'views/social_services/create.php', 'layout' => 'portal', 'roles' => ['secretary']],
    'social_services/view'         => ['file' => 'views/social_services/view.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer']],
    'social_services/distribute'   => ['file' => 'views/social_services/distribute.php', 'layout' => 'portal', 'roles' => ['treasurer']],

    // Certificates & Clearances
    'certificates'                 => ['file' => 'views/certificates/list.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'resident']],
    'certificates/list'            => ['file' => 'views/certificates/list.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'resident']],
    'certificates/print_clearance' => ['file' => 'prints/certificate_clearance.php', 'layout' => 'standalone'],
    'certificates/print_indigency' => ['file' => 'prints/certificate_indigency.php', 'layout' => 'standalone'],
    'certificates/print_residency' => ['file' => 'prints/certificate_residency.php', 'layout' => 'standalone'],

    // Reports & Audits
    'reports/distribution'         => ['file' => 'views/reports/distribution.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer']],
    'reports/distribution_report'  => ['file' => 'views/reports/distribution.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'treasurer']],
    'reports/demographic'          => ['file' => 'views/reports/demographic_report.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'bhw']],
    'reports/demographic_report'   => ['file' => 'views/reports/demographic_report.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary', 'bhw']],
    'reports/profiling'            => ['file' => 'views/reports/profiling_summary.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary']],
    'reports/profiling_summary'    => ['file' => 'views/reports/profiling_summary.php', 'layout' => 'portal', 'roles' => ['captain', 'secretary']],
    'reports/print'                => ['file' => 'prints/audit_payroll.php', 'layout' => 'standalone'],
    'reports/print_report'         => ['file' => 'prints/audit_payroll.php', 'layout' => 'standalone'],
];

// 3. Resolve Target Route
if (!isset($routes[$page])) {
    http_response_code(404);
    $pageTitle = 'Page Not Found';
    if (is_logged_in()) {
        require_once __DIR__ . '/components/header.php';
        echo '<div class="text-center py-5">
                <i class="bi bi-exclamation-triangle display-1 text-warning mb-3"></i>
                <h2 class="fw-bold">404 - Page Not Found</h2>
                <p class="text-muted">The requested page <code>' . htmlspecialchars($page) . '</code> was not found on this system.</p>
                <a href="' . url('dashboard') . '" class="btn btn-success mt-2"><i class="bi bi-house me-1"></i> Return to Dashboard</a>
              </div>';
        require_once __DIR__ . '/components/footer.php';
    } else {
        echo '<!DOCTYPE html><html><head><title>404 Not Found</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center vh-100 text-center"><div class="p-5 bg-white shadow rounded-4"><h1 class="display-3 text-warning">404</h1><p class="lead">Page Not Found</p><a href="' . url() . '" class="btn btn-success">Go to Homepage</a></div></body></html>';
    }
    exit;
}

$route = $routes[$page];
$targetFile = __DIR__ . '/' . $route['file'];

if (!file_exists($targetFile)) {
    http_response_code(500);
    die("Route view file not found: " . htmlspecialchars($route['file']));
}

// 4. Role & Permission Verification
if (isset($route['roles'])) {
    require_role(...$route['roles']);
}

// 5. Render Page View
if ($route['layout'] === 'portal') {
    // Buffer target file execution:
    // If the view executes form processing, redirects, or sets headers/session flash,
    // it executes cleanly before any HTML layout (header/sidebar/navbar) is emitted.
    ob_start();
    require $targetFile;
    $viewContent = ob_get_clean();

    // Render global portal container
    require_once __DIR__ . '/components/header.php';
    echo $viewContent;
    require_once __DIR__ . '/components/footer.php';
} else {
    // Render standalone views directly (Landing, Login, Signup, Printouts)
    require $targetFile;
}
