<?php
$currentPage = 'quizzes'; // To keep the 'Quizzes' link active in the sidebar

// Include session check and database connection
require_once '../php/session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

// --- Data Fetching Logic ---

// 1. Get and validate the quiz ID from the URL
$quiz_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$quiz_id) {
    header("location: quizzes.php"); // Redirect if ID is invalid
    exit;
}

// 2. Fetch the quiz details (including timer, retry limit, randomization settings)
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if quiz not found
if (!$quiz) {
    header("location: quizzes.php");
    exit;
}

// 3. Check retry limit
$user_id = $_SESSION['id'];
$stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?");
$stmt->execute([$quiz_id, $user_id]);
$attempt_data = $stmt->fetch(PDO::FETCH_ASSOC);
$attempt_count = $attempt_data['attempt_count'];

// If retry limit is set and exceeded, redirect with message
if (!empty($quiz['retry_limit']) && $attempt_count >= $quiz['retry_limit']) {
    $_SESSION['error_message'] = "You have reached the maximum number of attempts ({$quiz['retry_limit']}) for this quiz.";
    header("location: quizzes.php");
    exit;
}

// Store quiz start time in session
if (!isset($_SESSION['quiz_start_time_' . $quiz_id])) {
    $_SESSION['quiz_start_time_' . $quiz_id] = time();
}

// 4. Fetch all questions and their corresponding answers for this quiz
// Check for question_type column
$columns = $pdo->query("SHOW COLUMNS FROM questions LIKE 'question_type'")->fetchAll();
$has_question_type = count($columns) > 0;

$question_type_select = $has_question_type ? ", q.question_type, q.points, q.explanation, q.image_url" : "";

$sql = "SELECT q.question_id, q.question_text{$question_type_select}, a.answer_id, a.answer_text 
        FROM questions q 
        JOIN answers a ON q.question_id = a.question_id 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_id, a.answer_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([$quiz_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Structure the data into a nested array for easy display
$questions = [];
foreach ($results as $row) {
    // Add question text if not already set
    if (!isset($questions[$row['question_id']])) {
        $questions[$row['question_id']] = [
            'question_text' => $row['question_text'],
            'question_type' => $has_question_type ? ($row['question_type'] ?? 'single') : 'single',
            'points' => $has_question_type ? ($row['points'] ?? 1) : 1,
            'explanation' => $has_question_type ? ($row['explanation'] ?? null) : null,
            'image_url' => $has_question_type ? ($row['image_url'] ?? null) : null,
            'answers' => []
        ];
    }
    // Add answer to the question
    $questions[$row['question_id']]['answers'][] = [
        'answer_id' => $row['answer_id'],
        'answer_text' => $row['answer_text']
    ];
}

// 6. Apply randomization if enabled
if (!empty($quiz['randomize_questions'])) {
    $question_ids = array_keys($questions);
    shuffle($question_ids);
    $randomized_questions = [];
    foreach ($question_ids as $id) {
        $randomized_questions[$id] = $questions[$id];
    }
    $questions = $randomized_questions;
}

if (!empty($quiz['randomize_answers'])) {
    foreach ($questions as &$question) {
        shuffle($question['answers']);
    }
    unset($question);
}

include '../includes/header.php';
?>
<script>document.title = 'Take Quiz: <?php echo htmlspecialchars($quiz['title']); ?> - Self-Learning Hub';</script>

<?php
// Calculate remaining attempts
$remaining_attempts = null;
if (!empty($quiz['retry_limit'])) {
    $remaining_attempts = $quiz['retry_limit'] - $attempt_count;
}

// Calculate time limit in seconds for JavaScript
$time_limit_seconds = !empty($quiz['time_limit']) ? ($quiz['time_limit'] * 60) : null;
?>

<style>
.quiz-timer {
    position: fixed;
    top: 80px;
    right: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    font-size: 1.2em;
    font-weight: bold;
    z-index: 1000;
    min-width: 150px;
    text-align: center;
}

.quiz-timer.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.quiz-info-banner {
    background: #f0f4f8;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.quiz-info-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quiz-info-item i {
    color: #667eea;
}

.question-block {
    background: white;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.question-block h3 {
    color: #2d3748;
    margin-bottom: 15px;
    font-size: 1.1em;
}

.question-image {
    max-width: 100%;
    height: auto;
    margin: 15px 0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.answers-block {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.answer-label {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.answer-label:hover {
    border-color: #667eea;
    background: #f7fafc;
}

.answer-label input[type="radio"],
.answer-label input[type="checkbox"] {
    margin-right: 12px;
    cursor: pointer;
}

.answer-label input[type="text"] {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 1em;
}

.btn-submit-quiz {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 40px;
    border: none;
    border-radius: 8px;
    font-size: 1.1em;
    font-weight: bold;
    cursor: pointer;
    margin-top: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.btn-submit-quiz:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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

.question-points {
    color: #667eea;
    font-weight: bold;
    font-size: 0.9em;
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Timer Display (shown only if time limit is set) -->
        <?php if ($time_limit_seconds): ?>
        <div id="timerDisplay" class="quiz-timer">
            <div style="font-size: 0.8em; margin-bottom: 5px;">Time Remaining</div>
            <div id="timerText">--:--</div>
        </div>
        <?php endif; ?>

        <header class="main-header">
            <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p><?php echo htmlspecialchars($quiz['description'] ?? 'Please answer all questions to the best of your ability.'); ?></p>
        </header>

        <!-- Quiz Info Banner -->
        <div class="quiz-info-banner">
            <?php if (!empty($quiz['difficulty'])): ?>
            <div class="quiz-info-item">
                <i class="fas fa-signal"></i>
                <span>Difficulty: 
                    <span class="difficulty-badge difficulty-<?php echo $quiz['difficulty']; ?>">
                        <?php echo ucfirst($quiz['difficulty']); ?>
                    </span>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if ($time_limit_seconds): ?>
            <div class="quiz-info-item">
                <i class="fas fa-clock"></i>
                <span>Time Limit: <?php echo $quiz['time_limit']; ?> minutes</span>
            </div>
            <?php endif; ?>
            
            <?php if ($remaining_attempts !== null): ?>
            <div class="quiz-info-item">
                <i class="fas fa-redo"></i>
                <span>Attempts Remaining: <?php echo $remaining_attempts; ?>/<?php echo $quiz['retry_limit']; ?></span>
            </div>
            <?php endif; ?>
            
            <div class="quiz-info-item">
                <i class="fas fa-question-circle"></i>
                <span>Questions: <?php echo count($questions); ?></span>
            </div>
        </div>

        <div class="quiz-container">
            <form action="../php/submit_quiz.php" method="POST" id="quizForm">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                <input type="hidden" name="time_spent" id="timeSpentInput" value="0">
                <?php echo csrf_token_field(); ?>
                
                <?php $questionNumber = 1; ?>
                <?php foreach ($questions as $question_id => $data): ?>
                    <div class="question-block">
                        <h3>
                            Question <?php echo $questionNumber++; ?>: <?php echo htmlspecialchars($data['question_text']); ?>
                            <?php if ($data['points'] > 1): ?>
                                <span class="question-points">(<?php echo $data['points']; ?> points)</span>
                            <?php endif; ?>
                        </h3>
                        
                        <?php if (!empty($data['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($data['image_url']); ?>" 
                                 alt="Question Image" 
                                 class="question-image">
                        <?php endif; ?>
                        
                        <div class="answers-block">
                            <?php if ($data['question_type'] === 'multiple'): ?>
                                <!-- Multiple choice (multiple correct answers) -->
                                <?php foreach ($data['answers'] as $answer): ?>
                                    <label class="answer-label">
                                        <input type="checkbox" 
                                               name="answers[<?php echo $question_id; ?>][]" 
                                               value="<?php echo $answer['answer_id']; ?>">
                                        <span><?php echo htmlspecialchars($answer['answer_text']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            
                            <?php elseif ($data['question_type'] === 'fill_blank'): ?>
                                <!-- Fill in the blank -->
                                <label class="answer-label">
                                    <span>Your Answer:</span>
                                    <input type="text" 
                                           name="answers[<?php echo $question_id; ?>]" 
                                           placeholder="Type your answer here..." 
                                           required>
                                </label>
                            
                            <?php else: ?>
                                <!-- Single choice (default) -->
                                <?php foreach ($data['answers'] as $answer): ?>
                                    <label class="answer-label">
                                        <input type="radio" 
                                               name="answers[<?php echo $question_id; ?>]" 
                                               value="<?php echo $answer['answer_id']; ?>" 
                                               required>
                                        <span><?php echo htmlspecialchars($answer['answer_text']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn-submit-quiz">Submit Quiz</button>
            </form>
        </div>
    </main>
</div>

<?php if ($time_limit_seconds): ?>
<script>
// Quiz Timer Functionality
let timeRemaining = <?php echo $time_limit_seconds; ?>; // in seconds
let timerInterval;

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function updateTimer() {
    const timerText = document.getElementById('timerText');
    const timerDisplay = document.getElementById('timerDisplay');
    
    timerText.textContent = formatTime(timeRemaining);
    
    // Warning when less than 5 minutes (300 seconds)
    if (timeRemaining <= 300 && !timerDisplay.classList.contains('warning')) {
        timerDisplay.classList.add('warning');
    }
    
    if (timeRemaining <= 0) {
        clearInterval(timerInterval);
        alert('Time is up! Your quiz will be submitted automatically.');
        document.getElementById('quizForm').submit();
    }
    
    timeRemaining--;
}

// Start timer
updateTimer();
timerInterval = setInterval(updateTimer, 1000);

// Track time spent and update hidden input
const startTime = Date.now();
setInterval(() => {
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    document.getElementById('timeSpentInput').value = timeSpent;
}, 1000);

// Confirm before leaving page
window.addEventListener('beforeunload', function (e) {
    e.preventDefault();
    e.returnValue = '';
    return '';
});

// Don't confirm when submitting the form
document.getElementById('quizForm').addEventListener('submit', function() {
    window.removeEventListener('beforeunload', arguments.callee);
});
</script>
<?php else: ?>
<script>
// Track time spent even without timer
const startTime = Date.now();
setInterval(() => {
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    document.getElementById('timeSpentInput').value = timeSpent;
}, 1000);

// Confirm before leaving page
window.addEventListener('beforeunload', function (e) {
    e.preventDefault();
    e.returnValue = '';
    return '';
});

// Don't confirm when submitting the form
document.getElementById('quizForm').addEventListener('submit', function() {
    window.removeEventListener('beforeunload', arguments.callee);
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
