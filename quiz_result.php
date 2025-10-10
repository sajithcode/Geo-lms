<?php
$currentPage = 'quizzes'; // To keep the 'Quizzes' link active in the sidebar

require_once 'php/session_check.php';
require_once 'config/database.php';

// Get the attempt ID from the URL
$attempt_id = filter_input(INPUT_GET, 'attempt_id', FILTER_VALIDATE_INT);
if (!$attempt_id) {
    header("location: quizzes.php");
    exit;
}

// Fetch the attempt details from the database
$sql = "SELECT qa.score, q.title AS quiz_title, qa.quiz_id
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.attempt_id = ? AND qa.user_id = ?";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$attempt_id, $_SESSION['id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    // Attempt not found or doesn't belong to the user
    header("location: quizzes.php");
    exit;
}

include 'includes/header.php';
?>
<script>document.title = 'Quiz Result - Self-Learning Hub';</script>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Quiz Result: <?php echo htmlspecialchars($result['quiz_title']); ?></h1>
            <p>Here is your score for the recent attempt.</p>
        </header>

        <div class="result-container">
            <div class="score-circle">
                <span class="score-value"><?php echo round($result['score']); ?>%</span>
            </div>
            <h2>Congratulations!</h2>
            <p>You have completed the quiz.</p>
            <div class="result-actions">
                <a href="take_quiz.php?id=<?php echo $result['quiz_id']; ?>" class="btn-action">Try Again</a>
                <a href="quizzes.php" class="btn-action btn-secondary">View Other Quizzes</a>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>