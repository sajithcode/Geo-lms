<?php
$currentPage = 'performance';

// Include session check and database connection
require_once '../php/session_check.php';
require_once '../config/database.php';

$user_id = $_SESSION['id'];

// Get overall statistics
try {
    $qa_cols = $pdo->query("SHOW COLUMNS FROM `quiz_attempts`")->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    $qa_cols = [];
}

if (in_array('passed', $qa_cols)) {
    $stats_sql = "SELECT 
        COUNT(*) as total_attempts,
        AVG(score) as avg_score,
        MAX(score) as best_score,
        MIN(score) as lowest_score,
        SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count
        FROM quiz_attempts WHERE user_id = ?";
} else {
    $stats_sql = "SELECT 
        COUNT(*) as total_attempts,
        AVG(qa.score) as avg_score,
        MAX(qa.score) as best_score,
        MIN(qa.score) as lowest_score,
        SUM(CASE WHEN qa.score >= q.passing_score THEN 1 ELSE 0 END) as passed_count
        FROM quiz_attempts qa 
        LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id 
        WHERE qa.user_id = ?";
}

$stmt = $pdo->prepare($stats_sql);
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if correct_answers column exists in quiz_attempts
$qa_correct_col = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'correct_answers'")->fetchAll();
$has_correct_answers = count($qa_correct_col) > 0;

// Get recent quiz attempts
// Check if total_questions column exists; if not, fall back to counting questions per quiz
$qa_total_col = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'total_questions'")->fetchAll();
$has_total_questions = count($qa_total_col) > 0;

$total_questions_select = $has_total_questions
    ? "qa.total_questions"
    : "(SELECT COUNT(*) FROM questions WHERE quiz_id = qa.quiz_id) AS total_questions";

// Detect which timestamp column exists in quiz_attempts and alias it to created_at for consistency
$possible_ts = ['created_at', 'attempted_at', 'started_at', 'timestamp'];
$ts_column = null;
foreach ($possible_ts as $col) {
    $check = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE '" . $col . "'")->fetchAll();
    if (count($check) > 0) {
        $ts_column = $col;
        break;
    }
}

// If no timestamp column found, select NULL as created_at to avoid SQL errors
$created_at_select = $ts_column ? "qa." . $ts_column . " AS created_at" : "NULL AS created_at";

if ($has_correct_answers) {
    $recent_sql = "SELECT 
        qa.quiz_id,
        qa.score,
        qa.correct_answers,
        {$total_questions_select},
        {$created_at_select},
        q.title as quiz_title
        FROM quiz_attempts qa
        LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.user_id = ?
        ORDER BY " . ($ts_column ? "qa." . $ts_column . " DESC" : "qa.quiz_id DESC") . "
        LIMIT 10";
} else {
    $recent_sql = "SELECT 
        qa.quiz_id,
        qa.score,
        {$total_questions_select},
        {$created_at_select},
        q.title as quiz_title
        FROM quiz_attempts qa
        LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.user_id = ?
        ORDER BY " . ($ts_column ? "qa." . $ts_column . " DESC" : "qa.quiz_id DESC") . "
        LIMIT 10";
}

$stmt = $pdo->prepare($recent_sql);
$stmt->execute([$user_id]);
$recent_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pass rate
$pass_rate = $stats['total_attempts'] > 0 
    ? round(($stats['passed_count'] / $stats['total_attempts']) * 100, 1) 
    : 0;

include '../includes/header.php';
?>
<script>document.title = 'Performance Tracking - Self-Learning Hub';</script>
<link rel="stylesheet" href="../assets/css/performance.css">

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1><i class="fa-solid fa-chart-line"></i> Performance Tracking</h1>
            <p>Monitor your learning progress and achievements.</p>
        </header>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['total_attempts'] ?? 0; ?></h3>
                    <p>Quizzes Taken</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['passed_count'] ?? 0; ?></h3>
                    <p>Quizzes Passed</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-percentage"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['avg_score'] ? round($stats['avg_score'], 1) . '%' : 'N/A'; ?></h3>
                    <p>Average Score</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $pass_rate; ?>%</h3>
                    <p>Pass Rate</p>
                </div>
            </div>
        </div>

        <!-- Performance Chart & Achievements -->
        <div class="performance-grid">
            
            <!-- Score Range -->
            <div class="performance-section">
                <h2><i class="fa-solid fa-chart-bar"></i> Score Range</h2>
                <div class="score-range">
                    <div class="score-item">
                        <span class="score-label">Best Score</span>
                        <span class="score-value best"><?php echo $stats['best_score'] ?? 0; ?>%</span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Average Score</span>
                        <span class="score-value avg"><?php echo $stats['avg_score'] ? round($stats['avg_score'], 1) : 0; ?>%</span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Lowest Score</span>
                        <span class="score-value low"><?php echo $stats['lowest_score'] ?? 0; ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Achievements -->
            <div class="performance-section">
                <h2><i class="fa-solid fa-medal"></i> Achievements</h2>
                <div class="achievements">
                    <?php
                    $achievements = [
                        ['icon' => 'fa-rocket', 'title' => 'Quick Start', 'desc' => 'Completed first quiz', 'unlocked' => $stats['total_attempts'] >= 1],
                        ['icon' => 'fa-fire', 'title' => 'On Fire', 'desc' => 'Passed 5 quizzes', 'unlocked' => $stats['passed_count'] >= 5],
                        ['icon' => 'fa-star', 'title' => 'High Achiever', 'desc' => 'Average score above 80%', 'unlocked' => ($stats['avg_score'] ?? 0) >= 80],
                        ['icon' => 'fa-crown', 'title' => 'Perfect Score', 'desc' => 'Scored 100% on a quiz', 'unlocked' => ($stats['best_score'] ?? 0) >= 100],
                    ];

                    foreach ($achievements as $achievement):
                        $class = $achievement['unlocked'] ? 'unlocked' : 'locked';
                    ?>
                    <div class="achievement-badge <?php echo $class; ?>">
                        <i class="fa-solid <?php echo $achievement['icon']; ?>"></i>
                        <div class="achievement-info">
                            <h4><?php echo $achievement['title']; ?></h4>
                            <p><?php echo $achievement['desc']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Quiz History -->
        <div class="performance-section">
            <h2><i class="fa-solid fa-history"></i> Recent Quiz History</h2>
            <?php if (empty($recent_attempts)): ?>
                <p class="no-data">No quiz attempts yet. Start taking quizzes to track your performance!</p>
            <?php else: ?>
                <div class="quiz-history-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Score</th>
                                <th>Correct Answers</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_attempts as $attempt): 
                                $score = $attempt['score'];
                                $status_class = $score >= 70 ? 'passed' : 'failed';
                                $status_text = $score >= 70 ? 'Passed' : 'Failed';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attempt['quiz_title'] ?? 'Untitled Quiz'); ?></td>
                                <td><strong><?php echo $score; ?>%</strong></td>
                                <td>
                                    <?php if ($has_correct_answers): ?>
                                        <?php echo $attempt['correct_answers']; ?> / <?php echo $attempt['total_questions']; ?>
                                    <?php else: ?>
                                        <?php echo $attempt['total_questions']; ?> questions
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($attempt['created_at'])); ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
