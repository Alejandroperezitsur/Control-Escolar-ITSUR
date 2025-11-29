<?php
// API para verificación completa de datos poblados
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    $data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'students' => [],
        'professors' => [],
        'careers' => [],
        'subjects' => [],
        'groups' => [],
        'enrollments' => [],
        'schedules' => [],
        'grades' => [],
        'summary' => []
    ];
    
    // ESTUDIANTES (700) - Con TODA su información
    $studentsQuery = "
        SELECT 
            a.id,
            a.matricula,
            a.nombre,
            a.apellido,
            a.email,
            a.password,
            a.fecha_nac,
            a.activo,
            c.nombre AS carrera_nombre,
            c.clave AS carrera_clave,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.alumno_id = a.id) AS total_inscripciones,
            (SELECT COUNT(*) FROM calificaciones cal WHERE cal.alumno_id = a.id) AS total_calificaciones,
            (SELECT GROUP_CONCAT(DISTINCT g.nombre ORDER BY g.ciclo, g.nombre SEPARATOR ', ') 
             FROM calificaciones cal 
             JOIN grupos g ON g.id = cal.grupo_id 
             WHERE cal.alumno_id = a.id) AS grupos_asignados,
            (SELECT COUNT(DISTINCT g.id) FROM calificaciones cal JOIN grupos g ON g.id = cal.grupo_id WHERE cal.alumno_id = a.id AND cal.final IS NULL) AS materias_pendientes
        FROM alumnos a
        LEFT JOIN carreras c ON c.id = a.carrera_id
        ORDER BY a.id
    ";
    $stmt = $pdo->query($studentsQuery);
    $data['students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // PROFESORES (70) - Con TODA su información
    $professorsQuery = "
        SELECT 
            u.id,
            u.nombre,
            u.email,
            u.password,
            u.rol,
            u.activo,
            u.carrera_id,
            c.nombre AS carrera_nombre,
            (SELECT COUNT(*) FROM grupos g WHERE g.profesor_id = u.id) AS total_grupos,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(m.nombre, ' (', g.nombre, ')') ORDER BY g.ciclo, m.nombre SEPARATOR ', ') 
             FROM grupos g 
             JOIN materias m ON m.id = g.materia_id 
             WHERE g.profesor_id = u.id) AS grupos_asignados,
            (SELECT COUNT(DISTINCT g.materia_id) FROM grupos g WHERE g.profesor_id = u.id) AS materias_distintas
        FROM usuarios u
        LEFT JOIN carreras c ON c.id = u.carrera_id
        WHERE u.rol = 'profesor'
        ORDER BY u.id
    ";
    $stmt = $pdo->query($professorsQuery);
    $data['professors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CARRERAS
    $careersQuery = "
        SELECT 
            c.id,
            c.nombre,
            c.clave,
            c.activo,
            (SELECT COUNT(*) FROM alumnos a WHERE a.carrera_id = c.id) AS total_alumnos,
            (SELECT COUNT(*) FROM usuarios u WHERE u.carrera_id = c.id AND u.rol = 'profesor') AS total_profesores,
            (SELECT COUNT(*) FROM materias_carrera mc WHERE mc.carrera_id = c.id) AS total_materias
        FROM carreras c
        ORDER BY c.id
    ";
    $stmt = $pdo->query($careersQuery);
    $data['careers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // MATERIAS
    $subjectsQuery = "
        SELECT 
            m.id,
            m.nombre,
            m.clave,
            (SELECT COUNT(*) FROM grupos g WHERE g.materia_id = m.id) AS total_grupos,
            (SELECT GROUP_CONCAT(DISTINCT c.clave ORDER BY c.clave SEPARATOR ', ') 
             FROM materias_carrera mc 
             JOIN carreras c ON c.id = mc.carrera_id 
             WHERE mc.materia_id = m.id) AS carreras,
            (SELECT COUNT(*) FROM calificaciones cal JOIN grupos g ON g.id = cal.grupo_id WHERE g.materia_id = m.id) AS total_calificaciones
        FROM materias m
        ORDER BY m.id
    ";
    $stmt = $pdo->query($subjectsQuery);
    $data['subjects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // GRUPOS
    $groupsQuery = "
        SELECT 
            g.id,
            g.nombre,
            g.ciclo,
            g.cupo,
            g.aula_default,
            m.nombre AS materia_nombre,
            m.clave AS materia_clave,
            u.nombre AS profesor_nombre,
            u.email AS profesor_email,
            (SELECT COUNT(*) FROM calificaciones cal WHERE cal.grupo_id = g.id) AS alumnos_inscritos,
            (SELECT COUNT(*) FROM horarios h WHERE h.grupo_id = g.id) AS horarios_definidos
        FROM grupos g
        JOIN materias m ON m.id = g.materia_id
        LEFT JOIN usuarios u ON u.id = g.profesor_id
        ORDER BY g.ciclo DESC, m.nombre, g.nombre
    ";
    $stmt = $pdo->query($groupsQuery);
    $data['groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // INSCRIPCIONES
    $enrollmentsQuery = "
        SELECT 
            i.id,
            i.alumno_id,
            a.matricula,
            a.nombre AS alumno_nombre,
            i.grupo_id,
            g.nombre AS grupo_nombre,
            m.nombre AS materia_nombre,
            g.ciclo,
            i.fecha_inscripcion
        FROM inscripciones i
        JOIN alumnos a ON a.id = i.alumno_id
        JOIN grupos g ON g.id = i.grupo_id
        JOIN materias m ON m.id = g.materia_id
        ORDER BY i.id
        LIMIT 1000
    ";
    $stmt = $pdo->query($enrollmentsQuery);
    $data['enrollments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // HORARIOS
    $schedulesQuery = "
        SELECT 
            h.id,
            h.grupo_id,
            g.nombre AS grupo_nombre,
            m.nombre AS materia_nombre,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            h.aula
        FROM horarios h
        JOIN grupos g ON g.id = h.grupo_id
        JOIN materias m ON m.id = g.materia_id
        ORDER BY h.id
        LIMIT 1000
    ";
    $stmt = $pdo->query($schedulesQuery);
    $data['schedules'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CALIFICACIONES
    $gradesQuery = "
        SELECT 
            cal.id,
            cal.alumno_id,
            a.matricula,
            a.nombre AS alumno_nombre,
            cal.grupo_id,
            g.nombre AS grupo_nombre,
            m.nombre AS materia_nombre,
            g.ciclo,
            cal.parcial1,
            cal.parcial2,
            cal.final,
            ROUND(IFNULL(cal.final, (IFNULL(cal.parcial1,0)+IFNULL(cal.parcial2,0))/2), 2) AS promedio
        FROM calificaciones cal
        JOIN alumnos a ON a.id = cal.alumno_id
        JOIN grupos g ON g.id = cal.grupo_id
        JOIN materias m ON m.id = g.materia_id
        ORDER BY cal.id
        LIMIT 5000
    ";
    $stmt = $pdo->query($gradesQuery);
    $data['grades'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // RESUMEN
    $data['summary'] = [
        'total_students' => count($data['students']),
        'total_professors' => count($data['professors']),
        'total_careers' => count($data['careers']),
        'total_subjects' => count($data['subjects']),
        'total_groups' => count($data['groups']),
        'total_enrollments_shown' => count($data['enrollments']),
        'total_schedules_shown' => count($data['schedules']),
        'total_grades_shown' => count($data['grades']),
        'students_without_career' => count(array_filter($data['students'], fn($s) => empty($s['carrera_nombre']))),
        'students_without_enrollments' => count(array_filter($data['students'], fn($s) => $s['total_calificaciones'] == 0)),
        'professors_without_groups' => count(array_filter($data['professors'], fn($p) => $p['total_grupos'] == 0)),
        'subjects_without_groups' => count(array_filter($data['subjects'], fn($s) => $s['total_grupos'] == 0))
    ];
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
