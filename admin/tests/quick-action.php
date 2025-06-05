<?php
/**
 * Admin - Tests Quick Actions
 * 
 * This page provides quick access to common test management tasks.
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

// Get test stats
$stats = [
    'total_tests' => 0,
    'active_tests' => 0,
    'inactive_tests' => 0,
    'featured_tests' => 0
];

// Get total tests
$result = $conn->query("SELECT COUNT(*) as count FROM tests");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['total_tests'] = $row['count'];
}

// Get active tests
$result = $conn->query("SELECT COUNT(*) as count FROM tests WHERE is_active = 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['active_tests'] = $row['count'];
}

// Get inactive tests
$result = $conn->query("SELECT COUNT(*) as count FROM tests WHERE is_active = 0");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['inactive_tests'] = $row['count'];
}

// Get featured tests
$result = $conn->query("SELECT COUNT(*) as count FROM tests WHERE is_featured = 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $stats['featured_tests'] = $row['count'];
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-tasks"></i> Test Quick Actions</h1>
            <div class="admin-content-header-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tests
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
        
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card bg-primary text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Tests</h5>
                        <p class="card-text display-4"><?php echo $stats['total_tests']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-success text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Active Tests</h5>
                        <p class="card-text display-4"><?php echo $stats['active_tests']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-danger text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Inactive Tests</h5>
                        <p class="card-text display-4"><?php echo $stats['inactive_tests']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card bg-warning text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Featured Tests</h5>
                        <p class="card-text display-4"><?php echo $stats['featured_tests']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Grid -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Add New Test -->
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="quick-action-card">
                            <div class="quick-action-icon bg-primary text-white">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="quick-action-content">
                                <h5>Add New Test</h5>
                                <p>Create a new diagnostic test with parameters</p>
                                <a href="add.php" class="btn btn-primary btn-sm">Add Test</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Import Test Data -->
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="quick-action-card">
                            <div class="quick-action-icon bg-success text-white">
                                <i class="fas fa-file-import"></i>
                            </div>
                            <div class="quick-action-content">
                                <h5>Import Test Data</h5>
                                <p>Import test data from a predefined template</p>
                                <a href="insert-data.php" class="btn btn-success btn-sm">Import Data</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bulk Activate -->
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="quick-action-card">
                            <div class="quick-action-icon bg-warning text-white">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div class="quick-action-content">
                                <h5>Bulk Activate</h5>
                                <p>Activate multiple tests at once</p>
                                <a href="index.php?bulk=activate" class="btn btn-warning btn-sm">Go to Bulk Activate</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Manage Featured Tests -->
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="quick-action-card">
                            <div class="quick-action-icon bg-info text-white">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="quick-action-content">
                                <h5>Manage Featured Tests</h5>
                                <p>Add or remove tests from featured section</p>
                                <a href="index.php?filter=featured" class="btn btn-info btn-sm">Manage Featured</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Update Prices -->
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="quick-action-card">
                            <div class="quick-action-icon bg-secondary text-white">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="quick-action-content">
                                <h5>Update Prices</h5>
                                <p>Quickly update test prices or apply discounts</p>
                                <a href="#" class="btn btn-secondary btn-sm" onclick="alert('Bulk price update feature coming soon!')">Update Prices</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- View Test Reports -->
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="quick-action-card">
                            <div class="quick-action-icon bg-dark text-white">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="quick-action-content">
                                <h5>Test Analytics</h5>
                                <p>View test popularity and performance metrics</p>
                                <a href="#" class="btn btn-dark btn-sm" onclick="alert('Test analytics feature coming soon!')">View Analytics</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Recently Added Tests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Test Name</th>
                                <th>Category</th>
                                <th>Added On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get recent tests
                            $stmt = $conn->prepare("SELECT t.id, t.name, t.created_at, c.name as category_name 
                                                   FROM tests t 
                                                   LEFT JOIN categories c ON t.category_id = c.id 
                                                   ORDER BY t.created_at DESC LIMIT 5");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows === 0) {
                                echo '<tr><td colspan="5" class="text-center">No tests found.</td></tr>';
                            } else {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . $row['id'] . '</td>';
                                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['category_name'] ?? 'Uncategorized') . '</td>';
                                    echo '<td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>';
                                    echo '<td>';
                                    echo '<div class="btn-group btn-group-sm">';
                                    echo '<a href="edit.php?id=' . $row['id'] . '" class="btn btn-primary" title="Edit"><i class="fas fa-edit"></i></a>';
                                    echo '<a href="parameters.php?test_id=' . $row['id'] . '" class="btn btn-secondary" title="Manage Parameters"><i class="fas fa-list-ul"></i></a>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quick-action-card {
    display: flex;
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    height: 100%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.quick-action-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    font-size: 24px;
}

.quick-action-content {
    padding: 15px;
    flex: 1;
}

.quick-action-content h5 {
    margin-bottom: 5px;
    font-size: 16px;
}

.quick-action-content p {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}
</style>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 