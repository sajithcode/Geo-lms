<?php
$currentPage = 'teacher_performance';

require_once 'php/teacher_session_check.php';
require_once '../config/database.php';

$teacher_id = $_SESSION['id'];

// Get filter parameters
$date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING);
$date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING);
$filter_quiz = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);

// Get overall teacher statistics
$teacher_stats = [
    'total_quizzes' => 0,
    'total_questions' => 0,
    'total_attempts' => 0,
    'avg_score' => 0,
    'unique_students' => 0,
    'total_students' => 0,
    'active_quizzes' => 0
];

try {
    // Total quizzes created
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quizzes WHERE created_by = ?");
    $stmt->execute([$teacher_id]);
    $teacher_stats['total_quizzes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active quizzes (published/active)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quizzes WHERE created_by = ? AND status = 'active'");
    $stmt->execute([$teacher_id]);
    $teacher_stats['active_quizzes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total questions
    $stmt = $pdo->prepare("SELECT COUNT(q.question_id) as count FROM questions q 
                           INNER JOIN quizzes qz ON q.quiz_id = qz.quiz_id 
                           WHERE qz.created_by = ?");
    $stmt->execute([$teacher_id]);
    $teacher_stats['total_questions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total attempts on teacher's quizzes
    $stmt = $pdo->prepare("SELECT COUNT(qa.attempt_id) as count, AVG(qa.score) as avg 
                           FROM quiz_attempts qa 
                           INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id 
                           WHERE q.created_by = ?");
    $stmt->execute([$teacher_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $teacher_stats['total_attempts'] = $result['count'];
    $teacher_stats['avg_score'] = $result['avg'] ? round($result['avg'], 1) : 0;
    
    // Unique students who attempted teacher's quizzes
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT qa.user_id) as count 
                           FROM quiz_attempts qa 
                           INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id 
                           WHERE q.created_by = ?");
    $stmt->execute([$teacher_id]);
    $teacher_stats['unique_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total students in system
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $teacher_stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (PDOException $e) {
    error_log("Error fetching teacher stats: " . $e->getMessage());
}

// Get quiz-wise performance
$quiz_performance = [];
try {
    $sql = "SELECT 
                q.quiz_id,
                q.title,
                q.category_id,
                q.status,
                COUNT(qa.attempt_id) as attempts,
                AVG(qa.score) as avg_score,
                MAX(qa.score) as highest_score,
                MIN(qa.score) as lowest_score,
                COUNT(DISTINCT qa.user_id) as unique_students
            FROM quizzes q
            LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id
            WHERE q.created_by = ?";
    
    $params = [$teacher_id];
    
    if ($date_from && $date_to) {
        $sql .= " AND DATE(qa.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
    }
    
    if ($filter_quiz) {
        $sql .= " AND q.quiz_id = ?";
        $params[] = $filter_quiz;
    }
    
    $sql .= " GROUP BY q.quiz_id ORDER BY attempts DESC, avg_score DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $quiz_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching quiz performance: " . $e->getMessage());
}

// Get recent student attempts
$recent_attempts = [];
try {
    $sql = "SELECT 
                qa.attempt_id,
                qa.score,
                qa.created_at,
                q.title as quiz_title,
                u.username,
                u.full_name
            FROM quiz_attempts qa
            INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
            INNER JOIN users u ON qa.user_id = u.user_id
            WHERE q.created_by = ?
            ORDER BY qa.created_at DESC
            LIMIT 15";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id]);
    $recent_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching recent attempts: " . $e->getMessage());
}

// Get student performance ranking
$student_ranking = [];
try {
    $sql = "SELECT 
                u.user_id,
                u.username,
                u.full_name,
                COUNT(qa.attempt_id) as total_attempts,
                AVG(qa.score) as avg_score,
                MAX(qa.score) as best_score
            FROM users u
            INNER JOIN quiz_attempts qa ON u.user_id = qa.user_id
            INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id
            WHERE q.created_by = ? AND u.role = 'student'
            GROUP BY u.user_id
            ORDER BY avg_score DESC, total_attempts DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id]);
    $student_ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching student ranking: " . $e->getMessage());
}

// Get teacher's quizzes for filter dropdown
$teacher_quizzes = [];
try {
    $stmt = $pdo->prepare("SELECT quiz_id, title FROM quizzes WHERE created_by = ? ORDER BY title");
    $stmt->execute([$teacher_id]);
    $teacher_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching quizzes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Analytics - Teacher Portal</title>
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

        /* Filters */
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

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
        }

        .btn-filter {
            padding: 10px 20px;
            background: var(--teacher-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Tables */
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
        }

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
                <h1><i class="fas fa-chart-line"></i> Performance Analytics</h1>
                <p>Track and analyze quiz performance, student engagement, and teaching effectiveness</p>
            </div>

            <!-- Overall Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-puzzle-piece"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $teacher_stats['total_quizzes']; ?></h3>
                        <p>Total Quizzes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $teacher_stats['total_attempts']; ?></h3>
                        <p>Quiz Attempts</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $teacher_stats['avg_score']; ?>%</h3>
                        <p>Average Score</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $teacher_stats['unique_students']; ?></h3>
                        <p>Active Students</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="quiz_id">Filter by Quiz</label>
                            <select name="quiz_id" id="quiz_id">
                                <option value="">All Quizzes</option>
                                <?php foreach ($teacher_quizzes as $quiz): ?>
                                    <option value="<?php echo $quiz['quiz_id']; ?>" <?php echo $filter_quiz == $quiz['quiz_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($quiz['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="date_from">From Date</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="date_to">To Date</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                        </div>

                        <div class="filter-group">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Quiz Performance Table -->
            <div class="data-section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-bar"></i> Quiz Performance</h2>
                </div>
                <div style="overflow-x: auto;">
                    <?php if (empty($quiz_performance)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <h3>No Performance Data</h3>
                            <p>Create quizzes and students will start taking them!</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Quiz Title</th>
                                    <th>Attempts</th>
                                    <th>Students</th>
                                    <th>Avg Score</th>
                                    <th>Highest</th>
                                    <th>Lowest</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_performance as $quiz): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($quiz['title']); ?></strong></td>
                                        <td><?php echo $quiz['attempts'] ?: 0; ?></td>
                                        <td><?php echo $quiz['unique_students'] ?: 0; ?></td>
                                        <td>
                                            <?php 
                                            $avg = $quiz['avg_score'] ? round($quiz['avg_score'], 1) : 0;
                                            $class = $avg >= 80 ? 'score-excellent' : ($avg >= 60 ? 'score-good' : ($avg >= 40 ? 'score-average' : 'score-poor'));
                                            ?>
                                            <span class="score-badge <?php echo $class; ?>"><?php echo $avg; ?>%</span>
                                        </td>
                                        <td><?php echo $quiz['highest_score'] ? round($quiz['highest_score'], 1) : 'N/A'; ?>%</td>
                                        <td><?php echo $quiz['lowest_score'] ? round($quiz['lowest_score'], 1) : 'N/A'; ?>%</td>
                                        <td><?php echo ucfirst($quiz['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Student Attempts -->
            <div class="data-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Student Attempts</h2>
                </div>
                <div style="overflow-x: auto;">
                    <?php if (empty($recent_attempts)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>No Recent Attempts</h3>
                            <p>Student attempts will appear here</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Quiz</th>
                                    <th>Score</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attempts as $attempt): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($attempt['full_name'] ?: $attempt['username']); ?></strong>
                                            <br><small>@<?php echo htmlspecialchars($attempt['username']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($attempt['quiz_title']); ?></td>
                                        <td>
                                            <?php 
                                            $score = round($attempt['score'], 1);
                                            $class = $score >= 80 ? 'score-excellent' : ($score >= 60 ? 'score-good' : ($score >= 40 ? 'score-average' : 'score-poor'));
                                            ?>
                                            <span class="score-badge <?php echo $class; ?>"><?php echo $score; ?>%</span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($attempt['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

</body>
</html>
