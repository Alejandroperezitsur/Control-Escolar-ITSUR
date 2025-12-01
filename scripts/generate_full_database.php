<?php
// Script to generate a single massive SQL file for the entire system
// Usage: php scripts/generate_full_database.php > database_full.sql

ini_set('memory_limit', '1G');

$OUTPUT_FILE = __DIR__ . '/../database_full.sql';
$handle = fopen($OUTPUT_FILE, 'w');

function writeLine($line) {
    global $handle;
    fwrite($handle, $line . "\n");
}

function loadFile($path) {
    return file_get_contents(__DIR__ . '/../migrations/' . $path);
}

// 1. Header & Schema
writeLine("-- ============================================================================");
writeLine("-- SISTEMA DE CONTROL ESCOLAR - BASE DE DATOS COMPLETA Y POBLADA");
writeLine("-- Generado automáticamente: " . date('Y-m-d H:i:s'));
writeLine("-- ============================================================================");
writeLine("");
writeLine("SET FOREIGN_KEY_CHECKS = 0;");
writeLine("SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";");
writeLine("SET time_zone = \"+00:00\";");
writeLine("");

// Read Schema
$schema = loadFile('complete_real_system_schema.sql');
// Remove the last few lines that are just SELECTs
$schema = preg_replace('/SELECT \'Schema created.*/s', '', $schema);
writeLine($schema);

// 2. Catalogs (Subjects & Curriculum)
writeLine("-- ============================================================================");
writeLine("-- CATALOGOS (MATERIAS Y RETICULAS)");
writeLine("-- ============================================================================");
writeLine(loadFile('seed_subjects_data.sql'));
writeLine("");
writeLine(loadFile('seed_curriculum_part1.sql'));
writeLine("");
writeLine(loadFile('seed_curriculum_part2.sql'));
writeLine("");

// 3. Dynamic Data Generation
writeLine("-- ============================================================================");
writeLine("-- GENERACION DE DATOS MASIVOS (ALUMNOS, PROFESORES, GRUPOS, CALIFICACIONES)");
writeLine("-- ============================================================================");

// Configuration
$TOTAL_STUDENTS = 3200;
$TOTAL_PROFESSORS = 100;
$CYCLES = ['2024-A', '2024-B']; // A = Jan-Jun, B = Aug-Dec
$CURRENT_CYCLE = '2024-B';

// Helper Data
$firstNames = ['Juan', 'Maria', 'Pedro', 'Ana', 'Luis', 'Sofia', 'Carlos', 'Lucia', 'Jose', 'Elena', 'Miguel', 'Patricia', 'David', 'Carmen', 'Francisco', 'Isabel', 'Manuel', 'Margarita', 'Javier', 'Veronica', 'Alejandro', 'Teresa', 'Daniel', 'Rosa', 'Jorge', 'Silvia', 'Ricardo', 'Andrea', 'Roberto', 'Adriana'];
$lastNames = ['Garcia', 'Gonzalez', 'Rodriguez', 'Fernandez', 'Lopez', 'Martinez', 'Sanchez', 'Perez', 'Gomez', 'Martin', 'Jimenez', 'Ruiz', 'Hernandez', 'Diaz', 'Moreno', 'Muñoz', 'Alvarez', 'Romero', 'Alonso', 'Gutierrez', 'Navarro', 'Torres', 'Dominguez', 'Vazquez', 'Ramos', 'Gil', 'Ramirez', 'Serrano', 'Blanco', 'Molina'];

function getRandomName() {
    global $firstNames, $lastNames;
    return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
}

function getRandomEmail($name, $domain = 'itsur.edu.mx') {
    $parts = explode(' ', strtolower($name));
    return $parts[0] . '.' . $parts[1] . rand(10, 99) . '@' . $domain;
}

// A. Professors
writeLine("-- PROFESORES");
$professors = [];
$batch = [];
for ($i = 1; $i <= $TOTAL_PROFESSORS; $i++) {
    $name = getRandomName();
    $email = getRandomEmail($name);
    $password = '$2y$10$iy.ePorFR/2j6ZvmJEFy1uMniVFux3/bIOlFsw.IrggPjURr8eCOG'; // admin123
    $matricula = 'P' . str_pad($i, 4, '0', STR_PAD_LEFT);
    
    $batch[] = "('$matricula', '$name', '$email', '$password', 'profesor', 1)";
    $professors[] = $i; // ID will be $i + 1 (admin is 1)
    
    if (count($batch) >= 50) {
        writeLine("INSERT INTO usuarios (matricula, nombre, email, password, rol, activo) VALUES " . implode(',', $batch) . ";");
        $batch = [];
    }
}
if (!empty($batch)) {
    writeLine("INSERT INTO usuarios (matricula, nombre, email, password, rol, activo) VALUES " . implode(',', $batch) . ";");
}

// B. Groups (We need to know subject IDs first)
// Since we are running this offline, we can't query the DB. We have to assume IDs based on insertion order or use subqueries.
// Using subqueries for massive inserts is slow.
// Better approach: We know the subjects are inserted in a specific order in seed_subjects_data.sql.
// However, mapping names to IDs is tedious.
// Alternative: Use a stored procedure for the massive part?
// No, let's use a clever trick. We will fetch the subject IDs by reading the seed_subjects_data.sql file again and building a map in PHP.

$subjectMap = []; // clave => id (simulated)
$subjectLines = explode("\n", loadFile('seed_subjects_data.sql'));
$idCounter = 1;
foreach ($subjectLines as $line) {
    if (preg_match("/\('(.+?)', '(.+?)'\)/", $line, $matches)) {
        $subjectMap[$matches[2]] = $idCounter++;
    }
}

// Map Career Claves to IDs
$careerMap = [
    'ISC' => 1, 'II' => 2, 'IGE' => 3, 'IE' => 4, 'IM' => 5, 'IER' => 6, 'CP' => 7
];

// Build Curriculum Map (Career ID -> Semester -> [Subject IDs])
// We need to parse the curriculum files. This is getting complicated to parse SQL.
// Let's simplify. We will assume a standard distribution of subjects.
// Actually, we can just generate groups for ALL subjects in the subjectMap.
// For each subject, we create 2 groups for the current cycle.

writeLine("-- GRUPOS");
$groupIdCounter = 1;
$groupBatch = [];
$groupsBySubject = []; // subject_id => [group_id1, group_id2]

foreach ($subjectMap as $clave => $subId) {
    // Create 2 groups per subject
    for ($g = 1; $g <= 2; $g++) {
        $profId = $professors[array_rand($professors)] + 1; // +1 because admin is 1
        $letter = ($g == 1) ? 'A' : 'B';
        $name = "Grupo " . $letter;
        $cupo = 40;
        
        $groupBatch[] = "($subId, $profId, '$name', '$CURRENT_CYCLE', $cupo)";
        $groupsBySubject[$subId][] = $groupIdCounter++;
        
        if (count($groupBatch) >= 50) {
            writeLine("INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo, cupo) VALUES " . implode(',', $groupBatch) . ";");
            $groupBatch = [];
        }
    }
}
if (!empty($groupBatch)) {
    writeLine("INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo, cupo) VALUES " . implode(',', $groupBatch) . ";");
}

// C. Students & Enrollments
writeLine("-- ALUMNOS E INSCRIPCIONES");
$studentBatch = [];
$enrollmentBatch = [];
$gradesUnitBatch = [];
$gradesFinalBatch = [];

$inscripcionIdCounter = 1;

// We need to know which subjects belong to which semester/career to assign correctly.
// Parsing the SQL is hard.
// Let's use a simplified approach:
// We will assign each student a random set of 5 subjects from the $subjectMap.
// This is not perfect "Curriculum" alignment but it fills the DB with realistic volume.
// WAIT, the user wants "Métricas y números realistas". Random subjects is bad.
// I MUST parse the curriculum.
// The curriculum files use: ((SELECT id FROM materias WHERE clave = 'ISC-1001'), @isc_id, 1, ...
// I can regex this.

$curriculum = []; // career_id => [ semester => [subject_clave1, ...] ]

$currFiles = ['seed_curriculum_part1.sql', 'seed_curriculum_part2.sql'];
foreach ($currFiles as $f) {
    $content = loadFile($f);
    // Split by career sections if possible, or just line by line
    // We need to track the current career ID variable being used.
    // SET @isc_id = ...
    // This is too complex to parse perfectly.
    // Let's manually map the prefixes.
    // ISC-*, MAT-*, etc.
    // Most subjects have the career prefix.
    // Basic sciences (MAT, FIS, QUI) are shared.
}

// Hardcoded mapping of common subjects to semesters (Simplified)
// We will assign subjects based on the student's semester.
// We'll group subjects by their numeric code (1000s = sem 1-2, 2000s = sem 3-4, etc)
// This is a heuristic that works for TecNM codes.
// X-1xxx -> Sem 1-2
// X-2xxx -> Sem 3-4
// X-3xxx -> Sem 5-6
// X-4xxx -> Sem 7-8
// X-9xxx -> Sem 9

$subjectsByLevel = []; // level (1-9) => [clave1, clave2...]
foreach ($subjectMap as $clave => $id) {
    if (preg_match('/-(\d)(\d+)/', $clave, $m)) {
        $level = intval($m[1]);
        if ($level == 9) $level = 9;
        else if ($level >= 1 && $level <= 4) {
             // Map 1->1,2; 2->3,4; 3->5,6; 4->7,8 roughly
             // Actually TecNM codes: 1xxx is usually sem 1-2? No, it's just a code.
             // Let's just randomize.
             // Wait, the user provided the SQL. I can just use the SQL to insert the curriculum,
             // and then for students, I need to know what to enroll them in.
             // I will use a SQL CURSOR in the final file to enroll students based on the `materias_carrera` table!
             // Yes! I don't need to know the curriculum in PHP. I can let MySQL handle the logic.
        }
    }
}

// REVISED PLAN FOR STUDENTS:
// 1. Generate Students in PHP (easy).
// 2. Use a MySQL Procedure to enroll them.
//    The procedure will:
//    - Iterate all students.
//    - Look up their career and semester (I need to store semester in students table? No, it's usually derived, but for seeding I can put it in a temp table or just assume based on enrollment).
//    - Actually, `alumnos` doesn't have a `semestre` column. It's derived from `inscripciones`.
//    - So I will assign a "target semester" in the PHP loop, and then generate enrollments for that semester.
//    - I need to know which subjects are in that semester.
//    - I WILL PARSE `materias_carrera` INSERTs to build a map in PHP. It's worth it.

$curriculumMap = []; // career_clave => [ semester => [subject_clave] ]
$currentCareer = '';

$allCurrLines = explode("\n", loadFile('seed_curriculum_part1.sql') . "\n" . loadFile('seed_curriculum_part2.sql'));
foreach ($allCurrLines as $line) {
    // Detect career switch
    if (strpos($line, 'INGENIERÍA EN SISTEMAS') !== false) $currentCareer = 'ISC';
    elseif (strpos($line, 'INGENIERÍA INDUSTRIAL') !== false) $currentCareer = 'II';
    elseif (strpos($line, 'INGENIERÍA EN GESTIÓN') !== false) $currentCareer = 'IGE';
    elseif (strpos($line, 'INGENIERÍA ELECTRÓNICA') !== false) $currentCareer = 'IE';
    elseif (strpos($line, 'INGENIERÍA MECATRÓNICA') !== false) $currentCareer = 'IM';
    elseif (strpos($line, 'INGENIERÍA EN ENERGÍAS') !== false) $currentCareer = 'IER';
    elseif (strpos($line, 'CONTADOR PÚBLICO') !== false) $currentCareer = 'CP';
    
    // Detect Insert
    // ((SELECT id FROM materias WHERE clave = 'ISC-1001'), @isc_id, 1, ...
    if (preg_match("/clave = '([^']+)'\), @[^,]+, (\d+)/", $line, $m)) {
        if ($currentCareer) {
            $curriculumMap[$currentCareer][$m[2]][] = $m[1];
        }
    }
}

// Generate Students
for ($i = 1; $i <= $TOTAL_STUDENTS; $i++) {
    $name = getRandomName();
    $parts = explode(' ', $name);
    $nombre = $parts[0];
    $apellido = $parts[1] . ' ' . $lastNames[array_rand($lastNames)]; // Two last names
    $email = getRandomEmail($name);
    $password = '$2y$10$iy.ePorFR/2j6ZvmJEFy1uMniVFux3/bIOlFsw.IrggPjURr8eCOG';
    
    // Distribute careers
    $careerKeys = array_keys($careerMap);
    $cKey = $careerKeys[array_rand($careerKeys)];
    $cId = $careerMap[$cKey];
    
    // Distribute semesters (1-9), weighted towards lower semesters? No, uniform is fine.
    $sem = rand(1, 9);
    
    // Matricula: Year + Career + ID
    // Year: 24 (freshman) down to 20 (seniors)
    $year = 25 - ceil($sem / 2); 
    $matricula = $year . $cKey . str_pad($i, 4, '0', STR_PAD_LEFT);
    
    $studentBatch[] = "('$matricula', '$nombre', '$apellido', '$email', '$password', 1, $cId)";
    
    // Enrollments
    if (isset($curriculumMap[$cKey][$sem])) {
        $subjects = $curriculumMap[$cKey][$sem];
        foreach ($subjects as $subjClave) {
            if (!isset($subjectMap[$subjClave])) continue;
            $sId = $subjectMap[$subjClave];
            
            // Pick a group
            if (isset($groupsBySubject[$sId])) {
                $gId = $groupsBySubject[$sId][array_rand($groupsBySubject[$sId])];
                
                // Inscripcion
                $enrollmentBatch[] = "($i, $gId, '$CURRENT_CYCLE', 'inscrito', $sem)";
                
                // Grades (Current cycle)
                // 3 Partials + Final
                // Randomize performance
                $perf = rand(1, 100);
                $p1 = $p2 = $p3 = 0;
                if ($perf > 20) { // 80% pass rate
                    $p1 = rand(70, 100);
                    $p2 = rand(70, 100);
                    $p3 = rand(70, 100);
                } else {
                    $p1 = rand(40, 80);
                    $p2 = rand(40, 80);
                    $p3 = rand(0, 60);
                }
                $final = round(($p1+$p2+$p3)/3);
                
                // We need the inscripcion_id.
                // Since we are batching, we can't know it easily.
                // We have to assume sequential IDs.
                // $inscripcionIdCounter is the ID for this enrollment.
                
                $gradesUnitBatch[] = "($inscripcionIdCounter, 1, $p1)";
                $gradesUnitBatch[] = "($inscripcionIdCounter, 2, $p2)";
                $gradesUnitBatch[] = "($inscripcionIdCounter, 3, $p3)";
                
                $status = ($final >= 70) ? 'aprobado' : 'reprobado';
                $gradesFinalBatch[] = "($inscripcionIdCounter, $final, $final, $final, '$status')";
                
                $inscripcionIdCounter++;
            }
        }
    }
    
    if (count($studentBatch) >= 50) {
        writeLine("INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo, carrera_id) VALUES " . implode(',', $studentBatch) . ";");
        $studentBatch = [];
    }
    if (count($enrollmentBatch) >= 50) {
        writeLine("INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus, semestre_cursado) VALUES " . implode(',', $enrollmentBatch) . ";");
        $enrollmentBatch = [];
    }
    if (count($gradesUnitBatch) >= 150) {
        writeLine("INSERT INTO calificaciones_unidades (inscripcion_id, unidad_num, calificacion) VALUES " . implode(',', $gradesUnitBatch) . ";");
        $gradesUnitBatch = [];
    }
    if (count($gradesFinalBatch) >= 50) {
        writeLine("INSERT INTO calificaciones_finales (inscripcion_id, calificacion_final, promedio_unidades, promedio_general, estatus) VALUES " . implode(',', $gradesFinalBatch) . ";");
        $gradesFinalBatch = [];
    }
}

// Flush remaining
if (!empty($studentBatch)) writeLine("INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo, carrera_id) VALUES " . implode(',', $studentBatch) . ";");
if (!empty($enrollmentBatch)) writeLine("INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus, semestre_cursado) VALUES " . implode(',', $enrollmentBatch) . ";");
if (!empty($gradesUnitBatch)) writeLine("INSERT INTO calificaciones_unidades (inscripcion_id, unidad_num, calificacion) VALUES " . implode(',', $gradesUnitBatch) . ";");
if (!empty($gradesFinalBatch)) writeLine("INSERT INTO calificaciones_finales (inscripcion_id, calificacion_final, promedio_unidades, promedio_general, estatus) VALUES " . implode(',', $gradesFinalBatch) . ";");


writeLine("");
writeLine("SET FOREIGN_KEY_CHECKS = 1;");
writeLine("SELECT 'Database generated successfully' as status;");

fclose($handle);
echo "Generated database_full.sql\n";
