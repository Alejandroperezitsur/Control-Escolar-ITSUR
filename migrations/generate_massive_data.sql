-- ============================================================================
-- GENERACIÓN MASIVA DE DATOS VIA SQL
-- Ejecutar DESPUÉS de schema_infinityfree.sql
-- ============================================================================
-- Este script usa loops SQL para generar datos masivos sin archivos gigantes
-- ============================================================================

SET @profesor_count = 0;
SET @alumno_count = 0;
SET @materia_count = 0;

-- ============================================================================
-- 1. PROFESORES (70)
-- ============================================================================

INSERT INTO usuarios (nombre, email, matricula, password, rol, activo, carrera_id) 
SELECT 
    CONCAT(
        CASE FLOOR(RAND() * 6)
            WHEN 0 THEN 'Dr. '
            WHEN 1 THEN 'Dra. '
            WHEN 2 THEN 'M.C. '
            WHEN 3 THEN 'Ing. '
            WHEN 4 THEN 'Mtro. '
            ELSE 'Mtra. '
        END,
        ELT(FLOOR(1 + RAND() * 10), 'Juan', 'María', 'Carlos', 'Ana', 'Luis', 'Rosa', 'Miguel', 'Patricia', 'Fernando', 'Laura'),
        ' ',
        ELT(FLOOR(1 + RAND() * 20), 'García', 'Rodríguez', 'Hernández', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez', 'Díaz', 'Cruz', 'Morales', 'Jiménez', 'Ruiz', 'Mendoza', 'Vargas'),
        ' ',
        ELT(FLOOR(1 + RAND() * 20), 'García', 'Rodríguez', 'Hernández', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez', 'Díaz', 'Cruz', 'Morales', 'Jiménez', 'Ruiz', 'Mendoza', 'Vargas')
    ) as nombre,
    CONCAT('profesor', n, '@itsur.edu.mx') as email,
    CONCAT('P', LPAD(n, 3, '0')) as matricula,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password,
    'profesor' as rol,
    1 as activo,
    ((n - 1) MOD 7) + 1 as carrera_id
FROM (
    SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION
    SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
    SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30 UNION
    SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35 UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40 UNION
    SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45 UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50 UNION
    SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55 UNION SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION SELECT 60 UNION
    SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64 UNION SELECT 65 UNION SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69 UNION SELECT 70
) numbers;

-- ============================================================================
-- 2. MATERIAS BÁSICAS (30 materias comunes)
-- ============================================================================

INSERT INTO materias (nombre, clave, num_parciales, num_unidades, creditos, tipo) VALUES
('Cálculo Diferencial', 'MAT001', 2, 8, 5, 'basica'),
('Cálculo Integral', 'MAT002', 2, 8, 5, 'basica'),
('Cálculo Vectorial', 'MAT003', 2, 8, 5, 'basica'),
('Ecuaciones Diferenciales', 'MAT004', 2, 8, 5, 'basica'),
('Álgebra Lineal', 'MAT005', 2, 8, 5, 'basica'),
('Probabilidad y Estadística', 'MAT006', 2, 8, 5, 'basica'),
('Química', 'QUI001', 2, 7, 4, 'basica'),
('Física I', 'FIS001', 2, 8, 5, 'basica'),
('Física II', 'FIS002', 2, 8, 5, 'basica'),
('Taller de Ética', 'ETI001', 2, 5, 4, 'basica'),
('Desarrollo Sustentable', 'SUS001', 2, 7, 5, 'basica'),
('Fundamentos de Investigación', 'INV001', 2, 6, 4, 'basica'),
('Taller de Investigación I', 'INV002', 2, 6, 4, 'especialidad'),
('Taller de Investigación II', 'INV003', 2, 6, 4, 'especialidad'),
('Inglés I', 'ING001', 2, 5, 3, 'basica'),
('Inglés II', 'ING002', 2, 5, 3, 'basica'),
('Inglés III', 'ING003', 2, 5, 3, 'basica'),
('Inglés IV', 'ING004', 2, 5, 3, 'basica'),
('Fundamentos de Programación', 'PRG001', 2, 10, 5, 'basica'),
('Programación Orientada a Objetos', 'PRG002', 2, 10, 5, 'basica'),
('Estructura de Datos', 'PRG003', 2, 10, 5, 'basica'),
('Bases de Datos', 'BD001', 2, 10, 5, 'basica'),
('Sistemas Operativos', 'SO001', 2, 10, 5, 'basica'),
('Redes de Computadoras', 'RED001', 2, 10, 5, 'basica'),
('Ingeniería de Software', 'SW001', 2, 10, 5, 'especialidad'),
('Inteligencia Artificial', 'IA001', 2, 10, 5, 'especialidad'),
('Programación Web', 'WEB001', 2, 10, 5, 'especialidad'),
('Programación Móvil', 'MOV001', 2, 8, 4, 'especialidad'),
('Seguridad Informática', 'SEG001', 2, 8, 5, 'especialidad'),
('Gestión de Proyectos', 'GPR001', 2, 8, 5, 'especialidad');

-- Link materias a carreras (distribución por semestres)
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo)
SELECT 
    m.id,
    c.id,
    CASE 
        WHEN m.id <= 6 THEN ((m.id - 1) MOD 4) + 1
        WHEN m.id <= 12 THEN ((m.id - 7) MOD 3) + 3
        WHEN m.id <= 18 THEN ((m.id - 13) MOD 4) + 1
        ELSE ((m.id - 19) MOD 5) + 4
    END as semestre,
    m.creditos,
    m.tipo
FROM materias m
CROSS JOIN carreras c
WHERE c.id <= 7;

-- ============================================================================
-- 3. GRUPOS (2-3 grupos por materia)
-- ============================================================================

INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo, cupo, aula_default)
SELECT 
    m.id as materia_id,
    (SELECT id FROM usuarios WHERE rol = 'profesor' ORDER BY RAND() LIMIT 1) as profesor_id,
    CHAR(64 + g.n) as nombre,
    CASE FLOOR(RAND() * 4)
        WHEN 0 THEN '2023-1'
        WHEN 1 THEN '2023-2'
        WHEN 2 THEN '2024-1'
        ELSE '2024-2'
    END as ciclo,
    30 as cupo,
    CONCAT(
        CHAR(65 + FLOOR(RAND() * 6)),
        FLOOR(1 + RAND() * 15)
    ) as aula_default
FROM materias m
CROSS JOIN (SELECT 1 as n UNION SELECT 2 UNION SELECT 3) g;

-- ============================================================================
-- 4. HORARIOS (2-3 sesiones por grupo)
-- ============================================================================

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    g.id as grupo_id,
    ELT(((g.id + s.n - 1) MOD 5) + 1, 'lunes', 'martes', 'miércoles', 'jueves', 'viernes') as dia_semana,
    CONCAT(LPAD(7 + ((g.id + s.n) MOD 10), 2, '0'), ':00:00') as hora_inicio,
    CONCAT(LPAD(9 + ((g.id + s.n) MOD 10), 2, '0'), ':00:00') as hora_fin,
    CONCAT(
        CHAR(65 + FLOOR(RAND() * 6)),
        FLOOR(1 + RAND() * 15)
    ) as aula
FROM grupos g
CROSS JOIN (SELECT 1 as n UNION SELECT 2) s;

-- ============================================================================
-- 5. ALUMNOS (700 alumnos - 100 por carrera)
-- ============================================================================

INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo, carrera_id)
SELECT 
    CONCAT(
        ELT(carrera, 'S', 'I', 'G', 'E', 'M', 'R', 'C'),
        LPAD(20 + FLOOR(RAND() * 5), 2, '0'),
        LPAD((carrera - 1) * 100 + alumno_num, 4, '0')
    ) as matricula,
    ELT(FLOOR(1 + RAND() * 10), 'Alejandro', 'María', 'Carlos', 'Ana', 'Luis', 'Rosa', 'Miguel', 'Patricia', 'Fernando', 'Laura') as nombre,
    CONCAT(
        ELT(FLOOR(1 + RAND() * 20), 'García', 'Rodríguez', 'Hernández', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez', 'Díaz', 'Cruz', 'Morales', 'Jiménez', 'Ruiz', 'Mendoza', 'Vargas'),
        ' ',
        ELT(FLOOR(1 + RAND() * 20), 'García', 'Rodríguez', 'Hernández', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivera', 'Gómez', 'Díaz', 'Cruz', 'Morales', 'Jiménez', 'Ruiz', 'Mendoza', 'Vargas')
    ) as apellido,
    CONCAT('alumno', (carrera - 1) * 100 + alumno_num, '@itsur.edu.mx') as email,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password,
    1 as activo,
    carrera as carrera_id
FROM (
    SELECT c.id as carrera, n.alumno_num
    FROM carreras c
    CROSS JOIN (
        SELECT 1 as alumno_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION
        SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
        SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30 UNION
        SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35 UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40 UNION
        SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45 UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50 UNION
        SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55 UNION SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION SELECT 60 UNION
        SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64 UNION SELECT 65 UNION SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69 UNION SELECT 70 UNION
        SELECT 71 UNION SELECT 72 UNION SELECT 73 UNION SELECT 74 UNION SELECT 75 UNION SELECT 76 UNION SELECT 77 UNION SELECT 78 UNION SELECT 79 UNION SELECT 80 UNION
        SELECT 81 UNION SELECT 82 UNION SELECT 83 UNION SELECT 84 UNION SELECT 85 UNION SELECT 86 UNION SELECT 87 UNION SELECT 88 UNION SELECT 89 UNION SELECT 90 UNION
        SELECT 91 UNION SELECT 92 UNION SELECT 93 UNION SELECT 94 UNION SELECT 95 UNION SELECT 96 UNION SELECT 97 UNION SELECT 98 UNION SELECT 99 UNION SELECT 100
    ) n
    WHERE c.id <= 7
) alumnos_data;

-- ============================================================================
-- 6. INSCRIPCIONES (Cada alumno inscrito en 5-8 materias)
-- ============================================================================

INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus, semestre_cursado)
SELECT 
    a.id as alumno_id,
    (SELECT id FROM grupos WHERE materia_id = m.id ORDER BY RAND() LIMIT 1) as grupo_id,
    '2024-2' as ciclo,
    CASE WHEN RAND() > 0.8 THEN 'completado' ELSE 'inscrito' END as estatus,
    FLOOR(1 + RAND() * 9) as semestre_cursado
FROM alumnos a
CROSS JOIN (SELECT id FROM materias ORDER BY RAND() LIMIT 6) m
WHERE NOT EXISTS (
    SELECT 1 FROM inscripciones i2 
    WHERE i2.alumno_id = a.id 
    AND i2.grupo_id IN (SELECT id FROM grupos WHERE materia_id = m.id)
);

-- ============================================================================
-- 7. CALIFICACIONES POR UNIDADES (Para inscripciones completadas)
-- ============================================================================

INSERT INTO calificaciones_unidades (inscripcion_id, unidad_num, calificacion)
SELECT 
    i.id as inscripcion_id,
    u.num as unidad_num,
    60 + FLOOR(RAND() * 40) as calificacion
FROM inscripciones i
JOIN grupos g ON g.id = i.grupo_id
JOIN materias m ON m.id = g.materia_id
CROSS JOIN (
    SELECT 1 as num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION
    SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) u
WHERE i.estatus = 'completado'
AND u.num <= m.num_unidades;

-- ============================================================================
-- 8. CALIFICACIONES FINALES (Resumen)
-- ============================================================================

INSERT INTO calificaciones_finales (inscripcion_id, calificacion_final, promedio_unidades, promedio_general, estatus, tipo_acreditacion, periodo_acreditacion)
SELECT 
    i.id as inscripcion_id,
    60 + FLOOR(RAND() * 40) as calificacion_final,
    (SELECT AVG(calificacion) FROM calificaciones_unidades WHERE inscripcion_id = i.id) as promedio_unidades,
    (SELECT (AVG(cu.calificacion) + (60 + FLOOR(RAND() * 40))) / 2 
     FROM calificaciones_unidades cu 
     WHERE cu.inscripcion_id = i.id) as promedio_general,
    'aprobado' as estatus,
    'ordinario' as tipo_acreditacion,
    '2024-2' as periodo_acreditacion
FROM inscripciones i
WHERE i.estatus = 'completado';

-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================

SELECT 'Datos generados exitosamente' as mensaje;
SELECT COUNT(*) as total_profesores FROM usuarios WHERE rol = 'profesor';
SELECT COUNT(*) as total_alumnos FROM alumnos;
SELECT COUNT(*) as total_materias FROM materias;
SELECT COUNT(*) as total_grupos FROM grupos;
SELECT COUNT(*) as total_inscripciones FROM inscripciones;
SELECT COUNT(*) as total_calificaciones FROM calificaciones_unidades;
