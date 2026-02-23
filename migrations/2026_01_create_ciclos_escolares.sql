-- Ciclos escolares reales y relación con grupos, inscripciones y calificaciones

CREATE TABLE IF NOT EXISTS ciclos_escolares (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ciclos_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE grupos
  ADD COLUMN IF NOT EXISTS ciclo_id INT UNSIGNED NULL AFTER profesor_id,
  ADD INDEX IF NOT EXISTS idx_grupos_ciclo_id (ciclo_id);

ALTER TABLE calificaciones
  ADD COLUMN IF NOT EXISTS ciclo_id INT UNSIGNED NULL AFTER grupo_id,
  ADD INDEX IF NOT EXISTS idx_calificaciones_ciclo_id (ciclo_id);

CREATE TABLE IF NOT EXISTS inscripciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT UNSIGNED NOT NULL,
    grupo_id INT UNSIGNED NOT NULL,
    ciclo_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_inscripcion_alumno_grupo_ciclo (alumno_id, grupo_id, ciclo_id),
    KEY idx_inscripciones_ciclo (ciclo_id),
    CONSTRAINT fk_insc_alumno FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_insc_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE grupos
  ADD CONSTRAINT IF NOT EXISTS fk_grupos_ciclo FOREIGN KEY (ciclo_id) REFERENCES ciclos_escolares(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE calificaciones
  ADD CONSTRAINT IF NOT EXISTS fk_calificaciones_ciclo FOREIGN KEY (ciclo_id) REFERENCES ciclos_escolares(id) ON DELETE RESTRICT ON UPDATE CASCADE;

INSERT INTO ciclos_escolares (nombre, fecha_inicio, fecha_fin, activo)
SELECT DISTINCT g.ciclo, 
       COALESCE(MIN(COALESCE(NULLIF(g.created_at,'0000-00-00'), CURRENT_DATE())), CURRENT_DATE()),
       COALESCE(MAX(COALESCE(NULLIF(g.created_at,'0000-00-00'), CURRENT_DATE())), CURRENT_DATE()),
       0
FROM grupos g
LEFT JOIN ciclos_escolares c ON c.nombre = g.ciclo
WHERE g.ciclo IS NOT NULL AND g.ciclo <> '' AND c.id IS NULL
GROUP BY g.ciclo;

UPDATE grupos g
JOIN ciclos_escolares c ON c.nombre = g.ciclo
SET g.ciclo_id = c.id
WHERE g.ciclo IS NOT NULL AND g.ciclo <> '' AND g.ciclo_id IS NULL;

UPDATE calificaciones c
JOIN grupos g ON g.id = c.grupo_id
SET c.ciclo_id = g.ciclo_id
WHERE c.ciclo_id IS NULL AND g.ciclo_id IS NOT NULL;

