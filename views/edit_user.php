<?php
/**
 * Edit User Page (Revised)
 *
 * Allows admins to modify user details with proper feedback and navigation.
 * Super admins have extended privileges.
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

// --- 2. DATA FETCHING & VALIDATION ---
$user_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id_to_edit <= 0) {
    $_SESSION['page_error'] = "Invalid user specified.";
    redirect('manage_users.php');
}

try {
    $user_model = new User($pdo);
    $org_model = new Organization($pdo);
    
    $user = $user_model->findById($user_id_to_edit);

    if (!$user) {
        $_SESSION['page_error'] = "User not found.";
        redirect('manage_users.php');
    }

    if (is_admin() && !is_super_admin()) {
        if ($user['organization_id'] !== get_user_organization_id()) {
            $_SESSION['page_error'] = "You do not have permission to edit this user.";
            redirect('manage_users.php');
        }
    }
    
    $organizations = is_super_admin() ? $org_model->getAll() : [];

} catch (PDOException $e) {
    $_SESSION['page_error'] = 'A database error occurred.';
    error_log('Edit User Page Error: ' . $e->getMessage());
    redirect('manage_users.php');
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
    <title><?= htmlspecialchars(APP_NAME) ?> - Edit User</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <?php endif; ?>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Edit User: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
            
            <?php if (isset($error)): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if (isset($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <div class="form-container">
                <form action="../controllers/admin_controller.php" method="POST">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">New Password (optional):</label>
                        <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                    </div>

                    <?php if (is_super_admin()): ?>
                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" <?= $user['role'] === 'super_admin' ? 'disabled' : '' ?>>
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                            </select>
                             <?php if ($user['role'] === 'super_admin'): ?>
                                <small>The Super Admin role cannot be changed.</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="organization_id">Organization:</label>
                            <select id="organization_id" name="organization_id">
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?= $org['id'] ?>" <?= $user['organization_id'] == $org['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($org['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                             <label for="is_active">Status:</label>
                             <select id="is_active" name="is_active">
                                 <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Active</option>
                                 <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>Inactive</option>
                             </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="update_user" class="btn btn-success">Update User</button>
                    <a href="manage_users.php" class="btn">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
