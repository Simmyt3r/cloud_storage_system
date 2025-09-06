<?php
/**
 * File Management Page
 *
 * Displays a list of all files within a user's organization and provides
 * tools to upload, download, rename, and delete them.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../controllers/auth_controller.php';
require_once '../includes/functions.php';
require_once '../models/User.php';

require_once '../models/File.php';
require_once '../models/Folder.php';
require_once '../models/Organization.php';

if (!is_logged_in()) {
    redirect('login.php');
}

// --- 2. HELPER FUNCTIONS ---
// (Assuming formatFileSize and getFileIcon are available or defined in functions.php)
// For completeness, they are included here.
function formatFileSize(int $bytes): string {
    if ($bytes <= 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, $k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getFileIcon(string $filename): string {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'pdf': return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z"/><path d="M15 12H9"/><path d="M15 16H9"/></svg>';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>';
        case 'doc': case 'docx': return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z"/><path d="M12 18v-6"/><path d="M12 12H9"/><path d="M15 12h-3"/></svg>';
        default: return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>';
    }
}

// --- 3. DATA FETCHING ---
try {
    $org_id = get_user_organization_id();

    $file_model = new File($pdo);
    $folder_model = new Folder($pdo);
    
    $files = $file_model->getFilesByOrganization($org_id);
    $folders = $folder_model->getFoldersByOrganization($org_id, null); // For upload dropdown
    
    // Performance: Create a folder map to avoid N+1 queries in the table
    $all_org_folders = $folder_model->getAllFoldersByOrganization($org_id);
    $folder_map = array_column($all_org_folders, 'name', 'id');

} catch (PDOException $e) {
    error_log('Files Page Error: ' . $e->getMessage());
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
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .modal-header { padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .modal-footer { padding-top: 10px; text-align: right; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .file-icon-cell { width: 40px; text-align: center; }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="user_dashboard.php">Dashboard</a></li>
                    <li><a href="folder_view.php">Folders</a></li>
                    <li><a href="files.php">Files</a></li>
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h1>Manage Files</h1>
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
                            <th>Size</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($files)): ?>
                            <tr><td colspan="7">No files found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td class="file-icon-cell"><?= getFileIcon($file['name']) ?></td>
                                    <td><?= htmlspecialchars($file['name']) ?></td>
                                    <td><?= htmlspecialchars($folder_map[$file['folder_id']] ?? 'Root') ?></td>
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

    <!-- UPLOAD MODAL -->
    <div id="upload-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" data-modal-id="upload-modal">&times;</span>
            <h2>Upload New File</h2>
            <form action="../controllers/file_controller.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="file_upload">Select file:</label>
                    <input type="file" id="file_upload" name="file_upload" required>
                </div>
                <div class="form-group">
                    <label for="folder_id">Upload to Folder:</label>
                    <select id="folder_id" name="folder_id">
                        <option value="">Root Directory</option>
                        <?php foreach ($all_org_folders as $folder): ?>
                            <option value="<?= $folder['id'] ?>"><?= htmlspecialchars($folder['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="upload_file" class="btn btn-success">Upload</button>
            </form>
        </div>
    </div>

    <!-- RENAME MODAL -->
    <div id="rename-modal" class="modal">
        <div class="modal-content">
             <span class="close-btn" data-modal-id="rename-modal">&times;</span>
            <h2>Rename File</h2>
            <form action="../controllers/file_controller.php" method="POST">
                <input type="hidden" id="rename-file-id" name="file_id">
                <div class="form-group">
                    <label for="new-file-name">New File Name:</label>
                    <input type="text" id="new-file-name" name="new_name" required>
                </div>
                <button type="submit" name="rename_file" class="btn btn-primary">Rename</button>
            </form>
        </div>
    </div>

    <!-- DELETE MODAL -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" data-modal-id="delete-modal">&times;</span>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete the file "<strong id="delete-file-name"></strong>"?</p>
            <form action="../controllers/file_controller.php" method="POST">
                <input type="hidden" id="delete-file-id" name="file_id">
                <button type="button" class="btn btn-secondary close-btn" data-modal-id="delete-modal">Cancel</button>
                <button type="submit" name="delete_file" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            upload: document.getElementById('upload-modal'),
            rename: document.getElementById('rename-modal'),
            delete: document.getElementById('delete-modal')
        };

        // Open Modals
        document.getElementById('upload-btn').addEventListener('click', () => modals.upload.style.display = 'block');
        
        document.querySelectorAll('.rename-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('rename-file-id').value = btn.dataset.fileId;
                document.getElementById('new-file-name').value = btn.dataset.fileName;
                modals.rename.style.display = 'block';
            });
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('delete-file-id').value = btn.dataset.fileId;
                document.getElementById('delete-file-name').textContent = btn.dataset.fileName;
                modals.delete.style.display = 'block';
            });
        });

        // Close Modals
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.modalId;
                document.getElementById(modalId).style.display = 'none';
            });
        });

        window.addEventListener('click', e => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>
<?php
include "footer.php";
?>