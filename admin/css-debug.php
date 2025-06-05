<?php
/**
 * CSS Debug Script
 * 
 * This script helps debug CSS loading issues in the admin panel.
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
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Debug - GK Lab Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-styles.css">
    <style>
        .debug-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .debug-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .debug-section {
            margin-bottom: 20px;
        }
        
        .debug-section h3 {
            margin-bottom: 10px;
            color: #16A085;
        }
        
        .debug-element {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        .debug-element h4 {
            margin-bottom: 5px;
        }
        
        .debug-element p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .debug-info {
            background-color: #e8f7f4;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .debug-info pre {
            margin: 0;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <h1>CSS Debug Page</h1>
            <p>This page helps debug CSS loading issues in the admin panel.</p>
        </div>
        
        <div class="debug-info">
            <h3>Current Path Information</h3>
            <pre>
Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?>

Current Script: <?php echo $_SERVER['PHP_SELF']; ?>

Dirname: <?php echo dirname($_SERVER['PHP_SELF']); ?>

Calculated Admin Path: 
<?php
$adminPath = '';
$currentDir = dirname($_SERVER['PHP_SELF']);
$adminPos = strpos($currentDir, '/admin');
if ($adminPos !== false) {
    $subfolderDepth = substr_count(substr($currentDir, $adminPos + 6), '/');
    $adminPath = str_repeat('../', $subfolderDepth);
    echo $adminPath . ' (Subfolder depth: ' . $subfolderDepth . ')';
} else {
    echo 'Not in admin directory';
}
?>
            </pre>
        </div>
        
        <div class="debug-section">
            <h3>CSS Classes Test</h3>
            
            <div class="debug-element">
                <h4>Buttons</h4>
                <button class="btn btn-primary">Primary Button</button>
                <button class="btn btn-secondary">Secondary Button</button>
                <button class="btn btn-danger">Danger Button</button>
            </div>
            
            <div class="debug-element">
                <h4>Alerts</h4>
                <div class="alert alert-success">Success Alert</div>
                <div class="alert alert-error">Error Alert</div>
                <div class="alert alert-warning">Warning Alert</div>
                <div class="alert alert-info">Info Alert</div>
            </div>
            
            <div class="debug-element">
                <h4>Cards</h4>
                <div class="card">
                    <div class="card-header">
                        <h3>Card Title</h3>
                    </div>
                    <div class="card-content">
                        <p>This is the card content.</p>
                    </div>
                </div>
            </div>
            
            <div class="debug-element">
                <h4>Form Elements</h4>
                <form>
                    <div class="form-group">
                        <label for="test-input">Test Input</label>
                        <input type="text" id="test-input" placeholder="Test input">
                    </div>
                    <div class="form-group">
                        <label for="test-select">Test Select</label>
                        <select id="test-select">
                            <option>Option 1</option>
                            <option>Option 2</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <a href="./" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html> 