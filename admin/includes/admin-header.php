<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Set flash message
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'You do not have permission to access the admin area.'
    ];
    
    // Redirect to login page
    header('Location: ../../login.php');
    exit;
}

// Include database connection
require_once dirname(dirname(__DIR__)) . '/connection.php';

/**
 * Admin Header Template
 * 
 * Contains the admin header with navigation and user dropdown
 */

// Determine active page based on current URL
$current_page = basename(dirname($_SERVER['PHP_SELF']));
if ($current_page == 'admin') {
    $current_page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GK Lab Admin - <?php echo ucfirst($current_page); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php
    // Calculate path to admin directory
    $adminPath = '';
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $adminPos = strpos($currentDir, '/admin');
    if ($adminPos !== false) {
        $subfolderDepth = substr_count(substr($currentDir, $adminPos + 6), '/');
        $adminPath = str_repeat('../', $subfolderDepth);
    }
    ?>
    <link rel="stylesheet" href="<?php echo $adminPath; ?>css/admin-styles.css">
    <script src="<?php echo $adminPath; ?>js/responsive-tables.js"></script>
    <style>
        <?php include_once __DIR__ . '/../css/admin-inline.css'; ?>
        
        /* Mobile sidebar styles */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Mobile menu button styling */
        .sidebar-toggle-mobile {
            display: none;
            background: none;
            border: none;
            color: #16A085;
            cursor: pointer;
            font-size: 24px;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle-mobile:hover {
            background-color: rgba(22, 160, 133, 0.1);
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                position: fixed;
                left: -250px;
                width: 250px;
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
                z-index: 1050;
                transition: left 0.3s ease;
            }
            
            .admin-sidebar.active {
                left: 0;
            }
            
            .admin-main {
                margin-left: 0;
                width: 100%;
            }
            
            .admin-wrapper.sidebar-collapsed .admin-main {
                margin-left: 0;
            }
            
            .sidebar-toggle-mobile {
                display: flex;
            }
            
            body.sidebar-open {
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay"></div>
        
        <!-- Sidebar -->
        <?php include_once __DIR__ . '/admin-sidebar.php'; ?>
        
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
                            <a href="<?php echo $adminPath; ?>settings/profile.php">
                                <i class="fas fa-user-cog"></i>
                                <span>Profile</span>
                            </a>
                            <a href="<?php echo $adminPath; ?>../">
                                <i class="fas fa-home"></i>
                                <span>Main Site</span>
                            </a>
                            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Logout Form -->
            <form id="logout-form" action="<?php echo $adminPath; ?>../logout.php" method="POST" style="display: none;">
                <input type="hidden" name="logout" value="1">
            </form>
            
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