<?php
/**
 * Provides a visual overview of the system using charts and graphs.
 */
//session_start();
require_once '../includes/config.php';
require_once '../models/Organization.php';
require_once '../models/User.php';
require_once '../models/File.php'; // Added File model

if (!is_logged_in() || !is_super_admin()) {
    redirect('login.php');
}

// --- DATA FETCHING FOR CHARTS & STATS ---
try {
    $org_model = new Organization($pdo);
    $user_model = new User($pdo);
    $file_model = new File($pdo);

    // Data for stat cards
    $pending_organizations = $org_model->getPending();
    $approved_organizations = $org_model->getApproved();
    $all_users = $user_model->getAllUsers();

    // Data for charts
    $org_stats = $org_model->getOrganizationStats();
    $file_type_stats = $file_model->getFileTypeDistribution();

    // Prepare data for JavaScript
    $org_names = json_encode(array_column($org_stats, 'name'));
    $user_counts = json_encode(array_column($org_stats, 'user_count'));
    $storage_usage = json_encode(array_column($org_stats, 'storage_used'));

    $file_extensions = json_encode(array_column($file_type_stats, 'extension'));
    $file_counts = json_encode(array_column($file_type_stats, 'count'));

} catch (PDOException $e) {
    error_log('Super Admin Dashboard Error: ' . $e->getMessage());
    $page_error = 'Could not load dashboard data. Please try again later.';
    $pending_organizations = $approved_organizations = $all_users = [];
    $org_names = $user_counts = $storage_usage = $file_extensions = $file_counts = '[]';
}

$error = $_SESSION['page_error'] ?? null;
$success = $_SESSION['page_success'] ?? null;
unset($_SESSION['page_error'], $_SESSION['page_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Super Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .chart-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
             <div class="logo"><i class="fas fa-cloud"> </i> <?= htmlspecialchars(APP_NAME) ?></div>
            <nav>
                <ul>
                    <li><a href="super_admin_dashboard.php"><i class="fas fa-home"> </i> Dashboard</a></li>
                    <li><a href="manage_organizations.php"><i class="fas fa-setting"> </i> Manage Organizations</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
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

            <div class="chart-container">
                <div class="chart-card">
                    <h3>Users per Organization</h3>
                    <canvas id="usersPerOrgChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Storage Usage (Bytes)</h3>
                    <canvas id="storageUsageChart"></canvas>
                </div>
                 <div class="chart-card">
                    <h3>File Type Distribution</h3>
                    <canvas id="fileTypesChart"></canvas>
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
                                <th>Requested By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_organizations as $org): ?>
                                <tr>
                                    <td><?= htmlspecialchars($org['name']) ?></td>
                                    <td><?= htmlspecialchars($org['description'] ?: 'No description provided') ?></td>
                                    <td>User ID: <?= htmlspecialchars($org['requested_by']) ?></td>
                                    <td>
                                        <form action="../controllers/admin_controller.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="org_id" value="<?= $org['id'] ?>">
                                            <button type="submit" name="approve_org" class="btn btn-success">Approve</button>
                                        </form>
                                        <form action="../controllers/admin_controller.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['id']) ?>">
                                            <button type="submit" name="reject_org" class="btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to reject and delete this organization?')">Reject</button>
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
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const orgNames = <?= $org_names ?>;
        const userCounts = <?= $user_counts ?>;
        const storageUsage = <?= $storage_usage ?>;
        const fileExtensions = <?= $file_extensions ?>;
        const fileCounts = <?= $file_counts ?>;
        
        // Users per Organization Chart
        new Chart(document.getElementById('usersPerOrgChart'), {
            type: 'bar',
            data: {
                labels: orgNames,
                datasets: [{
                    label: '# of Users',
                    data: userCounts,
                    backgroundColor: 'rgba(52, 152, 219, 0.5)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });

        // Storage Usage Chart
        new Chart(document.getElementById('storageUsageChart'), {
            type: 'bar',
            data: {
                labels: orgNames,
                datasets: [{
                    label: 'Bytes Used',
                    data: storageUsage,
                    backgroundColor: 'rgba(46, 204, 113, 0.5)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });

        // File Types Chart
        new Chart(document.getElementById('fileTypesChart'), {
            type: 'doughnut',
            data: {
                labels: fileExtensions,
                datasets: [{
                    label: 'File Types',
                    data: fileCounts,
                    backgroundColor: [
                        'rgba(231, 76, 60, 0.7)', 'rgba(52, 152, 219, 0.7)',
                        'rgba(241, 196, 15, 0.7)', 'rgba(46, 204, 113, 0.7)',
                        'rgba(155, 89, 182, 0.7)', 'rgba(26, 188, 156, 0.7)',
                        'rgba(230, 126, 34, 0.7)', 'rgba(149, 165, 166, 0.7)'
                    ]
                }]
            }
        });
    });
    </script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>

