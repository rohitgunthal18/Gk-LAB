<?php
/**
 * UI/UX and JavaScript Check
 */
echo "<h1>UI/UX and JavaScript Check</h1>";

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check CSS files
echo "<h2>CSS Files Check</h2>";
if (file_exists('css/style.css')) {
    echo "<p>Main style.css file exists.</p>";
    
    // Check file size
    $cssSize = filesize('css/style.css');
    echo "<p>style.css size: " . round($cssSize / 1024, 2) . " KB</p>";
    
    // Check for responsive media queries
    $cssContent = file_get_contents('css/style.css');
    if (preg_match('/@media/i', $cssContent)) {
        echo "<p>CSS contains media queries for responsive design.</p>";
    } else {
        echo "<p>Warning: CSS might not have responsive media queries.</p>";
    }
    
    // Check for mobile-specific styles
    if (preg_match('/@media\s+\(\s*max-width/i', $cssContent)) {
        echo "<p>CSS contains mobile-specific media queries.</p>";
    } else {
        echo "<p>Warning: CSS might not have mobile-specific styles.</p>";
    }
} else {
    echo "<p>Main style.css file is missing!</p>";
}

// Check admin CSS
if (file_exists('admin/css/admin-styles.css')) {
    echo "<p>Admin styles file exists.</p>";
} else {
    echo "<p>Admin styles file is missing!</p>";
}

// Check JavaScript files
echo "<h2>JavaScript Files Check</h2>";
$jsFiles = glob('js/*.js');
if (!empty($jsFiles)) {
    echo "<p>JavaScript files found: " . count($jsFiles) . "</p><ul>";
    foreach ($jsFiles as $file) {
        echo "<li>" . basename($file) . " - " . round(filesize($file) / 1024, 2) . " KB</li>";
        
        // Check for common JS issues
        $jsContent = file_get_contents($file);
        
        // Check for console.log (development code)
        if (preg_match('/console\.log/i', $jsContent)) {
            echo "<li class='warning'>Warning: " . basename($file) . " contains console.log statements that should be removed in production.</li>";
        }
        
        // Check for potential undefined variables
        if (preg_match('/([a-zA-Z_$][a-zA-Z0-9_$]*\s+=[^=])/i', $jsContent)) {
            echo "<li class='warning'>Warning: " . basename($file) . " might contain variables used without declaration (missing 'var', 'let', or 'const').</li>";
        }
        
        // Check for error handling
        if (!preg_match('/catch|error|try|exception/i', $jsContent) && preg_match('/\.(ajax|get|post|fetch)/i', $jsContent)) {
            echo "<li class='warning'>Warning: " . basename($file) . " might not have proper error handling for AJAX/fetch requests.</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>No JavaScript files found in js/ directory!</p>";
}

// Check HTML pages
echo "<h2>HTML/PHP Pages Check</h2>";
$htmlFiles = array_merge(glob('*.html'), glob('*.php'), glob('pages/*.html'), glob('pages/*.php'));
$htmlIssues = [];

foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    
    // Skip generated check files
    if (basename($file) == basename(__FILE__) || 
        basename($file) == 'check_database.php' || 
        basename($file) == 'check_errors.php' || 
        basename($file) == 'check_login.php' || 
        basename($file) == 'check_cart.php' || 
        basename($file) == 'check_appointments.php') {
        continue;
    }
    
    $fileIssues = [];
    
    // Check for viewport meta tag
    if (!preg_match('/<meta\s+[^>]*name\s*=\s*[\'"]viewport[\'"][^>]*>/i', $content) && 
        pathinfo($file, PATHINFO_EXTENSION) == 'html') {
        $fileIssues[] = "Missing viewport meta tag for responsive design";
    }
    
    // Check for character encoding
    if (!preg_match('/<meta\s+[^>]*charset\s*=|<meta\s+[^>]*http-equiv\s*=\s*[\'"]Content-Type[\'"][^>]*>/i', $content) && 
        pathinfo($file, PATHINFO_EXTENSION) == 'html') {
        $fileIssues[] = "Missing character encoding meta tag";
    }
    
    // Check for broken image links
    preg_match_all('/<img\s+[^>]*src\s*=\s*[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $imgSrc) {
            if (strpos($imgSrc, 'http') !== 0) { // Skip external URLs
                $imgPath = str_replace('../', '', $imgSrc);
                if (!file_exists($imgPath)) {
                    $fileIssues[] = "Broken image link: $imgSrc";
                }
            }
        }
    }
    
    // Check for inline styles (not ideal for maintainability)
    if (preg_match_all('/<[^>]+style\s*=\s*[\'"][^\'"]+[\'"][^>]*>/i', $content, $inlineStyles)) {
        if (count($inlineStyles[0]) > 5) { // Allow a few inline styles
            $fileIssues[] = "Excessive use of inline styles: " . count($inlineStyles[0]) . " instances";
        }
    }
    
    // Check for missing alt attributes on images
    preg_match_all('/<img\s+[^>]*>/i', $content, $imgTags);
    foreach ($imgTags[0] as $imgTag) {
        if (!preg_match('/alt\s*=\s*[\'"][^\'"]*[\'"]/', $imgTag)) {
            $fileIssues[] = "Image missing alt attribute: " . substr($imgTag, 0, 50) . "...";
        }
    }
    
    // Check for form accessibility
    preg_match_all('/<input\s+[^>]*>/i', $content, $inputTags);
    foreach ($inputTags[0] as $inputTag) {
        if (!preg_match('/id\s*=\s*[\'"][^\'"]+[\'"]/', $inputTag) && 
            preg_match('/type\s*=\s*[\'"](?:text|email|password|tel|number)[\'"]/', $inputTag)) {
            $fileIssues[] = "Input field missing id attribute: " . substr($inputTag, 0, 50) . "...";
        }
    }
    
    if (!empty($fileIssues)) {
        $htmlIssues[basename($file)] = $fileIssues;
    }
}

if (empty($htmlIssues)) {
    echo "<p>No major UI/UX issues found in HTML/PHP files.</p>";
} else {
    echo "<p>UI/UX issues found:</p>";
    foreach ($htmlIssues as $file => $issues) {
        echo "<p><strong>$file</strong></p><ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
    }
}

// Check for mobile-friendliness
echo "<h2>Mobile-Friendliness Check</h2>";
$mobileIssues = [];

// Check main CSS for mobile features
if (file_exists('css/style.css')) {
    $cssContent = file_get_contents('css/style.css');
    
    // Check for common mobile-friendly features
    if (!preg_match('/@media\s+\([^)]*max-width\s*:\s*[67][0-9]{2}px/i', $cssContent)) {
        $mobileIssues[] = "CSS may not have specific styles for mobile phones (viewport width ≤ 767px)";
    }
    
    if (!preg_match('/flex|display\s*:\s*flex/i', $cssContent)) {
        $mobileIssues[] = "CSS may not use flexible box layout (flexbox) for responsive design";
    }
    
    if (!preg_match('/\b(vh|vw|vmin|vmax)\b/i', $cssContent)) {
        $mobileIssues[] = "CSS may not use viewport-relative units (vh, vw, vmin, vmax) for responsive sizing";
    }
}

// Check meta viewport tag in index file
if (file_exists('index.html')) {
    $indexContent = file_get_contents('index.html');
    
    if (!preg_match('/<meta\s+[^>]*name\s*=\s*[\'"]viewport[\'"][^>]*content\s*=\s*[\'"][^\'"]*(width\s*=\s*device-width)[^\'"][\'"][^>]*>/i', $indexContent)) {
        $mobileIssues[] = "Index page might not have proper viewport meta tag for mobile devices";
    }
}

if (empty($mobileIssues)) {
    echo "<p>No major mobile-friendliness issues detected.</p>";
} else {
    echo "<p>Potential mobile-friendliness issues:</p><ul>";
    foreach ($mobileIssues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

// Check for accessibility issues
echo "<h2>Accessibility Check</h2>";
$a11yIssues = [];

// Check a sample of HTML files
$sampleFiles = [];
if (file_exists('index.html')) $sampleFiles[] = 'index.html';
if (file_exists('login.php')) $sampleFiles[] = 'login.php';
if (file_exists('pages/contact.html')) $sampleFiles[] = 'pages/contact.html';

foreach ($sampleFiles as $file) {
    $content = file_get_contents($file);
    $fileIssues = [];
    
    // Check for image alt attributes
    preg_match_all('/<img\s+[^>]*>/i', $content, $imgTags);
    $missingAlt = 0;
    foreach ($imgTags[0] as $imgTag) {
        if (!preg_match('/alt\s*=\s*[\'"][^\'"]*[\'"]/', $imgTag)) {
            $missingAlt++;
        }
    }
    if ($missingAlt > 0) {
        $fileIssues[] = "Missing alt attributes on $missingAlt " . ($missingAlt == 1 ? "image" : "images");
    }
    
    // Check for form labels
    preg_match_all('/<input\s+[^>]*id\s*=\s*[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $inputs);
    foreach ($inputs[1] as $inputId) {
        if (!preg_match('/<label\s+[^>]*for\s*=\s*[\'"]' . preg_quote($inputId, '/') . '[\'"][^>]*>/i', $content)) {
            $fileIssues[] = "Input #$inputId has no associated label";
        }
    }
    
    // Check for ARIA roles
    if (!preg_match('/role\s*=\s*[\'"][^\'"]+[\'"]/', $content)) {
        $fileIssues[] = "No ARIA roles found for improved accessibility";
    }
    
    // Check for heading structure
    if (!preg_match('/<h1/i', $content)) {
        $fileIssues[] = "No h1 heading found - important for screen readers and SEO";
    }
    
    if (!empty($fileIssues)) {
        $a11yIssues[$file] = $fileIssues;
    }
}

if (empty($a11yIssues)) {
    echo "<p>No major accessibility issues detected in sample files.</p>";
} else {
    echo "<p>Potential accessibility issues:</p>";
    foreach ($a11yIssues as $file => $issues) {
        echo "<p><strong>$file</strong></p><ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
    }
}

// Check for SEO issues
echo "<h2>SEO Check</h2>";
$seoIssues = [];

// Check index file
if (file_exists('index.html')) {
    $indexContent = file_get_contents('index.html');
    
    // Check for title tag
    if (!preg_match('/<title>[^<]{5,60}<\/title>/i', $indexContent)) {
        $seoIssues[] = "Index page title tag missing or outside recommended length (5-60 chars)";
    }
    
    // Check for meta description
    if (!preg_match('/<meta\s+[^>]*name\s*=\s*[\'"]description[\'"][^>]*content\s*=\s*[\'"][^\'"]{50,160}[\'"][^>]*>/i', $indexContent)) {
        $seoIssues[] = "Index page meta description missing or outside recommended length (50-160 chars)";
    }
    
    // Check for heading structure
    if (!preg_match('/<h1[^>]*>[^<]+<\/h1>/i', $indexContent)) {
        $seoIssues[] = "Index page has no h1 heading - crucial for SEO";
    }
}

if (empty($seoIssues)) {
    echo "<p>No major SEO issues detected.</p>";
} else {
    echo "<p>Potential SEO issues:</p><ul>";
    foreach ($seoIssues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

// Summary of all issues
echo "<h2>Summary of Potential Issues</h2>";
echo "<p>Here are key issues found that need to be addressed:</p>";
echo "<ul>";

// Add any critical issues here based on previous checks
if (!file_exists('css/style.css')) {
    echo "<li>Missing main CSS file</li>";
}

if (empty($jsFiles)) {
    echo "<li>Missing JavaScript files</li>";
}

if (!empty($htmlIssues)) {
    echo "<li>UI/UX issues found in " . count($htmlIssues) . " HTML/PHP files</li>";
}

if (!empty($mobileIssues)) {
    echo "<li>Mobile-friendliness issues detected</li>";
}

if (!empty($a11yIssues)) {
    echo "<li>Accessibility issues found in " . count($a11yIssues) . " files</li>";
}

if (!empty($seoIssues)) {
    echo "<li>SEO issues detected on index page</li>";
}

echo "</ul>";

echo "<p>This is a high-level overview. For detailed results, check each section above.</p>";
?> 