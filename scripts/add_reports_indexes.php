<?php
// Ejecutar: php scripts/add_reports_indexes.php
// Este script crea índices útiles para acelerar reportes y agregaciones.
// Comprueba si el índice existe antes de crearlo.

require_once __DIR__ . '/../config/db.php';
$config = include __DIR__ . '/../config/config.php';
$dbName = $config['db']['name'] ?? 'control_escolar';

try {
    $db = Database::getInstance()->getConnection();
} catch (Throwable $e) {
    echo "ERROR: no se pudo conectar a la DB: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$indexes = [
    ['table' => 'grupos', 'name' => 'idx_grupos_ciclo', 'cols' => '(ciclo)'],
    ['table' => 'grupos', 'name' => 'idx_grupos_materia', 'cols' => '(materia_id)'],
    ['table' => 'grupos', 'name' => 'idx_grupos_profesor', 'cols' => '(profesor_id)'],
    ['table' => 'calificaciones', 'name' => 'idx_calif_grupo', 'cols' => '(grupo_id)'],
    ['table' => 'calificaciones', 'name' => 'idx_calif_alumno', 'cols' => '(alumno_id)'],
    ['table' => 'calificaciones', 'name' => 'idx_calif_grupo_final', 'cols' => '(grupo_id, final)'],
    ['table' => 'usuarios', 'name' => 'idx_usuarios_rol_activo', 'cols' => '(rol, activo)'],
    // index por prefijo en nombre de materias (ajusta longitud si tu collation lo requiere)
    ['table' => 'materias', 'name' => 'idx_materias_nombre', 'cols' => '(nombre(100))'],
];

foreach ($indexes as $ix) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND INDEX_NAME = :iname");
        $stmt->execute([':db' => $dbName, ':tbl' => $ix['table'], ':iname' => $ix['name']]);
        $exists = (int)$stmt->fetchColumn();
        if ($exists > 0) {
            echo "Índice ya existe: {$ix['name']} en {$ix['table']}\n";
            continue;
        }
        $sql = "ALTER TABLE `{$ix['table']}` ADD INDEX `{$ix['name']}` {$ix['cols']};";
        $db->exec($sql);
        echo "Índice creado: {$ix['name']} en {$ix['table']} ({$ix['cols']})\n";
    } catch (Throwable $e) {
        echo "Error creando índice {$ix['name']} en {$ix['table']}: " . $e->getMessage() . PHP_EOL;
    }
}

echo "Operación finalizada. Revisa mensajes anteriores para posibles errores.\n";
