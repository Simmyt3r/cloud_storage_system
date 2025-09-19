<?php
// Application configuration
session_start();

// Base URL of the application
define('BASE_URL', 'http://localhost/cloud_storage_system-1');

// Upload directory
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Application name
define('APP_NAME', 'iStorage');

// Security settings
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Include database connection
require_once 'db_connection.php';

function redirect($url) {
    header('Location: ' . $url);
        exit();
}

/**
 * A simple function to sanitize user input.
 * @param string $data The raw input data.
 * @return string The sanitized data.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to check if user is super admin
function is_super_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

// Function to get current user's organization ID
function get_user_organization_id() {
    return isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : null;
}


// Function to generate a secure hash for folder passwords
function hash_folder_password($password) {
    return password_hash($password, PASSWORD_HASH_ALGO);
}

// Function to verify folder password
function verify_folder_password($password, $hash) {
    return password_verify($password, $hash);
}
?>