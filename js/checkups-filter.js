/**
 * GK Lab - Checkup Filter Functionality
 * This script handles the filter functionality for checkup cards
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
            if (typeof updateCheckupCards === 'function') updateCheckupCards();
        }
    }
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const checkupCards = document.querySelectorAll('.checkup-card');
    const priceSlider = document.getElementById('price-slider');
    const priceValue = document.getElementById('price-value');
    const resetButton = document.querySelector('.btn-reset-filter');
    
    // Function to update checkup cards based on filters
    function updateCheckupCards() {
        const activeCategory = document.querySelector('.filter-btn.active').dataset.category;
        const maxPrice = parseInt(priceSlider.value);
        
        checkupCards.forEach(card => {
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
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            updateCheckupCards();
        });
    });
    
    // Add event listener to price slider
    if (priceSlider) {
        priceSlider.addEventListener('input', function() {
            priceValue.textContent = `₹${this.value}`;
            updateCheckupCards();
        });
    }
    
    // Add event listener to reset button
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            document.querySelector('[data-category="all"]').classList.add('active');
            priceSlider.value = 10000;
            priceValue.textContent = '₹10000';
            updateCheckupCards();
        });
    }
    
    // View Details Popup Functionality
    const viewDetailsButtons = document.querySelectorAll('.btn-details');
    const checkupPopup = document.getElementById('checkup-popup');
    const closePopupButton = checkupPopup ? checkupPopup.querySelector('.popup-close') : null;
    
    // Checkup parameter mappings based on checkup ID
    const checkupParameters = {
        1: ['CBC', 'Liver Function', 'Kidney Function', 'Lipid Profile', 'Thyroid Profile', 'Blood Sugar', 'Urine Routine', 'Vitamin D', 'Vitamin B12', 'Iron Studies', 'Calcium', 'Phosphorus', 'Electrolytes', 'ESR', 'CRP'],
        2: ['CBC', 'Liver Function', 'Kidney Function', 'Lipid Profile', 'Thyroid Profile', 'Blood Sugar', 'Urine Routine', 'Vitamin D', 'Vitamin B12', 'Iron Studies', 'Calcium', 'Phosphorus', 'Electrolytes', 'ESR', 'CRP', 'Cardiac Markers', 'Pancreatic Enzymes', 'Hormonal Panel'],
        3: ['Fasting Blood Sugar', 'HbA1c', 'Urine Microalbumin', 'Lipid Profile', 'Kidney Function', 'Electrolytes', 'CBC', 'CRP', 'Insulin', 'C-Peptide', 'Thyroid Profile', 'Liver Function'],
        4: ['ECG', 'Lipid Profile', 'CBC', 'CRP', 'Blood Sugar', 'Kidney Function', 'Electrolytes', 'Cardiac Enzymes', 'Homocysteine', 'ApoA1', 'ApoB', 'Lp(a)', 'hs-CRP', 'NT-proBNP', 'Troponin I'],
        5: ['SGOT', 'SGPT', 'ALP', 'GGT', 'Bilirubin - Total', 'Bilirubin - Direct', 'Bilirubin - Indirect', 'Total Protein', 'Albumin', 'Globulin'],
        6: ['Urea', 'Creatinine', 'Uric Acid', 'Electrolytes', 'BUN', 'Calcium', 'Phosphorus', 'Urine Routine', 'Microalbumin'],
        7: ['CBC', 'Thyroid Profile', 'Vitamin D', 'Vitamin B12', 'Hormonal Panel', 'Lipid Profile', 'Blood Sugar', 'Urine Routine', 'Calcium', 'Iron Studies', 'Pap Smear', 'Breast Ultrasound'],
        8: ['CBC', 'Liver Function', 'Kidney Function', 'Lipid Profile', 'Thyroid Profile', 'Blood Sugar', 'Urine Routine', 'Vitamin D', 'Vitamin B12', 'Calcium', 'PSA', 'Electrolytes', 'CRP', 'ESR', 'Iron Studies']
    };
    
    // Category to checkup type mapping
    const categoryToType = {
        'fullbody': 'Full Body',
        'diabetes': 'Diabetes',
        'heart': 'Heart',
        'liver': 'Liver',
        'kidney': 'Kidney',
        'women': 'Women',
        'senior': 'Senior Citizen'
    };
    
    if (viewDetailsButtons.length && checkupPopup) {
        viewDetailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons first
                document.querySelectorAll('.btn-details[data-checkup-id]').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Mark this button as active
                this.classList.add('active');
                
                const checkupId = this.dataset.checkupId;
                const checkupCard = this.closest('.checkup-card');
                const checkupCategory = checkupCard.dataset.category;
                
                // Get information from the checkup card
                const checkupTitle = checkupCard.querySelector('.checkup-title').textContent;
                const originalPrice = checkupCard.querySelector('.original-price').textContent;
                const discountedPrice = checkupCard.querySelector('.discounted-price').textContent;
                const discountBadge = checkupCard.querySelector('.discount-badge').textContent;
                const parameters = checkupCard.querySelector('.detail-row:first-child .detail-value').textContent;
                const reportTime = checkupCard.querySelector('.detail-row:last-child .detail-value').textContent;
                
                // Update popup with card information
                checkupPopup.querySelector('.popup-title').textContent = checkupTitle;
                checkupPopup.querySelector('.popup-subtitle').textContent = `Comprehensive health checkup`;
                checkupPopup.querySelector('.original-price').textContent = originalPrice;
                checkupPopup.querySelector('.discounted-price').textContent = discountedPrice;
                checkupPopup.querySelector('.discount-badge').textContent = discountBadge;
                checkupPopup.querySelector('.popup-btn').textContent = `Add to Cart - ${discountedPrice}`;
                
                // Update checkup type based on category
                const checkupTypeElement = document.getElementById('checkup-type-value');
                if (checkupTypeElement) {
                    checkupTypeElement.textContent = categoryToType[checkupCategory] || 'General';
                }
                
                // Update report time
                const reportTimeElement = document.getElementById('checkup-report-time-value');
                if (reportTimeElement) {
                    reportTimeElement.textContent = reportTime + ' after sample collection';
                }
                
                // Populate parameters grid
                const parametersGrid = document.getElementById('checkup-parameters-grid');
                if (parametersGrid) {
                    parametersGrid.innerHTML = '';
                    const checkupParamList = checkupParameters[checkupId] || [];
                    
                    checkupParamList.forEach(param => {
                        const paramItem = document.createElement('div');
                        paramItem.className = 'parameter-item';
                        paramItem.textContent = param;
                        parametersGrid.appendChild(paramItem);
                    });
                    
                    if (checkupParamList.length === 0) {
                        const paramItem = document.createElement('div');
                        paramItem.className = 'parameter-item';
                        paramItem.textContent = 'Parameters information not available';
                        parametersGrid.appendChild(paramItem);
                    }
                }
                
                // Show popup
                checkupPopup.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Close popup when clicking close button
        if (closePopupButton) {
            closePopupButton.addEventListener('click', function() {
                checkupPopup.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        // Close popup when clicking outside popup content
        checkupPopup.addEventListener('click', function(e) {
            if (e.target === this) {
                checkupPopup.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Close popup when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && checkupPopup.classList.contains('active')) {
                checkupPopup.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
    
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.btn-add-cart, .popup-btn');
    
    if (addToCartButtons.length) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                let checkupId = '';
                let checkupName = '';
                let price = 0;
                let type = 'Checkup';
                
                if (this.classList.contains('popup-btn')) {
                    // If clicked from popup
                    checkupName = checkupPopup.querySelector('.popup-title').textContent;
                    price = parseInt(checkupPopup.querySelector('.discounted-price').textContent.replace(/[^0-9]/g, ''));
                    
                    // Get checkup ID from the button that opened the popup
                    const activeDetailsBtn = document.querySelector('.btn-details[data-checkup-id].active');
                    if (activeDetailsBtn) {
                        checkupId = "c" + activeDetailsBtn.getAttribute('data-checkup-id');
                    } else {
                        checkupId = "c" + Date.now(); // Fallback unique ID
                    }
                    
                    // Close popup
                    checkupPopup.classList.remove('active');
                    document.body.style.overflow = '';
                } else {
                    // If clicked from card
                    const checkupCard = this.closest('.checkup-card');
                    const detailsBtn = checkupCard.querySelector('.btn-details');
                    checkupId = "c" + detailsBtn.getAttribute('data-checkup-id');
                    checkupName = checkupCard.querySelector('.checkup-title').textContent;
                    price = parseInt(checkupCard.querySelector('.discounted-price').textContent.replace(/[^0-9]/g, ''));
                }
                
                // Use the addToCartPHP function from php-cart.js
                if (typeof addToCartPHP === 'function') {
                    addToCartPHP(checkupId, checkupName, price, type);
                } else {
                    // Fallback if function not available
                    window.location.href = `cart.php?action=add&id=${encodeURIComponent(checkupId)}&name=${encodeURIComponent(checkupName)}&price=${encodeURIComponent(price)}&type=${encodeURIComponent(type)}`;
                }
                
                return false;
            });
        });
    }
    
    // Make updateCheckupCards function available globally
    window.updateCheckupCards = updateCheckupCards;
}); 