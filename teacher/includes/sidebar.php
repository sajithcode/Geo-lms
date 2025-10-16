<?php
// teacher/includes/sidebar.php
// The $currentPage variable is set on each page that includes this sidebar
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h3>Geomatics LMS</h3>
        <p style="font-size: 0.85rem; margin: 5px 0 0; opacity: 0.8;">Teacher Portal</p>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($currentPage == 'teacher_dashboard') ? 'active' : '' ?>">
                <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </li>
            <li class="<?php echo ($currentPage == 'teacher_quizzes') ? 'active' : '' ?>">
                <a href="quizzes.php"><i class="fa-solid fa-puzzle-piece"></i> Manage Quizzes</a>
            </li>
            <li class="<?php echo ($currentPage == 'teacher_resources') ? 'active' : '' ?>">
                <a href="resources.php"><i class="fa-solid fa-book"></i> Learning Resources</a>
            </li>
            <li class="<?php echo ($currentPage == 'teacher_students') ? 'active' : '' ?>">
                <a href="students.php"><i class="fa-solid fa-users"></i> Students</a>
            </li>
            <li class="<?php echo ($currentPage == 'teacher_performance') ? 'active' : '' ?>">
                <a href="performance.php"><i class="fa-solid fa-chart-line"></i> Performance</a>
            </li>
            <li class="<?php echo ($currentPage == 'teacher_interaction') ? 'active' : '' ?>">
                <a href="interaction.php"><i class="fa-solid fa-comments"></i> Interaction</a>
            </li>
            <li class="<?php echo ($currentPage == 'teacher_settings') ? 'active' : '' ?>">
                <a href="settings.php"><i class="fa-solid fa-cog"></i> Settings</a>
            </li>
            <li><a href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>
</aside>
```