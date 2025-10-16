<?php
/**
 * Admin Login Processor
 * Handles admin authentication with role verification
 */

// Start session
session_start();

// Include CSRF protection
require_once '../../php/csrf.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate CSRF token
    csrf_validate_or_redirect('../login.php');
    
    // Include database connection
    require_once '../../config/database.php';
    
    // Get credentials from form
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Prepare statement to fetch user
    $sql = "SELECT user_id, username, password_hash, role FROM users WHERE username = :username OR email = :username";
    
    if ($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() == 1) {
                if ($row = $stmt->fetch()) {
                    $id = $row["user_id"];
                    $hashed_password = $row["password_hash"];
                    $role = $row["role"];
                    
                    // Verify password
                    if (password_verify($password, $hashed_password)) {
                        
                        // Check if user has admin role
                        if ($role !== 'admin') {
                            header("location: ../login.php?error=not_admin");
                            exit;
                        }
                        
                        // Password correct and user is admin - create session
                        session_regenerate_id(true);
                        
                        // Store session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $row["username"];
                        $_SESSION["role"] = $role;
                        $_SESSION["last_activity"] = time();
                        
                        // Update last_login in database
                        try {
                            $updateStmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
                            $updateStmt->execute([$id]);
                        } catch (PDOException $e) {
                            // Log error but don't fail login
                            error_log("Failed to update last_login: " . $e->getMessage());
                        }
                        
                        // Redirect to admin dashboard
                        header("location: ../dashboard.php");
                        exit;
                    } else {
                        // Invalid password
                        header("location: ../login.php?error=invalid_credentials");
                        exit;
                    }
                }
            } else {
                // Username doesn't exist
                header("location: ../login.php?error=invalid_credentials");
                exit;
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        unset($stmt);
    }
    
    unset($pdo);
}
?>
