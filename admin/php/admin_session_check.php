<?php
/**
 * Admin Session Check
 * 
 * This file must be included at the top of every admin page
 * Verifies that the user is logged in and has admin role
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Redirect to admin login page
    header("location: ../admin/login.php");
    exit;
}

// Check if user has admin role
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    // User is logged in but not an admin - redirect to user dashboard
    header("location: ../pages/dashboard.php");
    exit;
}

// Optional: Update last activity timestamp for session timeout
$_SESSION['last_activity'] = time();
?>
