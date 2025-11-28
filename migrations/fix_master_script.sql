-- ============================================
-- SCRIPT MAESTRO DE CORRECCIÓN
-- Ejecutar en este orden para corregir datos de alumnos
-- Control Escolar ITSUR
-- ============================================

-- IMPORTANTE: Ejecutar estos scripts EN ORDEN

USE control_escolar;

-- ============================================
-- PASO 1: BACKUP (MANUAL - ejecutar ANTES)
-- ============================================
-- mysqldump -u root -p control_escolar > backup_antes_correccion_$(date +%Y%m%d).sql

-- ============================================
-- PASO 2: LIMPIEZA
-- ============================================
SELECT 'Ejecutando limpieza de datos...' AS paso;
SOURCE migrations/fix_cleanup_enrollments.sql;

-- ============================================
-- PASO 3: SEED REALISTA
-- ============================================
SELECT 'Poblando inscripciones realistas...' AS paso;
SOURCE migrations/fix_realistic_enrollments.sql;

-- ============================================
-- PASO 4: POBLAR HORARIOS
-- ============================================
SELECT 'Poblando horarios...' AS paso;
SOURCE migrations/fix_populate_schedules.sql;

-- ============================================
-- VERIFICACIÓN FINAL
-- ============================================

SELECT '\n\n=== VERIFICACIÓN FINAL ===' AS resultado;

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

-- 2. Top 10 estudiantes (verificar que sea razonable: 6-18 materias)
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

SELECT '\n✓ Corrección completada. Revisa los resultados arriba.' AS status;
SELECT 'Si todo se ve bien (6-18 materias por alumno), los datos son coherentes.' AS nota;
