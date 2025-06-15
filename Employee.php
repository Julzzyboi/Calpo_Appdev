<?php
session_start();
include 'config.php';

// Debug session information
error_log("Employee Dashboard - Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("Employee Dashboard - Session role_name: " . (isset($_SESSION['role_name']) ? $_SESSION['role_name'] : 'not set'));

// Check if user is logged in and is employee
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'employee') {
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

// Get total products
$total_products_query = "SELECT COUNT(*) as total_products FROM tbl_products";
$total_products_result = $conn->query($total_products_query);
$total_products = $total_products_result->fetch_assoc()['total_products'] ?? 0;

// Get total orders
$total_orders_query = "SELECT COUNT(DISTINCT transaction_id) as total_orders FROM tbl_transactions";
$total_orders_result = $conn->query($total_orders_query);
$total_orders = $total_orders_result->fetch_assoc()['total_orders'] ?? 0;

// Get total revenue
$total_revenue_query = "SELECT SUM(t.quantity_sold * p.price) AS total_revenue 
                       FROM tbl_transactions t 
                       JOIN tbl_products p ON t.product_id = p.product_id";
$total_revenue_result = $conn->query($total_revenue_query);
$total_revenue = $total_revenue_result->fetch_assoc()['total_revenue'] ?? 0;

// Handle Product Operations
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
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
                    $upload_dir = 'uploads/';
                    $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = uniqid('product_') . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                            $product_image = $upload_path;
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
                $stmt->bind_param("sssdis", $category, $product_name, $description, $price, $quantity_available, $product_image);

                if ($stmt->execute()) {
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

                $product_image = null;
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/';
                    $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = uniqid('product_') . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                            $product_image = $upload_path;

                            // Delete old image if it exists
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
                $bind_params = [$types];
                for ($i = 0; $i < count($params); $i++) {
                    $bind_params[] = &$params[$i];
                }
                call_user_func_array([$stmt, 'bind_param'], $bind_params);

                if ($stmt->execute()) {
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
                error_log("Employee.php: Attempting to delete product_id: " . $product_id);

                // Get image path before deleting
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
                    error_log("Employee.php: Product deletion successful for ID: " . $product_id);
                    // Delete the product image file if it exists
                    if ($image_row && !empty($image_row['product_image']) && $image_row['product_image'] !== 'uploads/no-image.png' && file_exists($image_row['product_image'])) {
                        unlink($image_row['product_image']);
                        error_log("Employee.php: Product image deleted: " . $image_row['product_image']);
                    }
                    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
                    exit();
                } else {
                    error_log("Employee.php: Failed to delete product " . $product_id . ": " . $stmt->error);
                    echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $stmt->error]);
                    exit();
                }
                $stmt->close();
            } else {
                error_log("Employee.php: Product ID not provided for delete");
                echo json_encode(['success' => false, 'message' => 'Product ID not provided for delete']);
                exit();
            }
            break;
    }
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
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
                <h4 class="nav-title">Employee Dashboard</h4>
            </div>
            <div class="nav-right">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
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
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Log Out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="offcanvas offcanvas-start" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1"
            id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasScrollingLabel">Employee Dashboard</h5>
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
                        <div class="col-md-4">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Products</h5>
                                    <h2 class="card-text"><?php echo $total_products; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Orders</h5>
                                    <h2 class="card-text"><?php echo $total_orders; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Revenue</h5>
                                    <h2 class="card-text">₱<?php echo number_format($total_revenue, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions Section -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Transactions</h5>
                            <button class="btn btn-primary" id="refreshRecentTransactions">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="recentTransactionsTable">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Total Amount</th>
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

                <!-- Products Section -->
                <div id="products" class="content-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Products Management</h5>
                            <button class="btn btn-primary" id="addProductBtn">
                                <i class="fas fa-plus"></i> Add New Product
                            </button>
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
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Total Amount</th>
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
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
                            <input type="text" class="form-control" id="add_product_category" name="Category" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="add_product_name" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_product_description" class="form-label">Description</label>
                            <textarea class="form-control" id="add_product_description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="add_product_price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="add_product_price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_product_quantity_available" class="form-label">Quantity Available</label>
                            <input type="number" class="form-control" id="add_product_quantity_available" name="quantity_available" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="add_product_image" name="product_image" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
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
                            <input type="text" class="form-control" id="edit_product_category" name="Category" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_product_description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="edit_product_price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_quantity_available" class="form-label">Quantity Available</label>
                            <input type="number" class="form-control" id="edit_product_quantity_available" name="quantity_available" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_product_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="edit_product_image" name="product_image" accept="image/*">
                            <small class="form-text text-muted">Leave blank to keep existing image.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
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
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true" data-bs-backdrop="false">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
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
                        loadProducts();
                    } else if (targetId === 'transactions') {
                        loadTransactions();
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

        // Function to load transactions
        function loadTransactions() {
            console.log('Loading transactions...');
            fetch('api/transactions.php')
                .then(response => response.json())
                .then(data => {
                    // Load recent transactions
                    const recentTbody = document.querySelector('#recentTransactionsTable tbody');
                    if (recentTbody) {
                        recentTbody.innerHTML = ''; // Clear existing rows
                        if (data.success && data.data.length > 0) {
                            // Show only the 5 most recent transactions
                            const recentTransactions = data.data.slice(0, 5);
                            recentTransactions.forEach(transaction => {
                                const row = recentTbody.insertRow();
                                row.innerHTML = `
                                    <td>${transaction.transaction_id || 'N/A'}</td>
                                    <td>${transaction.product_name || 'N/A'}</td>
                                    <td>${transaction.quantity_sold || 'N/A'}</td>
                                    <td>₱${parseFloat(transaction.item_price || 0).toFixed(2)}</td>
                                    <td>${new Date(transaction.transaction_date).toLocaleString()}</td>
                                `;
                            });
                        } else {
                            recentTbody.innerHTML = '<tr><td colspan="5" class="text-center">No recent transactions found.</td></tr>';
                        }
                    }

                    // Load full transaction history
                    const tbody = document.querySelector('#transactionsTable tbody');
                    if (tbody) {
                        tbody.innerHTML = ''; // Clear existing rows
                        if (data.success && data.data.length > 0) {
                            data.data.forEach(transaction => {
                                const row = tbody.insertRow();
                                row.innerHTML = `
                                    <td>${transaction.transaction_id || 'N/A'}</td>
                                    <td>${transaction.product_name || 'N/A'}</td>
                                    <td>${transaction.quantity_sold || 'N/A'}</td>
                                    <td>₱${parseFloat(transaction.item_price || 0).toFixed(2)}</td>
                                    <td>${new Date(transaction.transaction_date).toLocaleString()}</td>
                                `;
                            });
                        } else {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No transactions found.</td></tr>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading transactions:', error);
                    const recentTbody = document.querySelector('#recentTransactionsTable tbody');
                    const tbody = document.querySelector('#transactionsTable tbody');
                    
                    if (recentTbody) {
                        recentTbody.innerHTML = '<tr><td colspan="5" class="text-center">Failed to load recent transactions. Please try again.</td></tr>';
                    }
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Failed to load transactions. Please try again.</td></tr>';
                    }
                });
        }

        // Add event listeners for refresh buttons
        document.getElementById('refreshRecentTransactions')?.addEventListener('click', loadTransactions);
        document.getElementById('refreshTransactions')?.addEventListener('click', loadTransactions);

        // Handle Add New Product button click
        document.getElementById('addProductBtn')?.addEventListener('click', function () {
            const addProductForm = document.getElementById('addProductForm');
            if (addProductForm) {
                addProductForm.reset();
                addProductForm.classList.remove('was-validated');
            }
            const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
            addProductModal.show();
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

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function () {
            loadProducts();
            loadTransactions();
        });
    </script>
</body>
</html>
