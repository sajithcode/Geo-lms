<?php
$currentPage = 'admin_quiz_categories';

// Include admin session check
require_once 'php/admin_session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($category_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM quiz_categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $_SESSION['success_message'] = "Category deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error deleting category. It may be in use by quizzes.";
        }
        header("Location: quiz_categories.php");
        exit;
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_redirect('quiz_categories.php');
    
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    
    if (empty($category_name)) {
        $_SESSION['error_message'] = "Category name is required!";
    } else {
        try {
            if ($category_id) {
                // Update existing category
                $stmt = $pdo->prepare("UPDATE quiz_categories SET category_name = ?, description = ?, icon = ? WHERE category_id = ?");
                $stmt->execute([$category_name, $description, $icon, $category_id]);
                $_SESSION['success_message'] = "Category updated successfully!";
            } else {
                // Add new category
                $stmt = $pdo->prepare("INSERT INTO quiz_categories (category_name, description, icon) VALUES (?, ?, ?)");
                $stmt->execute([$category_name, $description, $icon]);
                $_SESSION['success_message'] = "Category added successfully!";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error saving category: " . $e->getMessage();
        }
        header("Location: quiz_categories.php");
        exit;
    }
}

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = $pdo->prepare("SELECT * FROM quiz_categories WHERE category_id = ?");
        $stmt->execute([$edit_id]);
        $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Fetch all categories with quiz count
$sql = "SELECT qc.*, COUNT(q.quiz_id) as quiz_count 
        FROM quiz_categories qc 
        LEFT JOIN quizzes q ON qc.category_id = q.category_id
        GROUP BY qc.category_id
        ORDER BY qc.category_name";
$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Categories - Geo-LMS Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --admin-primary: #667eea;
            --admin-secondary: #764ba2;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 24px;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-card, .list-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
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
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
        }
        
        .btn-primary {
            background: var(--admin-primary);
            color: white;
            width: 100%;
            justify-content: center;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .category-item {
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .category-item:hover {
            border-color: var(--admin-primary);
            background: #f9fafb;
        }
        
        .category-info {
            flex: 1;
        }
        
        .category-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .category-desc {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .category-count {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .category-actions {
            display: flex;
            gap: 8px;
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
        
        .form-help {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="admin-dashboard">
    <div class="admin-header">
        <h1>
            <i class="fa-solid fa-tags"></i>
            Quiz Categories
        </h1>
        <p>Organize quizzes by categories</p>
    </div>

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

    <div style="margin-bottom: 20px;">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <a href="quizzes.php" class="btn btn-secondary" style="margin-left: 10px;">
            <i class="fa-solid fa-puzzle-piece"></i> Manage Quizzes
        </a>
    </div>

    <div class="content-grid">
        <!-- Add/Edit Form -->
        <div class="form-card">
            <h2 style="margin: 0 0 20px 0;">
                <?php echo $edit_category ? '<i class="fas fa-edit"></i> Edit Category' : '<i class="fas fa-plus"></i> Add Category'; ?>
            </h2>
            
            <form method="POST" action="">
                <?php echo csrf_token_field(); ?>
                <?php if ($edit_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="category_name">Category Name <span style="color: red;">*</span></label>
                    <input type="text" id="category_name" name="category_name" required 
                           value="<?php echo $edit_category ? htmlspecialchars($edit_category['category_name']) : ''; ?>"
                           placeholder="e.g., Geography, Mathematics">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Brief description of this category"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="icon">Icon Class</label>
                    <input type="text" id="icon" name="icon" 
                           value="<?php echo $edit_category ? htmlspecialchars($edit_category['icon']) : ''; ?>"
                           placeholder="e.g., fa-globe, fa-book">
                    <div class="form-help">Font Awesome icon class (optional)</div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 
                    <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                </button>
                
                <?php if ($edit_category): ?>
                    <a href="quiz_categories.php" class="btn btn-secondary" style="width: 100%; margin-top: 10px; justify-content: center;">
                        <i class="fas fa-times"></i> Cancel Edit
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Categories List -->
        <div class="list-card">
            <h2 style="margin: 0 0 20px 0;"><i class="fas fa-list"></i> All Categories (<?php echo count($categories); ?>)</h2>
            
            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <div class="category-info">
                            <div class="category-name">
                                <?php if ($cat['icon']): ?>
                                    <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </div>
                            <?php if ($cat['description']): ?>
                                <div class="category-desc"><?php echo htmlspecialchars($cat['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <span class="category-count">
                                <?php echo $cat['quiz_count']; ?> quiz<?php echo $cat['quiz_count'] != 1 ? 'zes' : ''; ?>
                            </span>
                            <div class="category-actions">
                                <a href="?edit=<?php echo $cat['category_id']; ?>" 
                                   class="btn btn-primary btn-sm" 
                                   title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($cat['quiz_count'] == 0): ?>
                                    <a href="?action=delete&id=<?php echo $cat['category_id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       title="Delete"
                                       onclick="return confirm('Delete this category?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <i class="fas fa-tags" style="font-size: 3em; margin-bottom: 10px; color: #cbd5e0;"></i>
                    <p>No categories yet. Create your first category!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
