<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=pages/checkout-payment.php');
    exit;
}

// Include database connection
include_once '../config/db.php';

// Check if cart is empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header('Location: cart.php');
    exit;
}

// Check if address is selected
if (!isset($_SESSION['checkout_address_id'])) {
    header('Location: checkout-address.php');
    exit;
}

// Calculate cart totals
$subtotal = 0;
$discount = 0;
$total = 0;

// Calculate total from cart items
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Apply coupon discount if applicable
$discount = 0;
$couponApplied = false;
$discountLabel = "Discount";

if (isset($_SESSION['coupon'])) {
    $couponApplied = true;
    $couponDetails = $_SESSION['coupon'];
    $couponCode = $couponDetails['code'];
    
    if ($couponDetails['discount_type'] === 'percentage') {
        $discount = ($subtotal * $couponDetails['discount_value']) / 100;
        $discountLabel = "Discount ({$couponDetails['discount_value']}% off)";
        
        // Apply maximum discount cap if set
        if (!empty($couponDetails['max_discount_amount']) && $discount > $couponDetails['max_discount_amount']) {
            $discount = $couponDetails['max_discount_amount'];
        }
    } else {
        // Fixed amount discount
        $discount = $couponDetails['discount_value'];
        $discountLabel = "Discount (₹{$couponDetails['discount_value']} off)";
    }
}

$total = $subtotal - $discount;

// Get address details
$address_id = $_SESSION['checkout_address_id'];
$address_details = null;

$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $address_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $address_details = $result->fetch_assoc();
} else {
    // Address not found, redirect back
    header('Location: checkout-address.php');
    exit;
}

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
    
    // Validate payment method
    if ($payment_method !== 'cod' && $payment_method !== 'upi') {
        $error = "Invalid payment method selected";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into orders table with only essential fields
            $stmt = $conn->prepare("INSERT INTO orders (
                user_id, 
                total_amount,
                payment_method,
                payment_status,
                order_status
            ) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $user_id = $_SESSION['user_id'];
            $payment_status = ($payment_method === 'cod') ? 'pending' : 'completed';
            $order_status = 'pending';
            $coupon_code = isset($_SESSION['coupon']) ? $_SESSION['coupon']['code'] : null;
            
            // Save the final total (after discount) as total_amount
            $finalTotal = $subtotal - $discount;
            
            $stmt->bind_param(
                "idsss", 
                $user_id, 
                $finalTotal, // Save discounted total
                $payment_method,
                $payment_status,
                $order_status
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $order_id = $conn->insert_id;
            
            // Insert order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, item_name, item_type, price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($_SESSION['cart'] as $item) {
                $itemPrice = $item['price'];
                $itemQuantity = $item['quantity'];
                $stmt->bind_param("iissdi", $order_id, $item['id'], $item['name'], $item['type'], $itemPrice, $itemQuantity);
                $stmt->execute();
            }
            
            // Record coupon usage if a coupon was applied
            if (isset($_SESSION['coupon'])) {
                try {
                    $couponId = $_SESSION['coupon']['id'];
                    
                    // Insert coupon usage record
                    $usageStmt = $conn->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, used_at) VALUES (?, ?, ?, ?, NOW())");
                    $usageStmt->bind_param("iiid", $couponId, $user_id, $order_id, $discount);
                    $usageStmt->execute();
                    
                    // Update coupon usage count in the coupons table
                    $updateCouponStmt = $conn->prepare("UPDATE coupons SET current_uses = current_uses + 1 WHERE id = ?");
                    $updateCouponStmt->bind_param("i", $couponId);
                    $updateCouponStmt->execute();
                } catch (Exception $e) {
                    // Log error but don't fail the order
                    error_log("Error recording coupon usage: " . $e->getMessage());
                }
            }
            
            // Try to update the coupon code separately
            if (isset($_SESSION['coupon']) && !empty($_SESSION['coupon']['code'])) {
                try {
                    $coupon_code = $_SESSION['coupon']['code'];
                    $updateStmt = $conn->prepare("UPDATE orders SET coupon_code = ? WHERE id = ?");
                    if ($updateStmt) {
                        $updateStmt->bind_param("si", $coupon_code, $order_id);
                        $updateStmt->execute();
                    }
                } catch (Exception $e) {
                    // Log error but don't fail the order
                    error_log("Error updating coupon code: " . $e->getMessage());
                }
            }
            
            // Try to update the subtotal field separately if it exists
            try {
                $updateStmt = $conn->prepare("UPDATE orders SET subtotal = ? WHERE id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("di", $subtotal, $order_id);
                    $updateStmt->execute();
                }
            } catch (Exception $e) {
                // Ignore if field doesn't exist
                error_log("Subtotal field might not exist: " . $e->getMessage());
            }
            
            // Try to store the discount separately
            if ($discount > 0) {
                // First try with a generic field name
                try {
                    $updateStmt = $conn->prepare("UPDATE orders SET discount = ? WHERE id = ?");
                    if ($updateStmt) {
                        $updateStmt->bind_param("di", $discount, $order_id);
                        $updateStmt->execute();
                    }
                } catch (Exception $e) {
                    // Log error but don't fail the order
                    error_log("Discount field might not exist: " . $e->getMessage());
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart and checkout data
            unset($_SESSION['cart']);
            unset($_SESSION['checkout_address_id']);
            unset($_SESSION['coupon']); // Clear the coupon after successful order
            
            // Set success message and redirect
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $order_id;
            
            header('Location: order-success.php?order_id=' . $order_id);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .checkout-header {
            margin-bottom: 2rem;
        }

        .checkout-header h2 {
            color: var(--primary-green);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            color: #666;
            position: relative;
            z-index: 2;
        }

        .step.active .step-icon,
        .step.completed .step-icon {
            background: var(--primary-green);
            color: var(--white);
        }

        .step-text {
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        .step.active .step-text,
        .step.completed .step-text {
            color: var(--primary-green);
            font-weight: 600;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 70px;
            right: 70px;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .progress-line-active {
            position: absolute;
            top: 20px;
            left: 70px;
            width: 66%; /* 2/3 complete */
            height: 2px;
            background: var(--primary-green);
            z-index: 0;
        }

        .payment-options {
            margin-bottom: 2rem;
        }

        .payment-option {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: var(--white);
        }

        .payment-option:hover {
            border-color: #bbb;
        }

        .payment-option.selected {
            border-color: var(--primary-green);
            background-color: rgba(22, 160, 133, 0.05);
        }

        .payment-radio {
            margin-right: 15px;
            margin-top: 3px;
            accent-color: var(--primary-green);
        }

        .payment-details {
            flex: 1;
        }

        .payment-details h4 {
            margin: 0 0 0.5rem;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .payment-details p {
            margin: 0;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .order-summary {
            margin-bottom: 2rem;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            background-color: var(--background-light);
        }

        .order-summary h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--text-dark);
            font-size: 1.2rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: var(--text-gray);
        }

        .summary-item.total {
            border-top: 1px solid #eee;
            padding-top: 1rem;
            margin-top: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .summary-title {
            flex: 1;
        }

        .summary-value {
            text-align: right;
        }

        .address-summary {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            background-color: var(--white);
        }

        .address-summary h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--text-dark);
            font-size: 1.2rem;
        }

        .address-details p {
            margin: 0.3rem 0;
            color: var(--text-gray);
        }

        .address-details strong {
            color: var(--text-dark);
        }

        .change-link {
            color: var(--primary-green);
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .change-link:hover {
            text-decoration: underline;
        }

        .checkout-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .btn-continue {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-continue:hover {
            background: #138D75;
        }

        .btn-back {
            background: #f5f5f5;
            color: var(--text-dark);
            border: 1px solid #ddd;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #e9e9e9;
        }

        .payment-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .checkout-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .checkout-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn-continue, .btn-back {
                width: 100%;
            }
        }

        .summary-item.coupon-info {
            background-color: rgba(22, 160, 133, 0.08);
            padding: 8px 10px;
            border-radius: 4px;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        
        .summary-item.coupon-info .summary-value {
            font-weight: 600;
            color: var(--primary-green);
            background-color: rgba(22, 160, 133, 0.15);
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .coupon-badge {
            position: relative;
            cursor: pointer;
        }
        
        .coupon-tooltip {
            display: none;
            position: absolute;
            background-color: #333;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            width: 200px;
            z-index: 100;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 5px;
            text-align: center;
            font-weight: normal;
        }
        
        .coupon-badge:hover .coupon-tooltip {
            display: block;
        }
        
        .coupon-tooltip:after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: transparent transparent #333 transparent;
        }

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
                <a href="cart.php" class="btn-cart">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <span class="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
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

    <!-- Checkout Payment Section -->
    <section class="checkout-section">
        <div class="checkout-container">
            <div class="checkout-header">
                <h2>Checkout</h2>
                <p>Select your payment method</p>
            </div>
            
            <div class="progress-steps">
                <div class="progress-line"></div>
                <div class="progress-line-active" style="width: 66%;"></div>
                
                <div class="step completed">
                    <div class="step-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-text">Address</div>
                </div>
                
                <div class="step active">
                    <div class="step-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="step-text">Payment</div>
                </div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-text">Confirmation</div>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="payment-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="checkout-content">
                <div class="address-summary">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Delivery Address</h3>
                        <a href="checkout-address.php" class="change-link">Change</a>
                    </div>
                    
                    <div class="address-details">
                        <p><strong><?php echo $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name']; ?></strong></p>
                        <p><?php echo $address_details['address']; ?></p>
                        <p><?php echo $address_details['city'] . ', ' . $address_details['state'] . ' - ' . $address_details['pincode']; ?></p>
                    </div>
                </div>
                
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-item">
                        <div class="summary-title">Subtotal</div>
                        <div class="summary-value">₹<?php echo number_format($subtotal, 2); ?></div>
                    </div>
                    
                    <?php if ($discount > 0): ?>
                    <div class="summary-item">
                        <div class="summary-title"><?php echo $discountLabel; ?></div>
                        <div class="summary-value">-₹<?php echo number_format($discount, 2); ?></div>
                    </div>
                    <?php if ($couponApplied): ?>
                    <div class="summary-item coupon-info">
                        <div class="summary-title">Coupon Applied</div>
                        <div class="summary-value coupon-badge">
                            <?php echo htmlspecialchars($couponCode); ?>
                            <?php if (isset($couponDetails['description']) && !empty($couponDetails['description'])): ?>
                            <span class="coupon-tooltip"><?php echo htmlspecialchars($couponDetails['description']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="summary-item total">
                        <div class="summary-title">Total</div>
                        <div class="summary-value">₹<?php echo number_format($total, 2); ?></div>
                    </div>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="payment-options">
                        <h3>Select Payment Method</h3>
                        
                        <div class="payment-option" onclick="selectPayment('upi')">
                            <input type="radio" name="payment_method" id="payment_upi" value="upi" class="payment-radio" required>
                            <div class="payment-details">
                                <h4>UPI Payment</h4>
                                <p>Pay using UPI apps like Google Pay, PhonePe, Paytm, etc.</p>
                            </div>
                        </div>
                        
                        <div class="payment-option" onclick="selectPayment('cod')">
                            <input type="radio" name="payment_method" id="payment_cod" value="cod" class="payment-radio" required>
                            <div class="payment-details">
                                <h4>Cash on Delivery</h4>
                                <p>Pay when our phlebotomist arrives for sample collection</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkout-actions">
                        <a href="checkout-address.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn-continue" id="placeOrderBtn">
                            Place Order <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- JavaScript -->
    <script>
        function selectPayment(method) {
            // Clear previous selections
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Select the clicked option
            document.querySelector(`input[value="${method}"]`).checked = true;
            document.querySelector(`input[value="${method}"]`).closest('.payment-option').classList.add('selected');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Select default payment method if none is selected
            if (!document.querySelector('input[name="payment_method"]:checked')) {
                selectPayment('cod');
            }
        });
    </script>
</body>
</html> 