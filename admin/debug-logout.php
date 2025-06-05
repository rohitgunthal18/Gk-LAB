<?php
// Start session
session_start();

// Display session data
echo "<h1>Current Session Data</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Display logout link
echo "<h2>Test Logout Links</h2>";
echo "<p>1. <a href='../logout.php'>Standard Link (../logout.php)</a></p>";
echo "<p>2. <a href='../logout.php' id='jslink'>JavaScript Link</a></p>";
echo "<p>3. <a href='#' onclick='submitLogoutForm()'>Form Submit</a></p>";

// Absolute URL
$host = $_SERVER['HTTP_HOST'];
$logout_absolute = "http://$host/logout.php";
echo "<p>4. <a href='$logout_absolute'>Absolute URL ($logout_absolute)</a></p>";

// Form
echo "<form id='logoutForm' method='POST' action='../logout.php' style='display:none;'>";
echo "<input type='hidden' name='logout' value='1'>";
echo "</form>";

// JavaScript
echo "<script>
document.getElementById('jslink').addEventListener('click', function(e) {
    e.preventDefault();
    window.location.href = '../logout.php';
});

function submitLogoutForm() {
    document.getElementById('logoutForm').submit();
}
</script>";
?> 