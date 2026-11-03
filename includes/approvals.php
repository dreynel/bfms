<?php
// includes/approvals.php - Centralized Approvals & Chart of Authority System

define('APPROVE_ORDER_DRAFT', 0);
define('APPROVE_ORDER_APPROVED', 777);
define('APPROVE_ORDER_REJECTED', 555);

/**
 * Return human-readable label and CSS classes for an approval order
 */
function get_approval_order_info($order) {
    switch ((int)$order) {
        case APPROVE_ORDER_DRAFT:
            return [
                'label' => 'Draft',
                'badge_class' => 'bg-secondary',
                'text_class' => 'text-secondary',
                'icon' => 'bi-pencil'
            ];
        case APPROVE_ORDER_APPROVED:
            return [
                'label' => 'Approved',
                'badge_class' => 'bg-success',
                'text_class' => 'text-success',
                'icon' => 'bi-check-circle-fill'
            ];
        case APPROVE_ORDER_REJECTED:
            return [
                'label' => 'Rejected',
                'badge_class' => 'bg-danger',
                'text_class' => 'text-danger',
                'icon' => 'bi-x-circle-fill'
            ];
        default:
            return [
                'label' => "Under Review (Stage {$order})",
                'badge_class' => 'bg-warning text-dark',
                'text_class' => 'text-warning-emphasis',
                'icon' => 'bi-hourglass-split'
            ];
    }
}

function render_approval_order_badge($order) {
    $info = get_approval_order_info($order);
    return '<span class="badge ' . $info['badge_class'] . '"><i class="bi ' . $info['icon'] . ' me-1"></i>' . htmlspecialchars($info['label']) . '</span>';
}

/**
 * Fetch the active approval record for a document
 */
function get_approval_for($documentType, $documentId, $db = null) {
    if (!$db) $db = getDB();
    $stmt = $db->prepare("SELECT * FROM approvals WHERE document_type = ? AND document_id = ? LIMIT 1");
    $stmt->execute([$documentType, (int)$documentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetch the Chart of Authority approvers configured for a document type
 */
function get_approvers_hierarchy($documentType, $db = null) {
    if (!$db) $db = getDB();
    $stmt = $db->prepare("SELECT * FROM approvers WHERE document_type = ? ORDER BY approve_order ASC");
    $stmt->execute([$documentType]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create or register a new document for centralized approval
 */
function create_approval_request($documentType, $documentId, $title, $submittedByUserId, $isDraft = false, $remarks = '', $db = null) {
    if (!$db) $db = getDB();

    $hierarchy = get_approvers_hierarchy($documentType, $db);
    $firstOrder = 1;
    $firstRole = 'captain';
    if (!empty($hierarchy)) {
        $firstOrder = (int)$hierarchy[0]['approve_order'];
        $firstRole = $hierarchy[0]['approver_role'];
    }

    $currentOrder = $isDraft ? APPROVE_ORDER_DRAFT : $firstOrder;
    $currentRole  = $isDraft ? null : $firstRole;

    // Check if already exists
    $existing = get_approval_for($documentType, $documentId, $db);
    if ($existing) {
        $stmt = $db->prepare("
            UPDATE approvals 
            SET title = ?, approve_order = ?, current_approver_role = ?, submitted_by_user_id = ?, remarks = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $currentOrder, $currentRole, $submittedByUserId, $remarks, $existing['id']]);
        $approvalId = $existing['id'];
    } else {
        $stmt = $db->prepare("
            INSERT INTO approvals (document_type, document_id, title, approve_order, current_approver_role, submitted_by_user_id, remarks, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$documentType, (int)$documentId, $title, $currentOrder, $currentRole, $submittedByUserId, $remarks]);
        $approvalId = (int)$db->lastInsertId();
    }

    // Log the event
    $user = auth_user();
    $userName = $user ? $user['full_name'] : 'System User';
    $userRole = $user ? $user['role'] : 'system';

    $logStmt = $db->prepare("
        INSERT INTO approval_logs (approval_id, document_type, document_id, from_order, to_order, action, action_by_user_id, action_by_name, action_by_role, remarks, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $actionName = $isDraft ? 'return_draft' : 'submit';
    $logStmt->execute([
        $approvalId, $documentType, (int)$documentId, 
        0, $currentOrder, $actionName, 
        $submittedByUserId, $userName, $userRole, $remarks
    ]);

    return $approvalId;
}

/**
 * Execute an approval workflow action: 'approve' or 'reject'
 */
function process_approval_action($approvalId, $action, $userId, $userRole, $userName, $remarks = '', $extraData = [], $db = null) {
    if (!$db) $db = getDB();

    $stmt = $db->prepare("SELECT * FROM approvals WHERE id = ?");
    $stmt->execute([(int)$approvalId]);
    $approval = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$approval) {
        throw new Exception("Approval transaction record #{$approvalId} not found.");
    }

    // Authorization check: User role must match current_approver_role, or captain as executive master
    if ($approval['current_approver_role'] !== $userRole && $userRole !== 'captain') {
        throw new Exception("Unauthorized: Your role ({$userRole}) does not have approval authority at Stage {$approval['approve_order']}. Required role: {$approval['current_approver_role']}.");
    }

    $currentOrder = (int)$approval['approve_order'];
    $docType      = $approval['document_type'];
    $docId        = (int)$approval['document_id'];

    if ($action === 'approve') {
        // Find if next approver exists in the Chart of Authority hierarchy
        $nextStmt = $db->prepare("
            SELECT * FROM approvers 
            WHERE document_type = ? AND approve_order > ? 
            ORDER BY approve_order ASC 
            LIMIT 1
        ");
        $nextStmt->execute([$docType, $currentOrder]);
        $nextApprover = $nextStmt->fetch(PDO::FETCH_ASSOC);

        if ($nextApprover) {
            // Forward to next stage
            $nextOrder = (int)$nextApprover['approve_order'];
            $nextRole  = $nextApprover['approver_role'];

            $upd = $db->prepare("
                UPDATE approvals 
                SET approve_order = ?, current_approver_role = ?, action_by_user_id = ?, action_at = NOW(), remarks = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$nextOrder, $nextRole, $userId, $remarks, $approvalId]);

            // Log
            $logStmt = $db->prepare("
                INSERT INTO approval_logs (approval_id, document_type, document_id, from_order, to_order, action, action_by_user_id, action_by_name, action_by_role, remarks, created_at)
                VALUES (?, ?, ?, ?, ?, 'approve', ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([$approvalId, $docType, $docId, $currentOrder, $nextOrder, $userId, $userName, $userRole, $remarks]);

            return [
                'status' => 'forwarded',
                'order' => $nextOrder,
                'role' => $nextRole,
                'message' => "Approved at Stage {$currentOrder} and forwarded to Stage {$nextOrder} ({$nextApprover['approver_title']})."
            ];
        } else {
            // Final Approval -> 777
            $upd = $db->prepare("
                UPDATE approvals 
                SET approve_order = ?, current_approver_role = NULL, action_by_user_id = ?, action_at = NOW(), remarks = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([APPROVE_ORDER_APPROVED, $userId, $remarks, $approvalId]);

            // Log
            $logStmt = $db->prepare("
                INSERT INTO approval_logs (approval_id, document_type, document_id, from_order, to_order, action, action_by_user_id, action_by_name, action_by_role, remarks, created_at)
                VALUES (?, ?, ?, ?, ?, 'approve', ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([$approvalId, $docType, $docId, $currentOrder, APPROVE_ORDER_APPROVED, $userId, $userName, $userRole, $remarks]);

            // Execute Document Specific Final Actions
            if ($docType === 'resident_signup') {
                $assignedHh = clean($extraData['assigned_household_no'] ?? '');

                // Ensure resident has code
                $resStmt = $db->prepare("SELECT * FROM residents WHERE id = ?");
                $resStmt->execute([$docId]);
                $res = $resStmt->fetch(PDO::FETCH_ASSOC);

                if ($res && empty($res['resident_code'])) {
                    $uniqueCode = generate_resident_code($db);
                    $db->prepare("UPDATE residents SET resident_code = ? WHERE id = ?")->execute([$uniqueCode, $docId]);
                }

                if (!empty($assignedHh) && !in_array(strtolower($assignedHh), ['n/a', 'na', 'none', 'null', '-'])) {
                    $db->prepare("
                        UPDATE residents 
                        SET status = 'approved', approved_at = NOW(), approved_by_user_id = ?, household_no = ?, rejection_reason = NULL 
                        WHERE id = ?
                    ")->execute([$userId, $assignedHh, $docId]);
                } else {
                    $db->prepare("
                        UPDATE residents 
                        SET status = 'approved', approved_at = NOW(), approved_by_user_id = ?, rejection_reason = NULL 
                        WHERE id = ?
                    ")->execute([$userId, $docId]);
                }

                if (!empty($res['user_id'])) {
                    $db->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$res['user_id']]);
                }

                log_activity('Centralized Approval: Resident Approved', "Resident #{$docId} ({$approval['title']}) approved [Code: 777] by {$userName}");
            } elseif ($docType === 'assistance_program') {
                $db->prepare("UPDATE assistance_programs SET status = 'Ongoing' WHERE id = ?")->execute([$docId]);
                log_activity('Centralized Approval: Assistance Program Approved', "Program #{$docId} ({$approval['title']}) approved [Code: 777] by {$userName} and set to Ongoing.");
            }

            return [
                'status' => 'approved',
                'order' => APPROVE_ORDER_APPROVED,
                'message' => "Final approval successfully granted [Code: 777] for {$approval['title']}."
            ];
        }

    } elseif ($action === 'reject') {
        // Rejection -> 555
        $upd = $db->prepare("
            UPDATE approvals 
            SET approve_order = ?, current_approver_role = NULL, action_by_user_id = ?, action_at = NOW(), remarks = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([APPROVE_ORDER_REJECTED, $userId, $remarks, $approvalId]);

        // Log
        $logStmt = $db->prepare("
            INSERT INTO approval_logs (approval_id, document_type, document_id, from_order, to_order, action, action_by_user_id, action_by_name, action_by_role, remarks, created_at)
            VALUES (?, ?, ?, ?, ?, 'reject', ?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([$approvalId, $docType, $docId, $currentOrder, APPROVE_ORDER_REJECTED, $userId, $userName, $userRole, $remarks]);

        // Document specific rejection
        if ($docType === 'resident_signup') {
            $db->prepare("
                UPDATE residents 
                SET status = 'rejected', approved_at = NOW(), approved_by_user_id = ?, rejection_reason = ? 
                WHERE id = ?
            ")->execute([$userId, $remarks, $docId]);

            $resStmt = $db->prepare("SELECT user_id FROM residents WHERE id = ?");
            $resStmt->execute([$docId]);
            $resUserId = $resStmt->fetchColumn();
            if ($resUserId) {
                $db->prepare("UPDATE users SET status = 'rejected' WHERE id = ?")->execute([$resUserId]);
            }

            log_activity('Centralized Approval: Resident Rejected', "Resident #{$docId} ({$approval['title']}) rejected [Code: 555] by {$userName}. Reason: {$remarks}");
        } elseif ($docType === 'assistance_program') {
            $db->prepare("UPDATE assistance_programs SET status = 'Rejected' WHERE id = ?")->execute([$docId]);
            log_activity('Centralized Approval: Assistance Program Rejected', "Program #{$docId} ({$approval['title']}) rejected [Code: 555] by {$userName}. Reason: {$remarks}");
        }

        return [
            'status' => 'rejected',
            'order' => APPROVE_ORDER_REJECTED,
            'message' => "Rejected [Code: 555] for {$approval['title']}."
        ];
    }

    throw new Exception("Invalid approval action: {$action}");
}

/**
 * Return count of pending approvals awaiting a specific role
 */
function get_pending_approvals_count_for_role($role, $db = null) {
    if (!$db) $db = getDB();
    if ($role === 'captain') {
        // Captain can view all pending hierarchy stages or specifically captain stages
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM approvals 
            WHERE approve_order NOT IN (0, 777, 555) 
              AND (current_approver_role = 'captain' OR current_approver_role IS NULL)
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } else {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM approvals 
            WHERE approve_order NOT IN (0, 777, 555) 
              AND current_approver_role = ?
        ");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }
}

