<?php
require_once '../includes/config.php';
require_once '../models/Organization.php';
require_once '../models/User.php';
require_once '../models/Permission.php';

$org_model = new Organization($pdo);
$user_model = new User($pdo);
$permission_model = new Permission($pdo);

// Handle organization approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_org'])) {
    $org_id = $_POST['org_id'];
    
    if ($org_model->approve($org_id, $_SESSION['user_id'])) {
        $success = "Organization approved successfully.";
    } else {
        $error = "Failed to approve organization.";
    }
}

// Handle organization rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_org'])) {
    $org_id = $_POST['org_id'];
    
    if ($org_model->delete($org_id)) {
        $success = "Organization rejected and deleted successfully.";
    } else {
        $error = "Failed to reject organization.";
    }
}

// Handle permission granting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grant_permission'])) {
    $user_id = $_POST['user_id'];
    $folder_id = $_POST['folder_id'];
    $permission_level = $_POST['permission_level'];
    $granted_by = $_SESSION['user_id'];
    
    if ($permission_model->grant($user_id, $folder_id, $permission_level, $granted_by)) {
        $success = "Permission granted successfully.";
    } else {
        $error = "Failed to grant permission.";
    }
}

// Handle permission revocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_permission'])) {
    $user_id = $_POST['user_id'];
    $folder_id = $_POST['folder_id'];
    
    if ($permission_model->revoke($user_id, $folder_id)) {
        $success = "Permission revoked successfully.";
    } else {
        $error = "Failed to revoke permission.";
    }

}


?>