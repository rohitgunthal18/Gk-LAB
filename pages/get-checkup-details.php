<?php
/**
 * Get Checkup Details
 * 
 * This script handles AJAX requests to fetch detailed information 
 * about a specific checkup for the popup modal.
 */

// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
include_once '../config/db.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid checkup ID'
    ]);
    exit;
}

$checkup_id = (int) $_GET['id'];

// Get checkup details
$stmt = $conn->prepare("
    SELECT c.*, cat.name as category_name 
    FROM checkups c 
    LEFT JOIN categories cat ON c.category_id = cat.id 
    WHERE c.id = ? AND c.is_active = 1
");
$stmt->bind_param("i", $checkup_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Checkup not found'
    ]);
    exit;
}

$checkup = $result->fetch_assoc();

// Get checkup parameters
$parameters = [];
$stmt = $conn->prepare("
    SELECT id, parameter_name, test_id 
    FROM checkup_items 
    WHERE checkup_id = ? 
    ORDER BY id
");
$stmt->bind_param("i", $checkup_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $parameters[] = $row;
}

// Add parameters to checkup data
$checkup['parameters'] = $parameters;

// Format numeric values for display
$checkup['original_price'] = number_format($checkup['original_price']);
$checkup['discounted_price'] = number_format($checkup['discounted_price']);

// Return JSON response
echo json_encode([
    'success' => true,
    'checkup' => $checkup
]);
?> 