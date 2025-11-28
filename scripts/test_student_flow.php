<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/StudentsController.php';
require_once __DIR__ . '/../src/Services/StudentsService.php';

// Mock Session
session_start();
$_SESSION['role'] = 'alumno';
$_SESSION['user_id'] = 1; // Assuming ID 1 is a student (S12345678 from migration)
$_SESSION['csrf_token'] = 'test_token';
$_POST['csrf_token'] = 'test_token';

use App\Controllers\StudentsController;

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Verify user 1 is a student
    $stmt = $pdo->query("SELECT * FROM alumnos WHERE id = 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        // Find a student
        $stmt = $pdo->query("SELECT * FROM alumnos LIMIT 1");
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['user_id'] = $student['id'];
        echo "Using student ID: " . $student['id'] . "\n";
    } else {
        echo "Using student ID: 1\n";
    }

    $ctl = new StudentsController($pdo);

    // Test Reinscripcion View (just to see if it runs)
    // We can't easily capture output of view inclusion without output buffering, 
    // but the controller does ob_start/ob_get_clean.
    echo "Testing myReinscripcion()...\n";
    $html = $ctl->myReinscripcion();
    if (strpos($html, 'Reinscripción') !== false) {
        echo "SUCCESS: myReinscripcion view loaded.\n";
    } else {
        echo "FAILURE: myReinscripcion view content missing.\n";
    }

    // Test Enrollment
    // First find a group to enroll
    $stmt = $pdo->query("SELECT id FROM grupos LIMIT 1");
    $groupId = $stmt->fetchColumn();
    
    if ($groupId) {
        echo "Attempting to enroll in group $groupId...\n";
        $_POST['grupo_id'] = $groupId;
        
        // Capture JSON output
        ob_start();
        $ctl->selfEnroll();
        $json = ob_get_clean();
        echo "Enroll response: $json\n";
        
        // Test Unenroll
        echo "Attempting to unenroll from group $groupId...\n";
        ob_start();
        $ctl->selfUnenroll();
        $json = ob_get_clean();
        echo "Unenroll response: $json\n";
    } else {
        echo "No groups found to test enrollment.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
