-- Migration: Add fields to materias and calificaciones tables
-- Execute this BEFORE running the new seed

-- Add fields to materias table
ALTER TABLE `materias` ADD COLUMN IF NOT EXISTS `creditos` INT DEFAULT 5 COMMENT 'Créditos de la materia (4-6)';
ALTER TABLE `materias` ADD COLUMN IF NOT EXISTS `num_parciales` INT DEFAULT 2 COMMENT 'Número de parciales (2-5)';
ALTER TABLE `materias` ADD COLUMN IF NOT EXISTS `tipo` ENUM('Básica', 'Especialidad') DEFAULT 'Básica' COMMENT 'Tipo de materia';

-- Add parcial3,4,5 to calificaciones table
ALTER TABLE `calificaciones` ADD COLUMN IF NOT EXISTS `parcial3` DECIMAL(5,2) DEFAULT NULL COMMENT 'Calificación parcial 3';
ALTER TABLE `calificaciones` ADD COLUMN IF NOT EXISTS `parcial4` DECIMAL(5,2) DEFAULT NULL COMMENT 'Calificación parcial 4';
ALTER TABLE `calificaciones` ADD COLUMN IF NOT EXISTS `parcial5` DECIMAL(5,2) DEFAULT NULL COMMENT 'Calificación parcial 5';

-- Add index for faster queries
CREATE INDEX IF NOT EXISTS idx_materias_tipo ON materias(tipo);
CREATE INDEX IF NOT EXISTS idx_materias_creditos ON materias(creditos);
