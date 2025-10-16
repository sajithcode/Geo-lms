<?php
$currentPage = 'teacher_quizzes';

// Include teacher session check
require_once 'php/teacher_session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

// Get quiz ID
$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
if (!$quiz_id) {
    $_SESSION['error_message'] = "Invalid quiz ID!";
    header("Location: quizzes.php");
    exit;
}

// Fetch quiz details
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    $_SESSION['error_message'] = "Quiz not found!";
    header("Location: quizzes.php");
    exit;
}

// Check which columns exist in questions and answers tables
try {
    $question_columns = $pdo->query("SHOW COLUMNS FROM questions")->fetchAll(PDO::FETCH_COLUMN);
    $has_question_type = in_array('question_type', $question_columns);
    $has_points = in_array('points', $question_columns);
    $has_explanation = in_array('explanation', $question_columns);
    $has_image_url = in_array('image_url', $question_columns);
} catch (PDOException $e) {
    // Tables might not exist yet
    $has_question_type = false;
    $has_points = false;
    $has_explanation = false;
    $has_image_url = false;
}

// Handle delete question
if (isset($_GET['action']) && $_GET['action'] === 'delete_question' && isset($_GET['question_id'])) {
    $question_id = filter_input(INPUT_GET, 'question_id', FILTER_VALIDATE_INT);
    if ($question_id) {
        try {
            // Delete answers first (foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            // Delete question
            $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id = ?");
            $stmt->execute([$question_id]);
            
            $_SESSION['success_message'] = "Question deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error deleting question: " . $e->getMessage();
        }
        header("Location: manage_questions.php?quiz_id=" . $quiz_id);
        exit;
    }
}

// Handle add/edit question form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_validate_or_redirect('manage_questions.php?quiz_id=' . $quiz_id);
    
    $question_text = trim($_POST['question_text']);
    $question_type = $_POST['question_type'] ?? 'single';
    $points = filter_input(INPUT_POST, 'points', FILTER_VALIDATE_INT) ?? 1;
    $explanation = trim($_POST['explanation'] ?? '');
    $answers = $_POST['answers'] ?? [];
    $correct_answers = $_POST['correct_answers'] ?? [];
    
    if (empty($question_text)) {
        $_SESSION['error_message'] = "Question text is required!";
    } elseif (empty($answers) || count(array_filter($answers)) < 2) {
        $_SESSION['error_message'] = "At least 2 answer options are required!";
    } elseif (empty($correct_answers)) {
        $_SESSION['error_message'] = "At least one correct answer must be selected!";
    } else {
        try {
            if ($_POST['action'] === 'add') {
                // Build dynamic INSERT query for questions
                $columns = ['quiz_id', 'question_text'];
                $values = [$quiz_id, $question_text];
                $placeholders = ['?', '?'];
                
                if ($has_question_type) {
                    $columns[] = 'question_type';
                    $values[] = $question_type;
                    $placeholders[] = '?';
                }
                if ($has_points) {
                    $columns[] = 'points';
                    $values[] = $points;
                    $placeholders[] = '?';
                }
                if ($has_explanation) {
                    $columns[] = 'explanation';
                    $values[] = $explanation;
                    $placeholders[] = '?';
                }
                
                $sql = "INSERT INTO questions (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                $question_id = $pdo->lastInsertId();
                
            } else { // edit
                $question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
                
                // Build dynamic UPDATE query
                $set_clauses = ['question_text = ?'];
                $values = [$question_text];
                
                if ($has_question_type) {
                    $set_clauses[] = 'question_type = ?';
                    $values[] = $question_type;
                }
                if ($has_points) {
                    $set_clauses[] = 'points = ?';
                    $values[] = $points;
                }
                if ($has_explanation) {
                    $set_clauses[] = 'explanation = ?';
                    $values[] = $explanation;
                }
                
                $values[] = $question_id;
                
                $sql = "UPDATE questions SET " . implode(', ', $set_clauses) . " WHERE question_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                
                // Delete existing answers
                $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id = ?");
                $stmt->execute([$question_id]);
            }
            
            // Insert answers
            foreach ($answers as $index => $answer_text) {
                $answer_text = trim($answer_text);
                if (!empty($answer_text)) {
                    $is_correct = in_array($index, $correct_answers) ? 1 : 0;
                    
                    $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                    $stmt->execute([$question_id, $answer_text, $is_correct]);
                }
            }
            
            $_SESSION['success_message'] = ($_POST['action'] === 'add') ? "Question added successfully!" : "Question updated successfully!";
            header("Location: manage_questions.php?quiz_id=" . $quiz_id);
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error saving question: " . $e->getMessage();
        }
    }
}

// Fetch all questions for this quiz
$question_type_select = $has_question_type ? ", q.question_type" : ", 'single' as question_type";
$points_select = $has_points ? ", q.points" : ", 1 as points";
$explanation_select = $has_explanation ? ", q.explanation" : ", NULL as explanation";

$sql = "SELECT q.question_id, q.question_text{$question_type_select}{$points_select}{$explanation_select}, 
               a.answer_id, a.answer_text, a.is_correct
        FROM questions q 
        LEFT JOIN answers a ON q.question_id = a.question_id 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_id, a.answer_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([$quiz_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Structure questions with answers
$questions = [];
foreach ($results as $row) {
    if (!isset($questions[$row['question_id']])) {
        $questions[$row['question_id']] = [
            'question_id' => $row['question_id'],
            'question_text' => $row['question_text'],
            'question_type' => $row['question_type'],
            'points' => $row['points'],
            'explanation' => $row['explanation'],
            'answers' => []
        ];
    }
    if ($row['answer_id']) {
        $questions[$row['question_id']]['answers'][] = [
            'answer_id' => $row['answer_id'],
            'answer_text' => $row['answer_text'],
            'is_correct' => $row['is_correct']
        ];
    }
}

// Get question to edit if edit_id is provided
$edit_question = null;
if (isset($_GET['edit_id'])) {
    $edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
    if (isset($questions[$edit_id])) {
        $edit_question = $questions[$edit_id];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --admin-primary: #10b981;
            --admin-secondary: #059669;
            --admin-success: #10b981;
            --admin-warning: #f59e0b;
            --admin-danger: #ef4444;
        }
        
        body {
            background: #f4f7fc;
        }
        
        /* Override sidebar colors for teacher theme */
        .sidebar {
            background: linear-gradient(180deg, #059669 0%, #047857 100%);
        }
        
        .sidebar-nav li.active a,
        .sidebar-nav li a:hover {
            background-color: var(--admin-primary);
        }
        
        .main-content {
            background: #f4f7fc;
        }
        
        .main-header h1 {
            color: #1c3d5a;
            margin-bottom: 5px;
        }
        
        .main-header p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .quiz-info-bar {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .quiz-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .stat {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            white-space: nowrap;
        }
        
        .stat i {
            color: var(--admin-primary);
            font-size: 1.1em;
        }
        
        @media (max-width: 768px) {
            .quiz-info-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .quiz-info-bar > div:last-child {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .quiz-info-bar .btn {
                flex: 1;
                min-width: calc(50% - 5px);
                justify-content: center;
            }
            
            .quiz-stats {
                gap: 20px;
            }
        }
        
        .two-column-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        @media (max-width: 1200px) {
            .two-column-layout {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .card h2 {
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #111827;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .questions-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .answers-section {
            margin: 20px 0;
        }
        
        .answer-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            align-items: center;
        }
        
        .answer-item input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
        
        .answer-item input[type="text"] {
            flex: 1;
        }
        
        .answer-item .btn-remove {
            padding: 8px 12px;
            background: var(--admin-danger);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
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
            font-size: 14px;
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
        
        .btn-outline {
            background: white;
            color: var(--admin-primary);
            border: 2px solid var(--admin-primary);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .question-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 16px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .question-card:hover {
            border-color: var(--admin-primary);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
            gap: 15px;
        }
        
        .question-text {
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }
        
        .question-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 12px;
        }
        
        .answers-list {
            margin: 10px 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .answer-option {
            padding: 12px;
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        
        .answer-option:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        
        .answer-option.correct {
            background: #d1fae5;
            border-color: #10b981;
            font-weight: 500;
        }
        
        .answer-option.correct i {
            color: #10b981;
            font-size: 1.1em;
        }
        
        .answer-option.incorrect i {
            color: #9ca3af;
        }
        
        .answer-option.incorrect {
            opacity: 0.85;
        }
        
        .answer-option span {
            flex: 1;
            word-break: break-word;
        }
        
        .question-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }
        
        @media (max-width: 768px) {
            .question-header {
                flex-direction: column;
            }
            
            .question-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .question-meta {
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .answer-option {
                flex-direction: row;
                gap: 8px;
                font-size: 0.875rem;
            }
            
            .question-actions {
                flex-wrap: wrap;
            }
            
            .btn-sm {
                flex: 1;
                justify-content: center;
                min-width: 45%;
            }
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
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-type {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .form-help {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Manage Questions: <?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p>Add and edit questions for this quiz</p>
        </header>

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

    <!-- Quiz Info Bar -->
    <div class="quiz-info-bar">
        <div class="quiz-stats">
            <div class="stat">
                <i class="fas fa-question-circle"></i>
                <strong><?php echo count($questions); ?></strong> Questions
            </div>
            <div class="stat">
                <i class="fas fa-check-circle"></i>
                Passing: <?php echo $quiz['passing_score']; ?>%
            </div>
        </div>
        <div>
            <a href="quizzes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Quizzes
            </a>
            <a href="../pages/preview_quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-outline" target="_blank">
                <i class="fas fa-eye"></i> Preview Quiz
            </a>
        </div>
    </div>

    <div class="two-column-layout">
        <!-- Left Column: Add/Edit Question Form -->
        <div class="card">
            <h2>
                <i class="fas fa-<?php echo $edit_question ? 'edit' : 'plus'; ?>"></i>
                <?php echo $edit_question ? 'Edit Question' : 'Add New Question'; ?>
            </h2>
            
            <form method="POST" action="">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="<?php echo $edit_question ? 'edit' : 'add'; ?>">
                <?php if ($edit_question): ?>
                    <input type="hidden" name="question_id" value="<?php echo $edit_question['question_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="question_text">Question Text <span style="color: red;">*</span></label>
                    <textarea id="question_text" name="question_text" required><?php echo $edit_question ? htmlspecialchars($edit_question['question_text']) : ''; ?></textarea>
                </div>
                
                <div class="form-grid">
                    <?php if ($has_question_type): ?>
                    <div class="form-group">
                        <label for="question_type">Question Type</label>
                        <select id="question_type" name="question_type">
                            <option value="single" <?php echo (!$edit_question || $edit_question['question_type'] === 'single') ? 'selected' : ''; ?>>Single Choice</option>
                            <option value="multiple" <?php echo ($edit_question && $edit_question['question_type'] === 'multiple') ? 'selected' : ''; ?>>Multiple Choice</option>
                            <option value="true_false" <?php echo ($edit_question && $edit_question['question_type'] === 'true_false') ? 'selected' : ''; ?>>True/False</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($has_points): ?>
                    <div class="form-group">
                        <label for="points">Points</label>
                        <input type="number" id="points" name="points" min="1" value="<?php echo $edit_question ? $edit_question['points'] : 1; ?>">
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($has_explanation): ?>
                <div class="form-group">
                    <label for="explanation">Explanation (Optional)</label>
                    <textarea id="explanation" name="explanation" rows="3"><?php echo $edit_question ? htmlspecialchars($edit_question['explanation'] ?? '') : ''; ?></textarea>
                    <div class="form-help">Shown to students after they complete the quiz</div>
                </div>
                <?php endif; ?>
                
                <!-- Answers Section -->
                <div class="answers-section">
                    <label><i class="fas fa-list"></i> Answer Options <span style="color: red;">*</span></label>
                    <div class="form-help" style="margin-bottom: 12px;">Check the correct answer(s). At least 2 options required.</div>
                    
                    <div id="answers-container">
                        <?php 
                        $answer_count = 0;
                        if ($edit_question && !empty($edit_question['answers'])): 
                            foreach ($edit_question['answers'] as $index => $answer):
                                $answer_count++;
                        ?>
                        <div class="answer-item">
                            <input type="checkbox" name="correct_answers[]" value="<?php echo $index; ?>" 
                                   <?php echo $answer['is_correct'] ? 'checked' : ''; ?>>
                            <input type="text" name="answers[]" placeholder="Answer option" 
                                   value="<?php echo htmlspecialchars($answer['answer_text']); ?>" required>
                            <button type="button" class="btn-remove" onclick="removeAnswer(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php 
                            endforeach;
                        else: 
                            // Default 4 answer fields
                            for ($i = 0; $i < 4; $i++):
                                $answer_count++;
                        ?>
                        <div class="answer-item">
                            <input type="checkbox" name="correct_answers[]" value="<?php echo $i; ?>">
                            <input type="text" name="answers[]" placeholder="Answer option" <?php echo $i < 2 ? 'required' : ''; ?>>
                            <button type="button" class="btn-remove" onclick="removeAnswer(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php 
                            endfor;
                        endif; 
                        ?>
                    </div>
                    
                    <button type="button" class="btn btn-outline" onclick="addAnswer()" style="margin-top: 12px;">
                        <i class="fas fa-plus"></i> Add Answer Option
                    </button>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 24px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $edit_question ? 'Update Question' : 'Add Question'; ?>
                    </button>
                    <?php if ($edit_question): ?>
                    <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel Edit
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Right Column: Questions List -->
        <div class="card">
            <h2><i class="fas fa-list"></i> Questions (<?php echo count($questions); ?>)</h2>
            
            <?php if (count($questions) > 0): ?>
                <div class="questions-container">
                <?php 
                $question_number = 1;
                foreach ($questions as $q): 
                ?>
                    <div class="question-card">
                        <div class="question-header">
                            <div style="flex: 1;">
                                <div class="question-text">
                                    <span style="color: var(--admin-primary); font-weight: 700; margin-right: 8px;">Q<?php echo $question_number; ?>.</span>
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                </div>
                                <div class="question-meta">
                                    <?php if ($has_question_type): ?>
                                    <span class="badge badge-type">
                                        <?php 
                                        $types = ['single' => 'Single Choice', 'multiple' => 'Multiple Choice', 'true_false' => 'True/False'];
                                        echo $types[$q['question_type']] ?? 'Single Choice';
                                        ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($has_points): ?>
                                    <span><i class="fas fa-star"></i> <?php echo $q['points']; ?> point<?php echo $q['points'] != 1 ? 's' : ''; ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-list"></i> <?php echo count($q['answers']); ?> answers</span>
                                </div>
                            </div>
                            <div class="question-actions">
                                <a href="?quiz_id=<?php echo $quiz_id; ?>&edit_id=<?php echo $q['question_id']; ?>" 
                                   class="btn btn-primary btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?quiz_id=<?php echo $quiz_id; ?>&action=delete_question&question_id=<?php echo $q['question_id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this question?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="answers-list">
                            <?php foreach ($q['answers'] as $ans): ?>
                                <div class="answer-option <?php echo $ans['is_correct'] ? 'correct' : 'incorrect'; ?>">
                                    <?php if ($ans['is_correct']): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php else: ?>
                                        <i class="far fa-circle"></i>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($ans['answer_text']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($has_explanation && $q['explanation']): ?>
                        <div style="margin-top: 12px; padding: 12px; background: white; border-radius: 6px; font-size: 0.875rem; border-left: 4px solid var(--admin-primary); display: flex; gap: 10px;">
                            <i class="fas fa-info-circle" style="color: var(--admin-primary); margin-top: 2px;"></i>
                            <div style="flex: 1;">
                                <strong style="display: block; margin-bottom: 4px; color: #374151;">Explanation:</strong>
                                <span style="color: #6b7280;"><?php echo nl2br(htmlspecialchars($q['explanation'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php 
                $question_number++;
                endforeach; 
                ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-question-circle"></i>
                    <h3>No Questions Yet</h3>
                    <p>Add your first question using the form on the left!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let answerIndex = <?php echo $answer_count; ?>;

function addAnswer() {
    const container = document.getElementById('answers-container');
    const answerItem = document.createElement('div');
    answerItem.className = 'answer-item';
    answerItem.innerHTML = `
        <input type="checkbox" name="correct_answers[]" value="${answerIndex}">
        <input type="text" name="answers[]" placeholder="Answer option">
        <button type="button" class="btn-remove" onclick="removeAnswer(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(answerItem);
    answerIndex++;
}

function removeAnswer(button) {
    const container = document.getElementById('answers-container');
    const answerItems = container.getElementsByClassName('answer-item');
    
    // Don't allow removing if only 2 answers left
    if (answerItems.length <= 2) {
        alert('At least 2 answer options are required!');
        return;
    }
    
    button.closest('.answer-item').remove();
    
    // Update checkbox values to maintain sequential order
    const checkboxes = container.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach((checkbox, index) => {
        checkbox.value = index;
    });
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const answers = document.querySelectorAll('input[name="answers[]"]');
    const correctAnswers = document.querySelectorAll('input[name="correct_answers[]"]:checked');
    
    // Count non-empty answers
    let filledAnswers = 0;
    answers.forEach(input => {
        if (input.value.trim() !== '') filledAnswers++;
    });
    
    if (filledAnswers < 2) {
        e.preventDefault();
        alert('Please provide at least 2 answer options!');
        return;
    }
    
    if (correctAnswers.length === 0) {
        e.preventDefault();
        alert('Please select at least one correct answer!');
        return;
    }
});
</script>

    </main>
</div>

</body>
</html>
