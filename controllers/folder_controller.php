<?php
/**
 * Folder Controller
 *
 * Handles all actions related to folders, such as creation, renaming,
 * deletion, and password verification. It processes form submissions
 * and redirects the user with appropriate feedback messages.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/Folder.php';
require_once '../models/Permission.php'; // For permission checks

// Safely start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// All actions require a logged-in user
if (!is_logged_in()) {
    redirect('../views/login.php');
}

// Instantiate models
$folder_model = new Folder($pdo);
$permission_model = new Permission($pdo);

// --- 2. ACTION ROUTING ---
// Determine the action from the submitted form button.
$action = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_folder']))   $action = 'create';
    if (isset($_POST['rename_folder']))   $action = 'rename'; // Assumes 'rename_folder' from UI
    if (isset($_POST['delete_folder']))   $action = 'delete';
    if (isset($_POST['verify_password'])) $action = 'verify_password';
}

// --- 3. CONTROLLER LOGIC ---
try {
    switch ($action) {
        case 'create':
            handle_folder_create($pdo, $folder_model);
            break;
        case 'rename':
            handle_folder_rename($pdo, $folder_model);
            break;
        case 'delete':
            handle_folder_delete($pdo, $folder_model, $permission_model);
            break;
        case 'verify_password':
            handle_password_verify($pdo, $folder_model);
            break;
        default:
            // If no valid action, redirect with an error
            throw new Exception("Invalid folder action.");
    }
} catch (Exception $e) {
    // Catch any error, set a session message, and redirect back.
    $_SESSION['page_error'] = $e->getMessage();
    redirect_back();
}

// --- ACTION HANDLER FUNCTIONS ---

/**
 * Handles creation of a new folder.
 */
function handle_folder_create($pdo, $folder_model) {
    $name = sanitize_input($_POST['folder_name']);
    if (empty($name)) {
        throw new Exception("Folder name cannot be empty.");
    }

    $parent_folder_id = !empty($_POST['parent_folder_id']) ? (int)$_POST['parent_folder_id'] : null;
    $description = sanitize_input($_POST['folder_description']);
    $password = !empty($_POST['folder_password']) ? $_POST['folder_password'] : null;
    
    // Check if user has permission to create a folder here (important for subfolders)
    // For now, we assume they do.

    $success = $folder_model->create(
        get_user_organization_id(),
        $parent_folder_id,
        $name,
        $description,
        get_user_id(),
        $password
    );

    if ($success) {
        $_SESSION['page_success'] = "Folder '{$name}' created successfully.";
    } else {
        throw new Exception("Failed to create the folder due to a database error.");
    }
    redirect('../index.php');
}

/**
 * Handles renaming a folder.
 */
function handle_folder_rename($pdo, $folder_model) {
    $folder_id = (int)$_POST['folder_id'];
    $new_name = sanitize_input($_POST['new_name']);

    if (empty($new_name)) {
        throw new Exception("New folder name cannot be empty.");
    }
    
    // Security: Verify the folder exists and belongs to the user's org.
    $folder = $folder_model->findById($folder_id);
    if (!$folder || $folder['organization_id'] !== get_user_organization_id()) {
        throw new Exception("Folder not found or you do not have permission to edit it.");
    }
    
    // (Add permission check here if needed)

    if ($folder_model->update($folder_id, $new_name, $folder['description'])) { // Assuming update takes name and description
        $_SESSION['page_success'] = "Folder renamed to '{$new_name}' successfully.";
    } else {
        throw new Exception("Failed to rename the folder.");
    }
    redirect('../views/folder_view.php?folder_id=' . $folder_id);
}


/**
 * Handles deleting a folder.
 */
function handle_folder_delete($pdo, $folder_model, $permission_model) {
    $folder_id = (int)$_POST['folder_id'];
    
    // Security: Verify the folder exists and belongs to the user's org.
    $folder = $folder_model->findById($folder_id);
    if (!$folder || $folder['organization_id'] !== get_user_organization_id()) {
        throw new Exception("Folder not found or you do not have permission to delete it.");
    }

    // Placeholder for a real permission check
    // if (!$permission_model->hasPermission(get_user_id(), $folder_id, 'delete')) {
    //     throw new Exception("You do not have permission to delete this folder.");
    // }

    if ($folder_model->delete($folder_id)) {
        $_SESSION['page_success'] = "Folder deleted successfully.";
    } else {
        throw new Exception("Failed to delete the folder.");
    }
    redirect('../views/folders.php'); // Redirect to main folders list
}

/**
 * Handles verifying a folder password.
 */
function handle_password_verify($pdo, $folder_model) {
    $folder_id = (int)$_POST['folder_id'];
    $password = $_POST['folder_password'];
    
    if ($folder_model->verifyPassword($folder_id, $password)) {
        // Grant access for this session
        $_SESSION['folder_access_' . $folder_id] = true;
        // No success message needed, just redirect to the folder
    } else {
        // Set an error message and redirect
        $_SESSION['page_error'] = "Invalid password.";
    }
    // Redirect back to the same folder view page
    redirect("../views/folder_view.php?folder_id={$folder_id}");
}
?>

