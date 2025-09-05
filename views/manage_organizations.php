<?php
/**
 * Manage Organizations Page (Super Admin)
 *
 * Allows a super administrator to view a list of all organizations
 * in the system and provides links to edit them.
 */

// --- 1. INITIALIZATION & SECURITY ---
// Always start the session at the very top of the script.
//session_start();

// Include necessary configuration and model files.
require_once '../includes/config.php';
require_once '../models/Organization.php';

// Authorization check: Ensure the user is logged in and is a super administrator.
if (!is_logged_in() || !is_super_admin()) {
    redirect('login.php');
}

// --- 2. DATA FETCHING ---
// All data fetching is wrapped in a try-catch block for graceful error handling.
try {
    $org_model = new Organization($pdo);
    $all_organizations = $org_model->getAll();

} catch (PDOException $e) {
    // If the database fails, log the error and set defaults to prevent page crash.
    error_log('Manage Organizations Error: ' . $e->getMessage());
    $page_error = 'Could not load organization data. Please try again later.';
    $all_organizations = []; // Initialize as empty array to prevent errors in the HTML.
}

// --- 3. FLASH MESSAGES ---
// Check for success or error messages set in the session from other actions.
$error = $_SESSION['page_error'] ?? null;
$success = $_SESSION['page_success'] ?? null;

// Unset the session variables so the messages don't show up again on refresh.
unset($_SESSION['page_error'], $_SESSION['page_success']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 4. SECURITY: Escape all dynamic output to prevent XSS attacks -->
    <title><?= htmlspecialchars(APP_NAME) ?> - Manage Organizations</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo"><?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="super_admin_dashboard.php">Dashboard</a></li>
                    <li><a href="manage_organizations.php">Manage Organizations</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <!-- 5. BEST PRACTICE: Centralize logout logic -->
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                 <h1>Manage Organizations</h1>
                 <a href="create_organization.php" class="btn btn-success">Create New Organization</a>
            </div>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Organization Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Approved At</th>
                            <th>Approved By</th>
                            <!-- NEW FEATURE: Actions Column -->
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_organizations)): ?>
                            <tr>
                                <!-- Updated colspan to account for the new column -->
                                <td colspan="7">No organizations found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_organizations as $org): ?>
                                <tr>
                                    <td><?= htmlspecialchars($org['name']) ?></td>
                                    <td><?= htmlspecialchars($org['description'] ?: 'No description') ?></td>
                                    <td>
                                        <span class="status-<?= $org['approved'] ? 'approved' : 'pending' ?>">
                                            <?= $org['approved'] ? 'Approved' : 'Pending' ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y H:i', strtotime($org['created_at'])) ?></td>
                                    <td><?= $org['approved_at'] ? date('M j, Y H:i', strtotime($org['approved_at'])) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars($org['approved_by'] ?: 'N/A') ?></td>
                                    <td>
                                        <!-- NEW FEATURE: Edit Button -->
                                        <a href="edit_organization.php?id=<?= htmlspecialchars($org['id']) ?>" class="btn btn-primary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>

