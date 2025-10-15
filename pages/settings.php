<?php
$currentPage = 'settings';

// Include session check and database connection
require_once '../php/session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

$user_id = $_SESSION['id'];
$success_message = '';
$error_message = '';

// Detect if 'bio' column exists in users table to support older schemas
try {
    $cols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    // If this fails, fall back to attempting to select with the full column list (this will raise later if truly missing)
    $cols = [];
}

$select_columns = ['username', 'email', 'full_name', 'profile_picture'];
if (in_array('bio', $cols)) {
    // include bio if present
    array_splice($select_columns, 2, 0, 'bio'); // insert before profile_picture to keep original order
}

$select_sql = 'SELECT ' . implode(', ', $select_columns) . ' FROM users WHERE user_id = ?';
$stmt = $pdo->prepare($select_sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user settings (create if doesn't exist)
$stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$default_settings = [
    'theme' => 'light',
    'language' => 'en',
    'notifications_enabled' => 1,
    'email_notifications' => 1,
    'show_profile_publicly' => 0,
    'timezone' => 'UTC'
];

if (!$settings) {
    // Create default settings in DB
    $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, theme, language, notifications_enabled, email_notifications, show_profile_publicly, timezone) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $default_settings['theme'], $default_settings['language'], $default_settings['notifications_enabled'], $default_settings['email_notifications'], $default_settings['show_profile_publicly'], $default_settings['timezone']]);
    $settings = $default_settings;
} else {
    // Merge DB settings with defaults to avoid undefined keys
    $settings = array_merge($default_settings, $settings);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token for all form submissions
    if (!csrf_validate_token($_POST['csrf_token'] ?? '')) {
        $error_message = "Security token validation failed. Please try again.";
    } else {
    
    // Profile Update
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;

        try {
            // Build update dynamically depending on whether 'bio' exists
            if (in_array('bio', $cols)) {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->execute([$full_name, $bio, $user_id]);
            } else {
                // Update only available columns
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->execute([$full_name, $user_id]);
            }
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $pdo->prepare($select_sql);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long!";
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
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Current password is incorrect!";
            }
        }
    }
    
    // Settings Update
    if (isset($_POST['update_settings'])) {
        $theme = $_POST['theme'];
        $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $show_profile_publicly = isset($_POST['show_profile_publicly']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE user_settings SET theme = ?, notifications_enabled = ?, email_notifications = ?, show_profile_publicly = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([$theme, $notifications_enabled, $email_notifications, $show_profile_publicly, $user_id]);
            $success_message = "Settings updated successfully!";
            
            // Refresh settings data
            $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_message = "Error updating settings: " . $e->getMessage();
        }
    }
    
    } // End CSRF validation
}

include '../includes/header.php';
?>
<script>document.title = 'Settings - Self-Learning Hub';</script>
<link rel="stylesheet" href="../assets/css/settings.css">

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Settings</h1>
            <p>Manage your account settings and preferences.</p>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            
            <!-- Profile Settings -->
            <div class="settings-section">
                <h2><i class="fa-solid fa-user"></i> Profile Information</h2>
                <form method="POST" class="settings-form">
                    <?php echo csrf_token_field(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small>Username cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="4" maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <small>Tell us a bit about yourself (max 500 characters)</small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Password Change -->
            <div class="settings-section">
                <h2><i class="fa-solid fa-lock"></i> Change Password</h2>
                <form method="POST" class="settings-form">
                    <?php echo csrf_token_field(); ?>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fa-solid fa-key"></i> Change Password
                    </button>
                </form>
            </div>

            <!-- Application Settings -->
            <div class="settings-section">
                <h2><i class="fa-solid fa-cog"></i> Application Preferences</h2>
                <form method="POST" class="settings-form">
                    <?php echo csrf_token_field(); ?>
                    <div class="form-group">
                        <label for="theme">Theme</label>
                        <select id="theme" name="theme">
                            <option value="light" <?php echo ($settings['theme'] === 'light') ? 'selected' : ''; ?>>Light</option>
                            <option value="dark" <?php echo ($settings['theme'] === 'dark') ? 'selected' : ''; ?>>Dark</option>
                            <option value="auto" <?php echo ($settings['theme'] === 'auto') ? 'selected' : ''; ?>>Auto (System)</option>
                        </select>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notifications_enabled" <?php echo !empty($settings['notifications_enabled']) ? 'checked' : ''; ?>>
                            <span>Enable notifications</span>
                        </label>
                        <small>Receive in-app notifications about quizzes and updates</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="email_notifications" <?php echo !empty($settings['email_notifications']) ? 'checked' : ''; ?>>
                            <span>Email notifications</span>
                        </label>
                        <small>Receive email notifications for important updates</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_profile_publicly" <?php echo !empty($settings['show_profile_publicly']) ? 'checked' : ''; ?>>
                            <span>Show profile publicly</span>
                        </label>
                        <small>Allow other users to view your profile information</small>
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Preferences
                    </button>
                </form>
            </div>

            <!-- Account Statistics -->
            <div class="settings-section">
                <h2><i class="fa-solid fa-chart-bar"></i> Account Statistics</h2>
                <div class="stats-grid">
                    <?php
                    // Get user statistics. Be resilient to schema differences (e.g., older DBs may not have `passed` column)
                    try {
                        $qa_cols = $pdo->query("SHOW COLUMNS FROM `quiz_attempts`")->fetchAll(PDO::FETCH_COLUMN, 0);
                    } catch (PDOException $e) {
                        $qa_cols = [];
                    }

                    if (in_array('passed', $qa_cols)) {
                        $sql = "SELECT COUNT(*) as attempts, AVG(score) as avg_score, SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed FROM quiz_attempts WHERE user_id = ?";
                    } else {
                        // If 'passed' doesn't exist, derive 'passed' from score comparing to quiz passing_score joined via quizzes table when possible.
                        $sql = "SELECT COUNT(*) as attempts, AVG(qa.score) as avg_score, SUM(CASE WHEN qa.score >= q.passing_score THEN 1 ELSE 0 END) as passed FROM quiz_attempts qa LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id WHERE qa.user_id = ?";
                    }
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$user_id]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="stat-card">

                        <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['attempts'] ?? 0; ?></h3>
                            <p>Quizzes Taken</p>
                        </div>

                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-trophy"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['passed'] ?? 0; ?></h3>
                            <p>Quizzes Passed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-percentage"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['avg_score'] ? round($stats['avg_score'], 1) . '%' : 'N/A'; ?></h3>
                            <p>Average Score</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="settings-section danger-zone">
                <h2><i class="fa-solid fa-exclamation-triangle"></i> Danger Zone</h2>
                <p>Once you delete your account, there is no going back. Please be certain.</p>
                <button class="btn btn-danger" onclick="alert('Account deletion feature coming soon!')">
                    <i class="fa-solid fa-trash"></i> Delete Account
                </button>
            </div>

        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
