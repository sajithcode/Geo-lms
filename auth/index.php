<?php
// Start session to check if user is already logged in
session_start();

// Include CSRF protection
require_once '../php/csrf.php';

// If user is already logged in, redirect to appropriate dashboard based on role
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $role = $_SESSION["role"] ?? 'student';
    
    if ($role === 'admin') {
        header("location: ../admin/dashboard.php");
    } elseif ($role === 'teacher') {
        header("location: ../teacher/dashboard.php");
    } else {
        header("location: ../pages/dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Self-Learning Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">

    <div class="login-container">
        <header class="login-header">
            <h1>Welcome to Self-Learning Hub</h1>
            <p>Learn anytime, anywhere. Track your progress, take quizzes, and interact.</p>
        </header>

        <div class="login-card">
            <?php
            // Display success message if redirected from registration
            if (isset($_GET['success'])) {
                if ($_GET['success'] == 'registered') {
                    echo '<p class="success-message">Registration successful! Please login with your credentials.</p>';
                }
            }
            
            // Display error message if login fails
            if (isset($_GET['error'])) {
                if ($_GET['error'] == 'invalid_credentials') {
                    echo '<p class="error-message">Error: Invalid username or password.</p>';
                } elseif ($_GET['error'] == 'csrf_token_invalid') {
                    echo '<p class="error-message">Error: Security token invalid. Please try again.</p>';
                } elseif ($_GET['error'] == 'session_expired') {
                    echo '<p class="error-message">Your session has expired. Please login again.</p>';
                }
            }
            ?>
            <h2>Login</h2>
            <p style="text-align: center; color: #6b7280; font-size: 14px; margin: 10px 0 20px;">
                Sign in to access your dashboard
            </p>
            <form action="../php/login_process.php" method="POST">
                <?php echo csrf_token_field(); ?>
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
            <div class="login-links">
                <a href="#">Forgot Password?</a>
                <a href="register.php">Create Account</a>
            </div>
        </div>

        <!-- Role Information Box -->
        <div class="role-info-box">
            <h3 style="margin: 0 0 16px; color: #111827; font-size: 18px; text-align: center;">
                <i class="fa-solid fa-circle-info" style="color: #3b82f6;"></i> Access Levels
            </h3>
            <div class="role-grid">
                <div class="role-item">
                    <div class="role-icon student-icon">👨‍🎓</div>
                    <h4>Student</h4>
                    <p>Access learning materials, take quizzes, track progress</p>
                </div>
                <div class="role-item">
                    <div class="role-icon teacher-icon">👨‍🏫</div>
                    <h4>Teacher</h4>
                    <p>Create quizzes, upload resources, monitor students</p>
                </div>
                <div class="role-item">
                    <div class="role-icon admin-icon">👨‍💼</div>
                    <h4>Admin</h4>
                    <p>Full system access, user management, analytics</p>
                </div>
            </div>
        </div>

        <footer class="footer">
            <p>© 2025 Self-Learning Hub | All Rights Reserved</p>
        </footer>
    </div>

</body>
</html>