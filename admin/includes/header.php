<?php
/**
 * Admin Header
 * 
 * This file contains the admin header and sidebar navigation.
 */

// Get current page
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Include functions if not already included
if (!function_exists('is_admin')) {
    include_once '../../includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GK Lab Admin - Diagnostic Services</title>
    <!-- Early admin styling -->
    <script>
    // Apply admin wrapper class to body immediately
    document.documentElement.classList.add('admin-page');
    </script>
    <script src="<?php echo $current_file === 'index.php' ? '../js/admin-init.js' : '../../js/admin-init.js'; ?>"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Fallback inline CSS to ensure admin styling works -->
    <style>
    :root {
        --primary-green: #16A085;
        --secondary-green: #27AE60;
        --primary-orange: #FF8A00;
        --text-dark: #333333;
        --text-gray: #666666;
        --background-light: #F8F9FA;
        --sidebar-bg: #2c3e50;
        --sidebar-active: #16A085;
        --white: #FFFFFF;
        --red: #e74c3c;
        --yellow: #f39c12;
        --blue: #3498db;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        background-color: var(--background-light);
    }

    .admin-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .admin-sidebar {
        width: 250px;
        background-color: var(--sidebar-bg);
        color: var(--white);
        position: fixed;
        left: 0;
        top: 0;
        height: 100%;
        overflow-y: auto;
        z-index: 100;
    }

    .admin-main {
        flex: 1;
        margin-left: 250px;
    }

    .sidebar-header {
        padding: 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logo {
        display: flex;
        align-items: center;
    }

    .logo a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: var(--white);
    }

    .logo img {
        width: 40px;
        height: 40px;
        margin-right: 10px;
    }

    .logo span {
        font-size: 18px;
        font-weight: 600;
    }

    .sidebar-nav {
        padding: 15px 0;
    }

    .sidebar-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-nav li {
        margin-bottom: 5px;
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: var(--white);
        text-decoration: none;
    }

    .sidebar-nav a i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .sidebar-nav li.active a {
        background-color: var(--sidebar-active);
        border-left: 3px solid var(--primary-orange);
    }

    .admin-header {
        background-color: var(--white);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 99;
    }

    .admin-content {
        padding: 20px;
    }

    .dashboard-container {
        padding: 20px 0;
    }

    @media (max-width: 768px) {
        .admin-sidebar {
            width: 70px;
        }
        
        .admin-main {
            margin-left: 70px;
        }
        
        .logo span, 
        .sidebar-nav a span {
            display: none;
        }
    }
    </style>
    
    <?php
    // Determine the proper path to CSS files
    $basePath = $current_file === 'index.php' ? '../' : '../../';
    
    // Check if local CSS exists in admin/css directory first
    if (file_exists(__DIR__ . '/../css/style.css')) {
        echo '<link rel="stylesheet" href="../css/style.css">';
        echo '<link rel="stylesheet" href="../css/admin-styles.css">';
        echo '<link rel="stylesheet" href="../css/responsive.css">';
        echo '<script src="../js/admin.js" defer></script>';
        echo '<script src="../js/responsive.js" defer></script>';
    } else {
        echo '<link rel="stylesheet" href="' . $basePath . 'css/style.css">';
        echo '<link rel="stylesheet" href="' . $basePath . 'css/admin-styles.css">';
        echo '<link rel="stylesheet" href="' . $basePath . 'css/responsive.css">';
        echo '<script src="' . $basePath . 'js/admin.js" defer></script>';
        echo '<script src="' . $basePath . 'js/responsive.js" defer></script>';
    }
    ?>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay"></div>
        
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <a href="<?php echo $current_file === 'index.php' ? './' : '../'; ?>">
                        <img src="<?php echo $current_file === 'index.php' ? '../' : '../../'; ?>assets/images/logo.png" alt="GK Lab Logo">
                        <span>Admin Panel</span>
                    </a>
                </div>
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo $current_file === 'index.php' && $current_dir === 'admin' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './' : '../'; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $current_dir === 'tests' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './tests/' : ($current_dir === 'tests' ? './' : '../tests/'); ?>">
                            <i class="fas fa-flask"></i>
                            <span>Tests</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $current_dir === 'categories' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './categories/' : ($current_dir === 'categories' ? './' : '../categories/'); ?>">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $current_dir === 'orders' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './orders/' : ($current_dir === 'orders' ? './' : '../orders/'); ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $current_dir === 'appointments' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './appointments/' : ($current_dir === 'appointments' ? './' : '../appointments/'); ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $current_dir === 'users' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './users/' : ($current_dir === 'users' ? './' : '../users/'); ?>">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $current_dir === 'reports' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './reports/' : ($current_dir === 'reports' ? './' : '../reports/'); ?>">
                            <i class="fas fa-file-medical-alt"></i>
                            <span>Test Reports</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $current_dir === 'features' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './features/' : ($current_dir === 'features' ? './' : '../features/'); ?>">
                            <i class="fas fa-sliders-h"></i>
                            <span>Feature Sliders</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $current_dir === 'settings' ? 'active' : ''; ?>">
                        <a href="<?php echo $current_file === 'index.php' ? './settings/' : ($current_dir === 'settings' ? './' : '../settings/'); ?>">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Admin Header -->
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle-mobile">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="header-right">
                    <div class="admin-user-dropdown">
                        <button class="user-dropdown-toggle">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="user-name"><?php echo $_SESSION['user_first_name']; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-dropdown-menu">
                            <a href="<?php echo $current_file === 'index.php' ? './settings/profile.php' : '../settings/profile.php'; ?>">
                                <i class="fas fa-user-cog"></i>
                                <span>Profile</span>
                            </a>
                            <a href="<?php echo $current_file === 'index.php' ? '../' : '../../'; ?>">
                                <i class="fas fa-home"></i>
                                <span>Main Site</span>
                            </a>
                            <a href="<?php echo $current_file === 'index.php' ? '../logout.php' : '../../logout.php'; ?>">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Flash Messages -->
            <div class="flash-messages">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?>">
                        <?php echo $_SESSION['flash_message']['message']; ?>
                        <button class="alert-close"><i class="fas fa-times"></i></button>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>
            </div>
            
            <!-- Main Content Area -->
            <div class="admin-content">
                <!-- Content will be inserted here -->