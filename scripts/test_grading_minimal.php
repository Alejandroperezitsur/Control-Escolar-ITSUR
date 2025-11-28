<?php
require_once __DIR__ . '/../config/db.php';

session_start();
$_SESSION['role'] = 'profesor';
$_SESSION['user_id'] = 2;
$_SESSION['csrf_token'] = 'test';
$_POST['csrf_token'] = 'test';
$_POST['alumno_id'] = 1;
$_POST['grupo_id'] = 797;
$_POST['parcial1'] = 85;
$_POST['parcial2'] = 90;
$_POST['final'] = 88;

try {
    $pdo = Database::getInstance()->getConnection();
    require_once __DIR__ . '/../src/Controllers/GradesController.php';
    require_once __DIR__ . '/../src/Utils/Logger.php';
    
    $ctl = new \App\Controllers\GradesController($pdo);
    echo "Created controller\n";
    
    $result = $ctl->create();
    echo "Result: $result\n";
    
    echo "Flash: " . ($_SESSION['flash'] ?? 'none') . "\n";
    
    // Check DB
    $stmt = $pdo->prepare("SELECT parcial1, final FROM calificaciones WHERE alumno_id = 1 AND grupo_id = 797");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "DB Check: " . json_encode($row) . "\n";
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
