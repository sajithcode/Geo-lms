<?php
$currentPage = 'admin_quizzes';

// Include admin session check
require_once 'php/admin_session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

// Get quiz ID
$quiz_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$quiz_id) {
    $_SESSION['error_message'] = "Invalid quiz ID!";
    header("Location: quizzes.php");
    exit;
}

// Fetch quiz data
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    $_SESSION['error_message'] = "Quiz not found!";
    header("Location: quizzes.php");
    exit;
}

// Check which columns exist in the database
$columns_check = $pdo->query("SHOW COLUMNS FROM quizzes")->fetchAll(PDO::FETCH_COLUMN);
$has_category = in_array('category_id', $columns_check);
$has_difficulty = in_array('difficulty', $columns_check);
$has_time_limit = in_array('time_limit', $columns_check);
$has_retry_limit = in_array('retry_limit', $columns_check);
$has_randomize_q = in_array('randomize_questions', $columns_check);
$has_randomize_a = in_array('randomize_answers', $columns_check);
$has_show_answers = in_array('show_answers_after', $columns_check);
$has_is_active = in_array('is_active', $columns_check);

// Fetch quiz categories if table exists
$categories = [];
try {
    if ($has_category) {
        $stmt = $pdo->query("SELECT * FROM quiz_categories ORDER BY category_name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Quiz categories table might not exist
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_redirect('quizzes.php');
    
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $difficulty = $_POST['difficulty'] ?? '';
    $time_limit = filter_input(INPUT_POST, 'time_limit', FILTER_VALIDATE_INT) ?? 0;
    $passing_score = filter_input(INPUT_POST, 'passing_score', FILTER_VALIDATE_FLOAT) ?? 60;
    $retry_limit = filter_input(INPUT_POST, 'retry_limit', FILTER_VALIDATE_INT) ?? 0;
    $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
    $randomize_answers = isset($_POST['randomize_answers']) ? 1 : 0;
    $show_answers_after = isset($_POST['show_answers_after']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if (empty($title)) {
        $_SESSION['error_message'] = "Quiz title is required!";
    } else {
        try {
            // Build dynamic UPDATE query based on existing columns
            $set_clauses = ['title = ?', 'description = ?', 'passing_score = ?'];
            $values = [$title, $description, $passing_score];
            
            if ($has_category) {
                $set_clauses[] = 'category_id = ?';
                $values[] = $category_id;
            }
            if ($has_difficulty) {
                $set_clauses[] = 'difficulty = ?';
                $values[] = $difficulty;
            }
            if ($has_time_limit) {
                $set_clauses[] = 'time_limit = ?';
                $values[] = $time_limit;
            }
            if ($has_retry_limit) {
                $set_clauses[] = 'retry_limit = ?';
                $values[] = $retry_limit;
            }
            if ($has_randomize_q) {
                $set_clauses[] = 'randomize_questions = ?';
                $values[] = $randomize_questions;
            }
            if ($has_randomize_a) {
                $set_clauses[] = 'randomize_answers = ?';
                $values[] = $randomize_answers;
            }
            if ($has_show_answers) {
                $set_clauses[] = 'show_answers_after = ?';
                $values[] = $show_answers_after;
            }
            if ($has_is_active) {
                $set_clauses[] = 'is_active = ?';
                $values[] = $is_active;
            }
            
            $values[] = $quiz_id; // Add quiz_id for WHERE clause
            
            $sql = "UPDATE quizzes SET " . implode(', ', $set_clauses) . " WHERE quiz_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            $_SESSION['success_message'] = "Quiz updated successfully!";
            header("Location: quizzes.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating quiz: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz - Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --admin-primary: #0a74da;
            --admin-secondary: #1c3d5a;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
        }
        
        .sidebar {
            background: #1c3d5a;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(10, 116, 218, 0.3);
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

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            opacity: 0.9;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .breadcrumb a:hover {
            opacity: 0.7;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.95em;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(10, 116, 218, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
            margin: 0;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
            color: #4a5568;
        }
        
        .form-help {
            font-size: 0.85em;
            color: #718096;
            margin-top: 4px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        
        .btn-primary {
            background: var(--admin-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--admin-secondary);
            box-shadow: 0 4px 12px rgba(10, 116, 218, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }
        
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .section-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2d3748;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 1.5em;
            }

            .form-container {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Edit Quiz</h1>
            <p>Update quiz details and settings</p>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>→</span>
                <a href="quizzes.php"><i class="fas fa-puzzle-piece"></i> Quizzes</a>
                <span>→</span>
                <span>Edit Quiz</span>
            </div>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <?php echo csrf_token_field(); ?>
                
                <!-- Basic Information -->
                <h3 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="title">Quiz Title <span style="color: red;">*</span></label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($quiz['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($quiz['description'] ?? ''); ?></textarea>
                    <div class="form-help">This will be shown to students before they start the quiz</div>
                </div>
                
                <?php if ($has_category || $has_difficulty): ?>
                <div class="form-grid">
                    <?php if ($has_category): ?>
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                        <?php echo ($quiz['category_id'] ?? '') == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($has_difficulty): ?>
                    <div class="form-group">
                        <label for="difficulty">Difficulty Level</label>
                        <select id="difficulty" name="difficulty">
                            <option value="">Not Set</option>
                            <option value="easy" <?php echo ($quiz['difficulty'] ?? '') == 'easy' ? 'selected' : ''; ?>>Easy</option>
                            <option value="medium" <?php echo ($quiz['difficulty'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="hard" <?php echo ($quiz['difficulty'] ?? '') == 'hard' ? 'selected' : ''; ?>>Hard</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Quiz Settings -->
                <h3 class="section-title"><i class="fas fa-cog"></i> Quiz Settings</h3>
                
                <div class="form-grid">
                    <?php if ($has_time_limit): ?>
                    <div class="form-group">
                        <label for="time_limit">Time Limit (minutes)</label>
                        <input type="number" id="time_limit" name="time_limit" min="0" 
                               value="<?php echo $quiz['time_limit'] ?? 0; ?>">
                        <div class="form-help">Leave 0 for no time limit</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="passing_score">Passing Score (%)</label>
                        <input type="number" id="passing_score" name="passing_score" min="0" max="100" step="0.01"
                               value="<?php echo $quiz['passing_score']; ?>">
                        <div class="form-help">Minimum score required to pass</div>
                    </div>
                    
                    <?php if ($has_retry_limit): ?>
                    <div class="form-group">
                        <label for="retry_limit">Retry Limit</label>
                        <input type="number" id="retry_limit" name="retry_limit" min="0" 
                               value="<?php echo $quiz['retry_limit'] ?? 0; ?>">
                        <div class="form-help">Maximum attempts allowed (0 for unlimited)</div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Advanced Options -->
                <?php if ($has_randomize_q || $has_randomize_a || $has_show_answers || $has_is_active): ?>
                <h3 class="section-title"><i class="fas fa-sliders-h"></i> Advanced Options</h3>
                
                <?php if ($has_randomize_q): ?>
                <div class="checkbox-group">
                    <input type="checkbox" id="randomize_questions" name="randomize_questions" value="1"
                           <?php echo ($quiz['randomize_questions'] ?? false) ? 'checked' : ''; ?>>
                    <label for="randomize_questions">Randomize question order for each attempt</label>
                </div>
                <?php endif; ?>
                
                <?php if ($has_randomize_a): ?>
                <div class="checkbox-group">
                    <input type="checkbox" id="randomize_answers" name="randomize_answers" value="1"
                           <?php echo ($quiz['randomize_answers'] ?? false) ? 'checked' : ''; ?>>
                    <label for="randomize_answers">Randomize answer options for each question</label>
                </div>
                <?php endif; ?>
                
                <?php if ($has_show_answers): ?>
                <div class="checkbox-group">
                    <input type="checkbox" id="show_answers_after" name="show_answers_after" value="1"
                           <?php echo ($quiz['show_answers_after'] ?? false) ? 'checked' : ''; ?>>
                    <label for="show_answers_after">Show correct answers after quiz completion</label>
                </div>
                <?php endif; ?>
                
                <?php if ($has_is_active): ?>
                <div class="checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           <?php echo ($quiz['is_active'] ?? false) ? 'checked' : ''; ?>>
                    <label for="is_active">Quiz is active</label>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <!-- Submit Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Quiz
                    </button>
                    <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-warning">
                        <i class="fas fa-question-circle"></i> Manage Questions
                    </a>
                    <a href="quizzes.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

    </main>
</div>

</body>
</html>
