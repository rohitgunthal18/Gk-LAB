        </div>
    </div>
    
    <script>
        // Simple toggle for sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebarToggleMobile = document.querySelector('.sidebar-toggle-mobile');
            const adminSidebar = document.querySelector('.admin-sidebar');
            const sidebarOverlay = document.querySelector('.sidebar-overlay');
            
            // Function to toggle sidebar on mobile
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
                if (window.innerWidth > 768 && adminSidebar.classList.contains('active')) {
                    adminSidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            });
            
            // Alert close button
            const alertCloseButtons = document.querySelectorAll('.alert-close');
            
            alertCloseButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const alert = this.closest('.alert');
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                });
            });
            
            // Table checkboxes
            const selectAll = document.querySelector('.select-all');
            const tableCheckboxes = document.querySelectorAll('.table-checkbox');
            const selectedCount = document.querySelector('.selected-count');
            
            if (selectAll && tableCheckboxes.length > 0) {
                selectAll.addEventListener('change', function() {
                    tableCheckboxes.forEach(function(checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                    
                    updateSelectedCount();
                });
                
                tableCheckboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        updateSelectedCount();
                        
                        // Check if all checkboxes are checked
                        const allChecked = Array.from(tableCheckboxes).every(function(cb) {
                            return cb.checked;
                        });
                        
                        selectAll.checked = allChecked;
                    });
                });
                
                // Bulk delete confirmation
                const bulkDeleteBtn = document.querySelector('.bulk-delete-btn');
                if (bulkDeleteBtn) {
                    bulkDeleteBtn.addEventListener('click', function(e) {
                        const form = this.closest('form');
                        const action = form.querySelector('[name="bulk_action"]').value;
                        
                        if (action === 'delete') {
                            const checkedCount = document.querySelectorAll('.table-checkbox:checked').length;
                            
                            if (checkedCount === 0) {
                                e.preventDefault();
                                alert('Please select at least one item to delete.');
                            } else {
                                if (!confirm('Are you sure you want to delete ' + checkedCount + ' selected items?')) {
                                    e.preventDefault();
                                }
                            }
                        }
                    });
                }
                
                function updateSelectedCount() {
                    const count = document.querySelectorAll('.table-checkbox:checked').length;
                    
                    if (selectedCount) {
                        selectedCount.textContent = count;
                    }
                }
            }
            
            // Single delete confirmation
            const deleteButtons = document.querySelectorAll('.delete-btn');
            
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html> 