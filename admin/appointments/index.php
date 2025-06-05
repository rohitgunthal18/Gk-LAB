<?php
/**
 * Admin - Appointment Management
 * 
 * This page displays all appointments and provides appointment management functionality.
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

// Handle appointment status update
if (isset($_GET['update_status']) && is_numeric($_GET['update_status']) && isset($_GET['status'])) {
    $appointment_id = (int) $_GET['update_status'];
    $new_status = sanitize_input($_GET['status']);
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        // Update appointment status
        $stmt = $conn->prepare("UPDATE appointments SET appointment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $appointment_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Appointment #$appointment_id status has been updated to " . ucfirst($new_status) . "."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => "Failed to update appointment status. Please try again."
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => "Invalid appointment status."
        ];
    }
    
    // Redirect to remove the query string
    header('Location: index.php');
    exit;
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['appointment_ids']) && !empty($_POST['appointment_ids'])) {
    $action = $_POST['bulk_action'];
    $appointment_ids = $_POST['appointment_ids'];
    
    if (in_array($action, ['confirmed', 'completed', 'cancelled'])) {
        $ids_string = implode(',', array_map('intval', $appointment_ids));
        
        // Update appointments status
        $stmt = $conn->prepare("UPDATE appointments SET appointment_status = ? WHERE id IN ($ids_string)");
        $stmt->bind_param("s", $action);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "$affected_rows appointments have been updated to " . ucfirst($action) . "."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to update appointments. Please try again.'
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
    $search_condition = "WHERE (a.id LIKE ? OR a.patient_name LIKE ? OR a.patient_email LIKE ? OR a.patient_phone LIKE ?)";
    $search_param = "%$search_term%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $param_types = 'ssss';
}

// Appointment status filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!empty($status_filter) && in_array($status_filter, $valid_statuses)) {
    if (empty($search_condition)) {
        $search_condition = "WHERE a.appointment_status = ?";
    } else {
        $search_condition .= " AND a.appointment_status = ?";
    }
    $params[] = $status_filter;
    $param_types .= 's';
}

// Date range filter
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';

if (!empty($date_from)) {
    if (empty($search_condition)) {
        $search_condition = "WHERE a.appointment_date >= ?";
    } else {
        $search_condition .= " AND a.appointment_date >= ?";
    }
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    if (empty($search_condition)) {
        $search_condition = "WHERE a.appointment_date <= ?";
    } else {
        $search_condition .= " AND a.appointment_date <= ?";
    }
    $params[] = $date_to;
    $param_types .= 's';
}

// Get total appointments for pagination
$total_appointments = 0;
$count_query = "SELECT COUNT(*) as count 
                FROM appointments a
                $search_condition";

if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_appointments = $row['count'];
} else {
    $result = $conn->query($count_query);
    $row = $result->fetch_assoc();
    $total_appointments = $row['count'];
}

$total_pages = ceil($total_appointments / $limit);

// Get appointments with pagination
$appointments = [];
$query = "SELECT a.id, a.patient_name AS name, a.patient_email AS email, a.patient_phone AS phone, a.appointment_date, a.appointment_time, a.time_slot, 
                 a.test_type, a.appointment_status, a.sample_collection_address, a.additional_notes, 
                 a.created_at, o.id as order_id
          FROM appointments a
          LEFT JOIN orders o ON a.order_id = o.id
          $search_condition
          ORDER BY a.appointment_date DESC, a.appointment_time ASC 
          LIMIT ? OFFSET ?";

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
    $appointments[] = $row;
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-check"></i> Appointment Management</h1>
            <div class="page-header-actions">
                <a href="calendar.php" class="btn btn-secondary">
                    <i class="fas fa-calendar-alt"></i> Calendar View
                </a>
                <a href="export.php" class="btn btn-secondary">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filters-container card">
            <div class="card-body">
                <form action="" method="GET" class="filter-form">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <input type="text" name="search" class="form-control" placeholder="Search appointments..." value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="form-group date-range">
                                <input type="date" name="date_from" class="form-control" placeholder="From Date" value="<?php echo $date_from; ?>">
                                <span class="date-separator">to</span>
                                <input type="date" name="date_to" class="form-control" placeholder="To Date" value="<?php echo $date_to; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <?php if (!empty($search_term) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                                    <a href="index.php" class="btn btn-secondary btn-clear">Clear</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Actions -->
        <form action="" method="POST" id="appointments-form">
            <div class="bulk-actions">
                <select name="bulk_action" class="form-control">
                    <option value="">Bulk Actions</option>
                    <option value="confirmed">Mark as Confirmed</option>
                    <option value="completed">Mark as Completed</option>
                    <option value="cancelled">Mark as Cancelled</option>
                </select>
                <button type="submit" class="btn btn-secondary">Apply</button>
                <span class="bulk-counter"><span class="selected-count">0</span> items selected</span>
            </div>

            <!-- Appointments Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table table table-striped">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" class="select-all" title="Select All">
                                    </th>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Order</th>
                                    <th>Address</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointments)): ?>
                                    <tr>
                                        <td colspan="9" class="no-results">No appointments found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td class="checkbox-cell">
                                                <input type="checkbox" name="appointment_ids[]" value="<?php echo $appointment['id']; ?>" class="table-checkbox">
                                            </td>
                                            <td>#<?php echo $appointment['id']; ?></td>
                                            <td>
                                                <div class="patient-info">
                                                    <div class="patient-name"><?php echo htmlspecialchars($appointment['name']); ?></div>
                                                    <div class="patient-contact">
                                                        <div class="patient-email"><?php echo htmlspecialchars($appointment['email']); ?></div>
                                                        <div class="patient-phone"><?php echo htmlspecialchars($appointment['phone']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="appointment-datetime">
                                                    <div class="appointment-date"><?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></div>
                                                    <div class="appointment-time"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo 
                                                    $appointment['appointment_status'] === 'completed' ? 'success' : 
                                                    ($appointment['appointment_status'] === 'confirmed' ? 'primary' : 
                                                    ($appointment['appointment_status'] === 'cancelled' ? 'danger' : 'warning')); 
                                                ?>">
                                                    <?php echo ucfirst($appointment['appointment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($appointment['order_id']): ?>
                                                    <a href="../orders/view.php?id=<?php echo $appointment['order_id']; ?>" class="order-link">
                                                        #<?php echo $appointment['order_id']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="no-order">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="address-preview" title="<?php echo htmlspecialchars($appointment['sample_collection_address']); ?>">
                                                    <?php echo htmlspecialchars(substr($appointment['sample_collection_address'], 0, 30) . (strlen($appointment['sample_collection_address']) > 30 ? '...' : '')); ?>
                                                </div>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($appointment['created_at'])); ?></td>
                                            <td class="actions-cell">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-info" title="View Appointment Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <div class="dropdown">
                                                        <button class="btn btn-secondary dropdown-toggle" type="button" id="statusDropdown<?php echo $appointment['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Change Status">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                        <div class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $appointment['id']; ?>">
                                                            <a href="index.php?update_status=<?php echo $appointment['id']; ?>&status=pending" class="dropdown-item <?php echo $appointment['appointment_status'] === 'pending' ? 'active' : ''; ?>">
                                                                Pending
                                                            </a>
                                                            <a href="index.php?update_status=<?php echo $appointment['id']; ?>&status=confirmed" class="dropdown-item <?php echo $appointment['appointment_status'] === 'confirmed' ? 'active' : ''; ?>">
                                                                Confirmed
                                                            </a>
                                                            <a href="index.php?update_status=<?php echo $appointment['id']; ?>&status=completed" class="dropdown-item <?php echo $appointment['appointment_status'] === 'completed' ? 'active' : ''; ?>">
                                                                Completed
                                                            </a>
                                                            <a href="index.php?update_status=<?php echo $appointment['id']; ?>&status=cancelled" class="dropdown-item <?php echo $appointment['appointment_status'] === 'cancelled' ? 'active' : ''; ?>">
                                                                Cancelled
                                                            </a>
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

<!-- JavaScript for Appointment Page -->
<script>
    $(document).ready(function() {
        // Initialize Bootstrap dropdowns
        $('.dropdown-toggle').dropdown();
        
        // Update selected count for checkboxes
        function updateSelectedCount() {
            var count = $('.table-checkbox:checked').length;
            $('.selected-count').text(count);
        }
        
        // Select all checkbox
        $('.select-all').change(function() {
            $('.table-checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedCount();
        });
        
        // Individual checkbox change
        $('.table-checkbox').change(function() {
            updateSelectedCount();
            
            // Update select all checkbox
            var allChecked = $('.table-checkbox:checked').length === $('.table-checkbox').length;
            $('.select-all').prop('checked', allChecked);
        });
        
        // Initialize count
        updateSelectedCount();
    });
</script>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 