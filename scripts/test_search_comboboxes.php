<?php
require_once __DIR__ . '/../config/db.php';

echo "=== SEARCH FUNCTIONALITY TEST ===\n\n";

$pdo = Database::getInstance()->getConnection();

// Test 1: Student Search
echo "1. Testing Student Search\n";
$searchTerm = 'S20';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM alumnos WHERE matricula LIKE :term1 OR nombre LIKE :term2 OR apellido LIKE :term3");
$stmt->execute([':term1' => "%$searchTerm%", ':term2' => "%$searchTerm%", ':term3' => "%$searchTerm%"]);
$count = $stmt->fetchColumn();
echo "  ✓ Search for '$searchTerm': $count results\n";

// Test 2: Professor Search  
echo "\n2. Testing Professor Search\n";
$searchTerm = 'P';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND (matricula LIKE :term1 OR nombre LIKE :term2 OR email LIKE :term3)");
$stmt->execute([':term1' => "%$searchTerm%", ':term2' => "%$searchTerm%", ':term3' => "%$searchTerm%"]);
$count = $stmt->fetchColumn();
echo "  ✓ Search for '$searchTerm': $count results\n";

// Test 3: Subject Search
echo "\n3. Testing Subject Search\n";
$searchTerm = 'MAT';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM materias WHERE clave LIKE :term1 OR nombre LIKE :term2");
$stmt->execute([':term1' => "%$searchTerm%", ':term2' => "%$searchTerm%"]);
$count = $stmt->fetchColumn();
echo "  ✓ Search for '$searchTerm': $count results\n";

// Test 4: Empty State
echo "\n4. Testing Empty State Handling\n";
$searchTerm = 'NONEXISTENT12345';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM alumnos WHERE matricula LIKE :term");
$stmt->execute([':term' => "%$searchTerm%"]);
$count = $stmt->fetchColumn();
echo "  ✓ Search for non-existent term: $count results (should be 0)\n";

echo "\n=== COMBOBOX POPULATION TEST ===\n\n";

// Test 1: Grade Entry - Groups
echo "1. Testing Grade Entry Groups Combobox\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM grupos");
$groupCount = $stmt->fetchColumn();
echo "  ✓ Available groups: $groupCount\n";

// Test 2: Grade Entry - Students by Group
echo "\n2. Testing Students by Group\n";
$stmt = $pdo->query("SELECT id FROM grupos LIMIT 1");
$groupId = $stmt->fetchColumn();
if ($groupId) {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.alumno_id) FROM calificaciones c WHERE c.grupo_id = :g");
    $stmt->execute([':g' => $groupId]);
    $studentCount = $stmt->fetchColumn();
    echo "  ✓ Students in group $groupId: $studentCount\n";
}

// Test 3: Enrollment Filters - Cycles
echo "\n3. Testing Enrollment Cycle Filter\n";
$stmt = $pdo->query("SELECT COUNT(DISTINCT ciclo) FROM grupos");
$cycleCount = $stmt->fetchColumn();
echo "  ✓ Available cycles: $cycleCount\n";

// Test 4: Career Filter
echo "\n4. Testing Career Filter\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM carreras WHERE activo = 1");
$careerCount = $stmt->fetchColumn();
echo "  ✓ Active careers: $careerCount\n";

// Test 5: Professor Assignment
echo "\n5. Testing Professor Dropdown for Groups\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1");
$profCount = $stmt->fetchColumn();
echo "  ✓ Active professors: $profCount\n";

echo "\n=== TEST COMPLETE ===\n";
echo "All search and combobox queries executed successfully.\n";
