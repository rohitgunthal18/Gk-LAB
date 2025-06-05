<?php
/**
 * Admin - View Appointment Details
 * 
 * This page displays detailed information about a specific appointment.
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

// Helper function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if appointment ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid appointment ID.'
    ];
    
    header('Location: index.php');
    exit;
}

$appointment_id = (int) $_GET['id'];

// Get appointment details
$query = "SELECT a.*, o.id as order_id, o.id as order_number, o.total_amount as order_total, o.payment_status
          FROM appointments a
          LEFT JOIN orders o ON a.order_id = o.id
          WHERE a.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Appointment not found.'
    ];
    
    header('Location: index.php');
    exit;
}

$appointment = $result->fetch_assoc();

// Get tests associated with this appointment if an order exists
$tests = [];
if (!empty($appointment['order_id'])) {
    $test_query = "SELECT t.name, t.short_description, t.price, oi.quantity
                  FROM order_items oi
                  JOIN tests t ON oi.test_id = t.id
                  WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($test_query);
    $stmt->bind_param("i", $appointment['order_id']);
    $stmt->execute();
    $test_result = $stmt->get_result();
    
    while ($test = $test_result->fetch_assoc()) {
        $tests[] = $test;
    }
}

// Handle appointment status update
if (isset($_POST['update_status']) && !empty($_POST['status'])) {
    $new_status = sanitize_input($_POST['status']);
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        // Update appointment status
        $update_stmt = $conn->prepare("UPDATE appointments SET appointment_status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $appointment_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Appointment status has been updated to ' . ucfirst($new_status) . '.'
            ];
            
            // Refresh appointment data
            $appointment['appointment_status'] = $new_status;
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to update appointment status. Please try again.'
            ];
        }
    }
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-calendar-check"></i> Appointment #<?php echo $appointment['id']; ?>
                <span class="badge badge-<?php echo 
                    $appointment['appointment_status'] === 'completed' ? 'success' : 
                    ($appointment['appointment_status'] === 'confirmed' ? 'primary' : 
                    ($appointment['appointment_status'] === 'cancelled' ? 'danger' : 'warning')); 
                ?>">
                    <?php echo ucfirst($appointment['appointment_status']); ?>
                </span>
            </h1>
            <div class="page-header-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Appointments
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Appointment Details -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Appointment Details</h2>
                    </div>
                    <div class="card-body">
                        <div class="appointment-details">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Patient Name</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Email</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($appointment['patient_email']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Phone</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($appointment['patient_phone']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Age</div>
                                        <div class="detail-value"><?php echo !empty($appointment['age']) ? htmlspecialchars($appointment['age']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Gender</div>
                                        <div class="detail-value"><?php echo !empty($appointment['gender']) ? htmlspecialchars($appointment['gender']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Test Type</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($appointment['test_type']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Appointment Date</div>
                                        <div class="detail-value"><?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Appointment Time</div>
                                        <div class="detail-value"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="detail-item">
                                        <div class="detail-label">Sample Collection Address</div>
                                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($appointment['sample_collection_address'])); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($appointment['additional_notes'])): ?>
                                <div class="col-md-12">
                                    <div class="detail-item">
                                        <div class="detail-label">Additional Notes</div>
                                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($appointment['additional_notes'])); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <div class="detail-label">Created On</div>
                                        <div class="detail-value"><?php echo date('d M Y, h:i A', strtotime($appointment['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($tests)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="card-title">Tests Ordered</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Test Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tests as $test): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($test['name']); ?></td>
                                        <td><?php echo htmlspecialchars($test['short_description']); ?></td>
                                        <td>₹<?php echo number_format($test['price'], 2); ?></td>
                                        <td><?php echo $test['quantity']; ?></td>
                                        <td>₹<?php echo number_format($test['price'] * $test['quantity'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Total Order Value:</th>
                                        <th>₹<?php echo number_format($appointment['order_total'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Status Update -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Update Status</h2>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="form-group">
                                <label for="status">Appointment Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="pending" <?php echo $appointment['appointment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $appointment['appointment_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo $appointment['appointment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $appointment['appointment_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary btn-block">Update Status</button>
                        </form>
                    </div>
                </div>
                
                <!-- Order Information -->
                <?php if (!empty($appointment['order_id'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="card-title">Order Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="detail-item">
                            <div class="detail-label">Order ID</div>
                            <div class="detail-value">#<?php echo $appointment['order_number']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Order Total</div>
                            <div class="detail-value">₹<?php echo number_format($appointment['order_total'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value">
                                <span class="badge badge-<?php echo $appointment['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($appointment['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <a href="../orders/view.php?id=<?php echo $appointment['order_id']; ?>" class="btn btn-info btn-block mt-3">
                            <i class="fas fa-shopping-cart"></i> View Order Details
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="card-title">Actions</h2>
                    </div>
                    <div class="card-body">
                        <a href="mailto:<?php echo $appointment['patient_email']; ?>" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-envelope"></i> Send Email
                        </a>
                        <a href="tel:<?php echo $appointment['patient_phone']; ?>" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-phone"></i> Call Patient
                        </a>
                        <a href="javascript:void(0);" onclick="window.print();" class="btn btn-secondary btn-block">
                            <i class="fas fa-print"></i> Print Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Appointment details styling */
    .detail-item {
        margin-bottom: 15px;
    }
    
    .detail-label {
        font-weight: bold;
        color: #555;
        margin-bottom: 3px;
    }
    
    .detail-value {
        font-size: 1.1em;
    }
    
    /* Print styles */
    @media print {
        .admin-sidebar, .admin-header, .page-header-actions, .flash-messages,
        .card-header, .actions, button, .btn {
            display: none !important;
        }
        
        body, .admin-content, .container, .card {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            background: white !important;
            box-shadow: none !important;
        }
        
        .admin-main {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        
        h1 {
            font-size: 18pt !important;
            margin-bottom: 20px !important;
        }
        
        .badge {
            border: 1px solid #333 !important;
            color: #333 !important;
            background: none !important;
        }
    }
</style>

<?php include_once '../includes/admin-footer.php'; ?> 