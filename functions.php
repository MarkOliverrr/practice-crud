<?php
// Functions file for User Management System

// Function to validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to validate password strength
function validatePassword($password) {
    return strlen($password) >= 8;
}

// Function to display errors using loop
function displayErrors($errors) {
    if (!empty($errors)) {
        echo '<div class="alert alert-danger">';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

// Function to check if user exists by email
function userExists($email, $conn) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to register new user
function registerUser($firstName, $lastName, $email, $password, $gender, $role, $address, $conn) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, gender, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $gender, $role);
    
    return $stmt->execute();
}

// Function to authenticate user login
function authenticateUser($email, $password, $conn) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role, status, login_attempts FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if account is inactive
        if ($user['status'] === 'inactive') {
            return ['success' => false, 'message' => 'Account is inactive. Please contact administrator.'];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Reset login attempts on successful login
            $stmt = $conn->prepare("UPDATE users SET login_attempts = 0 WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            return ['success' => true, 'user' => $user];
        } else {
            // Increment login attempts
            $newAttempts = $user['login_attempts'] + 1;
            $stmt = $conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
            $stmt->bind_param("ii", $newAttempts, $user['id']);
            $stmt->execute();
            
            // Block account after 3 failed attempts
            if ($newAttempts >= 3) {
                $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                return ['success' => false, 'message' => 'Account blocked due to too many failed attempts.'];
            }
            
            return ['success' => false, 'message' => 'Invalid password. Attempts remaining: ' . (3 - $newAttempts)];
        }
    }
    
    return ['success' => false, 'message' => 'Email not found.'];
}

// Function to get all users (for admin)
function getAllUsers($conn) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, gender, role, status, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// Function to search users
function searchUsers($searchTerm, $conn) {
    $searchTerm = "%" . $searchTerm . "%";
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, gender, role, status, created_at FROM users WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// Function to get user by ID
function getUserById($userId, $conn) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, gender, role, status, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to update user
function updateUser($userId, $firstName, $lastName, $email, $gender, $role, $conn) {
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, gender = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $firstName, $lastName, $email, $gender, $role, $userId);
    
    return $stmt->execute();
}

// Function to delete user
function deleteUser($userId, $conn) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    return $stmt->execute();
}

// Function to activate/deactivate user
function toggleUserStatus($userId, $status, $conn) {
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $userId);
    
    return $stmt->execute();
}

// Function to check if session is active
function isSessionActive() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to redirect based on role
function redirectBasedOnRole($role) {
    if ($role === 'admin') {
        header('Location: admin_dashboard.php');
        exit();
    } else {
        header('Location: user_dashboard.php');
        exit();
    }
}

// Function to protect pages
function protectPage() {
    if (!isSessionActive()) {
        header('Location: login.php');
        exit();
    }
}

// Function to logout
function logout() {
    session_start();
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
