<?php
require_once '../includes/config.php';
require_once '../models/User.php';
require_once '../models/Organization.php';

$user_model = new User($pdo);
$org_model = new Organization($pdo);

// Handle organization registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_org'])) {
    // SECURITY: Only a logged-in super admin can create a new organization
    if (!is_logged_in() || !is_super_admin()) {
        $_SESSION['page_error'] = "You do not have permission to perform this action.";
        redirect('../views/login.php');
    }

    $org_name = sanitize_input($_POST['org_name']);
    $org_description = sanitize_input($_POST['org_description']);
    $admin_username = sanitize_input($_POST['admin_username']);
    $admin_email = sanitize_input($_POST['admin_email']);
    $admin_password = $_POST['admin_password'];
    $admin_first_name = sanitize_input($_POST['admin_first_name']);
    $admin_last_name = sanitize_input($_POST['admin_last_name']);
    
    // Check if organization already exists
    if ($org_model->findByName($org_name)) {
        $error = "Organization name already exists.";
    } else {
        // Create organization
        if ($org_model->create($org_name, $org_description)) {
            // Get the organization ID
            $organization = $org_model->findByName($org_name);
            $organization_id = $organization['id'];
            
            // Check if admin user already exists
            if ($user_model->findByUsername($admin_username) || $user_model->findByEmail($admin_email)) {
                $error = "Admin username or email already exists.";
                // Delete the organization since we couldn't create the admin user
                $org_model->delete($organization_id);
            } else {
                // Create admin user for the organization
                if ($user_model->create($organization_id, $admin_username, $admin_email, $admin_password, 
                                       $admin_first_name, $admin_last_name, 'admin')) {
                    $success = "Organization and admin account created successfully.";
                } else {
                    $error = "Failed to create admin user.";
                    // Delete the organization since we couldn't create the admin user
                    $org_model->delete($organization_id);
                }
            }
        } else {
            $error = "Failed to register organization.";
        }
    }
    // Redirect back to the creation page on error, or manage page on success
    if (isset($error)) {
        $_SESSION['form_error'] = $error;
        redirect('../views/create_organization.php');
    } else {
        $_SESSION['page_success'] = $success;
        redirect('../views/manage_organizations.php');
    }
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
        // Create user
        if ($user_model->create($organization_id, $username, $email, $password, $first_name, $last_name)) {
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

