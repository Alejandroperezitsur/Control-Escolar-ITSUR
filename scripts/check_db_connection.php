<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    echo "Database connection successful.\n";

    // Check users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Users table columns: " . implode(', ', $columns) . "\n";

    // Check if admin exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE rol = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "Admin user found: " . $admin['email'] . "\n";
    } else {
        echo "No admin user found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
