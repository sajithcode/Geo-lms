<?php
$currentPage = 'teacher_students';

require_once 'php/teacher_session_check.php';
require_once '../config/database.php';

$teacher_id = $_SESSION['id'];

// Get filter parameters
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?: 'name';

// Get overall student statistics
$student_stats = [
    'total_students' => 0,
    'active_students' => 0,
    'quiz_attempts' => 0,
    'avg_score' => 0
];

try {
    // Total students in system
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $student_stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active students (who have attempted quizzes)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT qa.user_id) as count 
                         FROM quiz_attempts qa 
                         INNER JOIN users u ON qa.user_id = u.user_id 
                         WHERE u.role = 'student'");
    $student_stats['active_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total quiz attempts by students
    $stmt = $pdo->query("SELECT COUNT(*) as count, AVG(score) as avg 
                         FROM quiz_attempts qa 
                         INNER JOIN users u ON qa.user_id = u.user_id 
                         WHERE u.role = 'student'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_stats['quiz_attempts'] = $result['count'];
    $student_stats['avg_score'] = $result['avg'] ? round($result['avg'], 1) : 0;
    
} catch (PDOException $e) {
    error_log("Error fetching student stats: " . $e->getMessage());
}

// Build student query
$sql = "SELECT 
            u.user_id,
            u.username,
            u.full_name,
            u.email,
            u.created_at,
            COUNT(qa.attempt_id) as total_attempts,
            AVG(qa.score) as avg_score,
            MAX(qa.score) as best_score,
            MAX(qa.created_at) as last_attempt
        FROM users u
        LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id
        WHERE u.role = 'student'";

$params = [];

// Add search filter
if ($search) {
    $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " GROUP BY u.user_id";

// Add having clause for status filter
if ($status_filter === 'active') {
    $sql .= " HAVING total_attempts > 0";
} elseif ($status_filter === 'inactive') {
    $sql .= " HAVING total_attempts = 0";
}

// Add sorting
switch ($sort_by) {
    case 'name':
        $sql .= " ORDER BY u.full_name, u.username";
        break;
    case 'attempts':
        $sql .= " ORDER BY total_attempts DESC";
        break;
    case 'score':
        $sql .= " ORDER BY avg_score DESC";
        break;
    case 'recent':
        $sql .= " ORDER BY last_attempt DESC";
        break;
    default:
        $sql .= " ORDER BY u.full_name, u.username";
}

$students = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching students: " . $e->getMessage());
}

// Get recent student activities for sidebar
$recent_activities = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.username,
            u.full_name,
            qa.score,
            qa.created_at,
            q.title as quiz_title
        FROM quiz_attempts qa
        INNER JOIN users u ON qa.user_id = u.user_id
        INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE u.role = 'student'
        ORDER BY qa.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent activities: " . $e->getMessage());
}

// Get top performers for sidebar
$top_performers = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.username,
            u.full_name,
            COUNT(qa.attempt_id) as total_attempts,
            AVG(qa.score) as avg_score
        FROM users u
        INNER JOIN quiz_attempts qa ON u.user_id = qa.user_id
        WHERE u.role = 'student'
        GROUP BY u.user_id
        HAVING total_attempts >= 3
        ORDER BY avg_score DESC, total_attempts DESC
        LIMIT 5
    ");
    $stmt->execute();
    $top_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching top performers: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Teacher Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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



        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }

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

        /* Filters and Search */
        .controls-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .controls-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .control-group {
            flex: 1;
            min-width: 200px;
        }

        .control-group label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .control-group input,
        .control-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
        }

        .btn-search {
            padding: 10px 20px;
            background: var(--teacher-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Students Table */
        .students-section {
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            margin: 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
        }

        .students-table th,
        .students-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .students-table th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .students-table tr:hover {
            background: #f7fafc;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2em;
        }

        .student-details h4 {
            margin: 0 0 3px 0;
            color: #2d3748;
            font-size: 1em;
        }

        .student-details p {
            margin: 0;
            color: #718096;
            font-size: 0.85em;
        }

        .score-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .score-excellent { background: #d1fae5; color: #065f46; }
        .score-good { background: #dbeafe; color: #1e40af; }
        .score-average { background: #fef3c7; color: #92400e; }
        .score-poor { background: #fee2e2; color: #991b1b; }

        .activity-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .activity-active { background: #d1fae5; color: #065f46; }
        .activity-inactive { background: #f3f4f6; color: #6b7280; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #cbd5e0;
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Student Management</h1>
                <p>Monitor student progress, track performance, and manage learner activities</p>
            </div>

            <!-- Student Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $student_stats['total_students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $student_stats['active_students']; ?></h3>
                        <p>Active Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $student_stats['quiz_attempts']; ?></h3>
                        <p>Quiz Attempts</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $student_stats['avg_score']; ?>%</h3>
                        <p>Average Score</p>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="controls-section">
                <form method="GET" action="">
                    <div class="controls-row">
                        <div class="control-group">
                            <label for="search">Search Students</label>
                            <input type="text" name="search" id="search" 
                                   placeholder="Search by name, username, or email..." 
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>

                        <div class="control-group">
                            <label for="status">Activity Status</label>
                            <select name="status" id="status">
                                <option value="">All Students</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="control-group">
                            <label for="sort">Sort By</label>
                            <select name="sort" id="sort">
                                <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name</option>
                                <option value="attempts" <?php echo $sort_by === 'attempts' ? 'selected' : ''; ?>>Quiz Attempts</option>
                                <option value="score" <?php echo $sort_by === 'score' ? 'selected' : ''; ?>>Average Score</option>
                                <option value="recent" <?php echo $sort_by === 'recent' ? 'selected' : ''; ?>>Recent Activity</option>
                            </select>
                        </div>

                        <div class="control-group">
                            <button type="submit" class="btn-search">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="students-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Students (<?php echo count($students); ?>)</h2>
                </div>
                <div style="overflow-x: auto;">
                    <?php if (empty($students)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Students Found</h3>
                            <p>No students match your search criteria</p>
                        </div>
                    <?php else: ?>
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Quiz Attempts</th>
                                    <th>Average Score</th>
                                    <th>Best Score</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-avatar">
                                                    <?php echo strtoupper(substr($student['full_name'] ?: $student['username'], 0, 1)); ?>
                                                </div>
                                                <div class="student-details">
                                                    <h4><?php echo htmlspecialchars($student['full_name'] ?: $student['username']); ?></h4>
                                                    <p>@<?php echo htmlspecialchars($student['username']); ?></p>
                                                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo $student['total_attempts'] ?: 0; ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($student['avg_score']): ?>
                                                <?php 
                                                $avg = round($student['avg_score'], 1);
                                                $class = $avg >= 80 ? 'score-excellent' : ($avg >= 60 ? 'score-good' : ($avg >= 40 ? 'score-average' : 'score-poor'));
                                                ?>
                                                <span class="score-badge <?php echo $class; ?>"><?php echo $avg; ?>%</span>
                                            <?php else: ?>
                                                <span class="score-badge" style="background: #f3f4f6; color: #6b7280;">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['best_score']): ?>
                                                <strong><?php echo round($student['best_score'], 1); ?>%</strong>
                                            <?php else: ?>
                                                <span style="color: #6b7280;">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['last_attempt']): ?>
                                                <?php echo date('M j, Y', strtotime($student['last_attempt'])); ?>
                                            <?php else: ?>
                                                <span style="color: #6b7280;">No attempts</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['total_attempts'] > 0): ?>
                                                <span class="activity-badge activity-active">Active</span>
                                            <?php else: ?>
                                                <span class="activity-badge activity-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

<script>
// Auto-submit form on select change
document.getElementById('status').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('sort').addEventListener('change', function() {
    this.form.submit();
});
</script>

</body>
</html>