<?php
// certificates/print_residency - Official Certificate of Residency Printable
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary', 'resident');

$db = getDB();
$residentId = (int)($_GET['resident_id'] ?? 0);
$certNo     = clean($_GET['cert_no'] ?? ('RES-' . date('Y') . '-' . rand(100, 999)));

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Residency - <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @page { size: A4; margin: 10mm; }
        body { background-color: #f8fafc; font-family: 'Times New Roman', Times, serif; color: #111827; }
        .cert-container { max-width: 820px; margin: 20px auto; background: #fff; padding: 35px 45px; border: 4px double #0d6338; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .cert-header { text-align: center; border-bottom: 2px solid #0d6338; padding-bottom: 12px; margin-bottom: 20px; }
        .cert-header h4 { font-weight: 800; color: #064e3b; margin: 0; letter-spacing: 0.05em; }
        .cert-sidebar { border-right: 2px solid #0d6338; padding-right: 20px; font-size: 0.78rem; line-height: 1.3; }
        .cert-body { padding-left: 25px; font-size: 1.05rem; line-height: 1.7; text-align: justify; }
        .cert-title { text-align: center; font-size: 1.6rem; font-weight: 900; color: #0d6338; letter-spacing: 0.1em; text-transform: uppercase; margin: 25px 0; text-decoration: underline; }
    </style>
</head>
<body>

<div class="no-print text-center py-3 bg-white border-bottom shadow-sm mb-4">
    <button onclick="window.print()" class="btn btn-success fw-bold px-4">
        <i class="bi bi-printer me-2"></i> Print Certificate of Residency
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3 ms-2">Close</button>
</div>

<div class="cert-container">
    <div class="cert-header">
        <div style="font-size: 0.85rem; text-transform: uppercase;">Republic of the Philippines</div>
        <div style="font-size: 0.85rem;">Province of Iloilo &bull; Municipality of Dumangas</div>
        <h4>BARANGAY LUBLUB (P.D. MONFORT NORTH)</h4>
        <div style="font-size: 0.82rem; font-style: italic; color: #047857;">Office of the Punong Barangay</div>
    </div>

    <div class="row g-0">
        <div class="col-4 cert-sidebar">
            <div class="text-center mb-3">
                <strong class="d-block text-success" style="font-size: 0.82rem; text-transform: uppercase;">Barangay Council (2023-2026)</strong>
                <strong class="d-block text-success" style="font-size: 0.82rem; text-transform: uppercase;">Barangay Executive Council</strong>
            </div>

            <div class="mb-3">
                <strong class="d-block text-dark">HON. ELISEO RODRIGUEZ</strong>
                <small class="text-muted d-block">Punong Barangay (Captain)</small>
            </div>

            <hr class="my-2">

            <div class="mb-2">
                <strong class="d-block text-dark">HON. RAFAEL M. MONFORT</strong>
                <small class="text-muted d-block">Punong Barangay</small>
                <strong class="d-block text-dark">MS. ANNIELOR GULBIQUE</strong>
                <small class="text-muted d-block">Barangay Secretary</small>
            </div>

            <div class="mb-2"><strong>SANGGUNIANG KAGAWAD:</strong></div>
            <div class="mb-1"><strong>Hon. Eduardo P. Decolongon</strong><div class="text-muted" style="font-size: 0.7rem;">Sitio Centro</div></div>
            <div class="mb-1"><strong>Hon. Maria Fe L. Divinagracia</strong><div class="text-muted" style="font-size: 0.7rem;">Sitio Ilaya</div></div>
            <div class="mb-1"><strong>Hon. Roberto S. Celis</strong><div class="text-muted" style="font-size: 0.7rem;">Sitio Ilawod</div></div>
            <div class="mb-1"><strong>Hon. Jocelyn D. Baldevia</strong><div class="text-muted" style="font-size: 0.7rem;">Sitio Tabucan</div></div>
            <div class="mb-1"><strong>Hon. Arnel G. Dumalag</strong><div class="text-muted" style="font-size: 0.7rem;">Sitio Crossing</div></div>
            <div class="mb-1"><strong>Hon. Salvacion T. Labordo</strong><div class="text-muted" style="font-size: 0.7rem;">Sitio Malipayon</div></div>
            <div class="mb-2"><strong>Hon. Nelson B. Cordero</strong><div class="text-muted" style="font-size: 0.7rem;">Sitio Pag-asa</div></div>
            <hr class="my-2">
            <div class="mb-1"><strong>Ma. Theresa B. Alipao</strong><div class="text-muted" style="font-size: 0.7rem;">Barangay Secretary</div></div>
            <div class="mb-1"><strong>Ronaldo C. Valencia</strong><div class="text-muted" style="font-size: 0.7rem;">Barangay Treasurer</div></div>
            <div class="mb-2">
                <strong class="d-block text-dark">MS. JOY VALIENTE</strong>
                <small class="text-muted d-block">Barangay Treasurer</small>
            </div>
        </div>

        <div class="col-8 cert-body">
            <div class="text-end text-muted small mb-2 font-monospace">Doc No: <strong><?= htmlspecialchars($certNo) ?></strong></div>

            <div class="cert-title">CERTIFICATE OF RESIDENCY</div>

            <p class="fw-bold mb-4">TO WHOM IT MAY CONCERN:</p>

            <p style="text-indent: 2rem;">
                This is to certify that <strong><?= strtoupper(htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix'])) ?></strong>, 
                <?= $resident['age'] ?> years of age, <?= $resident['civil_status'] ?>, is a bonafide permanent resident of 
                <strong><?= htmlspecialchars($resident['address_line']) ?>, <?= htmlspecialchars($resident['sitio_name']) ?>, Barangay Lublub (P.D. Monfort North), Municipality of Dumangas, Province of Iloilo</strong>.
            </p>

            <p style="text-indent: 2rem;">
                This certifies further that the subject individual has been residing within the territory of this Barangay and is duly recorded in the official Barangay Registry of Inhabitants under Household No. <strong><?= htmlspecialchars($resident['household_no'] ?: 'HH-LUBLUB') ?></strong>.
            </p>

            <p style="text-indent: 2rem;">
                This certification is issued upon the request of the interested party for:
                <br>
                <strong class="text-uppercase d-block text-center my-2 p-2 border bg-light" style="font-size: 1rem;">
                    <?= htmlspecialchars($_GET['purpose'] ?? 'PROOF OF RESIDENCE / BANK & GOVERNMENT TRANSACTION') ?>
                </strong>
            </p>

            <p style="text-indent: 2rem;">
                Given this <strong><?= date('jS') ?></strong> day of <strong><?= date('F, Y') ?></strong> at Barangay Lublub, Dumangas, Iloilo.
            </p>

            <div class="mt-5 pt-4 text-end">
                <div class="fw-bold text-dark fs-6">HON. RAFAEL M. MONFORT</div>
                <div class="text-muted small">Punong Barangay</div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center" style="font-size: 0.72rem; color: #64748b;">
                <span>Resident ID: <strong><?= htmlspecialchars($resident['resident_code']) ?></strong></span>
                <span>Official Seal</span>
                <span>Barangay Lublub LGU</span>
            </div>
        </div>
    </div>
</div>

</body>
</html>

