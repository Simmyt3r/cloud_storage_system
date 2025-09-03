<?php
/**
 * User Dashboard Page
 *
 * Displays a user's primary dashboard, showing root-level folders and files
 * they have access to within their organization, with added management features,
 * view switching, and sorting.
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

// --- 2. VIEW & SORTING LOGIC ---
// View preference (list or grid)
$default_view = 'list';
if (isset($_GET['view']) && in_array($_GET['view'], ['list', 'grid'])) {
    $_SESSION['dashboard_view'] = $_GET['view'];
}
$view = $_SESSION['dashboard_view'] ?? $default_view;

// Sorting preference
$sort_options = ['name', 'date', 'size'];
$sort_by = (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $sort_options)) ? $_GET['sort_by'] : 'name';
$sort_dir = (isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'desc') ? 'desc' : 'asc';


// --- 3. HELPER FUNCTIONS ---
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
        default:
            return "<svg {$common_attrs}><path d='M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z'></path><polyline points='13 2 13 9 20 9'></polyline></svg>";
    }
}

// --- 4. DATA FETCHING & PROCESSING ---
try {
    $user_id = get_user_id();
    $org_id = get_user_organization_id();

    $folder_model = new Folder($pdo);
    $file_model = new File($pdo);
    $org_model = new Organization($pdo);
    
    $organization = $org_model->findById($org_id);
    $folders = $folder_model->getFoldersByOrganization($org_id, null); 
    $files = $file_model->getFilesByFolder(null);

    // Combine folders and files for unified sorting
    $items = [];
    foreach ($folders as $folder) {
        $items[] = [
            'type' => 'folder',
            'id' => $folder['id'],
            'name' => $folder['name'],
            'date' => strtotime($folder['updated_at']),
            'size' => -1 // Folders have no size, -1 to sort them first
        ];
    }
    foreach ($files as $file) {
        $items[] = [
            'type' => 'file',
            'id' => $file['id'],
            'name' => $file['name'],
            'date' => strtotime($file['uploaded_at']),
            'size' => (int)$file['file_size']
        ];
    }
    
    // Perform sorting
    usort($items, function($a, $b) use ($sort_by, $sort_dir) {
        $direction = ($sort_dir === 'asc') ? 1 : -1;
        if ($sort_by === 'name') {
            return strnatcasecmp($a['name'], $b['name']) * $direction;
        }
        if ($sort_by === 'date') {
            return ($a['date'] <=> $b['date']) * $direction;
        }
        if ($sort_by === 'size') {
            return ($a['size'] <=> $b['size']) * $direction;
        }
        return 0;
    });


} catch (PDOException $e) {
    error_log('User Dashboard Error: ' . $e->getMessage());
    $page_error = 'Could not load dashboard data. Please try again later.';
    $organization = ['name' => 'Error'];
    $items = [];
}

// --- 5. FLASH MESSAGES ---
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
        body { background-color: #f4f7f9; }
        .main-content { padding: 25px; }
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding-bottom: 15px; 
            margin-bottom: 15px;
        }
        .page-header h1 { margin: 0; font-size: 28px; color: #333; }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
        }
        .view-controls button, .sort-controls select {
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 5px;
        }
        .view-controls button.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .file-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .file-table th { text-align: left; padding: 10px 15px; color: #666; font-weight: 600; text-transform: uppercase; font-size: 12px; border-bottom: 2px solid #e0e0e0; }
        .file-table td { padding: 15px; background: #fff; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
        .file-table tr:hover td { background-color: #f9f9f9; }
        .file-table tr td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .file-table tr td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
        
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        .grid-item { background-color: #fff; border-radius: 8px; padding: 20px; text-align: center; border: 1px solid #e0e0e0; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; color: inherit; position: relative; }
        .grid-item:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .grid-item .icon-lg { margin-bottom: 15px; }
        .grid-item .name { font-weight: 600; margin: 0; word-break: break-all; font-size: 15px; }
        .grid-item .details { font-size: 0.8rem; color: #777; margin-top: 5px; }

        .item-link { display: flex; align-items: center; text-decoration: none; color: #333; font-weight: 600; cursor: pointer; }
        .item-link:hover { color: #3498db; }
        .item-link svg { margin-right: 15px; }
        
        .folder-icon { color: #5DADE2; }
        .text-muted { color: #888; font-size: 14px; }

        .actions-cell { text-align: right; }
        .actions-menu { position: relative; display: inline-block; }
        .kebab-button { background: transparent; border: none; cursor: pointer; padding: 5px; border-radius: 50%; }
        .kebab-button:hover { background-color: #e0e0e0; }
        
        .context-menu { display: none; position: absolute; z-index: 1000; background-color: #fff; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); list-style: none; padding: 8px 0; margin: 0; min-width: 160px; right: 0; top: 30px; }
        .context-menu li { padding: 10px 20px; cursor: pointer; font-size: 14px; }
        .context-menu li:hover { background-color: #f2f2f2; }
        .context-menu li.delete { color: #e74c3c; }

        .empty-state { text-align: center; padding: 80px 20px; background: #fff; border-radius: 8px; margin-top: 20px; }
        .empty-state p { margin: 0; color: #777; font-size: 18px; }
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
                <div class="page-actions">
                    <button class="btn btn-primary" id="upload-file-btn">Upload File</button>
                    <button class="btn btn-success" id="create-folder-btn">Create Folder</button>
                </div>
            </div>
            
            <div class="toolbar">
                <div class="sort-controls">
                    <label for="sort-by">Sort by:</label>
                    <select id="sort-by" onchange="applySort()">
                        <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Name</option>
                        <option value="date" <?= $sort_by === 'date' ? 'selected' : '' ?>>Date</option>
                        <option value="size" <?= $sort_by === 'size' ? 'selected' : '' ?>>Size</option>
                    </select>
                    <select id="sort-dir" onchange="applySort()">
                        <option value="asc" <?= $sort_dir === 'asc' ? 'selected' : '' ?>>Asc</option>
                        <option value="desc" <?= $sort_dir === 'desc' ? 'selected' : '' ?>>Desc</option>
                    </select>
                </div>
                <div class="view-controls">
                    <button id="list-view-btn" class="<?= $view === 'list' ? 'active' : '' ?>" onclick="switchView('list')">List</button>
                    <button id="grid-view-btn" class="<?= $view === 'grid' ? 'active' : '' ?>" onclick="switchView('grid')">Grid</button>
                </div>
            </div>
            
            <p>Welcome, <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>! (Department: <?= htmlspecialchars($organization['name'] ?? 'Unknown') ?>)</p>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <?php if (empty($items)): ?>
                <div class="empty-state"><p>This directory is empty. Start by uploading a file or creating a folder.</p></div>
            <?php else: ?>
                <?php if ($view === 'list'): ?>
                    <table class="file-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Last Modified</th>
                                <th>File Size</th>
                                <th class="actions-cell">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr class="item" data-type="<?= $item['type'] ?>" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>">
                                    <td>
                                        <a href="<?= $item['type'] === 'folder' ? 'folder_view.php?folder_id='.$item['id'] : '../controllers/file_controller.php?download=1&file_id='.$item['id'] ?>" class="item-link">
                                            <?= $item['type'] === 'folder' ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="folder-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>' : getFileIcon($item['name']) ?>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                    </td>
                                    <td class="text-muted"><?= date('M j, Y', $item['date']) ?></td>
                                    <td class="text-muted"><?= $item['size'] === -1 ? '--' : formatFileSize($item['size']) ?></td>
                                    <td class="actions-cell">
                                        <div class="actions-menu">
                                            <button class="kebab-button">&#8942;</button>
                                            <ul class="context-menu">
                                                <?php if($item['type'] === 'file'): ?><li data-action="download">Download</li><?php endif; ?>
                                                <li data-action="rename">Rename</li>
                                                <li data-action="delete" class="delete">Delete</li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: // Grid View ?>
                    <div class="grid-container">
                         <?php foreach ($items as $item): ?>
                            <div class="grid-item item" data-type="<?= $item['type'] ?>" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>">
                                <a href="<?= $item['type'] === 'folder' ? 'folder_view.php?folder_id='.$item['id'] : '../controllers/file_controller.php?download=1&file_id='.$item['id'] ?>" class="item-link" style="flex-direction: column; justify-content: center; height: 100%;">
                                    <div class="icon-lg">
                                         <?= $item['type'] === 'folder' ? '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="folder-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>' : getFileIcon($item['name'], 48) ?>
                                    </div>
                                    <p class="name"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="details"><?= $item['size'] === -1 ? 'Folder' : formatFileSize($item['size']) ?></p>
                                </a>
                                 <div class="actions-menu" style="position: absolute; top: 10px; right: 10px;">
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
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MODALS -->
    <div id="create-folder-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Create New Folder</h2>
            <form action="../controllers/folder_controller.php" method="POST">
                <input type="hidden" name="parent_folder_id" value=""> <!-- Root folder -->
                <div class="form-group"><label>Folder Name:</label><input type="text" name="folder_name" required></div>
                <div class="form-group"><label>Password (Optional):</label><input type="password" name="folder_password"></div>
                <button type="submit" name="create_folder" class="btn btn-success">Create</button>
            </form>
        </div>
    </div>
    <div id="upload-file-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Upload File</h2>
            <form action="../controllers/file_controller.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="folder_id" value=""> <!-- Root folder -->
                <div class="form-group"><label>Select file:</label><input type="file" name="file_upload" required></div>
                <button type="submit" name="upload_file" class="btn btn-success">Upload</button>
            </form>
        </div>
    </div>
    <div id="rename-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="rename-title">Rename Item</h2>
            <form id="rename-form" method="POST">
                <input type="hidden" id="rename-id" name="">
                <div class="form-group"><label>New Name:</label><input type="text" id="rename-name" name="new_name" required></div>
                <button type="submit" id="rename-submit" class="btn btn-primary">Rename</button>
            </form>
        </div>
    </div>
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="delete-title">Delete Item</h2>
            <p>Are you sure you want to delete "<strong id="delete-name"></strong>"? This action cannot be undone.</p>
            <form id="delete-form" method="POST">
                <input type="hidden" id="delete-id" name="">
                <button type="submit" id="delete-submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
    
    <script>
    function switchView(view) {
        const url = new URL(window.location);
        url.searchParams.set('view', view);
        window.location.href = url.toString();
    }
    function applySort() {
        const sortBy = document.getElementById('sort-by').value;
        const sortDir = document.getElementById('sort-dir').value;
        const url = new URL(window.location);
        url.searchParams.set('sort_by', sortBy);
        url.searchParams.set('sort_dir', sortDir);
        window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            createFolder: document.getElementById('create-folder-modal'),
            uploadFile: document.getElementById('upload-file-modal'),
            rename: document.getElementById('rename-modal'),
            delete: document.getElementById('delete-modal'),
        };

        document.getElementById('create-folder-btn').addEventListener('click', () => modals.createFolder.style.display = 'block');
        document.getElementById('upload-file-btn').addEventListener('click', () => modals.uploadFile.style.display = 'block');
        
        document.querySelectorAll('.modal .close-btn').forEach(btn => {
            btn.addEventListener('click', () => btn.closest('.modal').style.display = 'none');
        });
        window.addEventListener('click', e => {
            if (e.target.classList.contains('modal')) e.target.style.display = 'none';
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
                    modals.rename.style.display = 'block';
                }

                if (action === 'delete') {
                    modals.delete.querySelector('#delete-title').textContent = `Delete ${type}`;
                    const form = modals.delete.querySelector('#delete-form');
                    form.action = `../controllers/${type}_controller.php`;
                    modals.delete.querySelector('#delete-id').name = `${type}_id`;
                    modals.delete.querySelector('#delete-id').value = id;
                    modals.delete.querySelector('#delete-name').textContent = name;
                    modals.delete.querySelector('#delete-submit').name = `delete_${type}`;
                    modals.delete.style.display = 'block';
                }
            });
        });
    });
    </script>
</body>
</html>

