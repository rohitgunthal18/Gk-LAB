<?php
/**
 * Admin - Checkup Management
 * 
 * This page allows administrators to view and manage health checkup packages.
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

// Delete checkup if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $checkup_id = (int) $_GET['delete'];
    
    // Check if checkup exists
    $stmt = $conn->prepare("SELECT name FROM checkups WHERE id = ?");
    $stmt->bind_param("i", $checkup_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $checkup = $result->fetch_assoc();
        $checkup_name = $checkup['name'];
        
        // Delete the checkup
        $stmt = $conn->prepare("DELETE FROM checkups WHERE id = ?");
        $stmt->bind_param("i", $checkup_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Checkup '{$checkup_name}' has been deleted successfully."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => "Failed to delete checkup '{$checkup_name}'. Please try again."
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Checkup not found.'
        ];
    }
    
    // Redirect to remove the query string
    header('Location: index.php');
    exit;
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle search
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM checkups WHERE 1=1";
$countParams = [];
$countTypes = "";

if (!empty($search)) {
    $countSql .= " AND (name LIKE ? OR description LIKE ?)";
    $searchTerm = "%{$search}%";
    array_push($countParams, $searchTerm, $searchTerm);
    $countTypes .= "ss";
}

if ($category_filter > 0) {
    $countSql .= " AND category_id = ?";
    array_push($countParams, $category_filter);
    $countTypes .= "i";
}

$stmt = $conn->prepare($countSql);
if (!empty($countParams)) {
    $stmt->bind_param($countTypes, ...$countParams);
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_records = $row['total'];

// Pagination settings
$records_per_page = 10;
$total_pages = ceil($total_records / $records_per_page);
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($page, $total_pages)); // Ensure page is within valid range
$offset = ($page - 1) * $records_per_page;

// Base query for fetching checkups
$sql = "SELECT c.*, cat.name as category_name 
        FROM checkups c 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        WHERE 1=1";

$params = [];
$types = "";

// Add search condition
if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%{$search}%";
    array_push($params, $searchTerm, $searchTerm);
    $types .= "ss";
}

// Add category filter
if ($category_filter > 0) {
    $sql .= " AND c.category_id = ?";
    array_push($params, $category_filter);
    $types .= "i";
}

// Add order and limit
$sql .= " ORDER BY c.id DESC LIMIT ?, ?";
array_push($params, $offset, $records_per_page);
$types .= "ii";

// Execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$checkups = [];

while ($row = $result->fetch_assoc()) {
    $checkups[] = $row;
}

// Get all categories for the filter dropdown
$categories = [];
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-box"></i> Checkup Management</h1>
            <div class="admin-content-header-actions">
                <a href="insert-data.php" class="btn btn-secondary mr-2">
                    <i class="fas fa-database"></i> Import Data
                </a>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Checkup
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?>">
                <?php 
                echo $_SESSION['flash_message']['message']; 
                unset($_SESSION['flash_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <div class="filter-form">
                    <form action="" method="GET" class="d-flex">
                        <div class="form-group mb-0 mr-2">
                            <input type="text" name="search" class="form-control" placeholder="Search checkups..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group mb-0 mr-2">
                            <select name="category" class="form-control">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mr-2">Filter</button>
                        <a href="index.php" class="btn btn-secondary">Reset</a>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Original Price</th>
                                <th>Discounted Price</th>
                                <th>Parameters</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($checkups)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No checkups found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($checkups as $checkup): ?>
                                    <tr>
                                        <td><?php echo $checkup['id']; ?></td>
                                        <td>
                                            <div class="test-name">
                                                <?php echo htmlspecialchars($checkup['name']); ?>
                                            </div>
                                            <?php if ($checkup['is_featured']): ?>
                                                <span class="badge badge-warning">Featured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($checkup['category_name'] ?? 'None'); ?></td>
                                        <td>₹<?php echo number_format($checkup['original_price'], 2); ?></td>
                                        <td>₹<?php echo number_format($checkup['discounted_price'], 2); ?></td>
                                        <td><?php echo $checkup['parameters_count']; ?></td>
                                        <td>
                                            <?php if ($checkup['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="items.php?checkup_id=<?php echo $checkup['id']; ?>" class="btn btn-info btn-sm" title="Manage Items">
                                                    <i class="fas fa-list-ul"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $checkup['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" class="btn btn-danger btn-sm delete-btn" 
                                                   data-id="<?php echo $checkup['id']; ?>" 
                                                   data-name="<?php echo htmlspecialchars($checkup['name']); ?>"
                                                   title="Delete">
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
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo (!empty($search) ? '&search=' . urlencode($search) : '') . ($category_filter > 0 ? '&category=' . $category_filter : ''); ?>">First</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1) . (!empty($search) ? '&search=' . urlencode($search) : '') . ($category_filter > 0 ? '&category=' . $category_filter : ''); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i . (!empty($search) ? '&search=' . urlencode($search) : '') . ($category_filter > 0 ? '&category=' . $category_filter : ''); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1) . (!empty($search) ? '&search=' . urlencode($search) : '') . ($category_filter > 0 ? '&category=' . $category_filter : ''); ?>">Next</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . ($category_filter > 0 ? '&category=' . $category_filter : ''); ?>">Last</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the checkup "<span id="delete-checkup-name"></span>"?
                <p class="text-danger mt-2">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-delete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Make sure jQuery and Bootstrap JS are loaded -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

<script>
// Override any existing delete button handlers
$(document).ready(function() {
    // Remove any existing click handlers from delete buttons
    $('.delete-btn').off('click');
    
    // Add our custom click handler
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var checkupId = $(this).data('id');
        var checkupName = $(this).data('name');
        
        console.log("Delete button clicked for: ", checkupId, checkupName);
        
        // Set the checkup name in the modal
        $('#delete-checkup-name').text(checkupName);
        
        // Set the delete URL
        $('#confirm-delete').attr('href', 'index.php?delete=' + checkupId);
        
        // Show the modal
        $('#deleteModal').modal('show');
    });
});
</script>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 