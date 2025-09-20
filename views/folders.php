<?php
/**
 * Folders Page
 *
 * Displays a list of all folders within a user's organization and provides
 * tools for creating, renaming, and deleting them with a modern UI.
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
$folders = [];
$organization = ['name' => 'N/A'];
$page_error = null;

try {
    $org_id = get_user_organization_id();
    $folder_model = new Folder($pdo);
    $org_model = new Organization($pdo);

    $folders = $folder_model->getAllFoldersByOrganization($org_id);
    $organization = $org_model->findById($org_id);

} catch (PDOException $e) {
    error_log('Folders Page Error: ' . $e->getMessage());
    $page_error = 'Could not load folder data. Please try again later.';
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
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
        .modal-content { background-color: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); width: 90%; max-width: 450px; position: relative; }
        .close-btn { position: absolute; top: 15px; right: 15px; font-size: 24px; font-weight: bold; cursor: pointer; color: #aaa; }
        .close-btn:hover { color: #333; }
        .modal h2 { margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input[type="text"], .form-group input[type="password"] { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; }
        .modal .btn { width: 100%; padding: 12px; }

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid transparent; }
        .alert-error { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
        .empty-state { text-align: center; padding: 60px 20px; background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color); margin-top: 30px; }
        .empty-state p { margin: 0; color: var(--text-muted); font-size: 18px; }
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
            <h1>All Folders <span style="color: var(--text-muted); font-weight: 500; font-size: 20px;">(<?= htmlspecialchars($organization['name'] ?? 'Unknown') ?>)</span></h1>
            <button class="btn btn-success" data-modal-target="create-folder-modal">Create New Folder</button>
        </div>
        
        <?php if ($page_error): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if (empty($folders)): ?>
            <div class="empty-state"><p>No folders found. Get started by creating one!</p></div>
        <?php else: ?>
            <div class="grid-container">
                <?php foreach ($folders as $folder): ?>
                <div class="grid-item item" data-id="<?= $folder['id'] ?>" data-name="<?= htmlspecialchars($folder['name']) ?>">
                    <a href="folder_view.php?folder_id=<?= $folder['id'] ?>" class="grid-item-link">
                        <div class="icon-lg">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="folder-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        </div>
                        <p class="name"><?= htmlspecialchars($folder['name']) ?></p>
                    </a>
                    <div class="actions-menu">
                        <button class="kebab-button">&#8942;</button>
                        <ul class="context-menu">
                            <li data-action="rename">Rename</li>
                            <li data-action="delete" class="delete">Delete</li>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODALS -->
    <div id="create-folder-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2>Create New Folder</h2><form action="../controllers/folder_controller.php" method="POST"><input type="hidden" name="parent_folder_id" value=""><div class="form-group"><label>Folder Name:</label><input type="text" name="folder_name" required></div><div class="form-group"><label>Password (Optional):</label><input type="password" name="folder_password"></div><button type="submit" name="create_folder" class="btn btn-success">Create Folder</button></form></div></div>
    <div id="rename-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2>Rename Folder</h2><form id="rename-form" action="../controllers/folder_controller.php" method="POST"><input type="hidden" id="rename-id" name="folder_id"><div class="form-group"><label>New Name:</label><input type="text" id="rename-name" name="new_name" required></div><button type="submit" name="rename_folder" class="btn btn-primary">Save Changes</button></form></div></div>
    <div id="delete-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2>Confirm Deletion</h2><p>Are you sure you want to delete "<strong id="delete-name"></strong>"? This action cannot be undone.</p><form id="delete-form" action="../controllers/folder_controller.php" method="POST" style="display: flex; gap: 10px; margin-top: 20px;"><input type="hidden" id="delete-id" name="folder_id"><button type="button" class="btn close-btn-action" style="background-color: #ccc;">Cancel</button><button type="submit" name="delete_folder" class="btn btn-danger">Delete</button></form></div></div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            createFolder: document.getElementById('create-folder-modal'),
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
                const id = row.dataset.id;
                const name = row.dataset.name;
                
                if (action === 'rename') {
                    modals.rename.querySelector('#rename-id').value = id;
                    modals.rename.querySelector('#rename-name').value = name;
                    modals.rename.classList.add('active');
                }

                if (action === 'delete') {
                    modals.delete.querySelector('#delete-id').value = id;
                    modals.delete.querySelector('#delete-name').textContent = name;
                    modals.delete.classList.add('active');
                }
            });
        });
    });
    </script>
</body>
</html>
