<?php
/**
 * Logout Script
 * 
 * Handles user logout by destroying the session and redirecting to login page
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log the request method for debugging
$method = $_SERVER['REQUEST_METHOD'];
error_log("Logout accessed via $method method");

// Check if user is actually logged in
if (isset($_SESSION['user_id'])) {
    error_log("Logging out user ID: " . $_SESSION['user_id']);
    
    // Set a logout success message
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'You have been successfully logged out.'
    ];
    
    // Unset all session variables except flash message
    $flash_message = $_SESSION['flash_message'];
    $_SESSION = array();
    $_SESSION['flash_message'] = $flash_message;
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Restart session for flash message
    session_start();
    $_SESSION['flash_message'] = $flash_message;
} else {
    error_log("Logout attempted but no user was logged in");
    
    // Set a message for users who weren't logged in
    $_SESSION['flash_message'] = [
        'type' => 'info',
        'message' => 'You were not logged in.'
    ];
}

// Redirect to login page with absolute path to ensure it works from any directory
$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$redirect_url = "http://$host$uri/login.php";
error_log("Redirecting to: $redirect_url");

header("Location: $redirect_url");
exit;
?> 