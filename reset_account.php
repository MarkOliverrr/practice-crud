<?php
require_once 'autoload.php';

$database = new Database();
$user = new User($database->getConnection());

$emails = ['admin@example.com', 'john@example.com'];

foreach ($emails as $email) {
    $userData = $user->getByEmail($email);
    if ($userData) {
        $user->updateStatus($userData['id'], 'active');
        $user->resetLoginAttempts($userData['id']);
        echo "Reset account: $email<br>";
    }
}

echo "<br><a href='login.php'>Go to Login</a>";
?>
