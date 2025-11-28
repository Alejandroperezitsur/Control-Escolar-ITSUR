<?php
require_once __DIR__ . '/../config/db.php';
$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->query("SHOW CREATE TABLE calificaciones");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
