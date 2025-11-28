-- Poblar horarios para grupos con aulas y horarios realistas
-- Sistema de Control Escolar ITSUR

-- ============================================
-- DEFINICIÓN DE AULAS DISPONIBLES
-- ============================================
-- Edificio A: Aulas teóricas (8 salones)
-- Edificio B: Aulas teóricas (6 salones)
-- Edificio C: Laboratorios (4 labs)
-- Edificio D: Aulas mixtas (4 salones)

-- ============================================
-- PASO 1: Asignar aulas por defecto a los grupos
-- ============================================

-- Asignar aulas del Edificio A (A101-A302)
UPDATE grupos SET aula_default = 'A101' WHERE id % 22 = 1;
UPDATE grupos SET aula_default = 'A102' WHERE id % 22 = 2;
UPDATE grupos SET aula_default = 'A103' WHERE id % 22 = 3;
UPDATE grupos SET aula_default = 'A201' WHERE id % 22 = 4;
UPDATE grupos SET aula_default = 'A202' WHERE id % 22 = 5;
UPDATE grupos SET aula_default = 'A203' WHERE id % 22 = 6;
UPDATE grupos SET aula_default = 'A301' WHERE id % 22 = 7;
UPDATE grupos SET aula_default = 'A302' WHERE id % 22 = 8;

-- Asignar aulas del Edificio B (B101-B302)
UPDATE grupos SET aula_default = 'B101' WHERE id % 22 = 9;
UPDATE grupos SET aula_default = 'B102' WHERE id % 22 = 10;
UPDATE grupos SET aula_default = 'B201' WHERE id % 22 = 11;
UPDATE grupos SET aula_default = 'B202' WHERE id % 22 = 12;
UPDATE grupos SET aula_default = 'B301' WHERE id % 22 = 13;
UPDATE grupos SET aula_default = 'B302' WHERE id % 22 = 14;

-- Asignar laboratorios a materias de programación/computación
UPDATE grupos g
JOIN materias m ON m.id = g.materia_id
SET g.aula_default = 'LAB-COMP1'
WHERE m.clave LIKE 'INF%' AND g.id % 4 = 0;

UPDATE grupos g
JOIN materias m ON m.id = g.materia_id
SET g.aula_default = 'LAB-COMP2'
WHERE m.clave LIKE 'INF%' AND g.id % 4 = 1;

-- Asignar aulas del Edificio D para el resto
UPDATE grupos SET aula_default = 'D101' WHERE aula_default IS NULL AND id % 4 = 0;
UPDATE grupos SET aula_default = 'D102' WHERE aula_default IS NULL AND id % 4 = 1;
UPDATE grupos SET aula_default = 'D201' WHERE aula_default IS NULL AND id % 4 = 2;
UPDATE grupos SET aula_default = 'D202' WHERE aula_default IS NULL;

-- ============================================
-- PASO 2: Crear horarios para cada grupo
-- ============================================
-- Cada grupo tendrá 2-3 sesiones por semana
-- Horarios en bloques de 90 minutos

-- LUNES Y MIÉRCOLES (7:00-8:30) - Grupos impares matutinos
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'lunes',
    '07:00:00',
    '08:30:00',
    aula_default
FROM grupos
WHERE id % 6 = 1;

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'miércoles',
    '07:00:00',
    '08:30:00',
    aula_default
FROM grupos
WHERE id % 6 = 1;

-- LUNES Y MIÉRCOLES (8:40-10:10)
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'lunes',
    '08:40:00',
    '10:10:00',
    aula_default
FROM grupos
WHERE id % 6 = 2;

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'miércoles',
    '08:40:00',
    '10:10:00',
    aula_default
FROM grupos
WHERE id % 6 = 2;

-- MARTES Y JUEVES (10:20-11:50)
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'martes',
    '10:20:00',
    '11:50:00',
    aula_default
FROM grupos
WHERE id % 6 = 3;

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'jueves',
    '10:20:00',
    '11:50:00',
    aula_default
FROM grupos
WHERE id % 6 = 3;

-- MARTES Y JUEVES (12:00-13:30) - Horario vespertino temprano
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'martes',
    '12:00:00',
    '13:30:00',
    aula_default
FROM grupos
WHERE id % 6 = 4;

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'jueves',
    '12:00:00',
    '13:30:00',
    aula_default
FROM grupos
WHERE id % 6 = 4;

-- LUNES Y MIÉRCOLES (15:20-16:50) - Horario vespertino
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'lunes',
    '15:20:00',
    '16:50:00',
    aula_default
FROM grupos
WHERE id % 6 = 5;

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'miércoles',
    '15:20:00',
    '16:50:00',
    aula_default
FROM grupos
WHERE id % 6 = 5;

-- MARTES Y JUEVES (17:00-18:30) - Horario nocturno
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'martes',
    '17:00:00',
    '18:30:00',
    aula_default
FROM grupos
WHERE id % 6 = 0;

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'jueves',
    '17:00:00',
    '18:30:00',
    aula_default
FROM grupos
WHERE id % 6 = 0;

-- VIERNES (sesión extra para algunos grupos)
INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
SELECT 
    id,
    'viernes',
    '08:40:00',
    '10:10:00',
    aula_default
FROM grupos
WHERE id % 3 = 0;

-- ============================================
-- VERIFICACIÓN
-- ============================================

-- Mostrar distribución de horarios por día
SELECT 
    dia_semana AS Día,
    COUNT(*) AS 'Sesiones Programadas'
FROM horarios
GROUP BY dia_semana
ORDER BY FIELD(dia_semana, 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado');

-- Mostrar grupos con sus horarios
SELECT 
    g.nombre AS Grupo,
    m.nombre AS Materia,
    g.aula_default AS Aula,
    GROUP_CONCAT(
        CONCAT(h.dia_semana, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), '-', TIME_FORMAT(h.hora_fin, '%H:%i'))
        ORDER BY FIELD(h.dia_semana, 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado')
        SEPARATOR ', '
    ) AS Horarios
FROM grupos g
JOIN materias m ON m.id = g.materia_id
LEFT JOIN horarios h ON h.grupo_id = g.id
GROUP BY g.id, g.nombre, m.nombre, g.aula_default
ORDER BY g.nombre
LIMIT 20;

-- Mostrar uso de aulas
SELECT 
    aula AS Aula,
    COUNT(DISTINCT grupo_id) AS 'Grupos Asignados',
    COUNT(*) AS 'Sesiones Totales'
FROM horarios
GROUP BY aula
ORDER BY aula;

-- Grupos sin horarios (debería ser 0)
SELECT COUNT(*) AS 'Grupos sin horarios'
FROM grupos g
WHERE NOT EXISTS (SELECT 1 FROM horarios h WHERE h.grupo_id = g.id);
