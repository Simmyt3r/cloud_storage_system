<?php
/**
 * Folders Page
 *
 * Displays a list of all folders within a user's organization and provides
 * tools for creating, renaming, and deleting them.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/Folder.php';
require_once '../models/Organization.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    redirect('login.php');
}

// --- 2. DATA FETCHING ---
try {
    $org_id = get_user_organization_id();

    $folder_model = new Folder($pdo);
    $org_model = new Organization($pdo);

    // Fetch all folders for the user's organization
    $folders = $folder_model->getAllFoldersByOrganization($org_id);
    $organization = $org_model->findById($org_id);

} catch (PDOException $e) {
    error_log('Folders Page Error: ' . $e->getMessage());
    $page_error = 'Could not load folder data. Please try again later.';
    $folders = [];
    $organization = ['name' => 'Error'];
}

// --- 3. FLASH MESSAGES ---
$error = $_SESSION['page_error'] ?? null;
$success = $_SESSION['page_success'] ?? null;
unset($_SESSION['page_error'], $_SESSION['page_success']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - All Folders</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Styles for modern grid, context menu, and modals -->
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; }
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; margin-top: 20px; }
        .grid-item { background-color: #f9f9f9; border-radius: 8px; padding: 15px; text-align: center; border: 1px solid #e0e0e0; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; color: inherit; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; cursor: pointer; }
        .grid-item:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .grid-item .icon { margin-bottom: 15px; }
        .grid-item .name { font-weight: 500; margin: 0; word-break: break-all; }
        .context-menu { display: none; position: absolute; z-index: 1000; background-color: #fff; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); list-style: none; padding: 5px 0; margin: 0; min-width: 150px; }
        .context-menu li { padding: 8px 15px; cursor: pointer; }
        .context-menu li:hover { background-color: #f2f2f2; }
        .context-menu li.delete { color: #dc3545; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
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
                <h1>All Folders</h1>
                <button class="btn btn-success" id="create-folder-btn">Create New Folder</button>
            </div>
            <p>Organization: <?= htmlspecialchars($organization['name'] ?? 'Unknown') ?></p>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <div class="grid-container">
                <?php if (empty($folders)): ?>
                    <p>No folders have been created yet.</p>
                <?php else: ?>
                    <?php foreach ($folders as $folder): ?>
                        <div class="grid-item folder-item" 
                             data-item-id="<?= $folder['id'] ?>" 
                             data-item-name="<?= htmlspecialchars($folder['name']) ?>"
                             data-open-url="folder_view.php?folder_id=<?= $folder['id'] ?>">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#5c9ded" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                            <p class="name"><?= htmlspecialchars($folder['name']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <div id="create-folder-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="create-folder-modal">&times;</span> <h2>New Folder</h2> <form action="../controllers/folder_controller.php" method="POST"> <input type="hidden" name="parent_folder_id" value=""> <div class="form-group"><label>Folder Name:</label><input type="text" name="folder_name" required></div> <div class="form-group"><label>Description:</label><textarea name="folder_description"></textarea></div> <div class="form-group"><label>Password (Optional):</label><input type="password" name="folder_password"></div> <button type="submit" name="create_folder" class="btn btn-success">Create</button></form></div></div>
    <div id="rename-folder-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="rename-folder-modal">&times;</span> <h2>Rename Folder</h2> <form action="../controllers/folder_controller.php" method="POST"> <input type="hidden" name="folder_id" id="rename-folder-id"> <div class="form-group"><label>New Name:</label><input type="text" name="new_name" id="rename-folder-name" required></div> <button type="submit" name="rename_folder" class="btn btn-primary">Rename</button></form></div></div>
    <div id="delete-folder-modal" class="modal"> <div class="modal-content"> <span class="close-btn" data-modal-id="delete-folder-modal">&times;</span> <h2>Delete Folder</h2> <p>Are you sure you want to delete "<strong id="delete-folder-name"></strong>"? All its contents will be lost.</p> <form action="../controllers/folder_controller.php" method="POST"> <input type="hidden" name="folder_id" id="delete-folder-id"> <button type="submit" name="delete_folder" class="btn btn-danger">Delete</button></form></div></div>

    <!-- CONTEXT MENU -->
    <ul id="folder-context-menu" class="context-menu"> <li data-action="open">Open</li> <li data-action="rename">Rename</li> <li data-action="delete" class="delete">Delete</li> </ul>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            createFolder: document.getElementById('create-folder-modal'),
            renameFolder: document.getElementById('rename-folder-modal'),
            deleteFolder: document.getElementById('delete-folder-modal'),
        };
        const contextMenu = document.getElementById('folder-context-menu');
        let currentItem = null;

        document.getElementById('create-folder-btn').addEventListener('click', () => {
            // Ensure parent_folder_id is empty for root folder creation
            modals.createFolder.querySelector('input[name="parent_folder_id"]').value = '';
            modals.createFolder.style.display = 'block';
        });

        function hideContextMenu() {
            contextMenu.style.display = 'none';
        }

        document.querySelectorAll('.folder-item').forEach(item => {
            item.addEventListener('click', () => window.location.href = item.dataset.openUrl);
            item.addEventListener('contextmenu', e => {
                e.preventDefault();
                hideContextMenu();
                currentItem = item;
                contextMenu.style.display = 'block';
                contextMenu.style.left = `${e.pageX}px`;
                contextMenu.style.top = `${e.pageY}px`;
            });
        });

        window.addEventListener('click', (e) => {
            hideContextMenu();
            if (e.target.classList.contains('modal')) e.target.style.display = 'none';
        });

        contextMenu.addEventListener('click', e => {
            const action = e.target.dataset.action;
            if (!action || !currentItem) return;
            const itemId = currentItem.dataset.itemId;
            const itemName = currentItem.dataset.itemName;
            
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
        });

        document.querySelectorAll('.close-btn').forEach(btn => btn.addEventListener('click', () => btn.closest('.modal').style.display = 'none'));
    });
    </script>
</body>
</html>
<?php
include "footer.php";
?>