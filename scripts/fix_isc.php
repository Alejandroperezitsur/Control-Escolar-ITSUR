<?php

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$config = require __DIR__ . '/../config/config.php';
$env = (string)($config['app']['env'] ?? getenv('APP_ENV') ?: 'local');
if ($env === 'production') {
    fwrite(STDERR, "Fix ISC deshabilitado en producción\n");
    exit(1);
}

$pdo = Database::getInstance()->getConnection();

echo "=== DIAGNÓSTICO Y REPARACIÓN DE ISC (CLI MODE) ===\n\n";
echo "1. Buscando carrera ISC...\n";
$stmt = $pdo->prepare("SELECT * FROM carreras WHERE clave = ?");
$stmt->execute(['ISC']);
$isc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$isc) {
    echo "ERROR: No se encontró la carrera ISC. Intentando crearla...\n";
    $pdo->exec("INSERT INTO carreras (nombre, clave, descripcion, duracion_semestres, creditos_totales) VALUES 
        ('Ingeniería en Sistemas Computacionales', 'ISC', 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales.', 9, 240)");
    $iscId = $pdo->lastInsertId();
    echo "Carrera creada con ID: $iscId\n";
} else {
    $iscId = $isc['id'];
    echo "Carrera encontrada: ID $iscId, Nombre: {$isc['nombre']}\n";
}

echo "\n2. Verificando materias clave...\n";
$materiasClave = ['ISC-1001', 'MAT-1001', 'ING-1001'];
foreach ($materiasClave as $clave) {
    $stmt = $pdo->prepare("SELECT id, nombre FROM materias WHERE clave = ?");
    $stmt->execute([$clave]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($m) {
        echo "  [OK] $clave: ID {$m['id']} - {$m['nombre']}\n";
    } else {
        echo "  [FALTA] $clave no existe. Creando...\n";
        $pdo->prepare("INSERT INTO materias (nombre, clave) VALUES (?, ?)")->execute(["Materia $clave", $clave]);
        echo "  -> Creada.\n";
    }
}

echo "\n3. EJECUTANDO REPARACIÓN FORZADA...\n";
echo "  -> Limpiando registros actuales de ISC...\n";
$pdo->prepare("DELETE FROM materias_carrera WHERE carrera_id = ?")->execute([$iscId]);

$sqlFile = __DIR__ . '/../migrations/force_full_isc_curriculum.sql';
if (!file_exists($sqlFile)) {
    fwrite(STDERR, "ERROR CRÍTICO: No se encuentra el archivo SQL en $sqlFile\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
$sqlProcessed = str_replace('@isc_id', $iscId, $sql);
$sqlProcessed = preg_replace('/SET @isc_id = .*?;/', '', $sqlProcessed);

$statements = explode(';', $sqlProcessed);
$errors = 0;
$success = 0;

foreach ($statements as $query) {
    $query = trim($query);
    if (empty($query)) {
        continue;
    }
    if (strpos($query, 'USE control_escolar') !== false) {
        continue;
    }
    try {
        $pdo->exec($query);
        $success++;
    } catch (PDOException $e) {
        echo "  [ERROR SQL] " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== RESULTADO ===\n";
echo "Queries ejecutados: $success\n";
echo "Errores: $errors\n";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM materias_carrera WHERE carrera_id = ?");
$stmt->execute([$iscId]);
$finalCount = $stmt->fetchColumn();
echo "Total materias asignadas a ISC ahora: $finalCount\n";

if ($finalCount > 0) {
    echo "ÉXITO: La retícula ha sido reparada.\n";
} else {
    echo "FALLO: No se asignaron materias a ISC.\n";
}

