<?php
/**
 * Execute Database Migration: Transform to Real University System
 * 
 * This script executes the transformation migration for the control escolar system.
 * Run from command line: php apply_transform_migration.php
 */

require_once __DIR__ . '/config/config.php';

echo "\n";
echo "===============================================================\n";
echo " Database Migration: Transform to Real University System\n";
echo "===============================================================\n\n";

try {
    $config = require __DIR__ . '/config/config.php';
    
    // Create PDO connection using config
    $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']};charset=utf8mb4";
    $pdo = new PDO(
        $dsn,
        $config['db']['user'],
        $config['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "[INFO] Connected to database successfully.\n\n";
    
    // Read the migration file
    $sqlFile = __DIR__ . '/migrations/transform_to_real_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    echo "[INFO] Reading migration file...\n";
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon but be careful with stored procedures, triggers, etc.
    // For now, we'll execute the whole file as is
    echo "[INFO] Executing migration...\n\n";
    
    // Execute in transaction where possible
    $pdo->beginTransaction();
    
    try {
        // Split statements more intelligently
        $statements = preg_split('/;\s*[\r\n]+/', $sql);
        $executed = 0;
        $skipped = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            // Skip empty statements and comments
            if (empty($statement) || 
                str_starts_with($statement, '--') || 
                str_starts_with($statement, '/*') ||
                preg_match('/^SET\s+@/i', $statement) ||
                str_starts_with($statement, '===')) {
                continue;
            }
            
            // Execute the statement
            try {
                $pdo->exec($statement);
                $executed++;
                
                // Show progress for important statements
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "  ✓ Created table: {$matches[1]}\n";
                    }
                } elseif (stripos($statement, 'CREATE VIEW') !== false || 
                          stripos($statement, 'CREATE OR REPLACE VIEW') !== false) {
                    preg_match('/VIEW\s+`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "  ✓ Created view: {$matches[1]}\n";
                    }
                } elseif (stripos($statement, 'DROP TABLE') !== false) {
                    preg_match('/DROP TABLE\s+(?:IF EXISTS\s+)?`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "  ✓ Dropped table: {$matches[1]}\n";
                    }
                } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                    preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        echo "  ✓ Altered table: {$matches[1]}\n";
                    }
                }
            } catch (PDOException $e) {
                // Some errors are acceptable (e.g., column already exists)
                if (stripos($e->getMessage(), 'Duplicate column') !== false ||
                    stripos($e->getMessage(), 'already exists') !== false) {
                    $skipped++;
                } else {
                    echo "  ⚠ Warning: " . $e->getMessage() . "\n";
                }
            }
        }
        
        $pdo->commit();
        
        echo "\n[SUCCESS] Migration executed successfully!\n";
        echo "  - Statements executed: $executed\n";
        echo "  - Statements skipped: $skipped\n\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    // Verify the migration
    echo "\n[INFO] Verifying migration...\n\n";
    
    // Check new tables exist
    $tables = [
        'calificaciones_unidades',
        'calificaciones_finales'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ Table exists: $table\n";
        } else {
            echo "  ✗ Table missing: $table\n";
        }
    }
    
    // Check views exist
    $views = [
        'view_kardex',
        'view_carga_academica',
        'view_estadisticas_alumno'
    ];
    
    echo "\n";
    foreach ($views as $view) {
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_control_escolar = '$view'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ View exists: $view\n";
        } else {
            echo "  ✗ View missing: $view\n";
        }
    }
    
    // Check materias columns
    echo "\n[INFO] Checking materias table structure...\n";
    $columns = ['num_unidades', 'creditos', 'tipo'];
    $stmt = $pdo->query("DESCRIBE materias");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    foreach ($columns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "  ✓ Column exists: $col\n";
        } else {
            echo "  ✗ Column missing: $col\n";
        }
    }
    
    echo "\n";
    echo "===============================================================\n";
    echo " Migration Complete!\n";
    echo "===============================================================\n\n";
    echo "Next steps:\n";
    echo "  1. Generate realistic test data\n";
    echo "  2. Create Kardex controller and views\n";
    echo "  3. Create Academic Load controller and views\n";
    echo "  4. Update grading system to use units\n\n";
    
} catch (Exception $e) {
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
