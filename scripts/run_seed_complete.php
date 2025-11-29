<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    echo "Connected to database.\n";
    
    $sqlFile = __DIR__ . '/../migrations/seed_complete_realistic_data.sql';
    
    if (!file_exists($sqlFile)) {
        die("Error: SQL file not found: $sqlFile\n");
    }
    
    echo "Reading SQL file...\n";
    $sql = file_get_contents($sqlFile);
    
    echo "Executing SQL seed (this may take a moment)...\n";
    
    // Disable foreign key checks for the session
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Execute the whole block. 
    // If it's too large, we might need to split, but for ~200KB it should be fine for PDO.
    // However, splitting by statement is safer for error reporting.
    
    $statements = explode(";\n", $sql); // Split by semicolon+newline to avoid splitting inside strings
    
    $count = 0;
    $total = count($statements);
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        
        try {
            $pdo->exec($stmt);
            $count++;
            if ($count % 10 == 0) {
                echo "Executed $count / $total statements...\r";
            }
        } catch (PDOException $e) {
            echo "\nError executing statement:\n" . substr($stmt, 0, 100) . "...\n";
            echo "Message: " . $e->getMessage() . "\n";
            // Don't stop, try to continue (or should we stop? For seed, maybe stop)
            // die(); 
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\nSeed completed successfully!\n";
    
    // Verification
    $stmt = $pdo->query("SELECT count(*) FROM alumnos");
    echo "Alumnos: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT count(*) FROM usuarios WHERE rol='profesor'");
    echo "Profesores: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT count(*) FROM grupos");
    echo "Grupos: " . $stmt->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
