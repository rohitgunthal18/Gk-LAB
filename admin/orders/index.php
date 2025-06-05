<?php
/**
 * Admin - Order Management
 * 
 * This page displays all orders and provides order management functionality.
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

// Helper function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle order status update
if (isset($_GET['update_status']) && is_numeric($_GET['update_status']) && isset($_GET['status'])) {
    $order_id = (int) $_GET['update_status'];
    $new_status = sanitize_input($_GET['status']);
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        // Update order status
        if ($new_status === 'completed') {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
        } else {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Order #$order_id status has been updated to " . ucfirst($new_status) . "."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => "Failed to update order status. Please try again."
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => "Invalid order status."
        ];
    }
    
    // Redirect to remove the query string
    header('Location: index.php');
    exit;
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['order_ids']) && !empty($_POST['order_ids'])) {
    $action = $_POST['bulk_action'];
    $order_ids = $_POST['order_ids'];
    
    if (in_array($action, ['processing', 'completed', 'cancelled'])) {
        $ids_string = implode(',', array_map('intval', $order_ids));
        
        // Update orders status
        if ($action === 'completed') {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = 'paid' WHERE id IN ($ids_string)");
            $stmt->bind_param("s", $action);
        } else {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id IN ($ids_string)");
            $stmt->bind_param("s", $action);
        }
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "$affected_rows orders have been updated to " . ucfirst($action) . "."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to update orders. Please try again.'
            ];
        }
    }
    
    // Redirect to remove the post data
    header('Location: index.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search_term = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$search_condition = '';
$params = [];
$param_types = '';

if (!empty($search_term)) {
    $search_condition = "WHERE (o.id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search_term%";
    $params = [$search_param, $search_param, $search_param, $search_param, $search_param];
    $param_types = 'sssss';
}

// Order status filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
if (!empty($status_filter) && in_array($status_filter, $valid_statuses)) {
    if (empty($search_condition)) {
        $search_condition = "WHERE o.order_status = ?";
    } else {
        $search_condition .= " AND o.order_status = ?";
    }
    $params[] = $status_filter;
    $param_types .= 's';
}

// Date range filter
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';

if (!empty($date_from)) {
    if (empty($search_condition)) {
        $search_condition = "WHERE o.created_at >= ?";
    } else {
        $search_condition .= " AND o.created_at >= ?";
    }
    $params[] = $date_from . ' 00:00:00';
    $param_types .= 's';
}

if (!empty($date_to)) {
    if (empty($search_condition)) {
        $search_condition = "WHERE o.created_at <= ?";
    } else {
        $search_condition .= " AND o.created_at <= ?";
    }
    $params[] = $date_to . ' 23:59:59';
    $param_types .= 's';
}

// Get total orders for pagination
$total_orders = 0;
$count_query = "SELECT COUNT(*) as count 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id
                $search_condition";

if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_orders = $row['count'];
} else {
    $result = $conn->query($count_query);
    $row = $result->fetch_assoc();
    $total_orders = $row['count'];
}

$total_pages = ceil($total_orders / $limit);

// Get orders with pagination
$orders = [];
$query = "SELECT o.id, o.total_amount, o.order_status, o.payment_status, o.created_at, 
                 u.first_name, u.last_name, u.email, u.phone,
                 (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          $search_condition
          ORDER BY o.created_at DESC LIMIT ? OFFSET ?";

if (!empty($params)) {
    // Add limit and offset to params array
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<style>
    /* Action buttons styling */
    .btn-group {
        display: flex;
        gap: 5px;
    }
    .btn {
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background 0.3s;
    }
    .btn-info {
        background-color: #17a2b8;
        color: #fff;
    }
    .btn-info:hover {
        background-color: #138496;
    }
    .btn-secondary {
        background-color: #6c757d;
        color: #fff;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    
    /* Dropdown styling */
    .dropdown {
        position: relative;
    }
    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        z-index: 1000;
        min-width: 10rem;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        background-color: #fff;
        border: 1px solid rgba(0,0,0,.15);
        border-radius: 0.25rem;
    }
    .dropdown-menu.show {
        display: block;
    }
    .dropdown-item {
        display: block;
        width: 100%;
        padding: 0.25rem 1.5rem;
        clear: both;
        text-align: inherit;
        white-space: nowrap;
        background-color: transparent;
        color: #212529;
        text-decoration: none;
    }
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    /* Badge styling */
    .badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }
    .badge-success {
        background-color: #28a745;
        color: #fff;
    }
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    .badge-danger {
        background-color: #dc3545;
        color: #fff;
    }
    .badge-primary {
        background-color: #007bff;
        color: #fff;
    }
</style>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-shopping-cart"></i> Order Management</h1>
        </div>

        <!-- Search and Filters -->
        <div class="filters-container">
            <form action="" method="GET" class="filter-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <div class="filter-group">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date">
                </div>
                
                <div class="filter-group">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date">
                    <button type="submit" class="btn-filter">Filter</button>
                </div>
                
                <?php if (!empty($search_term) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                    <a href="index.php" class="btn-clear-filter">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form action="" method="POST" id="orders-form">
            <div class="bulk-actions">
                <select name="bulk_action" class="form-control">
                    <option value="">Bulk Actions</option>
                    <option value="processing">Mark as Processing</option>
                    <option value="completed">Mark as Completed</option>
                    <option value="cancelled">Mark as Cancelled</option>
                </select>
                <button type="submit" class="btn bulk-delete-btn">Apply</button>
                <span class="bulk-counter"><span class="selected-count">0</span> items selected</span>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" class="select-all" title="Select All">
                                    </th>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Order Status</th>
                                    <th>Payment Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="9" class="no-results">No orders found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="checkbox-cell">
                                                <input type="checkbox" name="order_ids[]" value="<?php echo $order['id']; ?>" class="table-checkbox">
                                            </td>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                                <div><small><?php echo htmlspecialchars($order['email']); ?></small></div>
                                            </td>
                                            <td><?php echo $order['items_count']; ?></td>
                                            <td>₹ <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $order['order_status'] === 'completed' ? 'success' : 
                                                        ($order['order_status'] === 'processing' ? 'primary' : 
                                                        ($order['order_status'] === 'cancelled' ? 'danger' : 'warning')); 
                                                ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view.php?id=<?php echo $order['id']; ?>" class="btn btn-info" title="View Order">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <div class="dropdown">
                                                        <a href="#" class="btn btn-secondary dropdown-toggle" title="Change Status">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </a>
                                                        <div class="dropdown-menu">
                                                            <a href="index.php?update_status=<?php echo $order['id']; ?>&status=pending" class="dropdown-item">Pending</a>
                                                            <a href="index.php?update_status=<?php echo $order['id']; ?>&status=processing" class="dropdown-item">Processing</a>
                                                            <a href="index.php?update_status=<?php echo $order['id']; ?>&status=completed" class="dropdown-item">Completed</a>
                                                            <a href="index.php?update_status=<?php echo $order['id']; ?>&status=cancelled" class="dropdown-item">Cancelled</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>" class="pagination-link prev">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($start_page + 4, $total_pages);
                
                if ($end_page - $start_page < 4 && $total_pages > 4) {
                    $start_page = max(1, $end_page - 4);
                }
                
                if ($start_page > 1): ?>
                    <a href="?page=1<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>" class="pagination-link">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>" class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>" class="pagination-link">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>" class="pagination-link next">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Add dropdown functionality 
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const parent = this.parentElement;
                const menu = parent.querySelector('.dropdown-menu');
                
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(function(el) {
                    if (el !== menu) {
                        el.classList.remove('show');
                    }
                });
                
                // Toggle current dropdown
                menu.classList.toggle('show');
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.dropdown-toggle') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(function(el) {
                    el.classList.remove('show');
                });
            }
        });
    });
</script>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 