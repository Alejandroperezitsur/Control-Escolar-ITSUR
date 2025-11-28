-- Poblar inscripciones de estudiantes en grupos
-- Asigna estudiantes a grupos de manera realista según su carrera

-- ============================================
-- PASO 1: Obtener información de grupos y estudiantes
-- ============================================

-- Primero, vamos a asignar estudiantes de ISC (Ingeniería en Sistemas)
-- a grupos de materias relacionadas con sistemas/programación

-- ISC - Grupos de Programación y Bases de Datos
INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT 
    a.id,
    g.id,
    g.ciclo,
    'inscrito'
FROM alumnos a
CROSS JOIN grupos g
JOIN materias m ON m.id = g.materia_id
WHERE a.carrera_id = (SELECT id FROM carreras WHERE clave = 'ISC' LIMIT 1)
  AND m.clave IN ('INF101', 'INF201')
  AND NOT EXISTS (
    SELECT 1 FROM inscripciones i 
    WHERE i.alumno_id = a.id AND i.grupo_id = g.id AND i.ciclo = g.ciclo
  )
LIMIT 50;

-- ISC - Asignar a grupos de matemáticas
INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT 
    a.id,
    g.id,
    g.ciclo,
    'inscrito'
FROM alumnos a
CROSS JOIN grupos g
JOIN materias m ON m.id = g.materia_id
WHERE a.carrera_id = (SELECT id FROM carreras WHERE clave = 'ISC' LIMIT 1)
  AND m.clave LIKE 'MAT%'
  AND NOT EXISTS (
    SELECT 1 FROM inscripciones i 
    WHERE i.alumno_id = a.id AND i.grupo_id = g.id AND i.ciclo = g.ciclo
  )
LIMIT 50;

-- ============================================
-- PASO 2: Contador Público (CP) - Asignar a grupos de contabilidad
-- ============================================

INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT 
    a.id,
    g.id,
    g.ciclo,
    'inscrito'
FROM alumnos a
CROSS JOIN grupos g
WHERE a.carrera_id = (SELECT id FROM carreras WHERE clave = 'CP' LIMIT 1)
  AND NOT EXISTS (
    SELECT 1 FROM inscripciones i 
    WHERE i.alumno_id = a.id AND i.grupo_id = g.id AND i.ciclo = g.ciclo
  )
LIMIT 50;

-- ============================================
-- PASO 3: Ingeniería Industrial (II)
-- ============================================

INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT 
    a.id,
    g.id,
    g.ciclo,
    'inscrito'
FROM alumnos a
CROSS JOIN grupos g
WHERE a.carrera_id = (SELECT id FROM carreras WHERE clave = 'II' LIMIT 1)
  AND NOT EXISTS (
    SELECT 1 FROM inscripciones i 
    WHERE i.alumno_id = a.id AND i.grupo_id = g.id AND i.ciclo = g.ciclo
  )
LIMIT 50;

-- ============================================
-- PASO 4: Ingeniería en Gestión Empresarial (IGE)
-- ============================================

INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT 
    a.id,
    g.id,
    g.ciclo,
    'inscrito'
FROM alumnos a
CROSS JOIN grupos g
WHERE a.carrera_id = (SELECT id FROM carreras WHERE clave = 'IGE' LIMIT 1)
  AND NOT EXISTS (
    SELECT 1 FROM inscripciones i 
    WHERE i.alumno_id = a.id AND i.grupo_id = g.id AND i.ciclo = g.ciclo
  )
LIMIT 50;

-- ============================================
-- PASO 5: Asegurar que todos los estudiantes tengan al menos 4 inscripciones
-- ============================================

-- Para estudiantes con menos de 4 inscripciones, asignar grupos aleatorios
INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT 
    a.id,
    g.id,
    g.ciclo,
    'inscrito'
FROM alumnos a
CROSS JOIN grupos g
WHERE (
    SELECT COUNT(*) 
    FROM inscripciones i 
    WHERE i.alumno_id = a.id
) < 4
AND NOT EXISTS (
    SELECT 1 FROM inscripciones i 
    WHERE i.alumno_id = a.id AND i.grupo_id = g.id AND i.ciclo = g.ciclo
)
ORDER BY RAND()
LIMIT 200;

-- ============================================
-- PASO 6: Asegurar que todos los grupos tengan estudiantes
-- ============================================

-- Para grupos sin estudiantes, asignar estudiantes aleatorios
INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT 
    a.id,
    g.id,
    g.ciclo,
    'inscrito'
FROM grupos g
CROSS JOIN alumnos a
WHERE NOT EXISTS (
    SELECT 1 FROM inscripciones i WHERE i.grupo_id = g.id
)
AND NOT EXISTS (
    SELECT 1 FROM inscripciones i 
    WHERE i.alumno_id = a.id AND i.grupo_id = g.id AND i.ciclo = g.ciclo
)
ORDER BY RAND()
LIMIT 100;

-- ============================================
-- VERIFICACIÓN
-- ============================================

-- Mostrar distribución de inscripciones por grupo
SELECT 
    g.nombre AS Grupo,
    m.nombre AS Materia,
    COUNT(i.id) AS 'Estudiantes Inscritos'
FROM grupos g
LEFT JOIN inscripciones i ON i.grupo_id = g.id
JOIN materias m ON m.id = g.materia_id
GROUP BY g.id, g.nombre, m.nombre
ORDER BY COUNT(i.id) DESC;

-- Mostrar distribución de inscripciones por estudiante
SELECT 
    CONCAT(a.nombre, ' ', a.apellido) AS Estudiante,
    a.matricula,
    c.nombre AS Carrera,
    COUNT(i.id) AS 'Grupos Inscritos'
FROM alumnos a
LEFT JOIN inscripciones i ON i.alumno_id = a.id
LEFT JOIN carreras c ON c.id = a.carrera_id
GROUP BY a.id, a.nombre, a.apellido, a.matricula, c.nombre
ORDER BY COUNT(i.id) DESC
LIMIT 20;

-- Estudiantes sin inscripciones (debería ser 0 o muy pocos)
SELECT COUNT(*) AS 'Estudiantes sin inscripciones'
FROM alumnos a
WHERE NOT EXISTS (SELECT 1 FROM inscripciones i WHERE i.alumno_id = a.id);

-- Grupos sin estudiantes (debería ser 0 o muy pocos)
SELECT COUNT(*) AS 'Grupos sin estudiantes'
FROM grupos g
WHERE NOT EXISTS (SELECT 1 FROM inscripciones i WHERE i.grupo_id = g.id);
