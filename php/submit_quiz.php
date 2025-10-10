<?php
// php/submit_quiz.php

require_once 'session_check.php';
require_once '../config/database.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Get Submitted Data ---
    $quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
    $user_answers = isset($_POST['answers']) ? $_POST['answers'] : [];
    $user_id = $_SESSION['id'];
    
    if (!$quiz_id || empty($user_answers)) {
        // Redirect if data is missing
        header('Location: ../quizzes.php');
        exit;
    }

    // --- Fetch Correct Answers ---
    $question_ids = array_keys($user_answers);
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    
    $sql = "SELECT question_id, answer_id FROM answers WHERE is_correct = 1 AND question_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($question_ids);
    $correct_answers_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Re-format correct answers for easy lookup: [question_id => correct_answer_id]
    $correct_answers = [];
    foreach ($correct_answers_raw as $row) {
        $correct_answers[$row['question_id']] = $row['answer_id'];
    }
    
    // --- Calculate Score ---
    $score = 0;
    foreach ($user_answers as $question_id => $submitted_answer_id) {
        if (isset($correct_answers[$question_id]) && $correct_answers[$question_id] == $submitted_answer_id) {
            $score++;
        }
    }

    $total_questions = count($question_ids);
    $percentage_score = ($total_questions > 0) ? ($score / $total_questions) * 100 : 0;
    
    // --- Save the Attempt to the Database ---
    $sql_insert = "INSERT INTO quiz_attempts (user_id, quiz_id, score) VALUES (?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([$user_id, $quiz_id, $percentage_score]);
    
    // Get the ID of the attempt we just saved
    $attempt_id = $pdo->lastInsertId();
    
    // --- Redirect to the Results Page ---
    header("Location: ../quiz_result.php?attempt_id=" . $attempt_id);
    exit;

} else {
    // Redirect if not a POST request
    header('Location: ../quizzes.php');
    exit;
}