<?php
/**
 * Forgot Password Page
 *
 * Allows users to request a password reset link by entering their email address.
 */

// --- 1. INITIALIZATION ---
session_start();
require_once '../includes/config.php';

// --- 2. FLASH MESSAGES ---
$error = $_SESSION['page_error'] ?? null;
$success = $_SESSION['page_success'] ?? null;
unset($_SESSION['page_error'], $_SESSION['page_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register_user.php">Register User</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Forgot Your Password?</h1>
            <p>Enter your email address below, and we will send you a link to reset your password.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success // Don't escape this, as it contains a link for the demo ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="../controllers/auth_controller.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <button type="submit" name="request_reset" class="btn btn-primary">Send Reset Link</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
