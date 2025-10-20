<?php
$currentPage = 'admin_users';

require_once 'php/admin_session_check.php';
require_once '../config/database.php';

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Check if user_id is provided
if (!$user_id) {
    $_SESSION['error_message'] = 'User not found';
    header('Location: users.php');
    exit();
}

try {
    // Fetch user data first
    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = 'User not found';
        header('Location: users.php');
        exit();
    }
    
    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    $_SESSION['success_message'] = "User '{$user['username']}' has been deleted successfully!";
    
} catch (PDOException $e) {
    error_log("Error deleting user: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error deleting user. Please try again.';
}

header('Location: users.php');
exit();
?>
