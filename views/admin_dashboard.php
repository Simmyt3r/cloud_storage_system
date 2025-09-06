<?php
/**
 * Admin Dashboard Page
 *
 * Displays statistics and recent activity for an organization's administrator.
 * This script is responsible for fetching all necessary data and rendering the view.
 */

// --- 1. INITIALIZATION & SECURITY ---
// Always start the session at the very top of the script.
//session_start();

// Include necessary configuration and model files.
require_once '../includes/config.php';
require_once '../models/Folder.php';
require_once '../models/File.php';
require_once '../models/User.php';
require_once '../models/Organization.php';

// Authorization check: Ensure the user is logged in and is an administrator.
if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

// --- 2. HELPER FUNCTION ---
/**
 * Formats a file size in bytes into a human-readable string.
 *
 * @param int $bytes The file size in bytes.
 * @return string The formatted file size (e.g., "1.23 MB").
 */
function formatFileSize(int $bytes): string {
    if ($bytes <= 0) {
        return '0 Bytes';
    }
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, $k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// --- 3. DATA FETCHING & PROCESSING ---
// All data fetching is wrapped in a try-catch block for graceful error handling.
try {
    $org_id = get_user_organization_id();

    // Instantiate models
    $org_model = new Organization($pdo);
    $user_model = new User($pdo);
    $folder_model = new Folder($pdo);
    $file_model = new File($pdo);

    // Fetch all necessary data in advance
    $organization = $org_model->findById($org_id);
    $users = $user_model->getUsersByOrganization($org_id);
    $folders = $folder_model->getFoldersByOrganization($org_id, null);
    $files = $file_model->getFilesByOrganization($org_id); // Assuming this gets recent files

    // --- PERFORMANCE FIX (N+1 Query Problem) ---
    // Instead of querying the database for each folder name inside the loop,
    // we create a map of [folder_id => folder_name] for quick lookups.
    $folder_map = array_column($folders, 'name', 'id');

    // --- EFFICIENCY IMPROVEMENT ---
    // Calculate total storage size more efficiently without a foreach loop.
    $total_storage_bytes = array_sum(array_column($files, 'file_size'));

} catch (PDOException $e) {
    // If the database fails, log the error and set defaults to prevent page crash.
    error_log('Admin Dashboard Error: ' . $e->getMessage());
    $page_error = 'Could not load dashboard data. Please try again later.';
    $organization = ['name' => 'Error'];
    $users = $folders = $files = [];
    $total_storage_bytes = 0;
}

// --- 4. FLASH MESSAGES ---
// Display and then clear any status messages from previous actions.
$error = $_SESSION['page_error'] ?? null;
$success = $_SESSION['page_success'] ?? null;
unset($_SESSION['page_error'], $_SESSION['page_success']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 5. SECURITY: Escape all dynamic output to prevent XSS attacks -->
    <title><?= htmlspecialchars(APP_NAME) ?> - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><i class="fas fa-cloud"> </i> <?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <li><a href="manage_folders.php">Manage Folders</a></li>
                    <li><a href="manage_files.php">Manage Files</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <!-- 6. BEST PRACTICE: Centralize logout logic -->
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Admin Dashboard</h1>
            <p>Welcome, <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>!</p>
            <p>Organization: <?= htmlspecialchars($organization ? $organization['name'] : 'Unknown') ?></p>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-value"><?= count($users) ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Folders</h3>
                    <div class="stat-value"><?= count($folders) ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Files</h3>
                    <div class="stat-value"><?= count($files) ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Storage</h3>
                    <div class="stat-value"><?= formatFileSize($total_storage_bytes) ?></div>
                </div>
            </div>
            
            <h2>Recent Files</h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Folder</th>
                            <th>Size</th>
                            <th>Uploaded By</th>
                            <th>Upload Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($files)): ?>
                            <tr>
                                <td colspan="6">No files have been uploaded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><?= htmlspecialchars($file['name']) ?></td>
                                    <td>
                                        <!-- Using the pre-built map is much faster than a DB query here -->
                                        <?= htmlspecialchars($folder_map[$file['folder_id']] ?? 'Root') ?>
                                    </td>
                                    <td><?= formatFileSize($file['file_size']) ?></td>
                                    <td><?= htmlspecialchars($file['uploaded_by_username']) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($file['uploaded_at'])) ?></td>
                                    <td>
                                        <a href="../controllers/file_controller.php?download=1&file_id=<?= htmlspecialchars($file['id']) ?>" 
                                           class="btn btn-primary">Download</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/script.js"></script>
</body>
</html>
<?php
include "footer.php";
