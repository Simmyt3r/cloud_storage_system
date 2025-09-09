<?php
/**
 * Manage Users Page (Admin & Super Admin) - Upgraded
 *
 * Allows administrators to manage users with granular controls directly
 * from a table-based interface. Actions include activate/deactivate,
 * role change (super admin), and deletion.
 */

// --- 1. INITIALIZATION & SECURITY ---
require_once '../includes/config.php';
require_once '../includes/functions.php'; 
require_once '../models/User.php';
require_once '../models/Organization.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in() || (!is_admin() && !is_super_admin())) {
    redirect('login.php');
}

// --- 2. DATA FETCHING ---
try {
    $user_model = new User($pdo);
    $org_model = new Organization($pdo);
    $organization_name = null;

    if (is_super_admin()) {
        $users = $user_model->getAllUsers();
        $page_heading = "Manage All Users";
    } else {
        $org_id = get_user_organization_id();
        $org_details = $org_model->findById($org_id);
        $organization_name = $org_details['name'] ?? 'Your Organization';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <?php if (is_super_admin()): ?>
                        <li><a href="super_admin_dashboard.php">Dashboard</a></li>
                        <li><a href="manage_organizations.php">Organizations</a></li>
                    <?php else: ?>
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <li><a href="manage_folders.php">Folders</a></li>
                        <li><a href="manage_files.php">Files</a></li>
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
            
            <?php if ($organization_name): ?>
                <p>Organization: <?= htmlspecialchars($organization_name) ?></p>
            <?php endif; ?>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <?php if (is_super_admin()): ?><th>Organization</th><?php endif; ?>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="<?= is_super_admin() ? '6' : '5' ?>">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <?php if (is_super_admin()): ?><td><?= htmlspecialchars($user['organization_name'] ?? 'N/A') ?></td><?php endif; ?>
                                    <td>
                                        <?php if (is_super_admin() && $_SESSION['user_id'] != $user['id']): ?>
                                            <form action="../controllers/admin_controller.php" method="POST">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="role" onchange="this.form.submit()">
                                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    <option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                                </select>
                                                <input type="hidden" name="update_user_role" value="1">
                                            </form>
                                        <?php else: ?>
                                            <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span style="color: green;">Active</span>
                                        <?php else: ?>
                                            <span style="color: orange;">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn">Edit</a>
                                        <form action="../controllers/admin_controller.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="is_active" value="<?= $user['is_active'] ? '0' : '1' ?>">
                                            <button type="submit" name="update_user_status" class="btn <?= $user['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                                <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                        <form action="../controllers/admin_controller.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this user?');">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
include "footer.php";
?>