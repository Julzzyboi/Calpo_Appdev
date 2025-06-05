<?php
session_start();
include 'config.php';

// Check if user is logged in before logging out
if (isset($_SESSION['user_id'])) {
    // Log the logout activity
    $log_user_id = $_SESSION['user_id'];
    $log_action = "User logged out";
    
    $log_stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
    $log_stmt->bind_param("is", $log_user_id, $log_action);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();
}

// Redirect to login page
header("location: login.php");
exit();
?> 