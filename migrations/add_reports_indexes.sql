-- Migración: add_reports_indexes.sql
-- Fecha: 2025-11-23
-- Esta migración comprueba en information_schema si los índices existen
-- y los crea en caso de ausencia. Ejecutar sobre la base de datos objetivo.

-- Asegúrate de ejecutar esto contra la base de datos correcta, por ejemplo:
-- mysql -u root -proot control_escolar < migrations/add_reports_indexes.sql

SET @db := DATABASE();

DELIMITER $$

-- idx_grupos_ciclo
BEGIN
  DECLARE _cnt INT DEFAULT 0;
  SELECT COUNT(*) INTO _cnt FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'grupos' AND INDEX_NAME = 'idx_grupos_ciclo';
  IF _cnt = 0 THEN
    SET @sql = 'ALTER TABLE `grupos` ADD INDEX `idx_grupos_ciclo` (`ciclo`)';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- idx_grupos_materia
BEGIN
  DECLARE _cnt2 INT DEFAULT 0;
  SELECT COUNT(*) INTO _cnt2 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'grupos' AND INDEX_NAME = 'idx_grupos_materia';
  IF _cnt2 = 0 THEN
    SET @sql = 'ALTER TABLE `grupos` ADD INDEX `idx_grupos_materia` (`materia_id`)';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- idx_grupos_profesor
BEGIN
  DECLARE _cnt3 INT DEFAULT 0;
  SELECT COUNT(*) INTO _cnt3 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'grupos' AND INDEX_NAME = 'idx_grupos_profesor';
  IF _cnt3 = 0 THEN
    SET @sql = 'ALTER TABLE `grupos` ADD INDEX `idx_grupos_profesor` (`profesor_id`)';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- idx_calif_grupo
BEGIN
  DECLARE _cnt4 INT DEFAULT 0;
  SELECT COUNT(*) INTO _cnt4 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calificaciones' AND INDEX_NAME = 'idx_calif_grupo';
  IF _cnt4 = 0 THEN
    SET @sql = 'ALTER TABLE `calificaciones` ADD INDEX `idx_calif_grupo` (`grupo_id`)';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- idx_calif_alumno
BEGIN
  DECLARE _cnt5 INT DEFAULT 0;
  SELECT COUNT(*) INTO _cnt5 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calificaciones' AND INDEX_NAME = 'idx_calif_alumno';
  IF _cnt5 = 0 THEN
    SET @sql = 'ALTER TABLE `calificaciones` ADD INDEX `idx_calif_alumno` (`alumno_id`)';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- idx_calif_grupo_final (compuesto)
BEGIN
  DECLARE _cnt6 INT DEFAULT 0;
  SELECT COUNT(*) INTO _cnt6 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'calificaciones' AND INDEX_NAME = 'idx_calif_grupo_final';
  IF _cnt6 = 0 THEN
    SET @sql = 'ALTER TABLE `calificaciones` ADD INDEX `idx_calif_grupo_final` (`grupo_id`, `final`)';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- idx_usuarios_rol_activo
BEGIN
  DECLARE _cnt7 INT DEFAULT 0;
  SELECT COUNT(*) INTO _cnt7 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND INDEX_NAME = 'idx_usuarios_rol_activo';
  IF _cnt7 = 0 THEN
    SET @sql = 'ALTER TABLE `usuarios` ADD INDEX `idx_usuarios_rol_activo` (`rol`, `activo`)';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

-- idx_materias_nombre (prefijo 100) — ajusta longitud si tu columna es más corta
BEGIN
  DECLARE _cnt8 INT DEFAULT 0;
  SELECT COUNT(*) INTO _cnt8 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'materias' AND INDEX_NAME = 'idx_materias_nombre';
  IF _cnt8 = 0 THEN
    SET @sql = 'ALTER TABLE `materias` ADD INDEX `idx_materias_nombre` (`nombre`(100))';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$

DELIMITER ;

-- Fin de migración
SELECT 'Migración finalizada. Revisa índices creados con SHOW INDEX FROM <tabla>;' AS info;
