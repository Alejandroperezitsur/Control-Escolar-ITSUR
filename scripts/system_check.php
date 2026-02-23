<?php

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../config/db.php';

$pdo = Database::getInstance()->getConnection();

function checkUniqueAlumnoGrupo(PDO $pdo): bool
{
    $sql = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'calificaciones'
              AND CONSTRAINT_TYPE = 'UNIQUE'
              AND CONSTRAINT_NAME = 'uniq_alumno_grupo'";
    $stmt = $pdo->query($sql);
    return (int)$stmt->fetchColumn() > 0;
}

function checkCheckFinalRange(PDO $pdo): bool
{
    $sql = "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS tc
            JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
            WHERE tc.TABLE_SCHEMA = DATABASE()
              AND tc.TABLE_NAME = 'calificaciones'
              AND tc.CONSTRAINT_TYPE = 'CHECK'
              AND tc.CONSTRAINT_NAME = 'chk_final_range'";
    $stmt = $pdo->query($sql);
    return (int)$stmt->fetchColumn() > 0;
}

function checkFkAlumno(PDO $pdo): bool
{
    $sql = "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME = 'fk_calif_alumno'
              AND TABLE_NAME = 'calificaciones'";
    $stmt = $pdo->query($sql);
    return (int)$stmt->fetchColumn() > 0;
}

function checkFkGrupo(PDO $pdo): bool
{
    $sql = "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME = 'fk_calif_grupo'
              AND TABLE_NAME = 'calificaciones'";
    $stmt = $pdo->query($sql);
    return (int)$stmt->fetchColumn() > 0;
}

function checkSingleActiveCycle(PDO $pdo): bool
{
    $sql = "SELECT COUNT(*) FROM ciclos_escolares WHERE activo = 1";
    $stmt = $pdo->query($sql);
    $count = (int)$stmt->fetchColumn();
    return $count <= 1;
}

function checkNoCycleValidationByName(): bool
{
    $baseDir = realpath(__DIR__ . '/../src');
    if ($baseDir === false) {
        return false;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        if (substr($file->getFilename(), -4) !== '.php') {
            continue;
        }
        $path = $file->getPathname();
        $contents = file_get_contents($path);
        if ($contents === false) {
            continue;
        }
        if (strpos($contents, 'assertActiveCycleForGroup') !== false) {
            continue;
        }
        if (strpos($contents, 'ciclos_escolares') !== false && strpos($contents, 'nombre') !== false && strpos($contents, 'activo = 1') !== false) {
            return false;
        }
    }
    return true;
}

$checks = [
    'UNIQUE alumno_grupo en calificaciones' => checkUniqueAlumnoGrupo($pdo),
    'CHECK rango final en calificaciones' => checkCheckFinalRange($pdo),
    'FK alumno en calificaciones' => checkFkAlumno($pdo),
    'FK grupo en calificaciones' => checkFkGrupo($pdo),
    'Solo un ciclo activo en ciclos_escolares' => checkSingleActiveCycle($pdo),
    'Sin validación de ciclo por nombre en código' => checkNoCycleValidationByName(),
];

$allOk = true;
foreach ($checks as $label => $ok) {
    echo ($ok ? "[OK] " : "[FAIL] ") . $label . PHP_EOL;
    if (!$ok) {
        $allOk = false;
    }
}

if (!$allOk) {
    exit(1);
}

exit(0);

