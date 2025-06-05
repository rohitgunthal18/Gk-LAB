            </div> <!-- End of admin-content -->
        </div> <!-- End of admin-main -->
    </div> <!-- End of admin-wrapper -->
    
    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle sidebar on mobile
            $('.sidebar-toggle, .sidebar-toggle-mobile').click(function() {
                $('.admin-sidebar').toggleClass('collapsed');
                $('.admin-main').toggleClass('expanded');
            });
            
            // User dropdown toggle
            $('.user-dropdown-toggle').click(function(e) {
                e.stopPropagation();
                $(this).toggleClass('active');
                $('.user-dropdown-menu').toggleClass('show');
            });
            
            // Close dropdown when clicking outside
            $(document).click(function(e) {
                if (!$(e.target).closest('.admin-user-dropdown').length) {
                    $('.user-dropdown-toggle').removeClass('active');
                    $('.user-dropdown-menu').removeClass('show');
                }
            });
            
            // Close alert messages
            $('.alert-close').click(function() {
                $(this).closest('.alert').fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Data table checkboxes
            $('.select-all').change(function() {
                var isChecked = $(this).prop('checked');
                $('.table-checkbox').prop('checked', isChecked);
                updateBulkActionButtons();
            });
            
            $('.table-checkbox').change(function() {
                updateBulkActionButtons();
                
                // Update select all checkbox
                var allChecked = $('.table-checkbox:checked').length === $('.table-checkbox').length;
                $('.select-all').prop('checked', allChecked);
            });
            
            // Helper function to show/hide bulk action buttons
            function updateBulkActionButtons() {
                var checkedCount = $('.table-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('.bulk-actions').addClass('show');
                    $('.selected-count').text(checkedCount);
                } else {
                    $('.bulk-actions').removeClass('show');
                }
            }
            
            // Confirm delete actions
            $('.delete-btn, .bulk-delete-btn').click(function(e) {
                if (!confirm('Are you sure you want to delete the selected item(s)? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
            
            // Date picker initialization if it exists
            if ($.fn.datepicker) {
                $('.datepicker').datepicker({
                    format: 'yyyy-mm-dd',
                    autoclose: true,
                    todayHighlight: true
                });
            }
        });
    </script>
</body>
</html> 