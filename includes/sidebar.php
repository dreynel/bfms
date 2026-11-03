<?php
// includes/sidebar.php - Dynamic Navigation Sidebar
require_once __DIR__ . '/../config/app.php';

$user = auth_user();
$role = $user['role'] ?? 'resident';
$sitioId = $user['assigned_sitio_id'] ?? null;

// Calculate pending approvals count for badge
$pendingCount = 0;
try {
    $db = getDB();
    if (in_array($role, ['captain', 'secretary'])) {
        $stmt = $db->query("SELECT COUNT(*) FROM residents WHERE status = 'pending'");
        $pendingCount = (int)$stmt->fetchColumn();
    }
} catch (Exception $e) {
    $pendingCount = 0;
}

$currentPage = $_GET['page'] ?? 'dashboard';
if (!function_exists('isPageActive')) {
    function isPageActive(...$pages) {
        global $currentPage;
        foreach ($pages as $p) {
            if ($currentPage === $p || str_starts_with($currentPage, $p . '/')) {
                return 'active';
            }
        }
        return '';
    }
}
?>

<aside class="sidebar d-flex flex-column" id="appSidebar">
    <!-- Brand Box -->
    <div class="brand-box d-flex align-items-center justify-content-between">
        <a href="<?= url('dashboard') ?>" class="text-decoration-none text-white d-flex align-items-center">
            <div class="me-2 bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-weight: 800; font-size: 1.1rem; border: 2px solid #34d399;">
                BL
            </div>
            <div>
                <div class="fw-bold fs-6 lh-1">BRGY LUBLUB</div>
                <small class="text-emerald-400 text-white-50" style="font-size: 0.68rem; letter-spacing: 0.05em;">P.D. MONFORT NORTH</small>
            </div>
        </a>
        <button class="btn btn-sm btn-outline-light d-lg-none" id="sidebarCloseBtn">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Assigned Sitio Badge for BHWs -->
    <?php if ($role === 'bhw' && $sitioId && isset(SITIOS_LIST[$sitioId])): ?>
        <div class="mx-3 mt-3 p-2 rounded bg-success bg-opacity-25 border border-success border-opacity-50">
            <div class="text-warning small fw-bold"><i class="bi bi-geo-alt-fill me-1"></i>ASSIGNED SITIO (BHW)</div>
            <div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars(SITIOS_LIST[$sitioId]) ?></div>
        </div>
    <?php endif; ?>

    <!-- Navigation Links -->
    <nav class="flex-grow-1 overflow-y-auto py-3">
        <div class="nav-heading">Main Navigation</div>

        <a href="<?= url('dashboard') ?>" class="nav-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <!-- CENTRALIZED APPROVALS HUB (Captain & Secretary) -->
        <?php if (in_array($role, ['captain', 'secretary'])): ?>
            <?php 
                $pendingApprovalsBadge = get_pending_approvals_count_for_role($role); 
            ?>
            <a href="<?= url('approvals') ?>" class="nav-link <?= (in_array($currentPage, ['approvals', 'approvals/list', 'residents/approvals'])) ? 'active' : '' ?> position-relative">
                <i class="bi bi-shield-check text-warning"></i>
                <span>Approvals Hub</span>
                <?php if ($pendingApprovalsBadge > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto animate-pulse"><?= $pendingApprovalsBadge ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <!-- CAPTAIN / ADMIN REPORTS (Ayuda, Programs, Residents, Issued Certificates) -->
        <?php if ($role === 'captain'): ?>
            <div class="nav-heading">Reports & Records</div>

            <a href="<?= url('reports/distribution') ?>" class="nav-link <?= (in_array($currentPage, ['reports/distribution', 'reports/distribution_report', 'reports/print'])) ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i>
                <span>Ayuda Distribution</span>
            </a>

            <a href="<?= url('social_services') ?>" class="nav-link <?= (in_array($currentPage, ['social_services', 'social_services/index', 'social_services/view'])) ? 'active' : '' ?>">
                <i class="bi bi-box2-heart-fill text-danger"></i>
                <span>Assistance Programs</span>
            </a>

            <a href="<?= url('residents') ?>" class="nav-link <?= (in_array($currentPage, ['residents', 'residents/index', 'residents/view'])) ? 'active' : '' ?>">
                <i class="bi bi-people-fill text-primary"></i>
                <span>Resident Masterlist</span>
            </a>

            <a href="<?= url('certificates') ?>" class="nav-link <?= (in_array($currentPage, ['certificates', 'certificates/index'])) ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-medical-fill text-warning"></i>
                <span>Issued Certificates</span>
            </a>
        <?php endif; ?>

        <!-- RESIDENT PROFILING (BHW Only) -->
        <?php if ($role === 'bhw'): ?>
            <div class="nav-heading">Resident & Household Profiling</div>

            <a href="<?= url('residents') ?>" class="nav-link <?= (in_array($currentPage, ['residents', 'residents/index', 'residents/view', 'residents/edit'])) ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>My Sitio Residents</span>
            </a>

            <a href="<?= url('households') ?>" class="nav-link <?= (in_array($currentPage, ['households', 'households/list', 'households/view', 'households/edit', 'households/add'])) ? 'active' : '' ?>">
                <i class="bi bi-houses-fill text-warning"></i>
                <span>Households & BHW Docs</span>
            </a>

            <a href="<?= url('residents/add') ?>" class="nav-link <?= ($currentPage === 'residents/add') ? 'active' : '' ?>">
                <i class="bi bi-person-plus-fill"></i>
                <span>Register Resident</span>
            </a>

            <a href="<?= url('reports/demographic') ?>" class="nav-link <?= (in_array($currentPage, ['reports/demographic', 'reports/demographic_report'])) ? 'active' : '' ?>">
                <i class="bi bi-pie-chart-fill"></i>
                <span>Vulnerability Profiling</span>
            </a>
        <?php endif; ?>

        <!-- RESIDENT REGISTRY (Secretary Only - Masterlist View) -->
        <?php if ($role === 'secretary'): ?>
            <div class="nav-heading">Registry & Records</div>
            <a href="<?= url('residents') ?>" class="nav-link <?= (in_array($currentPage, ['residents', 'residents/index', 'residents/view'])) ? 'active' : '' ?>">
                <i class="bi bi-people-fill text-primary"></i>
                <span>Resident Masterlist</span>
            </a>
        <?php endif; ?>

        <!-- SOCIAL SERVICES & AYUDA (Secretary Only) -->
        <?php if ($role === 'secretary'): ?>
            <div class="nav-heading">Social Welfare & Aid</div>
            <a href="<?= url('social_services') ?>" class="nav-link <?= (in_array($currentPage, ['social_services', 'social_services/index', 'social_services/create', 'social_services/view'])) ? 'active' : '' ?>">
                <i class="bi bi-box2-heart-fill text-danger"></i>
                <span>Assistance Programs</span>
            </a>
        <?php endif; ?>

        <!-- TREASURY & AYUDA DISTRIBUTION (Treasurer Only) -->
        <?php if ($role === 'treasurer'): ?>
            <div class="nav-heading">Treasury & Ayuda</div>

            <a href="<?= url('social_services/distribute') ?>" class="nav-link <?= ($currentPage === 'social_services/distribute') ? 'active' : '' ?>">
                <i class="bi bi-hand-index-thumb-fill text-info"></i>
                <span>Ayuda Distribution Desk</span>
            </a>

            <a href="<?= url('reports/distribution') ?>" class="nav-link <?= (in_array($currentPage, ['reports/distribution', 'reports/print'])) ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i>
                <span>Reports of Distribution</span>
            </a>
        <?php endif; ?>

        <!-- CERTIFICATIONS & CLEARANCES (Secretary & Resident Only) -->
        <?php if (in_array($role, ['secretary', 'resident'])): ?>
            <div class="nav-heading">Certificates & Clearances</div>

            <a href="<?= url('certificates') ?>" class="nav-link <?= (in_array($currentPage, ['certificates', 'certificates/index'])) ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-medical-fill"></i>
                <span>Certificates & Indigency</span>
            </a>
        <?php endif; ?>

        <!-- 7 SITIOS MANAGEMENT (Secretary Only) -->
        <?php if ($role === 'secretary'): ?>
            <div class="nav-heading">Territory & Sitios</div>

            <a href="<?= url('sitios') ?>" class="nav-link <?= ($currentPage === 'sitios') ? 'active' : '' ?>">
                <i class="bi bi-geo-alt-fill text-success"></i>
                <span>7 Sitios Directory</span>
            </a>
        <?php endif; ?>

        <!-- ADMINISTRATION (Secretary Only) -->
        <?php if ($role === 'secretary'): ?>
            <div class="nav-heading">Administration</div>

            <a href="<?= url('officials') ?>" class="nav-link <?= ($currentPage === 'officials') ? 'active' : '' ?>">
                <i class="bi bi-person-badge-fill"></i>
                <span>Barangay Officials</span>
            </a>

            <a href="<?= url('reports/profiling') ?>" class="nav-link <?= (in_array($currentPage, ['reports/profiling', 'reports/profiling_summary'])) ? 'active' : '' ?>">
                <i class="bi bi-printer-fill"></i>
                <span>Official Reports</span>
            </a>
        <?php endif; ?>

        <!-- RESIDENT PORTAL LINKS -->
        <?php if ($role === 'resident'): ?>
            <div class="nav-heading">My Account</div>

            <a href="<?= url('residents/my_profile') ?>" class="nav-link <?= ($currentPage === 'residents/my_profile') ? 'active' : '' ?>">
                <i class="bi bi-person-vcard-fill"></i>
                <span>My Resident ID</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- User Mini Profile Footer -->
    <div class="p-3 border-top border-secondary border-opacity-25 bg-black bg-opacity-20 d-flex align-items-center">
        <div class="me-2 rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: bold;">
            <?= strtoupper(substr($user['full_name'] ?? 'User', 0, 1)) ?>
        </div>
        <div class="flex-grow-1 overflow-hidden">
            <div class="text-white small fw-bold text-truncate"><?= htmlspecialchars($user['full_name'] ?? 'Guest') ?></div>
            <div class="text-white-50" style="font-size: 0.72rem;"><?= ucfirst($role) ?></div>
        </div>
        <a href="<?= url('auth/logout') ?>" class="btn btn-sm btn-link text-white-50 p-1" title="Log Out">
            <i class="bi bi-box-arrow-right fs-5"></i>
        </a>
    </div>
</aside>

