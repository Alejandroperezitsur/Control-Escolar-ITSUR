<?php
/**
 * Generate Realistic Student Data for 7th Semester ISC Student
 * 
 * This script generates:
 * - Realistic ISC subjects with appropriate credits
 * - A 7th semester student with complete academic history (semesters 1-6 completed, 7 in progress)
 * - Professors for each subject
 * - Groups with schedules and classrooms
 * - Unit-based grades for all subjects
 * - Realistic credits tracking (~179 credits completed out of 240)
 * 
 * Run: php scripts/generate_realistic_student_data.php
 */

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// Create database connection
try {
    $dsn = "mysql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']};charset=utf8mb4";
    $pdo = new PDO(
        $dsn,
        $config['db']['user'],
        $config['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("\n[ERROR] No se pudo conectar a la base de datos.\n" . $e->getMessage() . "\n\n" .
        "Asegúrate de importar primero el schema usando phpMyAdmin:\n" .
        "  migrations/complete_real_system_schema.sql\n\n");
}

echo "\n";
echo "==================================================================\n";
echo " Generando Datos Realistas - Alumno de 7mo Semestre ISC\n";
echo "==================================================================\n\n";

$pdo->beginTransaction();

try {
    
    // ==================================================================
    // MATERIAS DE ISC (Basadas en retícula real)
    // ==================================================================
    
    echo "[INFO] Creando materias de ISC...\n";
    
    $materias_isc = [
        // Semestre 1
        ['nombre' => 'Fundamentos de Programación', 'clave' => 'SCC1001', 'creditos' => 5, 'semestre' => 1, 'num_unidades' => 10],
        ['nombre' => 'Cálculo Diferencial', 'clave' => 'ACM0901', 'creditos' => 5, 'semestre' => 1, 'num_unidades' => 8],
        ['nombre' => 'Fundamentos de Investigación', 'clave' => 'ACC0902', 'creditos' => 4, 'semestre' => 1, 'num_unidades' => 6],
        ['nombre' => 'Matemáticas Discretas', 'clave' => 'SCH1009', 'creditos' => 5, 'semestre' => 1, 'num_unidades' => 8],
        ['nombre' => 'Taller de Ética', 'clave' => 'ACA0907', 'creditos' => 4, 'semestre' => 1, 'num_unidades' => 5],
        
        // Semestre 2
        ['nombre' => 'Programación Orientada a Objetos', 'clave' => 'SCD1008', 'creditos' => 5, 'semestre' => 2, 'num_unidades' => 10],
        ['nombre' => 'Cálculo Integral', 'clave' => 'ACF0903', 'creditos' => 5, 'semestre' => 2, 'num_unidades' => 8],
        ['nombre' => 'Contabilidad Financiera', 'clave' => 'AEC1008', 'creditos' => 4, 'semestre' => 2, 'num_unidades' => 7],
        ['nombre' => 'Álgebra Lineal', 'clave' => 'ACF0904', 'creditos' => 5, 'semestre' => 2, 'num_unidades' => 8],
        ['nombre' => 'Química', 'clave' => 'AEF1052', 'creditos' => 4, 'semestre' => 2, 'num_unidades' => 7],
        
        // Semestre 3
        ['nombre' => 'Estructura de Datos', 'clave' => 'SCD1003', 'creditos' => 5, 'semestre' => 3, 'num_unidades' => 10],
        ['nombre' => 'Cálculo Vectorial', 'clave' => 'ACF0905', 'creditos' => 5, 'semestre' => 3, 'num_unidades' => 8],
        ['nombre' => 'Cultura Empresarial', 'clave' => 'SCC1009', 'creditos' => 4, 'semestre' => 3, 'num_unidades' => 6],
        ['nombre' => 'Investigación de Operaciones', 'clave' => 'SCJ1013', 'creditos' => 4, 'semestre' => 3, 'num_unidades' => 8],
        ['nombre' => 'Desarrollo Sustentable', 'clave' => 'ACD0908', 'creditos' => 5, 'semestre' => 3, 'num_unidades' => 7],
        
        // Semestre 4
        ['nombre' => 'Topicos Avanzados de Programación', 'clave' => 'SCD1027', 'creditos' => 5, 'semestre' => 4, 'num_unidades' => 10],
        ['nombre' => 'Ecuaciones Diferenciales', 'clave' => 'ACF0906', 'creditos' => 5, 'semestre' => 4, 'num_unidades' => 8],
        ['nombre' => 'Fundamentos de Telecomunicaciones', 'clave' => 'SCC1005', 'creditos' => 4, 'semestre' => 4, 'num_unidades' => 7],
        ['nombre' => 'Fundamentos de Bases de Datos', 'clave' => 'SCC1004', 'creditos' => 5, 'semestre' => 4, 'num_unidades' => 10],
        ['nombre' => 'Principios Eléctricos y Aplicaciones Digitales', 'clave' => 'SCA1025', 'creditos' => 5, 'semestre' => 4, 'num_unidades' => 7],
        
        // Semestre 5
        ['nombre' => 'Sistemas Operativos', 'clave' => 'SCD1020', 'creditos' => 5, 'semestre' => 5, 'num_unidades' => 10],
        ['nombre' => 'Taller de Bases de Datos', 'clave' => 'SCC1024', 'creditos' => 4, 'semestre' => 5, 'num_unidades' => 8],
        ['nombre' => 'Redes de Computadoras', 'clave' => 'SCC1015', 'creditos' => 5, 'semestre' => 5, 'num_unidades' => 10],
        ['nombre' => 'Administración de Bases de Datos', 'clave' => 'SCC1001', 'creditos' => 4, 'semestre' => 5, 'num_unidades' => 8],
        ['nombre' => 'Simulación', 'clave' => 'SCA1019', 'creditos' => 4, 'semestre' => 5, 'num_unidades' => 7],
        
        // Semestre 6
        ['nombre' => 'Arquitectura de Computadoras', 'clave' => 'SCC1002', 'creditos' => 5, 'semestre' => 6, 'num_unidades' => 8],
        ['nombre' => 'Lenguajes y Autómatas I', 'clave' => 'SCD1010', 'creditos' => 5, 'semestre' => 6, 'num_unidades' => 8],
        ['nombre' => 'Ingeniería de Software', 'clave' => 'SCD1007', 'creditos' => 5, 'semestre' => 6, 'num_unidades' => 10],
        ['nombre' => 'Conmutación y Enrutamiento de Redes de Datos', 'clave' => 'SCI1009', 'creditos' => 5, 'semestre' => 6, 'num_unidades' => 8],
        ['nombre' => 'Graficación', 'clave' => 'SCA1006', 'creditos' => 4, 'semestre' => 6, 'num_unidades' => 7],
        
        // Semestre 7 (ACTUAL)
        ['nombre' => 'Inteligencia Artificial', 'clave' => 'SCC1012', 'creditos' => 5, 'semestre' => 7, 'num_unidades' => 10],
        ['nombre' => 'Taller de Investigación I', 'clave' => 'ACA0909', 'creditos' => 4, 'semestre' => 7, 'num_unidades' => 6],
        ['nombre' => 'Gestión de Proyectos de Software', 'clave' => 'SCC1007', 'creditos' => 5, 'semestre' => 7, 'num_unidades' => 8],
        ['nombre' => 'Investigación de Operaciones', 'clave' => 'SCC1013', 'creditos' => 4, 'semestre' => 7, 'num_unidades' => 8],
        ['nombre' => 'Programación Web II', 'clave' => 'TH2201', 'creditos' => 5, 'semestre' => 7, 'num_unidades' => 10],
        ['nombre' => 'Programación Móvil I', 'clave' => 'TI2300', 'creditos' => 4, 'semestre' => 7, 'num_unidades' => 8],
        ['nombre' => 'Comunicación y Emulación de Redes', 'clave' => 'SCI1009', 'creditos' => 4, 'semestre' => 7, 'num_unidades' => 7],
    ];
    
    // Insertar materias
    $materia_ids = [];
    foreach ($materias_isc as $mat) {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM materias WHERE clave = ?");
        $stmt->execute([$mat['clave']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $materia_ids[$mat['clave']] = $existing['id'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO materias (nombre, clave, num_parciales, num_unidades, creditos, tipo)
                VALUES (?, ?, 2, ?, ?, 'basica')
            ");
            $stmt->execute([$mat['nombre'], $mat['clave'], $mat['num_unidades'], $mat['creditos']]);
            $materia_ids[$mat['clave']] = $pdo->lastInsertId();
        }
        
        // Link to ISC career (id = 1)
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo)
            VALUES (?, 1, ?, ?, 'basica')
        ");
        $stmt->execute([$materia_ids[$mat['clave']], $mat['semestre'], $mat['creditos']]);
    }
    
    echo "  ✓ Creadas " . count($materias_isc) . " materias de ISC\n\n";
    
    // ==================================================================
    // PROFESORES
    // ==================================================================
    
    echo "[INFO] Creando profesores...\n";
    
    $profesores = [
        ['nombre' => 'Dr. Juan Pérez García', 'email' => 'juan.perez@itsur.edu.mx', 'matricula' => 'P001'],
        ['nombre' => 'M.C. María López Hernández', 'email' => 'maria.lopez@itsur.edu.mx', 'matricula' => 'P002'],
        ['nombre' => 'Ing. Carlos Ramírez Silva', 'email' => 'carlos.ramirez@itsur.edu.mx', 'matricula' => 'P003'],
        ['nombre' => 'Dra. Ana González Flores', 'email' => 'ana.gonzalez@itsur.edu.mx', 'matricula' => 'P004'],
        ['nombre' => 'M.C. Roberto Sánchez Torres', 'email' => 'roberto.sanchez@itsur.edu.mx', 'matricula' => 'P005'],
        ['nombre' => 'Ing. Laura Martínez Cruz', 'email' => 'laura.martinez@itsur.edu.mx', 'matricula' => 'P006'],
        ['nombre' => 'Dr. Fernando Díaz Méndez', 'email' => 'fernando.diaz@itsur.edu.mx', 'matricula' => 'P007'],
    ];
    
    $profesor_ids = [];
    foreach ($profesores as $prof) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$prof['email']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $profesor_ids[] = $existing['id'];
        } else {
            // Password: profesor123
            $password = password_hash('profesor123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, matricula, password, rol, activo, carrera_id)
                VALUES (?, ?, ?, ?, 'profesor', 1, 1)
            ");
            $stmt->execute([$prof['nombre'], $prof['email'], $prof['matricula'], $password]);
            $profesor_ids[] = $pdo->lastInsertId();
        }
    }
    
    echo "  ✓ Creados " . count($profesores) . " profesores\n\n";
    
    // ==================================================================
    // ALUMNO DE 7MO SEMESTRE
    // ==================================================================
    
    echo "[INFO] Creando alumno de 7mo semestre...\n";
    
    $matricula_alumno = 'S22121198';
    $stmt = $pdo->prepare("SELECT id FROM alumnos WHERE matricula = ?");
    $stmt->execute([$matricula_alumno]);
    $existing_alumno = $stmt->fetch();
    
    if ($existing_alumno) {
        $alumno_id = $existing_alumno['id'];
        echo "  ⚠ Alumno ya existe (ID: $alumno_id), se usará el existente\n";
    } else {
        // Password: alumno123
        $password = password_hash('alumno123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo, carrera_id)
            VALUES (?, ?, ?, ?, ?, 1, 1)
        ");
        $stmt->execute([
            $matricula_alumno,
            'Alejandro',
            'Pérez Vázquez',
            'alejandro.perez@itsur.edu.mx',
            $password
        ]);
        $alumno_id = $pdo->lastInsertId();
        echo "  ✓ Alumno creado (ID: $alumno_id, Matrícula: $matricula_alumno)\n";
    }
    
    echo "\n";
    
    // ==================================================================
    // GRUPOS, INSCRIPCIONES Y CALIFICACIONES
    // ==================================================================
    
    echo "[INFO] Generando grupos, inscripciones y calificaciones...\n\n";
    
    $ciclos = [
        1 => '2021-1',
        2 => '2022-1',
        3 => '2022-2',
        4 => '2023-1',
        5 => '2023-2',
        6 => '2024-1',
        7 => '2024-2', // Actual
    ];
    
    $aulas = ['A1', 'A2', 'A3', 'A4', 'A5', 'B1', 'B2', 'B3', 'B4', 'C1', 'C2', 'Lab1', 'Lab2'];
    $dias_semana = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'];
    
    $total_creditos = 0;
    $materias_aprobadas = 0;
    
    foreach ($materias_isc as $mat) {
        $semestre = $mat['semestre'];
        $ciclo = $ciclos[$semestre];
        $materia_id = $materia_ids[$mat['clave']];
        
        // Assign random professor
        $profesor_id = $profesor_ids[array_rand($profesor_ids)];
        
        // Create group
        $grupo_nombre = chr(64 + $semestre); // A, B, C, D, E, F, G
        $stmt = $pdo->prepare("
            INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo, cupo, aula_default)
            VALUES (?, ?, ?, ?, 30, ?)
            ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
        ");
        $aula_default = $aulas[array_rand($aulas)];
        $stmt->execute([$materia_id, $profesor_id, $grupo_nombre, $ciclo, $aula_default]);
        $grupo_id = $pdo->lastInsertId();
        
        // Create schedule (2-3 sessions per week)
        $num_sessions = rand(2, 3);
        $used_days = [];
        for ($i = 0; $i < $num_sessions; $i++) {
            $dia = $dias_semana[array_rand($dias_semana)];
            while (in_array($dia, $used_days)) {
                $dia = $dias_semana[array_rand($dias_semana)];
            }
            $used_days[] = $dia;
            
            $hora_inicio = rand(7, 17);
            $hora_fin = $hora_inicio + 2;
            $aula = $aulas[array_rand($aulas)];
            
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $grupo_id,
                $dia,
                sprintf('%02d:00:00', $hora_inicio),
                sprintf('%02d:00:00', $hora_fin),
                $aula
            ]);
        }
        
        // Enroll student
        $stmt = $pdo->prepare("
            INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus, semestre_cursado)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
        ");
        $estatus = ($semestre <= 6) ? 'completado' : 'inscrito';
        $stmt->execute([$alumno_id, $grupo_id, $ciclo, $estatus, $semestre]);
        $inscripcion_id = $pdo->lastInsertId();
        
        // Generate grades
        if ($semestre <= 6) {
            // Materias aprobadas (semestres 1-6)
            $promedio_unidades = rand(70, 95);
            $calificacion_final = rand(70, 100);
            $promedio_general = round(($promedio_unidades + $calificacion_final) / 2, 2);
            
            // Create unit grades
            for ($unidad = 1; $unidad <= $mat['num_unidades']; $unidad++) {
                $calif_unidad = rand(max(60, $promedio_unidades - 10), min(100, $promedio_unidades + 10));
                $stmt = $pdo->prepare("
                    INSERT INTO calificaciones_unidades (inscripcion_id, unidad_num, calificacion)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion)
                ");
                $stmt->execute([$inscripcion_id, $unidad, $calif_unidad]);
            }
            
            // Create final grade
            $periodo = ($semestre % 2 == 0) ? 'ENE-JUN ' . (2020 + floor($semestre / 2)) : 'AGO-DIC ' . (2020 + floor($semestre / 2));
            $stmt = $pdo->prepare("
                INSERT INTO calificaciones_finales 
                (inscripcion_id, calificacion_final, promedio_unidades, promedio_general, estatus, tipo_acreditacion, periodo_acreditacion)
                VALUES (?, ?, ?, ?, 'aprobado', 'ordinario', ?)
                ON DUPLICATE KEY UPDATE 
                    calificacion_final = VALUES(calificacion_final),
                    promedio_unidades = VALUES(promedio_unidades),
                    promedio_general = VALUES(promedio_general)
            ");
            $stmt->execute([$inscripcion_id, $calificacion_final, $promedio_unidades, $promedio_general, $periodo]);
            
            $total_creditos += $mat['creditos'];
            $materias_aprobadas++;
            
        } else {
            // Semestre 7 (cursando) - Solo algunas unidades calificadas
            $unidades_calificadas = rand(5, 7); // Some units graded, some pending
            for ($unidad = 1; $unidad <= $unidades_calificadas; $unidad++) {
                $calif_unidad = rand(70, 100);
                $stmt = $pdo->prepare("
                    INSERT INTO calificaciones_unidades (inscripcion_id, unidad_num, calificacion)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion)
                ");
                $stmt->execute([$inscripcion_id, $unidad, $calif_unidad]);
            }
            
            // Pending units (0 or NULL)
            for ($unidad = $unidades_calificadas + 1; $unidad <= $mat['num_unidades']; $unidad++) {
                $stmt = $pdo->prepare("
                    INSERT INTO calificaciones_unidades (inscripcion_id, unidad_num, calificacion)
                    VALUES (?, ?, 0)
                    ON DUPLICATE KEY UPDATE calificacion = 0
                ");
                $stmt->execute([$inscripcion_id, $unidad]);
            }
            
            // No final grade yet
            $stmt = $pdo->prepare("
                INSERT INTO calificaciones_finales 
                (inscripcion_id, estatus, tipo_acreditacion)
                VALUES (?, 'cursando', 'ordinario')
                ON DUPLICATE KEY UPDATE estatus = 'cursando'
            ");
            $stmt->execute([$inscripcion_id]);
        }
        
        echo "  ✓ Semestre $semestre: {$mat['nombre']} - Grupo $grupo_nombre\n";
    }
    
    $pdo->commit();
    
    echo "\n";
    echo "==================================================================\n";
    echo " ✓ DATOS GENERADOS EXITOSAMENTE\n";
    echo "==================================================================\n\n";
    echo "Resumen:\n";
    echo "  - Materias creadas: " . count($materias_isc) . "\n";
    echo "  - Profesores creados: " . count($profesores) . "\n";
    echo "  - Alumno: $matricula_alumno (ID: $alumno_id)\n";
    echo "  - Créditos completados: ~$total_creditos / 240\n";
    echo "  - Materias aprobadas: $materias_aprobadas\n";
    echo "  - Semestre actual: 7\n\n";
    echo "Credenciales de prueba:\n";
    echo "  Admin: admin@itsur.edu.mx / admin123\n";
    echo "  Alumno: $matricula_alumno / alumno123\n";
    echo "  Profesores: [email]@itsur.edu.mx / profesor123\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
