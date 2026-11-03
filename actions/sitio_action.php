<?php
// actions/sitio_action.php - Handles BHW territorial jurisdiction assignment
require_once __DIR__ . '/../config/app.php';

require_role('captain', 'secretary');

$db = getDB();
$user = auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_bhw') {
    $sitioId = (int)($_POST['sitio_id'] ?? 0);
    $bhwId   = (int)($_POST['bhw_user_id'] ?? 0);

    if ($sitioId > 0 && $bhwId > 0) {
        try {
            // Unassign current bhw from this sitio if any
            $db->prepare("UPDATE users SET assigned_sitio_id = NULL WHERE assigned_sitio_id = ? AND role = 'bhw'")->execute([$sitioId]);
            // Assign new bhw
            $db->prepare("UPDATE users SET assigned_sitio_id = ? WHERE id = ? AND role = 'bhw'")->execute([$sitioId, $bhwId]);

            $bhwName = $db->query("SELECT full_name FROM users WHERE id = {$bhwId}")->fetchColumn();
            log_activity('Assign BHW', "Assigned {$bhwName} to Sitio " . SITIOS_LIST[$sitioId]);
            set_flash('success', "Sitio jurisdiction updated successfully! <strong>{$bhwName}</strong> is now assigned to <strong>" . SITIOS_LIST[$sitioId] . "</strong>.");
        } catch (Exception $e) {
            set_flash('danger', 'Error updating BHW assignment: ' . $e->getMessage());
        }
    } else {
        set_flash('warning', 'Please select a valid Sitio and Barangay Health Worker.');
    }
    redirect('sitios');
}

