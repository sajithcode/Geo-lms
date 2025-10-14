<?php
$currentPage = 'resources';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

include 'includes/header.php';
?>
<script>document.title = 'Past Papers - Self-Learning Hub';</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/pastpapers.css">

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Past Papers</h1>
            <p>Previous year exam papers for practice and revision.</p>
        </header>

        <div class="lr-content">
            <div class="notes-list">
                <?php
                $dir = __DIR__ . '/assets/pastpapers';
                $files = [];
                if (is_dir($dir)) {
                    $patterns = ['/*.pdf','/*.zip'];
                    foreach ($patterns as $p) {
                        $found = glob($dir . $p);
                        if ($found) $files = array_merge($files, $found);
                    }
                }

                if (!empty($files)) {
                    echo '<div class="resource-cards">';
                    foreach ($files as $filePath) {
                        $fileName = basename($filePath);
                        $fileUrl = 'assets/pastpapers/' . rawurlencode($fileName);
                        $sizeKb = round(filesize($filePath) / 1024, 1) . ' KB';
                        echo '<div class="note-card">';
                        echo '<div class="note-info"><strong>' . htmlspecialchars($fileName) . '</strong><div class="note-meta">' . htmlspecialchars($sizeKb) . '</div></div>';
                        echo '<div class="note-actions"><a class="btn" href="' . htmlspecialchars($fileUrl) . '" download>Download</a></div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>No past papers are available yet. Upload files to <code>assets/pastpapers/</code>.</p>';
                }
                ?>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
<?php
$currentPage = 'resources';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

include 'includes/header.php';
?>
<script>document.title = 'Past Papers - Self-Learning Hub';</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/pastpapers.css">

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Past Papers</h1>
            <p>Previous year exam papers for practice and revision.</p>
        </header>

        <div class="lr-content">
            <div class="notes-list">
                <?php
                $dir = __DIR__ . '/assets/pastpapers';
                $files = [];
                if (is_dir($dir)) {
                    $patterns = ['/*.pdf','/*.zip'];
                    foreach ($patterns as $p) {
                        $found = glob($dir . $p);
                        if ($found) $files = array_merge($files, $found);
                    }
                }

                if (!empty($files)) {
                    echo '<div class="resource-cards">';
                    foreach ($files as $filePath) {
                        $fileName = basename($filePath);
                        $fileUrl = 'assets/pastpapers/' . rawurlencode($fileName);
                        $sizeKb = round(filesize($filePath) / 1024, 1);
                        echo "<div class=\"note-card\">";
                        echo "<div class=\"note-info\"><strong>" . htmlspecialchars($fileName) . "</strong><div class=\"note-meta\">{$sizeKb} KB</div></div>";
                        echo "<div class=\"note-actions\"><a class=\"btn\" href=\"{$fileUrl}\" download>Download</a></div>";
                        echo "</div>";
                    }
                    echo '</div>';
                } else {
                    echo '<p>No past papers are available yet. Upload files to <code>assets/pastpapers/</code>.</p>';
                }
                ?>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
