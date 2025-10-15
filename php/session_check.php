<?php
// php/session_check.php

// Start the session
session_start();

// Check if the user is not logged in, if so then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/index.php");
    exit;
}
?>