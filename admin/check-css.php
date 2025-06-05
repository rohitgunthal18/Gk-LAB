<?php
// Debug file to check CSS access
$styleFile = __DIR__ . '/../css/style.css';
$adminStyleFile = __DIR__ . '/../css/admin-styles.css';

echo "<h1>CSS File Check</h1>";
echo "<p>Style.css path: $styleFile</p>";
echo "<p>Style.css exists: " . (file_exists($styleFile) ? 'Yes' : 'No') . "</p>";
echo "<p>Style.css readable: " . (is_readable($styleFile) ? 'Yes' : 'No') . "</p>";

echo "<p>Admin-styles.css path: $adminStyleFile</p>";
echo "<p>Admin-styles.css exists: " . (file_exists($adminStyleFile) ? 'Yes' : 'No') . "</p>";
echo "<p>Admin-styles.css readable: " . (is_readable($adminStyleFile) ? 'Yes' : 'No') . "</p>";

echo "<h2>Files in CSS directory:</h2>";
$files = scandir(__DIR__ . '/../css/');
echo "<pre>" . print_r($files, true) . "</pre>";
?>

<h2>Testing Style Inclusion:</h2>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin-styles.css">

<div class="test-admin-style">
    <p>This should be styled if admin-styles.css is loaded.</p>
</div>

<style>
.test-admin-style {
    background-color: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}
</style> 