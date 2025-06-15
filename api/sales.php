<?php
session_start();
include '../config.php';

// Debug session information
error_log("Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("Session role_name: " . (isset($_SESSION['role_name']) ? $_SESSION['role_name'] : 'not set'));

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Get sales data with product information
    $query = "SELECT 
                t.transaction_date,
                p.product_name,
                t.quantity_sold,
                (t.quantity_sold * p.price) as total_amount
              FROM tbl_transactions t
              JOIN tbl_products p ON t.product_id = p.product_id
              ORDER BY t.transaction_date DESC
              LIMIT 30"; // Get last 30 days of sales

    $result = $conn->query($query);
    
    if ($result) {
        $sales_data = [];
        while ($row = $result->fetch_assoc()) {
            $sales_data[] = [
                'transaction_date' => $row['transaction_date'],
                'product_name' => $row['product_name'],
                'quantity_sold' => $row['quantity_sold'],
                'total_amount' => $row['total_amount']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $sales_data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch sales data'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 