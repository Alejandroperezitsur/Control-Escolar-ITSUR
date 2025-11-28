<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/StudentsController.php';
require_once __DIR__ . '/../src/Services/StudentsService.php';

// Mock Session
session_start();
$_SESSION['role'] = 'alumno';
$_SESSION['user_id'] = 1;
$_SESSION['csrf_token'] = 'test_token';
$_POST['csrf_token'] = 'test_token';

use App\Controllers\StudentsController;

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Verify user 1
    $stmt = $pdo->query("SELECT * FROM alumnos WHERE id = 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        $stmt = $pdo->query("SELECT * FROM alumnos LIMIT 1");
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $student['id'];
    }
    echo "STARTING TEST with Student ID: " . $_SESSION['user_id'] . "\n";

    $ctl = new StudentsController($pdo);

    echo "1. Testing myReinscripcion()...\n";
    try {
        $html = $ctl->myReinscripcion();
        if (strpos($html, 'Reinscripción') !== false) {
            echo "SUCCESS: myReinscripcion view loaded.\n";
        } else {
            echo "FAILURE: myReinscripcion view content missing.\n";
        }
    } catch (Exception $e) {
        echo "ERROR in myReinscripcion: " . $e->getMessage() . "\n";
    }

    echo "2. Testing Enrollment...\n";
    $stmt = $pdo->query("SELECT id FROM grupos LIMIT 1");
    $groupId = $stmt->fetchColumn();
    
    if ($groupId) {
        echo "Attempting to enroll in group $groupId...\n";
        $_POST['grupo_id'] = $groupId;
        
        ob_start();
        $ctl->selfEnroll();
        $json = ob_get_clean();
        echo "Enroll response: $json\n";
        
        echo "3. Testing Unenrollment...\n";
        ob_start();
        $ctl->selfUnenroll();
        $json = ob_get_clean();
        echo "Unenroll response: $json\n";
    } else {
        echo "No groups found.\n";
    }
    
    echo "ENDING TEST.\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
