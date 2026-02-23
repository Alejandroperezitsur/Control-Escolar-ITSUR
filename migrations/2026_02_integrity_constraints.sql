-- Constraints de integridad académica y concurrencia

-- UNIQUE alumno-grupo en calificaciones
ALTER TABLE calificaciones
  ADD CONSTRAINT uniq_alumno_grupo
  UNIQUE (alumno_id, grupo_id);

-- Foreign keys estrictas en calificaciones
ALTER TABLE calificaciones
  ADD CONSTRAINT fk_calif_alumno
  FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
  ON DELETE CASCADE,
  ADD CONSTRAINT fk_calif_grupo
  FOREIGN KEY (grupo_id) REFERENCES grupos(id)
  ON DELETE CASCADE;

-- CHECK rango de calificación final
ALTER TABLE calificaciones
  ADD CONSTRAINT chk_final_range
  CHECK (final IS NULL OR (final BETWEEN 0 AND 100));

-- Tabla de prerrequisitos explícitos de materias
CREATE TABLE IF NOT EXISTS materias_prerrequisitos (
  materia_id INT NOT NULL,
  materia_requisito_id INT NOT NULL,
  PRIMARY KEY (materia_id, materia_requisito_id),
  CONSTRAINT fk_prerreq_materia
    FOREIGN KEY (materia_id) REFERENCES materias(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_prerreq_requisito
    FOREIGN KEY (materia_requisito_id) REFERENCES materias(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índice único lógico para un solo ciclo activo usando expresión
CREATE UNIQUE INDEX uniq_single_active_cycle
  ON ciclos_escolares ((CASE WHEN activo = 1 THEN 1 ELSE NULL END));

-- Trigger para garantizar que al activar un ciclo se desactiven los demás
DROP TRIGGER IF EXISTS trg_ciclos_single_active_before_insert;
DELIMITER $$
CREATE TRIGGER trg_ciclos_single_active_before_insert
BEFORE INSERT ON ciclos_escolares
FOR EACH ROW
BEGIN
  IF NEW.activo = 1 THEN
    UPDATE ciclos_escolares SET activo = 0 WHERE activo = 1;
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_ciclos_single_active_before_update;
DELIMITER $$
CREATE TRIGGER trg_ciclos_single_active_before_update
BEFORE UPDATE ON ciclos_escolares
FOR EACH ROW
BEGIN
  IF NEW.activo = 1 AND (OLD.activo IS NULL OR OLD.activo = 0) THEN
    UPDATE ciclos_escolares SET activo = 0 WHERE activo = 1 AND id <> NEW.id;
  END IF;
END$$
DELIMITER ;

-- Índices para concurrencia en consultas críticas
CREATE INDEX idx_calif_grupo ON calificaciones(grupo_id);
CREATE INDEX idx_calif_alumno ON calificaciones(alumno_id);
CREATE INDEX idx_grupo_ciclo ON grupos(ciclo_id);
CREATE INDEX idx_materia_carrera ON materias_carrera(carrera_id, semestre);

-- EXPLAIN antes/después para consultas críticas
EXPLAIN SELECT COUNT(*) FROM calificaciones WHERE grupo_id = 123;
EXPLAIN SELECT COUNT(*) FROM calificaciones c
  JOIN grupos gx ON gx.id = c.grupo_id
  WHERE c.alumno_id = 1
    AND c.final IS NOT NULL
    AND c.final >= 70
    AND gx.materia_id IN (1,2,3);

