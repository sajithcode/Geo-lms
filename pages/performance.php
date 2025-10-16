<?php
$currentPage = 'performance';

// Include session check and database connection
require_once '../php/session_check.php';
require_once '../config/database.php';

$user_id = $_SESSION['id'];

// Get filter parameters
$date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING);
$date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING);
$filter_category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);

// Build WHERE clause for filters
$filter_where = "qa.user_id = ?";
$filter_params = [$user_id];

if ($date_from && $date_to) {
    $filter_where .= " AND DATE(qa.created_at) BETWEEN ? AND ?";
    $filter_params[] = $date_from;
    $filter_params[] = $date_to;
}

// Get overall statistics
try {
    $qa_cols = $pdo->query("SHOW COLUMNS FROM `quiz_attempts`")->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    $qa_cols = [];
}

if (in_array('passed', $qa_cols)) {
    $stats_sql = "SELECT 
        COUNT(*) as total_attempts,
        AVG(score) as avg_score,
        MAX(score) as best_score,
        MIN(score) as lowest_score,
        SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count
        FROM quiz_attempts WHERE user_id = ?";
} else {
    $stats_sql = "SELECT 
        COUNT(*) as total_attempts,
        AVG(qa.score) as avg_score,
        MAX(qa.score) as best_score,
        MIN(qa.score) as lowest_score,
        SUM(CASE WHEN qa.score >= q.passing_score THEN 1 ELSE 0 END) as passed_count
        FROM quiz_attempts qa 
        LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id 
        WHERE qa.user_id = ?";
}

$stmt = $pdo->prepare($stats_sql);
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if correct_answers column exists in quiz_attempts
$qa_correct_col = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'correct_answers'")->fetchAll();
$has_correct_answers = count($qa_correct_col) > 0;

// Get recent quiz attempts
// Check if total_questions column exists; if not, fall back to counting questions per quiz
$qa_total_col = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'total_questions'")->fetchAll();
$has_total_questions = count($qa_total_col) > 0;

$total_questions_select = $has_total_questions
    ? "qa.total_questions"
    : "(SELECT COUNT(*) FROM questions WHERE quiz_id = qa.quiz_id) AS total_questions";

// Detect which timestamp column exists in quiz_attempts and alias it to created_at for consistency
$possible_ts = ['created_at', 'attempted_at', 'started_at', 'timestamp'];
$ts_column = null;
foreach ($possible_ts as $col) {
    $check = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE '" . $col . "'")->fetchAll();
    if (count($check) > 0) {
        $ts_column = $col;
        break;
    }
}

// If no timestamp column found, select NULL as created_at to avoid SQL errors
$created_at_select = $ts_column ? "qa." . $ts_column . " AS created_at" : "NULL AS created_at";

if ($has_correct_answers) {
    $recent_sql = "SELECT 
        qa.quiz_id,
        qa.score,
        qa.correct_answers,
        {$total_questions_select},
        {$created_at_select},
        q.title as quiz_title
        FROM quiz_attempts qa
        LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.user_id = ?
        ORDER BY " . ($ts_column ? "qa." . $ts_column . " DESC" : "qa.quiz_id DESC") . "
        LIMIT 10";
} else {
    $recent_sql = "SELECT 
        qa.quiz_id,
        qa.score,
        {$total_questions_select},
        {$created_at_select},
        q.title as quiz_title
        FROM quiz_attempts qa
        LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.user_id = ?
        ORDER BY " . ($ts_column ? "qa." . $ts_column . " DESC" : "qa.quiz_id DESC") . "
        LIMIT 10";
}

$stmt = $pdo->prepare($recent_sql);
$stmt->execute([$user_id]);
$recent_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pass rate
$pass_rate = $stats['total_attempts'] > 0 
    ? round(($stats['passed_count'] / $stats['total_attempts']) * 100, 1) 
    : 0;

// Get data for score trend chart (last 10 attempts with timestamps)
$trend_sql = "SELECT qa.score, " . ($ts_column ? "qa." . $ts_column : "NOW()") . " AS attempt_date, q.title
              FROM quiz_attempts qa
              LEFT JOIN quizzes q ON qa.quiz_id = q.quiz_id
              WHERE qa.user_id = ?
              ORDER BY " . ($ts_column ? "qa." . $ts_column : "qa.quiz_id") . " ASC
              LIMIT 15";
$stmt = $pdo->prepare($trend_sql);
$stmt->execute([$user_id]);
$trend_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category-wise performance data (if categories exist)
$category_data = [];
$has_categories = false;
try {
    $cat_check = $pdo->query("SHOW TABLES LIKE 'quiz_categories'")->fetchAll();
    if (count($cat_check) > 0) {
        $has_categories = true;
        $cat_sql = "SELECT qc.category_name, AVG(qa.score) as avg_score, COUNT(qa.attempt_id) as attempts
                    FROM quiz_attempts qa
                    JOIN quizzes q ON qa.quiz_id = q.quiz_id
                    JOIN quiz_categories qc ON q.category_id = qc.category_id
                    WHERE qa.user_id = ?
                    GROUP BY qc.category_id, qc.category_name
                    ORDER BY avg_score DESC";
        $stmt = $pdo->prepare($cat_sql);
        $stmt->execute([$user_id]);
        $category_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Categories table doesn't exist
}

// Get monthly performance comparison (last 6 months)
$monthly_sql = "SELECT 
                DATE_FORMAT(" . ($ts_column ? "qa." . $ts_column : "NOW()") . ", '%Y-%m') as month,
                AVG(qa.score) as avg_score,
                COUNT(qa.attempt_id) as attempts
                FROM quiz_attempts qa
                WHERE qa.user_id = ?
                GROUP BY month
                ORDER BY month DESC
                LIMIT 6";
$stmt = $pdo->prepare($monthly_sql);
$stmt->execute([$user_id]);
$monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$monthly_data = array_reverse($monthly_data); // Oldest to newest

include '../includes/header.php';
?>
<script>document.title = 'Performance Tracking - Self-Learning Hub';</script>
<link rel="stylesheet" href="../assets/css/performance.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
.filters-export-bar {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.filters-section {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
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

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.95em;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #667eea;
}

.btn-filter {
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    margin-top: 20px;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.export-buttons {
    display: flex;
    gap: 10px;
}

.btn-export {
    padding: 10px 20px;
    border: 2px solid #667eea;
    background: white;
    color: #667eea;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-export:hover {
    background: #667eea;
    color: white;
}

.btn-export.pdf {
    border-color: #e53e3e;
    color: #e53e3e;
}

.btn-export.pdf:hover {
    background: #e53e3e;
    color: white;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 25px;
    margin-bottom: 25px;
}

.chart-container {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.chart-container h3 {
    color: #2d3748;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-wrapper {
    position: relative;
    height: 300px;
}

@media (max-width: 768px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    .filters-export-bar {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1><i class="fa-solid fa-chart-line"></i> Performance Tracking</h1>
            <p>Monitor your learning progress and achievements.</p>
        </header>

        <!-- Filters and Export Bar -->
        <div class="filters-export-bar">
            <form class="filters-section" method="GET">
                <div class="filter-group">
                    <label for="date_from">From Date:</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_to">To Date:</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <?php if ($date_from || $date_to): ?>
                <a href="performance.php" class="btn-filter" style="background: #718096; margin-left: 10px;">
                    <i class="fas fa-times"></i> Clear
                </a>
                <?php endif; ?>
            </form>
            
            <div class="export-buttons">
                <a href="../php/export_performance.php?format=csv" class="btn-export">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
                <a href="../php/export_performance.php?format=pdf" class="btn-export pdf">
                    <i class="fas fa-file-pdf"></i> Generate Report
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['total_attempts'] ?? 0; ?></h3>
                    <p>Quizzes Taken</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['passed_count'] ?? 0; ?></h3>
                    <p>Quizzes Passed</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-percentage"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['avg_score'] ? round($stats['avg_score'], 1) . '%' : 'N/A'; ?></h3>
                    <p>Average Score</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $pass_rate; ?>%</h3>
                    <p>Pass Rate</p>
                </div>
            </div>
        </div>

        <!-- Interactive Charts Section -->
        <div class="charts-grid">
            <!-- Score Trend Chart -->
            <div class="chart-container">
                <h3><i class="fas fa-chart-line"></i> Score Trend</h3>
                <div class="chart-wrapper">
                    <canvas id="scoreTrendChart"></canvas>
                </div>
            </div>

            <!-- Completion Rate Chart -->
            <div class="chart-container">
                <h3><i class="fas fa-tasks"></i> Completion Rate</h3>
                <div class="chart-wrapper">
                    <canvas id="completionRateChart"></canvas>
                </div>
            </div>

            <?php if ($has_categories && !empty($category_data)): ?>
            <!-- Category Performance Chart -->
            <div class="chart-container">
                <h3><i class="fas fa-chart-pie"></i> Performance by Category</h3>
                <div class="chart-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Monthly Comparison Chart -->
            <div class="chart-container">
                <h3><i class="fas fa-calendar-alt"></i> Monthly Performance</h3>
                <div class="chart-wrapper">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Performance Chart & Achievements -->
        <div class="performance-grid">
            
            <!-- Score Range -->
            <div class="performance-section">
                <h2><i class="fa-solid fa-chart-bar"></i> Score Range</h2>
                <div class="score-range">
                    <div class="score-item">
                        <span class="score-label">Best Score</span>
                        <span class="score-value best"><?php echo $stats['best_score'] ?? 0; ?>%</span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Average Score</span>
                        <span class="score-value avg"><?php echo $stats['avg_score'] ? round($stats['avg_score'], 1) : 0; ?>%</span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Lowest Score</span>
                        <span class="score-value low"><?php echo $stats['lowest_score'] ?? 0; ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Achievements -->
            <div class="performance-section">
                <h2><i class="fa-solid fa-medal"></i> Achievements</h2>
                <div class="achievements">
                    <?php
                    $achievements = [
                        ['icon' => 'fa-rocket', 'title' => 'Quick Start', 'desc' => 'Completed first quiz', 'unlocked' => $stats['total_attempts'] >= 1],
                        ['icon' => 'fa-fire', 'title' => 'On Fire', 'desc' => 'Passed 5 quizzes', 'unlocked' => $stats['passed_count'] >= 5],
                        ['icon' => 'fa-star', 'title' => 'High Achiever', 'desc' => 'Average score above 80%', 'unlocked' => ($stats['avg_score'] ?? 0) >= 80],
                        ['icon' => 'fa-crown', 'title' => 'Perfect Score', 'desc' => 'Scored 100% on a quiz', 'unlocked' => ($stats['best_score'] ?? 0) >= 100],
                    ];

                    foreach ($achievements as $achievement):
                        $class = $achievement['unlocked'] ? 'unlocked' : 'locked';
                    ?>
                    <div class="achievement-badge <?php echo $class; ?>">
                        <i class="fa-solid <?php echo $achievement['icon']; ?>"></i>
                        <div class="achievement-info">
                            <h4><?php echo $achievement['title']; ?></h4>
                            <p><?php echo $achievement['desc']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Quiz History -->
        <div class="performance-section">
            <h2><i class="fa-solid fa-history"></i> Recent Quiz History</h2>
            <?php if (empty($recent_attempts)): ?>
                <p class="no-data">No quiz attempts yet. Start taking quizzes to track your performance!</p>
            <?php else: ?>
                <div class="quiz-history-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Score</th>
                                <th>Correct Answers</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_attempts as $attempt): 
                                $score = $attempt['score'];
                                $status_class = $score >= 70 ? 'passed' : 'failed';
                                $status_text = $score >= 70 ? 'Passed' : 'Failed';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attempt['quiz_title'] ?? 'Untitled Quiz'); ?></td>
                                <td><strong><?php echo $score; ?>%</strong></td>
                                <td>
                                    <?php if ($has_correct_answers): ?>
                                        <?php echo $attempt['correct_answers']; ?> / <?php echo $attempt['total_questions']; ?>
                                    <?php else: ?>
                                        <?php echo $attempt['total_questions']; ?> questions
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($attempt['created_at'])); ?></td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
// Prepare data for charts
const trendData = <?php echo json_encode($trend_data); ?>;
const categoryData = <?php echo json_encode($category_data); ?>;
const monthlyData = <?php echo json_encode($monthly_data); ?>;
const passRate = <?php echo $pass_rate; ?>;

// Chart.js default configuration
Chart.defaults.font.family = "'Poppins', sans-serif";
Chart.defaults.color = '#4a5568';

// 1. Score Trend Line Chart
if (trendData.length > 0) {
    const ctx1 = document.getElementById('scoreTrendChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: trendData.map((d, i) => `Attempt ${i + 1}`),
            datasets: [{
                label: 'Score (%)',
                data: trendData.map(d => d.score),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#2d3748',
                    padding: 12,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 },
                    callbacks: {
                        title: function(context) {
                            return trendData[context[0].dataIndex].title || 'Quiz';
                        },
                        label: function(context) {
                            return 'Score: ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// 2. Completion Rate Doughnut Chart
const ctx2 = document.getElementById('completionRateChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Passed', 'Failed'],
        datasets: [{
            data: [passRate, 100 - passRate],
            backgroundColor: [
                '#48bb78',
                '#f56565'
            ],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 13
                    }
                }
            },
            tooltip: {
                backgroundColor: '#2d3748',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed.toFixed(1) + '%';
                    }
                }
            }
        }
    }
});

// 3. Category Performance Pie Chart
<?php if ($has_categories && !empty($category_data)): ?>
if (categoryData.length > 0) {
    const ctx3 = document.getElementById('categoryChart').getContext('2d');
    const colors = ['#667eea', '#f6ad55', '#48bb78', '#e53e3e', '#9f7aea', '#38b2ac'];
    new Chart(ctx3, {
        type: 'pie',
        data: {
            labels: categoryData.map(d => d.category_name),
            datasets: [{
                data: categoryData.map(d => parseFloat(d.avg_score)),
                backgroundColor: colors.slice(0, categoryData.length),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#2d3748',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.toFixed(1) + '% avg';
                        }
                    }
                }
            }
        }
    });
}
<?php endif; ?>

// 4. Monthly Performance Bar Chart
if (monthlyData.length > 0) {
    const ctx4 = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx4, {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => {
                const [year, month] = d.month.split('-');
                const date = new Date(year, month - 1);
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Average Score (%)',
                data: monthlyData.map(d => parseFloat(d.avg_score)),
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: '#667eea',
                borderWidth: 2,
                borderRadius: 6,
                hoverBackgroundColor: '#764ba2'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#2d3748',
                    padding: 12,
                    callbacks: {
                        afterLabel: function(context) {
                            const monthData = monthlyData[context.dataIndex];
                            return 'Attempts: ' + monthData.attempts;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
