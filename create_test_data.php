<?php
require_once 'includes/db_connection.php';

// Create a test organization
try {
    $stmt = $pdo->prepare("INSERT INTO organizations (name, description, approved) VALUES (?, ?, ?)");
    $stmt->execute(['Test Organization', 'A sample organization for testing', true]);
    $org_id = $pdo->lastInsertId();
    echo "Created Test Organization with ID: $org_id\n";
} catch(Exception $e) {
    echo "Error creating organization: " . $e->getMessage() . "\n";
    exit;
}

// Create an admin user for the test organization
try {
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$org_id, 'testadmin', 'testadmin@example.com', $password_hash, 'Test', 'Admin', 'admin']);
    echo "Admin user 'testadmin' created successfully!\n";
} catch(Exception $e) {
    echo "Error creating admin user: " . $e->getMessage() . "\n";
}

// Create a regular user for the test organization
try {
    $password_hash = password_hash('user123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (organization_id, username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$org_id, 'testuser', 'testuser@example.com', $password_hash, 'Test', 'User', 'user']);
    echo "Regular user 'testuser' created successfully!\n";
} catch(Exception $e) {
    echo "Error creating regular user: " . $e->getMessage() . "\n";
}

echo "\nTest data creation completed!\n";
echo "Organization: Test Organization\n";
echo "Admin user: testadmin / admin123\n";
echo "Regular user: testuser / user123\n";
?>