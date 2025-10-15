<?php
// Admin login page
session_start();

// Include CSRF protection
require_once '../php/csrf.php';

// If user is already logged in as admin, redirect to admin dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === 'admin') {
    header("location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Geo-LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-login-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .admin-badge {
            display: inline-block;
            background: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="login-page admin-login-page">

    <div class="login-container">
        <header class="login-header">
            <span class="admin-badge">ADMIN ACCESS</span>
            <h1>Geo-LMS Administration</h1>
            <p>Secure admin panel - authorized personnel only.</p>
        </header>

        <div class="login-card">
            <?php
            // Display error messages
            if (isset($_GET['error'])) {
                $errorMsg = '';
                switch ($_GET['error']) {
                    case 'invalid_credentials':
                        $errorMsg = 'Invalid username or password.';
                        break;
                    case 'not_admin':
                        $errorMsg = 'Access denied. Admin privileges required.';
                        break;
                    case 'csrf_token_invalid':
                        $errorMsg = 'Security token invalid. Please try again.';
                        break;
                    default:
                        $errorMsg = 'An error occurred. Please try again.';
                }
                echo '<p class="error-message"><i class="fa-solid fa-exclamation-circle"></i> ' . htmlspecialchars($errorMsg) . '</p>';
            }
            ?>
            <h2>Admin Login</h2>
            <form action="php/admin_login_process.php" method="POST">
                <?php echo csrf_token_field(); ?>
                <div class="form-group">
                    <label for="username">Admin Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-shield-halved"></i> Admin Login
                </button>
            </form>
            <div class="login-links" style="justify-content: center;">
                <a href="../auth/index.php">← Back to User Login</a>
            </div>
        </div>

        <footer class="footer">
            <p>© 2025 Geo-LMS | Admin Panel</p>
        </footer>
    </div>

    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
