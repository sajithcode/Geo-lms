<?php $currentPage = 'dashboard'; ?>
<?php
// dashboard.php

// Include the session check to secure the page
require_once '../php/session_check.php';

// Include the header
include '../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/dashboard.css">
<?php
?>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
            <p>Here's an overview of your learning tools.</p>
        </header>

        <div class="dashboard-cards">
            <a href="settings.php" style="text-decoration:none;color:inherit;">
                <div class="card student">
                    <div class="card-icon"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="card-info">
                        <h4>Student Registration</h4>
                        <p>Manage student enrollment and profiles.</p>
                    </div>
                </div>
            </a>

            <a href="quizzes.php" style="text-decoration:none;color:inherit;">
                <div class="card quizzes">
                    <div class="card-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="card-info">
                        <h4>Quizzes</h4>
                        <p>Take and manage assessments.</p>
                    </div>
                </div>
            </a>

            <a href="messages.php" style="text-decoration:none;color:inherit;">
                <div class="card notifications">
                    <div class="card-icon"><i class="fa-solid fa-bell"></i></div>
                    <div class="card-info">
                        <h4>Notifications</h4>
                        <p>View recent updates and alerts.</p>
                    </div>
                </div>
            </a>

            <a href="performance.php" style="text-decoration:none;color:inherit;">
                <div class="card progress">
                    <div class="card-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    <div class="card-info">
                        <h4>Performance Tracking</h4>
                        <p>Track learning progress over time.</p>
                    </div>
                </div>
            </a>

            <a href="interaction.php" style="text-decoration:none;color:inherit;">
                <div class="card teacher">
                    <div class="card-icon"><i class="fa-solid fa-comments"></i></div>
                    <div class="card-info">
                        <h4>Teacher Interaction</h4>
                        <p>Communicate with instructors and mentors.</p>
                    </div>
                </div>
            </a>

            <a href="learning_resources.php" style="text-decoration:none;color:inherit;">
                <div class="card resources">
                    <div class="card-icon"><i class="fa-solid fa-book"></i></div>
                    <div class="card-info">
                        <h4>Learning Resources</h4>
                        <p>Access notes, e-books, and pastpapers.</p>
                    </div>
                </div>
            </a>

            <a href="feedback.php" style="text-decoration:none;color:inherit;">
                <div class="card feedback">
                    <div class="card-icon"><i class="fa-solid fa-star"></i></div>
                    <div class="card-info">
                        <h4>Feedback</h4>
                        <p>Submit suggestions and bug reports.</p>
                    </div>
                </div>
            </a>
        </div>
    </main>
</div>

<?php
// Include the footer
include '../includes/footer.php'; 
?>
