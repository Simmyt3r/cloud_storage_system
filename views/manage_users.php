<?php
/**
 * Manage Users Page (Admin & Super Admin)
 *
 * Allows an administrator to view users within their organization,
 * and a super administrator to view all users across all organizations.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php'; // Ensures helper functions are available
require_once '../models/User.php';
require_once '../models/Organization.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authorization check: User must be an admin or a super admin.
if (!is_logged_in() || (!is_admin() && !is_super_admin())) {
    redirect('login.php');
}

// --- 2. DATA FETCHING ---
try {
    $user_model = new User($pdo);
    $org_model = new Organization($pdo);
    $organization = null;

    // Logic adapts based on the user's role.
    if (is_super_admin()) {
        // Super admin sees all users from all organizations.
        // The getAllUsers() method in the model is updated for performance.
        $users = $user_model->getAllUsers();
        $page_heading = "Manage All Users";
    } else {
        // Regular admin sees only users from their own organization.
        $org_id = get_user_organization_id();
        $organization = $org_model->findById($org_id);
        $users = $user_model->getUsersByOrganization($org_id);
        $page_heading = "Manage Users";
    }

} catch (PDOException $e) {
    error_log('Manage Users Error: ' . $e->getMessage());
    $page_error = 'Could not load user data. Please try again later.';
    $users = [];
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
    <title><?= htmlspecialchars(APP_NAME) ?> - Manage Users</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <!-- Navigation adapts to user role -->
                    <?php if (is_super_admin()): ?>
                        <li><a href="super_admin_dashboard.php">Dashboard</a></li>
                        <li><a href="manage_organizations.php">Manage Organizations</a></li>
                    <?php else: ?>
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <li><a href="manage_folders.php">Manage Folders</a></li>
                        <li><a href="manage_files.php">Manage Files</a></li>
                    <?php endif; ?>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1><?= htmlspecialchars($page_heading) ?></h1>
            
            <?php if (is_admin() && $organization): ?>
                <p>Organization: <?= htmlspecialchars($organization['name']) ?></p>
            <?php endif; ?>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <div class="user-grid">
                <?php if (empty($users)): ?>
                    <p>No users found.</p>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                            
                            <?php if (is_super_admin()): ?>
                                <p><strong>Organization:</strong> <?= htmlspecialchars($user['organization_name'] ?? 'N/A') ?></p>
                            <?php endif; ?>

                            <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                            <p><strong>Status:</strong> 
                                <?php if ($user['is_active']): ?>
                                    <span class="status-active" style="color: green; font-weight: bold;">Active</span>
                                <?php else: ?>
                                    <span class="status-pending" style="color: orange; font-weight: bold;">Pending Approval</span>
                                <?php endif; ?>
                            </p>

                            <div class="user-card-actions">
                                <!-- Show "Activate" button if user is inactive AND the viewer has permission -->
                                <?php if (!$user['is_active'] && ($user['organization_id'] === get_user_organization_id() || is_super_admin())): ?>
                                    <form action="../controllers/admin_controller.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="activate_user" class="btn btn-success">Activate</button>
                                    </form>
                                <?php endif; ?>
                                
                                <!-- A single edit link for simplicity -->
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-primary">Edit</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>

