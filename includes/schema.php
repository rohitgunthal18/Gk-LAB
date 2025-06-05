<?php
/**
 * Schema.org Structured Data Inclusion File
 * 
 * This file includes schema.org JSON-LD structured data in the website pages.
 * 
 * @author Rohit Gunthal <rohitgunthal1819@gmail.com>
 * @copyright 2023 Rohit Gunthal
 * @license Proprietary - All Rights Reserved
 */

// Load schema JSON
$schema_file = __DIR__ . '/../schema.json';
$schema_data = file_get_contents($schema_file);

// Output schema data
if ($schema_data) {
    echo '<script type="application/ld+json">' . PHP_EOL;
    echo $schema_data . PHP_EOL;
    echo '</script>' . PHP_EOL;
}
?> 