-- ============================================
-- SEED REALISTA DE INSCRIPCIONES
-- Basado en retícula de 8-9 semestres
-- Control Escolar ITSUR
-- ============================================

-- Variables de configuración
SET @ciclo_actual = '2024-2';

-- Obtener IDs de carreras
SET @isc_id = (SELECT id FROM carreras WHERE clave = 'ISC' OR clave = 'IC' LIMIT 1);
SET @ii_id = (SELECT id FROM carreras WHERE clave = 'II' LIMIT 1);
SET @ige_id = (SELECT id FROM carreras WHERE clave = 'IGE' LIMIT 1);
SET @cp_id = (SELECT id FROM carreras WHERE clave = 'CP' LIMIT 1);

-- ============================================
-- PASO 1: Crear tabla temporal de asignación de semestres
-- ============================================

DROP TEMPORARY TABLE IF EXISTS temp_alumno_semestre;

CREATE TEMPORARY TABLE temp_alumno_semestre AS
SELECT 
    a.id AS alumno_id,
    a.carrera_id,
    -- Distribuir alumnos entre semestres 1-8 basado en su ID
    CASE 
        WHEN MOD(a.id, 8) = 0 THEN 8
        ELSE MOD(a.id, 8)
    END AS semestre_actual
FROM alumnos a
WHERE a.activo = 1;

-- Verificar distribución
SELECT 
    semestre_actual,
    COUNT(*) AS cantidad_alumnos
FROM temp_alumno_semestre
GROUP BY semestre_actual
ORDER BY semestre_actual;

-- ============================================
-- PASO 2: Inscribir alumnos en materias del SEMESTRE ACTUAL
-- ============================================

-- Solo inscribir en grupos del ciclo actual que correspondan a su semestre
INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final)
SELECT DISTINCT
    tas.alumno_id,
    g.id AS grupo_id,
    NULL AS parcial1,
    NULL AS parcial2,
    NULL AS final
FROM temp_alumno_semestre tas
INNER JOIN materias_carrera mc ON mc.carrera_id = tas.carrera_id 
    AND mc.semestre = tas.semestre_actual
INNER JOIN grupos g ON g.materia_id = mc.materia_id 
    AND g.ciclo = @ciclo_actual
WHERE NOT EXISTS (
    SELECT 1 FROM calificaciones c2 
    WHERE c2.alumno_id = tas.alumno_id 
    AND c2.grupo_id = g.id
)
-- Tomar solo UN grupo por materia para evitar duplicados
AND g.id = (
    SELECT g2.id 
    FROM grupos g2 
    WHERE g2.materia_id = mc.materia_id 
    AND g2.ciclo = @ciclo_actual
    LIMIT 1
)
LIMIT 1000;

-- ============================================
-- PASO 3: Agregar HISTORIAL (semestres previos ya aprobados)
-- ============================================

-- Para estudiantes de semestres 2 en adelante, agregar calificaciones de semestres anteriores
INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final)
SELECT DISTINCT
    tas.alumno_id,
    g.id AS grupo_id,
    FLOOR(70 + (RAND() * 25)) AS parcial1,  -- 70-95
    FLOOR(70 + (RAND() * 25)) AS parcial2,  -- 70-95
    FLOOR(70 + (RAND() * 30)) AS final       -- 70-100
FROM temp_alumno_semestre tas
INNER JOIN materias_carrera mc ON mc.carrera_id = tas.carrera_id 
    AND mc.semestre < tas.semestre_actual  -- Solo semestres PREVIOS
INNER JOIN grupos g ON g.materia_id = mc.materia_id
WHERE tas.semestre_actual > 1  -- Solo para estudiantes que no están en 1er semestre
    AND g.ciclo IN ('2024-1', '2023-2', '2023-1')  -- Ciclos pasados
    AND NOT EXISTS (
        SELECT 1 FROM calificaciones c2 
        WHERE c2.alumno_id = tas.alumno_id 
        AND c2.grupo_id = g.id
    )
    -- Un grupo por materia
    AND g.id = (
        SELECT g2.id 
        FROM grupos g2 
        WHERE g2.materia_id = mc.materia_id 
        AND g2.ciclo = g.ciclo
        LIMIT 1
    )
LIMIT 2000;

-- ============================================
-- PASO 4: Poblar tabla de inscripciones (complementaria)
-- ============================================

-- Sincronizar inscripciones basadas en calificaciones del ciclo actual
INSERT INTO inscripciones (alumno_id, grupo_id, ciclo, estatus)
SELECT DISTINCT
    c.alumno_id,
    c.grupo_id,
    g.ciclo,
    CASE 
        WHEN c.final IS NULL THEN 'inscrito'
        WHEN c.final >= 70 THEN 'completado'
        ELSE 'inscrito'
    END AS estatus
FROM calificaciones c
INNER JOIN grupos g ON g.id = c.grupo_id
WHERE NOT EXISTS (
    SELECT 1 FROM inscripciones i
    WHERE i.alumno_id = c.alumno_id
    AND i.grupo_id = c.grupo_id
    AND i.ciclo = g.ciclo
);

-- ============================================
-- VERIFICACIÓN
-- ============================================

-- Ver distribución de inscripciones por alumno
SELECT 
    'Distribución de Inscripciones' AS reporte;

SELECT 
    CONCAT(a.nombre, ' ', a.apellido) AS alumno,
    a.matricula,
    c.nombre AS carrera,
    COUNT(DISTINCT cal.grupo_id) AS total_grupos_inscritos,
    COUNT(DISTINCT CASE WHEN g.ciclo = @ciclo_actual THEN cal.grupo_id END) AS grupos_ciclo_actual,
    COUNT(DISTINCT CASE WHEN cal.final IS NOT NULL THEN cal.grupo_id END) AS materias_completadas
FROM alumnos a
LEFT JOIN carreras c ON c.id = a.carrera_id
LEFT JOIN calificaciones cal ON cal.alumno_id = a.id
LEFT JOIN grupos g ON g.id = cal.grupo_id
WHERE a.activo = 1
GROUP BY a.id
ORDER BY total_grupos_inscritos DESC
LIMIT 20;

-- Contar por semestre actual
SELECT 
    tas.semestre_actual,
    COUNT(DISTINCT tas.alumno_id) AS alumnos,
    AVG(inscripciones_actuales) AS promedio_inscripciones
FROM temp_alumno_semestre tas
LEFT JOIN (
    SELECT 
        c.alumno_id,
        COUNT(DISTINCT c.grupo_id) AS inscripciones_actuales
    FROM calificaciones c
    INNER JOIN grupos g ON g.id = c.grupo_id
    WHERE g.ciclo = @ciclo_actual
    GROUP BY c.alumno_id
) stats ON stats.alumno_id = tas.alumno_id
GROUP BY tas.semestre_actual
ORDER BY tas.semestre_actual;

-- Limpiar tabla temporal
DROP TEMPORARY TABLE IF EXISTS temp_alumno_semestre;

SELECT 'Seed realista completado exitosamente' AS status;
