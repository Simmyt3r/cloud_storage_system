<?php
/**
 * File Management Page
 *
 * Displays a list of all files within a user's organization and provides
 * tools to upload, download, rename, and delete them with a modern UI.
 * This version filters out files from password-protected folders.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../models/File.php';
require_once '../models/Folder.php';
require_once '../models/Organization.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    redirect('login.php');
}

// --- 2. HELPER FUNCTIONS ---
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

// --- 3. DATA FETCHING & FILTERING ---
$files = $folder_map = [];
$page_error = null;

try {
    $org_id = get_user_organization_id();

    $file_model = new File($pdo);
    $folder_model = new Folder($pdo);
    
    // Fetch all files for the organization initially.
    $all_files = $file_model->getFilesByOrganization($org_id);
    
    // Fetch all folders to check for passwords and to create a name map.
    $all_org_folders = $folder_model->getAllFoldersByOrganization($org_id);
    $folder_map = array_column($all_org_folders, 'name', 'id');
    
    // Create a map of folder IDs to their password status for efficient lookup.
    $folder_password_map = [];
    foreach ($all_org_folders as $folder) {
        // A folder is considered password-protected if the password field is not empty.
        $folder_password_map[$folder['id']] = !empty($folder['password']);
    }

    // Filter the files list to exclude any files that reside in a password-protected folder.
    $files = array_filter($all_files, function($file) use ($folder_password_map) {
        // Condition 1: Keep the file if it's in the root directory (folder_id is null).
        if (is_null($file['folder_id'])) {
            return true;
        }
        // Condition 2: Keep the file if its parent folder exists and is NOT password protected.
        return isset($folder_password_map[$file['folder_id']]) && $folder_password_map[$file['folder_id']] === false;
    });

} catch (PDOException $e) {
    error_log('Files Page Error: ' . $e->getMessage());
    $page_error = 'Could not load file data. Please try again later.';
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
        
        .table-container { background-color: var(--card-bg); border-radius: 12px; padding: 20px; box-shadow: var(--shadow); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; }
        th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 12px; border-bottom: 2px solid var(--border-color); }
        td { border-bottom: 1px solid var(--border-color); }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #f9f9f9; }
        
        .actions-cell { text-align: right; }
        .kebab-button { background: transparent; border: none; cursor: pointer; padding: 5px; border-radius: 50%; width: 30px; height: 30px; }
        .kebab-button:hover { background-color: #e9e9e9; }
        .context-menu {
            display: none; position: absolute; z-index: 1000; background-color: #fff;
            border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow);
            list-style: none; padding: 8px 0; margin: 0; min-width: 160px; right: 0;
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
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; }
        .modal .btn { width: 100%; padding: 12px; }

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
            <a href="folders.php">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                Folders
            </a>
            <a href="files.php" class="active">
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
            <h1>Manage Files</h1>
            <button class="btn btn-success" data-modal-target="upload-modal">Upload New File</button>
        </div>
        
        <?php if ($page_error): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Folder</th>
                        <th>Size</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th class="actions-cell"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($files)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px;">No public files found in your organization.</td></tr>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                        <tr class="item" data-id="<?= $file['id'] ?>" data-name="<?= htmlspecialchars($file['name']) ?>">
                            <td><?= getFileIcon($file['name']) ?></td>
                            <td><?= htmlspecialchars($file['name']) ?></td>
                            <td><?= htmlspecialchars($folder_map[$file['folder_id']] ?? 'Root') ?></td>
                            <td><?= formatFileSize($file['file_size']) ?></td>
                            <td><?= htmlspecialchars($file['uploaded_by_username']) ?></td>
                            <td><?= date('M j, Y H:i', strtotime($file['uploaded_at'])) ?></td>
                            <td class="actions-cell">
                                <div style="position: relative; display: inline-block;">
                                    <button class="kebab-button">&#8942;</button>
                                    <ul class="context-menu">
                                        <li data-action="view">View</li>
                                        <li data-action="download">Download</li>
                                        <li data-action="rename">Rename</li>
                                        <li data-action="delete" class="delete">Delete</li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODALS -->
    <div id="upload-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2>Upload New File</h2><form action="../controllers/file_controller.php" method="POST" enctype="multipart/form-data"><div class="form-group"><label>Select file:</label><input type="file" name="file_upload" required></div><div class="form-group"><label>Upload to Folder:</label><select name="folder_id"><option value="">Root Directory</option><?php foreach ($all_org_folders as $folder): ?><?php if(empty($folder['password'])): ?><option value="<?= $folder['id'] ?>"><?= htmlspecialchars($folder['name']) ?></option><?php endif; ?><?php endforeach; ?></select></div><button type="submit" name="upload_file" class="btn btn-success">Upload Now</button></form></div></div>
    <div id="rename-file-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2>Rename File</h2><form id="rename-form" action="../controllers/file_controller.php" method="POST"><input type="hidden" id="rename-id" name="file_id"><div class="form-group"><label>New Name:</label><input type="text" id="rename-name" name="new_name" required></div><button type="submit" name="rename_file" class="btn btn-primary">Save Changes</button></form></div></div>
    <div id="delete-file-modal" class="modal"><div class="modal-content"><span class="close-btn">&times;</span><h2>Confirm Deletion</h2><p>Are you sure you want to delete "<strong id="delete-name"></strong>"?</p><form id="delete-form" action="../controllers/file_controller.php" method="POST" style="display: flex; gap: 10px; margin-top: 20px;"><input type="hidden" id="delete-id" name="file_id"><button type="button" class="btn close-btn-action" style="background-color: #ccc;">Cancel</button><button type="submit" name="delete_file" class="btn btn-danger">Delete</button></form></div></div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            upload: document.getElementById('upload-modal'),
            rename: document.getElementById('rename-file-modal'),
            delete: document.getElementById('delete-file-modal')
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

                if (action === 'view') {
                    window.open(`../views/file_viewer.php?file_id=${id}`, '_blank');
                } else if (action === 'download') {
                    window.location.href = `../controllers/file_controller.php?download=1&file_id=${id}`;
                } else if (action === 'rename') {
                    modals.rename.querySelector('#rename-id').value = id;
                    modals.rename.querySelector('#rename-name').value = name;
                    modals.rename.classList.add('active');
                } else if (action === 'delete') {
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

