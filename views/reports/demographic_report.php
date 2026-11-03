<?php
// reports/demographic - Comprehensive Social Welfare & Vulnerability Demographic Report
require_once __DIR__ . '/../../config/app.php';

require_role('captain', 'secretary', 'bhw');

$db = getDB();
$user = auth_user();
$role = $user['role'];
$userSitioId = $user['assigned_sitio_id'] ?? null;
$pageTitle = 'Social Welfare Demographic Report';

$filterSitio = isset($_GET['sitio']) ? (int)$_GET['sitio'] : 0;
if ($role === 'bhw' && $userSitioId) {
    $filterSitio = $userSitioId;
}

// 1. Overall Counts
$sqlTotals = "SELECT 
    COUNT(*) as total_pop,
    COUNT(DISTINCT household_no) as total_households,
    SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as males,
    SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as females,
    SUM(CASE WHEN age < 15 THEN 1 ELSE 0 END) as children,
    SUM(CASE WHEN is_youth = 1 THEN 1 ELSE 0 END) as youth,
    SUM(CASE WHEN age >= 18 AND age < 60 THEN 1 ELSE 0 END) as working_age,
    SUM(CASE WHEN is_senior = 1 THEN 1 ELSE 0 END) as seniors,
    SUM(CASE WHEN is_pwd = 1 THEN 1 ELSE 0 END) as pwds,
    SUM(CASE WHEN is_solo_parent = 1 THEN 1 ELSE 0 END) as solo_parents,
    SUM(CASE WHEN is_indigent = 1 THEN 1 ELSE 0 END) as indigents,
    SUM(CASE WHEN is_4ps = 1 THEN 1 ELSE 0 END) as four_ps,
    SUM(CASE WHEN is_voter = 1 THEN 1 ELSE 0 END) as voters
    FROM residents WHERE status = 'approved'";

if ($filterSitio > 0) {
    $sqlTotals .= " AND sitio_id = {$filterSitio}";
}
$totals = $db->query($sqlTotals)->fetch();

// 2. Sitio Breakdown
$sitiosSummary = $db->query("
    SELECT s.id, s.name, s.code, u.full_name AS bhw_name,
           COUNT(r.id) AS population,
           SUM(CASE WHEN r.gender = 'Male' THEN 1 ELSE 0 END) AS males,
           SUM(CASE WHEN r.gender = 'Female' THEN 1 ELSE 0 END) AS females,
           SUM(CASE WHEN r.is_pwd = 1 THEN 1 ELSE 0 END) AS pwds,
           SUM(CASE WHEN r.is_solo_parent = 1 THEN 1 ELSE 0 END) AS solo_parents,
           SUM(CASE WHEN r.is_indigent = 1 THEN 1 ELSE 0 END) AS indigents,
           SUM(CASE WHEN r.is_senior = 1 THEN 1 ELSE 0 END) AS seniors,
           SUM(CASE WHEN r.is_4ps = 1 THEN 1 ELSE 0 END) AS four_ps
    FROM sitios s
    LEFT JOIN users u ON s.id = u.assigned_sitio_id AND u.role = 'bhw'
    LEFT JOIN residents r ON s.id = r.sitio_id AND r.status = 'approved'
    GROUP BY s.id, s.name, s.code, u.full_name
    ORDER BY s.id ASC
")->fetchAll();

// 3. PWD Types Breakdown
$pwdTypes = $db->query("
    SELECT IFNULL(pwd_type, 'General / Mobility') AS ptype, COUNT(*) AS count
    FROM residents
    WHERE is_pwd = 1 AND status = 'approved'
    GROUP BY ptype
")->fetchAll();

// Handled by index.php container
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-0">Demographic & Vulnerability Profiling Report</h3>
        <p class="text-muted small mb-0">Official Population and Social Welfare Registry Data for Barangay Lublub</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- Key Stat Counters -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <span class="text-muted small fw-semibold text-uppercase">Total Approved Population</span>
            <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($totals['total_pop']) ?></h3>
            <small class="text-muted"><?= $totals['total_households'] ?> Households Recorded</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <span class="text-primary small fw-semibold text-uppercase">PWD Residents</span>
            <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($totals['pwds']) ?></h3>
            <small class="text-muted"><?= ($totals['total_pop'] > 0) ? round(($totals['pwds'] / $totals['total_pop']) * 100, 1) : 0 ?>% of Population</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <span class="text-danger small fw-semibold text-uppercase">Solo Mothers / Parents</span>
            <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($totals['solo_parents']) ?></h3>
            <small class="text-muted"><?= ($totals['total_pop'] > 0) ? round(($totals['solo_parents'] / $totals['total_pop']) * 100, 1) : 0 ?>% of Population</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 border-0 shadow-sm bg-white">
            <span class="text-warning-emphasis small fw-semibold text-uppercase">Indigent Households</span>
            <h3 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= number_format($totals['indigents']) ?></h3>
            <small class="text-muted"><?= number_format($totals['four_ps']) ?> on 4Ps DSWD</small>
        </div>
    </div>
</div>

<!-- 7 Sitios Tabular Demographics -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-geo-alt-fill text-success me-2"></i>Demographics by Sitio (The 7 Sitios of Lublub)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Sitio Name</th>
                    <th>Assigned BHW</th>
                    <th class="text-center">Pop.</th>
                    <th class="text-center">Male</th>
                    <th class="text-center">Female</th>
                    <th class="text-center">PWD</th>
                    <th class="text-center">Solo Mother</th>
                    <th class="text-center">Indigent</th>
                    <th class="text-center">Senior</th>
                    <th class="text-center">4Ps</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sitiosSummary as $s): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $s['id'] ?></td>
                        <td><strong class="text-dark"><?= htmlspecialchars($s['name']) ?></strong></td>
                        <td><?= htmlspecialchars($s['bhw_name'] ?? 'TBD') ?></td>
                        <td class="text-center fw-bold text-dark"><?= $s['population'] ?></td>
                        <td class="text-center"><?= $s['males'] ?></td>
                        <td class="text-center"><?= $s['females'] ?></td>
                        <td class="text-center"><span class="badge badge-pwd"><?= $s['pwds'] ?></span></td>
                        <td class="text-center"><span class="badge badge-solo"><?= $s['solo_parents'] ?></span></td>
                        <td class="text-center"><span class="badge badge-indigent"><?= $s['indigents'] ?></span></td>
                        <td class="text-center"><span class="badge badge-senior"><?= $s['seniors'] ?></span></td>
                        <td class="text-center"><span class="badge badge-4ps"><?= $s['four_ps'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">BARANGAY TOTALS:</td>
                    <td class="text-center"><?= $totals['total_pop'] ?></td>
                    <td class="text-center"><?= $totals['males'] ?></td>
                    <td class="text-center"><?= $totals['females'] ?></td>
                    <td class="text-center text-primary"><?= $totals['pwds'] ?></td>
                    <td class="text-center text-danger"><?= $totals['solo_parents'] ?></td>
                    <td class="text-center text-warning-emphasis"><?= $totals['indigents'] ?></td>
                    <td class="text-center text-info-emphasis"><?= $totals['seniors'] ?></td>
                    <td class="text-center text-success"><?= $totals['four_ps'] ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Detailed Disability Types Breakdown -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-person-wheelchair text-primary me-2"></i>PWD Classification Roster</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($pwdTypes)): ?>
                        <li class="list-group-item text-muted text-center py-4">No PWDs registered yet.</li>
                    <?php else: ?>
                        <?php foreach ($pwdTypes as $pt): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars($pt['ptype']) ?></span>
                                <span class="badge bg-primary rounded-pill"><?= $pt['count'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-pie-chart text-success me-2"></i>Age Demographics</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>Children (0 - 14 yrs):</span>
                    <strong class="text-dark"><?= $totals['children'] ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Youth (15 - 30 yrs):</span>
                    <strong class="text-dark"><?= $totals['youth'] ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Working Age Adults (31 - 59 yrs):</span>
                    <strong class="text-dark"><?= $totals['working_age'] ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Senior Citizens (60+ yrs):</span>
                    <strong class="text-dark"><?= $totals['seniors'] ?></strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>Registered Voters in Brgy Lublub:</span>
                    <strong class="text-success"><?= $totals['voters'] ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

// Handled by index.php container

