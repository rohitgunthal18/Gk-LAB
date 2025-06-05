<?php
/**
 * Admin - Test Management
 * 
 * This page displays all diagnostic tests and provides test management functionality.
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

// Handle test deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $test_id = (int) $_GET['delete'];
    
    // Check if test exists
    $stmt = $conn->prepare("SELECT id, name FROM tests WHERE id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $test = $result->fetch_assoc();
        $test_name = $test['name'];
        
        // Delete test
        $stmt = $conn->prepare("DELETE FROM tests WHERE id = ?");
        $stmt->bind_param("i", $test_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Test '{$test_name}' has been deleted successfully."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => "Failed to delete test '{$test_name}'. Please try again."
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Test not found.'
        ];
    }
    
    // Redirect to remove the query string
    header('Location: index.php');
    exit;
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['test_ids']) && !empty($_POST['test_ids'])) {
    $action = $_POST['bulk_action'];
    $test_ids = $_POST['test_ids'];
    
    if ($action === 'delete') {
        $ids_string = implode(',', array_map('intval', $test_ids));
        
        // Delete tests
        $stmt = $conn->prepare("DELETE FROM tests WHERE id IN ($ids_string)");
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "$affected_rows tests have been deleted successfully."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to delete tests. Please try again.'
            ];
        }
    } elseif ($action === 'activate') {
        $ids_string = implode(',', array_map('intval', $test_ids));
        
        // Activate tests
        $stmt = $conn->prepare("UPDATE tests SET is_active = 1 WHERE id IN ($ids_string)");
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "$affected_rows tests have been activated successfully."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to activate tests. Please try again.'
            ];
        }
    } elseif ($action === 'deactivate') {
        $ids_string = implode(',', array_map('intval', $test_ids));
        
        // Deactivate tests
        $stmt = $conn->prepare("UPDATE tests SET is_active = 0 WHERE id IN ($ids_string)");
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "$affected_rows tests have been deactivated successfully."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to deactivate tests. Please try again.'
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
    $search_condition = "WHERE (t.name LIKE ? OR t.description LIKE ? OR t.slug LIKE ?)";
    $search_param = "%$search_term%";
    $params = [$search_param, $search_param, $search_param];
    $param_types = 'sss';
}

// Category filter
$category_filter = isset($_GET['category']) ? (int) $_GET['category'] : 0;
if ($category_filter > 0) {
    if (empty($search_condition)) {
        $search_condition = "WHERE t.category_id = ?";
    } else {
        $search_condition .= " AND t.category_id = ?";
    }
    $params[] = $category_filter;
    $param_types .= 'i';
}

// Status filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
if ($status_filter === 'active' || $status_filter === 'inactive') {
    $is_active = ($status_filter === 'active') ? 1 : 0;
    if (empty($search_condition)) {
        $search_condition = "WHERE t.is_active = ?";
    } else {
        $search_condition .= " AND t.is_active = ?";
    }
    $params[] = $is_active;
    $param_types .= 'i';
}

// Get total tests for pagination
$total_tests = 0;
$count_query = "SELECT COUNT(*) as count FROM tests t $search_condition";

if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_tests = $row['count'];
} else {
    $result = $conn->query($count_query);
    $row = $result->fetch_assoc();
    $total_tests = $row['count'];
}

$total_pages = ceil($total_tests / $limit);

// Get tests with pagination
$tests = [];
$query = "SELECT t.id, t.name, t.slug, t.original_price, t.discounted_price, t.is_active, t.is_featured, 
            c.name as category_name, t.parameters_count
          FROM tests t
          LEFT JOIN categories c ON t.category_id = c.id
          $search_condition
          ORDER BY t.id DESC LIMIT ? OFFSET ?";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    // Create a new array with all parameters
    $all_params = $params;
    $all_params[] = $limit;
    $all_params[] = $offset;
    $stmt->bind_param($param_types . 'ii', ...$all_params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

while ($row = $result->fetch_assoc()) {
    $tests[] = $row;
}

// Get all categories for filter dropdown
$categories = [];
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Check if bulk activate parameter is set
$bulk_activate = isset($_GET['bulk']) && $_GET['bulk'] === 'activate';

// Check if featured filter is set 
$featured_filter = isset($_GET['filter']) && $_GET['filter'] === 'featured';

// If featured filter is set, adjust the query
if ($featured_filter) {
    if (empty($search_condition)) {
        $search_condition = "WHERE t.is_featured = 1";
    } else {
        $search_condition .= " AND t.is_featured = 1";
    }
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-flask"></i> Test Management</h1>
            <div class="admin-content-header-actions">
                <a href="quick-action.php" class="btn btn-secondary mr-2">
                    <i class="fas fa-tasks"></i> Quick Actions
                </a>
                <a href="add.php" class="btn btn-primary mr-2">
                    <i class="fas fa-plus"></i> Add New Test
                </a>
                <a href="insert-data.php" class="btn btn-success">
                    <i class="fas fa-database"></i> Import Tests
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filters-container">
            <form action="" method="GET" class="filter-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search tests..." value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                
                <div class="filter-group">
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter === (int) $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if (!empty($search_term) || $category_filter > 0 || !empty($status_filter)): ?>
                        <a href="index.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form action="" method="POST" id="tests-form">
            <div class="bulk-actions">
                <div class="filter-form">
                    <div class="filter-group">
                        <select name="bulk_action" class="form-control">
                            <option value="">Bulk Actions</option>
                            <option value="activate" <?php echo $bulk_activate ? 'selected' : ''; ?>>Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <span class="bulk-counter"><span class="selected-count">0</span> items selected</span>
                    </div>
                </div>
            </div>

            <!-- Tests Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" class="select-all" title="Select All">
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th class="d-none d-md-table-cell">Parameters</th>
                                    <th class="d-none d-md-table-cell">Original Price</th>
                                    <th class="d-none d-md-table-cell">Discounted Price</th>
                                    <th class="d-none d-lg-table-cell">Featured</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tests)): ?>
                                    <tr>
                                        <td colspan="10" class="no-results">No tests found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tests as $test): ?>
                                        <tr>
                                            <td class="checkbox-cell">
                                                <input type="checkbox" name="test_ids[]" value="<?php echo $test['id']; ?>" class="table-checkbox">
                                            </td>
                                            <td><?php echo $test['id']; ?></td>
                                            <td><?php echo htmlspecialchars($test['name']); ?></td>
                                            <td><?php echo htmlspecialchars($test['category_name'] ?? 'Uncategorized'); ?></td>
                                            <td class="d-none d-md-table-cell"><?php echo $test['parameters_count']; ?></td>
                                            <td class="d-none d-md-table-cell">₹ <?php echo number_format($test['original_price'], 2); ?></td>
                                            <td class="d-none d-md-table-cell">₹ <?php echo number_format($test['discounted_price'], 2); ?></td>
                                            <td class="d-none d-lg-table-cell">
                                                <span class="badge badge-<?php echo $test['is_featured'] ? 'primary' : 'secondary'; ?>">
                                                    <?php echo $test['is_featured'] ? 'Yes' : 'No'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $test['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $test['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../../test-detail.php?id=<?php echo $test['id']; ?>" class="btn btn-info d-none d-md-flex" title="View on Site" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $test['id']; ?>" class="btn btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="parameters.php?test_id=<?php echo $test['id']; ?>" class="btn btn-secondary d-none d-sm-flex" title="Manage Parameters">
                                                        <i class="fas fa-list-ul"></i>
                                                    </a>
                                                    <a href="index.php?delete=<?php echo $test['id']; ?>" class="btn btn-danger delete-btn" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link prev">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($start_page + 4, $total_pages);
                
                if ($end_page - $start_page < 4 && $total_pages > 4) {
                    $start_page = max(1, $end_page - 4);
                }
                
                if ($start_page > 1): ?>
                    <a href="?page=1<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="pagination-link next">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($bulk_activate || $featured_filter): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // For bulk activation
    <?php if ($bulk_activate): ?>
    // Highlight the bulk actions area
    const bulkActionsForm = document.getElementById('tests-form');
    if (bulkActionsForm) {
        // Scroll to the bulk actions
        bulkActionsForm.scrollIntoView({behavior: 'smooth'});
        
        // Add highlight effect
        const bulkActions = bulkActionsForm.querySelector('.bulk-actions');
        if (bulkActions) {
            bulkActions.style.backgroundColor = '#ffffd0';
            bulkActions.style.padding = '15px';
            bulkActions.style.borderRadius = '5px';
            bulkActions.style.boxShadow = '0 0 10px rgba(0,0,0,0.1)';
            bulkActions.style.transition = 'all 0.5s ease';
            
            // Show an informative message
            const message = document.createElement('div');
            message.className = 'alert alert-info mt-2';
            message.innerHTML = '<i class="fas fa-info-circle"></i> Select the tests you want to activate, then click "Apply".';
            bulkActions.appendChild(message);
            
            // Remove highlight after a delay
            setTimeout(function() {
                bulkActions.style.backgroundColor = '';
                bulkActions.style.boxShadow = '';
            }, 3000);
        }
    }
    <?php endif; ?>

    // For featured filter
    <?php if ($featured_filter): ?>
    // Add a highlighted message for the featured filter
    const container = document.querySelector('.admin-content > .container');
    if (container) {
        const message = document.createElement('div');
        message.className = 'alert alert-info';
        message.innerHTML = '<i class="fas fa-star"></i> Showing only featured tests. You can manage which tests are featured by editing individual tests.';
        
        // Insert after the header
        const header = container.querySelector('.admin-content-header');
        if (header && header.nextElementSibling) {
            container.insertBefore(message, header.nextElementSibling);
        } else {
            container.appendChild(message);
        }
    }
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 