<?php
/**
 * File Controller
 *
 * This script acts as a central hub for handling all file-related actions,
 * such as uploading, downloading, renaming, and deleting files. It ensures
 * proper security checks, user permissions, and provides feedback.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/File.php';
require_once '../models/Folder.php';
require_once '../models/Permission.php'; // Assuming this is used for permission checks

// *** FIX: Define upload validation constants ***
// Define the maximum allowed file size in bytes (e.g., 100 MB).
define('MAX_FILE_SIZE', 100 * 1024 * 1024); 
// Define an array of allowed file extensions.
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip']);


// Safely start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// All actions in this controller require a user to be logged in.
if (!is_logged_in()) {
    redirect('../views/login.php');
}

// Instantiate models
$file_model = new File($pdo);
$folder_model = new Folder($pdo);
$permission_model = new Permission($pdo); // Assuming you have this model

// --- 2. ACTION ROUTING ---
// Determine which action to perform based on the request.
$action = $_POST['action'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download'])) {
    $action = 'download';
}

// For POST actions, determine the specific action from the button name.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_file'])) $action = 'upload';
    if (isset($_POST['rename_file'])) $action = 'rename';
    if (isset($_POST['delete_file'])) $action = 'delete';
}


// --- 3. CONTROLLER LOGIC ---
try {
    switch ($action) {
        // --- HANDLE FILE UPLOAD ---
        case 'upload':
            handle_file_upload($pdo, $file_model, $permission_model);
            break;

        // --- HANDLE FILE DOWNLOAD ---
        case 'download':
            handle_file_download($pdo, $file_model, $permission_model);
            break;
            
        // --- HANDLE FILE RENAME ---
        case 'rename':
            handle_file_rename($pdo, $file_model, $permission_model);
            break;

        // --- HANDLE FILE DELETION ---
        case 'delete':
            handle_file_delete($pdo, $file_model, $permission_model);
            break;

        default:
            // If no valid action is provided, redirect with an error.
            $_SESSION['page_error'] = "Invalid action performed.";
            redirect_back();
    }
} catch (PDOException $e) {
    // Catch any database errors, log them, and show a generic error to the user.
    error_log("Database Error in File Controller: " . $e->getMessage());
    $_SESSION['page_error'] = "A database error occurred. Please try again.";
    redirect('../views/user_dashboard.php');
} catch (Exception $e) {
    // Catch other general errors (e.g., file system errors).
    $_SESSION['page_error'] = $e->getMessage();
    redirect('../views/user_dashboard.php');
}


// --- ACTION HANDLER FUNCTIONS ---

/**
 * Handles the logic for uploading a new file.
 */
function handle_file_upload($pdo, $file_model, $permission_model) {
    // Validate required fields
    if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception("No file was selected for upload.");
    }

    $file = $_FILES['file_upload'];
    $folder_id = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
    $organization_id = get_user_organization_id();
    $uploaded_by = get_user_id();
    
    // --- Security & Validation ---
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("An error occurred during file upload. Error code: " . $file['error']);
    }
    // Check file size (ensure MAX_FILE_SIZE is defined in config.php)
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception("The uploaded file exceeds the maximum size limit.");
    }
    // Check file type (ensure ALLOWED_FILE_TYPES is an array in config.php)
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_FILE_TYPES)) {
        throw new Exception("This file type is not allowed.");
    }
    // Placeholder for write permission check
    // if (!$permission_model->hasPermission($uploaded_by, $folder_id, 'write')) {
    //     throw new Exception("You don't have permission to upload to this folder.");
    // }

    // --- File Processing ---
    $file_name = sanitize_input($file['name']);
    $upload_dir = UPLOAD_DIR . $organization_id . '/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception("Failed to create upload directory.");
        }
    }

    $unique_filename = uniqid('', true) . '.' . $file_extension;
    $file_path = $upload_dir . $unique_filename;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Save to database
        $success = $file_model->upload($folder_id, $organization_id, $file_name, $file_path, $file['size'], $file['type'], $uploaded_by);
        if ($success) {
            $_SESSION['page_success'] = "File uploaded successfully.";
        } else {
            // If DB insert fails, delete the orphaned file from the server
            unlink($file_path);
            throw new Exception("Failed to save file information to the database.");
        }
    } else {
        throw new Exception("Failed to move the uploaded file.");
    }
    redirect('../views/user_dashboard.php');
}

/**
 * Handles the logic for downloading a file.
 */
function handle_file_download($pdo, $file_model, $permission_model) {
    $file_id = (int)$_GET['file_id'];
    $user_id = get_user_id();

    $file = $file_model->findById($file_id);

    // --- Security & Validation ---
    if (!$file || $file['organization_id'] !== get_user_organization_id()) {
        die("File not found or access denied.");
    }
    // Placeholder for read permission check
    // if (!$permission_model->hasPermission($user_id, $file['folder_id'], 'read')) {
    //     die("You do not have permission to download this file.");
    // }
    if (!file_exists($file['file_path'])) {
        die("File does not exist on the server.");
    }

    // --- Serve File ---
    $file_model->logDownload($file_id, $user_id, $_SERVER['REMOTE_ADDR']);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file['file_path']));
    
    ob_clean();
    flush();
    readfile($file['file_path']);
    exit;
}

/**
 * Handles the logic for renaming a file.
 */
function handle_file_rename($pdo, $file_model, $permission_model) {
    $file_id = (int)$_POST['file_id'];
    $new_name = sanitize_input($_POST['new_name']);
    $user_id = get_user_id();

    $file = $file_model->findById($file_id);

    // --- Security & Validation ---
    if (!$file || $file['organization_id'] !== get_user_organization_id()) {
        throw new Exception("File not found or access denied.");
    }
    // Placeholder for write permission check
    // if (!$permission_model->hasPermission($user_id, $file['folder_id'], 'write')) {
    //     throw new Exception("You do not have permission to rename this file.");
    // }

    // --- Update Database ---
    if ($file_model->rename($file_id, $new_name)) {
        $_SESSION['page_success'] = "File renamed successfully.";
    } else {
        throw new Exception("Failed to rename the file in the database.");
    }
    redirect_back();
}

/**
 * Handles the logic for deleting a file.
 */
function handle_file_delete($pdo, $file_model, $permission_model) {
    $file_id = (int)$_POST['file_id'];
    $user_id = get_user_id();

    $file = $file_model->findById($file_id);

    // --- Security & Validation ---
    if (!$file || $file['organization_id'] !== get_user_organization_id()) {
        throw new Exception("File not found or access denied.");
    }
    // Placeholder for delete permission check
    // if (!$permission_model->hasPermission($user_id, $file['folder_id'], 'delete')) {
    //     throw new Exception("You do not have permission to delete this file.");
    // }

    // --- Deletion Process ---
    // Step 1: Delete the physical file from the server.
    if (file_exists($file['file_path'])) {
        if (!unlink($file['file_path'])) {
            throw new Exception("Could not delete the physical file. Please check server permissions.");
        }
    }
    
    // Step 2: Delete the file record from the database.
    if ($file_model->delete($file_id)) {
        $_SESSION['page_success'] = "File deleted successfully.";
    } else {
        // This case is rare but important: the file was deleted but the DB record remains.
        throw new Exception("Physical file was deleted, but failed to remove the database record.");
    }
    redirect('../views/user_dashboard.php');
}

?>
