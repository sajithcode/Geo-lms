<?php
// php/submit_quiz.php

require_once 'session_check.php';
require_once '../config/database.php';
require_once 'csrf.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    csrf_validate_or_redirect('../pages/quizzes.php');
    
    // --- Get Submitted Data ---
    $quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
    $user_answers = isset($_POST['answers']) ? $_POST['answers'] : [];
    $time_spent = filter_input(INPUT_POST, 'time_spent', FILTER_VALIDATE_INT) ?? 0;
    $user_id = $_SESSION['user_id'];
    
    if (!$quiz_id || empty($user_answers)) {
        // Redirect if data is missing
        header('Location: ../pages/quizzes.php');
        exit;
    }

    // Clear quiz start time from session
    unset($_SESSION['quiz_start_time_' . $quiz_id]);

    // --- Check for question_type column (defensive) ---
    $columns = $pdo->query("SHOW COLUMNS FROM questions LIKE 'question_type'")->fetchAll();
    $has_question_type = count($columns) > 0;

    // --- Fetch Questions with Types ---
    $question_ids = array_keys($user_answers);
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    
    $question_type_select = $has_question_type ? ", question_type, points" : "";
    $sql_questions = "SELECT question_id{$question_type_select} FROM questions WHERE question_id IN ($placeholders)";
    $stmt_questions = $pdo->prepare($sql_questions);
    $stmt_questions->execute($question_ids);
    $questions_data = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);
    
    $questions_info = [];
    foreach ($questions_data as $q) {
        $questions_info[$q['question_id']] = [
            'type' => $has_question_type ? ($q['question_type'] ?? 'single') : 'single',
            'points' => $has_question_type ? ($q['points'] ?? 1) : 1
        ];
    }

    // --- Fetch Correct Answers (handle multiple correct answers) ---
    $sql = "SELECT question_id, answer_id, answer_text FROM answers WHERE is_correct = 1 AND question_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($question_ids);
    $correct_answers_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Re-format correct answers: [question_id => [answer_ids]] for multiple choice support
    $correct_answers = [];
    foreach ($correct_answers_raw as $row) {
        if (!isset($correct_answers[$row['question_id']])) {
            $correct_answers[$row['question_id']] = [];
        }
        $correct_answers[$row['question_id']][] = $row['answer_id'];
        // Also store text for fill-in-blank comparison
        $correct_answers[$row['question_id'] . '_text'][] = strtolower(trim($row['answer_text']));
    }
    
    // --- Calculate Score (weighted by points) ---
    $total_points = 0;
    $earned_points = 0;
    $correct_count = 0;
    
    foreach ($user_answers as $question_id => $submitted_answer) {
        $question_type = $questions_info[$question_id]['type'] ?? 'single';
        $question_points = $questions_info[$question_id]['points'] ?? 1;
        $total_points += $question_points;
        
        $is_correct = false;
        
        if ($question_type === 'multiple') {
            // Multiple choice - check if all selected answers are correct
            $submitted_array = is_array($submitted_answer) ? $submitted_answer : [$submitted_answer];
            $correct_array = $correct_answers[$question_id] ?? [];
            
            sort($submitted_array);
            sort($correct_array);
            
            if ($submitted_array == $correct_array) {
                $is_correct = true;
            }
            
        } elseif ($question_type === 'fill_blank') {
            // Fill in the blank - case-insensitive comparison
            $submitted_text = strtolower(trim($submitted_answer));
            $correct_texts = $correct_answers[$question_id . '_text'] ?? [];
            
            if (in_array($submitted_text, $correct_texts)) {
                $is_correct = true;
            }
            
        } else {
            // Single choice
            $correct_answer_id = $correct_answers[$question_id][0] ?? null;
            if ($correct_answer_id && $correct_answer_id == $submitted_answer) {
                $is_correct = true;
            }
        }
        
        if ($is_correct) {
            $earned_points += $question_points;
            $correct_count++;
        }
    }

    $total_questions = count($question_ids);
    $percentage_score = ($total_points > 0) ? ($earned_points / $total_points) * 100 : 0;
    
    // --- Fetch passing score and check if passed ---
    $stmt_quiz = $pdo->prepare("SELECT passing_score FROM quizzes WHERE quiz_id = ?");
    $stmt_quiz->execute([$quiz_id]);
    $quiz_info = $stmt_quiz->fetch(PDO::FETCH_ASSOC);
    $passing_score = $quiz_info['passing_score'] ?? 50;
    $passed = $percentage_score >= $passing_score ? 1 : 0;
    
    // --- Check if passed column exists ---
    $columns_passed = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'passed'")->fetchAll();
    $has_passed = count($columns_passed) > 0;
    
    $columns_time = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'time_spent'")->fetchAll();
    $has_time_spent = count($columns_time) > 0;
    
    $columns_started = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'started_at'")->fetchAll();
    $has_started_at = count($columns_started) > 0;
    
    // --- Save the Attempt to the Database ---
    $sql_insert = "INSERT INTO quiz_attempts (user_id, quiz_id, score, correct_answers";
    $params = [$user_id, $quiz_id, $percentage_score, $correct_count];
    
    if ($has_passed) {
        $sql_insert .= ", passed";
        $params[] = $passed;
    }
    if ($has_time_spent) {
        $sql_insert .= ", time_spent";
        $params[] = $time_spent;
    }
    if ($has_started_at) {
        $sql_insert .= ", started_at";
        $params[] = date('Y-m-d H:i:s', time() - $time_spent);
    }
    
    $sql_insert .= ") VALUES (" . implode(',', array_fill(0, count($params), '?')) . ")";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute($params);
    
    // Get the ID of the attempt we just saved
    $attempt_id = $pdo->lastInsertId();
    
    // --- Create notification (if notifications table exists) ---
    try {
        $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'quiz_result', ?, ?, ?)");
        $notif_title = $passed ? "Quiz Passed!" : "Quiz Completed";
        $notif_message = sprintf("You scored %.1f%% on the quiz. %s", $percentage_score, $passed ? "Congratulations!" : "Keep practicing!");
        $notif_link = "pages/quiz_result.php?attempt_id=" . $attempt_id;
        $stmt_notif->execute([$user_id, $notif_title, $notif_message, $notif_link]);
    } catch (PDOException $e) {
        // Notifications table doesn't exist yet, skip
    }
    
    // --- Redirect to the Results Page ---
    header("Location: ../pages/quiz_result.php?attempt_id=" . $attempt_id);
    exit;

} else {
    // Redirect if not a POST request
    header('Location: ../pages/quizzes.php');
    exit;
}