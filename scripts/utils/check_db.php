<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "--- CARRERAS ---\n";
    $stmt = $pdo->query("SELECT id, nombre, clave, activo FROM carreras");
    $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($carreras);
    
    echo "\n--- MATERIAS CARRERA COUNT ---\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM materias_carrera");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
