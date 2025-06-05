<?php
session_start();
include '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get all users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT u.*, r.role_name, 
              (SELECT COUNT(*) FROM tbl_users WHERE role_id = u.role_id) as users_count 
              FROM tbl_users u 
              JOIN tbl_roles r ON u.role_id = r.role_id";
    $result = $conn->query($query);
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode($users);
}

// Create new user
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = $_POST['role_id'];
    
    $stmt = $conn->prepare("INSERT INTO tbl_users (full_name, email, password, role_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $full_name, $email, $password, $role_id);
    
    if ($stmt->execute()) {
        // Log the activity
        $user_id = $_SESSION['user_id'];
        $action = "Created new user: $full_name";
        $stmt = $conn->prepare("INSERT INTO tbl_activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
    }
}

// Update user
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $_PUT);
    
    $user_id = $_PUT['user_id'];
    $full_name = $_PUT['full_name'];
    $email = $_PUT['email'];
    $role_id = $_PUT['role_id'];
    $status = $_PUT['status'];
    
    $stmt = $conn->prepare("UPDATE tbl_users SET full_name = ?, email = ?, role_id = ?, status = ? WHERE user_id = ?");
    $stmt->bind_param("ssisi", $full_name, $email, $role_id, $status, $user_id);
    
    if ($stmt->execute()) {
        // Log the activity
        $user_id = $_SESSION['user_id'];
        $action = "Updated user: $full_name";
        $stmt = $conn->prepare("INSERT INTO tbl_activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
}

// Delete user
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    $user_id = $_DELETE['user_id'];
    
    // Get user info for logging
    $stmt = $conn->prepare("SELECT full_name FROM tbl_users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM tbl_users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Log the activity
        $user_id = $_SESSION['user_id'];
        $action = "Deleted user: " . $user['full_name'];
        $stmt = $conn->prepare("INSERT INTO tbl_activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
} 