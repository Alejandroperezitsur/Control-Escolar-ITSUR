<?php
/**
 * Script para ejecutar la migración de campos de materias y calificaciones
 */

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "Ejecutando migración: add_flexible_grading_fields.sql\n\n";
    
    $sqlFile = __DIR__ . '/../migrations/add_flexible_grading_fields.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo no encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir en statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($stmt) => !empty($stmt) && !preg_match('/^--/', trim($stmt))
    );
    
    echo "Ejecutando " . count($statements) . " consultas...\n\n";
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            echo "[$index] Ejecutando: " . substr($statement, 0, 60) . "...\n";
            $pdo->exec($statement);
            echo "  ✓ OK\n";
        } catch (PDOException $e) {
            // Si el error es "columna ya existe", continuar
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "  → Ya existe, saltando...\n";
                continue;
            }
            echo "  ✗ ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ Migración completada\n";
   echo "\nVerificando cambios...\n";
    
    // Verificar campos en materias
    $materiasColumns = $pdo->query("SHOW COLUMNS FROM materias")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n📋 Columnas en tabla materias:\n";
    foreach ($materiasColumns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    // Verificar campos en calificaciones
    $calificacionesColumns = $pdo->query("SHOW COLUMNS FROM calificaciones")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n📋 Columnas en tabla calificaciones:\n";
    foreach ($calificacionesColumns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
