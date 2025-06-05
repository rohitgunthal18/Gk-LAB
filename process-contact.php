<?php
/**
 * Contact Form Processing Script
 * 
 * This script handles the submission of the contact form
 * and saves the data to the contact_messages table.
 */

// Start session if not already started
session_start();

// Include database connection
require_once 'connection.php';

// Set default response
$response = [
    'success' => false,
    'message' => 'There was an error processing your request.',
    'errors' => [],
    'csrf_token' => '' // We'll set a new token
];

// Function to log errors
function logError($message, $context = []) {
    $logFile = 'logs/contact_form_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message " . (!empty($context) ? json_encode($context) : '') . "\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }
    
    // Log the error
    error_log($logMessage, 3, $logFile);
}

// Generate a new CSRF token if there isn't one
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$response['csrf_token'] = $_SESSION['csrf_token'];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("CSRF token validation failed");
        }
        
        // Regenerate CSRF token after validation
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $response['csrf_token'] = $_SESSION['csrf_token'];
        
        // Verify database connection
        if (!$conn || $conn->connect_error) {
            throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection is null"));
        }

        // Collect and sanitize form data
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        // Validate data
        $errors = [];
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Please enter a valid name (at least 2 characters).';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (!empty($phone) && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
        
        if (empty($subject) || strlen($subject) < 3) {
            $errors[] = 'Please enter a valid subject (at least 3 characters).';
        }
        
        if (empty($message) || strlen($message) < 10) {
            $errors[] = 'Please enter a message (at least 10 characters).';
        }
        
        // If no errors, insert into database
        if (empty($errors)) {
            // First, check if the table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'contact_messages'");
            if ($tableCheck->num_rows == 0) {
                // Table doesn't exist - run the setup script
                require_once 'create_database.php';
            }

            // Prepare and execute the query with proper parameter binding
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Thank you for your message! We will get back to you soon.';
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
        } else {
            $response['errors'] = $errors;
            $response['message'] = 'Please correct the following errors:';
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        logError($errorMessage, [
            'post_data' => $_POST,
            'trace' => $e->getTraceAsString()
        ]);
        
        $response['message'] = 'An error occurred while processing your request.';
        $response['errors'][] = $e->getMessage();
        
        // Include debug info in development only
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            $response['debug'] = [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ];
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 