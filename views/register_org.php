<?php
require_once '../includes/config.php';

// If user is already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    if (is_super_admin()) {
        redirect('super_admin_dashboard.php');
    } elseif (is_admin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('user_dashboard.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Register Organization</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?php echo APP_NAME; ?></div>
            <nav>
                <ul>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register_org.php">Register Organization</a></li>
                    <li><a href="register_user.php">Register User</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Register New Organization</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
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
                    
                    <button type="submit" name="register_org" class="btn btn-success">Register Organization</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>