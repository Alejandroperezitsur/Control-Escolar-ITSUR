


-- Índices recomendados para reportes (ejecuta solo los que no existan)
ALTER TABLE grupos ADD INDEX idx_grupos_ciclo (ciclo);
ALTER TABLE grupos ADD INDEX idx_grupos_materia (materia_id);
ALTER TABLE grupos ADD INDEX idx_grupos_profesor (profesor_id);
ALTER TABLE calificaciones ADD INDEX idx_calif_grupo (grupo_id);
ALTER TABLE calificaciones ADD INDEX idx_calif_alumno (alumno_id);
ALTER TABLE calificaciones ADD INDEX idx_calif_grupo_final (grupo_id, final);
ALTER TABLE usuarios ADD INDEX idx_usuarios_rol_activo (rol, activo);
ALTER TABLE materias ADD INDEX idx_materias_nombre (nombre(100));
