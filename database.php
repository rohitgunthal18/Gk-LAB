<?php
/**
 * GK Lab Comprehensive Database Setup Script
 * 
 * This is the DEFINITIVE setup file for the entire GK Lab database.
 * Execute this script to create and configure all required database tables.
 * 
 * IMPORTANT: 
 * - This file consolidates all database structure in one place.
 * - No other setup scripts should be needed after running this file.
 * - Database connection settings are defined in connection.php.
 *   Always update connection settings there instead of modifying them here.
 */

// Database connection parameters
// NOTE: These should match the settings in connection.php
$host = "localhost";
$username = "root";
$password = "";
$database = "gk_lab";

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>GK Lab - Complete Database Setup</h1>";
echo "<p>Setting up and configuring all database tables for GK Lab website...</p>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

echo "<div class='success'>Database created successfully</div>";

// Select the database
$conn->select_db($database);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating users table: " . $conn->error);
}

echo "Users table created successfully<br>";

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating categories table: " . $conn->error);
}

echo "Categories table created successfully<br>";

// Create tests table
$sql = "CREATE TABLE IF NOT EXISTS tests (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11),
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    short_description TEXT,
    original_price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2) NOT NULL,
    discount_percentage INT(3),
    parameters_count INT(5),
    report_time VARCHAR(50),
    test_type VARCHAR(100),
    fasting_required VARCHAR(100),
    sample_type VARCHAR(100),
    age_group VARCHAR(100),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating tests table: " . $conn->error);
}

echo "Tests table created successfully<br>";

// Create test_parameters table with all needed columns
$sql = "CREATE TABLE IF NOT EXISTS test_parameters (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    test_id INT(11) NOT NULL,
    parameter_name VARCHAR(100) NOT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    normal_range VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating test_parameters table: " . $conn->error);
}

echo "Test parameters table created successfully<br>";

// Add columns to test_parameters table if they don't exist
$result = $conn->query("SHOW COLUMNS FROM test_parameters LIKE 'unit'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE test_parameters ADD COLUMN unit VARCHAR(50) DEFAULT NULL AFTER parameter_name";
    if ($conn->query($sql) === TRUE) {
        echo "Added unit column to test_parameters table<br>";
    } else {
        echo "Error adding unit column: " . $conn->error . "<br>";
    }
}

$result = $conn->query("SHOW COLUMNS FROM test_parameters LIKE 'normal_range'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE test_parameters ADD COLUMN normal_range VARCHAR(100) DEFAULT NULL AFTER unit";
    if ($conn->query($sql) === TRUE) {
        echo "Added normal_range column to test_parameters table<br>";
    } else {
        echo "Error adding normal_range column: " . $conn->error . "<br>";
    }
}

$result = $conn->query("SHOW COLUMNS FROM test_parameters LIKE 'description'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE test_parameters ADD COLUMN description TEXT DEFAULT NULL AFTER normal_range";
    if ($conn->query($sql) === TRUE) {
        echo "Added description column to test_parameters table<br>";
    } else {
        echo "Error adding description column: " . $conn->error . "<br>";
    }
}

// Create cart table
$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11),
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating cart table: " . $conn->error);
}

echo "Cart table created successfully<br>";

// Create cart_items table
$sql = "CREATE TABLE IF NOT EXISTS cart_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    cart_id INT(11) NOT NULL,
    test_id INT(11) NOT NULL,
    quantity INT(5) DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating cart_items table: " . $conn->error);
}

echo "Cart items table created successfully<br>";

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_id INT(11),
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    customer_address TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'pending',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    billing_first_name VARCHAR(50),
    billing_last_name VARCHAR(50),
    billing_email VARCHAR(100),
    billing_phone VARCHAR(20),
    billing_address TEXT,
    billing_city VARCHAR(50),
    billing_state VARCHAR(50),
    billing_pincode VARCHAR(10),
    appointment_date DATE,
    appointment_time TIME,
    additional_notes TEXT,
    coupon_code VARCHAR(50),
    coupon_discount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating orders table: " . $conn->error);
}

echo "Orders table created successfully<br>";

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    item_id VARCHAR(50) NOT NULL,
    test_id INT(11),
    item_name VARCHAR(100) NOT NULL,
    item_type VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT(5) DEFAULT 1,
    item_total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating order_items table: " . $conn->error);
}

echo "Order items table created successfully<br>";

// Create appointments table with all required fields
echo "<h3>Setting up appointments table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,
    order_id INT(11) NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_email VARCHAR(100) NOT NULL,
    patient_phone VARCHAR(20) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NULL,
    time_slot VARCHAR(50) NULL,
    test_type VARCHAR(100) NOT NULL,
    sample_collection_address TEXT NULL,
    additional_notes TEXT NULL,
    appointment_status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    echo "<div class='error'>Error creating appointments table: " . $conn->error . "</div>";
} else {
    echo "<div class='success'>Appointments table created successfully</div>";
}

// Check appointments table structure and ensure backward compatibility
$tableExists = $conn->query("SHOW TABLES LIKE 'appointments'")->num_rows > 0;
if ($tableExists) {
    echo "<h4>Checking appointments table structure...</h4>";
    
    // Check for column name conflicts and fix
    $hasPatientName = $conn->query("SHOW COLUMNS FROM appointments LIKE 'patient_name'")->num_rows > 0;
    $hasName = $conn->query("SHOW COLUMNS FROM appointments LIKE 'name'")->num_rows > 0;
    
    // Fix patient_name/name duplication if both exist
    if ($hasPatientName && $hasName) {
        echo "Both 'patient_name' and 'name' columns exist. Removing duplicate 'name' column.<br>";
        if ($conn->query("ALTER TABLE appointments DROP COLUMN name")) {
            echo "<div class='success'>Removed duplicate 'name' column</div>";
        } else {
            echo "<div class='error'>Error removing 'name' column: " . $conn->error . "</div>";
        }
    }
    // If only name exists but not patient_name, rename it
    else if (!$hasPatientName && $hasName) {
        echo "Renaming 'name' column to 'patient_name'<br>";
        if ($conn->query("ALTER TABLE appointments CHANGE name patient_name VARCHAR(100) NOT NULL")) {
            echo "<div class='success'>Renamed 'name' to 'patient_name'</div>";
        } else {
            echo "<div class='error'>Error renaming column: " . $conn->error . "</div>";
        }
    }
    // If neither exists, add patient_name
    else if (!$hasPatientName && !$hasName) {
        echo "Adding missing 'patient_name' column<br>";
        if ($conn->query("ALTER TABLE appointments ADD COLUMN patient_name VARCHAR(100) NOT NULL AFTER order_id")) {
            echo "<div class='success'>Added 'patient_name' column</div>";
        } else {
            echo "<div class='error'>Error adding column: " . $conn->error . "</div>";
        }
    }
    
    // Do similar checks for email
    $hasPatientEmail = $conn->query("SHOW COLUMNS FROM appointments LIKE 'patient_email'")->num_rows > 0;
    $hasEmail = $conn->query("SHOW COLUMNS FROM appointments LIKE 'email'")->num_rows > 0;
    
    if ($hasPatientEmail && $hasEmail) {
        echo "Both 'patient_email' and 'email' columns exist. Removing duplicate 'email' column.<br>";
        if ($conn->query("ALTER TABLE appointments DROP COLUMN email")) {
            echo "<div class='success'>Removed duplicate 'email' column</div>";
        } else {
            echo "<div class='error'>Error removing 'email' column: " . $conn->error . "</div>";
        }
    }
    else if (!$hasPatientEmail && $hasEmail) {
        echo "Renaming 'email' column to 'patient_email'<br>";
        if ($conn->query("ALTER TABLE appointments CHANGE email patient_email VARCHAR(100) NOT NULL")) {
            echo "<div class='success'>Renamed 'email' to 'patient_email'</div>";
        } else {
            echo "<div class='error'>Error renaming column: " . $conn->error . "</div>";
        }
    }
    else if (!$hasPatientEmail && !$hasEmail) {
        echo "Adding missing 'patient_email' column<br>";
        if ($conn->query("ALTER TABLE appointments ADD COLUMN patient_email VARCHAR(100) NOT NULL AFTER patient_name")) {
            echo "<div class='success'>Added 'patient_email' column</div>";
        } else {
            echo "<div class='error'>Error adding column: " . $conn->error . "</div>";
        }
    }
    
    // And for phone
    $hasPatientPhone = $conn->query("SHOW COLUMNS FROM appointments LIKE 'patient_phone'")->num_rows > 0;
    $hasPhone = $conn->query("SHOW COLUMNS FROM appointments LIKE 'phone'")->num_rows > 0;
    
    if ($hasPatientPhone && $hasPhone) {
        echo "Both 'patient_phone' and 'phone' columns exist. Removing duplicate 'phone' column.<br>";
        if ($conn->query("ALTER TABLE appointments DROP COLUMN phone")) {
            echo "<div class='success'>Removed duplicate 'phone' column</div>";
        } else {
            echo "<div class='error'>Error removing 'phone' column: " . $conn->error . "</div>";
        }
    }
    else if (!$hasPatientPhone && $hasPhone) {
        echo "Renaming 'phone' column to 'patient_phone'<br>";
        if ($conn->query("ALTER TABLE appointments CHANGE phone patient_phone VARCHAR(20) NOT NULL")) {
            echo "<div class='success'>Renamed 'phone' to 'patient_phone'</div>";
        } else {
            echo "<div class='error'>Error renaming column: " . $conn->error . "</div>";
        }
    }
    else if (!$hasPatientPhone && !$hasPhone) {
        echo "Adding missing 'patient_phone' column<br>";
        if ($conn->query("ALTER TABLE appointments ADD COLUMN patient_phone VARCHAR(20) NOT NULL AFTER patient_email")) {
            echo "<div class='success'>Added 'patient_phone' column</div>";
        } else {
            echo "<div class='error'>Error adding column: " . $conn->error . "</div>";
        }
    }
    
    // Check for time_slot versus appointment_time duplication
    $hasTimeSlot = $conn->query("SHOW COLUMNS FROM appointments LIKE 'time_slot'")->num_rows > 0;
    $hasAppointmentTime = $conn->query("SHOW COLUMNS FROM appointments LIKE 'appointment_time'")->num_rows > 0;
    
    if (!$hasTimeSlot) {
        echo "Adding missing 'time_slot' column<br>";
        if ($conn->query("ALTER TABLE appointments ADD COLUMN time_slot VARCHAR(50) NULL AFTER appointment_time")) {
            echo "<div class='success'>Added 'time_slot' column</div>";
        } else {
            echo "<div class='error'>Error adding column: " . $conn->error . "</div>";
        }
    }
    
    // Check if test_type exists (previously might have been test_ids)
    $hasTestType = $conn->query("SHOW COLUMNS FROM appointments LIKE 'test_type'")->num_rows > 0;
    $hasTestIds = $conn->query("SHOW COLUMNS FROM appointments LIKE 'test_ids'")->num_rows > 0;
    
    if ($hasTestIds && !$hasTestType) {
        echo "Renaming 'test_ids' column to 'test_type'<br>";
        if ($conn->query("ALTER TABLE appointments CHANGE test_ids test_type VARCHAR(100) NOT NULL")) {
            echo "<div class='success'>Renamed 'test_ids' to 'test_type'</div>";
        } else {
            echo "<div class='error'>Error renaming column: " . $conn->error . "</div>";
        }
    }
    else if (!$hasTestType && !$hasTestIds) {
        echo "Adding missing 'test_type' column<br>";
        if ($conn->query("ALTER TABLE appointments ADD COLUMN test_type VARCHAR(100) NOT NULL AFTER time_slot")) {
            echo "<div class='success'>Added 'test_type' column</div>";
        } else {
            echo "<div class='error'>Error adding column: " . $conn->error . "</div>";
        }
    }
    
    // Check for address column variations
    $hasSampleAddress = $conn->query("SHOW COLUMNS FROM appointments LIKE 'sample_collection_address'")->num_rows > 0;
    $hasAddress = $conn->query("SHOW COLUMNS FROM appointments LIKE 'address'")->num_rows > 0;
    
    if (!$hasSampleAddress && $hasAddress) {
        echo "Renaming 'address' column to 'sample_collection_address'<br>";
        if ($conn->query("ALTER TABLE appointments CHANGE address sample_collection_address TEXT NULL")) {
            echo "<div class='success'>Renamed 'address' to 'sample_collection_address'</div>";
        } else {
            echo "<div class='error'>Error renaming column: " . $conn->error . "</div>";
        }
    }
    else if (!$hasSampleAddress && !$hasAddress) {
        echo "Adding missing 'sample_collection_address' column<br>";
        if ($conn->query("ALTER TABLE appointments ADD COLUMN sample_collection_address TEXT NULL AFTER test_type")) {
            echo "<div class='success'>Added 'sample_collection_address' column</div>";
        } else {
            echo "<div class='error'>Error adding column: " . $conn->error . "</div>";
        }
    }
    
    // Check for notes/additional_notes
    $hasAdditionalNotes = $conn->query("SHOW COLUMNS FROM appointments LIKE 'additional_notes'")->num_rows > 0;
    $hasNotes = $conn->query("SHOW COLUMNS FROM appointments LIKE 'notes'")->num_rows > 0;
    
    if (!$hasAdditionalNotes && $hasNotes) {
        echo "Renaming 'notes' column to 'additional_notes'<br>";
        if ($conn->query("ALTER TABLE appointments CHANGE notes additional_notes TEXT NULL")) {
            echo "<div class='success'>Renamed 'notes' to 'additional_notes'</div>";
        } else {
            echo "<div class='error'>Error renaming column: " . $conn->error . "</div>";
        }
    }
    else if (!$hasAdditionalNotes && !$hasNotes) {
        echo "Adding missing 'additional_notes' column<br>";
        if ($conn->query("ALTER TABLE appointments ADD COLUMN additional_notes TEXT NULL AFTER sample_collection_address")) {
            echo "<div class='success'>Added 'additional_notes' column</div>";
        } else {
            echo "<div class='error'>Error adding column: " . $conn->error . "</div>";
        }
    }
    
    // Fix nullable constraints
    $userIdRow = $conn->query("SHOW COLUMNS FROM appointments LIKE 'user_id'")->fetch_assoc();
    if ($userIdRow && $userIdRow['Null'] !== 'YES') {
        echo "Setting user_id to allow NULL values<br>";
        if ($conn->query("ALTER TABLE appointments MODIFY user_id INT(11) NULL")) {
            echo "<div class='success'>Updated user_id to allow NULL values</div>";
        } else {
            echo "<div class='error'>Error modifying column: " . $conn->error . "</div>";
        }
    }
    
    // Also do this for order_id
    $orderIdRow = $conn->query("SHOW COLUMNS FROM appointments LIKE 'order_id'")->fetch_assoc();
    if ($orderIdRow && $orderIdRow['Null'] !== 'YES') {
        echo "Setting order_id to allow NULL values<br>";
        if ($conn->query("ALTER TABLE appointments MODIFY order_id INT(11) NULL")) {
            echo "<div class='success'>Updated order_id to allow NULL values</div>";
        } else {
            echo "<div class='error'>Error modifying column: " . $conn->error . "</div>";
        }
    }
}

// Create admin-friendly compatibility view for appointments
// This helps the admin panel work with both old and new column naming
echo "<h3>Setting up compatibility with various column names...</h3>";
echo "<div class='success'>Skipping view creation for shared hosting compatibility</div>";

// Create a trigger to allow inserting NULL values for user_id and order_id
echo "<h4>Setting up appointment triggers...</h4>";
$drop_trigger = "DROP TRIGGER IF EXISTS before_appointment_insert";
$conn->query($drop_trigger);

// Try to create trigger but don't fail the whole setup if it can't be created
echo "<div>Attempting to create trigger (may not work on some shared hosting)...</div>";
try {
    $create_trigger = "
    CREATE TRIGGER before_appointment_insert
    BEFORE INSERT ON appointments
    FOR EACH ROW
    BEGIN
        IF NEW.user_id = 0 THEN
            SET NEW.user_id = NULL;
        END IF;
        IF NEW.order_id = 0 THEN
            SET NEW.order_id = NULL;
        END IF;
    END;
    ";

    if ($conn->query($create_trigger)) {
        echo "<div class='success'>Created trigger to convert 0 values to NULL for user_id and order_id</div>";
    } else {
        echo "<div class='warning'>Could not create trigger: " . $conn->error . ". This is normal on shared hosting.</div>";
    }
} catch (Exception $e) {
    echo "<div class='warning'>Could not create trigger due to hosting restrictions. This is normal on shared hosting.</div>";
}

// Create test_reports table
$sql = "CREATE TABLE IF NOT EXISTS test_reports (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    report_file VARCHAR(255),
    report_status ENUM('pending', 'processing', 'completed') DEFAULT 'pending',
    upload_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating test_reports table: " . $conn->error);
}

echo "Test reports table created successfully<br>";

// Create features table (for homepage slider)
$sql = "CREATE TABLE IF NOT EXISTS features (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    button_text VARCHAR(50),
    button_url VARCHAR(255),
    display_order INT(5) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating features table: " . $conn->error);
}

echo "Features table created successfully<br>";

// Create user_addresses table
$sql = "CREATE TABLE IF NOT EXISTS user_addresses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(20) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating user_addresses table: " . $conn->error . "<br>";
} else {
    echo "User addresses table created successfully<br>";
}

// Create coupons table
$sql = "CREATE TABLE IF NOT EXISTS coupons (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_value DECIMAL(10,2) DEFAULT 0,
    max_discount_amount DECIMAL(10,2) DEFAULT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_first_order_only TINYINT(1) DEFAULT 0,
    is_one_time_use TINYINT(1) DEFAULT 0,
    max_uses INT(11) DEFAULT NULL,
    current_uses INT(11) DEFAULT 0,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating coupons table: " . $conn->error . "<br>";
} else {
    echo "Coupons table created successfully<br>";
}

// Create user_coupons table
$sql = "CREATE TABLE IF NOT EXISTS user_coupons (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    coupon_id INT(11) NOT NULL,
    claimed_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    used_at DATETIME DEFAULT NULL,
    order_id INT(11) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_coupon (user_id, coupon_id)
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating user_coupons table: " . $conn->error . "<br>";
} else {
    echo "User coupons table created successfully<br>";
}

// Create coupon_usage table for tracking coupon usage in orders
$sql = "CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    order_id INT(11) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating coupon_usage table: " . $conn->error . "<br>";
} else {
    echo "Coupon usage table created successfully<br>";
}

// Insert default categories
$sql = "INSERT IGNORE INTO categories (name, slug, description) VALUES
    ('Full Body', 'full-body', 'Comprehensive health assessment packages'),
    ('Diabetes', 'diabetes', 'Tests related to diabetes screening and monitoring'),
    ('Heart', 'heart', 'Tests related to heart health and cardiac risk assessment'),
    ('Liver', 'liver', 'Tests to assess liver function and health'),
    ('Kidney', 'kidney', 'Tests to assess kidney function and health'),
    ('Women', 'women', 'Health checkups tailored for women'),
    ('Senior Citizen', 'senior-citizen', 'Health checkups designed for elderly patients')";

if ($conn->query($sql) === FALSE) {
    echo "Error inserting default categories: " . $conn->error . "<br>";
} else {
    echo "Default categories added successfully (skipping any duplicates)<br>";
}

// Create admin user
$defaultPassword = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (first_name, last_name, email, password, phone, role) VALUES
    ('Admin', 'User', 'admin@gklab.com', '$defaultPassword', '9876543210', 'admin')";

if ($conn->query($sql) === FALSE) {
    echo "Error creating admin user: " . $conn->error . "<br>";
} else {
    echo "Admin user created successfully (skipped if already exists)<br>";
}

// Insert sample coupons
$sql = "INSERT IGNORE INTO coupons (code, description, discount_type, discount_value, min_order_value, max_discount_amount, start_date, end_date, is_first_order_only, status)
VALUES 
('WELCOME20', 'Get 20% off on your first order', 'percentage', 20, 500, 1000, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1, 'active'),
('FIRSTORDER', 'Get ₹200 off on your first order', 'fixed', 200, 1000, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1, 'active')";

if ($conn->query($sql) === FALSE) {
    echo "Error inserting sample coupons: " . $conn->error . "<br>";
} else {
    echo "Sample coupons added successfully (skipping any duplicates)<br>";
}

// Create checkups table
$sql = "CREATE TABLE IF NOT EXISTS checkups (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    category_id INT(11),
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    short_description TEXT,
    original_price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2) NOT NULL,
    discount_percentage INT(3),
    parameters_count INT(5),
    report_time VARCHAR(50),
    fasting_required VARCHAR(100),
    sample_type VARCHAR(100),
    age_group VARCHAR(100),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating checkups table: " . $conn->error);
}

echo "Checkups table created successfully<br>";

// Create checkup_items table (to link checkups with tests)
$sql = "CREATE TABLE IF NOT EXISTS checkup_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    checkup_id INT(11) NOT NULL,
    test_id INT(11),
    parameter_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (checkup_id) REFERENCES checkups(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating checkup_items table: " . $conn->error);
}

echo "Checkup items table created successfully<br>";

echo "<div style='margin-top: 20px; padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;'>";
echo "<h3>Database setup completed successfully!</h3>";
echo "<p>All tables have been updated and the appointment system should now work without foreign key issues.</p>";
echo "<p>You can now use the appointment booking form without any constraint errors.</p>";
echo "</div>";

// Create contact_messages table
$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
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

if ($conn->query($sql) === FALSE) {
    echo "Error creating contact_messages table: " . $conn->error . "<br>";
} else {
    echo "Contact messages table created successfully<br>";
}

// Final success message
echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;'>";
echo "<h2>Database setup completed successfully!</h2>";
echo "<p>All tables have been created and updated according to the latest schema. The database is now ready for use.</p>";
echo "<p>You can now use all features of the GK Lab website without any database-related issues.</p>";
echo "</div>";

// Create a standalone connection script with the correct database parameters
echo "<h3>Creating connection.php file if needed...</h3>";
$connection_file = __DIR__ . '/connection.php';
$connection_script = '<?php
/**
 * Database Connection
 * 
 * This file establishes a connection to the MySQL database.
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "gk_lab";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
';

// Only create the file if it doesn't exist or is empty
if (!file_exists($connection_file) || filesize($connection_file) == 0) {
    if (file_put_contents($connection_file, $connection_script)) {
        echo "<div class='success'>Created connection.php file</div>";
    } else {
        echo "<div class='error'>Error creating connection.php file. Please create it manually.</div>";
    }
} else {
    echo "<div class='success'>connection.php file already exists</div>";
}

echo "<div style='margin-top: 20px; padding: 10px; background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; border-radius: 4px;'>";
echo "<h3>Next Steps</h3>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li><a href='index.html'>View the website homepage</a></li>";
echo "<li><a href='admin/index.php'>Access the admin dashboard</a></li>";
echo "<li><a href='setup_test_data.php'>Add test data to the database</a> (optional, for development only)</li>";
echo "</ul>";
echo "</div>";

// Add some simple CSS for the messages
echo "<style>
.success {
    padding: 8px 12px;
    margin: 5px 0;
    color: #155724;
    background-color: #d4edda;
    border-radius: 4px;
}
.error {
    padding: 8px 12px;
    margin: 5px 0;
    color: #721c24;
    background-color: #f8d7da;
    border-radius: 4px;
}
.warning {
    padding: 8px 12px;
    margin: 5px 0;
    color: #856404;
    background-color: #fff3cd;
    border-radius: 4px;
}
h3 {
    margin-top: 20px;
    margin-bottom: 10px;
    color: #0c5460;
}
h4 {
    margin-top: 15px;
    margin-bottom: 8px;
    color: #383d41;
}
</style>";

// Close connection
$conn->close();
?> 