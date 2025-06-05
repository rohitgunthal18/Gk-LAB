<?php
/**
 * Registration Page
 * 
 * Handles user registration and account creation.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Include database connection and functions
include_once 'config/db.php';
include_once 'includes/functions.php';

// Initialize variables
$first_name = $last_name = $email = $phone = $password = $confirm_password = '';
$errors = [];

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate first name
    if (empty($first_name)) {
        $errors['first_name'] = 'First name is required';
    }
    
    // Validate last name
    if (empty($last_name)) {
        $errors['last_name'] = 'Last name is required';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors['email'] = 'Email already in use. Please use a different email or login';
        }
    }
    
    // Validate phone
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors['phone'] = 'Please enter a valid 10-digit phone number';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long';
    }
    
    // Validate confirm password
    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Please confirm your password';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no errors, create user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, 'customer')");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $phone);
        
        // Execute statement
        if ($stmt->execute()) {
            // Get user ID
            $user_id = $conn->insert_id;
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_first_name'] = $first_name;
            $_SESSION['user_last_name'] = $last_name;
            $_SESSION['user_role'] = 'customer';
            
            // Transfer cart from session to user if needed
            transfer_cart($conn, $user_id);
            
            // Set flash message
            set_flash_message('success', 'Registration successful! Your account has been created.');
            
            // Redirect to home page
            header('Location: index.php');
            exit;
        } else {
            $errors['general'] = 'Registration failed. Please try again later.';
        }
    }
}

// Page title
$pageTitle = "Register - GK Lab";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GK Lab - Registration</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Auth styles -->
    <link rel="stylesheet" href="css/auth-styles.css">
    <!-- Cart Badge CSS -->
    <style>
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

        /* Mobile responsive styles for the registration form */
        @media (max-width: 768px) {
            .auth-section {
                padding: 30px 0;
            }

            .auth-container {
                max-width: 100%;
                padding: 25px 20px;
                margin: 0 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }

            .auth-header h2 {
                font-size: 24px;
                margin-bottom: 8px;
            }

            .auth-header p {
                font-size: 15px;
                margin-bottom: 20px;
            }

            .form-group {
                margin-bottom: 20px;
                width: 100%;
            }

            .form-row .form-group {
                width: 100%;
                margin-right: 0;
            }

            .form-group label {
                font-size: 14px;
                margin-bottom: 4px;
                display: block;
            }

            .form-group input {
                padding: 12px 15px;
                font-size: 16px;
                width: 100%;
                box-sizing: border-box;
                height: auto;
                min-height: 48px;
            }

            .terms-agreement {
                font-size: 13px;
                display: flex;
                align-items: flex-start;
            }

            .terms-agreement input[type="checkbox"] {
                margin-top: 3px;
            }

            .terms-agreement label {
                margin-left: 5px;
                line-height: 1.4;
            }

            .btn-primary {
                padding: 14px 16px;
                font-size: 16px;
                min-height: 50px;
                -webkit-tap-highlight-color: transparent;
                -webkit-appearance: none;
                border-radius: 8px;
                transition: background-color 0.2s ease, transform 0.1s ease;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .btn-primary:active {
                transform: scale(0.98);
            }

            .alert {
                padding: 10px 12px;
                font-size: 14px;
                margin-bottom: 15px;
            }

            .error-message {
                font-size: 12px;
                margin-top: 3px;
            }
            
            /* Improve mobile touch experience */
            input[type="email"], 
            input[type="password"], 
            input[type="text"], 
            input[type="tel"] {
                -webkit-appearance: none;
                border-radius: 8px;
                border: 1px solid #ddd;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
                -webkit-tap-highlight-color: transparent;
                padding: 12px 15px;
                font-size: 16px;
                height: auto;
                min-height: 48px;
            }
            
            input[type="email"]:focus, 
            input[type="password"]:focus, 
            input[type="text"]:focus, 
            input[type="tel"]:focus {
                border-color: #16A085;
                box-shadow: 0 0 0 2px rgba(22, 160, 133, 0.1);
                outline: none;
            }
            
            input[type="checkbox"] {
                width: 18px;
                height: 18px;
                -webkit-tap-highlight-color: transparent;
            }
        }

        /* Even smaller screens */
        @media (max-width: 480px) {
            .auth-container {
                padding: 15px 12px;
                margin: 0 5px;
            }

            .auth-header h2 {
                font-size: 20px;
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
                    <a href="index.php">
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
                        <li class="logged-in-only" style="display: none;"><a href="pages/orders.php">Orders</a></li>
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
                        <li class="logged-in-only" style="display: none;"><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li class="logged-in-only" style="display: none;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Registration Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-header">
                    <h2>Create Your Account</h2>
                    <p>Sign up to access testing services and track your health</p>
                </div>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-error">
                        <?php echo $errors['general']; ?>
                    </div>
                <?php endif; ?>
                
                <form class="auth-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo $first_name; ?>" placeholder="Enter your first name" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <span class="error-message"><?php echo $errors['first_name']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo $last_name; ?>" placeholder="Enter your last name" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <span class="error-message"><?php echo $errors['last_name']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your email address" required>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-message"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo $phone; ?>" placeholder="Enter your 10-digit phone number" required>
                        <?php if (isset($errors['phone'])): ?>
                            <span class="error-message"><?php echo $errors['phone']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password (min. 6 characters)" required>
                        <?php if (isset($errors['password'])): ?>
                            <span class="error-message"><?php echo $errors['password']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <div class="terms-agreement">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the <a href="terms.php">Terms & Conditions</a> and <a href="privacy.php">Privacy Policy</a></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-primary btn-block">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </div>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login now</a></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    </footer>

    <!-- Main JavaScript -->
    <script src="js/main.js"></script>
    
    <!-- User login status handling -->
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
        });
    </script>
</body>
</html> 