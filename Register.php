<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php';

// Set JSON header for AJAX requests
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim(strtolower($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
        exit();
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Get customer role ID
    $role_query = $conn->prepare("SELECT role_id FROM tbl_roles WHERE role_name = 'customer'");
    $role_query->execute();
    $role_query->bind_result($role_id);
    if (!$role_query->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Customer role not found.']);
        exit();
    }
    $role_query->close();

    // Handle profile picture upload (optional)
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array(strtolower($filetype), $allowed)) {
            $upload_dir = 'uploads/profile_pictures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $profile_picture = $upload_path;
            }
        }
    }

    // Insert user
    $create_date = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO tbl_users (full_name, role_id, email, username, password, profile_picture, create_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssss", $full_name, $role_id, $email, $username, $hashed_password, $profile_picture, $create_date);

    if ($stmt->execute()) {
        // Optionally insert into customer table
        $insert_customer = $conn->prepare("INSERT INTO tbl_customer (customer_name, contact_information, address) VALUES (?, ?, ?)");
        $insert_customer->bind_param("sss", $username, $contact_number, $address);
        $insert_customer->execute();
        $insert_customer->close();

        echo json_encode(['success' => true, 'message' => 'Registration successful! Please log in.']);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $stmt->error]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="Register.css">
    
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Create an Account</h2>
            
            <form id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name:</label>
                    <input type="text" class="form-control" 
                           id="full_name" name="full_name">
                    <div class="error"></div>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" 
                           id="username" name="username">
                    <div class="error"></div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" 
                           id="email" name="email">
                    <div class="error"></div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <div class="password-field-wrapper">
                        <input type="password" class="form-control" 
                               id="password" name="password">
                        <button type="button" class="password-toggle" tabindex="-1">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    <div class="error"></div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password:</label>
                    <div class="password-field-wrapper">
                        <input type="password" class="form-control" 
                               id="confirm_password" name="confirm_password">
                        <button type="button" class="password-toggle" tabindex="-1">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                    <div class="error"></div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address:</label>
                    <textarea class="form-control" 
                              id="address" name="address" rows="3"></textarea>
                    <div class="error"></div>
                </div>

                <div class="mb-3">
                    <label for="contact_number" class="form-label">Contact Number:</label>
                    <input type="text" class="form-control" 
                           id="contact_number" name="contact_number" placeholder="09XXXXXXXXX">
                    <div class="error"></div>
                </div>

                <div class="mb-3">
                    <label for="profile_picture" class="form-label">Profile Picture:</label>
                    <input type="file" class="form-control" 
                           id="profile_picture" name="profile_picture" accept="image/*">
                    <div class="error"></div>
                    <img id="preview" class="preview-image d-none">
                </div>

                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>

            <div class="text-center mt-3">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="successMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="errorMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="Register.js"></script>
</body>
</html>