<?php
// Set error handling to catch all errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Function to handle errors and return JSON response
function handleError($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log the error details in a server log instead of displaying to the user
    // error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    handleError("Server error occurred. Please try again later.");
});

try {
    header('Content-Type: application/json');

    include '../config.php';

    if (!isset($_POST['action'])) {
        handleError('No action specified.');
    }

    $action = $_POST['action'];

    switch ($action) {
        case 'add_user':
            if (isset($_POST['full_name'], $_POST['email'], $_POST['password'], $_POST['role_id'])) {
                $full_name = htmlspecialchars(strip_tags(trim($_POST['full_name'])));
                $email = htmlspecialchars(strip_tags(trim($_POST['email'])));
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role_id = filter_var($_POST['role_id'], FILTER_SANITIZE_NUMBER_INT);

                // Basic validation
                if (empty($full_name) || empty($email) || empty($_POST['password']) || empty($role_id)) {
                    handleError('All fields are required.');
                }

                // Email format validation
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    handleError('Invalid email format.');
                }

                // Check if email already exists
                $stmt_check_email = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ? LIMIT 1");
                $stmt_check_email->bind_param("s", $email);
                $stmt_check_email->execute();
                $stmt_check_email->store_result();
                if ($stmt_check_email->num_rows > 0) {
                    handleError('Email already exists.');
                }
                $stmt_check_email->close();

                $stmt = $conn->prepare("INSERT INTO tbl_users (full_name, email, password, role_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $full_name, $email, $password, $role_id);

                if ($stmt->execute()) {
                     // Log activity (optional, based on your existing logging)
                    // session_start(); // Assuming session is already started in Dashboard.php
                    // if(isset($_SESSION['user_id'])) {
                    //     $user_id_log = $_SESSION['user_id'];
                    //     $action_log = "Created new user: $full_name";
                    //     $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    //     $stmt_log->bind_param("is", $user_id_log, $action_log);
                    //     $stmt_log->execute();
                    //     $stmt_log->close();
                    // }
                    echo json_encode(['success' => true, 'message' => 'User added successfully.']);
                } else {
                    handleError('Failed to add user.');
                }
                $stmt->close();
            } else {
                handleError('Missing user data.');
            }
            break;

        case 'edit_user':
            if (isset($_POST['user_id'], $_POST['full_name'], $_POST['email'], $_POST['role_id'], $_POST['status'])) {
                $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
                $full_name = htmlspecialchars(strip_tags(trim($_POST['full_name']))); // Sanitize as needed
                $email = htmlspecialchars(strip_tags(trim($_POST['email']))); // Sanitize as needed
                $role_id = filter_var($_POST['role_id'], FILTER_SANITIZE_NUMBER_INT);
                $status = htmlspecialchars(strip_tags(trim($_POST['status']))); // Sanitize as needed

                 // Basic validation
                if (empty($user_id) || empty($full_name) || empty($email) || empty($role_id) || empty($status)) {
                    handleError('All fields are required.');
                }

                 // Email format validation
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    handleError('Invalid email format.');
                }

                // Check if email already exists for another user
                $stmt_check_email = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ? AND user_id != ? LIMIT 1");
                $stmt_check_email->bind_param("si", $email, $user_id);
                $stmt_check_email->execute();
                $stmt_check_email->store_result();
                if ($stmt_check_email->num_rows > 0) {
                    handleError('Email already exists for another user.');
                }
                $stmt_check_email->close();

                $stmt = $conn->prepare("UPDATE tbl_users SET full_name = ?, email = ?, role_id = ?, status = ? WHERE user_id = ?");
                $stmt->bind_param("ssisi", $full_name, $email, $role_id, $status, $user_id);

                if ($stmt->execute()) {
                     // Log activity (optional)
                    // session_start(); // Assuming session is already started in Dashboard.php
                    // if(isset($_SESSION['user_id'])) {
                    //     $user_id_log = $_SESSION['user_id'];
                    //     $action_log = "Updated user with ID: $user_id";
                    //     $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    //     $stmt_log->bind_param("is", $user_id_log, $action_log);
                    //     $stmt_log->execute();
                    //     $stmt_log->close();
                    // }
                    echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
                } else {
                    handleError('Failed to update user.');
                }
                $stmt->close();
            } else {
                handleError('Missing user update data.');
            }
            break;

        case 'delete_user':
            if (isset($_POST['user_id'])) {
                $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);

                // Prevent deleting the currently logged in user
                session_start();
                if (isset($_SESSION['user_id']) && $user_id == $_SESSION['user_id']) {
                     handleError('Cannot delete your own account.');
                }

                // Check if it's the last admin
                $admin_role_id_query = "SELECT role_id FROM tbl_roles WHERE LOWER(role_name) = 'admin' LIMIT 1";
                $admin_role_result = $conn->query($admin_role_id_query);
                $admin_role = $admin_role_result->fetch_assoc();
                $admin_role_id = $admin_role['role_id'] ?? null;

                if ($admin_role_id) {
                    $is_target_admin_query = "SELECT COUNT(*) as count FROM tbl_users WHERE user_id = ? AND role_id = ?";
                    $stmt_is_admin = $conn->prepare($is_target_admin_query);
                    $stmt_is_admin->bind_param("ii", $user_id, $admin_role_id);
                    $stmt_is_admin->execute();
                    $is_target_admin_result = $stmt_is_admin->get_result();
                    $is_target_admin_row = $is_target_admin_result->fetch_assoc();
                    $stmt_is_admin->close();

                    if ($is_target_admin_row['count'] > 0) { // The user to be deleted is an admin
                         $admin_count_query = "SELECT COUNT(*) as count FROM tbl_users WHERE role_id = ?";
                         $stmt_admin_count = $conn->prepare($admin_count_query);
                         $stmt_admin_count->bind_param("i", $admin_role_id);
                         $stmt_admin_count->execute();
                         $admin_count_result = $stmt_admin_count->get_result();
                         $admin_count_row = $admin_count_result->fetch_assoc();
                         $stmt_admin_count->close();

                         if ($admin_count_row['count'] <= 1) { // This is the last admin
                             handleError('Cannot delete the last admin account.');
                         }
                    }
                }


                $stmt = $conn->prepare("DELETE FROM tbl_users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);

                if ($stmt->execute()) {
                     // Log activity (optional)
                    // if(isset($_SESSION['user_id'])) {
                    //     $user_id_log = $_SESSION['user_id'];
                    //     $action_log = "Deleted user with ID: $user_id";
                    //     $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    //     $stmt_log->bind_param("is", $user_id_log, $action_log);
                    //     $stmt_log->execute();
                    //     $stmt_log->close();
                    // }
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
                } else {
                    handleError('Failed to delete user.');
                }
                $stmt->close();
            } else {
                handleError('User ID not specified for deletion.');
            }
            break;

        case 'add_role':
             if (isset($_POST['role_name'])) {
                $role_name = htmlspecialchars(strip_tags(trim($_POST['role_name'])));

                // Basic validation
                if (empty($role_name)) {
                    handleError('Role name is required.');
                }

                // Check if role name already exists
                $stmt_check_role = $conn->prepare("SELECT role_id FROM tbl_roles WHERE LOWER(role_name) = LOWER(?) LIMIT 1");
                $stmt_check_role->bind_param("s", $role_name);
                $stmt_check_role->execute();
                $stmt_check_role->store_result();
                if ($stmt_check_role->num_rows > 0) {
                    handleError('Role name already exists.');
                }
                $stmt_check_role->close();

                $stmt = $conn->prepare("INSERT INTO tbl_roles (role_name) VALUES (?)");
                $stmt->bind_param("s", $role_name);

                if ($stmt->execute()) {
                     // Log activity (optional)
                    // session_start(); // Assuming session is already started in Dashboard.php
                    // if(isset($_SESSION['user_id'])) {
                    //     $user_id_log = $_SESSION['user_id'];
                    //     $action_log = "Created new role: $role_name";
                    //     $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    //     $stmt_log->bind_param("is", $user_id_log, $action_log);
                    //     $stmt_log->execute();
                    //     $stmt_log->close();
                    // }
                    echo json_encode(['success' => true, 'message' => 'Role added successfully.']);
                } else {
                    handleError('Failed to add role.');
                }
                $stmt->close();
             } else {
                handleError('Role name not specified.');
             }
            break;

        case 'edit_role':
             if (isset($_POST['role_id'], $_POST['role_name'])) {
                $role_id = filter_var($_POST['role_id'], FILTER_SANITIZE_NUMBER_INT);
                $role_name = htmlspecialchars(strip_tags(trim($_POST['role_name']))); // Sanitize as needed

                 // Basic validation
                if (empty($role_id) || empty($role_name)) {
                    handleError('Role ID and name are required.');
                }

                // Check if role name already exists for another role
                $stmt_check_role = $conn->prepare("SELECT role_id FROM tbl_roles WHERE LOWER(role_name) = LOWER(?) AND role_id != ? LIMIT 1");
                $stmt_check_role->bind_param("si", $role_name, $role_id);
                $stmt_check_role->execute();
                $stmt_check_role->store_result();
                if ($stmt_check_role->num_rows > 0) {
                    handleError('Role name already exists for another role.');
                }
                $stmt_check_role->close();

                $stmt = $conn->prepare("UPDATE tbl_roles SET role_name = ? WHERE role_id = ?");
                $stmt->bind_param("si", $role_name, $role_id);

                if ($stmt->execute()) {
                     // Log activity (optional)
                    // session_start(); // Assuming session is already started in Dashboard.php
                    // if(isset($_SESSION['user_id'])) {
                    //     $user_id_log = $_SESSION['user_id'];
                    //     $action_log = "Updated role with ID: $role_id";
                    //     $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    //     $stmt_log->bind_param("is", $user_id_log, $action_log);
                    //     $stmt_log->execute();
                    //     $stmt_log->close();
                    // }
                    echo json_encode(['success' => true, 'message' => 'Role updated successfully.']);
                } else {
                    handleError('Failed to update role.');
                }
                $stmt->close();
             } else {
                handleError('Missing role update data.');
             }
            break;

        case 'delete_role':
            if (isset($_POST['role_id'])) {
                $role_id = filter_var($_POST['role_id'], FILTER_SANITIZE_NUMBER_INT);

                // Prevent deleting the admin role
                $admin_role_id_query = "SELECT role_id FROM tbl_roles WHERE LOWER(role_name) = 'admin' LIMIT 1";
                $admin_role_result = $conn->query($admin_role_id_query);
                $admin_role = $admin_role_result->fetch_assoc();
                $admin_role_id = $admin_role['role_id'] ?? null;

                if ($admin_role_id && $role_id == $admin_role_id) {
                     handleError('Cannot delete the admin role.');
                }

                // Check if role is in use
                $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM tbl_users WHERE role_id = ?");
                $stmt_check->bind_param("i", $role_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $row_check = $result_check->fetch_assoc();
                $stmt_check->close();

                if ($row_check['count'] > 0) {
                    handleError('Cannot delete role that is in use.');
                }

                $stmt = $conn->prepare("DELETE FROM tbl_roles WHERE role_id = ?");
                $stmt->bind_param("i", $role_id);

                if ($stmt->execute()) {
                     // Log activity (optional)
                    // session_start(); // Assuming session is already started in Dashboard.php
                    // if(isset($_SESSION['user_id'])) {
                    //     $user_id_log = $_SESSION['user_id'];
                    //     $action_log = "Deleted role with ID: $role_id";
                    //     $stmt_log = $conn->prepare("INSERT INTO tbl_logs (user_id, action) VALUES (?, ?)");
                    //     $stmt_log->bind_param("is", $user_id_log, $action_log);
                    //     $stmt_log->execute();
                    //     $stmt_log->close();
                    // }
                    echo json_encode(['success' => true, 'message' => 'Role deleted successfully.']);
                } else {
                    handleError('Failed to delete role.');
                }
                $stmt->close();
            } else {
                handleError('Role ID not specified for deletion.');
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