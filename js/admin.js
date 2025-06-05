/**
 * GK Lab Admin JavaScript
 * 
 * This file contains JavaScript functionality for the admin panel.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarToggleMobile = document.querySelector('.sidebar-toggle-mobile');
    const adminWrapper = document.querySelector('.admin-wrapper');
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            adminWrapper.classList.toggle('sidebar-collapsed');
        });
    }
    
    if (sidebarToggleMobile) {
        sidebarToggleMobile.addEventListener('click', function() {
            adminSidebar.classList.toggle('active');
        });
    }
    
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
    
    // Data table functionality
    const dataTableSearchInputs = document.querySelectorAll('.data-table-search');
    
    dataTableSearchInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            const searchQuery = this.value.toLowerCase();
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(function(row) {
                    let text = row.textContent.toLowerCase();
                    if (text.indexOf(searchQuery) > -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    });
    
    // Form validation
    const adminForms = document.querySelectorAll('.admin-form');
    
    adminForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    
                    // Create error message if not exists
                    let errorDiv = field.nextElementSibling;
                    if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.textContent = 'This field is required';
                        field.parentNode.insertBefore(errorDiv, field.nextSibling);
                    }
                } else {
                    field.classList.remove('is-invalid');
                    
                    // Remove error message if exists
                    let errorDiv = field.nextElementSibling;
                    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                        errorDiv.remove();
                    }
                }
            });
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    });
    
    // Image preview on file input
    const imageInputs = document.querySelectorAll('.image-upload');
    
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);
            
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}); 