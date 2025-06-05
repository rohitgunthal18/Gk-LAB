<?php
/**
 * Admin - Reports Dashboard
 * 
 * This page displays analytics and reports for the business.
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
    header('Location: ../../login.php');
    exit;
}

// Include database connection
include_once '../../config/db.php';
include_once '../../includes/functions.php';

// Get date range
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : date('Y-m-d'); // Today

// Calculate summary statistics
$total_sales = 0;
$total_tests = 0;
$total_appointments = 0;
$total_customers = 0;

// Total sales in date range
$stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE created_at BETWEEN ? AND ? AND order_status != 'cancelled'");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_sales = $row['total'] ?: 0;
}

// Total tests ordered in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.created_at BETWEEN ? AND ? AND o.order_status != 'cancelled'");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_tests = $row['total'];
}

// Total appointments in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date BETWEEN ? AND ? AND appointment_status != 'cancelled'");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_appointments = $row['total'];
}

// Total new customers in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_customers = $row['total'];
}

// Get top selling tests
$top_tests = [];
$stmt = $conn->prepare("
    SELECT t.name, COUNT(oi.id) as count, SUM(oi.price) as revenue
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    JOIN tests t ON oi.test_id = t.id
    WHERE o.created_at BETWEEN ? AND ? AND o.order_status != 'cancelled'
    GROUP BY t.id
    ORDER BY count DESC
    LIMIT 5
");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $top_tests[] = $row;
}

// Get monthly sales trend
$monthly_sales = [];
$stmt = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as sales
    FROM orders
    WHERE created_at >= DATE_SUB(?, INTERVAL 12 MONTH) AND order_status != 'cancelled'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->bind_param("s", $date_to);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $monthly_sales[] = $row;
}

// Include admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Reports Dashboard</h1>
            <p>Analytics and business insights</p>
        </div>
        
        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-header">
                <h2>Date Range</h2>
            </div>
            <div class="card-body">
                <form action="" method="GET" class="form-inline">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date_from">From Date:</label>
                                <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date_to">To Date:</label>
                                <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                            <a href="index.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics Overview -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-primary">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-title">Total Sales</h3>
                            <p class="stat-card-value">₹ <?php echo number_format($total_sales, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-success">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-title">Tests Ordered</h3>
                            <p class="stat-card-value"><?php echo number_format($total_tests); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-warning">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-title">Appointments</h3>
                            <p class="stat-card-value"><?php echo number_format($total_appointments); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-info">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-title">New Customers</h3>
                            <p class="stat-card-value"><?php echo number_format($total_customers); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Top Selling Tests -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2>Top Selling Tests</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_tests)): ?>
                            <p class="text-center">No data available for the selected period.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Test Name</th>
                                            <th>Orders</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_tests as $test): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($test['name']); ?></td>
                                                <td><?php echo $test['count']; ?></td>
                                                <td>₹ <?php echo number_format($test['revenue'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2>Monthly Sales Trend</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthly_sales)): ?>
                            <p class="text-center">No data available for the selected period.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($monthly_sales as $month): ?>
                                            <tr>
                                                <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                                <td>₹ <?php echo number_format($month['sales'], 2); ?></td>
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
        
        <!-- Export Links -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2>Export Reports</h2>
                    </div>
                    <div class="card-body">
                        <div class="export-buttons">
                            <a href="export.php?type=sales&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-primary">
                                <i class="fas fa-download"></i> Export Sales Report
                            </a>
                            <a href="export.php?type=tests&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-success">
                                <i class="fas fa-download"></i> Export Tests Report
                            </a>
                            <a href="export.php?type=appointments&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-warning">
                                <i class="fas fa-download"></i> Export Appointments Report
                            </a>
                            <a href="export.php?type=customers&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-info">
                                <i class="fas fa-download"></i> Export Customers Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Add some custom styling for the reports page -->
<style>
    .stat-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .stat-card-body {
        display: flex;
        padding: 20px;
        align-items: center;
    }
    
    .stat-card-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .stat-card-icon i {
        font-size: 24px;
        color: white;
    }
    
    .stat-card-info {
        flex-grow: 1;
    }
    
    .stat-card-title {
        font-size: 14px;
        color: #777;
        margin-bottom: 5px;
    }
    
    .stat-card-value {
        font-size: 24px;
        font-weight: bold;
        margin: 0;
    }
    
    .bg-primary {
        background-color: #007bff;
    }
    
    .bg-success {
        background-color: #28a745;
    }
    
    .bg-warning {
        background-color: #ffc107;
    }
    
    .bg-info {
        background-color: #17a2b8;
    }
    
    .export-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    @media (max-width: 768px) {
        .stat-card-body {
            flex-direction: column;
            text-align: center;
        }
        
        .stat-card-icon {
            margin-right: 0;
            margin-bottom: 15px;
        }
    }
</style>

<?php
// Include admin footer
include_once '../includes/admin-footer.php';
?> 