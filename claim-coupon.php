<?php
/**
 * Claim Coupon Page
 * 
 * This is a hidden page for users to claim special coupons for their first order.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
include_once 'config/db.php';
include_once 'includes/functions.php';

// Page title
$pageTitle = "Claim Your Special Discount";

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$userEmail = $isLoggedIn ? $_SESSION['user_email'] : '';

// Check for redirect from login page
$redirected_from_login = isset($_GET['login_success']) && $_GET['login_success'] == 1;
$show_login_success = false;

// Create user_coupons table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS user_coupons (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    coupon_id INT(11) NOT NULL,
    claimed_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    used_at DATETIME DEFAULT NULL,
    order_id INT(11) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_coupon (user_id, coupon_id)
)";

if (!$conn->query($sql)) {
    // Handle error silently
}

// Get available first-order coupons
$available_coupons = [];
$current_date = date('Y-m-d H:i:s');

$sql = "SELECT * FROM coupons 
        WHERE status = 'active' 
        AND is_first_order_only = 1 
        AND start_date <= ? 
        AND end_date >= ?
        AND (max_uses IS NULL OR current_uses < max_uses)
        ORDER BY discount_value DESC
        LIMIT 3";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $current_date, $current_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $available_coupons[] = $row;
    }
}

// Handle form submission for claiming coupon
$coupon_claimed = false;
$claimed_coupon = null;
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_coupon'])) {
    $coupon_id = intval($_POST['coupon_id']);
    
    // Validate coupon
    $coupon_sql = "SELECT * FROM coupons WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($coupon_sql);
    $stmt->bind_param('i', $coupon_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $claimed_coupon = $result->fetch_assoc();
        
        // Check if user is logged in
        if (!$isLoggedIn) {
            // Store coupon claim in session for after login
            $_SESSION['pending_coupon_claim'] = $coupon_id;
            $_SESSION['login_message'] = "Please log in to claim your exclusive discount coupon!";
            
            // Redirect to login page
            header('Location: login.php?redirect=claim-coupon.php');
            exit;
        }
        
        // Check if user already has orders
        $order_check_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
        $stmt = $conn->prepare($order_check_sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['order_count'] > 0 && $claimed_coupon['is_first_order_only']) {
            $error_message = "This coupon is only available for your first order. You already have orders in the system.";
        } else {
            // Check if user has already claimed this coupon
            $usage_check_sql = "SELECT COUNT(*) as usage_count FROM coupon_usage WHERE coupon_id = ? AND user_id = ?";
            $stmt = $conn->prepare($usage_check_sql);
            $stmt->bind_param('ii', $coupon_id, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['usage_count'] > 0 && $claimed_coupon['is_one_time_use']) {
                $error_message = "You have already claimed this coupon.";
            } else {
                // Add coupon to user's account
                $add_sql = "INSERT INTO user_coupons (user_id, coupon_id, claimed_at, used) VALUES (?, ?, NOW(), 0)";
                $stmt = $conn->prepare($add_sql);
                $stmt->bind_param('ii', $userId, $coupon_id);
                
                if ($stmt->execute()) {
                    // Update coupon claim count
                    $update_sql = "UPDATE coupons SET current_uses = current_uses + 1 WHERE id = ?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param('i', $coupon_id);
                    $stmt->execute();
                    
                    // Set success flag
                    $coupon_claimed = true;
                    $success_message = "Congratulations! You've claimed an exclusive discount coupon that's been reserved just for you!";
                } else {
                    $error_message = "Error claiming coupon. Please try again.";
                }
            }
        }
    } else {
        $error_message = "Invalid coupon or coupon has expired.";
    }
}

// Check if there's a pending coupon claim after login
if ($isLoggedIn && isset($_SESSION['pending_coupon_claim'])) {
    $coupon_id = intval($_SESSION['pending_coupon_claim']);
    unset($_SESSION['pending_coupon_claim']);
    $show_login_success = true;
    
    // Add coupon to user's account
    $add_sql = "INSERT INTO user_coupons (user_id, coupon_id, claimed_at, used) VALUES (?, ?, NOW(), 0)";
    $stmt = $conn->prepare($add_sql);
    $stmt->bind_param('ii', $userId, $coupon_id);
    
    if ($stmt->execute()) {
        // Update coupon claim count
        $update_sql = "UPDATE coupons SET current_uses = current_uses + 1 WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('i', $coupon_id);
        $stmt->execute();
        
        // Get coupon details
        $coupon_sql = "SELECT * FROM coupons WHERE id = ?";
        $stmt = $conn->prepare($coupon_sql);
        $stmt->bind_param('i', $coupon_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $claimed_coupon = $result->fetch_assoc();
            $coupon_claimed = true;
            $success_message = "Welcome back! You've successfully claimed your exclusive discount coupon!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .coupon-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .coupon-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .coupon-header h1 {
            color: #16A085;
            margin-bottom: 10px;
        }
        
        .coupon-header p {
            color: #666;
            font-size: 18px;
        }
        
        .coupon-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            position: relative;
        }
        
        .coupon-card {
            border: 2px dashed #16A085;
            border-radius: 8px;
            padding: 20px;
            background-color: #f9f9f9;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .coupon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .coupon-card:before, .coupon-card:after {
            content: "";
            position: absolute;
            background-color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }
        
        .coupon-card:before {
            top: -10px;
            left: -10px;
        }
        
        .coupon-card:after {
            bottom: -10px;
            left: -10px;
        }
        
        .coupon-exclusive-tag {
            position: absolute;
            top: 10px;
            right: -30px;
            background-color: #FF8A00;
            color: white;
            padding: 5px 30px;
            font-size: 12px;
            font-weight: bold;
            transform: rotate(45deg);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 1;
        }
        
        .coupon-code {
            background-color: #16A085;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-family: monospace;
            font-size: 18px;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .coupon-value {
            font-size: 28px;
            font-weight: 700;
            color: #FF8A00;
            margin-bottom: 15px;
        }
        
        .coupon-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .coupon-details p {
            margin: 5px 0;
        }
        
        .coupon-expires {
            font-size: 12px;
            color: #FF8A00;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .btn-claim {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #16A085;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-claim:hover {
            background-color: #138a72;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-claim:active {
            transform: translateY(0);
        }
        
        .btn-claim:before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.5s ease;
        }
        
        .btn-claim:hover:before {
            left: 100%;
        }
        
        .btn-claim:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .claimed-message {
            text-align: center;
            margin: 40px 0;
            padding: 40px 30px;
            background-color: #e8f7f4;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .claimed-message:before {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            background-color: rgba(22, 160, 133, 0.05);
            border-radius: 50%;
            top: -50px;
            right: -50px;
            z-index: 0;
        }
        
        .claimed-message:after {
            content: "";
            position: absolute;
            width: 100px;
            height: 100px;
            background-color: rgba(255, 138, 0, 0.05);
            border-radius: 50%;
            bottom: -30px;
            left: -30px;
            z-index: 0;
        }
        
        .claimed-message h2 {
            color: #16A085;
            margin-bottom: 20px;
            position: relative;
        }
        
        .claimed-message .coupon-code-display {
            font-size: 28px;
            font-weight: bold;
            background-color: #16A085;
            color: white;
            padding: 15px 30px;
            border-radius: 6px;
            margin: 25px 0;
            display: inline-block;
            font-family: monospace;
            letter-spacing: 2px;
            position: relative;
            box-shadow: 0 5px 15px rgba(22, 160, 133, 0.2);
        }
        
        .claimed-message p {
            margin: 15px 0;
            font-size: 16px;
            position: relative;
        }
        
        .claimed-message .btn-shop {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background-color: #FF8A00;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 5px 15px rgba(255, 138, 0, 0.2);
        }
        
        .claimed-message .btn-shop:hover {
            background-color: #e67e00;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 138, 0, 0.3);
        }
        
        .claimed-message .btn-shop:active {
            transform: translateY(0);
        }
        
        .error-message {
            background-color: #fceaea;
            color: #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.1);
        }
        
        .success-message {
            background-color: #e8f7f4;
            color: #16A085;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(22, 160, 133, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .success-message:before {
            content: "🎉";
            position: absolute;
            font-size: 60px;
            opacity: 0.1;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
            max-width: 350px;
            z-index: 1000;
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .notification .notification-content {
            display: flex;
            align-items: center;
        }
        
        .notification .notification-icon {
            margin-right: 15px;
            font-size: 24px;
            color: #16A085;
        }
        
        .notification .notification-text {
            flex: 1;
        }
        
        .notification .notification-text h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }
        
        .notification .notification-text p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        
        .notification .notification-close {
            margin-left: 10px;
            cursor: pointer;
            color: #999;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .coupon-cards {
                grid-template-columns: 1fr;
            }
            
            .coupon-code-display {
                font-size: 20px;
                padding: 12px 20px;
            }
            
            .notification {
                left: 20px;
                right: 20px;
                max-width: none;
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
                    <a href="index.html">
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
                        <li><a href="pages/about.html">About Us</a></li>
                        <li><a href="pages/contact.html">Contact</a></li>
                    </ul>
                </div>
                <a href="tests.php" class="menu-item">
                    <i class="fas fa-microscope"></i>
                    Tests
                </a>
                <a href="checkups.php" class="menu-item">
                    <i class="fas fa-box"></i>
                    Checkups
                </a>
                <a href="pages/support.html" class="btn-support">
                    <i class="fab fa-whatsapp"></i>
                    Support
                </a>
                <a href="pages/cart.php" class="btn-cart">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                </a>
                
                <div class="menu-dropdown">
                    <a href="#" class="btn-support dropdown-toggle user-toggle" style="display: flex; align-items: center;">
                        <i class="fas fa-user-circle"></i>
                        Account <i class="fas fa-chevron-down" style="margin-left: 8px;"></i>
                    </a>
                    <ul class="dropdown-menu user-dropdown">
                        <?php if ($isLoggedIn): ?>
                            <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                            <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <section class="coupon-section">
        <div class="container coupon-container">
            <?php if ($coupon_claimed && $claimed_coupon): ?>
                <!-- Coupon Claimed Successfully -->
                <div class="claimed-message">
                    <i class="fas fa-check-circle" style="font-size: 80px; color: #16A085; margin-bottom: 20px;"></i>
                    <h2>Congratulations! Your Exclusive Coupon Has Been Claimed!</h2>
                    <p>You're one of the select few who have received this special offer. Use this code at checkout to redeem your discount:</p>
                    <div class="coupon-code-display">
                        <?php echo htmlspecialchars($claimed_coupon['code']); ?>
                    </div>
                    
                    <div class="coupon-details" style="margin-top: 15px;">
                        <p><strong>Your Discount:</strong> 
                            <?php if ($claimed_coupon['discount_type'] === 'percentage'): ?>
                                <?php echo $claimed_coupon['discount_value']; ?>% off your order
                                <?php if (!empty($claimed_coupon['max_discount_amount'])): ?>
                                    (up to ₹<?php echo $claimed_coupon['max_discount_amount']; ?>)
                                <?php endif; ?>
                            <?php else: ?>
                                ₹<?php echo $claimed_coupon['discount_value']; ?> off your order
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($claimed_coupon['min_order_value'] > 0): ?>
                            <p><strong>Minimum Order:</strong> ₹<?php echo $claimed_coupon['min_order_value']; ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Valid Until:</strong> <?php echo date('F j, Y', strtotime($claimed_coupon['end_date'])); ?></p>
                    </div>
                    
                    <p>This coupon has been added to your account and will be available during checkout.</p>
                    
                    <a href="tests.php" class="btn-shop">
                        <i class="fas fa-shopping-cart"></i> Start Shopping Now
                    </a>
                </div>
            <?php else: ?>
                <!-- Coupons Available to Claim -->
                <div class="coupon-header">
                    <h1>Exclusive Discount Coupons Just For You</h1>
                    <p>You've been selected to receive these special discount offers for your first order at GK Lab</p>
                </div>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($available_coupons)): ?>
                    <div class="claimed-message">
                        <i class="fas fa-info-circle" style="font-size: 50px; color: #3498db; margin-bottom: 20px;"></i>
                        <h2>No Special Coupons Available</h2>
                        <p>Sorry, there are no special coupons available for you at the moment.</p>
                        <p>Please check back later for new promotions!</p>
                        <a href="index.html" class="btn-shop">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                <?php else: ?>
                    <div class="coupon-cards">
                        <?php foreach ($available_coupons as $coupon): ?>
                            <div class="coupon-card">
                                <div class="coupon-exclusive-tag">EXCLUSIVE</div>
                                <div class="coupon-code"><?php echo htmlspecialchars($coupon['code']); ?></div>
                                <div class="coupon-value">
                                    <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                        <?php echo $coupon['discount_value']; ?>% OFF
                                    <?php else: ?>
                                        ₹<?php echo $coupon['discount_value']; ?> OFF
                                    <?php endif; ?>
                                </div>
                                <div class="coupon-details">
                                    <p><?php echo htmlspecialchars($coupon['description']); ?></p>
                                    
                                    <?php if ($coupon['min_order_value'] > 0): ?>
                                        <p>Minimum order: ₹<?php echo $coupon['min_order_value']; ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($coupon['discount_type'] === 'percentage' && !empty($coupon['max_discount_amount'])): ?>
                                        <p>Maximum discount: ₹<?php echo $coupon['max_discount_amount']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="coupon-expires">
                                    Valid until: <?php echo date('F j, Y', strtotime($coupon['end_date'])); ?>
                                </div>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                    <button type="submit" name="claim_coupon" class="btn-claim">
                                        <i class="fas fa-gift"></i> Claim This Exclusive Offer
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Notification -->
    <div id="notification" class="notification">
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas fa-gift"></i>
            </div>
            <div class="notification-text">
                <h3>Congratulations!</h3>
                <p>You've claimed your exclusive discount coupon!</p>
            </div>
            <div class="notification-close">
                <i class="fas fa-times"></i>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-container">
                <!-- Company Info -->
                <div class="footer-col">
                    <div class="footer-logo">
                        <!-- SVG logo directly embedded -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 80" width="180" height="60">
                          <defs>
                            <linearGradient id="flaskGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                              <stop offset="0%" stop-color="#16A085"/>
                              <stop offset="100%" stop-color="#107C67"/>
                            </linearGradient>
                          </defs>
                          
                          <!-- Main Flask -->
                          <path d="M50 15 v12 L30 65 c-1 2 0 5 3 5 h34 c3 0 4-3 3-5 L50 27 v-12 z" 
                                fill="white" stroke="url(#flaskGradient)" stroke-width="2.5"/>
                          
                          <!-- Flask liquid -->
                          <path d="M50 40 L38 62 c-1 2 0 4 3 4 h18 c3 0 4-2 3-4 L50 40z" 
                                fill="url(#flaskGradient)" opacity="0.3"/>
                          
                          <!-- Flask top -->
                          <rect x="45" y="10" width="10" height="5" rx="2" fill="url(#flaskGradient)"/>
                          
                          <!-- Bubbles in flask -->
                          <circle cx="43" cy="50" r="3" fill="url(#flaskGradient)" opacity="0.6"/>
                          <circle cx="55" cy="45" r="2" fill="url(#flaskGradient)" opacity="0.6"/>
                          <circle cx="47" cy="55" r="1.5" fill="url(#flaskGradient)" opacity="0.6"/>
                          
                          <!-- Text: GK LAB -->
                          <text x="85" y="45" font-family="Arial, sans-serif" font-size="28" font-weight="bold" fill="#16A085">
                            GK LAB
                          </text>
                          <text x="85" y="58" font-family="Arial, sans-serif" font-size="12" fill="#555555">
                            DIAGNOSTIC CENTER
                          </text>
                          
                          <!-- DNA Helix Icon -->
                          <g transform="translate(180, 40) scale(0.9)">
                            <!-- DNA strand 1 -->
                            <path d="M0,0 C5,5 15,5 20,0 C25,-5 35,-5 40,0" stroke="#16A085" stroke-width="2.5" fill="none"/>
                            <!-- DNA strand 2 -->
                            <path d="M0,10 C5,5 15,5 20,10 C25,15 35,15 40,10" stroke="#16A085" stroke-width="2.5" fill="none"/>
                            <!-- Connecting lines -->
                            <line x1="5" y1="2.5" x2="5" y2="7.5" stroke="#16A085" stroke-width="1.5"/>
                            <line x1="15" y1="2.5" x2="15" y2="7.5" stroke="#16A085" stroke-width="1.5"/>
                            <line x1="25" y1="2.5" x2="25" y2="7.5" stroke="#16A085" stroke-width="1.5"/>
                            <line x1="35" y1="2.5" x2="35" y2="7.5" stroke="#16A085" stroke-width="1.5"/>
                          </g>
                        </svg>
                    </div>
                    <p class="footer-text">
                        Experience the convenience of high-quality diagnostic laboratory services in the comfort of your home. We bring cutting-edge diagnostics right to your doorstep.
                    </p>
                    <div class="contact-info" style="margin-bottom: 15px;">
                        <p style="margin: 5px 0;"><i class="fas fa-phone-alt"></i> 7620065204</p>
                        <p style="margin: 5px 0;"><i class="fas fa-envelope"></i> kedargovind144@gmail.com</p>
                        <p style="margin: 5px 0;"><i class="fas fa-map-marker-alt"></i> Kedar Clinical Laboratory, Ambajogai</p>
                    </div>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/xrohia/" class="social-link" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <!-- Company Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Company</h4>
                    <ul class="footer-links">
                        <li><a href="pages/about.html">About Us</a></li>
                        <li><a href="pages/locate.html">Locate Us</a></li>
                        <li><a href="pages/careers.html">Careers</a></li>
                        <li><a href="pages/blog.html">Blogs</a></li>
                        <li><a href="pages/contact.html">Contact Us</a></li>
                    </ul>
                </div>
                
                <!-- Partner Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Partners</h4>
                    <ul class="footer-links">
                        <li><a href="pages/for-doctors.html">For Doctors</a></li>
                        <li><a href="pages/for-corporates.html">For Corporates</a></li>
                    </ul>
                </div>
                
                <!-- Services Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Services</h4>
                    <ul class="footer-links">
                        <li><a href="tests.php">Lab Tests</a></li>
                        <li><a href="checkups.php">Full Body Checkup</a></li>
                        <li><a href="pages/health-packages.html">Health Packages</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="copyright">
                    © <?php echo date('Y'); ?> GK Health Labs Pvt. Ltd. All rights reserved
                </div>
                <div class="policy-links">
                    <a href="pages/terms.html" class="policy-link">Terms</a>
                    <a href="pages/privacy.html" class="policy-link">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Main JavaScript -->
    <script src="js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show notification if coupon was claimed or after login success
            <?php if ($coupon_claimed || $show_login_success): ?>
                showNotification();
            <?php endif; ?>
            
            // Notification functions
            function showNotification() {
                const notification = document.getElementById('notification');
                notification.classList.add('show');
                
                // Auto hide after 5 seconds
                setTimeout(() => {
                    hideNotification();
                }, 5000);
                
                // Close notification on click
                const closeBtn = notification.querySelector('.notification-close');
                closeBtn.addEventListener('click', hideNotification);
            }
            
            function hideNotification() {
                const notification = document.getElementById('notification');
                notification.classList.remove('show');
            }
        });
    </script>
</body>
</html> 