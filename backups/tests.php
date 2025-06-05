<?php
/**
 * Tests Page
 * 
 * This page displays all available diagnostic tests from the database.
 */

// Include database connection
include_once '../config/db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GK Lab - Body Tests</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
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
                <a href="support.html" class="btn-support">
                    <i class="fab fa-whatsapp"></i>
                    Support
                </a>
                <a href="cart.php" class="btn-cart">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <span class="cart-count">0</span>
                </a>
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
                    <button class="btn-search green">
                        <i class="fas fa-box"></i>
                        Checkups
                    </button>
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
                    <a href="appointment.html" class="promo-btn">
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
                        <img src="../assets/images/logo.svg" alt="GK Lab Logo">
                    </div>
                    <p class="footer-text">
                        Experience the convenience of high-quality diagnostic laboratory services in the comfort of your home. We bring cutting-edge diagnostics right to your doorstep.
                    </p>
                    <div class="app-download">
                        <a href="#" class="app-btn">
                            <i class="fab fa-apple"></i>
                            <div>
                                <span>Download on the</span>
                                <strong>App Store</strong>
                            </div>
                        </a>
                        <a href="#" class="app-btn">
                            <i class="fab fa-google-play"></i>
                            <div>
                                <span>GET IT ON</span>
                                <strong>Google Play</strong>
                            </div>
                        </a>
                    </div>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <!-- Company Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Company</h4>
                    <ul class="footer-links">
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="locate.html">Locate Us</a></li>
                        <li><a href="careers.html">Careers</a></li>
                        <li><a href="blog.html">Blogs</a></li>
                        <li><a href="contact.html">Contact Us</a></li>
                    </ul>
                </div>
                
                <!-- Partner Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Partners</h4>
                    <ul class="footer-links">
                        <li><a href="for-doctors.html">For Doctors</a></li>
                        <li><a href="for-corporates.html">For Corporates</a></li>
                    </ul>
                </div>
                
                <!-- Services Links -->
                <div class="footer-col">
                    <h4 class="footer-heading">Services</h4>
                    <ul class="footer-links">
                        <li><a href="tests.php">Lab Tests</a></li>
                        <li><a href="full-body-checkup.html">Full Body Checkup</a></li>
                        <li><a href="health-packages.html">Health Packages</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="copyright">
                    © 2023 GK Health Labs Pvt. Ltd. All rights reserved
                </div>
                <div class="policy-links">
                    <a href="terms.html" class="policy-link">Terms</a>
                    <a href="privacy.html" class="policy-link">Privacy Policy</a>
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
    <script src="../js/main.js"></script>
    <!-- PHP Cart JavaScript -->
    <script src="../js/php-cart.js"></script>
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
    });
    </script>
    <!-- Search JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const testCards = document.querySelectorAll('.test-card');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            testCards.forEach(card => {
                const testName = card.querySelector('.test-title').textContent.toLowerCase();
                
                if (testName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
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
                
                // Show popup
                popup.classList.add('active');
            });
        });
        
        // Close popup
        closeButton.addEventListener('click', function() {
            popup.classList.remove('active');
        });
        
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.btn-add-cart');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const testId = this.getAttribute('data-test-id');
                const testCard = this.closest('.test-card');
                const testName = testCard.querySelector('.test-title').textContent;
                
                // In a real implementation, you would add the item to cart via AJAX
                // For now, we'll just show a notification
                
                // Add animation to cart button
                const cartButton = document.querySelector('.btn-cart');
                cartButton.classList.add('cart-added');
                
                // Update cart count
                const cartCount = document.querySelector('.cart-count');
                cartCount.textContent = parseInt(cartCount.textContent) + 1;
                
                // Show notification
                alert(`Added "${testName}" to cart`);
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    cartButton.classList.remove('cart-added');
                }, 500);
            });
        });
    });
    </script>
</body>
</html> 