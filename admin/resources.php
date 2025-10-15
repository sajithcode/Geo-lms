<?php
require_once 'php/admin_session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

$currentPage = 'resources';

// Check for resource tables existence
$tables_check = ['notes', 'ebooks', 'pastpapers', 'resource_categories'];
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

// Get categories
$categories = [];
if (in_array('resource_categories', $existing_tables)) {
    $stmt = $pdo->query("SELECT * FROM resource_categories ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle file upload
$upload_success = false;
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource'])) {
    csrf_validate_or_redirect('resources.php');
    
    $resource_type = $_POST['resource_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $author = trim($_POST['author'] ?? ''); // For ebooks
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT); // For pastpapers
    $semester = trim($_POST['semester'] ?? ''); // For pastpapers
    
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
                    $user_id = $_SESSION['user_id'];
                    $file_path = 'uploads/' . $resource_type . 's/' . $unique_filename;
                    
                    if ($resource_type === 'note') {
                        $sql = "INSERT INTO notes (title, description, file_path, file_size, category_id, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$title, $description, $file_path, $file_size, $category_id, $user_id]);
                    } elseif ($resource_type === 'ebook') {
                        $sql = "INSERT INTO ebooks (title, author, description, file_path, file_size, category_id, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$title, $author, $description, $file_path, $file_size, $category_id, $user_id]);
                    } elseif ($resource_type === 'pastpaper') {
                        $sql = "INSERT INTO pastpapers (title, year, semester, description, file_path, file_size, category_id, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$title, $year, $semester, $description, $file_path, $file_size, $category_id, $user_id]);
                    }
                    
                    $upload_success = true;
                } catch (PDOException $e) {
                    $upload_error = "Database error: " . $e->getMessage();
                    unlink($upload_path); // Delete uploaded file if database insert fails
                }
            } else {
                $upload_error = "Failed to upload file.";
            }
        }
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
            $id_field = $resource_type . '_id';
            
            $stmt = $pdo->prepare("SELECT file_path FROM $table WHERE $id_field = ?");
            $stmt->execute([$resource_id]);
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resource) {
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_field = ?");
                $stmt->execute([$resource_id]);
                
                // Delete file
                $file_path = '../' . $resource['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                $upload_success = true;
            }
        } catch (PDOException $e) {
            $upload_error = "Error deleting resource: " . $e->getMessage();
        }
    }
}

// Fetch all resources
$notes = [];
$ebooks = [];
$pastpapers = [];

if ($has_resource_tables) {
    try {
        $stmt = $pdo->query("SELECT n.*, rc.category_name, u.username 
                             FROM notes n 
                             LEFT JOIN resource_categories rc ON n.category_id = rc.category_id 
                             LEFT JOIN users u ON n.uploaded_by = u.user_id 
                             ORDER BY n.created_at DESC");
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT e.*, rc.category_name, u.username 
                             FROM ebooks e 
                             LEFT JOIN resource_categories rc ON e.category_id = rc.category_id 
                             LEFT JOIN users u ON e.uploaded_by = u.user_id 
                             ORDER BY e.created_at DESC");
        $ebooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT p.*, rc.category_name, u.username 
                             FROM pastpapers p 
                             LEFT JOIN resource_categories rc ON p.category_id = rc.category_id 
                             LEFT JOIN users u ON p.uploaded_by = u.user_id 
                             ORDER BY p.year DESC, p.created_at DESC");
        $pastpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $upload_error = "Error fetching resources: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>
<script>document.title = 'Resource Management - Admin Panel';</script>

<style>
.admin-container {
    display: flex;
    min-height: 100vh;
    background: #f7fafc;
}

.admin-sidebar {
    width: 250px;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
}

.admin-sidebar h2 {
    margin: 0 0 30px 0;
    font-size: 1.5em;
}

.admin-nav {
    list-style: none;
    padding: 0;
}

.admin-nav li {
    margin-bottom: 10px;
}

.admin-nav a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.admin-nav a:hover,
.admin-nav a.active {
    background: rgba(255, 255, 255, 0.2);
}

.admin-content {
    margin-left: 250px;
    flex: 1;
    padding: 30px;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2d3748;
    margin: 0 0 10px 0;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #c6f6d5;
    color: #22543d;
    border-left: 4px solid #38a169;
}

.alert-error {
    background: #fed7d7;
    color: #742a2a;
    border-left: 4px solid #e53e3e;
}

.upload-section {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.upload-section h2 {
    color: #2d3748;
    margin: 0 0 20px 0;
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
    padding: 10px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 1em;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
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

.btn-danger {
    background: #e53e3e;
    color: white;
    padding: 8px 16px;
    font-size: 0.9em;
}

.btn-danger:hover {
    background: #c53030;
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
}

.tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.resource-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
    background: #e6f2ff;
    color: #0066cc;
}

.file-size {
    color: #718096;
    font-size: 0.9em;
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

<div class="admin-container">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
        <ul class="admin-nav">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a></li>
            <li><a href="resources.php" class="active"><i class="fas fa-book"></i> Resources</a></li>
            <li><a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
            <li><a href="../pages/dashboard.php"><i class="fas fa-home"></i> User View</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="page-header">
            <h1><i class="fas fa-book"></i> Resource Management</h1>
            <p>Upload and manage learning resources (Notes, E-books, Past Papers)</p>
        </div>

        <?php if (!$has_resource_tables): ?>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Resource Tables Not Found</h3>
            <p>Please run the database migration <code>002_quiz_enhancements.sql</code> to create the required tables.</p>
            <p style="margin-top: 15px;"><a href="../docs/run_migration.php" class="btn btn-primary">Run Migration</a></p>
        </div>
        <?php else: ?>

        <?php if ($upload_success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Operation completed successfully!
        </div>
        <?php endif; ?>

        <?php if ($upload_error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($upload_error); ?>
        </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-section">
            <h2><i class="fas fa-upload"></i> Upload New Resource</h2>
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
                        <input type="text" name="title" id="title" required>
                    </div>

                    <div class="form-group" id="author_field" style="display:none;">
                        <label for="author">Author</label>
                        <input type="text" name="author" id="author">
                    </div>

                    <div class="form-group" id="year_field" style="display:none;">
                        <label for="year">Year</label>
                        <input type="number" name="year" id="year" min="2000" max="2100">
                    </div>

                    <div class="form-group" id="semester_field" style="display:none;">
                        <label for="semester">Semester</label>
                        <input type="text" name="semester" id="semester" placeholder="e.g., Fall, Spring, Semester 1">
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
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
                    <textarea name="description" id="description" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Resource
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
                            <th>Uploaded By</th>
                            <th>Size</th>
                            <th>Downloads</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notes)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding: 40px; color: #cbd5e0;">
                                <i class="fas fa-inbox" style="font-size: 2em;"></i><br>
                                No notes uploaded yet.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($notes as $note): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($note['title']); ?></strong></td>
                            <td>
                                <?php if ($note['category_name']): ?>
                                    <span class="badge badge-category"><?php echo htmlspecialchars($note['category_name']); ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e0;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($note['username']); ?></td>
                            <td class="file-size"><?php echo number_format($note['file_size'] / 1024 / 1024, 2); ?> MB</td>
                            <td><?php echo $note['download_count']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($note['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this resource?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="delete_resource" value="1">
                                    <input type="hidden" name="resource_type" value="note">
                                    <input type="hidden" name="resource_id" value="<?php echo $note['note_id']; ?>">
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
                            <th>Uploaded By</th>
                            <th>Size</th>
                            <th>Downloads</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ebooks)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 40px; color: #cbd5e0;">
                                <i class="fas fa-inbox" style="font-size: 2em;"></i><br>
                                No e-books uploaded yet.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($ebooks as $ebook): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ebook['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ebook['author'] ?? '-'); ?></td>
                            <td>
                                <?php if ($ebook['category_name']): ?>
                                    <span class="badge badge-category"><?php echo htmlspecialchars($ebook['category_name']); ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e0;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($ebook['username']); ?></td>
                            <td class="file-size"><?php echo number_format($ebook['file_size'] / 1024 / 1024, 2); ?> MB</td>
                            <td><?php echo $ebook['download_count']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($ebook['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this resource?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="delete_resource" value="1">
                                    <input type="hidden" name="resource_type" value="ebook">
                                    <input type="hidden" name="resource_id" value="<?php echo $ebook['ebook_id']; ?>">
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
                            <th>Uploaded By</th>
                            <th>Size</th>
                            <th>Downloads</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pastpapers)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding: 40px; color: #cbd5e0;">
                                <i class="fas fa-inbox" style="font-size: 2em;"></i><br>
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
                                <?php if ($paper['category_name']): ?>
                                    <span class="badge badge-category"><?php echo htmlspecialchars($paper['category_name']); ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e0;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($paper['username']); ?></td>
                            <td class="file-size"><?php echo number_format($paper['file_size'] / 1024 / 1024, 2); ?> MB</td>
                            <td><?php echo $paper['download_count']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($paper['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this resource?');">
                                    <?php echo csrf_token_field(); ?>
                                    <input type="hidden" name="delete_resource" value="1">
                                    <input type="hidden" name="resource_type" value="pastpaper">
                                    <input type="hidden" name="resource_id" value="<?php echo $paper['paper_id']; ?>">
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

<?php include '../includes/footer.php'; ?>
