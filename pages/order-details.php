<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=pages/orders.php');
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

// Include database connection
include_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'];
$order = null;
$order_items = [];

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
    
    // Get order items
    $items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
} else {
    // Order not found or doesn't belong to user
    header('Location: orders.php');
    exit;
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cancel_order') {
        $status = "cancelled";
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $status, $order_id, $user_id);
        
        if ($stmt->execute()) {
            // Refresh the page to show updated status
            header('Location: order-details.php?id=' . $order_id . '&cancelled=1');
            exit;
        }
    }
}

// Get status information
$statusClass = '';
$orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';

switch ($orderStatus) {
    case 'pending':
        $statusClass = 'status-pending';
        $statusText = 'Your order is being processed';
        break;
    case 'processing':
        $statusClass = 'status-processing';
        $statusText = 'Your order is being prepared';
        break;
    case 'completed':
        $statusClass = 'status-completed';
        $statusText = 'Your order has been completed successfully';
        break;
    case 'cancelled':
        $statusClass = 'status-cancelled';
        $statusText = 'Your order has been cancelled';
        break;
    default:
        $statusClass = 'status-pending';
        $statusText = 'Your order is being processed';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .order-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        .order-header {
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-header h2 {
            color: var(--primary-green);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 1rem;
        }
        
        .breadcrumb a {
            color: var(--primary-green);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb i {
            margin: 0 0.5rem;
            font-size: 0.7rem;
        }
        
        .order-status {
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #FFF3E0;
            color: #E65100;
        }
        
        .status-processing {
            background-color: #E3F2FD;
            color: #0D47A1;
        }
        
        .status-completed {
            background-color: #E8F5E9;
            color: #1B5E20;
        }
        
        .status-cancelled {
            background-color: #FFEBEE;
            color: #B71C1C;
        }
        
        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            background-color: var(--background-light);
        }
        
        .summary-card h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .summary-detail {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: flex-start;
        }
        
        .summary-detail:last-child {
            margin-bottom: 0;
        }
        
        .detail-icon {
            color: var(--text-gray);
            font-size: 1rem;
            margin-right: 0.8rem;
            margin-top: 0.2rem;
        }
        
        .detail-content {
            flex: 1;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: var(--text-gray);
            margin-bottom: 0.2rem;
        }
        
        .detail-value {
            color: var(--text-dark);
        }
        
        .order-timeline {
            margin-bottom: 2rem;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .order-timeline h3 {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: var(--text-dark);
        }
        
        .timeline-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
            padding: 0 2rem;
        }
        
        .timeline-line {
            position: absolute;
            top: 2rem;
            left: 3rem;
            right: 3rem;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .timeline-progress {
            position: absolute;
            top: 2rem;
            left: 3rem;
            height: 2px;
            background: var(--primary-green);
            z-index: 1;
            transition: width 0.5s ease;
        }
        
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            width: 25%;
        }
        
        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--white);
            border: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: var(--text-gray);
            font-size: 1.5rem;
            position: relative;
            z-index: 3;
        }
        
        .timeline-step.active .step-icon {
            background: var(--primary-green);
            color: var(--white);
            border-color: var(--primary-green);
        }
        
        .timeline-step.completed .step-icon {
            background: var(--primary-green);
            color: var(--white);
            border-color: var(--primary-green);
        }
        
        .step-title {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .step-date {
            font-size: 0.8rem;
            color: var(--text-gray);
            text-align: center;
        }
        
        .timeline-step.active .step-title,
        .timeline-step.completed .step-title {
            color: var(--primary-green);
        }
        
        .cancelled-notice {
            background-color: #FFEBEE;
            color: #B71C1C;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
        }
        
        .cancelled-notice i {
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .order-items {
            margin-bottom: 2rem;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .order-items h3 {
            font-size: 1.1rem;
            padding: 1.5rem;
            margin: 0;
            background-color: var(--background-light);
            color: var(--text-dark);
            border-bottom: 1px solid #eee;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            text-align: left;
            padding: 1rem 1.5rem;
            color: var(--text-gray);
            background-color: var(--background-light);
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }
        
        .items-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            color: var(--text-dark);
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .order-total-row {
            background-color: var(--background-light);
            font-weight: 600;
        }
        
        .order-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        
        .btn-action {
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-green);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #138D75;
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text-dark);
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background: #f5f5f5;
        }
        
        .btn-danger {
            background: #FFEBEE;
            color: #B71C1C;
            border: 1px solid #FFCDD2;
        }
        
        .btn-danger:hover {
            background: #FFCDD2;
        }
        
        .btn-action i {
            margin-right: 0.5rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            background-color: #E8F5E9;
            color: #1B5E20;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .order-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .timeline-steps {
                flex-direction: column;
                padding: 0;
                gap: 2rem;
            }
            
            .timeline-line, .timeline-progress {
                left: 30px;
                right: auto;
                top: 0;
                bottom: 0;
                width: 2px;
                height: 100%;
            }
            
            .timeline-step {
                width: 100%;
                flex-direction: row;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .step-icon {
                margin-bottom: 0;
            }
            
            .step-content {
                text-align: left;
            }
            
            .step-title, .step-date {
                text-align: left;
            }
            
            .order-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn-action {
                width: 100%;
                justify-content: center;
            }
            
            .items-table thead {
                display: none;
            }
            
            .items-table, .items-table tbody, .items-table tr, .items-table td {
                display: block;
                width: 100%;
            }
            
            .items-table tr {
                margin-bottom: 1rem;
                border-bottom: 1px solid #eee;
            }
            
            .items-table tr:last-child {
                border-bottom: none;
            }
            
            .items-table td {
                text-align: right;
                padding: 0.5rem 1rem;
                position: relative;
                border: none;
            }
            
            .items-table td:before {
                content: attr(data-label);
                float: left;
                font-weight: 500;
                color: var(--text-gray);
            }
            
            .order-total-row {
                background-color: transparent;
                border-top: 1px solid #eee;
                padding-top: 1rem;
            }
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

    <!-- Order Details Section -->
    <section class="order-details-section">
        <div class="order-container">
            <div class="order-header">
                <div>
                    <div class="breadcrumb">
                        <a href="orders.php">My Orders</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Order #<?php echo $order['id']; ?></span>
                    </div>
                    <h2>Order Details</h2>
                </div>
                
                <div class="order-status <?php echo $statusClass; ?>">
                    <?php echo ucfirst($orderStatus); ?>
                </div>
            </div>
            
            <?php if (isset($_GET['cancelled']) && $_GET['cancelled'] == 1): ?>
                <div class="alert">
                    <i class="fas fa-check-circle"></i>
                    Your order has been cancelled successfully.
                </div>
            <?php endif; ?>
            
            <?php if ($orderStatus === 'cancelled'): ?>
                <div class="cancelled-notice">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>This order has been cancelled</strong>
                        <p>If you cancelled this by mistake, please contact our support team.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="order-summary">
                <div class="summary-card">
                    <h3>Order Information</h3>
                    
                    <div class="summary-detail">
                        <div class="detail-icon">
                            <i class="fas fa-hashtag"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Order Number</div>
                            <div class="detail-value">#<?php echo $order['id']; ?></div>
                        </div>
                    </div>
                    
                    <div class="summary-detail">
                        <div class="detail-icon">
                            <i class="far fa-calendar-alt"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="summary-detail">
                        <div class="detail-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php /* Display additional information if available in your database
                <div class="summary-card">
                    <h3>Delivery Information</h3>
                    
                    <div class="summary-detail">
                        <div class="detail-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Delivery Address</div>
                            <div class="detail-value"><?php echo $order['billing_address']; ?>, <?php echo $order['billing_city']; ?>, <?php echo $order['billing_state']; ?> - <?php echo $order['billing_pincode']; ?></div>
                        </div>
                    </div>
                    
                    <div class="summary-detail">
                        <div class="detail-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Contact Name</div>
                            <div class="detail-value"><?php echo $order['billing_first_name'] . ' ' . $order['billing_last_name']; ?></div>
                        </div>
                    </div>
                    
                    <div class="summary-detail">
                        <div class="detail-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Contact Phone</div>
                            <div class="detail-value"><?php echo $order['billing_phone']; ?></div>
                        </div>
                    </div>
                </div>
                */ ?>
            </div>
            
            <div class="order-items">
                <h3>Order Items</h3>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($order_items as $item): 
                            $itemTotal = $item['price'] * $item['quantity'];
                            $subtotal += $itemTotal;
                        ?>
                            <tr>
                                <td data-label="Item"><?php echo $item['test_name']; ?></td>
                                <td data-label="Price">₹<?php echo number_format($item['price'], 2); ?></td>
                                <td data-label="Quantity"><?php echo $item['quantity']; ?></td>
                                <td data-label="Total" class="text-right">₹<?php echo number_format($itemTotal, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <tr class="order-total-row">
                            <td colspan="3" data-label="Total Amount">Total Amount</td>
                            <td class="text-right">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="order-actions">
                <a href="orders.php" class="btn-action btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
                
                <?php if ($orderStatus === 'pending'): ?>
                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <input type="hidden" name="action" value="cancel_order">
                        <button type="submit" class="btn-action btn-danger">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
</body>
</html> 