<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($success, $message, $cart_count = 0, $subtotal = 0, $item_quantity = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'cart_count' => $cart_count,
        'subtotal' => $subtotal,
        'item_quantity' => $item_quantity
    ]);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method. Only POST requests are allowed.');
}

// Get and validate action
if (!isset($_POST['action']) || empty($_POST['action'])) {
    sendResponse(false, 'Action is required.');
}
$action = trim($_POST['action']);

// Get and validate item ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    sendResponse(false, 'Item ID is required.');
}
$id = (string)trim($_POST['id']); // Convert to string for comparison

// Debug log
error_log("Cart action request - Action: $action, ID: $id");
error_log("ID type: " . gettype($id));

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Debug log cart contents
error_log("Current cart contents: " . print_r($_SESSION['cart'], true));

// Initialize variables
$success = false;
$message = '';
$cart_count = 0;
$subtotal = 0;
$item_quantity = null;

try {
    // Process the action
    switch ($action) {
        case 'increase':
            foreach ($_SESSION['cart'] as &$item) {
                // Convert item ID to string for comparison
                $itemId = (string)$item['id'];
                error_log("Comparing IDs - Request: $id, Cart: $itemId");
                
                if ($itemId === $id) {
                    $item['quantity']++;
                    $success = true;
                    $message = 'Quantity increased successfully.';
                    $item_quantity = $item['quantity'];
                    break;
                }
            }
            if (!$success) {
                sendResponse(false, 'Item not found in cart.');
            }
            break;

        case 'decrease':
            foreach ($_SESSION['cart'] as $key => &$item) {
                // Convert item ID to string for comparison
                $itemId = (string)$item['id'];
                error_log("Comparing IDs - Request: $id, Cart: $itemId");
                
                if ($itemId === $id) {
                    $item['quantity']--;
                    
                    if ($item['quantity'] <= 0) {
                        unset($_SESSION['cart'][$key]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']);
                        $message = 'Item removed from cart.';
                    } else {
                        $message = 'Quantity decreased successfully.';
                        $item_quantity = $item['quantity'];
                    }
                    $success = true;
                    break;
                }
            }
            if (!$success) {
                sendResponse(false, 'Item not found in cart.');
            }
            break;

        case 'remove':
            $found = false;
            foreach ($_SESSION['cart'] as $key => $item) {
                // Convert item ID to string for comparison
                $itemId = (string)$item['id'];
                error_log("Comparing IDs - Request: $id, Cart: $itemId");
                
                if ($itemId === $id) {
                    unset($_SESSION['cart'][$key]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                    $success = true;
                    $message = 'Item removed from cart.';
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                sendResponse(false, 'Item not found in cart.');
            }
            break;

        default:
            sendResponse(false, 'Invalid action.');
    }

    // Calculate cart totals
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
        $subtotal += $item['price'] * $item['quantity'];
    }

    // Debug log after action
    error_log("Cart after action - Count: $cart_count, Subtotal: $subtotal");
    error_log("Updated cart contents: " . print_r($_SESSION['cart'], true));

    // Send success response
    sendResponse($success, $message, $cart_count, $subtotal, $item_quantity);

} catch (Exception $e) {
    // Log the error
    error_log("Cart action error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    sendResponse(false, 'An error occurred while processing your request.');
} 