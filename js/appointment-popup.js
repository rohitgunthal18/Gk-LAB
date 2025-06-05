/**
 * Improved Appointment Popup Functionality
 * This script handles the popup form without applying inline styles
 * now that we have a dedicated CSS file
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("Appointment popup script loaded");
    
    // Ensure CSS is loaded
    ensureAppointmentCssLoaded();
    
    // Create popup container if it doesn't exist
    let popupContainer = document.getElementById('appointment-popup-container');
    
    if (!popupContainer) {
        console.log('Creating appointment popup container');
        popupContainer = document.createElement('div');
        popupContainer.id = 'appointment-popup-container';
        popupContainer.className = 'appointment-popup';
        
        // Add necessary inline styles to ensure visibility
        popupContainer.style.position = 'fixed';
        popupContainer.style.top = '0';
        popupContainer.style.left = '0';
        popupContainer.style.width = '100%';
        popupContainer.style.height = '100%';
        popupContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        popupContainer.style.zIndex = '9999';
        popupContainer.style.display = 'none';
        
        document.body.appendChild(popupContainer);
        console.log('Popup container created and added to body');
        
        // Add close button
        const closeButton = document.createElement('div');
        closeButton.className = 'popup-close';
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        closeButton.style.position = 'absolute';
        closeButton.style.top = '15px';
        closeButton.style.right = '15px';
        closeButton.style.background = 'white';
        closeButton.style.width = '30px';
        closeButton.style.height = '30px';
        closeButton.style.borderRadius = '50%';
        closeButton.style.display = 'flex';
        closeButton.style.alignItems = 'center';
        closeButton.style.justifyContent = 'center';
        closeButton.style.cursor = 'pointer';
        closeButton.style.zIndex = '10000';
        closeButton.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        
        popupContainer.appendChild(closeButton);
        
        // Handle close button click
        closeButton.addEventListener('click', function() {
            closeAppointmentPopup();
        });
        
        // Close popup when clicking outside content
        popupContainer.addEventListener('click', function(e) {
            if (e.target === popupContainer) {
                closeAppointmentPopup();
            }
        });
    } else {
        console.log('Popup container already exists:', popupContainer);
    }
    
    // Get all appointment buttons
    const appointmentButtons = document.querySelectorAll('[id^="book-appointment-btn"]');
    
    console.log('Found appointment buttons:', appointmentButtons.length);
    
    // Add click event to all appointment buttons
    appointmentButtons.forEach(button => {
        console.log('Adding click event to button:', button);
        button.addEventListener('click', function(e) {
            console.log('Appointment button clicked');
            e.preventDefault();
            openAppointmentPopup();
            return false;
        });
    });
    
    // Define global functions to open and close popup
    window.openAppointmentPopup = function() {
        console.log('Opening appointment popup');
        
        // Load appointment form content if empty
        if (popupContainer.children.length <= 1) { // Only has close button
            console.log('Fetching appointment form');
            fetchAppointmentForm();
        } else {
            console.log('Form already loaded, container has', popupContainer.children.length, 'children');
        }
        
        // Show popup with inline styles to ensure it works everywhere
        popupContainer.classList.add('active');
        popupContainer.style.display = 'flex';
        popupContainer.style.justifyContent = 'center';
        popupContainer.style.alignItems = 'center';
        popupContainer.style.position = 'fixed';
        popupContainer.style.top = '0';
        popupContainer.style.left = '0';
        popupContainer.style.right = '0';
        popupContainer.style.bottom = '0';
        popupContainer.style.width = '100%';
        popupContainer.style.height = '100%';
        popupContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        popupContainer.style.zIndex = '9999';
        popupContainer.style.overflow = 'auto';
        popupContainer.style.padding = '20px';
        
        console.log('Added active class to popup container');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    };
    
    window.closeAppointmentPopup = function() {
        console.log('Closing appointment popup');
        popupContainer.classList.remove('active');
        popupContainer.style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
    };
    
    // Function to check if appointment CSS is loaded and add it if needed
    function ensureAppointmentCssLoaded() {
        // Check if our CSS is loaded
        let cssLoaded = false;
        const linkElements = document.querySelectorAll('link[rel="stylesheet"]');
        console.log('Found', linkElements.length, 'linked stylesheets:');
        
        linkElements.forEach((link, index) => {
            const href = link.getAttribute('href');
            console.log(`${index + 1}: ${href}`);
            if (href && href.includes('appointment-popup.css')) {
                cssLoaded = true;
                console.log('Appointment CSS already loaded');
            }
        });
        
        if (!cssLoaded) {
            console.log('Appointment CSS not found, adding it dynamically');
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = 'css/appointment-popup.css';
            document.head.appendChild(cssLink);
            console.log('Added appointment-popup.css to head');
            
            // Also add the essential styles directly
            const styleEl = document.createElement('style');
            styleEl.textContent = `
                #appointment-popup-container.appointment-popup {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.7);
                    z-index: 9999;
                    overflow-y: auto;
                    justify-content: center;
                    align-items: center;
                    padding: 40px 0;
                }
                
                #appointment-popup-container.appointment-popup.active {
                    display: flex !important;
                }
                
                #appointment-popup-container .popup-close {
                    position: absolute;
                    top: 15px;
                    right: 15px;
                    background: white;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    z-index: 10000;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                }
                
                #appointment-popup-container .appointment-container,
                #appointment-popup-container .popup-appointment-container {
                    max-width: 500px;
                    margin: auto;
                    padding: 20px;
                    background: #fff;
                    border-radius: 10px;
                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                    max-height: 80vh;
                    overflow-y: auto;
                    width: 90%;
                    box-sizing: border-box;
                }
                
                #appointment-popup-container .form-group {
                    margin-bottom: 12px;
                }
            `;
            document.head.appendChild(styleEl);
            console.log('Added essential CSS styles directly to head');
        }
    }
    
    // Function to fetch appointment form
    function fetchAppointmentForm() {
        // Get the base URL for the proper path to appointment.php
        const getBaseUrl = () => {
            // Get the current protocol and hostname
            const protocol = window.location.protocol;
            const host = window.location.host;
            
            // Get site path from current URL
            const pathArray = window.location.pathname.split('/');
            let basePath = '';
            
            // If we're in a subdirectory like 'pages', we need to go up one level
            if (pathArray.length > 2 && pathArray[1] !== '') {
                // We're in a subdirectory, go up one level
                if (pathArray[1] === 'pages') {
                    basePath = '/';  // Go up one level without hardcoding gk_lab
                } else {
                    basePath = '/' + pathArray[1] + '/';  // Keep the current parent directory
                }
            } else {
                // We're at the root level of the site
                basePath = '/';
            }
            
            return protocol + '//' + host + basePath;
        };
        
        // Get the absolute URL for appointment.php
        const appointmentUrl = 'appointment.php';
        
        console.log('Appointment URL:', appointmentUrl);
        
        // Set a flag to bypass the fetch for debugging
        const useFallbackForm = true;
        
        if (useFallbackForm) {
            console.log('Using fallback inline form');
            // First fetch the CSRF token
            fetch(appointmentUrl)
                .then(response => response.json())
                .then(data => {
                    console.log('Received CSRF token:', data.csrf_token);
                    // Create a direct inline form for reliability with CSRF token
                    const inlineForm = document.createElement('div');
                    inlineForm.className = 'appointment-container popup-appointment-container';
                    inlineForm.style.backgroundColor = '#fff';
                    inlineForm.style.padding = '20px';
                    inlineForm.style.borderRadius = '10px';
                    inlineForm.style.maxWidth = '500px';
                    inlineForm.style.width = '90%';
                    inlineForm.style.maxHeight = '80vh';
                    inlineForm.style.overflow = 'auto';
                    inlineForm.style.position = 'relative';
                    inlineForm.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.2)';
                    inlineForm.style.margin = 'auto';
                    
                    inlineForm.innerHTML = `
                        <div class="form-header" style="margin-bottom: 20px; text-align: center;">
                            <h2 style="color: #16A085; font-size: 24px; margin-bottom: 10px;">Book Your Appointment</h2>
                            <p style="color: #666; font-size: 14px;">Fill the form below to schedule your home sample collection</p>
                        </div>
                        
                        <div id="alerts-container"></div>
                        
                        <form id="appointment-form" method="post">
                            <!-- Hidden CSRF token field -->
                            <input type="hidden" name="csrf_token" value="${data.csrf_token || ''}">
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Full Name *</label>
                                <input type="text" name="name" class="form-input" required 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;">
                            </div>
                            
                            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email Address *</label>
                                    <input type="email" name="email" class="form-input" required 
                                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Phone Number *</label>
                                    <input type="tel" name="phone" class="form-input" required 
                                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;">
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Preferred Date *</label>
                                    <input type="date" name="date" class="form-input" required 
                                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Preferred Time *</label>
                                    <select name="time_slot" class="form-select" required 
                                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=&quot;%23888&quot; height=&quot;24&quot; viewBox=&quot;0 0 24 24&quot; width=&quot;24&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;><path d=&quot;M7 10l5 5 5-5z&quot;/><path d=&quot;M0 0h24v24H0z&quot; fill=&quot;none&quot;/></svg>'); background-position: right 10px center; background-repeat: no-repeat;">
                                        <option value="">Select Time</option>
                                        <option value="07:00 - 09:00">07:00 - 09:00</option>
                                        <option value="09:00 - 11:00">09:00 - 11:00</option>
                                        <option value="11:00 - 13:00">11:00 - 13:00</option>
                                        <option value="13:00 - 15:00">13:00 - 15:00</option>
                                        <option value="15:00 - 17:00">15:00 - 17:00</option>
                                        <option value="17:00 - 19:00">17:00 - 19:00</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Test Type *</label>
                                <select name="test_type" class="form-select" required 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=&quot;%23888&quot; height=&quot;24&quot; viewBox=&quot;0 0 24 24&quot; width=&quot;24&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;><path d=&quot;M7 10l5 5 5-5z&quot;/><path d=&quot;M0 0h24v24H0z&quot; fill=&quot;none&quot;/></svg>'); background-position: right 10px center; background-repeat: no-repeat;">
                                    <option value="">Select Test Type</option>
                                    <option value="Individual Test">Individual Test</option>
                                    <option value="Full Body Checkup - Essential">Full Body Checkup - Essential</option>
                                    <option value="Full Body Checkup - Advanced">Full Body Checkup - Advanced</option>
                                    <option value="Full Body Checkup - Comprehensive">Full Body Checkup - Comprehensive</option>
                                    <option value="Cardiac Health Checkup">Cardiac Health Checkup</option>
                                    <option value="Diabetes Screening">Diabetes Screening</option>
                                    <option value="Thyroid Profile">Thyroid Profile</option>
                                    <option value="Women's Health Checkup">Women's Health Checkup</option>
                                    <option value="Men's Health Checkup">Men's Health Checkup</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Collection Address *</label>
                                <textarea name="address" class="form-input" rows="2" required 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;"></textarea>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Additional Notes</label>
                                <textarea name="notes" class="form-input" rows="2" 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" 
                                    style="display: block; width: 100%; padding: 12px; background: #16A085; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer;">
                                    Submit Appointment Request
                                </button>
                            </div>
                        </form>
                    `;
                    
                    // Clear previous content (except close button)
                    Array.from(popupContainer.children).forEach(child => {
                        if (!child.classList.contains('popup-close')) {
                            child.remove();
                        }
                    });
                    
                    // Add form to popup
                    popupContainer.appendChild(inlineForm);
                    
                    // Add submit event listener to the form
                    const form = popupContainer.querySelector('#appointment-form');
                    const alertsContainer = popupContainer.querySelector('#alerts-container');
                    
                    if (form) {
                        console.log('Found form element, adding submit handler');
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            console.log('Form submitted');
                            
                            // Show processing message
                            alertsContainer.innerHTML = `
                                <div style="padding: 15px; background-color: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 15px;">
                                    Processing your request... Please wait.
                                </div>
                            `;
                            
                            // Get form data
                            const formData = new FormData(form);
                            
                            // Send AJAX request to appointment.php with the correct URL
                            fetch(appointmentUrl, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Form submission response:', data);
                                
                                // Update CSRF token for subsequent requests
                                if (data.csrf_token) {
                                    const csrfTokenInput = form.querySelector('input[name="csrf_token"]');
                                    if (csrfTokenInput) {
                                        csrfTokenInput.value = data.csrf_token;
                                    }
                                }
                                
                                if (data.success) {
                                    // Show success message
                                    alertsContainer.innerHTML = `
                                        <div style="padding: 15px; background-color: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 15px;">
                                            ${data.message}
                                        </div>
                                    `;
                                    
                                    // Reset form but keep CSRF token
                                    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
                                    form.reset();
                                    form.querySelector('input[name="csrf_token"]').value = csrfToken;
                                    
                                    // Close popup after 3 seconds
                                    setTimeout(() => {
                                        closeAppointmentPopup();
                                    }, 3000);
                                } else {
                                    // Show error messages
                                    const errorHtml = data.errors.map(error => `<div>${error}</div>`).join('');
                                    alertsContainer.innerHTML = `
                                        <div style="padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin-bottom: 15px;">
                                            ${errorHtml}
                                        </div>
                                    `;
                                }
                            })
                            .catch(error => {
                                console.error('Error submitting form:', error);
                                alertsContainer.innerHTML = `
                                    <div style="padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin-bottom: 15px;">
                                        An unexpected error occurred. Please try again.
                                    </div>
                                `;
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching CSRF token:', error);
                    // Handle error by showing error message in popup
                    const errorContainer = document.createElement('div');
                    errorContainer.className = 'appointment-container popup-appointment-container';
                    errorContainer.style.backgroundColor = '#fff';
                    errorContainer.style.padding = '20px';
                    errorContainer.style.borderRadius = '10px';
                    errorContainer.style.maxWidth = '500px';
                    errorContainer.style.width = '90%';
                    errorContainer.style.maxHeight = '80vh';
                    errorContainer.style.overflow = 'auto';
                    errorContainer.style.position = 'relative';
                    errorContainer.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.2)';
                    errorContainer.style.margin = 'auto';
                    
                    errorContainer.innerHTML = `
                        <div class="form-header" style="margin-bottom: 20px; text-align: center;">
                            <h2 style="color: #16A085; font-size: 24px; margin-bottom: 10px;">Book Your Appointment</h2>
                        </div>
                        
                        <div style="padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin-bottom: 15px;">
                            Error loading appointment form. Please try again or visit the appointment page directly.
                        </div>
                        
                        <a href="${appointmentUrl}" class="btn-primary" style="display:block; width: 100%; padding: 12px; background: #16A085; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer; text-align:center; text-decoration: none;">
                            Go to Appointment Page
                        </a>
                    `;
                    
                    // Clear previous content (except close button)
                    Array.from(popupContainer.children).forEach(child => {
                        if (!child.classList.contains('popup-close')) {
                            child.remove();
                        }
                    });
                    
                    // Add error message to popup
                    popupContainer.appendChild(errorContainer);
                });
            
            return;
        }
        
        // Original fetch code continues below
        // Get the current page path to determine relative path to appointment.php
        const currentPath = window.location.pathname;
        // Check if file is in the root directory or in a subdirectory
        const inSubdirectory = currentPath.split('/').filter(Boolean).length > 1 && !currentPath.endsWith('index.html');
        const appointmentPath = inSubdirectory ? '../appointment.php' : 'appointment.php';
        
        console.log('Current path:', currentPath);
        console.log('Fetching form from:', appointmentPath);
        
        fetch(appointmentPath)
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(html => {
                console.log('Form HTML received, length:', html.length);
                // Extract only the appointment container from the response
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                console.log('Parsing HTML document...');
                console.log('Document body:', doc.body.innerHTML.substring(0, 200) + '...');
                
                // Look for the appointment container
                let formContainer = doc.querySelector('.appointment-container');
                
                // If not found, try alternative selectors
                if (!formContainer) {
                    console.log('Could not find .appointment-container, trying alternative selectors');
                    formContainer = doc.querySelector('form#appointment-form');
                    
                    if (formContainer) {
                        console.log('Found form directly, wrapping it');
                        const wrapper = document.createElement('div');
                        wrapper.className = 'appointment-container';
                        wrapper.innerHTML = formContainer.outerHTML;
                        formContainer = wrapper;
                    } else {
                        console.log('No form found either, using body content');
                        formContainer = document.createElement('div');
                        formContainer.className = 'appointment-container';
                        formContainer.innerHTML = `
                            <div class="form-header">
                                <h2>Book Your Appointment</h2>
                                <p>Fill the form to schedule your home sample collection</p>
                            </div>
                            <div class="alert alert-error">
                                Failed to load the appointment form. Please try again or visit the appointment page directly.
                            </div>
                            <a href="${appointmentUrl}" class="btn-primary" style="display:block; text-align:center; margin-top:15px;">
                                Go to Appointment Page
                            </a>
                        `;
                    }
                }
                
                if (formContainer) {
                    console.log('Found form container in parsed HTML');
                    // Clear previous content (except close button)
                    Array.from(popupContainer.children).forEach(child => {
                        if (!child.classList.contains('popup-close')) {
                            child.remove();
                        }
                    });
                    
                    // Add form to popup with classes for styling
                    const compactContainer = formContainer.cloneNode(true);
                    compactContainer.classList.add('popup-appointment-container');
                    console.log('Added popup-appointment-container class to the form container');
                    
                    // Set textarea rows to 2 for more compact appearance
                    const textareas = compactContainer.querySelectorAll('textarea');
                    textareas.forEach(textarea => {
                        if (textarea.rows > 2) {
                            textarea.rows = 2;
                        }
                    });
                    
                    // Apply additional inline styles to ensure proper display 
                    const formGroups = compactContainer.querySelectorAll('.form-group');
                    formGroups.forEach(group => {
                        group.style.marginBottom = '12px';
                    });
    
                    const formInputs = compactContainer.querySelectorAll('.form-input, .form-select');
                    formInputs.forEach(input => {
                        input.style.padding = '8px 12px';
                        input.style.width = '100%';
                        input.style.boxSizing = 'border-box';
                    });
                    
                    popupContainer.appendChild(compactContainer);
                    console.log('Form added to popup container');
                    
                    // Add submit event listener to the form
                    const form = popupContainer.querySelector('#appointment-form');
                    const alertsContainer = popupContainer.querySelector('#alerts-container');
                    
                    if (form) {
                        console.log('Found form element, adding submit handler');
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            console.log('Form submitted');
                            
                            // Clear previous alerts
                            if (alertsContainer) {
                                alertsContainer.innerHTML = '';
                            }
                            
                            // Get form data
                            const formData = new FormData(form);
                            
                            // Send AJAX request to correct URL
                            fetch(appointmentUrl, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Form submission response:', data);
                                if (data.success) {
                                    // Show success message
                                    alertsContainer.innerHTML = `
                                        <div class="alert alert-success">
                                            ${data.message}
                                        </div>
                                    `;
                                    
                                    // Reset form
                                    form.reset();
                                    
                                    // Close popup after 3 seconds
                                    setTimeout(() => {
                                        closeAppointmentPopup();
                                    }, 3000);
                                } else {
                                    // Show error messages
                                    const errorHtml = data.errors.map(error => `<div>${error}</div>`).join('');
                                    alertsContainer.innerHTML = `
                                        <div class="alert alert-error">
                                            ${errorHtml}
                                        </div>
                                    `;
                                }
                            })
                            .catch(error => {
                                console.error('Error submitting form:', error);
                                alertsContainer.innerHTML = `
                                    <div class="alert alert-error">
                                        An unexpected error occurred. Please try again.
                                    </div>
                                `;
                            });
                        });
                    } else {
                        console.error('Form element not found in loaded content');
                    }
                } else {
                    console.error('Could not find appointment container in the fetched HTML');
                }
            })
            .catch(error => {
                console.error('Error loading appointment form:', error);
                popupContainer.innerHTML = `
                    <div class="popup-close"><i class="fas fa-times"></i></div>
                    <div class="appointment-container popup-appointment-container">
                        <div class="alert alert-error">
                            Error loading appointment form. Please try again.
                        </div>
                    </div>
                `;
                
                // Re-add close button event listener
                popupContainer.querySelector('.popup-close').addEventListener('click', function() {
                    closeAppointmentPopup();
                });
            });
    }
});