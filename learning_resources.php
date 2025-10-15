<?php
$currentPage = 'resources'; // set active link in sidebar

// Include session check and database connection (if needed later)
require_once 'php/session_check.php';
require_once 'config/database.php';

// Include the common header (loads main stylesheet)
include 'includes/header.php';
?>
<script>document.title = 'Learning Resources - Self-Learning Hub';</script>

<!-- Page-specific font & styles -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/learning_resources.css">

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Learning Resources</h1>
            <p>Access study materials, notes, guides, and more.</p>
        </header>

        <div class="lr-content">
            <div class="resources">
                <article class="resource-card">
                    <h3><img src="assets/images-learning_resources/note-book.png" alt="Notes" width="24" height="24"> Notes</h3>
                    <p>Download subject-wise notes and concise summaries to aid revision.</p>
                    <a class="btn btn_a" href="notes.php">Browse Notes</a>
                </article>

                <article class="resource-card">
                    <h3><img src="assets/images-learning_resources/e-book.png" alt="E-Books" width="24" height="24"> E-Books</h3>
                    <p>Access textbooks and reference <br/> e-books for deeper study.</p>
                    <a class="btn btn_a" href="e-books.php">Open E-Books</a>
                </article>

                <article class="resource-card">
                    <h3><img src="assets/images-learning_resources/past-paper.png" alt="Papers" width="24" height="24"> Past Papers</h3>
                    <p>Practice with previous year exam papers and solutions.</p>
                    <a class="btn btn_a" href="pastpapers.php">Practice Papers</a>
                </article>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>