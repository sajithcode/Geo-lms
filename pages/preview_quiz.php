<?php
$currentPage = 'quizzes';

// Include session check and database connection
require_once '../php/session_check.php';
require_once '../config/database.php';

// Get and validate the quiz ID from the URL
$quiz_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$quiz_id) {
    header("location: quizzes.php");
    exit;
}

// Fetch the quiz details
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if quiz not found
if (!$quiz) {
    header("location: quizzes.php");
    exit;
}

// Check for question_type column
$columns = $pdo->query("SHOW COLUMNS FROM questions LIKE 'question_type'")->fetchAll();
$has_question_type = count($columns) > 0;

$question_type_select = $has_question_type ? ", q.question_type, q.points, q.explanation, q.image_url" : "";

// Fetch all questions and answers
$sql = "SELECT q.question_id, q.question_text{$question_type_select}, a.answer_id, a.answer_text, a.is_correct
        FROM questions q 
        JOIN answers a ON q.question_id = a.question_id 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_id, a.answer_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([$quiz_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Structure the data into a nested array
$questions = [];
foreach ($results as $row) {
    if (!isset($questions[$row['question_id']])) {
        $questions[$row['question_id']] = [
            'question_text' => $row['question_text'],
            'question_type' => $has_question_type ? ($row['question_type'] ?? 'single') : 'single',
            'points' => $has_question_type ? ($row['points'] ?? 1) : 1,
            'explanation' => $has_question_type ? ($row['explanation'] ?? null) : null,
            'image_url' => $has_question_type ? ($row['image_url'] ?? null) : null,
            'answers' => [],
            'correct_answers' => []
        ];
    }
    $questions[$row['question_id']]['answers'][] = [
        'answer_id' => $row['answer_id'],
        'answer_text' => $row['answer_text'],
        'is_correct' => $row['is_correct']
    ];
    if ($row['is_correct']) {
        $questions[$row['question_id']]['correct_answers'][] = $row['answer_text'];
    }
}

// Check retry limit
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?");
$stmt->execute([$quiz_id, $user_id]);
$attempt_data = $stmt->fetch(PDO::FETCH_ASSOC);
$attempt_count = $attempt_data['attempt_count'];

$can_take_quiz = true;
$remaining_attempts = null;
if (!empty($quiz['retry_limit'])) {
    $remaining_attempts = $quiz['retry_limit'] - $attempt_count;
    if ($remaining_attempts <= 0) {
        $can_take_quiz = false;
    }
}

include '../includes/header.php';
?>
<script>document.title = 'Preview Quiz: <?php echo htmlspecialchars($quiz['title']); ?> - Self-Learning Hub';</script>

<style>
.preview-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    text-align: center;
}

.preview-banner h2 {
    margin: 0 0 10px 0;
    font-size: 1.5em;
}

.preview-banner p {
    margin: 5px 0;
    opacity: 0.9;
}

.quiz-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.meta-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.meta-card i {
    font-size: 2em;
    color: #667eea;
    margin-bottom: 10px;
}

.meta-card .label {
    font-size: 0.85em;
    color: #718096;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.meta-card .value {
    font-size: 1.3em;
    font-weight: bold;
    color: #2d3748;
}

.question-preview {
    background: white;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.question-preview h3 {
    color: #2d3748;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.question-type-badge {
    font-size: 0.75em;
    padding: 4px 12px;
    border-radius: 12px;
    background: #e6f2ff;
    color: #0066cc;
    font-weight: normal;
}

.question-image-preview {
    max-width: 100%;
    height: auto;
    margin: 15px 0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.answers-preview {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.answer-preview {
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.answer-preview.correct {
    border-color: #48bb78;
    background: #f0fff4;
}

.answer-preview i {
    color: #48bb78;
}

.explanation-box {
    margin-top: 15px;
    padding: 15px;
    background: #fffaf0;
    border-left: 4px solid #ed8936;
    border-radius: 4px;
}

.explanation-box strong {
    color: #744210;
}

.action-buttons {
    display: flex;
    gap: 15px;
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
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #e2e8f0;
    color: #2d3748;
}

.btn-secondary:hover {
    background: #cbd5e0;
}

.btn-disabled {
    background: #cbd5e0;
    color: #718096;
    cursor: not-allowed;
}

.difficulty-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: bold;
    text-transform: uppercase;
}

.difficulty-easy {
    background: #c6f6d5;
    color: #22543d;
}

.difficulty-medium {
    background: #feebc8;
    color: #7c2d12;
}

.difficulty-hard {
    background: #fed7d7;
    color: #742a2a;
}

.warning-message {
    background: #fff5f5;
    border: 2px solid #fc8181;
    color: #742a2a;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="preview-banner">
            <h2><i class="fas fa-eye"></i> Quiz Preview Mode</h2>
            <p>Review the questions before starting the quiz. Your answers will not be recorded.</p>
        </div>

        <?php if (!$can_take_quiz): ?>
        <div class="warning-message">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Quiz Unavailable:</strong> You have reached the maximum number of attempts (<?php echo $quiz['retry_limit']; ?>) for this quiz.
        </div>
        <?php endif; ?>

        <header class="main-header">
            <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p><?php echo htmlspecialchars($quiz['description'] ?? 'No description available.'); ?></p>
        </header>

        <!-- Quiz Metadata -->
        <div class="quiz-meta">
            <div class="meta-card">
                <i class="fas fa-question-circle"></i>
                <div class="label">Total Questions</div>
                <div class="value"><?php echo count($questions); ?></div>
            </div>

            <?php if (!empty($quiz['time_limit'])): ?>
            <div class="meta-card">
                <i class="fas fa-clock"></i>
                <div class="label">Time Limit</div>
                <div class="value"><?php echo $quiz['time_limit']; ?> min</div>
            </div>
            <?php endif; ?>

            <?php if (!empty($quiz['difficulty'])): ?>
            <div class="meta-card">
                <i class="fas fa-signal"></i>
                <div class="label">Difficulty</div>
                <div class="value">
                    <span class="difficulty-badge difficulty-<?php echo $quiz['difficulty']; ?>">
                        <?php echo ucfirst($quiz['difficulty']); ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <div class="meta-card">
                <i class="fas fa-check-circle"></i>
                <div class="label">Passing Score</div>
                <div class="value"><?php echo $quiz['passing_score']; ?>%</div>
            </div>

            <?php if ($remaining_attempts !== null): ?>
            <div class="meta-card">
                <i class="fas fa-redo"></i>
                <div class="label">Attempts Remaining</div>
                <div class="value"><?php echo max(0, $remaining_attempts); ?>/<?php echo $quiz['retry_limit']; ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Questions Preview -->
        <h2 style="margin: 30px 0 20px 0; color: #2d3748;">Questions</h2>
        
        <?php $questionNumber = 1; ?>
        <?php foreach ($questions as $question_id => $data): ?>
            <div class="question-preview">
                <h3>
                    <span>
                        Question <?php echo $questionNumber++; ?>: <?php echo htmlspecialchars($data['question_text']); ?>
                        <?php if ($data['points'] > 1): ?>
                            <span class="question-type-badge"><?php echo $data['points']; ?> points</span>
                        <?php endif; ?>
                    </span>
                    <span class="question-type-badge">
                        <?php 
                        $type_labels = [
                            'single' => 'Single Choice',
                            'multiple' => 'Multiple Choice',
                            'fill_blank' => 'Fill in the Blank',
                            'matching' => 'Matching',
                            'essay' => 'Essay'
                        ];
                        echo $type_labels[$data['question_type']] ?? 'Single Choice';
                        ?>
                    </span>
                </h3>
                
                <?php if (!empty($data['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($data['image_url']); ?>" 
                         alt="Question Image" 
                         class="question-image-preview">
                <?php endif; ?>
                
                <div class="answers-preview">
                    <?php if ($data['question_type'] === 'fill_blank'): ?>
                        <div class="answer-preview correct">
                            <i class="fas fa-check-circle"></i>
                            <span><strong>Correct Answer:</strong> <?php echo htmlspecialchars(implode(' or ', $data['correct_answers'])); ?></span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($data['answers'] as $answer): ?>
                            <div class="answer-preview <?php echo $answer['is_correct'] ? 'correct' : ''; ?>">
                                <?php if ($answer['is_correct']): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($answer['answer_text']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($data['explanation'])): ?>
                    <div class="explanation-box">
                        <strong><i class="fas fa-lightbulb"></i> Explanation:</strong>
                        <p style="margin: 10px 0 0 0;"><?php echo htmlspecialchars($data['explanation']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if ($can_take_quiz): ?>
                <a href="take_quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                    <i class="fas fa-play"></i> Start Quiz
                </a>
            <?php else: ?>
                <span class="btn btn-disabled">
                    <i class="fas fa-ban"></i> Quiz Unavailable
                </span>
            <?php endif; ?>
            
            <a href="quizzes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Quizzes
            </a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
