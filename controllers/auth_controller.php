<?php
// --- BEST PRACTICE ---
// 1. Start the session at the very top of the script, before any output.
session_start();

// It's good practice to include all dependencies at the top.
require_once '../includes/config.php';
require_once '../models/User.php';

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

// --- PASSWORD RESET LOGIC ---

// Handle "Forgot Password" form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $token = $user_model->generatePasswordResetToken($email);

    if ($token) {
        // In a real application, you would email this link.
        // For this demo, we'll display it in the success message.
        $reset_link = BASE_URL . '/views/reset_password.php?token=' . urlencode($token);
        $message = "Password reset link has been generated. In a real app, this would be emailed. For now, please click here: <a href='{$reset_link}'>Reset Password</a>";
        $_SESSION['page_success'] = $message;
    } else {
        // Show a generic message to prevent email enumeration
        $_SESSION['page_success'] = "If an account with that email exists, a password reset link has been sent.";
    }
    redirect('../views/forgot_password.php');
}


// Handle "Reset Password" form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['page_error'] = "Passwords do not match.";
        redirect('../views/reset_password.php?token=' . urlencode($token));
    }

    if (strlen($password) < 8) { // Basic password strength check
        $_SESSION['page_error'] = "Password must be at least 8 characters long.";
        redirect('../views/reset_password.php?token=' . urlencode($token));
    }

    if ($user_model->resetPassword($token, $password)) {
        $_SESSION['login_success'] = "Your password has been reset successfully. You can now log in.";
        redirect('../views/login.php');
    } else {
        $_SESSION['page_error'] = "Invalid or expired password reset link. Please try again.";
        redirect('../views/forgot_password.php');
    }
}
?>

