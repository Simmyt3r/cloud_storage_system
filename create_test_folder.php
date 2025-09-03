<?php
require_once 'includes/db_connection.php';

// Get the test organization ID
$stmt = $pdo->prepare("SELECT id FROM organizations WHERE name = 'Test Organization' LIMIT 1");
$stmt->execute();
$org = $stmt->fetch();

if (!$org) {
    echo "Test organization not found\n";
    exit;
}

$org_id = $org['id'];

// Get the admin user ID
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'testadmin' AND organization_id = ? LIMIT 1");
$stmt->execute([$org_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Test admin user not found\n";
    exit;
}

$user_id = $user['id'];

// Create a test folder
try {
    $stmt = $pdo->prepare("INSERT INTO folders (organization_id, parent_folder_id, name, description, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$org_id, null, 'Test Folder', 'A sample folder for testing', $user_id]);
    $folder_id = $pdo->lastInsertId();
    echo "Created Test Folder with ID: $folder_id\n";
} catch(Exception $e) {
    echo "Error creating folder: " . $e->getMessage() . "\n";
    exit;
}

// Grant permissions to the regular user
try {
    $stmt = $pdo->prepare("INSERT INTO permissions (user_id, folder_id, permission_level, granted_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $folder_id, 'admin', $user_id]);
    echo "Granted admin permissions to testadmin for Test Folder\n";
} catch(Exception $e) {
    echo "Error granting permissions: " . $e->getMessage() . "\n";
}

// Grant read permission to the regular user
try {
    $stmt = $pdo->prepare("INSERT INTO permissions (user_id, folder_id, permission_level, granted_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id+1, $folder_id, 'read', $user_id]);
    echo "Granted read permissions to testuser for Test Folder\n";
} catch(Exception $e) {
    echo "Error granting permissions: " . $e->getMessage() . "\n";
}

echo "\nTest folder creation completed!\n";
?>