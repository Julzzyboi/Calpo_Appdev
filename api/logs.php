<?php
session_start();
include '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get all activity logs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT l.*, u.full_name, u.email 
              FROM tbl_activity_logs l 
              JOIN tbl_users u ON l.user_id = u.user_id 
              ORDER BY l.created_at DESC";
    $result = $conn->query($query);
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode($logs);
} 