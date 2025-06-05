/**
 * Responsive Tables Helper
 * 
 * This script automatically wraps tables in the admin area with
 * the table-responsive class if they're not already wrapped.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Target tables that need to be responsive
    const tables = document.querySelectorAll('.admin-table, .data-table, .table');
    
    tables.forEach(table => {
        // Check if the table is already inside a table-responsive div
        if (!table.parentElement.classList.contains('table-responsive')) {
            // Create a responsive wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            
            // Insert the wrapper before the table in the DOM
            table.parentNode.insertBefore(wrapper, table);
            
            // Move the table inside the wrapper
            wrapper.appendChild(table);
        }
    });
    
    // Add scroll indication for mobile devices
    const tableWrappers = document.querySelectorAll('.table-responsive');
    
    tableWrappers.forEach(wrapper => {
        wrapper.addEventListener('scroll', function() {
            // Add a class when scrolled
            if (this.scrollLeft > 0) {
                this.classList.add('is-scrolled');
            } else {
                this.classList.remove('is-scrolled');
            }
        });
        
        // Check if this table needs scrolling on mobile
        function checkScroll() {
            if (wrapper.scrollWidth > wrapper.clientWidth) {
                wrapper.classList.add('needs-scroll');
                
                // Check if we're in admin/coupons section
                const path = window.location.pathname;
                if (path.includes('/coupons/') && window.innerWidth <= 768) {
                    const table = wrapper.querySelector('table');
                    if (table) {
                        // Set width for smooth scrolling
                        if (table.closest('.card-content')) {
                            // Make sure the width is appropriate for the number of columns
                            const columnCount = table.querySelectorAll('th').length;
                            if (columnCount > 0) {
                                // Calculate width based on number of columns
                                const minWidth = Math.max(800, columnCount * 100);
                                table.style.minWidth = minWidth + 'px';
                                
                                // Add indicator for small screens
                                if (!wrapper.querySelector('.scroll-hint') && window.innerWidth < 480) {
                                    const hint = document.createElement('div');
                                    hint.className = 'scroll-hint';
                                    hint.textContent = '← Swipe to see more →';
                                    hint.style.cssText = 'text-align:center; font-size:12px; color:#666; padding:5px; background-color:rgba(22,160,133,0.05);';
                                    wrapper.prepend(hint);
                                }
                            }
                        }
                    }
                }
            } else {
                wrapper.classList.remove('needs-scroll');
                
                // Remove the hint if it exists
                const hint = wrapper.querySelector('.scroll-hint');
                if (hint) {
                    hint.remove();
                }
            }
        }
        
        // Run on load and resize
        checkScroll();
        window.addEventListener('resize', checkScroll);
    });
    
    // Special handling for description cells
    document.querySelectorAll('.description-cell').forEach(cell => {
        const text = cell.textContent;
        if (text.length > 30) {
            cell.setAttribute('title', text);
        }
    });
}); 