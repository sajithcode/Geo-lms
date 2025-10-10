<?php
$currentPage = 'quizzes'; // To keep the 'Quizzes' link active in the sidebar

// Include session check and database connection
require_once 'php/session_check.php';
require_once 'config/database.php';

// --- Data Fetching Logic ---

// 1. Get and validate the quiz ID from the URL
$quiz_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$quiz_id) {
    header("location: quizzes.php"); // Redirect if ID is invalid
    exit;
}

// 2. Fetch the quiz title
$stmt = $pdo->prepare("SELECT title FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if quiz not found
if (!$quiz) {
    header("location: quizzes.php");
    exit;
}

// 3. Fetch all questions and their corresponding answers for this quiz
$sql = "SELECT q.question_id, q.question_text, a.answer_id, a.answer_text 
        FROM questions q 
        JOIN answers a ON q.question_id = a.question_id 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_id, a.answer_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([$quiz_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Structure the data into a nested array for easy display
$questions = [];
foreach ($results as $row) {
    // Add question text if not already set
    if (!isset($questions[$row['question_id']])) {
        $questions[$row['question_id']] = [
            'question_text' => $row['question_text'],
            'answers' => []
        ];
    }
    // Add answer to the question
    $questions[$row['question_id']]['answers'][] = [
        'answer_id' => $row['answer_id'],
        'answer_text' => $row['answer_text']
    ];
}

include 'includes/header.php';
?>
<script>document.title = 'Take Quiz: <?php echo htmlspecialchars($quiz['title']); ?> - Self-Learning Hub';</script>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p>Please answer all questions to the best of your ability.</p>
        </header>

        <div class="quiz-container">
            <form action="php/submit_quiz.php" method="POST">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                
                <?php $questionNumber = 1; ?>
                <?php foreach ($questions as $question_id => $data): ?>
                    <div class="question-block">
                        <h3>Question <?php echo $questionNumber++; ?>: <?php echo htmlspecialchars($data['question_text']); ?></h3>
                        <div class="answers-block">
                            <?php foreach ($data['answers'] as $answer): ?>
                                <label class="answer-label">
                                    <input type="radio" name="answers[<?php echo $question_id; ?>]" value="<?php echo $answer['answer_id']; ?>" required>
                                    <span><?php echo htmlspecialchars($answer['answer_text']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn-submit-quiz">Submit Quiz</button>
            </form>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>