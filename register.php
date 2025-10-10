<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Self-Learning Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

    <div class="login-container">
        <header class="login-header">
            <h1>Join the Self-Learning Hub</h1>
            <p>Create an account to start your learning journey today.</p>
        </header>

        <div class="login-card">
            <h2>Create Account</h2>
            
            <?php
            // Display success message if registration was successful
            if (isset($_GET['success']) && $_GET['success'] == 'registered') {
                echo '<p class="success-message">Registration successful! You can now log in.</p>';
            }
            // Display error messages if registration fails
            if (isset($_GET['error'])) {
                $errorMsg = '';
                switch ($_GET['error']) {
                    case 'passwordmismatch':
                        $errorMsg = 'Passwords do not match.';
                        break;
                    case 'usertaken':
                        $errorMsg = 'Username or email is already taken.';
                        break;
                    case 'invalidemail':
                        $errorMsg = 'Please enter a valid email address.';
                        break;
                    default:
                        $errorMsg = 'An unknown error occurred.';
                }
                echo '<p class="error-message">Error: ' . htmlspecialchars($errorMsg) . '</p>';
            }
            ?>

            <form action="php/register_process.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-login">Create Account</button>
            </form>
            <div class="login-links" style="justify-content: center; margin-top: 20px;">
                <a href="index.php">Already have an account? Login</a>
            </div>
        </div>

        <footer class="login-footer">
            <p>© 2025 Self-Learning Hub | All Rights Reserved</p>
        </footer>
    </div>

</body>
</html>