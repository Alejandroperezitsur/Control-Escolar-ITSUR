-- ============================================================================
-- MIGRATION: Transform to Real University Control System (Like SICEnet)
-- ============================================================================
-- This migration transforms the system from basic 2-partial grading to a
-- comprehensive university control system with:
-- - Unit-based grading (1-10 units per subject)
-- - Kardex (complete academic history)
-- - Academic Load tracking
-- - Credits system
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- BACKUP EXISTING DATA
-- ============================================================================

-- Backup old calificaciones table (if exists)
CREATE TABLE IF NOT EXISTS calificaciones_backup_old AS 
SELECT * FROM calificaciones;

-- ============================================================================
-- DROP OLD GRADING STRUCTURE
-- ============================================================================

DROP TABLE IF EXISTS calificaciones;

-- ============================================================================
-- NEW GRADING TABLES
-- ============================================================================

-- Table: calificaciones_unidades
-- Stores individual unit grades (1-10 per subject)
CREATE TABLE IF NOT EXISTS calificaciones_unidades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    inscripcion_id INT UNSIGNED NOT NULL,
    unidad_num TINYINT NOT NULL COMMENT 'Unit number 1-10',
    calificacion DECIMAL(5,2) DEFAULT NULL COMMENT 'Grade 0-100, NULL or 0 = pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id) ON DELETE CASCADE,
    UNIQUE KEY uk_inscripcion_unidad (inscripcion_id, unidad_num),
    INDEX idx_inscripcion (inscripcion_id),
    INDEX idx_unidad (unidad_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Unit-based grades for each enrollment';

-- Table: calificaciones_finales
-- Summary table with final grades and status per inscription
CREATE TABLE IF NOT EXISTS calificaciones_finales (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    inscripcion_id INT UNSIGNED NOT NULL,
    calificacion_final DECIMAL(5,2) DEFAULT NULL COMMENT 'Final exam grade',
    promedio_unidades DECIMAL(5,2) DEFAULT NULL COMMENT 'Average of all units',
    promedio_general DECIMAL(5,2) DEFAULT NULL COMMENT 'Overall average (units + final)',
    estatus ENUM('cursando', 'aprobado', 'reprobado', 'extraordinario', 'complemento') DEFAULT 'cursando',
    tipo_acreditacion ENUM('ordinario', 'extraordinario', 'complemento') DEFAULT 'ordinario',
    periodo_acreditacion VARCHAR(20) DEFAULT NULL COMMENT 'e.g., AGO-DIC 2022, ENE-JUN 2023',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id) ON DELETE CASCADE,
    UNIQUE KEY uk_inscripcion (inscripcion_id),
    INDEX idx_estatus (estatus),
    INDEX idx_tipo_acreditacion (tipo_acreditacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Final grades and status summary per enrollment';

-- ============================================================================
-- UPDATE MATERIAS TABLE
-- ============================================================================

-- Add num_unidades if not exists (number of units to evaluate, default 10)
SET @query = (
    SELECT IF(
        COUNT(*) > 0,
        'SELECT "Column num_unidades already exists" AS message',
        'ALTER TABLE materias ADD COLUMN num_unidades TINYINT NOT NULL DEFAULT 10 AFTER num_parciales'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'materias'
    AND COLUMN_NAME = 'num_unidades'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure creditos column exists with proper default
SET @query = (
    SELECT IF(
        COUNT(*) > 0,
        'SELECT "Column creditos already exists" AS message',
        'ALTER TABLE materias ADD COLUMN creditos INT NOT NULL DEFAULT 5 AFTER num_unidades'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'materias'
    AND COLUMN_NAME = 'creditos'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure tipo column exists
SET @query = (
    SELECT IF(
        COUNT(*) > 0,
        'SELECT "Column tipo already exists" AS message',
        'ALTER TABLE materias ADD COLUMN tipo ENUM(\'basica\', \'especialidad\', \'residencia\') DEFAULT \'basica\' AFTER creditos'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'materias'
    AND COLUMN_NAME = 'tipo'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- UPDATE INSCRIPCIONES TABLE
-- ============================================================================

-- Add semestre_cursado column (tracks which semester the student was in when enrolled)
SET @query = (
    SELECT IF(
        COUNT(*) > 0,
        'SELECT "Column semestre_cursado already exists" AS message',
        'ALTER TABLE inscripciones ADD COLUMN semestre_cursado TINYINT DEFAULT NULL AFTER estatus'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'inscripciones'
    AND COLUMN_NAME = 'semestre_cursado'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- CREATE VIEWS FOR KARDEX AND ACADEMIC LOAD
-- ============================================================================

-- View: view_kardex
-- Complete academic history for each student
DROP VIEW IF EXISTS view_kardex;

CREATE VIEW view_kardex AS
SELECT 
    a.id as alumno_id,
    a.matricula,
    a.nombre,
    a.apellido,
    a.carrera_id,
    c.nombre as carrera_nombre,
    i.id as inscripcion_id,
    m.id as materia_id,
    m.nombre as materia_nombre,
    m.clave as materia_clave,
    m.creditos,
    g.nombre as grupo,
    g.ciclo,
    COALESCE(mc.semestre, i.semestre_cursado, 1) as semestre,
    cf.promedio_unidades,
    cf.calificacion_final,
    cf.promedio_general,
    cf.estatus,
    cf.tipo_acreditacion,
    cf.periodo_acreditacion,
    CASE 
        WHEN cf.promedio_general IS NULL THEN 'Sin Calificar'
        WHEN cf.promedio_general >= 90 THEN 'Excelente'
        WHEN cf.promedio_general >= 80 THEN 'Notable'
        WHEN cf.promedio_general >= 70 THEN 'Bueno'
        WHEN cf.promedio_general >= 60 THEN 'Suficiente'
        ELSE 'No Acreditado'
    END as nivel_desempeno,
    i.fecha_inscripcion,
    i.estatus as estatus_inscripcion
FROM alumnos a
JOIN inscripciones i ON i.alumno_id = a.id
JOIN grupos g ON g.id = i.grupo_id
JOIN materias m ON m.id = g.materia_id
LEFT JOIN carreras c ON c.id = a.carrera_id
LEFT JOIN materias_carrera mc ON mc.materia_id = m.id AND mc.carrera_id = a.carrera_id
LEFT JOIN calificaciones_finales cf ON cf.inscripcion_id = i.id
ORDER BY a.id, semestre, i.fecha_inscripcion;

-- View: view_carga_academica
-- Current semester academic load with schedules
DROP VIEW IF EXISTS view_carga_academica;

CREATE VIEW view_carga_academica AS
SELECT 
    a.id as alumno_id,
    a.matricula,
    a.nombre as alumno_nombre,
    a.apellido as alumno_apellido,
    m.id as materia_id,
    m.nombre as materia_nombre,
    m.clave as materia_clave,
    m.creditos,
    g.id as grupo_id,
    g.nombre as grupo_nombre,
    g.ciclo,
    u.id as profesor_id,
    u.nombre as profesor_nombre,
    GROUP_CONCAT(
        DISTINCT CONCAT(
            UPPER(SUBSTRING(h.dia_semana, 1, 1)),
            LOWER(SUBSTRING(h.dia_semana, 2)),
            ' ',
            TIME_FORMAT(h.hora_inicio, '%H:%i'),
            '-',
            TIME_FORMAT(h.hora_fin, '%H:%i'),
            ' ',
            COALESCE(h.aula, 'Sin asignar')
        )
        ORDER BY FIELD(h.dia_semana, 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado')
        SEPARATOR '; '
    ) as horarios,
    i.semestre_cursado
FROM alumnos a
JOIN inscripciones i ON i.alumno_id = a.id AND i.estatus = 'inscrito'
JOIN grupos g ON g.id = i.grupo_id
JOIN materias m ON m.id = g.materia_id
JOIN usuarios u ON u.id = g.profesor_id
LEFT JOIN horarios h ON h.grupo_id = g.id
GROUP BY a.id, m.id, g.id, u.id, i.semestre_cursado;

-- View: view_estadisticas_alumno
-- Student statistics for Kardex summary
DROP VIEW IF EXISTS view_estadisticas_alumno;

CREATE VIEW view_estadisticas_alumno AS
SELECT 
    a.id as alumno_id,
    a.matricula,
    a.nombre,
    a.apellido,
    a.carrera_id,
    c.nombre as carrera_nombre,
    c.creditos_totales as creditos_requeridos,
    COUNT(DISTINCT i.id) as total_materias_cursadas,
    SUM(CASE WHEN cf.estatus = 'aprobado' THEN 1 ELSE 0 END) as materias_aprobadas,
    SUM(CASE WHEN cf.estatus = 'reprobado' THEN 1 ELSE 0 END) as materias_reprobadas,
    SUM(CASE WHEN cf.estatus IN ('aprobado') THEN m.creditos ELSE 0 END) as creditos_completados,
    ROUND(
        (SUM(CASE WHEN cf.estatus = 'aprobado' THEN m.creditos ELSE 0 END) / c.creditos_totales) * 100,
        2
    ) as porcentaje_avance,
    ROUND(
        AVG(CASE WHEN cf.promedio_general IS NOT NULL AND cf.estatus IN ('aprobado', 'reprobado') 
            THEN cf.promedio_general 
            ELSE NULL 
        END),
        2
    ) as promedio_general,
    MAX(COALESCE(i.semestre_cursado, mc.semestre, 1)) as semestre_actual
FROM alumnos a
LEFT JOIN carreras c ON c.id = a.carrera_id
LEFT JOIN inscripciones i ON i.alumno_id = a.id
LEFT JOIN grupos g ON g.id = i.grupo_id
LEFT JOIN materias m ON m.id = g.materia_id
LEFT JOIN materias_carrera mc ON mc.materia_id = m.id AND mc.carrera_id = a.carrera_id
LEFT JOIN calificaciones_finales cf ON cf.inscripcion_id = i.id
GROUP BY a.id, c.id;

-- ============================================================================
-- MIGRATE EXISTING DATA (if any)
-- ============================================================================

-- Migrate old parcial grades to new unit system
-- We'll convert parcial1 and parcial2 to units 1 and 2, and final to calificacion_final
-- Only if backup table exists and has data

SET @table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'calificaciones_backup_old'
);

-- If backup exists, migrate data
SET @migrate_query = IF(
    @table_exists > 0,
    'INSERT INTO calificaciones_finales (inscripcion_id, calificacion_final, promedio_unidades, promedio_general, estatus)
     SELECT 
         (SELECT i.id FROM inscripciones i WHERE i.alumno_id = c.alumno_id AND i.grupo_id = c.grupo_id LIMIT 1) as inscripcion_id,
         c.final as calificacion_final,
         ROUND((COALESCE(c.parcial1, 0) + COALESCE(c.parcial2, 0)) / 2, 2) as promedio_unidades,
         c.promedio as promedio_general,
         CASE 
             WHEN c.promedio >= 70 THEN ''aprobado''
             WHEN c.promedio < 70 AND c.promedio > 0 THEN ''reprobado''
             ELSE ''cursando''
         END as estatus
     FROM calificaciones_backup_old c
     WHERE (SELECT i.id FROM inscripciones i WHERE i.alumno_id = c.alumno_id AND i.grupo_id = c.grupo_id LIMIT 1) IS NOT NULL
     ON DUPLICATE KEY UPDATE 
         calificacion_final = VALUES(calificacion_final),
         promedio_unidades = VALUES(promedio_unidades),
         promedio_general = VALUES(promedio_general),
         estatus = VALUES(estatus)',
    'SELECT "No backup data to migrate" as message'
);

PREPARE migrate_stmt FROM @migrate_query;
EXECUTE migrate_stmt;
DEALLOCATE PREPARE migrate_stmt;

-- ============================================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================================

-- Additional indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_materias_creditos ON materias(creditos);
CREATE INDEX IF NOT EXISTS idx_materias_tipo ON materias(tipo);
CREATE INDEX IF NOT EXISTS idx_inscripciones_semestre ON inscripciones(semestre_cursado);
CREATE INDEX IF NOT EXISTS idx_calificaciones_finales_estatus_tipo ON calificaciones_finales(estatus, tipo_acreditacion);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================

SELECT 'Migration completed successfully!' as status,
       (SELECT COUNT(*) FROM calificaciones_unidades) as total_unit_grades,
       (SELECT COUNT(*) FROM calificaciones_finales) as total_final_grades;
