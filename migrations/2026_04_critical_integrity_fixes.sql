-- ==========================================
-- MIGRACIÓN CRÍTICA: INTEGRIDAD ACADÉMICA Y SEGURIDAD
-- Ejecutar en producción antes de cualquier deploy
-- ==========================================

-- 1. BLOQUEO DE CALIFICACIONES POR PERIODO
ALTER TABLE ciclos_escolares 
ADD COLUMN IF NOT EXISTS calificaciones_bloqueadas TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS fecha_cierre_calificaciones DATETIME NULL,
ADD COLUMN IF NOT EXISTS motivo_cierre VARCHAR(255) NULL;

-- 2. CAMBIAR CASCADE A RESTRICT PARA EVITAR PÉRDIDA DE DATOS
-- Primero eliminar foreign keys existentes
ALTER TABLE calificaciones 
DROP FOREIGN KEY IF EXISTS fk_calificaciones_grupo,
DROP FOREIGN KEY IF EXISTS fk_calificaciones_alumno;

-- Recrear con RESTRICT
ALTER TABLE calificaciones
ADD CONSTRAINT fk_calificaciones_grupo
FOREIGN KEY (grupo_id) REFERENCES grupos(id) 
ON DELETE RESTRICT ON UPDATE CASCADE,
ADD CONSTRAINT fk_calificaciones_alumno
FOREIGN KEY (alumno_id) REFERENCES alumnos(id) 
ON DELETE RESTRICT ON UPDATE CASCADE;

-- 3. SOFT DELETE GLOBAL
ALTER TABLE alumnos 
ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER activo;

ALTER TABLE grupos 
ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER estado;

ALTER TABLE docentes 
ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER activo;

-- 4. ÍNDICES CRÍTICOS DE RENDIMIENTO
CREATE INDEX IF NOT EXISTS idx_alumnos_busqueda ON alumnos(apellido, nombre);
CREATE INDEX IF NOT EXISTS idx_alumnos_matricula ON alumnos(matricula);
CREATE INDEX IF NOT EXISTS idx_alumnos_active_not_deleted ON alumnos(activo, deleted_at);

CREATE UNIQUE INDEX IF NOT EXISTS idx_calificaciones_grupo_alumno_unique 
ON calificaciones(grupo_id, alumno_id);

CREATE INDEX IF NOT EXISTS idx_grupos_ciclo_materia ON grupos(ciclo_id, materia_id);
CREATE INDEX IF NOT EXISTS idx_grupos_active_not_deleted ON grupos(estado, deleted_at);

CREATE INDEX IF NOT EXISTS idx_periodos_activo ON ciclos_escolares(activo);

-- 5. TABLA DE AUDITORÍA ACADÉMICA
CREATE TABLE IF NOT EXISTS auditoria_academica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    valores_anteriores JSON NOT NULL,
    valores_nuevos JSON NOT NULL,
    motivo VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE INDEX IF NOT EXISTS idx_auditoria_tabla_registro 
ON auditoria_academica(tabla_afectada, registro_id);

-- 6. LIMPIEZA DE DATOS HUÉRFANOS (pre-migración)
UPDATE alumnos SET deleted_at = NOW() WHERE activo = 0 AND deleted_at IS NULL;
UPDATE grupos SET deleted_at = NOW() WHERE estado = 'cerrado' AND deleted_at IS NULL;
