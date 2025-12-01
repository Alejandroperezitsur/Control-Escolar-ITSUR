-- ============================================================================
-- SCRIPT DE LIMPIEZA - Ejecutar ANTES de importar complete_real_system_schema.sql
-- ============================================================================
-- Copia este script completo y pégalo en la pestaña SQL de phpMyAdmin
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Borrar todas las tablas
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
DROP TABLE IF EXISTS `estadisticas_logs`;
DROP TABLE IF EXISTS `helper_primary_groups`;
DROP TABLE IF EXISTS `kardex`;
DROP TABLE IF EXISTS `planes`;
DROP TABLE IF EXISTS `prerequisitos`;
DROP TABLE IF EXISTS `temp_aulas_fix`;
DROP TABLE IF EXISTS `temp_horarios_template_fix`;

-- Borrar todas las vistas
DROP VIEW IF EXISTS `view_kardex`;
DROP VIEW IF EXISTS `view_carga_academica`;
DROP VIEW IF EXISTS `view_estadisticas_alumno`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Base de datos limpiada exitosamente. Ahora importa complete_real_system_schema.sql' as mensaje;
