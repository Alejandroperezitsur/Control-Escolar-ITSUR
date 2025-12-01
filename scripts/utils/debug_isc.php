<?php
require_once __DIR__ . '/config/db.php';
$pdo = Database::getInstance()->getConnection();

echo "=== CARRERAS ===\n";
$stmt = $pdo->query("SELECT * FROM carreras");
$carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($carreras as $c) {
    echo "ID: {$c['id']}, Clave: {$c['clave']}, Nombre: {$c['nombre']}\n";
}

echo "\n=== MATERIAS (ISC Sample) ===\n";
$isc_codes = ['ISC-1001', 'MAT-1001', 'MAT-1004', 'QUI-1001', 'INV-1001', 'ING-1001'];
$placeholders = implode(',', array_fill(0, count($isc_codes), '?'));
$stmt = $pdo->prepare("SELECT * FROM materias WHERE clave IN ($placeholders)");
$stmt->execute($isc_codes);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($materias as $m) {
    echo "ID: {$m['id']}, Clave: {$m['clave']}, Nombre: {$m['nombre']}\n";
}

echo "\n=== MATERIAS_CARRERA (Count for ISC) ===\n";
// Find ISC ID
$iscId = null;
foreach ($carreras as $c) {
    if ($c['clave'] === 'ISC' || $c['clave'] === 'IC') {
        $iscId = $c['id'];
        break;
    }
}

if ($iscId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM materias_carrera WHERE carrera_id = ?");
    $stmt->execute([$iscId]);
    $count = $stmt->fetchColumn();
    echo "Total curriculum items for ISC (ID $iscId): $count\n";
    
    if ($count > 0) {
        echo "First 5 items:\n";
        $stmt = $pdo->prepare("SELECT mc.*, m.nombre FROM materias_carrera mc JOIN materias m ON mc.materia_id = m.id WHERE mc.carrera_id = ? LIMIT 5");
        $stmt->execute([$iscId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Sem: {$row['semestre']}, Mat: {$row['nombre']} ({$row['tipo']})\n";
        }
    }
} else {
    echo "ISC Career not found in DB.\n";
}
