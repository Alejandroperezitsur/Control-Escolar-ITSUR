-- ============================================================================
-- SISTEMA DE CONTROL ESCOLAR UNIVERSITARIO - SCHEMA COMPLETO
-- Sistema Realista Inspirado en SICEnet
-- ============================================================================
-- Este schema incluye:
-- - Sistema de calificaciones por unidades (1-10)
-- - Kardex completo (historial académico)
-- - Carga académica con horarios
-- - Sistema de créditos
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================================
-- LIMPIEZA COMPLETA (EMPEZAR DE CERO)
-- ============================================================================

DROP TABLE IF EXISTS `calificaciones_unidades`;
DROP TABLE IF EXISTS `calificaciones_finales`;
DROP TABLE IF EXISTS `calificaciones`;
DROP TABLE IF EXISTS `inscripciones`;
DROP TABLE IF EXISTS `horarios`;
DROP TABLE IF EXISTS `grupos`;
DROP TABLE IF EXISTS `materias_carrera`;
DROP TABLE IF EXISTS `alumnos`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `materias`;
DROP TABLE IF EXISTS `carreras`;

DROP VIEW IF EXISTS `view_kardex`;
DROP VIEW IF EXISTS `view_carga_academica`;
DROP VIEW IF EXISTS `view_estadisticas_alumno`;

-- ============================================================================
-- TABLA: CARRERAS
-- ============================================================================

CREATE TABLE `carreras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(255) NOT NULL,
    `clave` VARCHAR(50) NOT NULL UNIQUE,
    `descripcion` TEXT,
    `duracion_semestres` INT DEFAULT 9,
    `creditos_totales` INT DEFAULT 240,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_activo` (`activo`),
    INDEX `idx_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `carreras` (`nombre`, `clave`, `descripcion`, `duracion_semestres`, `creditos_totales`) VALUES
('Ingeniería en Sistemas Computacionales', 'ISC', 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales aplicando las metodologías y tecnologías más recientes.', 9, 240),
('Ingeniería Industrial', 'II', 'Profesionista capaz de diseñar, implementar y mejorar sistemas de producción de bienes y servicios.', 9, 240),
('Ingeniería en Gestión Empresarial', 'IGE' , 'Profesionista capaz de diseñar, crear y dirigir organizaciones competitivas con visión estratégica.', 9, 240),
('Ingeniería Electrónica', 'IE', 'Profesionista capaz de diseñar, desarrollar e innovar sistemas electrónicos para la solución de problemas en el sector productivo.', 9, 240),
('Ingeniería Mecatrónica', 'IM', 'Profesionista capaz de diseñar, construir y mantener sistemas mecatrónicos innovadores.', 9, 240),
('Ingeniería en Energías Renovables', 'IER', 'Profesionista capaz de diseñar, implementar y evaluar proyectos de energía sustentable.', 9, 240),
('Contador Público', 'CP', 'Profesionista capaz de diseñar, implementar y evaluar sistemas de información financiera.', 9, 240);

-- ============================================================================
-- TABLA: USUARIOS (Admins y Profesores)
-- ============================================================================

CREATE TABLE `usuarios` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `matricula` VARCHAR(20) DEFAULT NULL,
    `nombre` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('admin','profesor') NOT NULL DEFAULT 'profesor',
    `activo` TINYINT NOT NULL DEFAULT 1,
    `carrera_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `matricula` (`matricula`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_usuarios_rol_activo` (`rol`, `activo`),
    CONSTRAINT `fk_usuarios_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario admin por defecto (contraseña: admin123)
INSERT INTO `usuarios` (`email`, `password`, `rol`, `activo`, `nombre`) VALUES
('admin@itsur.edu.mx', '$2y$10$iy.ePorFR/2j6ZvmJEFy1uMniVFux3/bIOlFsw.IrggPjURr8eCOG', 'admin', 1, 'Administrador ITSUR');

-- ============================================================================
-- TABLA: MATERIAS (con créditos, tipos, unidades)
-- ============================================================================

CREATE TABLE `materias` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `clave` VARCHAR(20) DEFAULT NULL,
    `num_parciales` INT NOT NULL DEFAULT 2,
    `num_unidades` TINYINT NOT NULL DEFAULT 10,
    `creditos` INT NOT NULL DEFAULT 5,
    `tipo` ENUM('basica', 'especialidad', 'residencia') DEFAULT 'basica',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `clave` (`clave`),
    INDEX `idx_creditos` (`creditos`),
    INDEX `idx_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: MATERIAS_CARRERA (Retícula)
-- ============================================================================

CREATE TABLE `materias_carrera` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `materia_id` INT UNSIGNED NOT NULL,
    `carrera_id` INT NOT NULL,
    `semestre` TINYINT NOT NULL,
    `tipo` ENUM('basica', 'especialidad', 'residencia') DEFAULT 'basica',
    `creditos` INT DEFAULT 5,
    FOREIGN KEY (`materia_id`) REFERENCES `materias`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id` ON DELETE CASCADE,
    INDEX `idx_carrera_semestre` (`carrera_id`, `semestre`),
    INDEX `idx_materia` (`materia_id`),
    UNIQUE KEY `uk_materia_carrera_semestre` (`materia_id`, `carrera_id`, `semestre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: GRUPOS
-- ============================================================================

CREATE TABLE `grupos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `materia_id` INT UNSIGNED NOT NULL,
    `profesor_id` INT UNSIGNED NOT NULL,
    `nombre` VARCHAR(50) NOT NULL,
    `ciclo` VARCHAR(20) DEFAULT NULL,
    `cupo` INT NOT NULL DEFAULT 30,
    `aula_default` VARCHAR(20) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `materia_idx` (`materia_id`),
    KEY `profesor_idx` (`profesor_id`),
    CONSTRAINT `fk_grupo_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_grupo_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: ALUMNOS
-- ============================================================================

CREATE TABLE `alumnos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `matricula` VARCHAR(20) NOT NULL,
    `nombre` VARCHAR(50) NOT NULL,
    `apellido` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `password` VARCHAR(255) DEFAULT NULL,
    `fecha_nac` DATE DEFAULT NULL,
    `foto` VARCHAR(255) DEFAULT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `carrera_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `matricula` (`matricula`),
    CONSTRAINT `fk_alumnos_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: INSCRIPCIONES (con semestre cursado)
-- ============================================================================

CREATE TABLE `inscripciones` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alumno_id` INT UNSIGNED NOT NULL,
    `grupo_id` INT UNSIGNED NOT NULL,
    `ciclo` VARCHAR(20) NOT NULL,
    `estatus` ENUM('inscrito', 'retirado', 'completado') DEFAULT 'inscrito',
    `semestre_cursado` TINYINT DEFAULT NULL COMMENT 'Semestre en el que el alumno estaba cuando se inscribió',
    `fecha_inscripcion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `alumno_idx` (`alumno_id`),
    KEY `grupo_idx` (`grupo_id`),
    KEY `estatus_idx` (`estatus`),
    INDEX `idx_semestre` (`semestre_cursado`),
    UNIQUE KEY `unique_enrollment` (`alumno_id`, `grupo_id`, `ciclo`),
    CONSTRAINT `fk_insc_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_insc_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: HORARIOS (con aulas específicas)
-- ============================================================================

CREATE TABLE `horarios` (
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

-- ============================================================================
-- NUEVA TABLA: CALIFICACIONES_UNIDADES
-- Almacena calificaciones por unidad (1-10)
-- ============================================================================

CREATE TABLE `calificaciones_unidades` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `inscripcion_id` INT UNSIGNED NOT NULL,
    `unidad_num` TINYINT NOT NULL COMMENT 'Número de unidad 1-10',
    `calificacion` DECIMAL(5,2) DEFAULT NULL COMMENT 'Calificación 0-100, NULL o 0 = pendiente',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_inscripcion_unidad` (`inscripcion_id`, `unidad_num`),
    INDEX `idx_inscripcion` (`inscripcion_id`),
    INDEX `idx_unidad` (`unidad_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Calificaciones por unidad para cada inscripción';

-- ============================================================================
-- NUEVA TABLA: CALIFICACIONES_FINALES
-- Resumen de calificaciones y estatus por inscripción
-- ============================================================================

CREATE TABLE `calificaciones_finales` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `inscripcion_id` INT UNSIGNED NOT NULL,
    `calificacion_final` DECIMAL(5,2) DEFAULT NULL COMMENT 'Calificación del examen final',
    `promedio_unidades` DECIMAL(5,2) DEFAULT NULL COMMENT 'Promedio de todas las unidades',
    `promedio_general` DECIMAL(5,2) DEFAULT NULL COMMENT 'Promedio general (unidades + final)',
    `estatus` ENUM('cursando', 'aprobado', 'reprobado', 'extraordinario', 'complemento') DEFAULT 'cursando',
    `tipo_acreditacion` ENUM('ordinario', 'extraordinario', 'complemento') DEFAULT 'ordinario',
    `periodo_acreditacion` VARCHAR(20) DEFAULT NULL COMMENT 'e.g., AGO-DIC 2022, ENE-JUN 2023',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_inscripcion` (`inscripcion_id`),
    INDEX `idx_estatus` (`estatus`),
    INDEX `idx_tipo_acreditacion` (`tipo_acreditacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Resumen de calificaciones finales y estatus por inscripción';

-- ============================================================================
-- VISTA: VIEW_KARDEX
-- Historial académico completo del alumno  
-- ============================================================================

CREATE OR REPLACE VIEW `view_kardex` AS
SELECT 
    a.`id` as `alumno_id`,
    a.`matricula`,
    a.`nombre`,
    a.`apellido`,
    a.`carrera_id`,
    c.`nombre` as `carrera_nombre`,
    i.`id` as `inscripcion_id`,
    m.`id` as `materia_id`,
    m.`nombre` as `materia_nombre`,
    m.`clave` as `materia_clave`,
    m.`creditos`,
    g.`nombre` as `grupo`,
    g.`ciclo`,
    COALESCE(mc.`semestre`, i.`semestre_cursado`, 1) as `semestre`,
    cf.`promedio_unidades`,
    cf.`calificacion_final`,
    cf.`promedio_general`,
    cf.`estatus`,
    cf.`tipo_acreditacion`,
    cf.`periodo_acreditacion`,
    CASE 
        WHEN cf.`promedio_general` IS NULL THEN 'Sin Calificar'
        WHEN cf.`promedio_general` >= 90 THEN 'Excelente'
        WHEN cf.`promedio_general` >= 80 THEN 'Notable'
        WHEN cf.`promedio_general` >= 70 THEN 'Bueno'
        WHEN cf.`promedio_general` >= 60 THEN 'Suficiente'
        ELSE 'No Acreditado'
    END as `nivel_desempeno`,
    i.`fecha_inscripcion`,
    i.`estatus` as `estatus_inscripcion`
FROM `alumnos` a
JOIN `inscripciones` i ON i.`alumno_id` = a.`id`
JOIN `grupos` g ON g.`id` = i.`grupo_id`
JOIN `materias` m ON m.`id` = g.`materia_id`
LEFT JOIN `carreras` c ON c.`id` = a.`carrera_id`
LEFT JOIN `materias_carrera` mc ON mc.`materia_id` = m.`id` AND mc.`carrera_id` = a.`carrera_id`
LEFT JOIN `calificaciones_finales` cf ON cf.`inscripcion_id` = i.`id`
ORDER BY a.`id`, `semestre`, i.`fecha_inscripcion`;

-- ============================================================================
-- VISTA: VIEW_CARGA_ACADEMICA
-- Carga académica actual con horarios completos
-- ============================================================================

CREATE OR REPLACE VIEW `view_carga_academica` AS
SELECT 
    a.`id` as `alumno_id`,
    a.`matricula`,
    a.`nombre` as `alumno_nombre`,
    a.`apellido` as `alumno_apellido`,
    m.`id` as `materia_id`,
    m.`nombre` as `materia_nombre`,
    m.`clave` as `materia_clave`,
    m.`creditos`,
    g.`id` as `grupo_id`,
    g.`nombre` as `grupo_nombre`,
    g.`ciclo`,
    u.`id` as `profesor_id`,
    u.`nombre` as `profesor_nombre`,
    GROUP_CONCAT(
        DISTINCT CONCAT(
            UPPER(SUBSTRING(h.`dia_semana`, 1, 1)),
            LOWER(SUBSTRING(h.`dia_semana`, 2)),
            ' ',
            TIME_FORMAT(h.`hora_inicio`, '%H:%i'),
            '-',
            TIME_FORMAT(h.`hora_fin`, '%H:%i'),
            ' ',
            COALESCE(h.`aula`, 'Sin asignar')
        )
        ORDER BY FIELD(h.`dia_semana`, 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado')
        SEPARATOR '; '
    ) as `horarios`,
    i.`semestre_cursado`
FROM `alumnos` a
JOIN `inscripciones` i ON i.`alumno_id` = a.`id` AND i.`estatus` = 'inscrito'
JOIN `grupos` g ON g.`id` = i.`grupo_id`
JOIN `materias` m ON m.`id` = g.`materia_id`
JOIN `usuarios` u ON u.`id` = g.`profesor_id`
LEFT JOIN `horarios` h ON h.`grupo_id` = g.`id`
GROUP BY a.`id`, m.`id`, g.`id`, u.`id`, i.`semestre_cursado`;

-- ============================================================================
-- VISTA: VIEW_ESTADISTICAS_ALUMNO
-- Estadísticas académicas para resumen del Kardex
-- ============================================================================

CREATE OR REPLACE VIEW `view_estadisticas_alumno` AS
SELECT 
    a.`id` as `alumno_id`,
    a.`matricula`,
    a.`nombre`,
    a.`apellido`,
    a.`carrera_id`,
    c.`nombre` as `carrera_nombre`,
    c.`creditos_totales` as `creditos_requeridos`,
    COUNT(DISTINCT i.`id`) as `total_materias_cursadas`,
    SUM(CASE WHEN cf.`estatus` = 'aprobado' THEN 1 ELSE 0 END) as `materias_aprobadas`,
    SUM(CASE WHEN cf.`estatus` = 'reprobado' THEN 1 ELSE 0 END) as `materias_reprobadas`,
    SUM(CASE WHEN cf.`estatus` IN ('aprobado') THEN m.`creditos` ELSE 0 END) as `creditos_completados`,
    ROUND(
        (SUM(CASE WHEN cf.`estatus` = 'aprobado' THEN m.`creditos` ELSE 0 END) / c.`creditos_totales`) * 100,
        2
    ) as `porcentaje_avance`,
    ROUND(
        AVG(CASE WHEN cf.`promedio_general` IS NOT NULL AND cf.`estatus` IN ('aprobado', 'reprobado') 
            THEN cf.`promedio_general` 
            ELSE NULL 
        END),
        2
    ) as `promedio_general`,
    MAX(COALESCE(i.`semestre_cursado`, mc.`semestre`, 1)) as `semestre_actual`
FROM `alumnos` a
LEFT JOIN `carreras` c ON c.`id` = a.`carrera_id`
LEFT JOIN `inscripciones` i ON i.`alumno_id` = a.`id`
LEFT JOIN `grupos` g ON g.`id` = i.`grupo_id`
LEFT JOIN `materias` m ON m.`id` = g.`materia_id`
LEFT JOIN `materias_carrera` mc ON mc.`materia_id` = m.`id` AND mc.`carrera_id` = a.`carrera_id`
LEFT JOIN `calificaciones_finales` cf ON cf.`inscripcion_id` = i.`id`
GROUP BY a.`id`, c.`id`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SCHEMA COMPLETO
-- ============================================================================

SELECT 'Schema created successfully!' as status;
SELECT 'Ready for realistic data generation' as next_step;
