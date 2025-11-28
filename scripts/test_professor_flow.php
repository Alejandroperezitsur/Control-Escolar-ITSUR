<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Controllers/GroupsController.php';
require_once __DIR__ . '/../src/Controllers/GradesController.php';
require_once __DIR__ . '/../src/Services/GroupsService.php';

// Mock Session
session_start();
$_SESSION['role'] = 'profesor';
$_SESSION['user_id'] = 0; // Will find one
$_SESSION['csrf_token'] = 'test_token';
$_POST['csrf_token'] = 'test_token';

use App\Controllers\GroupsController;
use App\Controllers\GradesController;

function logMsg($msg) {
    file_put_contents('test_professor_log.txt', $msg, FILE_APPEND);
    echo $msg;
}
file_put_contents('test_professor_log.txt', "STARTING LOG\n");

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Find a professor
    $stmt = $pdo->query("SELECT id FROM usuarios WHERE rol = 'profesor' LIMIT 1");
    $profId = $stmt->fetchColumn();
    if (!$profId) {
        throw new Exception("No professor found in DB.");
    }
    $_SESSION['user_id'] = $profId;
    logMsg("Using Professor ID: $profId\n");

    $groupsCtl = new GroupsController($pdo);
    $gradesCtl = new GradesController($pdo);

    logMsg("1. Testing Groups List (mine)...\n");
    $html = $groupsCtl->mine();
    if (strpos($html, 'Mis Grupos') !== false) {
        logMsg("SUCCESS: Groups view loaded.\n");
    } else {
        logMsg("FAILURE: Groups view content missing.\n");
    }

    logMsg("2. Testing Grading...\n");
    // Find a group for this professor
    $stmt = $pdo->prepare("SELECT id FROM grupos WHERE profesor_id = :p LIMIT 1");
    $stmt->execute([':p' => $profId]);
    $groupId = $stmt->fetchColumn();
    
    if ($groupId) {
        logMsg("Found Group ID: $groupId\n");
        
        // Find a student in this group (or enroll one if needed)
        $stmt = $pdo->prepare("SELECT alumno_id FROM calificaciones WHERE grupo_id = :g LIMIT 1");
        $stmt->execute([':g' => $groupId]);
        $studentId = $stmt->fetchColumn();
        
        if (!$studentId) {
            logMsg("No student in group. Enrolling one for test...\n");
            $stmt = $pdo->query("SELECT id FROM alumnos LIMIT 1");
            $studentId = $stmt->fetchColumn();
            if ($studentId) {
                $pdo->prepare("INSERT INTO calificaciones (alumno_id, grupo_id) VALUES (?, ?)")->execute([$studentId, $groupId]);
            }
        }
        
        if ($studentId) {
            logMsg("Grading Student ID: $studentId in Group ID: $groupId\n");
            $_POST['alumno_id'] = $studentId;
            $_POST['grupo_id'] = $groupId;
            $_POST['parcial1'] = 85;
            $_POST['parcial2'] = 90;
            $_POST['final'] = 88;
            
            logMsg("Calling create()...\n");
            try {
                $result = $gradesCtl->create();
                logMsg("Create returned: " . substr($result, 0, 100) . "\n");
            } catch (Exception $e) {
                logMsg("Exception in create: " . $e->getMessage() . "\n");
            }
            
            logMsg("Session Flash: " . ($_SESSION['flash'] ?? 'None') . "\n");
            logMsg("Session Flash Type: " . ($_SESSION['flash_type'] ?? 'None') . "\n");
            
            // Verify in DB
            $stmt = $pdo->prepare("SELECT parcial1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g");
            $stmt->execute([':a' => $studentId, ':g' => $groupId]);
            $p1 = $stmt->fetchColumn();
            
            if ($p1 == 85) {
                logMsg("SUCCESS: Grade recorded (85).\n");
            } else {
                logMsg("FAILURE: Grade not recorded. Got: " . var_export($p1, true) . "\n");
            }
            
        } else {
            logMsg("No student found to grade.\n");
        }
        
    } else {
        logMsg("No groups found for this professor.\n");
    }
    
    logMsg("ENDING TEST.\n");

} catch (Exception $e) {
    logMsg("FATAL ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
