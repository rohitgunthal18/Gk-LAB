<?php
/**
 * Admin - User Management
 * 
 * This page displays all users and provides user management functionality.
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

// Include database connection and functions
include_once '../../config/db.php';
include_once '../../includes/functions.php';

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int) $_GET['delete'];
    
    // Cannot delete yourself
    if ($user_id === (int) $_SESSION['user_id']) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'You cannot delete your own account.'
        ];
    } else {
        // Check if user exists and is not an admin
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Additional check to prevent deleting other admins
            if ($user['role'] === 'admin') {
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'message' => 'You cannot delete another admin account.'
                ];
            } else {
                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => 'User has been deleted successfully.'
                    ];
                } else {
                    $_SESSION['flash_message'] = [
                        'type' => 'error',
                        'message' => 'Failed to delete user. Please try again.'
                    ];
                }
            }
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'User not found.'
            ];
        }
    }
    
    // Redirect to remove the query string
    header('Location: index.php');
    exit;
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['user_ids'])) {
    $action = $_POST['bulk_action'];
    $user_ids = $_POST['user_ids'];
    
    if (!empty($user_ids)) {
        if ($action === 'delete') {
            // Filter out admin users and the current user
            $current_user_id = (int) $_SESSION['user_id'];
            $valid_ids = [];
            
            foreach ($user_ids as $id) {
                $id = (int) $id;
                
                // Skip current user
                if ($id === $current_user_id) {
                    continue;
                }
                
                // Check if user is admin
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Skip admin users
                    if ($user['role'] !== 'admin') {
                        $valid_ids[] = $id;
                    }
                }
            }
            
            if (!empty($valid_ids)) {
                $ids_string = implode(',', $valid_ids);
                
                // Delete users
                $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($ids_string)");
                
                if ($stmt->execute()) {
                    $affected_rows = $stmt->affected_rows;
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => "$affected_rows users have been deleted successfully."
                    ];
                } else {
                    $_SESSION['flash_message'] = [
                        'type' => 'error',
                        'message' => 'Failed to delete users. Please try again.'
                    ];
                }
            } else {
                $_SESSION['flash_message'] = [
                    'type' => 'warning',
                    'message' => 'No valid users to delete. Admin users and your own account cannot be deleted.'
                ];
            }
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
    $search_condition = "WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search_term%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $param_types = 'ssss';
}

// Role filter
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
if (!empty($role_filter)) {
    if (empty($search_condition)) {
        $search_condition = "WHERE role = ?";
    } else {
        $search_condition .= " AND role = ?";
    }
    $params[] = $role_filter;
    $param_types .= 's';
}

// Get total users for pagination
$total_users = 0;
$count_query = "SELECT COUNT(*) as count FROM users $search_condition";

if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_users = $row['count'];
} else {
    $result = $conn->query($count_query);
    $row = $result->fetch_assoc();
    $total_users = $row['count'];
}

$total_pages = ceil($total_users / $limit);

// Get users with pagination
$users = [];
$query = "SELECT id, first_name, last_name, email, phone, role, created_at FROM users $search_condition ORDER BY id DESC LIMIT ? OFFSET ?";

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
    $users[] = $row;
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <div class="admin-content-header-actions">
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filters-container">
            <form action="" method="GET" class="filter-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <div class="filter-group">
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    </select>
                    <button type="submit" class="btn-filter">Filter</button>
                </div>
                
                <?php if (!empty($search_term) || !empty($role_filter)): ?>
                    <a href="index.php" class="btn-clear-filter">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form action="" method="POST" id="users-form">
            <div class="bulk-actions">
                <select name="bulk_action">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="btn bulk-delete-btn">Apply</button>
                <span class="bulk-counter"><span class="selected-count">0</span> items selected</span>
            </div>

            <!-- Users Table -->
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
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Date Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="8" class="no-results">No users found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="checkbox-cell">
                                                <input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" class="table-checkbox">
                                            </td>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : 'success'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?>
                                                        <a href="index.php?delete=<?php echo $user['id']; ?>" class="btn btn-danger delete-btn" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
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
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="pagination-link prev">
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
                    <a href="?page=1<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="pagination-link">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="pagination-link">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>" class="pagination-link next">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 