<?php
/**
 * Admin - Test Parameters Management
 * 
 * This page allows administrators to manage parameters for a specific test.
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

// Check if test_id is provided
if (!isset($_GET['test_id']) || !is_numeric($_GET['test_id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid test ID.'
    ];
    
    header('Location: index.php');
    exit;
}

$test_id = (int) $_GET['test_id'];

// Get test details
$stmt = $conn->prepare("SELECT id, name, parameters_count FROM tests WHERE id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Test not found.'
    ];
    
    header('Location: index.php');
    exit;
}

$test = $result->fetch_assoc();

// Handle parameter deletion
if (isset($_GET['delete_parameter']) && is_numeric($_GET['delete_parameter'])) {
    $parameter_id = (int) $_GET['delete_parameter'];
    
    // Check if parameter exists
    $stmt = $conn->prepare("SELECT id, parameter_name FROM test_parameters WHERE id = ? AND test_id = ?");
    $stmt->bind_param("ii", $parameter_id, $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $parameter = $result->fetch_assoc();
        $parameter_name = $parameter['parameter_name'];
        
        // Delete parameter
        $stmt = $conn->prepare("DELETE FROM test_parameters WHERE id = ?");
        $stmt->bind_param("i", $parameter_id);
        
        if ($stmt->execute()) {
            // Update parameter count in the tests table
            $stmt = $conn->prepare("UPDATE tests SET parameters_count = parameters_count - 1 WHERE id = ? AND parameters_count > 0");
            $stmt->bind_param("i", $test_id);
            $stmt->execute();
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Parameter '{$parameter_name}' has been deleted successfully."
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => "Failed to delete parameter '{$parameter_name}'. Please try again."
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Parameter not found.'
        ];
    }
    
    // Redirect to remove the query string
    header("Location: parameters.php?test_id=$test_id");
    exit;
}

// Handle form submission for adding a new parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_parameter') {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']);
    $normal_range = trim($_POST['normal_range']);
    $description = trim($_POST['description']);
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Parameter name is required.';
    }
    
    // If no errors, insert parameter
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO test_parameters (test_id, parameter_name, unit, normal_range, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $test_id, $name, $unit, $normal_range, $description);
        
        if ($stmt->execute()) {
            // Update parameter count in the tests table
            $stmt = $conn->prepare("UPDATE tests SET parameters_count = parameters_count + 1 WHERE id = ?");
            $stmt->bind_param("i", $test_id);
            $stmt->execute();
            
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Parameter '{$name}' has been added successfully."
            ];
            
            // Redirect to refresh the page
            header("Location: parameters.php?test_id=$test_id");
            exit;
        } else {
            $errors[] = 'Failed to add parameter. Please try again. Database error: ' . $stmt->error;
        }
    }
}

// Handle form submission for editing a parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_parameter') {
    // Validate and sanitize input
    $parameter_id = (int) $_POST['parameter_id'];
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']);
    $normal_range = trim($_POST['normal_range']);
    $description = trim($_POST['description']);
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Parameter name is required.';
    }
    
    // If no errors, update parameter
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE test_parameters SET parameter_name = ?, unit = ?, normal_range = ?, description = ? WHERE id = ? AND test_id = ?");
        $stmt->bind_param("ssssii", $name, $unit, $normal_range, $description, $parameter_id, $test_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Parameter '{$name}' has been updated successfully."
            ];
            
            // Redirect to refresh the page
            header("Location: parameters.php?test_id=$test_id");
            exit;
        } else {
            $errors[] = 'Failed to update parameter. Please try again. Database error: ' . $stmt->error;
        }
    }
}

// Get all parameters for this test
$parameters = [];
$stmt = $conn->prepare("SELECT id, parameter_name as name, unit, normal_range, description FROM test_parameters WHERE test_id = ? ORDER BY id");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $parameters[] = $row;
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-list-ul"></i> Test Parameters: <?php echo htmlspecialchars($test['name']); ?></h1>
            <div class="admin-content-header-actions">
                <a href="edit.php?id=<?php echo $test_id; ?>" class="btn btn-primary mr-2">
                    <i class="fas fa-edit"></i> Edit Test
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tests
                </a>
            </div>
        </div>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Add New Parameter</h3>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add_parameter">
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Parameter Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="unit">Unit</label>
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="e.g., mg/dL, g/dL, mmol/L">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="normal_range">Normal Range</label>
                            <input type="text" class="form-control" id="normal_range" name="normal_range" placeholder="e.g., 70-99 mg/dL">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Parameter
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Parameters (<?php echo count($parameters); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($parameters)): ?>
                    <p class="text-center">No parameters have been added for this test yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Unit</th>
                                    <th>Normal Range</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parameters as $parameter): ?>
                                    <tr>
                                        <td><?php echo $parameter['id']; ?></td>
                                        <td><?php echo htmlspecialchars($parameter['name']); ?></td>
                                        <td><?php echo htmlspecialchars($parameter['unit']); ?></td>
                                        <td><?php echo htmlspecialchars($parameter['normal_range']); ?></td>
                                        <td><?php echo htmlspecialchars($parameter['description']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-primary edit-parameter" 
                                                        data-id="<?php echo $parameter['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($parameter['name']); ?>"
                                                        data-unit="<?php echo htmlspecialchars($parameter['unit']); ?>"
                                                        data-range="<?php echo htmlspecialchars($parameter['normal_range']); ?>"
                                                        data-description="<?php echo htmlspecialchars($parameter['description']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="parameters.php?test_id=<?php echo $test_id; ?>&delete_parameter=<?php echo $parameter['id']; ?>" 
                                                   class="btn btn-danger delete-btn"
                                                   onclick="return confirm('Are you sure you want to delete this parameter?');">
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

<!-- Edit Parameter Modal -->
<div class="modal fade" id="editParameterModal" tabindex="-1" role="dialog" aria-labelledby="editParameterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editParameterModalLabel">Edit Parameter</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit_parameter">
                <input type="hidden" name="parameter_id" id="edit_parameter_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Parameter Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_unit">Unit</label>
                        <input type="text" class="form-control" id="edit_unit" name="unit">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_normal_range">Normal Range</label>
                        <input type="text" class="form-control" id="edit_normal_range" name="normal_range">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Parameter Modal
    const editButtons = document.querySelectorAll('.edit-parameter');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const unit = this.getAttribute('data-unit');
            const range = this.getAttribute('data-range');
            const description = this.getAttribute('data-description');
            
            document.getElementById('edit_parameter_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_unit').value = unit;
            document.getElementById('edit_normal_range').value = range;
            document.getElementById('edit_description').value = description;
            
            // Open the modal
            $('#editParameterModal').modal('show');
        });
    });
});
</script>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 