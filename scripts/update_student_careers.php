<?php
$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$pdo = null;

// Try config credentials
try {
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected using config credentials.\n";
} catch (PDOException $e) {
    echo "Config connection failed: " . $e->getMessage() . "\n";
    echo "Trying local defaults (localhost, root, '', control_escolar)...\n";
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=control_escolar;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected using local defaults.\n";
    } catch (PDOException $e2) {
        echo "Failed root/empty: " . $e2->getMessage() . "\n";
        echo "Trying local defaults (localhost, root, 'root', control_escolar)...\n";
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=control_escolar;charset=utf8mb4", "root", "root");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Connected using local defaults (root/root).\n";
        } catch (PDOException $e3) {
            die("All connection attempts failed. Last error: " . $e3->getMessage() . "\n");
        }
    }
}

// Check if carrera_id column exists
echo "Checking for carrera_id column...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM alumnos LIKE 'carrera_id'");
if (!$stmt->fetch()) {
    echo "Column carrera_id missing. Adding it...\n";
    try {
        $pdo->exec("ALTER TABLE alumnos ADD COLUMN carrera_id INT DEFAULT NULL AFTER activo");
        $pdo->exec("ALTER TABLE alumnos ADD CONSTRAINT fk_alumnos_carrera FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE SET NULL");
        echo "Column carrera_id added successfully.\n";
    } catch (PDOException $e) {
        die("Failed to add column: " . $e->getMessage() . "\n");
    }
} else {
    echo "Column carrera_id already exists.\n";
}

// Fetch all students
$stmt = $pdo->query("SELECT id, matricula FROM alumnos");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($students) . " students.\n";

$updated = 0;
$skipped = 0;

// Career mapping
// S -> ISC
// I -> II
// A -> IGE
// E -> IE
// M -> IM
// Q -> IER
// C -> CP
$mapping = [
    'S' => 'ISC',
    'I' => 'II',
    'A' => 'IGE',
    'E' => 'IE',
    'M' => 'IM',
    'Q' => 'IER',
    'C' => 'CP'
];

// Fetch career IDs
$careers = [];
$cStmt = $pdo->query("SELECT id, clave FROM carreras");
while ($row = $cStmt->fetch(PDO::FETCH_ASSOC)) {
    $careers[$row['clave']] = $row['id'];
}

foreach ($students as $s) {
    $prefix = strtoupper(substr($s['matricula'], 0, 1));
    $clave = $mapping[$prefix] ?? null;
    
    if ($clave && isset($careers[$clave])) {
        $cid = $careers[$clave];
        $upd = $pdo->prepare("UPDATE alumnos SET carrera_id = :c WHERE id = :id");
        $upd->execute([':c' => $cid, ':id' => $s['id']]);
        $updated++;
    } else {
        $skipped++;
        echo "Skipping student {$s['matricula']} (No mapping for prefix '$prefix')\n";
    }
}

echo "Done. Updated: $updated, Skipped: $skipped.\n";
