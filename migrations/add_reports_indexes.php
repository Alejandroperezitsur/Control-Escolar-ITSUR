<?php
// Migración PHP: add_reports_indexes.php
// Fecha: 2025-11-23
// Ejecutar: php migrations/add_reports_indexes.php
// Crea índices útiles para acelerar consultas de reportes. Idempotente.

require_once __DIR__ . '/../config/db.php';

try {
    $db = Database::getInstance()->getConnection();
} catch (Throwable $e) {
    echo "ERROR: no se pudo conectar a la base de datos: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

try {
    $dbName = (string)$db->query('SELECT DATABASE()')->fetchColumn();
} catch (Throwable $e) {
    $dbName = '';
}

$indexes = [
    ['table' => 'grupos', 'name' => 'idx_grupos_ciclo', 'cols' => '`ciclo`'],
    ['table' => 'grupos', 'name' => 'idx_grupos_materia', 'cols' => '`materia_id`'],
    ['table' => 'grupos', 'name' => 'idx_grupos_profesor', 'cols' => '`profesor_id`'],
    ['table' => 'calificaciones', 'name' => 'idx_calif_grupo', 'cols' => '`grupo_id`'],
    ['table' => 'calificaciones', 'name' => 'idx_calif_alumno', 'cols' => '`alumno_id`'],
    ['table' => 'calificaciones', 'name' => 'idx_calif_grupo_final', 'cols' => '`grupo_id`, `final`'],
    ['table' => 'usuarios', 'name' => 'idx_usuarios_rol_activo', 'cols' => '`rol`, `activo`'],
    // Nota: si tu columna 'nombre' es corta o tu collation no admite prefijos, ajusta la longitud.
    ['table' => 'materias', 'name' => 'idx_materias_nombre', 'cols' => '`nombre`(100)'],
];

foreach ($indexes as $ix) {
    $tbl = $ix['table'];
    $iname = $ix['name'];
    try {
        $chk = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND INDEX_NAME = :iname");
        $chk->execute([':db' => $dbName, ':tbl' => $tbl, ':iname' => $iname]);
        $exists = (int)$chk->fetchColumn();
    } catch (Throwable $e) {
        echo "Error comprobando índice {$iname} en {$tbl}: " . $e->getMessage() . PHP_EOL;
        $exists = 0;
    }

    if ($exists > 0) {
        echo "Índice ya existe: {$iname} en {$tbl}\n";
        continue;
    }

    $sql = "ALTER TABLE `{$tbl}` ADD INDEX `{$iname}` ({$ix['cols']})";
    try {
        $db->exec($sql);
        echo "Índice creado: {$iname} en {$tbl} ({$ix['cols']})\n";
    } catch (Throwable $e) {
        echo "ERROR al crear índice {$iname} en {$tbl}: " . $e->getMessage() . PHP_EOL;
        // No abortamos; intentamos seguir con los demás índices.
    }
}

echo "Operación finalizada. Revisa con SHOW INDEX FROM <tabla> o con phpMyAdmin.\n";
