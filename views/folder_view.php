<?php
/**
 * Folder View Page
 *
 * Displays the contents of a specific folder, including subfolders and files,
 * and provides a full suite of management tools (upload, rename, delete).
 * Now with password protection handling.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/Folder.php';
require_once '../models/File.php';
require_once '../models/Permission.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    redirect('login.php');
}

// --- 2. DATA VALIDATION & PERMISSIONS ---
$folder_id = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
$user_id = get_user_id();

if (!$folder_id) {
    redirect('user_dashboard.php');
}

// Initialize content variables
$subfolders = [];
$files = [];
$folder_path = [];
$has_access = false;
$show_password_modal = false;

try {
    $folder_model = new Folder($pdo);
    $file_model = new File($pdo);
    $permission_model = new Permission($pdo);

    $folder = $folder_model->findById($folder_id);

    // Verify folder exists and belongs to the user's organization
    if (!$folder || $folder['organization_id'] !== get_user_organization_id()) {
        $_SESSION['page_error'] = "Folder not found.";
        redirect('user_dashboard.php');
    }

    // --- 3. PASSWORD PROTECTION LOGIC ---
    if ($folder_model->isPasswordProtected($folder_id)) {
        if (isset($_SESSION['folder_access_' . $folder_id]) && $_SESSION['folder_access_' . $folder_id] === true) {
            $has_access = true;
        } else {
            $show_password_modal = true;
        }
    } else {
        $has_access = true;
    }

    // --- 4. DATA FETCHING ---
    if ($has_access) {
        $subfolders = $folder_model->getSubfolders($folder_id);
        $files = $file_model->getFilesByFolder($folder_id);
        $file_model->logFolderAccess($folder_id, $user_id, $_SERVER['REMOTE_ADDR']);
    }
    
    $folder_path = $folder_model->getFolderPath($folder_id);

} catch (PDOException $e) {
    error_log('Folder View Error: ' . $e->getMessage());
    $page_error = 'Could not load folder data. Please try again later.';
    $folder = ['name' => 'Error'];
}

// --- 5. HELPER FUNCTIONS ---
if (!function_exists('formatFileSize')) {
    function formatFileSize(int $bytes): string {
        if ($bytes <= 0) return '0 Bytes';
        $k = 1024; $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']; $i = floor(log($bytes, $k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}
if (!function_exists('getFileIcon')) {
    function getFileIcon(string $filename): string { $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)); switch ($extension) { case 'pdf': return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z"/><path d="M15 12H9"/><path d="M15 16H9"/></svg>'; case 'jpg': case 'jpeg': case 'png': case 'gif': return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>'; default: return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>'; } }
}

// --- 6. FLASH MESSAGES ---
$error = $_SESSION['page_error'] ?? null;
$success = $_SESSION['page_success'] ?? null;
unset($_SESSION['page_error'], $_SESSION['page_success']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME . ' - ' . ($folder['name'] ?? 'Folder')) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; }
        .page-actions button { margin-left: 10px; }
        .breadcrumb { margin: 15px 0; font-size: 0.9em; color: #555; }
        .breadcrumb a { color: #007bff; text-decoration: none; }
        .breadcrumb span { margin: 0 5px; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; margin-top: 20px; }
        .grid-item { background-color: #f9f9f9; border-radius: 8px; padding: 15px; text-align: center; border: 1px solid #e0e0e0; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; cursor: pointer; }
        .grid-item:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .context-menu { display: none; position: absolute; z-index: 1000; background-color: #fff; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); list-style: none; padding: 5px 0; margin: 0; min-width: 150px; }
        .context-menu li { padding: 8px 15px; cursor: pointer; }
        .context-menu li:hover { background-color: #f2f2f2; }
        .context-menu li.delete { color: #dc3545; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .locked-folder-message { text-align: center; padding: 40px; background-color: #f9f9f9; border-radius: 8px; border: 1px dashed #ccc; margin-top: 20px; }
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
                <h1><?= htmlspecialchars($folder['name']) ?></h1>
                <?php if ($has_access): ?>
                <div class="page-actions">
                    <button class="btn btn-primary" id="create-folder-btn">New Subfolder</button>
                    <button class="btn btn-success" id="upload-btn">Upload File</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="breadcrumb">
                <a href="user_dashboard.php">Home</a>
                <?php foreach ($folder_path as $path_part): ?>
                    <span>/</span>
                    <?php if ($path_part['id'] != $folder_id): ?>
                        <a href="folder_view.php?folder_id=<?= htmlspecialchars($path_part['id']) ?>"><?= htmlspecialchars($path_part['name']) ?></a>
                    <?php else: ?>
                        <span><?= htmlspecialchars($path_part['name']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <?php if ($has_access): ?>
                <div class="grid-container">
                    <?php foreach ($subfolders as $subfolder): ?>
                        <div class="grid-item item" data-item-type="folder" data-item-id="<?= $subfolder['id'] ?>" data-item-name="<?= htmlspecialchars($subfolder['name']) ?>" data-open-url="folder_view.php?folder_id=<?= $subfolder['id'] ?>">
                            <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#5c9ded" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg></div>
                            <p class="name"><?= htmlspecialchars($subfolder['name']) ?></p>
                        </div>
                    <?php endforeach; ?>
                   
<?php foreach ($files as $file): ?>
   <div class="grid-item item" data-open-url="../views/file_viewer.php?file_id=<?= $file['id'] ?>">

        <div class="icon"><?= getFileIcon($file['name']) ?></div>
        <p class="name"><?= htmlspecialchars($file['name']) ?></p>
        <p class="details"><?= formatFileSize($file['file_size']) ?></p>
    </div>
<?php endforeach; ?>
                </div>
                <?php if (empty($subfolders) && empty($files)): ?>
                    <div class="empty-state"><p>This folder is empty.</p></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="locked-folder-message">
                    <p>This folder is protected. Please enter the password to view its contents.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODALS -->
    <?php if ($has_access): ?>
    <div id="create-folder-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="create-folder-modal">&times;</span> <h2>New Subfolder</h2> <form action="../controllers/folder_controller.php" method="POST"> <input type="hidden" name="parent_folder_id" value="<?= $folder_id ?>"> <div class="form-group"><label>Folder Name:</label><input type="text" name="folder_name" required></div> <div class="form-group"><label>Description:</label><textarea name="folder_description"></textarea></div> <div class="form-group"><label>Password (Optional):</label><input type="password" name="folder_password"></div> <button type="submit" name="create_folder" class="btn btn-success">Create</button></form></div></div>
    <div id="upload-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="upload-modal">&times;</span> <h2>Upload File</h2> <form action="../controllers/file_controller.php" method="POST" enctype="multipart/form-data"> <input type="hidden" name="folder_id" value="<?= $folder_id ?>"> <div class="form-group"><label>Select file:</label><input type="file" name="file_upload" required></div> <button type="submit" name="upload_file" class="btn btn-success">Upload</button></form></div></div>
    <div id="rename-folder-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="rename-folder-modal">&times;</span> <h2>Rename Folder</h2> <form action="../controllers/folder_controller.php" method="POST"> <input type="hidden" name="folder_id" id="rename-folder-id"> <div class="form-group"><label>New Name:</label><input type="text" name="new_name" id="rename-folder-name" required></div> <button type="submit" name="rename_folder" class="btn btn-primary">Rename</button></form></div></div>
    <div id="delete-folder-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="delete-folder-modal">&times;</span> <h2>Delete Folder</h2> <p>Are you sure you want to delete "<strong id="delete-folder-name"></strong>"? All its contents will be lost.</p> <form action="../controllers/folder_controller.php" method="POST"> <input type="hidden" name="folder_id" id="delete-folder-id"> <button type="submit" name="delete_folder" class="btn btn-danger">Delete</button></form></div></div>
    <div id="rename-file-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="rename-file-modal">&times;</span> <h2>Rename File</h2> <form action="../controllers/file_controller.php" method="POST"> <input type="hidden" name="file_id" id="rename-file-id"> <div class="form-group"><label>New Name:</label><input type="text" name="new_name" id="rename-file-name" required></div> <button type="submit" name="rename_file" class="btn btn-primary">Rename</button></form></div></div>
    <div id="delete-file-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="delete-file-modal">&times;</span> <h2>Delete File</h2> <p>Are you sure you want to delete "<strong id="delete-file-name"></strong>"?</p> <form action="../controllers/file_controller.php" method="POST"> <input type="hidden" name="file_id" id="delete-file-id"> <button type="submit" name="delete_file" class="btn btn-danger">Delete</button></form></div></div>
    <?php endif; ?>

    <ul id="folder-context-menu" class="context-menu"> <li data-action="open">Open</li> <li data-action="rename">Rename</li> <li data-action="delete" class="delete">Delete</li> </ul>
    <ul id="file-context-menu" class="context-menu"> <li data-action="download">Download</li> <li data-action="rename">Rename</li> <li data-action="delete" class="delete">Delete</li> </ul>
    
    <?php if ($show_password_modal): ?>
    <div id="folder-password-modal" class="modal" style="display: block;">
        <div class="modal-content">
            <h2>Password Required</h2>
            <p>This folder is protected. Please enter the password to continue.</p>
            <form action="../controllers/folder_controller.php" method="POST">
                <input type="hidden" name="folder_id" value="<?= htmlspecialchars($folder_id) ?>">
                <div class="form-group"><label>Password:</label><input type="password" name="folder_password" required autofocus></div>
                <button type="submit" name="verify_password" class="btn btn-primary">Unlock</button>
                <a href="user_dashboard.php" class="btn btn-secondary">Go Back</a>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($has_access): ?>
        const modals = {
            createFolder: document.getElementById('create-folder-modal'),
            upload: document.getElementById('upload-modal'),
            renameFolder: document.getElementById('rename-folder-modal'),
            deleteFolder: document.getElementById('delete-folder-modal'),
            renameFile: document.getElementById('rename-file-modal'),
            deleteFile: document.getElementById('delete-file-modal'),
        };

        const folderContextMenu = document.getElementById('folder-context-menu');
        const fileContextMenu = document.getElementById('file-context-menu');
        let currentItem = null;

        document.getElementById('create-folder-btn').addEventListener('click', () => modals.createFolder.style.display = 'block');
        document.getElementById('upload-btn').addEventListener('click', () => modals.upload.style.display = 'block');

        function hideContextMenus() {
            folderContextMenu.style.display = 'none';
            fileContextMenu.style.display = 'none';
        }

        document.querySelectorAll('.item').forEach(item => {
            item.addEventListener('click', () => window.location.href = item.dataset.openUrl);
            item.addEventListener('contextmenu', e => {
                e.preventDefault();
                hideContextMenus();
                currentItem = item;
                const menu = item.dataset.itemType === 'folder' ? folderContextMenu : fileContextMenu;
                menu.style.display = 'block';
                menu.style.left = `${e.pageX}px`;
                menu.style.top = `${e.pageY}px`;
            });
        });

        window.addEventListener('click', (e) => {
            hideContextMenus();
            if (e.target.classList.contains('modal')) e.target.style.display = 'none';
        });

        document.querySelectorAll('.context-menu').forEach(menu => menu.addEventListener('click', e => {
            const action = e.target.dataset.action;
            if (!action || !currentItem) return;
            const itemType = currentItem.dataset.itemType;
            const itemId = currentItem.dataset.itemId;
            const itemName = currentItem.dataset.itemName;

            if (itemType === 'folder') {
                if (action === 'open') window.location.href = `folder_view.php?folder_id=${itemId}`;
                if (action === 'rename') {
                    modals.renameFolder.querySelector('#rename-folder-id').value = itemId;
                    modals.renameFolder.querySelector('#rename-folder-name').value = itemName;
                    modals.renameFolder.style.display = 'block';
                }
                if (action === 'delete') {
                    modals.deleteFolder.querySelector('#delete-folder-id').value = itemId;
                    modals.deleteFolder.querySelector('#delete-folder-name').textContent = itemName;
                    modals.deleteFolder.style.display = 'block';
                }
            } else if (itemType === 'file') {
                if (action === 'download') window.location.href = `../controllers/file_controller.php?download=1&file_id=${itemId}`;
                if (action === 'rename') {
                    modals.renameFile.querySelector('#rename-file-id').value = itemId;
                    modals.renameFile.querySelector('#rename-file-name').value = itemName;
                    modals.renameFile.style.display = 'block';
                }
                if (action === 'delete') {
                    modals.deleteFile.querySelector('#delete-file-id').value = itemId;
                    modals.deleteFile.querySelector('#delete-file-name').textContent = itemName;
                    modals.deleteFile.style.display = 'block';
                }
            }
        }));

        document.querySelectorAll('.close-btn').forEach(btn => btn.addEventListener('click', () => btn.closest('.modal').style.display = 'none'));
        <?php endif; ?>
    });
    </script>
</body>
</html>

<?php
include "footer.php";
?>