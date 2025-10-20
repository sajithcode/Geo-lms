-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2025 at 09:09 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `check_quiz_pass` (IN `p_attempt_id` INT, OUT `p_passed` BOOLEAN)  BEGIN
    DECLARE v_score DECIMAL(5,2);
    DECLARE v_passing_score DECIMAL(5,2);
    
    SELECT qa.score, q.passing_score
    INTO v_score, v_passing_score
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.quiz_id
    WHERE qa.attempt_id = p_attempt_id;
    
    SET p_passed = (v_score >= v_passing_score);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('general','academic','event','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `published_by` int(11) DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'published',
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `category`, `priority`, `published_by`, `status`, `published_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to Geo-LMS', 'Welcome to the Geomatics Learning Management System. We are excited to have you here!', 'general', 'high', 1, 'published', '2025-10-15 06:19:59', NULL, '2025-10-15 06:19:59', '2025-10-15 06:19:59'),
(2, 'New Quiz Available', 'A new quiz on Remote Sensing has been added. Test your knowledge now!', 'academic', 'medium', 2, 'published', '2025-10-15 06:19:59', NULL, '2025-10-15 06:19:59', '2025-10-15 06:19:59'),
(3, 'fsdf', 'sdfsdf', 'general', 'low', 2, 'published', '2025-10-16 05:47:38', NULL, '2025-10-16 05:47:38', '2025-10-16 05:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `order_number` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`answer_id`, `question_id`, `answer_text`, `is_correct`, `order_number`, `created_at`) VALUES
(1, 1, 'Geographic Information System', 1, 1, '2025-10-15 06:19:59'),
(2, 1, 'Global Internet Service', 0, 2, '2025-10-15 06:19:59'),
(3, 1, 'Geological Imaging Software', 0, 3, '2025-10-15 06:19:59'),
(4, 1, 'General Information System', 0, 4, '2025-10-15 06:19:59'),
(5, 2, 'Point', 1, 1, '2025-10-15 06:19:59'),
(6, 2, 'Raster', 0, 2, '2025-10-15 06:19:59'),
(7, 2, 'DEM', 0, 3, '2025-10-15 06:19:59'),
(8, 2, 'Grid', 0, 4, '2025-10-15 06:19:59'),
(9, 3, 'True', 0, 1, '2025-10-15 06:19:59'),
(10, 3, 'False', 1, 2, '2025-10-15 06:19:59'),
(11, 4, 'Analyzing spatial patterns and land use', 1, 1, '2025-10-15 06:19:59'),
(12, 4, 'Creating music playlists', 0, 2, '2025-10-15 06:19:59'),
(13, 4, 'Designing websites', 0, 3, '2025-10-15 06:19:59'),
(14, 4, 'Writing documents', 0, 4, '2025-10-15 06:19:59'),
(15, 5, 'vcbcv', 0, 0, '2025-10-15 22:11:26'),
(16, 5, 'cvbcvbv', 1, 0, '2025-10-15 22:11:26'),
(17, 5, 'cvbcv', 0, 0, '2025-10-15 22:11:26'),
(18, 5, 'cvb', 0, 0, '2025-10-15 22:11:26'),
(19, 6, 'cxb', 1, 0, '2025-10-15 22:11:42'),
(20, 6, 'xcxc', 0, 0, '2025-10-15 22:11:42'),
(21, 7, 'vfvdf', 1, 0, '2025-10-15 22:22:44'),
(22, 7, 'vdvfd', 0, 0, '2025-10-15 22:22:44'),
(23, 8, 'dfvfd', 0, 0, '2025-10-15 22:22:57'),
(24, 8, 'fvdf', 1, 0, '2025-10-15 22:22:57'),
(25, 9, 'xcv', 1, 0, '2025-10-16 03:41:05'),
(26, 9, 'cxvxc', 0, 0, '2025-10-16 03:41:06'),
(27, 10, 'fgdf', 1, 0, '2025-10-16 03:42:05'),
(28, 10, 'dfg', 0, 0, '2025-10-16 03:42:05'),
(29, 11, 'dfg', 0, 0, '2025-10-16 03:42:14'),
(30, 11, 'dfg', 1, 0, '2025-10-16 03:42:14');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `certificate_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `certificate_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ebooks`
--

CREATE TABLE `ebooks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filepath` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filesize` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `email_id` int(11) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text DEFAULT NULL,
  `status` enum('pending','sent','failed','logged') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('new','read','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `admin_response` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`feedback_id`, `user_id`, `message`, `status`, `admin_response`, `created_at`, `updated_at`) VALUES
(1, 3, 'Great platform! The quizzes are very helpful for learning.', 'new', NULL, '2025-10-15 06:19:59', '2025-10-15 06:19:59'),
(2, 1, 'cvbcf', 'new', NULL, '2025-10-15 20:59:49', '2025-10-15 20:59:49'),
(3, 3, 'hello this is my feedback', 'new', NULL, '2025-10-15 23:11:06', '2025-10-15 23:11:06');

-- --------------------------------------------------------

--
-- Table structure for table `forums`
--

CREATE TABLE `forums` (
  `forum_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','locked','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forums`
--

INSERT INTO `forums` (`forum_id`, `name`, `description`, `category`, `status`, `created_by`, `created_at`) VALUES
(1, 'General Discussion', 'General topics related to Geomatics', 'General', 'active', 1, '2025-10-15 06:19:59'),
(2, 'GIS Questions', 'Ask questions about GIS technology', 'Technical', 'active', 2, '2025-10-15 06:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE `forum_posts` (
  `post_id` int(11) NOT NULL,
  `forum_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_posts`
--

INSERT INTO `forum_posts` (`post_id`, `forum_id`, `user_id`, `title`, `content`, `is_pinned`, `is_locked`, `views`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'Introduction to the course', 'Hi everyone! Looking forward to learning with you all.', 0, 0, 15, '2025-10-15 06:19:59', '2025-10-15 06:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE `forum_replies` (
  `reply_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_replies`
--

INSERT INTO `forum_replies` (`reply_id`, `post_id`, `user_id`, `content`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Welcome! Feel free to ask any questions.', '2025-10-15 06:19:59', '2025-10-15 06:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `parent_message_id` int(11) DEFAULT NULL COMMENT 'For threaded conversations',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `subject`, `message`, `is_read`, `parent_message_id`, `created_at`, `read_at`) VALUES
(1, 3, 2, 'dgdf', 'fgdfg', 0, NULL, '2025-10-16 05:49:20', NULL),
(2, 3, 2, 'dgdf', 'dfgd', 0, NULL, '2025-10-16 05:49:34', NULL),
(3, 3, 2, 'dgdf', 'dfgd', 0, NULL, '2025-10-16 05:51:12', NULL),
(4, 3, 2, 'df', 'dfd', 0, NULL, '2025-10-16 05:53:01', NULL),
(5, 2, 3, 'fghfg', 'fbfgb', 1, NULL, '2025-10-16 06:03:22', '2025-10-16 09:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filepath` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filesize` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `title`, `description`, `filename`, `filepath`, `filesize`, `file_type`, `category`, `uploaded_by`, `downloads`, `created_at`, `updated_at`) VALUES
(1, 'cvbcb', 'cvb', 'FINAL.pdf', 'uploads/notes/68f070b2da83c_1760587954.pdf', 1244551, 'pdf', '', 2, 0, '2025-10-16 04:12:34', '2025-10-16 04:12:34'),
(2, 'cvbcb', 'cvb', 'FINAL.pdf', 'uploads/notes/68f070be77999_1760587966.pdf', 1244551, 'pdf', '', 2, 0, '2025-10-16 04:12:46', '2025-10-16 04:12:46'),
(3, 'ffg', 'xccx', 'IS 1107 lesson 3.pdf', 'uploads/notes/68f07233bddf1_1760588339.pdf', 502420, 'pdf', 'Physics', 2, 0, '2025-10-16 04:18:59', '2025-10-16 04:18:59'),
(4, 'tft', 'ftf', 'IS 1107 lesson 3.pdf', 'uploads/notes/68f07377f1465_1760588663.pdf', 502420, 'pdf', 'Geography', 2, 0, '2025-10-16 04:24:23', '2025-10-16 04:24:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('info','success','warning','error') COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`, `read_at`) VALUES
(1, 3, 'Welcome!', 'Welcome to Geo-LMS. Start learning today!', 'success', 'dashboard.php', 0, '2025-10-15 06:19:59', NULL),
(2, 3, 'New Quiz Available', 'Check out the new Remote Sensing quiz', 'info', 'quizzes.php', 0, '2025-10-15 06:19:59', NULL),
(3, 1, 'Quiz Completed', 'You scored 0.0% on the quiz. Keep practicing!', '', 'pages/quiz_result.php?attempt_id=4', 0, '2025-10-15 22:49:05', NULL),
(4, 1, 'Quiz Completed', 'You scored 0.0% on the quiz. Keep practicing!', '', 'pages/quiz_result.php?attempt_id=5', 0, '2025-10-15 22:49:08', NULL),
(5, 1, 'Quiz Completed', 'You scored 25.0% on the quiz. Keep practicing!', '', 'pages/quiz_result.php?attempt_id=6', 0, '2025-10-15 22:50:06', NULL),
(6, 1, 'Quiz Passed!', 'You scored 75.0% on the quiz. Congratulations!', '', 'pages/quiz_result.php?attempt_id=7', 0, '2025-10-15 22:54:21', NULL),
(7, 3, 'Quiz Completed', 'You scored 0.0% on the quiz. Keep practicing!', '', 'pages/quiz_result.php?attempt_id=8', 0, '2025-10-16 04:00:03', NULL),
(8, 2, 'New message from student1', 'fgdfg', '', 'pages/messages.php', 0, '2025-10-16 05:49:20', NULL),
(9, 2, 'New message from student1', 'dfgd', '', 'pages/messages.php', 0, '2025-10-16 05:49:34', NULL),
(10, 2, 'New message from student1', 'dfgd', '', 'pages/messages.php', 0, '2025-10-16 05:51:12', NULL),
(11, 2, 'New message from student1', 'dfd', '', 'pages/messages.php', 0, '2025-10-16 05:53:01', NULL),
(12, 3, 'New message from teacher1', 'fbfgb', '', 'pages/messages.php', 0, '2025-10-16 06:03:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_quiz_results` tinyint(1) DEFAULT 1,
  `email_announcements` tinyint(1) DEFAULT 1,
  `email_messages` tinyint(1) DEFAULT 1,
  `email_resources` tinyint(1) DEFAULT 1,
  `inapp_quiz_results` tinyint(1) DEFAULT 1,
  `inapp_announcements` tinyint(1) DEFAULT 1,
  `inapp_messages` tinyint(1) DEFAULT 1,
  `inapp_resources` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pastpapers`
--

CREATE TABLE `pastpapers` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filepath` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filesize` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pastpapers`
--

INSERT INTO `pastpapers` (`id`, `title`, `year`, `semester`, `subject`, `description`, `filename`, `filepath`, `filesize`, `file_type`, `uploaded_by`, `downloads`, `created_at`, `updated_at`) VALUES
(1, 'fd', 2025, 'dfs', NULL, 'sdfds', 'IS 1107 lesson 3.pdf', 'uploads/pastpapers/68f0745b2fb4d_1760588891.pdf', 502420, 'pdf', 2, 0, '2025-10-16 04:28:11', '2025-10-16 04:28:11');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') COLLATE utf8mb4_unicode_ci DEFAULT 'multiple_choice',
  `points` decimal(5,2) DEFAULT 1.00,
  `explanation` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_number` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `quiz_id`, `question_text`, `question_type`, `points`, `explanation`, `image_url`, `order_number`, `created_at`) VALUES
(1, 1, 'What does GIS stand for?', 'multiple_choice', '1.00', NULL, NULL, 1, '2025-10-15 06:19:59'),
(2, 1, 'Which of the following is a vector data type?', 'multiple_choice', '1.00', NULL, NULL, 2, '2025-10-15 06:19:59'),
(3, 1, 'GIS can only work with spatial data.', 'true_false', '1.00', NULL, NULL, 3, '2025-10-15 06:19:59'),
(4, 1, 'What is the primary use of GIS in urban planning?', 'multiple_choice', '1.00', NULL, NULL, 4, '2025-10-15 06:19:59'),
(5, 6, 'vbcvb', '', '1.00', 'cvbcvb', NULL, 0, '2025-10-15 22:11:26'),
(6, 6, 'vbcbcb', '', '1.00', 'cxbxc', NULL, 0, '2025-10-15 22:11:42'),
(7, 6, 'fdff', '', '1.00', '', NULL, 0, '2025-10-15 22:22:44'),
(8, 6, 'vvv', '', '1.00', 'f', NULL, 0, '2025-10-15 22:22:57'),
(9, 8, 'fvc', '', '1.00', 'xcvxc', NULL, 0, '2025-10-16 03:41:05'),
(10, 9, 'dfgfd', '', '1.00', 'dfgdf', NULL, 0, '2025-10-16 03:42:05'),
(11, 9, 'dfgdf', '', '1.00', '', NULL, 0, '2025-10-16 03:42:14');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `quiz_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `time_limit` int(11) DEFAULT NULL COMMENT 'Time limit in minutes, NULL = no limit',
  `retry_limit` int(11) DEFAULT NULL COMMENT 'Max attempts allowed',
  `is_active` tinyint(1) DEFAULT 1,
  `passing_score` decimal(5,2) DEFAULT 50.00 COMMENT 'Minimum percentage to pass',
  `randomize_questions` tinyint(1) DEFAULT 0,
  `randomize_answers` tinyint(1) DEFAULT 0,
  `show_answers_after` tinyint(1) DEFAULT 1,
  `show_results` tinyint(1) DEFAULT 1,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'published',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `category_id`, `title`, `description`, `difficulty`, `time_limit`, `retry_limit`, `is_active`, `passing_score`, `randomize_questions`, `randomize_answers`, `show_answers_after`, `show_results`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Introduction to GIS', 'Basic concepts and principles of Geographic Information Systems', 'medium', 30, NULL, 1, '70.00', 0, 0, 1, 1, 'published', 2, '2025-10-15 06:19:59', '2025-10-15 06:19:59'),
(2, NULL, 'Remote Sensing Fundamentals', 'Understanding satellite imagery and remote sensing techniques', 'medium', 45, NULL, 1, '60.00', 0, 0, 1, 1, 'published', 2, '2025-10-15 06:19:59', '2025-10-15 06:19:59'),
(4, 3, 'dfdg', 'dfsdfsdf', 'easy', 10, 2, 1, '60.00', 0, 0, 1, 1, 'published', 1, '2025-10-15 22:00:04', '2025-10-15 22:00:04'),
(5, 6, 'grtgrt', 'rtgrtgrt', 'easy', 10, 3, 1, '60.00', 0, 0, 1, 1, 'published', 1, '2025-10-15 22:00:26', '2025-10-15 22:00:26'),
(6, 3, 'fsdf', 'sdfsdf', 'easy', 19, 2, 1, '60.00', 0, 0, 1, 1, 'published', 1, '2025-10-15 22:02:32', '2025-10-15 22:02:32'),
(7, 3, 'fdf', 'dvdf', 'easy', 10, 0, 1, '60.00', 0, 0, 1, 1, 'published', 2, '2025-10-16 03:34:23', '2025-10-16 03:34:23'),
(8, 6, 'bvv', 'vcv', 'easy', 10, 3, 1, '60.00', 0, 0, 1, 1, 'published', 2, '2025-10-16 03:40:54', '2025-10-16 03:40:54'),
(9, 3, 'quiz 5', 'fgf', 'easy', 10, 5, 1, '60.00', 0, 0, 1, 1, 'published', 2, '2025-10-16 03:41:51', '2025-10-16 03:41:51'),
(10, 3, 'df', 'sfsd', 'easy', 10, 1, 1, '60.00', 0, 0, 1, 1, 'published', 2, '2025-10-16 03:50:40', '2025-10-16 03:50:40');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `attempt_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL COMMENT 'Score as percentage',
  `time_spent` int(11) DEFAULT 0 COMMENT 'Total time in seconds',
  `time_taken` int(11) DEFAULT NULL COMMENT 'Time taken in seconds',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('in_progress','completed','abandoned') COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `passed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`attempt_id`, `user_id`, `quiz_id`, `score`, `time_spent`, `time_taken`, `started_at`, `completed_at`, `status`, `passed`, `created_at`) VALUES
(1, 3, 1, '85.50', 0, 1200, '2025-10-15 06:19:59', '2025-10-15 06:19:59', 'completed', 1, '2025-10-15 20:51:35'),
(2, 3, 2, '72.00', 0, 1800, '2025-10-15 06:19:59', '2025-10-15 06:19:59', 'completed', 1, '2025-10-15 20:51:35'),
(3, 1, 1, '25.00', 0, NULL, '2025-10-15 13:06:55', NULL, 'completed', 0, '2025-10-15 20:51:35'),
(4, 1, 6, '0.00', 5, NULL, '2025-10-15 19:19:00', NULL, 'completed', 0, '2025-10-15 22:49:05'),
(5, 1, 6, '0.00', 1, NULL, '2025-10-15 19:19:07', NULL, 'completed', 0, '2025-10-15 22:49:08'),
(6, 1, 1, '25.00', 10, NULL, '2025-10-15 19:19:56', NULL, 'completed', 0, '2025-10-15 22:50:06'),
(7, 1, 1, '75.00', 7, NULL, '2025-10-15 19:24:14', NULL, 'completed', 1, '2025-10-15 22:54:21'),
(8, 3, 9, '0.00', 2, NULL, '2025-10-16 00:30:01', NULL, 'completed', 0, '2025-10-16 04:00:03');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_categories`
--

CREATE TABLE `quiz_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `quiz_categories`
--

INSERT INTO `quiz_categories` (`category_id`, `category_name`, `description`, `icon`, `created_at`) VALUES
(1, 'Geographic Information Systems', 'GIS theory, software, and applications', 'fa-map', '2025-10-15 20:43:25'),
(2, 'Remote Sensing', 'Satellite imagery, photogrammetry, and spatial analysis', 'fa-satellite', '2025-10-15 20:43:25'),
(3, 'Cartography', 'Map design, projection systems, and visualization', 'fa-map-marked-alt', '2025-10-15 20:43:25'),
(4, 'Surveying', 'Land surveying, geodesy, and spatial measurement', 'fa-ruler-combined', '2025-10-15 20:43:25'),
(5, 'Spatial Analysis', 'Spatial statistics and geospatial modeling', 'fa-chart-area', '2025-10-15 20:43:25'),
(6, 'General', 'Mixed topics and general knowledge', 'fa-book', '2025-10-15 20:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_question_timings`
--

CREATE TABLE `quiz_question_timings` (
  `timing_id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `time_spent` int(11) DEFAULT 0 COMMENT 'Time in seconds',
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Stand-in structure for view `quiz_statistics`
-- (See below for the actual view)
--
CREATE TABLE `quiz_statistics` (
`quiz_id` int(11)
,`title` varchar(255)
,`unique_students` bigint(21)
,`total_attempts` bigint(21)
,`average_score` decimal(9,6)
,`highest_score` decimal(5,2)
,`lowest_score` decimal(5,2)
,`question_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `recent_activity`
-- (See below for the actual view)
--
CREATE TABLE `recent_activity` (
`activity_type` varchar(12)
,`username` varchar(50)
,`activity_description` varchar(271)
,`activity_time` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `resource_categories`
--

CREATE TABLE `resource_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `resource_categories`
--

INSERT INTO `resource_categories` (`category_id`, `category_name`, `description`, `icon`, `created_at`) VALUES
(1, 'GIS Software', 'QGIS, ArcGIS, and other GIS tools', 'fa-laptop-code', '2025-10-15 20:43:25'),
(2, 'Remote Sensing', 'Satellite imagery and analysis', 'fa-satellite-dish', '2025-10-15 20:43:25'),
(3, 'Cartography', 'Map design and creation', 'fa-map', '2025-10-15 20:43:25'),
(4, 'Surveying', 'Land surveying techniques', 'fa-ruler', '2025-10-15 20:43:25'),
(5, 'Spatial Database', 'PostGIS, SpatiaLite, spatial SQL', 'fa-database', '2025-10-15 20:43:25'),
(6, 'General', 'General geomatics resources', 'fa-book', '2025-10-15 20:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `resource_ratings`
--

CREATE TABLE `resource_ratings` (
  `rating_id` int(11) NOT NULL,
  `resource_type` enum('note','ebook','pastpaper') NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_performance`
-- (See below for the actual view)
--
CREATE TABLE `student_performance` (
`user_id` int(11)
,`username` varchar(50)
,`full_name` varchar(100)
,`quizzes_taken` bigint(21)
,`average_score` decimal(9,6)
,`highest_score` decimal(5,2)
,`lowest_score` decimal(5,2)
,`total_attempts` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','teacher','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'student',
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `role`, `full_name`, `bio`, `profile_picture`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', 'admin@geolms.com', '$2y$10$dgQeFiavf1Zjv/zQrXVWAu1qlzALLLdI.RXeww/8ta/Js5AZdjgCO', 'admin', 'System Administrator', NULL, NULL, 'active', '2025-10-15 06:19:59', '2025-10-15 06:40:22', NULL),
(2, 'teacher1', 'teacher@geolms.com', '$2y$10$HSTCNFK.cANg7sXCyitlQueu3/n3LrqfbQ.SJnUUnFrFHmP2H3XVm', 'teacher', 'John Teacher', NULL, NULL, 'active', '2025-10-15 06:19:59', '2025-10-15 23:10:21', NULL),
(3, 'student1', 'student@geolms.com', '$2y$10$HSTCNFK.cANg7sXCyitlQueu3/n3LrqfbQ.SJnUUnFrFHmP2H3XVm', 'student', 'Jane Student', NULL, NULL, 'active', '2025-10-15 06:19:59', '2025-10-15 23:10:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_bookmarks`
--

CREATE TABLE `user_bookmarks` (
  `bookmark_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `resource_type` enum('note','ebook','pastpaper') NOT NULL,
  `resource_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `setting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `theme` enum('light','dark') COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `email_notifications` tinyint(1) DEFAULT 1,
  `push_notifications` tinyint(1) DEFAULT 1,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'UTC',
  `quiz_result_emails` tinyint(1) DEFAULT 1 COMMENT 'Send email on quiz completion',
  `announcement_emails` tinyint(1) DEFAULT 1 COMMENT 'Send email on new announcements',
  `message_emails` tinyint(1) DEFAULT 1 COMMENT 'Send email on new messages'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`setting_id`, `user_id`, `theme`, `language`, `email_notifications`, `push_notifications`, `timezone`, `quiz_result_emails`, `announcement_emails`, `message_emails`) VALUES
(1, 1, 'light', 'en', 1, 1, 'UTC', 1, 1, 1),
(2, 2, 'light', 'en', 1, 1, 'UTC', 1, 1, 1),
(3, 3, 'light', 'en', 1, 1, 'UTC', 1, 1, 1);

-- --------------------------------------------------------

--
-- Structure for view `quiz_statistics`
--
DROP TABLE IF EXISTS `quiz_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `quiz_statistics`  AS SELECT `q`.`quiz_id` AS `quiz_id`, `q`.`title` AS `title`, count(distinct `qa`.`user_id`) AS `unique_students`, count(`qa`.`attempt_id`) AS `total_attempts`, avg(`qa`.`score`) AS `average_score`, max(`qa`.`score`) AS `highest_score`, min(`qa`.`score`) AS `lowest_score`, count(distinct `ques`.`question_id`) AS `question_count` FROM ((`quizzes` `q` left join `quiz_attempts` `qa` on(`q`.`quiz_id` = `qa`.`quiz_id` and `qa`.`status` = 'completed')) left join `questions` `ques` on(`q`.`quiz_id` = `ques`.`quiz_id`)) GROUP BY `q`.`quiz_id`, `q`.`title` ;

-- --------------------------------------------------------

--
-- Structure for view `recent_activity`
--
DROP TABLE IF EXISTS `recent_activity`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recent_activity`  AS SELECT 'quiz_attempt' AS `activity_type`, `u`.`username` AS `username`, concat('Completed quiz: ',`q`.`title`) AS `activity_description`, `qa`.`completed_at` AS `activity_time` FROM ((`quiz_attempts` `qa` join `users` `u` on(`qa`.`user_id` = `u`.`user_id`)) join `quizzes` `q` on(`qa`.`quiz_id` = `q`.`quiz_id`)) WHERE `qa`.`status` = 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `student_performance`
--
DROP TABLE IF EXISTS `student_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_performance`  AS SELECT `u`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, count(distinct `qa`.`quiz_id`) AS `quizzes_taken`, avg(`qa`.`score`) AS `average_score`, max(`qa`.`score`) AS `highest_score`, min(`qa`.`score`) AS `lowest_score`, count(`qa`.`attempt_id`) AS `total_attempts` FROM (`users` `u` left join `quiz_attempts` `qa` on(`u`.`user_id` = `qa`.`user_id` and `qa`.`status` = 'completed')) WHERE `u`.`role` = 'student' GROUP BY `u`.`user_id`, `u`.`username`, `u`.`full_name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `published_by` (`published_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_published_at` (`published_at`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `idx_question_id` (`question_id`),
  ADD KEY `idx_is_correct` (`is_correct`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`certificate_id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `idx_certificate_code` (`certificate_code`);

--
-- Indexes for table `ebooks`
--
ALTER TABLE `ebooks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`email_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_recipient` (`recipient`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `forums`
--
ALTER TABLE `forums`
  ADD PRIMARY KEY (`forum_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `idx_forum_id` (`forum_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `parent_message_id` (`parent_message_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_receiver_id` (`receiver_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `pastpapers`
--
ALTER TABLE `pastpapers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_subject` (`subject`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_quiz_id` (`quiz_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_quiz_category` (`category_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_quiz_id` (`quiz_id`),
  ADD KEY `idx_completed_at` (`completed_at`);

--
-- Indexes for table `quiz_categories`
--
ALTER TABLE `quiz_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `unique_category_name` (`category_name`);

--
-- Indexes for table `quiz_question_timings`
--
ALTER TABLE `quiz_question_timings`
  ADD PRIMARY KEY (`timing_id`),
  ADD KEY `idx_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `resource_categories`
--
ALTER TABLE `resource_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `unique_category_name` (`category_name`);

--
-- Indexes for table `resource_ratings`
--
ALTER TABLE `resource_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_user_resource` (`resource_type`,`resource_id`,`user_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  ADD PRIMARY KEY (`bookmark_id`),
  ADD UNIQUE KEY `unique_user_bookmark` (`user_id`,`resource_type`,`resource_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ebooks`
--
ALTER TABLE `ebooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `email_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `forums`
--
ALTER TABLE `forums`
  MODIFY `forum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pastpapers`
--
ALTER TABLE `pastpapers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `quiz_categories`
--
ALTER TABLE `quiz_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `quiz_question_timings`
--
ALTER TABLE `quiz_question_timings`
  MODIFY `timing_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_categories`
--
ALTER TABLE `resource_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `resource_ratings`
--
ALTER TABLE `resource_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  MODIFY `bookmark_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`published_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `ebooks`
--
ALTER TABLE `ebooks`
  ADD CONSTRAINT `ebooks_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `forums`
--
ALTER TABLE `forums`
  ADD CONSTRAINT `forums_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `forum_posts_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`forum_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `forum_replies_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`message_id`) ON DELETE SET NULL;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `pastpapers`
--
ALTER TABLE `pastpapers`
  ADD CONSTRAINT `pastpapers_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quiz_category` FOREIGN KEY (`category_id`) REFERENCES `quiz_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_question_timings`
--
ALTER TABLE `quiz_question_timings`
  ADD CONSTRAINT `quiz_question_timings_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`attempt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_question_timings_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `resource_ratings`
--
ALTER TABLE `resource_ratings`
  ADD CONSTRAINT `resource_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  ADD CONSTRAINT `user_bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
