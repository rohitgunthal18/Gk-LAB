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

// Include database connection
include_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$orders = [];
$message = '';

// Process order cancellation if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    if (isset($_POST['order_id'])) {
        $order_id = $_POST['order_id'];
        
        // First check if the order belongs to the user
        $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Set the order status to cancelled
            $status = "cancelled";
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $order_id);
            
            if ($stmt->execute()) {
                $message = "Order #" . $order_id . " has been cancelled successfully.";
            } else {
                $message = "Error cancelling order. Please try again.";
            }
        } else {
            $message = "You don't have permission to cancel this order.";
        }
    }
}

// Get all orders for the user
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Fetch order items for this order
    $order_id = $row['id'];
    $items = [];
    
    $items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
    
    $row['items'] = $items;
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        /* Cart Badge CSS */
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
            font-weight: bold;
        }
        
        .btn-cart {
            position: relative;
        }
        
        /* Animation for cart button */
        @keyframes cartPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .cart-added {
            animation: cartPulse 0.5s ease;
        }
        
        .orders-header {
            margin-bottom: 2rem;
        }
        
        .orders-header h2 {
            color: var(--primary-green);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .orders-header p {
            color: var(--text-gray);
        }
        
        .order-card {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .order-header {
            background-color: var(--background-light);
            padding: 1rem 1.5rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .order-id {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.1rem;
        }
        
        .order-date {
            color: var(--text-gray);
            font-size: 0.9rem;
        }
        
        .order-status {
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
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
        
        .order-content {
            padding: 1.5rem;
        }
        
        .order-items {
            margin-bottom: 1.5rem;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-name {
            flex: 1;
            color: var(--text-dark);
        }
        
        .item-details {
            display: flex;
            gap: 2rem;
            color: var(--text-gray);
        }
        
        .order-total {
            display: flex;
            justify-content: flex-end;
            font-weight: 600;
            color: var(--text-dark);
            padding: 1rem 0;
            border-top: 1px solid #eee;
        }
        
        .order-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
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
        
        .no-orders {
            text-align: center;
            padding: 3rem 0;
            color: var(--text-gray);
        }
        
        .no-orders i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
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
        
        .alert.alert-success {
            background-color: #E8F5E9;
            color: #1B5E20;
        }
        
        .alert.alert-error {
            background-color: #FFEBEE;
            color: #B71C1C;
        }
        
        @media (max-width: 768px) {
            .orders-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .item-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .item-details {
                width: 100%;
                justify-content: space-between;
            }
            
            .order-actions {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
                justify-content: center;
            }
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
                        <li><a href="orders.php">My Orders</a></li>
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
                        <li class="logged-in-only"><a href="../profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li class="logged-in-only"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Orders Section -->
    <section class="orders-section">
        <div class="orders-container">
            <div class="orders-header">
                <h2>My Orders</h2>
                <p>View and manage your test and health check-up orders</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
                    <i class="fas <?php echo strpos($message, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-clipboard-list"></i>
                    <p>You haven't placed any orders yet.</p>
                    <a href="../tests.php" class="btn-action btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-microscope"></i> Browse Tests
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                <div class="order-date"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                            </div>
                            
                            <?php 
                                $statusClass = '';
                                $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
                                
                                switch ($orderStatus) {
                                    case 'pending':
                                        $statusClass = 'status-pending';
                                        break;
                                    case 'processing':
                                        $statusClass = 'status-processing';
                                        break;
                                    case 'completed':
                                        $statusClass = 'status-completed';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'status-cancelled';
                                        break;
                                    default:
                                        $statusClass = 'status-pending';
                                }
                            ?>
                            
                            <div class="order-status <?php echo $statusClass; ?>">
                                <?php echo ucfirst($orderStatus); ?>
                            </div>
                        </div>
                        
                        <div class="order-content">
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="item-row">
                                        <div class="item-name"><?php echo $item['test_name']; ?></div>
                                        <div class="item-details">
                                            <div>Qty: <?php echo $item['quantity']; ?></div>
                                            <div>₹<?php echo number_format($item['price'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-total">
                                Total: ₹<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                            
                            <div class="order-actions">
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-action btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                
                                <?php if ($orderStatus === 'pending'): ?>
                                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                        <input type="hidden" name="action" value="cancel_order">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="btn-action btn-danger">
                                            <i class="fas fa-times"></i> Cancel Order
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- User login status handling -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                
            // Update cart count
            fetch('../pages/cart-count.php')
                .then(response => response.text())
                .then(data => {
                    try {
                        // Try to parse as JSON first
                        const jsonData = JSON.parse(data);
                        document.querySelector('.cart-count').textContent = jsonData.count;
                    } catch (e) {
                        // If not valid JSON, use as plain text
                        document.querySelector('.cart-count').textContent = data;
                    }
                })
                .catch(error => {
                    console.error('Error fetching cart count:', error);
                });
        });
    </script>
</body>
</html> 