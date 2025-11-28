-- ============================================
-- SCRIPT MAESTRO DE CORRECCIÓN (COMPATIBLE AWARDSPACE)
-- Control Escolar ITSUR
-- ============================================

-- ============================================
-- PASO 1: Seleccionar base de datos
-- ============================================


-- ============================================
-- PASO 2: AVISO SOBRE BACKUP
-- ============================================

SELECT 'REALIZA UN BACKUP MANUAL DESDE PHPMYADMIN ANTES DE CONTINUAR' AS aviso;

-- ============================================
-- PASO 3: LIMPIEZA (EJECUTAR ARCHIVO 1)
-- ============================================

SELECT 'Ahora ejecuta el archivo: fix_cleanup_enrollments.sql' AS instruccion;

-- Aquí NO se puede usar SOURCE porque AwardSpace no lo soporta
-- Solo mostramos la instrucción.

-- ============================================
-- PASO 4: SEED REALISTA (ARCHIVO 2)
-- ============================================

SELECT 'Después ejecuta: fix_realistic_enrollments.sql' AS instruccion;

-- ============================================
-- PASO 5: POBLAR HORARIOS (ARCHIVO 3)
-- ============================================

SELECT 'Finalmente ejecuta: fix_populate_schedules_fix.sql (versión compatible)' AS instruccion;

-- ============================================
-- PASO 6: VERIFICACIÓN FINAL
-- ============================================

SELECT '\n=== VERIFICACIÓN FINAL ===' AS resultado;

-- 1. Resumen general
SELECT 
    COUNT(DISTINCT a.id) AS total_alumnos_activos,
    COUNT(DISTINCT c.id) AS total_registros_calificaciones,
    COUNT(DISTINCT c.grupo_id) AS grupos_con_inscripciones,
    COUNT(DISTINCT h.grupo_id) AS grupos_con_horarios
FROM alumnos a
LEFT JOIN calificaciones c ON c.alumno_id = a.id
LEFT JOIN grupos g ON g.id = c.grupo_id
LEFT JOIN horarios h ON h.grupo_id = g.id
WHERE a.activo = 1;

-- 2. Top 10 estudiantes
SELECT 
    a.matricula,
    CONCAT(a.nombre, ' ', a.apellido) AS alumno,
    c.nombre AS carrera,
    COUNT(DISTINCT cal.grupo_id) AS materias_totales,
    COUNT(DISTINCT CASE WHEN g.ciclo = '2024-2' THEN cal.grupo_id END) AS ciclo_actual
FROM alumnos a
LEFT JOIN carreras c ON c.id = a.carrera_id
LEFT JOIN calificaciones cal ON cal.alumno_id = a.id
LEFT JOIN grupos g ON g.id = cal.grupo_id
WHERE a.activo = 1
GROUP BY a.id
ORDER BY materias_totales DESC
LIMIT 10;

-- 3. Verificar horarios
SELECT 
    '\nGrupos con horarios:' AS info,
    COUNT(DISTINCT h.grupo_id) AS grupos_con_horario,
    COUNT(DISTINCT g.id) AS total_grupos_ciclo_actual
FROM grupos g
LEFT JOIN horarios h ON h.grupo_id = g.id
WHERE g.ciclo = '2024-2';

SELECT '\n✓ Corrección completada.' AS status;
