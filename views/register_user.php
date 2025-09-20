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
    <title><?= htmlspecialchars(APP_NAME) ?> Register User</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">

<header>
    <a href="../index.php" class="logo"><i class="fas fa-cloud"></i> <?= htmlspecialchars(APP_NAME) ?></a>
    
    <nav class="main-nav">
        <a href="login.php">Login</a>
        <a href="register_organization.php">Register Organization</a>
    </nav>
    
    <button class="nav-toggle" aria-label="toggle navigation">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>
</header>

<div class="container">
    <div class="card" style="max-width: 600px; margin: 4rem auto;">
        <div class="card-header">
            <h2>Register New User</h2>
        </div>

        <!-- Example of a success message -->
        <!-- <div class="alert alert-success">Registration successful! You can now log in.</div> -->
        
        <!-- Example of an error message -->
        <!-- <div class="alert alert-danger">Please correct the errors below.</div> -->

        <form action="../controllers/register_controller.php" method="POST">
            <div class="form-group">
                <label for="organization_id">Organization:</label>
                <select id="organization_id" name="organization_id" required>
                    <option value="">Select an Organization</option>
                    <!-- Static examples where PHP loop would be -->
                    <option value="1">Tech Solutions Inc.</option>
                    <option value="2">Global Innovations LLC</option>
                    <option value="3">Creative Minds Co.</option>
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
            
            <button type="submit" name="register_user" class="btn btn-primary" style="width: 100%;">Register</button>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>

<footer>
    <p>&copy; 2023 Cloud Storage. All Rights Reserved.</p>
</footer>
</body>
</html>
