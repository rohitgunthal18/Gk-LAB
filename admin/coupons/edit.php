<?php
/**
 * Admin - Edit Coupon
 * 
 * This page allows admins to edit existing discount coupons.
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

// Include database connection and functions
include_once '../../config/db.php';
include_once '../../includes/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid coupon ID'
    ];
    
    header('Location: ./');
    exit;
}

$id = intval($_GET['id']);

// Get existing coupon data
$coupon = [];
$sql = "SELECT * FROM coupons WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Coupon not found'
    ];
    
    header('Location: ./');
    exit;
}

$coupon = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $code = strtoupper(trim($_POST['code']));
    $description = trim($_POST['description']);
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $min_order_value = !empty($_POST['min_order_value']) ? floatval($_POST['min_order_value']) : 0;
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? floatval($_POST['max_discount_amount']) : NULL;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_first_order_only = isset($_POST['is_first_order_only']) ? 1 : 0;
    $is_one_time_use = isset($_POST['is_one_time_use']) ? 1 : 0;
    $max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : NULL;
    $status = $_POST['status'];
    
    // Validate data
    $errors = [];
    
    if (empty($code)) {
        $errors[] = 'Coupon code is required';
    }
    
    if (strlen($code) > 50) {
        $errors[] = 'Coupon code must be 50 characters or less';
    }
    
    // Check if coupon code already exists (excluding this coupon)
    $check_sql = "SELECT COUNT(*) as count FROM coupons WHERE code = ? AND id != ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param('si', $code, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $errors[] = 'Coupon code already exists. Please use a different code.';
    }
    
    if (!in_array($discount_type, ['percentage', 'fixed'])) {
        $errors[] = 'Invalid discount type';
    }
    
    if ($discount_value <= 0) {
        $errors[] = 'Discount value must be greater than zero';
    }
    
    if ($discount_type === 'percentage' && $discount_value > 100) {
        $errors[] = 'Percentage discount cannot exceed 100%';
    }
    
    if (empty($start_date)) {
        $errors[] = 'Start date is required';
    }
    
    if (empty($end_date)) {
        $errors[] = 'End date is required';
    }
    
    if (!empty($start_date) && !empty($end_date) && strtotime($start_date) > strtotime($end_date)) {
        $errors[] = 'End date must be greater than start date';
    }
    
    // If no errors, update the coupon
    if (empty($errors)) {
        $update_sql = "UPDATE coupons SET 
                            code = ?, 
                            description = ?, 
                            discount_type = ?, 
                            discount_value = ?, 
                            min_order_value = ?, 
                            max_discount_amount = ?, 
                            start_date = ?, 
                            end_date = ?, 
                            is_first_order_only = ?, 
                            is_one_time_use = ?, 
                            max_uses = ?, 
                            status = ? 
                        WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('sssdddssiiisi', $code, $description, $discount_type, $discount_value, $min_order_value, 
                         $max_discount_amount, $start_date, $end_date, $is_first_order_only, $is_one_time_use, $max_uses, $status, $id);
        
        if ($stmt->execute()) {
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Coupon updated successfully!'
            ];
            
            // Redirect to coupons list
            header('Location: ./');
            exit;
        } else {
            $errors[] = 'Error updating coupon: ' . $conn->error;
        }
    }
}

// Format dates for datetime-local input
$start_date_formatted = date('Y-m-d\TH:i', strtotime($coupon['start_date']));
$end_date_formatted = date('Y-m-d\TH:i', strtotime($coupon['end_date']));

// Page title
$pageTitle = "Edit Coupon: " . $coupon['code'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GK Lab Admin</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Coupon CSS -->
    <link rel="stylesheet" href="../css/coupons.css">
    <style>
        .form-section {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .form-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #16A085;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .form-group {
            flex: 1 0 250px;
            margin: 0 10px 15px;
        }
        
        .form-group.full-width {
            flex: 1 0 100%;
        }
        
        .checkbox-group {
            margin: 15px 0;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .form-footer {
            margin-top: 20px;
            text-align: right;
        }
        
        .coupon-usage {
            background-color: #e8f7f4;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .coupon-usage p {
            margin: 5px 0;
        }
        
        .info-text {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .form-section {
                padding: 15px;
            }
            
            .form-group label {
                margin-bottom: 8px;
            }
            
            .info-text {
                font-size: 12px;
            }
            
            .coupon-usage {
                padding: 12px;
                font-size: 14px;
            }
            
            .coupon-usage p {
                margin: 8px 0;
            }
            
            .form-section h3 {
                font-size: 16px;
            }
            
            .checkbox-group small {
                font-size: 12px;
                display: block;
                margin-left: 24px;
                margin-top: 2px;
            }
        }
        
        @media (max-width: 480px) {
            .form-section {
                padding: 12px;
            }
            
            .form-group {
                margin-bottom: 15px;
                flex: 1 0 100%;
            }
            
            .form-footer {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .form-footer .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .checkbox-group label {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../includes/admin-header.php'; ?>
    
    <div class="admin-wrapper">
        <?php include_once '../includes/admin-sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="content-header">
                <h1><?php echo $pageTitle; ?></h1>
                <a href="./" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Coupons
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <div class="content-body">
                <div class="card">
                    <div class="card-content">
                        <!-- Coupon Usage Info -->
                        <div class="coupon-usage">
                            <p><strong>Current Uses:</strong> <?php echo $coupon['current_uses']; ?></p>
                            <p><strong>Created:</strong> <?php echo date('F j, Y, g:i a', strtotime($coupon['created_at'])); ?></p>
                            <p><strong>Last Updated:</strong> <?php echo date('F j, Y, g:i a', strtotime($coupon['updated_at'])); ?></p>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>">
                            <div class="form-section">
                                <h3>Basic Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="code">Coupon Code *</label>
                                        <input type="text" id="code" name="code" required placeholder="e.g., WELCOME20" value="<?php echo htmlspecialchars($coupon['code']); ?>">
                                        <small>The coupon code that customers will enter at checkout</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status">Status *</label>
                                        <select id="status" name="status" required>
                                            <option value="active" <?php echo ($coupon['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($coupon['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="expired" <?php echo ($coupon['status'] === 'expired') ? 'selected' : ''; ?>>Expired</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group full-width">
                                        <label for="description">Description</label>
                                        <textarea id="description" name="description" rows="3" placeholder="Description of the coupon"><?php echo htmlspecialchars($coupon['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Discount Details</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="discount_type">Discount Type *</label>
                                        <select id="discount_type" name="discount_type" required>
                                            <option value="percentage" <?php echo ($coupon['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                            <option value="fixed" <?php echo ($coupon['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount (₹)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="discount_value">Discount Value *</label>
                                        <input type="number" id="discount_value" name="discount_value" required min="0" step="0.01" placeholder="e.g., 20" value="<?php echo htmlspecialchars($coupon['discount_value']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="min_order_value">Minimum Order Value (₹)</label>
                                        <input type="number" id="min_order_value" name="min_order_value" min="0" step="0.01" placeholder="e.g., 500" value="<?php echo htmlspecialchars($coupon['min_order_value']); ?>">
                                        <small>Leave empty for no minimum</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="max_discount_amount">Maximum Discount Amount (₹)</label>
                                        <input type="number" id="max_discount_amount" name="max_discount_amount" min="0" step="0.01" placeholder="e.g., 1000" value="<?php echo $coupon['max_discount_amount'] ? htmlspecialchars($coupon['max_discount_amount']) : ''; ?>">
                                        <small>For percentage discounts only. Leave empty for no maximum.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Validity Period</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="start_date">Start Date *</label>
                                        <input type="datetime-local" id="start_date" name="start_date" required value="<?php echo $start_date_formatted; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="end_date">End Date *</label>
                                        <input type="datetime-local" id="end_date" name="end_date" required value="<?php echo $end_date_formatted; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Usage Restrictions</h3>
                                <div class="checkbox-group">
                                    <label>
                                        <input type="checkbox" name="is_first_order_only" <?php echo ($coupon['is_first_order_only'] == 1) ? 'checked' : ''; ?>>
                                        First Order Only
                                    </label>
                                    <small>Coupon can only be used by customers placing their first order</small>
                                </div>
                                
                                <div class="checkbox-group">
                                    <label>
                                        <input type="checkbox" name="is_one_time_use" <?php echo ($coupon['is_one_time_use'] == 1) ? 'checked' : ''; ?>>
                                        One-Time Use Per Customer
                                    </label>
                                    <small>Each customer can use this coupon only once</small>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="max_uses">Maximum Uses</label>
                                        <input type="number" id="max_uses" name="max_uses" min="1" placeholder="e.g., 100" value="<?php echo $coupon['max_uses'] ? htmlspecialchars($coupon['max_uses']) : ''; ?>">
                                        <small>Leave empty for unlimited uses</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Coupon
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle discount type change
            const discountTypeSelect = document.getElementById('discount_type');
            const maxDiscountField = document.getElementById('max_discount_amount');
            const maxDiscountGroup = maxDiscountField.parentNode;
            
            function updateMaxDiscountVisibility() {
                if (discountTypeSelect.value === 'percentage') {
                    maxDiscountGroup.style.opacity = '1';
                    maxDiscountGroup.style.pointerEvents = 'auto';
                } else {
                    maxDiscountGroup.style.opacity = '0.5';
                    maxDiscountGroup.style.pointerEvents = 'none';
                    maxDiscountField.value = '';
                }
            }
            
            discountTypeSelect.addEventListener('change', updateMaxDiscountVisibility);
            updateMaxDiscountVisibility();
        });
    </script>
</body>
</html> 