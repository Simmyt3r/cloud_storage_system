<?php
/**
 * Create Organization Page (Super Admin Only)
 *
 * This form allows a super admin to create a new organization and its
 * initial administrator account.
 */

// --- 1. INITIALIZATION & SECURITY ---
session_start();
require_once '../includes/config.php';

// Authorization: Super admin access only.
if (!is_logged_in() || !is_super_admin()) {
    $_SESSION['page_error'] = "You do not have permission to access this page.";
    redirect('super_admin_dashboard.php');
}

// --- 2. FLASH MESSAGES ---
$error = $_SESSION['form_error'] ?? null;
$success = $_SESSION['form_success'] ?? null;
unset($_SESSION['form_error'], $_SESSION['form_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Create Organization</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="super_admin_dashboard.php">Dashboard</a></li>
                    <li><a href="manage_organizations.php">Manage Organizations</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Create New Organization</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="../controllers/register_controller.php" method="POST">
                    <div class="form-group">
                        <label for="org_name">Organization Name:</label>
                        <input type="text" id="org_name" name="org_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="org_description">Description:</label>
                        <textarea id="org_description" name="org_description" rows="4"></textarea>
                    </div>
                    
                    <h2>Administrator Account</h2>
                    
                    <div class="form-group">
                        <label for="admin_username">Admin Username:</label>
                        <input type="text" id="admin_username" name="admin_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Admin Email:</label>
                        <input type="email" id="admin_email" name="admin_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Admin Password:</label>
                        <input type="password" id="admin_password" name="admin_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_first_name">First Name:</label>
                        <input type="text" id="admin_first_name" name="admin_first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_last_name">Last Name:</label>
                        <input type="text" id="admin_last_name" name="admin_last_name" required>
                    </div>
                    
                    <button type="submit" name="register_org" class="btn btn-success">Create Organization</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
