<?php
$currentPage = 'teacher_settings';

// Include teacher session check and database connection
require_once 'php/teacher_session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

$user_id = $_SESSION['id'];
$success_message = '';
$error_message = '';

// Detect if 'bio' column exists in users table to support older schemas
try {
    $cols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    $cols = [];
}

$select_columns = ['username', 'email', 'full_name', 'profile_picture'];
if (in_array('bio', $cols)) {
    array_splice($select_columns, 2, 0, 'bio');
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
    'show_profile_publicly' => 1,
    'timezone' => 'UTC'
];

if (!$settings) {
    // Create default settings in DB
    try {
        $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, theme, language, notifications_enabled, email_notifications, show_profile_publicly, timezone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $default_settings['theme'], $default_settings['language'], $default_settings['notifications_enabled'], $default_settings['email_notifications'], $default_settings['show_profile_publicly'], $default_settings['timezone']]);
        $settings = $default_settings;
    } catch (PDOException $e) {
        error_log("Error creating default settings: " . $e->getMessage());
        $settings = $default_settings;
    }
} else {
    $settings = array_merge($default_settings, $settings);
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
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;

        try {
            // Check if email is already in use by another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $error_message = "Email is already in use by another account!";
            } else {
                // Build update dynamically depending on whether 'bio' exists
                if (in_array('bio', $cols)) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, bio = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                    $stmt->execute([$full_name, $email, $bio, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                    $stmt->execute([$full_name, $email, $user_id]);
                }
                
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
                
                // Redirect to prevent form resubmission on refresh
                $_SESSION['settings_success'] = "Password changed successfully!";
                header("Location: settings.php");
                exit;
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
            
            // Redirect to prevent form resubmission on refresh
            $_SESSION['settings_success'] = "Settings updated successfully!";
            header("Location: settings.php");
            exit;
        } catch (PDOException $e) {
            $error_message = "Error updating settings: " . $e->getMessage();
        }
    }
    
    } // End CSRF validation
}

// Get teacher statistics
$teacher_stats = [
    'quizzes_created' => 0,
    'total_questions' => 0,
    'total_attempts' => 0,
    'total_students' => 0,
    'resources_uploaded' => 0,
    'announcements_created' => 0
];

try {
    // Quizzes created
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quizzes WHERE created_by = ?");
    $stmt->execute([$user_id]);
    $teacher_stats['quizzes_created'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total questions in teacher's quizzes
    $stmt = $pdo->prepare("SELECT COUNT(q.question_id) as count FROM questions q 
                           INNER JOIN quizzes qz ON q.quiz_id = qz.quiz_id 
                           WHERE qz.created_by = ?");
    $stmt->execute([$user_id]);
    $teacher_stats['total_questions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total attempts on teacher's quizzes
    $stmt = $pdo->prepare("SELECT COUNT(qa.attempt_id) as count FROM quiz_attempts qa 
                           INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id 
                           WHERE q.created_by = ?");
    $stmt->execute([$user_id]);
    $teacher_stats['total_attempts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $teacher_stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Resources uploaded (notes, ebooks, pastpapers)
    try {
        $stmt = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM notes WHERE uploaded_by = ?) + 
            (SELECT COUNT(*) FROM ebooks WHERE uploaded_by = ?) + 
            (SELECT COUNT(*) FROM pastpapers WHERE uploaded_by = ?) as total");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $teacher_stats['resources_uploaded'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        // Tables might not exist
    }
    
    // Announcements created
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM announcements WHERE published_by = ?");
        $stmt->execute([$user_id]);
        $teacher_stats['announcements_created'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        // Table might not exist
    }
} catch (PDOException $e) {
    error_log("Error fetching teacher stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Teacher Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
    <style>
        :root {
            --teacher-primary: #10b981;
            --teacher-secondary: #059669;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #059669 0%, #047857 100%);
        }

        .main-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .main-header h1 {
            color:white;
            margin: 0 0 8px 0;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .main-header p {
            color:white;
            margin: 0;
            opacity: 0.95;
        }

        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
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
            color: var(--teacher-primary);
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
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
            border-color: var(--teacher-primary);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.2);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            color: white;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 2em;
            color: var(--teacher-primary);
            font-weight: 700;
        }

        .stat-info p {
            margin: 5px 0 0 0;
            color: #4a5568;
            font-size: 0.9em;
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
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Teacher-specific styling */
        .teacher-info-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .teacher-info-card h3 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .teacher-info-card p {
            margin: 5px 0;
            opacity: 0.95;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1><i class="fas fa-cog"></i> Settings</h1>
            <p>Manage your teacher account settings and preferences</p>
        </header>

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
            
            <!-- Teacher Info Card -->
            <div class="teacher-info-card">
                <h3><i class="fas fa-chalkboard-teacher"></i> Teacher Account</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></p>
                <p><strong>Username:</strong> @<?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Role:</strong> Teacher</p>
            </div>

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
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <small>Your contact email address</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" maxlength="100" required>
                    </div>
                    
                    <?php if (in_array('bio', $cols)): ?>
                    <div class="form-group">
                        <label for="bio">Professional Bio</label>
                        <textarea id="bio" name="bio" rows="4" maxlength="500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        <small>Share your teaching experience and qualifications (max 500 characters)</small>
                    </div>
                    <?php endif; ?>
                    
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
                <h2><i class="fa-solid fa-sliders"></i> Application Preferences</h2>
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
                        <small>Receive in-app notifications about student activity and updates</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="email_notifications" <?php echo !empty($settings['email_notifications']) ? 'checked' : ''; ?>>
                            <span>Email notifications</span>
                        </label>
                        <small>Receive email notifications for quiz submissions and important updates</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_profile_publicly" <?php echo !empty($settings['show_profile_publicly']) ? 'checked' : ''; ?>>
                            <span>Show profile publicly</span>
                        </label>
                        <small>Allow students to view your profile information</small>
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Preferences
                    </button>
                </form>
            </div>

            <!-- Teaching Statistics -->
            <div class="settings-section">
                <h2><i class="fa-solid fa-chart-bar"></i> Teaching Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $teacher_stats['quizzes_created']; ?></h3>
                            <p>Quizzes Created</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-question-circle"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $teacher_stats['total_questions']; ?></h3>
                            <p>Questions Created</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $teacher_stats['total_attempts']; ?></h3>
                            <p>Quiz Attempts</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $teacher_stats['total_students']; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $teacher_stats['resources_uploaded']; ?></h3>
                            <p>Resources Uploaded</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-bullhorn"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $teacher_stats['announcements_created']; ?></h3>
                            <p>Announcements Made</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="settings-section danger-zone">
                <h2><i class="fa-solid fa-exclamation-triangle"></i> Danger Zone</h2>
                <p>These actions cannot be undone. Please be careful.</p>
                <button class="btn btn-danger" onclick="if(confirm('Are you sure you want to delete your account? This action cannot be undone and all your quizzes, questions, and resources will be permanently deleted.')) { alert('Account deletion feature will be implemented soon. Please contact the administrator to delete your account.'); }">
                    <i class="fa-solid fa-trash"></i> Delete Account
                </button>
            </div>

        </div>
    </main>
</div>

</body>
</html>
