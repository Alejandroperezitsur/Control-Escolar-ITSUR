<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    echo "Connected.\n";
    
    $sql = file_get_contents(__DIR__ . '/../migrations/control_escolar.sql');
    
    // Split by semicolon but handle potential semicolons in strings if simple split fails
    // For this simple SQL, splitting by ; is usually fine
    $statements = explode(';', $sql);
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Ignore "table already exists" or similar if we want to be idempotent
                // But for now let's just print error
                echo "Error executing statement: " . substr($stmt, 0, 50) . "... " . $e->getMessage() . "\n";
            }
        }
    }
    echo "Migration completed.\n";
    
    // Verify tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
