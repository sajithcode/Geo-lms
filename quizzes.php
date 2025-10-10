<?php
$currentPage = 'quizzes'; // To set the active link in the sidebar

// Include session check and database connection
require_once 'php/session_check.php';
require_once 'config/database.php';

// Fetch all quizzes from the database
$stmt = $pdo->query("SELECT q.quiz_id, q.title, q.description, COUNT(qu.question_id) as question_count FROM quizzes q LEFT JOIN questions qu ON q.quiz_id = qu.quiz_id GROUP BY q.quiz_id");
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include the header
include 'includes/header.php';
?>
<script>document.title = 'Quizzes - Self-Learning Hub';</script>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Self-Assessment & Quizzes</h1>
            <p>Evaluate your knowledge through interactive quizzes and assessments.</p>
        </header>

        <div class="quiz-list">
            <?php if (count($quizzes) > 0): ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <div class="quiz-info">
                            <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                        </div>
                        <div class="quiz-meta">
                            <span><i class="fa-solid fa-question-circle"></i> <?php echo $quiz['question_count']; ?> Questions</span>
                            <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn-take-quiz">Take Quiz</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No quizzes are available at the moment. Please check back later.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php
// Include the footer
include 'includes/footer.php'; 
?>