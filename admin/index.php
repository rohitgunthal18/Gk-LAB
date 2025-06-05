<?php
/**
 * Admin Dashboard
 * 
 * This is the main admin dashboard page.
 */

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
    header('Location: ../login.php');
    exit;
}

// Include database connection
require_once '../connection.php';

// Set page title and current section
$page_title = "Dashboard";
$current_page = "dashboard";

// Include admin header
require_once 'includes/admin-header.php';

// Dashboard statistics
$stats = [
    'total_users' => 0,
    'total_orders' => 0,
    'total_tests' => 0,
    'pending_appointments' => 0,
    'revenue_today' => 0,
    'revenue_month' => 0
];

// Get total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total_users'] = $row['count'];
}

// Get total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total_orders'] = $row['count'];
}

// Get total tests
$result = $conn->query("SELECT COUNT(*) as count FROM tests");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total_tests'] = $row['count'];
}

// Get pending appointments
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_status = 'pending'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['pending_appointments'] = $row['count'];
}

// Get today's revenue
$today = date('Y-m-d');
$result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(created_at) = '$today' AND payment_status = 'paid'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['revenue_today'] = $row['revenue'] ? $row['revenue'] : 0;
}

// Get this month's revenue
$month_start = date('Y-m-01');
$result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE created_at >= '$month_start' AND payment_status = 'paid'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['revenue_month'] = $row['revenue'] ? $row['revenue'] : 0;
}

// Get recent orders
$recentOrders = [];
$result = $conn->query("SELECT o.id, o.created_at, o.total_amount, o.order_status, u.first_name, u.last_name
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        ORDER BY o.created_at DESC
                        LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

// Get upcoming appointments
$upcomingAppointments = [];
$result = $conn->query("SELECT a.id, a.appointment_date, a.appointment_time, a.appointment_status, u.first_name, u.last_name
                        FROM appointments a
                        JOIN users u ON a.user_id = u.id
                        WHERE a.appointment_date >= CURDATE() AND a.appointment_status NOT IN ('completed', 'cancelled')
                        ORDER BY a.appointment_date, a.appointment_time
                        LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $upcomingAppointments[] = $row;
    }
}
?>

            <div class="admin-content">
                <div class="dashboard-container">
        <h1>Welcome to GK Lab Admin</h1>
        <p>Here's an overview of your diagnostic center's performance.</p>
                    
                    <!-- Stats Cards -->
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-details">
                                <h3>Total Users</h3>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-details">
                                <h3>Total Orders</h3>
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div class="stat-details">
                                <h3>Total Tests</h3>
                    <div class="stat-value"><?php echo $stats['total_tests']; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-details">
                                <h3>Pending Appointments</h3>
                    <div class="stat-value"><?php echo $stats['pending_appointments']; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-details">
                                <h3>Today's Revenue</h3>
                    <div class="stat-value">₹<?php echo number_format($stats['revenue_today'], 2); ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-details">
                                <h3>Monthly Revenue</h3>
                    <div class="stat-value">₹<?php echo number_format($stats['revenue_month'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Data -->
                    <div class="recent-data-container">
                        <!-- Recent Orders -->
                        <div class="data-card">
                            <div class="card-header">
                                <h2>Recent Orders</h2>
                                <a href="orders/" class="btn-view-all">View All</a>
                            </div>
                            <div class="card-content">
                                <?php if (empty($recentOrders)): ?>
                        <div class="no-data">No recent orders found.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                    <th>ID</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                    <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($order['order_status']); ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Upcoming Appointments -->
                        <div class="data-card">
                            <div class="card-header">
                                <h2>Upcoming Appointments</h2>
                                <a href="appointments/" class="btn-view-all">View All</a>
                            </div>
                            <div class="card-content">
                                <?php if (empty($upcomingAppointments)): ?>
                        <div class="no-data">No upcoming appointments found.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Patient</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                                <tr>
                                                    <td>#<?php echo $appointment['id']; ?></td>
                                        <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($appointment['appointment_status']); ?>"><?php echo ucfirst($appointment['appointment_status']); ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    </div>
                                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include admin footer
require_once 'includes/admin-footer.php';
?> 