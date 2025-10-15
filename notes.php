<?php
$currentPage = 'resources';

// Allow guests to view notes (do not force login)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

include 'includes/header.php';
?>
<script>document.title = 'Notes - Self-Learning Hub';</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/notes.css">

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Browse Notes</h1>
            <p>Subject-wise notes available for download.</p>
        </header>

        <div class="lr-content">
            <div class="notes-list">
                <?php
                // Try DB first
                $dbNotes = [];
                try {
                    $stmt = $pdo->query("SELECT id, title, filename, filepath, filesize, uploaded_by, created_at FROM notes ORDER BY created_at DESC");
                    $dbNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $dbNotes = [];
                }

                if (!empty($dbNotes)) {
                    echo '<div class="resource-cards">';
                    foreach ($dbNotes as $row) {
                        $title = !empty($row['title']) ? $row['title'] : $row['filename'];
                        $fileUrl = !empty($row['filepath']) ? $row['filepath'] : (!empty($row['filename']) ? 'assets/notes/' . rawurlencode($row['filename']) : '');
                        $sizeKb = '';
                        if (!empty($row['filesize']) && is_numeric($row['filesize'])) {
                            $sizeKb = round($row['filesize'] / 1024, 1) . ' KB';
                        }
                        echo '<div class="note-card">';
                        echo '<div class="note-info"><strong>' . htmlspecialchars($title) . '</strong>' . ($sizeKb ? '<div class="note-meta">' . htmlspecialchars($sizeKb) . '</div>' : '') . '</div>';
                        if ($fileUrl) echo '<div class="note-actions"><a class="btn" href="' . htmlspecialchars($fileUrl) . '" download>Download</a></div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    // Fallback to filesystem
                    $notesDir = __DIR__ . '/assets/notes';
                    $notes = [];
                    if (is_dir($notesDir)) {
                        $patterns = ['/*.pdf', '/*.docx', '/*.pptx', '/*.zip'];
                        foreach ($patterns as $pat) {
                            $found = glob($notesDir . $pat);
                            if ($found) $notes = array_merge($notes, $found);
                        }
                    }

                    if (!empty($notes)) {
                        echo '<div class="resource-cards">';
                        foreach ($notes as $filePath) {
                            $fileName = basename($filePath);
                            $fileUrl = 'assets/notes/' . rawurlencode($fileName);
                            $sizeKb = round(filesize($filePath) / 1024, 1) . ' KB';
                            echo '<div class="note-card">';
                            echo '<div class="note-info"><strong>' . htmlspecialchars($fileName) . '</strong><div class="note-meta">' . htmlspecialchars($sizeKb) . '</div></div>';
                            echo '<div class="note-actions"><a class="btn" href="' . htmlspecialchars($fileUrl) . '" download>Download</a></div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p>No notes are available yet. Upload files to <code>assets/notes/</code>.</p>';
                    }
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
<script>document.title = 'Notes - Self-Learning Hub';</script>


<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <h1>Browse Notes</h1>
            <p>Subject-wise notes available for download.</p>
        </header>

        <div class="lr-content">
            <div class="notes-list">
                <?php
                // Try to fetch notes from the database first (table: notes)
                $dbNotes = [];
                try {
                    $stmt = $pdo->query("SELECT id, title, filename, filepath, filesize, uploaded_by, created_at FROM notes ORDER BY created_at DESC");
                    $dbNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    // Table might not exist or other DB error - fall back to filesystem listing
                    $dbNotes = [];
                }

                if (!empty($dbNotes)) {
                    echo '<div class="resource-cards">';
                    foreach ($dbNotes as $row) {
                        // Prefer explicit filepath from DB; otherwise build from filename
                        $title = !empty($row['title']) ? $row['title'] : $row['filename'];
                        $fileUrl = '';
                        if (!empty($row['filepath'])) {
                            $fileUrl = $row['filepath'];
                        } elseif (!empty($row['filename'])) {
                            $fileUrl = 'assets/notes/' . rawurlencode($row['filename']);
                        }
                        // Determine filesize display: use DB value if present, otherwise try to read file
                        $sizeKb = '';
                        if (!empty($row['filesize']) && is_numeric($row['filesize'])) {
                            $sizeKb = round($row['filesize'] / 1024, 1) . ' KB';
                        } else {
                            $absPath = __DIR__ . '/' . ltrim($fileUrl, '/');
                            if (file_exists($absPath)) {
                                $sizeKb = round(filesize($absPath) / 1024, 1) . ' KB';
                            }
                        }

                        echo "<div class=\"note-card\">";
                        echo "<div class=\"note-info\"><strong>" . htmlspecialchars($title) . "</strong>";
                        if ($sizeKb) echo "<div class=\"note-meta\">{$sizeKb}</div>";
                        echo "</div>";
                        if ($fileUrl) {
                            // if filepath is not an absolute URL, ensure it's safe for the href
                            $href = preg_match('#^https?://#i', $fileUrl) ? $fileUrl : $fileUrl;
                            echo "<div class=\"note-actions\"> <a class=\"btn\" href=\"{$href}\" download>Download</a></div>";
                        }
                        echo "</div>";
                    }
                    echo '</div>';
                } else {
                    // Fallback: scan assets/notes directory
                    $notesDir = __DIR__ . '/assets/notes';
                    $notes = [];
                    if (is_dir($notesDir)) {
                        $patterns = ['/*.pdf', '/*.docx', '/*.pptx', '/*.zip'];
                        foreach ($patterns as $pat) {
                            $found = glob($notesDir . $pat);
                            if ($found) $notes = array_merge($notes, $found);
                        }
                    }

                    if (!empty($notes)) {
                        echo '<div class="resource-cards">';
                        foreach ($notes as $filePath) {
                            $fileName = basename($filePath);
                            $fileUrl = 'assets/notes/' . rawurlencode($fileName);
                            $size = filesize($filePath);
                            $sizeKb = round($size / 1024, 1) . ' KB';
                            echo "<div class=\"note-card\">";
                            echo "<div class=\"note-info\"><strong>" . htmlspecialchars($fileName) . "</strong><div class=\"note-meta\">{$sizeKb}</div></div>";
                            echo "<div class=\"note-actions\"> <a class=\"btn\" href=\"{$fileUrl}\" download>Download</a></div>";
                            echo "</div>";
                        }
                        echo '</div>';
                    } else {
                        echo '<p>No notes are available yet. Upload files to <code>assets/notes/</code> (pdf, docx, pptx) or add them via the admin upload.</p>';
                    }
                }
                ?>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
