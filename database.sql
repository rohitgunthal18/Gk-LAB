-- GK Lab SQL Schema
-- This file creates the entire database structure and inserts initial data for the GK Lab website.

-- 1. Create database
CREATE DATABASE IF NOT EXISTS gk_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gk_lab;

-- 2. Create users table
CREATE TABLE IF NOT EXISTS users (
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
);

-- 3. Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Create tests table
CREATE TABLE IF NOT EXISTS tests (
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
);

-- 5. Create test_parameters table
CREATE TABLE IF NOT EXISTS test_parameters (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    test_id INT(11) NOT NULL,
    parameter_name VARCHAR(100) NOT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    normal_range VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
);

-- 6. Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11),
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. Create cart_items table
CREATE TABLE IF NOT EXISTS cart_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    cart_id INT(11) NOT NULL,
    test_id INT(11) NOT NULL,
    quantity INT(5) DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
);

-- 8. Create orders table
CREATE TABLE IF NOT EXISTS orders (
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
);

-- 9. Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
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
);

-- 10. Create appointments table
CREATE TABLE IF NOT EXISTS appointments (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- 11. Create test_reports table
CREATE TABLE IF NOT EXISTS test_reports (
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
);

-- 12. Create features table
CREATE TABLE IF NOT EXISTS features (
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
);

-- 13. Create user_addresses table
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(20) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 14. Create coupons table
CREATE TABLE IF NOT EXISTS coupons (
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
);

-- 15. Create user_coupons table
CREATE TABLE IF NOT EXISTS user_coupons (
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
);

-- 16. Create coupon_usage table
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    order_id INT(11) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 17. Create checkups table
CREATE TABLE IF NOT EXISTS checkups (
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
);

-- 18. Create checkup_items table
CREATE TABLE IF NOT EXISTS checkup_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    checkup_id INT(11) NOT NULL,
    test_id INT(11),
    parameter_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (checkup_id) REFERENCES checkups(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE SET NULL
);

-- 19. Create contact_messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 20. Insert default categories
INSERT IGNORE INTO categories (name, slug, description) VALUES
    ('Full Body', 'full-body', 'Comprehensive health assessment packages'),
    ('Diabetes', 'diabetes', 'Tests related to diabetes screening and monitoring'),
    ('Heart', 'heart', 'Tests related to heart health and cardiac risk assessment'),
    ('Liver', 'liver', 'Tests to assess liver function and health'),
    ('Kidney', 'kidney', 'Tests to assess kidney function and health'),
    ('Women', 'women', 'Health checkups tailored for women'),
    ('Senior Citizen', 'senior-citizen', 'Health checkups designed for elderly patients');

-- 21. Insert admin user (password: admin123, hashed)
INSERT IGNORE INTO users (first_name, last_name, email, password, phone, role) VALUES
    ('Admin', 'User', 'admin@gklab.com', '$2y$10$wH8Qw8Qw8Qw8Qw8Qw8Qw8uQw8Qw8Qw8Qw8Qw8Qw8Qw8Qw8Qw8Qw8', '9876543210', 'admin');

-- 22. Insert sample coupons
INSERT IGNORE INTO coupons (code, description, discount_type, discount_value, min_order_value, max_discount_amount, start_date, end_date, is_first_order_only, status)
VALUES 
('WELCOME20', 'Get 20% off on your first order', 'percentage', 20, 500, 1000, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1, 'active'),
('FIRSTORDER', 'Get ₹200 off on your first order', 'fixed', 200, 1000, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1, 'active');

-- 23. Create appointments_view for admin dashboard compatibility
-- CREATE OR REPLACE VIEW appointments_view AS 
--     SELECT 
--         id,
--         user_id,
--         order_id,
--         patient_name as name,
--         patient_email as email,
--         patient_phone as phone,
--         appointment_date,
--         appointment_time,
--         time_slot,
--         test_type,
--         sample_collection_address,
--         additional_notes,
--         appointment_status,
--         created_at,
--         updated_at
--     FROM appointments; 