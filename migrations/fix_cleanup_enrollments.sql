-- ============================================
-- SCRIPT DE LIMPIEZA Y RESETEO
-- Control Escolar ITSUR
-- ============================================

-- PASO 1: BACKUP DE SEGURIDAD
-- Ejecutar ANTES de este script:
-- mysqldump -u root -p control_escolar calificaciones > backup_calificaciones.sql

-- PASO 2: ANÁLISIS PREVIO (Opcional - para ver el estado actual)
SELECT 
    'Estado Actual' AS reporte,
    COUNT(*) AS total_registros,
    COUNT(DISTINCT alumno_id) AS alumnos_unicos,
    COUNT(DISTINCT grupo_id) AS grupos_unicos
FROM calificaciones;

-- Ver los casos más extremos
SELECT 
    a.matricula,
    CONCAT(a.nombre, ' ', a.apellido) AS alumno,
    COUNT(c.id) AS total_inscripciones
FROM alumnos a
LEFT JOIN calificaciones c ON c.alumno_id = a.id
GROUP BY a.id
ORDER BY COUNT(c.id) DESC
LIMIT 10;

-- PASO 3: RESETEO COMPLETO
-- Limpiamos la tabla de calificaciones para empezar con datos coherentes
TRUNCATE TABLE calificaciones;

-- PASO 4: VERIFICACIÓN
SELECT 
    'Después del Reseteo' AS reporte,
    COUNT(*) AS registros_calificaciones
FROM calificaciones;

-- PASO 5: También limpiar inscripciones si existen datos inconsistentes
TRUNCATE TABLE inscripciones;

SELECT 'Limpieza completada - Listo para seed realista' AS status;
