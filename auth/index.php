<?php
// Start session to check if user is already logged in
session_start();

// Include CSRF protection
require_once '../php/csrf.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../pages/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF--8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Self-Learning Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            // Display error message if login fails
            if (isset($_GET['error'])) {
                if ($_GET['error'] == 'invalid_credentials') {
                    echo '<p class="error-message">Error: Invalid username or password.</p>';
                } elseif ($_GET['error'] == 'csrf_token_invalid') {
                    echo '<p class="error-message">Error: Security token invalid. Please try again.</p>';
                }
            }
            ?>
            <h2>Login</h2>
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

        <footer class="footer">
            <p>© 2025 Self-Learning Hub | All Rights Reserved</p>
        </footer>
    </div>

</body>
</html>