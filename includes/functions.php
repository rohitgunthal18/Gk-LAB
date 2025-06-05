<?php
/**
 * Common Functions
 * 
 * Contains utility functions used throughout the application.
 */

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate slug from a string
 * 
 * @param string $string String to convert to slug
 * @return string Slug
 */
function generate_slug($string) {
    // Remove special characters
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    // Convert to lowercase
    $string = strtolower($string);
    // Replace spaces with dashes
    $string = str_replace(' ', '-', $string);
    // Remove multiple dashes
    $string = preg_replace('/-+/', '-', $string);
    
    return $string;
}

/**
 * Format price with currency symbol
 * 
 * @param float $price Price to format
 * @return string Formatted price
 */
function format_price($price) {
    return '₹ ' . number_format($price, 2);
}

/**
 * Calculate discount percentage
 * 
 * @param float $original Original price
 * @param float $discounted Discounted price
 * @return int Discount percentage
 */
function calculate_discount($original, $discounted) {
    if ($original <= 0) {
        return 0;
    }
    
    $discount = (($original - $discounted) / $original) * 100;
    return round($discount);
}

/**
 * Redirect to another page
 * 
 * @param string $location URL to redirect to
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Display flash message
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message to display
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Display alert message
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        
        echo "<div class='alert alert-{$type}'>{$message}</div>";
        
        // Clear the flash message
        unset($_SESSION['flash_message']);
    }
}

/**
 * Get cart ID for the current session/user
 * 
 * @param mysqli $conn Database connection
 * @return int Cart ID
 */
function get_cart_id($conn) {
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        
        // Check if user has a cart
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        // If no cart exists, create one
        $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        return $conn->insert_id;
    } else {
        // For non-logged-in users, use session ID
        if (!isset($_SESSION['session_id'])) {
            $_SESSION['session_id'] = session_id();
        }
        
        $session_id = $_SESSION['session_id'];
        
        // Check if session has a cart
        $stmt = $conn->prepare("SELECT id FROM cart WHERE session_id = ? LIMIT 1");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        // If no cart exists, create one
        $stmt = $conn->prepare("INSERT INTO cart (session_id) VALUES (?)");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        
        return $conn->insert_id;
    }
}

/**
 * Get cart count
 * 
 * @param mysqli $conn Database connection
 * @return int Number of items in cart
 */
function get_cart_count($conn) {
    $cart_id = get_cart_id($conn);
    
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] ? $row['count'] : 0;
}

/**
 * Add item to cart
 * 
 * @param mysqli $conn Database connection
 * @param int $test_id Test ID
 * @param float $price Price
 * @param int $quantity Quantity
 * @return bool True if item was added successfully
 */
function add_to_cart($conn, $test_id, $price, $quantity = 1) {
    $cart_id = get_cart_id($conn);
    
    // Check if test is already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND test_id = ?");
    $stmt->bind_param("ii", $cart_id, $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $row['id']);
        return $stmt->execute();
    } else {
        // Add new item
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, test_id, price, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iidi", $cart_id, $test_id, $price, $quantity);
        return $stmt->execute();
    }
}

/**
 * Transfer cart items from session to user after login
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 */
function transfer_cart($conn, $user_id) {
    if (!isset($_SESSION['session_id'])) {
        return;
    }
    
    $session_id = $_SESSION['session_id'];
    
    // Get session cart
    $stmt = $conn->prepare("SELECT id FROM cart WHERE session_id = ? LIMIT 1");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return;
    }
    
    $session_cart = $result->fetch_assoc();
    $session_cart_id = $session_cart['id'];
    
    // Get or create user cart
    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_cart = $result->fetch_assoc();
        $user_cart_id = $user_cart['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_cart_id = $conn->insert_id;
    }
    
    // Get session cart items
    $stmt = $conn->prepare("SELECT test_id, price, quantity FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $session_cart_id);
    $stmt->execute();
    $session_items = $stmt->get_result();
    
    // Transfer items to user cart
    while ($item = $session_items->fetch_assoc()) {
        $test_id = $item['test_id'];
        $price = $item['price'];
        $quantity = $item['quantity'];
        
        // Check if item is already in user cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND test_id = ?");
        $stmt->bind_param("ii", $user_cart_id, $test_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update quantity
            $user_item = $result->fetch_assoc();
            $new_quantity = $user_item['quantity'] + $quantity;
            
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $user_item['id']);
            $stmt->execute();
        } else {
            // Add new item
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, test_id, price, quantity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iidi", $user_cart_id, $test_id, $price, $quantity);
            $stmt->execute();
        }
    }
    
    // Delete session cart items and cart
    $conn->query("DELETE FROM cart_items WHERE cart_id = {$session_cart_id}");
    $conn->query("DELETE FROM cart WHERE id = {$session_cart_id}");
    
    // Update session
    unset($_SESSION['session_id']);
}
?> 