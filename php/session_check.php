<?php
// php/session_check.php

// Start the session
session_start();

// Check if the user is not logged in, if so then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/index.php");
    exit;
}

// Role-based access control - redirect non-students to their dashboards
if (isset($_SESSION["role"])) {
    $role = $_SESSION["role"];
    
    // If user is admin, redirect to admin dashboard
    if ($role === 'admin') {
        header("location: ../admin/dashboard.php");
        exit;
    }
    
    // If user is teacher, redirect to teacher dashboard (except for announcements and messages pages)
    if ($role === 'teacher') {
        // Allow teachers to view announcements and use messaging
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page !== 'announcements.php' && $current_page !== 'messages.php') {
            header("location: ../teacher/dashboard.php");
            exit;
        }
    }
    
    // Students continue normally to student pages
}

// Ensure backward compatibility - if 'id' exists but 'user_id' doesn't, set it
if (isset($_SESSION["id"]) && !isset($_SESSION["user_id"])) {
    $_SESSION["user_id"] = $_SESSION["id"];
}
?>