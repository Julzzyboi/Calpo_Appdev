<?php
// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display_errors in production

// Function to handle errors and return JSON response
function handleError($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Set custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log the error details in a server log instead of displaying to the user
    // error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    handleError("Server error occurred. Please try again later.");
});

// Set exception handler
set_exception_handler(function($exception) {
    // Log the exception details
    // error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    handleError("Server error occurred. Please try again later.");
});

try {
    session_start();
    include '../config.php';

    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }

    header('Content-Type: application/json');

    if (!isset($_POST['action'])) {
        handleError('No action specified.');
    }

    $action = $_POST['action'];

    switch ($action) {
        case 'get_products':
            // Logic to fetch all products
            $sql = "SELECT * FROM tbl_products";
            $result = $conn->query($sql);

            $products = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    // Ensure image_url is correctly formatted
                    $row['image_url'] = !empty($row['product_image']) ? $row['product_image'] : 'uploads/no-image.png';
                    $products[] = $row;
                }
                $response = $products; // Return array of products directly for table display
            } else {
                $response = ['success' => false, 'message' => 'Error fetching products: ' . $conn->error];
            }
            break;

        case 'get_product':
            // Logic to fetch a single product by ID
            if (isset($_GET['product_id'])) {
                $product_id = $_GET['product_id'];
                $sql = "SELECT * FROM tbl_products WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();

                if ($product) {
                    // Ensure image_url is correctly formatted
                     $product['image_url'] = !empty($product['product_image']) ? $product['product_image'] : 'uploads/no-image.png';
                    $response = $product; // Return product object directly
                } else {
                    $response = ['success' => false, 'message' => 'Product not found'];
                }
                $stmt->close();
            } else {
                 $response = ['success' => false, 'message' => 'Product ID not provided'];
            }
            break;

        case 'add_product':
            // Check if all required fields are set and file was uploaded without errors
            if (isset($_POST['Category'], $_POST['product_name'], $_POST['description'], $_POST['price'], $_POST['quantity_available']) && isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {

                $Category = htmlspecialchars(strip_tags(trim($_POST['Category'])));
                $product_name = htmlspecialchars(strip_tags(trim($_POST['product_name'])));
                $description = htmlspecialchars(strip_tags(trim($_POST['description'])));
                $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
                $quantity_available = filter_var($_POST['quantity_available'], FILTER_SANITIZE_NUMBER_INT);

                // Basic validation
                if (empty($Category) || empty($product_name) || empty($description) || $price === false || $quantity_available === false) {
                    handleError('All fields are required and must be valid.');
                }

                // File upload handling
                $uploadDir = '../uploads/'; // Directory to save uploaded images
                $fileName = uniqid() . '_' . basename($_FILES['product_image']['name']);
                $uploadPath = $uploadDir . $fileName;
                $imageFileType = strtolower(pathinfo($uploadPath, PATHINFO_EXTENSION));

                // Check if image file is a actual image or fake image
                $check = getimagesize($_FILES['product_image']['tmp_name']);
                if ($check === false) {
                    handleError('File is not an image.');
                }

                // Allow certain file formats
                if($imageFileType != "jpg" && "png" && "jpeg" && "gif" ) {
                    handleError('Sorry, only JPG, JPEG, PNG & GIF files are allowed.');
                }

                // Check file size (e.g., 5MB limit)
                if ($_FILES['product_image']['size'] > 5 * 1024 * 1024) { // 5MB
                    handleError('Sorry, your file is too large (max 5MB).');
                }

                 // Move uploaded file to the destination directory
                if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadPath)) {
                    handleError('Error uploading file.');
                }

                // Insert product into database
                $stmt = $conn->prepare("INSERT INTO tbl_products (Category, product_name, description, price, quantity_available, product_image) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdis", $Category, $product_name, $description, $price, $quantity_available, $uploadPath);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Product added successfully.']);
                } else {
                    // If database insertion fails, delete the uploaded file
                    if (file_exists($uploadPath)) {
                        unlink($uploadPath);
                    }
                    handleError('Failed to add product.');
                }
                $stmt->close();

            } else {
                handleError('Missing product data or file upload error.');
            }
            break;

        case 'edit_product':
            // Check if all required fields are set
            if (isset($_POST['product_id'], $_POST['Category'], $_POST['product_name'], $_POST['description'], $_POST['price'], $_POST['quantity_available'])) {

                $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
                $Category = htmlspecialchars(strip_tags(trim($_POST['Category'])));
                $product_name = htmlspecialchars(strip_tags(trim($_POST['product_name'])));
                $description = htmlspecialchars(strip_tags(trim($_POST['description'])));
                $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
                $quantity_available = filter_var($_POST['quantity_available'], FILTER_SANITIZE_NUMBER_INT);

                 // Basic validation
                if (empty($product_id) || empty($Category) || empty($product_name) || empty($description) || $price === false || $quantity_available === false) {
                    handleError('All fields are required and must be valid.');
                }

                $uploadPath = null; // Initialize upload path

                // Handle image upload if a new image is provided
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                     $uploadDir = '../uploads/'; // Directory to save uploaded images
                    $fileName = uniqid() . '_' . basename($_FILES['product_image']['name']);
                    $uploadPath = $uploadDir . $fileName;
                    $imageFileType = strtolower(pathinfo($uploadPath, PATHINFO_EXTENSION));

                    // Check if image file is a actual image or fake image
                    $check = getimagesize($_FILES['product_image']['tmp_name']);
                    if ($check === false) {
                        handleError('File is not an image.');
                    }

                    // Allow certain file formats
                    if($imageFileType != "jpg" && "png" && "jpeg" && "gif" ) {
                        handleError('Sorry, only JPG, JPEG, PNG & GIF files are allowed.');
                    }

                    // Check file size (e.g., 5MB limit)
                    if ($_FILES['product_image']['size'] > 5 * 1024 * 1024) { // 5MB
                        handleError('Sorry, your file is too large (max 5MB).');
                    }

                    // Move uploaded file to the destination directory
                    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadPath)) {
                        handleError('Error uploading file.');
                    }

                    // Optional: Delete old image if a new one is uploaded and exists
                    // Fetch the old image path from the database before updating
                    // $stmt_old_image = $conn->prepare("SELECT product_image FROM tbl_products WHERE product_id = ? LIMIT 1");
                    // $stmt_old_image->bind_param("i", $product_id);
                    // $stmt_old_image->execute();
                    // $result_old_image = $stmt_old_image->get_result();
                    // $old_image_row = $result_old_image->fetch_assoc();
                    // if ($old_image_row && !empty($old_image_row['product_image']) && file_exists($old_image_row['product_image'])) {
                    //     unlink($old_image_row['product_image']);
                    // }
                    // $stmt_old_image->close();

                }

                // Update product in database
                if ($uploadPath) {
                    // Update with new image
                    $stmt = $conn->prepare("UPDATE tbl_products SET Category = ?, product_name = ?, description = ?, price = ?, quantity_available = ?, product_image = ? WHERE product_id = ?");
                    $stmt->bind_param("sssdiss", $Category, $product_name, $description, $price, $quantity_available, $uploadPath, $product_id);
                } else {
                    // Update without changing image
                    $stmt = $conn->prepare("UPDATE tbl_products SET Category = ?, product_name = ?, description = ?, price = ?, quantity_available = ? WHERE product_id = ?");
                    $stmt->bind_param("sssdii", $Category, $product_name, $description, $price, $quantity_available, $product_id);
                }

                if ($stmt->execute()) {
                     // Log activity (optional)
                    echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
                } else {
                     // If database update fails after uploading a new image, delete the new image
                    if ($uploadPath && file_exists($uploadPath)) {
                        unlink($uploadPath);
                    }
                    handleError('Failed to update product.');
                }
                $stmt->close();

            } else {
                handleError('Missing product update data.');
            }
            break;

        case 'delete_product':
            if (isset($_POST['product_id'])) {
                $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);

                // Optional: Get image path before deleting product to delete the image file
                // $stmt_image = $conn->prepare("SELECT product_image FROM tbl_products WHERE product_id = ? LIMIT 1");
                // $stmt_image->bind_param("i", $product_id);
                // $stmt_image->execute();
                // $result_image = $stmt_image->get_result();
                // $image_row = $result_image->fetch_assoc();
                // $stmt_image->close();

                $stmt = $conn->prepare("DELETE FROM tbl_products WHERE product_id = ?");
                $stmt->bind_param("i", $product_id);

                if ($stmt->execute()) {
                    // Optional: Delete the actual image file after successful database deletion
                    // if ($image_row && !empty($image_row['product_image']) && file_exists($image_row['product_image'])) {
                    //     unlink($image_row['product_image']);
                    // }
                    echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
                } else {
                    handleError('Failed to delete product.');
                }
                $stmt->close();
            } else {
                handleError('Product ID not specified for deletion.');
            }
            break;

        default:
            handleError('Invalid action.');
            break;
    }

} catch (Exception $e) {
    handleError($e->getMessage());
}

$conn->close();
?> 