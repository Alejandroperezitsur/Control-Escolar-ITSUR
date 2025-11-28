-- ============================================
-- SEED REALISTA DE INSCRIPCIONES
-- Compatible con AwardSpace: sin tablas temporales
-- ============================================

SET @ciclo_actual = '2024-2';

-- Obtener IDs de carreras
SET @isc_id = (SELECT id FROM carreras WHERE clave IN ('ISC','IC') LIMIT 1);
SET @ii_id  = (SELECT id FROM carreras WHERE clave = 'II' LIMIT 1);
SET @ige_id = (SELECT id FROM carreras WHERE clave = 'IGE' LIMIT 1);
SET @cp_id  = (SELECT id FROM carreras WHERE clave = 'CP' LIMIT 1);

-- ============================================
-- SUBCONSULTA base: asignar semestre a alumnos
-- ============================================

-- Esta subconsulta reemplaza temp_alumno_semestre
-- y se usará en todos los INSERT

-- ============================================
-- PASO 1: Inscribir alumnos en materias del semestre actual
-- ============================================

INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final)
SELECT DISTINCT
    tas.alumno_id,
    g.id AS grupo_id,
    NULL, NULL, NULL
FROM (
    SELECT 
        a.id AS alumno_id,
        a.carrera_id,
        CASE WHEN MOD(a.id, 8) = 0 THEN 8 ELSE MOD(a.id, 8) END AS semestre_actual
    FROM alumnos a
    WHERE a.activo = 1
) AS tas
INNER JOIN materias_carrera mc 
    ON mc.carrera_id = tas.carrera_id 
    AND mc.semestre = tas.semestre_actual
INNER JOIN grupos g 
    ON g.materia_id = mc.materia_id
    AND g.ciclo = @ciclo_actual
WHERE NOT EXISTS (
    SELECT 1 FROM calificaciones c2
    WHERE c2.alumno_id = tas.alumno_id
    AND c2.grupo_id = g.id
)
AND g.id = (
    SELECT g2.id FROM grupos g2 
    WHERE g2.materia_id = mc.materia_id AND g2.ciclo = @ciclo_actual 
    LIMIT 1
)
LIMIT 1000;

-- ============================================
-- PASO 2: Inscribir historial de semestres previos
-- ============================================

INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final)
SELECT DISTINCT
    tas.alumno_id,
    g.id AS grupo_id,
    FLOOR(70 + RAND()*25),
    FLOOR(70 + RAND()*25),
    FLOOR(70 + RAND()*30)
FROM (
    SELECT 
        a.id AS alumno_id,
        a.carrera_id,
        CASE WHEN MOD(a.id, 8) = 0 THEN 8 ELSE MOD(a.id, 8) END AS semestre_actual
    FROM alumnos a
    WHERE a.activo = 1
) AS tas
INNER JOIN materias_carrera mc 
    ON mc.carrera_id = tas.carrera_id 
    AND mc.semestre < tas.semestre_actual
INNER JOIN grupos g 
    ON g.materia_id = mc.materia_id
WHERE tas.semestre_actual > 1
    AND g.ciclo IN ('2024-1','2023-2','2023-1')
    AND NOT EXISTS (
        SELECT 1 FROM calificaciones c2
        WHERE c2.alumno_id = tas.alumno_id
        AND c2.grupo_id = g.id
    )
    AND g.id = (
        SELECT g2.id FROM grupos g2
        WHERE g2.materia_id = mc.materia_id AND g2.ciclo = g.ciclo
        LIMIT 1
    )
LIMIT 2000;

-- ============================================
-- PASO 3: Poblar tabla inscripciones
-- ============================================

INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT DISTINCT
    c.alumno_id,
    c.grupo_id,
    g.ciclo,
    CASE 
        WHEN c.final IS NULL THEN 'inscrito'
        WHEN c.final >= 70 THEN 'completado'
        ELSE 'inscrito'
    END
FROM calificaciones c
INNER JOIN grupos g ON g.id = c.grupo_id
WHERE NOT EXISTS (
    SELECT 1 FROM inscripciones i
    WHERE i.alumno_id = c.alumno_id
    AND i.grupo_id = c.grupo_id
    AND i.ciclo = g.ciclo
);

SELECT 'Seed realista completado exitosamente (sin tablas temporales)' AS status;
