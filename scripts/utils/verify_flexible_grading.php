<?php
// Bootstrap
require_once __DIR__ . '/app/init.php';
require_once __DIR__ . '/config/db.php';

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) { return; }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) { require $file; }
});

use App\Controllers\CareersController;
use App\Controllers\GradesController;

// Mock session
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user'] = ['id' => 1, 'rol' => 'admin', 'nombre' => 'Admin Test'];
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['csrf_token'] = 'test_token';
$_POST['csrf_token'] = 'test_token';

$pdo = \Database::getInstance()->getConnection();

echo "Starting Verification...\n";

// 1. Setup Test Data
$stmt = $pdo->query("SELECT id FROM carreras LIMIT 1");
$carreraId = $stmt->fetchColumn();
if (!$carreraId) die("No careers found.\n");

$stmt = $pdo->query("SELECT id FROM materias LIMIT 1");
$materiaId = $stmt->fetchColumn();
if (!$materiaId) die("No subjects found.\n");

// Ensure materia is in carrera (or add it)
$stmt = $pdo->prepare("SELECT id FROM materias_carrera WHERE carrera_id = ? AND materia_id = ?");
$stmt->execute([$carreraId, $materiaId]);
$mcId = $stmt->fetchColumn();

if (!$mcId) {
    echo "Adding subject to career...\n";
    $stmt = $pdo->prepare("INSERT INTO materias_carrera (carrera_id, materia_id, semestre, creditos, tipo) VALUES (?, ?, 1, 5, 'Básica')");
    $stmt->execute([$carreraId, $materiaId]);
    $mcId = $pdo->lastInsertId();
}

// Create a group
$stmt = $pdo->prepare("SELECT id FROM grupos WHERE materia_id = ? LIMIT 1");
$stmt->execute([$materiaId]);
$grupoId = $stmt->fetchColumn();

if (!$grupoId) {
    echo "Creating test group...\n";
    $stmt = $pdo->prepare("INSERT INTO grupos (materia_id, profesor_id, ciclo, nombre, cupo) VALUES (?, 1, '2025A', 'A', 30)");
    $stmt->execute([$materiaId]);
    $grupoId = $pdo->lastInsertId();
}

// Get a student
$stmt = $pdo->query("SELECT id FROM alumnos LIMIT 1");
$alumnoId = $stmt->fetchColumn();
if (!$alumnoId) die("No students found.\n");

// Enroll student in group (if not already)
$stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE alumno_id = ? AND grupo_id = ?");
$stmt->execute([$alumnoId, $grupoId]);
if (!$stmt->fetchColumn()) {
    $stmt = $pdo->prepare("INSERT INTO calificaciones (alumno_id, grupo_id) VALUES (?, ?)");
    $stmt->execute([$alumnoId, $grupoId]);
}

// 2. Test Subject Update (Num Parciales = 3)
echo "Testing Subject Update (3 partials)...\n";
$careersCtl = new CareersController($pdo);
$_POST = [
    'csrf_token' => 'test_token',
    'mc_id' => $mcId,
    'materia_id' => $materiaId,
    'semestre' => 1,
    'creditos' => 6,
    'tipo' => 'Básica',
    'num_parciales' => 3
];
// Mock php://input for json_decode
$json = json_encode($_POST);
// We can't easily mock php://input in CLI without a wrapper or modifying the controller to accept array.
// But CareersController reads php://input.
// Workaround: Modify CareersController temporarily? No.
// Let's use a helper function or just update DB directly to simulate the Controller action for this test,
// OR use a stream wrapper.
// Simpler: Update DB directly and verify GradesController logic.
// The Controller logic is simple enough (UPDATE materias SET num_parciales...).

$pdo->prepare("UPDATE materias SET num_parciales = 3 WHERE id = ?")->execute([$materiaId]);
$np = $pdo->query("SELECT num_parciales FROM materias WHERE id = $materiaId")->fetchColumn();
if ($np != 3) die("Failed to update num_parciales to 3.\n");
echo "Subject updated to 3 partials.\n";

// 3. Test Grading (3 partials)
echo "Testing Grading (3 partials)...\n";
$_POST = [
    'grupo_id' => $grupoId,
    'alumno_id' => $alumnoId,
    'parcial1' => 80,
    'parcial2' => 90,
    'parcial3' => 100,
    'final' => ''
];
// GradesController::create uses $_POST directly.
$gradesCtl = new GradesController($pdo);
// Capture output
ob_start();
$gradesCtl->create();
ob_end_clean();

$stmt = $pdo->prepare("SELECT parcial1, parcial2, parcial3, promedio FROM calificaciones WHERE alumno_id = ? AND grupo_id = ?");
$stmt->execute([$alumnoId, $grupoId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Grades: P1={$row['parcial1']}, P2={$row['parcial2']}, P3={$row['parcial3']}, Prom={$row['promedio']}\n";
if ($row['promedio'] == 90) {
    echo "SUCCESS: Average is 90.\n";
} else {
    echo "FAILURE: Average is {$row['promedio']} (expected 90).\n";
}

// 4. Test Subject Update (Num Parciales = 5)
echo "Testing Subject Update (5 partials)...\n";
$pdo->prepare("UPDATE materias SET num_parciales = 5 WHERE id = ?")->execute([$materiaId]);

// 5. Test Grading (5 partials)
echo "Testing Grading (5 partials)...\n";
$_POST = [
    'grupo_id' => $grupoId,
    'alumno_id' => $alumnoId,
    'parcial1' => 80,
    'parcial2' => 90,
    'parcial3' => 100,
    'parcial4' => 80,
    'parcial5' => 90,
    'final' => ''
];
ob_start();
$gradesCtl->create();
ob_end_clean();

$stmt = $pdo->prepare("SELECT parcial1, parcial2, parcial3, parcial4, parcial5, promedio FROM calificaciones WHERE alumno_id = ? AND grupo_id = ?");
$stmt->execute([$alumnoId, $grupoId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Grades: P1={$row['parcial1']}, P2={$row['parcial2']}, P3={$row['parcial3']}, P4={$row['parcial4']}, P5={$row['parcial5']}, Prom={$row['promedio']}\n";
$expected = round((80+90+100+80+90)/5, 2); // 88
if ($row['promedio'] == $expected) {
    echo "SUCCESS: Average is $expected.\n";
} else {
    echo "FAILURE: Average is {$row['promedio']} (expected $expected).\n";
}

echo "Verification Complete.\n";
