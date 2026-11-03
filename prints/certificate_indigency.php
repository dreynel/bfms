<?php
// certificates/print_indigency - Official Certificate of Indigency Printable
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary', 'resident');

$db = getDB();
$residentId = (int)($_GET['resident_id'] ?? 0);
$certNo     = clean($_GET['cert_no'] ?? ('IND-' . date('Y') . '-' . rand(100, 999)));

$stmt = $db->prepare("
    SELECT r.*, s.name AS sitio_name, s.code AS sitio_code
    FROM residents r
    JOIN sitios s ON r.sitio_id = s.id
    WHERE r.id = ?
");
$stmt->execute([$residentId]);
$resident = $stmt->fetch();

if (!$resident) {
    die("Resident record not found.");
}

$secName = $db->query("SELECT full_name FROM users WHERE role = 'secretary' LIMIT 1")->fetchColumn() ?: 'Ma. Theresa B. Alipao';

// Fetch Officials for Certificate Sidebar
$officials = $db->query("
    SELECT o.*, COALESCE(o.official_name, u.full_name) AS full_name, s.name AS sitio_name 
    FROM officials o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN sitios s ON o.sitio_id = s.id
    ORDER BY o.id ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Indigency - <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Times New Roman', Times, serif;
            color: #111827;
        }
        .cert-container {
            max-width: 820px;
            margin: 20px auto;
            background: #fff;
            padding: 35px 45px;
            border: 4px double #0d6338;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: relative;
        }
        .cert-header {
            text-align: center;
            border-bottom: 2px solid #0d6338;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .cert-header h4 {
            font-weight: 800;
            color: #064e3b;
            margin: 0;
            letter-spacing: 0.05em;
        }
        .cert-sidebar {
            border-right: 2px solid #0d6338;
            padding-right: 20px;
            font-size: 0.78rem;
            line-height: 1.3;
        }
        .cert-body {
            padding-left: 25px;
            font-size: 1.05rem;
            line-height: 1.7;
            text-align: justify;
        }
        .cert-title {
            text-align: center;
            font-size: 1.6rem;
            font-weight: 900;
            color: #0d6338;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin: 25px 0;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="no-print text-center py-3 bg-white border-bottom shadow-sm mb-4">
    <button onclick="window.print()" class="btn btn-success fw-bold px-4">
        <i class="bi bi-printer me-2"></i> Print Certificate of Indigency
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3 ms-2">Close</button>
</div>

<div class="cert-container">
    <!-- Header -->
    <div class="cert-header">
        <div style="font-size: 0.85rem; text-transform: uppercase;">Republic of the Philippines</div>
        <div style="font-size: 0.85rem;">Province of Iloilo &bull; Municipality of Dumangas</div>
        <h4>BARANGAY LUBLUB (P.D. MONFORT NORTH)</h4>
        <div style="font-size: 0.82rem; font-style: italic; color: #047857;">Office of the Sangguniang Barangay &bull; Social Welfare Desk</div>
    </div>

    <div class="row g-0">
        <!-- Left Sidebar: Barangay Council Directory -->
        <div class="col-4 cert-sidebar">
            <div class="text-center mb-3">
                <strong class="d-block text-success" style="font-size: 0.82rem; text-transform: uppercase;">Barangay Council (2023-2026)</strong>
                <strong class="d-block text-success" style="font-size: 0.82rem; text-transform: uppercase;">Barangay Executive Council</strong>
            </div>

            <div class="mb-2">
                <strong class="d-block text-dark">HON. RAFAEL M. MONFORT</strong>
                <small class="text-muted d-block">Punong Barangay</small>
            <div class="mb-3">
                <strong class="d-block text-dark">HON. ELISEO RODRIGUEZ</strong>
                <small class="text-muted d-block">Punong Barangay (Captain)</small>
            </div>

            <hr class="my-2">

            <div class="mb-2">
                <strong class="d-block">SANGGUNIANG KAGAWAD:</strong>
                <strong class="d-block text-dark">MS. ANNIELOR GULBIQUE</strong>
                <small class="text-muted d-block">Barangay Secretary</small>
            </div>

            <div class="mb-1">
                <strong>Hon. Eduardo P. Decolongon</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Sitio Centro &bull; Health & Social Services</div>
            </div>
            <div class="mb-1">
                <strong>Hon. Maria Fe L. Divinagracia</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Sitio Ilaya &bull; Agriculture & Fisheries</div>
            </div>
            <div class="mb-1">
                <strong>Hon. Roberto S. Celis</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Sitio Ilawod &bull; Infrastructure</div>
            </div>
            <div class="mb-1">
                <strong>Hon. Jocelyn D. Baldevia</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Sitio Tabucan &bull; Education</div>
            </div>
            <div class="mb-1">
                <strong>Hon. Arnel G. Dumalag</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Sitio Crossing &bull; Appropriation</div>
            </div>
            <div class="mb-1">
                <strong>Hon. Salvacion T. Labordo</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Sitio Malipayon &bull; Women & Family</div>
            </div>
            <div class="mb-2">
                <strong>Hon. Nelson B. Cordero</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Sitio Pag-asa &bull; Environment & DRRM</div>
                <strong class="d-block text-dark">MS. JOY VALIENTE</strong>
                <small class="text-muted d-block">Barangay Treasurer</small>
            </div>

            <hr class="my-2">
            <div class="mb-1">
                <strong>Ma. Theresa B. Alipao</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Barangay Secretary</div>
            </div>
            <div class="mb-1">
                <strong>Ronaldo C. Valencia</strong>
                <div class="text-muted" style="font-size: 0.7rem;">Barangay Treasurer</div>
            </div>
        </div>

        <!-- Right: Certificate Content -->
        <div class="col-8 cert-body">
            <div class="text-end text-muted small mb-2 font-monospace">Control No: <strong><?= htmlspecialchars($certNo) ?></strong></div>

            <div class="cert-title">CERTIFICATE OF INDIGENCY</div>

            <p class="fw-bold mb-4">TO WHOM IT MAY CONCERN:</p>

            <p style="text-indent: 2rem;">
                This is to certify that <strong><?= strtoupper(htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix'])) ?></strong>, 
                <?= $resident['age'] ?> years of age, <?= $resident['civil_status'] ?>, Filipino, is a bonafide resident of 
                <strong><?= htmlspecialchars($resident['address_line']) ?>, <?= htmlspecialchars($resident['sitio_name']) ?>, Barangay Lublub (P.D. Monfort North), Dumangas, Iloilo</strong>.
            </p>

            <p style="text-indent: 2rem;">
                Based on the official Social Welfare Demographics Profiling of this Barangay, the above-named resident and their family belong to the 
                <strong>INDIGENT / LOW-INCOME BRACKET</strong> 
                <?php if ($resident['is_pwd']): ?>
                    and is a verified <strong>Person with Disability (PWD: <?= htmlspecialchars($resident['pwd_type'] ?: 'Registered') ?>)</strong>
                <?php endif; ?>
                <?php if ($resident['is_solo_parent']): ?>
                    and a recognized <strong>Solo Parent / Mother supporting <?= $resident['dependent_children_count'] ?> dependent child(ren)</strong>
                <?php endif; ?>
                <?php if ($resident['is_senior']): ?>
                    and a certified <strong>Senior Citizen</strong>
                <?php endif; ?>
                with estimated monthly earnings of less than <strong><?= format_peso($resident['monthly_income'] ?: 4000) ?></strong> derived from <?= htmlspecialchars($resident['income_source'] ?: 'informal livelihood') ?>.
            </p>

            <p style="text-indent: 2rem;">
                This certification is hereby issued upon the request of the interested party for the purpose of:
                <br>
                <strong class="text-uppercase d-block text-center my-2 p-2 border bg-light" style="font-size: 1rem; letter-spacing: 0.05em;">
                    <?= htmlspecialchars($_GET['purpose'] ?? 'MEDICAL ASSISTANCE / PHILHEALTH / DSWD FINANCIAL AID') ?>
                </strong>
            </p>

            <p style="text-indent: 2rem;">
                Issued this <strong><?= date('jS') ?></strong> day of <strong><?= date('F, Y') ?></strong> at the Barangay Hall of Lublub (P.D. Monfort North), Municipality of Dumangas, Province of Iloilo, Philippines.
            </p>

            <div class="mt-5 pt-3">
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted d-block">Verified & Endorsed by:</small>
                        <div class="fw-bold mt-3 text-dark"><?= strtoupper(htmlspecialchars($secName)) ?></div>
                        <small class="text-muted d-block">Barangay Secretary</small>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-muted d-block">Approved & Signed by:</small>
                        <div class="fw-bold mt-3 text-dark">HON. RAFAEL M. MONFORT</div>
                        <small class="text-muted d-block">Punong Barangay</small>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center" style="font-size: 0.72rem; color: #64748b;">
                <span>Resident Code: <strong><?= htmlspecialchars($resident['resident_code']) ?></strong></span>
                <span>Barangay Dry Seal Affixed</span>
                <span>FREE &bull; Indigent Exemption</span>
            </div>
        </div>
    </div>
</div>

</body>
</html>

