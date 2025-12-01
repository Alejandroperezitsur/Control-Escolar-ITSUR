<?php
/**
 * Quick Database Migration - Execute with: php quick_migrate.php
 */

echo "\n=== Database Migration ===\n\n";

// Try to connect to MySQL
$mysqli = @new mysqli('127.0.0.1', 'root', '', '', 3306);

if ($mysqli->connect_error) {
    die("Error: Cannot connect to MySQL. Please ensure XAMPP MySQL is running.\nError: " . $mysqli->connect_error . "\n");
}

echo "[OK] Connected to MySQL\n";

// Create database if not exists
$mysqli->query("CREATE DATABASE IF NOT EXISTS control_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "[OK] Database 'control_escolar' ready\n";

// Select database
$mysqli->select_db('control_escolar');

// Read migration file
$sql = file_get_contents(__DIR__ . '/migrations/transform_to_real_system.sql');

// Execute migration (multi_query for multiple statements)
if ($mysqli->multi_query($sql)) {
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    
    if ($mysqli->errno) {
        echo "[ERROR] " . $mysqli->error . "\n";
    } else {
        echo "[OK] Migration executed successfully\n\n";
        
        // Verify tables
        $result = $mysqli->query("SHOW TABLES LIKE 'calificaciones%'");
        echo "Tables created: " . $result->num_rows . "\n";
        
        // Check views
        $result = $mysqli->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
        echo "Views created: " . $result->num_rows . "\n";
    }
} else {
    echo "[ERROR] " . $mysqli->error . "\n";
}

$mysqli->close();
echo "\nDone!\n";
