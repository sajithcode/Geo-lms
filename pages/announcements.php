<?php
$currentPage = 'announcements';

require_once '../php/session_check.php';
require_once '../config/database.php';

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? 'student';

// Check if announcements table exists
$tables_check = $pdo->query("SHOW TABLES LIKE 'announcements'")->fetchAll();
$has_announcements_table = count($tables_check) > 0;

// Fetch published announcements
$announcements = [];
if ($has_announcements_table) {
    try {
        $stmt = $pdo->prepare("SELECT a.*, u.username as author_name, u.role as author_role
                               FROM announcements a
                               LEFT JOIN users u ON a.published_by = u.user_id
                               WHERE a.status = 'published'
                               ORDER BY a.published_at DESC");
        $stmt->execute();
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $announcements = [];
    }
}

// Get announcement statistics
$stats = ['total' => 0, 'general' => 0, 'academic' => 0, 'event' => 0, 'urgent' => 0];
if ($has_announcements_table && !empty($announcements)) {
    $stats['total'] = count($announcements);
    foreach ($announcements as $announcement) {
        if (isset($stats[$announcement['category']])) {
            $stats[$announcement['category']]++;
        }
    }
}

include '../includes/header.php';
?>
<script>document.title = 'Announcements - Self-Learning Hub';</script>

<style>
.announcements-container {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.page-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-header p {
    margin: 0;
    opacity: 0.95;
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

.stat-icon.general { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #3730a3; }
.stat-icon.academic { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; }
.stat-icon.event { background: linear-gradient(135deg, #fef5e7 0%, #fde68a 100%); color: #d97706; }
.stat-icon.urgent { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626; }

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

.announcements-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.announcements-header {
    padding: 25px;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    border-bottom: 2px solid #e2e8f0;
}

.announcements-header h2 {
    margin: 0;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 10px;
}

.announcements-content {
    padding: 0;
}

.announcement-card {
    padding: 25px;
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.announcement-card:hover {
    background: #f8fafc;
}

.announcement-card:last-child {
    border-bottom: none;
}

.announcement-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.announcement-title {
    font-size: 1.4em;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 10px 0;
}

.announcement-meta {
    display: flex;
    gap: 15px;
    font-size: 0.9em;
    color: #718096;
    margin-bottom: 15px;
    flex-wrap: wrap;
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
    color: #dc2626;
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
    color: #dc2626;
}

.badge-published {
    background: #d1fae5;
    color: #065f46;
}

.announcement-content {
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 15px;
}

.announcement-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9em;
    color: #718096;
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

.empty-state p {
    color: #cbd5e0;
    margin: 0;
}

.warning-box {
    background: #fffaf0;
    border: 2px solid #ed8936;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    margin-bottom: 25px;
}

.warning-box i {
    font-size: 3em;
    color: #ed8936;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .announcement-header {
        flex-direction: column;
        gap: 10px;
    }

    .announcement-meta {
        flex-direction: column;
        gap: 8px;
    }

    .announcement-footer {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="announcements-container">
            <!-- Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-bullhorn"></i>
                    Announcements
                </h1>
                <p>Stay updated with the latest news, events, and important information from your instructors.</p>
            </div>

            <?php if (!$has_announcements_table): ?>
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Announcements Not Available</h3>
                <p>The announcements system is currently being set up. Please check back later.</p>
            </div>
            <?php else: ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon general">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Announcements</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon academic">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['academic']; ?></h3>
                        <p>Academic</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon event">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['event']; ?></h3>
                        <p>Events</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon urgent">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $stats['urgent']; ?></h3>
                        <p>Urgent</p>
                    </div>
                </div>
            </div>

            <!-- Announcements List -->
            <div class="announcements-list">
                <div class="announcements-header">
                    <h2><i class="fas fa-list"></i> All Announcements (<?php echo $stats['total']; ?>)</h2>
                </div>

                <div class="announcements-content">
                    <?php if (empty($announcements)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bullhorn"></i>
                            <h3>No Announcements</h3>
                            <p>There are no announcements at the moment. Check back later for updates!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-card">
                                <div class="announcement-header">
                                    <div>
                                        <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                        <div class="announcement-meta">
                                            <span>
                                                <i class="fas fa-user-tie"></i>
                                                <?php echo htmlspecialchars($announcement['author_name'] ?? 'Admin'); ?>
                                                <?php if (isset($announcement['author_role'])): ?>
                                                    <span class="badge badge-<?php echo strtolower($announcement['author_role']); ?>">
                                                        <?php echo ucfirst($announcement['author_role']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($announcement['published_at'])); ?>
                                            </span>
                                            <span class="badge badge-<?php echo strtolower($announcement['category']); ?>">
                                                <i class="fas fa-tag"></i>
                                                <?php echo ucfirst($announcement['category']); ?>
                                            </span>
                                            <span class="badge badge-<?php echo strtolower($announcement['priority']); ?>">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <?php echo ucfirst($announcement['priority']); ?> Priority
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                </div>

                                <div class="announcement-footer">
                                    <span>
                                        <i class="fas fa-clock"></i>
                                        Published <?php echo date('F j, Y', strtotime($announcement['published_at'])); ?>
                                    </span>
                                    <?php if (!empty($announcement['expires_at'])): ?>
                                        <span>
                                            <i class="fas fa-hourglass-end"></i>
                                            Expires: <?php echo date('M j, Y', strtotime($announcement['expires_at'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>