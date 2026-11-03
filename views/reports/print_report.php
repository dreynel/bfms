<?php
// reports/print - Printable Beneficiary Disbursement Masterlist / Payroll for Auditing
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'bhw');

$db = getDB();
$programId = (int)($_GET['program_id'] ?? 0);

$stmtProg = $db->prepare("
    SELECT p.*, s.name AS target_sitio_name, u.full_name AS creator_name
    FROM assistance_programs p
    LEFT JOIN sitios s ON p.target_sitio_id = s.id
    LEFT JOIN users u ON p.created_by_user_id = u.id
    WHERE p.id = ?
");
$stmtProg->execute([$programId]);
$program = $stmtProg->fetch();

if (!$program) {
    die("Assistance program not found.");
}

$stmtBen = $db->prepare("
    SELECT b.*, r.resident_code, r.first_name, r.middle_name, r.last_name, r.suffix,
           r.contact_no, r.address_line, r.is_pwd, r.is_solo_parent, r.is_indigent, r.is_senior,
           s.name AS sitio_name,
           u.full_name AS releaser_name
    FROM assistance_beneficiaries b
    JOIN residents r ON b.resident_id = r.id
    JOIN sitios s ON r.sitio_id = s.id
    LEFT JOIN users u ON b.released_by_user_id = u.id
    WHERE b.program_id = ?
    ORDER BY s.id ASC, r.last_name ASC
");
$stmtBen->execute([$programId]);
$beneficiaries = $stmtBen->fetchAll();

$totalDisbursed = array_sum(array_map(fn($b) => ($b['status'] === 'Claimed') ? (float)$b['received_amount'] : 0, $beneficiaries));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda Disbursement Payroll - <?= htmlspecialchars($program['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @page { size: landscape; margin: 10mm; }
        body { background: #fff; font-family: 'Inter', system-ui, sans-serif; font-size: 0.82rem; }
        .table-payroll th, .table-payroll td { padding: 6px 8px; vertical-align: middle; }
        .signature-line { border-bottom: 1px solid #000; min-width: 130px; display: inline-block; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; }
        }
    </style>
</head>
<body class="p-4">

<div class="no-print text-center mb-4">
    <button onclick="window.print()" class="btn btn-success fw-bold px-4">
        <i class="bi bi-printer me-2"></i> Print Official Payroll
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3 ms-2">Close</button>
</div>

<div class="text-center mb-4">
    <div class="small text-uppercase">Republic of the Philippines &bull; Province of Iloilo &bull; Municipality of Dumangas</div>
    <h4 class="fw-bold mb-0 text-success">BARANGAY LUBLUB (P.D. MONFORT NORTH)</h4>
    <div class="fw-bold fs-6 text-uppercase mt-2">OFFICIAL AYUDA / SOCIAL ASSISTANCE DISBURSEMENT PAYROLL</div>
    <div class="text-muted small">Program: <strong><?= htmlspecialchars($program['title']) ?> (<?= htmlspecialchars($program['program_code']) ?>)</strong></div>
    <div class="text-muted small">Fund Source: <strong><?= htmlspecialchars($program['fund_source']) ?></strong> &bull; Date: <strong><?= format_date($program['distribution_date']) ?></strong></div>
</div>

<table class="table table-bordered table-payroll">
    <thead class="table-light">
        <tr class="text-center align-middle">
            <th style="width: 35px;">#</th>
            <th>Resident Code</th>
            <th>Beneficiary Full Name</th>
            <th>Sitio & Address</th>
            <th>Vulnerability Classification</th>
            <th>Cash Received</th>
            <th>In-Kind Goods</th>
            <th>Status</th>
            <th>Date / Time Claimed</th>
            <th style="width: 150px;">Beneficiary Signature / Thumbmark</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($beneficiaries as $idx => $b): ?>
            <tr>
                <td class="text-center fw-bold"><?= $idx + 1 ?></td>
                <td class="font-monospace"><?= htmlspecialchars($b['resident_code']) ?></td>
                <td>
                    <strong><?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name'] . ' ' . $b['middle_name'] . ' ' . $b['suffix']) ?></strong>
                </td>
                <td>
                    <strong><?= htmlspecialchars($b['sitio_name']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($b['address_line']) ?></small>
                </td>
                <td class="small">
                    <?php 
                        $tags = [];
                        if ($b['is_pwd']) $tags[] = 'PWD';
                        if ($b['is_solo_parent']) $tags[] = 'Solo Mother';
                        if ($b['is_indigent']) $tags[] = 'Indigent';
                        if ($b['is_senior']) $tags[] = 'Senior';
                        echo implode(', ', $tags) ?: 'Resident';
                    ?>
                </td>
                <td class="text-end fw-bold text-success"><?= format_peso($b['received_amount']) ?></td>
                <td class="small"><?= htmlspecialchars($b['received_items'] ?: '-') ?></td>
                <td class="text-center">
                    <span class="badge bg-<?= ($b['status'] === 'Claimed') ? 'success' : 'secondary' ?>"><?= $b['status'] ?></span>
                </td>
                <td class="small text-center">
                    <?= $b['claimed_at'] ? format_datetime($b['claimed_at']) : 'Unclaimed' ?>
                </td>
                <td class="text-center">
                    <?php if ($b['status'] === 'Claimed'): ?>
                        <span class="small text-success fw-bold">[VERIFIED CLAIM]</span>
                    <?php else: ?>
                        <div class="signature-line mt-3"></div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="table-light fw-bold">
        <tr>
            <td colspan="5" class="text-end">TOTAL DISBURSED TO DATE:</td>
            <td class="text-end text-success"><?= format_peso($totalDisbursed) ?></td>
            <td colspan="4"></td>
        </tr>
    </tfoot>
</table>

<!-- Sign-off Block for Audit -->
<div class="row mt-5 pt-3">
    <div class="col-4 text-center">
        <small class="text-muted d-block">Prepared / Disbursed by:</small>
        <div class="fw-bold mt-4" style="border-top: 1px solid #333; display: inline-block; min-width: 200px; padding-top: 4px;">
            <?= strtoupper(htmlspecialchars($user['full_name'])) ?>
        </div>
        <small class="text-muted d-block">Disbursing Official</small>
    </div>
    <div class="col-4 text-center">
        <small class="text-muted d-block">Certified Correct & Verified by:</small>
        <div class="fw-bold mt-4" style="border-top: 1px solid #333; display: inline-block; min-width: 200px; padding-top: 4px;">
            MA. THERESA B. ALIPAO
        </div>
        <small class="text-muted d-block">Barangay Secretary</small>
    </div>
    <div class="col-4 text-center">
        <small class="text-muted d-block">Attested & Approved by:</small>
        <div class="fw-bold mt-4" style="border-top: 1px solid #333; display: inline-block; min-width: 200px; padding-top: 4px;">
            HON. RAFAEL M. MONFORT
        </div>
        <small class="text-muted d-block">Punong Barangay</small>
    </div>
</div>

</body>
</html>

