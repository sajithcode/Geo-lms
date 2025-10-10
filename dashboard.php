
<?php $currentPage = 'dashboard'; ?>
<?php
// dashboard.php

// Include the session check to secure the page
require_once 'php/session_check.php';

// Include the header
include 'includes/header.php';
?>

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
            [cite_start]<p>Here's an overview of your learning tools.</p> [cite: 34, 35]
        </header>

        <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-user-graduate"></i></div>
                <div class="card-info">
                    <h4>Courses</h4>
                    <p>View your enrolled courses.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-chart-pie"></i></div>
                <div class="card-info">
                    <h4>My Progress</h4>
                    <p>Track your performance.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                <div class="card-info">
                    <h4>Quizzes</h4>
                    <p>Take new assessments.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-bell"></i></div>
                <div class="card-info">
                    <h4>Notifications</h4>
                    <p>Check recent updates.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
// Include the footer
include 'includes/footer.php'; 
?>