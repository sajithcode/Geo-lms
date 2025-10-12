<?php
// includes/sidebar.php
// The $currentPage variable is set on each page that includes this sidebar
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h3>Geomatics LMS</h3>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($currentPage == 'dashboard') ? 'active' : '' ?>"><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li class="<?php echo ($currentPage == 'quizzes') ? 'active' : '' ?>"><a href="quizzes.php"><i class="fa-solid fa-puzzle-piece"></i> Quizzes</a></li>
            <li class="<?php echo ($currentPage == 'performance') ? 'active' : '' ?>"><a href="#"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
            <li class="<?php echo ($currentPage == 'interaction') ? 'active' : '' ?>"><a href="#"><i class="fa-solid fa-comments"></i> Interaction</a></li>
            <li class="<?php echo ($currentPage == 'resources') ? 'active' : '' ?>"><a href="learning_resources.php"><i class="fa-solid fa-book"></i> Learning Resources</a></li>
            <li class="<?php echo ($currentPage == 'feedback') ? 'active' : '' ?>"><a href="#"><i class="fa-solid fa-message"></i> Feedback</a></li>
            <li class="<?php echo ($currentPage == 'settings') ? 'active' : '' ?>"><a href="#"><i class="fa-solid fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>
</aside>