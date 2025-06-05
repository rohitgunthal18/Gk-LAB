<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

// Include database connection
include_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Function to get column names from a table
function getTableColumns($conn, $tableName) {
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM " . $tableName);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $columns[] = $row["Field"];
        }
    }
    return $columns;
}

// Get the actual column names from user_addresses table
$addressColumns = getTableColumns($conn, 'user_addresses');

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user address exists
$hasAddress = false;
$address = [];

$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $hasAddress = true;
    $address = $result->fetch_assoc();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update user profile
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        // Validate inputs
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
            $message = "All fields are required";
            $messageType = "error";
        } else {
            // Update user data
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $user_id);
            
            if ($stmt->execute()) {
                $message = "Profile updated successfully";
                $messageType = "success";
                
                // Update session data
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $message = "Error updating profile. Please try again.";
                $messageType = "error";
            }
        }
    } elseif (isset($_POST['update_address'])) {
        // Update or add address
        $address1 = trim($_POST['address_line1']);
        $address2 = trim($_POST['address_line2']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $pincode = trim($_POST['pincode']);
        
        // Debug information
        if (empty($addressColumns)) {
            $message = "Error: Could not retrieve column names from user_addresses table.";
            $messageType = "error";
        }
        // Check for required address columns
        else if (!in_array('city', $addressColumns) || !in_array('state', $addressColumns) || !in_array('pincode', $addressColumns)) {
            $message = "Error: Required columns missing from user_addresses table. Available columns: " . implode(', ', $addressColumns);
            $messageType = "error";
        }
        // Validate inputs
        else if (empty($address1) || empty($city) || empty($state) || empty($pincode)) {
            $message = "Address Line 1, City, State and Pincode are required";
            $messageType = "error";
        } else {
            // Find appropriate address line columns
            $addressLine1Column = null;
            $addressLine2Column = null;
            
            // Check for common address line column names
            foreach (['street', 'address', 'line1', 'address1', 'addr1', 'addr_line1', 'address_line1'] as $col) {
                if (in_array($col, $addressColumns)) {
                    $addressLine1Column = $col;
                    break;
                }
            }
            
            foreach (['street2', 'address2', 'line2', 'address2', 'addr2', 'addr_line2', 'address_line2'] as $col) {
                if (in_array($col, $addressColumns)) {
                    $addressLine2Column = $col;
                    break;
                }
            }
            
            if (!$addressLine1Column) {
                $message = "Error: Could not find a column for address line 1. Available columns: " . implode(', ', $addressColumns);
                $messageType = "error";
            } else {
                try {
                    if ($hasAddress) {
                        // Build dynamic UPDATE SQL based on available columns
                        $sql = "UPDATE user_addresses SET " . $addressLine1Column . " = ?, ";
                        $sql .= $addressLine2Column ? $addressLine2Column . " = ?, " : "";
                        $sql .= "city = ?, state = ?, pincode = ? WHERE user_id = ?";
                        
                        $stmt = $conn->prepare($sql);
                        
                        // Bind parameters based on which columns exist
                        if ($addressLine2Column) {
                            $stmt->bind_param("sssssi", $address1, $address2, $city, $state, $pincode, $user_id);
                        } else {
                            $stmt->bind_param("ssssi", $address1, $city, $state, $pincode, $user_id);
                        }
                    } else {
                        // Build dynamic INSERT SQL based on available columns
                        $sql = "INSERT INTO user_addresses (user_id, " . $addressLine1Column;
                        $sql .= $addressLine2Column ? ", " . $addressLine2Column : "";
                        $sql .= ", city, state, pincode) VALUES (?, ?";
                        $sql .= $addressLine2Column ? ", ?" : "";
                        $sql .= ", ?, ?, ?)";
                        
                        $stmt = $conn->prepare($sql);
                        
                        // Bind parameters based on which columns exist
                        if ($addressLine2Column) {
                            $stmt->bind_param("isssss", $user_id, $address1, $address2, $city, $state, $pincode);
                        } else {
                            $stmt->bind_param("issss", $user_id, $address1, $city, $state, $pincode);
                        }
                    }
                    
                    $stmt->execute();
                    
                    $message = "Address updated successfully";
                    $messageType = "success";
                    
                    // Refresh address data
                    $stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? LIMIT 1");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $hasAddress = true;
                        $address = $result->fetch_assoc();
                    }
                } catch (mysqli_sql_exception $e) {
                    $message = "Error updating address: " . $e->getMessage();
                    $messageType = "error";
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = "All password fields are required";
            $messageType = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New passwords do not match";
            $messageType = "error";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            
            if (password_verify($currentPassword, $userData['password'])) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Password changed successfully";
                    $messageType = "success";
                } else {
                    $message = "Error changing password. Please try again.";
                    $messageType = "error";
                }
            } else {
                $message = "Current password is incorrect";
                $messageType = "error";
            }
        }
    }
}

// Get recent orders
$recentOrders = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recentOrders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-green: #16A085;
            --primary-green-dark: #138D75;
            --secondary-orange: #FF8A00;
            --text-dark: #2C3E50;
            --text-gray: #7F8C8D;
            --background-light: #F9F9F9;
            --border-color: #e0e0e0;
            --white: #FFFFFF;
            --error-color: #E74C3C;
            --success-color: #27AE60;
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
        
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--primary-green);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 600;
            margin-right: 1.5rem;
        }
        
        .profile-title h1 {
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .profile-subtitle {
            color: var(--text-gray);
            font-size: 0.9rem;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .profile-tab {
            padding: 1rem 1.5rem;
            font-weight: 500;
            color: var(--text-gray);
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }
        
        .profile-tab.active {
            color: var(--primary-green);
            border-bottom: 2px solid var(--primary-green);
        }
        
        .profile-tab-content {
            display: none;
        }
        
        .profile-tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-col {
            flex: 1;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-green);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-green-dark);
        }
        
        .btn-secondary {
            background-color: transparent;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--background-light);
        }
        
        .section-title {
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }
        
        .card-content {
            color: var(--text-gray);
        }
        
        .card-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #D4EDDA;
            color: var(--success-color);
            border: 1px solid #C3E6CB;
        }
        
        .alert-error {
            background-color: #F8D7DA;
            color: var(--error-color);
            border: 1px solid #F5C6CB;
        }
        
        .order-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .order-header {
            background-color: var(--background-light);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }
        
        .order-id {
            font-weight: 600;
            color: var(--text-dark);
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
        
        .order-body {
            padding: 1rem;
        }
        
        .order-total {
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .order-footer {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            text-align: right;
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .profile-tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
            
            .profile-tab {
                padding: 0.8rem 1rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .card-footer {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
            
            .order-header {
                flex-direction: column;
                gap: 0.5rem;
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
                        <li class="logged-in-only"><a href="pages/orders.php">Orders</a></li>
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
                <a href="pages/cart.php" class="btn-cart">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <span class="cart-count" id="cart-count">0</span>
                </a>
                <div class="menu-dropdown">
                    <a href="#" class="btn-support dropdown-toggle user-toggle" style="display: flex; align-items: center;" id="account-dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <span id="account-text">Account</span> <i class="fas fa-chevron-down" style="margin-left: 8px;"></i>
                    </a>
                    <ul class="dropdown-menu user-dropdown">
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                        <li class="logged-in-only"><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li class="logged-in-only"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
                <div class="profile-title">
                    <h1><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h1>
                    <div class="profile-subtitle">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Tabs -->
            <div class="profile-tabs">
                <div class="profile-tab active" data-tab="personal-info">Personal Information</div>
                <div class="profile-tab" data-tab="address">Address</div>
                <div class="profile-tab" data-tab="password">Change Password</div>
                <div class="profile-tab" data-tab="orders">Recent Orders</div>
            </div>
            
            <!-- Profile Tab Content -->
            <div class="profile-tab-content active" id="personal-info">
                <h2 class="section-title">Personal Information</h2>
                
                <form method="post" action="">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="first_name">First Name</label>
                                <input type="text" class="form-input" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="last_name">Last Name</label>
                                <input type="text" class="form-input" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" class="form-input" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
            
            <div class="profile-tab-content" id="address">
                <h2 class="section-title">Delivery Address</h2>
                
                <form method="post" action="">
                    <?php if (!empty($addressColumns)): ?>
                    <div class="form-group">
                        <label class="form-label" for="address_line1">Address Line 1</label>
                        <input type="text" class="form-input" id="address_line1" name="address_line1" value="<?php 
                            if ($hasAddress) {
                                // Find the address line 1 column and show its value
                                foreach (['street', 'address', 'line1', 'address1', 'addr1', 'addr_line1', 'address_line1'] as $col) {
                                    if (isset($address[$col])) {
                                        echo htmlspecialchars($address[$col]);
                                        break;
                                    }
                                }
                            }
                        ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="address_line2">Address Line 2 (Optional)</label>
                        <input type="text" class="form-input" id="address_line2" name="address_line2" value="<?php
                            if ($hasAddress) {
                                // Find the address line 2 column and show its value
                                foreach (['street2', 'address2', 'line2', 'addr2', 'addr_line2', 'address_line2'] as $col) {
                                    if (isset($address[$col])) {
                                        echo htmlspecialchars($address[$col]);
                                        break;
                                    }
                                }
                            }
                        ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="city">City</label>
                                <input type="text" class="form-input" id="city" name="city" value="<?php echo ($hasAddress && isset($address['city'])) ? htmlspecialchars($address['city']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label" for="state">State</label>
                                <input type="text" class="form-input" id="state" name="state" value="<?php echo ($hasAddress && isset($address['state'])) ? htmlspecialchars($address['state']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="pincode">Pincode</label>
                        <input type="text" class="form-input" id="pincode" name="pincode" value="<?php echo ($hasAddress && isset($address['pincode'])) ? htmlspecialchars($address['pincode']) : ''; ?>" required>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-error">
                        Error: Unable to retrieve address table columns. Please contact the administrator.
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <button type="submit" name="update_address" class="btn btn-primary">Update Address</button>
                    </div>
                </form>
            </div>
            
            <div class="profile-tab-content" id="password">
                <h2 class="section-title">Change Password</h2>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <div class="password-container">
                            <input type="password" class="form-input" id="current_password" name="current_password" required>
                            <span class="toggle-password" data-target="current_password"><i class="fas fa-eye"></i></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <div class="password-container">
                            <input type="password" class="form-input" id="new_password" name="new_password" required>
                            <span class="toggle-password" data-target="new_password"><i class="fas fa-eye"></i></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <div class="password-container">
                            <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
                            <span class="toggle-password" data-target="confirm_password"><i class="fas fa-eye"></i></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
            
            <div class="profile-tab-content" id="orders">
                <h2 class="section-title">Recent Orders</h2>
                
                <?php if (empty($recentOrders)): ?>
                    <div class="card">
                        <div class="card-content">
                            <p>You haven't placed any orders yet.</p>
                        </div>
                        <div class="card-footer">
                            <a href="tests.php" class="btn btn-primary">Browse Tests</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                
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
                            <div class="order-body">
                                <div class="order-date">
                                    Ordered on <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
                                </div>
                                <div class="order-total">
                                    Total: ₹<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                            <div class="order-footer">
                                <a href="pages/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="form-group" style="text-align: center; margin-top: 1.5rem;">
                        <a href="pages/orders.php" class="btn btn-primary">View All Orders</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- JavaScript for tabs and password toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check login status
            fetch('check_login_status.php')
                .then(response => response.json())
                .then(data => {
                    // Get all menu items that should only be shown when logged in
                    const loggedInItems = document.querySelectorAll('.logged-in-only');
                    
                    if (data.loggedIn) {
                        // User is logged in
                        loggedInItems.forEach(item => item.style.display = 'block');
                        
                        // Hide login/register options
                        document.querySelector('a[href="login.php"]').parentElement.style.display = 'none';
                        document.querySelector('a[href="register.php"]').parentElement.style.display = 'none';
                        
                        // Update account text to show user's name
                        const accountText = document.getElementById('account-text');
                        if (accountText && data.user_first_name) {
                            accountText.textContent = data.user_first_name;
                        }
                    } else {
                        // User is logged out
                        loggedInItems.forEach(item => item.style.display = 'none');
                        
                        // Make sure login/register are visible
                        document.querySelector('a[href="login.php"]').parentElement.style.display = 'block';
                        document.querySelector('a[href="register.php"]').parentElement.style.display = 'block';
                        
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
            fetch('pages/cart-count.php')
                .then(response => response.text())
                .then(data => {
                    try {
                        // Try to parse as JSON first
                        const jsonData = JSON.parse(data);
                        document.getElementById('cart-count').textContent = jsonData.count;
                    } catch (e) {
                        // If not valid JSON, use as plain text
                        document.getElementById('cart-count').textContent = data;
                    }
                })
                .catch(error => {
                    console.error('Error fetching cart count:', error);
                });
            
            // Tab functionality
            const tabs = document.querySelectorAll('.profile-tab');
            const tabContents = document.querySelectorAll('.profile-tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to current tab and content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Password toggle functionality
            const toggleButtons = document.querySelectorAll('.toggle-password');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navMenu = document.getElementById('nav-menu');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html> 