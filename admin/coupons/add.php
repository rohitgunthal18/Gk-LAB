<?php
/**
 * Admin - Add Coupon
 * 
 * This page allows admins to add new discount coupons.
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
    
    // Check if coupon code already exists
    $check_sql = "SELECT COUNT(*) as count FROM coupons WHERE code = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param('s', $code);
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
    
    // If no errors, insert the coupon
    if (empty($errors)) {
        $insert_sql = "INSERT INTO coupons (code, description, discount_type, discount_value, min_order_value, max_discount_amount, 
                                        start_date, end_date, is_first_order_only, is_one_time_use, max_uses, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('sssdddssiiis', $code, $description, $discount_type, $discount_value, $min_order_value, 
                         $max_discount_amount, $start_date, $end_date, $is_first_order_only, $is_one_time_use, $max_uses, $status);
        
        if ($stmt->execute()) {
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Coupon added successfully!'
            ];
            
            // Redirect to coupons list
            header('Location: ./');
            exit;
        } else {
            $errors[] = 'Error adding coupon: ' . $conn->error;
        }
    }
}

// Page title
$pageTitle = "Add New Coupon";
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
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
        }
        
        @media (max-width: 480px) {
            .form-section {
                padding: 12px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-footer {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-footer .btn {
                width: 100%;
                margin-bottom: 10px;
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
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="form-section">
                                <h3>Basic Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="code">Coupon Code *</label>
                                        <input type="text" id="code" name="code" required placeholder="e.g., WELCOME20" value="<?php echo isset($_POST['code']) ? htmlspecialchars($_POST['code']) : ''; ?>">
                                        <small>The coupon code that customers will enter at checkout</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status">Status *</label>
                                        <select id="status" name="status" required>
                                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group full-width">
                                        <label for="description">Description</label>
                                        <textarea id="description" name="description" rows="3" placeholder="Description of the coupon"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Discount Details</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="discount_type">Discount Type *</label>
                                        <select id="discount_type" name="discount_type" required>
                                            <option value="percentage" <?php echo (isset($_POST['discount_type']) && $_POST['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                            <option value="fixed" <?php echo (isset($_POST['discount_type']) && $_POST['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount (₹)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="discount_value">Discount Value *</label>
                                        <input type="number" id="discount_value" name="discount_value" required min="0" step="0.01" placeholder="e.g., 20" value="<?php echo isset($_POST['discount_value']) ? htmlspecialchars($_POST['discount_value']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="min_order_value">Minimum Order Value (₹)</label>
                                        <input type="number" id="min_order_value" name="min_order_value" min="0" step="0.01" placeholder="e.g., 500" value="<?php echo isset($_POST['min_order_value']) ? htmlspecialchars($_POST['min_order_value']) : ''; ?>">
                                        <small>Leave empty for no minimum</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="max_discount_amount">Maximum Discount Amount (₹)</label>
                                        <input type="number" id="max_discount_amount" name="max_discount_amount" min="0" step="0.01" placeholder="e.g., 1000" value="<?php echo isset($_POST['max_discount_amount']) ? htmlspecialchars($_POST['max_discount_amount']) : ''; ?>">
                                        <small>For percentage discounts only. Leave empty for no maximum.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Validity Period</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="start_date">Start Date *</label>
                                        <input type="datetime-local" id="start_date" name="start_date" required value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d\TH:i'); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="end_date">End Date *</label>
                                        <input type="datetime-local" id="end_date" name="end_date" required value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('Y-m-d\TH:i', strtotime('+30 days')); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Usage Restrictions</h3>
                                <div class="checkbox-group">
                                    <label>
                                        <input type="checkbox" name="is_first_order_only" <?php echo (isset($_POST['is_first_order_only'])) ? 'checked' : ''; ?>>
                                        First Order Only
                                    </label>
                                    <small>Coupon can only be used by customers placing their first order</small>
                                </div>
                                
                                <div class="checkbox-group">
                                    <label>
                                        <input type="checkbox" name="is_one_time_use" <?php echo (isset($_POST['is_one_time_use'])) ? 'checked' : ''; ?>>
                                        One-Time Use Per Customer
                                    </label>
                                    <small>Each customer can use this coupon only once</small>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="max_uses">Maximum Uses</label>
                                        <input type="number" id="max_uses" name="max_uses" min="1" placeholder="e.g., 100" value="<?php echo isset($_POST['max_uses']) ? htmlspecialchars($_POST['max_uses']) : ''; ?>">
                                        <small>Leave empty for unlimited uses</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Coupon
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
            
            // Generate random coupon code button
            const codeInput = document.getElementById('code');
            const generateBtn = document.createElement('button');
            generateBtn.type = 'button';
            generateBtn.className = 'btn btn-sm btn-secondary';
            generateBtn.innerHTML = '<i class="fas fa-random"></i> Generate Code';
            generateBtn.style.marginTop = '10px';
            
            generateBtn.addEventListener('click', function() {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                let result = '';
                for (let i = 0; i < 8; i++) {
                    result += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                codeInput.value = result;
            });
            
            codeInput.parentNode.appendChild(generateBtn);
        });
    </script>
</body>
</html> 