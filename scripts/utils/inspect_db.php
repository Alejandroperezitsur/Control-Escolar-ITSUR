<?php
require_once __DIR__ . '/config/db.php';
$pdo = Database::getInstance()->getConnection();

echo "Carreras:\n";
$stmt = $pdo->query("SELECT id, clave, nombre FROM careers"); // Wait, table is 'carreras' based on previous context
// Let me check the previous context. KpiController used 'carreras'.
$stmt = $pdo->query("SELECT id, clave, nombre FROM carreras");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']}: {$row['clave']} - {$row['nombre']}\n";
}

echo "\nMaterias (first 10):\n";
$stmt = $pdo->query("SELECT id, clave, nombre FROM materias LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']}: {$row['clave']} - {$row['nombre']}\n";
}
