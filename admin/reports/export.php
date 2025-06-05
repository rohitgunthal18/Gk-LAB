<?php
/**
 * Admin - Export Reports
 * 
 * This script generates and downloads various reports based on the requested type.
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
include_once '../../includes/functions.php';

// Get report parameters
$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : date('Y-m-d');

// Validate report type
$valid_types = ['sales', 'tests', 'appointments', 'customers'];
if (!in_array($report_type, $valid_types)) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid report type requested.'
    ];
    header('Location: index.php');
    exit;
}

// Function to format data as CSV
function array_to_csv_download($array, $filename = "export.csv", $delimiter = ",") {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    
    // Open output stream
    $f = fopen('php://output', 'w');
    
    // Check if headers are needed
    if (!empty($array)) {
        // Add headers (keys of first row)
        fputcsv($f, array_keys($array[0]), $delimiter);
        
        // Add data
        foreach ($array as $line) {
            fputcsv($f, $line, $delimiter);
        }
    }
    
    fclose($f);
    exit;
}

// Generate report based on type
$data = [];
$filename = "gklab_" . $report_type . "_report_" . date('Y-m-d') . ".csv";

switch ($report_type) {
    case 'sales':
        // Order sales report
        $stmt = $conn->prepare("
            SELECT o.id, o.total_amount, o.order_status, o.payment_status, o.created_at,
                   CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email, u.phone,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.created_at BETWEEN ? AND ?
            ORDER BY o.created_at DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'Order ID' => $row['id'],
                'Customer' => $row['customer_name'],
                'Email' => $row['email'],
                'Phone' => $row['phone'],
                'Total Amount' => $row['total_amount'],
                'Status' => $row['order_status'],
                'Payment Status' => $row['payment_status'],
                'Items Count' => $row['items_count'],
                'Date' => $row['created_at']
            ];
        }
        break;
        
    case 'tests':
        // Tests report
        $stmt = $conn->prepare("
            SELECT t.id, t.name, t.price, c.name as category, 
                   COUNT(oi.id) as orders_count, SUM(oi.price) as total_revenue
            FROM tests t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN order_items oi ON oi.test_id = t.id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at BETWEEN ? AND ?
            GROUP BY t.id
            ORDER BY orders_count DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'Test ID' => $row['id'],
                'Test Name' => $row['name'],
                'Category' => $row['category'],
                'Price' => $row['price'],
                'Orders Count' => $row['orders_count'] ?? 0,
                'Total Revenue' => $row['total_revenue'] ?? 0
            ];
        }
        break;
        
    case 'appointments':
        // Appointments report
        $stmt = $conn->prepare("
            SELECT a.id, a.appointment_date, a.appointment_time, a.appointment_status,
                   a.sample_collection_address, a.additional_notes, a.created_at,
                   CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email, u.phone,
                   o.id as order_id
            FROM appointments a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN orders o ON a.order_id = o.id
            WHERE a.appointment_date BETWEEN ? AND ?
            ORDER BY a.appointment_date, a.appointment_time
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'Appointment ID' => $row['id'],
                'Customer' => $row['customer_name'],
                'Email' => $row['email'],
                'Phone' => $row['phone'],
                'Date' => $row['appointment_date'],
                'Time' => $row['appointment_time'],
                'Status' => $row['appointment_status'],
                'Order ID' => $row['order_id'] ?? 'N/A',
                'Address' => $row['sample_collection_address'],
                'Notes' => $row['additional_notes'],
                'Created' => $row['created_at']
            ];
        }
        break;
        
    case 'customers':
        // Customers report
        $stmt = $conn->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
                   COUNT(DISTINCT o.id) as orders_count,
                   SUM(o.total_amount) as total_spent,
                   COUNT(DISTINCT a.id) as appointments_count
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            LEFT JOIN appointments a ON u.id = a.user_id
            WHERE u.role = 'customer' AND u.created_at BETWEEN ? AND ?
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'Customer ID' => $row['id'],
                'Name' => $row['first_name'] . ' ' . $row['last_name'],
                'Email' => $row['email'],
                'Phone' => $row['phone'],
                'Registered Date' => $row['created_at'],
                'Orders' => $row['orders_count'] ?? 0,
                'Total Spent' => $row['total_spent'] ?? 0,
                'Appointments' => $row['appointments_count'] ?? 0
            ];
        }
        break;
}

// Output data as CSV download
array_to_csv_download($data, $filename);
?> 