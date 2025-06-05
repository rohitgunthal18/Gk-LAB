<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Create response array
$response = array(
    'loggedIn' => $loggedIn
);

// If user is logged in, add user information to the response
if ($loggedIn) {
    $response['user_id'] = $_SESSION['user_id'];
    $response['user_email'] = $_SESSION['user_email'] ?? '';
    $response['user_first_name'] = $_SESSION['user_first_name'] ?? '';
    $response['user_last_name'] = $_SESSION['user_last_name'] ?? '';
    $response['user_role'] = $_SESSION['user_role'] ?? '';
}

// Return JSON response
echo json_encode($response);
?> 