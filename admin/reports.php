<?php
$currentPage = 'admin_reports';

require_once 'php/admin_session_check.php';
require_once '../config/database.php';

// Get date range parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get comprehensive system statistics
try {
    // User statistics
    $stmt = $pdo->query("SELECT
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teacher_count,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_count,
        COUNT(CASE WHEN created_at >= '$date_from' AND created_at <= '$date_to' THEN 1 END) as new_users_period
        FROM users");
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Quiz statistics - check if is_active column exists
    try {
        $stmt = $pdo->query("SELECT
            COUNT(*) as total_quizzes,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_quizzes,
            COUNT(CASE WHEN created_at >= '$date_from' AND created_at <= '$date_to' THEN 1 END) as new_quizzes_period
            FROM quizzes");
        $quiz_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Column might not exist, try without is_active
        $stmt = $pdo->query("SELECT
            COUNT(*) as total_quizzes,
            COUNT(*) as active_quizzes,
            COUNT(CASE WHEN created_at >= '$date_from' AND created_at <= '$date_to' THEN 1 END) as new_quizzes_period
            FROM quizzes");
        $quiz_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Quiz attempt statistics
    $stmt = $pdo->query("SELECT
        COUNT(*) as total_attempts,
        AVG(score) as avg_score,
        MAX(score) as highest_score,
        MIN(score) as lowest_score,
        COUNT(CASE WHEN completed_at >= '$date_from' AND completed_at <= '$date_to' THEN 1 END) as attempts_period
        FROM quiz_attempts");
    $attempt_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Resource statistics
    $resource_stats = ['total_resources' => 0, 'new_resources_period' => 0];
    try {
        $stmt = $pdo->query("SELECT
            ((SELECT COUNT(*) FROM notes) +
             (SELECT COUNT(*) FROM ebooks) +
             (SELECT COUNT(*) FROM pastpapers)) as total_resources,
            ((SELECT COUNT(*) FROM notes WHERE created_at >= '$date_from' AND created_at <= '$date_to') +
             (SELECT COUNT(*) FROM ebooks WHERE created_at >= '$date_from' AND created_at <= '$date_to') +
             (SELECT COUNT(*) FROM pastpapers WHERE created_at >= '$date_from' AND created_at <= '$date_to')) as new_resources_period");
        $resource_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Resource tables might not exist
    }

    // Feedback statistics
    $feedback_stats = ['total_feedback' => 0, 'new_feedback_period' => 0];
    try {
        $stmt = $pdo->query("SELECT
            COUNT(*) as total_feedback,
            COUNT(CASE WHEN created_at >= '$date_from' AND created_at <= '$date_to' THEN 1 END) as new_feedback_period
            FROM feedbacks");
        $feedback_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Feedback table might not exist
    }

    // Top performing quizzes
    $stmt = $pdo->query("
        SELECT
            q.quiz_id,
            q.title,
            COUNT(qa.attempt_id) as total_attempts,
            AVG(qa.score) as avg_score,
            MAX(qa.score) as highest_score
        FROM quizzes q
        LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id
        GROUP BY q.quiz_id, q.title
        ORDER BY total_attempts DESC, avg_score DESC
        LIMIT 10
    ");
    $top_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // User activity summary
    $stmt = $pdo->query("
        SELECT
            u.username,
            u.role,
            COUNT(DISTINCT qa.attempt_id) as quiz_attempts,
            AVG(qa.score) as avg_score,
            MAX(qa.completed_at) as last_activity
        FROM users u
        LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id
        GROUP BY u.user_id, u.username, u.role
        ORDER BY last_activity DESC
        LIMIT 10
    ");
    $user_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent quiz attempts
    $stmt = $pdo->query("
        SELECT
            qa.attempt_id,
            qa.score,
            qa.completed_at,
            u.username,
            u.role,
            q.title as quiz_title
        FROM quiz_attempts qa
        LEFT JOIN users u ON qa.user_id = u.user_id
        LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
        ORDER BY qa.completed_at DESC
        LIMIT 10
    ");
    $recent_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Quiz performance by category
    $stmt = $pdo->query("
        SELECT
            COALESCE(qc.category_name, 'Uncategorized') as category,
            COUNT(DISTINCT q.quiz_id) as quiz_count,
            COUNT(qa.attempt_id) as total_attempts,
            AVG(qa.score) as avg_score
        FROM quiz_categories qc
        LEFT JOIN quizzes q ON qc.category_id = q.category_id
        LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id
        GROUP BY qc.category_id, qc.category_name
        ORDER BY total_attempts DESC
    ");
    $category_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error fetching report data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - Admin Panel</title>
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

        .reports-container {
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

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Filters Section */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .filters-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--admin-primary);
        }

        .btn-filter {
            padding: 10px 20px;
            background: var(--admin-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filter:hover {
            background: var(--admin-secondary);
        }

        /* Statistics Grid */
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
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--admin-primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            margin-bottom: 15px;
        }

        .stat-icon.blue { background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }

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

        .stat-trend {
            font-size: 0.8em;
            margin-top: 8px;
        }

        .stat-trend.positive { color: var(--admin-success); }
        .stat-trend.negative { color: var(--admin-danger); }

        /* Report Sections */
        .report-section {
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
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th,
        .report-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-table th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .report-table tr:hover {
            background: #f7fafc;
        }

        .report-table tbody tr:last-child {
            border-bottom: none;
        }

        /* Badges and Status */
        .role-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-badge.admin { background: #dbeafe; color: #1e40af; }
        .role-badge.teacher { background: #d1fae5; color: #065f46; }
        .role-badge.student { background: #e0e7ff; color: #3730a3; }

        .score-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .score-badge.excellent { background: #d1fae5; color: #065f46; }
        .score-badge.good { background: #dbeafe; color: #1e40af; }
        .score-badge.average { background: #fef3c7; color: #92400e; }
        .score-badge.poor { background: #fee2e2; color: #991b1b; }

        .performance-bar {
            width: 100px;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 0 10px 0 0;
        }

        .performance-fill {
            height: 100%;
            border-radius: 4px;
        }

        .performance-fill.excellent { background: #10b981; }
        .performance-fill.good { background: #3b82f6; }
        .performance-fill.average { background: #f59e0b; }
        .performance-fill.poor { background: #ef4444; }

        /* Export Button */
        .export-section {
            text-align: right;
            margin-bottom: 20px;
        }

        .btn-export {
            padding: 10px 20px;
            background: var(--admin-success);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            background: #059669;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #cbd5e0;
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #a0aec0;
            margin: 0 0 10px 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-row {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .report-table {
                font-size: 0.9em;
            }

            .report-table th,
            .report-table td {
                padding: 10px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--admin-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="reports-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-chart-line"></i>
                    System Reports & Analytics
                </h1>
                <p>Comprehensive overview of system performance, user activity, and learning analytics</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Date Range Filters -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="date_from">From Date</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                        </div>

                        <div class="filter-group">
                            <label for="date_to">To Date</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                        </div>

                        <div class="filter-group">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- System Overview Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($user_stats['total_users']); ?></h3>
                        <p>Total Users</p>
                        <div class="stat-trend positive">+<?php echo $user_stats['new_users_period']; ?> this period</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-puzzle-piece"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($quiz_stats['total_quizzes']); ?></h3>
                        <p>Total Quizzes</p>
                        <div class="stat-trend positive"><?php echo $quiz_stats['active_quizzes']; ?> active</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($attempt_stats['total_attempts']); ?></h3>
                        <p>Quiz Attempts</p>
                        <div class="stat-trend positive">Avg: <?php echo number_format($attempt_stats['avg_score'], 1); ?>%</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($resource_stats['total_resources']); ?></h3>
                        <p>Learning Resources</p>
                        <div class="stat-trend positive">+<?php echo $resource_stats['new_resources_period']; ?> this period</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($feedback_stats['total_feedback']); ?></h3>
                        <p>Feedback Messages</p>
                        <div class="stat-trend positive">+<?php echo $feedback_stats['new_feedback_period']; ?> this period</div>
                    </div>
                </div>
            </div>

            <!-- User Distribution -->
            <div class="report-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-friends"></i> User Distribution</h2>
                </div>
                <div style="padding: 25px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="text-align: center; padding: 20px; background: #f7fafc; border-radius: 8px;">
                            <div style="font-size: 2em; color: var(--admin-primary); margin-bottom: 10px;">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div style="font-size: 1.5em; font-weight: 700; color: #2d3748;"><?php echo $user_stats['admin_count']; ?></div>
                            <div style="color: #718096;">Administrators</div>
                        </div>

                        <div style="text-align: center; padding: 20px; background: #f7fafc; border-radius: 8px;">
                            <div style="font-size: 2em; color: var(--admin-success); margin-bottom: 10px;">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div style="font-size: 1.5em; font-weight: 700; color: #2d3748;"><?php echo $user_stats['teacher_count']; ?></div>
                            <div style="color: #718096;">Teachers</div>
                        </div>

                        <div style="text-align: center; padding: 20px; background: #f7fafc; border-radius: 8px;">
                            <div style="font-size: 2em; color: var(--admin-info); margin-bottom: 10px;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div style="font-size: 1.5em; font-weight: 700; color: #2d3748;"><?php echo $user_stats['student_count']; ?></div>
                            <div style="color: #718096;">Students</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performing Quizzes -->
            <div class="report-section">
                <div class="section-header">
                    <h2><i class="fas fa-trophy"></i> Top Performing Quizzes</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Total Attempts</th>
                                <th>Average Score</th>
                                <th>Highest Score</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_quizzes)): ?>
                                <?php foreach ($top_quizzes as $quiz): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($quiz['title']); ?></strong></td>
                                        <td><?php echo number_format($quiz['total_attempts']); ?></td>
                                        <td><?php echo $quiz['avg_score'] ? number_format($quiz['avg_score'], 1) . '%' : 'N/A'; ?></td>
                                        <td><?php echo $quiz['highest_score'] ? number_format($quiz['highest_score'], 1) . '%' : 'N/A'; ?></td>
                                        <td>
                                            <?php if ($quiz['avg_score']): ?>
                                                <div style="display: flex; align-items: center;">
                                                    <div class="performance-bar">
                                                        <div class="performance-fill <?php
                                                            if ($quiz['avg_score'] >= 90) echo 'excellent';
                                                            elseif ($quiz['avg_score'] >= 75) echo 'good';
                                                            elseif ($quiz['avg_score'] >= 60) echo 'average';
                                                            else echo 'poor';
                                                        ?>" style="width: <?php echo min(100, $quiz['avg_score']); ?>%"></div>
                                                    </div>
                                                    <span class="score-badge <?php
                                                        if ($quiz['avg_score'] >= 90) echo 'excellent';
                                                        elseif ($quiz['avg_score'] >= 75) echo 'good';
                                                        elseif ($quiz['avg_score'] >= 60) echo 'average';
                                                        else echo 'poor';
                                                    ?>"><?php echo number_format($quiz['avg_score'], 1); ?>%</span>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #cbd5e0;">No attempts yet</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-puzzle-piece"></i>
                                            <h3>No Quiz Data Available</h3>
                                            <p>No quizzes have been attempted yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Category Performance -->
            <div class="report-section">
                <div class="section-header">
                    <h2><i class="fas fa-tags"></i> Performance by Category</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Quizzes</th>
                                <th>Total Attempts</th>
                                <th>Average Score</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($category_performance)): ?>
                                <?php foreach ($category_performance as $category): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($category['category']); ?></strong></td>
                                        <td><?php echo number_format($category['quiz_count']); ?></td>
                                        <td><?php echo number_format($category['total_attempts']); ?></td>
                                        <td><?php echo $category['avg_score'] ? number_format($category['avg_score'], 1) . '%' : 'N/A'; ?></td>
                                        <td>
                                            <?php if ($category['avg_score']): ?>
                                                <div style="display: flex; align-items: center;">
                                                    <div class="performance-bar">
                                                        <div class="performance-fill <?php
                                                            if ($category['avg_score'] >= 90) echo 'excellent';
                                                            elseif ($category['avg_score'] >= 75) echo 'good';
                                                            elseif ($category['avg_score'] >= 60) echo 'average';
                                                            else echo 'poor';
                                                        ?>" style="width: <?php echo min(100, $category['avg_score']); ?>%"></div>
                                                    </div>
                                                    <span class="score-badge <?php
                                                        if ($category['avg_score'] >= 90) echo 'excellent';
                                                        elseif ($category['avg_score'] >= 75) echo 'good';
                                                        elseif ($category['avg_score'] >= 60) echo 'average';
                                                        else echo 'poor';
                                                    ?>"><?php echo number_format($category['avg_score'], 1); ?>%</span>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #cbd5e0;">No attempts yet</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-tags"></i>
                                            <h3>No Category Data Available</h3>
                                            <p>No quiz categories have attempts yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- User Activity Summary -->
            <div class="report-section">
                <div class="section-header">
                    <h2><i class="fas fa-activity"></i> User Activity Summary</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Quiz Attempts</th>
                                <th>Average Score</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($user_activity)): ?>
                                <?php foreach ($user_activity as $user): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                        <td><span class="role-badge <?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                                        <td><?php echo number_format($user['quiz_attempts']); ?></td>
                                        <td><?php echo $user['avg_score'] ? number_format($user['avg_score'], 1) . '%' : 'N/A'; ?></td>
                                        <td><?php echo $user['last_activity'] ? date('M j, Y g:i A', strtotime($user['last_activity'])) : 'Never'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-users"></i>
                                            <h3>No User Activity Data</h3>
                                            <p>No user activity recorded yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Quiz Attempts -->
            <div class="report-section">
                <div class="section-header">
                    <h2><i class="fas fa-clock-rotate-left"></i> Recent Quiz Attempts</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Quiz</th>
                                <th>Score</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_attempts)): ?>
                                <?php foreach ($recent_attempts as $attempt): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($attempt['username'] ?? 'Unknown'); ?></strong></td>
                                        <td><span class="role-badge <?php echo $attempt['role'] ?? 'student'; ?>"><?php echo $attempt['role'] ?? 'student'; ?></span></td>
                                        <td><?php echo htmlspecialchars($attempt['quiz_title'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="score-badge <?php
                                                if ($attempt['score'] >= 90) echo 'excellent';
                                                elseif ($attempt['score'] >= 75) echo 'good';
                                                elseif ($attempt['score'] >= 60) echo 'average';
                                                else echo 'poor';
                                            ?>"><?php echo number_format($attempt['score'], 1); ?>%</span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($attempt['completed_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-clipboard-list"></i>
                                            <h3>No Recent Attempts</h3>
                                            <p>No quiz attempts have been recorded yet</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

</body>
</html>