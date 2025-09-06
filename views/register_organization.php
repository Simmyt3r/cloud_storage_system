<?php
/**
 * New Organization and User Registration Page
 *
 * Allows a new user to register themselves and request the creation of a new organization simultaneously.
 * This streamlines the onboarding process for new teams.
 */

// --- 1. INITIALIZATION & SECURITY ---
session_start();
require_once '../includes/config.php';

// If a user is already logged in, they should not be on this page. Redirect them.
if (is_logged_in()) {
    redirect('user_dashboard.php'); 
}

// --- 2. FLASH MESSAGES ---
// Display and clear any status messages from a previous registration attempt.
$error = $_SESSION['form_error'] ?? null;
$success = $_SESSION['form_success'] ?? null;
unset($_SESSION['form_error'], $_SESSION['form_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Register Your Organization</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register_user.php">Register as User</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Register Your Organization</h1>
            <p>Create your user account and submit your organization for approval by a super admin.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="../controllers/register_controller.php" method="POST">
                    <h2>Your Details</h2>
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                     <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <hr style="margin: 20px 0;">

                    <h2>Organization Details</h2>
                    <div class="form-group">
                        <label for="org_name">Organization Name:</label>
                        <input type="text" id="org_name" name="org_name" required>
                    </div>
                    <div class="form-group">
                        <label for="org_description">Organization Description:</label>
                        <textarea id="org_description" name="org_description" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="request_organization" class="btn btn-success">Submit for Approval</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php
include "footer.php";
?>