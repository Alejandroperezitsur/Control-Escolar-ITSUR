-- ============================================
-- POBLAR HORARIOS REALISTAS
-- Asignar días, horas y aulas a grupos activos
-- Control Escolar ITSUR
-- ============================================

SET @ciclo_actual = '2024-2';

-- ============================================
-- PASO 1: Limpiar horarios existentes del ciclo actual
-- ============================================

DELETE h FROM horarios h
INNER JOIN grupos g ON g.id = h.grupo_id
WHERE g.ciclo = @ciclo_actual;

-- ============================================
-- PASO 2: Crear templates de horarios
-- ============================================

DROP TEMPORARY TABLE IF EXISTS temp_horarios_template;
CREATE TEMPORARY TABLE temp_horarios_template (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dia_semana ENUM('lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'),
    hora_inicio TIME,
    hora_fin TIME
);

-- Bloques de 90 minutos típicos de universidad
INSERT INTO temp_horarios_template (dia_semana, hora_inicio, hora_fin) VALUES
('lunes', '07:00:00', '08:30:00'),
('lunes', '08:30:00', '10:00:00'),
('lunes', '10:00:00', '11:30:00'),
('lunes', '14:00:00', '15:30:00'),
('lunes', '15:30:00', '17:00:00'),
('martes', '07:00:00', '08:30:00'),
('martes', '08:30:00', '10:00:00'),
('martes', '10:00:00', '11:30:00'),
('martes', '14:00:00', '15:30:00'),
('martes', '15:30:00', '17:00:00'),
('miércoles', '07:00:00', '08:30:00'),
('miércoles', '08:30:00', '10:00:00'),
('miércoles', '10:00:00', '11:30:00'),
('miércoles', '14:00:00', '15:30:00'),
('miércoles', '15:30:00', '17:00:00'),
('jueves', '07:00:00', '08:30:00'),
('jueves', '08:30:00', '10:00:00'),
('jueves', '10:00:00', '11:30:00'),
('jueves', '14:00:00', '15:30:00'),
('jueves', '15:30:00', '17:00:00'),
('viernes', '07:00:00', '08:30:00'),
('viernes', '08:30:00', '10:00:00'),
('viernes', '10:00:00', '11:30:00'),
('viernes', '14:00:00', '15:30:00'),
('viernes', '15:30:00', '17:00:00'),
('sábado', '07:00:00', '08:30:00'),
('sábado', '08:30:00', '10:00:00'),
('sábado', '10:00:00', '11:30:00');

-- ============================================
-- PASO 3: Crear lista de aulas
-- ============================================

DROP TEMPORARY TABLE IF EXISTS temp_aulas;
CREATE TEMPORARY TABLE temp_aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aula VARCHAR(20)
);

INSERT INTO temp_aulas (aula) VALUES
('A-101'), ('A-102'), ('A-103'), ('A-104'), ('A-105'),
('A-201'), ('A-202'), ('A-203'), ('A-204'), ('A-205'),
('B-101'), ('B-102'), ('B-103'), ('B-104'), ('B-105'),
('B-201'), ('B-202'), ('B-203'), ('B-204'), ('B-205'),
('LAB-COMP-1'), ('LAB-COMP-2'), ('LAB-COMP-3'),
('LAB-FIS-1'), ('LAB-QUI-1'), ('AUDITORIO');

-- ============================================
-- PASO 4: Asignar horarios a grupos del ciclo actual
-- Cada grupo tiene 2 sesiones por semana
-- ============================================

-- Insertar primera sesión por grupo
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    g.id AS grupo_id,
    ht.dia_semana,
    ht.hora_inicio,
    ht.hora_fin,
    (SELECT aula FROM temp_aulas ORDER BY RAND() LIMIT 1) AS aula
FROM grupos g
CROSS JOIN (
    -- Seleccionar horarios para primera sesión (días lunes, martes, miércoles)
    SELECT * FROM temp_horarios_template
    WHERE dia_semana IN ('lunes', 'martes', 'miércoles')
    ORDER BY RAND()
    LIMIT 1
) AS ht
WHERE g.ciclo = @ciclo_actual
AND NOT EXISTS (
    SELECT 1 FROM horarios h 
    WHERE h.grupo_id = g.id
);

-- Insertar segunda sesión por grupo  
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    g.id AS grupo_id,
    ht.dia_semana,
    ht.hora_inicio,
    ht.hora_fin,
    h1.aula  -- Usar la misma aula que la primera sesión
FROM grupos g
CROSS JOIN (
    -- Seleccionar horarios para segunda sesión (días jueves, viernes)
    SELECT * FROM temp_horarios_template
    WHERE dia_semana IN ('jueves', 'viernes')
    ORDER BY RAND()
    LIMIT 1
) AS ht
INNER JOIN horarios h1 ON h1.grupo_id = g.id
WHERE g.ciclo = @ciclo_actual
AND (
    SELECT COUNT(*) FROM horarios h2 WHERE h2.grupo_id = g.id
) = 1  -- Solo si tiene exactamente 1 sesión
LIMIT (SELECT COUNT(*) FROM grupos WHERE ciclo = @ciclo_actual);

-- ============================================
-- VERIFICACIÓN
-- ============================================

SELECT 
    'Horarios Asignados' AS reporte;

-- Ver cuántos grupos tienen horarios
SELECT 
    COUNT(DISTINCT g.id) AS total_grupos_ciclo_actual,
    COUNT(DISTINCT h.grupo_id) AS grupos_con_horario,
    COUNT(h.id) AS total_sesiones_horario
FROM grupos g
LEFT JOIN horarios h ON h.grupo_id = g.id
WHERE g.ciclo = @ciclo_actual;

-- Ver distribución de horarios por día
SELECT 
    h.dia_semana,
    COUNT(*) AS sesiones
FROM horarios h
INNER JOIN grupos g ON g.id = h.grupo_id
WHERE g.ciclo = @ciclo_actual
GROUP BY h.dia_semana
ORDER BY FIELD(h.dia_semana, 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado');

-- Ejemplo de horario de un grupo
SELECT 
    g.nombre AS grupo,
    m.nombre AS materia,
    h.dia_semana,
    h.hora_inicio,
    h.hora_fin,
    h.aula
FROM horarios h
INNER JOIN grupos g ON g.id = h.grupo_id
INNER JOIN materias m ON m.id = g.materia_id
WHERE g.ciclo = @ciclo_actual
LIMIT 20;

-- Limpiar tablas temporales
DROP TEMPORARY TABLE IF EXISTS temp_horarios_template;
DROP TEMPORARY TABLE IF EXISTS temp_aulas;

SELECT 'Población de horarios completada exitosamente' AS status;
