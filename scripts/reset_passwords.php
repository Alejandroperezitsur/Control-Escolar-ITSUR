<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    $hash = password_hash('123456', PASSWORD_DEFAULT);
    
    echo "Resetting passwords to '123456'...\n";
    
    $stmt = $pdo->prepare("UPDATE usuarios SET password = :p");
    $stmt->execute([':p' => $hash]);
    echo "Updated " . $stmt->rowCount() . " users.\n";
    
    $stmt = $pdo->prepare("UPDATE alumnos SET password = :p");
    $stmt->execute([':p' => $hash]);
    echo "Updated " . $stmt->rowCount() . " students.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
