<?php
require_once 'db.php';
require_once 'functions.php';

// Prevent back button access after login
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit();
}

$errors = [];
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form inputs in PHP variables
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!validateEmail($email)) {
        $errors[] = "Email format is invalid.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    
    // If no validation errors, authenticate user
    if (empty($errors)) {
        $authResult = authenticateUser($email, $password, $conn);
        
        if ($authResult['success']) {
            // Start session upon successful login
            session_start();
            
            // Store session variables
            $_SESSION['user_id'] = $authResult['user']['id'];
            $_SESSION['user_name'] = $authResult['user']['first_name'] . ' ' . $authResult['user']['last_name'];
            $_SESSION['role'] = $authResult['user']['role'];
            $_SESSION['email'] = $authResult['user']['email'];
            
            // Redirect based on role
            redirectBasedOnRole($authResult['user']['role']);
        } else {
            $errors[] = $authResult['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-container w-100">
            
            <div class="form-header">
                <div class="profile-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h2>Welcome Back!</h2>
                <p class="text-muted">Please sign in to continue</p>
            </div>
            
            <?php 
            // Display errors if there are any (assuming displayErrors is a function in functions.php)
            if (!empty($errors)) {
                echo '<div class="alert alert-danger shadow-sm">';
                foreach ($errors as $error) {
                    echo '<div><i class="bi bi-exclamation-circle-fill me-2"></i>' . htmlspecialchars($error) . '</div>';
                }
                echo '</div>';
            }
            ?>
            
            <form method="POST" action="">
                <div class="form-floating mb-3 position-relative">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>" required>
                    <label for="email"><i class="bi bi-envelope me-2 text-muted"></i>Email address</label>
                </div>
                
                <div class="form-floating mb-4 position-relative">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password"><i class="bi bi-lock me-2 text-muted"></i>Password</label>
                </div>
                
                <div class="d-grid gap-2 mb-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Sign In <i class="bi bi-box-arrow-in-right ms-2"></i>
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-muted mb-0">Don't have an account? 
                        <a href="register.php" class="text-decoration-none fw-bold">Register here</a>
                    </p>
                </div>
            </form>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>