<?php
/**
 * Urdu Books World - Admin Layout Header
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Force admin login
require_admin();

$admin_user = current_user();
$site_name = get_setting('site_name', 'Urdu Books World');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo e($site_name); ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icon Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Console CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
</head>
<body class="admin-body">

    <!-- Top Admin Bar -->
    <div class="admin-topbar">
        <div class="admin-topbar-brand">
            <a href="<?php echo SITE_URL; ?>/admin/index.php">
                <i class="fa fa-book-open text-gold"></i> <?php echo e($site_name); ?> <span class="badge badge-admin">Admin</span>
            </a>
        </div>
        <div class="admin-topbar-menu">
            <a href="<?php echo SITE_URL; ?>/index.php" target="_blank" class="admin-topbar-link">
                <i class="fa fa-globe"></i> View Website
            </a>
            <span class="topbar-sep">|</span>
            <span class="admin-user-info">
                <i class="fa fa-user-shield text-gold"></i> <?php echo e($admin_user['first_name'] . ' ' . $admin_user['last_name']); ?>
            </span>
            <span class="topbar-sep">|</span>
            <a href="<?php echo SITE_URL; ?>/admin/logout.php" class="admin-topbar-link link-logout">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Admin Panel Layout Wrapper -->
    <div class="admin-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <ul class="admin-menu">
                <li class="admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/index.php" class="admin-menu-link">
                        <i class="fa fa-tachometer-alt admin-menu-icon"></i> Dashboard
                    </a>
                </li>
                <li class="admin-menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'books.php' || basename($_SERVER['PHP_SELF']) == 'book-edit.php') ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/books.php" class="admin-menu-link">
                        <i class="fa fa-book admin-menu-icon"></i> Books Catalog
                    </a>
                </li>
                <li class="admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="admin-menu-link">
                        <i class="fa fa-folder-open admin-menu-icon"></i> Categories
                    </a>
                </li>
                <li class="admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'authors.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/authors.php" class="admin-menu-link">
                        <i class="fa fa-pen-nib admin-menu-icon"></i> Authors
                    </a>
                </li>
                <li class="admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'publishers.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/publishers.php" class="admin-menu-link">
                        <i class="fa fa-building admin-menu-icon"></i> Publishers
                    </a>
                </li>
                <li class="admin-menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php' || basename($_SERVER['PHP_SELF']) == 'order-view.php') ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="admin-menu-link">
                        <i class="fa fa-shopping-cart admin-menu-icon"></i> Orders
                    </a>
                </li>
                <li class="admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="admin-menu-link">
                        <i class="fa fa-users admin-menu-icon"></i> Users & Roles
                    </a>
                </li>
                <li class="admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="admin-menu-link">
                        <i class="fa fa-sliders-h admin-menu-icon"></i> Store Settings
                    </a>
                </li>
                <li class="admin-menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'book-requests.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/admin/book-requests.php" class="admin-menu-link">
                        <i class="fa fa-envelope-open-text admin-menu-icon"></i> Book Requests
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Admin Content Area -->
        <main class="admin-main">
