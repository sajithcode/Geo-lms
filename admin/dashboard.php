<?php
$currentPage = 'admin_dashboard';

// Include admin session check
require_once 'php/admin_session_check.php';
require_once '../config/database.php';

// Get system statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch()['total'];
    
    // Total quizzes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
    $total_quizzes = $stmt->fetch();
    $total_quizzes = $total_quizzes ? $total_quizzes['total'] : 0;
    
    // Total quiz attempts
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quiz_attempts");
    $total_attempts = $stmt->fetch();
    $total_attempts = $total_attempts ? $total_attempts['total'] : 0;
    
    // Total feedback
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM feedbacks");
    $total_feedback = $stmt->fetch();
    $total_feedback = $total_feedback ? $total_feedback['total'] : 0;
    
    // Recent users
    $stmt = $pdo->query("SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent feedback
    $stmt = $pdo->query("SELECT f.feedback_id, f.message, f.created_at, u.username FROM feedbacks f LEFT JOIN users u ON f.user_id = u.user_id ORDER BY f.created_at DESC LIMIT 5");
    $recent_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Geo-LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --admin-primary: #667eea;
            --admin-secondary: #764ba2;
            --admin-success: #10b981;
            --admin-warning: #f59e0b;
            --admin-danger: #ef4444;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .admin-header h1 {
            margin: 0 0 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .admin-nav {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .admin-nav-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .admin-nav-links a {
            padding: 10px 20px;
            background: var(--admin-primary);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .admin-nav-links a:hover {
            background: var(--admin-secondary);
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-box {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid var(--admin-primary);
        }
        
        .stat-box.success { border-left-color: var(--admin-success); }
        .stat-box.warning { border-left-color: var(--admin-warning); }
        .stat-box.danger { border-left-color: var(--admin-danger); }
        
        .stat-box h3 {
            margin: 0 0 8px;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }
        
        .stat-box .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
        }
        
        .admin-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        
        .admin-section h2 {
            margin: 0 0 16px;
            font-size: 18px;
            color: #111827;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th {
            text-align: left;
            padding: 12px;
            background: #f9fafb;
            font-weight: 600;
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .admin-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .admin-table tr:hover {
            background: #f9fafb;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-badge.admin {
            background: #fef3c7;
            color: #92400e;
        }
        
        .role-badge.instructor {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-badge.student {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>

<div class="admin-dashboard">
    <div class="admin-header">
        <h1>
            <i class="fa-solid fa-shield-halved"></i>
            Admin Dashboard
            <span class="admin-badge">ADMINISTRATOR</span>
        </h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>

    <div class="admin-nav">
        <div class="admin-nav-links">
            <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
            <a href="quizzes.php"><i class="fa-solid fa-puzzle-piece"></i> Manage Quizzes</a>
            <a href="quiz_categories.php"><i class="fa-solid fa-tags"></i> Quiz Categories</a>
            <a href="resources.php"><i class="fa-solid fa-book"></i> Manage Resources</a>
            <a href="feedback.php"><i class="fa-solid fa-message"></i> View Feedback</a>
            <a href="../auth/logout.php" style="background: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <h3><i class="fa-solid fa-users"></i> Total Users</h3>
            <div class="stat-value"><?php echo number_format($total_users); ?></div>
        </div>
        
        <div class="stat-box success">
            <h3><i class="fa-solid fa-puzzle-piece"></i> Total Quizzes</h3>
            <div class="stat-value"><?php echo number_format($total_quizzes); ?></div>
        </div>
        
        <div class="stat-box warning">
            <h3><i class="fa-solid fa-clipboard-list"></i> Quiz Attempts</h3>
            <div class="stat-value"><?php echo number_format($total_attempts); ?></div>
        </div>
        
        <div class="stat-box danger">
            <h3><i class="fa-solid fa-message"></i> Feedback Messages</h3>
            <div class="stat-value"><?php echo number_format($total_feedback); ?></div>
        </div>
    </div>

    <div class="admin-section">
        <h2><i class="fa-solid fa-user-plus"></i> Recent Users</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_users)): ?>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-badge <?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="users.php?edit=<?php echo $user['user_id']; ?>" style="color: var(--admin-primary);">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#6b7280;">No users found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-section">
        <h2><i class="fa-solid fa-comments"></i> Recent Feedback</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Message</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_feedback)): ?>
                    <?php foreach ($recent_feedback as $fb): ?>
                        <tr>
                            <td><?php echo $fb['feedback_id']; ?></td>
                            <td><?php echo $fb['username'] ? htmlspecialchars($fb['username']) : 'Anonymous'; ?></td>
                            <td><?php echo htmlspecialchars(substr($fb['message'], 0, 100)); ?><?php echo strlen($fb['message']) > 100 ? '...' : ''; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($fb['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#6b7280;">No feedback yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
