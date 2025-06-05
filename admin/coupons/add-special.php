<?php
/**
 * Admin - Add Special Discount Coupon
 * 
 * This page allows admins to add new special discount coupons for first-time customers.
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

// Function to generate random coupon code
function generateCouponCode($length = 8) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

// Handle form submission
$errors = [];
$success = false;
$formData = [
    'code' => generateCouponCode(),
    'description' => '',
    'discount_type' => 'percentage',
    'discount_value' => '',
    'min_order_value' => '0',
    'max_discount_amount' => '',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+30 days')),
    'max_uses' => '',
    'status' => 'active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form data
    $formData['code'] = trim($_POST['code'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['discount_type'] = $_POST['discount_type'] ?? 'percentage';
    $formData['discount_value'] = floatval($_POST['discount_value'] ?? 0);
    $formData['min_order_value'] = floatval($_POST['min_order_value'] ?? 0);
    $formData['max_discount_amount'] = !empty($_POST['max_discount_amount']) ? floatval($_POST['max_discount_amount']) : null;
    $formData['start_date'] = trim($_POST['start_date'] ?? '');
    $formData['end_date'] = trim($_POST['end_date'] ?? '');
    $formData['max_uses'] = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null;
    $formData['status'] = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($formData['code'])) {
        $errors[] = 'Coupon code is required';
    } else {
        // Check if code already exists
        $check_sql = "SELECT COUNT(*) as count FROM coupons WHERE code = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param('s', $formData['code']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $errors[] = 'Coupon code already exists. Please choose a different code.';
        }
    }
    
    if (empty($formData['description'])) {
        $errors[] = 'Coupon description is required';
    }
    
    if ($formData['discount_value'] <= 0) {
        $errors[] = 'Discount value must be greater than zero';
    }
    
    if ($formData['discount_type'] === 'percentage' && $formData['discount_value'] > 100) {
        $errors[] = 'Percentage discount cannot exceed 100%';
    }
    
    if (empty($formData['start_date'])) {
        $errors[] = 'Start date is required';
    }
    
    if (empty($formData['end_date'])) {
        $errors[] = 'End date is required';
    }
    
    if (strtotime($formData['end_date']) < strtotime($formData['start_date'])) {
        $errors[] = 'End date must be after start date';
    }
    
    // If no errors, save coupon
    if (empty($errors)) {
        $sql = "INSERT INTO coupons (
                    code, 
                    description, 
                    discount_type, 
                    discount_value, 
                    min_order_value,
                    max_discount_amount,
                    start_date,
                    end_date,
                    is_first_order_only,
                    is_one_time_use,
                    max_uses,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?)";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sssddsssss',
            $formData['code'],
            $formData['description'],
            $formData['discount_type'],
            $formData['discount_value'],
            $formData['min_order_value'],
            $formData['max_discount_amount'],
            $formData['start_date'],
            $formData['end_date'],
            $formData['max_uses'],
            $formData['status']
        );
        
        if ($stmt->execute()) {
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Special coupon added successfully!'
            ];
            
            // Redirect to coupons page
            header('Location: special-coupons.php');
            exit;
        } else {
            $errors[] = 'Error adding coupon: ' . $conn->error;
        }
    }
}

// Page title
$pageTitle = "Add Special Discount Coupon";
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
        .form-group {
            margin-bottom: 20px;
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
        
        .special-note {
            background-color: #e8f7f4;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #16A085;
            margin-bottom: 20px;
        }
        
        .special-note h3 {
            color: #16A085;
            margin-top: 0;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .form-section {
                padding: 15px;
            }
            
            .form-group label {
                margin-bottom: 8px;
            }
            
            .special-note {
                padding: 12px;
                font-size: 14px;
            }
            
            .special-note h3 {
                font-size: 16px;
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
                <div class="action-buttons">
                    <a href="special-coupons.php" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Back to Special Coupons
                    </a>
                </div>
            </div>
            
            <!-- Display errors if any -->
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
                    <div class="card-header">
                        <h2>Special Discount Coupon Details</h2>
                    </div>
                    <div class="card-content">
                        <div class="special-note">
                            <h3><i class="fas fa-info-circle"></i> Special Coupon Note</h3>
                            <p>Special discount coupons are displayed on the claim-coupon.php page and are designed for first-time customers only. These coupons will automatically be set as:</p>
                            <ul>
                                <li><strong>First order only:</strong> Yes</li>
                                <li><strong>One-time use:</strong> Yes</li>
                            </ul>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="code">Coupon Code *</label>
                                    <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($formData['code']); ?>" required>
                                    <div class="info-text">
                                        A unique code that users will enter during checkout.
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="status">Status *</label>
                                    <select id="status" name="status" required>
                                        <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <div class="info-text">
                                        Only active coupons will be displayed on the claim page.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description *</label>
                                <textarea id="description" name="description" required><?php echo htmlspecialchars($formData['description']); ?></textarea>
                                <div class="info-text">
                                    A brief description of the coupon that will be shown to customers.
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="discount_type">Discount Type *</label>
                                    <select id="discount_type" name="discount_type" required>
                                        <option value="percentage" <?php echo $formData['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                                        <option value="fixed" <?php echo $formData['discount_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="discount_value">Discount Value *</label>
                                    <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" value="<?php echo htmlspecialchars($formData['discount_value']); ?>" required>
                                    <div class="info-text discount-info">
                                        <?php if ($formData['discount_type'] === 'percentage'): ?>
                                            Percentage discount (e.g. 10 for 10% off)
                                        <?php else: ?>
                                            Fixed amount discount in ₹
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="form-group col-md-4 max-discount-container" <?php echo $formData['discount_type'] === 'fixed' ? 'style="display: none;"' : ''; ?>>
                                    <label for="max_discount_amount">Max Discount Amount</label>
                                    <input type="number" id="max_discount_amount" name="max_discount_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($formData['max_discount_amount'] ?? ''); ?>">
                                    <div class="info-text">
                                        Maximum amount to discount (for percentage discounts only)
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="min_order_value">Minimum Order Value</label>
                                <input type="number" id="min_order_value" name="min_order_value" step="0.01" min="0" value="<?php echo htmlspecialchars($formData['min_order_value']); ?>">
                                <div class="info-text">
                                    Minimum order amount required to use this coupon (0 for no minimum)
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="start_date">Start Date *</label>
                                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($formData['start_date']); ?>" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="end_date">End Date *</label>
                                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($formData['end_date']); ?>" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="max_uses">Maximum Uses</label>
                                    <input type="number" id="max_uses" name="max_uses" min="1" value="<?php echo htmlspecialchars($formData['max_uses'] ?? ''); ?>">
                                    <div class="info-text">
                                        Maximum number of times this coupon can be used (leave empty for unlimited)
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add Special Coupon
                                </button>
                                <a href="special-coupons.php" class="btn btn-secondary">Cancel</a>
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
            // Update discount info text based on selected discount type
            const discountTypeSelect = document.getElementById('discount_type');
            const discountInfoText = document.querySelector('.discount-info');
            const maxDiscountContainer = document.querySelector('.max-discount-container');
            
            discountTypeSelect.addEventListener('change', function() {
                if (this.value === 'percentage') {
                    discountInfoText.textContent = 'Percentage discount (e.g. 10 for 10% off)';
                    maxDiscountContainer.style.display = 'block';
                } else {
                    discountInfoText.textContent = 'Fixed amount discount in ₹';
                    maxDiscountContainer.style.display = 'none';
                }
            });
            
            // Generate random coupon code
            const generateCodeBtn = document.createElement('button');
            generateCodeBtn.type = 'button';
            generateCodeBtn.className = 'btn btn-sm btn-secondary';
            generateCodeBtn.textContent = 'Generate Code';
            generateCodeBtn.style.marginLeft = '10px';
            
            const codeInput = document.getElementById('code');
            codeInput.parentNode.insertBefore(generateCodeBtn, codeInput.nextSibling);
            
            generateCodeBtn.addEventListener('click', function() {
                // Generate random code
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                let code = '';
                for (let i = 0; i < 8; i++) {
                    code += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                codeInput.value = code;
            });
            
            // Close alert button
            const alertCloseBtn = document.querySelector('.alert-close');
            if (alertCloseBtn) {
                alertCloseBtn.addEventListener('click', function() {
                    this.parentNode.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html> 