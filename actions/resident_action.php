<?php
// actions/resident_action.php - Handles resident approvals, status updates, and profiling actions
require_once __DIR__ . '/../config/app.php';

require_role('secretary', 'captain');

$db = getDB();
$user = auth_user();
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $residentId = (int)($_POST['resident_id'] ?? 0);
    $action = $_POST['action'];

    // Verify resident exists
    $stmt = $db->prepare("SELECT r.*, u.id AS linked_user_id FROM residents r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([$residentId]);
    $res = $stmt->fetch();

    if (!$res) {
        set_flash('danger', 'Resident record not found.');
        redirect('residents/approvals');
    }

    // Permission check: Secretary (and Captain) has approval authority
    if (!in_array($role, ['secretary', 'captain'])) {
        set_flash('danger', 'Access Restricted: Only the Barangay Secretary has authority to verify and approve resident signups.');
        redirect('residents/approvals');
    }

    if ($action === 'approve') {
        $assignedHh = clean($_POST['assigned_household_no'] ?? '');
        try {
            $db->beginTransaction();

            if (!empty($assignedHh) && !in_array(strtolower($assignedHh), ['n/a', 'na', 'none', 'null', '-'])) {
                $updateRes = $db->prepare("
                    UPDATE residents 
                    SET status = 'approved', approved_at = NOW(), approved_by_user_id = ?, rejection_reason = NULL,
                        household_no = ?
                    WHERE id = ?
                ");
                $updateRes->execute([$user['id'], $assignedHh, $residentId]);
            } else {
                $updateRes = $db->prepare("
                    UPDATE residents 
                    SET status = 'approved', approved_at = NOW(), approved_by_user_id = ?, rejection_reason = NULL 
                    WHERE id = ?
                ");
                $updateRes->execute([$user['id'], $residentId]);
            }

            if ($res['linked_user_id']) {
                $updateUser = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $updateUser->execute([$res['linked_user_id']]);
            }

            $db->commit();

            log_activity('Approve Resident', "Resident {$res['first_name']} {$res['last_name']} approved by {$user['full_name']} for Sitio " . SITIOS_LIST[$res['sitio_id']]);
            set_flash('success', "Resident <strong>{$res['first_name']} {$res['last_name']}</strong> has been approved and activated successfully!");
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Error approving resident: ' . $e->getMessage());
        }
        redirect('residents/approvals');

    } elseif ($action === 'reject') {
        $reason = clean($_POST['rejection_reason'] ?? 'Registration details could not be verified by Barangay Secretary.');
        try {
            $db->beginTransaction();

            $updateRes = $db->prepare("
                UPDATE residents 
                SET status = 'rejected', approved_at = NOW(), approved_by_user_id = ?, rejection_reason = ? 
                WHERE id = ?
            ");
            $updateRes->execute([$user['id'], $reason, $residentId]);

            if ($res['linked_user_id']) {
                $updateUser = $db->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
                $updateUser->execute([$res['linked_user_id']]);
            }

            $db->commit();

            log_activity('Reject Resident', "Resident {$res['first_name']} {$res['last_name']} rejected by {$user['full_name']}. Reason: {$reason}");
            set_flash('warning', "Resident registration for {$res['first_name']} {$res['last_name']} has been rejected.");
        } catch (Exception $e) {
            $db->rollBack();
            set_flash('danger', 'Error updating resident: ' . $e->getMessage());
        }
        redirect('residents/approvals');
    }
}

