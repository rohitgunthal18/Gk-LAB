<?php
/**
 * Security Utilities
 * 
 * This file contains security-related functions that should be used across the application
 * to improve overall site security.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CSRF token and store it in the session
 * 
 * @return string The generated CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the one stored in the session
 * 
 * @param string $token The token to validate
 * @return bool True if the token is valid
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !$token) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token after use (for stronger security)
 * 
 * @return string The new CSRF token
 */
function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Sanitize output to prevent XSS attacks
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Secure password hashing
 * 
 * @param string $password The password to hash
 * @return string The hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Verify a password against a stored hash
 * 
 * @param string $password The password to verify
 * @param string $hash The stored hash
 * @return bool True if the password is correct
 */
function verify_password($password, $hash) {
    if (password_verify($password, $hash)) {
        // Check if rehash is needed
        if (password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12])) {
            return ['verified' => true, 'rehash' => hash_password($password)];
        }
        return ['verified' => true, 'rehash' => false];
    }
    return ['verified' => false, 'rehash' => false];
}

/**
 * Regenerate session ID to prevent session fixation
 */
function regenerate_session() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
        return session_regenerate_id(true);
    }
    
    // Regenerate session ID every 30 minutes
    $regenerate_after = 1800; // 30 minutes in seconds
    
    if ($_SESSION['last_regeneration'] < time() - $regenerate_after) {
        $_SESSION['last_regeneration'] = time();
        return session_regenerate_id(true);
    }
    
    return false;
}

/**
 * Set secure headers to improve security
 */
function set_secure_headers() {
    // Protect against XSS attacks
    header('X-XSS-Protection: 1; mode=block');
    
    // Protect against clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Set content security policy
    header("Content-Security-Policy: default-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com;");
    
    // Set referrer policy
    header('Referrer-Policy: same-origin');
    
    // Set HSTS (HTTP Strict Transport Security)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

/**
 * Function to log security-related events
 * 
 * @param string $event_type Type of event (authentication, csrf, xss, etc.)
 * @param string $description Description of the event
 * @param array $context Additional context information
 */
function log_security_event($event_type, $description, $context = []) {
    $log_file = dirname(__DIR__) . '/logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'not-authenticated';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    // Format context as JSON
    $context_json = !empty($context) ? json_encode($context) : '';
    
    // Build log message
    $log_message = "[{$timestamp}] [{$event_type}] [IP: {$ip}] [User: {$user_id}] [UA: {$user_agent}] {$description}";
    if (!empty($context_json)) {
        $log_message .= " Context: {$context_json}";
    }
    $log_message .= PHP_EOL;
    
    // Write to log file
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Enforce HTTPS by redirecting if accessed via HTTP
 */
function enforce_https() {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url, true, 301);
        exit;
    }
}

/**
 * Rate limiting for login attempts and other sensitive actions
 * Simple IP-based rate limiting using session
 * 
 * @param string $action The action to rate limit (login, register, etc.)
 * @param int $max_attempts Maximum number of attempts allowed
 * @param int $timeframe Timeframe in seconds
 * @return bool|array False if not limited, or array with wait time info if limited
 */
function check_rate_limit($action, $max_attempts = 5, $timeframe = 300) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time(),
            'last_attempt' => time()
        ];
    }
    
    $rate_data = &$_SESSION[$key];
    
    // Reset if timeframe has passed
    if (time() - $rate_data['first_attempt'] > $timeframe) {
        $rate_data = [
            'attempts' => 0,
            'first_attempt' => time(),
            'last_attempt' => time()
        ];
        return false;
    }
    
    // Check if max attempts reached
    if ($rate_data['attempts'] >= $max_attempts) {
        $wait_time = $timeframe - (time() - $rate_data['first_attempt']);
        return [
            'limited' => true,
            'wait_time' => $wait_time,
            'wait_minutes' => ceil($wait_time / 60)
        ];
    }
    
    // Increment attempt count
    $rate_data['attempts']++;
    $rate_data['last_attempt'] = time();
    
    return false;
}

// When this file is included, automatically set secure headers
set_secure_headers();

// When this file is included, automatically regenerate session if needed
regenerate_session();

// When this file is included, automatically generate CSRF token if not exists
generate_csrf_token();
?> 