<?php
/**
 * Admin - Coupon Management
 * 
 * This page allows admins to manage discount coupons.
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

// Create coupons table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS coupons (
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
)";

if (!$conn->query($sql)) {
    // Set flash message for error
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Error creating coupons table: ' . $conn->error
    ];
}

// Handle coupon deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $delete_sql = "DELETE FROM coupons WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Coupon deleted successfully!'
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Error deleting coupon: ' . $conn->error
        ];
    }
    
    // Redirect to remove the GET parameter
    header('Location: ./');
    exit;
}

// Get all coupons
$coupons = [];
$sql = "SELECT * FROM coupons ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $coupons[] = $row;
    }
}

// Page title
$pageTitle = "Manage Coupons";
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
        /* Coupon badge styling */
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
        
        /* Desktop table styling */
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
        
        /* Action buttons in header */
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                display: flex;
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
            
            /* Mobile-specific styles and interactions */
            .btn {
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
            }
            
            .btn-pressed {
                opacity: 0.8;
                transform: scale(0.98);
            }
            
            /* Improved mobile modal display */
            .modal-content {
                width: 95%;
                max-width: 350px;
                margin: 15% auto;
            }
            
            /* Better touch targets for buttons */
            .btn-sm {
                padding: 8px 12px;
                min-height: 38px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Better description display on mobile */
            .detail-value {
                word-break: break-word;
            }
            
            /* Animate card entry */
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
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Coupon
                    </a>
                    <a href="special-coupons.php" class="btn btn-success">
                        <i class="fas fa-gift"></i> Special Discount Coupons
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
                    <div class="card-content">
                        <?php if (empty($coupons)): ?>
                            <div class="no-data">
                                <p>No coupons found. Click the "Add New Coupon" button to create one.</p>
                            </div>
                        <?php else: ?>
                            <!-- Desktop view (hidden on small screens) -->
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
                                        <?php foreach ($coupons as $coupon): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                                <td>
                                                    <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                        <?php echo $coupon['discount_value']; ?>%
                                                        <?php if (!empty($coupon['max_discount_amount'])): ?>
                                                            <small>(max: ₹<?php echo $coupon['max_discount_amount']; ?>)</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        ₹<?php echo $coupon['discount_value']; ?>
                                                    <?php endif; ?>
                                                    <?php if ($coupon['min_order_value'] > 0): ?>
                                                        <small>(min: ₹<?php echo $coupon['min_order_value']; ?>)</small>
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
                                                        <small>/<?php echo $coupon['max_uses']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons-inline">
                                                        <a href="edit.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-danger delete-coupon" data-id="<?php echo $coupon['id']; ?>" data-code="<?php echo htmlspecialchars($coupon['code']); ?>" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile view (card-based layout) -->
                            <div class="coupon-cards-container d-md-none">
                                <?php foreach ($coupons as $coupon): ?>
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
                                                            <small>(max: ₹<?php echo $coupon['max_discount_amount']; ?>)</small>
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
                <p>Are you sure you want to delete the coupon <span id="couponCode"></span>?</p>
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
                    confirmDeleteBtn.href = 'index.php?delete=' + id;
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
        });
    </script>
    <?php include_once '../includes/admin-footer.php'; ?>
</body>
</html> 