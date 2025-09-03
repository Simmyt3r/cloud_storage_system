<?php
require_once 'includes/db_connection.php';

// Test login with superadmin/password
$username = 'superadmin';
$password = 'password';

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    echo "Login successful for user: " . $user['username'] . "\n";
    echo "User role: " . $user['role'] . "\n";
    echo "Organization ID: " . $user['organization_id'] . "\n";
} else {
    echo "Login failed\n";
}
?>