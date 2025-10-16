<?php
require_once 'session_check.php';
require_once '../config/database.php';

// Get parameters
$resource_type = $_GET['type'] ?? '';
$resource_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!in_array($resource_type, ['note', 'ebook', 'pastpaper']) || !$resource_id) {
    http_response_code(400);
    die('Invalid request');
}

try {
    // Determine table
    $table = $resource_type . 's';
    
    // Fetch resource
    $stmt = $pdo->prepare("SELECT filepath, title FROM $table WHERE id = ?");
    $stmt->execute([$resource_id]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resource) {
        http_response_code(404);
        die('Resource not found');
    }
    
    $file_path = '../' . $resource['filepath'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('File not found');
    }
    
    // Increment download count
    $stmt = $pdo->prepare("UPDATE $table SET downloads = downloads + 1 WHERE id = ?");
    $stmt->execute([$resource_id]);
    
    // Get file info
    $file_name = basename($file_path);
    $file_size = filesize($file_path);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Set content type
    $content_types = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
    ];
    
    $content_type = $content_types[$file_ext] ?? 'application/octet-stream';
    
    // Clean filename for download
    $download_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $resource['title']) . '.' . $file_ext;
    
    // Send headers
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    
    // Output file
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error');
}
