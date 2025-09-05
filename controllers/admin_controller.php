<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/Organization.php';
require_once '../models/User.php';
require_once '../models/Permission.php';


// --- ACTION ROUTING ---
$action = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_org'])) $action = 'approve_org';
    if (isset($_POST['reject_org'])) $action = 'reject_org';
    if (isset($_POST['update_user_status'])) $action = 'update_user_status';
    if (isset($_POST['delete_user'])) $action = 'delete_user';
    if (isset($_POST['update_user_role'])) $action = 'update_user_role';
    if (isset($_POST['update_user'])) $action = 'update_user'; 
}

try {
    $user_model = new User($pdo);
    $org_model = new Organization($pdo);

    switch ($action) {
        case 'approve_org':
             $org_id = $_POST['org_id'];
    
            // Get the organization details to find the user who requested it
            $organization = $org_model->findById($org_id);
            if ($organization && $organization['requested_by']) {
                $user_to_make_admin_id = $organization['requested_by'];
                
                if ($org_model->approve($org_id, $_SESSION['user_id'])) {
                    // Now, update the user's role to 'admin' and activate them
                    $user_model->updateUserRole($user_to_make_admin_id, 'admin');
                    $user_model->activate($user_to_make_admin_id);
                    
                    // Also, update the user's organization_id from the temporary one to the new one
                    $stmt = $pdo->prepare("UPDATE users SET organization_id = ? WHERE id = ?");
                    $stmt->execute([$org_id, $user_to_make_admin_id]);
                    
                    $_SESSION['page_success'] = "Organization approved successfully. The requesting user has been made an admin.";
                } else {
                    $_SESSION['page_error'] = "Failed to approve organization.";
                }
            } else {
                $_SESSION['page_error'] = "Could not find the requesting user for this organization.";
            }
            redirect('../views/super_admin_dashboard.php');
            break;
        case 'reject_org':
             if ($org_model->delete($org_id)) {
                $success = "Organization rejected and deleted successfully.";
            } else {
                $error = "Failed to reject organization.";
            }
            break;

        case 'update_user_status':
            handle_user_status_update($user_model);
            break;
        
        case 'delete_user':
            handle_user_delete($user_model);
            break;

        case 'update_user_role':
            handle_user_role_update($user_model);
            break;

        case 'update_user':
             handle_user_update($user_model);
             break;
    }
} catch (Exception $e) {
    $_SESSION['page_error'] = $e->getMessage();
    redirect('../views/manage_users.php');
}


function handle_user_status_update($user_model) {
    $user_id = (int)$_POST['user_id'];
    $new_status = (int)$_POST['is_active'];

    // Security check: an admin can't deactivate the super admin
    $user_to_change = $user_model->findById($user_id);
    if ($user_to_change['role'] === 'super_admin') {
        throw new Exception("Super admin account cannot be deactivated.");
    }

    if ($user_model->update($user_id, ['is_active' => $new_status])) {
        $_SESSION['page_success'] = "User status updated successfully.";
    } else {
        throw new Exception("Failed to update user status.");
    }
    redirect('../views/manage_users.php');
}

function handle_user_delete($user_model) {
    $user_id_to_delete = (int)$_POST['user_id'];
    
    if ($user_id_to_delete === get_user_id()) {
        throw new Exception("You cannot delete your own account.");
    }
    
    $user_to_delete = $user_model->findById($user_id_to_delete);
    if ($user_to_delete['role'] === 'super_admin') {
        throw new Exception("Super admin account cannot be deleted.");
    }

    if ($user_model->delete($user_id_to_delete)) {
        $_SESSION['page_success'] = "User deleted successfully.";
    } else {
        throw new Exception("Failed to delete user.");
    }
    redirect('../views/manage_users.php');
}

function handle_user_role_update($user_model) {
    if (!is_super_admin()) {
        throw new Exception("You do not have permission to change user roles.");
    }
    
    $user_id_to_change = (int)$_POST['user_id'];
    $new_role = $_POST['role'];

    if ($user_model->updateUserRole($user_id_to_change, $new_role)) {
        $_SESSION['page_success'] = "User role updated successfully.";
    } else {
        throw new Exception("Failed to update user role.");
    }
    redirect('../views/manage_users.php');
}

/**
 * Handles updating a user's details from the edit_user.php form.
 */
function handle_user_update($user_model) {
    $user_id = (int)$_POST['user_id'];
    $data = [
        'first_name' => sanitize_input($_POST['first_name']),
        'last_name'  => sanitize_input($_POST['last_name']),
        'email'      => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
        'password'   => $_POST['password']
    ];

    // Super admins can change role, organization, and status
    if (is_super_admin()) {
        $data['role'] = $_POST['role'];
        $data['organization_id'] = (int)$_POST['organization_id'];
        $data['is_active'] = (int)$_POST['is_active'];
    }

    // Security check: Ensure the admin has permission to edit this user
    $user_to_edit = $user_model->findById($user_id);
    if (!is_super_admin() && $user_to_edit['organization_id'] !== get_user_organization_id()) {
        throw new Exception("You do not have permission to edit this user.");
    }
    
    if ($user_model->update($user_id, $data)) {
        $_SESSION['page_success'] = "User updated successfully.";
    } else {
        // Even if no rows were changed, it's not necessarily an error.
        // But for this case, we'll treat it as such for feedback.
        throw new Exception("Failed to update user details. No changes were made.");
    }
    
    redirect('../views/manage_users.php');
}

?>

