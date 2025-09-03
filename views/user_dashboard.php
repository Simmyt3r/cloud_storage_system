<?php
/**
 * User Dashboard Page
 *
 * Displays a user's primary dashboard, showing root-level folders and files
 * they have access to within their organization, with added management features.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/Folder.php';
require_once '../models/File.php';
require_once '../models/Organization.php';
require_once '../models/Permission.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    redirect('login.php');
}

// --- 2. HELPER FUNCTIONS (UNCHANGED) ---
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

// --- 3. DATA FETCHING (UNCHANGED) ---
try {
    $user_id = get_user_id();
    $org_id = get_user_organization_id();

    $folder_model = new Folder($pdo);
    $file_model = new File($pdo);
    $org_model = new Organization($pdo);
    $permission_model = new Permission($pdo);

    $organization = $org_model->findById($org_id);
    $folders = $folder_model->getFoldersByOrganization($org_id, null);
    $files = $file_model->getFilesByOrganization($org_id, null);

} catch (PDOException $e) {
    error_log('User Dashboard Error: ' . $e->getMessage());
    $page_error = 'Could not load dashboard data. Please try again later.';
    $organization = ['name' => 'Error'];
    $folders = $files = [];
}

// --- 4. FLASH MESSAGES (UNCHANGED) ---
$error = $_SESSION['page_error'] ?? null;
$success = $_SESSION['page_success'] ?? null;
unset($_SESSION['page_error'], $_SESSION['page_success']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; margin-top: 20px; }
        .grid-item { background-color: #f9f9f9; border-radius: 8px; padding: 15px; text-align: center; border: 1px solid #e0e0e0; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; }
        .grid-item:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .grid-item .icon { margin-bottom: 15px; color: #555; }
        .grid-item .icon.folder-icon { color: #5c9ded; }
        .grid-item .name { font-weight: 500; margin: 0; word-break: break-all; }
        .grid-item .details { font-size: 0.8rem; color: #777; margin-top: 5px; }
        .context-menu { display: none; position: absolute; z-index: 1000; background-color: #fff; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); list-style: none; padding: 5px 0; margin: 0; }
        .context-menu li { padding: 8px 15px; cursor: pointer; }
        .context-menu li:hover { background-color: #f2f2f2; }
        .context-menu li.delete { color: #dc3545; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="user_dashboard.php">Dashboard</a></li>
                    <li><a href="folders.php">Folders</a></li>
                    <li><a href="files.php">Files</a></li>
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <button class="btn btn-success" id="create-folder-btn">Create New Folder</button>
            </div>
            <p>Welcome, <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>!</p>
            <p>Department: <?= htmlspecialchars($organization['name'] ?? 'Unknown') ?></p>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <div class="grid-container">
                <!-- Folders -->
                <?php foreach ($folders as $folder): ?>
                    <div class="grid-item folder-item" data-folder-id="<?= htmlspecialchars($folder['id']) ?>" data-folder-name="<?= htmlspecialchars($folder['name']) ?>" data-open-url="folder_view.php?folder_id=<?= htmlspecialchars($folder['id']) ?>">
                        <div class="icon folder-icon"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg></div>
                        <p class="name"><?= htmlspecialchars($folder['name']) ?></p>
                        <p class="details">Created: <?= date('M j, Y', strtotime($folder['created_at'])) ?></p>
                    </div>
                <?php endforeach; ?>
                <!-- Files -->
                <?php foreach ($files as $file): ?>
                    <a href="../controllers/file_controller.php?download=1&file_id=<?= htmlspecialchars($file['id']) ?>" class="grid-item">
                        <div class="icon"><?= getFileIcon($file['name']) ?></div>
                        <p class="name"><?= htmlspecialchars($file['name']) ?></p>
                        <p class="details"><?= formatFileSize($file['file_size']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($folders) && empty($files)): ?>
                <div class="empty-state"><p>This directory is empty. Right-click to create a new folder.</p></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MODALS: Context Menu, Rename, Delete (Existing) -->
    <ul id="context-menu" class="context-menu">
        <li data-action="open">Open</li>
        <li data-action="rename">Rename</li>
        <li data-action="delete" class="delete">Delete</li>
    </ul>
    <div id="rename-modal" class="modal"> <!-- ... rename modal HTML ... --> </div>
    <div id="delete-modal" class="modal"> <!-- ... delete modal HTML ... --> </div>

    <!-- CREATE FOLDER MODAL (ENHANCED) -->
    <div id="create-folder-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" data-modal-id="create-folder-modal">&times;</span>
            <h2>Create New Folder</h2>
            <form action="../controllers/folder_controller.php" method="POST">
                <!-- This form creates a folder in the root directory (parent_folder_id is null) -->
                <div class="form-group">
                    <label for="folder_name">Folder Name:</label>
                    <input type="text" id="folder_name" name="folder_name" required>
                </div>
                <div class="form-group">
                    <label for="folder_description">Description (Optional):</label>
                    <textarea id="folder_description" name="folder_description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="folder_password">Password (Optional):</label>
                    <input type="password" id="folder_password" name="folder_password">
                </div>
                <button type="submit" name="create_folder" class="btn btn-success">Create Folder</button>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const createFolderBtn = document.getElementById('create-folder-btn');
        const createFolderModal = document.getElementById('create-folder-modal');
        const contextMenu = document.getElementById('context-menu');
        const renameModal = document.getElementById('rename-modal');
        const deleteModal = document.getElementById('delete-modal');
        let currentFolder = null;
        
        // --- Create Folder Button ---
        createFolderBtn.addEventListener('click', () => {
            createFolderModal.style.display = 'block';
        });

        // --- Context Menu Logic ---
        document.querySelectorAll('.folder-item').forEach(folder => {
            folder.addEventListener('contextmenu', e => {
                e.preventDefault();
                currentFolder = folder;
                contextMenu.style.display = 'block';
                contextMenu.style.left = `${e.pageX}px`;
                contextMenu.style.top = `${e.pageY}px`;
            });
            folder.addEventListener('click', e => {
                if (e.target.closest('a')) return;
                window.location.href = folder.dataset.openUrl;
            });
        });

        window.addEventListener('click', () => {
            contextMenu.style.display = 'none';
        });

        contextMenu.addEventListener('click', e => {
            const action = e.target.dataset.action;
            if (!action || !currentFolder) return;
            const folderId = currentFolder.dataset.folderId;
            const folderName = currentFolder.dataset.folderName;
            switch (action) {
                case 'open':
                    window.location.href = `folder_view.php?folder_id=${folderId}`;
                    break;
                case 'rename':
                    // This assumes your rename-modal has these elements
                    document.getElementById('rename-folder-id').value = folderId;
                    document.getElementById('new-folder-name').value = folderName;
                    renameModal.style.display = 'block';
                    break;
                case 'delete':
                    // This assumes your delete-modal has these elements
                    document.getElementById('delete-folder-id').value = folderId;
                    document.getElementById('delete-folder-name').textContent = folderName;
                    deleteModal.style.display = 'block';
                    break;
            }
        });

        // --- Generic Modal Closing Logic ---
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

