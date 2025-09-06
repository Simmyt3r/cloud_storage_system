<?php
/**
 * User Registration Page
 *
 * This script handles the display of the user registration form.
 * It also prevents access for users who are already logged in.
 */

// --- 1. SESSION & LOGIC INITIALIZATION ---
// Always start the session at the very top of the script, before any output.
//session_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../models/Organization.php';

// --- 2. GUEST-ONLY ACCESS ---
// If a user is already logged in, redirect them to their dashboard.
// This prevents logged-in users from accessing the registration page.
if (is_logged_in()) {
    // Using a switch statement for cleaner, more readable logic.
    switch ($_SESSION['role']) {
        case 'super_admin':
            redirect('super_admin_dashboard.php');
            break;
        case 'admin':
            redirect('admin_dashboard.php');
            break;
        default:
            redirect('user_dashboard.php');
            break;
    }
}

// --- 3. DATA FETCHING ---
// Fetch approved organizations for the dropdown menu.
try {
    $org_model = new Organization($pdo);
    $approved_organizations = $org_model->getApproved();
} catch (PDOException $e) {
    // In case of a database error, log it and show a friendly message.
    error_log('Database error fetching Departments: ' . $e->getMessage());
    $approved_organizations = []; // Ensure the variable exists to prevent errors in the HTML.
    $page_error = 'Could not load Departments. Please try again later.';
}

// --- 4. FLASH MESSAGES ---
// Check for success or error messages from a previous action (e.g., form submission).
$error = $_SESSION['form_error'] ?? null;
$success = $_SESSION['form_success'] ?? null;

// Unset the session variables so the messages don't show up again on refresh.
unset($_SESSION['form_error'], $_SESSION['form_success']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 5. SECURITY: Escape dynamic content to prevent XSS attacks -->
    <title><?= htmlspecialchars(APP_NAME) ?> - Register User</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="login.php">Login</a></li>
                  <!--  <li><a href="register_org.php">Register Organization</a></li>-->
                  <!--  <li><a href="register_user.php">Register User</a></li>-->
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Register New User</h1>
            
            <?php if (isset($page_error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="../controllers/register_controller.php" method="POST">
                    <div class="form-group">
                        <label for="organization_id">Organization:</label>
                        <select id="organization_id" name="organization_id" required>
                            <option value="">Select</option>
                            <?php foreach ($approved_organizations as $org): ?>
                                <!-- 5. SECURITY: Escape all dynamic attributes and content -->
                                <option value="<?= htmlspecialchars($org['id']) ?>">
                                    <?= htmlspecialchars($org['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                    
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    
                    <button type="submit" name="register_user" class="btn btn-success">Register User</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
<?php
include "footer.php";
?>