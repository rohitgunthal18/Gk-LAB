/**
 * GK Lab - Slider JavaScript
 * This file contains all the functionality for card sliders
 */

document.addEventListener('DOMContentLoaded', function() {
    // Constants for slider settings
    const CARD_WIDTH = 380; // Card width + gap in pixels for desktop sliders
    
    // Initialize all sliders
    initializeTestsSlider();
    initializeCheckupsSlider();
    initializeFeaturesSlider(); // Initialize the features slider
    initializePopups();
    initializePlaceholders();
    
    /**
     * Initialize the Features slider for mobile view
     */
    function initializeFeaturesSlider() {
        const featuresContainer = document.querySelector('.features-container');
        const featureDots = document.querySelectorAll('.feature-dot');
        
        if (!featuresContainer) {
            console.error('Features slider container not found');
            return;
        }
        
        const featureItems = featuresContainer.querySelectorAll('.feature-item');
        if (!featureItems.length) {
            console.error('No feature items found for the slider');
            return;
        }
        
        let currentIndex = 0;
        let autoSlideInterval;
        const SLIDE_INTERVAL = 3000; // 3 seconds between slides
        
        // Force full reset of styles to ensure proper sliding
        function applyMobileStyles() {
            // Remove any potentially conflicting inline styles
            featuresContainer.setAttribute('style', '');
            featureItems.forEach(item => item.setAttribute('style', ''));
            
            // Apply fresh inline styles with !important flags
            featuresContainer.style.cssText = `
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                overflow-x: hidden !important;
                scroll-behavior: smooth !important;
                width: 100% !important;
                position: relative !important;
                transition: transform 0.5s ease-in-out !important;
                transform: translateX(0) !important;
            `;
            
            featureItems.forEach(item => {
                item.style.cssText = `
                    flex: 0 0 100% !important;
                    width: 100% !important;
                    min-width: 100% !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
                    margin: 0 !important;
                    padding: 15px !important;
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    position: relative !important;
                `;
            });
            
            // Ensure dots are visible
            const dotsContainer = document.querySelector('.features-dots');
            if (dotsContainer) {
                dotsContainer.style.cssText = `
                    display: flex !important;
                    justify-content: center !important;
                    margin-top: 20px !important;
                    gap: 8px !important;
                    width: 100% !important;
                `;
            }
        }
        
        // Update slider position and active dot
        function updateFeatureSlider() {
            if (window.innerWidth <= 768) {
                // Apply transform to move cards
                console.log(`Sliding to card ${currentIndex + 1} of ${featureItems.length}`);
                featuresContainer.style.transform = `translateX(-${currentIndex * 100}%)`;
                
                // Update active dot indicator
                featureDots.forEach((dot, index) => {
                    if (index === currentIndex) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }
        }
        
        // Navigate to next slide
        function nextSlide() {
            if (window.innerWidth <= 768) {
                currentIndex = (currentIndex + 1) % featureItems.length;
                updateFeatureSlider();
            }
        }
        
        // Navigate to previous slide
        function prevSlide() {
            if (window.innerWidth <= 768) {
                currentIndex = (currentIndex - 1 + featureItems.length) % featureItems.length;
                updateFeatureSlider();
            }
        }
        
        // Go to specific slide
        function goToSlide(index) {
            if (window.innerWidth <= 768) {
                currentIndex = index;
                updateFeatureSlider();
            }
        }
        
        // Start automatic sliding
        function startAutoSlide() {
            stopAutoSlide();
            if (window.innerWidth <= 768) {
                // Reset to first slide
                goToSlide(0);
                autoSlideInterval = setInterval(nextSlide, SLIDE_INTERVAL);
            }
        }
        
        // Stop automatic sliding
        function stopAutoSlide() {
            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
                autoSlideInterval = null;
            }
        }
        
        // Set up event listeners
        function setupEventListeners() {
            // Dot navigation
            featureDots.forEach((dot, index) => {
                // Remove any existing event listeners first
                dot.replaceWith(dot.cloneNode(true));
            });
            
            // Re-select dots after replacing
            const newDots = document.querySelectorAll('.feature-dot');
            newDots.forEach((dot, index) => {
                dot.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (window.innerWidth <= 768) {
                        goToSlide(index);
                        stopAutoSlide();
                        setTimeout(startAutoSlide, 5000);
                    }
                });
            });
            
            // Touch events for swipe
            featuresContainer.addEventListener('touchstart', function(e) {
                if (window.innerWidth <= 768) {
                    touchStartX = e.changedTouches[0].screenX;
                    stopAutoSlide();
                }
            }, { passive: true });
            
            featuresContainer.addEventListener('touchend', function(e) {
                if (window.innerWidth <= 768) {
                    touchEndX = e.changedTouches[0].screenX;
                    const swipeDistance = touchEndX - touchStartX;
                    
                    if (Math.abs(swipeDistance) > 50) {
                        if (swipeDistance > 0) {
                            prevSlide();
                        } else {
                            nextSlide();
                        }
                    }
                    
                    setTimeout(startAutoSlide, 5000);
                }
            }, { passive: true });
        }
        
        // Setup for mobile view
        function setupMobileFeatures() {
            if (window.innerWidth <= 768) {
                console.log('Setting up mobile features slider');
                applyMobileStyles();
                setupEventListeners();
                goToSlide(0); // Reset to first slide
                startAutoSlide();
                
                // Force a reflow to ensure styles are applied
                featuresContainer.offsetHeight;
            } else {
                // Reset for desktop view
                featuresContainer.setAttribute('style', '');
                featureItems.forEach(item => item.setAttribute('style', ''));
                stopAutoSlide();
            }
        }
        
        // Initialize touch variables
        let touchStartX = 0;
        let touchEndX = 0;
        
        // Initial setup
        setupMobileFeatures();
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const wasMobile = window.innerWidth <= 768;
            setTimeout(() => {
                const isMobile = window.innerWidth <= 768;
                if (wasMobile !== isMobile) {
                    setupMobileFeatures();
                }
            }, 200);
        });
        
        console.log('Features slider initialized with improved sliding');
    }
    
    /**
     * Initialize the Tests slider
     */
    function initializeTestsSlider() {
        const sliderContainer = document.getElementById('tests-slider-container');
        const cards = document.querySelectorAll('#tests-slider-container .checkup-card');
        const prevBtn = document.getElementById('test-slider-prev');
        const nextBtn = document.getElementById('test-slider-next');
        const dots = document.querySelectorAll('#tests-slider-dots .indicator-dot');
        
        if (!sliderContainer || !cards.length) {
            console.error('Tests slider elements not found');
            return;
        }
        
        // Make all cards visible for mobile view
        cards.forEach(card => {
            card.style.opacity = '1';
            card.style.visibility = 'visible';
        });
        
        let currentIndex = 0;
        const visibleCards = calculateVisibleCards(sliderContainer);
        let maxIndex = Math.max(0, cards.length - visibleCards);
        
        // Update slider position
        function updateSlider() {
            const isMobile = window.innerWidth < 768;
            
            if (isMobile) {
                // For mobile, move in 25% increments (percentage-based)
                const percent = currentIndex * 25;
                sliderContainer.style.transform = `translateX(-${percent}%)`;
            } else {
                // For desktop, use pixel-based calculation
                sliderContainer.style.transform = `translateX(-${currentIndex * CARD_WIDTH}px)`;
            }
            
            // Update active dot
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
        }
        
        // Previous button click
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateSlider();
                }
            });
        }
        
        // Next button click
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateSlider();
                }
            });
        }
        
        // Dot navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', function() {
                currentIndex = Math.min(index, maxIndex);
                updateSlider();
            });
        });
        
        // Touch events for mobile swiping
        let touchStartX = 0;
        let touchEndX = 0;
        
        sliderContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        sliderContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const SWIPE_THRESHOLD = 50;
            if (touchEndX < touchStartX - SWIPE_THRESHOLD) {
                // Swipe left - next slide
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateSlider();
                }
            } else if (touchEndX > touchStartX + SWIPE_THRESHOLD) {
                // Swipe right - previous slide
                if (currentIndex > 0) {
                    currentIndex--;
                    updateSlider();
                }
            }
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const newVisibleCards = calculateVisibleCards(sliderContainer);
            maxIndex = Math.max(0, cards.length - newVisibleCards);
            
            // Reset position if needed
            if (currentIndex > maxIndex) {
                currentIndex = maxIndex;
            }
            
            updateSlider();
        });
        
        // Initialize slider
        updateSlider();
    }
    
    /**
     * Initialize the Checkups slider
     */
    function initializeCheckupsSlider() {
        const sliderContainer = document.getElementById('checkups-slider-container');
        const cards = document.querySelectorAll('#checkups-slider-container .checkup-card');
        const prevBtn = document.getElementById('slider-prev');
        const nextBtn = document.getElementById('slider-next');
        const dots = document.querySelectorAll('#checkups-slider-dots .indicator-dot');
        
        if (!sliderContainer || !cards.length) {
            console.error('Checkups slider elements not found');
            return;
        }
        
        // Make all cards visible for mobile view
        cards.forEach(card => {
            card.style.opacity = '1';
            card.style.visibility = 'visible';
        });
        
        let currentIndex = 0;
        const visibleCards = calculateVisibleCards(sliderContainer);
        let maxIndex = Math.max(0, cards.length - visibleCards);
        
        // Update slider position
        function updateSlider() {
            const isMobile = window.innerWidth < 768;
            
            if (isMobile) {
                // For mobile, move in 25% increments (percentage-based)
                const percent = currentIndex * 25;
                sliderContainer.style.transform = `translateX(-${percent}%)`;
            } else {
                // For desktop, use pixel-based calculation
                sliderContainer.style.transform = `translateX(-${currentIndex * CARD_WIDTH}px)`;
            }
            
            // Update active dot
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
        }
        
        // Previous button click
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateSlider();
                }
            });
        }
        
        // Next button click
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateSlider();
                }
            });
        }
        
        // Dot navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', function() {
                currentIndex = Math.min(index, maxIndex);
                updateSlider();
            });
        });
        
        // Touch events for mobile swiping
        let touchStartX = 0;
        let touchEndX = 0;
        
        sliderContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        sliderContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const SWIPE_THRESHOLD = 50;
            if (touchEndX < touchStartX - SWIPE_THRESHOLD) {
                // Swipe left - next slide
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateSlider();
                }
            } else if (touchEndX > touchStartX + SWIPE_THRESHOLD) {
                // Swipe right - previous slide
                if (currentIndex > 0) {
                    currentIndex--;
                    updateSlider();
                }
            }
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const newVisibleCards = calculateVisibleCards(sliderContainer);
            maxIndex = Math.max(0, cards.length - newVisibleCards);
            
            // Reset position if needed
            if (currentIndex > maxIndex) {
                currentIndex = maxIndex;
            }
            
            updateSlider();
        });
        
        // Initialize slider
        updateSlider();
    }
    
    /**
     * Initialize popup functionality for detail views
     */
    function initializePopups() {
        const detailBtns = document.querySelectorAll('.btn-details');
        const popupOverlays = document.querySelectorAll('.popup-overlay');
        const closeBtns = document.querySelectorAll('.popup-close');
        
        // Open popup for test/checkup details
        detailBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const checkupId = this.getAttribute('data-checkup-id');
                if (checkupId) {
                    const popup = document.getElementById(`checkup-popup-${checkupId}`);
                    if (popup) {
                        popup.classList.add('active');
                        document.body.style.overflow = 'hidden'; // Prevent scrolling
                    }
                }
                
                const testId = this.getAttribute('data-test-id');
                if (testId) {
                    const popup = document.getElementById(`test-popup-${testId}`);
                    if (popup) {
                        popup.classList.add('active');
                        document.body.style.overflow = 'hidden'; // Prevent scrolling
                    }
                }
            });
        });
        
        // Close popup with X button
        closeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const popup = this.closest('.popup-overlay');
                popup.classList.remove('active');
                document.body.style.overflow = 'auto'; // Restore scrolling
            });
        });
        
        // Close popup when clicking on overlay
        popupOverlays.forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = 'auto'; // Restore scrolling
                }
            });
        });
        
        console.log('Popups initialized');
    }
    
    /**
     * Initialize placeholder images
     */
    function initializePlaceholders() {
        // Set placeholders for icons
        setPlaceholder('heart-icon-placeholder', 'Heart');
        setPlaceholder('kidney-icon-placeholder', 'Kidney');
        setPlaceholder('liver-icon-placeholder', 'Liver');
        setPlaceholder('bone-icon-placeholder', 'Bone');
        setPlaceholder('vitamin-icon-placeholder', 'Vitamin');
        setPlaceholder('hormones-icon-placeholder', 'Hormones');
        setPlaceholder('gut-icon-placeholder', 'Gut Health');
        setPlaceholder('blood-icon-placeholder', 'Blood');
        setPlaceholder('reproductive-icon-placeholder', 'Reproductive');
        
        // ECG heart placeholder
        const ecgPlaceholder = document.getElementById('ecg-image-placeholder');
        if (ecgPlaceholder && !ecgPlaceholder.complete) {
            ecgPlaceholder.src = `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><path d="M100,20 C130,20 155,50 155,80 C155,120 100,160 100,160 C100,160 45,120 45,80 C45,50 70,20 100,20 Z" fill="%23FF8A00"></path><path d="M40,100 L70,100 L85,80 L100,140 L115,60 L130,100 L160,100" stroke="white" stroke-width="3" fill="none"></path></svg>`;
        }
    }
    
    /**
     * Helper function to set placeholder images
     */
    function setPlaceholder(id, name, color = '#16A085') {
        const element = document.getElementById(id);
        if (element && (!element.complete || element.naturalHeight === 0)) {
            // Choose the appropriate SVG based on the organ type
            let svgContent = '';
            
            if (name === 'Heart') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <path d="M40,25 C45,20 55,20 60,30 C65,40 55,50 40,65 C25,50 15,40 20,30 C25,20 35,20 40,25 Z" fill="${color}"/>
                </svg>`;
            } else if (name === 'Kidney') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <path d="M30,25 C40,20 55,30 50,45 C45,60 35,65 30,55 C25,45 20,30 30,25 Z" fill="${color}"/>
                </svg>`;
            } else if (name === 'Liver') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <path d="M25,30 C35,25 55,25 60,35 C65,45 55,60 40,60 C25,60 20,45 25,30 Z" fill="${color}"/>
                </svg>`;
            } else if (name === 'Bone') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <path d="M30,20 C35,15 40,20 40,25 L40,55 C40,60 35,65 30,60 C25,55 25,50 30,45 C25,40 25,35 30,30 C25,25 25,20 30,20 Z" fill="${color}"/>
                </svg>`;
            } else if (name === 'Vitamin') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <circle cx="40" cy="40" r="15" fill="${color}"/>
                    <text x="40" y="45" font-family="Arial" font-size="16" text-anchor="middle" fill="white">D</text>
                </svg>`;
            } else if (name === 'Hormones') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <path d="M30,25 L50,25 L50,40 C50,50 40,60 30,40 L30,25 Z" fill="${color}"/>
                    <circle cx="40" cy="20" r="5" fill="${color}"/>
                </svg>`;
            } else if (name === 'Gut Health') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <path d="M30,25 C50,25 50,35 30,35 C50,35 50,45 30,45 C50,45 50,55 30,55" stroke="${color}" stroke-width="5" fill="none"/>
                </svg>`;
            } else if (name === 'Blood') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <path d="M40,20 C50,30 60,40 40,60 C20,40 30,30 40,20 Z" fill="#D32F2F"/>
                </svg>`;
            } else if (name === 'Reproductive') {
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.1"/>
                    <circle cx="40" cy="30" r="10" stroke="${color}" stroke-width="3" fill="none"/>
                    <path d="M40,40 L40,60 M30,50 L50,50" stroke="${color}" stroke-width="3"/>
                </svg>`;
            } else {
                // Default icon if none of the specific ones match
                svgContent = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
                    <rect width="80" height="80" rx="40" fill="${color}" opacity="0.2"/>
                    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="12" fill="${color}">${name}</text>
                </svg>`;
            }
            
            // Set the SVG as the src
            element.src = `data:image/svg+xml;utf8,${encodeURIComponent(svgContent)}`;
            
            // Ensure the image is displayed properly
            element.style.display = 'block';
            element.style.maxWidth = '100%';
            element.style.height = 'auto';
        }
    }
    
    /**
     * Helper function to calculate visible cards based on container width
     */
    function calculateVisibleCards(container) {
        const containerWidth = container.parentElement.clientWidth;
        
        // Responsive breakpoints
        if (window.innerWidth < 768) {
            return 1; // Mobile: 1 card
        } else if (window.innerWidth < 992) {
            return 2; // Tablet: 2 cards
        } else {
            return Math.max(1, Math.floor(containerWidth / CARD_WIDTH));
        }
    }

    // Apply CSS transition to other sliders after initialization
    setTimeout(() => {
        const sliders = document.querySelectorAll('#tests-slider-container, #checkups-slider-container');
        sliders.forEach(slider => {
            if (slider) {
                slider.style.transition = 'transform 0.3s ease-in-out';
            }
        });
        
        // Initialize mobile view height
        if (window.innerWidth < 768) {
            const testSlider = document.getElementById('tests-slider-container');
            const checkupsSlider = document.getElementById('checkups-slider-container');
            
            if (testSlider) {
                const testCards = testSlider.querySelectorAll('.checkup-card');
                if (testCards.length > 0) {
                    testCards.forEach(card => {
                        card.style.opacity = '1';
                        card.style.visibility = 'visible';
                    });
                }
            }
            
            if (checkupsSlider) {
                const checkupCards = checkupsSlider.querySelectorAll('.checkup-card');
                if (checkupCards.length > 0) {
                    checkupCards.forEach(card => {
                        card.style.opacity = '1';
                        card.style.visibility = 'visible';
                    });
                }
            }
        }
    }, 100);

    // Organs Banner Slider Logic
    function initializeOrgansBannerSlider() {
        const slider = document.querySelector('.organs-banner-slider');
        const track = slider ? slider.querySelector('.organs-banner-track') : null;
        const banners = track ? track.querySelectorAll('.organs-banner') : [];
        const dots = slider ? slider.querySelectorAll('.organs-banner-dot') : [];
        
        if (!slider || !track || banners.length === 0 || dots.length === 0) return;

        let currentIndex = 0;
        let autoSlideTimer = null;
        const SLIDE_INTERVAL = 3000;
        const totalBanners = banners.length;

        function goToSlide(index) {
            currentIndex = index;
            if (currentIndex < 0) currentIndex = totalBanners - 1;
            if (currentIndex >= totalBanners) currentIndex = 0;
            
            // Calculate the exact percentage to move for each slide (20% per slide for 5 slides)
            const slidePercentage = 100 / totalBanners;
            track.style.transform = `translateX(-${currentIndex * slidePercentage}%)`;
            
            // Update active dot indicator
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === currentIndex);
            });
        }

        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        function startAutoSlide() {
            if (autoSlideTimer) clearInterval(autoSlideTimer);
            autoSlideTimer = setInterval(nextSlide, SLIDE_INTERVAL);
        }

        function stopAutoSlide() {
            if (autoSlideTimer) clearInterval(autoSlideTimer);
            autoSlideTimer = null;
        }

        // Set up dot navigation (keep this for manual control if needed)
        dots.forEach((dot, i) => {
            dot.onclick = function() {
                goToSlide(i);
                stopAutoSlide();
                setTimeout(startAutoSlide, 4000);
            };
        });

        // Touch swipe support
        let touchStartX = 0;
        let touchEndX = 0;
        track.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoSlide();
        }, { passive: true });
        
        track.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            const dx = touchEndX - touchStartX;
            if (Math.abs(dx) > 50) {
                if (dx > 0) goToSlide(currentIndex - 1);
                else goToSlide(currentIndex + 1);
            }
            setTimeout(startAutoSlide, 4000);
        }, { passive: true });

        // Handle window resize to ensure proper card display
        window.addEventListener('resize', function() {
            goToSlide(currentIndex);
        });

        // Initial setup
        goToSlide(0);
        startAutoSlide();
    }

    // Call the new organs banner slider initializer
    initializeOrgansBannerSlider();
}); 