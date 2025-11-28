-- Script para asignar carreras a estudiantes y profesores existentes
-- Esto permite que los filtros por carrera tengan datos útiles


-- Primero, verificamos las carreras disponibles
-- Las carreras típicas en ITSUR son:
-- 1: Ingeniería en Sistemas Computacionales (ISC)
-- 2: Contador Público (CP)
-- 3: Ingeniería Industrial (II)
-- 4: Ingeniería en Gestión Empresarial (IGE)

-- ============================================
-- ASIGNAR CARRERAS A ESTUDIANTES
-- ============================================

-- Asignar ISC (Ingeniería en Sistemas) a estudiantes con matrícula que empiece con 'A0' (primeros)
UPDATE alumnos 
SET carrera_id = (SELECT id FROM carreras WHERE clave = 'ISC' LIMIT 1)
WHERE matricula LIKE 'A0%' AND carrera_id IS NULL
LIMIT 5;

-- Asignar CP (Contador Público) a estudiantes con matrícula que empiece con 'E'
UPDATE alumnos 
SET carrera_id = (SELECT id FROM carreras WHERE clave = 'CP' LIMIT 1)
WHERE matricula LIKE 'E%' AND carrera_id IS NULL;

-- Asignar II (Ingeniería Industrial) a estudiantes con matrícula que empiece con 'Q'
UPDATE alumnos 
SET carrera_id = (SELECT id FROM carreras WHERE clave = 'II' LIMIT 1)
WHERE matricula LIKE 'Q%' AND carrera_id IS NULL;

-- Asignar IGE (Ingeniería en Gestión Empresarial) a algunos estudiantes con 'A0'
UPDATE alumnos 
SET carrera_id = (SELECT id FROM carreras WHERE clave = 'IGE' LIMIT 1)
WHERE matricula LIKE 'A01%' AND carrera_id IS NULL
LIMIT 3;

-- Para el resto sin asignar, distribuir entre las carreras principales
UPDATE alumnos a
SET carrera_id = (
    SELECT id FROM carreras 
    WHERE clave IN ('ISC', 'CP', 'II', 'IGE')
    ORDER BY RAND()
    LIMIT 1
)
WHERE carrera_id IS NULL;

-- ============================================
-- ASIGNAR CARRERAS A PROFESORES
-- ============================================

-- Asignar profesores a ISC (la carrera más grande típicamente)
UPDATE usuarios 
SET carrera_id = (SELECT id FROM carreras WHERE clave = 'ISC' LIMIT 1)
WHERE rol = 'profesor' AND carrera_id IS NULL
LIMIT 4;

-- Asignar profesores a CP
UPDATE usuarios 
SET carrera_id = (SELECT id FROM carreras WHERE clave = 'CP' LIMIT 1)
WHERE rol = 'profesor' AND carrera_id IS NULL
LIMIT 3;

-- Asignar profesores a II
UPDATE usuarios 
SET carrera_id = (SELECT id FROM carreras WHERE clave = 'II' LIMIT 1)
WHERE rol = 'profesor' AND carrera_id IS NULL
LIMIT 2;

-- Asignar profesores a IGE
UPDATE usuarios 
SET carrera_id = (SELECT id FROM carreras WHERE clave = 'IGE' LIMIT 1)
WHERE rol = 'profesor' AND carrera_id IS NULL
LIMIT 2;

-- Distribuir profesores restantes aleatoriamente
UPDATE usuarios u
SET carrera_id = (
    SELECT id FROM carreras 
    WHERE clave IN ('ISC', 'CP', 'II', 'IGE')
    ORDER BY RAND()
    LIMIT 1
)
WHERE rol = 'profesor' AND carrera_id IS NULL;

-- ============================================
-- VERIFICACIÓN
-- ============================================

-- Mostrar distribución de estudiantes por carrera
SELECT 
    c.nombre AS Carrera,
    COUNT(a.id) AS 'Cantidad de Estudiantes'
FROM carreras c
LEFT JOIN alumnos a ON a.carrera_id = c.id
WHERE c.activo = 1
GROUP BY c.id, c.nombre
ORDER BY COUNT(a.id) DESC;

-- Mostrar distribución de profesores por carrera
SELECT 
    c.nombre AS Carrera,
    COUNT(u.id) AS 'Cantidad de Profesores'
FROM carreras c
LEFT JOIN usuarios u ON u.carrera_id = c.id AND u.rol = 'profesor'
WHERE c.activo = 1
GROUP BY c.id, c.nombre
ORDER BY COUNT(u.id) DESC;

-- Mostrar estudiantes sin carrera asignada (debería ser 0)
SELECT COUNT(*) AS 'Estudiantes sin carrera'
FROM alumnos
WHERE carrera_id IS NULL;

-- Mostrar profesores sin carrera asignada (debería ser 0 o muy pocos)
SELECT COUNT(*) AS 'Profesores sin carrera'
FROM usuarios
WHERE rol = 'profesor' AND carrera_id IS NULL;
