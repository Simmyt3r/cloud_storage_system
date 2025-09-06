<?php
/**
 * File Management Page (Admin & Super Admin)
 *
 * Displays a comprehensive list of all files within an organization
 * and provides tools to upload, download, rename, and delete them.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/File.php';
require_once '../models/Folder.php';
require_once '../models/Organization.php';
require_once '../models/User.php';

// Authorization: Must be an admin or super admin
if (!is_logged_in() || (!is_admin() && !is_super_admin())) {
    redirect('login.php');
}

// --- 2. HELPER FUNCTIONS ---
// These functions are for display purposes within this view.
function formatFileSize(int $bytes): string {
    if ($bytes <= 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, $k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getFileIcon(string $filename): string {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $common_attrs = "width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'";
    switch ($extension) {
        case 'pdf': return "<svg {$common_attrs} stroke='#E74C3C'><path d='M14 2v4a2 2 0 0 0 2 2h4'/><path d='M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z'/><path d='M15 12H9'/><path d='M15 16H9'/></svg>";
        case 'jpg': case 'jpeg': case 'png': case 'gif': return "<svg {$common_attrs} stroke='#2ECC71'><rect x='3' y='3' width='18' height='18' rx='2' ry='2'></rect><circle cx='8.5' cy='8.5' r='1.5'></circle><polyline points='21 15 16 10 5 21'></polyline></svg>";
        case 'doc': case 'docx': return "<svg {$common_attrs} stroke='#3498DB'><path d='M14 2v4a2 2 0 0 0 2 2h4'/><path d='M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z'/><path d='M12 18v-6'/><path d='M12 12H9'/><path d='M15 12h-3'/></svg>";
        default: return "<svg {$common_attrs}><path d='M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z'></path><polyline points='13 2 13 9 20 9'></polyline></svg>";
    }
}

// --- 3. DATA FETCHING ---
try {
    $file_model = new File($pdo);
    $folder_model = new Folder($pdo);
    $org_model = new Organization($pdo);
    
    $files = [];
    $folders = [];
    $page_heading = "Manage Files";

    if (is_super_admin()) {
        $files = $file_model->getAllFiles(); // Assumes a new method to get ALL files
        $folders = $folder_model->getAllFolders(); // Assumes a new method
        $page_heading = "Manage All System Files";
    } else {
        $org_id = get_user_organization_id();
        $files = $file_model->getFilesByOrganization($org_id);
        $folders = $folder_model->getAllFoldersByOrganization($org_id);
    }
    
    // Performance: Create a lookup map for folder names to avoid N+1 queries in the loop
    $folder_map = array_column($folders, 'name', 'id');
    
} catch (PDOException $e) {
    error_log('Manage Files Page Error: ' . $e->getMessage());
    $page_error = 'Could not load file data. Please try again later.';
    $files = $folders = $folder_map = [];
}

// --- 4. FLASH MESSAGES ---
$error = $_SESSION['page_error'] ?? null;
$success = $_SESSION['page_success'] ?? null;
unset($_SESSION['page_error'], $_SESSION['page_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Manage Files</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; }
        .modal { display: none; /* Other modal styles from previous examples */ }
        .file-icon-cell { width: 40px; text-align: center; }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <?php if (is_super_admin()): ?>
                        <li><a href="super_admin_dashboard.php">Dashboard</a></li>
                        <li><a href="manage_organizations.php">Organizations</a></li>
                    <?php else: ?>
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <li><a href="manage_folders.php">Folders</a></li>
                    <?php endif; ?>
                    <li><a href="manage_files.php">Files</a></li>
                    <li><a href="manage_users.php">Users</a></li>
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h1><?= $page_heading ?></h1>
                <button class="btn btn-success" id="upload-btn">Upload New File</button>
            </div>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th class="file-icon-cell"></th>
                            <th>Name</th>
                            <th>Folder</th>
                            <?php if (is_super_admin()): ?><th>Organization</th><?php endif; ?>
                            <th>Size</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($files)): ?>
                            <tr><td colspan="<?= is_super_admin() ? '8' : '7' ?>">No files found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td class="file-icon-cell"><?= getFileIcon($file['name']) ?></td>
                                    <td><?= htmlspecialchars($file['name']) ?></td>
                                    <td><?= htmlspecialchars($folder_map[$file['folder_id']] ?? 'Root') ?></td>
                                    <?php if (is_super_admin()): ?><td><?= htmlspecialchars($file['organization_name'] ?? 'N/A') ?></td><?php endif; ?>
                                    <td><?= formatFileSize($file['file_size']) ?></td>
                                    <td><?= htmlspecialchars($file['uploaded_by_username']) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($file['uploaded_at'])) ?></td>
                                    <td>
                                        <a href="../controllers/file_controller.php?download=1&file_id=<?= $file['id'] ?>" class="btn btn-sm btn-info">Download</a>
                                        <button class="btn btn-sm btn-primary rename-btn" data-file-id="<?= $file['id'] ?>" data-file-name="<?= htmlspecialchars($file['name']) ?>">Rename</button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-file-id="<?= $file['id'] ?>" data-file-name="<?= htmlspecialchars($file['name']) ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODALS (Upload, Rename, Delete) would go here, similar to previous examples -->
    
    <script>
    // JavaScript for modals would go here, similar to previous examples
    </script>
</body>
</html>
