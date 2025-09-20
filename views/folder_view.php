<?php
/**
 * Folder View Page
 *
 * Displays the contents of a specific folder, including subfolders and files,
 * and provides a full suite of management tools with a consistent UI.
 * Handles password protection for secure access.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/Folder.php';
require_once '../models/File.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    redirect('login.php');
}

// --- 2. HELPER FUNCTIONS ---
// These functions are included here to ensure they are always available to this script.
function formatFileSize(int $bytes): string {
    if ($bytes <= 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, $k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getFileIcon(string $filename, $size = "24"): string {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $common_attrs = "width='{$size}' height='{$size}' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'";

    switch ($extension) {
        case 'pdf': 
            return "<svg {$common_attrs} stroke='#E74C3C'><path d='M14 2v4a2 2 0 0 0 2 2h4'/><path d='M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z'/><path d='M15 12H9'/><path d='M15 16H9'/></svg>";
        case 'jpg': case 'jpeg': case 'png': case 'gif':
            return "<svg {$common_attrs} stroke='#2ECC71'><rect x='3' y='3' width='18' height='18' rx='2' ry='2'></rect><circle cx='8.5' cy='8.5' r='1.5'></circle><polyline points='21 15 16 10 5 21'></polyline></svg>";
        case 'doc': case 'docx':
            return "<svg {$common_attrs} stroke='#3498DB'><path d='M14 2v4a2 2 0 0 0 2 2h4'/><path d='M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z'/><path d='M12 18v-6'/><path d='M12 12H9'/><path d='M15 12h-3'/></svg>";
        case 'zip': case 'rar': case '7z':
             return "<svg {$common_attrs} stroke='#F1C40F'><line x1='10' y1='1' x2='10' y2='23'></line><path d='M2 8.5A2.5 2.5 0 0 1 4.5 6h15A2.5 2.5 0 0 1 22 8.5v7a2.5 2.5 0 0 1-2.5 2.5h-15A2.5 2.5 0 0 1 2 15.5v-7Z'></path><line x1='6' y1='12' x2='6' y2='12'></line><line x1='14' y1='12' x2='14' y2='12'></line></svg>";
        default:
            return "<svg {$common_attrs} stroke='#95A5A6'><path d='M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z'></path><polyline points='13 2 13 9 20 9'></polyline></svg>";
    }
}


// --- 3. DATA VALIDATION & PERMISSIONS ---
$folder_id = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
if (!$folder_id) {
    redirect('user_dashboard.php');
}

$user_id = get_user_id();
$folder = null;
$items = [];
$folder_path = [];
$has_access = false;
$show_password_modal = false;
$page_error = null;

try {
    $folder_model = new Folder($pdo);
    $file_model = new File($pdo);

    $folder = $folder_model->findById($folder_id);

    if (!$folder || $folder['organization_id'] !== get_user_organization_id()) {
        $_SESSION['page_error'] = "Folder not found or you don't have permission to access it.";
        redirect('user_dashboard.php');
    }

    // --- 4. PASSWORD PROTECTION & ACCESS LOGIC ---
    if ($folder_model->isPasswordProtected($folder_id)) {
        if (isset($_SESSION['folder_access_' . $folder_id]) && $_SESSION['folder_access_' . $folder_id] === true) {
            $has_access = true;
        } else {
            $show_password_modal = true;
        }
    } else {
        $has_access = true;
    }

    // --- 5. DATA FETCHING & PROCESSING ---
    if ($has_access) {
        $file_model->logFolderAccess($folder_id, $user_id, $_SERVER['REMOTE_ADDR']);
        
        $subfolders = $folder_model->getSubfolders($folder_id);
        $files = $file_model->getFilesByFolder($folder_id);

        foreach ($subfolders as $subfolder) {
            $items[] = [
                'type' => 'folder', 'id' => $subfolder['id'], 'name' => $subfolder['name'],
                'date' => strtotime($subfolder['updated_at']), 'size' => -1
            ];
        }
        foreach ($files as $file) {
            $items[] = [
                'type' => 'file', 'id' => $file['id'], 'name' => $file['name'],
                'date' => strtotime($file['uploaded_at']), 'size' => (int)$file['file_size']
            ];
        }
    }
    
    $folder_path = $folder_model->getFolderPath($folder_id);

} catch (PDOException $e) {
    error_log('Folder View Error: ' . $e->getMessage());
    $page_error = 'Could not load folder data. Please try again later.';
    $folder = ['name' => 'Error'];
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2; --success-color: #50e3c2; --danger-color: #e24a4a;
            --bg-color: #f4f7f9; --sidebar-bg: #ffffff; --card-bg: #ffffff;
            --text-color: #333; --text-muted: #888; --border-color: #e0e0e0;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0; font-family: 'Inter', sans-serif; background-color: var(--bg-color);
            color: var(--text-color); display: flex; height: 100vh; overflow: hidden;
        }
        .sidebar {
            width: 260px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color);
            display: flex; flex-direction: column; padding: 25px;
        }
        .sidebar .logo { font-size: 24px; font-weight: 700; color: var(--primary-color); margin-bottom: 40px; }
        .sidebar nav a {
            display: flex; align-items: center; padding: 12px 15px; border-radius: 8px;
            text-decoration: none; color: var(--text-muted); font-weight: 500;
            margin-bottom: 10px; transition: background-color 0.2s, color 0.2s;
        }
        .sidebar nav a.active, .sidebar nav a:hover { background-color: #e9f2fd; color: var(--primary-color); }
        .sidebar nav a svg { margin-right: 15px; }
        .sidebar .user-info { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-color); }
        
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; }
        .btn {
            padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;
            font-size: 14px; transition: background-color 0.2s, box-shadow 0.2s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-success { background-color: var(--success-color); color: white; }
        .btn-danger { background-color: var(--danger-color); color: white; }
        .btn-secondary { background-color: #ccc; color: #333; }

        .breadcrumb {
            display: flex; align-items: center; gap: 8px; margin-bottom: 25px; font-size: 15px;
            color: var(--text-muted); flex-wrap: wrap;
        }
        .breadcrumb a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { font-weight: 500; }

        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        .grid-item {
            background-color: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color);
            text-align: center; text-decoration: none; color: inherit; position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .grid-item:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
        .grid-item-link {
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 20px; height: 100%; text-decoration: none; color: inherit; cursor: pointer;
        }
        .grid-item .icon-lg { margin-bottom: 15px; }
        .grid-item .name { font-weight: 600; word-break: break-all; font-size: 15px; margin: 0; }
        .grid-item .details { font-size: 13px; color: var(--text-muted); margin-top: 5px; }
        
        .folder-icon { color: #5DADE2; }
        
        .actions-menu { position: absolute; top: 10px; right: 10px; }
        .kebab-button { background: transparent; border: none; cursor: pointer; padding: 5px; border-radius: 50%; width: 30px; height: 30px; }
        .kebab-button:hover { background-color: #e9e9e9; }
        .context-menu {
            display: none; position: absolute; z-index: 1000; background-color: #fff;
            border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow);
            list-style: none; padding: 8px 0; margin: 0; min-width: 160px; right: 0; top: 35px;
        }
        .context-menu li { padding: 10px 20px; cursor: pointer; font-size: 14px; }
        .context-menu li:hover { background-color: #f2f2f2; }
        .context-menu li.delete { color: var(--danger-color); }
        
        .modal {
            display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%;
            overflow: auto; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background-color: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            width: 90%; max-width: 450px; position: relative;
        }
        .close-btn {
            position: absolute; top: 15px; right: 15px; font-size: 24px;
            font-weight: bold; cursor: pointer; color: #aaa;
        }
        .close-btn:hover { color: #333; }
        .modal h2 { margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group input[type="file"] {
            width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;
        }
        .modal .btn { width: 100%; padding: 12px; }

        .locked-folder-message { text-align: center; padding: 60px 20px; background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color); margin-top: 30px; }
        .locked-folder-message p { margin: 0 0 20px 0; color: var(--text-muted); font-size: 18px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid transparent; }
        .alert-error { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
        <nav>
            <a href="user_dashboard.php">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard
            </a>
            <a href="folders.php" class="active">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                Folders
            </a>
            <a href="files.php">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                Files
            </a>
        </nav>
        <div class="user-info">
             <div class="name"><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></div>
            <a href="../controllers/auth_controller.php?logout=1" style="font-size: 14px; margin-top: 10px; padding: 5px 0; color: var(--danger-color);">Logout</a>
        </div>
    </aside>

    <div class="main-content">
        <div class="page-header">
            <h1><?= htmlspecialchars($folder['name']) ?></h1>
            <?php if ($has_access): ?>
            <div class="page-actions">
                <button class="btn btn-primary" data-modal-target="upload-file-modal">Upload File</button>
                <button class="btn btn-success" data-modal-target="create-folder-modal">New Subfolder</button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="breadcrumb">
            <a href="user_dashboard.php">Dashboard</a>
            <?php foreach ($folder_path as $part): ?>
                <span>/</span>
                <?php if ($part['id'] != $folder_id): ?>
                    <a href="folder_view.php?folder_id=<?= $part['id'] ?>"><?= htmlspecialchars($part['name']) ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($part['name']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <?php if ($page_error): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if ($has_access): ?>
            <?php if (empty($items)): ?>
                <div class="locked-folder-message"><p>This folder is empty.</p></div>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($items as $item): ?>
                    <div class="grid-item item" data-type="<?= $item['type'] ?>" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>">
                        <a href="<?= $item['type'] === 'folder' ? 'folder_view.php?folder_id='.$item['id'] : '../controllers/file_controller.php?download=1&file_id='.$item['id'] ?>" class="grid-item-link">
                            <div class="icon-lg">
                                <?= $item['type'] === 'folder' ? '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="folder-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>' : getFileIcon($item['name'], 48) ?>
                            </div>
                            <p class="name"><?= htmlspecialchars($item['name']) ?></p>
                            <p class="details"><?= $item['size'] === -1 ? 'Folder' : formatFileSize($item['size']) ?></p>
                        </a>
                        <div class="actions-menu">
                            <button class="kebab-button">&#8942;</button>
                            <ul class="context-menu">
                                <?php if($item['type'] === 'file'): ?><li data-action="download">Download</li><?php endif; ?>
                                <li data-action="rename">Rename</li>
                                <li data-action="delete" class="delete">Delete</li>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif (!$show_password_modal): ?>
            <div class="locked-folder-message">
                <p>This folder is protected. Please enter the password to view its contents.</p>
                <button class="btn btn-primary" onclick="document.getElementById('folder-password-modal').classList.add('active')">Enter Password</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODALS -->
    <?php if ($has_access): ?>
    <div id="create-folder-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2>Create New Subfolder</h2><form action="../controllers/folder_controller.php" method="POST"><input type="hidden" name="parent_folder_id" value="<?= $folder_id ?>"><div class="form-group"><label>Folder Name:</label><input type="text" name="folder_name" required></div><div class="form-group"><label>Password (Optional):</label><input type="password" name="folder_password"></div><button type="submit" name="create_folder" class="btn btn-success">Create Folder</button></form></div></div>
    <div id="upload-file-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2>Upload File</h2><form action="../controllers/file_controller.php" method="POST" enctype="multipart/form-data"><input type="hidden" name="folder_id" value="<?= $folder_id ?>"><div class="form-group"><label>Select file:</label><input type="file" name="file_upload" required></div><button type="submit" name="upload_file" class="btn btn-primary">Upload Now</button></form></div></div>
    <div id="rename-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2 id="rename-title">Rename Item</h2><form id="rename-form" method="POST"><input type="hidden" id="rename-id" name=""><div class="form-group"><label>New Name:</label><input type="text" id="rename-name" name="new_name" required></div><button type="submit" id="rename-submit" class="btn btn-primary">Save Changes</button></form></div></div>
    <div id="delete-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2 id="delete-title">Confirm Deletion</h2><p>Are you sure you want to delete "<strong id="delete-name"></strong>"? This action cannot be undone.</p><form id="delete-form" method="POST" style="display: flex; gap: 10px; margin-top: 20px;"><input type="hidden" id="delete-id" name=""><button type="button" class="btn close-btn-action" style="background-color: #ccc;">Cancel</button><button type="submit" id="delete-submit" class="btn btn-danger">Delete</button></form></div></div>
    <?php endif; ?>

    <div id="folder-password-modal" class="modal <?= $show_password_modal ? 'active' : '' ?>">
        <div class="modal-content">
            <h2>Password Required</h2>
            <p>This folder is protected. Please enter the password to continue.</p>
            <form action="../controllers/folder_controller.php" method="POST">
                <input type="hidden" name="folder_id" value="<?= htmlspecialchars($folder_id) ?>">
                <div class="form-group"><label>Password:</label><input type="password" name="folder_password" required autofocus></div>
                <div style="display: flex; gap: 10px;">
                    <a href="user_dashboard.php" class="btn btn-secondary" style="flex:1;">Go Back</a>
                    <button type="submit" name="verify_password" class="btn btn-primary" style="flex:1;">Unlock</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            createFolder: document.getElementById('create-folder-modal'),
            uploadFile: document.getElementById('upload-file-modal'),
            rename: document.getElementById('rename-modal'),
            delete: document.getElementById('delete-modal'),
        };

        document.querySelectorAll('[data-modal-target]').forEach(button => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-modal-target');
                document.getElementById(modalId)?.classList.add('active');
            });
        });

        document.querySelectorAll('.modal .close-btn, .modal .close-btn-action').forEach(btn => {
            btn.addEventListener('click', () => btn.closest('.modal').classList.remove('active'));
        });
        window.addEventListener('click', e => {
            if (e.target.classList.contains('modal')) e.target.classList.remove('active');
        });

        document.querySelectorAll('.kebab-button').forEach(button => {
            button.addEventListener('click', e => {
                e.stopPropagation();
                let menu = button.nextElementSibling;
                document.querySelectorAll('.context-menu').forEach(m => {
                    if (m !== menu) m.style.display = 'none';
                });
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            });
        });
        window.addEventListener('click', () => {
            document.querySelectorAll('.context-menu').forEach(m => m.style.display = 'none');
        });

        document.querySelectorAll('.context-menu li').forEach(item => {
            item.addEventListener('click', e => {
                const action = e.target.dataset.action;
                const row = e.target.closest('.item');
                const type = row.dataset.type;
                const id = row.dataset.id;
                const name = row.dataset.name;

                if (action === 'download') {
                    window.location.href = `../controllers/file_controller.php?download=1&file_id=${id}`;
                    return;
                }
                
                if (action === 'rename') {
                    modals.rename.querySelector('#rename-title').textContent = `Rename ${type}`;
                    const form = modals.rename.querySelector('#rename-form');
                    form.action = `../controllers/${type}_controller.php`;
                    modals.rename.querySelector('#rename-id').name = `${type}_id`;
                    modals.rename.querySelector('#rename-id').value = id;
                    modals.rename.querySelector('#rename-name').value = name;
                    modals.rename.querySelector('#rename-submit').name = `rename_${type}`;
                    modals.rename.classList.add('active');
                }

                if (action === 'delete') {
                    modals.delete.querySelector('#delete-title').textContent = `Delete ${type}`;
                    const form = modals.delete.querySelector('#delete-form');
                    form.action = `../controllers/${type}_controller.php`;
                    modals.delete.querySelector('#delete-id').name = `${type}_id`;
                    modals.delete.querySelector('#delete-id').value = id;
                    modals.delete.querySelector('#delete-name').textContent = name;
                    modals.delete.querySelector('#delete-submit').name = `delete_${type}`;
                    modals.delete.classList.add('active');
                }
            });
        });
    });
    </script>
</body>
</html>

