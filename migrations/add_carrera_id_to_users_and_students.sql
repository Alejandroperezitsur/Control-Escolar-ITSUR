-- Migration: Add carrera_id to alumnos and usuarios
-- Description: Adds a foreign key to the carreras table for students and professors.

-- Add carrera_id to alumnos
ALTER TABLE alumnos ADD COLUMN carrera_id INT DEFAULT NULL;
ALTER TABLE alumnos ADD CONSTRAINT fk_alumnos_carrera FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE SET NULL ON UPDATE CASCADE;
CREATE INDEX idx_alumnos_carrera ON alumnos(carrera_id);

-- Add carrera_id to usuarios (only relevant for professors)
ALTER TABLE usuarios ADD COLUMN carrera_id INT DEFAULT NULL;
ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_carrera FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE SET NULL ON UPDATE CASCADE;
CREATE INDEX idx_usuarios_carrera ON usuarios(carrera_id);
