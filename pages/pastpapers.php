<?php
$currentPage = 'resources';

require_once '../php/session_check.php';
require_once '../config/database.php';

// Check if pastpapers table exists
$tables_check = $pdo->query("SHOW TABLES LIKE 'pastpapers'")->fetchAll();
$has_pastpapers_table = count($tables_check) > 0;

// Get search and filter parameters
$search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$selected_category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$selected_year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$selected_semester = filter_input(INPUT_GET, 'semester', FILTER_VALIDATE_INT);
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?? 'recent';

// Initialize variables
$pastpapers = [];
$categories = [];
$years = [];

if ($has_pastpapers_table) {
    // Fetch categories
    try {
        $cat_check = $pdo->query("SHOW TABLES LIKE 'resource_categories'")->fetchAll();
        if (count($cat_check) > 0) {
            $stmt = $pdo->query("SELECT * FROM resource_categories ORDER BY category_name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $categories = [];
    }

    // Fetch available years
    try {
        $stmt = $pdo->query("SELECT DISTINCT year FROM pastpapers WHERE year IS NOT NULL ORDER BY year DESC");
        $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $years = [];
    }

    // Build WHERE clause
    $where_clauses = [];
    $params = [];

    if ($search_query) {
        $where_clauses[] = "(pp.title LIKE ? OR pp.description LIKE ?)";
        $search_term = "%{$search_query}%";
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if ($selected_category) {
        $where_clauses[] = "pp.category_id = ?";
        $params[] = $selected_category;
    }

    if ($selected_year) {
        $where_clauses[] = "pp.year = ?";
        $params[] = $selected_year;
    }

    if ($selected_semester) {
        $where_clauses[] = "pp.semester = ?";
        $params[] = $selected_semester;
    }

    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

    // Build ORDER BY clause
    $order_by = match($sort_by) {
        'popular' => 'pp.download_count DESC',
        'title' => 'pp.title ASC',
        'year' => 'pp.year DESC, pp.semester DESC',
        default => 'pp.created_at DESC'
    };

    // Fetch pastpapers with ratings
    try {
        $sql = "SELECT pp.*, 
                rc.category_name,
                u.username as uploader_name,
                (SELECT AVG(rating) FROM resource_ratings WHERE resource_type = 'pastpaper' AND resource_id = pp.pastpaper_id) as avg_rating,
                (SELECT COUNT(*) FROM resource_ratings WHERE resource_type = 'pastpaper' AND resource_id = pp.pastpaper_id) as rating_count
                FROM pastpapers pp
                LEFT JOIN resource_categories rc ON pp.category_id = rc.category_id
                LEFT JOIN users u ON pp.uploaded_by = u.user_id
                {$where_sql}
                ORDER BY {$order_by}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pastpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $pastpapers = [];
    }
}

include '../includes/header.php';
?>
<script>document.title = 'Past Papers - Self-Learning Hub';</script>

<style>
.search-filter-bar {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.filter-row {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 12px 15px;
    padding-left: 40px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1em;
}

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
}

.filter-select {
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1em;
    min-width: 150px;
}

.btn-clear {
    padding: 12px 24px;
    background: #f7fafc;
    color: #4a5568;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.btn-clear:hover {
    background: #e2e8f0;
}

.resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.resource-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.resource-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
}

.resource-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 12px;
}

.resource-title {
    font-size: 1.1em;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 8px 0;
    line-height: 1.4;
}

.category-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.75em;
    font-weight: 600;
    white-space: nowrap;
}

.exam-info {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.info-badge {
    background: #edf2f7;
    color: #2d3748;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85em;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.info-badge.year {
    background: #e6fffa;
    color: #047857;
}

.info-badge.semester {
    background: #fef3c7;
    color: #92400e;
}

.resource-description {
    color: #4a5568;
    font-size: 0.95em;
    line-height: 1.5;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex-grow: 1;
}

.resource-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #718096;
    font-size: 0.85em;
}

.meta-item i {
    color: #a0aec0;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
}

.stars {
    display: flex;
    gap: 2px;
}

.stars i {
    color: #fbbf24;
    font-size: 0.9em;
}

.rating-count {
    color: #718096;
    font-size: 0.85em;
}

.download-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.download-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

.warning-box {
    background: #fffaf0;
    border: 2px solid #ed8936;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.warning-box i {
    font-size: 3em;
    color: #ed8936;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .resources-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        min-width: auto;
    }
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1><i class="fas fa-file-alt"></i> Past Papers</h1>
            <p>Previous year examination papers for practice and revision</p>
        </header>

        <?php if (!$has_pastpapers_table): ?>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Past Papers Table Not Found</h3>
            <p>Please run the database migration to create the pastpapers table.</p>
            <p style="margin-top: 15px;"><a href="../docs/run_migration.php" class="download-btn" style="display: inline-flex;">Run Migration</a></p>
        </div>
        <?php else: ?>

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <form method="GET" class="filter-row">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search past papers by title or description..." value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
                </div>
                
                <select name="category" class="filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($selected_category == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="year" class="filter-select">
                    <option value="">All Years</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="semester" class="filter-select">
                    <option value="">All Semesters</option>
                    <option value="1" <?php echo ($selected_semester === 1) ? 'selected' : ''; ?>>Semester 1</option>
                    <option value="2" <?php echo ($selected_semester === 2) ? 'selected' : ''; ?>>Semester 2</option>
                </select>
                
                <select name="sort" class="filter-select">
                    <option value="recent" <?php echo ($sort_by === 'recent') ? 'selected' : ''; ?>>Most Recent</option>
                    <option value="popular" <?php echo ($sort_by === 'popular') ? 'selected' : ''; ?>>Most Popular</option>
                    <option value="year" <?php echo ($sort_by === 'year') ? 'selected' : ''; ?>>Year (Newest)</option>
                    <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>Title (A-Z)</option>
                </select>
                
                <button type="submit" class="download-btn">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <?php if ($search_query || $selected_category || $selected_year || $selected_semester || $sort_by !== 'recent'): ?>
                <a href="pastpapers.php" class="btn-clear">
                    <i class="fas fa-times"></i> Clear
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Past Papers Grid -->
        <?php if (empty($pastpapers)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>No Past Papers Found</h3>
                <p><?php echo $search_query ? 'Try different search terms or filters.' : 'No past papers have been uploaded yet.'; ?></p>
                <?php if (!$search_query): ?>
                <p style="margin-top: 15px;"><a href="../docs/add_dummy_resources.php" class="download-btn" style="display: inline-flex;">Add Dummy Past Papers</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="resources-grid">
                <?php foreach ($pastpapers as $paper): ?>
                    <div class="resource-card">
                        <div class="resource-header">
                            <h3 class="resource-title"><?php echo htmlspecialchars($paper['title']); ?></h3>
                            <?php if ($paper['category_name']): ?>
                                <span class="category-badge"><?php echo htmlspecialchars($paper['category_name']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="exam-info">
                            <?php if ($paper['year']): ?>
                                <span class="info-badge year">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo $paper['year']; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($paper['semester']): ?>
                                <span class="info-badge semester">
                                    <i class="fas fa-book-reader"></i>
                                    Semester <?php echo $paper['semester']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="resource-description"><?php echo htmlspecialchars($paper['description']); ?></p>
                        
                        <div class="resource-meta">
                            <span class="meta-item">
                                <i class="fas fa-file-pdf"></i>
                                <?php echo number_format($paper['file_size'] / 1048576, 1); ?> MB
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-download"></i>
                                <?php echo $paper['download_count']; ?> downloads
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-eye"></i>
                                <?php echo $paper['view_count']; ?> views
                            </span>
                        </div>
                        
                        <?php if ($paper['avg_rating']): ?>
                            <div class="rating-display">
                                <div class="stars">
                                    <?php 
                                    $rating = round($paper['avg_rating']);
                                    for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo $i <= $rating ? '#fbbf24' : '#e2e8f0'; ?>;"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-count">(<?php echo $paper['rating_count']; ?> <?php echo $paper['rating_count'] == 1 ? 'rating' : 'ratings'; ?>)</span>
                            </div>
                        <?php endif; ?>
                        
                        <a href="../php/download_resource.php?type=pastpaper&id=<?php echo $paper['pastpaper_id']; ?>" class="download-btn">
                            <i class="fas fa-download"></i>
                            Download Past Paper
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
<?php
$currentPage = 'resources';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

include '../includes/header.php';
?>
<script>document.title = 'Past Papers - Self-Learning Hub';</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/pastpapers.css">

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Past Papers</h1>
            <p>Previous year exam papers for practice and revision.</p>
        </header>

        <div class="lr-content">
            <div class="notes-list">
                <?php
                $dir = __DIR__ . '/assets/pastpapers';
                $files = [];
                if (is_dir($dir)) {
                    $patterns = ['/*.pdf','/*.zip'];
                    foreach ($patterns as $p) {
                        $found = glob($dir . $p);
                        if ($found) $files = array_merge($files, $found);
                    }
                }

                if (!empty($files)) {
                    echo '<div class="resource-cards">';
                    foreach ($files as $filePath) {
                        $fileName = basename($filePath);
                        $fileUrl = 'assets/pastpapers/' . rawurlencode($fileName);
                        $sizeKb = round(filesize($filePath) / 1024, 1);
                        echo "<div class=\"note-card\">";
                        echo "<div class=\"note-info\"><strong>" . htmlspecialchars($fileName) . "</strong><div class=\"note-meta\">{$sizeKb} KB</div></div>";
                        echo "<div class=\"note-actions\"><a class=\"btn\" href=\"{$fileUrl}\" download>Download</a></div>";
                        echo "</div>";
                    }
                    echo '</div>';
                } else {
                    echo '<p>No past papers are available yet. Upload files to <code>assets/pastpapers/</code>.</p>';
                }
                ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
