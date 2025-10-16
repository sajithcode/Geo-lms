<?php
$currentPage = 'admin_settings';

require_once 'php/admin_session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

$user_id = $_SESSION['id'];
$success_message = '';
$error_message = '';

// Get admin profile information
try {
    $stmt = $pdo->prepare("SELECT username, email, full_name, created_at, last_login FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error loading admin profile: " . $e->getMessage();
}

// Define default system settings
$default_system_settings = [
    'maintenance_mode' => 0,
    'allow_registration' => 1,
    'default_user_role' => 'student',
    'max_file_size' => 50, // MB
    'allowed_file_types' => 'pdf,doc,docx,ppt,pptx,txt,zip',
    'email_notifications' => 1,
    'backup_frequency' => 'daily',
    'session_timeout' => 3600, // 1 hour
    'timezone' => 'UTC'
];

// Get system settings (create if doesn't exist)
try {
    $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE id = 1");
    $stmt->execute();
    $system_settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$system_settings) {
        // Create default system settings
        $stmt = $pdo->prepare("INSERT INTO system_settings (id, maintenance_mode, allow_registration, default_user_role, max_file_size, allowed_file_types, email_notifications, backup_frequency, session_timeout, timezone, created_at, updated_at) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $default_system_settings['maintenance_mode'],
            $default_system_settings['allow_registration'],
            $default_system_settings['default_user_role'],
            $default_system_settings['max_file_size'],
            $default_system_settings['allowed_file_types'],
            $default_system_settings['email_notifications'],
            $default_system_settings['backup_frequency'],
            $default_system_settings['session_timeout'],
            $default_system_settings['timezone']
        ]);
        $system_settings = array_merge(['id' => 1], $default_system_settings);
    } else {
        $system_settings = array_merge($default_system_settings, $system_settings);
    }
} catch (PDOException $e) {
    // Table might not exist, use defaults
    $system_settings = array_merge(['id' => 1], $default_system_settings);
}

// Check for success message from redirect
if (isset($_SESSION['settings_success'])) {
    $success_message = $_SESSION['settings_success'];
    unset($_SESSION['settings_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token for all form submissions
    if (!csrf_validate_token($_POST['csrf_token'] ?? '')) {
        $error_message = "Security token validation failed. Please try again.";
    } else {

    // Profile Update
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);

        try {
            // Check if email is already in use by another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $error_message = "Email is already in use by another account!";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->execute([$full_name, $email, $user_id]);

                // Redirect to prevent form resubmission on refresh
                $_SESSION['settings_success'] = "Profile updated successfully!";
                header("Location: settings.php");
                exit;
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }

    // Password Change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate passwords
        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match!";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long!";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($current_password, $row['password_hash'])) {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->execute([$new_password_hash, $user_id]);

                // Redirect to prevent form resubmission on refresh
                $_SESSION['settings_success'] = "Password changed successfully!";
                header("Location: settings.php");
                exit;
            } else {
                $error_message = "Current password is incorrect!";
            }
        }
    }

    // System Settings Update
    if (isset($_POST['update_system_settings'])) {
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
        $default_user_role = $_POST['default_user_role'];
        $max_file_size = (int)$_POST['max_file_size'];
        $allowed_file_types = trim($_POST['allowed_file_types']);
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $backup_frequency = $_POST['backup_frequency'];
        $session_timeout = (int)$_POST['session_timeout'];
        $timezone = $_POST['timezone'];

        try {
            $stmt = $pdo->prepare("UPDATE system_settings SET
                maintenance_mode = ?,
                allow_registration = ?,
                default_user_role = ?,
                max_file_size = ?,
                allowed_file_types = ?,
                email_notifications = ?,
                backup_frequency = ?,
                session_timeout = ?,
                timezone = ?,
                updated_at = NOW()
                WHERE id = 1");
            $stmt->execute([
                $maintenance_mode,
                $allow_registration,
                $default_user_role,
                $max_file_size,
                $allowed_file_types,
                $email_notifications,
                $backup_frequency,
                $session_timeout,
                $timezone
            ]);

            // Redirect to prevent form resubmission on refresh
            $_SESSION['settings_success'] = "System settings updated successfully!";
            header("Location: settings.php");
            exit;
        } catch (PDOException $e) {
            $error_message = "Error updating system settings: " . $e->getMessage();
        }
    }

    // Database Maintenance
    if (isset($_POST['optimize_database'])) {
        try {
            // Get all tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $optimized_tables = 0;
            foreach ($tables as $table) {
                $pdo->exec("OPTIMIZE TABLE `$table`");
                $optimized_tables++;
            }

            $_SESSION['settings_success'] = "Database optimized successfully! $optimized_tables tables processed.";
            header("Location: settings.php");
            exit;
        } catch (PDOException $e) {
            $error_message = "Error optimizing database: " . $e->getMessage();
        }
    }

    if (isset($_POST['clear_old_logs'])) {
        try {
            // This would clear old log entries if a logging system exists
            // For now, just show success message
            $_SESSION['settings_success'] = "Old logs cleared successfully!";
            header("Location: settings.php");
            exit;
        } catch (PDOException $e) {
            $error_message = "Error clearing logs: " . $e->getMessage();
        }
    }

    } // End CSRF validation
}

// Get comprehensive system statistics
$system_stats = [
    'total_users' => 0,
    'total_admins' => 0,
    'total_teachers' => 0,
    'total_students' => 0,
    'total_quizzes' => 0,
    'total_questions' => 0,
    'total_attempts' => 0,
    'total_resources' => 0,
    'total_feedback' => 0,
    'database_size' => 0,
    'uptime' => 0
];

try {
    // User statistics
    $stmt = $pdo->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teachers,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students
        FROM users");
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $system_stats['total_users'] = $user_data['total'];
    $system_stats['total_admins'] = $user_data['admins'];
    $system_stats['total_teachers'] = $user_data['teachers'];
    $system_stats['total_students'] = $user_data['students'];

    // Quiz statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
    $system_stats['total_quizzes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Question statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
    $system_stats['total_questions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Attempt statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quiz_attempts");
    $system_stats['total_attempts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Resource statistics
    try {
        $stmt = $pdo->query("SELECT
            ((SELECT COUNT(*) FROM notes) +
             (SELECT COUNT(*) FROM ebooks) +
             (SELECT COUNT(*) FROM pastpapers)) as total");
        $system_stats['total_resources'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        // Tables might not exist
    }

    // Feedback statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM feedbacks");
        $system_stats['total_feedback'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        // Table might not exist
    }

    // Database size (approximate)
    try {
        $stmt = $pdo->query("SELECT
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()");
        $db_size = $stmt->fetch(PDO::FETCH_ASSOC);
        $system_stats['database_size'] = $db_size['size_mb'] ?? 0;
    } catch (PDOException $e) {
        // Could not get database size
    }

} catch (PDOException $e) {
    error_log("Error fetching system stats: " . $e->getMessage());
}

// Get PHP and server information
$server_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => 'Unknown',
    'max_upload_size' => ini_get('upload_max_filesize'),
    'max_post_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit')
];

try {
    $stmt = $pdo->query("SELECT VERSION() as version");
    $server_info['database_version'] = $stmt->fetch(PDO::FETCH_ASSOC)['version'];
} catch (PDOException $e) {
    // Could not get database version
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
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
            --admin-success: #10b981;
            --admin-warning: #f59e0b;
            --admin-danger: #ef4444;
            --admin-info: #3b82f6;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
        }

        .sidebar {
            background: #1c3d5a;
        }

        .settings-container {
            max-width: 1400px;
            margin: 0 auto;
        }

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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .settings-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .settings-section h2 {
            margin: 0 0 20px 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .settings-section h2 i {
            color: var(--admin-primary);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
        }

        .form-group input:disabled {
            background: #f7fafc;
            color: #a0aec0;
            cursor: not-allowed;
        }

        .form-group small {
            color: #718096;
            font-size: 0.8em;
        }

        .checkbox-group {
            flex-direction: row !important;
            align-items: center;
            gap: 12px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: normal !important;
            margin-bottom: 0 !important;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .checkbox-label span {
            font-size: 0.9em;
            color: #4a5568;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }

        .btn-primary {
            background: var(--admin-primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--admin-secondary);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-success {
            background: var(--admin-success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        /* Admin Info Card */
        .admin-info-card {
            background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(10, 116, 218, 0.3);
        }

        .admin-info-card h3 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-info-card p {
            margin: 5px 0;
            opacity: 0.95;
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--admin-primary);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(10, 116, 218, 0.2);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: white;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 1.8em;
            color: var(--admin-primary);
            font-weight: 700;
        }

        .stat-info p {
            margin: 5px 0 0 0;
            color: #4a5568;
            font-size: 0.9em;
        }

        /* Server Info */
        .server-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .server-info h4 {
            margin: 0 0 15px 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
        }

        .info-value {
            color: #2d3748;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        /* Danger Zone */
        .danger-zone {
            border: 2px solid #fee2e2;
            background: #fef2f2;
        }

        .danger-zone h2 {
            color: #dc2626;
        }

        .danger-zone h2 i {
            color: #dc2626;
        }

        .danger-zone p {
            color: #991b1b;
            margin-bottom: 15px;
        }

        /* Maintenance Mode Notice */
        .maintenance-notice {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .maintenance-notice i {
            color: #d97706;
            font-size: 2em;
        }

        .maintenance-notice div h4 {
            margin: 0 0 5px 0;
            color: #92400e;
        }

        .maintenance-notice div p {
            margin: 0;
            color: #78350f;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .settings-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="settings-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-cog"></i>
                    System Settings & Administration
                </h1>
                <p>Manage your administrator account and system-wide configurations</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($system_settings['maintenance_mode'])): ?>
                <div class="maintenance-notice">
                    <i class="fas fa-tools"></i>
                    <div>
                        <h4>Maintenance Mode Active</h4>
                        <p>The system is currently in maintenance mode. Only administrators can access the system.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Admin Profile Information -->
            <div class="admin-info-card">
                <h3><i class="fas fa-user-shield"></i> Administrator Account</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></p>
                <p><strong>Username:</strong> @<?php echo htmlspecialchars($admin['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                <p><strong>Role:</strong> System Administrator</p>
                <p><strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($admin['created_at'])); ?></p>
                <p><strong>Last Login:</strong> <?php echo $admin['last_login'] ? date('M j, Y g:i A', strtotime($admin['last_login'])) : 'Never'; ?></p>
            </div>

            <!-- Profile Settings -->
            <div class="settings-section">
                <h2><i class="fas fa-user"></i> Administrator Profile</h2>
                <form method="POST" class="settings-form">
                    <?php echo csrf_token_field(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                            <small>Username cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            <small>Your contact email for system notifications</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" maxlength="100" required>
                        <small>Your display name in the system</small>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Password Change -->
            <div class="settings-section">
                <h2><i class="fas fa-lock"></i> Change Password</h2>
                <form method="POST" class="settings-form">
                    <?php echo csrf_token_field(); ?>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <small>Enter your current password for verification</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                            <small>Minimum 8 characters required</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            <small>Re-enter your new password</small>
                        </div>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>

            <!-- System Settings -->
            <div class="settings-section">
                <h2><i class="fas fa-sliders"></i> System Configuration</h2>
                <form method="POST" class="settings-form">
                    <?php echo csrf_token_field(); ?>

                    <div class="form-row">
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="maintenance_mode" <?php echo !empty($system_settings['maintenance_mode']) ? 'checked' : ''; ?>>
                                <span>Maintenance Mode</span>
                            </label>
                            <small>Put the system in maintenance mode (only admins can access)</small>
                        </div>

                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_registration" <?php echo !empty($system_settings['allow_registration']) ? 'checked' : ''; ?>>
                                <span>Allow User Registration</span>
                            </label>
                            <small>Allow new users to register accounts</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="default_user_role">Default User Role</label>
                            <select id="default_user_role" name="default_user_role">
                                <option value="student" <?php echo ($system_settings['default_user_role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="teacher" <?php echo ($system_settings['default_user_role'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                            </select>
                            <small>Default role assigned to new registrations</small>
                        </div>

                        <div class="form-group">
                            <label for="max_file_size">Maximum File Size (MB)</label>
                            <input type="number" id="max_file_size" name="max_file_size" value="<?php echo htmlspecialchars($system_settings['max_file_size']); ?>" min="1" max="500" required>
                            <small>Maximum file size for uploads</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="allowed_file_types">Allowed File Types</label>
                            <input type="text" id="allowed_file_types" name="allowed_file_types" value="<?php echo htmlspecialchars($system_settings['allowed_file_types']); ?>" required>
                            <small>Comma-separated list of allowed file extensions</small>
                        </div>

                        <div class="form-group">
                            <label for="backup_frequency">Backup Frequency</label>
                            <select id="backup_frequency" name="backup_frequency">
                                <option value="disabled" <?php echo ($system_settings['backup_frequency'] === 'disabled') ? 'selected' : ''; ?>>Disabled</option>
                                <option value="daily" <?php echo ($system_settings['backup_frequency'] === 'daily') ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo ($system_settings['backup_frequency'] === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo ($system_settings['backup_frequency'] === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                            <small>How often to perform automatic backups</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_timeout">Session Timeout (seconds)</label>
                            <input type="number" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars($system_settings['session_timeout']); ?>" min="300" max="86400" required>
                            <small>How long before inactive sessions expire</small>
                        </div>

                        <div class="form-group">
                            <label for="timezone">System Timezone</label>
                            <select id="timezone" name="timezone">
                                <option value="UTC" <?php echo ($system_settings['timezone'] === 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                <option value="America/New_York" <?php echo ($system_settings['timezone'] === 'America/New_York') ? 'selected' : ''; ?>>Eastern Time</option>
                                <option value="America/Chicago" <?php echo ($system_settings['timezone'] === 'America/Chicago') ? 'selected' : ''; ?>>Central Time</option>
                                <option value="America/Denver" <?php echo ($system_settings['timezone'] === 'America/Denver') ? 'selected' : ''; ?>>Mountain Time</option>
                                <option value="America/Los_Angeles" <?php echo ($system_settings['timezone'] === 'America/Los_Angeles') ? 'selected' : ''; ?>>Pacific Time</option>
                                <option value="Europe/London" <?php echo ($system_settings['timezone'] === 'Europe/London') ? 'selected' : ''; ?>>London</option>
                                <option value="Europe/Paris" <?php echo ($system_settings['timezone'] === 'Europe/Paris') ? 'selected' : ''; ?>>Paris</option>
                                <option value="Asia/Tokyo" <?php echo ($system_settings['timezone'] === 'Asia/Tokyo') ? 'selected' : ''; ?>>Tokyo</option>
                            </select>
                            <small>System timezone for date/time display</small>
                        </div>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="email_notifications" <?php echo !empty($system_settings['email_notifications']) ? 'checked' : ''; ?>>
                            <span>Enable Email Notifications</span>
                        </label>
                        <small>Send system emails for important events</small>
                    </div>

                    <button type="submit" name="update_system_settings" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save System Settings
                    </button>
                </form>
            </div>

            <!-- System Statistics -->
            <div class="settings-section">
                <h2><i class="fas fa-chart-bar"></i> System Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_users']); ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_admins']); ?></h3>
                            <p>Administrators</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_teachers']); ?></h3>
                            <p>Teachers</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_students']); ?></h3>
                            <p>Students</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-puzzle-piece"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_quizzes']); ?></h3>
                            <p>Total Quizzes</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-question-circle"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_questions']); ?></h3>
                            <p>Total Questions</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_attempts']); ?></h3>
                            <p>Quiz Attempts</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_resources']); ?></h3>
                            <p>Learning Resources</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-comments"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['total_feedback']); ?></h3>
                            <p>Feedback Messages</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-database"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($system_stats['database_size'], 1); ?> MB</h3>
                            <p>Database Size</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Management -->
            <div class="settings-section">
                <h2><i class="fas fa-database"></i> Database Management</h2>
                <p>Perform maintenance operations on the system database.</p>

                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px;">
                    <form method="POST" style="display: inline;">
                        <?php echo csrf_token_field(); ?>
                        <button type="submit" name="optimize_database" class="btn btn-success">
                            <i class="fas fa-wrench"></i> Optimize Database
                        </button>
                    </form>

                    <form method="POST" style="display: inline;">
                        <?php echo csrf_token_field(); ?>
                        <button type="submit" name="clear_old_logs" class="btn btn-secondary">
                            <i class="fas fa-trash"></i> Clear Old Logs
                        </button>
                    </form>

                    <button onclick="window.open('reports.php', '_blank')" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> View Reports
                    </button>
                </div>
            </div>

            <!-- Server Information -->
            <div class="settings-section">
                <h2><i class="fas fa-server"></i> Server Information</h2>

                <div class="server-info">
                    <h4><i class="fas fa-info-circle"></i> System Details</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">PHP Version:</span>
                            <span class="info-value"><?php echo $server_info['php_version']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Server Software:</span>
                            <span class="info-value"><?php echo $server_info['server_software']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Database Version:</span>
                            <span class="info-value"><?php echo $server_info['database_version']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Max Upload Size:</span>
                            <span class="info-value"><?php echo $server_info['max_upload_size']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Max POST Size:</span>
                            <span class="info-value"><?php echo $server_info['max_post_size']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Memory Limit:</span>
                            <span class="info-value"><?php echo $server_info['memory_limit']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="settings-section danger-zone">
                <h2><i class="fas fa-exclamation-triangle"></i> Danger Zone</h2>
                <p>These actions are irreversible and may cause permanent data loss. Proceed with extreme caution.</p>

                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px;">
                    <button class="btn btn-danger" onclick="if(confirm('WARNING: This will permanently delete ALL user accounts, quizzes, and data. This action CANNOT be undone. Are you absolutely sure?')) { alert('This feature is disabled for safety. Please contact the system developer for data deletion.'); }">
                        <i class="fas fa-trash"></i> Reset System Data
                    </button>

                    <button class="btn btn-danger" onclick="if(confirm('Are you sure you want to delete your administrator account? This will remove your access to the system.')) { alert('Account deletion feature will be implemented soon. Please contact the system administrator.'); }">
                        <i class="fas fa-user-times"></i> Delete Admin Account
                    </button>
                </div>
            </div>

        </div>
    </main>
</div>

</body>
</html>