<?php
$currentPage = 'admin_quizzes';

// Include admin session check
require_once 'php/admin_session_check.php';
require_once '../config/database.php';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $quiz_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($quiz_id) {
        try {
            // Delete quiz (cascade will delete questions and answers)
            $stmt = $pdo->prepare("DELETE FROM quizzes WHERE quiz_id = ?");
            $stmt->execute([$quiz_id]);
            $_SESSION['success_message'] = "Quiz deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error deleting quiz: " . $e->getMessage();
        }
        header("Location: quizzes.php");
        exit;
    }
}

// Check for optional columns
$columns_active = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'is_active'")->fetchAll();
$has_active = count($columns_active) > 0;

$columns_cat = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'category_id'")->fetchAll();
$has_category = count($columns_cat) > 0;

$columns_diff = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'difficulty'")->fetchAll();
$has_difficulty = count($columns_diff) > 0;

$columns_time = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'time_limit'")->fetchAll();
$has_time_limit = count($columns_time) > 0;

$columns_retry = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'retry_limit'")->fetchAll();
$has_retry_limit = count($columns_retry) > 0;

$columns_created = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'created_at'")->fetchAll();
$has_created_at = count($columns_created) > 0;

// Handle toggle active status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $quiz_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($quiz_id && $has_active) {
        try {
            $stmt = $pdo->prepare("UPDATE quizzes SET is_active = NOT is_active WHERE quiz_id = ?");
            $stmt->execute([$quiz_id]);
            $_SESSION['success_message'] = "Quiz status updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating quiz: " . $e->getMessage();
        }
        header("Location: quizzes.php");
        exit;
    } elseif (!$has_active) {
        $_SESSION['error_message'] = "Quiz status toggle is not available (is_active column missing)";
        header("Location: quizzes.php");
        exit;
    }
}

// Build SELECT query dynamically
$active_select = $has_active ? ", q.is_active" : ", 1 as is_active";
$category_select = $has_category ? ", qc.category_name" : ", NULL as category_name";
$difficulty_select = $has_difficulty ? ", q.difficulty" : ", NULL as difficulty";
$time_select = $has_time_limit ? ", q.time_limit" : ", NULL as time_limit";
$retry_select = $has_retry_limit ? ", q.retry_limit" : ", NULL as retry_limit";
$created_select = $has_created_at ? ", q.created_at" : ", NOW() as created_at";

$category_join = $has_category ? "LEFT JOIN quiz_categories qc ON q.category_id = qc.category_id" : "";

// Fetch all quizzes with statistics
$sql = "SELECT q.quiz_id, q.title, q.description, q.passing_score
               {$difficulty_select}{$time_select}{$retry_select}{$active_select}{$created_select}{$category_select},
               COUNT(DISTINCT qu.question_id) as question_count,
               COUNT(DISTINCT qa.attempt_id) as attempt_count
        FROM quizzes q 
        {$category_join}
        LEFT JOIN questions qu ON q.quiz_id = qu.quiz_id
        LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id
        GROUP BY q.quiz_id
        ORDER BY q.quiz_id DESC";

$stmt = $pdo->query($sql);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get quiz statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
$total_quizzes = $stmt->fetch()['total'];

if ($has_active) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes WHERE is_active = 1");
    $active_quizzes = $stmt->fetch()['total'];
} else {
    $active_quizzes = $total_quizzes; // If no is_active column, assume all are active
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM quiz_attempts");
$total_attempts = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
$total_questions = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Management - Geo-LMS Admin</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid var(--admin-primary);
        }
        
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
        
        .action-bar {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--admin-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--admin-secondary);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--admin-success);
            color: white;
        }
        
        .btn-warning {
            background: var(--admin-warning);
            color: white;
        }
        
        .btn-danger {
            background: var(--admin-danger);
            color: white;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .quiz-table-container {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow-x: auto;
        }
        
        .quiz-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .quiz-table th {
            text-align: left;
            padding: 12px;
            background: #f9fafb;
            font-weight: 600;
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .quiz-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .quiz-table tr:hover {
            background: #f9fafb;
        }
        
        .quiz-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        
        .quiz-meta {
            font-size: 0.875rem;
            color: #6b7280;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-easy {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-medium {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-hard {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-category {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 16px;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 4em;
            color: #cbd5e0;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="admin-dashboard">
    <div class="admin-header">
        <h1>
            <i class="fa-solid fa-puzzle-piece"></i>
            Quiz Management
        </h1>
        <p>Create, edit, and manage quizzes for your students</p>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-box">
            <h3><i class="fa-solid fa-puzzle-piece"></i> Total Quizzes</h3>
            <div class="stat-value"><?php echo number_format($total_quizzes); ?></div>
        </div>
        
        <div class="stat-box">
            <h3><i class="fa-solid fa-check-circle"></i> Active Quizzes</h3>
            <div class="stat-value"><?php echo number_format($active_quizzes); ?></div>
        </div>
        
        <div class="stat-box">
            <h3><i class="fa-solid fa-clipboard-list"></i> Total Attempts</h3>
            <div class="stat-value"><?php echo number_format($total_attempts); ?></div>
        </div>
        
        <div class="stat-box">
            <h3><i class="fa-solid fa-question-circle"></i> Total Questions</h3>
            <div class="stat-value"><?php echo number_format($total_questions); ?></div>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <div class="action-bar">
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        <div>
            <a href="quiz_categories.php" class="btn btn-warning">
                <i class="fa-solid fa-tags"></i> Manage Categories
            </a>
            <a href="create_quiz.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Create New Quiz
            </a>
        </div>
    </div>

    <!-- Quizzes Table -->
    <div class="quiz-table-container">
        <h2 style="margin: 0 0 20px 0;"><i class="fas fa-list"></i> All Quizzes</h2>
        
        <?php if (count($quizzes) > 0): ?>
        <table class="quiz-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Quiz Details</th>
                    <th>Category</th>
                    <th>Difficulty</th>
                    <th>Questions</th>
                    <th>Attempts</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><?php echo $quiz['quiz_id']; ?></td>
                        <td>
                            <div class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
                            <div class="quiz-meta">
                                <?php if ($has_time_limit && $quiz['time_limit']): ?>
                                    <span class="meta-item">
                                        <i class="fas fa-clock"></i> <?php echo $quiz['time_limit']; ?> min
                                    </span>
                                <?php endif; ?>
                                <span class="meta-item">
                                    <i class="fas fa-check-circle"></i> <?php echo $quiz['passing_score']; ?>%
                                </span>
                                <?php if ($has_retry_limit && $quiz['retry_limit']): ?>
                                    <span class="meta-item">
                                        <i class="fas fa-redo"></i> <?php echo $quiz['retry_limit']; ?> attempts
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($has_category && $quiz['category_name']): ?>
                                <span class="badge badge-category"><?php echo htmlspecialchars($quiz['category_name']); ?></span>
                            <?php elseif ($has_category): ?>
                                <span style="color: #9ca3af;">No category</span>
                            <?php else: ?>
                                <span style="color: #9ca3af;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($has_difficulty && $quiz['difficulty']): ?>
                                <span class="badge badge-<?php echo $quiz['difficulty']; ?>">
                                    <?php echo ucfirst($quiz['difficulty']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <strong><?php echo $quiz['question_count']; ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <strong><?php echo $quiz['attempt_count']; ?></strong>
                        </td>
                        <td>
                            <?php if ($quiz['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="../pages/preview_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" 
                                   class="btn btn-secondary btn-sm" 
                                   title="Preview" 
                                   target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" 
                                   class="btn btn-primary btn-sm" 
                                   title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="manage_questions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                                   class="btn btn-warning btn-sm" 
                                   title="Manage Questions">
                                    <i class="fas fa-question-circle"></i>
                                </a>
                                <?php if ($has_active): ?>
                                <a href="?action=toggle&id=<?php echo $quiz['quiz_id']; ?>" 
                                   class="btn btn-secondary btn-sm" 
                                   title="Toggle Active/Inactive"
                                   onclick="return confirm('Toggle quiz status?')">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $quiz['quiz_id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this quiz? This will also delete all questions and answers.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Quizzes Yet</h3>
                <p>Create your first quiz to get started!</p>
                <a href="create_quiz.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fa-solid fa-plus"></i> Create New Quiz
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
