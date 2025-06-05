<?php
/**
 * Login Page
 * 
 * Handles user login and authentication.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // If there's a redirect parameter, go there
    if (isset($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
    } else {
        header('Location: index.html');
    }
    exit;
}

// Store redirect URL in session if provided
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
} else {
    // Try to detect referring page if no redirect parameter is provided
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        // Only store internal references and not the login page itself
        if (strpos($referer, $_SERVER['HTTP_HOST']) !== false && strpos($referer, 'login.php') === false) {
            $_SESSION['redirect_after_login'] = $referer;
        }
    }
}

// Include database connection and functions
include_once 'config/db.php';
include_once 'includes/functions.php';

$email = $password = '';
$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Validate email
    if (empty($email)) {
        $error = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    }
    
    // Validate password
    if (empty($password)) {
        $error = 'Please enter your password.';
    }
    
    // If no errors, check credentials
    if (empty($error)) {
        // Prepare SQL statement
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_first_name'] = $user['first_name'];
                $_SESSION['user_last_name'] = $user['last_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Transfer cart from session to user if needed
                transfer_cart($conn, $user['id']);
                
                // Set flash message
                set_flash_message('success', 'You have successfully logged in.');
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    // Redirect to intended page if set, otherwise to homepage
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header("Location: $redirect");
                    } else {
                        header('Location: index.html');
                    }
                }
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// Page title
$pageTitle = "Login - GK Lab";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GK Lab - Login</title>
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

        /* Mobile responsive styles for the login form */
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
            }

            .form-group label {
                font-size: 14px;
                margin-bottom: 4px;
            }

            .form-group input {
                padding: 12px 15px;
                font-size: 16px;
                height: auto;
                min-height: 48px;
            }

            .form-extras {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .form-extras .forgot-password {
                margin-top: 5px;
            }

            .btn-primary {
                padding: 12px 15px;
                font-size: 15px;
            }

            .alert {
                padding: 10px 12px;
                font-size: 14px;
                margin-bottom: 15px;
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

    <!-- Login Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-header">
                    <h2>Login to Your Account</h2>
                    <p>Enter your credentials to access your account</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form class="auth-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-extras">
                            <div class="remember-me">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">Remember me</label>
                            </div>
                            <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Register now</a></p>
                </div>
            </div>
        </div>
    </section>

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