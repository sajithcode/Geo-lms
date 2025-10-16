<?php
$currentPage = 'teacher_announcements';

require_once 'php/teacher_session_check.php';
require_once '../config/database.php';

$user_id = $_SESSION['id'];
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'create') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $category = $_POST['category'] ?? 'general';
        $priority = $_POST['priority'] ?? 'medium';
        
        if (!empty($title) && !empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, content, category, priority, published_by, status, published_at, created_at, updated_at) 
                                      VALUES (?, ?, ?, ?, ?, 'published', NOW(), NOW(), NOW())");
                $stmt->execute([$title, $content, $category, $priority, $user_id]);
                $_SESSION['success_message'] = "Announcement created successfully!";
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error creating announcement: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Please fill in all required fields.";
        }
        header("Location: announcements.php");
        exit;
    }
    
    if ($_POST['action'] === 'delete' && isset($_POST['announcement_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ? AND published_by = ?");
            $stmt->execute([$_POST['announcement_id'], $user_id]);
            $_SESSION['success_message'] = "Announcement deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error deleting announcement: " . $e->getMessage();
        }
        header("Location: announcements.php");
        exit;
    }
    
    if ($_POST['action'] === 'toggle' && isset($_POST['announcement_id'])) {
        try {
            $stmt = $pdo->prepare("UPDATE announcements SET status = CASE WHEN status = 'published' THEN 'archived' ELSE 'published' END, updated_at = NOW() WHERE announcement_id = ? AND published_by = ?");
            $stmt->execute([$_POST['announcement_id'], $user_id]);
            $_SESSION['success_message'] = "Announcement status updated!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating announcement: " . $e->getMessage();
        }
        header("Location: announcements.php");
        exit;
    }
}

// Fetch announcements created by this teacher
$my_announcements = [];
try {
    $stmt = $pdo->prepare("SELECT a.*, 
                          (SELECT COUNT(*) FROM users WHERE role = 'student') as student_count
                          FROM announcements a 
                          WHERE a.published_by = ?
                          ORDER BY a.created_at DESC");
    $stmt->execute([$user_id]);
    $my_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching announcements: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Teacher Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --teacher-primary: #10b981;
            --teacher-secondary: #059669;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #059669 0%, #047857 100%);
        }

        .announcement-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .create-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .create-section h2 {
            margin: 0 0 20px 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--teacher-primary);
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .announcements-list {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .announcements-list h2 {
            margin: 0 0 20px 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .announcement-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .announcement-card:hover {
            border-color: var(--teacher-primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .announcement-card.inactive {
            opacity: 0.6;
            background: #f7fafc;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .announcement-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 8px 0;
        }

        .announcement-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9em;
            color: #718096;
            margin-bottom: 15px;
        }

        .announcement-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-general {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-academic {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-event {
            background: #fef5e7;
            color: #d97706;
        }

        .badge-urgent {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-low {
            background: #f0fdf4;
            color: #166534;
        }

        .badge-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .announcement-content {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .announcement-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-toggle {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-toggle:hover {
            background: #fcd34d;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #fca5a5;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #cbd5e0;
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #a0aec0;
            margin: 0 0 10px 0;
        }

        @media (max-width: 768px) {
            .announcement-header {
                flex-direction: column;
            }
            
            .announcement-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="announcement-container">
            <!-- Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-bullhorn"></i>
                    Announcements
                </h1>
                <p>Create and manage announcements for your students</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Create Announcement Form -->
            <div class="create-section">
                <h2><i class="fas fa-plus-circle"></i> Create New Announcement</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required placeholder="Enter announcement title">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content *</label>
                        <textarea id="content" name="content" required placeholder="Enter announcement content"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="general">General</option>
                            <option value="academic">Academic</option>
                            <option value="event">Event</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priority *</label>
                        <select id="priority" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        Publish Announcement
                    </button>
                </form>
            </div>

            <!-- My Announcements List -->
            <div class="announcements-list">
                <h2><i class="fas fa-list"></i> My Announcements (<?php echo count($my_announcements); ?>)</h2>
                
                <?php if (empty($my_announcements)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <h3>No Announcements Yet</h3>
                        <p>Create your first announcement using the form above</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($my_announcements as $announcement): ?>
                        <div class="announcement-card <?php echo $announcement['status'] === 'published' ? '' : 'inactive'; ?>">
                            <div class="announcement-header">
                                <div>
                                    <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                    <div class="announcement-meta">
                                        <span>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($announcement['published_at'])); ?>
                                        </span>
                                        <span class="badge badge-<?php echo strtolower($announcement['category']); ?>">
                                            <i class="fas fa-tag"></i>
                                            <?php echo ucfirst($announcement['category']); ?>
                                        </span>
                                        <span class="badge badge-<?php echo strtolower($announcement['priority']); ?>">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?php echo ucfirst($announcement['priority']); ?>
                                        </span>
                                        <span class="badge <?php echo $announcement['status'] === 'published' ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo ucfirst($announcement['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="announcement-content">
                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                            </div>
                            
                            <div class="announcement-actions">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcement_id']; ?>">
                                    <button type="submit" class="btn-action btn-toggle">
                                        <i class="fas fa-toggle-<?php echo $announcement['status'] === 'published' ? 'on' : 'off'; ?>"></i>
                                        <?php echo $announcement['status'] === 'published' ? 'Archive' : 'Publish'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcement_id']; ?>">
                                    <button type="submit" class="btn-action btn-delete">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>
