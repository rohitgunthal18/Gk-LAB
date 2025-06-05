<?php
/**
 * Add to Cart AJAX Handler
 * 
 * This file handles AJAX requests to add items to the cart.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
include_once 'config/db.php';
include_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST requests are allowed.'
    ]);
    exit;
}

// Get item details from request
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$type = isset($_POST['type']) ? sanitize_input($_POST['type']) : '';
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
$price = isset($_POST['price']) ? (float) $_POST['price'] : 0;

// Validate input
if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item ID.'
    ]);
    exit;
}

if (empty($type) || empty($name)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields.'
    ]);
    exit;
}

if ($price <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid price.'
    ]);
    exit;
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if item already exists in cart
$item_exists = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['id'] === $id && $item['type'] === $type) {
        $item['quantity']++;
        $item_exists = true;
        break;
    }
}

// If item doesn't exist, add it to cart
if (!$item_exists) {
    $_SESSION['cart'][] = [
        'id' => $id,
        'type' => $type,
        'name' => $name,
        'price' => $price,
        'quantity' => 1
    ];
}

// Get updated cart count
$cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));

// Return success response
echo json_encode([
    'success' => true,
    'message' => "{$name} has been added to your cart.",
    'cart_count' => $cart_count
]);
?> 