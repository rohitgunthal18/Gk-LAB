/**
 * GK Lab - Test Filter Functionality
 * This script handles the filter functionality for test cards
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-select filter if category is in URL
    const params = new URLSearchParams(window.location.search);
    const category = params.get('category');
    if (category) {
        const btn = document.querySelector(`.filter-btn[data-category="${category}"]`);
        if (btn) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    }
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const testCards = document.querySelectorAll('.test-card');
    const priceSlider = document.getElementById('price-slider');
    const priceValue = document.getElementById('price-value');
    const resetButton = document.querySelector('.btn-reset-filter');
    
    // Function to update test cards based on filters
    function updateTestCards() {
        const activeCategory = document.querySelector('.filter-btn.active').dataset.category;
        const maxPrice = parseInt(priceSlider.value);
        
        testCards.forEach(card => {
            const cardCategory = card.dataset.category;
            const cardPrice = parseInt(card.querySelector('.discounted-price').textContent.replace(/[^0-9]/g, ''));
            
            let showCard = true;
            
            // Check category filter
            if (activeCategory !== 'all' && cardCategory !== activeCategory) {
                showCard = false;
            }
            
            // Check price filter
            if (cardPrice > maxPrice) {
                showCard = false;
            }
            
            card.style.display = showCard ? 'block' : 'none';
        });
        
        // If search is active, also apply search filter
        const searchInput = document.getElementById('search-input');
        if (searchInput && searchInput.value.trim()) {
            const searchEvent = new Event('search');
            searchInput.dispatchEvent(searchEvent);
        }
    }
    
    // Add event listeners to filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update test cards
            updateTestCards();
        });
    });
    
    // Add event listener to price slider
    if (priceSlider) {
        priceSlider.addEventListener('input', function() {
            priceValue.textContent = `₹${this.value}`;
            updateTestCards();
        });
    }
    
    // Add event listener to reset button
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            // Reset category filter
            filterButtons.forEach(btn => btn.classList.remove('active'));
            document.querySelector('[data-category="all"]').classList.add('active');
            
            // Reset price filter
            priceSlider.value = 5000;
            priceValue.textContent = '₹5000';
            
            // Update test cards
            updateTestCards();
        });
    }
    
    // Test popup functionality
    const viewDetailsButtons = document.querySelectorAll('.btn-details');
    const testPopup = document.getElementById('test-popup');
    const closePopupButton = testPopup ? testPopup.querySelector('.popup-close') : null;
    
    // Test parameter mappings based on test ID
    const testParameters = {
        1: ['Complete Blood Count', 'Hemoglobin', 'Red Blood Cells', 'White Blood Cells', 'Platelets', 'Hematocrit', 'MCV', 'MCH', 'MCHC'],
        2: ['Vitamin D (25-OH)', 'Vitamin B12'],
        3: ['T3', 'T4', 'TSH', 'FT3', 'FT4'],
        4: ['HbA1c', 'Fasting Blood Glucose', 'Post Prandial Blood Glucose'],
        5: ['SGOT', 'SGPT', 'ALP', 'GGT', 'Bilirubin - Total', 'Bilirubin - Direct', 'Bilirubin - Indirect', 'Total Protein', 'Albumin', 'Globulin', 'A/G Ratio', 'PT/INR'],
        6: ['Urea', 'Creatinine', 'Uric Acid', 'Electrolytes', 'BUN', 'BUN/Creatinine Ratio', 'Calcium', 'Phosphorus'],
        7: ['Total Cholesterol', 'Triglycerides', 'HDL Cholesterol', 'LDL Cholesterol', 'VLDL Cholesterol', 'Total Cholesterol/HDL Ratio', 'LDL/HDL Ratio', 'Non-HDL Cholesterol'],
        8: ['Serum Iron', 'TIBC', 'Transferrin Saturation', 'Ferritin'],
        9: ['HbA1c']
    };
    
    // Category to test type mapping
    const categoryToType = {
        'blood': 'Blood',
        'heart': 'Cardiovascular',
        'diabetes': 'Metabolic',
        'liver': 'Hepatic',
        'kidney': 'Renal',
        'thyroid': 'Endocrine',
        'vitamin': 'Nutritional'
    };
    
    if (viewDetailsButtons.length && testPopup) {
        viewDetailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons first
                document.querySelectorAll('.btn-details[data-test-id]').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Mark this button as active
                this.classList.add('active');
            
                const testId = this.dataset.testId;
                const testCard = this.closest('.test-card');
                const testCategory = testCard.dataset.category;
                
                // Get information from the test card
                const testTitle = testCard.querySelector('.test-title').textContent;
                const originalPrice = testCard.querySelector('.original-price').textContent;
                const discountedPrice = testCard.querySelector('.discounted-price').textContent;
                const discountBadge = testCard.querySelector('.discount-badge').textContent;
                const parameters = testCard.querySelector('.detail-row:first-child .detail-value').textContent;
                const reportTime = testCard.querySelector('.detail-row:last-child .detail-value').textContent;
                
                // Update popup with card information
                testPopup.querySelector('.popup-title').textContent = testTitle;
                testPopup.querySelector('.popup-subtitle').textContent = `Comprehensive health assessment`;
                testPopup.querySelector('.original-price').textContent = originalPrice;
                testPopup.querySelector('.discounted-price').textContent = discountedPrice;
                testPopup.querySelector('.discount-badge').textContent = discountBadge;
                testPopup.querySelector('.popup-btn').textContent = `Add to Cart - ${discountedPrice}`;
                
                // Update test type based on category
                const testTypeElement = document.getElementById('test-type-value');
                if (testTypeElement) {
                    testTypeElement.textContent = categoryToType[testCategory] || 'General';
                }
                
                // Update report time
                const reportTimeElement = document.getElementById('report-time-value');
                if (reportTimeElement) {
                    reportTimeElement.textContent = reportTime + ' after sample collection';
                }
                
                // Populate parameters grid
                const parametersGrid = document.getElementById('parameters-grid');
                if (parametersGrid) {
                    parametersGrid.innerHTML = ''; // Clear existing parameters
                    
                    // Get parameters for this test
                    const testParamList = testParameters[testId] || [];
                    
                    // Create parameter items
                    testParamList.forEach(param => {
                        const paramItem = document.createElement('div');
                        paramItem.className = 'parameter-item';
                        paramItem.textContent = param;
                        parametersGrid.appendChild(paramItem);
                    });
                    
                    // If no parameters are available
                    if (testParamList.length === 0) {
                        const paramItem = document.createElement('div');
                        paramItem.className = 'parameter-item';
                        paramItem.textContent = 'Parameters information not available';
                        parametersGrid.appendChild(paramItem);
                    }
                }
                
                // Show popup
                testPopup.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
        });
        
        // Close popup when clicking close button
        if (closePopupButton) {
            closePopupButton.addEventListener('click', function() {
                testPopup.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            });
        }
        
        // Close popup when clicking outside popup content
        testPopup.addEventListener('click', function(e) {
            if (e.target === this) {
                testPopup.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            }
        });
        
        // Close popup when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && testPopup.classList.contains('active')) {
                testPopup.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            }
        });
    }
    
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.btn-add-cart, .popup-btn');
    
    if (addToCartButtons.length) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                let testId = '';
                let testName = '';
                let price = 0;
                let type = 'Test';
                
                if (this.classList.contains('popup-btn')) {
                    // If clicked from popup
                    testName = testPopup.querySelector('.popup-title').textContent;
                    price = parseInt(testPopup.querySelector('.discounted-price').textContent.replace(/[^0-9]/g, ''));
                    
                    const testTypeElement = document.getElementById('test-type-value');
                    type = testTypeElement ? testTypeElement.textContent : 'Test';
                    
                    // Get test ID from the button that opened the popup
                    const activeDetailsBtn = document.querySelector('.btn-details[data-test-id].active');
                    if (activeDetailsBtn) {
                        testId = activeDetailsBtn.getAttribute('data-test-id');
                    } else {
                        testId = "t" + Date.now(); // Fallback unique ID
                    }
                    
                    // Close popup
                    testPopup.classList.remove('active');
                    document.body.style.overflow = '';
                } else {
                    // If clicked from card
                    const testCard = this.closest('.test-card');
                    const detailsBtn = testCard.querySelector('.btn-details');
                    testId = detailsBtn.getAttribute('data-test-id');
                    testName = testCard.querySelector('.test-title').textContent;
                    price = parseInt(testCard.querySelector('.discounted-price').textContent.replace(/[^0-9]/g, ''));
                }
                
                // Use the addToCartPHP function from php-cart.js
                if (typeof addToCartPHP === 'function') {
                    addToCartPHP(testId, testName, price, type);
                } else {
                    // Fallback if function not available
                    window.location.href = `cart.php?action=add&id=${encodeURIComponent(testId)}&name=${encodeURIComponent(testName)}&price=${encodeURIComponent(price)}&type=${encodeURIComponent(type)}`;
                }
                
                return false;
            });
        });
    }
    
    // Make updateTestCards function available globally
    window.updateTestCards = updateTestCards;
}); 