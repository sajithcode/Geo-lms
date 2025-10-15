<?php
/**
 * CSRF Protection Helper
 * 
 * Provides functions to generate and validate CSRF tokens
 * for protecting against Cross-Site Request Forgery attacks
 */

/**
 * Generate a CSRF token and store it in the session
 * 
 * @return string The generated CSRF token
 */
function csrf_generate_token() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate a random token
    $token = bin2hex(random_bytes(32));
    
    // Store token in session
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Get the current CSRF token from session
 * If no token exists, generate a new one
 * 
 * @return string The CSRF token
 */
function csrf_get_token() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if token exists and is not expired (valid for 1 hour)
    if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
        $tokenAge = time() - $_SESSION['csrf_token_time'];
        if ($tokenAge < 3600) { // 1 hour
            return $_SESSION['csrf_token'];
        }
    }
    
    // Generate new token if none exists or expired
    return csrf_generate_token();
}

/**
 * Validate CSRF token from request
 * 
 * @param string $token The token to validate (from POST/GET request)
 * @return bool True if token is valid, false otherwise
 */
function csrf_validate_token($token) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if session token exists
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Check if token has expired (1 hour)
    if (isset($_SESSION['csrf_token_time'])) {
        $tokenAge = time() - $_SESSION['csrf_token_time'];
        if ($tokenAge >= 3600) {
            return false;
        }
    }
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate HTML input field for CSRF token
 * 
 * @return string HTML input field
 */
function csrf_token_field() {
    $token = csrf_get_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from POST request
 * Terminates script with error if validation fails
 * 
 * @param string $errorMessage Custom error message (optional)
 * @return void
 */
function csrf_validate_or_die($errorMessage = 'Invalid CSRF token. Please try again.') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!csrf_validate_token($token)) {
        // Log the failed attempt
        error_log('CSRF validation failed from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Terminate with error
        http_response_code(403);
        die($errorMessage);
    }
}

/**
 * Validate CSRF token and redirect on failure
 * 
 * @param string $redirectUrl URL to redirect to on failure
 * @param string $errorParam Query parameter name for error message
 * @return void
 */
function csrf_validate_or_redirect($redirectUrl, $errorParam = 'error') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!csrf_validate_token($token)) {
        // Log the failed attempt
        error_log('CSRF validation failed from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Redirect with error
        header('Location: ' . $redirectUrl . '?' . $errorParam . '=csrf_token_invalid');
        exit;
    }
}
