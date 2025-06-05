<?php
/**
 * Admin Sidebar Navigation
 * 
 * This file contains the sidebar navigation for the admin panel.
 * It is included by admin-header.php
 */

// Use current_page from the including file
if (!isset($current_page)) {
    $current_page = basename(dirname($_SERVER['PHP_SELF']));
    if ($current_page == 'admin') {
        $current_page = 'dashboard';
    }
}

// Use adminPath from the including file
if (!isset($adminPath)) {
    $adminPath = '';
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $adminPos = strpos($currentDir, '/admin');
    if ($adminPos !== false) {
        $subfolderDepth = substr_count(substr($currentDir, $adminPos + 6), '/');
        $adminPath = str_repeat('../', $subfolderDepth);
    }
}
?>
<!-- Admin Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <a href="<?php echo $adminPath; ?>index.php">
                <div class="logo-icon">
                    <i class="fas fa-flask"></i>
                </div>
                <span class="logo-text">GK Lab</span>
                <span class="panel-text">Admin</span>
            </a>
        </div>
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'tests' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>tests/">
                    <i class="fas fa-flask"></i>
                    <span>Tests</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'checkups' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>checkups/">
                    <i class="fas fa-box"></i>
                    <span>Checkups</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'categories' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>categories/">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>orders/">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'appointments' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>appointments/">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'users' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>users/">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>reports/">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>Test Reports</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'messages' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>messages/" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php
                    // Get count of new messages if database connection exists
                    if (isset($conn)) {
                        $result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
                        if ($result && $row = $result->fetch_assoc()) {
                            if ($row['count'] > 0) {
                                echo '<span class="badge badge-danger">' . $row['count'] . '</span>';
                            }
                        }
                    }
                    ?>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'coupons' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>coupons/">
                    <i class="fas fa-tags"></i>
                    <span>Coupons</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                <a href="<?php echo $adminPath; ?>settings/">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
</aside> 