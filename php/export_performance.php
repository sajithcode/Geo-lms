<?php
// php/export_performance.php

require_once 'session_check.php';
require_once '../config/database.php';

$user_id = $_SESSION['id'];
$format = filter_input(INPUT_GET, 'format', FILTER_SANITIZE_STRING);

if (!in_array($format, ['csv', 'pdf'])) {
    header('Location: ../pages/performance.php');
    exit;
}

// Detect timestamp column
$possible_ts = ['created_at', 'attempted_at', 'started_at', 'timestamp'];
$ts_column = null;
foreach ($possible_ts as $col) {
    $check = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE '" . $col . "'")->fetchAll();
    if (count($check) > 0) {
        $ts_column = $col;
        break;
    }
}

$created_at_select = $ts_column ? "qa." . $ts_column . " AS created_at" : "NOW() AS created_at";

// Check if total_questions exists
$qa_total_col = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'total_questions'")->fetchAll();
$has_total_questions = count($qa_total_col) > 0;

$total_questions_select = $has_total_questions
    ? "qa.total_questions"
    : "(SELECT COUNT(*) FROM questions WHERE quiz_id = qa.quiz_id) AS total_questions";

// Check if correct_answers exists
$qa_correct_col = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'correct_answers'")->fetchAll();
$has_correct_answers = count($qa_correct_col) > 0;

// Fetch all quiz attempts for export
if ($has_correct_answers) {
    $sql = "SELECT 
            q.title as quiz_title,
            qa.score,
            qa.correct_answers,
            {$total_questions_select},
            {$created_at_select}
            FROM quiz_attempts qa
            LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
            WHERE qa.user_id = ?
            ORDER BY " . ($ts_column ? "qa." . $ts_column : "qa.quiz_id") . " DESC";
} else {
    $sql = "SELECT 
            q.title as quiz_title,
            qa.score,
            {$total_questions_select},
            {$created_at_select}
            FROM quiz_attempts qa
            LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
            WHERE qa.user_id = ?
            ORDER BY " . ($ts_column ? "qa." . $ts_column : "qa.quiz_id") . " DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user information
$stmt = $pdo->prepare("SELECT username, full_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_attempts,
    AVG(score) as avg_score,
    MAX(score) as best_score,
    MIN(score) as lowest_score
    FROM quiz_attempts WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=performance_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add header information
    fputcsv($output, ['Performance Report']);
    fputcsv($output, ['Generated:', date('F j, Y g:i A')]);
    fputcsv($output, ['Student:', $user['full_name'] ?? $user['username']]);
    fputcsv($output, ['Username:', $user['username']]);
    fputcsv($output, ['Email:', $user['email']]);
    fputcsv($output, []);
    
    // Add statistics
    fputcsv($output, ['Statistics']);
    fputcsv($output, ['Total Attempts:', $stats['total_attempts']]);
    fputcsv($output, ['Average Score:', round($stats['avg_score'], 1) . '%']);
    fputcsv($output, ['Best Score:', $stats['best_score'] . '%']);
    fputcsv($output, ['Lowest Score:', $stats['lowest_score'] . '%']);
    fputcsv($output, []);
    
    // Add table headers
    if ($has_correct_answers) {
        fputcsv($output, ['Quiz Title', 'Score (%)', 'Correct Answers', 'Total Questions', 'Date', 'Status']);
    } else {
        fputcsv($output, ['Quiz Title', 'Score (%)', 'Total Questions', 'Date', 'Status']);
    }
    
    // Add data rows
    foreach ($attempts as $attempt) {
        $status = $attempt['score'] >= 70 ? 'Passed' : 'Failed';
        $date = $attempt['created_at'] ? date('M j, Y', strtotime($attempt['created_at'])) : 'N/A';
        
        if ($has_correct_answers) {
            fputcsv($output, [
                $attempt['quiz_title'] ?? 'Untitled Quiz',
                $attempt['score'],
                $attempt['correct_answers'],
                $attempt['total_questions'],
                $date,
                $status
            ]);
        } else {
            fputcsv($output, [
                $attempt['quiz_title'] ?? 'Untitled Quiz',
                $attempt['score'],
                $attempt['total_questions'],
                $date,
                $status
            ]);
        }
    }
    
    fclose($output);
    exit;
    
} elseif ($format === 'pdf') {
    // PDF Export - Simple HTML to PDF conversion
    // For production, consider using libraries like TCPDF or mPDF
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=performance_report_' . date('Y-m-d') . '.pdf');
    
    // For now, we'll create a simple HTML page that can be printed to PDF
    // In production, integrate a proper PDF library
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Performance Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 40px;
                background: white;
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #667eea;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #667eea;
                margin: 0;
            }
            .info-section {
                margin-bottom: 30px;
                background: #f7fafc;
                padding: 20px;
                border-radius: 8px;
            }
            .info-section h2 {
                color: #2d3748;
                margin-top: 0;
            }
            .info-row {
                display: flex;
                padding: 8px 0;
                border-bottom: 1px solid #e2e8f0;
            }
            .info-label {
                font-weight: bold;
                width: 150px;
                color: #4a5568;
            }
            .info-value {
                color: #2d3748;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th {
                background: #667eea;
                color: white;
                padding: 12px;
                text-align: left;
                font-weight: bold;
            }
            td {
                padding: 10px 12px;
                border-bottom: 1px solid #e2e8f0;
            }
            tr:hover {
                background: #f7fafc;
            }
            .status-passed {
                color: #38a169;
                font-weight: bold;
            }
            .status-failed {
                color: #e53e3e;
                font-weight: bold;
            }
            .footer {
                margin-top: 40px;
                text-align: center;
                color: #718096;
                font-size: 12px;
                border-top: 1px solid #e2e8f0;
                padding-top: 20px;
            }
            @media print {
                body {
                    padding: 20px;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Performance Report</h1>
            <p>Geomatics Learning Management System</p>
            <p style="color: #718096;">Generated on <?php echo date('F j, Y g:i A'); ?></p>
        </div>
        
        <div class="info-section">
            <h2>Student Information</h2>
            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Username:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
        </div>
        
        <div class="info-section">
            <h2>Performance Statistics</h2>
            <div class="info-row">
                <div class="info-label">Total Attempts:</div>
                <div class="info-value"><?php echo $stats['total_attempts']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Average Score:</div>
                <div class="info-value"><?php echo round($stats['avg_score'], 1); ?>%</div>
            </div>
            <div class="info-row">
                <div class="info-label">Best Score:</div>
                <div class="info-value"><?php echo $stats['best_score']; ?>%</div>
            </div>
            <div class="info-row">
                <div class="info-label">Lowest Score:</div>
                <div class="info-value"><?php echo $stats['lowest_score']; ?>%</div>
            </div>
        </div>
        
        <h2>Quiz History</h2>
        <table>
            <thead>
                <tr>
                    <th>Quiz Title</th>
                    <th>Score</th>
                    <?php if ($has_correct_answers): ?>
                    <th>Correct Answers</th>
                    <?php endif; ?>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attempts as $attempt): 
                    $status = $attempt['score'] >= 70 ? 'Passed' : 'Failed';
                    $status_class = $attempt['score'] >= 70 ? 'status-passed' : 'status-failed';
                    $date = $attempt['created_at'] ? date('M j, Y', strtotime($attempt['created_at'])) : 'N/A';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($attempt['quiz_title'] ?? 'Untitled Quiz'); ?></td>
                    <td><strong><?php echo $attempt['score']; ?>%</strong></td>
                    <?php if ($has_correct_answers): ?>
                    <td><?php echo $attempt['correct_answers']; ?> / <?php echo $attempt['total_questions']; ?></td>
                    <?php endif; ?>
                    <td><?php echo $date; ?></td>
                    <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>This is an official performance report from Geo-LMS</p>
            <p>&copy; <?php echo date('Y'); ?> Geomatics Learning Management System. All rights reserved.</p>
        </div>
        
        <script class="no-print">
            // Auto-trigger print dialog for PDF generation
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>
