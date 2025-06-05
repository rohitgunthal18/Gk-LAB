<?php
// Start session
session_start();

// Set required session variables
$_SESSION['user_first_name'] = 'Admin';
$_SESSION['user_last_name'] = 'User';

// Set current file and dir variables
$current_file = 'index.php';
$current_dir = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Link Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-container { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; }
        h1, h2 { color: #333; }
        .link-display { background: #f5f5f5; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Admin Logout Link Test</h1>
    
    <div class="test-container">
        <h2>In admin/index.php</h2>
        <div class="link-display">
            Link code: &lt;a href="<?php echo $current_file === 'index.php' ? '../logout.php' : '../../logout.php'; ?>"&gt;Logout&lt;/a&gt;
        </div>
        <div class="link-display">
            Rendered link: <a href="<?php echo $current_file === 'index.php' ? '../logout.php' : '../../logout.php'; ?>">Logout</a>
        </div>
        <div class="link-display">
            File exists: <?php echo file_exists($current_file === 'index.php' ? '../logout.php' : '../../logout.php') ? 'Yes' : 'No'; ?>
        </div>
    </div>
    
    <div class="test-container">
        <h2>In admin/tests/index.php</h2>
        <?php $current_file = 'index.php'; $current_dir = 'tests'; ?>
        <div class="link-display">
            Link code: &lt;a href="<?php echo $current_file === 'index.php' ? '../logout.php' : '../../logout.php'; ?>"&gt;Logout&lt;/a&gt;
        </div>
        <div class="link-display">
            Rendered link: <a href="<?php echo $current_file === 'index.php' ? '../logout.php' : '../../logout.php'; ?>">Logout</a>
        </div>
        <div class="link-display">
            File exists: <?php echo file_exists($current_file === 'index.php' ? '../logout.php' : '../../logout.php') ? 'Yes' : 'No'; ?>
        </div>
    </div>
</body>
</html> 