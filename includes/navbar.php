<?php
// includes/navbar.php - Top Navigation Bar
require_once __DIR__ . '/../config/app.php';

$user = auth_user();
$role = $user['role'] ?? 'guest';
$sitioId = $user['assigned_sitio_id'] ?? null;
?>

<header class="top-navbar d-flex align-items-center justify-content-between">
    <!-- Left: Mobile Toggle & Barangay Info -->
    <div class="d-flex align-items-center">
        <button class="btn btn-outline-secondary btn-sm me-3 d-lg-none" id="sidebarToggleBtn" type="button" aria-label="Toggle navigation">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="d-none d-sm-block">
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle me-2">
                <i class="bi bi-geo-alt-fill me-1"></i><?= MUNICIPALITY ?>, <?= PROVINCE ?>
            </span>
            <span class="text-secondary small fw-medium">
                <?= BRGY_NAME ?> &bull; <span class="text-muted"><?= BRGY_SUBTITLE ?></span>
            </span>
        </div>
    </div>

    <!-- Right: Actions & User Dropdown -->
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url() ?>" target="_blank" class="btn btn-sm btn-outline-success d-none d-md-inline-flex align-items-center" title="Open Public Resident Portal">
            <i class="bi bi-box-arrow-up-right me-1"></i> Public Portal
        </a>

        <?php if ($role === 'bhw' && $sitioId && isset(SITIOS_LIST[$sitioId])): ?>
            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 small d-none d-md-inline-block">
                <i class="bi bi-heart-pulse-fill me-1"></i> BHW &bull; <?= htmlspecialchars(SITIOS_LIST[$sitioId]) ?>
            </span>
        <?php endif; ?>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.8rem; font-weight: bold;">
                    <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="d-none d-md-inline small fw-semibold"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li class="dropdown-header">
                    <div class="fw-bold"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                    <div class="badge bg-secondary-subtle text-secondary mt-1"><?= ucfirst($role) ?></div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php if ($role === 'resident'): ?>
                    <li><a class="dropdown-item" href="<?= url('residents/my_profile') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="<?= url('certificates') ?>"><i class="bi bi-file-earmark-text me-2"></i>Clearances & Aid</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= url('auth/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
            </ul>
        </div>
    </div>
</header>

