/**
 * Admin Responsive JavaScript
 * Handles mobile-friendly interactions for the GK Lab admin panel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    initSidebar();
    
    // Table responsiveness
    initResponsiveTables();
    
    // Form responsiveness
    initResponsiveForms();
    
    // Modal handling on small screens
    initResponsiveModals();
});

/**
 * Initialize sidebar functionality for mobile devices
 */
function initSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarToggleMobile = document.querySelector('.sidebar-toggle-mobile');
    const adminSidebar = document.querySelector('.admin-sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    // Function to toggle sidebar
    function toggleSidebar() {
        adminSidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                toggleSidebar();
            } else {
                document.body.classList.toggle('sidebar-collapsed');
            }
        });
    }
    
    if (sidebarToggleMobile) {
        sidebarToggleMobile.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar when overlay is clicked
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar when window is resized above mobile breakpoint
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && adminSidebar && adminSidebar.classList.contains('active')) {
            adminSidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });
}

/**
 * Initialize responsive behavior for tables
 */
function initResponsiveTables() {
    // Add horizontal scroll indicator for tables
    const tableResponsives = document.querySelectorAll('.table-responsive');
    
    tableResponsives.forEach(container => {
        const table = container.querySelector('table');
        if (!table) return;
        
        // Check if table is wider than container
        const isOverflowing = table.offsetWidth > container.offsetWidth;
        
        if (isOverflowing && !container.querySelector('.scroll-indicator')) {
            // Add scroll indicator if table is wider than container
            const indicator = document.createElement('div');
            indicator.className = 'scroll-indicator';
            indicator.innerHTML = '<i class="fas fa-arrows-left-right"></i> Scroll horizontally to see more data';
            container.prepend(indicator);
            
            // Hide indicator when scrolled to end
            container.addEventListener('scroll', function() {
                const maxScroll = container.scrollWidth - container.clientWidth;
                const currentScroll = container.scrollLeft;
                
                if (currentScroll > 20) {
                    indicator.classList.add('faded');
                } else {
                    indicator.classList.remove('faded');
                }
            });
        }
    });
    
    // Add data labels to cells for mobile view and apply stacked table class
    const tables = document.querySelectorAll('.admin-table');
    
    tables.forEach(table => {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = table.querySelectorAll('tbody tr');
        
        // Add data-label attributes to cells
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (index < headers.length && headers[index] !== '') {
                    // Add data label attribute for CSS to use
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
        
        // Enable stacked table layout for small screens
        function updateTableLayout() {
            if (window.innerWidth <= 480) {
                table.classList.add('stacked-table');
            } else {
                table.classList.remove('stacked-table');
            }
        }
        
        // Initial call
        updateTableLayout();
        
        // Add resize event listener
        window.addEventListener('resize', updateTableLayout);
    });
}

/**
 * Initialize responsive behavior for forms
 */
function initResponsiveForms() {
    // Handle form validation messages responsively
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const formInputs = form.querySelectorAll('input, select, textarea');
        
        formInputs.forEach(input => {
            // Show validation messages in a mobile-friendly way
            input.addEventListener('invalid', function(e) {
                // Prevent browser default bubbles
                e.preventDefault();
                
                // Add error class to parent
                const formGroup = this.closest('.form-group') || this.parentNode;
                formGroup.classList.add('has-error');
                
                // Create or update error message
                let errorMessage = formGroup.querySelector('.error-message');
                if (!errorMessage) {
                    errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message';
                    formGroup.appendChild(errorMessage);
                }
                errorMessage.textContent = this.validationMessage;
                
                // Scroll to the error
                this.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
            
            // Clear error when field is changed
            input.addEventListener('input', function() {
                const formGroup = this.closest('.form-group') || this.parentNode;
                formGroup.classList.remove('has-error');
                const errorMessage = formGroup.querySelector('.error-message');
                if (errorMessage) {
                    errorMessage.textContent = '';
                }
            });
        });
    });
}

/**
 * Initialize responsive behavior for modals
 */
function initResponsiveModals() {
    // Make modals more mobile friendly
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const modalDialog = modal.querySelector('.modal-dialog');
        
        // Adjust modal position on small screens
        function adjustModalPosition() {
            if (window.innerWidth < 576) {
                // For very small screens, maximize the modal
                modalDialog.style.margin = '10px';
                modalDialog.style.width = 'calc(100% - 20px)';
                modalDialog.style.maxWidth = 'none';
            } else {
                // Reset for larger screens
                modalDialog.style.margin = '1.75rem auto';
                modalDialog.style.width = '';
                modalDialog.style.maxWidth = '';
            }
        }
        
        // Called when modal is opened
        const modalTriggers = document.querySelectorAll(`[data-toggle="modal"][data-target="#${modal.id}"]`);
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', adjustModalPosition);
        });
        
        // Handle resize events when modal is open
        window.addEventListener('resize', function() {
            if (modal.classList.contains('show')) {
                adjustModalPosition();
            }
        });
    });
}

/**
 * Utility functions for responsive design
 */
// Detect if device is touch-enabled
function isTouchDevice() {
    return ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);
}

// Get current breakpoint based on Bootstrap's breakpoints
function getCurrentBreakpoint() {
    const width = window.innerWidth;
    if (width < 576) return 'xs';
    if (width < 768) return 'sm';
    if (width < 992) return 'md';
    if (width < 1200) return 'lg';
    return 'xl';
} 