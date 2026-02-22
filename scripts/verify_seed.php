<?php

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$config = require __DIR__ . '/../config/config.php';
$env = (string)($config['app']['env'] ?? getenv('APP_ENV') ?: 'local');
if ($env === 'production') {
    fwrite(STDERR, "Verificación de seed deshabilitada en producción\n");
    exit(1);
}

$pdo = Database::getInstance()->getConnection();

function countTable(PDO $pdo, string $table): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$counts = [
    'alumnos' => countTable($pdo, 'alumnos'),
    'profesores' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchColumn(),
    'materias' => countTable($pdo, 'materias'),
    'grupos' => countTable($pdo, 'grupos'),
    'calificaciones' => countTable($pdo, 'calificaciones'),
    'alumnos_con_calificacion' => (int)$pdo->query("SELECT COUNT(DISTINCT alumno_id) FROM calificaciones")->fetchColumn(),
    'profesores_con_grupo' => (int)$pdo->query("SELECT COUNT(DISTINCT u.id) FROM usuarios u JOIN grupos g ON g.profesor_id = u.id WHERE u.rol = 'profesor' AND u.activo = 1")->fetchColumn(),
];

$alumnosSinCal = $pdo->query("SELECT a.matricula, a.nombre, a.apellido FROM alumnos a LEFT JOIN calificaciones c ON c.alumno_id = a.id WHERE c.id IS NULL LIMIT 10")->fetchAll();
$profesSinGrupo = $pdo->query("SELECT u.matricula, u.email FROM usuarios u LEFT JOIN grupos g ON g.profesor_id = u.id WHERE u.rol = 'profesor' AND u.activo = 1 AND g.id IS NULL LIMIT 10")->fetchAll();

echo "=== VERIFICACIÓN DE SEED ===\n\n";
echo "Alumnos: " . (int)$counts['alumnos'] . "\n";
echo "Profesores activos: " . (int)$counts['profesores'] . "\n";
echo "Materias: " . (int)$counts['materias'] . "\n";
echo "Grupos: " . (int)$counts['grupos'] . "\n";
echo "Calificaciones: " . (int)$counts['calificaciones'] . "\n";
echo "Alumnos con calificación: " . (int)$counts['alumnos_con_calificacion'] . "\n";
echo "Profesores con grupo: " . (int)$counts['profesores_con_grupo'] . "\n\n";

echo "Alumnos sin calificaciones (muestra):\n";
if (!$alumnosSinCal) {
    echo "  Todos los alumnos tienen al menos una calificación.\n";
} else {
    foreach ($alumnosSinCal as $a) {
        echo "  " . ($a['matricula'] ?? '') . " - " . ($a['nombre'] ?? '') . " " . ($a['apellido'] ?? '') . "\n";
    }
}

echo "\nProfesores sin grupo (muestra):\n";
if (!$profesSinGrupo) {
    echo "  Todos los profesores activos tienen al menos un grupo.\n";
} else {
    foreach ($profesSinGrupo as $p) {
        echo "  " . ($p['matricula'] ?? '') . " - " . ($p['email'] ?? '') . "\n";
    }
}

