<?php
/**
 * Site Settings Management
 * 
 * Allows admin to manage site settings such as contact information, 
 * business hours, payment settings, etc.
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

// Process form submissions
$error = '';
$success = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_general'])) {
        // General settings update
        $site_name = isset($_POST['site_name']) ? sanitize_input($_POST['site_name']) : '';
        $site_tagline = isset($_POST['site_tagline']) ? sanitize_input($_POST['site_tagline']) : '';
        $admin_email = isset($_POST['admin_email']) ? sanitize_input($_POST['admin_email']) : '';
        
        // Validate input
        if (empty($site_name)) {
            $error = "Site name is required.";
        } elseif (empty($admin_email)) {
            $error = "Admin email is required.";
        } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid admin email.";
        } else {
            // Update settings
            update_setting('site_name', $site_name);
            update_setting('site_tagline', $site_tagline);
            update_setting('admin_email', $admin_email);
            
            $success = "General settings updated successfully.";
        }
    } elseif (isset($_POST['update_contact'])) {
        // Contact settings update
        $contact_email = isset($_POST['contact_email']) ? sanitize_input($_POST['contact_email']) : '';
        $contact_phone = isset($_POST['contact_phone']) ? sanitize_input($_POST['contact_phone']) : '';
        $contact_address = isset($_POST['contact_address']) ? sanitize_input($_POST['contact_address']) : '';
        
        // Validate input
        if (empty($contact_email)) {
            $error = "Contact email is required.";
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid contact email.";
        } elseif (empty($contact_phone)) {
            $error = "Contact phone is required.";
        } else {
            // Update settings
            update_setting('contact_email', $contact_email);
            update_setting('contact_phone', $contact_phone);
            update_setting('contact_address', $contact_address);
            
            $success = "Contact settings updated successfully.";
        }
    } elseif (isset($_POST['update_business_hours'])) {
        // Business hours update
        $business_days = isset($_POST['business_days']) ? sanitize_input($_POST['business_days']) : '';
        $business_hours = isset($_POST['business_hours']) ? sanitize_input($_POST['business_hours']) : '';
        
        // Update settings
        update_setting('business_days', $business_days);
        update_setting('business_hours', $business_hours);
        
        $success = "Business hours updated successfully.";
    } elseif (isset($_POST['update_social'])) {
        // Social media update
        $facebook_url = isset($_POST['facebook_url']) ? sanitize_input($_POST['facebook_url']) : '';
        $twitter_url = isset($_POST['twitter_url']) ? sanitize_input($_POST['twitter_url']) : '';
        $instagram_url = isset($_POST['instagram_url']) ? sanitize_input($_POST['instagram_url']) : '';
        $linkedin_url = isset($_POST['linkedin_url']) ? sanitize_input($_POST['linkedin_url']) : '';
        
        // Update settings
        update_setting('facebook_url', $facebook_url);
        update_setting('twitter_url', $twitter_url);
        update_setting('instagram_url', $instagram_url);
        update_setting('linkedin_url', $linkedin_url);
        
        $success = "Social media links updated successfully.";
    } elseif (isset($_POST['update_payment'])) {
        // Payment settings update
        $currency = isset($_POST['currency']) ? sanitize_input($_POST['currency']) : '';
        $payment_methods = isset($_POST['payment_methods']) ? sanitize_input($_POST['payment_methods']) : '';
        
        // Update settings
        update_setting('currency', $currency);
        update_setting('payment_methods', $payment_methods);
        
        $success = "Payment settings updated successfully.";
    } elseif (isset($_POST['update_seo'])) {
        // SEO settings update
        $meta_title = isset($_POST['meta_title']) ? sanitize_input($_POST['meta_title']) : '';
        $meta_description = isset($_POST['meta_description']) ? sanitize_input($_POST['meta_description']) : '';
        $meta_keywords = isset($_POST['meta_keywords']) ? sanitize_input($_POST['meta_keywords']) : '';
        $google_analytics = isset($_POST['google_analytics']) ? sanitize_input($_POST['google_analytics']) : '';
        
        // Validate input
        if (empty($meta_title)) {
            $error = "Meta title is required.";
        } elseif (empty($meta_description)) {
            $error = "Meta description is required.";
        } elseif (empty($meta_keywords)) {
            $error = "Meta keywords are required.";
        } elseif (empty($google_analytics)) {
            $error = "Google Analytics code is required.";
        } else {
            // Update settings
            update_setting('meta_title', $meta_title);
            update_setting('meta_description', $meta_description);
            update_setting('meta_keywords', $meta_keywords);
            update_setting('google_analytics', $google_analytics);
            
            $success = "SEO settings updated successfully.";
        }
    } elseif (isset($_POST['update_terms'])) {
        // Terms & privacy update
        $terms_conditions = isset($_POST['terms_conditions']) ? sanitize_input($_POST['terms_conditions']) : '';
        $privacy_policy = isset($_POST['privacy_policy']) ? sanitize_input($_POST['privacy_policy']) : '';
        
        // Validate input
        if (empty($terms_conditions)) {
            $error = "Terms & conditions are required.";
        } elseif (empty($privacy_policy)) {
            $error = "Privacy policy is required.";
        } else {
            // Update settings
            update_setting('terms_conditions', $terms_conditions);
            update_setting('privacy_policy', $privacy_policy);
            
            $success = "Terms & privacy policy updated successfully.";
        }
    }
}

// Function to update a setting
function update_setting($key, $value) {
    global $conn;
    
    // Check if setting exists
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        // Update existing setting
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
    } else {
        // Create new setting
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
    }
    
    return $stmt->execute();
}

// Function to get a setting value
function get_setting($key, $default = '') {
    global $conn;
    
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['setting_value'];
    }
    
    return $default;
}

// Include admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-wrapper">
    <?php include_once '../includes/admin-sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-content">
            <div class="container">
                <div class="page-header">
                    <h1><i class="fas fa-cogs"></i> Site Settings</h1>
                    <p>Configure your site settings</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                        <button class="alert-close"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <button class="alert-close"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-3 col-sm-12">
                        <div class="settings-nav">
                            <div class="list-group">
                                <a href="#general" class="list-group-item list-group-item-action active" data-toggle="tab">
                                    <i class="fas fa-wrench"></i> General Settings
                                </a>
                                <a href="#contact" class="list-group-item list-group-item-action" data-toggle="tab">
                                    <i class="fas fa-envelope"></i> Contact Information
                                </a>
                                <a href="#business-hours" class="list-group-item list-group-item-action" data-toggle="tab">
                                    <i class="fas fa-clock"></i> Business Hours
                                </a>
                                <a href="#social" class="list-group-item list-group-item-action" data-toggle="tab">
                                    <i class="fas fa-share-alt"></i> Social Media
                                </a>
                                <a href="#payment" class="list-group-item list-group-item-action" data-toggle="tab">
                                    <i class="fas fa-credit-card"></i> Payment Settings
                                </a>
                                <a href="#seo" class="list-group-item list-group-item-action" data-toggle="tab">
                                    <i class="fas fa-search"></i> SEO Settings
                                </a>
                                <a href="#terms" class="list-group-item list-group-item-action" data-toggle="tab">
                                    <i class="fas fa-file-contract"></i> Terms & Privacy
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-9 col-sm-12">
                        <div class="tab-content">
                            <!-- General Settings -->
                            <div class="tab-pane fade show active" id="general">
                                <div class="card">
                                    <div class="card-header">
                                        <h2>General Settings</h2>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <div class="form-group">
                                                <label for="site_name">Site Name*</label>
                                                <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo get_setting('site_name', 'GK Lab'); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="site_tagline">Site Tagline</label>
                                                <input type="text" id="site_tagline" name="site_tagline" class="form-control" value="<?php echo get_setting('site_tagline', 'Diagnostic Services'); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="admin_email">Admin Email*</label>
                                                <input type="email" id="admin_email" name="admin_email" class="form-control" value="<?php echo get_setting('admin_email', 'admin@gklab.com'); ?>" required>
                                            </div>
                                            
                                            <div class="form-buttons">
                                                <button type="submit" name="update_general" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="tab-pane fade" id="contact">
                                <div class="card">
                                    <div class="card-header">
                                        <h2>Contact Information</h2>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <div class="form-group">
                                                <label for="contact_email">Contact Email*</label>
                                                <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?php echo get_setting('contact_email', 'info@gklab.com'); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="contact_phone">Contact Phone*</label>
                                                <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?php echo get_setting('contact_phone', '+1 (123) 456-7890'); ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="contact_address">Office Address</label>
                                                <textarea id="contact_address" name="contact_address" class="form-control" rows="3"><?php echo get_setting('contact_address', '123 Main Street, City, State, ZIP'); ?></textarea>
                                            </div>
                                            
                                            <div class="form-buttons">
                                                <button type="submit" name="update_contact" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Business Hours -->
                            <div class="tab-pane fade" id="business-hours">
                                <div class="card">
                                    <div class="card-header">
                                        <h2>Business Hours</h2>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <div class="form-group">
                                                <label for="business_days">Business Days</label>
                                                <input type="text" id="business_days" name="business_days" class="form-control" value="<?php echo get_setting('business_days', 'Monday - Saturday'); ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="business_hours">Business Hours</label>
                                                <input type="text" id="business_hours" name="business_hours" class="form-control" value="<?php echo get_setting('business_hours', '8:00 AM - 8:00 PM'); ?>">
                                            </div>
                                            
                                            <div class="form-buttons">
                                                <button type="submit" name="update_business_hours" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Social Media -->
                            <div class="tab-pane fade" id="social">
                                <div class="card">
                                    <div class="card-header">
                                        <h2>Social Media Links</h2>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <div class="form-group">
                                                <label for="facebook_url">Facebook URL</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-facebook-f"></i></span>
                                                    </div>
                                                    <input type="url" id="facebook_url" name="facebook_url" class="form-control" value="<?php echo get_setting('facebook_url', 'https://facebook.com/gklab'); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="twitter_url">Twitter URL</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                                    </div>
                                                    <input type="url" id="twitter_url" name="twitter_url" class="form-control" value="<?php echo get_setting('twitter_url', 'https://twitter.com/gklab'); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="instagram_url">Instagram URL</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                                    </div>
                                                    <input type="url" id="instagram_url" name="instagram_url" class="form-control" value="<?php echo get_setting('instagram_url', 'https://instagram.com/gklab'); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="linkedin_url">LinkedIn URL</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-linkedin-in"></i></span>
                                                    </div>
                                                    <input type="url" id="linkedin_url" name="linkedin_url" class="form-control" value="<?php echo get_setting('linkedin_url', 'https://linkedin.com/company/gklab'); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="form-buttons">
                                                <button type="submit" name="update_social" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Settings -->
                            <div class="tab-pane fade" id="payment">
                                <div class="card">
                                    <div class="card-header">
                                        <h2>Payment Settings</h2>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <div class="form-group">
                                                <label for="currency">Currency</label>
                                                <select id="currency" name="currency" class="form-control">
                                                    <option value="INR" <?php echo get_setting('currency', 'INR') === 'INR' ? 'selected' : ''; ?>>Indian Rupee (₹)</option>
                                                    <option value="USD" <?php echo get_setting('currency') === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                                    <option value="EUR" <?php echo get_setting('currency') === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                                    <option value="GBP" <?php echo get_setting('currency') === 'GBP' ? 'selected' : ''; ?>>British Pound (£)</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Payment Methods</label>
                                                <div class="checkbox-group">
                                                    <?php 
                                                    $payment_methods = explode(',', get_setting('payment_methods', 'cash,card,online'));
                                                    ?>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" id="cash" name="payment_methods[]" value="cash" <?php echo in_array('cash', $payment_methods) ? 'checked' : ''; ?>>
                                                        <label class="custom-control-label" for="cash">Cash on Delivery</label>
                                                    </div>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" id="card" name="payment_methods[]" value="card" <?php echo in_array('card', $payment_methods) ? 'checked' : ''; ?>>
                                                        <label class="custom-control-label" for="card">Credit/Debit Card</label>
                                                    </div>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" id="online" name="payment_methods[]" value="online" <?php echo in_array('online', $payment_methods) ? 'checked' : ''; ?>>
                                                        <label class="custom-control-label" for="online">Online Banking</label>
                                                    </div>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" id="wallet" name="payment_methods[]" value="wallet" <?php echo in_array('wallet', $payment_methods) ? 'checked' : ''; ?>>
                                                        <label class="custom-control-label" for="wallet">Digital Wallet</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-buttons">
                                                <button type="submit" name="update_payment" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SEO Settings -->
                            <div class="tab-pane fade" id="seo">
                                <div class="card">
                                    <div class="card-header">
                                        <h2>SEO Settings</h2>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <div class="form-group">
                                                <label for="meta_title">Default Meta Title</label>
                                                <input type="text" id="meta_title" name="meta_title" class="form-control" value="<?php echo get_setting('meta_title', 'GK Lab - Diagnostic Services'); ?>">
                                                <small class="form-text text-muted">Recommended length: 50-60 characters</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="meta_description">Default Meta Description</label>
                                                <textarea id="meta_description" name="meta_description" class="form-control" rows="3"><?php echo get_setting('meta_description', 'GK Lab offers comprehensive diagnostic services with a wide range of medical tests and health checkups.'); ?></textarea>
                                                <small class="form-text text-muted">Recommended length: 150-160 characters</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="meta_keywords">Default Meta Keywords</label>
                                                <input type="text" id="meta_keywords" name="meta_keywords" class="form-control" value="<?php echo get_setting('meta_keywords', 'diagnostic lab, medical tests, health checkup, blood test, pathology'); ?>">
                                                <small class="form-text text-muted">Separate keywords with commas</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="google_analytics">Google Analytics Code</label>
                                                <textarea id="google_analytics" name="google_analytics" class="form-control" rows="5"><?php echo get_setting('google_analytics', ''); ?></textarea>
                                                <small class="form-text text-muted">Paste your Google Analytics tracking code here</small>
                                            </div>
                                            
                                            <div class="form-buttons">
                                                <button type="submit" name="update_seo" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Terms & Privacy -->
                            <div class="tab-pane fade" id="terms">
                                <div class="card">
                                    <div class="card-header">
                                        <h2>Terms & Privacy Policy</h2>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <div class="form-group">
                                                <label for="terms_conditions">Terms & Conditions</label>
                                                <textarea id="terms_conditions" name="terms_conditions" class="form-control" rows="10"><?php echo get_setting('terms_conditions', 'Enter your terms and conditions here...'); ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="privacy_policy">Privacy Policy</label>
                                                <textarea id="privacy_policy" name="privacy_policy" class="form-control" rows="10"><?php echo get_setting('privacy_policy', 'Enter your privacy policy here...'); ?></textarea>
                                            </div>
                                            
                                            <div class="form-buttons">
                                                <button type="submit" name="update_terms" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Settings tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.settings-nav .list-group-item');
    const tabContents = document.querySelectorAll('.tab-content .tab-pane');
    
    tabLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            tabLinks.forEach(function(tab) {
                tab.classList.remove('active');
            });
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Hide all tab contents
            tabContents.forEach(function(content) {
                content.classList.remove('show', 'active');
            });
            
            // Show target tab content
            const target = this.getAttribute('href').substring(1);
            document.getElementById(target).classList.add('show', 'active');
        });
    });
});
</script>

<?php include_once '../includes/admin-footer.php'; ?>
</body>
</html> 