<?php
/**
 * GK Lab - Central Database Connection
 * 
 * This is the MAIN database connection file for the entire GK Lab website.
 * All database connections should use this file.
 * 
 * When deploying the site, you only need to configure this file
 * with the correct database credentials.
 */

// Database configuration
// IMPORTANT: Update these settings for production environment
$host = "localhost";
$username = "root";
$password = "";
$database = "gk_lab";

// Create connection with error handling
try {
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        // Log the error to error log
        error_log("Database Connection Failed: " . $conn->connect_error);
        
        // If in production, show a user-friendly message
        die("Database connection failed. Please try again later or contact support.");
    }
    
    // Set charset to ensure proper handling of special characters
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log the error to error log
    error_log("Database Exception: " . $e->getMessage());
    
    // If in production, show a user-friendly message
    die("A database error occurred. Please try again later or contact support.");
}

// Uncomment the line below to verify connection is working during setup
// echo "Database connected successfully!"; 