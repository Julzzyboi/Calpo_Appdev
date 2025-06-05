<?php
session_start();
include 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle User Operations
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_user':
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role_id = $_POST['role_id'];

            $stmt = $conn->prepare("INSERT INTO tbl_users (full_name, email, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $full_name, $email, $password, $role_id);

            if ($stmt->execute()) {
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Created new user: $full_name";
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                $_SESSION['success'] = "User added successfully";
            } else {
                $_SESSION['error'] = "Failed to add user";
            }
            break;

        case 'edit_user':
            $user_id = $_POST['user_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $role_id = $_POST['role_id'];
            $status = $_POST['status'];

            $stmt = $conn->prepare("UPDATE tbl_users SET full_name = ?, email = ?, role_id = ?, status = ? WHERE user_id = ?");
            $stmt->bind_param("ssisi", $full_name, $email, $role_id, $status, $user_id);

            if ($stmt->execute()) {
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Updated user: $full_name";
                $stmt = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();

                $_SESSION['success'] = "User updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update user";
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

                $_SESSION['success'] = "User deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete user";
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

                $_SESSION['success'] = "Role added successfully";
            } else {
                $_SESSION['error'] = "Failed to add role";
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

                $_SESSION['success'] = "Role updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update role";
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
                $_SESSION['error'] = "Cannot delete role that is in use";
                break;
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

                $_SESSION['success'] = "Role deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete role";
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

                $_SESSION['success'] = "Logs cleared successfully";
            } else {
                $_SESSION['error'] = "Failed to clear logs";
            }
            break;
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get all users
$users_query = "SELECT u.*, r.role_name, 
                (SELECT COUNT(*) FROM tbl_users WHERE role_id = u.role_id) as users_count 
                FROM tbl_users u 
                JOIN tbl_roles r ON u.role_id = r.role_id";
$users_result = $conn->query($users_query);
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

// Get all roles
$roles_query = "SELECT r.*, 
                (SELECT COUNT(*) FROM tbl_users WHERE role_id = r.role_id) as users_count 
                FROM tbl_roles r";
$roles_result = $conn->query($roles_query);
$roles = [];
while ($row = $roles_result->fetch_assoc()) {
    $roles[] = $row;
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
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="logout.php" class="btn btn-danger btn-sm ms-2">Logout</a>
                </div>
            </div>
        </nav>

        <div class="offcanvas offcanvas-start" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1"
            id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasScrollingLabel">Admin Menu</h5>
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
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users</h5>
                                    <h2 class="card-text">150</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Products</h5>
                                    <h2 class="card-text">45</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Orders</h5>
                                    <h2 class="card-text">89</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Revenue</h5>
                                    <h2 class="card-text">₱15,000</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Recent Transactions</h5>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Customer</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>#1234</td>
                                                    <td>John Doe</td>
                                                    <td>₱1,200</td>
                                                    <td><span class="badge bg-success">Completed</span></td>
                                                </tr>
                                                <!-- Add more rows as needed -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Quick Actions</h5>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary">Add New Product</button>
                                        <button class="btn btn-success">View Orders</button>
                                        <button class="btn btn-info">Generate Report</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Log Activities Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Activity Logs</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
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
                        <div class="card-body">
                            <!-- User Management Section -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">User Management</h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    <i class="fas fa-plus"></i> Add New User
                                </button>
                            </div>
                            <div class="table-responsive mb-4">
                                <table class="table table-hover" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['role_name']); ?></td>
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
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Role Management</h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                                    <i class="fas fa-plus"></i> Add New Role
                                </button>
                            </div>
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
                            <button class="btn btn-primary">Add New Product</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>Product 1</td>
                                            <td>Category 1</td>
                                            <td>₱100</td>
                                            <td>50</td>
                                            <td>
                                                <button class="btn btn-sm btn-info">Edit</button>
                                                <button class="btn btn-sm btn-danger">Delete</button>
                                            </td>
                                        </tr>
                                        <!-- Add more rows as needed -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Section -->
                <div id="transactions" class="content-section">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Transaction History</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>#1234</td>
                                            <td>2024-03-20</td>
                                            <td>John Doe</td>
                                            <td>₱1,200</td>
                                            <td><span class="badge bg-success">Completed</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-info">View Details</button>
                                                <button class="btn btn-sm btn-warning">Print Receipt</button>
                                            </td>
                                        </tr>
                                        <!-- Add more rows as needed -->
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

                    // Scroll to top after content change
                    scrollToTop();
                }, 300); // Match this with CSS transition time

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
    </script>

    <script>
        // Function to edit user
        function editUser(userId) {
            const users = <?php echo json_encode($users); ?>;
            const user = users.find(u => u.user_id == userId);
            if (user) {
                document.getElementById('edit_user_id_for_edit_modal').value = user.user_id;
                document.getElementById('edit_full_name').value = user.full_name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_role_id').value = user.role_id;
                document.getElementById('edit_status').value = user.status;
                new bootstrap.Modal(document.getElementById('editUserModal')).show();
            }
        }

        // Function to edit role
        function editRole(roleId) {
            const roles = <?php echo json_encode($roles); ?>;
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
            document.getElementById('delete_role_id').value = '';
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Function to delete role
        function deleteRole(roleId) {
            document.getElementById('delete_action').value = 'delete_role';
            document.getElementById('delete_role_id').value = roleId;
            document.getElementById('delete_user_id').value = '';
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Function to clear logs
        document.getElementById('clearLogs').addEventListener('click', function () {
            if (confirm('Are you sure you want to clear all logs?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'clear_logs';

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Display success/error messages
        <?php if (isset($_SESSION['success'])): ?>
            alert('<?php echo $_SESSION['success']; ?>');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            alert('<?php echo $_SESSION['error']; ?>');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
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
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_user">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
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
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id_for_edit_modal">
                        <input type="hidden" name="role_id" id="edit_user_modal_role_id_hidden">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role_id" id="edit_role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
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
                    <form method="POST" action="">
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Explicitly activate the Users tab when the DOM is fully loaded
            var usersTab = document.getElementById('users-tab');
            if (usersTab) {
                var bsTab = new bootstrap.Tab(usersTab);
                bsTab.show();
            }
        });
    </script>
</body>

</html>