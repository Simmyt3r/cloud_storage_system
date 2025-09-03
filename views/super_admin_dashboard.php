<?php
/**
 * Super Admin Dashboard Page
 *
 * Provides an overview of the entire system, including organization
 * approvals and user statistics for the super administrator.
 */

// --- 1. INITIALIZATION & SECURITY ---
// Always start the session at the very top of the script.
//session_start();

// Include necessary configuration and model files.
require_once '../includes/config.php';
require_once '../models/Organization.php';
require_once '../models/User.php';

// Authorization check: Ensure the user is logged in and is a super administrator.
if (!is_logged_in() || !is_super_admin()) {
    redirect('login.php');
}

// --- 2. DATA FETCHING & PROCESSING ---
// All data fetching is wrapped in a try-catch block for graceful error handling.
try {
    // Instantiate models
    $org_model = new Organization($pdo);
    $user_model = new User($pdo);

    // Fetch all necessary data from the database.
    $pending_organizations = $org_model->getPending();
    $approved_organizations = $org_model->getApproved();
    $all_users = $user_model->getAllUsers();

} catch (PDOException $e) {
    // If the database fails, log the error and set defaults to prevent page crash.
    error_log('Super Admin Dashboard Error: ' . $e->getMessage());
    $page_error = 'Could not load dashboard data. Please try again later.';
    // Initialize arrays to prevent "undefined variable" errors in the HTML.
    $pending_organizations = $approved_organizations = $all_users = [];
}

// --- 3. FLASH MESSAGES ---
// Check for success or error messages set in the session by controller actions.
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
    <title><?= htmlspecialchars(APP_NAME) ?> - Super Admin Dashboard</title>
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
                    <!-- 5. BEST PRACTICE: Point logout to a central controller -->
                    <li><a href="../controllers/auth_controller.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="main-content">
            <h1>Super Admin Dashboard</h1>
            <p>Welcome, <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>!</p>
            
            <?php if (isset($page_error)): ?><div class="alert alert-error"><?= htmlspecialchars($page_error) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Pending Organizations</h3>
                    <div class="stat-value"><?= count($pending_organizations) ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Approved Organizations</h3>
                    <div class="stat-value"><?= count($approved_organizations) ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-value"><?= count($all_users) ?></div>
                </div>
            </div>
            
            <h2>Pending Organization Requests</h2>
            
            <?php if (!empty($pending_organizations)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Organization Name</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_organizations as $org): ?>
                                <tr>
                                    <td><?= htmlspecialchars($org['name']) ?></td>
                                    <td><?= htmlspecialchars($org['description'] ?: 'No description provided') ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($org['created_at'])) ?></td>
                                    <td>
                                        <form action="../controllers/admin_controller.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['id']) ?>">
                                            <button type="submit" name="approve_org" class="btn btn-success">Approve</button>
                                        </form>
                                        <form action="../controllers/admin_controller.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['id']) ?>">
                                            <button type="submit" name="reject_org" class="btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to reject and delete this organization?')">Reject</button>
                                        </form>
                                    </td>
                                    <td>
    <!-- Approve Form -->
    <form action="../controllers/admin_controller.php" method="POST" style="display: inline;">
        <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['id']) ?>">
        <button type="submit" name="approve_org" class="btn btn-success">Approve</button>
    </form>
    
    <!-- Reject Form -->
    <form action="../controllers/admin_controller.php" method="POST" style="display: inline;">
        <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['id']) ?>">
        <button type="submit" name="reject_org" class="btn btn-danger" 
                onclick="return confirm('Are you sure you want to reject this organization? This will delete the organization and its admin user.')">
            Reject
        </button>
    </form>
</td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No pending organization requests.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
