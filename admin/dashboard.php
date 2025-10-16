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

        .admin-badge {
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.5em;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            margin-bottom: 15px;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }

        .stat-details h3 {
            margin: 0;
            font-size: 2em;
            color: #2d3748;
            font-weight: 700;
        }

        .stat-details p {
            margin: 5px 0 0 0;
            color: #718096;
            font-size: 0.9em;
        }

        /* Data Sections */
        .data-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .section-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-bottom: 2px solid #e2e8f0;
        }

        .section-header h2 {
            margin: 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .data-table tr:hover {
            background: #f7fafc;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85em;
            text-transform: uppercase;
        }

        .role-badge.admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .role-badge.teacher {
            background: #d1fae5;
            color: #065f46;
        }

        .role-badge.student {
            background: #e0e7ff;
            color: #3730a3;
        }

        .action-link {
            color: var(--admin-primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .action-link:hover {
            color: var(--admin-secondary);
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #cbd5e0;
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
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
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fa-solid fa-shield-halved"></i>
                Admin Dashboard
                <span class="admin-badge">ADMINISTRATOR</span>
            </h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! Manage your learning management system</p>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_users); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_quizzes); ?></h3>
                    <p>Total Quizzes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_attempts); ?></h3>
                    <p>Quiz Attempts</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fa-solid fa-message"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($total_feedback); ?></h3>
                    <p>Feedback Messages</p>
                </div>
            </div>
        </div>

        <!-- Recent Users Section -->
        <div class="data-section">
            <div class="section-header">
                <h2><i class="fa-solid fa-user-plus"></i> Recent Users</h2>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
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
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="role-badge <?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="users.php?edit=<?php echo $user['user_id']; ?>" class="action-link">
                                            <i class="fa-solid fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-users"></i>
                                        <h3>No Users Found</h3>
                                        <p>No recent users to display</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Feedback Section -->
        <div class="data-section">
            <div class="section-header">
                <h2><i class="fa-solid fa-comments"></i> Recent Feedback</h2>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
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
                                    <td><strong><?php echo $fb['username'] ? htmlspecialchars($fb['username']) : 'Anonymous'; ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($fb['message'], 0, 100)); ?><?php echo strlen($fb['message']) > 100 ? '...' : ''; ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($fb['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-comments"></i>
                                        <h3>No Feedback Yet</h3>
                                        <p>User feedback will appear here</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

</body>
</html>
