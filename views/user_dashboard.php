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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    redirect('login.php');
}

// --- 2. VIEW & SORTING LOGIC ---
$view = $_SESSION['dashboard_view'] ?? 'grid'; // Default to grid view
if (isset($_GET['view']) && in_array($_GET['view'], ['list', 'grid'])) {
    $_SESSION['dashboard_view'] = $_GET['view'];
    $view = $_GET['view'];
}

$sort_options = ['name', 'date', 'size'];
$sort_by = $_SESSION['dashboard_sort_by'] ?? 'name';
$sort_dir = $_SESSION['dashboard_sort_dir'] ?? 'asc';

if (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $sort_options)) {
    $_SESSION['dashboard_sort_by'] = $_GET['sort_by'];
    $sort_by = $_GET['sort_by'];
}
if (isset($_GET['sort_dir']) && in_array($_GET['sort_dir'], ['asc', 'desc'])) {
    $_SESSION['dashboard_sort_dir'] = $_GET['sort_dir'];
    $sort_dir = $_GET['sort_dir'];
}


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
            return "<svg {$common_attrs} class='icon-pdf'><path d='M14 2v4a2 2 0 0 0 2 2h4'/><path d='M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z'/><path d='M15 12H9'/><path d='M15 16H9'/></svg>";
        case 'jpg': case 'jpeg': case 'png': case 'gif':
            return "<svg {$common_attrs} class='icon-image'><rect x='3' y='3' width='18' height='18' rx='2' ry='2'></rect><circle cx='8.5' cy='8.5' r='1.5'></circle><polyline points='21 15 16 10 5 21'></polyline></svg>";
        case 'doc': case 'docx':
             return "<svg {$common_attrs} class='icon-doc'><path d='M14 2v4a2 2 0 0 0 2 2h4'/><path d='M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4.5l6.5 6.5v8a2 2 0 0 1-2 2Z'/><path d='M12 18v-6'/><path d='M12 12H9'/><path d='M15 12h-3'/></svg>";
        case 'zip': case 'rar': case '7z':
            return "<svg {$common_attrs} class='icon-zip'><path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'></path><polyline points='3.27 6.96 12 12.01 20.73 6.96'></polyline><line x1='12' y1='22.08' x2='12' y2='12'></line></svg>";
        default:
            return "<svg {$common_attrs} class='icon-file'><path d='M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z'></path><polyline points='13 2 13 9 20 9'></polyline></svg>";
    }
}

// --- 4. DATA FETCHING & PROCESSING ---
$items = [];
$organization = ['name' => 'N/A'];
$page_error = null;

try {
    $user_id = get_user_id();
    $org_id = get_user_organization_id();

    $folder_model = new Folder($pdo);
    $file_model = new File($pdo);
    $org_model = new Organization($pdo);
    
    $organization = $org_model->findById($org_id) ?: $organization;
    $folders = $folder_model->getFoldersByOrganization($org_id, null); 
    $files = $file_model->getFilesByFolder(null);

    // Combine folders and files
    foreach ($folders as $folder) {
        $items[] = [
            'type' => 'folder', 'id' => $folder['id'], 'name' => $folder['name'],
            'date' => strtotime($folder['updated_at']), 'size' => -1
        ];
    }
    foreach ($files as $file) {
        $items[] = [
            'type' => 'file', 'id' => $file['id'], 'name' => $file['name'],
            'date' => strtotime($file['uploaded_at']), 'size' => (int)$file['file_size']
        ];
    }
    
    // Perform sorting
    usort($items, function($a, $b) use ($sort_by, $sort_dir) {
        $direction = ($sort_dir === 'asc') ? 1 : -1;
        if ($sort_by === 'name') {
            return strnatcasecmp($a['name'], $b['name']) * $direction;
        }
        return ($a[$sort_by] <=> $b[$sort_by]) * $direction;
    });

} catch (PDOException $e) {
    error_log('User Dashboard Error: ' . $e->getMessage());
    $page_error = 'Could not load dashboard data. Please try again later.';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --success-color: #50e3c2;
            --danger-color: #e24a4a;
            --bg-color: #f4f7f9;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --text-color: #333;
            --text-muted: #888;
            --border-color: #e0e0e0;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        /* --- Sidebar --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 25px;
            transition: margin-left 0.3s ease;
        }
        .sidebar .logo { font-size: 24px; font-weight: 700; color: var(--primary-color); margin-bottom: 40px; }
        .sidebar nav a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 10px;
            transition: background-color 0.2s, color 0.2s;
        }
        .sidebar nav a.active, .sidebar nav a:hover {
            background-color: #e9f2fd;
            color: var(--primary-color);
        }
        .sidebar nav a svg { margin-right: 15px; }
        .sidebar .user-info {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .sidebar .user-info .name { font-weight: 600; }
        .sidebar .user-info .org { font-size: 14px; color: var(--text-muted); }

        /* --- Main Content --- */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; }
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-success { background-color: var(--success-color); color: white; }
        .btn-danger { background-color: var(--danger-color); color: white; }

        /* --- Toolbar --- */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background-color: var(--card-bg);
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        .toolbar-group { display: flex; align-items: center; gap: 10px; }
        .toolbar-group label { font-size: 14px; font-weight: 500; color: var(--text-muted); }
        .toolbar-group select, .view-controls button {
            background-color: #fff;
            border: 1px solid var(--border-color);
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-color);
        }
        .view-controls button { background: none; }
        .view-controls button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* --- List View --- */
        .file-list { width: 100%; border-collapse: collapse; }
        .file-list th, .file-list td { text-align: left; padding: 15px; }
        .file-list thead { border-bottom: 2px solid var(--border-color); }
        .file-list th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 12px; }
        .file-list tbody tr { border-bottom: 1px solid var(--border-color); transition: background-color 0.2s; }
        .file-list tbody tr:hover { background-color: #fafafa; }
        .item-link { display: flex; align-items: center; text-decoration: none; color: inherit; font-weight: 600; }
        .item-link:hover { color: var(--primary-color); }
        .item-link svg { margin-right: 15px; }

        /* --- Grid View --- */
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        .grid-item {
            background-color: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            text-align: center;
            text-decoration: none;
            color: inherit;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .grid-item:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
        .grid-item-link {
            display: flex; flex-direction: column; justify-content: center;
            align-items: center; padding: 20px; height: 100%; text-decoration: none; color: inherit;
        }
        .grid-item .icon-lg { margin-bottom: 15px; }
        .grid-item .name { font-weight: 600; word-break: break-all; font-size: 15px; margin: 0; }
        .grid-item .details { font-size: 13px; color: var(--text-muted); margin-top: 5px; }
        
        /* Item Icons Colors */
        .folder-icon { color: #5DADE2; }
        .icon-pdf { color: #E74C3C; }
        .icon-image { color: #2ECC71; }
        .icon-doc { color: #3498DB; }
        .icon-zip { color: #F1C40F; }
        .icon-file { color: #95A5A6; }

        /* --- Actions Menu --- */
        .actions-cell { text-align: right; }
        .actions-menu { position: relative; display: inline-block; }
        .kebab-button { background: transparent; border: none; cursor: pointer; padding: 5px; border-radius: 50%; width: 30px; height: 30px; }
        .kebab-button:hover { background-color: #e9e9e9; }
        .context-menu {
            display: none; position: absolute; z-index: 1000;
            background-color: #fff; border: 1px solid var(--border-color);
            border-radius: 8px; box-shadow: var(--shadow);
            list-style: none; padding: 8px 0; margin: 0;
            min-width: 160px; right: 0; top: 35px;
        }
        .context-menu li {
            padding: 10px 20px; cursor: pointer; font-size: 14px;
            display: flex; align-items: center; gap: 10px;
        }
        .context-menu li:hover { background-color: #f2f2f2; }
        .context-menu li.delete { color: var(--danger-color); }

        /* --- Modals --- */
        .modal {
            display: none; position: fixed; z-index: 1001; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);
            align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background-color: #fff; padding: 30px; border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2); width: 90%; max-width: 450px;
            position: relative;
        }
        .close-btn {
            position: absolute; top: 15px; right: 15px;
            font-size: 24px; font-weight: bold; cursor: pointer; color: #aaa;
        }
        .close-btn:hover { color: #333; }
        .modal h2 { margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group input[type="file"] {
            width: 100%; padding: 12px; border: 1px solid var(--border-color);
            border-radius: 8px; font-size: 14px;
        }
        .modal .btn { width: 100%; padding: 12px; }

        /* --- Empty State & Alerts --- */
        .empty-state { text-align: center; padding: 80px 20px; background: var(--card-bg); border-radius: 8px; margin-top: 20px; }
        .empty-state p { margin: 0; color: #777; font-size: 18px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid transparent; }
        .alert-error { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }

    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
        <nav>
            <a href="user_dashboard.php" class="active">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard
            </a>
            <a href="folders.php">
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
            <div class="org"><?= htmlspecialchars($organization['name']) ?></div>
            <a href="../controllers/auth_controller.php?logout=1" style="font-size: 14px; margin-top: 10px; padding: 5px 0; color: var(--danger-color);">Logout</a>
        </div>
    </aside>

    <div class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <div class="page-actions">
                <button class="btn btn-primary" data-modal-target="upload-file-modal">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Upload File
                </button>
                <button class="btn btn-success" data-modal-target="create-folder-modal">
                     <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    New Folder
                </button>
            </div>
        </div>
        
        <div class="toolbar">
            <div class="toolbar-group">
                <label for="sort-by">Sort by:</label>
                <select id="sort-by" onchange="applySort()">
                    <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Name</option>
                    <option value="date" <?= $sort_by === 'date' ? 'selected' : '' ?>>Date Modified</option>
                    <option value="size" <?= $sort_by === 'size' ? 'selected' : '' ?>>Size</option>
                </select>
                <select id="sort-dir" onchange="applySort()">
                    <option value="asc" <?= $sort_dir === 'asc' ? 'selected' : '' ?>>Asc</option>
                    <option value="desc" <?= $sort_dir === 'desc' ? 'selected' : '' ?>>Desc</option>
                </select>
            </div>
            <div class="view-controls">
                <button id="list-view-btn" class="<?= $view === 'list' ? 'active' : '' ?>" onclick="switchView('list')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg></button>
                <button id="grid-view-btn" class="<?= $view === 'grid' ? 'active' : '' ?>" onclick="switchView('grid')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></button>
            </div>
        </div>
        
        <?php if ($page_error): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        
        <?php if (empty($items)): ?>
            <div class="empty-state"><p>This directory is empty. Start by uploading a file or creating a folder.</p></div>
        <?php else: ?>
            <?php if ($view === 'list'): ?>
                <table class="file-list">
                    <thead>
                        <tr>
                            <th>Name</th><th>Last Modified</th><th>File Size</th><th class="actions-cell"></th>
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
                        <a href="<?= $item['type'] === 'folder' ? 'folder_view.php?folder_id='.$item['id'] : '../controllers/file_controller.php?download=1&file_id='.$item['id'] ?>" class="grid-item-link">
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
    
    <!-- MODALS -->
    <div id="create-folder-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Create New Folder</h2>
            <form action="../controllers/folder_controller.php" method="POST">
                <input type="hidden" name="parent_folder_id" value="">
                <div class="form-group"><label>Folder Name:</label><input type="text" name="folder_name" required></div>
                <div class="form-group"><label>Password (Optional):</label><input type="password" name="folder_password"></div>
                <button type="submit" name="create_folder" class="btn btn-success">Create Folder</button>
            </form>
        </div>
    </div>
    <div id="upload-file-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Upload File</h2>
            <form action="../controllers/file_controller.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="folder_id" value="">
                <div class="form-group"><label>Select file:</label><input type="file" name="file_upload" required></div>
                <button type="submit" name="upload_file" class="btn btn-primary">Upload Now</button>
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
                <button type="submit" id="rename-submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="delete-title">Confirm Deletion</h2>
            <p>Are you sure you want to delete "<strong id="delete-name"></strong>"? This action cannot be undone.</p>
            <form id="delete-form" method="POST" style="display: flex; gap: 10px; margin-top: 20px;">
                <input type="hidden" id="delete-id" name="">
                 <button type="button" class="btn close-btn-action" style="background-color: #ccc;">Cancel</button>
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

        // Open modals
        document.querySelectorAll('[data-modal-target]').forEach(button => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-modal-target');
                document.getElementById(modalId)?.classList.add('active');
            });
        });

        // Close modals
        document.querySelectorAll('.modal .close-btn, .modal .close-btn-action').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal').classList.remove('active');
            });
        });
        window.addEventListener('click', e => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });

        // Context menu logic
        document.querySelectorAll('.kebab-button').forEach(button => {
            button.addEventListener('click', e => {
                e.stopPropagation();
                let menu = button.nextElementSibling;
                // Close all other menus
                document.querySelectorAll('.context-menu').forEach(m => {
                    if (m !== menu) m.style.display = 'none';
                });
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            });
        });

        // Close context menu when clicking anywhere else
        window.addEventListener('click', () => {
            document.querySelectorAll('.context-menu').forEach(m => m.style.display = 'none');
        });

        // Context menu actions
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

