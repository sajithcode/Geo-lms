<?php
$currentPage = 'feedback'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Don't force a login here; feedback should be public-friendly.
// If you want to require authentication later, re-enable the session_check include.
// require_once '../php/session_check.php';
require_once '../config/database.php';
require_once '../php/csrf.php';


include '../includes/header.php';
?>
<script>document.title = 'Feedback - Self-Learning Hub';</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/feedback.css">

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Feedback</h1>
            <p>Share your thoughts to improve the software.</p>
        </header>

        <div class="lr-content">
            <?php
            
            $feedbackSent = false;
            $feedbackError = '';
            // Keep the submitted message to re-populate the textarea on error
            $submittedMessage = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate CSRF token
                if (!csrf_validate_token($_POST['csrf_token'] ?? '')) {
                    $feedbackError = 'Security token validation failed. Please try again.';
                } else {
                    $submittedMessage = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
                    if ($submittedMessage === '') {
                        $feedbackError = 'Please enter a message.';
                    } else {
                    try {
                        // Use the existing `feedbacks` table. Do not create tables here.
                        $userId = isset($_SESSION['id']) ? $_SESSION['id'] : null;
                        $stmt = $pdo->prepare('INSERT INTO feedbacks (user_id, message) VALUES (:user_id, :message)');
                        $stmt->execute([':user_id' => $userId, ':message' => $submittedMessage]);
                        $feedbackSent = true;
                    } catch (PDOException $e) {
                        error_log('Feedback DB error: ' . $e->getMessage());
                        $feedbackError = 'An error occurred while saving your message. Please try again later.';
                        $feedbackSent = false;
                    }
                }
                }
            }
            ?>

            <div class="feedback-wrap">
                <?php if ($feedbackSent): ?>
                    <div class="feedback-success">Thank you — your message has been received.</div>
                <?php else: ?>
                    <?php if (!empty($feedbackError)): ?>
                        <div class="feedback-error"><?php echo htmlspecialchars($feedbackError, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <form class="feedback-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo csrf_token_field(); ?>
                        <label for="message">Your message</label>
                        <textarea id="message" name="message" rows="6" placeholder="Enter your comment" required><?php echo isset($submittedMessage) ? htmlspecialchars($submittedMessage, ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

