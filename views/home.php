<?php
// pages/home.php - Public Homepage & Resident Portal for Barangay Lublub
require_once __DIR__ . '/../config/app.php';

$db = getDB();

// Fetch summary metrics for public display
$totalResidents = (int)$db->query("SELECT COUNT(*) FROM residents WHERE status = 'approved'")->fetchColumn();
$totalPwds      = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_pwd = 1 AND status = 'approved'")->fetchColumn();
$totalSolo      = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_solo_parent = 1 AND status = 'approved'")->fetchColumn();
$totalSeniors   = (int)$db->query("SELECT COUNT(*) FROM residents WHERE is_senior = 1 AND status = 'approved'")->fetchColumn();

// Fetch Sitios with Council Sponsors and BHWs
$sitios = $db->query("
    SELECT s.*, o.official_name AS council_sponsor_name, o.committee, ub.full_name AS bhw_name,
           COUNT(CASE WHEN r.status = 'approved' THEN 1 END) AS resident_count
    FROM sitios s
    LEFT JOIN officials o ON s.id = o.sitio_id
    LEFT JOIN users ub ON s.id = ub.assigned_sitio_id AND ub.role = 'bhw'
    LEFT JOIN residents r ON s.id = r.sitio_id
    GROUP BY s.id, o.official_name, o.committee, ub.full_name
    ORDER BY s.id ASC
")->fetchAll();

// Fetch Active Programs
$programs = $db->query("
    SELECT * FROM assistance_programs 
    WHERE status = 'Ongoing' 
    ORDER BY distribution_date DESC LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BRGY_NAME ?> | <?= BRGY_SUBTITLE ?> - Social Services & Resident Portal</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        .hero-banner {
            background: linear-gradient(135deg, #052e18 0%, #0d6338 60%, #15803d 100%);
            color: #ffffff;
            padding: 90px 0 80px;
            position: relative;
            overflow: hidden;
        }
        .hero-pattern {
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
        }
        .sitio-card {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            border-top: 4px solid #10b981;
        }
        .sitio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top border-bottom border-success border-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url() ?>">
            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; font-size: 1.1rem; border: 2px solid #34d399;">
                BL
            </div>
            <div>
                <strong class="d-block lh-1 text-white"><?= BRGY_NAME ?></strong>
                <small class="text-white-50" style="font-size: 0.72rem;"><?= BRGY_SUBTITLE ?></small>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">
                <li class="nav-item"><a class="nav-link text-white active" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link text-white-50" href="#sitios">The 7 Sitios</a></li>
                <li class="nav-item"><a class="nav-link text-white-50" href="#social-services">Social Services</a></li>
                <li class="nav-item"><a class="nav-link text-white-50" href="#officials">Barangay Council</a></li>

                <?php if (is_logged_in()): ?>
                    <li class="nav-item ms-lg-2">
                        <a href="<?= url('dashboard') ?>" class="btn btn-warning text-dark fw-bold px-3">
                            <i class="bi bi-grid-fill me-1"></i> My Dashboard
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a href="<?= url('auth/login') ?>" class="btn btn-outline-light px-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= url('auth/signup') ?>" class="btn btn-success fw-bold px-3 shadow-sm">
                            <i class="bi bi-person-plus-fill me-1"></i> Resident Sign Up
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero-banner" id="home">
    <div class="hero-pattern"></div>
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3 shadow-sm">
                    <i class="bi bi-shield-heart-fill me-1"></i> OFFICIAL SOCIAL SERVICES & PROFILING PORTAL
                </span>
                <h1 class="display-4 fw-bold lh-sm mb-3">
                    Barangay Lublub <br>
                    <span class="text-emerald-300" style="color: #6ee7b7;">(P.D. Monfort North)</span>
                </h1>
                <p class="lead text-white-50 mb-4 fs-5">
                    Municipality of Dumangas, Province of Iloilo. Dedicated to targeted social welfare, inclusive resident profiling, fast assistance distribution, and transparent community governance across all 7 Sitios.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= url('auth/signup') ?>" class="btn btn-warning btn-lg fw-bold text-dark px-4 py-3 shadow">
                        <i class="bi bi-pencil-square me-2"></i> Register as Resident
                    </a>
                    <a href="<?= url('auth/login') ?>" class="btn btn-outline-light btn-lg px-4 py-3">
                        <i class="bi bi-shield-lock me-2"></i> Official & Resident Login
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- Live Profiling Counter Card -->
                <div class="card bg-white text-dark shadow-lg border-0 rounded-4 p-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <i class="bi bi-bar-chart-fill text-success fs-4"></i>
                        <h5 class="fw-bold mb-0">Social Welfare Profiling</h5>
                    </div>

                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="small text-muted d-block">Verified Residents</span>
                                <h3 class="fw-bold text-success mb-0"><?= number_format($totalResidents) ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="small text-primary d-block">PWD Citizens</span>
                                <h3 class="fw-bold text-primary mb-0"><?= number_format($totalPwds) ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="small text-danger d-block">Solo Mothers</span>
                                <h3 class="fw-bold text-danger mb-0"><?= number_format($totalSolo) ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="small text-info-emphasis d-block">Senior Citizens</span>
                                <h3 class="fw-bold text-info-emphasis mb-0"><?= number_format($totalSeniors) ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-center small text-muted">
                        <i class="bi bi-check-circle-fill text-success me-1"></i> Dedicated Barangay Health Workers across 7 Sitios
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Quick Action Highlights -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="rounded-circle bg-success text-white d-inline-flex p-3 mb-3" style="width: 60px; height: 60px; font-size: 1.6rem; align-items: center; justify-content: center;">
                        <i class="bi bi-person-vcard"></i>
                    </div>
                    <h5 class="fw-bold">1. Online Resident Signup</h5>
                    <p class="text-muted small">Select your Sitio from the 7 territorial zones of Barangay Lublub. Fill in your household and vulnerability data.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="rounded-circle bg-warning text-dark d-inline-flex p-3 mb-3" style="width: 60px; height: 60px; font-size: 1.6rem; align-items: center; justify-content: center;">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <h5 class="fw-bold">2. Barangay Secretary Verification</h5>
                    <p class="text-muted small">The Barangay Secretary inspects and approves your registration to grant verified resident status.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100">
                    <div class="rounded-circle bg-danger text-white d-inline-flex p-3 mb-3" style="width: 60px; height: 60px; font-size: 1.6rem; align-items: center; justify-content: center;">
                        <i class="bi bi-box2-heart-fill"></i>
                    </div>
                    <h5 class="fw-bold">3. Social Welfare & Clearances</h5>
                    <p class="text-muted small">Access financial aid, rice subsidies, medical assistance, Certificate of Indigency, and Barangay Clearance seamlessly.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- THE 7 SITIOS SECTION -->
<section class="py-5" id="sitios">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold mb-2">
                TERRITORIAL JURISDICTION
            </span>
            <h2 class="fw-bold text-dark">The 7 Sitios of Barangay Lublub</h2>
            <p class="text-muted">Each Sitio is supported by dedicated Barangay Health Workers (BHWs) and Council Sponsors who assist community operations and aid distributions.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($sitios as $s): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden sitio-card bg-white">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-success rounded-pill px-3 py-1">Sitio <?= $s['id'] ?></span>
                                <span class="text-muted small fw-bold font-monospace"><?= htmlspecialchars($s['code']) ?></span>
                            </div>

                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($s['name']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($s['description']) ?></p>

                            <div class="p-3 rounded-3 bg-light border border-success border-opacity-25 mb-3">
                                <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Council Sponsor:</small>
                                <strong class="text-success d-block"><?= htmlspecialchars($s['council_sponsor_name'] ?? 'Barangay Council') ?></strong>
                                <small class="text-muted d-block mb-2" style="font-size: 0.72rem;"><?= htmlspecialchars($s['committee'] ?? 'Council Committee') ?></small>

                                <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Barangay Health Worker (BHW):</small>
                                <span class="text-dark fw-semibold small d-block"><i class="bi bi-heart-pulse-fill text-danger me-1"></i><?= htmlspecialchars($s['bhw_name'] ?? 'Assigned Health Worker') ?></span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center small text-muted">
                                <span>Verified Residents:</span>
                                <strong class="text-dark fs-6"><?= $s['resident_count'] ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SOCIAL SERVICES & AYUDA TRANSPARENCY SECTION -->
<section class="py-5 bg-white border-top border-bottom" id="social-services">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-bold mb-2">
                SOCIAL PROTECTION & WELFARE
            </span>
            <h2 class="fw-bold text-dark">Active Assistance & Relief Programs</h2>
            <p class="text-muted">Barangay Lublub implements prioritized social assistance for PWDs, Solo Mothers, Low-Income Families, and Senior Citizens.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($programs as $prog): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden bg-light">
                        <div class="card-body p-4">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-2"><?= htmlspecialchars($prog['category']) ?></span>
                            <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($prog['title']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($prog['description']) ?></p>

                            <div class="p-3 rounded-3 bg-white border mb-3">
                                <div class="text-muted small">Standard Grant / Relief Value:</div>
                                <div class="h4 fw-bold text-success mb-0"><?= format_peso($prog['amount_per_beneficiary']) ?></div>
                                <?php if ($prog['in_kind_details']): ?>
                                    <small class="text-muted d-block mt-1">+ <?= htmlspecialchars($prog['in_kind_details']) ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="small text-muted">
                                <div><i class="bi bi-calendar-event me-1"></i> Distribution: <strong><?= format_date($prog['distribution_date']) ?></strong></div>
                                <div><i class="bi bi-bank me-1"></i> Fund: <?= htmlspecialchars($prog['fund_source']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BARANGAY OFFICIALS SECTION -->
<section class="py-5" id="officials">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold mb-2">
                LEADERSHIP & GOVERNANCE
            </span>
            <h2 class="fw-bold text-dark">Sangguniang Barangay Council</h2>
            <p class="text-muted">Serving the people of Barangay Lublub (P.D. Monfort North), Municipality of Dumangas.</p>
        </div>

        <div class="card p-4 shadow-sm border-0 rounded-4 bg-white">
            <div class="row g-4 align-items-center">
                <div class="col-md-4 text-center border-end">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center shadow mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                        ER
                    </div>
                    <h4 class="fw-bold text-dark mb-0">HON. ELISEO RODRIGUEZ</h4>
                    <span class="badge bg-success px-3 py-1 mt-1">Punong Barangay (Captain)</span>
                    <p class="text-muted small mt-2 mb-0">Executive Head & Administrator</p>
                </div>
                <div class="col-md-8">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-shield-check me-1"></i>Barangay Executive Leadership:</h6>
                    <div class="row g-3 small">
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <strong class="d-block text-dark fs-6">Ms. Annielor Gulbique</strong>
                                <span class="badge bg-primary mt-1">Barangay Secretary</span>
                                <div class="text-muted small mt-1">Administrative Affairs & Resident Records</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <strong class="d-block text-dark fs-6">Ms. Joy Valiente</strong>
                                <span class="badge bg-info text-dark mt-1">Barangay Treasurer</span>
                                <div class="text-muted small mt-1">Ayuda Distribution & Financial Services</div>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="p-2 px-3 rounded-3 bg-success bg-opacity-10 text-success border border-success-subtle d-flex align-items-center gap-2">
                                <i class="bi bi-geo-alt-fill fs-5"></i>
                                <span><strong>7 Territorial Sitios:</strong> Sitio Proper &bull; Sitio Centro &bull; Sitio Weapons &bull; Sitio Ilaya Facoma &bull; Sitio Sabang &bull; Sitio Subdivision &bull; Sitio Lanag</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-5 border-top border-success border-4">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success rounded-circle p-2 fs-5">BL</span>
                    <h5 class="fw-bold text-white mb-0"><?= BRGY_NAME ?></h5>
                </div>
                <p class="text-white-50 small mb-2"><?= BRGY_SUBTITLE ?>, Municipality of Dumangas, Province of Iloilo &bull; ZIP 5006</p>
                <p class="text-white-50 small mb-0">Barangay Facility & Social Services Management System (BFMS)</p>
            </div>
            <div class="col-lg-3">
                <h6 class="fw-bold text-success text-uppercase mb-3">Emergency Hotlines</h6>
                <ul class="list-unstyled small text-white-50">
                    <li><i class="bi bi-telephone-fill text-success me-2"></i>Barangay Hall: (033) 361-2045</li>
                    <li><i class="bi bi-shield-fill text-primary me-2"></i>PNP Dumangas: 0998-598-6211</li>
                    <li><i class="bi bi-fire text-danger me-2"></i>BFP Dumangas: 0917-889-1234</li>
                    <li><i class="bi bi-hospital-fill text-info me-2"></i>Rural Health Unit: (033) 361-2201</li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-bold text-success text-uppercase mb-3">Online Citizen Access</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="<?= url('auth/signup') ?>" class="btn btn-sm btn-success fw-bold">
                        <i class="bi bi-person-plus me-1"></i> Register as Resident
                    </a>
                    <a href="<?= url('auth/login') ?>" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
                    </a>
                </div>
            </div>
        </div>
        <div class="border-top border-secondary pt-3 text-center text-white-50 small">
            &copy; <?= date('Y') ?> Barangay Lublub (P.D. Monfort North), Dumangas, Iloilo. All Rights Reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

