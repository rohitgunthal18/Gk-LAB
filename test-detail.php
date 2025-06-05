<?php
/**
 * Test Detail Page
 * 
 * This page displays detailed information about a specific diagnostic test.
 */

// Include database connection
include_once 'config/db.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to tests page if no valid ID
    header('Location: tests.php');
    exit;
}

$test_id = (int)$_GET['id'];

// Get test details
$stmt = $conn->prepare("SELECT t.*, c.name as category_name, c.slug as category_slug
                        FROM tests t
                        LEFT JOIN categories c ON t.category_id = c.id
                        WHERE t.id = ? AND t.is_active = 1");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if test exists
if ($result->num_rows === 0) {
    // Redirect to tests page if test not found
    header('Location: tests.php');
    exit;
}

// Fetch test data
$test = $result->fetch_assoc();

// Get test parameters
$parameters = [];
$stmt = $conn->prepare("SELECT id, parameter_name, unit, normal_range, description 
                        FROM test_parameters 
                        WHERE test_id = ? 
                        ORDER BY id");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $parameters[] = $row;
}

// Calculate discount percentage
function calculate_discount($original, $discounted) {
    if ($original > 0 && $discounted > 0 && $discounted < $original) {
        return round((($original - $discounted) / $original) * 100);
    }
    return 0;
}

$discount_percentage = calculate_discount($test['original_price'], $test['discounted_price']);

// Meta information
$page_title = $test['name'] . " - GK Lab Diagnostic Test";
$page_description = $test['short_description'] ?? "Detailed information about " . $test['name'] . " diagnostic test provided by GK Lab.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Cart Badge Styling -->
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
        
        /* Test detail specific styles */
        .test-detail-section {
            padding: 40px 0;
        }
        
        .test-detail-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .test-detail-image {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .test-detail-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        .test-detail-info {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .test-detail-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }
        
        .test-category {
            display: inline-block;
            font-size: 14px;
            padding: 5px 12px;
            border-radius: 30px;
            background-color: #f0f7f4;
            color: #16A085;
            margin-bottom: 15px;
        }
        
        .test-description {
            margin-bottom: 20px;
            line-height: 1.6;
            color: #555;
        }
        
        .test-parameters-title {
            font-size: 18px;
            font-weight: 600;
            margin: 20px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .test-parameter-item {
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
        }
        
        .parameter-name {
            font-weight: 500;
            color: #333;
        }
        
        .parameter-value {
            color: #666;
        }
        
        .test-detail-sidebar {
            position: sticky;
            top: 20px;
        }
        
        .test-price-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .price-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .price-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .discounted-price {
            font-size: 28px;
            font-weight: 700;
            color: #16A085;
        }
        
        .original-price {
            font-size: 16px;
            text-decoration: line-through;
            color: #999;
            margin-left: 10px;
        }
        
        .discount-badge {
            background-color: #ff6b6b;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .add-to-cart-section {
            margin-top: 20px;
        }
        
        .btn-add-to-cart {
            width: 100%;
            padding: 12px;
            background-color: #FF8A00;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-add-to-cart i {
            margin-right: 8px;
        }
        
        .btn-add-to-cart:hover {
            background-color: #e67e00;
        }
        
        .test-info-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .info-item {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background-color: #f0f7f4;
            color: #16A085;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .info-value {
            color: #666;
        }
        
        @media (max-width: 768px) {
            .test-detail-container {
                grid-template-columns: 1fr;
            }
            
            .test-detail-sidebar {
                position: static;
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
                <a href="tests.php" class="menu-item active">
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
                    <span class="cart-count" id="cart-count">0</span>
                </a>
            </nav>
        </div>
    </header>

    <!-- Test Detail Section -->
    <section class="test-detail-section">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.html">Home</a> <i class="fas fa-chevron-right"></i>
                <a href="tests.php">Tests</a> <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($test['name']); ?></span>
            </div>
            
            <div class="test-detail-container">
                <div class="test-detail-main">
                    <div class="test-detail-info">
                        <h1 class="test-detail-title"><?php echo htmlspecialchars($test['name']); ?></h1>
                        
                        <?php if (!empty($test['category_name'])): ?>
                        <div class="test-category">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($test['category_name']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="test-description">
                            <?php echo nl2br(htmlspecialchars($test['description'] ?? 'No description available.')); ?>
                        </div>
                        
                        <?php if (!empty($parameters)): ?>
                        <h3 class="test-parameters-title">Parameters Included (<?php echo count($parameters); ?>)</h3>
                        <div class="test-parameters-list">
                            <?php foreach ($parameters as $parameter): ?>
                            <div class="test-parameter-item">
                                <div class="parameter-name">
                                    <?php echo htmlspecialchars($parameter['parameter_name']); ?>
                                    <?php if (!empty($parameter['unit'])): ?>
                                    <span class="parameter-unit">(<?php echo htmlspecialchars($parameter['unit']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($parameter['normal_range'])): ?>
                                <div class="parameter-value">
                                    Normal Range: <?php echo htmlspecialchars($parameter['normal_range']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="test-detail-sidebar">
                    <div class="test-price-card">
                        <h3 class="price-title">Test Price</h3>
                        <div class="price-container">
                            <div class="discounted-price">₹<?php echo number_format($test['discounted_price'], 2); ?></div>
                            <?php if ($discount_percentage > 0): ?>
                            <div class="original-price">₹<?php echo number_format($test['original_price'], 2); ?></div>
                            <div class="discount-badge"><?php echo $discount_percentage; ?>% OFF</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="add-to-cart-section">
                            <button class="btn-add-to-cart" data-test-id="<?php echo $test['id']; ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                    
                    <div class="test-info-card">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-title">Report Time</div>
                                <div class="info-value"><?php echo htmlspecialchars($test['report_time'] ?? 'Same Day'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-title">Sample Collection</div>
                                <div class="info-value">Home Collection Available</div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-title">Sample Type</div>
                                <div class="info-value"><?php echo htmlspecialchars($test['sample_type'] ?? 'Blood Sample'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-title">Pre-Test Information</div>
                                <div class="info-value"><?php echo htmlspecialchars($test['pre_test_info'] ?? 'No special preparation required.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
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
                    </svg>
                    <p>Your trusted partner for accurate and reliable diagnostic services. We're committed to providing top-quality healthcare solutions.</p>
                </div>
                
                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="pages/about.html">About Us</a></li>
                        <li><a href="tests.php">Tests</a></li>
                        <li><a href="checkups.php">Checkups</a></li>
                        <li><a href="pages/contact.html">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Kedar Clinical Laboratory, Ambajogai</p>
                    <p><i class="fas fa-phone"></i> 7620065204</p>
                    <p><i class="fas fa-envelope"></i> kedargovind144@gmail.com</p>
                </div>
                
                <div class="footer-newsletter">
                    <h3>Stay Updated</h3>
                    <p>Subscribe to our newsletter for health tips and updates.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Your Email Address">
                        <button type="submit"><i class="fas fa-arrow-right"></i></button>
                    </form>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/xrohia/" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 GK Lab. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript for functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navMenu = document.getElementById('nav-menu');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    this.classList.toggle('active');
                });
            }
            
            // Dropdown menu toggle
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
            
            dropdownToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.classList.toggle('active');
                    this.nextElementSibling.classList.toggle('show');
                });
            });
            
            // Add to cart functionality
            const addToCartButton = document.querySelector('.btn-add-to-cart');
            if (addToCartButton) {
                addToCartButton.addEventListener('click', function() {
                    const testId = this.getAttribute('data-test-id');
                    addToCart(testId);
                });
            }
            
            // Function to add test to cart
            function addToCart(testId) {
                // Show loading state
                addToCartButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                addToCartButton.disabled = true;
                
                // Ajax request to add to cart
                fetch('pages/cart-count.php?add_to_cart=' + testId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update cart count
                            document.getElementById('cart-count').textContent = data.cart_count;
                            
                            // Show success state
                            addToCartButton.innerHTML = '<i class="fas fa-check"></i> Added to Cart';
                            addToCartButton.classList.add('cart-added');
                            
                            // Reset button after delay
                            setTimeout(function() {
                                addToCartButton.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
                                addToCartButton.disabled = false;
                                addToCartButton.classList.remove('cart-added');
                            }, 2000);
                        } else {
                            // Show error state
                            addToCartButton.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
                            
                            // Reset button after delay
                            setTimeout(function() {
                                addToCartButton.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
                                addToCartButton.disabled = false;
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Reset button
                        addToCartButton.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
                        addToCartButton.disabled = false;
                    });
            }
            
            // Get current cart count on page load
            fetch('pages/cart-count.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.cart_count;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    </script>
</body>
</html> 