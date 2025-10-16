<?php
$currentPage = 'teacher_dashboard';

// Include teacher session check
require_once 'php/teacher_session_check.php';
require_once '../config/database.php';

// Get teacher statistics
try {
    $teacher_id = $_SESSION['id'];
    
    // Total students (you can customize this based on your system)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $total_students = $stmt->fetch()['total'];
    
    // Total quizzes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
    $total_quizzes = $stmt->fetch();
    $total_quizzes = $total_quizzes ? $total_quizzes['total'] : 0;
    
    // Total quiz attempts
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quiz_attempts");
    $total_attempts = $stmt->fetch();
    $total_attempts = $total_attempts ? $total_attempts['total'] : 0;
    
    // Total resources - count from all resource tables
    $total_resources = 0;
    try {
        $stmt = $pdo->query("SELECT 
            (SELECT COUNT(*) FROM notes) + 
            (SELECT COUNT(*) FROM ebooks) + 
            (SELECT COUNT(*) FROM pastpapers) as total");
        $result = $stmt->fetch();
        $total_resources = $result ? $result['total'] : 0;
    } catch (PDOException $e) {
        error_log("Error fetching total resources: " . $e->getMessage());
    }
    
    // Recent quiz attempts
    $stmt = $pdo->query("
        SELECT qa.attempt_id, qa.score, qa.completed_at, u.username, q.title as quiz_title
        FROM quiz_attempts qa
        LEFT JOIN users u ON qa.user_id = u.user_id
        LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
        ORDER BY qa.completed_at DESC
        LIMIT 5
    ");
    $recent_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Geo-LMS</title>
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
            --teacher-accent: #34d399;
            --teacher-warning: #f59e0b;
            --teacher-danger: #ef4444;
            --teacher-info: #3b82f6;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fc;
            margin: 0;
            padding: 0;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--teacher-primary);
            transition: all 0.3s;
        }
        
        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }
        
        .stat-box.success { border-left-color: var(--teacher-primary); }
        .stat-box.warning { border-left-color: var(--teacher-warning); }
        .stat-box.info { border-left-color: var(--teacher-info); }
        .stat-box.danger { border-left-color: var(--teacher-danger); }
        
        .stat-box h3 {
            margin: 0 0 10px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-box .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1c3d5a;
            margin-bottom: 4px;
        }
        
        .stat-box .stat-label {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .teacher-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }
        
        .teacher-section h2 {
            margin: 0 0 20px;
            font-size: 1.3rem;
            color: #1c3d5a;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .teacher-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .teacher-table th {
            text-align: left;
            padding: 14px;
            background: #f9fafb;
            font-weight: 600;
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .teacher-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        
        .teacher-table tr:hover {
            background: #f9fafb;
        }
        
        .score-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .score-badge.excellent {
            background: #d1fae5;
            color: #065f46;
        }
        
        .score-badge.good {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .score-badge.average {
            background: #fef3c7;
            color: #92400e;
        }
        
        .score-badge.poor {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--teacher-primary);
        }
        
        .action-card i {
            font-size: 3rem;
            color: var(--teacher-primary);
            margin-bottom: 15px;
        }
        
        .action-card h3 {
            margin: 0 0 8px;
            font-size: 1.1rem;
            color: #1c3d5a;
            font-weight: 600;
        }
        
        .action-card p {
            margin: 0;
            color: #777;
            font-size: 0.9rem;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
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
            <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>Here's an overview of your teaching tools and student progress.</p>
        </header>

    <div class="stats-grid">
        <div class="stat-box success">
            <h3><i class="fa-solid fa-user-graduate"></i> Total Students</h3>
            <div class="stat-value"><?php echo number_format($total_students); ?></div>
            <div class="stat-label">Active Learners</div>
        </div>
        
        <div class="stat-box info">
            <h3><i class="fa-solid fa-puzzle-piece"></i> Total Quizzes</h3>
            <div class="stat-value"><?php echo number_format($total_quizzes); ?></div>
            <div class="stat-label">Assessments Created</div>
        </div>
        
        <div class="stat-box warning">
            <h3><i class="fa-solid fa-clipboard-check"></i> Quiz Attempts</h3>
            <div class="stat-value"><?php echo number_format($total_attempts); ?></div>
            <div class="stat-label">Total Submissions</div>
        </div>
        
        <div class="stat-box danger">
            <h3><i class="fa-solid fa-book-open"></i> Learning Resources</h3>
            <div class="stat-value"><?php echo number_format($total_resources); ?></div>
            <div class="stat-label">Materials Uploaded</div>
        </div>
    </div>

    <div class="quick-actions">
        <div class="action-card" onclick="location.href='quizzes.php'">
            <i class="fa-solid fa-plus-circle"></i>
            <h3>Create Quiz</h3>
            <p>Design new assessments for your students</p>
        </div>
        
        <div class="action-card" onclick="location.href='resources.php'">
            <i class="fa-solid fa-upload"></i>
            <h3>Upload Resources</h3>
            <p>Share notes, e-books, and materials</p>
        </div>
        
        <div class="action-card" onclick="location.href='students.php'">
            <i class="fa-solid fa-chart-line"></i>
            <h3>Track Progress</h3>
            <p>Monitor student performance and grades</p>
        </div>
        
        <div class="action-card" onclick="location.href='../pages/messages.php?tab=compose'">
            <i class="fa-solid fa-pen"></i>
            <h3>Compose Message</h3>
            <p>Send a message to students or colleagues</p>
        </div>
        
        <div class="action-card" onclick="location.href='announcements.php'">
            <i class="fa-solid fa-bullhorn"></i>
            <h3>Create Announcement</h3>
            <p>Broadcast a message to students</p>
        </div>
        
        <div class="action-card" onclick="location.href='announcements.php'">
            <i class="fa-solid fa-list"></i>
            <h3>View All Announcements</h3>
            <p>See all announcements and updates</p>Perfect! The file exists but is empty. Let me check the student feedback page to understand the structure, and then create a proper teacher feedback view:
        </div>
        
        <div class="action-card" onclick="location.href='feedback.php'">
            <i class="fa-solid fa-comment-dots"></i>
            <h3>Student Feedback</h3>
            <p>Review feedback from students</p>
        </div>
    </div>

    <div class="teacher-section">
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Recent Quiz Attempts</h2>
        <?php if (!empty($recent_attempts)): ?>
            <table class="teacher-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_attempts as $attempt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($attempt['username'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($attempt['quiz_title'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $score = $attempt['score'];
                                $badge_class = 'poor';
                                if ($score >= 90) $badge_class = 'excellent';
                                elseif ($score >= 75) $badge_class = 'good';
                                elseif ($score >= 60) $badge_class = 'average';
                                ?>
                                <span class="score-badge <?php echo $badge_class; ?>"><?php echo number_format($score, 1); ?>%</span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($attempt['completed_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #6b7280; padding: 20px;">No quiz attempts yet</p>
        <?php endif; ?>
    </div>

    <div class="teacher-section">
        <h2><i class="fa-solid fa-lightbulb"></i> Quick Tips</h2>
        <div style="display: grid; gap: 16px;">
            <div style="padding: 16px; background: #f0fdf4; border-left: 4px solid var(--teacher-primary); border-radius: 8px;">
                <strong style="color: var(--teacher-secondary);">💡 Create Engaging Quizzes</strong>
                <p style="margin: 8px 0 0; color: #374151;">Mix different question types and difficulty levels to keep students challenged and motivated.</p>
            </div>
            <div style="padding: 16px; background: #eff6ff; border-left: 4px solid var(--teacher-info); border-radius: 8px;">
                <strong style="color: #1e40af;">📊 Track Performance</strong>
                <p style="margin: 8px 0 0; color: #374151;">Regularly review student scores to identify areas where additional support may be needed.</p>
            </div>
            <div style="padding: 16px; background: #fffbeb; border-left: 4px solid var(--teacher-warning); border-radius: 8px;">
                <strong style="color: #92400e;">📚 Share Resources</strong>
                <p style="margin: 8px 0 0; color: #374151;">Upload study materials, notes, and past papers to help students prepare effectively.</p>
            </div>
        </div>
    </div>
    </main>
</div>

</body>
</html>
