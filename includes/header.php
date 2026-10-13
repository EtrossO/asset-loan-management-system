<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$csrf_token = csrf_token();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$is_login_page = ($current_page === 'login');

$page_titles = [
    'dashboard'  => 'Dashboard',
    'assets'     => 'Assets',
    'borrowers'  => 'Borrowers',
    'loans'      => 'Loans',
    'reports'    => 'Reports',
    'users'      => 'User Management',
];
$page_title = $page_titles[$current_page] ?? '';
$site_name = 'ICT Asset Loan System';
$title = $page_title ? e($page_title) . ' - ' . $site_name : $site_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php if (!$is_login_page && is_logged_in()): ?>
<div class="app-layout">
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <span class="brand-icon"><i class="bi bi-box-seam-fill"></i></span>
            <span>Asset Loans</span>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="assets.php" class="nav-link <?= $current_page === 'assets' ? 'active' : '' ?>">
                <i class="bi bi-box"></i> Assets
            </a>
            <a href="borrowers.php" class="nav-link <?= $current_page === 'borrowers' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Borrowers
            </a>
            <a href="loans.php" class="nav-link <?= $current_page === 'loans' ? 'active' : '' ?>">
                <i class="bi bi-clipboard-check"></i> Loans
            </a>

            <div class="nav-section">Insights</div>
            <a href="reports.php" class="nav-link <?= $current_page === 'reports' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i> Reports
            </a>
            <?php if (current_user_role() === 'admin'): ?>
            <a href="users.php" class="nav-link <?= $current_page === 'users' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Users
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?= strtoupper(substr(current_user_name(), 0, 1)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars(current_user_name()) ?></div>
                    <div class="user-role"><?= htmlspecialchars(current_user_role()) ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-sm w-100 mt-2" style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.1);min-height:36px;font-size:var(--fs-xs);">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <button class="topbar-toggler" id="sidebarToggle" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="topbar-title"><?= e($page_title) ?></h1>
            <div class="topbar-actions">
                <span class="topbar-date d-none d-md-inline"><?= date('M j, Y') ?></span>
            </div>
        </header>

        <div class="page-content">
<?php endif; ?>
