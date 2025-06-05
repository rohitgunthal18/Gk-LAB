<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if this page was accessed properly
if (!isset($_SESSION['order_success']) || !isset($_GET['order_id'])) {
    header('Location: ../index.html');
    exit;
}

// Include database connection
include_once '../config/db.php';

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$order_details = null;
$order_items = [];

// Get order data
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $order_details = $result->fetch_assoc();
    
    // Get order items
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
} else {
    // Order not found or doesn't belong to user
    header('Location: ../index.html');
    exit;
}

// Clear success flag after showing this page
unset($_SESSION['order_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        .success-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--primary-green);
            margin-bottom: 1rem;
        }
        
        .success-header h2 {
            color: var(--text-dark);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .success-header p {
            color: var(--text-gray);
        }
        
        .order-details {
            margin-bottom: 2rem;
        }
        
        .order-details h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.5rem;
        }
        
        .detail-label {
            width: 200px;
            color: var(--text-gray);
            font-weight: 500;
        }
        
        .detail-value {
            flex: 1;
            color: var(--text-dark);
        }
        
        .order-items {
            margin-bottom: 2rem;
        }
        
        .order-items h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .item-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .item-table th {
            text-align: left;
            padding: 0.75rem;
            color: var(--text-gray);
            background: var(--background-light);
            font-weight: 500;
        }
        
        .item-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            color: var(--text-dark);
        }
        
        .item-right {
            text-align: right;
        }
        
        .order-total {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
            font-size: 1rem;
        }
        
        .total-row {
            display: flex;
            gap: 1rem;
            width: 300px;
        }
        
        .total-label {
            flex: 1;
            text-align: right;
            color: var(--text-gray);
        }
        
        .total-value {
            width: 100px;
            text-align: right;
            color: var(--text-dark);
        }
        
        .grand-total {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-dark);
            border-top: 1px solid #eee;
            padding-top: 0.5rem;
        }
        
        .actions {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .btn-action {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            background: #138D75;
        }
        
        .btn-action i {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .success-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .detail-row {
                flex-direction: column;
                margin-bottom: 1rem;
            }
            
            .detail-label {
                width: 100%;
                margin-bottom: 0.25rem;
            }
            
            .item-table thead {
                display: none;
            }
            
            .item-table, .item-table tbody, .item-table tr, .item-table td {
                display: block;
                width: 100%;
            }
            
            .item-table tr {
                margin-bottom: 1rem;
                border: 1px solid #eee;
                border-radius: 4px;
                padding: 0.5rem;
            }
            
            .item-table td {
                padding: 0.5rem;
                border: none;
                text-align: right;
                position: relative;
                padding-left: 50%;
            }
            
            .item-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 0.5rem;
                width: 45%;
                text-align: left;
                font-weight: 500;
                color: var(--text-gray);
            }
            
            .total-row {
                width: 100%;
            }
            
            .actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
        
        .total-value.discount-value {
            color: #16A085;
            font-weight: 500;
        }
        
        .total-value.coupon-code {
            color: #16A085;
            font-weight: 500;
            background-color: rgba(22, 160, 133, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            width: auto;
            text-align: center;
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

    <!-- Success Section -->
    <section class="success-section">
        <div class="success-container">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Order Placed Successfully!</h2>
                <p>Thank you for your order. Your health check-up/test has been scheduled.</p>
                <p>Our phlebotomist will contact you shortly to confirm the details.</p>
            </div>
            
            <div class="order-details">
                <h3>Order Information</h3>
                
                <div class="detail-row">
                    <div class="detail-label">Order Number</div>
                    <div class="detail-value">#<?php echo $order_details['id']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Order Date</div>
                    <div class="detail-value"><?php echo date('d M Y, h:i A', strtotime($order_details['created_at'])); ?></div>
                </div>
                
                <?php if (isset($order_details['payment_method'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Payment Method</div>
                    <div class="detail-value"><?php echo ucfirst($order_details['payment_method']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($order_details['payment_status'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Payment Status</div>
                    <div class="detail-value"><?php echo ucfirst($order_details['payment_status']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php /* Removed fields that don't exist in the database
                <div class="detail-row">
                    <div class="detail-label">Delivery Address</div>
                    <div class="detail-value"><?php echo $order_details['customer_address']; ?></div>
                </div>
                */ ?>
            </div>
            
            <div class="order-items">
                <h3>Order Items</h3>
                
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="item-right">Price</th>
                            <th class="item-right">Quantity</th>
                            <th class="item-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td data-label="Item"><?php echo $item['item_name']; ?></td>
                                <td data-label="Price" class="item-right">₹<?php echo number_format($item['price'], 2); ?></td>
                                <td data-label="Quantity" class="item-right"><?php echo $item['quantity']; ?></td>
                                <td data-label="Total" class="item-right">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="order-total">
                    <?php 
                    // Get the subtotal and discount
                    $subtotal = 0;
                    $discountAmount = 0;
                    
                    // Check if we have a subtotal field
                    if (isset($order_details['subtotal']) && $order_details['subtotal'] > 0) {
                        $subtotal = $order_details['subtotal'];
                    } else {
                        // Calculate the original subtotal by adding back the discount
                        $subtotal = $order_details['total_amount'];
                        
                        if (isset($order_details['discount']) && $order_details['discount'] > 0) {
                            $discountAmount = $order_details['discount'];
                            $subtotal += $discountAmount;
                        }
                    }
                    
                    // Get discount amount
                    if (isset($order_details['discount']) && $order_details['discount'] > 0) {
                        $discountAmount = $order_details['discount'];
                    }
                    ?>
                    
                    <div class="total-row">
                        <div class="total-label">Subtotal</div>
                        <div class="total-value">₹<?php echo number_format($subtotal, 2); ?></div>
                    </div>
                    
                    <?php if ($discountAmount > 0): ?>
                    <div class="total-row">
                        <div class="total-label">Discount</div>
                        <div class="total-value discount-value">-₹<?php echo number_format($discountAmount, 2); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order_details['coupon_code']) && !empty($order_details['coupon_code'])): ?>
                    <div class="total-row">
                        <div class="total-label">Coupon Applied</div>
                        <div class="total-value coupon-code"><?php echo $order_details['coupon_code']; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="total-row grand-total">
                        <div class="total-label">Total</div>
                        <div class="total-value">₹<?php echo number_format($order_details['total_amount'], 2); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <a href="../index.html" class="btn-action">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="orders.php" class="btn-action">
                    <i class="fas fa-clipboard-list"></i> View Orders
                </a>
            </div>
        </div>
    </section>

    <!-- JavaScript to redirect after timeout -->
    <script>
        // Redirect to home page after 5 minutes of inactivity
        setTimeout(function() {
            window.location.href = '../index.html';
        }, 5 * 60 * 1000);
    </script>
</body>
</html> 