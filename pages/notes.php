<?php
$currentPage = 'resources';

// Allow guests to view notes (do not force login)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if notes table exists
$tables_check = $pdo->query("SHOW TABLES LIKE 'notes'")->fetchAll();
$has_notes_table = count($tables_check) > 0;

// Get filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'recent';

// Get predefined categories
$categories = [
    'Mathematics',
    'Physics', 
    'Chemistry',
    'Computer Science',
    'Engineering',
    'General',
    'Biology',
    'Economics',
    'Geography',
    'History'
];

// Fetch notes from database
$notes = [];
if ($has_notes_table) {
    try {
        $where_clauses = [];
        $params = [];
        
        if (!empty($search)) {
            $where_clauses[] = "(n.title LIKE ? OR n.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($category_filter)) {
            $where_clauses[] = "n.category = ?";
            $params[] = $category_filter;
        }
        
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        $order_sql = "ORDER BY n.created_at DESC";
        if ($sort === 'popular') {
            $order_sql = "ORDER BY n.downloads DESC";
        } elseif ($sort === 'title') {
            $order_sql = "ORDER BY n.title ASC";
        }
        
        $sql = "SELECT n.*, u.username
                FROM notes n 
                LEFT JOIN users u ON n.uploaded_by = u.user_id 
                $where_sql
                $order_sql";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $notes = [];
    }
}

include '../includes/header.php';
?>
<script>document.title = 'Notes - Self-Learning Hub';</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/notes.css">


<style>
.search-filter-bar {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.search-row {
    display: grid;
    grid-template-columns: 1fr auto auto auto;
    gap: 15px;
    align-items: end;
}

.search-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.search-group label {
    font-weight: 600;
    color: #4a5568;
    font-size: 0.9em;
}

.search-group input,
.search-group select {
    padding: 10px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 1em;
}

.search-group input:focus,
.search-group select:focus {
    outline: none;
    border-color: #667eea;
}

.btn-search {
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-reset {
    padding: 10px 20px;
    background: #e2e8f0;
    color: #2d3748;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}

.btn-reset:hover {
    background: #cbd5e0;
}

.resource-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.resource-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.resource-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.resource-header {
    margin-bottom: 15px;
}

.resource-title {
    font-size: 1.1em;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 10px;
}

.resource-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.badge-category {
    background: #e6f2ff;
    color: #0066cc;
}

.badge-size {
    background: #f0f4f8;
    color: #4a5568;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #f59e0b;
}

.resource-description {
    color: #718096;
    font-size: 0.9em;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.resource-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #718096;
}

.resource-stats span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.resource-actions {
    display: flex;
    gap: 10px;
}

.btn-download {
    flex: 1;
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-download:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1><i class="fas fa-sticky-note"></i> Browse Notes</h1>
            <p>Subject-wise notes available for download</p>
        </header>

        <?php if (!$has_notes_table): ?>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Notes Table Not Found</h3>
            <p>Please run the database migration to create the notes table.</p>
            <p style="margin-top: 15px;"><a href="../docs/run_migration.php" class="btn-search">Run Migration</a></p>
        </div>
        <?php else: ?>

        <!-- Search and Filter Bar -->
        <form method="GET" class="search-filter-bar">
            <div class="search-row">
                <div class="search-group">
                    <label for="search"><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" id="search" placeholder="Search notes..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <?php if (!empty($categories)): ?>
                <div class="search-group">
                    <label for="category"><i class="fas fa-filter"></i> Category</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter == $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="search-group">
                    <label for="sort"><i class="fas fa-sort"></i> Sort By</label>
                    <select name="sort" id="sort">
                        <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> Search
                </button>
                
                <?php if ($search || $category_filter || $sort !== 'recent'): ?>
                <button type="button" class="btn-reset" onclick="window.location.href='notes.php'">
                    <i class="fas fa-times"></i> Clear
                </button>
                <?php endif; ?>
            </div>
        </form>

        <!-- Notes Grid -->
        <?php if (empty($notes)): ?>
        <div class="empty-state">
            <i class="fas fa-sticky-note"></i>
            <h3>No Notes Found</h3>
            <p>
                <?php if ($search || $category_filter): ?>
                    No notes match your search criteria. Try adjusting your filters.
                <?php else: ?>
                    No notes are available yet. Check back later!
                <?php endif; ?>
            </p>
        </div>
        <?php else: ?>
        <div class="resource-grid">
            <?php foreach ($notes as $note): ?>
            <div class="resource-card">
                <div class="resource-header">
                    <div class="resource-title"><?php echo htmlspecialchars($note['title']); ?></div>
                    
                    <div class="resource-meta">
                        <?php if (!empty($note['category'])): ?>
                            <span class="badge badge-category">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($note['category']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <span class="badge badge-size">
                            <i class="fas fa-file"></i> <?php echo number_format($note['filesize'] / 1024 / 1024, 2); ?> MB
                        </span>
                    </div>
                </div>
                
                <?php if ($note['description']): ?>
                <div class="resource-description">
                    <?php echo htmlspecialchars($note['description']); ?>
                </div>
                <?php endif; ?>
                
                <div class="resource-stats">
                    <span>
                        <i class="fas fa-download"></i> <?php echo $note['downloads']; ?> downloads
                    </span>
                    <span>
                        <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($note['created_at'])); ?>
                    </span>
                </div>
                
                <div class="resource-actions">
                    <a href="../php/download_resource.php?type=note&id=<?php echo $note['id']; ?>" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
