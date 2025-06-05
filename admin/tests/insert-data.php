<?php
/**
 * Insert Initial Test Data
 * 
 * This script extracts test data from the static HTML template
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

// Define test data from the HTML cards
$tests = [
    [
        'name' => 'Complete Blood Count',
        'category' => 'blood',
        'original_price' => 890,
        'discounted_price' => 399,
        'parameters_count' => 28,
        'report_time' => '6 hours',
        'description' => 'A complete blood count (CBC) is a blood test used to evaluate your overall health and detect a wide range of disorders, including anemia, infection and leukemia.',
        'short_description' => 'Comprehensive blood test for overall health assessment',
        'test_type' => 'Blood',
        'sample_type' => 'Blood',
        'fasting_required' => 'No',
        'age_group' => 'All ages',
        'discount_percentage' => 55,
        'parameters' => ['Hemoglobin', 'RBC Count', 'WBC Count', 'Platelet Count', 'Hematocrit']
    ],
    [
        'name' => 'Vitamin D & B12 Combo',
        'category' => 'vitamin',
        'original_price' => 2300,
        'discounted_price' => 999,
        'parameters_count' => 2,
        'report_time' => '8 hours',
        'description' => 'This combo test checks for Vitamin D and B12 levels in your blood, helping identify potential deficiencies that can affect your energy levels, immune function, and overall health.',
        'short_description' => 'Check for vital vitamin deficiencies',
        'test_type' => 'Blood',
        'sample_type' => 'Blood',
        'fasting_required' => 'Yes, 8-10 hours',
        'age_group' => 'All ages',
        'discount_percentage' => 57,
        'parameters' => ['Vitamin D (25-OH)', 'Vitamin B12']
    ],
    [
        'name' => 'Thyroid Profile Total',
        'category' => 'thyroid',
        'original_price' => 1500,
        'discounted_price' => 599,
        'parameters_count' => 5,
        'report_time' => '6 hours',
        'description' => 'The Thyroid Profile test measures thyroid function and helps diagnose thyroid disorders including hypothyroidism, hyperthyroidism, and others.',
        'short_description' => 'Complete thyroid function assessment',
        'test_type' => 'Blood',
        'sample_type' => 'Blood',
        'fasting_required' => 'Yes, 8-10 hours',
        'age_group' => 'All ages',
        'discount_percentage' => 60,
        'parameters' => ['TSH', 'T3', 'T4', 'Free T3', 'Free T4']
    ],
    [
        'name' => 'Diabetes Screening',
        'category' => 'diabetes',
        'original_price' => 1100,
        'discounted_price' => 499,
        'parameters_count' => 3,
        'report_time' => '6 hours',
        'description' => 'This test helps screen for diabetes and pre-diabetes by measuring blood glucose levels and other important markers.',
        'short_description' => 'Screen for diabetes and pre-diabetes',
        'test_type' => 'Blood',
        'sample_type' => 'Blood',
        'fasting_required' => 'Yes, 8-10 hours',
        'age_group' => 'All ages',
        'discount_percentage' => 55,
        'parameters' => ['Fasting Blood Glucose', 'HbA1c', 'Post Prandial Glucose']
    ],
    [
        'name' => 'Liver Function Test',
        'category' => 'liver',
        'original_price' => 1280,
        'discounted_price' => 549,
        'parameters_count' => 12,
        'report_time' => '6 hours',
        'description' => 'The Liver Function Test (LFT) helps determine the health of your liver by measuring levels of proteins, liver enzymes, and bilirubin in your blood.',
        'short_description' => 'Assess liver health and function',
        'test_type' => 'Blood',
        'sample_type' => 'Blood',
        'fasting_required' => 'Yes, 8-10 hours',
        'age_group' => 'All ages',
        'discount_percentage' => 57,
        'parameters' => ['SGOT/AST', 'SGPT/ALT', 'Total Bilirubin', 'Direct Bilirubin', 'Alkaline Phosphatase']
    ],
    [
        'name' => 'Kidney Function Test',
        'category' => 'kidney',
        'original_price' => 1350,
        'discounted_price' => 599,
        'parameters_count' => 8,
        'report_time' => '6 hours',
        'description' => 'The Kidney Function Test measures how well your kidneys are working by testing blood and urine samples for various markers of kidney health.',
        'short_description' => 'Comprehensive kidney health assessment',
        'test_type' => 'Blood & Urine',
        'sample_type' => 'Blood, Urine',
        'fasting_required' => 'Yes, 8-10 hours',
        'age_group' => 'All ages',
        'discount_percentage' => 56,
        'parameters' => ['Urea', 'Creatinine', 'Uric Acid', 'Sodium', 'Potassium', 'Chloride', 'Calcium', 'Phosphorus']
    ],
    [
        'name' => 'Lipid Profile',
        'category' => 'heart',
        'original_price' => 950,
        'discounted_price' => 399,
        'parameters_count' => 8,
        'report_time' => '6 hours',
        'description' => 'A Lipid Profile is a panel of blood tests that serves as an initial screening tool for abnormalities in lipids, such as cholesterol and triglycerides.',
        'short_description' => 'Check cholesterol and lipid levels',
        'test_type' => 'Blood',
        'sample_type' => 'Blood',
        'fasting_required' => 'Yes, 12 hours',
        'age_group' => 'All ages',
        'discount_percentage' => 58,
        'parameters' => ['Total Cholesterol', 'Triglycerides', 'HDL Cholesterol', 'LDL Cholesterol', 'VLDL', 'Total Lipids', 'TC/HDL Ratio', 'LDL/HDL Ratio']
    ],
    [
        'name' => 'Iron Deficiency Profile',
        'category' => 'blood',
        'original_price' => 1400,
        'discounted_price' => 649,
        'parameters_count' => 4,
        'report_time' => '8 hours',
        'description' => 'This test measures iron levels in your blood and helps diagnose and monitor conditions such as iron deficiency anemia.',
        'short_description' => 'Check iron levels and anemia markers',
        'test_type' => 'Blood',
        'sample_type' => 'Blood',
        'fasting_required' => 'Yes, 8-10 hours',
        'age_group' => 'All ages',
        'discount_percentage' => 54,
        'parameters' => ['Serum Iron', 'TIBC', 'Transferrin Saturation', 'Ferritin']
    ],
    [
        'name' => 'HbA1c Test',
        'category' => 'diabetes',
        'original_price' => 780,
        'discounted_price' => 349,
        'parameters_count' => 1,
        'report_time' => '6 hours',
        'description' => 'The HbA1c test measures your average blood sugar levels over the past 2-3 months and is used to diagnose and monitor diabetes.',
        'short_description' => 'Measure long-term blood glucose control',
        'test_type' => 'Blood',
        'sample_type' => 'Blood',
        'fasting_required' => 'No',
        'age_group' => 'All ages',
        'discount_percentage' => 55,
        'parameters' => ['HbA1c']
    ]
];

// First, check if categories exist, and create them if they don't
$categories = ['blood', 'vitamin', 'thyroid', 'diabetes', 'liver', 'kidney', 'heart'];
$category_ids = [];

foreach ($categories as $category) {
    // Check if category exists
    $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Category exists, get its ID
        $row = $result->fetch_assoc();
        $category_ids[$category] = $row['id'];
    } else {
        // Category doesn't exist, create it
        $category_name = ucfirst($category); // Convert to title case for display
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, is_active, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("ss", $category_name, $category);
        
        if ($stmt->execute()) {
            $category_ids[$category] = $stmt->insert_id;
        } else {
            echo "Error creating category '$category': " . $stmt->error . "<br>";
        }
    }
}

// Now insert the tests
$tests_inserted = 0;
$tests_skipped = 0;
$errors = [];

foreach ($tests as $test) {
    // Generate slug
    $slug = generate_slug($test['name']);
    
    // Check if test already exists
    $stmt = $conn->prepare("SELECT id FROM tests WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $tests_skipped++;
        continue; // Skip this test, it already exists
    }
    
    // Get category ID
    $category_id = isset($category_ids[$test['category']]) ? $category_ids[$test['category']] : 0;
    
    // Set default values
    $is_active = 1;
    $is_featured = 0;
    $discount_percentage = $test['discount_percentage'];
    $short_description = $test['short_description'];
    $test_type = $test['test_type'];
    
    // Insert the test
    $stmt = $conn->prepare("INSERT INTO tests (
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
        test_type,
        fasting_required,
        sample_type,
        age_group,
        is_active, 
        is_featured, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->bind_param(
        "ssiidissssssssii", 
        $test['name'], 
        $slug, 
        $category_id, 
        $test['original_price'], 
        $test['discounted_price'],
        $discount_percentage,
        $test['description'],
        $short_description,
        $test['parameters_count'], 
        $test['report_time'],
        $test_type,
        $test['fasting_required'],
        $test['sample_type'],
        $test['age_group'],
        $is_active, 
        $is_featured
    );
    
    if ($stmt->execute()) {
        $tests_inserted++;
        
        // Now for tests with parameters, add these to test_parameters
        $test_id = $stmt->insert_id;
        
        // Add parameters if they exist
        if (!empty($test['parameters'])) {
            foreach ($test['parameters'] as $parameter_name) {
                $stmt = $conn->prepare("INSERT INTO test_parameters (test_id, parameter_name, created_at) VALUES (?, ?, NOW())");
                $stmt->bind_param("is", $test_id, $parameter_name);
                $stmt->execute();
            }
        }
    } else {
        $errors[] = "Error inserting test '" . $test['name'] . "': " . $stmt->error;
    }
}

// Include the admin header
include_once '../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="container">
        <div class="admin-content-header">
            <h1><i class="fas fa-database"></i> Insert Initial Test Data</h1>
            <div class="admin-content-header-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tests
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
                    <p><strong>Tests Inserted:</strong> <?php echo $tests_inserted; ?></p>
                    <p><strong>Tests Skipped (already exist):</strong> <?php echo $tests_skipped; ?></p>
                </div>
                
                <?php if ($tests_inserted > 0): ?>
                    <div class="alert alert-success">
                        <p>Test data has been successfully imported into the database. You can now view and manage these tests from the <a href="index.php">Test Management</a> page.</p>
                    </div>
                <?php endif; ?>
                
                <p class="mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Tests
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