<?php
// config/database.php

// --- Database Credentials ---
// Replace with your actual database details
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'lms_db'); // Choose a name for your database

// --- Attempt to connect to MySQL database ---
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // If connection fails, stop the script and display an error
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>