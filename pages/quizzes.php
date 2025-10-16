<?php
$currentPage = 'quizzes'; // To set the active link in the sidebar

// Include session check and database connection
require_once '../php/session_check.php';
require_once '../config/database.php';

// Check for category_id and difficulty columns
$columns_cat = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'category_id'")->fetchAll();
$has_category = count($columns_cat) > 0;

$columns_diff = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'difficulty'")->fetchAll();
$has_difficulty = count($columns_diff) > 0;

$columns_time = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'time_limit'")->fetchAll();
$has_time_limit = count($columns_time) > 0;

$columns_retry = $pdo->query("SHOW COLUMNS FROM quizzes LIKE 'retry_limit'")->fetchAll();
$has_retry_limit = count($columns_retry) > 0;

// Build SELECT query dynamically
$category_select = $has_category ? ", q.category_id, qc.category_name" : "";
$difficulty_select = $has_difficulty ? ", q.difficulty" : "";
$time_select = $has_time_limit ? ", q.time_limit" : "";
$retry_select = $has_retry_limit ? ", q.retry_limit" : "";

$category_join = $has_category ? "LEFT JOIN quiz_categories qc ON q.category_id = qc.category_id" : "";

// Get filter parameters
$selected_category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$selected_difficulty = filter_input(INPUT_GET, 'difficulty', FILTER_SANITIZE_STRING);

// Build WHERE clause for filters
$where_clauses = [];
$params = [];

if ($selected_category && $has_category) {
    $where_clauses[] = "q.category_id = ?";
    $params[] = $selected_category;
}

if ($selected_difficulty && $has_difficulty) {
    $where_clauses[] = "q.difficulty = ?";
    $params[] = $selected_difficulty;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch all quizzes with metadata
$sql = "SELECT q.quiz_id, q.title, q.description, q.passing_score{$category_select}{$difficulty_select}{$time_select}{$retry_select}, 
        COUNT(qu.question_id) as question_count 
        FROM quizzes q 
        {$category_join}
        LEFT JOIN questions qu ON q.quiz_id = qu.quiz_id 
        {$where_sql}
        GROUP BY q.quiz_id
        ORDER BY q.quiz_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for filter dropdown
$categories = [];
if ($has_category) {
    $stmt_cat = $pdo->query("SELECT * FROM quiz_categories ORDER BY category_name");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
}

// Include the header
include '../includes/header.php';
?>
<script>document.title = 'Quizzes - Self-Learning Hub';</script>

<style>
.filters-bar {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-size: 0.85em;
    color: #718096;
    font-weight: 600;
}

.filter-group select {
    padding: 10px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.95em;
    min-width: 180px;
    cursor: pointer;
}

.filter-group select:focus {
    outline: none;
    border-color: #667eea;
}

.btn-reset-filters {
    padding: 10px 20px;
    background: #e2e8f0;
    color: #2d3748;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    margin-top: auto;
}

.btn-reset-filters:hover {
    background: #cbd5e0;
}

.quiz-card {
    background: white;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.quiz-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.quiz-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.quiz-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
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

.category-badge {
    background: #e6f2ff;
    color: #0066cc;
}

.quiz-info h3 {
    color: #2d3748;
    margin: 0 0 10px 0;
    font-size: 1.3em;
}

.quiz-info p {
    color: #718096;
    margin: 0 0 15px 0;
}

.quiz-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 15px 0;
    padding: 15px 0;
    border-top: 1px solid #e2e8f0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4a5568;
}

.meta-item i {
    color: #667eea;
}

.quiz-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-secondary:hover {
    background: #f7fafc;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 10px;
}

.empty-state i {
    font-size: 4em;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #2d3748;
    margin-bottom: 10px;
}

.empty-state p {
    color: #718096;
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Self-Assessment & Quizzes</h1>
            <p>Evaluate your knowledge through interactive quizzes and assessments.</p>
        </header>

        <!-- Filters Bar -->
        <?php if ($has_category || $has_difficulty): ?>
        <form method="GET" class="filters-bar">
            <?php if ($has_category && count($categories) > 0): ?>
            <div class="filter-group">
                <label for="category">Category</label>
                <select name="category" id="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" 
                                <?php echo $selected_category == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($has_difficulty): ?>
            <div class="filter-group">
                <label for="difficulty">Difficulty</label>
                <select name="difficulty" id="difficulty" onchange="this.form.submit()">
                    <option value="">All Levels</option>
                    <option value="easy" <?php echo $selected_difficulty === 'easy' ? 'selected' : ''; ?>>Easy</option>
                    <option value="medium" <?php echo $selected_difficulty === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="hard" <?php echo $selected_difficulty === 'hard' ? 'selected' : ''; ?>>Hard</option>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($selected_category || $selected_difficulty): ?>
            <button type="button" class="btn-reset-filters" onclick="window.location.href='quizzes.php'">
                <i class="fas fa-times"></i> Clear Filters
            </button>
            <?php endif; ?>
        </form>
        <?php endif; ?>

        <div class="quiz-list">
            <?php if (count($quizzes) > 0): ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <div class="quiz-header">
                            <div class="quiz-info">
                                <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            </div>
                            <div class="quiz-badges">
                                <?php if ($has_category && !empty($quiz['category_name'])): ?>
                                    <span class="badge category-badge">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($quiz['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($has_difficulty && !empty($quiz['difficulty'])): ?>
                                    <span class="badge difficulty-<?php echo $quiz['difficulty']; ?>">
                                        <?php echo ucfirst($quiz['difficulty']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                        
                        <div class="quiz-meta-grid">
                            <div class="meta-item">
                                <i class="fas fa-question-circle"></i>
                                <span><?php echo $quiz['question_count']; ?> Questions</span>
                            </div>
                            
                            <div class="meta-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Passing: <?php echo $quiz['passing_score']; ?>%</span>
                            </div>
                            
                            <?php if ($has_time_limit && !empty($quiz['time_limit'])): ?>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $quiz['time_limit']; ?> minutes</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($has_retry_limit && !empty($quiz['retry_limit'])): ?>
                            <div class="meta-item">
                                <i class="fas fa-redo"></i>
                                <span><?php echo $quiz['retry_limit']; ?> attempts max</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="quiz-actions">
                            <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-play"></i> Take Quiz
                            </a>
                            <a href="preview_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> Preview
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Quizzes Found</h3>
                    <p>
                        <?php if ($selected_category || $selected_difficulty): ?>
                            No quizzes match your selected filters. Try adjusting or clearing the filters.
                        <?php else: ?>
                            No quizzes are available at the moment. Please check back later.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php
// Display error message if set
if (isset($_SESSION['error_message'])):
?>
<script>
alert('<?php echo addslashes($_SESSION['error_message']); ?>');
</script>
<?php
    unset($_SESSION['error_message']);
endif;
?>

<?php
// Include the footer
include '../includes/footer.php'; 
?>
