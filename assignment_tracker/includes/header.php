<?php
require_once 'db.php';
if (!isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="navbar">
        <h1>📑 Assignment Tracker</h1>
        <div class="icons">
            <a href="home.php" class="icon-container" title="Home">
                <i class="fas fa-home"></i>
            </a>
            <a href="assignments.php" class="icon-container" title="Assignments">
                <i class="fas fa-tasks"></i>
            </a>
            <a href="profile.php" class="icon-container" title="Profile">
                <i class="fas fa-user"></i>
            </a>
            <a href="logout.php" class="icon-container" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>