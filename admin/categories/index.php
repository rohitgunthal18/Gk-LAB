<?php
/**
 * Categories Management
 * 
 * Allows admin to manage test and checkup categories.
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

// Process form submissions
$error = '';
$success = '';

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // Check if category is in use
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tests WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $error = "Cannot delete category: it is used by {$count} tests.";
    } else {
        // Delete the category
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            $success = "Category deleted successfully.";
        } else {
            $error = "Failed to delete category: " . $conn->error;
        }
    }
}

// Handle category creation and update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Get form data
        $category_name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
        $category_type = isset($_POST['type']) ? sanitize_input($_POST['type']) : '';
        $category_description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        
        // Validate input
        if (empty($category_name)) {
            $error = "Category name is required.";
        } elseif (empty($category_type)) {
            $error = "Category type is required.";
        } else {
            // Create new category
            if ($_POST['action'] === 'create') {
                $stmt = $conn->prepare("INSERT INTO categories (name, type, description) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $category_name, $category_type, $category_description);
                
                if ($stmt->execute()) {
                    $success = "Category created successfully.";
                } else {
                    $error = "Failed to create category: " . $conn->error;
                }
            }
            // Update existing category
            elseif ($_POST['action'] === 'update' && isset($_POST['id']) && is_numeric($_POST['id'])) {
                $category_id = $_POST['id'];
                
                $stmt = $conn->prepare("UPDATE categories SET name = ?, type = ?, description = ? WHERE id = ?");
                $stmt->bind_param("sssi", $category_name, $category_type, $category_description, $category_id);
                
                if ($stmt->execute()) {
                    $success = "Category updated successfully.";
                } else {
                    $error = "Failed to update category: " . $conn->error;
                }
            }
        }
    }
}

// Get all categories
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get a specific category for editing
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $category_id = $_GET['edit'];
    
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
    }
}

// Include admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-tags"></i> Categories Management</h1>
            <p>Manage test and checkup categories</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
                <button class="alert-close"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <button class="alert-close"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="action" value="<?php echo $edit_category ? 'update' : 'create'; ?>">
                    <?php if ($edit_category): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Category Name*</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo $edit_category ? $edit_category['name'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Category Type*</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="">-- Select Type --</option>
                            <option value="test" <?php echo ($edit_category && $edit_category['type'] === 'test') ? 'selected' : ''; ?>>Test</option>
                            <option value="checkup" <?php echo ($edit_category && $edit_category['type'] === 'checkup') ? 'selected' : ''; ?>>Checkup</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo $edit_category ? $edit_category['description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-buttons">
                        <?php if ($edit_category): ?>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Category List</h2>
            </div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                    <div class="alert alert-info">No categories found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo isset($category['type']) && $category['type'] === 'test' ? 'primary' : 'success'; ?>">
                                                <?php echo isset($category['type']) ? ucfirst($category['type']) : 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-info" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-danger delete-btn" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
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

<?php
// Include admin footer
include_once '../includes/admin-footer.php';
?> 