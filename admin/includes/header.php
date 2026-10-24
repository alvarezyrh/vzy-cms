<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> - <?= e(getSetting('site_name', 'CMS')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
<div class="wrapper">
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="btn btn-icon btn-secondary" id="sidebarToggle" style="display:none;">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h1 class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
                <?php if (!empty($breadcrumb)): ?>
                <div class="breadcrumb">
                    <a href="index.php">Dashboard</a>
                    <i class="fas fa-chevron-right" style="font-size:10px"></i>
                    <span><?= e($breadcrumb) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="topbar-right">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari..." id="globalSearch">
            </div>
            <div class="user-menu" id="userMenu">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= e($user['full_name'] ?? $user['username']) ?></div>
                    <div class="user-role"><?= e($user['role']) ?></div>
                </div>
                <i class="fas fa-chevron-down" style="font-size:11px;color:var(--text-muted)"></i>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
                    <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                    <hr style="border:none;border-top:1px solid var(--border);margin:6px 0">
                    <a href="logout.php" class="dropdown-item danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
        <?= e($flash['message']) ?>
    </div>
    <?php endif; ?>
