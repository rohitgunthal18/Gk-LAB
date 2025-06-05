<?php
/**
 * Database Connection
 * 
 * This is a compatibility wrapper that includes the main connection.php file.
 * This file exists to maintain compatibility with existing code that includes config/db.php.
 */

// Include the primary connection file
require_once dirname(__DIR__) . '/connection.php';
?> 