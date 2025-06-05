<?php
/**
 * Add Developer Information Script
 * 
 * This script adds developer attribution to PHP files in the project.
 * 
 * @author Rohit Gunthal <rohitgunthal1819@gmail.com>
 * @copyright 2023 Rohit Gunthal
 * @license Proprietary - All Rights Reserved
 */

// Developer information to add
$developer_info = <<<EOT
/**
 * @author Rohit Gunthal <rohitgunthal1819@gmail.com>
 * @copyright 2023 Rohit Gunthal
 * @license Proprietary - All Rights Reserved
 * 
 * UNAUTHORIZED COPYING, MODIFICATION OR DISTRIBUTION OF THIS FILE IS STRICTLY PROHIBITED.
 * Contact: 8408088454
 */

EOT;

// Directory to process
$directory = __DIR__;

// Get all PHP files
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory),
    RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;
$modified = 0;

// Process each PHP file
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() == 'php') {
        $filename = $file->getPathname();
        
        // Skip this script itself
        if (basename($filename) == basename(__FILE__)) {
            continue;
        }
        
        $count++;
        $contents = file_get_contents($filename);
        
        // Check if file already has copyright notice
        if (strpos($contents, 'Rohit Gunthal') !== false) {
            echo "File already has copyright notice: " . $filename . PHP_EOL;
            continue;
        }
        
        // Find the PHP opening tag
        $php_pos = strpos($contents, '<?php');
        if ($php_pos !== false) {
            // Check if there's already a doc comment
            $doc_pos = strpos($contents, '/**', $php_pos);
            $next_code = strpos($contents, "\n", $php_pos + 5);
            
            if ($doc_pos !== false && $doc_pos < $next_code) {
                // There's already a doc comment, find its end
                $doc_end = strpos($contents, '*/', $doc_pos) + 2;
                $new_contents = substr($contents, 0, $doc_end) . "\n" . $developer_info . substr($contents, $doc_end);
            } else {
                // No existing doc comment, add after PHP tag
                $new_contents = substr($contents, 0, $php_pos + 5) . "\n" . $developer_info . substr($contents, $php_pos + 5);
            }
            
            // Write the modified content back to the file
            file_put_contents($filename, $new_contents);
            $modified++;
            echo "Added developer info to: " . $filename . PHP_EOL;
        }
    }
}

echo "Processed $count PHP files. Modified $modified files." . PHP_EOL;
?> 