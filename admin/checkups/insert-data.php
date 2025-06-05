<?php
/**
 * Insert Initial Checkup Data
 * 
 * This script extracts checkup data from the static HTML template
 * and inserts it into the database so it can be displayed dynamically.
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

// Function to generate slug from name
function generate_slug($name) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Function to extract price from formatted string
function extract_price($price_str) {
    // Remove non-numeric characters except decimal point
    return (float) preg_replace('/[^0-9.]/', '', $price_str);
}

// Function to extract percentage from discount badge
function extract_percentage($percentage_str) {
    return (int) preg_replace('/[^0-9]/', '', $percentage_str);
}

// Map category slugs to the database categories
$category_mapping = [
    'fullbody' => 'full-body',
    'diabetes' => 'diabetes',
    'heart' => 'heart',
    'liver' => 'liver',
    'kidney' => 'kidney',
    'women' => 'women',
    'senior' => 'senior-citizen'
];

// Define checkup data from the former HTML cards now moved to PHP files
$checkups = [
    [
        'name' => 'Full Body Checkup - Essential',
        'category' => 'fullbody',
        'original_price' => 6163,
        'discounted_price' => 1499,
        'parameters_count' => 69,
        'report_time' => '24 hours',
        'description' => 'This health checkup package is designed to provide a comprehensive assessment of your overall health, helping you detect potential health issues early and take preventive action.',
        'short_description' => 'Comprehensive health assessment with essential parameters',
        'fasting_required' => 'Yes, 8-10 hours',
        'sample_type' => 'Blood & Urine',
        'age_group' => 'All ages',
        'discount_percentage' => 76,
        'parameters' => ['CBC', 'Liver Function', 'Kidney Function', 'Lipid Profile', 'Thyroid Profile', 'Blood Sugar', 'Urine Routine', 'Vitamin D', 'Vitamin B12', 'Iron Studies', 'Calcium', 'Phosphorus', 'Electrolytes', 'ESR', 'CRP']
    ],
    [
        'name' => 'Full Body Checkup - Advanced',
        'category' => 'fullbody',
        'original_price' => 8500,
        'discounted_price' => 2499,
        'parameters_count' => 89,
        'report_time' => '24 hours',
        'description' => 'Our Advanced Full Body Checkup is a comprehensive health package that covers all major body systems. It includes advanced cardiac markers, tumor markers, and detailed vitamin profiles for a complete health assessment.',
        'short_description' => 'Complete health evaluation with advanced markers',
        'fasting_required' => 'Yes, 8-10 hours',
        'sample_type' => 'Blood & Urine',
        'age_group' => 'All ages',
        'discount_percentage' => 71,
        'parameters' => ['CBC with ESR', 'Liver Function', 'Kidney Function', 'Lipid Profile', 'Thyroid Profile', 'Blood Sugar (Fasting & PP)', 'HbA1c', 'Urine Routine', 'Vitamin Profile (D, B12, Folate)', 'Iron Studies', 'Calcium', 'Phosphorus', 'Electrolytes', 'Cardiac Markers', 'Tumor Markers']
    ],
    [
        'name' => 'Diabetes Checkup',
        'category' => 'diabetes',
        'original_price' => 2200,
        'discounted_price' => 799,
        'parameters_count' => 12,
        'report_time' => '12 hours',
        'description' => 'Our Diabetes Checkup package helps monitor glucose levels and assess diabetes-related health parameters. It\'s ideal for both diabetics and those at risk of developing diabetes.',
        'short_description' => 'Comprehensive diabetes screening and monitoring',
        'fasting_required' => 'Yes, 8-10 hours',
        'sample_type' => 'Blood',
        'age_group' => 'All ages',
        'discount_percentage' => 64,
        'parameters' => ['Fasting Blood Glucose', 'Post Prandial Glucose', 'HbA1c', 'Urine Microalbumin', 'Creatinine', 'Lipid Profile', 'Kidney Function']
    ],
    [
        'name' => 'Heart Checkup',
        'category' => 'heart',
        'original_price' => 3500,
        'discounted_price' => 1299,
        'parameters_count' => 15,
        'report_time' => '18 hours',
        'description' => 'Our Heart Checkup is designed to assess cardiac health and identify risk factors for heart disease. It includes lipid profile, cardiac markers, and other essential tests.',
        'short_description' => 'Comprehensive cardiac health assessment',
        'fasting_required' => 'Yes, 10-12 hours',
        'sample_type' => 'Blood',
        'age_group' => 'All ages',
        'discount_percentage' => 63,
        'parameters' => ['Complete Lipid Profile', 'Cardiac Markers (Troponin I, CK-MB)', 'Homocysteine', 'hs-CRP', 'ECG', 'Blood Pressure', 'Blood Sugar', 'Thyroid Profile']
    ],
    [
        'name' => 'Liver Checkup',
        'category' => 'liver',
        'original_price' => 1800,
        'discounted_price' => 699,
        'parameters_count' => 10,
        'report_time' => '10 hours',
        'description' => 'Our Liver Checkup assesses liver function and detects potential liver diseases. It\'s recommended for people with a history of liver disease, excessive alcohol consumption, or metabolic disorders.',
        'short_description' => 'Complete liver health screening',
        'fasting_required' => 'Yes, 8-10 hours',
        'sample_type' => 'Blood',
        'age_group' => 'All ages',
        'discount_percentage' => 61,
        'parameters' => ['Liver Function Tests (Complete)', 'Hepatitis B Surface Antigen', 'Anti-HCV', 'GGT', 'Albumin/Globulin Ratio', 'Prothrombin Time']
    ],
    [
        'name' => 'Kidney Checkup',
        'category' => 'kidney',
        'original_price' => 2000,
        'discounted_price' => 799,
        'parameters_count' => 9,
        'report_time' => '12 hours',
        'description' => 'Our Kidney Checkup package evaluates kidney function and detects early signs of kidney disease. It\'s especially useful for people with diabetes, hypertension, or a family history of kidney issues.',
        'short_description' => 'Comprehensive kidney function assessment',
        'fasting_required' => 'Yes, 8-10 hours',
        'sample_type' => 'Blood & Urine',
        'age_group' => 'All ages',
        'discount_percentage' => 60,
        'parameters' => ['Urea', 'Creatinine', 'Uric Acid', 'Electrolytes', 'Urine Routine & Microscopy', 'Urine Microalbumin', 'Cystatin C', 'eGFR']
    ],
    [
        'name' => 'Women\'s Wellness Checkup',
        'category' => 'women',
        'original_price' => 4000,
        'discounted_price' => 1599,
        'parameters_count' => 22,
        'report_time' => '24 hours',
        'description' => 'Our Women\'s Wellness Checkup is specially designed to address women\'s health needs, including hormonal balance, bone health, and nutritional status, along with screening for common women\'s health issues.',
        'short_description' => 'Specialized health assessment for women',
        'fasting_required' => 'Yes, 8-10 hours',
        'sample_type' => 'Blood & Urine',
        'age_group' => 'Women of all ages',
        'discount_percentage' => 60,
        'parameters' => ['CBC', 'Thyroid Profile', 'Vitamin D', 'Vitamin B12', 'Hormonal Panel', 'Lipid Profile', 'Blood Sugar', 'Urine Routine', 'Calcium', 'Iron Studies', 'Pap Smear', 'Breast Ultrasound']
    ],
    [
        'name' => 'Senior Citizen Checkup',
        'category' => 'senior',
        'original_price' => 5000,
        'discounted_price' => 1999,
        'parameters_count' => 30,
        'report_time' => '24 hours',
        'description' => 'Our Senior Citizen Checkup is tailored for adults over 60, focusing on age-related health concerns. It covers a wide range of tests to assess overall health status and catch potential issues early.',
        'short_description' => 'Specialized health package for seniors',
        'fasting_required' => 'Yes, 8-10 hours',
        'sample_type' => 'Blood & Urine',
        'age_group' => '60+ years',
        'discount_percentage' => 60,
        'parameters' => ['CBC', 'Liver Function', 'Kidney Function', 'Lipid Profile', 'Thyroid Profile', 'Blood Sugar', 'Urine Routine', 'Vitamin D', 'Vitamin B12', 'Calcium', 'PSA', 'Electrolytes', 'CRP', 'ESR', 'Iron Studies']
    ]
];

// First, check if categories exist and map them
$category_ids = [];

foreach ($category_mapping as $html_category => $db_category) {
    // Check if category exists
    $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->bind_param("s", $db_category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Category exists, get its ID
        $row = $result->fetch_assoc();
        $category_ids[$html_category] = $row['id'];
    } else {
        // Category doesn't exist, create it
        $category_name = ucwords(str_replace('-', ' ', $db_category));
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, is_active, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("ss", $category_name, $db_category);
        
        if ($stmt->execute()) {
            $category_ids[$html_category] = $stmt->insert_id;
        } else {
            echo "Error creating category '$db_category': " . $stmt->error . "<br>";
        }
    }
}

// Now insert the checkups
$checkups_inserted = 0;
$checkups_skipped = 0;
$errors = [];

foreach ($checkups as $checkup) {
    // Generate slug
    $slug = generate_slug($checkup['name']);
    
    // Check if checkup already exists
    $stmt = $conn->prepare("SELECT id FROM checkups WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $checkups_skipped++;
        continue; // Skip this checkup, it already exists
    }
    
    // Get category ID
    $category_id = isset($category_ids[$checkup['category']]) ? $category_ids[$checkup['category']] : 0;
    
    // Set default values
    $is_active = 1;
    $is_featured = 0;
    
    // Insert the checkup
    $stmt = $conn->prepare("INSERT INTO checkups (
        name, 
        slug, 
        category_id, 
        original_price, 
        discounted_price,
        discount_percentage,
        description,
        short_description,
        parameters_count, 
        report_time,
        fasting_required,
        sample_type,
        age_group,
        is_active, 
        is_featured, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->bind_param(
        "ssiidisssssssii", 
        $checkup['name'], 
        $slug, 
        $category_id, 
        $checkup['original_price'], 
        $checkup['discounted_price'],
        $checkup['discount_percentage'],
        $checkup['description'],
        $checkup['short_description'],
        $checkup['parameters_count'], 
        $checkup['report_time'],
        $checkup['fasting_required'],
        $checkup['sample_type'],
        $checkup['age_group'],
        $is_active, 
        $is_featured
    );
    
    if ($stmt->execute()) {
        $checkups_inserted++;
        
        // Now for checkups with parameters, add these to checkup_items
        $checkup_id = $stmt->insert_id;
        
        // Add parameters if they exist
        if (!empty($checkup['parameters'])) {
            foreach ($checkup['parameters'] as $parameter_name) {
                $stmt = $conn->prepare("INSERT INTO checkup_items (checkup_id, parameter_name, created_at) VALUES (?, ?, NOW())");
                $stmt->bind_param("is", $checkup_id, $parameter_name);
                $stmt->execute();
            }
        }
    } else {
        $errors[] = "Error inserting checkup '" . $checkup['name'] . "': " . $stmt->error;
    }
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-database"></i> Insert Initial Checkup Data</h1>
            <div class="admin-content-header-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Checkups
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Data Import Results</h3>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h4>Errors:</h4>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <p><strong>Checkups Inserted:</strong> <?php echo $checkups_inserted; ?></p>
                    <p><strong>Checkups Skipped (already exist):</strong> <?php echo $checkups_skipped; ?></p>
                </div>
                
                <?php if ($checkups_inserted > 0): ?>
                    <div class="alert alert-success">
                        <p>Checkup data has been successfully imported into the database. You can now view and manage these checkups from the <a href="index.php">Checkup Management</a> page.</p>
                    </div>
                <?php endif; ?>
                
                <p class="mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Checkups
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Include the admin footer
include_once '../includes/admin-footer.php';
?> 