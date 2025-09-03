<?php
require_once 'includes/db_connection.php';

// Create system admin organization
try {
    $stmt = $pdo->prepare("INSERT INTO organizations (name, description, approved) VALUES (?, ?, ?)");
    $stmt->execute(['System Admin', 'System administration organization', true]);
    $org_id = $pdo->lastInsertId();
    echo "Created System Admin organization with ID: $org_id\n";
} catch(Exception $e) {
    echo "Error creating organization: " . $e->getMessage() . "\n";
    // Try to get existing organization ID
    $stmt = $pdo->prepare("SELECT id FROM organizations WHERE name = 'System Admin' LIMIT 1");
    $stmt->execute();
    $org = $stmt->fetch();
    $org_id = $org ? $org['id'] : 1;
    echo "Using existing organization ID: $org_id\n";
}

// Create super admin user
try {
    $password_hash = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$org_id, 'superadmin', 'superadmin@example.com', $password_hash, 'Super', 'Admin', 'super_admin']);
    echo "Super admin user created successfully!\n";
    echo "Username: superadmin\n";
    echo "Password: password\n";
    echo "Email: superadmin@example.com\n";
} catch(Exception $e) {
    echo "Error creating super admin user: " . $e->getMessage() . "\n";
}
?>