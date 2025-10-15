<?php
// php/register_process.php

// Start session
session_start();

// Include CSRF protection
require_once 'csrf.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate CSRF token
    csrf_validate_or_redirect('../auth/register.php');
    
    // Include the database connection file
    require_once '../config/database.php';

    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // --- Input Validation ---

    // 1. Check if passwords match
    if ($password !== $confirm_password) {
        header("location: ../auth/register.php?error=passwordmismatch");
        exit;
    }

    // 2. Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("location: ../auth/register.php?error=invalidemail");
        exit;
    }

    // --- Check if username or email already exists ---
    $sql_check = "SELECT user_id FROM users WHERE username = :username OR email = :email";

    if ($stmt_check = $pdo->prepare($sql_check)) {
        $stmt_check->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt_check->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            // Username or email is already taken
            header("location: ../auth/register.php?error=usertaken");
            exit;
        }
    }
    unset($stmt_check); // Close statement

    // --- Insert new user into the database ---
    
    // Hash the password for security
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Prepare an insert statement
    $sql_insert = "INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)";

    if ($stmt_insert = $pdo->prepare($sql_insert)) {
        // Set a default role for new users
        $role = 'student';

        // Bind variables to the prepared statement
        $stmt_insert->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt_insert->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt_insert->bindParam(":password_hash", $password_hash, PDO::PARAM_STR);
        $stmt_insert->bindParam(":role", $role, PDO::PARAM_STR);

        // Attempt to execute the statement
        if ($stmt_insert->execute()) {
            // Redirect to login page with a success message
            header("location: ../auth/index.php?success=registered");
            exit;
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        unset($stmt_insert); // Close statement
    }
    
    unset($pdo); // Close connection
}
?>