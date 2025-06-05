// Orange Health Lab - Main JavaScript

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-toggle');
    const navMenu = document.getElementById('nav-menu');
    
    if (mobileMenuButton && navMenu) {
        mobileMenuButton.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (navMenu.classList.contains('active') && 
                !navMenu.contains(e.target) && 
                !mobileMenuButton.contains(e.target)) {
                navMenu.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    }

    // Dropdown toggles for mobile
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                e.preventDefault();
                const dropdownMenu = this.nextElementSibling;
                dropdownMenu.classList.toggle('active');
            }
        });
    });

    // Location selector functionality
    const locationSelector = document.getElementById('location-selector');
    if (locationSelector) {
        locationSelector.addEventListener('change', function() {
            // In a real app, this would update the location and refresh content
            console.log('Location changed to: ' + this.value);
        });
    }

    // Search functionality
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchInput = document.getElementById('search-input');
            console.log('Search query: ' + searchInput.value);
            // In a real app, this would perform a search or redirect to search results
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}); 