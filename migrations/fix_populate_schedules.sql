-- ============================================
-- POBLAR HORARIOS REALISTAS (SIN TEMPORARY TABLES)
-- Compatible con AwardSpace
-- ============================================

SET @ciclo_actual = '2024-2';

-- ============================================
-- PASO 1: Limpiar horarios existentes del ciclo actual
-- ============================================

DELETE h FROM horarios h
INNER JOIN grupos g ON g.id = h.grupo_id
WHERE g.ciclo = @ciclo_actual;

-- ============================================
-- PASO 2: Crear tabla de templates de horarios (NO TEMPORARY)
-- ============================================

DROP TABLE IF EXISTS temp_horarios_template_fix;
CREATE TABLE temp_horarios_template_fix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dia_semana ENUM('lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'),
    hora_inicio TIME,
    hora_fin TIME
);

INSERT INTO temp_horarios_template_fix (dia_semana, hora_inicio, hora_fin) VALUES
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
-- PASO 3: Crear tabla de aulas (NO TEMPORARY)
-- ============================================

DROP TABLE IF EXISTS temp_aulas_fix;
CREATE TABLE temp_aulas_fix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aula VARCHAR(20)
);

INSERT INTO temp_aulas_fix (aula) VALUES
('A-101'), ('A-102'), ('A-103'), ('A-104'), ('A-105'),
('A-201'), ('A-202'), ('A-203'), ('A-204'), ('A-205'),
('B-101'), ('B-102'), ('B-103'), ('B-104'), ('B-105'),
('B-201'), ('B-202'), ('B-203'), ('B-204'), ('B-205'),
('LAB-COMP-1'), ('LAB-COMP-2'), ('LAB-COMP-3'),
('LAB-FIS-1'), ('LAB-QUI-1'), ('AUDITORIO');

-- ============================================
-- PASO 4 CORREGIDO: Asignar primera sesión por grupo
-- ============================================

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT
    g.id AS grupo_id,
    -- Selecciona una plantilla aleatoria por grupo (días lun-mié). La semilla RAND(g.id) garantiza
    -- que las 3 subconsultas elijan la MISMA fila para este grupo.
    (SELECT ht.dia_semana
     FROM temp_horarios_template_fix ht
     WHERE ht.dia_semana IN ('lunes','martes','miércoles')
     ORDER BY RAND(g.id)
     LIMIT 1) AS dia_semana,
    (SELECT ht.hora_inicio
     FROM temp_horarios_template_fix ht
     WHERE ht.dia_semana IN ('lunes','martes','miércoles')
     ORDER BY RAND(g.id)
     LIMIT 1) AS hora_inicio,
    (SELECT ht.hora_fin
     FROM temp_horarios_template_fix ht
     WHERE ht.dia_semana IN ('lunes','martes','miércoles')
     ORDER BY RAND(g.id)
     LIMIT 1) AS hora_fin,
    (SELECT aula FROM temp_aulas_fix ORDER BY RAND(g.id) LIMIT 1) AS aula
FROM grupos g
WHERE g.ciclo = @ciclo_actual
  AND NOT EXISTS (SELECT 1 FROM horarios h WHERE h.grupo_id = g.id);

-- ============================================
-- PASO 5 CORREGIDO: Asignar segunda sesión por grupo
-- Usamos otra semilla RAND(g.id + 100000) para obtener una plantilla distinta
-- (días jue-vie) y reutilizamos el aula de la primera sesión.
-- ============================================

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT
    g.id AS grupo_id,
    (SELECT ht.dia_semana
     FROM temp_horarios_template_fix ht
     WHERE ht.dia_semana IN ('jueves','viernes')
     ORDER BY RAND(g.id + 100000)
     LIMIT 1) AS dia_semana,
    (SELECT ht.hora_inicio
     FROM temp_horarios_template_fix ht
     WHERE ht.dia_semana IN ('jueves','viernes')
     ORDER BY RAND(g.id + 100000)
     LIMIT 1) AS hora_inicio,
    (SELECT ht.hora_fin
     FROM temp_horarios_template_fix ht
     WHERE ht.dia_semana IN ('jueves','viernes')
     ORDER BY RAND(g.id + 100000)
     LIMIT 1) AS hora_fin,
    h1.aula
FROM grupos g
INNER JOIN horarios h1 ON h1.grupo_id = g.id  -- primera sesión ya insertada
WHERE g.ciclo = @ciclo_actual
  -- Solo insertar segunda sesión si actualmente hay exactamente 1 sesión para el grupo
  AND (SELECT COUNT(*) FROM horarios h2 WHERE h2.grupo_id = g.id) = 1;
