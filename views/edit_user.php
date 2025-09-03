<?php
/**
 * Edit User Page
 *
 * Allows admins to modify user details. Super admins have extended
 * privileges to change roles and organizations.
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

    // Security check: Ensure user exists
    if (!$user) {
        $_SESSION['page_error'] = "User not found.";
        redirect('manage_users.php');
    }

    // Security check for regular admins: Can only edit users in their own org
    if (is_admin() && !is_super_admin()) {
        if ($user['organization_id'] !== get_user_organization_id()) {
            $_SESSION['page_error'] = "You do not have permission to edit this user.";
            redirect('manage_users.php');
        }
    }
    
    // For super admins, fetch all organizations for the dropdown
    $organizations = is_super_admin() ? $org_model->getAll() : [];

} catch (PDOException $e) {
    $_SESSION['page_error'] = 'A database error occurred.';
    error_log('Edit User Page Error: ' . $e->getMessage());
    redirect('manage_users.php');
}

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
        <!-- Header navigation from your manage_users.php can be copied here -->
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Edit User: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
            
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
                            <select id="role" name="role">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                            </select>
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
                    <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
```

### 2. Update `controllers/admin_controller.php`

Now, add the logic to your admin controller to handle the form submission from the new edit page.

```php
// Inside controllers/admin_controller.php

// --- ACTION ROUTING ---
// ... add this line to your routing logic ...
if (isset($_POST['update_user'])) $action = 'update_user';

// ... add this case to your switch statement ...
case 'update_user':
    handle_user_update($user_model);
    break;

// ... add this new handler function at the bottom of the file ...
/**
 * Handles updating a user's details.
 */
function handle_user_update($user_model) {
    $user_id = (int)$_POST['user_id'];
    $data = [
        'first_name' => sanitize_input($_POST['first_name']),
        'last_name'  => sanitize_input($_POST['last_name']),
        'email'      => filter_var($_POST['email'], FILTER_SANITIZE_EMAIL),
        'password'   => $_POST['password'] // Pass it directly, model will handle hashing if not empty
    ];

    // Super admins can change role, organization, and status
    if (is_super_admin()) {
        $data['role'] = $_POST['role'];
        $data['organization_id'] = (int)$_POST['organization_id'];
        $data['is_active'] = (int)$_POST['is_active'];
    }

    // Security check: Ensure the admin has permission to edit this user
    $user_to_edit = $user_model->findById($user_id);
    if (!is_super_admin() && $user_to_edit['organization_id'] !== get_user_organization_id()) {
        throw new Exception("You do not have permission to edit this user.");
    }
    
    if ($user_model->update($user_id, $data)) {
        $_SESSION['page_success'] = "User updated successfully.";
    } else {
        throw new Exception("Failed to update user details.");
    }
    
    redirect('../views/manage_users.php');
}
