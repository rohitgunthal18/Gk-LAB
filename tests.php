<?php
/**
 * Tests Page
 * 
 * This page displays all available diagnostic tests from the database.
 * 
 * @author Rohit Gunthal <rohitgunthal1819@gmail.com>
 * @copyright 2023 Rohit Gunthal
 * @license Proprietary - All Rights Reserved
 * 
 * UNAUTHORIZED COPYING, MODIFICATION OR DISTRIBUTION OF THIS FILE IS STRICTLY PROHIBITED.
 * Contact: 8408088454
 */

// Include database connection
include_once 'config/db.php';

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get category filter
$category_filter = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';

// Prepare the query
$query = "SELECT t.id, t.name, t.original_price, t.discounted_price, t.parameters_count, 
                 t.report_time, t.is_featured, c.name as category_name, c.slug as category_slug 
          FROM tests t
          LEFT JOIN categories c ON t.category_id = c.id
          WHERE t.is_active = 1";

// Add category filter if provided
if (!empty($category_filter)) {
    $query .= " AND c.slug = ?";
}

$query .= " ORDER BY t.is_featured DESC, t.id DESC";

// Execute the query
if (!empty($category_filter)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $category_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

// Fetch all tests
$tests = [];
while ($row = $result->fetch_assoc()) {
    $tests[] = $row;
}

// Get all active categories
$categories_query = "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Calculate discount percentage
function calculate_discount($original, $discounted) {
    if ($original > 0 && $discounted > 0 && $discounted < $original) {
        return round((($original - $discounted) / $original) * 100);
    }
    return 0;
}

// Meta data for SEO
$page_title = "Blood Test at Home in Latur | Doorstep Sample Collection - GK Lab";
$page_description = "Best diagnostic lab in Latur for blood tests at home with doorstep sample collection. Top-rated blood testing services by Rohit Gunthal with accurate, same-day results.";
$page_keywords = "blood test at home latur, doorstep blood collection, medical tests latur, home collection lab tests, blood test home service latur, rohit gunthal laboratory, fastest blood test latur, affordable blood tests";

// Category-specific SEO
if (!empty($category_filter)) {
    switch ($category_filter) {
        case 'blood':
            $page_title = "Blood Tests at Home in Latur | CBC, Diabetes Test - GK Lab";
            $page_description = "Complete range of blood tests in Latur with home sample collection. CBC, diabetes, thyroid tests with same-day results by Rohit Gunthal's GK Lab.";
            $page_keywords = "blood tests latur, CBC test at home, diabetes test latur, thyroid test home collection, rohit gunthal lab services";
            break;
        case 'thyroid':
            $page_title = "Thyroid Tests in Latur | Home Sample Collection - GK Lab";
            $page_description = "Comprehensive thyroid function tests in Latur with home sample collection. T3, T4, TSH testing by Rohit Gunthal's GK Lab with accurate results.";
            $page_keywords = "thyroid test latur, T3 T4 TSH test home collection, thyroid profile tests, rohit gunthal lab, thyroid function test latur";
            break;
        case 'diabetes':
            $page_title = "Diabetes Tests in Latur | Blood Sugar Test at Home - GK Lab";
            $page_description = "Diabetes screening and monitoring tests in Latur with home sample collection. FBS, PPBS, HbA1c tests by Rohit Gunthal's GK Lab with quick results.";
            $page_keywords = "diabetes test latur, blood sugar test at home, HbA1c test latur, glucose test home collection, rohit gunthal lab services";
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="<?php echo $page_keywords; ?>">
    <meta name="author" content="Rohit Gunthal">
    <meta name="robots" content="index, follow">
    <meta name="geo.region" content="IN-MH">
    <meta name="geo.placename" content="Latur">
    
    <!-- Canonical URL -->
    <meta rel="canonical" href="https://gklab.com/tests.php<?php echo !empty($category_filter) ? '?category=' . urlencode($category_filter) : ''; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://gklab.com/tests.php<?php echo !empty($category_filter) ? '?category=' . urlencode($category_filter) : ''; ?>">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="https://gklab.com/assets/images/og-image.jpg">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://gklab.com/tests.php<?php echo !empty($category_filter) ? '?category=' . urlencode($category_filter) : ''; ?>">
    <meta property="twitter:title" content="<?php echo $page_title; ?>">
    <meta property="twitter:description" content="<?php echo $page_description; ?>">
    <meta property="twitter:image" content="https://gklab.com/assets/images/og-image.jpg">
    
    <!-- Humans.txt attribution -->
    <link rel="author" href="humans.txt">
    
    <!-- Developer Comment: This website is developed by Rohit Gunthal (rohitgunthal1819@gmail.com, 8408088454) -->
    <!-- All rights reserved. Unauthorized use or distribution of this code is prohibited. -->
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Appointment Popup CSS -->
    <link rel="stylesheet" href="css/appointment-popup.css">
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
                <a href="tests.php" class="menu-item active">
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

    <!-- Tests Hero Section -->
    <section class="page-hero-section">
        <div class="container">
            <div class="page-hero-content">
                <h1 class="page-title">Body Tests</h1>
                <p class="page-subtitle">Comprehensive diagnostic tests for accurate health assessment</p>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <div class="search-container">
                <div class="search-input-wrapper">
                    <input type="text" class="search-input" id="search-input" placeholder="Search for tests">
                    <button class="search-icon-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="search-buttons">
                    <button class="btn-search orange active">
                        <i class="fas fa-microscope"></i>
                        Lab Tests
                    </button>
                    <a href="checkups.php" class="btn-search green">
                        <i class="fas fa-box"></i>
                        Checkups
                    </a>
                </div>
            </div>
            
            <!-- Promo Tag -->
            <div style="text-align: center; margin-top: 20px;">
                <div class="promo-tag">
                    Get <span class="highlight">15% OFF*</span> on orders above ₹500 | Use: <span class="highlight">GKLAB15</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Test Filter Section -->
    <section class="filter-section">
        <div class="container">
            <div class="filter-container">
                <div class="filter-heading">
                    <h3>Filter Tests</h3>
                    <button class="btn-reset-filter">Reset</button>
                </div>
                <div class="filter-options">
                    <div class="filter-group">
                        <label>Category</label>
                        <div class="filter-buttons">
                            <button class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>" data-category="all">All</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="filter-btn <?php echo $category_filter === $category['slug'] ? 'active' : ''; ?>" 
                                        data-category="<?php echo htmlspecialchars($category['slug']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Price Range</label>
                        <div class="price-slider-container">
                            <input type="range" min="0" max="5000" value="5000" class="price-slider" id="price-slider">
                            <div class="price-range-display">
                                <span>₹0</span>
                                <span id="price-value">₹5000</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tests Section -->
    <section class="popular-tests-section">
        <div class="container">
            <h2 class="section-title">Available Body Tests</h2>
            
            <div class="tests-grid">
                <?php if (empty($tests)): ?>
                    <div class="no-tests-message">
                        <p>No tests found. Please try a different filter or check back later.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tests as $test): ?>
                        <?php $discount = calculate_discount($test['original_price'], $test['discounted_price']); ?>
                        <div class="test-card" data-category="<?php echo htmlspecialchars($test['category_slug'] ?? 'uncategorized'); ?>">
                            <div class="test-header">
                                <div class="test-badge">Test</div>
                                <h3 class="test-title"><?php echo htmlspecialchars($test['name']); ?></h3>
                                <div class="test-price">
                                    <span class="original-price">₹ <?php echo number_format($test['original_price'], 0); ?></span>
                                    <span class="discounted-price">₹ <?php echo number_format($test['discounted_price'], 0); ?></span>
                                    <span class="discount-badge"><?php echo $discount; ?>% Off</span>
                                </div>
                            </div>
                            <div class="test-content">
                                <div class="detail-row">
                                    <div class="detail-info">
                                        <div class="detail-icon">
                                            <i class="fas fa-vial"></i>
                                        </div>
                                        <span>Parameters included</span>
                                    </div>
                                    <div class="detail-value"><?php echo $test['parameters_count']; ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-info">
                                        <div class="detail-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <span>Reports within</span>
                                    </div>
                                    <div class="detail-value"><?php echo htmlspecialchars($test['report_time']); ?></div>
                                </div>
                                <div class="test-actions">
                                    <button class="btn-details" data-test-id="<?php echo $test['id']; ?>">View Details</button>
                                    <button class="btn-add-cart" data-test-id="<?php echo $test['id']; ?>">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="view-all-container">
                <a href="#" class="btn-view-all">View All Tests</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose GK Lab for Tests</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon orange">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3 class="feature-title">NABL Accredited</h3>
                    <div class="feature-tag orange">Certified</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon green">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Fast Results</h3>
                    <div class="feature-tag">6-8 Hours</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon orange">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3 class="feature-title">Home Collection</h3>
                    <div class="feature-tag orange">Convenient</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon green">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Digital Reports</h3>
                    <div class="feature-tag">Instant</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Appointment Booking Banner Section -->
    <section class="promo-banner-section">
        <div class="container">
            <!-- Appointment Booking Banner -->
            <div class="promo-banner appointment-banner">
                <div class="promo-content">
                    <h2 class="promo-title">BOOK YOUR<br>LAB TEST TODAY</h2>
                    <div class="promo-text">Easy online booking, same-day sample collection</div>
                    <a href="javascript:void(0);" class="promo-btn" id="book-appointment-btn">
                        Book Now <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <div class="promo-image">
                    <!-- Lab Test/Medical SVG Icon -->
                    <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" id="lab-test-svg">
                        <!-- Test Tube -->
                        <path d="M80 30 L100 30 L100 130 C100 140, 90 150, 80 150 C70 150, 60 140, 60 130 L60 30 Z" fill="#FFF" stroke="#16A085" stroke-width="3"/>
                        <path d="M60 80 L100 80 L100 130 C100 140, 90 150, 80 150 C70 150, 60 140, 60 130 L60 80 Z" fill="#FF8A00" opacity="0.3"/>
                        
                        <!-- Test Tube Cap -->
                        <rect x="60" y="20" width="40" height="10" rx="2" fill="#16A085"/>
                        
                        <!-- Small Bubbles in Liquid -->
                        <circle cx="70" cy="100" r="3" fill="#FFF" opacity="0.6"/>
                        <circle cx="85" cy="110" r="2" fill="#FFF" opacity="0.6"/>
                        <circle cx="90" cy="95" r="4" fill="#FFF" opacity="0.6"/>
                        
                        <!-- Microscope Base Suggestion -->
                        <ellipse cx="130" cy="150" rx="30" ry="10" fill="#16A085" opacity="0.5"/>
                        
                        <!-- Microscope Stand -->
                        <rect x="120" y="80" width="20" height="70" rx="5" fill="#16A085"/>
                        
                        <!-- Microscope Viewing Arm -->
                        <path d="M130 80 C160 80, 160 60, 130 60" stroke="#16A085" stroke-width="10" fill="none"/>
                        
                        <!-- Microscope Eyepiece -->
                        <rect x="125" y="45" width="10" height="15" rx="2" fill="#16A085"/>
                        <circle cx="130" cy="45" r="7" fill="#16A085"/>
                        
                        <!-- Small DNA Helix -->
                        <path d="M30 40 C40 50, 50 50, 40 60 C30 70, 40 80, 50 70" stroke="#FF8A00" stroke-width="3" fill="none"/>
                        <path d="M50 40 C40 50, 30 50, 40 60 C50 70, 40 80, 30 70" stroke="#16A085" stroke-width="3" fill="none"/>
                        
                        <!-- Small Connection Lines for DNA -->
                        <line x1="36" y1="45" x2="44" y2="45" stroke="#666" stroke-width="2"/>
                        <line x1="36" y1="55" x2="44" y2="55" stroke="#666" stroke-width="2"/>
                        <line x1="36" y1="65" x2="44" y2="65" stroke="#666" stroke-width="2"/>
                        <line x1="36" y1="75" x2="44" y2="75" stroke="#666" stroke-width="2"/>
                    </svg>
                </div>
            </div>
        </div>
    </section>

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
                    </div>
                </div>
                <!-- Quick Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="pages/about.html">About Us</a></li>
                        <li><a href="tests.php">Lab Tests</a></li>
                        <li><a href="checkups.php">Health Checkups</a></li>
                        <li><a href="pages/contact.html">Contact Us</a></li>
                    </ul>
                </div>
                <!-- Services -->
                <div class="footer-col">
                    <h4 class="footer-heading">For Patients</h4>
                    <ul class="footer-links">
                        <li><a href="pages/appointment.html">Book Appointment</a></li>
                        <li><a href="pages/full-body-checkup.html">Full Body Checkup</a></li>
                        <li><a href="pages/support.html">Support</a></li>
                    </ul>
                </div>
            </div>
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="copyright">
                    © 2023 GK Health Labs Pvt. Ltd. All rights reserved
                </div>
                <div class="policy-links">
                    <a href="pages/terms.html" class="policy-link">Terms</a>
                    <a href="pages/privacy.html" class="policy-link">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Test Details Popup Modals -->
    <div class="popup-overlay" id="test-popup">
        <div class="popup-content">
            <div class="popup-header">
                <h3 class="popup-title">Test Name</h3>
                <div class="popup-subtitle">Test description</div>
                <div class="popup-price">
                    <span class="original-price">₹ 0</span>
                    <span class="discounted-price">₹ 0</span>
                    <span class="discount-badge">0% Off</span>
                </div>
                <div class="popup-close"><i class="fas fa-times"></i></div>
            </div>
            <div class="popup-body">
                <div class="popup-section">
                    <h4 class="popup-section-title">Overview</h4>
                    <div class="info-row">
                        <div class="info-label">Test Type</div>
                        <div class="info-value" id="test-type-value">Blood</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Report Time</div>
                        <div class="info-value" id="report-time-value">6 hours after sample collection</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Fasting Required</div>
                        <div class="info-value">Yes, 8-10 hours (water permitted)</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Sample Type</div>
                        <div class="info-value">Blood</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Age Group</div>
                        <div class="info-value">All ages</div>
                    </div>
                </div>
                
                <div class="popup-section">
                    <h4 class="popup-section-title">Description</h4>
                    <p>This comprehensive diagnostic test provides valuable insights into your health status, helping identify potential health issues before they become serious problems. Regular testing is an essential part of preventive healthcare.</p>
                </div>
                
                <div class="popup-section">
                    <h4 class="popup-section-title">Parameters Included</h4>
                    <div class="parameters-grid" id="parameters-grid">
                        <!-- Parameters will be populated dynamically -->
                    </div>
                </div>
                
                <div class="popup-section">
                    <h4 class="popup-section-title">When to Take This Test</h4>
                    <ul>
                        <li>During routine annual health checkups</li>
                        <li>When experiencing unexplained symptoms</li>
                        <li>When monitoring existing health conditions</li>
                        <li>As recommended by your healthcare provider</li>
                    </ul>
                </div>
            </div>
            <div class="popup-footer">
                <button class="popup-btn">Add to Cart</button>
            </div>
        </div>
    </div>

    <!-- Main JavaScript -->
    <script src="js/main.js"></script>
    <!-- PHP Cart JavaScript -->
    <script src="js/php-cart.js"></script>
    <!-- Appointment Popup Script -->
    <script src="js/appointment-popup.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Tests.php DOM loaded');
            
            // Ensure bottom appointment button also works
            const bottomAppointmentBtn = document.getElementById('book-appointment-btn-bottom');
            if (bottomAppointmentBtn) {
                console.log('Found bottom appointment button');
                bottomAppointmentBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Bottom appointment button clicked');
                    if (typeof window.openAppointmentPopup === 'function') {
                        console.log('Calling openAppointmentPopup function');
                        window.openAppointmentPopup();
                    } else {
                        console.error('openAppointmentPopup function not found!');
                    }
                    return false;
                });
            }
        });
    </script>
    
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
        });
    </script>
    
    <!-- Tests Filter JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Category filter functionality
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                
                // Update active button
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Redirect to filtered page if not "all"
                if (category === 'all') {
                    window.location.href = 'tests.php';
                } else {
                    window.location.href = 'tests.php?category=' + category;
                }
            });
        });
        
        // Price slider functionality
        const priceSlider = document.getElementById('price-slider');
        const priceValue = document.getElementById('price-value');
        const testCards = document.querySelectorAll('.test-card');
        
        priceSlider.addEventListener('input', function() {
            const maxPrice = this.value;
            priceValue.textContent = '₹' + maxPrice;
            
            // Filter tests by price
            testCards.forEach(card => {
                const priceElement = card.querySelector('.discounted-price');
                const price = parseInt(priceElement.textContent.replace('₹', '').replace(',', '').trim());
                
                if (price <= maxPrice) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Reset filter button
        const resetButton = document.querySelector('.btn-reset-filter');
        resetButton.addEventListener('click', function() {
            window.location.href = 'tests.php';
        });

        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.btn-add-cart');
        const cartCountDisplay = document.querySelector('.cart-count');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const testId = this.getAttribute('data-test-id');
                const testCard = this.closest('.test-card');
                const testName = testCard.querySelector('.test-title').textContent;
                const priceElement = testCard.querySelector('.discounted-price');
                const price = parseFloat(priceElement.textContent.replace('₹', '').replace(',', '').trim());
                
                // Send AJAX request to add item to cart
                fetch('cart-add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${testId}&type=test&name=${encodeURIComponent(testName)}&price=${price}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        cartCountDisplay.textContent = data.cart_count;
                        
                        // Animate cart button
                        const cartButton = document.querySelector('.btn-cart');
                        cartButton.classList.add('cart-added');
                        
                        // Remove animation class after animation completes
                        setTimeout(() => {
                            cartButton.classList.remove('cart-added');
                        }, 500);
                        
                        // Change button text temporarily
                        const originalText = this.textContent;
                        this.textContent = 'Added!';
                        setTimeout(() => {
                            this.textContent = originalText;
                        }, 1500);

                        // Show notification
                        showCartNotification(`${testName} added to cart!`);
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                });
            });
        });
        
        // Test details popup functionality
        const detailButtons = document.querySelectorAll('.btn-details');
        const popup = document.getElementById('test-popup');
        const closeButton = popup.querySelector('.popup-close');
        
        detailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const testId = this.getAttribute('data-test-id');
                
                // In a real implementation, you would fetch test details via AJAX
                // For now, we'll just show the popup with some placeholder data
                const testCard = this.closest('.test-card');
                const testName = testCard.querySelector('.test-title').textContent;
                const originalPrice = testCard.querySelector('.original-price').textContent;
                const discountedPrice = testCard.querySelector('.discounted-price').textContent;
                const discountBadge = testCard.querySelector('.discount-badge').textContent;
                const parametersCount = testCard.querySelector('.detail-value').textContent;
                const reportTime = testCard.querySelectorAll('.detail-value')[1].textContent;
                
                // Update popup content
                popup.querySelector('.popup-title').textContent = testName;
                popup.querySelector('.original-price').textContent = originalPrice;
                popup.querySelector('.discounted-price').textContent = discountedPrice;
                popup.querySelector('.discount-badge').textContent = discountBadge;
                popup.querySelector('#report-time-value').textContent = reportTime + ' after sample collection';
                
                // Set data attributes for add to cart button
                const popupAddToCartBtn = popup.querySelector('.popup-btn');
                popupAddToCartBtn.dataset.id = testId;
                popupAddToCartBtn.dataset.type = 'test';
                popupAddToCartBtn.dataset.name = testName;
                popupAddToCartBtn.dataset.price = parseFloat(discountedPrice.replace('₹', '').replace(',', '').trim());
                
                // Show popup
                popup.classList.add('active');
            });
        });
        
        // Close popup
        closeButton.addEventListener('click', function() {
            popup.classList.remove('active');
        });
        
        // Add to cart functionality for popup button
        const popupAddToCartBtn = popup.querySelector('.popup-btn');
        popupAddToCartBtn.addEventListener('click', function() {
            const testId = this.dataset.id;
            const testName = this.dataset.name;
            const price = this.dataset.price;
            
            // Send AJAX request to add item to cart
            fetch('cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${testId}&type=test&name=${encodeURIComponent(testName)}&price=${price}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    cartCountDisplay.textContent = data.cart_count;
                    
                    // Change button text temporarily
                    const originalText = this.textContent;
                    this.textContent = 'Added to Cart!';
                    
                    // Show notification
                    showCartNotification(`${testName} added to cart!`);
                    
                    // Close popup after a delay
                    setTimeout(() => {
                        this.textContent = originalText;
                        popup.classList.remove('active');
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
            });
        });
    });
    </script>
</body>
</html> 