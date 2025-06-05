<?php
// Debug: Show errors during deployment (remove/comment out after debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If not accessed via AJAX or session is invalid, always return JSON error
if (php_sapi_name() !== 'cli' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    if (!isset($_SESSION)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Session error. Please refresh the page.']]);
        exit;
    }
}

// Generate CSRF token if one doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to log errors
function log_error($message, $error = null) {
    $log_file = 'appointment_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($error !== null) {
        $log_message .= " Error: " . print_r($error, true);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [
        'success' => false,
        'errors' => [],
        'message' => '',
        'csrf_token' => $_SESSION['csrf_token']
    ];
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        log_error("CSRF token validation failed");
        $response['errors'][] = "Security validation failed. Please try again.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Regenerate CSRF token after validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $response['csrf_token'] = $_SESSION['csrf_token'];
    
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $time_slot = isset($_POST['time_slot']) ? trim($_POST['time_slot']) : '';
    $test_type = isset($_POST['test_type']) ? trim($_POST['test_type']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Log the POST data
    log_error("Form submission data: " . json_encode($_POST));
    
    // Validate data
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($date)) $errors[] = "Date is required";
    if (empty($time_slot)) $errors[] = "Time slot is required";
    if (empty($test_type)) $errors[] = "Test type is required";
    if (empty($address)) $errors[] = "Address is required";
    
    // If no errors, save appointment
    if (empty($errors)) {
        try {
            // Include database connection (updated to use standard connection file)
            require_once 'connection.php';
        
            // Check if database connection is working
            if ($conn->connect_error) {
                log_error("Database connection failed", $conn->connect_error);
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            // Check if appointments table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'appointments'");
            if ($tableCheck->num_rows == 0) {
                // Table doesn't exist - run the setup script
                require_once 'create_database.php';
            }
            
            // Just check what columns exist without trying to modify the schema
            log_error("Checking existing appointments table structure");
            
            // Get all column names from the appointments table
            $columns = [];
            $columnsResult = $conn->query("SHOW COLUMNS FROM appointments");
            log_error("Found the following columns in appointments table:");
            while($column = $columnsResult->fetch_assoc()) {
                $columns[] = $column['Field'];
                log_error("- " . $column['Field']);
            }
            
            // Build the appropriate query based on existing columns
            log_error("Building query based on existing columns");
            
            // Use patient_name, patient_email, patient_phone as the standard column names
            $nameColumn = in_array('patient_name', $columns) ? 'patient_name' : 'name';
            $emailColumn = in_array('patient_email', $columns) ? 'patient_email' : 'email';  
            $phoneColumn = in_array('patient_phone', $columns) ? 'patient_phone' : 'phone';
            $dateColumn = 'appointment_date';
            $timeColumn = 'appointment_time';
            $addressColumn = in_array('sample_collection_address', $columns) ? 'sample_collection_address' : 'address';
            $notesColumn = in_array('additional_notes', $columns) ? 'additional_notes' : 'notes';
            
            // Build the query
            $insertColumns = [
                $nameColumn, 
                $emailColumn, 
                $phoneColumn, 
                $dateColumn, 
                $timeColumn, 
                'time_slot',
                'test_type', 
                $addressColumn,
                $notesColumn
            ];
            
            // Add status column if it exists
            if (in_array('appointment_status', $columns)) {
                $insertColumns[] = 'appointment_status';
            }
            
            // Get user ID if logged in
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            // Convert the time slot to a proper time format for appointment_time
            $appointmentTime = !empty($time_slot) ? substr($time_slot, 0, 5) . ':00' : null;
            
            // Build the final query with placeholders
            $placeholders = array_fill(0, count($insertColumns), '?');
            
            $sql = "INSERT INTO appointments (" . implode(', ', $insertColumns) . 
                   ($user_id && in_array('user_id', $columns) ? ', user_id' : '') . 
                   ") VALUES (" . implode(', ', $placeholders) . 
                   ($user_id && in_array('user_id', $columns) ? ", ?" : "") . ")";

            log_error("Final insert query: " . $sql);

            // Create prepared statement
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                log_error("Prepare failed: " . $conn->error);
                $response['errors'][] = "Database error: " . $conn->error;
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            
            // Prepare parameters array to match columns exactly
            $params = [
                $name,                // name/patient_name
                $email,               // email/patient_email
                $phone,               // phone/patient_phone
                $date,                // appointment_date
                $appointmentTime,     // appointment_time
                $time_slot,           // time_slot
                $test_type,           // test_type
                $address,             // address/sample_collection_address
                $notes                // notes/additional_notes
            ];
            
            // Add status if it exists in columns
            if (in_array('appointment_status', $columns)) {
                $params[] = 'pending';
            }
            
            // Set parameter types (all strings)
            $types = str_repeat('s', count($params));
            
            // Only add user_id if the column exists
            if ($user_id && in_array('user_id', $columns)) {
                $params[] = $user_id;
                $types .= 'i'; // Integer for user_id
            }
            
            log_error("Parameter types: " . $types);
            log_error("Parameter count: " . count($params));
            
            // Bind parameters
            $stmt->bind_param($types, ...$params);
        
        // Execute the query
        if ($stmt->execute()) {
                log_error("Appointment saved successfully for: " . $name);
                $response['success'] = true;
                $response['message'] = "Your appointment has been successfully scheduled. We'll contact you shortly to confirm.";
                
            // Return JSON response for AJAX
            header('Content-Type: application/json');
                echo json_encode($response);
            exit;
        } else {
                log_error("Execute statement failed", $stmt->error);
                throw new Exception("Failed to save appointment: " . $stmt->error);
            }
        } catch (Exception $e) {
            log_error("Exception occurred", $e->getMessage());
            $response['errors'][] = "Error booking appointment. Please try again. Error: " . $e->getMessage();
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    } else {
        log_error("Validation errors: " . json_encode($errors));
        $response['errors'] = $errors;
        
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Return CSRF token for GET requests (used for form initialization)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    header('Content-Type: application/json');
    echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Appointment form styles */
        .appointment-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .form-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-header h2 {
            color: var(--primary-green);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: var(--text-gray);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            border-color: var(--primary-green);
            outline: none;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .btn-primary {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #138D75;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        
        .alert-error {
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        
        /* Popup styles */
        .appointment-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            overflow-y: auto;
            justify-content: center;
            align-items: center;
        }
        
        .appointment-popup.active {
            display: flex;
        }
        
        .popup-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: var(--text-dark);
            cursor: pointer;
            z-index: 1001;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .appointment-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="appointment-container">
        <div class="form-header">
            <h2>Book Your Appointment</h2>
            <p>Fill the form below to schedule your home sample collection</p>
        </div>
        
        <div id="alerts-container"></div>
        
        <form id="appointment-form" method="post">
            <!-- Hidden CSRF token field will be added by JavaScript -->
            <input type="hidden" name="csrf_token" id="csrf_token" value="">
            
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-input" required>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" class="form-input" required>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="date">Preferred Date *</label>
                        <input type="date" id="date" name="date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="time_slot">Preferred Time *</label>
                        <select id="time_slot" name="time_slot" class="form-select" required>
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
            </div>
            
            <div class="form-group">
                <label class="form-label" for="test_type">Test Type *</label>
                <select id="test_type" name="test_type" class="form-select" required>
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
            
            <div class="form-group">
                <label class="form-label" for="address">Collection Address *</label>
                <textarea id="address" name="address" class="form-input" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" class="form-input" rows="2"></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">Submit Appointment Request</button>
            </div>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('appointment-form');
            const alertsContainer = document.getElementById('alerts-container');
            const csrfTokenInput = document.getElementById('csrf_token');
            
            // Fetch CSRF token when page loads
            fetch('appointment.php')
                .then(response => response.json())
                .then(data => {
                    if (data.csrf_token) {
                        csrfTokenInput.value = data.csrf_token;
                    }
                })
                .catch(error => {
                    console.error('Error fetching CSRF token:', error);
                    alertsContainer.innerHTML = `
                        <div class="alert alert-error">
                            Error initializing form. Please refresh the page.
                        </div>
                    `;
                });
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Clear previous alerts
                alertsContainer.innerHTML = '';
                
                // Show loading state
                const submitButton = form.querySelector('.btn-primary');
                const originalText = submitButton.textContent;
                submitButton.textContent = 'Processing...';
                submitButton.disabled = true;
                
                // Get form data
                const formData = new FormData(form);
                
                // Send AJAX request
                fetch('appointment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Update CSRF token
                    if (data.csrf_token) {
                        csrfTokenInput.value = data.csrf_token;
                    }
                    
                    if (data.success) {
                        // Show success message
                        alertsContainer.innerHTML = `
                            <div class="alert alert-success">
                                ${data.message}
                            </div>
                        `;
                        
                        // Reset form
                        form.reset();
                        csrfTokenInput.value = data.csrf_token;
                        
                        // Close popup after 3 seconds if in popup mode
                        if (window.closeAppointmentPopup) {
                            setTimeout(() => {
                                window.closeAppointmentPopup();
                            }, 3000);
                        }
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
                })
                .finally(() => {
                    // Restore button state
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                });
            });
        });
    </script>
</body>
</html> 