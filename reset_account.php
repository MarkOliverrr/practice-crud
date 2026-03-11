<?php
require_once 'db.php';

// Reset admin account (for testing purposes)
$stmt = $conn->prepare("UPDATE users SET login_attempts = 0, status = 'active' WHERE email = ?");
$stmt->bind_param("s", $email);

$emails = ['admin@example.com', 'john@example.com'];

foreach ($emails as $email) {
    $stmt->execute();
    echo "Reset account: $email<br>";
}

echo "<br><a href='login.php'>Go to Login</a>";
?>
