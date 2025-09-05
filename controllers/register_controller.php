<?php
require_once '../includes/config.php';
require_once '../models/User.php';
require_once '../models/Organization.php';

$user_model = new User($pdo);
$org_model = new Organization($pdo);

// Handle combined user and organization registration request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_organization'])) {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $org_name = sanitize_input($_POST['org_name']);
    $org_description = sanitize_input($_POST['org_description']);

    // --- Validation ---
    if ($user_model->findByUsername($username)) {
        $_SESSION['form_error'] = "Username already exists.";
        redirect('../views/register_organization.php');
    }
    if ($user_model->findByEmail($email)) {
        $_SESSION['form_error'] = "Email address is already in use.";
        redirect('../views/register_organization.php');
    }
    if ($org_model->findByName($org_name)) {
        $_SESSION['form_error'] = "An organization with this name already exists.";
        redirect('../views/register_organization.php');
    }

    // --- Process Registration ---
    // We need a dummy organization to create the user, since organization_id is a required field.
    // Let's use the System Admin organization ID (usually 1) as a temporary placeholder.
    $temp_org_id = 1; 
    
    $new_user_id = $user_model->create($temp_org_id, $username, $email, $password, $first_name, $last_name, 'user', 0); // Create as inactive user

    if ($new_user_id) {
        if ($org_model->create($org_name, $org_description, $new_user_id)) {
            $_SESSION['form_success'] = "Your registration and organization request have been submitted. You will be notified once a super admin approves your request.";
        } else {
            // Clean up the created user if organization creation fails
            // This part is crucial for data consistency
            // $user_model->delete($new_user_id); // You would need to add a delete method to your User model
            $_SESSION['form_error'] = "Could not create the organization. Please try again.";
        }
    } else {
        $_SESSION['form_error'] = "Could not create the user account. Please try again.";
    }
    redirect('../views/register_organization.php');
}


// Handle user registration (for existing organizations)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_user'])) {
    $organization_id = $_POST['organization_id'];
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    
    // Check if user already exists
    if ($user_model->findByUsername($username) || $user_model->findByEmail($email)) {
        $error = "Username or email already exists.";
    } else {
        // Create user (as inactive by default)
        if ($user_model->create($organization_id, $username, $email, $password, $first_name, $last_name, 'user', 0)) {
            $success = "User registered successfully. An administrator will need to activate your account.";
            
        } else {
            $error = "Failed to register user.";
        }
    }
    // Redirect back to the registration page with feedback
    if (isset($error)) {
        $_SESSION['form_error'] = $error;
    } else {
        $_SESSION['form_success'] = $success;
    }
    redirect('../views/register_user.php');
}
?>
