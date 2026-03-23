<?php
require_once 'autoload.php';

$database = new Database();
$session = new Session();
$user = new User($database->getConnection());
$auth = new Auth($user, $session);

$auth->protectPage();

if ($auth->getCurrentUserRole() === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$errors = [];
$success = '';

$currentUser = $user->getById($auth->getCurrentUserId());

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $firstName = Validator::sanitizeInput($_POST['first_name'] ?? '');
        $lastName = Validator::sanitizeInput($_POST['last_name'] ?? '');
        $email = Validator::sanitizeInput($_POST['email'] ?? '');
        $gender = Validator::sanitizeInput($_POST['gender'] ?? '');
        
        $errors[] = Validator::validateRequired('first_name', $firstName, 'First name');
        $errors[] = Validator::validateRequired('last_name', $lastName, 'Last name');
        $errors[] = Validator::validateRequired('email', $email, 'Email');
        if (empty($errors[count($errors) - 1])) {
            $errors[count($errors) - 1] = Validator::validateEmailFormat($email);
            if (empty($errors[count($errors) - 1])) {
                $stmt = $database->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $auth->getCurrentUserId());
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors[count($errors) - 1] = "Email already exists.";
                }
            }
        }
        
        $errors[] = Validator::validateGender($gender);
        
        $errors = array_filter($errors);
        
        if (empty($errors)) {
            if ($user->update($auth->getCurrentUserId(), $firstName, $lastName, $email, $gender, 'user')) {
                $success = "Profile updated successfully.";
                $session->set('user_name', $firstName . ' ' . $lastName);
                $currentUser = $user->getById($auth->getCurrentUserId());
            } else {
                $errors[] = "Update failed. Please try again.";
            }
        }
    }
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors[] = Validator::validateRequired('current_password', $currentPassword, 'Current password');
        $errors[] = Validator::validateRequired('new_password', $newPassword, 'New password');
        if (empty($errors[count($errors) - 1])) {
            $errors[count($errors) - 1] = Validator::validatePasswordStrength($newPassword);
        }
        $errors[] = Validator::validateRequired('confirm_password', $confirmPassword, 'Confirm password');
        if (empty($errors[count($errors) - 1])) {
            $errors[count($errors) - 1] = Validator::validatePasswordMatch($newPassword, $confirmPassword);
        }
        
        $errors = array_filter($errors);
        
        if (empty($errors)) {
            $stmt = $database->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $auth->getCurrentUserId());
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            
            if (!password_verify($currentPassword, $userData['password'])) {
                $errors[] = "Current password is incorrect.";
            }
        }
        
        if (empty($errors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $database->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $auth->getCurrentUserId());
            if ($stmt->execute()) {
                $success = "Password changed successfully.";
            } else {
                $errors[] = "Password change failed. Please try again.";
            }
        }
    }
    
    if ($action === 'delete_account') {
        $password = $_POST['delete_password'] ?? '';
        
        if (empty($password)) {
            $errors[] = "Password is required to delete account.";
        } else {
            // Verify password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                if (deleteUser($_SESSION['user_id'], $conn)) {
                    session_destroy();
                    header('Location: login.php?message=account_deleted');
                    exit();
                } else {
                    $errors[] = "Failed to delete account.";
                }
            } else {
                $errors[] = "Password is incorrect.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - PHP Integrated Activity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard-container {
            padding: 20px;
        }
        .profile-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">User Dashboard</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-4">
                <div class="profile-card text-center">
                    <div class="profile-avatar">
                        <i class="bi bi-person"></i>
                    </div>
                    <h4><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></h4>
                    <p class="text-muted"><?= htmlspecialchars($currentUser['email']) ?></p>
                    
                    <div class="mt-3">
                        <span class="badge bg-primary"><?= htmlspecialchars($currentUser['role']) ?></span>
                        <span class="badge bg-success"><?= htmlspecialchars($currentUser['status']) ?></span>
                    </div>
                    
                    <div class="mt-3 text-muted">
                        <small>
                            <i class="bi bi-calendar"></i> 
                            Member since <?= date('F d, Y', strtotime($currentUser['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Profile Management -->
            <div class="col-md-8">
                <!-- Update Profile -->
                <div class="section-card">
                    <h5><i class="bi bi-person-gear"></i> Update Profile</h5>
                    <hr>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <?php 
                    if (!empty($errors)) {
                        echo '<div class="alert alert-danger">';
                        foreach ($errors as $error) {
                            echo '<div><i class="bi bi-exclamation-circle-fill me-2"></i>' . htmlspecialchars($error) . '</div>';
                        }
                        echo '</div>';
                    }
                    ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($currentUser['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($currentUser['last_name']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?= $currentUser['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $currentUser['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $currentUser['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="section-card">
                    <h5><i class="bi bi-shield-lock"></i> Change Password</h5>
                    <hr>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </form>
                </div>

                <!-- Delete Account -->
                <div class="section-card border-danger">
                    <h5 class="text-danger"><i class="bi bi-trash"></i> Delete Account</h5>
                    <hr>
                    <p class="text-muted">Warning: This action cannot be undone. All your data will be permanently deleted.</p>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">
                        <input type="hidden" name="action" value="delete_account">
                        
                        <div class="mb-3">
                            <label for="delete_password" class="form-label">Enter your password to confirm</label>
                            <input type="password" class="form-control" id="delete_password" name="delete_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete My Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
