<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=pages/checkout-address.php');
    exit;
}

// Include database connection
include_once '../config/db.php';

// Check if cart is empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header('Location: cart.php');
    exit;
}

// Initialize variables for address form
$address = $city = $state = $pincode = '';
$address_id = 0;

// Check if we have saved addresses
$addresses = [];
$user_id = $_SESSION['user_id'];

// Get saved addresses from database
$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Delete address
        if ($_POST['action'] === 'delete' && isset($_POST['address_id'])) {
            $address_id = $_POST['address_id'];
            $stmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $address_id, $user_id);
            $stmt->execute();
            header('Location: checkout-address.php');
            exit;
        }
        
        // Save new address
        if ($_POST['action'] === 'save_address') {
            // Get form data
            $address = $_POST['address'];
            $city = $_POST['city'];
            $state = $_POST['state'];
            $pincode = $_POST['pincode'];
            
            // Basic validation
            if (empty($address) || empty($city) || empty($state) || empty($pincode)) {
                $error = "All fields are required";
            } else {
                // If editing existing address
                if (isset($_POST['address_id']) && $_POST['address_id'] > 0) {
                    $address_id = $_POST['address_id'];
                    $stmt = $conn->prepare("UPDATE user_addresses SET address = ?, city = ?, state = ?, pincode = ? WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("sssiii", $address, $city, $state, $pincode, $address_id, $user_id);
                } else {
                    // Insert new address
                    $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address, city, state, pincode) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssi", $user_id, $address, $city, $state, $pincode);
                }
                
                if ($stmt->execute()) {
                    // Reload page to show updated addresses
                    header('Location: checkout-address.php');
                    exit;
                } else {
                    $error = "Error saving address: " . $conn->error;
                }
            }
        }
        
        // Proceed to payment
        if ($_POST['action'] === 'proceed' && isset($_POST['selected_address'])) {
            $selected_address_id = $_POST['selected_address'];
            $_SESSION['checkout_address_id'] = $selected_address_id;
            header('Location: checkout-payment.php');
            exit;
        }
    }
}

// Get address details for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $address_id = $_GET['edit'];
    foreach ($addresses as $addr) {
        if ($addr['id'] == $address_id) {
            $address = $addr['address'];
            $city = $addr['city'];
            $state = $addr['state'];
            $pincode = $addr['pincode'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Address - GK Lab</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .checkout-header {
            margin-bottom: 2rem;
        }

        .checkout-header h2 {
            color: var(--primary-green);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            color: #666;
            position: relative;
            z-index: 2;
        }

        .step.active .step-icon {
            background: var(--primary-green);
            color: var(--white);
        }

        .step-text {
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        .step.active .step-text {
            color: var(--primary-green);
            font-weight: 600;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 70px;
            right: 70px;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .progress-line-active {
            position: absolute;
            top: 20px;
            left: 70px;
            width: 33%;
            height: 2px;
            background: var(--primary-green);
            z-index: 0;
            transition: width 0.3s ease;
        }

        .address-form {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: var(--background-light);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            outline: none;
            box-shadow: 0 0 0 2px rgba(22, 160, 133, 0.2);
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        .btn-save {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #138D75;
        }

        .btn-cancel {
            background: #f5f5f5;
            color: var(--text-dark);
            border: 1px solid #ddd;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #e9e9e9;
        }

        .address-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .address-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            position: relative;
            transition: all 0.3s ease;
            background-color: var(--white);
        }

        .address-card.selected {
            border-color: var(--primary-green);
            box-shadow: 0 0 10px rgba(22, 160, 133, 0.2);
        }

        .address-radio {
            position: absolute;
            top: 15px;
            right: 15px;
            accent-color: var(--primary-green);
        }

        .address-content {
            margin-right: 30px;
        }

        .address-actions {
            display: flex;
            margin-top: 1rem;
            gap: 1rem;
        }

        .address-action {
            color: var(--primary-green);
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }

        .address-action:hover {
            text-decoration: underline;
        }

        .btn-add-address {
            background: var(--background-light);
            color: var(--text-dark);
            border: 1px dashed #ddd;
            padding: 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-add-address:hover {
            border-color: var(--primary-green);
            background: rgba(22, 160, 133, 0.05);
        }

        .btn-add-address i {
            font-size: 2rem;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }

        .checkout-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .btn-continue {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-continue:hover {
            background: #138D75;
        }

        .btn-back {
            background: #f5f5f5;
            color: var(--text-dark);
            border: 1px solid #ddd;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #e9e9e9;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-error {
            background-color: #FEE;
            border-color: #FCC;
            color: #C00;
        }

        @media (max-width: 768px) {
            .checkout-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .address-cards {
                grid-template-columns: 1fr;
            }
            
            .checkout-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn-continue, .btn-back {
                width: 100%;
            }
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #FF8A00;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-cart {
            position: relative;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container header-container">
            <div class="logo-container">
                <div class="logo">
                    <a href="../index.html">
                        <div class="logo-icon">
                            <i class="fas fa-flask"></i>
                        </div>
                        <h1 class="logo-text">GK Lab</h1>
                    </a>
                </div>
            </div>
            <div class="mobile-menu-toggle" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <nav class="nav-menu" id="nav-menu">
                <div class="menu-dropdown">
                    <a href="#" class="menu-item dropdown-toggle">
                        <i class="fas fa-th-large"></i>
                        Pages <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <li class="logged-in-only" style="display: none;"><a href="orders.php">Orders</a></li>
                    </ul>
                </div>
                <a href="../tests.php" class="menu-item">
                    <i class="fas fa-microscope"></i>
                    Tests
                </a>
                <a href="../checkups.php" class="menu-item">
                    <i class="fas fa-box"></i>
                    Checkups
                </a>
                <a href="cart.php" class="btn-cart">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <span class="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
                </a>
                <div class="menu-dropdown">
                    <a href="#" class="btn-support dropdown-toggle user-toggle" style="display: flex; align-items: center;" id="account-dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <span id="account-text">Account</span> <i class="fas fa-chevron-down" style="margin-left: 8px;"></i>
                    </a>
                    <ul class="dropdown-menu user-dropdown">
                        <li><a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="../register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                        <li class="logged-in-only" style="display: none;"><a href="../profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li class="logged-in-only" style="display: none;"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Checkout Address Section -->
    <section class="checkout-section">
        <div class="checkout-container">
            <div class="checkout-header">
                <h2>Checkout</h2>
                <p>Enter the address for sample collection or health check-up</p>
            </div>
            
            <div class="progress-steps">
                <div class="progress-line"></div>
                <div class="progress-line-active" style="width: 33%;"></div>
                
                <div class="step active">
                    <div class="step-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="step-text">Address</div>
                </div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="step-text">Payment</div>
                </div>
                
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-text">Confirmation</div>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Address Form -->
            <div class="address-form" id="addressForm" style="<?php echo (isset($_GET['edit']) || count($addresses) == 0) ? 'display:block;' : 'display:none;'; ?>">
                <h3><?php echo $address_id > 0 ? 'Edit' : 'Add New'; ?> Address</h3>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="action" value="save_address">
                    <?php if ($address_id > 0): ?>
                        <input type="hidden" name="address_id" value="<?php echo $address_id; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="address">Full Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $address; ?></textarea>
                    </div>
                    
                    <div class="form-row" style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="city">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo $city; ?>" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="state">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo $state; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" class="form-control" id="pincode" name="pincode" value="<?php echo $pincode; ?>" required>
                    </div>
                    
                    <div class="btn-container">
                        <button type="submit" class="btn-save">Save Address</button>
                        <button type="button" id="btnCancelAddress" class="btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>
            
            <!-- Saved Addresses -->
            <div id="savedAddresses" style="<?php echo (isset($_GET['edit']) || count($addresses) == 0) ? 'display:none;' : 'display:block;'; ?>">
                <div class="section-header">
                    <h3>Select Delivery Address</h3>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="action" value="proceed">
                    
                    <div class="address-cards">
                        <?php foreach ($addresses as $addr): ?>
                            <div class="address-card">
                                <input type="radio" name="selected_address" value="<?php echo $addr['id']; ?>" class="address-radio" required>
                                <div class="address-content">
                                    <p><strong><?php echo $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name']; ?></strong></p>
                                    <p><?php echo $addr['address']; ?></p>
                                    <p><?php echo $addr['city'] . ', ' . $addr['state'] . ' - ' . $addr['pincode']; ?></p>
                                </div>
                                <div class="address-actions">
                                    <a href="?edit=<?php echo $addr['id']; ?>" class="address-action">
                                        <i class="fas fa-pencil-alt"></i> Edit
                                    </a>
                                    <a href="#" onclick="deleteAddress(<?php echo $addr['id']; ?>)" class="address-action">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="btn-add-address" id="btnAddAddress">
                            <i class="fas fa-plus-circle"></i>
                            <p>Add New Address</p>
                        </div>
                    </div>
                    
                    <div class="checkout-actions">
                        <a href="cart.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Cart
                        </a>
                        <button type="submit" class="btn-continue">
                            Save & Continue <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Delete Address Confirmation Form -->
    <form id="deleteAddressForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="address_id" id="deleteAddressId" value="">
    </form>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show address form when "Add New Address" is clicked
            document.getElementById('btnAddAddress')?.addEventListener('click', function() {
                document.getElementById('savedAddresses').style.display = 'none';
                document.getElementById('addressForm').style.display = 'block';
            });
            
            // Hide address form when "Cancel" is clicked
            document.getElementById('btnCancelAddress')?.addEventListener('click', function() {
                <?php if (count($addresses) > 0): ?>
                    document.getElementById('addressForm').style.display = 'none';
                    document.getElementById('savedAddresses').style.display = 'block';
                <?php endif; ?>
            });
            
            // Add selected class to address card when radio is clicked
            const addressRadios = document.querySelectorAll('.address-radio');
            addressRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.address-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    this.closest('.address-card').classList.add('selected');
                });
            });
        });
        
        // Function to handle address deletion
        function deleteAddress(addressId) {
            if (confirm('Are you sure you want to delete this address?')) {
                document.getElementById('deleteAddressId').value = addressId;
                document.getElementById('deleteAddressForm').submit();
            }
        }
    </script>
</body>
</html> 