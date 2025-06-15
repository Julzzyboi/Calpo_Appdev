<?php
session_start();
include 'config.php'; // Include your database connection

header('Content-Type: application/json'); // Set response header

// Check if the request method is POST and if cart data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart'])) {
    $cartItems = json_decode($_POST['cart'], true); // Decode the JSON cart data

    // Basic validation
    if (empty($cartItems) || !is_array($cartItems)) {
        echo json_encode(['success' => false, 'message' => 'Invalid or empty cart data.']);
        exit;
    }

    // Get customer ID from session (assuming user is logged in and ID is stored in session)
    $customerId = $_SESSION['user_id'] ?? null; // Replace 'user_id' with your actual session variable name for user ID

    if (!$customerId) {
        echo json_encode(['success' => false, 'message' => 'User not logged in.']);
        exit;
    }

    // Start database transaction
    $conn->begin_transaction();

    $totalAmount = 0;
    $transactionSuccessful = true;
    $firstTransactionId = null; // To store the transaction_id of the first item

    try {
        // Prepare insert statement for tbl_transactions
        // We will insert transactions first to get the transaction_id(s)
        $insertTransactionQuery = "INSERT INTO tbl_transactions (product_id, customer_id, quantity_sold, transaction_date) VALUES (?, ?, ?, ?)";
        $stmtTransaction = $conn->prepare($insertTransactionQuery);

        // Variables for binding transaction parameters
        // Note: sales_id in tbl_transactions will be 0 or null for now,
        // as tbl_sales.sales_id is AUTO_INCREMENT and we insert into sales AFTER transactions.
        // This contradicts the schema image where tbl_transactions has sales_id as FK.
        // Given the FOREIGN KEY (`transaction_id`) REFERENCES `tbl_transactions` (`transaction_id`) in tbl_sales
        // we MUST insert into tbl_transactions first.
        // Let's insert with a temporary sales_id (e.g., 0 or NULL if allowed) and maybe update it later?
        // Or, if sales_id in tbl_transactions MUST reference tbl_sales.sales_id,
        // and tbl_sales.transaction_id MUST reference tbl_transactions.transaction_id,
        // the schema is a bit circular/confusing for a typical one-to-many sale-to-items relationship.
        // Let's assume sales_id in tbl_transactions *can* be updated later or is nullable for now,
        // and focus on satisfying the foreign key constraint from the error message.

        // $bindSalesId = null; // Assuming sales_id in tbl_transactions is nullable or can be updated
        $bindProductId = 0;
        $bindCustomerId = $customerId;
        $bindQuantitySold = 0;
        $bindTransactionDate = date('Y-m-d H:i:s');

        // Use 'i' for sales_id if it's INT and non-nullable, adjust as needed based on actual schema definition
        // Removed the binding for sales_id as it's removed from the INSERT query
        $stmtTransaction->bind_param("iiss", $bindProductId, $bindCustomerId, $bindQuantitySold, $bindTransactionDate);

        $calculatedTotal = 0;

        // Insert into tbl_transactions first for each item in the cart
        $insertedTransactionIds = [];
        foreach ($cartItems as $item) {
            $productId = $item['product_id'] ?? 0;
            $quantity = $item['quantity'] ?? 0;

            if ($productId <= 0 || $quantity <= 0) {
                throw new Exception('Invalid product ID or quantity in cart data.');
            }

            // Fetch product price from database for validation and total calculation
            $priceQuery = "SELECT price FROM tbl_products WHERE product_id = ? LIMIT 1";
            $stmtPrice = $conn->prepare($priceQuery);
            $stmtPrice->bind_param("i", $productId);
            $stmtPrice->execute();
            $priceResult = $stmtPrice->get_result();
            $product = $priceResult->fetch_assoc();
            $stmtPrice->close();

            if (!$product) {
                throw new Exception('Product with ID ' . $productId . ' not found in database.');
            }

            $price = $product['price'];
            $itemTotal = $price * $quantity;
            $calculatedTotal += $itemTotal;

            // Insert into tbl_transactions
            // Note: sales_id is being inserted as NULL/0 here. This might need adjustment
            // if tbl_transactions.sales_id is NOT nullable and MUST reference tbl_sales.sales_id.
            // However, to satisfy the tbl_sales FK on transaction_id, we must insert transaction first.
            $bindProductId = $productId; // Update bound parameter
            $bindQuantitySold = $quantity; // Update bound parameter

            if (!$stmtTransaction->execute()) {
                throw new Exception('Error inserting into tbl_transactions: ' . $stmtTransaction->error);
            }

            // Get the last inserted transaction_id
            $lastTransactionId = $conn->insert_id;
            $insertedTransactionIds[] = $lastTransactionId;

            if ($firstTransactionId === null) {
                $firstTransactionId = $lastTransactionId; // Store the first one for tbl_sales
            }
        }

        $stmtTransaction->close();

        // Now, insert into tbl_sales using the transaction_id of the first item
        // This assumes tbl_sales is linked to a single transaction item, which is unusual.
        // If tbl_sales is meant to summarize the *entire* sale,
        // the foreign key in tbl_sales should likely be on sales_id referencing tbl_sales.sales_id itself (not possible),
        // or the schema needs review.
        // But following the error: tbl_sales.transaction_id references tbl_transactions.transaction_id

        if (empty($insertedTransactionIds) || $firstTransactionId === null) {
             throw new Exception('No transaction items were successfully inserted.');
        }

        $salesDate = date('Y-m-d H:i:s');
        $paymentMethod = $_POST['payment_method'] ?? 'Unspecified'; // Get payment method from POST

        $insertSalesQuery = "INSERT INTO tbl_sales (transaction_id, total_amount, payment_method, sales_date) VALUES (?, ?, ?, ?)";
        $stmtSales = $conn->prepare($insertSalesQuery);
        // Bind parameters: i for transaction_id (INT), d for total_amount (DOUBLE), s for payment_method (VARCHAR), s for sales_date (DATETIME)
        $stmtSales->bind_param("idss", $firstTransactionId, $calculatedTotal, $paymentMethod, $salesDate);

        if (!$stmtSales->execute()) {
            throw new Exception('Error inserting into tbl_sales: ' . $stmtSales->error);
        }

        $salesId = $conn->insert_id; // Get the sales_id for the newly created sales record
        $stmtSales->close();

        // --- Potential Schema Discrepancy / Area for Review ---
        // The schema images showed tbl_transactions having a `sales_id` foreign key.
        // If that FK is active and NOT NULL, the inserts above where sales_id is NULL/0 would fail.
        // Also, relating tbl_sales to only ONE transaction_id is unusual for a multi-item sale summary.
        // It might be that tbl_sales.transaction_id is NOT a foreign key, or the schema definition is different.
        // Given the error message *specifically* mentions `FOREIGN KEY (transaction_id) REFERENCES tbl_transactions (transaction_id)` on `tbl_sales`,
        // we are prioritizing satisfying that constraint. If this structure is causing issues or seems incorrect
        // based on the intended database design, the database schema itself might need to be reviewed/adjusted.
        // For now, we proceed assuming tbl_sales links to a single transaction record via transaction_id.
        // We also assume tbl_transactions.sales_id can be NULL or 0 initially, which contradicts the schema image having sales_id as INT.
        // A proper fix might involve altering the table schemas.
        // --- End Potential Schema Discrepancy ---

        // If everything was successful, commit the transaction
        $conn->commit();

        // Note: The returned sales_id here is from the tbl_sales table insertion.
        // The first_transaction_id is from the tbl_transactions table insertion.
        echo json_encode(['success' => true, 'message' => 'Checkout successful!', 'sales_id' => $salesId, 'first_transaction_id' => $firstTransactionId]);

    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        $conn->rollback();
        error_log('Checkout error: ' . $e->getMessage()); // Log the error on the server side
        echo json_encode(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()]);
        $transactionSuccessful = false;
    }

    $conn->close();

} else {
    // Not a POST request or missing cart data
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?> 