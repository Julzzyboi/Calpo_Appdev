<?php
session_start();
include 'config.php';

// Debug session information
error_log("Cashier Dashboard - Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("Cashier Dashboard - Session role_name: " . (isset($_SESSION['role_name']) ? $_SESSION['role_name'] : 'not set'));

// Check if user is logged in and is cashier
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'cashier') {
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

// Get total orders
$total_orders_query = "SELECT COUNT(DISTINCT transaction_id) as total_orders FROM tbl_transactions";
$total_orders_result = $conn->query($total_orders_query);
$total_orders = $total_orders_result->fetch_assoc()['total_orders'] ?? 0;

// Get today's sales
$today_sales_query = "SELECT SUM(t.quantity_sold * p.price) AS today_sales 
                     FROM tbl_transactions t 
                     JOIN tbl_products p ON t.product_id = p.product_id 
                     WHERE DATE(t.transaction_date) = CURDATE()";
$today_sales_result = $conn->query($today_sales_query);
$today_sales = $today_sales_result->fetch_assoc()['today_sales'] ?? 0;

// Get total revenue
$total_revenue_query = "SELECT SUM(t.quantity_sold * p.price) AS total_revenue 
                       FROM tbl_transactions t 
                       JOIN tbl_products p ON t.product_id = p.product_id";
$total_revenue_result = $conn->query($total_revenue_query);
$total_revenue = $total_revenue_result->fetch_assoc()['total_revenue'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
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
                <h4 class="nav-title">Cashier Dashboard</h4>
            </div>
            <div class="nav-right">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-flex align-items-center px-3 py-2">
                            <img src="<?php echo htmlspecialchars(!empty($currentUser['profile_picture']) ? $currentUser['profile_picture'] : 'uploads/no-image.png'); ?>" alt="Profile" width="32" height="32" class="rounded-circle me-2">
                            <div>
                                <p class="mb-0 user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?></p>
                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                        <li>
                            <div class="dropdown-item text-wrap py-2">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars(!empty($currentUser['profile_picture']) ? $currentUser['profile_picture'] : 'uploads/no-image.png'); ?>" alt="Profile" width="40" height="40" class="rounded-circle me-2">
                                    <div>
                                        <p class="mb-0 user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?></p>
                                        <p class="mb-0 text-muted small"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
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
                <h5 class="offcanvas-title" id="offcanvasScrollingLabel">Cashier Dashboard</h5>
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
                                    <h5 class="card-title">Total Orders</h5>
                                    <h2 class="card-text"><?php echo $total_orders; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <h5 class="card-title">Today's Sales</h5>
                                    <h2 class="card-text">₱<?php echo number_format($today_sales, 2); ?></h2>
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
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Transactions</h5>
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
                    if (targetId === 'transactions') {
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

        // Function to load transactions
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
                    tbody.innerHTML = '';

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
                    } else {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No transactions found.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error loading transactions:', error);
                    const tbody = document.querySelector('#transactionsTable tbody');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Failed to load transactions. Please try again.</td></tr>';
                    }
                });
        }

        // Function to load sales
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
                    tbody.innerHTML = '';

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

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function () {
            loadSales();
            loadTransactions();
        });

        // Add event listeners for refresh buttons
        document.getElementById('refreshSales')?.addEventListener('click', loadSales);
        document.getElementById('refreshTransactions')?.addEventListener('click', loadTransactions);
    </script>

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
