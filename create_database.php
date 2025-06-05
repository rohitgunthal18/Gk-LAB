<?php
/**
 * Comprehensive Database Setup Script
 * 
 * This script creates all necessary tables for the GK Lab website
 */

// Include database connection
require_once 'connection.php';

// Function to log messages
function log_message($message) {
    echo "$message<br>";
}

log_message("<h1>GK Lab Database Setup</h1>");
log_message("<p>Setting up all required database tables...</p>");

// Create users table if not exists
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    address TEXT,
    user_role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_users) === TRUE) {
    log_message("Users table created or already exists");
} else {
    log_message("Error creating users table: " . $conn->error);
}

// Create contact_messages table if not exists
$sql_contact = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL, 
    phone VARCHAR(20),
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_contact) === TRUE) {
    log_message("Contact messages table created or already exists");
} else {
    log_message("Error creating contact_messages table: " . $conn->error);
}

// Create categories table if not exists
$sql_categories = "CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_categories) === TRUE) {
    log_message("Categories table created or already exists");
} else {
    log_message("Error creating categories table: " . $conn->error);
}

// Create tests table if not exists
$sql_tests = "CREATE TABLE IF NOT EXISTS tests (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category_id INT(11),
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    preparation TEXT,
    turnaround_time VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";

if ($conn->query($sql_tests) === TRUE) {
    log_message("Tests table created or already exists");
} else {
    log_message("Error creating tests table: " . $conn->error);
}

// Create checkups table if not exists
$sql_checkups = "CREATE TABLE IF NOT EXISTS checkups (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    included_tests TEXT,
    recommendation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_checkups) === TRUE) {
    log_message("Checkups table created or already exists");
} else {
    log_message("Error creating checkups table: " . $conn->error);
}

// Create appointments table if not exists
$sql_appointments = "CREATE TABLE IF NOT EXISTS appointments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_email VARCHAR(100) NOT NULL,
    patient_phone VARCHAR(20) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    test_ids TEXT,
    checkup_ids TEXT,
    notes TEXT,
    appointment_status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql_appointments) === TRUE) {
    log_message("Appointments table created or already exists");
} else {
    log_message("Error creating appointments table: " . $conn->error);
}

// Create orders table if not exists
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql_orders) === TRUE) {
    log_message("Orders table created or already exists");
} else {
    log_message("Error creating orders table: " . $conn->error);
}

// Create order_items table if not exists
$sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    item_type ENUM('test', 'checkup') NOT NULL,
    item_id INT(11) NOT NULL,
    quantity INT(5) NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

if ($conn->query($sql_order_items) === TRUE) {
    log_message("Order items table created or already exists");
} else {
    log_message("Error creating order_items table: " . $conn->error);
}

// Check if admin account exists, if not create one
$admin_check = $conn->query("SELECT id FROM users WHERE user_role = 'admin' LIMIT 1");
if ($admin_check->num_rows == 0) {
    // Create default admin user
    $admin_email = 'admin@gklab.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT); // Secure hash of password
    
    $sql_admin = "INSERT INTO users (email, password, first_name, last_name, user_role) 
                VALUES ('$admin_email', '$admin_password', 'GK', 'Admin', 'admin')";
                
    if ($conn->query($sql_admin) === TRUE) {
        log_message("Default admin account created (admin@gklab.com / admin123)");
    } else {
        log_message("Error creating admin account: " . $conn->error);
    }
}

// Close connection
$conn->close();

log_message("<h2>Database setup completed!</h2>");
log_message("<p>All tables have been created or updated successfully.</p>");
?> 