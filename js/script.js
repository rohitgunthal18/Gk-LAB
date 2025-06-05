// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event bubbling
            navMenu.classList.toggle('active');
            // Change icon between bars and times
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (navMenu && mobileMenuToggle) {
            const isClickInsideMenu = navMenu.contains(event.target);
            const isClickOnToggle = mobileMenuToggle.contains(event.target);
            
            if (!isClickInsideMenu && !isClickOnToggle && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        }
    });
    
    // Handle dropdown menus in mobile view
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                const dropdown = this.nextElementSibling;
                dropdown.classList.toggle('active');
                
                // Toggle the chevron icon
                const chevron = this.querySelector('.fa-chevron-down, .fa-chevron-up');
                if (chevron) {
                    if (chevron.classList.contains('fa-chevron-down')) {
                        chevron.classList.remove('fa-chevron-down');
                        chevron.classList.add('fa-chevron-up');
                    } else {
                        chevron.classList.remove('fa-chevron-up');
                        chevron.classList.add('fa-chevron-down');
                    }
                }
            }
        });
    });
    
    // Features Section Mobile Slider
    const setupFeatureSlider = () => {
        const featuresContainer = document.querySelector('.features-container');
        const featureDots = document.querySelectorAll('.feature-dot');
        
        if (featuresContainer && featureDots.length && window.innerWidth <= 768) {
            const featureItems = featuresContainer.querySelectorAll('.feature-item');
            let touchStartX = 0;
            let touchEndX = 0;
            
            // Force 100% width for mobile view
            featureItems.forEach(item => {
                item.style.width = '100%';
            });
            
            // Auto-sliding functionality
            let currentSlideIndex = 0;
            let autoSlideInterval;
            
            const showSlide = (index) => {
                currentSlideIndex = index;
                
                if (currentSlideIndex >= featureItems.length) {
                    currentSlideIndex = 0;
                }
                
                // Update active dot
                featureDots.forEach(dot => dot.classList.remove('active'));
                featureDots[currentSlideIndex].classList.add('active');
                
                // Scroll to the target slide
                const scrollPosition = featuresContainer.clientWidth * currentSlideIndex;
                featuresContainer.scrollTo({
                    left: scrollPosition,
                    behavior: 'smooth'
                });
            };
            
            const nextSlide = () => {
                showSlide((currentSlideIndex + 1) % featureItems.length);
            };
            
            const previousSlide = () => {
                showSlide(currentSlideIndex === 0 ? featureItems.length - 1 : currentSlideIndex - 1);
            };
            
            const startAutoSlide = () => {
                clearInterval(autoSlideInterval);
                autoSlideInterval = setInterval(nextSlide, 3000);
            };
            
            // Handle dot clicks
            featureDots.forEach(dot => {
                dot.addEventListener('click', function() {
                    // Stop auto-sliding when user interacts
                    clearInterval(autoSlideInterval);
                    
                    const index = parseInt(this.getAttribute('data-index'));
                    showSlide(index);
                    
                    // Resume auto-sliding after 5 seconds
                    setTimeout(startAutoSlide, 5000);
                });
            });
            
            // Touch events for swipe functionality
            featuresContainer.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
                clearInterval(autoSlideInterval);
            }, { passive: true });
            
            featuresContainer.addEventListener('touchmove', (e) => {
                touchEndX = e.touches[0].clientX;
            }, { passive: true });
            
            featuresContainer.addEventListener('touchend', () => {
                const swipeDistance = touchEndX - touchStartX;
                
                if (Math.abs(swipeDistance) > 50) { // Minimum swipe distance
                    if (swipeDistance > 0) {
                        previousSlide();
                    } else {
                        nextSlide();
                    }
                }
                
                // Resume auto-sliding after 5 seconds
                setTimeout(startAutoSlide, 5000);
            }, { passive: true });
            
            // Update active dot on scroll
            featuresContainer.addEventListener('scroll', function() {
                const scrollPosition = this.scrollLeft;
                const slideIndex = Math.round(scrollPosition / this.clientWidth);
                
                if (slideIndex !== currentSlideIndex && slideIndex < featureItems.length) {
                    featureDots.forEach(dot => dot.classList.remove('active'));
                    featureDots[slideIndex].classList.add('active');
                    currentSlideIndex = slideIndex;
                }
            }, { passive: true });
            
            // Start auto-sliding
            startAutoSlide();
            
            // Show first slide initially
            showSlide(0);
        }
    };
    
    // Reset and reinitialize feature slider on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && navMenu && navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            const icon = mobileMenuToggle.querySelector('i');
            if (icon && icon.classList.contains('fa-times')) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
            
            // Reset all dropdown menus
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('active');
            });
            
            // Reset all chevron icons
            document.querySelectorAll('.fa-chevron-up').forEach(icon => {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            });
        }
        
        // Reset and reinitialize feature slider
        const featuresContainer = document.querySelector('.features-container');
        if (featuresContainer) {
            // Reset styles
            featuresContainer.style.width = '';
            featuresContainer.style.transform = '';
            featuresContainer.querySelectorAll('.feature-item').forEach(item => {
                item.style.width = '';
            });
            
            // Reinitialize if in mobile view
            if (window.innerWidth <= 768) {
                setupFeatureSlider();
            }
        }
    });
    
    // Initialize feature slider
    setupFeatureSlider();
    
    // Checkup Cards Slider Functionality
    const setupCheckupSlider = () => {
        const sliderContainer = document.querySelector('.slider-container');
        const prevBtn = document.getElementById('slider-prev');
        const nextBtn = document.getElementById('slider-next');
        const dots = document.querySelectorAll('.indicator-dot');
        
        if (!sliderContainer || !prevBtn || !nextBtn) return;
        
        const cards = document.querySelectorAll('.checkup-card');
        let currentIndex = 0;
        let isMobile = window.innerWidth <= 768;
        
        // Reset all card styles and container transform
        const resetStyles = () => {
            // Reset container
            sliderContainer.style.transition = 'none';
            sliderContainer.style.transform = '';
            
            // Reset all cards
            cards.forEach(card => {
                card.style.transition = 'none';
                card.style.width = '';
                card.style.minWidth = '';
                card.style.opacity = '1';
                card.style.visibility = 'visible';
                card.style.position = '';
                card.style.transform = '';
            });
            
            // Force reflow
            void sliderContainer.offsetWidth;
            
            // Restore transitions
            sliderContainer.style.transition = 'transform 0.3s ease';
            cards.forEach(card => {
                card.style.transition = 'opacity 0.3s ease';
            });
        };
        
        // Update slider based on current index and screen size
        const updateSlider = (index, animate = true) => {
            if (index < 0) index = 0;
            if (index >= cards.length) index = cards.length - 1;
            
            currentIndex = index;
            
            // Update indicator dots
            dots.forEach((dot, i) => {
                if (i < cards.length) {
                    dot.style.display = 'block';
                    dot.classList.toggle('active', i === currentIndex);
                } else {
                    dot.style.display = 'none';
                }
            });
            
            if (isMobile) {
                // Mobile view: translate the container
                sliderContainer.style.transition = animate ? 'transform 0.3s ease' : 'none';
                
                // Make all cards visible but only show the current one fully
                cards.forEach((card, i) => {
                    card.style.width = '100%';
                    card.style.minWidth = '100%';
                    card.style.visibility = 'visible';
                    card.style.opacity = '1';
                });
                
                // Move the container to show the current card
                const translateX = -(currentIndex * 100);
                sliderContainer.style.transform = `translateX(${translateX}%)`;
            } else {
                // Desktop view: Use original slider calculation
                resetStyles();
                
                if (cards.length > 0) {
                    const cardWidth = cards[0].offsetWidth;
                    const gap = 20; // Gap between cards
                    const translateX = -(currentIndex * (cardWidth + gap));
                    
                    sliderContainer.style.transition = animate ? 'transform 0.3s ease' : 'none';
                    sliderContainer.style.transform = `translateX(${translateX}px)`;
                }
            }
        };
        
        // Button click handlers
        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                updateSlider(currentIndex - 1);
            }
        });
        
        nextBtn.addEventListener('click', () => {
            if (currentIndex < cards.length - 1) {
                updateSlider(currentIndex + 1);
            }
        });
        
        // Dot click handlers
        dots.forEach((dot, index) => {
            if (index < cards.length) {
                dot.addEventListener('click', () => {
                    updateSlider(index);
                });
            }
        });
        
        // Swipe functionality
        let touchStartX = 0;
        let touchEndX = 0;
        
        sliderContainer.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });
        
        sliderContainer.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].clientX;
            const swipeDistance = touchEndX - touchStartX;
            
            if (Math.abs(swipeDistance) > 50) {
                if (swipeDistance > 0 && currentIndex > 0) {
                    // Swipe right (previous)
                    updateSlider(currentIndex - 1);
                } else if (swipeDistance < 0 && currentIndex < cards.length - 1) {
                    // Swipe left (next)
                    updateSlider(currentIndex + 1);
                }
            }
        }, { passive: true });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            const wasMobile = isMobile;
            isMobile = window.innerWidth <= 768;
            
            // If changing between mobile and desktop, reset everything
            if (wasMobile !== isMobile) {
                resetStyles();
            }
            
            // Update slider without animation on resize
            updateSlider(currentIndex, false);
        });
        
        // Initialize slider
        resetStyles();
        updateSlider(0, false);
    };
    
    // Initialize checkup slider
    setupCheckupSlider();
    
    // Checkup Details Popup Functionality
    const setupCheckupPopups = () => {
        const detailButtons = document.querySelectorAll('.btn-details');
        const popupOverlays = document.querySelectorAll('.popup-overlay');
        const popupCloseButtons = document.querySelectorAll('.popup-close');
        
        // Open popup when clicking on View Details button
        detailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const checkupId = this.getAttribute('data-checkup-id');
                const popup = document.getElementById(`checkup-popup-${checkupId}`);
                
                if (popup) {
                    // Close any open popups first
                    popupOverlays.forEach(overlay => {
                        overlay.classList.remove('active');
                    });
                    
                    // Open the selected popup
                    popup.classList.add('active');
                    
                    // Prevent body scrolling when popup is open
                    document.body.style.overflow = 'hidden';
                }
            });
        });
        
        // Close popup when clicking on close button
        popupCloseButtons.forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                const popup = this.closest('.popup-overlay');
                
                if (popup) {
                    popup.classList.remove('active');
                    
                    // Re-enable body scrolling
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Close popup when clicking outside the popup content
        popupOverlays.forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                // Check if click was on the overlay itself, not on the content
                if (e.target === this) {
                    this.classList.remove('active');
                    
                    // Re-enable body scrolling
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Close popup when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activePopup = document.querySelector('.popup-overlay.active');
                
                if (activePopup) {
                    activePopup.classList.remove('active');
                    
                    // Re-enable body scrolling
                    document.body.style.overflow = '';
                }
            }
        });
        
        // Add to Cart functionality for popup
        const popupAddButtons = document.querySelectorAll('.popup-btn');
        
        popupAddButtons.forEach(button => {
            button.addEventListener('click', function() {
                const popup = this.closest('.popup-overlay');
                const popupTitle = popup.querySelector('.popup-title').textContent;
                
                // Add to cart (you can customize this functionality)
                console.log(`Added to cart: ${popupTitle}`);
                
                // Close the popup
                popup.classList.remove('active');
                
                // Re-enable body scrolling
                document.body.style.overflow = '';
                
                // Show a confirmation message
                alert(`${popupTitle} has been added to your cart!`);
            });
        });
    };
    
    // Initialize checkup popup functionality
    setupCheckupPopups();

    // Mobile User Icon Dropdown
    const mobileUserIcon = document.querySelector('.mobile-user-icon');
    const mobileUserDropdown = document.querySelector('.mobile-user-dropdown');
    
    if (mobileUserIcon && mobileUserDropdown) {
        mobileUserIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileUserIcon.classList.toggle('active');
            mobileUserDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileUserDropdown.contains(e.target) && !mobileUserIcon.contains(e.target)) {
                mobileUserIcon.classList.remove('active');
                mobileUserDropdown.classList.remove('active');
            }
        });
    }
});

// Desktop User Dropdown
document.addEventListener('DOMContentLoaded', function() {
    const userButton = document.querySelector('.btn-user');
    const userDropdownMenu = document.querySelector('.user-dropdown-menu');
    
    if (userButton && userDropdownMenu) {
        userButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userButton.classList.toggle('active');
            userDropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdownMenu.contains(e.target) && !userButton.contains(e.target)) {
                userButton.classList.remove('active');
                userDropdownMenu.classList.remove('show');
            }
        });
    }
}); 