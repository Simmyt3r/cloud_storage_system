<?php
require_once '../includes/config.php';
 
 // Make sure session is started at the top of login.php
 // session_start();
 if (isset($_SESSION['login_error'])) {
 echo '<p style="color: red;">' . $_SESSION['login_error'] . '</p>';
 unset($_SESSION['login_error']); // Clear message after displaying it
  }

 
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
    <title><?php echo APP_NAME; ?> - Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?php echo APP_NAME; ?></div>
            <nav>
                <ul>
                  <!--  <li><a href="login.php">Login</a></li>-->
                    <!--<li><a href="register_org.php">Register Organization</a></li> -->
                    <li><a href="register_user.php">Register User</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>User Login</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="../controllers/auth_controller.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>