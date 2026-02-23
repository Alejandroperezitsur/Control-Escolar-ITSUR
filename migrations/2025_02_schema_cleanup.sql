-- Limpieza final de DDL en runtime

-- Asegurar columna promedio en calificaciones
ALTER TABLE calificaciones
  ADD COLUMN IF NOT EXISTS promedio DECIMAL(5,2) NULL AFTER final;

-- Tabla materias_carrera (estructura consolidada)
CREATE TABLE IF NOT EXISTS materias_carrera (
  id INT AUTO_INCREMENT PRIMARY KEY,
  materia_id INT NOT NULL,
  carrera_id INT NOT NULL,
  semestre TINYINT NOT NULL,
  tipo ENUM('basica','especialidad','residencia') DEFAULT 'basica',
  creditos INT DEFAULT 5,
  UNIQUE KEY uk_materia_carrera_semestre (materia_id, carrera_id, semestre),
  KEY idx_materias_carrera_materia (materia_id),
  KEY idx_materias_carrera_carrera (carrera_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

