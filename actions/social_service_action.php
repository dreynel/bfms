<?php
// actions/social_service_action.php - Handles Ayuda releasing, program distribution, and applications
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary', 'treasurer');

$db = getDB();
$user = auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Release Ayuda / Aid Claim (Strictly Treasurer)
    if ($action === 'release_aid') {
        require_role('treasurer');
        $beneficiaryId = (int)($_POST['beneficiary_id'] ?? 0);
        $programId     = (int)($_POST['program_id'] ?? 0);
        $remarks       = clean($_POST['remarks'] ?? 'Claimed in person with valid identification');
        $receivedAmt   = (float)($_POST['received_amount'] ?? 0.00);

        // Verification: Ensure program is authorized (Approved [777])
        $progStatus = $db->query("SELECT status FROM assistance_programs WHERE id = {$programId}")->fetchColumn();
        if ($progStatus !== 'Ongoing') {
            set_flash('danger', 'Disbursement Blocked: This program is currently pending executive authorization by the Punong Barangay (Status: ' . htmlspecialchars($progStatus) . ').');
            redirect('social_services/distribute?program_id=' . $programId);
        }

        $updateStmt = $db->prepare("
            UPDATE assistance_beneficiaries 
            SET status = 'Claimed', claimed_at = NOW(), released_by_user_id = ?, remarks = ?, received_amount = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$user['id'], $remarks, $receivedAmt, $beneficiaryId]);

        log_activity('Release Aid', "Ayuda released to beneficiary ID {$beneficiaryId} by Treasurer {$user['full_name']}");
        set_flash('success', 'Ayuda disbursement marked as CLAIMED successfully!');
        redirect('social_services/distribute?program_id=' . $programId);
    }

    // 2. Add Beneficiary to Program
    if ($action === 'add_beneficiary') {
        $programId  = (int)($_POST['program_id'] ?? 0);
        $residentId = (int)($_POST['resident_id'] ?? 0);
        $amount     = (float)($_POST['amount'] ?? 0.00);

        try {
            $insert = $db->prepare("
                INSERT INTO assistance_beneficiaries (program_id, resident_id, status, received_amount)
                VALUES (?, ?, 'Enrolled', ?)
            ");
            $insert->execute([$programId, $residentId, $amount]);
            set_flash('success', 'Resident added to program beneficiary roster.');
        } catch (Exception $e) {
            set_flash('danger', 'Resident is already enrolled in this program.');
        }
        redirect('social_services/distribute?program_id=' . $programId);
    }
}

