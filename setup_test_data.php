<?php
/**
 * Test Data Setup Script
 * 
 * This script populates the database with test data for development and testing purposes.
 */

// Include database connection
require_once 'connection.php';

// Function to log messages
function log_message($message) {
    echo "$message<br>";
}

log_message("<h1>GK Lab Test Data Setup</h1>");
log_message("<p>Setting up test data...</p>");

// Make sure all tables exist first
require_once 'create_database.php';

// Clear existing test data
log_message("<h2>Clearing existing test data...</h2>");

// Don't delete users table as it might have actual admin users
$tables_to_clear = ['categories', 'tests', 'checkups', 'appointments', 'orders', 'order_items', 'contact_messages'];

foreach ($tables_to_clear as $table) {
    $conn->query("TRUNCATE TABLE $table");
    log_message("Cleared $table table");
}

// Create test admin user if it doesn't exist
$admin_email = 'admin@gklab.com';
$check_admin = $conn->query("SELECT id FROM users WHERE email = '$admin_email'");

if ($check_admin->num_rows == 0) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (email, password, first_name, last_name, user_role) 
                VALUES ('$admin_email', '$admin_password', 'Admin', 'User', 'admin')");
    log_message("Created admin user: $admin_email / admin123");
}

// Create test customer user if it doesn't exist
$customer_email = 'customer@example.com';
$check_customer = $conn->query("SELECT id FROM users WHERE email = '$customer_email'");

if ($check_customer->num_rows == 0) {
    $customer_password = password_hash('customer123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (email, password, first_name, last_name, user_role, phone, address) 
                VALUES ('$customer_email', '$customer_password', 'Test', 'Customer', 'customer', '+910987654321', '123 Test Street, Test City, India')");
    log_message("Created customer user: $customer_email / customer123");
}

// Add test categories
log_message("<h2>Creating test categories...</h2>");

$categories = [
    ['Hematology', 'Blood tests that provide information about blood cells and blood disorders'],
    ['Biochemistry', 'Tests that analyze blood chemistry, organ function, and the presence of disease markers'],
    ['Microbiology', 'Tests to identify infectious agents like bacteria, viruses, and fungi'],
    ['Immunology', 'Tests that examine the function of the immune system and detect immune disorders'],
    ['Cardiology', 'Tests related to heart function and cardiovascular health'],
    ['Endocrinology', 'Tests evaluating hormone levels and endocrine system function']
];

$category_ids = [];

foreach ($categories as $category) {
    $name = $category[0];
    $description = $category[1];
    
    $conn->query("INSERT INTO categories (name, description) VALUES ('$name', '$description')");
    $category_ids[$name] = $conn->insert_id;
    log_message("Created category: $name");
}

// Add test tests
log_message("<h2>Creating test tests...</h2>");

$tests = [
    ['Complete Blood Count (CBC)', $category_ids['Hematology'], 500, 450, 'A complete blood count (CBC) is a blood test that gives important information about the types and numbers of cells in your blood. It helps detect a wide range of disorders including anemia, infection, and leukemia.', 'No special preparation is needed.', '1 day'],
    ['Lipid Profile', $category_ids['Biochemistry'], 800, 700, 'A lipid profile measures the amount of cholesterol and triglycerides in your blood. It helps assess your risk for heart disease and stroke.', 'Fasting for 9-12 hours before the test.', '1 day'],
    ['Liver Function Test', $category_ids['Biochemistry'], 900, 850, 'Liver function tests are blood tests that measure different enzymes, proteins, and substances made by the liver. They check how well your liver is working.', 'No special preparation is needed.', '1 day'],
    ['Kidney Function Test', $category_ids['Biochemistry'], 800, 750, 'Kidney function tests are blood and urine tests that check how well your kidneys are functioning. They measure levels of waste products in your blood.', 'No special preparation is needed.', '1 day'],
    ['Thyroid Profile', $category_ids['Endocrinology'], 1200, 1100, 'A thyroid profile is a group of tests that check the function of your thyroid. These tests measure the amount of thyroid hormones in your blood.', 'No special preparation is needed.', '1 day'],
    ['HbA1c', $category_ids['Endocrinology'], 700, 650, 'The HbA1c test is a blood test that reflects your average blood glucose levels over the past 3 months. It is used to diagnose and monitor diabetes.', 'No special preparation is needed.', '1 day'],
    ['Culture & Sensitivity', $category_ids['Microbiology'], 1000, 950, 'A culture and sensitivity test is used to find out which specific bacteria or fungi are causing an infection. It also determines which antibiotics will be most effective against the microbe.', 'Depends on the type of sample collected.', '3-5 days'],
    ['Vitamin D', $category_ids['Biochemistry'], 1500, 1300, 'The vitamin D test measures the level of vitamin D in your blood. It helps diagnose vitamin D deficiency, which can lead to bone disorders.', 'No special preparation is needed.', '1-2 days'],
    ['Vitamin B12', $category_ids['Biochemistry'], 1200, 1000, 'The vitamin B12 test measures the level of vitamin B12 in your blood. It helps diagnose anemia and evaluate nutritional status.', 'No special preparation is needed.', '1-2 days'],
    ['ECG', $category_ids['Cardiology'], 600, 500, 'An electrocardiogram (ECG) is a test that measures the electrical activity of your heart. It helps detect heart problems like arrhythmias and heart attacks.', 'No special preparation is needed.', 'Immediate']
];

$test_ids = [];

foreach ($tests as $test) {
    $name = $test[0];
    $category_id = $test[1];
    $original_price = $test[2];
    $discounted_price = $test[3];
    $description = $test[4];
    $preparation = $test[5];
    $turnaround = $test[6];
    
    $sql = "INSERT INTO tests (name, category_id, price, original_price, description, preparation, turnaround_time) 
            VALUES ('$name', $category_id, $discounted_price, $original_price, '$description', '$preparation', '$turnaround')";
    
    $conn->query($sql);
    $test_ids[$name] = $conn->insert_id;
    log_message("Created test: $name");
}

// Add test checkups
log_message("<h2>Creating test checkups...</h2>");

$checkups = [
    ['Basic Health Checkup', 1500, 1200, 'A basic health check that covers essential tests to evaluate your overall health.', 'Complete Blood Count (CBC), Liver Function Test, Kidney Function Test', 'Recommended for adults of all ages as an annual health check.'],
    ['Comprehensive Health Checkup', 3500, 3000, 'A detailed health assessment that provides a comprehensive evaluation of your health.', 'Complete Blood Count (CBC), Lipid Profile, Liver Function Test, Kidney Function Test, Thyroid Profile, Vitamin D', 'Recommended for adults above 30 years as an annual health check.'],
    ['Diabetes Checkup', 2000, 1800, 'A specialized checkup to monitor diabetes and related complications.', 'HbA1c, Lipid Profile, Kidney Function Test', 'Recommended for individuals with diabetes or at risk of diabetes.'],
    ['Cardiac Health Checkup', 2500, 2200, 'A focused assessment of heart health and cardiac risk factors.', 'ECG, Lipid Profile, Complete Blood Count (CBC)', 'Recommended for individuals with a family history of heart disease or over 40 years.'],
    ['Women\'s Health Checkup', 4000, 3600, 'A comprehensive health check designed specifically for women\'s health concerns.', 'Complete Blood Count (CBC), Thyroid Profile, Vitamin D, Vitamin B12', 'Recommended for women of all ages as an annual health check.']
];

$checkup_ids = [];

foreach ($checkups as $checkup) {
    $name = $checkup[0];
    $original_price = $checkup[1];
    $discounted_price = $checkup[2];
    $description = $checkup[3];
    $included_tests = $checkup[4];
    $recommendation = $checkup[5];
    
    $sql = "INSERT INTO checkups (name, price, original_price, description, included_tests, recommendation) 
            VALUES ('$name', $discounted_price, $original_price, '$description', '$included_tests', '$recommendation')";
    
    $conn->query($sql);
    $checkup_ids[$name] = $conn->insert_id;
    log_message("Created checkup: $name");
}

// Add test appointments
log_message("<h2>Creating test appointments...</h2>");

$appointments = [
    ['John Doe', 'john@example.com', '+919876543210', '2023-12-10', '09:00:00', 'Basic Health Checkup', 'pending'],
    ['Jane Smith', 'jane@example.com', '+919876543211', '2023-12-11', '10:00:00', 'Comprehensive Health Checkup', 'confirmed'],
    ['Test Customer', 'customer@example.com', '+910987654321', '2023-12-12', '11:00:00', 'Diabetes Checkup', 'completed']
];

foreach ($appointments as $appointment) {
    $name = $appointment[0];
    $email = $appointment[1];
    $phone = $appointment[2];
    $date = $appointment[3];
    $time = $appointment[4];
    $test_type = $appointment[5];
    $status = $appointment[6];
    
    // Get customer ID if it's the test customer
    $user_id = 'NULL';
    if ($email == 'customer@example.com') {
        $customer_result = $conn->query("SELECT id FROM users WHERE email = 'customer@example.com'");
        if ($customer_result->num_rows > 0) {
            $user_data = $customer_result->fetch_assoc();
            $user_id = $user_data['id'];
        }
    }
    
    $sql = "INSERT INTO appointments (patient_name, patient_email, patient_phone, appointment_date, appointment_time, test_ids, notes, user_id, appointment_status) 
            VALUES ('$name', '$email', '$phone', '$date', '$time', '$test_type', 'Test appointment', $user_id, '$status')";
    
    $conn->query($sql);
    log_message("Created appointment for: $name");
}

// Add test contact messages
log_message("<h2>Creating test contact messages...</h2>");

$contact_messages = [
    ['John Doe', 'john@example.com', '+919876543210', 'General Inquiry', 'I would like to know more about your health checkup packages.', 'new'],
    ['Jane Smith', 'jane@example.com', '+919876543211', 'Test Result Query', 'I haven\'t received my test results yet. When can I expect them?', 'read'],
    ['Mike Johnson', 'mike@example.com', '+919876543212', 'Appointment Booking Issue', 'I\'m having trouble booking an appointment through your website.', 'replied'],
    ['Sarah Brown', 'sarah@example.com', '+919876543213', 'Billing Question', 'I have a question about the charges on my recent bill.', 'archived']
];

foreach ($contact_messages as $message) {
    $name = $message[0];
    $email = $message[1];
    $phone = $message[2];
    $subject = $message[3];
    $message_text = $message[4];
    $status = $message[5];
    
    $sql = "INSERT INTO contact_messages (name, email, phone, subject, message, status) 
            VALUES ('$name', '$email', '$phone', '$subject', '$message_text', '$status')";
    
    $conn->query($sql);
    log_message("Created contact message from: $name");
}

// Close connection
$conn->close();

log_message("<h2>Test data setup completed!</h2>");
log_message("<p>All test data has been added to the database.</p>");
log_message("<p><a href='index.html'>Go to homepage</a> | <a href='admin/index.php'>Go to admin panel</a></p>");
?> 