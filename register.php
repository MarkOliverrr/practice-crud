<?php
require_once 'autoload.php';

$database = new Database();
$session = new Session();
$user = new User($database->getConnection());
$auth = new Auth($user, $session);

if ($auth->isLoggedIn()) {
    $role = $auth->getCurrentUserRole();
    header('Location: ' . ($role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit();
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = Validator::sanitizeInput($_POST['first_name'] ?? '');
    $lastName = Validator::sanitizeInput($_POST['last_name'] ?? '');
    $email = Validator::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $gender = Validator::sanitizeInput($_POST['gender'] ?? '');
    $role = 'user';
    $address = Validator::sanitizeInput($_POST['address'] ?? '');
    
    $errors[] = Validator::validateRequired('first_name', $firstName, 'First name');
    $errors[] = Validator::validateRequired('last_name', $lastName, 'Last name');
    $errors[] = Validator::validateRequired('email', $email, 'Email');
    if (empty($errors[count($errors) - 1])) {
        $errors[count($errors) - 1] = Validator::validateEmailFormat($email);
        if (empty($errors[count($errors) - 1]) && $user->emailExists($email)) {
            $errors[count($errors) - 1] = "Email already exists.";
        }
    }
    
    $errors[] = Validator::validateRequired('password', $password, 'Password');
    if (empty($errors[count($errors) - 1])) {
        $errors[count($errors) - 1] = Validator::validatePasswordStrength($password);
    }
    
    $errors[] = Validator::validateRequired('confirm_password', $confirmPassword, 'Confirm password');
    if (empty($errors[count($errors) - 1])) {
        $errors[count($errors) - 1] = Validator::validatePasswordMatch($password, $confirmPassword);
    }
    
    $errors[] = Validator::validateGender($gender);
    $errors[] = Validator::validateRole($role);
    
    $errors = array_filter($errors);
    
    if (empty($errors)) {
        if ($user->register($firstName, $lastName, $email, $password, $gender, $role, $address)) {
            $success = true;
            $firstName = $lastName = $email = $password = $confirmPassword = $gender = $role = $address = '';
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - PHP Integrated Activity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .registration-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <div class="form-header">
                <h2>User Registration</h2>
                <p class="text-muted">Create your account</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Registration successful! You can now <a href="login.php">login</a>.
                </div>
            <?php endif; ?>
            
            <?php 
            if (!empty($errors)) {
                echo '<div class="alert alert-danger shadow-sm">';
                foreach ($errors as $error) {
                    echo '<div><i class="bi bi-exclamation-circle-fill me-2"></i>' . htmlspecialchars($error) . '</div>';
                }
                echo '</div>';
            }
            ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?= htmlspecialchars($firstName ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?= htmlspecialchars($lastName ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?= ($gender ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($gender ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($gender ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="role" class="form-label">Account Type</label>
                        <input type="text" class="form-control" value="User" disabled>
                        <small class="text-muted">New accounts are created as regular users</small>
                        <input type="hidden" name="role" value="user">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($address ?? '') ?></textarea>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
