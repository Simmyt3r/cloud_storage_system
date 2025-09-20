<?php
require_once '../includes/config.php';
 
 // Make sure session is started at the top of login.php
 // session_start();
 if (isset($_SESSION['login_error'])) {
    echo '<p style="color: red;">' . $_SESSION['login_error'] . '</p>';
    unset($_SESSION['login_error']); // Clear message after displaying it
 }
 
  // Display success message from password reset
  if (isset($_SESSION['login_success'])) {
      echo '<p style="color: green;">' . $_SESSION['login_success'] . '</p>';
      unset($_SESSION['login_success']);
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
    <title><?= htmlspecialchars(APP_NAME) ?> Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">

<header>
    <a href="../index.php" class="logo"><i class="fas fa-cloud"></i> <?= htmlspecialchars(APP_NAME) ?></a>
    
    <nav class="main-nav">
        <a href="register_organization.php">Register Organization</a>
        <a href="register_user.php">Register User</a>
    </nav>
    
    <button class="nav-toggle" aria-label="toggle navigation">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>
</header>

<div class="container">
    <!-- Center the card on the page -->
    <div class="card" style="max-width: 500px; margin: 4rem auto;">
        <div class="card-header">
            <h2>User Login</h2>
        </div>

        <!-- Example of an error message -->
        <!-- <div class="alert alert-danger">Invalid credentials. Please try again.</div> -->

        <form action="../controllers/auth_controller.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <!-- Make the button full-width for a better look -->
            <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="forgot_password.php">Forgot your password?</a>
        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>

<footer>
    <p>&copy; 2023 <?= htmlspecialchars(APP_NAME) ?>. All Rights Reserved.</p>
</footer>
</body>
</html>

<?php
include ('footer.php');
?>