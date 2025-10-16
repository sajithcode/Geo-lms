<?php
/**
 * Teacher Session Check
 * Ensures only logged-in teachers can access teacher pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/index.php");
    exit;
}

// Check if user has teacher role
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'teacher') {
    // If not a teacher, redirect based on actual role
    if ($_SESSION["role"] === 'admin') {
        header("location: ../admin/dashboard.php");
    } else {
        header("location: ../pages/dashboard.php");
    }
    exit;
}

// Backward compatibility: sync id and user_id
if (isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['id'];
} elseif (isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    $_SESSION['id'] = $_SESSION['user_id'];
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
