<?php
/**
 * Admin - Special Discount Coupon Management
 * 
 * This page allows admins to manage special discount coupons that appear on the claim-coupon.php page.
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

// Handle coupon deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $delete_sql = "DELETE FROM coupons WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Special coupon deleted successfully!'
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Error deleting special coupon: ' . $conn->error
        ];
    }
    
    // Redirect to remove the GET parameter
    header('Location: special-coupons.php');
    exit;
}

// Handle activation/deactivation of coupon
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $new_status = $_GET['status'] === 'active' ? 'inactive' : 'active';
    
    $update_sql = "UPDATE coupons SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('si', $new_status, $id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Special coupon status updated successfully!'
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Error updating special coupon status: ' . $conn->error
        ];
    }
    
    // Redirect to remove the GET parameter
    header('Location: special-coupons.php');
    exit;
}

// Get all special coupons (first-order only)
$special_coupons = [];
$sql = "SELECT * FROM coupons WHERE is_first_order_only = 1 ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $special_coupons[] = $row;
    }
}

// Page title
$pageTitle = "Manage Special Discount Coupons";
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
        /* Coupon badge styles */
        .coupon-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .coupon-active {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27AE60;
        }
        
        .coupon-inactive {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .coupon-expired {
            background-color: rgba(52, 73, 94, 0.1);
            color: #34495e;
        }
        
        /* Desktop table styles */
        .coupon-table-container {
            margin: 0 -10px;
            width: calc(100% + 20px);
        }
        
        .coupon-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .coupon-table th, 
        .coupon-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .coupon-table th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 600;
            font-size: 14px;
        }
        
        .coupon-table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .action-buttons-inline {
            display: flex;
            gap: 5px;
        }
        
        /* Mobile card-based layout */
        .coupon-cards-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .coupon-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .coupon-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .coupon-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background-color: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .coupon-code {
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        
        .coupon-card-body {
            padding: 15px;
        }
        
        .coupon-detail {
            display: flex;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .detail-label {
            flex: 0 0 100px;
            font-weight: 500;
            color: #666;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .coupon-card-footer {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            background-color: rgba(0, 0, 0, 0.02);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .coupon-card-footer .btn {
            flex: 1;
            text-align: center;
            margin: 0 5px;
        }
        
        /* Coupon preview section */
        .preview-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #16A085;
        }
        
        .preview-content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: flex-start;
        }
        
        .coupon-preview {
            width: 280px;
            border: 2px dashed #16A085;
            border-radius: 12px;
            padding: 15px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .preview-code {
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 1px;
            margin-bottom: 10px;
            color: #16A085;
        }
        
        .preview-value {
            font-size: 24px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 15px;
            color: #16A085;
        }
        
        .preview-details {
            font-size: 14px;
            text-align: center;
            line-height: 1.4;
            margin-bottom: 15px;
        }
        
        .preview-expires {
            font-size: 12px;
            text-align: center;
            color: #666;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding-top: 10px;
        }
        
        /* Utility classes */
        .d-none {
            display: none !important;
        }
        
        @media (min-width: 768px) {
            .d-md-block {
                display: block !important;
            }
            
            .d-md-none {
                display: none !important;
            }
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                width: 100%;
                gap: 8px;
                margin-top: 10px;
            }
            
            .action-buttons .btn {
                width: 100%;
                justify-content: center;
                display: flex;
                align-items: center;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .content-header h1 {
                margin-bottom: 10px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header h2 {
                margin-bottom: 5px;
            }
            
            /* Preview mobile optimizations */
            .preview-content {
                justify-content: center;
            }
            
            .coupon-preview {
                width: 100%;
                max-width: 280px;
            }
            
            /* Touch interactions */
            .btn {
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
            }
            
            .coupon-card {
                animation: fadeInUp 0.3s ease forwards;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            /* Better spacing for button text on mobile */
            .btn-sm i {
                margin-right: 5px;
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
                    <a href="add-special.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Special Coupon
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Back to All Coupons
                    </a>
                </div>
            </div>
            
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?>">
                    <?php echo $_SESSION['flash_message']['message']; ?>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>
            
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h2>Special Discount Coupons</h2>
                        <p>These coupons are displayed on the claim-coupon.php page for users to claim for their first order.</p>
                    </div>
                    <div class="card-content">
                        <?php if (empty($special_coupons)): ?>
                            <div class="no-data">
                                <p>No special coupons found. Click the "Add New Special Coupon" button to create one.</p>
                            </div>
                        <?php else: ?>
                            <!-- Desktop view for special coupons (hidden on small screens) -->
                            <div class="coupon-table-container d-none d-md-block">
                                <table class="coupon-table">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Discount</th>
                                            <th>Description</th>
                                            <th>Valid From</th>
                                            <th>Valid Until</th>
                                            <th>Status</th>
                                            <th>Usage</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($special_coupons as $coupon): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                                <td>
                                                    <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                        <?php echo $coupon['discount_value']; ?>%
                                                        <?php if (!empty($coupon['max_discount_amount'])): ?>
                                                            (up to ₹<?php echo $coupon['max_discount_amount']; ?>)
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        ₹<?php echo $coupon['discount_value']; ?>
                                                    <?php endif; ?>
                                                    <?php if ($coupon['min_order_value'] > 0): ?>
                                                        <small>(min. order: ₹<?php echo $coupon['min_order_value']; ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($coupon['description']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($coupon['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($coupon['end_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                        $statusClass = 'coupon-' . $coupon['status'];
                                                        $statusText = ucfirst($coupon['status']);
                                                    ?>
                                                    <span class="coupon-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </td>
                                                <td>
                                                    <?php echo $coupon['current_uses']; ?>
                                                    <?php if (!empty($coupon['max_uses'])): ?>
                                                        / <?php echo $coupon['max_uses']; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons-inline">
                                                        <a href="edit.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="special-coupons.php?toggle_status=<?php echo $coupon['id']; ?>&status=<?php echo $coupon['status']; ?>" class="btn btn-sm btn-<?php echo $coupon['status'] === 'active' ? 'warning' : 'success'; ?>">
                                                            <i class="fas fa-<?php echo $coupon['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-danger delete-coupon" data-id="<?php echo $coupon['id']; ?>" data-code="<?php echo htmlspecialchars($coupon['code']); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile view for special coupons (card-based layout) -->
                            <div class="coupon-cards-container d-md-none">
                                <?php foreach ($special_coupons as $coupon): ?>
                                    <div class="coupon-card">
                                        <div class="coupon-card-header">
                                            <div class="coupon-code">
                                                <strong><?php echo htmlspecialchars($coupon['code']); ?></strong>
                                            </div>
                                            <?php 
                                                $statusClass = 'coupon-' . $coupon['status'];
                                                $statusText = ucfirst($coupon['status']);
                                            ?>
                                            <span class="coupon-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </div>
                                        
                                        <div class="coupon-card-body">
                                            <div class="coupon-detail">
                                                <span class="detail-label">Discount:</span>
                                                <span class="detail-value">
                                                    <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                        <?php echo $coupon['discount_value']; ?>%
                                                        <?php if (!empty($coupon['max_discount_amount'])): ?>
                                                            <small>(up to ₹<?php echo $coupon['max_discount_amount']; ?>)</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        ₹<?php echo $coupon['discount_value']; ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($coupon['min_order_value'] > 0): ?>
                                                <div class="coupon-detail">
                                                    <span class="detail-label">Min. Order:</span>
                                                    <span class="detail-value">₹<?php echo $coupon['min_order_value']; ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="coupon-detail">
                                                <span class="detail-label">Description:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($coupon['description']); ?></span>
                                            </div>
                                            
                                            <div class="coupon-detail">
                                                <span class="detail-label">Valid:</span>
                                                <span class="detail-value"><?php echo date('M d, Y', strtotime($coupon['start_date'])); ?> - <?php echo date('M d, Y', strtotime($coupon['end_date'])); ?></span>
                                            </div>
                                            
                                            <div class="coupon-detail">
                                                <span class="detail-label">Usage:</span>
                                                <span class="detail-value">
                                                    <?php echo $coupon['current_uses']; ?>
                                                    <?php if (!empty($coupon['max_uses'])): ?>
                                                        <small>/<?php echo $coupon['max_uses']; ?></small>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="coupon-card-footer">
                                            <a href="edit.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="special-coupons.php?toggle_status=<?php echo $coupon['id']; ?>&status=<?php echo $coupon['status']; ?>" class="btn btn-sm btn-<?php echo $coupon['status'] === 'active' ? 'warning' : 'success'; ?>">
                                                <i class="fas fa-<?php echo $coupon['status'] === 'active' ? 'ban' : 'check'; ?>"></i> 
                                                <?php echo $coupon['status'] === 'active' ? 'Disable' : 'Enable'; ?>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-danger delete-coupon" data-id="<?php echo $coupon['id']; ?>" data-code="<?php echo htmlspecialchars($coupon['code']); ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Preview</h2>
                        <p>This is how your special coupons will appear on the claim-coupon.php page.</p>
                    </div>
                    <div class="card-content">
                        <?php if (empty($special_coupons)): ?>
                            <div class="no-data">
                                <p>No special coupons to preview. Add coupons first.</p>
                            </div>
                        <?php else: ?>
                            <div class="preview-container">
                                <div class="preview-title">Coupon Display Preview</div>
                                <div class="preview-content">
                                    <?php 
                                    $active_coupons = array_filter($special_coupons, function($c) {
                                        return $c['status'] === 'active' && strtotime($c['end_date']) >= time();
                                    });
                                    $active_coupons = array_slice($active_coupons, 0, 3);
                                    ?>
                                    
                                    <?php if (empty($active_coupons)): ?>
                                        <div class="no-data">
                                            <p>No active special coupons to display.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($active_coupons as $coupon): ?>
                                            <div class="coupon-preview">
                                                <div class="preview-code"><?php echo htmlspecialchars($coupon['code']); ?></div>
                                                <div class="preview-value">
                                                    <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                                        <?php echo $coupon['discount_value']; ?>% OFF
                                                    <?php else: ?>
                                                        ₹<?php echo $coupon['discount_value']; ?> OFF
                                                    <?php endif; ?>
                                                </div>
                                                <div class="preview-details">
                                                    <p><?php echo htmlspecialchars($coupon['description']); ?></p>
                                                    
                                                    <?php if ($coupon['min_order_value'] > 0): ?>
                                                        <p>Min order: ₹<?php echo $coupon['min_order_value']; ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="preview-expires">
                                                    Valid until: <?php echo date('F j, Y', strtotime($coupon['end_date'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="coupon-info">
                                <h3><i class="fas fa-info-circle"></i> How It Works</h3>
                                <p>Special discount coupons are specifically designed for first-time customers. When a user visits the claim-coupon.php page, they can claim one of these coupons for their first order.</p>
                                <p>Only active coupons that haven't expired will be shown to users (maximum of 3).</p>
                                <p>To make a coupon available on the claim page, ensure it has:</p>
                                <ul>
                                    <li>Status set to "active"</li>
                                    <li>"First order only" option checked</li>
                                    <li>Valid start and end dates</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the special coupon <span id="couponCode"></span>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close-btn">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        // Delete confirmation modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-coupon');
            const modal = document.getElementById('deleteModal');
            const couponCodeSpan = document.getElementById('couponCode');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const closeButtons = document.querySelectorAll('.modal-close, .modal-close-btn');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const code = this.getAttribute('data-code');
                    
                    couponCodeSpan.textContent = code;
                    confirmDeleteBtn.href = 'special-coupons.php?delete=' + id;
                    modal.style.display = 'block';
                });
            });
            
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Helper function to add button-pressing effect on mobile
            function addTouchEffects() {
                const buttons = document.querySelectorAll('.btn');
                
                buttons.forEach(btn => {
                    btn.addEventListener('touchstart', function() {
                        this.classList.add('btn-pressed');
                    });
                    
                    btn.addEventListener('touchend', function() {
                        this.classList.remove('btn-pressed');
                    });
                });
            }
            
            // Call the function to add touch effects
            addTouchEffects();
            
            // Alert auto close
            const alerts = document.querySelectorAll('.alert');
            const alertCloseButtons = document.querySelectorAll('.alert-close');
            
            // Auto close alerts after 5 seconds
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(alert => {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 300);
                    });
                }, 5000);
            }
            
            // Close alert on button click
            alertCloseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const alert = this.parentElement;
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                });
            });
        });
    </script>
</body>
</html> 