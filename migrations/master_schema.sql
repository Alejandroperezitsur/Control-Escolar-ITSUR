-- MASTER SCHEMA SCRIPT
-- Combines all necessary tables and relationships

SET FOREIGN_KEY_CHECKS = 0;

-- DROP TABLES IF EXISTS (Clean Slate)
DROP TABLE IF EXISTS `calificaciones`;
DROP TABLE IF EXISTS `inscripciones`;
DROP TABLE IF EXISTS `horarios`;
DROP TABLE IF EXISTS `grupos`;
DROP TABLE IF EXISTS `materias_carrera`;
DROP TABLE IF EXISTS `alumnos`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `materias`;
DROP TABLE IF EXISTS `carreras`;

-- 1. CARRERAS
CREATE TABLE IF NOT EXISTS carreras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    clave VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    duracion_semestres INT DEFAULT 9,
    creditos_totales INT DEFAULT 240,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO carreras (nombre, clave, descripcion, duracion_semestres, creditos_totales) VALUES
('Ingeniería en Sistemas Computacionales', 'ISC', 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales aplicando las metodologías y tecnologías más recientes.', 9, 240),
('Ingeniería Industrial', 'II', 'Profesionista capaz de diseñar, implementar y mejorar sistemas de producción de bienes y servicios.', 9, 240),
('Ingeniería en Gestión Empresarial', 'IGE', 'Profesionista capaz de diseñar, crear y dirigir organizaciones competitivas con visión estratégica.', 9, 240),
('Ingeniería Electrónica', 'IE', 'Profesionista capaz de diseñar, desarrollar e innovar sistemas electrónicos para la solución de problemas en el sector productivo.', 9, 240),
('Ingeniería Mecatrónica', 'IM', 'Profesionista capaz de diseñar, construir y mantener sistemas mecatrónicos innovadores.', 9, 240),
('Ingeniería en Energías Renovables', 'IER', 'Profesionista capaz de diseñar, implementar y evaluar proyectos de energía sustentable.', 9, 240),
('Contador Público', 'CP', 'Profesionista capaz de diseñar, implementar y evaluar sistemas de información financiera.', 9, 240);

-- 2. USUARIOS
CREATE TABLE IF NOT EXISTS `usuarios` (
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

-- Admin user
INSERT INTO `usuarios` (`email`, `password`, `rol`, `activo`, `nombre`) VALUES
('admin@itsur.edu.mx', '$2y$10$iy.ePorFR/2j6ZvmJEFy1uMniVFux3/bIOlFsw.IrggPjURr8eCOG', 'admin', 1, 'Administrador ITSUR');

-- 3. MATERIAS
CREATE TABLE IF NOT EXISTS `materias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `clave` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. MATERIAS_CARRERA
CREATE TABLE IF NOT EXISTS materias_carrera (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT UNSIGNED NOT NULL,
    carrera_id INT NOT NULL,
    semestre TINYINT NOT NULL,
    tipo ENUM('basica', 'especialidad', 'residencia') DEFAULT 'basica',
    creditos INT DEFAULT 5,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE,
    INDEX idx_carrera_semestre (carrera_id, semestre),
    INDEX idx_materia (materia_id),
    UNIQUE KEY uk_materia_carrera_semestre (materia_id, carrera_id, semestre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. GRUPOS
CREATE TABLE IF NOT EXISTS `grupos` (
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

-- 6. ALUMNOS
CREATE TABLE IF NOT EXISTS `alumnos` (
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

-- 7. INSCRIPCIONES
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

-- 8. HORARIOS
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

-- 9. CALIFICACIONES
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alumno_id` INT UNSIGNED NOT NULL,
  `grupo_id` INT UNSIGNED NOT NULL,
  `parcial1` DECIMAL(5,2) DEFAULT NULL,
  `parcial2` DECIMAL(5,2) DEFAULT NULL,
  `final` DECIMAL(5,2) DEFAULT NULL,
  `promedio` DECIMAL(5,2) GENERATED ALWAYS AS (ROUND((COALESCE(`parcial1`,0)+COALESCE(`parcial2`,0)+COALESCE(`final`,0))/3,2)) STORED,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `alumno_idx` (`alumno_id`),
  KEY `grupo_idx` (`grupo_id`),
  CONSTRAINT `fk_cal_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cal_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
