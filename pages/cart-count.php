<?php
// Start the session to access cart data
session_start();

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Count items in cart
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
}

// Set content type to JSON
header('Content-Type: application/json');

// Return cart count as JSON
echo json_encode(['count' => $cart_count]); 