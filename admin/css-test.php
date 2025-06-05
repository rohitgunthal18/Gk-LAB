<?php
/**
 * CSS Test File
 * 
 * This is a simple test file to check if our CSS is loading correctly
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For demo, we'll simulate the user data
$_SESSION['user_first_name'] = 'Admin';
$_SESSION['user_role'] = 'admin';
$_SESSION['user_id'] = 1;

// Include our header and footer templates
include_once 'includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-check-circle"></i> CSS Loading Test</h1>
            <p>This page demonstrates that our CSS is loading correctly</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Test Components</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <p>This is a success alert</p>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="alert alert-danger">
                    <p>This is an error alert</p>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="alert alert-info">
                    <p>This is an info alert</p>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="alert alert-warning">
                    <p>This is a warning alert</p>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="form-group">
                    <label for="test-input">Test Input</label>
                    <input type="text" id="test-input" class="form-control" placeholder="Test input field">
                </div>
                
                <div class="form-group">
                    <label for="test-select">Test Select</label>
                    <select id="test-select" class="form-control">
                        <option value="">Choose an option</option>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                    </select>
                </div>
                
                <div class="form-buttons">
                    <button class="btn btn-primary">Primary Button</button>
                    <button class="btn btn-secondary">Secondary Button</button>
                    <button class="btn btn-danger">Danger Button</button>
                    <button class="btn btn-info">Info Button</button>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Test Table</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Test User 1</td>
                                <td>user1@example.com</td>
                                <td><span class="badge badge-primary">Admin</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" class="btn btn-info" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="#" class="btn btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="#" class="btn btn-danger" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Test User 2</td>
                                <td>user2@example.com</td>
                                <td><span class="badge badge-success">Customer</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" class="btn btn-info" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="#" class="btn btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="#" class="btn btn-danger" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the admin footer
include_once 'includes/admin-footer.php';
?> 