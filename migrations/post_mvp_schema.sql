-- Migration: Post-MVP Features
-- Adds support for Planes, Prerrequisitos, Aulas, Kardex, and expanded Roles

SET FOREIGN_KEY_CHECKS = 0;

-- 1. PLANES DE ESTUDIO
CREATE TABLE IF NOT EXISTS `planes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `carrera_id` INT NOT NULL,
    `anio` INT NOT NULL COMMENT 'Año del plan (ej. 2025)',
    `descripcion` TEXT,
    `clave` VARCHAR(50) DEFAULT NULL COMMENT 'Clave interna del plan',
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_planes_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PRERREQUISITOS
CREATE TABLE IF NOT EXISTS `prerrequisitos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `materia_id` INT UNSIGNED NOT NULL,
    `prerrequisito_id` INT UNSIGNED NOT NULL COMMENT 'La materia que debe haber aprobado antes',
    `tipo` ENUM('obligatorio', 'recomendado') DEFAULT 'obligatorio',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_prerrequisito` (`materia_id`, `prerrequisito_id`),
    CONSTRAINT `fk_prerreq_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_prerreq_prev` FOREIGN KEY (`prerrequisito_id`) REFERENCES `materias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. AULAS
CREATE TABLE IF NOT EXISTS `aulas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clave` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Ej. A-101, LAB-COMP',
    `capacidad` INT DEFAULT 30,
    `tipo` ENUM('aula', 'laboratorio', 'auditorio', 'otro') DEFAULT 'aula',
    `recursos_json` TEXT COMMENT 'JSON con lista de recursos: proyector, aire, etc.',
    `ubicacion` VARCHAR(100) DEFAULT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. KARDEX (Historial Académico Consolidado)
CREATE TABLE IF NOT EXISTS `kardex` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alumno_id` INT UNSIGNED NOT NULL,
    `materia_id` INT UNSIGNED NOT NULL,
    `periodo` VARCHAR(20) NOT NULL COMMENT 'Ciclo escolar ej. 2025-1',
    `calificacion` DECIMAL(5,2) DEFAULT NULL,
    `estatus` ENUM('Cursando', 'Aprobada', 'Reprobada', 'Baja', 'Convalidada') DEFAULT 'Cursando',
    `creditos_obtenidos` INT DEFAULT 0,
    `tipo_evaluacion` ENUM('Ordinario', 'Extraordinario', 'Recuperacion', 'Especial') DEFAULT 'Ordinario',
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `observaciones` TEXT,
    PRIMARY KEY (`id`),
    INDEX `idx_kardex_alumno` (`alumno_id`),
    INDEX `idx_kardex_materia` (`materia_id`),
    CONSTRAINT `fk_kardex_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kardex_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ESTADISTICAS / LOGS
CREATE TABLE IF NOT EXISTS `estadisticas_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT UNSIGNED DEFAULT NULL,
    `accion` VARCHAR(50) NOT NULL,
    `tabla` VARCHAR(50) DEFAULT NULL,
    `registro_id` INT DEFAULT NULL,
    `descripcion` TEXT,
    `ip` VARCHAR(45) DEFAULT NULL,
    `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_logs_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. UPDATE USUARIOS TABLE (Expand Roles)
-- Note: ENUM modification requires recreating the column or using ALTER carefully.
-- Since we want to be safe, we will modify the column definition.
ALTER TABLE `usuarios` MODIFY COLUMN `rol` ENUM('admin', 'coordinador', 'profesor', 'secretaria', 'alumno') NOT NULL DEFAULT 'profesor';

-- 7. UPDATE GRUPOS TABLE (Link to Aulas)
ALTER TABLE `grupos` ADD COLUMN IF NOT EXISTS `aula_id` INT UNSIGNED DEFAULT NULL AFTER `aula_default`;
-- Add FK if not exists (checking first to avoid errors if re-running, but simple ADD CONSTRAINT fails if exists)
-- We'll just add the index and constraint blindly, assuming clean run or handling error manually if needed.
-- For safety in this script, we use a stored procedure or just try/catch logic in application, but here is standard SQL:
-- ALTER TABLE `grupos` ADD CONSTRAINT `fk_grupo_aula` FOREIGN KEY (`aula_id`) REFERENCES `aulas`(`id`) ON DELETE SET NULL;
-- (Commented out constraint to avoid error if it already exists, will run it separately or assume fresh apply)

-- Attempt to migrate existing text aulas to new table (Best effort)
-- INSERT IGNORE INTO `aulas` (clave) SELECT DISTINCT aula_default FROM grupos WHERE aula_default IS NOT NULL AND aula_default != '';
-- UPDATE grupos g JOIN aulas a ON g.aula_default = a.clave SET g.aula_id = a.id WHERE g.aula_id IS NULL;

-- 8. UPDATE MATERIAS (Link to Plan)
ALTER TABLE `materias` ADD COLUMN IF NOT EXISTS `plan_id` INT UNSIGNED DEFAULT NULL;
-- ALTER TABLE `materias` ADD CONSTRAINT `fk_materia_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes`(`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
