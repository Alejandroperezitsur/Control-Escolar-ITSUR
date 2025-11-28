-- Crear tablas para inscripciones (enrollments) y horarios (schedules)
-- Script para Sistema de Control Escolar ITSUR

-- ============================================
-- 1. TABLA: inscripciones
-- ============================================
-- Registra qué estudiantes están inscritos en qué grupos

CREATE TABLE IF NOT EXISTS `inscripciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alumno_id` INT UNSIGNED NOT NULL,
  `grupo_id` INT UNSIGNED NOT NULL,
  `ciclo` VARCHAR(20) NOT NULL,
  `estatus` ENUM('inscrito', 'retirado', 'completado') DEFAULT 'inscrito',
  `fecha_inscripcion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `alumno_idx` (`alumno_id`),
  KEY `grupo_idx` (`grupo_id`),
  KEY `estatus_idx` (`estatus`),
  UNIQUE KEY `unique_enrollment` (`alumno_id`, `grupo_id`, `ciclo`),
  CONSTRAINT `fk_insc_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_insc_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. TABLA: horarios
-- ============================================
-- Almacena los horarios de clase para cada grupo (días, horas, aulas)

CREATE TABLE IF NOT EXISTS `horarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `grupo_id` INT UNSIGNED NOT NULL,
  `dia_semana` ENUM('lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `aula` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grupo_idx` (`grupo_id`),
  KEY `dia_idx` (`dia_semana`),
  CONSTRAINT `fk_horario_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. MODIFICAR TABLA: grupos
-- ============================================
-- Agregar aula por defecto para cada grupo

ALTER TABLE grupos 
ADD COLUMN IF NOT EXISTS `aula_default` VARCHAR(20) DEFAULT NULL AFTER `ciclo`;

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Índice compuesto para búsquedas comunes de horarios
CREATE INDEX IF NOT EXISTS `idx_horario_grupo_dia` ON `horarios` (`grupo_id`, `dia_semana`);

-- Índice para búsquedas por ciclo
CREATE INDEX IF NOT EXISTS `idx_inscripcion_ciclo` ON `inscripciones` (`ciclo`);

-- ============================================
-- VERIFICACIÓN
-- ============================================

-- Mostrar información de las tablas creadas
SELECT 
    'inscripciones' AS tabla,
    COUNT(*) AS registros
FROM inscripciones
UNION ALL
SELECT 
    'horarios' AS tabla,
    COUNT(*) AS registros
FROM horarios;
