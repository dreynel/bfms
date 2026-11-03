<?php
// includes/header.php - Global Header for internal pages
if (defined('BFMS_HEADER_LOADED')) {
    return;
}
define('BFMS_HEADER_LOADED', true);

require_once __DIR__ . '/../config/app.php';

$currentUser = auth_user();
$pageTitle = $pageTitle ?? 'Barangay Lublub Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <?php require_once __DIR__ . '/navbar.php'; ?>

        <main class="p-3 p-md-4 flex-grow-1">
            <div class="container-fluid">
                <?= render_flash() ?>

