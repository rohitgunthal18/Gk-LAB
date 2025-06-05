<?php
/**
 * Admin - Edit Checkup
 * 
 * This page allows administrators to edit existing health checkup packages.
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

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate slug from name
function generate_slug($name) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Check if checkup ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Invalid checkup ID.'
    ];
    
    header('Location: index.php');
    exit;
}

$checkup_id = (int) $_GET['id'];

// Get checkup details
$stmt = $conn->prepare("SELECT * FROM checkups WHERE id = ?");
$stmt->bind_param("i", $checkup_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Checkup not found.'
    ];
    
    header('Location: index.php');
    exit;
}

$checkup = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = sanitize_input($_POST['name']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $original_price = (float)$_POST['original_price'];
    $discounted_price = (float)$_POST['discounted_price'];
    $description = sanitize_input($_POST['description']);
    $short_description = sanitize_input($_POST['short_description']);
    $parameters_count = (int)$_POST['parameters_count'];
    $report_time = sanitize_input($_POST['report_time']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $fasting_required = sanitize_input($_POST['fasting_required']);
    $sample_type = sanitize_input($_POST['sample_type']);
    $age_group = sanitize_input($_POST['age_group']);
    
    // Only update slug if name changed
    $slug = ($name !== $checkup['name']) ? generate_slug($name) : $checkup['slug'];
    
    // Calculate discount percentage
    $discount_percentage = 0;
    if ($original_price > 0 && $discounted_price > 0 && $discounted_price < $original_price) {
        $discount_percentage = round((($original_price - $discounted_price) / $original_price) * 100);
    }
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Checkup name is required.';
    }
    
    if ($original_price <= 0) {
        $errors[] = 'Original price must be greater than zero.';
    }
    
    if ($discounted_price <= 0) {
        $errors[] = 'Discounted price must be greater than zero.';
    }
    
    if ($discounted_price > $original_price) {
        $errors[] = 'Discounted price cannot be greater than original price.';
    }
    
    if (empty($report_time)) {
        $errors[] = 'Report time is required.';
    }
    
    if (empty($sample_type)) {
        $errors[] = 'Sample type is required.';
    }
    
    // If no errors, update the checkup
    if (empty($errors)) {
        // Check if checkup with same name already exists (excluding current checkup)
        $stmt = $conn->prepare("SELECT id FROM checkups WHERE name = ? AND id != ?");
        $stmt->bind_param("si", $name, $checkup_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'A checkup with this name already exists.';
        } else {
            // Update the checkup
            $stmt = $conn->prepare("UPDATE checkups SET 
                                name = ?, 
                                slug = ?, 
                                category_id = ?, 
                                original_price = ?, 
                                discounted_price = ?,
                                discount_percentage = ?,
                                description = ?,
                                short_description = ?,
                                parameters_count = ?, 
                                report_time = ?, 
                                fasting_required = ?, 
                                sample_type = ?,
                                age_group = ?,
                                is_active = ?, 
                                is_featured = ?, 
                                updated_at = NOW() 
                            WHERE id = ?");
            
            $stmt->bind_param(
                "ssiidisssssssiii", 
                $name, 
                $slug, 
                $category_id, 
                $original_price, 
                $discounted_price,
                $discount_percentage,
                $description, 
                $short_description,
                $parameters_count, 
                $report_time, 
                $fasting_required, 
                $sample_type, 
                $age_group,
                $is_active, 
                $is_featured,
                $checkup_id
            );
            
            if ($stmt->execute()) {
                // Set success message
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => "Checkup '{$name}' has been updated successfully."
                ];
                
                // Redirect to checkups list
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to update checkup. Please try again. Database error: ' . $stmt->error;
            }
        }
    }
}

// Get all categories for the dropdown
$categories = [];
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-edit"></i> Edit Checkup</h1>
            <div class="admin-content-header-actions">
                <a href="items.php?checkup_id=<?php echo $checkup_id; ?>" class="btn btn-secondary mr-2">
                    <i class="fas fa-list-ul"></i> Manage Items
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Checkups
                </a>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form action="" method="POST" id="checkup-form">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Checkup Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($checkup['name']); ?>" required>
                            <small class="form-text text-muted">Enter a descriptive name for the checkup package.</small>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="category_id">Category</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="0">-- Select Category --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($checkup['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="original_price">Original Price (₹) <span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="original_price" name="original_price" value="<?php echo htmlspecialchars($checkup['original_price']); ?>" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="discounted_price">Discounted Price (₹) <span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="discounted_price" name="discounted_price" value="<?php echo htmlspecialchars($checkup['discounted_price']); ?>" required>
                            <div id="discount-percentage" class="mt-2"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="parameters_count">Number of Parameters</label>
                            <input type="number" min="0" class="form-control" id="parameters_count" name="parameters_count" value="<?php echo htmlspecialchars($checkup['parameters_count']); ?>">
                            <small class="form-text text-muted">How many parameters are included in this checkup?</small>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="report_time">Report Time <span class="required">*</span></label>
                            <input type="text" class="form-control" id="report_time" name="report_time" value="<?php echo htmlspecialchars($checkup['report_time']); ?>" required>
                            <small class="form-text text-muted">For example: "6 hours", "24 hours", "3 days"</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="sample_type">Sample Type <span class="required">*</span></label>
                            <input type="text" class="form-control" id="sample_type" name="sample_type" value="<?php echo htmlspecialchars($checkup['sample_type']); ?>" required>
                            <small class="form-text text-muted">For example: Blood, Urine, Stool, etc.</small>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="fasting_required">Fasting Required <span class="required">*</span></label>
                            <input type="text" class="form-control" id="fasting_required" name="fasting_required" value="<?php echo htmlspecialchars($checkup['fasting_required']); ?>">
                            <small class="form-text text-muted">For example: "No", "Yes, 8-10 hours"</small>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="age_group">Age Group <span class="required">*</span></label>
                            <input type="text" class="form-control" id="age_group" name="age_group" value="<?php echo htmlspecialchars($checkup['age_group']); ?>">
                            <small class="form-text text-muted">For example: "All ages", "18+ years"</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="short_description">Short Description</label>
                        <input type="text" class="form-control" id="short_description" name="short_description" value="<?php echo htmlspecialchars($checkup['short_description']); ?>">
                        <small class="form-text text-muted">A brief one-line description of the checkup (displayed in listings).</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($checkup['description']); ?></textarea>
                        <small class="form-text text-muted">Provide a detailed description of the checkup, its purpose, and benefits.</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $checkup['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                                <small class="form-text text-muted">Inactive checkups won't be visible to users on the website.</small>
                            </div>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?php echo $checkup['is_featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">
                                    Featured
                                </label>
                                <small class="form-text text-muted">Featured checkups may be highlighted on the homepage or in search results.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Checkup
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate and display discount percentage
function calculateDiscount() {
    const originalPrice = parseFloat(document.getElementById('original_price').value) || 0;
    const discountedPrice = parseFloat(document.getElementById('discounted_price').value) || 0;
    const discountElement = document.getElementById('discount-percentage');
    
    if (originalPrice > 0 && discountedPrice > 0 && discountedPrice < originalPrice) {
        const discountAmount = originalPrice - discountedPrice;
        const discountPercentage = (discountAmount / originalPrice) * 100;
        discountElement.innerHTML = `<span class="badge badge-success">Discount: ${discountPercentage.toFixed(0)}%</span>`;
    } else {
        discountElement.innerHTML = '';
    }
}

document.getElementById('original_price').addEventListener('input', calculateDiscount);
document.getElementById('discounted_price').addEventListener('input', calculateDiscount);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateDiscount();
});
</script>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 