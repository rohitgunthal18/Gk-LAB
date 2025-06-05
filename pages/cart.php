<?php
// Start the session to track cart items
session_start();

// Include database connection
require_once '../connection.php';

// Check connection - already handled in connection.php

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Initialize coupon variables
$couponCode = '';
$couponDiscount = 0;
$couponError = '';
$couponSuccess = '';
$couponDetails = null;

// Handle coupon application
if (isset($_POST['apply_coupon']) && !empty($_POST['coupon_code'])) {
    $enteredCouponCode = trim($_POST['coupon_code']);
    
    // Check if user is logged in
    if (!$isLoggedIn) {
        // Store the coupon code in session for later application after login
        $_SESSION['pending_coupon'] = $enteredCouponCode;
        
        // Set error message
        $couponError = "Please login to apply coupon code.";
    } else {
        // Validate coupon in database
        $couponQuery = "SELECT * FROM coupons 
                        WHERE code = ? 
                        AND status = 'active' 
                        AND start_date <= NOW() 
                        AND end_date >= NOW()
                        AND (max_uses IS NULL OR current_uses < max_uses)";
        
        $stmt = $conn->prepare($couponQuery);
        $stmt->bind_param("s", $enteredCouponCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $couponDetails = $result->fetch_assoc();
            
            // Check minimum order value
            $subtotal = 0;
            foreach ($_SESSION['cart'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            if ($subtotal < $couponDetails['min_order_value']) {
                $couponError = "This coupon requires a minimum order of ₹" . number_format($couponDetails['min_order_value'], 2);
            } 
            // Check if it's first order only
            elseif ($couponDetails['is_first_order_only']) {
                // Check if user has previous orders
                $orderCheckQuery = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
                $stmt = $conn->prepare($orderCheckQuery);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $orderResult = $stmt->get_result();
                $orderCount = $orderResult->fetch_assoc()['order_count'];
                
                if ($orderCount > 0) {
                    $couponError = "This coupon is valid only for first-time orders.";
                } else {
                    // Set coupon in session
                    $_SESSION['coupon'] = $couponDetails;
                    $couponSuccess = "Coupon applied successfully!";
                }
            } 
            // Check if one-time use and already used
            elseif ($couponDetails['is_one_time_use']) {
                // Check if user has already used this coupon
                $usageCheckQuery = "SELECT COUNT(*) as usage_count FROM coupon_usage WHERE coupon_id = ? AND user_id = ?";
                $stmt = $conn->prepare($usageCheckQuery);
                $stmt->bind_param("ii", $couponDetails['id'], $userId);
                $stmt->execute();
                $usageResult = $stmt->get_result();
                $usageCount = $usageResult->fetch_assoc()['usage_count'];
                
                if ($usageCount > 0) {
                    $couponError = "You have already used this coupon.";
                } else {
                    // Set coupon in session
                    $_SESSION['coupon'] = $couponDetails;
                    $couponSuccess = "Coupon applied successfully!";
                }
            } else {
                // Set coupon in session
                $_SESSION['coupon'] = $couponDetails;
                $couponSuccess = "Coupon applied successfully!";
            }
        } else {
            $couponError = "Invalid or expired coupon code.";
        }
    }
}

// Handle coupon removal
if (isset($_GET['action']) && $_GET['action'] === 'remove_coupon') {
    unset($_SESSION['coupon']);
    $couponSuccess = "Coupon removed successfully.";
}

// Handle cart actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'add' && isset($_GET['id'], $_GET['name'], $_GET['price'], $_GET['type'])) {
        // Add item to cart
        $id = $_GET['id'];
        $name = $_GET['name'];
        $price = $_GET['price'];
        $type = $_GET['type'];
        
        // Check if item already exists in cart
        $itemExists = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $id) {
                // Increment quantity
                $item['quantity'] += 1;
                $itemExists = true;
                break;
            }
        }
        
        // Add new item if it doesn't exist
        if (!$itemExists) {
            $_SESSION['cart'][] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'type' => $type,
                'quantity' => 1
            ];
        }
        
        // Redirect back
        header('Location: cart.php');
        exit;
    } 
    elseif ($action === 'increase' && isset($_GET['id'])) {
        // Increase item quantity
        $id = $_GET['id'];
        
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $id) {
                $item['quantity'] += 1;
                break;
            }
        }
        
        // Redirect back
        header('Location: cart.php');
        exit;
    } 
    elseif ($action === 'decrease' && isset($_GET['id'])) {
        // Decrease item quantity
        $id = $_GET['id'];
        
        foreach ($_SESSION['cart'] as $key => &$item) {
            if ($item['id'] === $id) {
                $item['quantity'] -= 1;
                
                // Remove item if quantity is 0
                if ($item['quantity'] <= 0) {
                    unset($_SESSION['cart'][$key]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
                }
                
                break;
            }
        }
        
        // Redirect back
        header('Location: cart.php');
        exit;
    } 
    elseif ($action === 'remove' && isset($_GET['id'])) {
        // Remove item from cart
        $id = $_GET['id'];
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] === $id) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
                break;
            }
        }
        
        // Redirect back
        header('Location: cart.php');
        exit;
    } 
    elseif ($action === 'empty') {
        // Empty the cart
        $_SESSION['cart'] = [];
        unset($_SESSION['coupon']);
        
        // Redirect back
        header('Location: cart.php');
        exit;
    }
    elseif ($action === 'checkout' && !empty($_SESSION['cart'])) {
        // Process checkout - save order to database
        if ($conn->connect_error) {
            // Database connection failed
            $error = "Unable to process your order. Please try again later.";
        } else {
            // Get current date and time
            $order_date = date('Y-m-d H:i:s');
            $total_amount = 0;
            
            // Calculate total
            foreach ($_SESSION['cart'] as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }
            
            // Apply coupon discount if applicable
            $discount = 0;
            $coupon_code = null;
            $coupon_discount = 0;
            
            if (isset($_SESSION['coupon'])) {
                $coupon = $_SESSION['coupon'];
                $coupon_code = $coupon['code'];
                
                if ($coupon['discount_type'] === 'percentage') {
                    $coupon_discount = ($total_amount * $coupon['discount_value']) / 100;
                    
                    // Apply maximum discount cap if set
                    if (!empty($coupon['max_discount_amount']) && $coupon_discount > $coupon['max_discount_amount']) {
                        $coupon_discount = $coupon['max_discount_amount'];
                    }
                } else {
                    // Fixed amount discount
                    $coupon_discount = $coupon['discount_value'];
                }
                
                $discount = $coupon_discount;
            }
            
            $final_amount = $total_amount - $discount;
            
            // Create order in database
            $stmt = $conn->prepare("INSERT INTO orders (order_date, user_id, total_amount, discount, final_amount, coupon_code, coupon_discount, payment_status, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
            $stmt->bind_param("sidddsd", $order_date, $userId, $total_amount, $discount, $final_amount, $coupon_code, $coupon_discount);
            
            if ($stmt->execute()) {
                $order_id = $conn->insert_id;
                
                // Insert order items
                foreach ($_SESSION['cart'] as $item) {
                    $item_total = $item['price'] * $item['quantity'];
                    
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, item_name, item_type, price, quantity, item_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssdid", $order_id, $item['id'], $item['name'], $item['type'], $item['price'], $item['quantity'], $item_total);
                    $stmt->execute();
                }
                
                // Record coupon usage if coupon was applied
                if (isset($_SESSION['coupon'])) {
                    $coupon_id = $_SESSION['coupon']['id'];
                    
                    // Insert into coupon_usage table
                    $stmt = $conn->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiid", $coupon_id, $userId, $order_id, $coupon_discount);
                    $stmt->execute();
                    
                    // Update coupon usage count
                    $stmt = $conn->prepare("UPDATE coupons SET current_uses = current_uses + 1 WHERE id = ?");
                    $stmt->bind_param("i", $coupon_id);
                    $stmt->execute();
                    
                    // Clear applied coupon
                    unset($_SESSION['coupon']);
                }
                
                // Clear cart after successful order
                $_SESSION['cart'] = [];
                
                // Redirect to success page
                $_SESSION['order_success'] = true;
                $_SESSION['order_id'] = $order_id;
                header('Location: order-success.php');
                exit;
            } else {
                $error = "Unable to process your order. Please try again later.";
            }
        }
    }
}

// Count items in cart
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Apply coupon discount if applicable
$discount = 0;
$couponApplied = false;

if (isset($_SESSION['coupon'])) {
    $couponApplied = true;
    $couponDetails = $_SESSION['coupon'];
    $couponCode = $couponDetails['code'];
    
    if ($couponDetails['discount_type'] === 'percentage') {
        $discount = ($subtotal * $couponDetails['discount_value']) / 100;
        
        // Apply maximum discount cap if set
        if (!empty($couponDetails['max_discount_amount']) && $discount > $couponDetails['max_discount_amount']) {
            $discount = $couponDetails['max_discount_amount'];
        }
    } else {
        // Fixed amount discount
        $discount = $couponDetails['discount_value'];
    }
}

$total = $subtotal - $discount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Cart Page Specific Styles */
        .cart-section {
            padding: 40px 0;
            min-height: 70vh;
            background-color: #f9f9f9;
        }
        
        .cart-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .cart-header {
            background-color: #16A085;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .cart-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .cart-count-badge {
            background-color: white;
            color: #16A085;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        .cart-empty {
            text-align: center;
            padding: 60px 20px;
        }
        
        .cart-empty i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .cart-empty h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .cart-empty p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .cart-items {
            padding: 0;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            display: flex;
            flex-direction: column;
            padding-right: 20px;
        }
        
        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .item-type {
            color: #666;
            font-size: 14px;
            display: inline-block;
            background-color: rgba(22, 160, 133, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #16A085;
            font-weight: 500;
        }
        
        .item-quantity {
            display: flex;
            align-items: center;
            background-color: #f9f9f9;
            border-radius: 4px;
            padding: 5px;
            margin: 0 15px;
        }
        
        .quantity-btn {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .quantity-btn:hover {
            background-color: #f1f1f1;
        }
        
        .quantity-input {
            width: 35px;
            text-align: center;
            border: none;
            background: transparent;
            font-size: 14px;
            font-weight: 600;
            margin: 0 8px;
        }
        
        .item-total {
            font-weight: 600;
            color: #333;
            min-width: 80px;
            text-align: right;
        }
        
        .remove-item {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 14px;
            padding: 5px;
            margin-left: 15px;
            transition: color 0.2s;
        }
        
        .remove-item:hover {
            color: #D32F2F;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #f9f9f9;
        }
        
        .cart-actions-left a {
            display: inline-flex;
            align-items: center;
            color: #16A085;
            text-decoration: none;
            font-weight: 500;
        }
        
        .cart-actions-left a i {
            margin-right: 8px;
        }
        
        .cart-actions-right {
            display: flex;
            align-items: center;
        }
        
        .empty-cart-btn {
            background: none;
            border: 1px solid #ddd;
            color: #666;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .empty-cart-btn:hover {
            background-color: #f1f1f1;
            color: #333;
        }
        
        .cart-summary {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .summary-header {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 15px;
        }
        
        .summary-row.discount {
            color: #16A085;
        }
        
        .summary-row.discount .discount-code {
            background-color: rgba(22, 160, 133, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .summary-row.total {
            font-weight: 600;
            font-size: 18px;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .checkout-btn {
            display: block;
            width: 100%;
            background-color: #16A085;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.2s;
            text-align: center;
            text-decoration: none;
        }
        
        .checkout-btn:hover {
            background-color: #138a72;
        }
        
        .cart-note {
            margin-top: 20px;
            padding: 15px;
            background-color: rgba(255, 138, 0, 0.1);
            border-radius: 4px;
            font-size: 14px;
            color: #666;
        }
        
        .cart-note i {
            color: #FF8A00;
            margin-right: 5px;
        }
        
        /* Coupon styles */
        .coupon-section {
            margin: 15px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border: 1px dashed #ddd;
        }
        
        .coupon-form {
            margin-bottom: 0;
        }
        
        .coupon-input-group {
            display: flex;
            gap: 8px;
        }
        
        .coupon-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .apply-coupon-btn {
            background-color: #16A085;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .apply-coupon-btn:hover {
            background-color: #138a72;
        }
        
        .coupon-error {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 8px;
        }
        
        .coupon-success {
            color: #27ae60;
            font-size: 13px;
            margin-top: 8px;
        }
        
        .applied-coupon {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .coupon-info {
            display: flex;
            flex-direction: column;
        }
        
        .coupon-code-badge {
            display: inline-block;
            background-color: #16A085;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .coupon-value {
            font-size: 13px;
            color: #16A085;
        }
        
        .remove-coupon {
            color: #e74c3c;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            transition: color 0.2s;
        }
        
        .remove-coupon:hover {
            color: #c0392b;
        }
        
        /* Responsive design */
        @media (max-width: 991px) {
            .cart-summary {
                margin-top: 30px;
            }
        }
        
        @media (max-width: 767px) {
            .cart-item {
                grid-template-columns: 1fr auto;
                row-gap: 15px;
            }
            
            .item-info {
                grid-column: 1 / -1;
            }
            
            .item-quantity {
                margin: 0;
            }
            
            .item-total {
                text-align: right;
            }
            
            .remove-item {
                grid-column: 2;
                grid-row: 1;
            }
            
            .cart-actions {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .cart-actions-right {
                width: 100%;
            }
            
            .empty-cart-btn {
                width: 100%;
                margin-left: 0;
            }
        }
        
        /* Cart count badge */
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #FF8A00;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-cart {
            position: relative;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container header-container">
            <div class="logo-container">
                <div class="logo">
                    <a href="../index.html">
                        <div class="logo-icon">
                            <i class="fas fa-flask"></i>
                        </div>
                        <h1 class="logo-text">GK Lab</h1>
                    </a>
                </div>
            </div>
            
            <div class="mobile-menu-toggle" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <nav class="nav-menu" id="nav-menu">
                <div class="menu-dropdown">
                    <a href="#" class="menu-item dropdown-toggle">
                        <i class="fas fa-th-large"></i>
                        Pages <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <li class="logged-in-only" style="display: none;"><a href="orders.php">Orders</a></li>
                    </ul>
                </div>
                <a href="../tests.php" class="menu-item">
                    <i class="fas fa-microscope"></i>
                    Tests
                </a>
                <a href="../checkups.php" class="menu-item">
                    <i class="fas fa-box"></i>
                    Checkups
                </a>
                <a href="cart.php" class="btn-cart active">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
                <div class="menu-dropdown">
                    <a href="#" class="btn-support dropdown-toggle user-toggle" style="display: flex; align-items: center;" id="account-dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <span id="account-text">Account</span> <i class="fas fa-chevron-down" style="margin-left: 8px;"></i>
                    </a>
                    <ul class="dropdown-menu user-dropdown">
                        <li><a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="../register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                        <li class="logged-in-only" style="display: none;"><a href="../profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li class="logged-in-only" style="display: none;"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="cart-container">
                            <div class="cart-header">
                                <h2>Your Cart</h2>
                                <div class="cart-count-badge"><?php echo $cart_count; ?></div>
                            </div>
                            
                            <div class="cart-items">
                                <?php foreach ($_SESSION['cart'] as $item): ?>
                                    <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                                        <div class="item-info">
                                            <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                            <span class="item-type"><?php echo htmlspecialchars($item['type']); ?></span>
                                            <span class="item-price">₹ <?php echo number_format($item['price'], 2); ?></span>
                                        </div>
                                        
                                        <div class="item-quantity">
                                            <button class="quantity-btn decrease-btn">-</button>
                                            <input type="text" class="quantity-input" value="<?php echo $item['quantity']; ?>" readonly>
                                            <button class="quantity-btn increase-btn">+</button>
                                        </div>
                                        
                                        <div class="item-total">₹ <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                        
                                        <button class="remove-item">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="cart-actions">
                                <div class="cart-actions-left">
                                    <a href="../index.html">
                                        <i class="fas fa-arrow-left"></i> Continue Shopping
                                    </a>
                                </div>
                                <div class="cart-actions-right">
                                    <a href="cart.php?action=empty" class="empty-cart-btn">
                                        <i class="fas fa-trash"></i> Empty Cart
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="cart-summary">
                            <div class="summary-header">
                                <h3>Order Summary</h3>
                            </div>
                            
                            <div class="summary-row">
                                <div>Subtotal</div>
                                <div>₹ <?php echo number_format($subtotal, 2); ?></div>
                            </div>
                            
                            <!-- Coupon Form -->
                            <div class="coupon-section">
                                <?php if (isset($_SESSION['coupon'])): ?>
                                    <div class="applied-coupon">
                                        <div class="coupon-info">
                                            <span class="coupon-code-badge"><?php echo htmlspecialchars($couponCode); ?></span>
                                            <span class="coupon-value">
                                                <?php if ($couponDetails['discount_type'] === 'percentage'): ?>
                                                    <?php echo $couponDetails['discount_value']; ?>% discount
                                                <?php else: ?>
                                                    ₹<?php echo number_format($couponDetails['discount_value'], 2); ?> discount
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <a href="cart.php?action=remove_coupon" class="remove-coupon">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <form method="post" action="cart.php" class="coupon-form">
                                        <div class="coupon-input-group">
                                            <input type="text" name="coupon_code" placeholder="Enter coupon code" class="coupon-input" value="<?php echo htmlspecialchars($couponCode); ?>">
                                            <button type="submit" name="apply_coupon" class="apply-coupon-btn">Apply</button>
                                        </div>
                                        <?php if (!empty($couponError)): ?>
                                            <div class="coupon-error"><?php echo $couponError; ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($couponSuccess)): ?>
                                            <div class="coupon-success"><?php echo $couponSuccess; ?></div>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="summary-row discount">
                                <div>Discount</div>
                                <div>
                                    <?php if ($discount > 0): ?>
                                        - ₹ <?php echo number_format($discount, 2); ?>
                                    <?php else: ?>
                                        ₹ 0.00
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="summary-row total">
                                <div>Total</div>
                                <div>₹ <?php echo number_format($total, 2); ?></div>
                            </div>
                            
                            <a href="<?php echo (isset($_SESSION['user_id'])) ? 'checkout-address.php' : '../login.php?redirect=pages/cart.php'; ?>" class="checkout-btn">
                                Proceed to Checkout
                            </a>
                            
                            <?php if (!$couponApplied): ?>
                                <div class="cart-note">
                                    <i class="fas fa-info-circle"></i> Apply a coupon code for additional discounts!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="cart-container">
                    <div class="cart-header">
                        <h2>Your Cart</h2>
                        <div class="cart-count-badge">0</div>
                    </div>
                    
                    <div class="cart-empty">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p>Looks like you haven't added any tests or checkups to your cart yet.</p>
                        <a href="../index.html" class="checkout-btn">Browse Tests & Checkups</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Optional: Add a small script to handle mobile menu toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navMenu = document.getElementById('nav-menu');
            
            if (mobileMenuToggle && navMenu) {
                mobileMenuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }
            
            // Check login status
            fetch('../check_login_status.php')
                .then(response => response.json())
                .then(data => {
                    // Get all menu items that should only be shown when logged in
                    const loggedInItems = document.querySelectorAll('.logged-in-only');
                    
                    if (data.loggedIn) {
                        // User is logged in
                        loggedInItems.forEach(item => item.style.display = 'block');
                        
                        // Hide login/register options
                        document.querySelector('a[href="../login.php"]').parentElement.style.display = 'none';
                        document.querySelector('a[href="../register.php"]').parentElement.style.display = 'none';
                        
                        // Update account text to show user's name
                        const accountText = document.getElementById('account-text');
                        if (accountText && data.user_first_name) {
                            accountText.textContent = data.user_first_name;
                        }
                    } else {
                        // User is logged out
                        loggedInItems.forEach(item => item.style.display = 'none');
                        
                        // Make sure login/register are visible
                        document.querySelector('a[href="../login.php"]').parentElement.style.display = 'block';
                        document.querySelector('a[href="../register.php"]').parentElement.style.display = 'block';
                        
                        // Reset account text
                        const accountText = document.getElementById('account-text');
                        if (accountText) {
                            accountText.textContent = 'Account';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking login status:', error);
                });
        });
    </script>

    <!-- Add this before the closing </body> tag -->
    <script>
    // Function to show cart notification
    function showCartNotification(message, isError = false) {
        // Remove any existing notifications
        const existingNotifications = document.querySelectorAll('.cart-notification');
        existingNotifications.forEach(notification => notification.remove());

        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.textContent = message;
        
        // Add styles
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.backgroundColor = isError ? '#e74c3c' : '#16A085';
        notification.style.color = 'white';
        notification.style.padding = '15px 25px';
        notification.style.borderRadius = '4px';
        notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        notification.style.zIndex = '1000';
        notification.style.animation = 'slideIn 0.3s ease-out';
        
        // Add keyframes for animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        // Add to document
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Function to update cart UI
        function updateCartUI(response) {
            if (!response.success) {
                showCartNotification(response.message || 'An error occurred', true);
                return;
            }

            // Update cart count
            const cartCountBadges = document.querySelectorAll('.cart-count, .cart-count-badge');
            cartCountBadges.forEach(badge => {
                badge.textContent = response.cart_count;
            });
            
            // Update subtotal
            const subtotalElement = document.querySelector('.summary-row:first-child div:last-child');
            if (subtotalElement) {
                subtotalElement.textContent = '₹ ' + response.subtotal.toFixed(2);
            }
            
            // Update total
            const totalElement = document.querySelector('.summary-row.total div:last-child');
            if (totalElement) {
                totalElement.textContent = '₹ ' + response.subtotal.toFixed(2);
            }
            
            // Show notification
            showCartNotification(response.message);
        }
        
        // Function to handle cart actions
        function handleCartAction(action, itemId, button) {
            if (!button || !itemId) {
                console.error('Invalid parameters:', { action, itemId, button });
                showCartNotification('Invalid request parameters', true);
                return;
            }

            // Disable the button that was clicked
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Create form data
            const formData = new FormData();
            formData.append('action', action);
            formData.append('id', String(itemId)); // Ensure ID is sent as string

            // Debug log
            console.log('Sending request:', {
                action: action,
                id: itemId,
                formData: Object.fromEntries(formData)
            });

            // Make the request
            fetch('../cart-actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response:', data); // Debug log
                
                if (data.success) {
                    if (action === 'remove' || (action === 'decrease' && data.message.includes('removed'))) {
                        // Remove the item element from DOM
                        const itemElement = document.querySelector(`.cart-item[data-id="${itemId}"]`);
                        if (itemElement) {
                            itemElement.remove();
                            
                            // If no items left, reload page to show empty cart
                            if (data.cart_count === 0) {
                                location.reload();
                            }
                        }
                    } else {
                        // Update quantity input and total
                        const itemElement = document.querySelector(`.cart-item[data-id="${itemId}"]`);
                        if (itemElement) {
                            const quantityInput = itemElement.querySelector('.quantity-input');
                            const itemTotal = itemElement.querySelector('.item-total');
                            const itemPrice = parseFloat(itemElement.querySelector('.item-price').textContent.replace('₹ ', ''));
                            
                            // Get the new quantity from the response
                            const newQuantity = data.item_quantity || parseInt(quantityInput.value) + (action === 'increase' ? 1 : -1);
                            quantityInput.value = newQuantity;
                            itemTotal.textContent = '₹ ' + (itemPrice * newQuantity).toFixed(2);
                        }
                    }
                    
                    updateCartUI(data);
                } else {
                    showCartNotification(data.message || 'An error occurred', true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCartNotification('An error occurred. Please try again.', true);
            })
            .finally(() => {
                // Re-enable the button
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }
        
        // Add event listeners for quantity buttons
        document.querySelectorAll('.increase-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = this.closest('.cart-item').dataset.id;
                console.log('Increase button clicked for item:', itemId); // Debug log
                handleCartAction('increase', itemId, this);
            });
        });
        
        document.querySelectorAll('.decrease-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = this.closest('.cart-item').dataset.id;
                console.log('Decrease button clicked for item:', itemId); // Debug log
                handleCartAction('decrease', itemId, this);
            });
        });
        
        // Add event listeners for remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = this.closest('.cart-item').dataset.id;
                console.log('Remove button clicked for item:', itemId); // Debug log
                handleCartAction('remove', itemId, this);
            });
        });
        
        // Add event listener for empty cart button
        const emptyCartBtn = document.querySelector('.empty-cart-btn');
        if (emptyCartBtn) {
            emptyCartBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to empty your cart?')) {
                    location.href = 'cart.php?action=empty';
                }
            });
        }
    });
    </script>
</body>
</html> 