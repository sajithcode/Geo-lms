<?php
$currentPage = 'admin_feedback';

require_once 'php/admin_session_check.php';
require_once '../config/database.php';

// Check if feedback table exists
$feedback_table_exists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'feedbacks'");
    $feedback_table_exists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    error_log("Error checking feedbacks table: " . $e->getMessage());
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Fetch feedback statistics
$stats = ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'resolved' => 0];
if ($feedback_table_exists) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM feedbacks");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // If status column exists
        try {
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM feedbacks GROUP BY status");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($stats[$row['status']])) {
                    $stats[$row['status']] = $row['count'];
                }
            }
        } catch (PDOException $e) {
            // Status column might not exist
        }
    } catch (PDOException $e) {
        error_log("Error fetching feedback stats: " . $e->getMessage());
    }
}

// Fetch feedbacks
$feedbacks = [];
if ($feedback_table_exists) {
    try {
        $sql = "SELECT f.*, u.username, u.full_name, u.email, u.role
                FROM feedbacks f
                LEFT JOIN users u ON f.user_id = u.user_id
                WHERE 1=1";

        $params = [];

        // Add status filter if applicable
        if ($filter_status !== 'all') {
            try {
                // Check if status column exists
                $pdo->query("SELECT status FROM feedbacks LIMIT 1");
                $sql .= " AND f.status = ?";
                $params[] = $filter_status;
            } catch (PDOException $e) {
                // Status column doesn't exist, ignore filter
            }
        }

        // Add search filter
        if (!empty($search_query)) {
            $sql .= " AND (f.message LIKE ? OR u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $search_param = '%' . $search_query . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        $sql .= " ORDER BY f.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching feedbacks: " . $e->getMessage());
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $feedback_id = $_POST['feedback_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? 'pending';

    try {
        $stmt = $pdo->prepare("UPDATE feedbacks SET status = ? WHERE feedback_id = ?");
        $stmt->execute([$new_status, $feedback_id]);
        $_SESSION['success_message'] = "Feedback status updated successfully!";
        header("Location: feedback.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating feedback status: " . $e->getMessage();
    }
}

// Handle feedback deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_feedback') {
    $feedback_id = $_POST['feedback_id'] ?? 0;

    try {
        $stmt = $pdo->prepare("DELETE FROM feedbacks WHERE feedback_id = ?");
        $stmt->execute([$feedback_id]);
        $_SESSION['success_message'] = "Feedback deleted successfully!";
        header("Location: feedback.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting feedback: " . $e->getMessage();
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Feedback Management - Admin Panel</title>
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
            --admin-success: #10b981;
            --admin-warning: #f59e0b;
            --admin-danger: #ef4444;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
        }

        .sidebar {
            background: #1c3d5a;
        }

        .feedback-container {
            max-width: 1400px;
            margin: 0 auto;
        }

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
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            margin: 0 auto 15px auto;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%);
            color: white;
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .stat-details h3 {
            margin: 0;
            font-size: 2em;
            color: #2d3748;
        }

        .stat-details p {
            margin: 5px 0 0 0;
            color: #718096;
            font-size: 0.9em;
        }

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .filters-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--admin-primary);
        }

        .btn-filter {
            padding: 10px 20px;
            background: var(--admin-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filter:hover {
            background: var(--admin-secondary);
        }

        .feedback-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .feedback-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-bottom: 2px solid #e2e8f0;
        }

        .feedback-header h2 {
            margin: 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feedback-content {
            padding: 0;
        }

        .feedback-item {
            padding: 25px;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.3s ease;
        }

        .feedback-item:hover {
            background: #f8fafc;
        }

        .feedback-item:last-child {
            border-bottom: none;
        }

        .feedback-top {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .feedback-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0a74da 0%, #1c3d5a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2em;
        }

        .user-info h3 {
            margin: 0 0 5px 0;
            color: #2d3748;
            font-size: 1.1em;
        }

        .user-info p {
            margin: 0;
            color: #718096;
            font-size: 0.9em;
        }

        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 8px;
        }

        .role-badge.admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .role-badge.teacher {
            background: #d1fae5;
            color: #065f46;
        }

        .role-badge.student {
            background: #e0e7ff;
            color: #3730a3;
        }

        .feedback-meta {
            text-align: right;
        }

        .feedback-date {
            color: #718096;
            font-size: 0.9em;
            margin-bottom: 8px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-reviewed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .feedback-message {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #4a5568;
            line-height: 1.6;
        }

        .feedback-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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

        .btn-primary {
            background: var(--admin-primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--admin-secondary);
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
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

        .warning-box {
            background: #fffaf0;
            border: 2px solid #ed8936;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
        }

        .warning-box i {
            font-size: 3em;
            color: #ed8936;
            margin-bottom: 15px;
        }

        .warning-box h3 {
            color: #7c2d12;
            margin: 0 0 10px 0;
        }

        .warning-box p {
            color: #92400e;
            margin: 0;
        }

        /* Status update dropdown */
        .status-update {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-update select {
            padding: 6px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.85em;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .feedback-top {
                flex-direction: column;
                gap: 15px;
            }

            .feedback-meta {
                text-align: left;
            }

            .filters-row {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .feedback-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="feedback-container">
            <!-- Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-comments"></i>
                    User Feedback Management
                </h1>
                <p>Review, manage, and respond to user feedback across the system</p>
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

            <?php if (!$feedback_table_exists): ?>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Feedback System Not Available</h3>
                    <p>The feedback table does not exist in the database. Please contact the system administrator.</p>
                </div>
            <?php else: ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Feedback</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['reviewed']; ?></h3>
                        <p>Reviewed</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['resolved']; ?></h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="status">Filter by Status</label>
                            <select name="status" id="status">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="reviewed" <?php echo $filter_status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="resolved" <?php echo $filter_status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search"
                                   placeholder="Search by message, name, or email..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>

                        <div class="filter-group">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Feedback List -->
            <div class="feedback-list">
                <div class="feedback-header">
                    <h2><i class="fas fa-list"></i> Feedback Messages (<?php echo count($feedbacks); ?>)</h2>
                </div>

                <div class="feedback-content">
                    <?php if (empty($feedbacks)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No Feedback Found</h3>
                            <p>No feedback messages match your current filters.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <div class="feedback-item">
                                <div class="feedback-top">
                                    <div class="feedback-user">
                                        <div class="user-avatar">
                                            <?php
                                            $name = $feedback['full_name'] ?? $feedback['username'] ?? 'Anonymous';
                                            echo strtoupper(substr($name, 0, 1));
                                            ?>
                                        </div>
                                        <div class="user-info">
                                            <h3>
                                                <?php echo htmlspecialchars($name); ?>
                                                <?php if ($feedback['role']): ?>
                                                    <span class="role-badge <?php echo $feedback['role']; ?>">
                                                        <?php echo $feedback['role']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </h3>
                                            <p>
                                                <?php if ($feedback['username']): ?>
                                                    @<?php echo htmlspecialchars($feedback['username']); ?>
                                                <?php endif; ?>
                                                <?php if ($feedback['email']): ?>
                                                    • <?php echo htmlspecialchars($feedback['email']); ?>
                                                <?php endif; ?>
                                                <?php if (!$feedback['user_id']): ?>
                                                    Anonymous User
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="feedback-meta">
                                        <div class="feedback-date">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?>
                                        </div>
                                        <?php if (isset($feedback['status'])): ?>
                                            <span class="status-badge status-<?php echo strtolower($feedback['status']); ?>">
                                                <?php echo ucfirst($feedback['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="feedback-message">
                                    <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                                </div>

                                <?php if (isset($feedback['status'])): ?>
                                <div class="feedback-actions">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                        <div class="status-update">
                                            <span>Update Status:</span>
                                            <select name="new_status" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $feedback['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="reviewed" <?php echo $feedback['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                <option value="resolved" <?php echo $feedback['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            </select>
                                        </div>
                                    </form>

                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                        <input type="hidden" name="action" value="delete_feedback">
                                        <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                        <button type="submit" class="btn-action btn-danger">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>