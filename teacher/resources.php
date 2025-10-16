<?php
$currentPage = 'teacher_resources';

// Include teacher session check
require_once 'php/teacher_session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

// Check for resource tables existence
$tables_check = ['notes', 'ebooks', 'pastpapers'];
$existing_tables = [];
foreach ($tables_check as $table) {
    $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
    if (count($result) > 0) {
        $existing_tables[] = $table;
    }
}

$has_resource_tables = in_array('notes', $existing_tables) && 
                       in_array('ebooks', $existing_tables) && 
                       in_array('pastpapers', $existing_tables);

// Get predefined categories (since resource_categories table doesn't exist)
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

// Check for session messages
$upload_error = '';
if (isset($_SESSION['upload_error'])) {
    $upload_error = $_SESSION['upload_error'];
    unset($_SESSION['upload_error']);
}

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource'])) {
    csrf_validate_or_redirect('resources.php');
    
    $resource_type = $_POST['resource_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $semester = trim($_POST['semester'] ?? '');
    
    if (!in_array($resource_type, ['note', 'ebook', 'pastpaper'])) {
        $upload_error = "Invalid resource type.";
    } elseif (empty($title)) {
        $upload_error = "Title is required.";
    } elseif (!isset($_FILES['resource_file']) || $_FILES['resource_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_error = "Please upload a file.";
    } else {
        $file = $_FILES['resource_file'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $upload_error = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
        }
        // Validate file size (max 50MB)
        elseif ($file_size > 50 * 1024 * 1024) {
            $upload_error = "File is too large. Maximum size is 50MB.";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = '../uploads/' . $resource_type . 's/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $unique_filename = uniqid() . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Insert into database
                try {
                    $user_id = $_SESSION['id'];
                    $file_path = 'uploads/' . $resource_type . 's/' . $unique_filename;
                    
                    if ($resource_type === 'note') {
                        $sql = "INSERT INTO notes (title, description, filename, filepath, filesize, file_type, category, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$title, $description, $file_name, $file_path, $file_size, $file_ext, $category, $user_id]);
                    } elseif ($resource_type === 'ebook') {
                        $sql = "INSERT INTO ebooks (title, author, description, filename, filepath, filesize, file_type, category, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$title, $author, $description, $file_name, $file_path, $file_size, $file_ext, $category, $user_id]);
                    } elseif ($resource_type === 'pastpaper') {
                        $sql = "INSERT INTO pastpapers (title, year, semester, description, filename, filepath, filesize, file_type, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$title, $year, $semester, $description, $file_name, $file_path, $file_size, $file_ext, $user_id]);
                    }
                    
                    $_SESSION['success_message'] = "Resource uploaded successfully!";
                    // Redirect to prevent form resubmission on refresh
                    header('Location: resources.php');
                    exit;
                } catch (PDOException $e) {
                    $upload_error = "Database error: " . $e->getMessage();
                    unlink($upload_path); // Delete uploaded file if database insert fails
                }
            } else {
                $upload_error = "Failed to upload file.";
            }
        }
    }
    
    // If there's an error, store it in session and redirect
    if (!empty($upload_error)) {
        $_SESSION['upload_error'] = $upload_error;
        header('Location: resources.php');
        exit;
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    csrf_validate_or_redirect('resources.php');
    
    $resource_type = $_POST['resource_type'] ?? '';
    $resource_id = filter_input(INPUT_POST, 'resource_id', FILTER_VALIDATE_INT);
    
    if ($resource_id && in_array($resource_type, ['note', 'ebook', 'pastpaper'])) {
        try {
            // Get file path before deleting
            $table = $resource_type . 's';
            $id_field = 'id';
            
            $stmt = $pdo->prepare("SELECT filepath FROM $table WHERE $id_field = ?");
            $stmt->execute([$resource_id]);
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resource) {
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
                $stmt->execute([$resource_id]);
                
                // Delete file
                $file_path = '../' . $resource['filepath'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                $_SESSION['success_message'] = "Resource deleted successfully!";
            }
        } catch (PDOException $e) {
            $_SESSION['upload_error'] = "Error deleting resource: " . $e->getMessage();
        }
    }
    
    // Redirect after delete to prevent resubmission
    header('Location: resources.php');
    exit;
}

// Fetch all resources
$notes = [];
$ebooks = [];
$pastpapers = [];

if ($has_resource_tables) {
    try {
        $stmt = $pdo->query("SELECT n.*, u.username 
                             FROM notes n 
                             LEFT JOIN users u ON n.uploaded_by = u.user_id 
                             ORDER BY n.created_at DESC");
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT e.*, u.username 
                             FROM ebooks e 
                             LEFT JOIN users u ON e.uploaded_by = u.user_id 
                             ORDER BY e.created_at DESC");
        $ebooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT p.*, u.username 
                             FROM pastpapers p 
                             LEFT JOIN users u ON p.uploaded_by = u.user_id 
                             ORDER BY p.year DESC, p.created_at DESC");
        $pastpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $upload_error = "Error fetching resources: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Resources - Teacher Portal</title>
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
            background: #f4f7fc;
            font-family: 'Poppins', sans-serif;
        }
        
        /* Override sidebar colors for teacher theme */
        .sidebar {
            background: linear-gradient(180deg, #059669 0%, #047857 100%);
        }
        
        .sidebar-nav li.active a,
        .sidebar-nav li a:hover {
            background-color: var(--teacher-primary);
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
            margin-bottom: 30px;
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
            border-left: 4px solid var(--teacher-primary);
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .warning-box {
            background: #fffbeb;
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
        }
        
        .warning-box i {
            font-size: 3em;
            color: #f59e0b;
            margin-bottom: 15px;
        }
        
        .warning-box h3 {
            color: #92400e;
            margin: 0 0 10px;
        }
        
        .warning-box code {
            background: #fef3c7;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .upload-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .upload-section h2 {
            color: #1c3d5a;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #4a5568;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--teacher-primary);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary {
            background: var(--teacher-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--teacher-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            font-size: 0.9em;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #718096;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .tab.active {
            color: var(--teacher-primary);
            border-bottom-color: var(--teacher-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .resource-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f7fafc;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 2px solid #e2e8f0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }
        
        tr:hover {
            background: #f7fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-category {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .file-size {
            color: #718096;
            font-size: 0.9em;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #cbd5e0;
        }
        
        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Learning Resources Management</h1>
            <p>Upload and manage notes, e-books, and past papers for your students</p>
        </header>

        <?php if (!$has_resource_tables): ?>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Resource Tables Not Found</h3>
            <p>Please run the database migration file to create the required tables.</p>
            <p style="margin-top: 15px; color: #6b7280;">
                Run the SQL file: <code>database/learning_resources.sql</code>
            </p>
        </div>
        <?php else: ?>

        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($upload_error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($upload_error); ?>
        </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-section">
            <h2><i class="fas fa-cloud-upload-alt"></i> Upload New Resource</h2>
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="upload_resource" value="1">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="resource_type">Resource Type *</label>
                        <select name="resource_type" id="resource_type" required onchange="toggleFields()">
                            <option value="">Select Type</option>
                            <option value="note">Note</option>
                            <option value="ebook">E-book</option>
                            <option value="pastpaper">Past Paper</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" name="title" id="title" required placeholder="Enter resource title">
                    </div>

                    <div class="form-group" id="author_field" style="display:none;">
                        <label for="author">Author</label>
                        <input type="text" name="author" id="author" placeholder="Book author name">
                    </div>

                    <div class="form-group" id="year_field" style="display:none;">
                        <label for="year">Year</label>
                        <input type="number" name="year" id="year" min="2000" max="2100" placeholder="2024">
                    </div>

                    <div class="form-group" id="semester_field" style="display:none;">
                        <label for="semester">Semester</label>
                        <input type="text" name="semester" id="semester" placeholder="e.g., Fall, Spring, Semester 1">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="resource_file">File * (PDF, DOC, DOCX, PPT, PPTX, TXT, ZIP - Max 50MB)</label>
                        <input type="file" name="resource_file" id="resource_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3" placeholder="Brief description of the resource"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Resource
                </button>
            </form>
        </div>

        <!-- Resource Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('notes')">
                <i class="fas fa-sticky-note"></i> Notes (<?php echo count($notes); ?>)
            </button>
            <button class="tab" onclick="switchTab('ebooks')">
                <i class="fas fa-book"></i> E-books (<?php echo count($ebooks); ?>)
            </button>
            <button class="tab" onclick="switchTab('pastpapers')">
                <i class="fas fa-file-alt"></i> Past Papers (<?php echo count($pastpapers); ?>)
            </button>
        </div>

        <!-- Notes Table -->
        <div id="notes" class="tab-content active">
            <div class="resource-table">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Downloads</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notes)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-inbox"></i><br>
                                No notes uploaded yet.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($notes as $note): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($note['title']); ?></strong></td>
                            <td>
                                <?php if (!empty($note['category'])): ?>
                                    <span class="badge badge-category"><?php echo htmlspecialchars($note['category']); ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e0;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="file-size"><?php echo number_format($note['filesize'] / 1024 / 1024, 2); ?> MB</td>
                            <td><?php echo $note['downloads']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($note['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this resource?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="delete_resource" value="1">
                                    <input type="hidden" name="resource_type" value="note">
                                    <input type="hidden" name="resource_id" value="<?php echo $note['id']; ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- E-books Table -->
        <div id="ebooks" class="tab-content">
            <div class="resource-table">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Downloads</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ebooks)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-inbox"></i><br>
                                No e-books uploaded yet.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($ebooks as $ebook): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ebook['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ebook['author'] ?? '-'); ?></td>
                            <td>
                                <?php if (!empty($ebook['category'])): ?>
                                    <span class="badge badge-category"><?php echo htmlspecialchars($ebook['category']); ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e0;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="file-size"><?php echo number_format($ebook['filesize'] / 1024 / 1024, 2); ?> MB</td>
                            <td><?php echo $ebook['downloads']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($ebook['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this resource?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="delete_resource" value="1">
                                    <input type="hidden" name="resource_type" value="ebook">
                                    <input type="hidden" name="resource_id" value="<?php echo $ebook['id']; ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Past Papers Table -->
        <div id="pastpapers" class="tab-content">
            <div class="resource-table">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Downloads</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pastpapers)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="fas fa-inbox"></i><br>
                                No past papers uploaded yet.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($pastpapers as $paper): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($paper['title']); ?></strong></td>
                            <td><?php echo $paper['year'] ?? '-'; ?></td>
                            <td><?php echo htmlspecialchars($paper['semester'] ?? '-'); ?></td>
                            <td>
                                <?php if (!empty($paper['subject'])): ?>
                                    <span class="badge badge-category"><?php echo htmlspecialchars($paper['subject']); ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e0;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="file-size"><?php echo number_format($paper['filesize'] / 1024 / 1024, 2); ?> MB</td>
                            <td><?php echo $paper['downloads']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($paper['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this resource?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="delete_resource" value="1">
                                    <input type="hidden" name="resource_type" value="pastpaper">
                                    <input type="hidden" name="resource_id" value="<?php echo $paper['id']; ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>
    </main>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.closest('.tab').classList.add('active');
}

function toggleFields() {
    const resourceType = document.getElementById('resource_type').value;
    const authorField = document.getElementById('author_field');
    const yearField = document.getElementById('year_field');
    const semesterField = document.getElementById('semester_field');
    const authorInput = document.getElementById('author');
    const yearInput = document.getElementById('year');
    const semesterInput = document.getElementById('semester');
    
    // Hide all optional fields
    authorField.style.display = 'none';
    yearField.style.display = 'none';
    semesterField.style.display = 'none';
    authorInput.removeAttribute('required');
    yearInput.removeAttribute('required');
    semesterInput.removeAttribute('required');
    
    // Show fields based on type
    if (resourceType === 'ebook') {
        authorField.style.display = 'block';
    } else if (resourceType === 'pastpaper') {
        yearField.style.display = 'block';
        semesterField.style.display = 'block';
    }
}
</script>

</body>
</html>
