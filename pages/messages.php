<?php
$currentPage = 'messages';

require_once '../php/session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';

// Check if messages table exists
$tables_check = $pdo->query("SHOW TABLES LIKE 'messages'")->fetchAll();
$has_messages_table = count($tables_check) > 0;

$user_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $_SESSION['error_message'] = "User session error. Please login again.";
    header("location: ../auth/index.php");
    exit;
}

$active_tab = $_GET['tab'] ?? 'inbox';

// Handle send message
$send_success = false;
$send_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    csrf_validate_or_redirect('messages.php');
    
    $receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!$receiver_id) {
        $send_error = "Please select a recipient.";
    } elseif (empty($message)) {
        $send_error = "Message cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $receiver_id, $subject, $message]);
            $send_success = true;
            
            // Create notification for receiver
            try {
                $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'message', ?, ?, ?)");
                $stmt_notif->execute([
                    $receiver_id,
                    "New message from " . $_SESSION['username'],
                    substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
                    "pages/messages.php"
                ]);
            } catch (PDOException $e) {
                // Notifications table doesn't exist yet
            }
        } catch (PDOException $e) {
            $send_error = "Error sending message: " . $e->getMessage();
        }
    }
}

// Handle mark as read
if (isset($_GET['mark_read']) && $has_messages_table) {
    $message_id = filter_input(INPUT_GET, 'mark_read', FILTER_VALIDATE_INT);
    if ($message_id) {
        try {
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE message_id = ? AND receiver_id = ?");
            $stmt->execute([$message_id, $user_id]);
        } catch (PDOException $e) {
            // Error marking as read
        }
    }
}

// Fetch inbox messages
$inbox_messages = [];
if ($has_messages_table) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, u.username as sender_name, u.role as sender_role 
                               FROM messages m 
                               JOIN users u ON m.sender_id = u.user_id 
                               WHERE m.receiver_id = ? 
                               ORDER BY m.created_at DESC");
        $stmt->execute([$user_id]);
        $inbox_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $inbox_messages = [];
    }
}

// Fetch sent messages
$sent_messages = [];
if ($has_messages_table) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, u.username as receiver_name, u.role as receiver_role 
                               FROM messages m 
                               JOIN users u ON m.receiver_id = u.user_id 
                               WHERE m.sender_id = ? 
                               ORDER BY m.created_at DESC");
        $stmt->execute([$user_id]);
        $sent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $sent_messages = [];
    }
}

// Fetch users for recipient dropdown
$users = [];
if ($has_messages_table) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, full_name, role FROM users WHERE user_id != ? ORDER BY username");
        $stmt->execute([$user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $users = [];
    }
}

// Count unread messages
$unread_count = 0;
if ($has_messages_table) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $unread_count = $result['unread'];
    } catch (PDOException $e) {
        $unread_count = 0;
    }
}

include '../includes/header.php';
?>
<script>document.title = 'Messages - Self-Learning Hub';</script>

<style>
.messages-container {
    display: flex;
    gap: 25px;
    max-width: 1400px;
    margin: 0 auto;
}

.compose-section {
    flex: 1;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: fit-content;
}

.compose-section h2 {
    color: #2d3748;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.messages-section {
    flex: 2;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
    text-decoration: none;
    transition: all 0.3s ease;
}

.tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.tab:hover {
    color: #667eea;
}

.tab .badge {
    background: #e53e3e;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-left: 5px;
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
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 1em;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.btn-send {
    width: 100%;
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-send:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.message-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.message-card {
    padding: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.message-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.message-card.unread {
    background: #f7fafc;
    border-left: 4px solid #667eea;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.message-from {
    font-weight: 600;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 8px;
}

.role-badge {
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
}

.role-admin {
    background: #fed7d7;
    color: #742a2a;
}

.role-instructor {
    background: #feebc8;
    color: #7c2d12;
}

.role-student {
    background: #e6f2ff;
    color: #0066cc;
}

.message-date {
    color: #718096;
    font-size: 0.9em;
}

.message-subject {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
}

.message-preview {
    color: #718096;
    font-size: 0.95em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.message-actions {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

.btn-small {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-mark-read {
    background: #48bb78;
    color: white;
}

.btn-mark-read:hover {
    background: #38a169;
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

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #cbd5e0;
}

.empty-state i {
    font-size: 4em;
    margin-bottom: 20px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5em;
    cursor: pointer;
    color: #718096;
}

.modal-close:hover {
    color: #2d3748;
}

.message-full {
    line-height: 1.6;
    color: #4a5568;
}
</style>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1><i class="fas fa-envelope"></i> Messages</h1>
            <p>Communicate with instructors and classmates</p>
        </header>

        <?php if (!$has_messages_table): ?>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Messages Table Not Found</h3>
            <p>Please run the database migration to create the messages table.</p>
            <p style="margin-top: 15px;"><a href="../docs/run_migration.php" class="btn-send">Run Migration</a></p>
        </div>
        <?php else: ?>

        <?php if ($send_success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Message sent successfully!
        </div>
        <?php endif; ?>

        <?php if ($send_error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($send_error); ?>
        </div>
        <?php endif; ?>

        <div class="messages-container">
            <!-- Compose Message Section -->
            <aside class="compose-section">
                <h2><i class="fas fa-pen"></i> Compose Message</h2>
                <form method="POST">
                    <?php echo csrf_token_field(); ?>
                    <input type="hidden" name="send_message" value="1">
                    
                    <div class="form-group">
                        <label for="receiver_id">To: *</label>
                        <select name="receiver_id" id="receiver_id" required>
                            <option value="">Select Recipient</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?> 
                                    (<?php echo htmlspecialchars($user['full_name']); ?>) 
                                    - <?php echo ucfirst($user['role']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" name="subject" id="subject" placeholder="Enter subject (optional)">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message: *</label>
                        <textarea name="message" id="message" required placeholder="Type your message here..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </aside>

            <!-- Messages List Section -->
            <section class="messages-section">
                <div class="tabs">
                    <a href="?tab=inbox" class="tab <?php echo $active_tab === 'inbox' ? 'active' : ''; ?>">
                        <i class="fas fa-inbox"></i> Inbox
                        <?php if ($unread_count > 0): ?>
                            <span class="badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=sent" class="tab <?php echo $active_tab === 'sent' ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane"></i> Sent
                    </a>
                </div>

                <!-- Inbox Tab -->
                <?php if ($active_tab === 'inbox'): ?>
                    <?php if (empty($inbox_messages)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No Messages</h3>
                            <p>Your inbox is empty. When someone sends you a message, it will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="message-list">
                            <?php foreach ($inbox_messages as $msg): ?>
                                <div class="message-card <?php echo $msg['is_read'] ? '' : 'unread'; ?>" 
                                     onclick="viewMessage(<?php echo $msg['message_id']; ?>)">
                                    <div class="message-header">
                                        <div class="message-from">
                                            <i class="fas fa-user-circle"></i>
                                            <?php echo htmlspecialchars($msg['sender_name']); ?>
                                            <span class="role-badge role-<?php echo $msg['sender_role']; ?>">
                                                <?php echo $msg['sender_role']; ?>
                                            </span>
                                        </div>
                                        <span class="message-date">
                                            <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($msg['subject']): ?>
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="message-preview">
                                        <?php echo htmlspecialchars($msg['message']); ?>
                                    </div>
                                    
                                    <?php if (!$msg['is_read']): ?>
                                        <div class="message-actions" onclick="event.stopPropagation();">
                                            <a href="?mark_read=<?php echo $msg['message_id']; ?>&tab=inbox" 
                                               class="btn-small btn-mark-read">
                                                <i class="fas fa-check"></i> Mark as Read
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Hidden modal content -->
                                <div id="modal-<?php echo $msg['message_id']; ?>" class="modal">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h3><?php echo htmlspecialchars($msg['subject'] ?: 'No Subject'); ?></h3>
                                            <button class="modal-close" onclick="closeModal(<?php echo $msg['message_id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div style="margin-bottom: 15px;">
                                            <strong>From:</strong> <?php echo htmlspecialchars($msg['sender_name']); ?><br>
                                            <strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                        <div class="message-full">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Sent Tab -->
                <?php if ($active_tab === 'sent'): ?>
                    <?php if (empty($sent_messages)): ?>
                        <div class="empty-state">
                            <i class="fas fa-paper-plane"></i>
                            <h3>No Sent Messages</h3>
                            <p>You haven't sent any messages yet. Use the compose form to send your first message!</p>
                        </div>
                    <?php else: ?>
                        <div class="message-list">
                            <?php foreach ($sent_messages as $msg): ?>
                                <div class="message-card" onclick="viewMessage(<?php echo $msg['message_id']; ?>)">
                                    <div class="message-header">
                                        <div class="message-from">
                                            <i class="fas fa-user"></i>
                                            To: <?php echo htmlspecialchars($msg['receiver_name']); ?>
                                            <span class="role-badge role-<?php echo $msg['receiver_role']; ?>">
                                                <?php echo $msg['receiver_role']; ?>
                                            </span>
                                        </div>
                                        <span class="message-date">
                                            <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($msg['subject']): ?>
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="message-preview">
                                        <?php echo htmlspecialchars($msg['message']); ?>
                                    </div>
                                </div>
                                
                                <!-- Hidden modal content -->
                                <div id="modal-<?php echo $msg['message_id']; ?>" class="modal">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h3><?php echo htmlspecialchars($msg['subject'] ?: 'No Subject'); ?></h3>
                                            <button class="modal-close" onclick="closeModal(<?php echo $msg['message_id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div style="margin-bottom: 15px;">
                                            <strong>To:</strong> <?php echo htmlspecialchars($msg['receiver_name']); ?><br>
                                            <strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                        <div class="message-full">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>

        <?php endif; ?>
    </main>
</div>

<script>
function viewMessage(messageId) {
    document.getElementById('modal-' + messageId).classList.add('active');
}

function closeModal(messageId) {
    document.getElementById('modal-' + messageId).classList.remove('active');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
