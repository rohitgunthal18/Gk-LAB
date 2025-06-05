<?php
// Start session
session_start();

// Set required session variables
$_SESSION['user_first_name'] = 'Admin';
$_SESSION['user_last_name'] = 'User';

// Set variables to test different scenarios
$current_file = 'index.php'; 
$current_dir = 'admin';

// Echo what the logout link would be in the admin header
echo "<h1>Admin Logout Link Test</h1>";

echo "<h2>When on admin/index.php:</h2>";
echo "../logout.php<br>";
echo "Absolute path: " . realpath('../logout.php') . "<br>";
echo "File exists: " . (file_exists('../logout.php') ? 'Yes' : 'No') . "<br>";

echo "<h2>When on admin/subdirectory/index.php:</h2>";
$current_file = 'index.php';
$current_dir = 'subdirectory';
echo "../../logout.php<br>";
echo "Absolute path: " . realpath('../../logout.php') . "<br>";
echo "File exists from subfolder: " . (file_exists('../../logout.php') ? 'Yes' : 'No');
?> 