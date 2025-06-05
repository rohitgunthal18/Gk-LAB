<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
include_once 'config/db.php';
require_once 'includes/functions.php';

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get cart count if available
$cart_count = 0;
if (isset($conn)) {
    $cart_count = get_cart_count($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GK Lab - Diagnostic Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <!-- Header Section -->
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/images/logo.png" alt="GK Lab Logo">
                    </a>
                </div>
                
                <nav class="main-nav">
                    <ul class="nav-links">
                        <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                            <a href="index.php">Home</a>
                        </li>
                        <li class="<?php echo ($current_page == 'tests.php') ? 'active' : ''; ?>">
                            <a href="tests.php">Tests</a>
                        </li>
                        <li class="<?php echo ($current_page == 'checkups.php') ? 'active' : ''; ?>">
                            <a href="checkups.php">Checkups</a>
                        </li>
                        <li class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                            <a href="about.php">About Us</a>
                        </li>
                        <li class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
                            <a href="contact.php">Contact</a>
                        </li>
                    </ul>
                </nav>
                
                <div class="header-actions">
                    <a href="cart.php" class="btn-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if (is_logged_in()): ?>
                    <div class="user-dropdown">
                        <button class="btn-user">
                            <i class="fas fa-user"></i>
                            <span><?php echo $_SESSION['user_first_name']; ?></span>
                        </button>
                        <div class="user-dropdown-menu">
                            <a href="profile.php">My Profile</a>
                            <a href="orders.php">My Orders</a>
                            <a href="appointments.php">My Appointments</a>
                            <?php if (is_admin()): ?>
                            <a href="admin/index.php">Admin Dashboard</a>
                            <?php endif; ?>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                    <?php endif; ?>
                    
                    <div class="mobile-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu (outside header for better positioning) -->
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <div class="mobile-logo">
                <a href="index.php">
                    <img src="assets/images/logo.png" alt="GK Lab Logo">
                </a>
            </div>
            <div class="mobile-menu-close">
                <i class="fas fa-times"></i>
            </div>
        </div>
        
        <nav class="mobile-nav">
            <ul>
                <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <a href="index.php">Home</a>
                </li>
                <li class="<?php echo ($current_page == 'tests.php') ? 'active' : ''; ?>">
                    <a href="tests.php">Tests</a>
                </li>
                <li class="<?php echo ($current_page == 'checkups.php') ? 'active' : ''; ?>">
                    <a href="checkups.php">Checkups</a>
                </li>
                <li class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                    <a href="about.php">About Us</a>
                </li>
                <li class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
                    <a href="contact.php">Contact</a>
                </li>
            </ul>
        </nav>
        
        <div class="mobile-actions">
            <a href="cart.php" class="mobile-cart-link">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                <span class="cart-count"><?php echo $cart_count; ?></span>
                <?php endif; ?>
                <span>Cart</span>
            </a>
            
            <?php if (is_logged_in()): ?>
            <div class="mobile-user-icon">
                <i class="fas fa-user"></i>
                <span><?php echo $_SESSION['user_first_name']; ?></span>
            </div>
            
            <div class="mobile-user-dropdown">
                <a href="profile.php">My Profile</a>
                <a href="orders.php">My Orders</a>
                <a href="appointments.php">My Appointments</a>
                <?php if (is_admin()): ?>
                <a href="admin/index.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
            <?php else: ?>
            <div class="mobile-auth-links">
                <a href="login.php" class="btn-mobile-login">Login</a>
                <a href="register.php" class="btn-mobile-register">Register</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <div class="container">
        <?php display_flash_message(); ?>
    </div>
    
    <!-- Main Content Section -->
    <main class="main-content"><?php // Main content will be inserted here ?> 