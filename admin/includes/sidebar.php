<?php
// admin/includes/sidebar.php
// The $currentPage variable is set on each page that includes this sidebar
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h3>Geomatics LMS</h3>
        <p style="font-size: 0.85rem; margin: 5px 0 0; opacity: 0.9;">Admin Portal</p>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($currentPage == 'admin_dashboard') ? 'active' : '' ?>">
                <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </li>
            <li class="<?php echo ($currentPage == 'admin_users') ? 'active' : '' ?>">
                <a href="users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
            </li>
            <li class="<?php echo ($currentPage == 'admin_quizzes') ? 'active' : '' ?>">
                <a href="quizzes.php"><i class="fa-solid fa-puzzle-piece"></i> Manage Quizzes</a>
            </li>
            <li class="<?php echo ($currentPage == 'admin_categories') ? 'active' : '' ?>">
                <a href="quiz_categories.php"><i class="fa-solid fa-tags"></i> Quiz Categories</a>
            </li>
            <li class="<?php echo ($currentPage == 'admin_resources') ? 'active' : '' ?>">
                <a href="resources.php"><i class="fa-solid fa-book"></i> Learning Resources</a>
            </li>
            <li class="<?php echo ($currentPage == 'admin_feedback') ? 'active' : '' ?>">
                <a href="feedback.php"><i class="fa-solid fa-comments"></i> User Feedback</a>
            </li>
            <li class="<?php echo ($currentPage == 'admin_reports') ? 'active' : '' ?>">
                <a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a>
            </li>
            <li class="<?php echo ($currentPage == 'admin_settings') ? 'active' : '' ?>">
                <a href="settings.php"><i class="fa-solid fa-cog"></i> Settings</a>
            </li>
            <li><a href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>
</aside>
