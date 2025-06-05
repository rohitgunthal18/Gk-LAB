<?php
/**
 * Admin - Checkup Items Management
 * 
 * This page allows administrators to manage items for a specific checkup package.
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

// Check if checkup_id is provided
if (!isset($_GET['checkup_id']) || !is_numeric($_GET['checkup_id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid checkup ID.'
    ];
    
    header('Location: index.php');
    exit;
}

$checkup_id = (int) $_GET['checkup_id'];

// Get checkup details
$stmt = $conn->prepare("SELECT id, name, parameters_count FROM checkups WHERE id = ?");
$stmt->bind_param("i", $checkup_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Checkup not found.'
    ];
    
    header('Location: index.php');
    exit;
}

$checkup = $result->fetch_assoc();

// Handle item deletion
if (isset($_GET['delete_item']) && is_numeric($_GET['delete_item'])) {
    $item_id = (int) $_GET['delete_item'];
    
    // Check if item exists
    $stmt = $conn->prepare("SELECT id, parameter_name FROM checkup_items WHERE id = ? AND checkup_id = ?");
    $stmt->bind_param("ii", $item_id, $checkup_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $item = $result->fetch_assoc();
        $item_name = $item['parameter_name'];
        
        // Delete item
        $stmt = $conn->prepare("DELETE FROM checkup_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        
        if ($stmt->execute()) {
            // Update parameters count in the checkups table
            $stmt = $conn->prepare("UPDATE checkups SET parameters_count = parameters_count - 1 WHERE id = ? AND parameters_count > 0");
            $stmt->bind_param("i", $checkup_id);
            $stmt->execute();
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Item '{$item_name}' has been deleted successfully."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => "Failed to delete item '{$item_name}'. Please try again."
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Item not found.'
        ];
    }
    
    // Redirect to remove the query string
    header("Location: items.php?checkup_id=$checkup_id");
    exit;
}

// Handle form submission for adding a new item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_item') {
    // Validate and sanitize input
    $parameter_name = trim($_POST['parameter_name']);
    $test_id = !empty($_POST['test_id']) ? (int)$_POST['test_id'] : null;
    
    $errors = [];
    
    if (empty($parameter_name)) {
        $errors[] = 'Parameter name is required.';
    }
    
    // If no errors, insert item
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO checkup_items (checkup_id, test_id, parameter_name, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $checkup_id, $test_id, $parameter_name);
        
        if ($stmt->execute()) {
            // Update parameter count in the checkups table
            $stmt = $conn->prepare("UPDATE checkups SET parameters_count = parameters_count + 1 WHERE id = ?");
            $stmt->bind_param("i", $checkup_id);
            $stmt->execute();
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Item '{$parameter_name}' has been added successfully."
            ];
            
            // Redirect to refresh the page
            header("Location: items.php?checkup_id=$checkup_id");
            exit;
        } else {
            $errors[] = 'Failed to add item. Please try again. Database error: ' . $stmt->error;
        }
    }
}

// Handle form submission for editing an item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_item') {
    // Validate and sanitize input
    $item_id = (int) $_POST['item_id'];
    $parameter_name = trim($_POST['parameter_name']);
    $test_id = !empty($_POST['test_id']) ? (int)$_POST['test_id'] : null;
    
    $errors = [];
    
    if (empty($parameter_name)) {
        $errors[] = 'Parameter name is required.';
    }
    
    // If no errors, update item
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE checkup_items SET parameter_name = ?, test_id = ? WHERE id = ? AND checkup_id = ?");
        $stmt->bind_param("siii", $parameter_name, $test_id, $item_id, $checkup_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Item '{$parameter_name}' has been updated successfully."
            ];
            
            // Redirect to refresh the page
            header("Location: items.php?checkup_id=$checkup_id");
            exit;
        } else {
            $errors[] = 'Failed to update item. Please try again. Database error: ' . $stmt->error;
        }
    }
}

// Get all items for this checkup
$items = [];
$stmt = $conn->prepare("
    SELECT ci.id, ci.parameter_name, ci.test_id, t.name as test_name 
    FROM checkup_items ci
    LEFT JOIN tests t ON ci.test_id = t.id
    WHERE ci.checkup_id = ? 
    ORDER BY ci.id
");
$stmt->bind_param("i", $checkup_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Get all tests for the dropdown
$tests = [];
$stmt = $conn->prepare("SELECT id, name FROM tests WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tests[] = $row;
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-list-ul"></i> Checkup Items: <?php echo htmlspecialchars($checkup['name']); ?></h1>
            <div class="admin-content-header-actions">
                <a href="edit.php?id=<?php echo $checkup_id; ?>" class="btn btn-primary mr-2">
                    <i class="fas fa-edit"></i> Edit Checkup Details
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Checkups
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
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Add/Edit Item Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 id="form-title">Add New Item</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" id="item-form">
                            <input type="hidden" name="action" id="form-action" value="add_item">
                            <input type="hidden" name="item_id" id="item-id" value="">
                            
                            <div class="form-group">
                                <label for="parameter_name">Parameter Name <span class="required">*</span></label>
                                <input type="text" class="form-control" id="parameter_name" name="parameter_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="test_id">Link to Test (Optional)</label>
                                <select class="form-control" id="test_id" name="test_id">
                                    <option value="">-- None --</option>
                                    <?php foreach ($tests as $test): ?>
                                        <option value="<?php echo $test['id']; ?>">
                                            <?php echo htmlspecialchars($test['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Optionally link this item to a test in the system.</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="submit-btn">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                                <button type="button" class="btn btn-secondary" id="cancel-btn" style="display: none;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Items List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Checkup Items (<?php echo count($items); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($items)): ?>
                            <div class="alert alert-info">
                                No items have been added to this checkup yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Parameter Name</th>
                                            <th>Linked Test</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?php echo $item['id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['parameter_name']); ?></td>
                                                <td>
                                                    <?php if (!empty($item['test_name'])): ?>
                                                        <a href="../tests/edit.php?id=<?php echo $item['test_id']; ?>" target="_blank">
                                                            <?php echo htmlspecialchars($item['test_name']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <em>None</em>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button class="btn btn-primary btn-sm edit-btn" 
                                                                data-id="<?php echo $item['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($item['parameter_name']); ?>"
                                                                data-test-id="<?php echo $item['test_id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="#" class="btn btn-danger btn-sm delete-btn"
                                                           data-id="<?php echo $item['id']; ?>"
                                                           data-name="<?php echo htmlspecialchars($item['parameter_name']); ?>">
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
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the item "<span id="delete-item-name"></span>"?
                <p class="text-danger mt-2">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <a href="#" id="confirm-delete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle edit button
    $('.edit-btn').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var testId = $(this).data('test-id');
        
        // Set form to edit mode
        $('#form-title').text('Edit Item');
        $('#form-action').val('edit_item');
        $('#item-id').val(id);
        $('#parameter_name').val(name);
        $('#test_id').val(testId);
        $('#submit-btn').html('<i class="fas fa-save"></i> Update Item');
        $('#cancel-btn').show();
    });
    
    // Handle cancel button
    $('#cancel-btn').click(function() {
        // Reset form to add mode
        $('#form-title').text('Add New Item');
        $('#form-action').val('add_item');
        $('#item-id').val('');
        $('#parameter_name').val('');
        $('#test_id').val('');
        $('#submit-btn').html('<i class="fas fa-plus"></i> Add Item');
        $('#cancel-btn').hide();
    });
    
    // Handle delete confirmation
    $('.delete-btn').click(function(e) {
        e.preventDefault();
        
        var itemId = $(this).data('id');
        var itemName = $(this).data('name');
        
        $('#delete-item-name').text(itemName);
        $('#confirm-delete').attr('href', 'items.php?checkup_id=<?php echo $checkup_id; ?>&delete_item=' + itemId);
        
        $('#deleteModal').modal('show');
    });
});
</script>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 