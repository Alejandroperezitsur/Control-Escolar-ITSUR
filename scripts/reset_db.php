<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    echo "Connected to database.\n";
    
    // 1. Run Master Schema
    $schemaFile = __DIR__ . '/../migrations/master_schema.sql';
    if (!file_exists($schemaFile)) die("Schema file not found.\n");
    
    echo "Resetting schema...\n";
    $sql = file_get_contents($schemaFile);
    $pdo->exec($sql);
    echo "Schema reset successfully.\n";
    
    // 2. Run Seed
    $seedFile = __DIR__ . '/../migrations/seed_complete_realistic_data.sql';
    if (!file_exists($seedFile)) die("Seed file not found.\n");
    
    echo "Seeding data (this may take a while)...\n";
    $sql = file_get_contents($seedFile);
    
    // Split by semicolon for safety/progress
    $statements = explode(";\n", $sql);
    $total = count($statements);
    $count = 0;
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
            $count++;
            if ($count % 50 == 0) echo "Seeding: $count / $total\r";
        } catch (PDOException $e) {
            // Ignore TRUNCATE errors if tables were just dropped/created
            if (strpos($stmt, 'TRUNCATE') !== false) continue;
            echo "\nError: " . $e->getMessage() . "\nStmt: " . substr($stmt, 0, 50) . "...\n";
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\nSeeding completed.\n";
    
    // Verify
    echo "Alumnos: " . $pdo->query("SELECT count(*) FROM alumnos")->fetchColumn() . "\n";
    echo "Profesores: " . $pdo->query("SELECT count(*) FROM usuarios WHERE rol='profesor'")->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
