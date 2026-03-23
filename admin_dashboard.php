<?php
require_once 'autoload.php';

$database = new Database();
$session = new Session();
$user = new User($database->getConnection());
$auth = new Auth($user, $session);

$auth->requireAdmin();

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$errors = [];
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            $userId = $_POST['user_id'] ?? 0;
            if ($userId > 0 && $userId != $auth->getCurrentUserId()) {
                if ($user->delete($userId)) {
                    $success = "User deleted successfully.";
                } else {
                    $errors[] = "Failed to delete user.";
                }
            }
            break;
            
        case 'toggle_status':
            $userId = $_POST['user_id'] ?? 0;
            $currentStatus = $_POST['current_status'] ?? '';
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            
            if ($userId > 0 && $userId != $auth->getCurrentUserId()) {
                if ($user->updateStatus($userId, $newStatus)) {
                    $success = "User status updated successfully.";
                } else {
                    $errors[] = "Failed to update user status.";
                }
            }
            break;
    }
}

$searchTerm = Validator::sanitizeInput($_GET['search'] ?? '');
$users = !empty($searchTerm) ? $user->search($searchTerm) : $user->getAll();

// Demonstrate different types of loops
$userCount = count($users);
$counter = 0;

// Function to display errors
function displayErrors($errors) {
    if (!empty($errors)) {
        echo '<div class="alert alert-danger m-3">';
        foreach ($errors as $error) {
            echo htmlspecialchars($error) . '<br>';
        }
        echo '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PHP Integrated Activity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
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
            <!-- Statistics Cards -->
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?= $userCount ?></h3>
                    <p class="text-muted">Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <?php 
                $activeUsers = 0;
                foreach ($users as $user) {
                    if ($user['status'] === 'active') $activeUsers++;
                }
                ?>
                <div class="stats-card text-center">
                    <h3><?= $activeUsers ?></h3>
                    <p class="text-muted">Active Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <?php 
                $adminUsers = 0;
                foreach ($users as $user) {
                    if ($user['role'] === 'admin') $adminUsers++;
                }
                ?>
                <div class="stats-card text-center">
                    <h3><?= $adminUsers ?></h3>
                    <p class="text-muted">Admin Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <?php 
                $regularUsers = 0;
                foreach ($users as $user) {
                    if ($user['role'] === 'user') $regularUsers++;
                }
                ?>
                <div class="stats-card text-center">
                    <h3><?= $regularUsers ?></h3>
                    <p class="text-muted">Regular Users</p>
                </div>
            </div>
        </div>

        <!-- Search and User Table -->
        <div class="user-table">
            <div class="p-3 border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4>User Management</h4>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control me-2" name="search" 
                                   placeholder="Search users..." value="<?= htmlspecialchars($searchTerm) ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success m-3"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php displayErrors($errors); ?>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    No users found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- Demonstrate foreach loop -->
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['gender']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($user['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                    <td class="action-buttons">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to <?= $user['status'] === 'active' ? 'deactivate' : 'activate' ?> this user?')">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $user['status'] ?>">
                                                <button type="submit" class="btn btn-sm btn-<?= $user['status'] === 'active' ? 'warning' : 'success' ?>">
                                                    <i class="bi bi-<?= $user['status'] === 'active' ? 'pause' : 'play' ?>"></i> 
                                                    <?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Demonstrate while loop -->
        <div class="mt-4">
            <h5>System Information (While Loop Demo)</h5>
            <div class="row">
                <?php 
                $systemInfo = [
                    'Total Users' => $userCount,
                    'Active Users' => $activeUsers,
                    'Admin Users' => $adminUsers,
                    'Regular Users' => $regularUsers
                ];
                
                $infoKeys = array_keys($systemInfo);
                $infoIndex = 0;
                
                // Demonstrate while loop
                while ($infoIndex < count($infoKeys)) {
                    $key = $infoKeys[$infoIndex];
                    $value = $systemInfo[$key];
                    ?>
                    <div class="col-md-3 mb-2">
                        <div class="p-2 bg-light rounded text-center">
                            <strong><?= $key ?>:</strong> <?= $value ?>
                        </div>
                    </div>
                    <?php
                    $infoIndex++;
                }
                ?>
            </div>
        </div>

        <!-- Demonstrate for loop -->
        <div class="mt-4">
            <h5>Recent Activity (For Loop Demo)</h5>
            <?php 
            $recentUsers = array_slice($users, 0, min(5, count($users)));
            ?>
            <div class="row">
                <?php 
                // Demonstrate for loop
                for ($i = 0; $i < count($recentUsers); $i++) {
                    $user = $recentUsers[$i];
                    ?>
                    <div class="col-md-12 mb-2">
                        <div class="p-2 bg-light rounded">
                            <i class="bi bi-person-plus"></i>
                            <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                            joined on <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            as <?= htmlspecialchars($user['role']) ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
