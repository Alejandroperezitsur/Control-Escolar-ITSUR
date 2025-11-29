<?php
/**
 * Script para importar seed_complete_realistic_data.sql de forma segura
 * Desactiva temporalmente las restricciones de claves foráneas
 */

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "Iniciando importación segura...\n";
    
    // 1. Desactivar verificación de claves foráneas
    echo "Desactivando restricciones de claves foráneas...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 2. Leer el archivo SQL
    $sqlFile = __DIR__ . '/../migrations/seed_complete_realistic_data.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo no encontrado: $sqlFile");
    }
    
    echo "Leyendo archivo SQL...\n";
    $sql = file_get_contents($sqlFile);
    
    // 3. Dividir en statements individuales
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($stmt) => !empty($stmt) && !preg_match('/^--/', $stmt)
    );
    
    echo "Ejecutando " . count($statements) . " consultas...\n";
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $index => $statement) {
        try {
            if (trim($statement)) {
                $pdo->exec($statement);
                $executed++;
                
                // Mostrar progreso cada 100 consultas
                if ($executed % 100 === 0) {
                    echo "Progreso: $executed consultas ejecutadas...\n";
                }
            }
        } catch (PDOException $e) {
            $errors[] = [
                'index' => $index + 1,
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // 4. Reactivar verificación de claves foráneas
    echo "\nReactivando restricciones de claves foráneas...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 5. Mostrar resultados
    echo "\n=== RESUMEN ===\n";
    echo "Consultas ejecutadas exitosamente: $executed\n";
    echo "Errores encontrados: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "\n=== ERRORES ===\n";
        foreach ($errors as $error) {
            echo "Consulta #{$error['index']}: {$error['error']}\n";
            echo "Statement: {$error['statement']}\n\n";
        }
    }
    
    // 6. Verificar conteos
    echo "\n=== VERIFICACIÓN ===\n";
    $counts = [
        'alumnos' => $pdo->query("SELECT COUNT(*) FROM alumnos")->fetchColumn(),
        'usuarios (profesores)' => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='profesor'")->fetchColumn(),
        'materias' => $pdo->query("SELECT COUNT(*) FROM materias")->fetchColumn(),
        'grupos' => $pdo->query("SELECT COUNT(*) FROM grupos")->fetchColumn(),
        'calificaciones' => $pdo->query("SELECT COUNT(*) FROM calificaciones")->fetchColumn(),
    ];
    
    foreach ($counts as $table => $count) {
        echo "$table: $count\n";
    }
    
    echo "\n✓ Importación completada\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
