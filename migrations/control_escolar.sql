

-- Tabla de usuarios (muy simple)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `matricula` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin','profesor') NOT NULL DEFAULT 'profesor',
  `activo` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de materias
CREATE TABLE IF NOT EXISTS `materias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `clave` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de grupos (relaciona materia con profesor)
CREATE TABLE IF NOT EXISTS `grupos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `materia_id` INT UNSIGNED NOT NULL,
  `profesor_id` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(50) NOT NULL,
  `ciclo` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `materia_idx` (`materia_id`),
  KEY `profesor_idx` (`profesor_id`),
  CONSTRAINT `fk_grupo_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_grupo_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Tabla de alumnos (simplificada)
CREATE TABLE IF NOT EXISTS `alumnos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `matricula` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(50) NOT NULL,
  `apellido` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `fecha_nac` DATE DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de calificaciones (vinculada a alumno, materia libre como texto)
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alumno_id` INT UNSIGNED NOT NULL,
  `grupo_id` INT UNSIGNED NOT NULL,
  `parcial1` DECIMAL(5,2) DEFAULT NULL,
  `parcial2` DECIMAL(5,2) DEFAULT NULL,
  `final` DECIMAL(5,2) DEFAULT NULL,
  -- Usar columna generada almacenada para promedio con ROUND y COALESCE
  `promedio` DECIMAL(5,2) GENERATED ALWAYS AS (ROUND((COALESCE(`parcial1`,0)+COALESCE(`parcial2`,0)+COALESCE(`final`,0))/3,2)) STORED,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `alumno_idx` (`alumno_id`),
  KEY `grupo_idx` (`grupo_id`),
  CONSTRAINT `fk_cal_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cal_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo (muy simples, contraseña en texto plano para simplicidad)
-- Se eliminaron los datos de ejemplo para evitar contraseñas en texto plano

-- Datos de ejemplo (seguro): usuarios y alumnos con contraseñas hasheadas
-- Admin (login interno por email)
INSERT INTO `usuarios` (`email`, `password`, `rol`, `activo`) VALUES
('admin@itsur.edu.mx', '$2y$10$iy.ePorFR/2j6ZvmJEFy1uMniVFux3/bIOlFsw.IrggPjURr8eCOG', 'admin', 1);

-- Profesores (login por matrícula)
INSERT INTO `usuarios` (`matricula`, `email`, `password`, `rol`, `activo`) VALUES
('S87654321', 'ana.torres@example.com', '$2y$10$JI9hKZGHTfoP4cqt68heEeqLS8Mzcmj8hXVM0wF.NzO3DL25tD7hC', 'profesor', 1),
('E11223344', 'carlos.ruiz@example.com', '$2y$10$JI9hKZGHTfoP4cqt68heEeqLS8Mzcmj8hXVM0wF.NzO3DL25tD7hC', 'profesor', 1);

-- Alumnos (login por matrícula)
INSERT INTO `alumnos` (`matricula`, `nombre`, `apellido`, `email`, `password`) VALUES
('S12345678', 'Juan', 'Pérez', 'juan@example.com', '$2y$10$8Q9l5j68ixdQmG9eAsvA8.PBKGHp0CpvEQk9ho/77NNaE6YxSqRWu'),
('I23456789', 'María', 'López', 'maria@example.com', '$2y$10$8Q9l5j68ixdQmG9eAsvA8.PBKGHp0CpvEQk9ho/77NNaE6YxSqRWu'),
('C34567890', 'Luis', 'García', 'luis@example.com', '$2y$10$8Q9l5j68ixdQmG9eAsvA8.PBKGHp0CpvEQk9ho/77NNaE6YxSqRWu');

-- Materias de ejemplo
INSERT INTO `materias` (`nombre`, `clave`) VALUES
('Programación I', 'INF101'),
('Bases de Datos', 'INF201'),
('Álgebra Lineal', 'MAT102');

-- Grupos de ejemplo usando SELECTs para resolver IDs
INSERT INTO `grupos` (`materia_id`, `profesor_id`, `nombre`, `ciclo`)
SELECT m.id, u.id, 'GPO-101-A', '2024A'
FROM materias m
JOIN usuarios u ON u.matricula = 'S87654321'
WHERE m.clave = 'INF101';

INSERT INTO `grupos` (`materia_id`, `profesor_id`, `nombre`, `ciclo`)
SELECT m.id, u.id, 'BD-201-B', '2024A'
FROM materias m
JOIN usuarios u ON u.matricula = 'E11223344'
WHERE m.clave = 'INF201';

-- Calificaciones de ejemplo (ligadas a grupo)
INSERT INTO `calificaciones` (`alumno_id`, `grupo_id`, `parcial1`, `parcial2`, `final`)
SELECT a.id, g.id, 85, 90, 88
FROM alumnos a
JOIN grupos g ON g.nombre = 'GPO-101-A' AND g.ciclo = '2024A'
WHERE a.matricula = 'S12345678';

INSERT INTO `calificaciones` (`alumno_id`, `grupo_id`, `parcial1`, `parcial2`, `final`)
SELECT a.id, g.id, 78, 82, 80
FROM alumnos a
JOIN grupos g ON g.nombre = 'BD-201-B' AND g.ciclo = '2024A'
WHERE a.matricula = 'I23456789';

INSERT INTO `calificaciones` (`alumno_id`, `grupo_id`, `parcial1`, `parcial2`, `final`)
SELECT a.id, g.id, 92, 88, 90
FROM alumnos a
JOIN grupos g ON g.nombre = 'GPO-101-A' AND g.ciclo = '2024A'
WHERE a.matricula = 'C34567890';
-- Fin del script simplificado

-- Ajustes para remover display width en tablas ya existentes (evita warnings 1681)
ALTER TABLE `usuarios`
  MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY `activo` TINYINT NOT NULL DEFAULT 1;

ALTER TABLE `alumnos`
  MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
-- Activar/desactivar alumnos: agregar columna 'activo' si no existe
-- Si tu MySQL soporta IF NOT EXISTS, puedes usar:
-- ALTER TABLE `alumnos` ADD COLUMN IF NOT EXISTS `activo` TINYINT NOT NULL DEFAULT 1;
-- En versiones sin IF NOT EXISTS, ejecuta manualmente este ALTER sólo una vez:
-- ALTER TABLE `alumnos` ADD COLUMN `activo` TINYINT NOT NULL DEFAULT 1;

ALTER TABLE `materias`
  MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `grupos`
  MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY `materia_id` INT UNSIGNED NOT NULL,
  MODIFY `profesor_id` INT UNSIGNED NOT NULL;

ALTER TABLE `calificaciones`
  MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY `alumno_id` INT UNSIGNED NOT NULL,
  MODIFY `grupo_id` INT UNSIGNED NOT NULL;

-- Ajustes de compatibilidad (ejecutar manualmente si tu base ya existía)
-- Profesores por matrícula: agrega columna en `usuarios` si no existe
-- ALTER TABLE `usuarios` ADD COLUMN `matricula` VARCHAR(20) DEFAULT NULL;
-- UNIQUE KEY en `matricula` permitirá una sola matrícula por usuario/profesor.

-- Alumnos con contraseña: agrega columna en `alumnos` si no existe
-- ALTER TABLE `alumnos` ADD COLUMN `password` VARCHAR(255) DEFAULT NULL;
