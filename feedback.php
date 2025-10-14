<?php
$currentPage = 'feedback'; 


require_once 'php/session_check.php';
require_once 'config/database.php';


include 'includes/header.php';
?>
<script>document.title = 'Feedback - Self-Learning Hub';</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/feedback.css">

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Feedback</h1>
            <p>Share your thoughts to improve the software.</p>
        </header>

        <div class="lr-content">
            <?php
            
            $feedbackSent = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
               
                $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));
                if (!empty($message)) {
                    try {
                        // Ensure feedbacks table exists
                        $pdo->exec("CREATE TABLE IF NOT EXISTS feedbacks (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NULL,
                            message TEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                        // Get user id from session if available
                        $userId = isset($_SESSION['id']) ? $_SESSION['id'] : null;

                        // Insert feedback using prepared statement
                        $stmt = $pdo->prepare('INSERT INTO feedbacks (user_id, message) VALUES (:user_id, :message)');
                        $stmt->execute([':user_id' => $userId, ':message' => $message]);

                        $feedbackSent = true;
                    } catch (PDOException $e) {
                        // Log error in production; for now set feedbackSent false
                        error_log('Feedback DB error: ' . $e->getMessage());
                        $feedbackSent = false;
                    }
                }
            }
            ?>

            <div class="feedback-wrap">
                <?php if ($feedbackSent): ?>
                    <div class="feedback-success">Thank you — your message has been received.</div>
                <?php else: ?>
                <form class="feedback-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <label for="message">Your message</label>
                    <textarea id="message" name="message" rows="6" placeholder="Enter your comment" required></textarea>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

