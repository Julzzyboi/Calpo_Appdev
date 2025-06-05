<?php
session_start();
include '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get all roles
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT r.*, 
              (SELECT COUNT(*) FROM tbl_users WHERE role_id = r.role_id) as users_count 
              FROM tbl_roles r";
    $result = $conn->query($query);
    
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
    
    echo json_encode($roles);
}

// Create new role
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = $_POST['role_name'];
    $description = $_POST['description'];
    
    $stmt = $conn->prepare("INSERT INTO tbl_roles (role_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $role_name, $description);
    
    if ($stmt->execute()) {
        // Log the activity
        $user_id = $_SESSION['user_id'];
        $action = "Created new role: $role_name";
        $stmt = $conn->prepare("INSERT INTO tbl_activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create role']);
    }
}

// Update role
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $_PUT);
    
    $role_id = $_PUT['role_id'];
    $role_name = $_PUT['role_name'];
    $description = $_PUT['description'];
    
    $stmt = $conn->prepare("UPDATE tbl_roles SET role_name = ?, description = ? WHERE role_id = ?");
    $stmt->bind_param("ssi", $role_name, $description, $role_id);
    
    if ($stmt->execute()) {
        // Log the activity
        $user_id = $_SESSION['user_id'];
        $action = "Updated role: $role_name";
        $stmt = $conn->prepare("INSERT INTO tbl_activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update role']);
    }
}

// Delete role
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    $role_id = $_DELETE['role_id'];
    
    // Check if role is in use
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_users WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete role that is in use']);
        exit();
    }
    
    // Get role info for logging
    $stmt = $conn->prepare("SELECT role_name FROM tbl_roles WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM tbl_roles WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    
    if ($stmt->execute()) {
        // Log the activity
        $user_id = $_SESSION['user_id'];
        $action = "Deleted role: " . $role['role_name'];
        $stmt = $conn->prepare("INSERT INTO tbl_activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete role']);
    }
} 