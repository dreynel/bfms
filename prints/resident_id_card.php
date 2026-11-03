<?php
// residents/id_card - Official Printable Barangay Resident ID Card
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary', 'bhw', 'resident');

$db = getDB();
$user = auth_user();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    if ($user['role'] === 'resident') {
        $stmt = $db->prepare("SELECT id FROM residents WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $id = (int)$stmt->fetchColumn();
    }
}

$stmt = $db->prepare("
    SELECT r.*, s.name AS sitio_name, s.code AS sitio_code,
           ub.full_name AS assigned_bhw_name
    FROM residents r
    JOIN sitios s ON r.sitio_id = s.id
    LEFT JOIN users ub ON s.id = ub.assigned_sitio_id AND ub.role = 'bhw'
    WHERE r.id = ?
");
$stmt->execute([$id]);
$resident = $stmt->fetch();

$secName = $db->query("SELECT full_name FROM users WHERE role = 'secretary' LIMIT 1")->fetchColumn() ?: 'Maria Clara De Los Santos';

if (!$resident) {
    die("Resident record not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay ID - <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', system-ui, sans-serif;
            padding: 30px;
        }
        .id-card-wrapper {
            max-width: 440px;
            margin: 0 auto;
        }
        .barangay-id-card {
            width: 100%;
            height: 270px;
            background: #ffffff;
            border-radius: 14px;
            border: 2px solid #0d6338;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-bottom: 25px;
        }
        .id-header-stripe {
            background: linear-gradient(135deg, #06381e 0%, #0d6338 100%);
            color: #ffffff;
            padding: 8px 12px;
            border-bottom: 3px solid #f59e0b;
        }
        .id-body {
            display: flex;
            padding: 10px 14px;
            flex-grow: 1;
            gap: 12px;
        }
        .id-photo-box {
            width: 90px;
            height: 95px;
            border: 2px solid #0d6338;
            border-radius: 8px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #0d6338;
            flex-shrink: 0;
        }
        .id-info {
            flex-grow: 1;
            font-size: 0.78rem;
            line-height: 1.35;
        }
        .id-name {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
            margin-bottom: 3px;
        }
        .id-footer-stripe {
            background: #f8fafc;
            border-top: 1px dashed #cbd5e1;
            padding: 6px 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 0.68rem;
        }
        .watermark {
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 9rem;
            color: rgba(13, 99, 56, 0.04);
            pointer-events: none;
        }
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .barangay-id-card {
                box-shadow: none !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-4">
    <a href="javascript:window.print()" class="btn btn-success fw-bold px-4 shadow-sm">
        <i class="bi bi-printer me-2"></i> Print Resident ID Card
    </a>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3 ms-2">Close</button>
</div>

<div class="id-card-wrapper">
    <!-- FRONT OF CARD -->
    <div class="barangay-id-card">
        <i class="bi bi-shield-fill watermark"></i>
        <!-- Header -->
        <div class="id-header-stripe d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                    BL
                </div>
                <div>
                    <div style="font-size: 0.62rem; letter-spacing: 0.05em; text-transform: uppercase;">Republic of the Philippines &bull; Iloilo</div>
                    <div class="fw-bold lh-1" style="font-size: 0.85rem;">BARANGAY LUBLUB (P.D. MONFORT NORTH)</div>
                    <div style="font-size: 0.62rem; color: #fde68a;">Municipality of Dumangas &bull; ZIP 5006</div>
                </div>
            </div>
            <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.65rem;">RESIDENT ID</span>
        </div>

        <!-- Body -->
        <div class="id-body">
            <div class="id-photo-box">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="id-info">
                <div class="id-name"><?= strtoupper(htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix'])) ?></div>
                <div class="text-success fw-bold font-monospace" style="font-size: 0.72rem;">ID: <?= htmlspecialchars($resident['resident_code']) ?></div>
                <div><strong>Sitio:</strong> <span class="text-success fw-bold"><?= htmlspecialchars($resident['sitio_name']) ?></span></div>
                <div><strong>Address:</strong> <?= htmlspecialchars($resident['address_line']) ?></div>
                <div><strong>Birthdate:</strong> <?= format_date($resident['birth_date']) ?> (Age: <?= $resident['age'] ?>)</div>
                <div><strong>Civil Status:</strong> <?= $resident['civil_status'] ?> &bull; <strong>Blood:</strong> <?= htmlspecialchars($resident['blood_type'] ?: 'N/A') ?></div>

                <div class="d-flex gap-1 mt-1">
                    <?php if ($resident['is_pwd']): ?><span class="badge bg-primary" style="font-size: 0.58rem;">PWD</span><?php endif; ?>
                    <?php if ($resident['is_solo_parent']): ?><span class="badge bg-danger" style="font-size: 0.58rem;">SOLO PARENT</span><?php endif; ?>
                    <?php if ($resident['is_indigent']): ?><span class="badge bg-warning text-dark" style="font-size: 0.58rem;">INDIGENT</span><?php endif; ?>
                    <?php if ($resident['is_senior']): ?><span class="badge bg-info text-dark" style="font-size: 0.58rem;">SENIOR</span><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="id-footer-stripe">
            <div>
                <div class="text-muted" style="font-size: 0.58rem;">Verified by:</div>
                <div class="fw-bold text-dark"><?= htmlspecialchars($secName) ?></div>
                <div class="text-muted" style="font-size: 0.55rem;">Barangay Secretary</div>
            </div>
            <div class="text-end">
                <div class="fw-bold text-dark" style="border-bottom: 1px solid #333; padding-bottom: 1px;">HON. RAFAEL M. MONFORT</div>
                <div class="text-muted" style="font-size: 0.58rem;">Punong Barangay</div>
            </div>
        </div>
    </div>

    <!-- BACK OF CARD -->
    <div class="barangay-id-card">
        <div class="p-3 text-center d-flex flex-column justify-content-between h-100">
            <div>
                <h6 class="fw-bold text-success mb-1" style="font-size: 0.85rem;">BARANGAY LUBLUB RESIDENT IDENTIFICATION</h6>
                <p class="text-muted mb-2" style="font-size: 0.65rem;">This certifies that the bearer is a bonafide resident and eligible beneficiary of social services under Barangay Lublub, Dumangas, Iloilo.</p>
            </div>

            <div class="p-2 rounded bg-light border text-start" style="font-size: 0.7rem;">
                <div class="fw-bold text-danger mb-1"><i class="bi bi-telephone-fill me-1"></i> IN CASE OF EMERGENCY:</div>
                <div><strong>Contact:</strong> <?= htmlspecialchars($resident['emergency_contact_name'] ?: 'Barangay Hall') ?></div>
                <div><strong>Phone:</strong> <?= htmlspecialchars($resident['emergency_contact_no'] ?: '(033) 361-2045') ?></div>
                <div><strong>Precinct:</strong> <?= htmlspecialchars($resident['precinct_no'] ?: 'Lublub Elem School') ?></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <div class="text-start" style="font-size: 0.62rem;">
                    <div>Emergency Hotlines:</div>
                    <strong>PNP Dumangas: 0998-598-6211</strong><br>
                    <strong>BFP Fire: 0917-889-1234</strong>
                </div>
                <div class="text-end font-monospace" style="font-size: 0.62rem;">
                    Valid: 2026 - 2028<br>
                    <span class="text-success fw-bold">OFFICIAL VERIFIED</span>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

