<?php
require_once 'includes/config.php';

echo "<h1>Cloud Storage System - Database Test</h1>";

// Test database connection
try {
    $stmt = $pdo->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll();
    
    echo "<h2>Database Tables</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . reset($table) . "</li>";
    }
    echo "</ul>";
    
    echo "<p>Database connection successful!</p>";
} catch(PDOException $e) {
    echo "<p>Database connection failed: " . $e->getMessage() . "</p>";
}
?>