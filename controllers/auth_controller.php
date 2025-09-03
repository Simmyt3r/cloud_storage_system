<?php
// --- BEST PRACTICE ---
// 1. Start the session at the very top of the script, before any output.
session_start();

// It's good practice to include all dependencies at the top.
require_once '../includes/config.php';
require_once '../models/User.php';

// --- HELPER FUNCTIONS ---
// If these are in your config.php, you can remove them from here.
// I'm adding them so the script is self-contained and demonstrates the fix.

/**
 * Redirects to a new page and stops script execution.
 * @param string $url The URL to redirect to.
 */


// --- INITIALIZATION ---
// Initialize the user model
$user_model = new User($pdo);

// --- LOGOUT LOGIC ---
// 3. Check for logout action first. It's a simple, clean action.
if (isset($_GET['logout'])) {
    session_unset();    // Unset all session variables
    session_destroy();  // Destroy the session
    redirect('../views/login.php'); // Redirect to login page
}

// --- LOGIN LOGIC ---
// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $user = $user_model->authenticate($username, $password);
        
        if ($user) {
            // Regenerate session ID for security
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['organization_id'] = $user['organization_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            // Update last login
            $user_model->updateLastLogin($user['id']);
            
            // Redirect based on role
            switch ($user['role']) {
                case 'super_admin':
                    redirect('../views/super_admin_dashboard.php');
                    break;
                case 'admin':
                    redirect('../views/admin_dashboard.php');
                    break;
                default:
                    redirect('../views/user_dashboard.php');
                    break;
            }
        } else {
            // Set error message in session and redirect back to login
            $_SESSION['login_error'] = "Invalid username or password.";
            redirect('../views/login.php');
        }
    } else {
        // Set error message in session and redirect back to login
        $_SESSION['login_error'] = "Please fill in all fields.";
        redirect('../views/login.php');
    }
}


// --- LOGOUT LOGIC ---
// Checks if 'logout' is present in the URL (e.g., your-site.com/auth_controller.php?logout=1)
if (isset($_GET['logout'])) {
    // Unset all session variables like user_id, username, etc.
    session_unset();
    
    // Destroy the session data on the server
    session_destroy();
    
    // Redirect the user back to the login page
    redirect('../views/login.php');
}
/*
 * --- IMPORTANT ---
 * To display the login error message, add the following PHP code to your
 * `login.php` file, right before the login form.
 *
 * <?php
 * // Make sure session is started at the top of login.php
 * // session_start();
 * * if (isset($_SESSION['login_error'])) {
 * echo '<p style="color: red;">' . $_SESSION['login_error'] . '</p>';
 * unset($_SESSION['login_error']); // Clear message after displaying it
 * }
 * ?>
 *
 */
?>

