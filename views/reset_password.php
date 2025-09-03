<?php
/**
 * Reset Password Page
 *
 * Allows a user to set a new password using a valid token from their email.
 */

// --- 1. INITIALIZATION & SECURITY ---
session_start();
require_once '../includes/config.php';
require_once '../models/User.php';

$token = $_GET['token'] ?? null;

if (!$token) {
    $_SESSION['login_error'] = "Invalid password reset link.";
    redirect('login.php');
}

// Check if the token is valid before showing the form
$user_model = new User($pdo);
$user = $user_model->findUserByResetToken($token);

if (!$user) {
    $_SESSION['login_error'] = "Invalid or expired password reset link. Please try again.";
    redirect('login.php');
}

// --- 2. FLASH MESSAGES ---
$error = $_SESSION['page_error'] ?? null;
unset($_SESSION['page_error']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Reset Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Set a New Password</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="../controllers/auth_controller.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn btn-success">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
