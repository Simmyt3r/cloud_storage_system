<?php
require_once '../includes/config.php';
require_once '../models/Folder.php';
require_once '../models/Organization.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

$folder_model = new Folder($pdo);
$org_model = new Organization($pdo);

// Get organization info
$org_id = get_user_organization_id();
$organization = $org_model->findById($org_id);

// Get root folders for the organization
$folders = $folder_model->getFoldersByOrganization($org_id, null);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_folder'])) {
        $parent_folder_id = !empty($_POST['parent_folder_id']) ? $_POST['parent_folder_id'] : null;
        $name = sanitize_input($_POST['folder_name']);
        $description = sanitize_input($_POST['folder_description']);
        $password = !empty($_POST['folder_password']) ? $_POST['folder_password'] : null;
        $created_by = $_SESSION['user_id'];
        
        if (!empty($name)) {
            if ($folder_model->create($org_id, $parent_folder_id, $name, $description, $created_by, $password)) {
                $success = "Folder created successfully.";
                // Refresh folder list
                $folders = $folder_model->getFoldersByOrganization($org_id, null);
            } else {
                $error = "Failed to create folder.";
            }
        } else {
            $error = "Folder name is required.";
        }
    }
    
    if (isset($_POST['delete_folder'])) {
        $folder_id = $_POST['folder_id'];
        
        if ($folder_model->delete($folder_id)) {
            $success = "Folder deleted successfully.";
            // Refresh folder list
            $folders = $folder_model->getFoldersByOrganization($org_id, null);
        } else {
            $error = "Failed to delete folder.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Manage Folders</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?php echo APP_NAME; ?></div>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <li><a href="manage_folders.php">Manage Folders</a></li>
                    <li><a href="manage_files.php">Manage Files</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="admin_dashboard.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Manage Folders</h1>
            <p>Organization: <?php echo $organization ? $organization['name'] : 'Unknown'; ?></p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Create Folder Form -->
            <div class="form-container">
                <h2>Create New Folder</h2>
                <form action="manage_folders.php" method="POST">
                    <div class="form-group">
                        <label for="folder_name">Folder Name:</label>
                        <input type="text" id="folder_name" name="folder_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="folder_description">Description:</label>
                        <textarea id="folder_description" name="folder_description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="folder_password">Password (optional):</label>
                        <input type="password" id="folder_password" name="folder_password">
                    </div>
                    
                    <button type="submit" name="create_folder" class="btn btn-success">Create Folder</button>
                </form>
            </div>
            
            <!-- Folder List -->
            <h2>Existing Folders</h2>
            
            <div class="folder-grid">
                <?php foreach ($folders as $folder): ?>
                    <div class="folder-card">
                        <h3><?php echo $folder['name']; ?></h3>
                        <p><?php echo !empty($folder['description']) ? $folder['description'] : 'No description'; ?></p>
                        <p>Created: <?php echo date('M j, Y', strtotime($folder['created_at'])); ?></p>
                        
                        <form action="manage_folders.php" method="POST" style="display: inline;">
                            <input type="hidden" name="folder_id" value="<?php echo $folder['id']; ?>">
                            <button type="submit" name="delete_folder" class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete this folder and all its contents?')">Delete</button>
                        </form>
                        
                        <a href="folder_view.php?folder_id=<?php echo $folder['id']; ?>" class="btn btn-primary">View</a>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($folders) == 0): ?>
                    <p>No folders found in this organization.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
<?php
include "footer.php";
?>