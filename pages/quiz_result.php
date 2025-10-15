<?php
$currentPage = 'quizzes';

require_once '../php/session_check.php';
require_once '../config/database.php';

// Get the attempt ID from the URL
$attempt_id = filter_input(INPUT_GET, 'attempt_id', FILTER_VALIDATE_INT);
if (!$attempt_id) {
    header("location: quizzes.php");
    exit;
}

// Check for additional columns
$columns_passed = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'passed'")->fetchAll();
$has_passed = count($columns_passed) > 0;

$columns_time = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'time_spent'")->fetchAll();
$has_time_spent = count($columns_time) > 0;

$columns_show = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'show_answers_after'")->fetchAll();
$has_show_answers = count($columns_show) > 0;

$passed_select = $has_passed ? ", qa.passed" : "";
$time_select = $has_time_spent ? ", qa.time_spent" : "";
$show_select = $has_show_answers ? ", q.show_answers_after" : "";

// Fetch the attempt details
$sql = "SELECT qa.score, qa.correct_answers{$passed_select}{$time_select}, 
               q.title AS quiz_title, qa.quiz_id, q.passing_score{$show_select}, qa.created_at
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.attempt_id = ? AND qa.user_id = ?";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$attempt_id, $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header("location: quizzes.php");
    exit;
}

// Calculate passed status if column doesn't exist
$passing_score = $result['passing_score'] ?? 50;
$passed = $has_passed ? $result['passed'] : ($result['score'] >= $passing_score);
$show_answers = $has_show_answers ? ($result['show_answers_after'] ?? 1) : 1;

// Get total questions
$stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE quiz_id = ?");
$stmt_total->execute([$result['quiz_id']]);
$total_data = $stmt_total->fetch(PDO::FETCH_ASSOC);
$total_questions = $total_data['total'];

include '../includes/header.php';
?>
<script>document.title = 'Quiz Result - Self-Learning Hub';</script>

<style>
.result-banner {
    background: linear-gradient(135deg, <?php echo $passed ? '#48bb78 0%, #38a169' : '#f56565 0%, #e53e3e'; ?> 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 30px;
}

.result-banner h2 {
    font-size: 2.5em;
    margin: 0 0 10px 0;
}

.result-banner p {
    font-size: 1.2em;
    opacity: 0.9;
}

.result-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card i {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.stat-card.success i {
    color: #48bb78;
}

.stat-card.info i {
    color: #4299e1;
}

.stat-card.warning i {
    color: #ed8936;
}

.stat-value {
    font-size: 2em;
    font-weight: bold;
    color: #2d3748;
    display: block;
    margin-bottom: 5px;
}

.stat-label {
    color: #718096;
    font-size: 0.9em;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 30px;
}

.btn {
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1em;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-secondary:hover {
    background: #f7fafc;
}

.detailed-link {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 20px;
}

.score-circle {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20px auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.score-value {
    font-size: 3.5em;
    font-weight: bold;
    color: <?php echo $passed ? '#38a169' : '#e53e3e'; ?>;
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1><?php echo htmlspecialchars($result['quiz_title']); ?></h1>
            <p>Quiz completed on <?php echo date('F j, Y \a\t g:i A', strtotime($result['created_at'])); ?></p>
        </header>

        <!-- Result Banner -->
        <div class="result-banner">
            <h2>
                <i class="fas fa-<?php echo $passed ? 'check-circle' : 'times-circle'; ?>"></i>
                <?php echo $passed ? 'Congratulations!' : 'Keep Practicing!'; ?>
            </h2>
            <p><?php echo $passed ? 'You have passed the quiz!' : 'You did not pass this time. Try again!'; ?></p>
            
            <div class="score-circle">
                <span class="score-value"><?php echo round($result['score']); ?>%</span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="result-stats">
            <div class="stat-card success">
                <i class="fas fa-check-circle"></i>
                <span class="stat-value"><?php echo $result['correct_answers'] ?? '-'; ?></span>
                <div class="stat-label">Correct Answers</div>
            </div>

            <div class="stat-card info">
                <i class="fas fa-question-circle"></i>
                <span class="stat-value"><?php echo $total_questions; ?></span>
                <div class="stat-label">Total Questions</div>
            </div>

            <?php if ($has_time_spent && $result['time_spent']): ?>
            <div class="stat-card warning">
                <i class="fas fa-clock"></i>
                <span class="stat-value"><?php echo gmdate("i:s", $result['time_spent']); ?></span>
                <div class="stat-label">Time Spent</div>
            </div>
            <?php endif; ?>

            <div class="stat-card <?php echo $passed ? 'success' : 'warning'; ?>">
                <i class="fas fa-<?php echo $passed ? 'trophy' : 'exclamation-triangle'; ?>"></i>
                <span class="stat-value"><?php echo $passing_score; ?>%</span>
                <div class="stat-label">Passing Score</div>
            </div>
        </div>

        <!-- Detailed Analysis Link -->
        <?php if ($show_answers): ?>
        <div class="detailed-link">
            <h3><i class="fas fa-chart-line"></i> Want to see detailed analysis?</h3>
            <p>Review each question, see correct answers, and understand your mistakes.</p>
            <a href="detailed_result.php?attempt_id=<?php echo $attempt_id; ?>" class="btn btn-primary" style="margin-top: 15px;">
                <i class="fas fa-list"></i> View Detailed Analysis
            </a>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="take_quiz.php?id=<?php echo $result['quiz_id']; ?>" class="btn btn-primary">
                <i class="fas fa-redo"></i> Try Again
            </a>
            <a href="quizzes.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> View Other Quizzes
            </a>
            <a href="performance.php" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> View Performance
            </a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
