<?php
// actions/approval_action.php - Centralized Approval Execution Handler
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary');

$db = getDB();
$user = auth_user();
$userId = (int)$user['id'];
$userRole = $user['role'];
$userName = $user['full_name'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('approvals');
}

$action = clean($_POST['action'] ?? '');
$approvalId = (int)($_POST['approval_id'] ?? 0);
$remarks = clean($_POST['remarks'] ?? '');
$redirectTarget = clean($_POST['redirect_to'] ?? 'approvals');

// Extra data for resident approval (household assignment)
$extraData = [
    'assigned_household_no' => clean($_POST['assigned_household_no'] ?? '')
];

try {
    $db->beginTransaction();

    $result = process_approval_action(
        $approvalId,
        $action,
        $userId,
        $userRole,
        $userName,
        $remarks,
        $extraData,
        $db
    );

    $db->commit();

    if ($action === 'approve') {
        if ($result['status'] === 'forwarded') {
            set_flash('info', $result['message']);
        } else {
            set_flash('success', $result['message']);
        }
    } elseif ($action === 'reject') {
        set_flash('warning', $result['message']);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    set_flash('danger', 'Approval Processing Error: ' . $e->getMessage());
}

redirect($redirectTarget);

