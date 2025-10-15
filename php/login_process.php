<?php
// php/login_process.php

// Always start the session at the beginning of the script
session_start();

// Include CSRF protection
require_once 'csrf.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate CSRF token
    csrf_validate_or_redirect('../auth/index.php');
    
    // Include the database connection file
    require_once '../config/database.php';
    
    // Get username and password from the form
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Prepare a select statement to prevent SQL injection
    $sql = "SELECT user_id, username, password_hash, role FROM users WHERE username = :username OR email = :username";
    
    if ($stmt = $pdo->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Check if username exists, if yes then verify password
            if ($stmt->rowCount() == 1) {
                if ($row = $stmt->fetch()) {
                    $id = $row["user_id"];
                    $hashed_password = $row["password_hash"];
                    
                    // Verify the submitted password against the hashed password
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, so start a new session
                        session_regenerate_id(); // Security measure
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $row["username"];
                        $_SESSION["role"] = $row["role"];                            
                        
                        // Redirect user to the dashboard
                        header("location: ../pages/dashboard.php");
                        exit;
                    } else {
                        // Password is not valid, redirect back with an error
                        header("location: ../auth/index.php?error=invalid_credentials");
                        exit;
                    }
                }
            } else {
                // Username doesn't exist, redirect back with an error
                header("location: ../auth/index.php?error=invalid_credentials");
                exit;
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }

        // Close statement
        unset($stmt);
    }
    
    // Close connection
    unset($pdo);
}
?>