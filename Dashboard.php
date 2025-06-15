<?php
session_start();
include 'config.php';

// Debug session information
error_log("Dashboard - Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("Dashboard - Session role_name: " . (isset($_SESSION['role_name']) ? $_SESSION['role_name'] : 'not set'));

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get current user's data
$current_user_id = $_SESSION['user_id'];
$current_user_query = "SELECT * FROM tbl_users WHERE user_id = ?";
$stmt = $conn->prepare($current_user_query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$current_user_result = $stmt->get_result();
$currentUser = $current_user_result->fetch_assoc();
$stmt->close();

// Handle User Operations
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_user':
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role_id = $_POST['role_id'];

            $stmt = $conn->prepare("INSERT INTO tbl_users (full_name, email, username, password, role_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $full_name, $email, $username, $password, $role_id);

            if ($stmt->execute()) {
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Created new user: $full_name";
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'User added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add user']);
            }

            break;

        case 'edit_user':
            $user_id = $_POST['user_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $username = $_POST['username'];
            $role_id = $_POST['role_id'];


            $stmt = $conn->prepare("UPDATE tbl_users SET full_name = ?, email = ?, username = ?, role_id = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $full_name, $email, $username, $role_id, $user_id);

            if ($stmt->execute()) {
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Updated user: $full_name";
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user']);
            }

            break;

        case 'delete_user':
            $user_id = $_POST['user_id'];

            // Get user info for logging
            $stmt = $conn->prepare("SELECT full_name FROM tbl_users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt = $conn->prepare("DELETE FROM tbl_users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Deleted user: " . $user['full_name'];
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }

            break;

        case 'add_role':
            $role_name = $_POST['role_name'];

            $stmt = $conn->prepare("INSERT INTO tbl_roles (role_name) VALUES (?)");
            $stmt->bind_param("s", $role_name);

            if ($stmt->execute()) {
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Created new role: $role_name";
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Role added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add role']);
            }

            break;

        case 'edit_role':
            $role_id = $_POST['role_id'];
            $role_name = $_POST['role_name'];

            $stmt = $conn->prepare("UPDATE tbl_roles SET role_name = ? WHERE role_id = ?");
            $stmt->bind_param("si", $role_name, $role_id);

            if ($stmt->execute()) {
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Updated role: $role_name";
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update role']);
            }

            break;

        case 'delete_role':
            $role_id = $_POST['role_id'];

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
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Deleted role: " . $role['role_name'];
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Role deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete role']);
            }

            break;

        case 'clear_logs':
            $stmt = $conn->prepare("TRUNCATE TABLE tbl_logs");
            if ($stmt->execute()) {
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Cleared all activity logs";
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Logs cleared successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear logs']);
            }

            break;

        case 'add_product':
            if (isset($_POST['Category'], $_POST['product_name'], $_POST['description'], $_POST['price'], $_POST['quantity_available'])) {
                $category = $_POST['Category'];
                $product_name = $_POST['product_name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $quantity_available = $_POST['quantity_available'];

                $product_image = '';
                // Handle image upload
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/'; // Directory to save images
                    $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']; // Allowed image formats

                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = uniqid('product_') . '.' . $file_extension; // Generate unique filename
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                            $product_image = $upload_path; // Store path relative to Dashboard.php
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                            exit();
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.']);
                        exit();
                    }
                }

                $sql = "INSERT INTO tbl_products (Category, product_name, description, price, quantity_available, product_image) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                // Assuming price is decimal/double and quantity_available is integer based on common practice, adjust if your schema is strictly VARCHAR for these.
                $stmt->bind_param("sssdis", $category, $product_name, $description, $price, $quantity_available, $product_image);

                if ($stmt->execute()) {
                    // Log activity
                    $user_id = $_SESSION['user_id'];
                    $action = "Added new product: $product_name";
                    $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    $stmt_log->bind_param("is", $user_id, $action);
                    $stmt_log->execute();
                    $stmt_log->close();

                    echo json_encode(['success' => true, 'message' => 'Product added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $stmt->error]);
                }
                $stmt->close();

            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required fields for adding product']);
            }

            break;

        case 'edit_product':
            if (isset($_POST['product_id'], $_POST['Category'], $_POST['product_name'], $_POST['description'], $_POST['price'], $_POST['quantity_available'])) {
                $product_id = $_POST['product_id'];
                $category = $_POST['Category'];
                $product_name = $_POST['product_name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $quantity_available = $_POST['quantity_available'];

                $product_image = null; // Use null to indicate no image update
                // Handle image upload
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/'; // Directory to save images
                    $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']; // Allowed image formats

                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = uniqid('product_') . '.' . $file_extension; // Generate unique filename
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                            $product_image = $upload_path; // Store path relative to Dashboard.php

                            // Optional: Delete old image if it's not the default
                            $old_image_query = "SELECT product_image FROM tbl_products WHERE product_id = ?";
                            $old_image_stmt = $conn->prepare($old_image_query);
                            $old_image_stmt->bind_param("i", $product_id);
                            $old_image_stmt->execute();
                            $old_image_result = $old_image_stmt->get_result();
                            $old_image_row = $old_image_result->fetch_assoc();
                            if ($old_image_row && !empty($old_image_row['product_image']) && $old_image_row['product_image'] !== 'uploads/no-image.png' && file_exists($old_image_row['product_image'])) {
                                unlink($old_image_row['product_image']);
                            }
                            $old_image_stmt->close();

                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to upload new image']);
                            exit();
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid file type for image. Only JPG, JPEG, PNG, GIF are allowed.']);
                        exit();
                    }
                }

                $sql = "UPDATE tbl_products SET Category = ?, product_name = ?, description = ?, price = ?, quantity_available = ?";
                $params = [$category, $product_name, $description, $price, $quantity_available];
                $types = "sssdi";

                if ($product_image !== null) {
                    $sql .= ", product_image = ?";
                    $params[] = $product_image;
                    $types .= "s";
                }

                $sql .= " WHERE product_id = ?";
                $params[] = $product_id;
                $types .= "i";

                $stmt = $conn->prepare($sql);
                // Dynamically bind parameters
                $bind_params = [$types];
                for ($i = 0; $i < count($params); $i++) {
                    $bind_params[] = &$params[$i];
                }
                call_user_func_array([$stmt, 'bind_param'], $bind_params);

                if ($stmt->execute()) {
                    // Log activity
                    $user_id = $_SESSION['user_id'];
                    $action = "Updated product: $product_name";
                    $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    $stmt_log->bind_param("is", $user_id, $action);
                    $stmt_log->execute();
                    $stmt_log->close();

                    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update product: ' . $stmt->error]);
                }
                $stmt->close();

            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required fields for editing product']);
            }

            break;

        case 'delete_product':
            if (isset($_POST['product_id'])) {
                $product_id = $_POST['product_id'];

                // Optional: Get image path before deleting record to delete the file
                $image_query = "SELECT product_image FROM tbl_products WHERE product_id = ?";
                $image_stmt = $conn->prepare($image_query);
                $image_stmt->bind_param("i", $product_id);
                $image_stmt->execute();
                $image_result = $image_stmt->get_result();
                $image_row = $image_result->fetch_assoc();
                $image_stmt->close();

                $sql = "DELETE FROM tbl_products WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);

                if ($stmt->execute()) {
                    // Optional: Delete the product image file if it exists and is not the default
                    if ($image_row && !empty($image_row['product_image']) && $image_row['product_image'] !== 'uploads/no-image.png' && file_exists($image_row['product_image'])) {
                        unlink($image_row['product_image']);
                    }
                    // Log activity
                    $user_id = $_SESSION['user_id'];
                    $action = "Deleted product ID: " . $product_id;
                    $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    $stmt_log->bind_param("is", $user_id, $action);
                    $stmt_log->execute();
                    $stmt_log->close();

                    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $stmt->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Product ID not provided for delete']);
            }

            break;
    }
}

// Get total users
$total_users_query = "SELECT COUNT(*) as total_users FROM tbl_users";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['total_users'] ?? 0;

// Get total products
$total_products_query = "SELECT COUNT(*) as total_products FROM tbl_products";
$total_products_result = $conn->query($total_products_query);
$total_products = $total_products_result->fetch_assoc()['total_products'] ?? 0;

// Get total orders (assuming each entry in tbl_transactions is an order or can be grouped by sales_id/transaction_id)
$total_orders_query = "SELECT COUNT(DISTINCT transaction_id) as total_orders FROM tbl_transactions";
$total_orders_result = $conn->query($total_orders_query);
$total_orders = $total_orders_result->fetch_assoc()['total_orders'] ?? 0;

// Get total revenue
$total_revenue_query = "SELECT SUM(t.quantity_sold * p.price) AS total_revenue FROM tbl_transactions t JOIN tbl_products p ON t.product_id = p.product_id"; // Assuming 'Completed' status for revenue
$total_revenue_result = $conn->query($total_revenue_query);
$total_revenue = $total_revenue_result->fetch_assoc()['total_revenue'] ?? 0;

// Pagination for Users table
$users_per_page = 10; // Number of users per page
$current_users_page = isset($_GET['users_page']) ? (int)$_GET['users_page'] : 1;
$offset_users = ($current_users_page - 1) * $users_per_page;

// Get total count of users
$total_users_count_query = "SELECT COUNT(*) as total_users FROM tbl_users";
$total_users_count_result = $conn->query($total_users_count_query);
$total_users_count = $total_users_count_result->fetch_assoc()['total_users'] ?? 0;
$total_users_pages = ceil($total_users_count / $users_per_page);

// Get all users with pagination
$users_query = "SELECT u.*, r.role_name, 
                (SELECT COUNT(*) FROM tbl_users WHERE role_id = u.role_id) as users_count 
                FROM tbl_users u 
                JOIN tbl_roles r ON u.role_id = r.role_id 
                LIMIT ? OFFSET ?";
$stmt_users = $conn->prepare($users_query);
$stmt_users->bind_param("ii", $users_per_page, $offset_users);
$stmt_users->execute();
$users_result = $stmt_users->get_result();
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}
$stmt_users->close();

// Get all roles
$roles_query = "SELECT r.*, 
                (SELECT COUNT(*) FROM tbl_users WHERE role_id = r.role_id) as users_count 
                FROM tbl_roles r";
$roles_result = $conn->query($roles_query);
$roles = [];
while ($row = $roles_result->fetch_assoc()) {
    $roles[] = $row;
}

// Get all products
$products_query = "SELECT * FROM tbl_products";
$products_result = $conn->query($products_query);
$products = [];
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get all logs
$logs_query = "SELECT l.log_id, u.full_name, l.action, l.log_DateTime
               FROM tbl_logs l
               JOIN tbl_users u ON l.user_id = u.user_id
               ORDER BY l.log_DateTime DESC";
$logs_result = $conn->query($logs_query);
$logs = [];
while ($row = $logs_result->fetch_assoc()) {
    $logs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="Dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="wrapper">
        <!-- Top Navigation Bar -->
        <nav class="top-nav">
            <div class="nav-left">
                <i class="fas fa-bars toggle-sidebar" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling"
                    aria-controls="offcanvasScrolling"></i>
                <h4 class="nav-title">Admin Dashboard</h4>
            </div>

            <!-- user profile -->
            <div class="nav-right">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-flex align-items-center px-3 py-2">
                            <img src="<?php echo !empty($currentUser['profile_picture']) ? htmlspecialchars($currentUser['profile_picture']) : 'uploads/profile_pictures/Default.svg'; ?>"
                                alt="Profile" width="32" height="32" class="rounded-circle me-2">
                            <div>
                                <p class="mb-0 user-name">
                                    <?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>
                                </p>
                                <p class="mb-0 user-name">
                                    <?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>
                                </p>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                        <li>
                            <div class="dropdown-item text-wrap py-2">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo !empty($currentUser['profile_picture']) ? htmlspecialchars($currentUser['profile_picture']) : 'uploads/profile_pictures/Default.svg'; ?>"
                                        alt="Profile" width="40" height="40" class="rounded-circle me-2">
                                    <div>
                                        <p class="mb-0 user-name">
                                            <?php echo htmlspecialchars($currentUser['full_name'] ?? 'No user'); ?>
                                        </p>
                                        <p class="mb-0 user-name">
                                            <?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="Customer.php"><i class="fas fa-shop fa-fw me-2"></i>Your Shop</a></li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Log
                                Out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="offcanvas offcanvas-start" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1"
            id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasScrollingLabel">Admin Dashboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="nav-menu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="#dashboard" class="nav-link active">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#accounts" class="nav-link">
                                <i class="fas fa-users me-2"></i>
                                <span>Accounts</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#products" class="nav-link">
                                <i class="fas fa-box me-2"></i>
                                <span>Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#transactions" class="nav-link">
                                <i class="fas fa-exchange-alt me-2"></i>
                                <span>Transactions</span>
                            </a>
                        </li>

                    </ul>
                </div>
            </div>
        </div>

        <main>
            <div class="container1">
                <!-- Dashboard Section -->
                <div id="dashboard" class="content-section active">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users</h5>
                                    <h2 class="card-text"><?php echo $total_users; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Products</h5>
                                    <h2 class="card-text"><?php echo $total_products; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Orders</h5>
                                    <h2 class="card-text"><?php echo $total_orders; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h5 class="card-title">Revenue</h5>
                                    <h2 class="card-text">₱<?php echo number_format($total_revenue, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Sales Overview</h5>
                                    <button class="btn btn-primary" id="refreshSales">
                                        <i class="fas fa-sync"></i> Refresh
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Total Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="salesTableBody">
                                                <!-- Sales data will be loaded dynamically -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Quick Actions</h5>
                                    <button class="btn btn-primary" onclick="window.location.reload();">
                                        <i class="fas fa-sync"></i> Refresh
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="button1 btn " onclick="navigateToSection('products')">
                                            <i class="fas fa-box me-2"></i>Manage Products
                                        </button>
                                        <button class="button2 btn " onclick="navigateToSection('transactions')">
                                            <i class="fas fa-exchange-alt me-2"></i>View Transactions
                                        </button>
                                        <button class="button3 btn " onclick="navigateToSection('accounts')">
                                            <i class="fas fa-users me-2"></i>Manage Accounts
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Log Activities Section -->
                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Activity Logs</h5>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary" onclick="window.location.reload();">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                                <button class="btn btn-outline-danger" id="clearLogs">
                                    <i class="fas fa-trash"></i> Clear Logs
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="logsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Log_DateTime</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['log_id'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($log['full_name'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($log['action'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($log['log_DateTime'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accounts Section -->
                <div id="accounts" class="content-section">
                    <!-- User Management Section -->
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">User Management</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus"></i> Add New User
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive mb-4">
                                <table class="table table-hover" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Profile Picture</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                                <td>
                                                    <img src="<?php echo htmlspecialchars(!empty($user['profile_picture']) ? $user['profile_picture'] : 'uploads/no-image.png'); ?>"
                                                        alt="Profile" width="40" height="40" class="rounded-circle">
                                                </td>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['create_date']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info"
                                                        onclick="editUser(<?php echo $user['user_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger"
                                                        onclick="deleteUser(<?php echo $user['user_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Role Management Section -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Role Management</h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                                <i class="fas fa-plus"></i> Add New Role
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="rolesTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Role Name</th>
                                            <th>Users Count</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($roles as $role): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($role['role_id']); ?></td>
                                                <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                                <td><?php echo htmlspecialchars($role['users_count']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info"
                                                        onclick="editRole(<?php echo $role['role_id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger"
                                                        onclick="deleteRole(<?php echo $role['role_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Section -->
                <div id="products" class="content-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Products</h5>
                            <button class="btn btn-primary" id="addProductBtn">Add New Product</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Image</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Product rows will be loaded here by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                </div>

                <!-- Transactions Section -->
                <div id="transactions" class="content-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Transaction History</h5>
                            <button class="btn btn-primary" id="refreshTransactions">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="transactionsTable">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Customer ID</th>
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Item Price</th>
                                            <th>Customer Name</th>
                                            <th>Payment Method</th>
                                            <th>Transaction Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Transaction rows will be loaded here by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="dashboard.js"></script>-->
    <script>

        // Function to scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Scroll to top when page loads
        window.addEventListener('load', scrollToTop);




        // Handle navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();

                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));

                // Add active class to clicked link
                this.classList.add('active');

                // Get the target section
                const targetId = this.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);

                // Add fade-out class to current active section
                const currentActive = document.querySelector('.content-section.active');
                if (currentActive) {
                    currentActive.classList.add('fade-out');
                    currentActive.classList.remove('active');
                }

                // Wait for fade-out animation to complete
                setTimeout(() => {
                    // Hide all sections
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.style.display = 'none';
                        section.classList.remove('fade-out');
                    });

                    // Show and animate the target section
                    targetSection.style.display = 'block';
                    // Force reflow
                    targetSection.offsetHeight;
                    targetSection.classList.add('active');

                    // Load content specific to the active section
                    if (targetId === 'products') {
                        // Assuming loadProducts function exists and loads products for productsTable
                        // You would call it here if products are dynamically loaded
                    } else if (targetId === 'transactions') {
                        loadTransactions(); // Load transactions when the transactions tab is active
                    }
                    // Scroll to top after content change
                    scrollToTop();
                }, 300);

                // Close sidebar on mobile after clicking
                if (window.innerWidth < 992) {
                    const offcanvas = document.querySelector('.offcanvas');
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
                    if (bsOffcanvas) {
                        bsOffcanvas.hide();
                    }
                }
            });
        });

        const products = <?php echo json_encode($products); ?>;
        const users = <?php echo json_encode($users); ?>;
        const roles = <?php echo json_encode($roles); ?>;

        // Function to load products into the table
        function loadProducts() {
            const tbody = document.querySelector('#productsTable tbody');
            if (!tbody) {
                console.error('Products table tbody not found.');
                return;
            }
            tbody.innerHTML = ''; // Clear existing rows

            if (products.length > 0) {
                products.forEach(product => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                <td>${product.product_id}</td>
                <td><img src="${product.product_image && product.product_image !== '' ? product.product_image : 'uploads/no-image.png'}" alt="${product.product_name}" width="50"></td>
                <td>${product.product_name}</td>
                <td>${product.Category}</td>
                <td>${product.description}</td>
                <td>₱${parseFloat(product.price).toFixed(2)}</td>
                <td>${product.quantity_available}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-info edit-product-btn" data-id="${product.product_id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-product-btn" data-id="${product.product_id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
                });

                // Add event listeners to the dynamically created buttons
                document.querySelectorAll('.edit-product-btn').forEach(button => {
                    button.addEventListener('click', function () {
                        const productId = this.getAttribute('data-id');
                        editProduct(productId);
                    });
                });

                document.querySelectorAll('.delete-product-btn').forEach(button => {
                    button.addEventListener('click', function () {
                        const productId = this.getAttribute('data-id');
                        deleteProduct(productId);
                    });
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No products found.</td></tr>';
            }
        }

        // Function to edit user (populate modal)
        function editUser(userId) {
            const user = users.find(u => u.user_id == userId);
            if (user) {
                document.getElementById('edit_user_id_for_edit_modal').value = user.user_id;
                document.getElementById('edit_full_name').value = user.full_name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_role_id').value = user.role_id;
                new bootstrap.Modal(document.getElementById('editUserModal')).show();
            }
        }

        // Function to edit role (populate modal)
        function editRole(roleId) {
            const role = roles.find(r => r.role_id == roleId);
            if (role) {
                document.getElementById('edit_role_modal_role_id_hidden').value = role.role_id;
                document.getElementById('edit_role_name').value = role.role_name;
                new bootstrap.Modal(document.getElementById('editRoleModal')).show();
            }
        }

        // Function to delete user
        function deleteUser(userId) {
            document.getElementById('delete_action').value = 'delete_user';
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_role_id').value = ''; // Clear role_id
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Function to delete role
        function deleteRole(roleId) {
            document.getElementById('delete_action').value = 'delete_role';
            document.getElementById('delete_role_id').value = roleId;
            document.getElementById('delete_user_id').value = ''; // Clear user_id
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Handle Add New Product button click
        document.getElementById('addProductBtn')?.addEventListener('click', function () {
            // Reset the form and clear any previous validation states when the modal is shown
            const addProductForm = document.getElementById('addProductForm');
            if (addProductForm) {
                addProductForm.reset();
                // Manually clear any Bootstrap validation classes if used
                addProductForm.classList.remove('was-validated');
            }
            const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
            addProductModal.show();
        });

        // Function to edit product (populate modal)
        function editProduct(productId) {
            const product = products.find(p => p.product_id == productId);
            if (product) {
                document.getElementById('edit_product_id').value = product.product_id;
                document.getElementById('edit_product_category').value = product.Category;
                document.getElementById('edit_product_name').value = product.product_name;
                document.getElementById('edit_product_description').value = product.description;
                document.getElementById('edit_product_price').value = parseFloat(product.price).toFixed(2);
                document.getElementById('edit_product_quantity_available').value = product.quantity_available;
                // You might want to display the current image here if applicable

                const editProductModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                editProductModal.show();
            }
        }

        // Function to delete product (populate modal)
        function deleteProduct(productId) {
            document.getElementById('delete_product_id_modal').value = productId;
            const deleteProductModal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
            deleteProductModal.show();
        }

        // Function to load transactions into the table
        function loadTransactions() {
            console.log('Loading transactions...');
            fetch('api/transactions.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#transactionsTable tbody');
                    if (!tbody) {
                        console.error('Transactions table tbody not found.');
                        return;
                    }
                    tbody.innerHTML = ''; // Clear existing rows

                    if (data.success && data.data.length > 0) {
                        data.data.forEach(transaction => {
                            const row = tbody.insertRow();
                            row.innerHTML = `
                        <td>${transaction.sales_id || 'N/A'}</td>
                        <td>${transaction.transaction_id || 'N/A'}</td>
                        <td>${transaction.product_name || 'N/A'}</td>
                        <td>${transaction.quantity_sold || 'N/A'}</td>
                        <td>₱${parseFloat(transaction.item_price || 0).toFixed(2)}</td>
                        <td>${transaction.customer_name || 'N/A'}</td>
                        <td>${transaction.payment_method || 'N/A'}</td>
                        <td>${new Date(transaction.transaction_date).toLocaleString()}</td>
                    `;
                        });
                        console.log(`Loaded ${data.data.length} transactions.`);
                    } else if (data.success && data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No transactions found.</td></tr>';
                        console.log('No transactions found.');
                    } else {
                        tbody.innerHTML = `<tr><td colspan="9" class="text-center">Error: ${data.message}</td></tr>`;
                        console.error('Error loading transactions:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error for transactions:', error);
                    const tbody = document.querySelector('#transactionsTable tbody');
                    if (tbody) {
                        tbody.innerHTML = `<tr><td colspan="9" class="text-center">Failed to load transactions. Please try again.</td></tr>`;
                    }
                });
        }

        // Add this JavaScript function after the existing loadTransactions function
        function loadSales() {
            console.log('Loading sales data...');
            fetch('api/sales.php')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Sales data:', data);
                    const tbody = document.querySelector('#salesTableBody');
                    if (!tbody) {
                        console.error('Sales table tbody not found.');
                        return;
                    }
                    tbody.innerHTML = ''; // Clear existing rows

                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(sale => {
                            const row = tbody.insertRow();
                            row.innerHTML = `
                        <td>${new Date(sale.transaction_date).toLocaleDateString()}</td>
                        <td>${sale.product_name || 'N/A'}</td>
                        <td>${sale.quantity_sold || 'N/A'}</td>
                        <td>₱${parseFloat(sale.total_amount || 0).toFixed(2)}</td>
                        <td><span class="badge bg-success">Completed</span></td>
                    `;
                        });
                    } else {
                        tbody.innerHTML = `<tr><td colspan="5" class="text-center">${data.message || 'No sales data available.'}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Error loading sales:', error);
                    const tbody = document.querySelector('#salesTableBody');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Failed to load sales data. Please try again.</td></tr>';
                    }
                });
        }

        // Update the document.addEventListener('DOMContentLoaded') function to include loadSales
        document.addEventListener('DOMContentLoaded', function () {
            // Existing code...
            loadSales(); // Add this line to load sales data when the page loads
        });

        // Update all form submissions to use the new handler
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData
                }).then(handleAjaxResponse);
            });
        });

        // Handle Clear Logs button click
        document.getElementById('clearLogs')?.addEventListener('click', function () {
            if (confirm('Are you sure you want to clear all logs?')) {
                const formData = new FormData();
                formData.append('action', 'clear_logs');

                fetch('', {
                    method: 'POST',
                    body: formData
                }).then(handleAjaxResponse);
            }
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function () {
            loadProducts();
            loadSales();
        });

        // Function to handle AJAX responses and show modal
        function handleAjaxResponse(response) {
            return response.json().then(data => {
                const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                const messageModalBody = document.getElementById('messageModalBody');

                if (messageModalBody) {
                    messageModalBody.innerHTML = data.message;
                }

                if (messageModal) {
                    messageModal.show();
                }

                if (data.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                }
            }).catch(error => {
                console.error('Error:', error);
                const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                const messageModalBody = document.getElementById('messageModalBody');
                if (messageModalBody) {
                    messageModalBody.innerHTML = 'An unexpected error occurred.';
                }
                if (messageModal) {
                    messageModal.show();
                }
            });
        }

        // Function to load logs
        function loadLogs() {
            console.log('Loading logs...');
            const tbody = document.querySelector('#logsTable tbody');
            if (!tbody) {
                console.error('Logs table tbody not found.');
                return;
            }
            tbody.innerHTML = '';

            if (logs.length > 0) {
                logs.forEach(log => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                <td>${log.log_id}</td>
                <td>${log.full_name}</td>
                <td>${log.action}</td>
                <td>${log.log_DateTime}</td>
            `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No activity logs found.</td></tr>';
            }
        }

        // Handle Clear Logs button click
        const clearLogsBtn = document.getElementById('clearLogsBtn');
        if (clearLogsBtn) {
            clearLogsBtn.addEventListener('click', function () {
                if (confirm('Are you sure you want to clear all activity logs? This action cannot be undone.')) {
                    const formData = new FormData();
                    formData.append('action', 'clear_logs');

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                        .then(handleAjaxResponse);
                }
            });
        }

        // Add this new function for navigation
        function navigateToSection(sectionId) {
            // Find the nav link for the section
            const navLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);
            if (navLink) {
                // Trigger click on the nav link
                navLink.click();
                // Scroll to top
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        }

    </script>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_user">
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_role">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" class="form-control" name="role_name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Role</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id_for_edit_modal">
                        <div class="mb-3">
                            <label class="form-label">Current Profile Picture</label>
                            <div class="mb-2">
                                <img id="current_profile_picture" src="" alt="Current Profile" width="100" height="100"
                                    class="rounded-circle">
                            </div>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*">
                            <small class="form-text text-muted">Leave empty to keep current picture</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="password">
                            <small class="form-text text-muted">Leave empty to keep current password</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role_id" id="edit_role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="editRoleForm">
                        <input type="hidden" name="action" value="edit_role">
                        <input type="hidden" name="role_id" id="edit_role_modal_role_id_hidden">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" class="form-control" name="role_name" id="edit_role_name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this item?</p>
                    <form method="POST" action="" id="deleteForm">
                        <input type="hidden" name="action" id="delete_action">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <input type="hidden" name="role_id" id="delete_role_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_product">
                        <div class="mb-3">
                            <label for="add_product_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="add_product_category" name="Category">
                        </div>
                        <div class="mb-3">
                            <label for="add_product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="add_product_name" name="product_name">
                        </div>
                        <div class="mb-3">
                            <label for="add_product_description" class="form-label">Description</label>
                            <textarea class="form-control" id="add_product_description" name="description"
                                rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="add_product_price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="add_product_price" name="price" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="add_product_quantity_available" class="form-label">Quantity Available</label>
                            <input type="number" class="form-control" id="add_product_quantity_available"
                                name="quantity_available">
                        </div>
                        <div class="mb-3">
                            <label for="add_product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="add_product_image" name="product_image">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit_product">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        <div class="mb-3">
                            <label for="edit_product_category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="edit_product_category" name="Category">
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit_product_name" name="product_name">
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_product_description" name="description"
                                rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="edit_product_price" name="price" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_quantity_available" class="form-label">Quantity Available</label>
                            <input type="number" class="form-control" id="edit_product_quantity_available"
                                name="quantity_available">
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="edit_product_image" name="product_image">
                            <small class="form-text text-muted">Leave blank to keep existing image.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal for Product -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteProductModalLabel">Confirm Product Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this product?</p>
                    <form id="deleteProductForm" method="POST" action="">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" id="delete_product_id_modal">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true"
        data-bs-backdrop="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="messageModalBody">
                    <!-- Message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>