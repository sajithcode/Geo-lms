<?php
$currentPage = 'interaction';

require_once '../php/session_check.php';
require_once '../config/database.php';

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? 'student';

// Check if communication tables exist
$tables_check = [];
$tables_to_check = ['messages', 'announcements', 'notifications'];
foreach ($tables_to_check as $table) {
    $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
    $tables_check[$table] = count($check) > 0;
}

// Get message statistics
$message_stats = ['inbox' => 0, 'sent' => 0, 'unread' => 0];
if ($tables_check['messages']) {
    try {
        // Inbox count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ?");
        $stmt->execute([$user_id]);
        $message_stats['inbox'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Sent count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE sender_id = ?");
        $stmt->execute([$user_id]);
        $message_stats['sent'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Unread count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $message_stats['unread'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        // Error fetching stats
    }
}

// Get recent messages
$recent_messages = [];
if ($tables_check['messages']) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, u.username as sender_name, u.role as sender_role 
                               FROM messages m 
                               JOIN users u ON m.sender_id = u.user_id 
                               WHERE m.receiver_id = ? 
                               ORDER BY m.created_at DESC 
                               LIMIT 5");
        $stmt->execute([$user_id]);
        $recent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $recent_messages = [];
    }
}

// Get announcement statistics
$announcement_stats = ['total' => 0];
if ($tables_check['announcements']) {
    try {
        // Total published announcements
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM announcements 
                               WHERE status = 'published'");
        $stmt->execute();
        $announcement_stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        // Error fetching stats
    }
}

// Get recent announcements
$recent_announcements = [];
if ($tables_check['announcements']) {
    try {
        $stmt = $pdo->prepare("SELECT a.*, u.username as author_name 
                               FROM announcements a 
                               LEFT JOIN users u ON a.published_by = u.user_id 
                               WHERE a.status = 'published'
                               ORDER BY a.published_at DESC 
                               LIMIT 5");
        $stmt->execute();
        $recent_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $recent_announcements = [];
    }
}

// Get notification statistics
$notification_stats = ['total' => 0, 'unread' => 0];
if ($tables_check['notifications']) {
    try {
        // Total notifications
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $notification_stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Unread notifications
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $notification_stats['unread'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        // Error fetching stats
    }
}

// Get recent notifications
$recent_notifications = [];
if ($tables_check['notifications']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications 
                               WHERE user_id = ? 
                               ORDER BY created_at DESC 
                               LIMIT 5");
        $stmt->execute([$user_id]);
        $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $recent_notifications = [];
    }
}

include '../includes/header.php';
?>
<script>document.title = 'Interaction Hub - Self-Learning Hub';</script>

<style>
.interaction-container {
    max-width: 1400px;
    margin: 0 auto;
}

.interaction-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.interaction-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    display: flex;
    align-items: center;
    gap: 15px;
}

.interaction-header p {
    margin: 0;
    font-size: 1.1em;
    opacity: 0.95;
}

.quick-stats {
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
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
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
}

.stat-icon.blue {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-icon.green {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.stat-icon.orange {
    background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
    color: white;
}

.stat-icon.purple {
    background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%);
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

.stat-badge {
    background: #e53e3e;
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.75em;
    font-weight: 600;
    margin-left: auto;
}

.communication-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.comm-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.section-header {
    padding: 20px 25px;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h2 {
    margin: 0;
    color: #2d3748;
    font-size: 1.3em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-view-all {
    padding: 8px 16px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.btn-view-all:hover {
    background: #764ba2;
    transform: translateY(-2px);
}

.section-content {
    padding: 20px 25px;
    max-height: 400px;
    overflow-y: auto;
}

.item-card {
    padding: 15px;
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.2s ease;
    cursor: pointer;
}

.item-card:hover {
    background: #f7fafc;
}

.item-card:last-child {
    border-bottom: none;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 8px;
}

.item-title {
    font-weight: 600;
    color: #2d3748;
    margin: 0;
}

.item-meta {
    display: flex;
    gap: 10px;
    font-size: 0.85em;
    color: #718096;
    align-items: center;
}

.item-badge {
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    font-weight: 600;
}

.badge-unread {
    background: #fed7d7;
    color: #742a2a;
}

.badge-role {
    background: #e6f2ff;
    color: #0066cc;
}

.badge-admin {
    background: #fed7d7;
    color: #742a2a;
}

.badge-instructor {
    background: #feebc8;
    color: #7c2d12;
}

.badge-priority {
    margin-right: 8px;
}

.badge-priority.low {
    background: #f0f9ff;
    color: #0369a1;
}

.badge-priority.medium {
    background: #fef3c7;
    color: #d97706;
}

.badge-priority.high {
    background: #fee2e2;
    color: #dc2626;
}

.badge-status {
    font-size: 0.75em;
}

.badge-status.published {
    background: #d1fae5;
    color: #065f46;
}

.badge-status.draft {
    background: #f3f4f6;
    color: #374151;
}

.badge-status.archived {
    background: #fed7d7;
    color: #991b1b;
}

.item-preview {
    color: #4a5568;
    font-size: 0.9em;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
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

.quick-actions {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.quick-actions h2 {
    margin: 0 0 20px 0;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 10px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-action {
    flex: 1;
    min-width: 200px;
    padding: 15px 20px;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    color: inherit;
}

.btn-action:hover {
    border-color: #667eea;
    background: #f7fafc;
    transform: translateY(-2px);
}

.btn-action i {
    font-size: 1.5em;
    color: #667eea;
}

.btn-action-content h3 {
    margin: 0;
    color: #2d3748;
    font-size: 1em;
}

.btn-action-content p {
    margin: 5px 0 0 0;
    color: #718096;
    font-size: 0.85em;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2em;
    flex-shrink: 0;
}

.notif-quiz {
    background: #e6f2ff;
    color: #0066cc;
}

.notif-message {
    background: #e6fffa;
    color: #047857;
}

.notif-announcement {
    background: #fef5e7;
    color: #d97706;
}

.notif-resource {
    background: #f3e8ff;
    color: #7c3aed;
}

@media (max-width: 768px) {
    .communication-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-action {
        min-width: 100%;
    }
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="interaction-container">
            <!-- Header -->
            <div class="interaction-header">
                <h1>
                    <i class="fas fa-comments"></i>
                    Interaction Hub
                </h1>
                <p>Stay connected with instructors and classmates. Access messages, announcements, and notifications all in one place.</p>
            </div>

            <?php if (!$tables_check['messages'] && !$tables_check['announcements']): ?>
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Communication Tables Not Found</h3>
                <p>Please run the database migration to enable messaging and announcements.</p>
                <p style="margin-top: 15px;"><a href="../docs/run_migration.php" class="btn-view-all">Run Migration</a></p>
            </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <a href="messages.php" class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $message_stats['inbox']; ?></h3>
                        <p>Messages</p>
                    </div>
                </a>

                <a href="announcements.php" class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $announcement_stats['total']; ?></h3>
                        <p>Announcements</p>
                    </div>
                </a>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $message_stats['sent']; ?></h3>
                        <p>Sent Messages</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $notification_stats['total']; ?></h3>
                        <p>Notifications</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="action-buttons">
                    <a href="messages.php?tab=compose" class="btn-action">
                        <i class="fas fa-pen"></i>
                        <div class="btn-action-content">
                            <h3>Compose Message</h3>
                            <p>Send a new message to instructors or classmates</p>
                        </div>
                    </a>

                    <?php if ($user_role === 'admin' || $user_role === 'instructor'): ?>
                    <a href="../admin/announcements.php" class="btn-action">
                        <i class="fas fa-bullhorn"></i>
                        <div class="btn-action-content">
                            <h3>Create Announcement</h3>
                            <p>Broadcast a message to students</p>
                        </div>
                    </a>
                    <?php endif; ?>

                    <a href="announcements.php" class="btn-action">
                        <i class="fas fa-list"></i>
                        <div class="btn-action-content">
                            <h3>View All Announcements</h3>
                            <p>See all announcements and updates</p>
                        </div>
                    </a>

                    <a href="feedback.php" class="btn-action">
                        <i class="fas fa-comment-dots"></i>
                        <div class="btn-action-content">
                            <h3>Send Feedback</h3>
                            <p>Share your thoughts and suggestions</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Communication Grid -->
            <div class="communication-grid">
                <!-- Recent Messages -->
                <div class="comm-section">
                    <div class="section-header">
                        <h2><i class="fas fa-inbox"></i> Recent Messages</h2>
                        <a href="messages.php" class="btn-view-all">View All</a>
                    </div>
                    <div class="section-content">
                        <?php if (empty($recent_messages)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No Messages</h3>
                                <p>Your inbox is empty</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_messages as $msg): ?>
                                <div class="item-card" onclick="window.location.href='messages.php'">
                                    <div class="item-header">
                                        <h3 class="item-title"><?php echo htmlspecialchars($msg['subject'] ?: 'No Subject'); ?></h3>
                                        <div class="item-meta">
                                        </div>
                                    </div>
                                    <div class="item-meta">
                                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($msg['sender_name']); ?></span>
                                        <span class="item-badge badge-role"><?php echo ucfirst($msg['sender_role']); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?></span>
                                    </div>
                                    <p class="item-preview"><?php echo htmlspecialchars($msg['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Announcements -->
                <div class="comm-section">
                    <div class="section-header">
                        <h2><i class="fas fa-bullhorn"></i> Recent Announcements</h2>
                        <a href="announcements.php" class="btn-view-all">View All</a>
                    </div>
                    <div class="section-content">
                        <?php if (empty($recent_announcements)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bullhorn"></i>
                                <h3>No Announcements</h3>
                                <p>No announcements posted yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_announcements as $announcement): ?>
                                <div class="item-card" onclick="window.location.href='announcements.php'">
                                    <div class="item-header">
                                        <h3 class="item-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                    </div>
                                    <div class="item-meta">
                                        <span><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($announcement['author_name'] ?? 'Admin'); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($announcement['published_at'])); ?></span>
                                        <span class="item-badge badge-role">
                                            <?php echo ucfirst($announcement['category']); ?>
                                        </span>
                                    </div>
                                    <div class="item-actions">
                                        <span class="item-badge badge-priority <?php echo $announcement['priority']; ?>">
                                            <?php echo ucfirst($announcement['priority']); ?>
                                        </span>
                                        <span class="item-badge badge-status <?php echo $announcement['status']; ?>">
                                            <?php echo ucfirst($announcement['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="comm-section">
                    <div class="section-header">
                        <h2><i class="fas fa-bell"></i> Recent Notifications</h2>
                        <a href="#" class="btn-view-all">View All</a>
                    </div>
                    <div class="section-content">
                        <?php if (empty($recent_notifications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bell"></i>
                                <h3>No Notifications</h3>
                                <p>You're all caught up!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_notifications as $notif): ?>
                                <div class="item-card">
                                    <div class="item-header">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div class="notification-icon notif-<?php echo $notif['type']; ?>">
                                                <?php
                                                $icons = [
                                                    'quiz_result' => 'fa-clipboard-check',
                                                    'message' => 'fa-envelope',
                                                    'announcement' => 'fa-bullhorn',
                                                    'resource' => 'fa-book',
                                                    'achievement' => 'fa-trophy',
                                                    'system' => 'fa-cog'
                                                ];
                                                $icon = $icons[$notif['type']] ?? 'fa-bell';
                                                ?>
                                                <i class="fas <?php echo $icon; ?>"></i>
                                            </div>
                                            <h3 class="item-title"><?php echo htmlspecialchars($notif['title']); ?></h3>
                                        </div>
                                        <div class="item-meta">
                                        </div>
                                    </div>
                                    <div class="item-meta" style="margin-left: 52px;">
                                        <span><i class="fas fa-clock"></i> <?php echo date('M j, g:i A', strtotime($notif['created_at'])); ?></span>
                                    </div>
                                    <p class="item-preview" style="margin-left: 52px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
