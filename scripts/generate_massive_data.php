<?php
/**
 * GENERACIÓN MASIVA DE DATOS REALISTAS
 * 
 * Genera un sistema universitario completo:
 * - 700+ alumnos (100 por carrera x 7 carreras)
 * - 70 profesores
 * - Materias completas para todas las carreras
 * - Grupos realistas con horarios y aulas
 * - Inscripciones distribuidas por semestre
 * - Calificaciones variadas
 * 
 * IMPORTANTE: Este script puede tardar 1-3 minutos en ejecutarse
 */

set_time_limit(300); // 5 minutos máximo
ini_set('memory_limit', '512M');

$config = require __DIR__ . '/../config/config.php';

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
    die("\n[ERROR] No se pudo conectar a la base de datos.\n" . $e->getMessage() . "\n");
}

echo "\n";
echo "==================================================================\n";
echo " GENERACIÓN MASIVA DE DATOS - SISTEMA UNIVERSITARIO COMPLETO\n";
echo "==================================================================\n\n";
echo "Este proceso puede tardar 1-3 minutos...\n\n";

$pdo->beginTransaction();

try {
    
    // ==================================================================
    // DATOS BASE
    // ==================================================================
    
    $nombres_m = ['Alejandro', 'Carlos', 'José', 'Luis', 'Miguel', 'Juan', 'Fernando', 'Roberto', 'David', 'Javier'];
    $nombres_f = ['María', 'Ana', 'Laura', 'Carmen', 'Rosa', 'Patricia', 'Elena', 'Sandra', 'Diana', 'Gabriela'];
    $apellidos = ['García', 'Rodríguez', 'Hernández', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez', 'Díaz', 'Cruz', 'Morales', 'Jiménez', 'Ruiz', 'Mendoza', 'Vargas'];
    
    $aulas = [];
    for ($edificio = ord('A'); $edificio <= ord('F'); $edificio++) {
        for ($num = 1; $num <= 15; $num++) {
            $aulas[] = chr($edificio) . $num;
        }
    }
    $aulas = array_merge($aulas, ['Lab1', 'Lab2', 'Lab3', 'Lab4', 'Lab5', 'Taller1', 'Taller2', 'Auditorio']);
    
    // ==================================================================
    // 1. PROFESORES (70 profesores)
    // ==================================================================
    
    echo "[1/7] Generando 70 profesores...\n";
    
    $profesores = [];
    $prof_count = 0;
    
    for ($i = 1; $i <= 70; $i++) {
        $genero = rand(0, 1);
        $nombre = $genero ? $nombres_m[array_rand($nombres_m)] . ' ' . $apellidos[array_rand($apellidos)] : 
                           $nombres_f[array_rand($nombres_f)] . ' ' . $apellidos[array_rand($apellidos)];
        
        $titulo = ['Dr.', 'Dra.', 'M.C.', 'Ing.', 'Mtro.', 'Mtra.'][array_rand(['Dr.', 'Dra.', 'M.C.', 'Ing.', 'Mtro.', 'Mtra.'])];
        $nombre_completo = "$titulo $nombre " . $apellidos[array_rand($apellidos)];
        
        $matricula = sprintf('P%03d', $i);
        $email = strtolower(str_replace([' ', '.'], ['', ''], $nombre)) . '@itsur.edu.mx';
        $password = password_hash('profesor123', PASSWORD_DEFAULT);
        
        $carrera_id = ($i % 7) + 1; // Distribuir profesores entre carreras
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, matricula, password, rol, activo, carrera_id)
            VALUES (?, ?, ?, ?, 'profesor', 1, ?)
            ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
        ");
        $stmt->execute([$nombre_completo, $email, $matricula, $password, $carrera_id]);
        $profesores[] = $pdo->lastInsertId();
        $prof_count++;
    }
    
    echo "  ✓ Creados $prof_count profesores\n\n";
    
    // ==================================================================
    // 2. MATERIAS POR CARRERA (35-40 materias por carrera)
    // ==================================================================
    
    echo "[2/7] Generando materias para todas las carreras...\n";
    
    $materias_base = [
        // Materias generales (todas las carreras)
        ['Cálculo Diferencial', 5, ['básica', 1]],
        ['Cálculo Integral', 5, ['básica', 2]],
        ['Cálculo Vectorial', 5, ['básica', 3]],
        ['Ecuaciones Diferenciales', 5, ['básica', 4]],
        ['Álgebra Lineal', 5, ['básica', 2]],
        ['Probabilidad y Estadística', 5, ['básica', 5]],
        ['Química', 4, ['básica', 2]],
        ['Física I', 5, ['básica', 3]],
        ['Física II', 5, ['básica', 4]],
        ['Taller de Ética', 4, ['básica', 1]],
        ['Desarrollo Sustentable', 5, ['básica', 3]],
        ['Fundamentos de Investigación', 4, ['básica', 1]],
        ['Taller de Investigación I', 4, ['especialidad', 7]],
        ['Taller de Investigación II', 4, ['especialidad', 8]],
        ['Inglés I', 3, ['básica', 1]],
        ['Inglés II', 3, ['básica', 2]],
        ['Inglés III', 3, ['básica', 3]],
        ['Inglés IV', 3, ['básica', 4]],
    ];
    
    // Materias específicas por carrera
    $materias_carreras = [
        1 => [ // ISC
            ['Fundamentos de Programación', 5, 1],
            ['Programación Orientada a Objetos', 5, 2],
            ['Estructura de Datos', 5, 3],
            ['Tópicos Avanzados de Programación', 5, 4],
            ['Sistemas Operativos', 5, 5],
            ['Redes de Computadoras', 5, 5],
            ['Bases de Datos', 5, 4],
            ['Taller de Bases de Datos', 4, 5],
            ['Ingeniería de Software', 5, 6],
            ['Arquitectura de Computadoras', 5, 6],
            ['Inteligencia Artificial', 5, 7],
            ['Programación Web', 5, 6],
            ['Programación Móvil', 4, 7],
            ['Seguridad Informática', 5, 8],
            ['Gestión de Proyectos de Software', 5, 7],
            ['Lenguajes y Autómatas', 5, 6],
            ['Graficación', 4, 6],
            ['Simulación', 4, 5],
        ],
        2 => [ // II - Industrial
            ['Gestión de Costos', 5, 3],
            ['Planeación Financiera', 5, 4],
            ['Sistemas de Manufactura', 5, 5],
            ['Logística y Cadenas de Suministro', 5, 6],
            ['Ingeniería Económica', 5, 4],
            ['Estudio del Trabajo I', 4, 3],
            ['Estudio del Trabajo II', 4, 4],
            ['Control Estadístico de Calidad', 5, 5],
            ['Administración de Proyectos', 5, 7],
            ['Diseño de Instalaciones', 4, 6],
        ],
        3 => [ // IGE - Gestión Empresarial
            ['Fundamentos de Gestión Empresarial', 5, 1],
            ['Contabilidad Básica', 5, 2],
            ['Mercadotecnia', 5, 4],
            ['Administración de Recursos Humanos', 5, 5],
            ['Finanzas Empresariales', 5, 6],
            ['Plan de Negocios', 4, 7],
            ['Comercio Electrónico', 4, 6],
        ],
    ];
    
    $materias_ids = [];
    $materia_count = 0;
    
    // Insertar materias por carrera
    for ($carrera_id = 1; $carrera_id <= 7; $carrera_id++) {
        $claveBase = ['ISC', 'II', 'IGE', 'IE', 'IM', 'IER', 'CP'][$carrera_id - 1];
        
        // Materias generales
        foreach ($materias_base as $idx => $matData) {
            $clave = $claveBase . sprintf('%03d', $idx + 1);
            
            $stmt = $pdo->prepare("
                INSERT INTO materias (nombre, clave, num_unidades, creditos, tipo)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
            ");
            $stmt->execute([$matData[0], $clave, rand(6, 10), $matData[1], $matData[2][0]]);
            $materia_id = $pdo->lastInsertId();
            
            // Link to career
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$materia_id, $carrera_id, $matData[2][1], $matData[1], $matData[2][0]]);
            
            $materias_ids[$carrera_id][] = $materia_id;
            $materia_count++;
        }
        
        // Materias específicas si existen
        if (isset($materias_carreras[$carrera_id])) {
            foreach ($materias_carreras[$carrera_id] as $idx => $matData) {
                $clave = $claveBase . sprintf('%03d', 100 + $idx);
                
                $stmt = $pdo->prepare("
                    INSERT INTO materias (nombre, clave, num_unidades, creditos, tipo)
                    VALUES (?, ?, ?, ?, 'especialidad')
                    ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
                ");
                $stmt->execute([$matData[0], $clave, rand(8, 10), $matData[1]]);
                $materia_id = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo)
                    VALUES (?, ?, ?, ?, 'especialidad')
                ");
                $stmt->execute([$materia_id, $carrera_id, $matData[2], $matData[1]]);
                
                $materias_ids[$carrera_id][] = $materia_id;
                $materia_count++;
            }
        }
    }
    
    echo "  ✓ Creadas $materia_count materias\n\n";
    
    // ==================================================================
    // 3. GRUPOS (múltiples grupos por materia)
    // ==================================================================
    
    echo "[3/7] Creando grupos con horarios...\n";
    
    $ciclos = ['2021-1', '2021-2', '2022-1', '2022-2', '2023-1', '2023-2', '2024-1', '2024-2'];
    $dias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'];
    $grupos_ids = [];
    $grupo_count = 0;
    
    foreach ($materias_ids as $carrera_id => $mats) {
        foreach ($mats as $materia_id) {
            // 2-3 grupos por materia
            $num_grupos = rand(2, 3);
            
            for ($g = 0; $g < $num_grupos; $g++) {
                $grupo_nombre = chr(65 + $g); // A, B, C
                $profesor_id = $profesores[array_rand($profesores)];
                $ciclo = $ciclos[array_rand($ciclos)];
                $aula = $aulas[array_rand($aulas)];
                
                $stmt = $pdo->prepare("
                    INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo, cupo, aula_default)
                    VALUES (?, ?, ?, ?, 30, ?)
                ");
                $stmt->execute([$materia_id, $profesor_id, $grupo_nombre, $ciclo, $aula]);
                $grupo_id = $pdo->lastInsertId();
                $grupos_ids[] = $grupo_id;
                
                // Crear horario (2-3 sesiones por semana)
                $num_sesiones = rand(2, 3);
                $dias_usados = [];
                
                for ($s = 0; $s < $num_sesiones; $s++) {
                    do {
                        $dia = $dias[array_rand($dias)];
                    } while (in_array($dia, $dias_usados));
                    $dias_usados[] = $dia;
                    
                    $hora_inicio = rand(7, 17);
                    $hora_fin = $hora_inicio + 2;
                    $aula_ses = $aulas[array_rand($aulas)];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $grupo_id,
                        $dia,
                        sprintf('%02d:00:00', $hora_inicio),
                        sprintf('%02d:00:00', $hora_fin),
                        $aula_ses
                    ]);
                }
                
                $grupo_count++;
            }
        }
    }
    
    echo "  ✓ Creados $grupo_count grupos con horarios\n\n";
    
    // ==================================================================
    // 4. ALUMNOS (100 por carrera = 700 alumnos)
    // ==================================================================
    
    echo "[4/7] Generando 700 alumnos (puede tardar)...\n";
    
    $alumnos_ids = [];
    $alumno_count = 0;
    
    for ($carrera_id = 1; $carrera_id <= 7; $carrera_id++) {
        $clave_carrera = ['S', 'I', 'G', 'E', 'M', 'R', 'C'][$carrera_id - 1];
        
        for ($a = 1; $a <= 100; $a++) {
            $genero = rand(0, 1);
            $nombre = $genero ? $nombres_m[array_rand($nombres_m)] : $nombres_f[array_rand($nombres_f)];
            $apellido = $apellidos[array_rand($apellidos)] . ' ' . $apellidos[array_rand($apellidos)];
            
            $año = rand(20, 24);
            $consecutivo = ($carrera_id - 1) * 100 + $a;
            $matricula = sprintf('%s%02d%04d', $clave_carrera, $año, $consecutivo);
            
            $email = strtolower($nombre . '.' . str_replace(' ', '', $apellido)) . '@itsur.edu.mx';
            $password = password_hash('alumno123', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo, carrera_id)
                VALUES (?, ?, ?, ?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
            ");
            $stmt->execute([$matricula, $nombre, $apellido, $email, $password, $carrera_id]);
            $alumnos_ids[$carrera_id][] = [
                'id' => $pdo->lastInsertId(),
                'semestre_actual' => rand(1, 9)
            ];
            $alumno_count++;
            
            if ($alumno_count % 100 == 0) {
                echo "  ... $alumno_count alumnos creados\n";
            }
        }
    }
    
    echo "  ✓ Total: $alumno_count alumnos creados\n\n";
    
    // ==================================================================
    // 5. INSCRIPCIONES Y CALIFICACIONES (MASIVO)
    // ==================================================================
    
    echo "[5/7] Inscribiendo alumnos y generando calificaciones (puede tardar 1-2 min)...\n";
    
    $inscripcion_count = 0;
    $calif_count = 0;
    
    foreach ($alumnos_ids as $carrera_id => $alumnos) {
        if (!isset($materias_ids[$carrera_id])) continue;
        
        foreach ($alumnos as $alumno_data) {
            $alumno_id = $alumno_data['id'];
            $semestre_actual = $alumno_data['semestre_actual'];
            
            // Inscribir en materias de semestres anteriores
            for ($sem = 1; $sem < $semestre_actual; $sem++) {
                // Buscar materias de este semestre
                $stmt = $pdo->prepare("
                    SELECT m.id, m.num_unidades, m.creditos
                    FROM materias m
                    JOIN materias_carrera mc ON mc.materia_id = m.id
                    WHERE mc.carrera_id = ? AND mc.semestre = ?
                    LIMIT 5
                ");
                $stmt->execute([$carrera_id, $sem]);
                $materias_sem = $stmt->fetchAll();
                
                foreach ($materias_sem as $mat) {
                    // Buscar un grupo de esta materia
                    $stmt = $pdo->prepare("SELECT id FROM grupos WHERE materia_id = ? LIMIT 1");
                    $stmt->execute([$mat['id']]);
                    $grupo = $stmt->fetch();
                    if (!$grupo) continue;
                    
                    $ciclo = $ciclos[array_rand($ciclos)];
                    
                    // Inscribir
                    $stmt = $pdo->prepare("
                        INSERT IGNORE INTO inscripciones (alumno_id, grupo_id, ciclo, estatus, semestre_cursado)
                        VALUES (?, ?, ?, 'completado', ?)
                    ");
                    $stmt->execute([$alumno_id, $grupo['id'], $ciclo, $sem]);
                    $inscripcion_id = $pdo->lastInsertId();
                    
                    if ($inscripcion_id) {
                        $inscripcion_count++;
                        
                        // Generar calificaciones por unidad
                        $promedio_base = rand(70, 95);
                        for ($unidad = 1; $unidad <= $mat['num_unidades']; $unidad++) {
                            $calif = rand(max(60, $promedio_base - 10), min(100, $promedio_base + 10));
                            $stmt = $pdo->prepare("
                                INSERT IGNORE INTO calificaciones_unidades (inscripcion_id, unidad_num, calificacion)
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$inscripcion_id, $unidad, $calif]);
                            $calif_count++;
                        }
                        
                        // Calificación final
                        $calif_final = rand(70, 100);
                        $promedio = round(($promedio_base + $calif_final) / 2, 2);
                        $periodo = ($sem % 2 == 0) ? 'ENE-JUN ' . (2020 + floor($sem / 2)) : 'AGO-DIC ' . (2020 + floor($sem / 2));
                        
                        $stmt = $pdo->prepare("
                            INSERT IGNORE INTO calificaciones_finales 
                            (inscripcion_id, calificacion_final, promedio_unidades, promedio_general, estatus, tipo_acreditacion, periodo_acreditacion)
                            VALUES (?, ?, ?, ?, 'aprobado', 'ordinario', ?)
                        ");
                        $stmt->execute([$inscripcion_id, $calif_final, $promedio_base, $promedio, $periodo]);
                    }
                }
            }
            
            if ($inscripcion_count % 500 == 0) {
                echo "  ... $inscripcion_count inscripciones procesadas\n";
            }
        }
    }
    
    echo "  ✓ $inscripcion_count inscripciones\n";
    echo "  ✓ $calif_count calificaciones generadas\n\n";
    
    $pdo->commit();
    
    echo "==================================================================\n";
    echo " ✓ SISTEMA COMPLETO GENERADO EXITOSAMENTE\n";
    echo "==================================================================\n\n";
    echo "Resumen:\n";
    echo "  - Profesores: $prof_count\n";
    echo "  - Materias: $materia_count\n";
    echo "  - Grupos: $grupo_count\n";
    echo "  - Alumnos: $alumno_count\n";
    echo "  - Inscripciones: $inscripcion_count\n";
    echo "  - Calificaciones: $calif_count\n\n";
    echo "Credenciales:\n";
    echo "  Admin: admin@itsur.edu.mx / admin123\n";
    echo "  Profesores: P001-P070 / profesor123\n";
    echo "  Alumnos: [matrícula] / alumno123\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
