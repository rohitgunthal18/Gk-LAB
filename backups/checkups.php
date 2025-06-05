<?php
/**
 * Health Checkups Page
 * 
 * This page displays health checkup packages available at GK Lab.
 * Data is dynamically pulled from the database.
 */

// Include database connection
include_once '../config/db.php';

// Function to get all active checkups
function get_checkups() {
    global $conn;
    
    $checkups = [];
    
    $sql = "SELECT c.*, cat.name as category_name, cat.slug as category_slug 
            FROM checkups c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            WHERE c.is_active = 1 
            ORDER BY c.is_featured DESC, c.id DESC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $checkups[] = $row;
        }
    }
    
    return $checkups;
}

// Function to get checkup parameters
function get_checkup_parameters($checkup_id) {
    global $conn;
    
    $parameters = [];
    
    $sql = "SELECT parameter_name FROM checkup_items WHERE checkup_id = ? ORDER BY id ASC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $checkup_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $parameters[] = $row['parameter_name'];
        }
    }
    
    return $parameters;
}

// Get all active checkups
$checkups = get_checkups();

// Get unique categories from checkups
$categories = [];
foreach ($checkups as $checkup) {
    if (!empty($checkup['category_slug']) && !in_array($checkup['category_slug'], array_column($categories, 'slug'))) {
        $categories[] = [
            'name' => $checkup['category_name'],
            'slug' => $checkup['category_slug']
        ];
    }
}

// Start the session to use cart functionality
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GK Lab - Health Checkups</title>
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
                <a href="tests.php" class="menu-item">
                    <i class="fas fa-microscope"></i>
                    Tests
                </a>
                <a href="checkups.php" class="menu-item active">
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
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </nav>
        </div>
    </header>

    <!-- Checkups Hero Section -->
    <section class="page-hero-section">
        <div class="container">
            <div class="page-hero-content">
                <h1 class="page-title">Health Checkup Packages</h1>
                <p class="page-subtitle">Comprehensive health checkup plans for preventive and diagnostic care</p>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <div class="search-container">
                <div class="search-input-wrapper">
                    <input type="text" class="search-input" id="search-input" placeholder="Search for checkup plans">
                    <button class="search-icon-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="search-buttons">
                    <button class="btn-search green active">
                        <i class="fas fa-box"></i>
                        Checkups
                    </button>
                    <a href="tests.php" class="btn-search orange">
                        <i class="fas fa-microscope"></i>
                        Lab Tests
                    </a>
                </div>
            </div>
            <!-- Promo Tag -->
            <div style="text-align: center; margin-top: 20px;">
                <div class="promo-tag">
                    Get <span class="highlight">20% OFF*</span> on checkup packages above ₹1000 | Use: <span class="highlight">GKCHECK20</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Checkup Filter Section -->
    <section class="filter-section">
        <div class="container">
            <div class="filter-container">
                <div class="filter-heading">
                    <h3>Filter Checkups</h3>
                    <button class="btn-reset-filter">Reset</button>
                </div>
                <div class="filter-options">
                    <div class="filter-group">
                        <label>Category</label>
                        <div class="filter-buttons">
                            <button class="filter-btn active" data-category="all">All</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="filter-btn" data-category="<?php echo $category['slug']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Price Range</label>
                        <div class="price-slider-container">
                            <input type="range" min="0" max="10000" value="10000" class="price-slider" id="price-slider">
                            <div class="price-range-display">
                                <span>₹0</span>
                                <span id="price-value">₹10000</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Checkups Section -->
    <section class="popular-tests-section">
        <div class="container">
            <h2 class="section-title">Popular Health Checkup Packages</h2>
            <div class="tests-grid" id="checkups-grid">
                <?php if (empty($checkups)): ?>
                    <div class="no-results">
                        <p>No checkup packages found. Please check back later.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($checkups as $checkup): ?>
                        <?php 
                        // Get parameters for this checkup
                        $parameters = get_checkup_parameters($checkup['id']);
                        ?>
                        <div class="checkup-card" data-category="<?php echo htmlspecialchars($checkup['category_slug']); ?>" data-price="<?php echo $checkup['discounted_price']; ?>">
                            <div class="checkup-header">
                                <div class="checkup-badge">Checkup</div>
                                <h3 class="checkup-title"><?php echo htmlspecialchars($checkup['name']); ?></h3>
                                <div class="checkup-price">
                                    <span class="original-price">₹ <?php echo number_format($checkup['original_price']); ?></span>
                                    <span class="discounted-price">₹ <?php echo number_format($checkup['discounted_price']); ?></span>
                                    <span class="discount-badge"><?php echo $checkup['discount_percentage']; ?>% Off</span>
                                </div>
                            </div>
                            <div class="checkup-content">
                                <div class="detail-row">
                                    <div class="detail-info">
                                        <div class="detail-icon">
                                            <i class="fas fa-vial"></i>
                                        </div>
                                        <span>Parameters included</span>
                                    </div>
                                    <div class="detail-value"><?php echo $checkup['parameters_count']; ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-info">
                                        <div class="detail-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <span>Reports within</span>
                                    </div>
                                    <div class="detail-value"><?php echo htmlspecialchars($checkup['report_time']); ?></div>
                                </div>
                                <div class="checkup-actions">
                                    <button class="btn-details" data-checkup-id="<?php echo $checkup['id']; ?>">View Details</button>
                                    <button class="btn-add-cart" data-id="<?php echo $checkup['id']; ?>" data-type="checkup" data-name="<?php echo htmlspecialchars($checkup['name']); ?>" data-price="<?php echo $checkup['discounted_price']; ?>">Add to Cart</button>
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
            <h2 class="section-title">Why Choose GK Lab for Checkups</h2>
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
                    <h2 class="promo-title">BOOK YOUR<br>CHECKUP TODAY</h2>
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
                        <li><a href="checkups.php">Health Checkups</a></li>
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

    <!-- Checkup Details Popup Modals -->
    <div class="popup-overlay" id="checkup-popup">
        <div class="popup-content">
            <div class="popup-header">
                <h3 class="popup-title">Checkup Name</h3>
                <div class="popup-subtitle">Checkup description</div>
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
                        <div class="info-label">Checkup Type</div>
                        <div class="info-value" id="checkup-type-value">Full Body</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Report Time</div>
                        <div class="info-value" id="checkup-report-time-value">24 hours after sample collection</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Fasting Required</div>
                        <div class="info-value" id="checkup-fasting-required-value">Yes, 8-10 hours (water permitted)</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Sample Type</div>
                        <div class="info-value" id="checkup-sample-type-value">Blood & Urine</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Age Group</div>
                        <div class="info-value" id="checkup-age-group-value">All ages</div>
                    </div>
                </div>
                <div class="popup-section">
                    <h4 class="popup-section-title">Description</h4>
                    <p id="checkup-description">This health checkup package is designed to provide a comprehensive assessment of your overall health, helping you detect potential health issues early and take preventive action.</p>
                </div>
                <div class="popup-section">
                    <h4 class="popup-section-title">Parameters Included</h4>
                    <div class="parameters-grid" id="checkup-parameters-grid">
                        <!-- Parameters will be populated dynamically -->
                    </div>
                </div>
                <div class="popup-section">
                    <h4 class="popup-section-title">When to Take This Checkup</h4>
                    <ul>
                        <li>Annual preventive health screening</li>
                        <li>When experiencing unexplained symptoms</li>
                        <li>For chronic disease management</li>
                        <li>As recommended by your healthcare provider</li>
                    </ul>
                </div>
            </div>
            <div class="popup-footer">
                <button class="popup-btn" id="popup-add-to-cart">Add to Cart</button>
            </div>
        </div>
    </div>

    <!-- Main JavaScript -->
    <script src="../js/main.js"></script>
    
    <!-- PHP Cart JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.btn-add-cart');
        const cartCountDisplay = document.querySelector('.cart-count');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.dataset.id;
                const itemType = this.dataset.type;
                const itemName = this.dataset.name;
                const itemPrice = this.dataset.price;
                
                // Send AJAX request to add item to cart
                fetch('../cart-add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${itemId}&type=${itemType}&name=${encodeURIComponent(itemName)}&price=${itemPrice}`
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
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                });
            });
        });
        
        // View details functionality
        const viewDetailsButtons = document.querySelectorAll('.btn-details');
        const checkupPopup = document.getElementById('checkup-popup');
        const popupClose = document.querySelector('.popup-close');
        const popupAddToCartBtn = document.getElementById('popup-add-to-cart');
        
        if (viewDetailsButtons.length && checkupPopup) {
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const checkupId = this.dataset.checkupId;
                    
                    // Fetch checkup details via AJAX
                    fetch(`get-checkup-details.php?id=${checkupId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const checkup = data.checkup;
                            
                            // Update popup with checkup data
                            document.querySelector('.popup-title').textContent = checkup.name;
                            document.querySelector('.popup-subtitle').textContent = checkup.short_description;
                            document.querySelector('.popup-price .original-price').textContent = `₹ ${checkup.original_price}`;
                            document.querySelector('.popup-price .discounted-price').textContent = `₹ ${checkup.discounted_price}`;
                            document.querySelector('.popup-price .discount-badge').textContent = `${checkup.discount_percentage}% Off`;
                            
                            document.getElementById('checkup-type-value').textContent = checkup.category_name;
                            document.getElementById('checkup-report-time-value').textContent = checkup.report_time;
                            document.getElementById('checkup-fasting-required-value').textContent = checkup.fasting_required;
                            document.getElementById('checkup-sample-type-value').textContent = checkup.sample_type;
                            document.getElementById('checkup-age-group-value').textContent = checkup.age_group;
                            document.getElementById('checkup-description').textContent = checkup.description;
                            
                            // Update parameters grid
                            const parametersGrid = document.getElementById('checkup-parameters-grid');
                            parametersGrid.innerHTML = '';
                            
                            checkup.parameters.forEach(param => {
                                const paramElement = document.createElement('div');
                                paramElement.className = 'parameter-item';
                                paramElement.textContent = param.parameter_name;
                                parametersGrid.appendChild(paramElement);
                            });
                            
                            // Set data attributes for add to cart button
                            popupAddToCartBtn.dataset.id = checkup.id;
                            popupAddToCartBtn.dataset.type = 'checkup';
                            popupAddToCartBtn.dataset.name = checkup.name;
                            popupAddToCartBtn.dataset.price = checkup.discounted_price;
                            
                            // Show popup
                            checkupPopup.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching checkup details:', error);
                    });
                });
            });
            
            // Close popup when clicking the close button
            popupClose.addEventListener('click', function() {
                checkupPopup.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Close popup when clicking outside
            checkupPopup.addEventListener('click', function(e) {
                if (e.target === checkupPopup) {
                    checkupPopup.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
            
            // Add to cart functionality for popup button
            popupAddToCartBtn.addEventListener('click', function() {
                const itemId = this.dataset.id;
                const itemType = this.dataset.type;
                const itemName = this.dataset.name;
                const itemPrice = this.dataset.price;
                
                // Send AJAX request to add item to cart
                fetch('../cart-add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${itemId}&type=${itemType}&name=${encodeURIComponent(itemName)}&price=${itemPrice}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        cartCountDisplay.textContent = data.cart_count;
                        
                        // Change button text temporarily
                        const originalText = this.textContent;
                        this.textContent = 'Added to Cart!';
                        
                        // Close popup after a delay
                        setTimeout(() => {
                            this.textContent = originalText;
                            checkupPopup.classList.remove('active');
                            document.body.style.overflow = '';
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                });
            });
        }
        
        // Filter functionality
        const filterButtons = document.querySelectorAll('.filter-btn');
        const priceSlider = document.getElementById('price-slider');
        const priceValue = document.getElementById('price-value');
        const checkupCards = document.querySelectorAll('.checkup-card');
        const resetFilterButton = document.querySelector('.btn-reset-filter');
        
        let activeCategory = 'all';
        let maxPrice = 10000;
        
        // Update filters
        function updateFilters() {
            checkupCards.forEach(card => {
                const cardCategory = card.dataset.category;
                const cardPrice = parseFloat(card.dataset.price);
                
                const categoryMatch = activeCategory === 'all' || cardCategory === activeCategory;
                const priceMatch = cardPrice <= maxPrice;
                
                if (categoryMatch && priceMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show message if no results
            const visibleCards = Array.from(checkupCards).filter(card => card.style.display !== 'none');
            const noResultsElement = document.querySelector('.no-results');
            
            if (visibleCards.length === 0) {
                if (!noResultsElement) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-results';
                    noResultsDiv.innerHTML = '<p>No checkups match your filters. Please try different criteria.</p>';
                    document.getElementById('checkups-grid').appendChild(noResultsDiv);
                }
            } else if (noResultsElement) {
                noResultsElement.remove();
            }
        }
        
        // Category filter
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                activeCategory = this.dataset.category;
                updateFilters();
            });
        });
        
        // Price filter
        if (priceSlider && priceValue) {
            priceSlider.addEventListener('input', function() {
                maxPrice = parseInt(this.value);
                priceValue.textContent = `₹${maxPrice}`;
                updateFilters();
            });
        }
        
        // Reset filters
        if (resetFilterButton) {
            resetFilterButton.addEventListener('click', function() {
                // Reset category
                filterButtons.forEach(btn => btn.classList.remove('active'));
                document.querySelector('[data-category="all"]').classList.add('active');
                activeCategory = 'all';
                
                // Reset price
                priceSlider.value = 10000;
                priceValue.textContent = '₹10000';
                maxPrice = 10000;
                
                // Apply filters
                updateFilters();
            });
        }
        
        // Search functionality
        const searchInput = document.getElementById('search-input');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                checkupCards.forEach(card => {
                    const title = card.querySelector('.checkup-title').textContent.toLowerCase();
                    const isVisible = title.includes(searchTerm);
                    
                    if (isVisible) {
                        card.dataset.searchMatch = 'true';
                    } else {
                        card.dataset.searchMatch = 'false';
                    }
                    
                    updateFilters();
                });
            });
        }
    });
    </script>
</body>
</html> 