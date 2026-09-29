<?php
/**
 * SIWES360 - Common Navbar Topbar
 */
$userName = $_SESSION['full_name'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'guest';
$userInitials = strtoupper(substr($userName, 0, 1));
?>
<main class="app-main">
    <header class="app-navbar">
        <div class="navbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation">☰</button>
            <span class="page-breadcrumb"><?= isset($pageTitle) ? e($pageTitle) : 'Overview' ?></span>
        </div>

        <div class="navbar-right">
            <div class="user-chip">
                <div class="user-avatar"><?= e($userInitials) ?></div>
                <div class="user-meta">
                    <span class="user-name"><?= e($userName) ?></span>
                    <span class="user-role-badge"><?= e($userRole) ?></span>
                </div>
            </div>
            <a href="/siwes360/logout.php" class="btn btn-secondary btn-sm" title="Sign out of current session">Logout</a>
        </div>
    </header>

    <div class="content-area">
        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
