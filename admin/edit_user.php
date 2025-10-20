<?php
$currentPage = 'admin_users';

require_once 'php/admin_session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

$form_errors = [];
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Check if user_id is provided
if (!$user_id) {
    $_SESSION['error_message'] = 'User not found';
    header('Location: users.php');
    exit();
}

// Fetch user data
$user = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found';
        header('Location: users.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error loading user data';
    header('Location: users.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_redirect('users.php');
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username)) {
        $form_errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $form_errors['username'] = 'Username must be at least 3 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $form_errors['username'] = 'Username can only contain letters, numbers, underscores, dots, and hyphens';
    }
    
    if (empty($email)) {
        $form_errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = 'Invalid email format';
    }
    
    if (empty($full_name)) {
        $form_errors['full_name'] = 'Full name is required';
    }
    
    if (!in_array($role, ['student', 'teacher', 'admin'])) {
        $form_errors['role'] = 'Invalid role selected';
    }
    
    // Check if username already exists (for another user)
    if (!isset($form_errors['username'])) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $form_errors['username'] = 'Username already exists';
            }
        } catch (PDOException $e) {
            error_log("Error checking username: " . $e->getMessage());
            $form_errors['username'] = 'Error checking username availability';
        }
    }
    
    // Check if email already exists (for another user)
    if (!isset($form_errors['email'])) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $form_errors['email'] = 'Email already exists';
            }
        } catch (PDOException $e) {
            error_log("Error checking email: " . $e->getMessage());
            $form_errors['email'] = 'Error checking email availability';
        }
    }
    
    // Password validation only if provided
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $form_errors['password'] = 'Password must be at least 8 characters';
        } elseif ($password !== $confirm_password) {
            $form_errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    // If no errors, update user
    if (empty($form_errors)) {
        try {
            if (!empty($password)) {
                // Update with password change
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, full_name = ?, role = ?, password_hash = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $email, $full_name, $role, $hashed_password, $user_id]);
            } else {
                // Update without password change
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, full_name = ?, role = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $email, $full_name, $role, $user_id]);
            }
            
            $_SESSION['success_message'] = "User '{$username}' updated successfully!";
            header('Location: users.php');
            exit();
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            $form_errors['general'] = 'Error updating user. Please try again.';
        }
    }
}

// Prepare form values
$form_username = $username ?? $user['username'] ?? '';
$form_email = $email ?? $user['email'] ?? '';
$form_full_name = $full_name ?? $user['full_name'] ?? '';
$form_role = $role ?? $user['role'] ?? 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --admin-primary: #0a74da;
            --admin-secondary: #1c3d5a;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
        }
        
        .sidebar {
            background: #1c3d5a;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(10, 116, 218, 0.3);
        }

        .page-header h1 {
            margin: 0 0 8px 0;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header p {
            margin: 0;
            opacity: 0.95;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            opacity: 0.9;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .breadcrumb a:hover {
            opacity: 0.7;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.95em;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(10, 116, 218, 0.1);
        }

        .form-group.has-error input,
        .form-group.has-error select {
            border-color: #ef4444;
        }

        .error-message {
            color: #ef4444;
            font-size: 0.85em;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .general-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Password Section */
        .password-section {
            background: #f0f9ff;
            border: 2px dashed #bfdbfe;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }

        .password-section p {
            margin: 0 0 15px 0;
            color: #1e40af;
            font-size: 0.9em;
            font-weight: 500;
        }

        .password-requirements {
            background: #f7fafc;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 8px;
            font-size: 0.85em;
            color: #4a5568;
        }

        .password-requirements ul {
            margin: 8px 0 0 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin: 4px 0;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--admin-primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--admin-secondary);
            box-shadow: 0 4px 12px rgba(10, 116, 218, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .role-info {
            background: #f7fafc;
            border-left: 4px solid var(--admin-primary);
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #4a5568;
            margin-top: 8px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .user-info-box {
            background: #f0f9ff;
            border-left: 4px solid #0a74da;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .user-info-box strong {
            color: #1e40af;
            display: block;
            margin-bottom: 8px;
        }

        .user-info-box p {
            margin: 4px 0;
            color: #4a5568;
            font-size: 0.9em;
        }

        @media (max-width: 640px) {
            .form-container {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-user-edit"></i> Edit User</h1>
            <p>Update user information and permissions</p>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>→</span>
                <a href="users.php"><i class="fas fa-users-cog"></i> Users</a>
                <span>→</span>
                <span>Edit User</span>
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <!-- User Info Box -->
            <div class="user-info-box">
                <strong><i class="fas fa-info-circle"></i> User ID: <?php echo $user['user_id']; ?></strong>
                <p><strong>Account Created:</strong> <?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></p>
                <?php if (isset($user['updated_at']) && $user['updated_at']): ?>
                    <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></p>
                <?php endif; ?>
            </div>

            <?php if (isset($form_errors['general'])): ?>
                <div class="general-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($form_errors['general']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <?php echo csrf_token_field(); ?>

                <div class="form-row">
                    <!-- Username -->
                    <div class="form-group <?php echo isset($form_errors['username']) ? 'has-error' : ''; ?>">
                        <label for="username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo htmlspecialchars($form_username); ?>"
                            placeholder="Enter username"
                            required
                        >
                        <?php if (isset($form_errors['username'])): ?>
                            <div class="error-message">
                                <i class="fas fa-times-circle"></i>
                                <?php echo htmlspecialchars($form_errors['username']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group <?php echo isset($form_errors['full_name']) ? 'has-error' : ''; ?>">
                        <label for="full_name">
                            <i class="fas fa-id-card"></i> Full Name
                        </label>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            value="<?php echo htmlspecialchars($form_full_name); ?>"
                            placeholder="Enter full name"
                            required
                        >
                        <?php if (isset($form_errors['full_name'])): ?>
                            <div class="error-message">
                                <i class="fas fa-times-circle"></i>
                                <?php echo htmlspecialchars($form_errors['full_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group <?php echo isset($form_errors['email']) ? 'has-error' : ''; ?>">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($form_email); ?>"
                        placeholder="Enter email address"
                        required
                    >
                    <?php if (isset($form_errors['email'])): ?>
                        <div class="error-message">
                            <i class="fas fa-times-circle"></i>
                            <?php echo htmlspecialchars($form_errors['email']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Role -->
                <div class="form-group <?php echo isset($form_errors['role']) ? 'has-error' : ''; ?>">
                    <label for="role">
                        <i class="fas fa-shield-halved"></i> User Role
                    </label>
                    <select id="role" name="role" required>
                        <option value="student" <?php echo $form_role === 'student' ? 'selected' : ''; ?>>
                            Student
                        </option>
                        <option value="teacher" <?php echo $form_role === 'teacher' ? 'selected' : ''; ?>>
                            Teacher
                        </option>
                        <option value="admin" <?php echo $form_role === 'admin' ? 'selected' : ''; ?>>
                            Administrator
                        </option>
                    </select>
                    <div class="role-info">
                        <strong>Roles:</strong>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                            <li><strong>Student:</strong> Can take quizzes, view resources, and submit feedback</li>
                            <li><strong>Teacher:</strong> Can create quizzes, manage content, and view student performance</li>
                            <li><strong>Administrator:</strong> Full system access and management capabilities</li>
                        </ul>
                    </div>
                    <?php if (isset($form_errors['role'])): ?>
                        <div class="error-message">
                            <i class="fas fa-times-circle"></i>
                            <?php echo htmlspecialchars($form_errors['role']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Password Section -->
                <div class="password-section">
                    <p><i class="fas fa-lock"></i> Leave password fields empty to keep the current password</p>
                    
                    <div class="form-row">
                        <!-- Password -->
                        <div class="form-group <?php echo isset($form_errors['password']) ? 'has-error' : ''; ?>">
                            <label for="password">
                                <i class="fas fa-lock"></i> New Password (Optional)
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Leave empty to keep current password"
                            >
                            <?php if (isset($form_errors['password'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-times-circle"></i>
                                    <?php echo htmlspecialchars($form_errors['password']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group <?php echo isset($form_errors['confirm_password']) ? 'has-error' : ''; ?>">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Confirm Password (Optional)
                            </label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Leave empty to keep current password"
                            >
                            <?php if (isset($form_errors['confirm_password'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-times-circle"></i>
                                    <?php echo htmlspecialchars($form_errors['confirm_password']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <strong><i class="fas fa-info-circle"></i> Password Requirements (if changing):</strong>
                        <ul>
                            <li>Minimum 8 characters</li>
                            <li>Mix of uppercase and lowercase letters</li>
                            <li>At least one number and special character</li>
                        </ul>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

    </main>
</div>

</body>
</html>
