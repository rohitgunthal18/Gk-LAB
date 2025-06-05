<?php
/**
 * Admin - View Order Details
 * 
 * This page displays the detailed information of a specific order.
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

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid order ID.'
    ];
    header('Location: index.php');
    exit;
}

$order_id = (int) $_GET['id'];

// Get order details
$query = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          WHERE o.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Order not found.'
    ];
    header('Location: index.php');
    exit;
}

$order = $result->fetch_assoc();

// Get shipping address from user_addresses table
// First check if orders table has address_id field
$shipping_address = null;
$column_query = "SHOW COLUMNS FROM orders LIKE 'address_id'";
$column_result = $conn->query($column_query);

if ($column_result && $column_result->num_rows > 0) {
    // If orders table has address_id field, use it to get address
    $address_id = $order['address_id'];
    if (!empty($address_id)) {
        $address_query = "SELECT * FROM user_addresses WHERE id = ?";
        $stmt = $conn->prepare($address_query);
        $stmt->bind_param('i', $address_id);
        $stmt->execute();
        $address_result = $stmt->get_result();
        if ($address_result->num_rows > 0) {
            $shipping_address = $address_result->fetch_assoc();
        }
    }
}

// If shipping_addresses table doesn't exist, check for order_addresses
if ($shipping_address === null) {
    $check_table = $conn->query("SHOW TABLES LIKE 'order_addresses'");
    if ($check_table->num_rows > 0) {
        // Try to get shipping address from order_addresses table
        $address_query = "SELECT * FROM order_addresses WHERE order_id = ?";
        $stmt = $conn->prepare($address_query);
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $address_result = $stmt->get_result();
        if ($address_result->num_rows > 0) {
            $shipping_address = $address_result->fetch_assoc();
        }
    }
}

// If we didn't find address using address_id, try other methods
if ($shipping_address === null) {
    // Check if there's a checkout_address_id field
    $column_query = "SHOW COLUMNS FROM orders LIKE 'checkout_address_id'";
    $column_result = $conn->query($column_query);

    if ($column_result && $column_result->num_rows > 0) {
        $checkout_address_id = $order['checkout_address_id'];
        if (!empty($checkout_address_id)) {
            $address_query = "SELECT * FROM user_addresses WHERE id = ?";
            $stmt = $conn->prepare($address_query);
            $stmt->bind_param('i', $checkout_address_id);
            $stmt->execute();
            $address_result = $stmt->get_result();
            if ($address_result->num_rows > 0) {
                $shipping_address = $address_result->fetch_assoc();
            }
        }
    }
}

// If still not found, check if there's a user_address_id field
if ($shipping_address === null) {
    $column_query = "SHOW COLUMNS FROM orders LIKE 'user_address_id'";
    $column_result = $conn->query($column_query);

    if ($column_result && $column_result->num_rows > 0) {
        $user_address_id = $order['user_address_id'];
        if (!empty($user_address_id)) {
            $address_query = "SELECT * FROM user_addresses WHERE id = ?";
            $stmt = $conn->prepare($address_query);
            $stmt->bind_param('i', $user_address_id);
            $stmt->execute();
            $address_result = $stmt->get_result();
            if ($address_result->num_rows > 0) {
                $shipping_address = $address_result->fetch_assoc();
            }
        }
    }
}

// If still not found, try to get the most recent address for this user
if ($shipping_address === null && !empty($order['user_id'])) {
    $address_query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($address_query);
    $stmt->bind_param('i', $order['user_id']);
    $stmt->execute();
    $address_result = $stmt->get_result();
    if ($address_result->num_rows > 0) {
        $shipping_address = $address_result->fetch_assoc();
    }
}

// As a last resort, check if there are address-related fields directly in the orders table
if ($shipping_address === null) {
    // Get all column names from orders table
    $column_query = "SHOW COLUMNS FROM orders";
    $column_result = $conn->query($column_query);
    
    if ($column_result) {
        $address_fields = [];
        while ($column = $column_result->fetch_assoc()) {
            $field_name = $column['Field'];
            if (strpos($field_name, 'address') !== false || 
                strpos($field_name, 'city') !== false ||
                strpos($field_name, 'state') !== false ||
                strpos($field_name, 'zip') !== false ||
                strpos($field_name, 'postal') !== false ||
                strpos($field_name, 'country') !== false ||
                strpos($field_name, 'street') !== false) {
                
                $address_fields[$field_name] = $order[$field_name] ?? '';
            }
        }
        
        // If we found any address-related fields, create a shipping_address array
        if (!empty($address_fields)) {
            $shipping_address = $address_fields;
        }
    }
}

// Let's examine what columns we actually have in the orders table
$columns_info = [];
foreach ($order as $key => $value) {
    $columns_info[$key] = $value;
}

// Check what columns exist in order_items table
$check_columns = "SHOW COLUMNS FROM order_items";
$columns_result = $conn->query($check_columns);
$has_product_id = false;

if ($columns_result) {
    while ($col = $columns_result->fetch_assoc()) {
        if ($col['Field'] === 'product_id') {
            $has_product_id = true;
            break;
        }
    }
}

// Get order items - modified to not rely on products table
$items_query = "SELECT * FROM order_items WHERE order_id = ?";

$stmt = $conn->prepare($items_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

$order_items = [];
while ($item = $items_result->fetch_assoc()) {
    // Add product name placeholder
    if ($has_product_id && isset($item['product_id'])) {
        $item['product_name'] = 'Product #' . $item['product_id'];
    } else {
        $item['product_name'] = 'Item #' . $item['id'];
    }
    $item['product_image'] = '';
    $order_items[] = $item;
}

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['status'])) {
    $new_status = sanitize_input($_POST['status']);
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        // If status is 'completed', also update payment status to 'paid'
        if ($new_status === 'completed') {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
        } else {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Order #$order_id status has been updated to " . ucfirst($new_status) . "."
            ];
            
            // Update order variable
            $order['order_status'] = $new_status;
            
            // Update payment status in order variable if changed to 'completed'
            if ($new_status === 'completed') {
                $order['payment_status'] = 'paid';
            }
            
            // Redirect to refresh the page with updated data
            header("Location: view.php?id=$order_id");
            exit;
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => "Failed to update order status. Please try again."
            ];
        }
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => "Invalid order status."
        ];
    }
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<style>
    /* Action buttons styling */
    .btn {
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background 0.3s;
    }
    .btn-primary {
        background-color: #007bff;
        color: #fff;
        border: none;
    }
    .btn-primary:hover {
        background-color: #0069d9;
    }
    .btn-secondary {
        background-color: #6c757d;
        color: #fff;
        border: none;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    
    /* Order details styling */
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .order-id {
        font-size: 1.2rem;
        font-weight: bold;
    }
    .order-date {
        color: #6c757d;
    }
    .order-details-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .order-detail-card {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
    }
    .order-detail-card h3 {
        margin-top: 0;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .order-status {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }
    .badge-success {
        background-color: #28a745;
        color: #fff;
    }
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    .badge-danger {
        background-color: #dc3545;
        color: #fff;
    }
    .badge-primary {
        background-color: #007bff;
        color: #fff;
    }
    .badge-info {
        background-color: #17a2b8;
        color: #fff;
    }
    .order-items-table {
        width: 100%;
        border-collapse: collapse;
    }
    .order-items-table th, 
    .order-items-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    .order-items-table th {
        background-color: #f8f9fa;
    }
    .order-items-table tr:last-child td {
        border-bottom: none;
    }
    .product-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    .order-summary {
        margin-left: auto;
        width: 300px;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #dee2e6;
    }
    .summary-row.total {
        font-weight: bold;
        border-bottom: 2px solid #dee2e6;
    }
    .status-form {
        margin-top: 15px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    select.form-control {
        display: block;
        width: 100%;
        padding: 6px 12px;
        font-size: 1rem;
        line-height: 1.5;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
</style>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-shopping-cart"></i> Order #<?php echo $order_id; ?> Details</h1>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        </div>
        
        <div class="order-header">
            <div>
                <div class="order-id">Order #<?php echo $order_id; ?></div>
                <div class="order-date"><?php echo date('F d, Y, h:i A', strtotime($order['created_at'])); ?></div>
            </div>
            <div class="order-status">
                <span>Status:</span>
                <span class="badge badge-<?php 
                    echo $order['order_status'] === 'completed' ? 'success' : 
                        ($order['order_status'] === 'processing' ? 'primary' : 
                        ($order['order_status'] === 'cancelled' ? 'danger' : 'warning')); 
                ?>">
                    <?php echo ucfirst($order['order_status']); ?>
                </span>
            </div>
        </div>
        
        <div class="order-details-container">
            <div class="order-detail-card">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
            </div>
            
            <div class="order-detail-card">
                <h3>Shipping Address</h3>
                <?php
                if ($shipping_address !== null) {
                    // Format the address from user_addresses table
                    echo '<p><strong>' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</strong><br>';
                    
                    if (!empty($shipping_address['address'])) {
                        echo htmlspecialchars($shipping_address['address']) . '<br>';
                    }
                    
                    $location_parts = [];
                    if (!empty($shipping_address['city'])) {
                        $location_parts[] = htmlspecialchars($shipping_address['city']);
                    }
                    if (!empty($shipping_address['state'])) {
                        $location_parts[] = htmlspecialchars($shipping_address['state']);
                    }
                    if (!empty($shipping_address['pincode'])) {
                        $location_parts[] = htmlspecialchars($shipping_address['pincode']);
                    }
                    
                    if (!empty($location_parts)) {
                        echo implode(', ', $location_parts) . '</p>';
                    }
                    
                    if (!empty($order['phone'])) {
                        echo '<p><strong>Phone:</strong> ' . htmlspecialchars($order['phone']) . '</p>';
                    }
                    
                    if (!empty($order['email'])) {
                        echo '<p><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>';
                    }
                } else {
                    // Fall back to user contact information if no shipping address found
                    // Format name and contact info as an address
                    $name = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                    $email = htmlspecialchars($order['email']);
                    $phone = htmlspecialchars($order['phone']);
                    
                    // Display formatted address using available user info
                    echo '<p><strong>' . $name . '</strong><br>';
                    if (!empty($order['phone'])) {
                        echo 'Phone: ' . $phone . '<br>';
                    }
                    if (!empty($order['email'])) {
                        echo 'Email: ' . $email . '<br>';
                    }
                    echo '<em>(No shipping address found - using customer contact information)</em></p>';
                }
                
                // For COD orders, we can mention this
                if ($order['payment_method'] == 'cod') {
                    echo '<p><span class="badge badge-info">Cash on Delivery</span></p>';
                }
                ?>
            </div>
            
            <div class="order-detail-card">
                <h3>Payment Information</h3>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'Unknown')); ?></p>
                <p><strong>Payment Status:</strong> 
                    <span class="badge badge-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </p>
                
                <form action="" method="POST" class="status-form">
                    <div class="form-group">
                        <label for="status"><strong>Update Order Status:</strong></label>
                        <select name="status" id="status" class="form-control">
                            <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $order['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Order Items</h3>
                <div class="table-responsive">
                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div>
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                                <?php if (!empty($item['product_options'])): ?>
                                                    <div><small><?php echo htmlspecialchars($item['product_options']); ?></small></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>₹<?php echo number_format($order['subtotal'] ?? $order['total_amount'], 2); ?></span>
                    </div>
                    <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <span>Discount:</span>
                        <span>-₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>₹<?php echo number_format($order['shipping_cost'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 