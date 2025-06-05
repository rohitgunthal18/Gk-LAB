<?php
/**
 * Settings Table Setup
 * 
 * This script creates the 'settings' table in the database if it doesn't exist already.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Set flash message
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'You do not have permission to access the admin area.'
    ];
    
    // Redirect to login page
    header('Location: ../../login.php');
    exit;
}

// Include database connection
include_once '../../config/db.php';

// Create settings table if it doesn't exist
$settings_table = "
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($settings_table) === TRUE) {
    $message = "Settings table created successfully or already exists.";
    $type = "success";
} else {
    $message = "Error creating settings table: " . $conn->error;
    $type = "error";
    
    // Set flash message
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    
    // Redirect to settings index
    header('Location: index.php');
    exit;
}

// Check if default data should be inserted
$insert_defaults = isset($_GET['defaults']) && $_GET['defaults'] == 'yes';

if ($insert_defaults) {
    // Default settings
    $default_settings = [
        // General settings
        ['site_name', 'GK Lab'],
        ['site_tagline', 'Diagnostic Services'],
        ['admin_email', 'admin@gklab.com'],
        
        // Contact information
        ['contact_email', 'info@gklab.com'],
        ['contact_phone', '+91 123 456 7890'],
        ['contact_address', '123 Main Street, Mumbai, Maharashtra 400001, India'],
        
        // Business hours
        ['business_days', 'Monday - Saturday'],
        ['business_hours', '8:00 AM - 8:00 PM'],
        
        // Social media links
        ['facebook_url', 'https://facebook.com/gklab'],
        ['twitter_url', 'https://twitter.com/gklab'],
        ['instagram_url', 'https://instagram.com/gklab'],
        ['linkedin_url', 'https://linkedin.com/company/gklab'],
        
        // Payment settings
        ['currency', 'INR'],
        ['payment_methods', 'cash,card,online,wallet'],
        
        // SEO settings
        ['meta_title', 'GK Lab - Diagnostic Services'],
        ['meta_description', 'GK Lab offers comprehensive diagnostic services with a wide range of medical tests and health checkups.'],
        ['meta_keywords', 'diagnostic lab, medical tests, health checkup, blood test, pathology'],
        ['google_analytics', '<!-- Google Analytics code goes here -->'],
        
        // Terms and privacy policy
        ['terms_conditions', '# Terms and Conditions

## 1. Introduction
Welcome to GK Lab. These terms and conditions outline the rules and regulations for the use of our services.

## 2. Services
We offer diagnostic laboratory services including but not limited to blood tests, urine tests, and other medical examinations.

## 3. Appointment and Sample Collection
Appointments can be scheduled online or by phone. Home collection services are subject to availability.

## 4. Payment
Payment is required at the time of service unless otherwise agreed upon.

## 5. Results
Test results will be provided within the timeframe specified at the time of service.'],
        ['privacy_policy', '# Privacy Policy

## 1. Information We Collect
We collect personal information including name, contact details, and health information relevant to the tests being performed.

## 2. Use of Information
Your information is used to provide and improve our services, communicate with you about your tests, and as required by law.

## 3. Protection of Information
We implement appropriate security measures to protect your personal information.

## 4. Sharing of Information
We may share your information with healthcare providers involved in your care, as authorized by you, or as required by law.

## 5. Your Rights
You have the right to access, correct, or delete your personal information.']
    ];
    
    $settings_added = 0;
    $settings_updated = 0;
    
    foreach ($default_settings as $setting) {
        $key = $setting[0];
        $value = $setting[1];
        
        // Check if setting already exists
        $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing setting
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            
            if ($stmt->execute()) {
                $settings_updated++;
            }
        } else {
            // Create new setting
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->bind_param("ss", $key, $value);
            
            if ($stmt->execute()) {
                $settings_added++;
            }
        }
    }
    
    $message .= " Added $settings_added new settings and updated $settings_updated existing settings.";
} else {
    $message .= " Default settings were not installed. To install default settings, add ?defaults=yes to the URL.";
}

// Set flash message
$_SESSION['flash_message'] = [
    'type' => $type,
    'message' => $message
];

// Redirect to settings index
header('Location: index.php');
exit;
?> 