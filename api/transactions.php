<?php
include '../config.php'; // Adjust path as necessary

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An error occurred.',
    'data' => []
];

// Pagination parameters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Number of transactions per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // Query to get total count of transactions
    $count_query = "SELECT COUNT(*) AS total_transactions FROM tbl_transactions";
    $count_result = $conn->query($count_query);
    $total_transactions = $count_result->fetch_assoc()['total_transactions'] ?? 0;
    $total_pages = ceil($total_transactions / $limit);

    // Query to fetch transaction data with pagination
    $query = "
        SELECT
            ts.sales_id,
            tt.transaction_id,
            tp.product_name,
            tt.quantity_sold,
            tp.price AS item_price,
            tu.full_name AS customer_name,
            ts.payment_method,
            ts.total_amount AS sale_total,
            tt.transaction_date
        FROM
            tbl_transactions tt
        LEFT JOIN
            tbl_products tp ON tt.product_id = tp.product_id
        LEFT JOIN
            tbl_users tu ON tt.customer_id = tu.user_id
        LEFT JOIN
            tbl_sales ts ON tt.transaction_id = ts.transaction_id
        ORDER BY
            tt.transaction_date DESC, tt.transaction_id DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        $response['success'] = true;
        $response['message'] = 'Transactions fetched successfully.';
        $response['data'] = $transactions;
        $response['total_transactions'] = $total_transactions;
        $response['total_pages'] = $total_pages;
        $response['current_page'] = $page;
    } else {
        $response['message'] = 'Error fetching transactions: ' . $conn->error;
        error_log('Error fetching transactions: ' . $conn->error);
    }

} catch (Exception $e) {
    $response['message'] = 'Exception: ' . $e->getMessage();
    error_log('Exception in transactions.php: ' . $e->getMessage());
}

$conn->close();

echo json_encode($response);
?> 